// lib/controllers/orders/order_list_controller.dart
// Danh sách đơn CHƯA giao (CONFIRMED + SHIPPING) + lọc trạng thái + phân trang.

import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';
import '../../utils/app_strings.dart';

class OrderListController extends ChangeNotifier {
  bool loading = true;
  String? error;

  List<Map<String, dynamic>> items = [];
  int page = 1;
  int limit = 20;
  int total = 0;
  int totalPages = 1;

  /// null = tất cả; 'CONFIRMED' / 'SHIPPING' để lọc.
  String? trangThai;

  bool _disposed = false;

  /// Mỗi lần load tăng 1 → response cũ (đến muộn) bị bỏ qua.
  int _loadSeq = 0;

  /// Debounce filter + pagination (300ms) để tránh spam request.
  Timer? _loadDebounce;

  @override
  void dispose() {
    _disposed = true;
    _loadDebounce?.cancel();
    super.dispose();
  }

  @override
  void notifyListeners() {
    if (!_disposed) super.notifyListeners();
  }

  Future<void> load({int? toPage, bool refresh = false}) async {
    if (toPage != null) page = toPage;
    final seq = ++_loadSeq;
    if (!refresh) loading = true;
    error = null;
    notifyListeners();

    try {
      final query = <String, dynamic>{'page': page, 'limit': limit};
      if (trangThai != null) query['trang_thai'] = trangThai;

      final res = await ApiService.get('/orders', query: query);
      if (seq != _loadSeq) return;
      _apply(res);
      error = null;
    } on ApiException catch (e) {
      if (seq != _loadSeq) return;
      error = e.message;
    } catch (e) {
      if (seq != _loadSeq) return;
      error = tr('conn_err', {'e': '$e'});
    } finally {
      if (seq == _loadSeq) {
        loading = false;
        notifyListeners();
      }
    }
  }

  Future<void> refresh() => load(refresh: true);

  void setFilter(String? tt) {
    if (trangThai == tt) return;
    trangThai = tt;
    notifyListeners(); // chip active ngay
    _scheduleLoad(toPage: 1);
  }

  void nextPage() {
    if (page < totalPages) _scheduleLoad(toPage: page + 1);
  }

  void prevPage() {
    if (page > 1) _scheduleLoad(toPage: page - 1);
  }

  void _scheduleLoad({int? toPage}) {
    _loadDebounce?.cancel();
    _loadDebounce = Timer(const Duration(milliseconds: 300), () {
      load(toPage: toPage);
    });
  }

  void _apply(dynamic res) {
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
