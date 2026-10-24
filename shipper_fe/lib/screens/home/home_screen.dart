// lib/screens/home/home_screen.dart
// Shell 4 tab (Home / Đơn hàng / Lịch sử / Profile).
// IndexedStack giữ state các tab đã mở; tab chưa bấm vào thì chưa build (lazy).

import 'package:flutter/material.dart';
import '../../controllers/profile/app_settings_controller.dart';
import '../../utils/app_strings.dart';
import '../../widgets/common/liquid_bottom_nav.dart';
import '../../theme/pink_theme.dart';
import 'home_tab.dart';
import '../orders/order_list_tab.dart';
import '../history/history_tab.dart';
import '../profile/profile_tab.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override
  State<HomeScreen> createState() => HomeScreenState();
}

class HomeScreenState extends State<HomeScreen> {
  int _index = 0;
  final Set<int> _visited = {0};

  /// Key để điều khiển OrdersTab từ bên ngoài (mở chi tiết đơn khi tap thông báo).
  final GlobalKey<OrdersTabState> _ordersKey = GlobalKey<OrdersTabState>();

  List<NavItemData> get _items => [
    NavItemData(icon: Icons.home_rounded, label: tr('nav_home')),
    NavItemData(icon: Icons.receipt_long_rounded, label: tr('nav_orders')),
    NavItemData(icon: Icons.history_rounded, label: tr('nav_history')),
    NavItemData(icon: Icons.person_rounded, label: tr('nav_profile')),
  ];

  /// Về tab Trang chủ (dùng sau khi đổi mật khẩu xong).
  void goHome() {
    if (_index == 0) return;
    setState(() => _index = 0);
  }

  void _onTap(int i) {
    if (i == _index) return;
    setState(() {
      _index = i;
      _visited.add(i);
    });
  }

  /// Gọi từ HomeTab khi user tap thông báo "đơn mới":
  /// 1) switch sang tab Đơn hàng, 2) gọi OrdersTab mở chi tiết đơn.
  void openOrderFromNotification(int maHoaDon) {
    setState(() {
      _index = 1;
      _visited.add(1);
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _ordersKey.currentState?.openOrder(maHoaDon);
    });
  }

  Widget _tabAt(int i) {
    if (!_visited.contains(i)) return const SizedBox.shrink();
    switch (i) {
      case 0:
        return HomeTab(
          active: _index == 0,
          onOpenOrder: openOrderFromNotification,
        );
      case 1:
        return OrdersTab(key: _ordersKey, active: _index == 1);
      case 2:
        return const HistoryTab();
      case 3:
        return const ProfileTab();
      default:
        return const SizedBox.shrink();
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;

    return ListenableBuilder(
      listenable: AppSettings.instance,
      builder: (context, _) => Scaffold(
        backgroundColor: Pink.blush,
        body: Stack(
          children: [
            const PinkBackground(),
            Positioned.fill(
              child: IndexedStack(
                index: _index,
                children: List.generate(4, _tabAt),
              ),
            ),
            Positioned(
              left: 16,
              right: 16,
              bottom: 12 + bottomInset,
              child: LiquidBottomNav(
                items: _items,
                currentIndex: _index,
                onTap: _onTap,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
