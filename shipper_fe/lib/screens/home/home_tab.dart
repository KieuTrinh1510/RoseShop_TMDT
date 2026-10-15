// lib/screens/home/home_tab.dart
// Màn hình Home: header (avatar + tên + chuông), toggle online,
// card số đơn, card tiền cần nộp.

import 'dart:async';

import 'package:flutter/material.dart';
import '../../controllers/profile/app_settings_controller.dart';
import '../../utils/app_strings.dart';
import '../../widgets/common/user_avatar.dart';
import '../../controllers/home/home_controller.dart';
import '../../theme/pink_theme.dart';
import '../../widgets/common/common.dart';
import '../../widgets/common/glass_container.dart';
import '../../widgets/common/skeleton.dart';
import '../../widgets/common/staggered_fade_slide.dart';
import '../../widgets/home/dashboard_card.dart';
import '../../widgets/home/notification_bell.dart';
import '../notification/notification_screen.dart';

class HomeTab extends StatefulWidget {
  /// true khi tab đang hiển thị (HomeScreen truyền xuống).
  final bool active;

  /// Callback điều hướng khi user tap thông báo "đơn mới".
  final void Function(int maHoaDon)? onOpenOrder;

  const HomeTab({super.key, this.active = true, this.onOpenOrder});

  @override
  State<HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<HomeTab> with WidgetsBindingObserver {
  late final HomeController _c = HomeController();

  // ── Tự làm mới (không cần kéo thả) ─────────────────────────────
  static const _autoRefreshEvery = Duration(seconds: 15);
  Timer? _timer;
  bool _refreshing = false; // chặn 2 request chạy chồng nhau
  bool _inForeground = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _c.load();
    _startTimer();
  }

  @override
  void didUpdateWidget(covariant HomeTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.active != oldWidget.active) {
      if (widget.active) _autoRefresh();
      _startTimer();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final fg = state == AppLifecycleState.resumed;
    if (fg == _inForeground) return;
    _inForeground = fg;
    if (fg && widget.active) _autoRefresh();
    _startTimer();
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = null;
    if (widget.active && _inForeground) {
      _timer = Timer.periodic(_autoRefreshEvery, (_) => _autoRefresh());
    }
  }

  Future<void> _autoRefresh() async {
    if (!mounted || _refreshing || _c.loading) return;
    _refreshing = true;
    try {
      await _c.load(refresh: true);
    } finally {
      _refreshing = false;
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: Listenable.merge([_c, AppSettings.instance]),
      builder: (context, _) {
        if (_c.loading && _c.data == null) return const HomeSkeleton();

        if (_c.error != null && _c.data == null) {
          return SafeArea(
            bottom: false,
            child: ErrorBox(message: _c.error!, onRetry: () => _c.load()),
          );
        }

        return RefreshIndicator(
          edgeOffset: MediaQuery.of(context).padding.top,
          onRefresh: () => _c.load(refresh: true),
          child: _buildBody(context),
        );
      },
    );
  }

