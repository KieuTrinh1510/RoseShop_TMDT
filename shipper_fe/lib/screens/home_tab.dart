// lib/screens/home_tab.dart
// Màn hình Home: header (avatar + tên + chuông), toggle online, card số đơn, card COD.

import 'package:flutter/material.dart';
import '../controllers/home_controller.dart';
import '../widgets/common.dart';
import '../widgets/dashboard_card.dart';
import '../widgets/glass_container.dart';
import '../widgets/notification_bell.dart';
import '../widgets/skeleton.dart';
import '../widgets/staggered_fade_slide.dart';

class HomeTab extends StatefulWidget {
  const HomeTab({super.key});
  @override
  State<HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<HomeTab> {
  late final HomeController _c = HomeController();

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

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: _c,
      builder: (context, _) {
        // Lần đầu chưa có dữ liệu → skeleton
        if (_c.loading && _c.data == null) return const HomeSkeleton();

        // Lỗi lần đầu, không có gì để hiển thị → ErrorBox
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
        Container(
          width: 52,
          height: 52,
          decoration: const BoxDecoration(shape: BoxShape.circle, color: kNavy),
          alignment: Alignment.center,
          child: Text(
            _c.chuCaiDau,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Xin chào, ${_c.tenNhanVien}!',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: kNavy,
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                'Mã nhân viên: ${_c.maNhanVien}',
                style: TextStyle(color: kNavy.withOpacity(0.6), fontSize: 13),
              ),
            ],
          ),
        ),
        NotificationBell(
          count: _c.thongBaoChuaDoc,
          onTap: () {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Chưa có API danh sách thông báo')),
            );
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
                  ? Colors.green.withOpacity(0.15)
                  : Colors.grey.withOpacity(0.2),
            ),
            alignment: Alignment.center,
            child: Icon(
              isOnline ? Icons.wifi_tethering : Icons.wifi_tethering_off,
              color: isOnline ? Colors.green.shade700 : Colors.grey.shade700,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isOnline ? 'Đang ONLINE' : 'Đang OFFLINE',
                  style: const TextStyle(
                    color: kNavy,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  isOnline
                      ? (_c.isFull
                            ? 'Đã đủ ${_c.gioiHanDon}/${_c.gioiHanDon} đơn'
                            : 'Sẵn sàng nhận đơn mới')
                      : 'Bật để nhận đơn mới',
                  style: TextStyle(color: kNavy.withOpacity(0.6), fontSize: 12),
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

  // ── Grid 2 card: số đơn + tiền COD ───────────────────────────
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
                title: 'Đơn đang xử lý',
                value: '${_c.soDonHienTai}/${_c.gioiHanDon}',
                subtitle: _c.isFull
                    ? 'Đã đầy, không nhận thêm'
                    : 'Có thể nhận thêm đơn',
                icon: Icons.inventory_2_rounded,
              ),
            ),
            SizedBox(
              width: w,
              child: DashboardCard(
                title: 'Tiền COD cần nộp',
                value: _formatTien(_c.codCanNop),
                subtitle: '${_c.codSoDon} đơn đã giao',
                icon: Icons.payments_rounded,
              ),
            ),
          ],
        );
      },
    );
  }

  static String _formatTien(double v) {
    if (v <= 0) return '0đ';
    final s = v.toStringAsFixed(0);
    final buf = StringBuffer();
    for (int i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) buf.write('.');
      buf.write(s[i]);
    }
    return '${buf.toString()}đ';
  }
}
