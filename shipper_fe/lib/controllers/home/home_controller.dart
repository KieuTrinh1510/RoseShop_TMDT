// lib/controllers/home_controller.dart
// Controller màn hình Home của shipper.
// Quản lý state: thông tin shipper, online/offline, số đơn, COD, thông báo.
// Screen chỉ ListenableBuilder + gọi action, không tự gọi ApiService.

import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';

class HomeController extends ChangeNotifier {
  bool loading = true;
  String? error;

  /// Dữ liệu /home trả về (Map gốc từ backend).
  Map<String, dynamic>? data;

  /// Chặn bấm toggle liên tục trong lúc request đang bay.
  bool toggling = false;

  // Chống notifyListeners() sau khi dispose (request còn bay mà tab đã đóng).
  bool _disposed = false;

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  @override
  void notifyListeners() {
    if (!_disposed) super.notifyListeners();
  }

  // ── Getter đọc dữ liệu (đều có fallback an toàn) ───────────────
  String get tenNhanVien => _str(_dig(['shipper', 'ten_nhan_vien']));
  String get chuCaiDau {
    final s = _str(_dig(['shipper', 'chu_cai_dau']));
    return s.isEmpty ? '?' : s;
  }

  int get maNhanVien => _int(_dig(['shipper', 'ma_nhan_vien']));

  bool get isOnline => _dig(['trang_thai', 'is_online']) == true;
  String get trangThaiHoatDong =>
      _str(_dig(['trang_thai', 'trang_thai_hoat_dong']));

  int get soDonHienTai => _int(_dig(['don_hang', 'so_don_hien_tai']));
  int get gioiHanDon => _int(_dig(['don_hang', 'gioi_han_don']), fallback: 10);
  bool get isFull => _dig(['don_hang', 'is_full']) == true;

  // Giữ nguyên: đọc thẳng từ backend
  double get codCanNop => _num(_dig(['cod', 'tien_can_nop']));
  int get codSoDon => _int(_dig(['cod', 'so_don']));

  int get thongBaoChuaDoc => _int(_dig(['thong_bao', 'chua_doc']));

  // ── Actions ────────────────────────────────────────────────────

  /// Lần đầu: loading=true → Skeleton.
  /// Pull-to-refresh: refresh=true → giữ data cũ, chỉ hiện vòng refresh.
  Future<void> load({bool refresh = false}) async {
    if (!refresh) loading = true;
    error = null;
    notifyListeners();

    try {
      final res = await ApiService.get('/home');
      if (res is Map) data = Map<String, dynamic>.from(res);
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

  /// Optimistic update: UI đổi ngay, gọi API, lỗi thì revert về trạng thái cũ.
  /// Backend vẫn là nguồn dữ liệu cuối cùng — sau khi API trả về, ta đồng bộ
  /// lại theo response.
  /// Trả về null nếu thành công; ngược lại trả message lỗi cho SnackBar.
  Future<String?> toggleOnline(bool online) async {
    if (toggling) return null;
    toggling = true;

    final previous = data?['trang_thai'];

    // 1) Optimistic: đổi UI ngay
    data?['trang_thai'] = {
      'trang_thai_hoat_dong': online ? 'ONLINE' : 'OFFLINE',
      'is_online': online,
    };
    notifyListeners();

    try {
      final res = await ApiService.put(
        '/status/online',
        body: {'trang_thai': online ? 'ONLINE' : 'OFFLINE'},
      );
      // 2) Đồng bộ theo backend (nguồn cuối cùng)
      if (res is Map) {
        final tt = res['trang_thai_hoat_dong']?.toString();
        data?['trang_thai'] = {
          'trang_thai_hoat_dong': tt,
          'is_online': tt == 'ONLINE',
        };
      }
      toggling = false;
      notifyListeners();
      return null;
    } on ApiException catch (e) {
      data?['trang_thai'] = previous; // revert
      toggling = false;
      notifyListeners();
      return e.message;
    } catch (e) {
      data?['trang_thai'] = previous;
      toggling = false;
      notifyListeners();
      return 'Lỗi kết nối: $e';
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

  static String _str(dynamic v) => v?.toString() ?? '';
  static int _int(dynamic v, {int fallback = 0}) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v?.toString() ?? '') ?? fallback;
  }

  static double _num(dynamic v) {
    if (v is num) return v.toDouble();
    return double.tryParse(v?.toString() ?? '') ?? 0;
  }

  double get tienCod => _num(_dig(['cod', 'tien_cod']));
  double get tienPhat => _num(_dig(['cod', 'tien_phat']));
  String get codPhu {
    final cod = tienCod;
    final phat = tienPhat;
    if (cod == 0 && phat == 0) return 'Chưa có đơn nào cần nộp';
    if (phat == 0) return '$codSoDon đơn • Chạm để xem chi tiết';
    return '$codSoDon đơn + phạt • Chạm để xem';
  }
}
