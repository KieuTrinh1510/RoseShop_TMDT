// lib/controllers/orders/order_detail_controller.dart
// Controller chi tiết đơn hàng: load + hành động giao hàng + các đồng hồ nghiệp vụ
// + luồng báo giao thất bại (2 giai đoạn lỗi khách hàng).
//
// Nguyên tắc: BACKEND là nguồn sự thật. Timer local chỉ để hiển thị (đếm theo mốc
// hết hạn tuyệt đối nên không trôi khi app bị tạm dừng); mỗi lần mở lại / resume /
// kéo refresh, màn hình gọi lại failure-check để đồng bộ.

import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';

/// Gọi sau khi finalize xong. [trangThai]: 'DELIVERED' | 'CANCELLED' | 'SYNC'
/// ('SYNC' = request trước đã xử lý, chỉ cần tải lại màn hình).
typedef FinalizedCallback = void Function(String trangThai);
typedef FailureMessageCallback = void Function(String message);

class OrderDetailController extends ChangeNotifier {
  bool loading = true;
  bool acting = false; // đang gọi pickup/arrived/delivered
  String? error;
  Map<String, dynamic>? data;

  int _id = 0;
  Timer? _ticker;
  Timer? _failureTicker;
  int _remaining = 0; // giây còn lại — tick local mỗi 1s

  // ── Luồng báo thất bại ──
  Map<String, dynamic>? failureStatus; // response failure-check (camelCase)
  bool failureLoading = false;
  bool failureActing = false; // đang gọi report-failure
  bool failureFinalizing = false; // đang gọi finalize-customer-failure
  int failureRemaining =
      0; // giây còn lại của lần chờ đang chạy (lần 1 hoặc lần 2)

  FinalizedCallback? onFinalized;
  FailureMessageCallback? onFailureMessage;

  /// Order đã được gọi finalize (tự động) — chống gọi lặp cho CÙNG 1 đơn.
  int _finalizeCalledId = -1;

  /// Tăng khi đổi order: mọi response của order cũ bị bỏ qua.
  int _epoch = 0;

  /// Tăng mỗi lần gọi failure-check: chỉ response mới nhất được áp dụng.
  int _statusSeq = 0;

  DateTime? _failureDeadline;

  // Chống gọi /overdue nhiều lần cho CÙNG 1 đơn.
  int _idDaGoiOverdue = -1;

  bool _disposed = false;

  @override
  void dispose() {
    _disposed = true;
    onFinalized = null;
    onFailureMessage = null;
    _ticker?.cancel();
    _failureTicker?.cancel();
    super.dispose();
  }

  @override
  void notifyListeners() {
    if (!_disposed) super.notifyListeners();
  }

  // ── Getters cho UI ─────────────────────────────────────────────
  String get maHienThi => _str(_dig(['ma_hien_thi']));
  String get trangThai => _str(_dig(['trang_thai']));
  String get nhanTrangThai => _str(_dig(['nhan_trang_thai']));
  bool get laHoaTuoi => _dig(['la_hoa_tuoi']) == true;

  Map<String, dynamic> get dongHo => _map(_dig(['dong_ho']));
  bool get coDongHo => dongHo['co_dong_ho'] == true;
  bool get dongHoCanhBao => dongHo['canh_bao'] == true;
  bool get daPhatTre => dongHo['da_phat_tre'] == true;
  int get remaining => _remaining;
  String get lyDoKhongCoDongHo => _str(dongHo['ly_do']);

  Map<String, dynamic> get hanhDong => _map(_dig(['hanh_dong']));
  bool get coTheNhanHang => hanhDong['co_the_nhan_hang'] == true;
  bool get coTheBaoDenNoi => hanhDong['co_the_bao_den_noi'] == true;
  bool get coTheGiaoXong => hanhDong['co_the_giao_xong'] == true;

