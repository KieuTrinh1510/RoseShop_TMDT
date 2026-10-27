// lib/controllers/notification/notification_controller.dart
// Controller màn hình Thông báo của shipper.

import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';

class NotificationController extends ChangeNotifier {
  bool loading = true;
  String? error;

  List<Map<String, dynamic>> items = [];

  /// Bộ lọc UI: all | penalty | order_new | info
  String filter = 'all';

  bool _disposed = false;

  /// Mỗi lần load tăng 1 → response cũ (đến muộn) bị bỏ qua.
  int _loadSeq = 0;

  /// Chống xóa / mark-read cùng id 2 lần liên tiếp.
  final Set<int> _deletingIds = {};
  final Set<int> _markingIds = {};

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  @override
  void notifyListeners() {
    if (!_disposed) super.notifyListeners();
  }

  /// UI dùng để disable nút X khi item đang xóa.
  bool dangXoa(int id) => _deletingIds.contains(id);

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

  Future<void> load({bool refresh = false}) async {
    final seq = ++_loadSeq;
    if (!refresh) loading = true;
    error = null;
    notifyListeners();

    try {
      final res = await ApiService.get('/notifications', query: {'limit': 50});
      if (seq != _loadSeq) return; // đã có request mới hơn
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
      if (seq != _loadSeq) return;
      error = e.message;
    } catch (e) {
      if (seq != _loadSeq) return;
      error = 'Lỗi kết nối: $e';
    } finally {
      if (seq == _loadSeq) {
        loading = false;
        notifyListeners();
      }
    }
  }

  Future<void> markRead(Map<String, dynamic> tb) async {
    final id = _int(tb['ma_thong_bao']);
    final i = items.indexWhere((x) => _int(x['ma_thong_bao']) == id);
    if (i == -1) return;
    if (items[i]['da_doc'] == true) return; // đã đọc rồi
    if (_markingIds.contains(id)) return; // đang gọi

    _markingIds.add(id);

    // Optimistic update — khôi phục nếu API fail.
    items[i] = {...items[i], 'da_doc': true};
    notifyListeners();

    try {
      await ApiService.patch('/notifications/$id/read');
    } catch (_) {
      final j = items.indexWhere((x) => _int(x['ma_thong_bao']) == id);
      if (j != -1 && !_disposed) {
        items[j] = {...items[j], 'da_doc': false};
        notifyListeners();
      }
    } finally {
      _markingIds.remove(id);
    }
  }

  /// Chỉ gọi API DELETE. KHÔNG xóa khỏi [items] — việc xóa do [removeById]
  /// đảm nhận để Dismissible hoàn tất animation trước khi list thay đổi.
  Future<bool> xoa(Map<String, dynamic> tb) async {
    final id = _int(tb['ma_thong_bao']);
    if (_deletingIds.contains(id)) return false; // đang xóa → bỏ qua
    _deletingIds.add(id);
    notifyListeners(); // hiện spinner X ngay
    try {
      await ApiService.delete('/notifications/$id');
      return true;
    } catch (_) {
      return false;
    } finally {
      _deletingIds.remove(id);
      notifyListeners(); // tắt spinner
    }
  }

  /// Xóa item khỏi danh sách. CHỈ gọi sau khi [xoa] trả về true.
  void removeById(int id) {
    final before = items.length;
    items.removeWhere((x) => _int(x['ma_thong_bao']) == id);
    if (items.length != before && !_disposed) notifyListeners();
  }

  static int _int(dynamic v) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v?.toString() ?? '') ?? 0;
  }
}
