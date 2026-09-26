// lib/services/api_service.dart
// Gói gọn việc gọi API: tự thêm header mã nhân viên, tự parse { success, data }
// hoặc { success:false, code, message } giống format của shipperController.js.

import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config.dart';

class ApiException implements Exception {
  final int status;
  final String code;
  final String message;
  ApiException(this.status, this.code, this.message);

  @override
  String toString() => '[$code] $message (HTTP $status)';
}

class ApiService {
  static Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    // Header giả lập đăng nhập - đọc ở middleware fakeAuth phía backend
    'x-ma-nhan-vien': AppConfig.maNhanVien.toString(),
  };

  static Uri _uri(String path, [Map<String, dynamic>? query]) {
    final cleanQuery = <String, String>{};
    query?.forEach((k, v) {
      if (v != null && v.toString().isNotEmpty) cleanQuery[k] = v.toString();
    });
    return Uri.parse(
      '${AppConfig.baseUrl}$path',
    ).replace(queryParameters: cleanQuery.isEmpty ? null : cleanQuery);
  }

  static dynamic _parse(http.Response res) {
    Map<String, dynamic> body;
    try {
      body = jsonDecode(res.body) as Map<String, dynamic>;
    } catch (_) {
      throw ApiException(
        res.statusCode,
        'BAD_RESPONSE',
        'Server không trả JSON hợp lệ: ${res.body}',
      );
    }

    if (body['success'] == true) {
      return body['data'];
    }
    throw ApiException(
      res.statusCode,
      (body['code'] ?? 'UNKNOWN').toString(),
      (body['message'] ?? 'Lỗi không xác định').toString(),
    );
  }

  static Future<dynamic> get(String path, {Map<String, dynamic>? query}) async {
    final res = await http.get(_uri(path, query), headers: _headers);
    return _parse(res);
  }

  static Future<dynamic> post(String path, {Map<String, dynamic>? body}) async {
    final res = await http.post(
      _uri(path),
      headers: _headers,
      body: body == null ? null : jsonEncode(body),
    );
    return _parse(res);
  }

  static Future<dynamic> put(String path, {Map<String, dynamic>? body}) async {
    final res = await http.put(
      _uri(path),
      headers: _headers,
      body: body == null ? null : jsonEncode(body),
    );
    return _parse(res);
  }
}