  Map<String, dynamic> get coTheGuiLoi => _map(failureStatus?['coTheGuiLoi']);
  bool get coTheBaoLoiShipper => coTheGuiLoi['SHIPPER'] == true;
  bool get coTheBaoLoiKhachHang => coTheGuiLoi['CUSTOMER'] == true;
  bool get daDenNoi => failureStatus?['daArrived'] == true;
  bool get duoi10Phut => failureStatus?['duoi10Phut'] == true;
  bool get coTheHoanThanh => failureStatus?['coTheHoanThanh'] == true;
  bool get coTheFinalize => failureStatus?['coTheFinalize'] == true;
  String get trangThaiBaoLoiKhach =>
      _str(failureStatus?['trangThaiBaoLoiKhach']);

  /// Đã có báo cáo lỗi khách đang chờ (đơn vẫn SHIPPING, khách còn có thể nhận hàng).
  bool get dangChoKhach => trangThaiBaoLoiKhach == 'DANG_CHO_KHACH';

  // ── Gắn order / reset ──────────────────────────────────────────
  /// Gắn controller với 1 đơn. Đổi sang đơn khác → reset toàn bộ timer + trạng thái cũ.
  /// Không notify (được gọi từ initState / load).
  void bindOrder(int id) {
    if (id == _id) return;
    _epoch++;
    _statusSeq++;
    _ticker?.cancel();
    _ticker = null;
    _failureTicker?.cancel();
    _failureTicker = null;
    _failureDeadline = null;
    _remaining = 0;
    data = null;
    error = null;
    failureStatus = null;
    failureLoading = false;
    failureActing = false;
    failureFinalizing = false;
    failureRemaining = 0;
    _finalizeCalledId = -1;
    _id = id;
  }

  /// Đơn không còn SHIPPING (DELIVERED / CANCELLED...): xóa trạng thái báo lỗi + timer.
  void resetFailure() {
    _statusSeq++; // response failure-check đang bay sẽ bị bỏ qua
    _failureTicker?.cancel();
    _failureTicker = null;
    _failureDeadline = null;
    failureStatus = null;
    failureRemaining = 0;
    notifyListeners();
  }

