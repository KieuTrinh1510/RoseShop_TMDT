// lib/controllers/home/home_controller.dart
// Controller cho tab Trang chủ.
//  - GET  /home          -> nested: { shipper, trang_thai, don_hang, cod, thong_bao }
//  - PUT  /status/online -> FLAT  : { ma_nhan_vien, ten_nhan_vien,
//                                     trang_thai_hoat_dong,
//                                     so_don_hien_tai, gioi_han_don,
//                                     is_full, co_the_nhan_don }
//
// LƯU Ý ApiService:
//  - Là static class, gọi ApiService.get(...) / ApiService.put(...).
//  - Hàm _parse() bên trong ĐÃ unwrap envelope {success, data},
//    nên giá trị trả về CHÍNH LÀ payload, KHÔNG phải res['data'].

import 'package:flutter/foundation.dart';

import '../../services/api_service.dart';
import '../../utils/app_strings.dart';

class HomeController extends ChangeNotifier {
  Map<String, dynamic>? _data;
  Map<String, dynamic>? get data => _data;

  bool loading = false;
  bool toggling = false;
  String? error;

  // ── Getters từ _data (shape nested của /home) ────────────────
  String get maNhanVien =>
      (_data?['shipper']?['ma_nhan_vien'] ?? '').toString();

  String get tenNhanVien =>
      (_data?['shipper']?['ten_nhan_vien'] ?? '').toString();

  String get chuCaiDau {
    final c = (_data?['shipper']?['chu_cai_dau'] ?? '').toString();
    return c.isEmpty ? '?' : c;
  }

  bool get isOnline => _data?['trang_thai']?['is_online'] == true;

  String get trangThaiHoatDong =>
      (_data?['trang_thai']?['trang_thai_hoat_dong'] ?? 'OFFLINE').toString();

  int get soDonHienTai => _asInt(_data?['don_hang']?['so_don_hien_tai']);
  int get gioiHanDon =>
      _asInt(_data?['don_hang']?['gioi_han_don'], fallback: 10);
  bool get isFull => _data?['don_hang']?['is_full'] == true;
  bool get coTheNhanDon => _data?['don_hang']?['co_the_nhan_don'] == true;

  // Tiền: backend trả Number (int đồng) -> ép double để format.
  double get tienCod => _asDouble(_data?['cod']?['tien_cod']);
  double get tienPhat => _asDouble(_data?['cod']?['tien_phat']);
  double get codCanNop => _asDouble(_data?['cod']?['tien_can_nop']);

  String get codPhu {
    final soDon = _asInt(_data?['cod']?['so_don']);
    if (soDon == 0) return tr('cod_none');
    return tr('cod_from', {'n': '$soDon'});
  }

  int get thongBaoChuaDoc => _asInt(_data?['thong_bao']?['chua_doc']);

  // ── Load /home ───────────────────────────────────────────────
  Future<void> load({bool refresh = false}) async {
    if (loading) return;
    loading = true;
    if (!refresh) notifyListeners();

    try {
      // ApiService.get trả về THẲNG payload (đã unwrap).
      final res = await ApiService.get('/home');
      _data = _asMap(res);
      error = null;
    } catch (e) {
      error = e.toString();
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  // ── Toggle ONLINE/OFFLINE ────────────────────────────────────
  // Trả null nếu thành công; ngược lại trả message lỗi.
  Future<String?> toggleOnline(bool value) async {
    if (toggling || _data == null) return null;

    toggling = true;
    notifyListeners();

    final mongMuon = value ? 'ONLINE' : 'OFFLINE';
    try {
      // ApiService.put trả về THẲNG payload (đã unwrap).
      final res = await ApiService.put(
        '/status/online',
        body: {'trang_thai': mongMuon},
      );
      final d = _asMap(res);

      // Backend trả FLAT -> merge từng field, KHÔNG replace _data.
      final trangThaiMoi = (d['trang_thai_hoat_dong'] ?? mongMuon).toString();

      _data!['trang_thai'] = {
        'trang_thai_hoat_dong': trangThaiMoi,
        'is_online': trangThaiMoi == 'ONLINE',
      };

      _data!['don_hang'] = {
        ...(_data!['don_hang'] ?? const <String, dynamic>{}),
        'so_don_hien_tai': _asInt(d['so_don_hien_tai']),
        'gioi_han_don': _asInt(d['gioi_han_don'], fallback: 10),
        'is_full': d['is_full'] == true,
        'co_the_nhan_don': d['co_the_nhan_don'] == true,
      };

      // Đồng bộ tên shipper nếu BE có trả kèm.
      if (d['ten_nhan_vien'] != null || d['ma_nhan_vien'] != null) {
        _data!['shipper'] = {
          ...(_data!['shipper'] ?? const <String, dynamic>{}),
          'ma_nhan_vien':
              d['ma_nhan_vien'] ?? _data!['shipper']?['ma_nhan_vien'],
          'ten_nhan_vien':
              d['ten_nhan_vien'] ?? _data!['shipper']?['ten_nhan_vien'],
        };
      }

      error = null;
      return null;
    } catch (e) {
      return e.toString();
    } finally {
      toggling = false;
      notifyListeners();
    }
  }

  // ── Helpers parse an toàn ────────────────────────────────────
  static Map<String, dynamic> _asMap(dynamic v) {
    if (v == null) return <String, dynamic>{};
    if (v is Map<String, dynamic>) return v;
    if (v is Map) return Map<String, dynamic>.from(v);
    return <String, dynamic>{};
  }

  static int _asInt(dynamic v, {int fallback = 0}) {
    if (v == null) return fallback;
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v.toString()) ?? fallback;
  }

  static double _asDouble(dynamic v, {double fallback = 0}) {
    if (v == null) return fallback;
    if (v is double) return v;
    if (v is num) return v.toDouble();
    return double.tryParse(v.toString()) ?? fallback;
  }
}
