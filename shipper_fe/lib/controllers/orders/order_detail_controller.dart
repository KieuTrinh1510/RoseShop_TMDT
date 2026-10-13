// lib/controllers/orders/order_detail_controller.dart
// Controller chi tiết đơn hàng: load + 3 nút hành động + đồng hồ 3h + xử lý quá hạn.

import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';

class OrderDetailController extends ChangeNotifier {
  bool loading = true;
  bool acting = false; // đang gọi pickup/arrived/delivered
  String? error;
  Map<String, dynamic>? data;

  int _id = 0;
  Timer? _ticker;
  int _remaining = 0; // giây còn lại — tick local mỗi 1s

  // Chống gọi /overdue nhiều lần cho CÙNG 1 đơn.
  int _idDaGoiOverdue = -1;

  bool _disposed = false;

  @override
  void dispose() {
    _disposed = true;
    _ticker?.cancel();
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

  // ── Load ────────────────────────────────────────────────────────
  Future<void> load(int id, {bool refresh = false}) async {
    _id = id;
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

  // ── 3 nút hành động ────────────────────────────────────────────
  Future<String?> pickup() => _act('/pickup');
  Future<String?> arrived() => _act('/arrived');
  Future<String?> delivered() => _act('/delivered');

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

  // ── Timer ───────────────────────────────────────────────────────
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
