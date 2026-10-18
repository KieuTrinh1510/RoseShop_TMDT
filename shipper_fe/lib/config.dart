// lib/config.dart
// Cấu hình dùng chung: URL backend.
// Xác thực dùng JWT (xem services/auth_service.dart), KHÔNG còn dùng mã nhân viên giả lập.

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppConfig {
  static const _kBaseUrl = 'base_url';

  // Mặc định theo nền tảng:
  //  - Android emulator : http://10.0.2.2:3000/api/shipper
  //  - Web / iOS simulator / desktop : http://localhost:3000/api/shipper
  //  - Thiết bị thật: đổi thành IP LAN máy chạy Node, vd http://192.168.1.5:3000/api/shipper
  //    (ở màn đăng nhập: nhấn giữ logo Roseshop để đổi, giá trị được nhớ lại).
  static String get defaultBaseUrl {
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:3000/api/shipper';
    }
    return 'http://localhost:3000/api/shipper';
  }

  static String baseUrl = defaultBaseUrl;

  @Deprecated('Không còn dùng. Backend lấy mã nhân viên từ JWT.')
  static int maNhanVien = 9;

  /// Nạp URL đã lưu (gọi 1 lần ở main()).
  static Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_kBaseUrl);
    if (saved != null && normalizeUrl(saved) != null) baseUrl = saved;
  }

  static Future<void> setBaseUrl(String url) async {
    final u = normalizeUrl(url);
    if (u == null) return;
    baseUrl = u;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kBaseUrl, u);
  }

  /// Trả URL đã chuẩn hóa (bỏ "/" cuối) hoặc null nếu không hợp lệ.
  static String? normalizeUrl(String input) {
    var s = input.trim();
    while (s.endsWith('/')) {
      s = s.substring(0, s.length - 1);
    }
    final uri = Uri.tryParse(s);
    if (uri == null || uri.host.isEmpty) return null;
    if (uri.scheme != 'http' && uri.scheme != 'https') return null;
    return s;
  }
}
