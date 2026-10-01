// lib/controllers/lich_su_controller.dart
// Danh sách đơn ĐÃ giao (DELIVERED) — chỉ phân trang.

import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class LichSuController extends ChangeNotifier {
  bool loading = true;
  String? error;

  List<Map<String, dynamic>> items = [];
  int page = 1;
  int limit = 20;
  int total = 0;
  int totalPages = 1;

  Future<void> load({int? toPage, bool refresh = false}) async {
    if (toPage != null) page = toPage;
    if (!refresh) loading = true;
    error = null;
    notifyListeners();

    try {
      final res = await ApiService.get(
        '/history',
        query: {'page': page, 'limit': limit},
      );
      if (res is Map) {
        items = _asListMap(res['items']);
        total = _asInt(res['total']);
        page = _asInt(res['page'], fallback: page);
        limit = _asInt(res['limit'], fallback: limit);
        totalPages = _asInt(res['total_pages'], fallback: 1);
      } else if (res is List) {
        items = _asListMap(res);
        total = items.length;
        totalPages = 1;
      }
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

  void nextPage() {
    if (page < totalPages) load(toPage: page + 1);
  }

  void prevPage() {
    if (page > 1) load(toPage: page - 1);
  }

  static List<Map<String, dynamic>> _asListMap(dynamic v) {
    if (v is List) {
      return v
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    }
    return [];
  }

  static int _asInt(dynamic v, {int fallback = 0}) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v?.toString() ?? '') ?? fallback;
  }
}
