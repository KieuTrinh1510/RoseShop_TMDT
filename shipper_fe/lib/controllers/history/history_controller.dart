// lib/controllers/history/history_controller.dart
// Lịch sử: đơn DELIVERED + đơn CANCELLED do SHIPPER/CUSTOMER — phân trang 10/trang chung
// + filter ngày/khoảng/tháng/mã. total / total_pages lấy đúng từ backend.

import 'package:flutter/foundation.dart';
import '../../services/api_service.dart';
import '../../utils/app_strings.dart';

class HistoryController extends ChangeNotifier {
  /// Thời gian skeleton tối thiểu khi đổi filter / trang, tránh nháy.
  static const Duration minSkeleton = Duration(milliseconds: 350);

  bool loading = true;

  /// true sau lần tải THÀNH CÔNG đầu tiên.
  bool hasLoaded = false;

  String? error;

  /// Mã lỗi backend (INVALID_ORDER_CODE, INVALID_DATE, ...). null nếu lỗi mạng.
  String? errorCode;

  List<Map<String, dynamic>> items = [];
  int page = 1;
  int limit = 10;
  int total = 0;
  int totalPages = 1;

  // Bộ lọc (null = không lọc)
  String? ngay;
  String? tuNgay;
  String? denNgay;
  String? thang;
  String? ma;

  bool _disposed = false;

  /// Mỗi lần load tăng 1; response của lần load cũ (đến muộn) sẽ bị bỏ qua.
  int _reqId = 0;

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  @override
  void notifyListeners() {
    if (!_disposed) super.notifyListeners();
  }

  bool get coLoc =>
      (ngay?.isNotEmpty ?? false) ||
      (tuNgay?.isNotEmpty ?? false) ||
      (denNgay?.isNotEmpty ?? false) ||
      (thang?.isNotEmpty ?? false) ||
      (ma?.isNotEmpty ?? false);

  Future<void> load({int? toPage, bool refresh = false}) async {
    final reqId = ++_reqId;
    if (toPage != null) page = toPage;
    if (!refresh) loading = true;
    error = null;
    errorCode = null;
    notifyListeners();

    try {
      final query = <String, dynamic>{'page': page, 'limit': limit};
      if (ngay?.isNotEmpty ?? false) query['ngay'] = ngay;
      if (tuNgay?.isNotEmpty ?? false) query['tu_ngay'] = tuNgay;
      if (denNgay?.isNotEmpty ?? false) query['den_ngay'] = denNgay;
      if (thang?.isNotEmpty ?? false) query['thang'] = thang;
      if (ma?.isNotEmpty ?? false) query['ma'] = ma;

      // Pull-to-refresh không cần giữ skeleton.
      final minDelay = Future<void>.delayed(
        refresh ? Duration.zero : minSkeleton,
      );
      final res = await ApiService.get('/history', query: query);
      await minDelay;
      if (reqId != _reqId) return; // đã có request mới hơn

      if (res is Map) {
        items = _asListMap(res['items']).where(_hienTrongLichSu).toList();
        total = _asInt(res['total']);
        page = _asInt(res['page'], fallback: page);
        limit = _asInt(res['limit'], fallback: limit);
        totalPages = _asInt(res['total_pages'], fallback: 1);
      } else if (res is List) {
        items = _asListMap(res).where(_hienTrongLichSu).toList();
        total = items.length;
        totalPages = 1;
      }
      error = null;
      errorCode = null;
      hasLoaded = true;
    } on ApiException catch (e) {
      if (reqId != _reqId) return;
      error = e.message;
      errorCode = '${e.code}';
      // Filter sai (INVALID_*) → bỏ list cũ để không nhầm với kết quả mới.
      if (errorCode!.startsWith('INVALID_')) {
        items = [];
        total = 0;
        page = 1;
        totalPages = 1;
      }
    } catch (e) {
      if (reqId != _reqId) return;
      error = tr('conn_err', {'e': '$e'});
      errorCode = null;
    } finally {
      if (reqId == _reqId) {
        loading = false;
        notifyListeners();
      }
    }
  }

  Future<void> refresh() => load(refresh: true);

  void nextPage() {
    if (page < totalPages) load(toPage: page + 1);
  }

  void prevPage() {
    if (page > 1) load(toPage: page - 1);
  }

  /// Ghi đè TOÀN BỘ filter (field không truyền = null) rồi load lại trang 1.
  void setFilter({
    String? ngay,
    String? tuNgay,
    String? denNgay,
    String? thang,
    String? ma,
  }) {
    this.ngay = ngay;
    this.tuNgay = tuNgay;
    this.denNgay = denNgay;
    this.thang = thang;
    this.ma = ma;
    load(toPage: 1);
  }

  void clearFilter() => setFilter();

  /// Phòng thủ phía client (backend đã lọc sẵn): chỉ DELIVERED hoặc
  /// CANCELLED có nguon_huy = SHIPPER / CUSTOMER. Backend cũ không trả trang_thai → coi như DELIVERED.
  static bool _hienTrongLichSu(Map<String, dynamic> it) {
    final tt = it['trang_thai']?.toString() ?? '';
    if (tt.isEmpty || tt == 'DELIVERED') return true;
    if (tt == 'CANCELLED') {
      final n = it['nguon_huy']?.toString();
      return n == 'SHIPPER' || n == 'CUSTOMER';
    }
    return false;
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
