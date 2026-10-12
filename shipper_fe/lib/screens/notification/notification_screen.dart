// lib/screens/notification/notification_screen.dart
// Màn hình danh sách thông báo của shipper.
// Filter chip: Tất cả / Đơn mới / Khoản phạt / Thanh toán.
// Tap item:
//   - order_new → gọi onOpenOrder(maHoaDon) để HomeScreen mở chi tiết đơn
//   - payment   → bottom sheet hiển thị breakdown từ du_lieu
//   - còn lại   → đánh dấu đã đọc

import 'package:flutter/material.dart';
import '../../controllers/notification/notification_controller.dart';
import '../../controllers/profile/app_settings_controller.dart';
import '../../theme/pink_theme.dart';
import '../../utils/app_strings.dart';
import '../../widgets/common/skeleton.dart';
import '../../widgets/common/staggered_fade_slide.dart';

class ThongBaoScreen extends StatefulWidget {
  /// Callback để mở chi tiết đơn (nhận ma_hoa_don).
  final void Function(int maHoaDon)? onOpenOrder;

  const ThongBaoScreen({super.key, this.onOpenOrder});

  @override
  State<ThongBaoScreen> createState() => _ThongBaoScreenState();
}

class _ThongBaoScreenState extends State<ThongBaoScreen> {
  final _c = NotificationController();

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
            Text(
              tr('paid_detail'),
              style: TextStyle(
                color: Pink.plum,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 16),
            if (dsDon.isNotEmpty) ...[
              Text(
                tr('orders_word'),
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
            _row(tr('cod_collected'), tongCod),
            if (tienPhat > 0) _row(tr('late_fee'), tienPhat),
            const Divider(height: 24),
            _row(tr('total_paid'), tongNop, bold: true),
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
    return ListenableBuilder(
      listenable: AppSettings.instance,
      builder: (context, _) => _buildScaffold(),
    );
  }

  Widget _buildScaffold() {
    return Scaffold(
      backgroundColor: Pink.blush,
      appBar: AppBar(
        backgroundColor: Pink.blush,
        elevation: 0,
        foregroundColor: Pink.plum,
        title: Text(
          tr('notif_title'),
          style: TextStyle(color: Pink.plum, fontWeight: FontWeight.w800),
        ),
      ),
      body: ListenableBuilder(
        listenable: _c,
        builder: (context, _) {
          if (_c.loading && _c.items.isEmpty) {
            return _skeleton();
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
                    child: Text(tr('retry')),
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
                    _chip('all', tr('f_all'), _c.items.length),
                    _chip(
                      'order_new',
                      tr('notif_new'),
                      _c.demTheoLoai('order_new'),
                    ),
                    _chip(
                      'penalty',
                      tr('notif_penalty'),
                      _c.demTheoLoai('penalty'),
                    ),
                    _chip(
                      'payment',
                      tr('notif_payment'),
                      _c.demTheoLoai('payment'),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: () => _c.load(refresh: true),
                  child: list.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            Center(child: Text(tr('notif_empty'))),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                          itemCount: list.length,
                          separatorBuilder: (_, __) =>
                              const SizedBox(height: 10),
                          itemBuilder: (_, i) {
                            final tb = list[i];
                            final id = _int(tb['ma_thong_bao']);
                            return StaggeredFadeSlide(
                              key: ValueKey('anim_$id'),
                              index: i,
                              child: _NotifItem(
                                key: ValueKey('item_$id'),
                                onDeleted: () => _c.xoa(tb),
                                builder: (remove) => _buildCard(tb, remove),
                              ),
                            );
                          },
                        ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildCard(Map<String, dynamic> tb, VoidCallback remove) {
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
                    margin: const EdgeInsets.only(left: 8, top: 6),
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: Pink.rose,
                      shape: BoxShape.circle,
                    ),
                  ),
                const SizedBox(width: 4),
                InkResponse(
                  onTap: remove,
                  radius: 18,
                  child: Tooltip(
                    message: tr('delete'),
                    child: Padding(
                      padding: const EdgeInsets.all(4),
                      child: Icon(
                        Icons.close_rounded,
                        size: 18,
                        color: Pink.plum.withOpacity(0.45),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  _Cfg _cfgFor(String loai) {
    switch (loai) {
      case 'order_new':
        return _Cfg(
          tr('lbl_order_new'),
          Icons.receipt_long_rounded,
          Color(0xFF22c55e),
          Color(0xFFecfdf5),
          Color(0xFF16a34a),
        );
      case 'penalty':
        return _Cfg(
          tr('lbl_penalty'),
          Icons.warning_amber_rounded,
          Color(0xFFef4444),
          Color(0xFFfef2f2),
          Color(0xFFdc2626),
        );
      case 'payment':
        return _Cfg(
          tr('lbl_payment'),
          Icons.payments_rounded,
          Color(0xFF3b82f6),
          Color(0xFFeff6ff),
          Color(0xFF2563eb),
        );
      case 'shipping':
        return _Cfg(
          tr('f_shipping'),
          Icons.motorcycle_rounded,
          Color(0xFF3b82f6),
          Color(0xFFeff6ff),
          Color(0xFF2563eb),
        );
      default:
        return _Cfg(
          tr('lbl_info'),
          Icons.notifications_rounded,
          Color(0xFFe75480),
          Color(0xFFfce8ef),
          Color(0xFFe75480),
        );
    }
  }

  Widget _skeleton() => SkeletonShimmer(
    child: ListView.separated(
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      itemCount: 6,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (_, i) => StaggeredFadeSlide(
        index: i,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            SkeletonBox(width: 40, height: 40, radius: 20),
            SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SkeletonBox(width: 90, height: 12),
                  SizedBox(height: 8),
                  SkeletonBox(height: 14),
                  SizedBox(height: 6),
                  SkeletonBox(width: 120, height: 10),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
  );

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

/// Bọc 1 thẻ thông báo: khi bấm X thì thu nhỏ + mờ dần rồi mới xoá thật.
class _NotifItem extends StatefulWidget {
  final VoidCallback onDeleted;
  final Widget Function(VoidCallback remove) builder;
  const _NotifItem({super.key, required this.onDeleted, required this.builder});

  @override
  State<_NotifItem> createState() => _NotifItemState();
}

class _NotifItemState extends State<_NotifItem>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctl = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 320),
    value: 1,
  );
  bool _removing = false;

  @override
  void dispose() {
    _ctl.dispose();
    super.dispose();
  }

  Future<void> _remove() async {
    if (_removing) return;
    _removing = true;
    await _ctl.reverse();
    if (mounted) widget.onDeleted();
  }

  @override
  Widget build(BuildContext context) {
    final curved = CurvedAnimation(parent: _ctl, curve: Curves.easeInOut);
    return SizeTransition(
      sizeFactor: curved,
      axisAlignment: -1,
      child: FadeTransition(
        opacity: curved,
        child: SlideTransition(
          position: Tween<Offset>(
            begin: const Offset(0.25, 0),
            end: Offset.zero,
          ).animate(curved),
          child: widget.builder(_remove),
        ),
      ),
    );
  }
}
