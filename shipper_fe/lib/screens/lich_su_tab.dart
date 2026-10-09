// lib/screens/history_tab.dart
// Lịch sử đơn ĐÃ giao (DELIVERED). Chỉ phân trang, không lọc.

import 'package:flutter/material.dart';
import '../controllers/lich_su/lich_su_controller.dart';
import '../widgets/common/common.dart';
import '../widgets/common/glass_container.dart';
import '../widgets/common/pretty_json.dart';
import '../widgets/common/skeleton.dart';
import '../widgets/common/staggered_fade_slide.dart';
import 'don_hang/chi_tiet_don_hang_screen.dart';
import '../widgets/home/dashboard_card.dart';

class HistoryTab extends StatefulWidget {
  const HistoryTab({super.key});
  @override
  State<HistoryTab> createState() => _HistoryTabState();
}

class _HistoryTabState extends State<HistoryTab> {
  late final LichSuController _c = LichSuController();

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
        if (_c.loading && _c.items.isEmpty) return const ListSkeleton();

        if (_c.error != null && _c.items.isEmpty) {
          return SafeArea(
            bottom: false,
            child: ErrorBox(message: _c.error!, onRetry: () => _c.load()),
          );
        }

        return SafeArea(
          bottom: false,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
                child: _buildHeader(),
              ),
              Expanded(child: _buildList()),
            ],
          ),
        );
      },
    );
  }

  Widget _buildHeader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Lịch sử',
          style: TextStyle(
            color: kNavy,
            fontSize: 30,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'Đơn đã giao • Tổng ${_c.total}',
          style: TextStyle(color: kNavy.withOpacity(0.6), fontSize: 13),
        ),
        const SizedBox(height: 10),
        _buildPager(),
      ],
    );
  }

  Widget _buildPager() {
    return Row(
      children: [
        IconButton(
          onPressed: _c.page > 1 ? _c.prevPage : null,
          icon: const Icon(Icons.chevron_left),
        ),
        Text(
          'Trang ${_c.page}/${_c.totalPages}',
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        IconButton(
          onPressed: _c.page < _c.totalPages ? _c.nextPage : null,
          icon: const Icon(Icons.chevron_right),
        ),
        const Spacer(),
        if (_c.loading)
          const SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
      ],
    );
  }

  Widget _buildList() {
    final bottomPad = 130 + MediaQuery.of(context).padding.bottom;

    if (_c.items.isEmpty) {
      return RefreshIndicator(
        onRefresh: _c.refresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            SizedBox(
              height: MediaQuery.of(context).size.height * 0.4,
              child: const Center(child: Text('Chưa có đơn nào đã giao.')),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _c.refresh,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(20, 4, 20, bottomPad),
        itemCount: _c.items.length,
        itemBuilder: (context, i) {
          final item = _c.items[i];
          final id = item['ma_hoa_don'] ?? i;
          return StaggeredFadeSlide(
            key: ValueKey('ls-$id'),
            index: i,
            child: _HistoryTile(
              item: item,
              onTap: () {
                final maHD = guessId(item);
                if (maHD == null) return;
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) =>
                        OrderDetailScreen(maHoaDon: maHD.toString()),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback onTap;
  const _HistoryTile({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final title = guessTitle(item, 0);
    final kh = item['khachHang'];
    final tien = item['tong_tien'];
    final ngayGiao = item['ngay_giao']?.toString() ?? '';

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: GestureDetector(
        onTap: onTap,
        child: GlassContainer(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.green.withOpacity(0.15),
                ),
                alignment: Alignment.center,
                child: Icon(
                  Icons.check_circle,
                  color: Colors.green.shade700,
                  size: 22,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: kNavy,
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    if (kh is Map && kh['ten_khach_hang'] != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        'KH: ${kh['ten_khach_hang']}',
                        style: TextStyle(
                          color: kNavy.withOpacity(0.7),
                          fontSize: 13,
                        ),
                      ),
                    ],
                    if (ngayGiao.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        'Giao: $ngayGiao',
                        style: TextStyle(
                          color: kNavy.withOpacity(0.55),
                          fontSize: 12,
                        ),
                      ),
                    ],
                    if (tien != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        '$tien đ',
                        style: const TextStyle(
                          color: kNavy,
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              Icon(Icons.chevron_right, color: kNavy.withOpacity(0.4)),
            ],
          ),
        ),
      ),
    );
  }
}