  Widget _buildBody(BuildContext context) {
    final bottomPad = 130 + MediaQuery.of(context).padding.bottom;

    return SafeArea(
      bottom: false,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(20, 16, 20, bottomPad),
        children: [
          StaggeredFadeSlide(index: 0, child: _buildHeader(context)),
          const SizedBox(height: 18),
          StaggeredFadeSlide(index: 1, child: _buildStatusToggle(context)),
          const SizedBox(height: 18),
          StaggeredFadeSlide(index: 2, child: _buildStatsGrid()),
        ],
      ),
    );
  }

  // ── Header: avatar + tên + chuông ─────────────────────────────
  Widget _buildHeader(BuildContext context) {
    return Row(
      children: [
        const UserAvatar(size: 52),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                tr('home_hello', {'name': _c.tenNhanVien}),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Pink.plum,
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                '${tr('employee_id')}: ${_c.maNhanVien}',
                style: TextStyle(
                  color: Pink.plum.withOpacity(0.6),
                  fontSize: 13,
                ),
              ),
            ],
          ),
        ),
        NotificationBell(
          count: _c.thongBaoChuaDoc,
          color: Pink.plum,
          onTap: () async {
            await Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => ThongBaoScreen(onOpenOrder: widget.onOpenOrder),
              ),
            );
            // Sau khi quay lại: refresh badge (số chưa đọc đã giảm khi user đọc trong màn thông báo)
            _autoRefresh();
          },
        ),
      ],
    );
  }

  // ── Card toggle ONLINE/OFFLINE ────────────────────────────────
  Widget _buildStatusToggle(BuildContext context) {
    final isOnline = _c.isOnline;
    return GlassContainer(
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isOnline
                  ? Pink.leaf.withOpacity(0.15)
                  : Pink.grey.withOpacity(0.2),
            ),
            alignment: Alignment.center,
            child: Icon(
              isOnline ? Icons.wifi_tethering : Icons.wifi_tethering_off,
              color: isOnline ? Pink.leaf : Pink.grey,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isOnline ? tr('online_on') : tr('online_off'),
                  style: const TextStyle(
                    color: Pink.plum,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  isOnline
                      ? (_c.isFull
                            ? tr('full_msg', {'n': '${_c.gioiHanDon}'})
                            : tr('ready'))
                      : tr('turn_on'),
                  style: TextStyle(
                    color: Pink.plum.withOpacity(0.6),
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          if (_c.toggling)
            const Padding(
              padding: EdgeInsets.only(right: 8),
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            ),
          Switch(
            value: isOnline,
            activeColor: Pink.rose,
            onChanged: _c.toggling
                ? null
                : (v) async {
                    final err = await _c.toggleOnline(v);
                    if (err != null && context.mounted) {
                      ScaffoldMessenger.of(
                        context,
                      ).showSnackBar(SnackBar(content: Text(err)));
                    }
                  },
          ),
        ],
      ),
    );
  }

  // ── Grid 2 card: số đơn + tiền cần nộp ────────────────────────
  Widget _buildStatsGrid() {
    return LayoutBuilder(
      builder: (context, c) {
        const gap = 12.0;
        final w = (c.maxWidth - gap) / 2;
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            SizedBox(
              width: w,
              child: DashboardCard(
                title: tr('card_processing'),
                value: '${_c.soDonHienTai}/${_c.gioiHanDon}',
                subtitle: _c.isFull ? tr('card_full') : tr('card_can_more'),
                icon: Icons.inventory_2_rounded,
              ),
            ),
            SizedBox(
              width: w,
              child: DashboardCard(
                title: tr('card_cod'),
                value: _formatTien(_c.codCanNop),
                subtitle: _c.codPhu,
                icon: Icons.payments_rounded,
                onTap: () => _showCodBreakdown(context),
              ),
            ),
          ],
        );
      },
    );
  }

  void _showCodBreakdown(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
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
              tr('cod_detail_title'),
              style: const TextStyle(
                color: Pink.plum,
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 16),
            _codRow(tr('cod_collected'), _c.tienCod),
            _codRow(tr('late_fee'), _c.tienPhat),
            const Divider(height: 28),
            _codRow(tr('cod_total'), _c.codCanNop, bold: true),
          ],
        ),
      ),
    );
  }

  Widget _codRow(String label, double value, {bool bold = false}) {
    return Padding(
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
            _formatTien(value),
            style: TextStyle(
              color: Pink.plum,
              fontSize: bold ? 18 : 15,
              fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  // Nhận num để chấp nhận cả int (backend) lẫn double.
  static String _formatTien(num v) {
    final n = v.toDouble();
    if (n <= 0) return '0đ';
    final s = n.toStringAsFixed(0);
    final buf = StringBuffer();
    for (int i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) buf.write('.');
      buf.write(s[i]);
    }
    return '${buf.toString()}đ';
  }
}
