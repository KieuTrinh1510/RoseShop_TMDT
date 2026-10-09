// lib/screens/thong_bao/thong_bao_screen.dart
// Màn hình danh sách thông báo của shipper.
// Filter chip: Tất cả / Đơn mới / Khoản phạt / Thanh toán.
// Tap item:
//   - order_new → gọi onOpenOrder(maHoaDon) để HomeScreen mở chi tiết đơn
//   - payment   → bottom sheet hiển thị breakdown từ du_lieu
//   - còn lại   → đánh dấu đã đọc

import 'package:flutter/material.dart';
import '../../controllers/thong_bao/thong_bao_controller.dart';
import '../../theme/pink_theme.dart';

class ThongBaoScreen extends StatefulWidget {
  /// Callback để mở chi tiết đơn (nhận ma_hoa_don).
  final void Function(int maHoaDon)? onOpenOrder;

  const ThongBaoScreen({super.key, this.onOpenOrder});

  @override
  State<ThongBaoScreen> createState() => _ThongBaoScreenState();
}

class _ThongBaoScreenState extends State<ThongBaoScreen> {
  final _c = ThongBaoController();

  @override
  void initState() {
    super.initState();
    _c.load();
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  // ── Xử lý khi tap item ────────────────────────────────────────
  Future<void> _onTapItem(Map<String, dynamic> tb) async {
    await _c.markRead(tb);

    final loai = tb['loai']?.toString() ?? '';
    final maHoaDon = tb['ma_hoa_don'];

    if (loai == 'order_new' && maHoaDon != null) {
      Navigator.pop(context);
      widget.onOpenOrder?.call(_int(maHoaDon));
      return;
    }
    if (loai == 'payment') {
      _showPaymentSheet(tb);
      return;
    }
    // penalty và loại khác → không làm gì thêm
  }

  // ── Bottom sheet chi tiết khoản đã nộp ────────────────────────
  void _showPaymentSheet(Map<String, dynamic> tb) {
    final d = (tb['du_lieu'] is Map)
        ? Map<String, dynamic>.from(tb['du_lieu'])
        : <String, dynamic>{};

    final dsDon = (d['ds_don'] is List)
        ? (d['ds_don'] as List).whereType<Map>().toList()
        : <Map>[];
    final tongCod = _num(d['tong_cod']);
    final tienPhat = _num(d['tien_phat']);
    final tongNop = _num(d['tong_nop'], fallback: tongCod + tienPhat);

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => Container(
        padding: EdgeInsets.fromLTRB(
          24,
          20,
          24,
          24 + MediaQuery.of(context).padding.bottom,
        ),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Chi tiết khoản đã nộp',
              style: TextStyle(
                color: Pink.plum,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 16),
            if (dsDon.isNotEmpty) ...[
              const Text(
                'Đơn hàng',
                style: TextStyle(
                  color: Pink.plum,
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                ),
              ),
              const SizedBox(height: 8),
              ...dsDon.map((row) {
                final ma =
                    row['ma_hien_thi']?.toString() ??
                    '#HD-${row['ma_hoa_don']}';
                final tien = _num(row['tien']);
                return _row(ma, tien);
              }),
              const Divider(height: 24),
            ],
            _row('Tiền COD đã thu', tongCod),
            if (tienPhat > 0) _row('Tiền phạt giao trễ', tienPhat),
            const Divider(height: 24),
            _row('Tổng đã nộp', tongNop, bold: true),
          ],
        ),
      ),
    );
  }

  Widget _row(String label, double v, {bool bold = false}) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              color: Pink.plum.withOpacity(bold ? 1 : 0.7),
              fontSize: bold ? 15 : 14,
              fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
            ),
          ),
        ),
        Text(
          _tien(v),
          style: TextStyle(
            color: Pink.plum,
            fontSize: bold ? 18 : 15,
            fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
          ),
        ),
      ],
    ),
  );

  static String _tien(double v) {
    final s = v.toStringAsFixed(0);
    final buf = StringBuffer();
    for (int i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) buf.write('.');
      buf.write(s[i]);
    }
    return '${buf}đ';
  }

  // ── Chip filter ───────────────────────────────────────────────
  Widget _chip(String key, String label, int count) {
    final active = _c.filter == key;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(count > 0 ? '$label ($count)' : label),
        selected: active,
        onSelected: (_) => _c.setFilter(key),
        selectedColor: Pink.rose.withOpacity(0.15),
        labelStyle: TextStyle(
          color: active ? Pink.roseDeep : Pink.plum.withOpacity(0.7),
          fontWeight: FontWeight.w600,
        ),
        backgroundColor: Colors.white,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Pink.blush,
      appBar: AppBar(
        backgroundColor: Pink.blush,
        elevation: 0,
        foregroundColor: Pink.plum,
        title: const Text(
          'Thông báo',
          style: TextStyle(color: Pink.plum, fontWeight: FontWeight.w800),
        ),
      ),
      body: ListenableBuilder(
        listenable: _c,
        builder: (context, _) {
          if (_c.loading && _c.items.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }
          if (_c.error != null && _c.items.isEmpty) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(_c.error!),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: () => _c.load(),
                    child: const Text('Thử lại'),
                  ),
                ],
              ),
            );
          }

          final list = _c.filtered;

          return Column(
            children: [
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
                child: Row(
                  children: [
                    _chip('all', 'Tất cả', _c.items.length),
                    _chip('order_new', 'Đơn mới', _c.demTheoLoai('order_new')),
                    _chip('penalty', 'Khoản phạt', _c.demTheoLoai('penalty')),
                    _chip('payment', 'Thanh toán', _c.demTheoLoai('payment')),
                  ],
                ),
              ),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: () => _c.load(refresh: true),
                  child: list.isEmpty
                      ? ListView(
                          children: const [
                            SizedBox(height: 120),
                            Center(child: Text('Không có thông báo nào')),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                          itemCount: list.length,
                          separatorBuilder: (_, __) =>
                              const SizedBox(height: 10),
                          itemBuilder: (_, i) => _buildCard(list[i]),
                        ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildCard(Map<String, dynamic> tb) {
    final loai = tb['loai']?.toString() ?? 'info';
    final daDoc = tb['da_doc'] == true;
    final cfg = _cfgFor(loai);

    return Dismissible(
      key: ValueKey(_int(tb['ma_thong_bao'])),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        decoration: BoxDecoration(
          color: Pink.danger,
          borderRadius: BorderRadius.circular(16),
        ),
        child: const Icon(Icons.delete_outline, color: Colors.white),
      ),
      onDismissed: (_) => _c.xoa(tb),
      child: Material(
        color: daDoc ? Colors.white.withOpacity(0.7) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _onTapItem(tb),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              border: Border(left: BorderSide(color: cfg.border, width: 4)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: cfg.bg,
                  ),
                  child: Icon(cfg.icon, color: cfg.color, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        cfg.label,
                        style: TextStyle(
                          color: cfg.color,
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        tb['noi_dung']?.toString() ?? '',
                        style: TextStyle(
                          color: Pink.plum.withOpacity(daDoc ? 0.7 : 1),
                          fontSize: 14,
                          height: 1.35,
                          fontWeight: daDoc ? FontWeight.w400 : FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        _formatTime(tb['thoi_gian']),
                        style: TextStyle(
                          color: Pink.plum.withOpacity(0.45),
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
                if (!daDoc)
                  Container(
                    margin: const EdgeInsets.only(left: 8, top: 4),
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: Pink.rose,
                      shape: BoxShape.circle,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  static _Cfg _cfgFor(String loai) {
    switch (loai) {
      case 'order_new':
        return const _Cfg(
          'Đơn hàng mới',
          Icons.receipt_long_rounded,
          Color(0xFF22c55e),
          Color(0xFFecfdf5),
          Color(0xFF16a34a),
        );
      case 'penalty':
        return const _Cfg(
          'Bị phạt giao trễ',
          Icons.warning_amber_rounded,
          Color(0xFFef4444),
          Color(0xFFfef2f2),
          Color(0xFFdc2626),
        );
      case 'payment':
        return const _Cfg(
          'Đã nộp tiền',
          Icons.payments_rounded,
          Color(0xFF3b82f6),
          Color(0xFFeff6ff),
          Color(0xFF2563eb),
        );
      case 'shipping':
        return const _Cfg(
          'Đang giao',
          Icons.motorcycle_rounded,
          Color(0xFF3b82f6),
          Color(0xFFeff6ff),
          Color(0xFF2563eb),
        );
      default:
        return const _Cfg(
          'Thông báo',
          Icons.notifications_rounded,
          Color(0xFFe75480),
          Color(0xFFfce8ef),
          Color(0xFFe75480),
        );
    }
  }

  static String _formatTime(dynamic raw) {
    final t = DateTime.tryParse(raw?.toString() ?? '');
    if (t == null) return '';
    final l = t.toLocal();
    String two(int n) => n.toString().padLeft(2, '0');
    return '${two(l.hour)}:${two(l.minute)}, ${two(l.day)}/${two(l.month)}/${l.year}';
  }

  static int _int(dynamic v) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v?.toString() ?? '') ?? 0;
  }

  static double _num(dynamic v, {double fallback = 0}) {
    if (v is num) return v.toDouble();
    return double.tryParse(v?.toString() ?? '') ?? fallback;
  }
}

class _Cfg {
  final String label;
  final IconData icon;
  final Color border;
  final Color bg;
  final Color color;
  const _Cfg(this.label, this.icon, this.border, this.bg, this.color);
}
