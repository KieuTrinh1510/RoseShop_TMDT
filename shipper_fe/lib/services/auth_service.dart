// lib/services/auth_service.dart
// Quản lý phiên đăng nhập shipper (JWT).
//  - login(): POST {baseUrl}/auth/login, lưu token + thông tin shipper.
//  - getToken() / authHeaders(): ApiService dùng để gắn "Authorization: Bearer <token>".
//  - logout(): gọi POST /profile/logout (nếu được), xóa token + shipper local.
//  - handleUnauthorized(): token hết hạn/sai -> xóa phiên, báo cho main.dart đưa về LoginScreen.
// KHÔNG lưu mật khẩu.
//
// Ghi chú: token đang lưu bằng shared_preferences (đủ cho đồ án). Muốn an toàn hơn trên
// thiết bị thật có thể thay bằng flutter_secure_storage, chỉ cần sửa 3 chỗ dùng _prefs().

import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../config.dart';

class AuthException implements Exception {
  final int status;
  final String code;
  final String message;
  AuthException(this.status, this.code, this.message);

  @override
  String toString() => message;
}

class AuthService {
  AuthService._();

  static const _kToken = 'auth_token';
  static const _kShipper = 'auth_shipper';
  static const _loginTimeout = Duration(seconds: 15);

  /// Chỉ 3 mã này mới nghĩa là "phiên hết hạn". Lưu ý: backend còn trả 401
  /// WRONG_PASSWORD khi đổi mật khẩu sai mật khẩu cũ -> KHÔNG được đăng xuất.
  static const _sessionErrorCodes = {
    'TOKEN_MISSING',
    'TOKEN_INVALID',
    'TOKEN_EXPIRED',
  };

  static String? _token;
  static Map<String, dynamic>? _shipper;
  static bool _sessionEnding = false;

  /// main.dart gán: đưa người dùng về LoginScreen khi phiên hết hạn.
  static VoidCallback? onSessionExpired;

