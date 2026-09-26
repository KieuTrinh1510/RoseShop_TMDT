// lib/screens/home_screen.dart
import 'package:flutter/material.dart';
import '../config.dart';
import 'status_tab.dart';
import 'dashboard_tab.dart';
import 'orders_tab.dart';
import 'history_tab.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;

  static const _titles = ['Trạng thái', 'Dashboard', 'Đơn hàng', 'Lịch sử'];

  // Dùng key để mỗi lần build lại tab thì state (fetch) cũng khởi tạo lại
  static const _tabs = [
    StatusTab(key: PageStorageKey('status')),
    DashboardTab(key: PageStorageKey('dashboard')),
    OrdersTab(key: PageStorageKey('orders')),
    HistoryTab(key: PageStorageKey('history')),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_titles[_index]),
        actions: [
          IconButton(
            tooltip: 'Đổi mã nhân viên test (${AppConfig.maNhanVien})',
            icon: const Icon(Icons.badge_outlined),
            onPressed: () {
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const LoginScreen()),
              );
            },
          ),
        ],
      ),
      body: IndexedStack(index: _index, children: _tabs),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.wifi_tethering),
            label: 'Trạng thái',
          ),
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            label: 'Dashboard',
          ),
          NavigationDestination(icon: Icon(Icons.list_alt), label: 'Đơn hàng'),
          NavigationDestination(icon: Icon(Icons.history), label: 'Lịch sử'),
        ],
      ),
    );
  }
}
