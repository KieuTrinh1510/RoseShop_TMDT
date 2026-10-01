// lib/screens/home_screen.dart
// 4 tab (Home / Đơn hàng / Lịch sử / Profile). IndexedStack giữ state các tab
// đã mở; tab chưa bấm vào thì chưa build (lazy) để không gọi API thừa.

import 'package:flutter/material.dart';
import '../widgets/liquid_bottom_nav.dart';
import 'home_tab.dart';
import 'orders_tab.dart';
import 'history_tab.dart';
import 'profile_tab.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;

  /// Tab đã được người dùng vào ít nhất 1 lần → mới build & giữ state.
  final Set<int> _visited = {0};

  static const _items = [
    NavItemData(icon: Icons.home_rounded, label: 'Home'),
    NavItemData(icon: Icons.receipt_long_rounded, label: 'Đơn hàng'),
    NavItemData(icon: Icons.history_rounded, label: 'Lịch sử'),
    NavItemData(icon: Icons.person_rounded, label: 'Profile'),
  ];

  void _onTap(int i) {
    if (i == _index) return;
    setState(() {
      _index = i;
      _visited.add(i);
    });
  }

  Widget _tabAt(int i) {
    if (!_visited.contains(i)) return const SizedBox.shrink();
    switch (i) {
      case 0:
        return const HomeTab();
      case 1:
        return const OrdersTab();
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

    return Scaffold(
      backgroundColor: const Color(0xFF7B808D),
      body: Stack(
        children: [
          const _Background(),
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
    );
  }
}

class _Background extends StatelessWidget {
  const _Background();
  @override
  Widget build(BuildContext context) {
    return Positioned.fill(
      child: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF9096A3), Color(0xFF6C7280)],
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              top: -60,
              right: -50,
              child: _blob(220, Colors.white.withOpacity(0.18)),
            ),
            Positioned(
              top: 280,
              left: -80,
              child: _blob(240, const Color(0xFF0B0B1A).withOpacity(0.18)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _blob(double size, Color color) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(shape: BoxShape.circle, color: color),
  );
}
