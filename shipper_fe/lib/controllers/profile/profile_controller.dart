// lib/controllers/profile/profile_controller.dart
// Controller tab Hồ sơ: info + đổi tên TK + đổi mật khẩu + đăng xuất.

import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';

class ProfileController extends ChangeNotifier {
  bool loading = true;
  bool saving = false;
  String? error;
  Map<String, dynamic>? data;

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

  String get maNhanVien => _str(data?['ma_nhan_vien']);
  String get tenNhanVien => _str(data?['ten_nhan_vien']);
  String get chuCaiDau {
    final s = _str(data?['chu_cai_dau']);
    return s.isEmpty ? '?' : s;
  }

  String get soDienThoai => _str(data?['so_dien_thoai']);
  String get email => _str(data?['email']);
  String get chucVu => _str(data?['chuc_vu']);
  String get tenDangNhap => _str(data?['ten_dang_nhap']);
  String get vaiTro => _str(data?['vai_tro']);
  String get trangThaiHoatDong => _str(data?['trang_thai_hoat_dong']);

  Future<void> load({bool refresh = false}) async {
    if (!refresh) loading = true;
    error = null;
    notifyListeners();

    try {
      final res = await ApiService.get('/profile');
      data = Map<String, dynamic>.from(res as Map);
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

  Future<void> refresh() => load(refresh: true);

  Future<String?> updateInfo({
    String? tenNhanVien,
    String? soDienThoai,
    String? email,
  }) async {
    if (saving) return null;
    saving = true;
    notifyListeners();
    try {
      final body = <String, dynamic>{};
      if (tenNhanVien != null) body['ten_nhan_vien'] = tenNhanVien;
      if (soDienThoai != null) body['so_dien_thoai'] = soDienThoai;
      if (email != null) body['email'] = email;

      final res = await ApiService.put('/profile', body: body);
      if (res is Map) data = Map<String, dynamic>.from(res);
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (e) {
      return 'Lỗi kết nối: $e';
    } finally {
      saving = false;
      notifyListeners();
    }
  }

  Future<String?> changeUsername(String tenDangNhapMoi) async {
    if (saving) return null;
    saving = true;
    notifyListeners();
    try {
      final res = await ApiService.put(
        '/profile/username',
        body: {'ten_dang_nhap_moi': tenDangNhapMoi},
      );
      if (res is Map && data != null) {
        data = {...data!, 'ten_dang_nhap': res['ten_dang_nhap']};
      }
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (e) {
      return 'Lỗi kết nối: $e';
    } finally {
      saving = false;
      notifyListeners();
    }
  }

  Future<String?> changePassword(String matKhauCu, String matKhauMoi) async {
    if (saving) return null;
    saving = true;
    notifyListeners();
    try {
      await ApiService.put(
        '/profile/password',
        body: {'mat_khau_cu': matKhauCu, 'mat_khau_moi': matKhauMoi},
      );
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (e) {
      return 'Lỗi kết nối: $e';
    } finally {
      saving = false;
      notifyListeners();
    }
  }

  Future<String?> logout() async {
    if (saving) return null;
    saving = true;
    notifyListeners();
    try {
      // Gọi POST /profile/logout (nếu thất bại vẫn đăng xuất local) rồi xóa token + shipper.
      await AuthService.logout();
      return null;
    } catch (e) {
      return 'Lỗi: $e';
    } finally {
      saving = false;
      notifyListeners();
    }
  }

  static String _str(dynamic v) => v?.toString() ?? '';
}
