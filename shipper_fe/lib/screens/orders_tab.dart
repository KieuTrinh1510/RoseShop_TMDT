// lib/screens/orders_tab.dart
// Danh sách đơn CHƯA giao (CONFIRMED + SHIPPING), lọc trạng thái + phân trang.

import 'package:flutter/material.dart';
import '../controllers/don_hang_controller.dart';
import '../widgets/common.dart';
import '../widgets/glass_container.dart';
import '../widgets/pretty_json.dart';
import '../widgets/skeleton.dart';
import '../widgets/staggered_fade_slide.dart';
import 'order_detail_screen.dart';

const kNavy = Color(0xFF0B0B1A);

class OrdersTab extends StatefulWidget {
  const OrdersTab({super.key});
  @override
  State<OrdersTab> createState() => _OrdersTabState();
}

class _OrdersTabState extends State<OrdersTab> {
  late final DonHangController _c = DonHangController();

  static const _filters = <String?, String>{
    null: 'Tất cả',
    'CONFIRMED': 'Chờ lấy hàng',
    'SHIPPING': 'Đang giao',
  };

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
        // Lần đầu chưa có items → skeleton
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
          'Đơn hàng',
          style: TextStyle(
            color: kNavy,
            fontSize: 30,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'Đơn chưa giao • Tổng ${_c.total}',
          style: TextStyle(color: kNavy.withOpacity(0.6), fontSize: 13),
        ),
        const SizedBox(height: 14),
        _buildFilters(),
        const SizedBox(height: 6),
        _buildPager(),
      ],
    );
  }

  Widget _buildFilters() {
    return Wrap(
      spacing: 8,
      children: _filters.entries.map((e) {
        final selected = _c.trangThai == e.key;
        return ChoiceChip(
          label: Text(e.value),
          selected: selected,
          onSelected: (_) => _c.setFilter(e.key),
        );
      }).toList(),
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
        // Đang tải nhưng vẫn còn items cũ → chỉ hiện indicator nhỏ
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

    // Rỗng → vẫn cho kéo refresh
    if (_c.items.isEmpty) {
      return RefreshIndicator(
        onRefresh: _c.refresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            SizedBox(
              height: MediaQuery.of(context).size.height * 0.4,
              child: const Center(child: Text('Không có đơn nào.')),
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
          // Key ổn định theo mã đơn: item cũ giữ state, item mới mới fade-in.
          final id = item['ma_hoa_don'] ?? i;
          return StaggeredFadeSlide(
            key: ValueKey('don-$id'),
            index: i,
            child: _OrderTile(
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
                ).then((_) => _c.refresh());
              },
            ),
          );
        },
      ),
    );
  }
}

// ── Tile 1 đơn ──────────────────────────────────────────────────
class _OrderTile extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback onTap;
  const _OrderTile({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final title = guessTitle(item, 0);
    final tt = item['trang_thai']?.toString() ?? '';
    final tien = item['tong_tien'];
    final kh = item['khachHang'];

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: GestureDetector(
        onTap: onTap,
        child: GlassContainer(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                        color: kNavy,
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  _StatusChip(status: tt),
                ],
              ),
              if (kh is Map && kh['ten_khach_hang'] != null) ...[
                const SizedBox(height: 6),
                Text(
                  'KH: ${kh['ten_khach_hang']}',
                  style: TextStyle(color: kNavy.withOpacity(0.7), fontSize: 13),
                ),
              ],
              if (item['dia_chi_giao'] != null) ...[
                const SizedBox(height: 4),
                Text(
                  item['dia_chi_giao'].toString(),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: kNavy.withOpacity(0.6), fontSize: 12),
                ),
              ],
              if (tien != null) ...[
                const SizedBox(height: 8),
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
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String status;
  const _StatusChip({required this.status});

  @override
  Widget build(BuildContext context) {
    MaterialColor bg;
    String label;
    switch (status) {
      case 'CONFIRMED':
        bg = Colors.orange;
        label = 'Chờ lấy';
        break;
      case 'SHIPPING':
        bg = Colors.blue;
        label = 'Đang giao';
        break;
      case 'DELIVERED':
        bg = Colors.green;
        label = 'Đã giao';
        break;
      default:
        bg = Colors.grey;
        label = status.isEmpty ? '—' : status;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg.withOpacity(0.15),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: bg.withOpacity(0.4)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: bg.shade700,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
