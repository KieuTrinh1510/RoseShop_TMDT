// lib/controllers/thong_bao/thong_bao_controller.dart
// Controller màn hình Thông báo của shipper.
// Không dùng model class — giữ nguyên Map từ API như HomeController.

import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';

class ThongBaoController extends ChangeNotifier {
  bool loading = true;
  String? error;

  /// Danh sách thông báo dạng Map thô (đúng format Node trả về).
  List<Map<String, dynamic>> items = [];

  /// Bộ lọc UI: all | order_new | penalty | payment
  String filter = 'all';

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

  // ── Getter ────────────────────────────────────────────────────
  List<Map<String, dynamic>> get filtered {
    if (filter == 'all') return items;
    return items.where((t) => t['loai']?.toString() == filter).toList();
  }

  int get soChuaDoc => items.where((t) => t['da_doc'] != true).length;

  int demTheoLoai(String loai) =>
      items.where((t) => t['loai']?.toString() == loai).length;

  void setFilter(String f) {
    filter = f;
    notifyListeners();
  }

  // ── Actions ───────────────────────────────────────────────────

  Future<void> load({bool refresh = false}) async {
    if (!refresh) loading = true;
    error = null;
    notifyListeners();

    try {
      final res = await ApiService.get('/notifications', query: {'limit': 50});
      if (res is List) {
        items = res
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
      } else {
        items = [];
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

  /// Đánh dấu 1 thông báo đã đọc (local + gọi API).
  Future<void> markRead(Map<String, dynamic> tb) async {
    if (tb['da_doc'] == true) return;
    final id = _int(tb['ma_thong_bao']);
    final i = items.indexWhere((x) => _int(x['ma_thong_bao']) == id);
    if (i == -1) return;

    // Tạo bản copy với da_doc = true (không sửa in-place để UI rebuild đúng)
    items[i] = {...items[i], 'da_doc': true};
    notifyListeners();

    try {
      await ApiService.patch('/notifications/$id/read');
    } catch (_) {
      // đọc thông báo lỗi thì bỏ qua, refresh sau sẽ đồng bộ lại
    }
  }

  Future<bool> xoa(Map<String, dynamic> tb) async {
    final id = _int(tb['ma_thong_bao']);
    try {
      await ApiService.delete('/notifications/$id');
      items.removeWhere((x) => _int(x['ma_thong_bao']) == id);
      notifyListeners();
      return true;
    } catch (_) {
      return false;
    }
  }

  static int _int(dynamic v) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v?.toString() ?? '') ?? 0;
  }
}