  // ── Khởi động: nạp token đã lưu (bỏ nếu đã hết hạn) ──────────
  static Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final t = prefs.getString(_kToken);
    if (t == null) return;
    if (_isExpired(t)) {
      await clearSession();
      return;
    }
    _token = t;
    final raw = prefs.getString(_kShipper);
    if (raw != null) {
      try {
        _shipper = Map<String, dynamic>.from(jsonDecode(raw) as Map);
      } catch (_) {}
    }
  }

  static bool get isLoggedIn => _token != null && !_isExpired(_token!);

  static String? getToken() => _token;

  /// Thông tin shipper lấy lúc đăng nhập (ma_nhan_vien, ten_nhan_vien, email, so_dien_thoai).
  static Map<String, dynamic>? get shipper => _shipper;

  static Map<String, String> authHeaders({bool json = true}) => {
    if (json) 'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  static bool isSessionError(int status, String code) =>
      status == 401 && _sessionErrorCodes.contains(code);

  // ── Đăng nhập ────────────────────────────────────────────────
  static Future<Map<String, dynamic>> login(
    String tenDangNhap,
    String matKhau,
  ) async {
    final res = await http
        .post(
          Uri.parse('${AppConfig.baseUrl}/auth/login'),
          headers: const {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: jsonEncode({'ten_dang_nhap': tenDangNhap, 'mat_khau': matKhau}),
        )
        .timeout(_loginTimeout);

    Map<String, dynamic> body;
    try {
      body = jsonDecode(res.body) as Map<String, dynamic>;
    } catch (_) {
      throw AuthException(
        res.statusCode,
        'BAD_RESPONSE',
        'Máy chủ phản hồi không hợp lệ. Kiểm tra địa chỉ máy chủ.',
      );
    }

    final token = body['token'];
    if (res.statusCode == 200 &&
        body['success'] == true &&
        token is String &&
        token.isNotEmpty) {
      final shipper = body['shipper'] is Map
          ? Map<String, dynamic>.from(body['shipper'] as Map)
          : <String, dynamic>{};
      await _saveSession(token, shipper);
      return shipper;
    }

    final code = (body['code'] ?? 'UNKNOWN').toString();
    throw AuthException(
      res.statusCode,
      code,
      _friendly(res.statusCode, code, body['message']?.toString()),
    );
  }

  static String _friendly(int status, String code, String? backendMsg) {
    if (status >= 500) return 'Máy chủ đang gặp lỗi. Vui lòng thử lại sau.';
    if (code == 'NOT_FOUND') {
      return 'Không tìm thấy máy chủ đăng nhập. Kiểm tra địa chỉ máy chủ.';
    }
    if (backendMsg != null && backendMsg.trim().isNotEmpty) return backendMsg;
    return 'Đăng nhập thất bại (HTTP $status).';
  }

  // ── Quên mật khẩu (công khai, không cần token) ───────────────
  /// Bước 1: gửi mã 6 số tới email của shipper. Trả về thông báo từ server.
  static Future<String> forgotPassword(String email) async {
    final body = await _postPublic('/auth/forgot-password', {
      'email': email.trim(),
    });
    return (body['message'] ?? 'Đã gửi mã xác thực.').toString();
  }

  /// Bước 2: nhập mã + mật khẩu mới. Trả về thông báo từ server.
  static Future<String> resetPassword({
    required String email,
    required String maXacThuc,
    required String matKhauMoi,
  }) async {
    final body = await _postPublic('/auth/reset-password', {
      'email': email.trim(),
      'ma_xac_thuc': maXacThuc.trim(),
      'mat_khau_moi': matKhauMoi,
    });
    return (body['message'] ?? 'Đặt lại mật khẩu thành công.').toString();
  }

  static Future<Map<String, dynamic>> _postPublic(
    String path,
    Map<String, dynamic> data,
  ) async {
    final res = await http
        .post(
          Uri.parse('${AppConfig.baseUrl}$path'),
          headers: const {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: jsonEncode(data),
        )
        .timeout(const Duration(seconds: 30)); // gửi email có thể mất vài giây

    Map<String, dynamic> body;
    try {
      body = jsonDecode(res.body) as Map<String, dynamic>;
    } catch (_) {
      throw AuthException(
        res.statusCode,
        'BAD_RESPONSE',
        'Máy chủ phản hồi không hợp lệ. Kiểm tra địa chỉ máy chủ.',
      );
    }

    if (res.statusCode == 200 && body['success'] == true) return body;

    final code = (body['code'] ?? 'UNKNOWN').toString();
    final msg = body['message']?.toString();
    throw AuthException(
      res.statusCode,
      code,
      (msg != null && msg.trim().isNotEmpty)
          ? msg
          : _friendly(res.statusCode, code, null),
    );
  }

  // ── Đăng xuất ────────────────────────────────────────────────
  /// Gọi POST /profile/logout (không bắt buộc thành công), rồi xóa phiên local.
  static Future<void> logout({bool notifyServer = true}) async {
    // Chặn các request nền (timer refresh) làm bật redirect thêm lần nữa.
    _sessionEnding = true;
    if (notifyServer && _token != null) {
      try {
        await http
            .post(
              Uri.parse('${AppConfig.baseUrl}/profile/logout'),
              headers: authHeaders(),
            )
            .timeout(const Duration(seconds: 5));
      } catch (_) {
        // Mất mạng / token hết hạn: vẫn đăng xuất local bình thường.
      }
    }
    await clearSession();
  }

  /// Xóa token + thông tin shipper (local).
  static Future<void> clearSession() async {
    _token = null;
    _shipper = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_kToken);
    await prefs.remove(_kShipper);
  }

  /// ApiService gọi khi backend trả 401 TOKEN_*. Chỉ chạy 1 lần cho tới lần đăng nhập kế tiếp.
  static Future<void> handleUnauthorized() async {
    if (_sessionEnding) return;
    _sessionEnding = true;
    await clearSession();
    onSessionExpired?.call();
  }

  // ── Nội bộ ───────────────────────────────────────────────────
  static Future<void> _saveSession(
    String token,
    Map<String, dynamic> shipper,
  ) async {
    _token = token;
    _shipper = shipper;
    _sessionEnding = false;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kToken, token);
    await prefs.setString(_kShipper, jsonEncode(shipper));
  }

  /// Đọc "exp" trong JWT (không cần verify chữ ký; server mới là nơi verify).
  static bool _isExpired(String token) {
    try {
      final parts = token.split('.');
      if (parts.length != 3) return true;
      final payload =
          jsonDecode(
                utf8.decode(base64Url.decode(base64Url.normalize(parts[1]))),
              )
              as Map<String, dynamic>;
      final exp = payload['exp'];
      if (exp is! num) return false;
      return DateTime.now().millisecondsSinceEpoch ~/ 1000 >= exp.toInt();
    } catch (_) {
      return true;
    }
  }
}
