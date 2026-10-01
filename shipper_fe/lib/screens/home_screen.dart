// lib/screens/home_screen.dart
// Giữ nguyên tên class HomeScreen để login_screen.dart không phải sửa gì.
import 'package:flutter/material.dart';
import '../widgets/liquid_bottom_nav.dart';
import '../widgets/loadable_view.dart';
import '../widgets/skeleton.dart';
import 'mock_pages.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;

  static const _items = [
    NavItemData(icon: Icons.home_rounded, label: 'Home'),
    NavItemData(icon: Icons.receipt_long_rounded, label: 'Đơn hàng'),
    NavItemData(icon: Icons.history_rounded, label: 'Lịch sử'),
    NavItemData(icon: Icons.person_rounded, label: 'Profile'),
  ];

  static const _pages = <Widget>[
    HomePage(),
    OrdersPage(),
    HistoryPage(),
    ProfilePage(),
  ];

  static const _skeletons = <Widget>[
    HomeSkeleton(),
    ListSkeleton(),
    ListSkeleton(),
    ProfileSkeleton(),
  ];

  // ---- MOCK LOADER -------------------------------------------------------
  // Khi nối API thật: thay hàm này bằng lời gọi ApiService cho từng tab,
  // trả về dữ liệu (List/Map/model) rồi dùng nó trong builder bên dưới.
  static const bool _simulateError =
      false; // đổi true để test màn Error + Retry

  Future<bool> _mockLoad() async {
    await Future<void>.delayed(const Duration(milliseconds: 600));
    if (_simulateError) throw Exception('Mô phỏng lỗi mạng');
    return true;
  }
  // -----------------------------------------------------------------------

  void _onTap(int i) {
    if (i == _index) return;
    setState(() => _index = i);
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      backgroundColor: const Color(0xFF7B808D),
      body: Stack(
        children: [
          // Nền xám lạnh + vài đốm sáng để hiệu ứng kính có chiều sâu
          const _Background(),

          // Đổi tab: trang cũ bị gỡ ngay (không cross-fade, không chồng hình).
          // Trang mới: Skeleton -> dữ liệu thật -> từng item fade + slide up.
          Positioned.fill(
            child: LoadableView<bool>(
              key: ValueKey<int>(_index),
              loader: _mockLoad,
              skeleton: _skeletons[_index],
              builder: (context, data) => _pages[_index],
            ),
          ),

          // Thanh nav nổi, nằm trong SafeArea (cộng inset đáy)
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