  // ── Load ────────────────────────────────────────────────────────
  Future<void> load(int id, {bool refresh = false}) async {
    bindOrder(id);
    if (!refresh) loading = true;
    error = null;
    notifyListeners();

    try {
      final res = await ApiService.get('/orders/$id');
      data = Map<String, dynamic>.from(res as Map);
      _initTimer();
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Lỗi kết nối: $e';
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  // ── 3 nút hành động (giữ nguyên) ───────────────────────────────
  Future<String?> pickup() => _act('/pickup');
  Future<String?> arrived() => _act('/arrived');
  Future<String?> delivered() => _act('/delivered');

  // ── failure-check ──────────────────────────────────────────────
  /// Lấy trạng thái báo lỗi từ backend và đồng bộ timer theo server.
  /// [silent] = true: không bật cờ loading, lỗi mạng thì GIỮ trạng thái cũ (không mất trạng thái chờ).
  Future<void> loadFailureStatus(int id, {bool silent = false}) async {
    if (id <= 0) return;
    bindOrder(id);
    final epoch = _epoch;
    final seq = ++_statusSeq;
    if (!silent) {
      failureLoading = true;
      notifyListeners();
    }
    try {
      final res = await ApiService.getFailureCheck(id.toString());
      if (epoch != _epoch || seq != _statusSeq) return; // đã có request mới hơn
      failureStatus = Map<String, dynamic>.from(res as Map);
      _initFailureTimer();
      _maybeAutoFinalize();
    } on ApiException catch (e) {
      if (epoch == _epoch && seq == _statusSeq && !silent) {
        onFailureMessage?.call(e.message);
      }
    } catch (e) {
      if (epoch == _epoch && seq == _statusSeq && !silent) {
        onFailureMessage?.call('Lỗi kết nối: $e');
      }
    } finally {
      if (epoch == _epoch) {
        failureLoading = false;
        notifyListeners();
      }
    }
  }

  // ── Báo lỗi ────────────────────────────────────────────────────
  /// Trả về null nếu thành công, ngược lại là message để hiển thị.
  /// KHÔNG xóa failureStatus: sau lỗi CUSTOMER đơn vẫn SHIPPING và đang chờ khách.
  Future<String?> reportFailure({
    required String loaiLoi,
    required String lyDo,
    String? ghiChu,
  }) async {
    if (_id == 0) return null;
    if (failureActing || failureFinalizing) {
      return 'Đang xử lý yêu cầu trước, vui lòng đợi giây lát.';
    }
    final id = _id;
    final epoch = _epoch;
    failureActing = true;
    notifyListeners();
    try {
      await ApiService.reportFailure(
        id.toString(),
        loaiLoi: loaiLoi,
        lyDo: lyDo,
        ghiChu: ghiChu,
      );
      // Đồng bộ lại ngay từ server → khởi động timer lần hai theo giayConLaiLan2.
      if (epoch == _epoch) await loadFailureStatus(id, silent: true);
      return null;
    } on ApiException catch (e) {
      return e.message; // 400/404/409/422/500: màn hình sẽ tải lại để đồng bộ
    } catch (e) {
      return 'Lỗi kết nối: $e';
    } finally {
      if (epoch == _epoch) {
        failureActing = false;
        notifyListeners();
      }
    }
  }

  // ── Finalize (giai đoạn 2) ─────────────────────────────────────
  /// Gọi khi hết 10 phút chờ lần hai. Chống gọi trùng bằng [failureFinalizing].
  /// Trả về null nếu không có lỗi cần hiển thị.
  ///  - Thành công (DELIVERED / CANCELLED): tải lại failure-check rồi gọi [onFinalized].
  ///  - 409 / mã "đã xử lý": tải lại trạng thái, KHÔNG coi là lỗi.
  ///  - Lỗi mạng: trả message, cho phép thử lại.
  Future<String?> finalizeCustomerFailure() async {
    if (failureFinalizing || _id == 0) return null;
    final id = _id;
    final epoch = _epoch;
    _finalizeCalledId = id;
    failureFinalizing = true;
    notifyListeners();

    String? error;
    String? ketQua;
    var dongBo = false; // cần tải lại từ server

    try {
      final res = await ApiService.finalizeCustomerFailure(id.toString());
      ketQua = _str(_map(res)['trang_thai']);
      if (ketQua.isEmpty) dongBo = true;
    } on ApiException catch (e) {
      dongBo = true;
      final choHetHan = e.code == 'WAIT_NOT_EXPIRED';
      if (!e.isAlreadyHandled && !choHetHan) error = e.message;
    } catch (e) {
      // Lỗi mạng: không chắc server đã xử lý hay chưa → cho phép retry an toàn
      // (finalize idempotent). Lần resync / timer kế tiếp sẽ thử lại.
      _finalizeCalledId = -1;
      error = 'Lỗi kết nối: $e';
    }

    if (epoch != _epoch) return null; // đã đổi order, bỏ kết quả cũ

    failureFinalizing = false;
    notifyListeners();

    if (ketQua != null || dongBo) {
      await loadFailureStatus(id, silent: true);
      if (epoch != _epoch) return null;
      final r = (ketQua == 'DELIVERED' || ketQua == 'CANCELLED')
          ? ketQua!
          : 'SYNC';
      onFinalized?.call(r);
    }
    return error;
  }

  /// Sau khi tải failure-check: nếu server báo đã được phép finalize (vd mở lại app khi
  /// đã hết giờ) thì tự gọi đúng 1 lần cho order này.
  void _maybeAutoFinalize() {
    if (coTheFinalize && !failureFinalizing && _finalizeCalledId != _id) {
      unawaited(_autoFinalize());
    }
  }

  Future<void> _autoFinalize() async {
    if (failureFinalizing || _id == 0) return;
    final err = await finalizeCustomerFailure();
    if (err != null && !_disposed) onFailureMessage?.call(err);
  }

  // ── Timer lần 1 / lần 2 (chỉ hiển thị) ─────────────────────────
  void _initFailureTimer() {
    _failureTicker?.cancel();
    _failureTicker = null;
    _failureDeadline = null;

    final st = failureStatus;
    if (st == null || _str(st['trangThai']) != 'SHIPPING') {
      failureRemaining = 0;
      notifyListeners();
      return;
    }

    int secs;
    if (dangChoKhach) {
      secs = _int(st['giayConLaiLan2']); // lần chờ thứ hai
    } else if (st['daArrived'] == true && st['duoi10Phut'] == true) {
      secs = _int(st['giayConLai']); // lần chờ thứ nhất
    } else {
      secs = 0;
    }

    failureRemaining = secs;
    if (secs > 0) {
      _failureDeadline = DateTime.now().add(Duration(seconds: secs));
      _failureTicker = Timer.periodic(
        const Duration(seconds: 1),
        (_) => _onFailureTick(),
      );
    }
    notifyListeners();
  }

  void _onFailureTick() {
    final dl = _failureDeadline;
    if (_disposed || dl == null) {
      _failureTicker?.cancel();
      _failureTicker = null;
      return;
    }
    var left = (dl.difference(DateTime.now()).inMilliseconds / 1000).ceil();
    if (left < 0) left = 0;
    if (left != failureRemaining) {
      failureRemaining = left;
      notifyListeners();
    }
    if (left <= 0) {
      _failureTicker?.cancel();
      _failureTicker = null;
      _failureDeadline = null;
      _onFailureTimerEnd();
    }
  }

  void _onFailureTimerEnd() {
    if (dangChoKhach) {
      // Hết lần chờ thứ hai → finalize (backend vẫn tự kiểm tra lại).
      unawaited(_autoFinalize());
    } else {
      // Hết lần chờ thứ nhất → hỏi server để mở nút "lỗi khách hàng".
      unawaited(loadFailureStatus(_id, silent: true));
    }
  }

  Future<String?> _act(String action) async {
    if (acting || _id == 0) return null;
    acting = true;
    notifyListeners();
    try {
      await ApiService.post('/orders/$_id$action');
      await load(_id, refresh: true);
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (e) {
      return 'Lỗi kết nối: $e';
    } finally {
      acting = false;
      notifyListeners();
    }
  }

  // ── Đồng hồ 3h (giữ nguyên) ────────────────────────────────────
  void _initTimer() {
    _ticker?.cancel();
    _ticker = null;
    if (!coDongHo) return;

    _remaining = _int(dongHo['remaining']);

    final daTrigger = daPhatTre || _idDaGoiOverdue == _id;

    if (dongHo['is_timeout'] == true && !daTrigger) {
      _idDaGoiOverdue = _id;
      _goiOverdue();
      return;
    }

    if (dongHo['is_timeout'] == true) return;

    _ticker = Timer.periodic(const Duration(seconds: 1), (_) {
      if (_remaining > 0) {
        _remaining--;
        notifyListeners();
      } else if (_idDaGoiOverdue != _id) {
        _idDaGoiOverdue = _id;
        _ticker?.cancel();
        _ticker = null;
        _goiOverdue();
      }
    });
  }

  Future<void> _goiOverdue() async {
    if (_id == 0) return;
    try {
      await ApiService.post('/orders/$_id/overdue');
      await load(_id, refresh: true);
    } catch (_) {
      _idDaGoiOverdue = -1;
    }
  }

  // ── Helpers ────────────────────────────────────────────────────
  dynamic _dig(List<String> path) {
    dynamic cur = data;
    for (final k in path) {
      if (cur is Map) {
        cur = cur[k];
      } else {
        return null;
      }
    }
    return cur;
  }

  static Map<String, dynamic> _map(dynamic v) =>
      v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};

  static String _str(dynamic v) => v?.toString() ?? '';

  static int _int(dynamic v, {int fallback = 0}) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v?.toString() ?? '') ?? fallback;
  }
}
