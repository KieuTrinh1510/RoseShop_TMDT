// lib/screens/mock_pages.dart
// 4 trang mock chỉ để kiểm tra thanh menu + chuyển trang.
// Sau này thay từng trang bằng tab thật (StatusTab, DashboardTab, OrdersTab, HistoryTab).
import 'package:flutter/material.dart';
import '../config.dart';
import '../widgets/dashboard_card.dart';
import '../widgets/glass_container.dart';
import '../widgets/staggered_fade_slide.dart';
import 'login_screen.dart';

// Chừa chỗ phía dưới cho thanh nav nổi (72 + 30 + margin + safe area)
double _bottomSpace(BuildContext context) =>
    130 + MediaQuery.of(context).padding.bottom;

class _PageScaffold extends StatelessWidget {
  final String title;
  final String subtitle;
  final List<Widget> children;
  final int stepMs;

  const _PageScaffold({
    required this.title,
    required this.subtitle,
    required this.children,
    this.stepMs = 70,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      bottom: false,
      child: ListView(
        // luôn cho kéo để pull-to-refresh hoạt động kể cả khi nội dung ngắn
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(20, 16, 20, _bottomSpace(context)),
        children: [
          StaggeredFadeSlide(
            index: 0,
            stepMs: stepMs,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: kNavy,
                    fontSize: 34,
                    fontWeight: FontWeight.w800,
                    height: 1.1,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: TextStyle(
                    color: kNavy.withOpacity(0.65),
                    fontSize: 15,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 22),
          ...children,
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------- Home
class _Stat {
  final String title;
  final String value;
  final String subtitle;
  final IconData icon;
  const _Stat(this.title, this.value, this.subtitle, this.icon);
}

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  static const _stats = [
    _Stat(
      'Tổng đơn hàng',
      '128',
      '+12% so với tháng trước',
      Icons.inventory_2_rounded,
    ),
    _Stat(
      'Đang xử lý',
      '14',
      '3 đơn cần giao gấp',
      Icons.local_shipping_rounded,
    ),
    _Stat('Hoàn thành', '109', 'Tỷ lệ 85%', Icons.check_circle_rounded),
    _Stat('Doanh thu', '12.4tr', 'Tháng này', Icons.payments_rounded),
  ];

  static const _step = 80; // delay giữa các card (ms)

  @override
  Widget build(BuildContext context) {
    return _PageScaffold(
      title: 'Xin chào!',
      subtitle: 'Quản lý đơn hàng của bạn',
      stepMs: _step,
      children: [
        LayoutBuilder(
          builder: (context, c) {
            const gap = 12.0;
            final w = (c.maxWidth - gap) / 2;
            return Wrap(
              spacing: gap,
              runSpacing: gap,
              children: [
                for (int i = 0; i < _stats.length; i++)
                  SizedBox(
                    width: w,
                    child: StaggeredFadeSlide(
                      index: i + 1, // 0 là header
                      stepMs: _step,
                      child: DashboardCard(
                        title: _stats[i].title,
                        value: _stats[i].value,
                        subtitle: _stats[i].subtitle,
                        icon: _stats[i].icon,
                      ),
                    ),
                  ),
              ],
            );
          },
        ),
        const SizedBox(height: 12),
        StaggeredFadeSlide(
          index: _stats.length + 1,
          stepMs: _step,
          child: GlassContainer(
            child: Row(
              children: [
                const Icon(Icons.bolt_rounded, color: kNavy),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Lối tắt: nhận đơn mới, xem đơn gần nhất, báo cáo nhanh',
                    style: TextStyle(color: kNavy.withOpacity(0.8)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

// -------------------------------------------------------------- Orders
class OrdersPage extends StatelessWidget {
  const OrdersPage({super.key});

  @override
  Widget build(BuildContext context) {
    return _PageScaffold(
      title: 'Đơn hàng',
      subtitle: 'Danh sách đơn đang xử lý',
      children: [
        for (int i = 1; i <= 6; i++)
          StaggeredFadeSlide(
            index: i, // 0 là header
            child: Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _MockTile(
                icon: Icons.receipt_long_rounded,
                title: 'Đơn #${1000 + i}',
                subtitle: i.isEven
                    ? 'SHIPPING • 250.000đ'
                    : 'CONFIRMED • 180.000đ',
              ),
            ),
          ),
      ],
    );
  }
}

// ------------------------------------------------------------- History
class HistoryPage extends StatelessWidget {
  const HistoryPage({super.key});

  @override
  Widget build(BuildContext context) {
    return _PageScaffold(
      title: 'Lịch sử',
      subtitle: 'Các đơn đã giao',
      children: [
        for (int i = 1; i <= 6; i++)
          StaggeredFadeSlide(
            index: i, // 0 là header
            child: Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _MockTile(
                icon: Icons.history_rounded,
                title: 'Đơn #${900 + i}',
                subtitle: 'Đã giao • ${i + 2}/09/2026',
              ),
            ),
          ),
      ],
    );
  }
}

// ------------------------------------------------------------- Profile
class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    return _PageScaffold(
      title: 'Profile',
      subtitle: 'Thông tin tài khoản',
      children: [
        StaggeredFadeSlide(
          index: 1,
          child: GlassContainer(
            child: Row(
              children: [
                const CircleAvatar(
                  radius: 28,
                  backgroundColor: kNavy,
                  child: Icon(Icons.person_rounded, color: Colors.white),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Mã nhân viên: ${AppConfig.maNhanVien}',
                        style: const TextStyle(
                          color: kNavy,
                          fontSize: 17,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        AppConfig.baseUrl,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: kNavy.withOpacity(0.6),
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        StaggeredFadeSlide(
          index: 2,
          child: GlassContainer(
            padding: EdgeInsets.zero,
            child: ListTile(
              leading: const Icon(Icons.badge_outlined, color: kNavy),
              title: const Text('Đổi mã nhân viên / Base URL'),
              trailing: const Icon(Icons.chevron_right, color: kNavy),
              onTap: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const LoginScreen()),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _MockTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  const _MockTile({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return GlassContainer(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          Icon(icon, color: kNavy),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: kNavy,
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: TextStyle(color: kNavy.withOpacity(0.6), fontSize: 13),
                ),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: kNavy.withOpacity(0.5)),
        ],
      ),
    );
  }
}
