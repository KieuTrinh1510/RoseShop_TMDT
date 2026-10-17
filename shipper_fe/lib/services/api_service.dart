// lib/services/api_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config.dart';

class ApiException implements Exception {
  final int status;
  final String code;
  final String message;
  ApiException(this.status, this.code, this.message);

  /// Mã 409/422 mà backend dùng khi yêu cầu TRƯỚC ĐÓ đã xử lý xong
  /// (báo lỗi / finalize / giao hàng). Gồm cả mã thực tế lẫn mã trong đặc tả.
  static const Set<String> _daXuLyCodes = {
    'ALREADY_REPORTED',
    'ALREADY_CANCELLED',
    'NO_PENDING_REPORT',
    'STATE_CHANGED',
    'ORDER_DELIVERED',
    'ORDER_CANCELLED',
    'ORDER_ALREADY_DELIVERED',
    'FAILURE_ALREADY_REPORTED',
    'FAILURE_ALREADY_FINALIZED',
  };

  bool get isBadRequest => status == 400;
  bool get isNotFound => status == 404;
  bool get isConflict => status == 409;
  bool get isUnprocessable => status == 422;
  bool get isServerError => status >= 500;

  /// Trạng thái đơn đã đổi do request khác → UI phải TẢI LẠI thay vì coi là lỗi.
  bool get isAlreadyHandled => isConflict || _daXuLyCodes.contains(code);

  @override
  String toString() => '[$code] $message (HTTP $status)';
}

class ApiService {
  static Map<String, String> get _headers => {
    'Content-Type': 'application/json',
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
      final raw = res.body.length > 200
          ? '${res.body.substring(0, 200)}…'
          : res.body;
      throw ApiException(
        res.statusCode,
        'BAD_RESPONSE',
        'Server không trả JSON hợp lệ: $raw',
      );
    }

    if (body['success'] == true) {
      return body['data'];
    }
    // Giữ nguyên status + code + message của backend (400/404/409/422/500...)
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

  // ── Báo giao hàng thất bại ─────────────────────────────────────
  static Future<dynamic> getFailureCheck(String orderId) {
    return get('/orders/$orderId/failure-check');
  }

  static Future<dynamic> reportFailure(
    String orderId, {
    required String loaiLoi,
    required String lyDo,
    String? ghiChu,
  }) {
    return post(
      '/orders/$orderId/report-failure',
      body: {
        'loai_loi': loaiLoi,
        'ly_do': lyDo,
        if (ghiChu != null && ghiChu.trim().isNotEmpty)
          'ghi_chu': ghiChu.trim(),
      },
    );
  }

  /// Hết 10 phút chờ lần hai: hủy đơn do khách không nhận hàng.
  /// Không có body; backend tự kiểm tra mọi điều kiện và idempotent.
  /// Data trả về: { trang_thai: 'CANCELLED' | 'DELIVERED', ket_qua, da_finalize_truoc_do, ... }
  static Future<dynamic> finalizeCustomerFailure(String orderId) {
    return post('/orders/$orderId/finalize-customer-failure');
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

  static Future<dynamic> patch(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final res = await http.patch(
      _uri(path),
      headers: _headers,
      body: body == null ? null : jsonEncode(body),
    );
    return _parse(res);
  }

  static Future<dynamic> delete(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final res = await http.delete(
      _uri(path),
      headers: _headers,
      body: body == null ? null : jsonEncode(body),
    );
    return _parse(res);
  }
}
