// lib/screens/hoa_don/danh_sach_hoa_don_tab.dart
// Danh sách đơn CHƯA giao (CONFIRMED + SHIPPING), lọc trạng thái + phân trang.
// Mỗi card: mã đơn, trạng thái, nhãn COD / Hoa tươi, tên khách, địa chỉ, SĐT.
// Bấm vào card -> OrderDetailScreen.

import 'package:flutter/material.dart';
import '../../controllers/don_hang_controller.dart';
import '../../theme/pink_theme.dart';
import '../../utils/format.dart';
import '../../widgets/common/glass_container.dart';
import '../../widgets/common/skeleton.dart';
import '../../widgets/common/staggered_fade_slide.dart';
import 'chi_tiet_don_hang_screen.dart'; // ← đổi path

class OrdersTab extends StatefulWidget {
  const OrdersTab({super.key});
  @override
  State<OrdersTab> createState() => _OrdersTabState();
}

class _OrdersTabState extends State<OrdersTab> {
  late final DonHangController _c = DonHangController();

  static const _filters = <String?, String>{
    null: 'Tất cả',
    'CONFIRMED': 'Chờ lấy',
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
        // Lần đầu chưa có dữ liệu -> skeleton
        if (_c.loading && _c.items.isEmpty) return const _OrdersSkeleton();

        if (_c.error != null && _c.items.isEmpty) {
          return SafeArea(
            bottom: false,
            child: PinkErrorBox(message: _c.error!, onRetry: () => _c.load()),
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
    return StaggeredFadeSlide(
      index: 0,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Đơn hàng',
                  style: TextStyle(
                    color: Pink.plum,
                    fontSize: 32,
                    fontWeight: FontWeight.w800,
                    height: 1.1,
                  ),
                ),
              ),
              if (_c.loading)
                const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Pink.rose,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Đơn chưa giao • Tổng ${_c.total}',
            style: const TextStyle(color: Pink.muted, fontSize: 13.5),
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _filters.entries
                .map(
                  (e) => _FilterPill(
                    label: e.value,
                    selected: _c.trangThai == e.key,
                    onTap: () => _c.setFilter(e.key),
                  ),
                )
                .toList(),
          ),
          if (_c.totalPages > 1) ...[const SizedBox(height: 6), _buildPager()],
        ],
      ),
    );
  }

  Widget _buildPager() {
    return Row(
      children: [
        IconButton(
          color: Pink.plum,
          onPressed: _c.page > 1 ? _c.prevPage : null,
          icon: const Icon(Icons.chevron_left_rounded),
        ),
        Text(
          'Trang ${_c.page}/${_c.totalPages}',
          style: const TextStyle(color: Pink.plum, fontWeight: FontWeight.w600),
        ),
        IconButton(
          color: Pink.plum,
          onPressed: _c.page < _c.totalPages ? _c.nextPage : null,
          icon: const Icon(Icons.chevron_right_rounded),
        ),
      ],
    );
  }

  Widget _buildList() {
    final bottomPad = 130 + MediaQuery.of(context).padding.bottom;

    // Rỗng -> vẫn cho kéo để refresh
    if (_c.items.isEmpty) {
      return RefreshIndicator(
        color: Pink.rose,
        onRefresh: _c.refresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            SizedBox(
              height: MediaQuery.of(context).size.height * 0.4,
              child: const Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.inbox_rounded, size: 44, color: Pink.faint),
                    SizedBox(height: 8),
                    Text(
                      'Không có đơn nào.',
                      style: TextStyle(color: Pink.muted, fontSize: 14),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      color: Pink.rose,
      onRefresh: _c.refresh,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(20, 4, 20, bottomPad),
        itemCount: _c.items.length,
        itemBuilder: (context, i) {
          final item = _c.items[i];
          final id = jStr(item['ma_hoa_don'], '$i');
          // Key theo mã đơn: card cũ giữ nguyên, card mới mới fade-in
          return StaggeredFadeSlide(
            key: ValueKey('don-$id'),
            index: i + 1, // 0 là header
            child: _OrderCard(
              item: item,
              onTap: () {
                if (id.isEmpty) return;
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => OrderDetailScreen(maHoaDon: id),
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

// ── Nút lọc ─────────────────────────────────────────────────────
class _FilterPill extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;
  const _FilterPill({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 220),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
        decoration: BoxDecoration(
          gradient: selected ? Pink.buttonGradient : null,
          color: selected ? null : Colors.white.withOpacity(0.4),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: selected
                ? Colors.transparent
                : Colors.white.withOpacity(0.7),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: selected ? Colors.white : Pink.plum,
            fontWeight: FontWeight.w700,
            fontSize: 13,
          ),
        ),
      ),
    );
  }
}

// ── Card 1 đơn ─────────────────────────────────────────────────
class _OrderCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback onTap;
  const _OrderCard({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final tt = jStr(item['trang_thai']);
    final st = statusStyle(tt, jStr(item['nhan_trang_thai']));
    final maHienThi = jStr(
      item['ma_hien_thi'],
      '#HD-${jStr(item['ma_hoa_don'])}',
    );
    final thanhToan = jMap(item['thanh_toan']);
    final kh = jMap(item['khach_hang']);
    final laCod = jBool(thanhToan['la_cod']);
    final laHoaTuoi = jBool(item['la_hoa_tuoi']);
    final canThu = jBool(thanhToan['can_thu_tien_mat']);
    final soTienThu = jNum(thanhToan['so_tien_can_thu']);
    final tong = jNum(item['tong_tien']);

    final ten = jStr(kh['ten'], 'Khách hàng');
    final sdt = jStr(kh['so_dien_thoai']);
    final diaChi = jStr(item['dia_chi_giao']);

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: onTap,
        child: PinkCard(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      maHienThi,
                      style: const TextStyle(
                        color: Pink.plum,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  PinkChip(label: st.label, color: st.color),
                ],
              ),
              if (laCod || laHoaTuoi) ...[
                const SizedBox(height: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    if (laCod)
                      const PinkChip(
                        label: 'COD',
                        color: Pink.violet,
                        icon: Icons.payments_rounded,
                      ),
                    if (laHoaTuoi)
                      const PinkChip(label: '🌸 Hoa tươi', color: Pink.leaf),
                  ],
                ),
              ],
              const SizedBox(height: 12),
              _line(Icons.person_rounded, ten, bold: true),
              if (diaChi.isNotEmpty) ...[
                const SizedBox(height: 6),
                _line(Icons.location_on_rounded, diaChi, maxLines: 2),
              ],
              if (sdt.isNotEmpty) ...[
                const SizedBox(height: 6),
                _line(Icons.phone_rounded, sdt),
              ],
              const SizedBox(height: 12),
              Container(height: 1, color: Colors.white.withOpacity(0.6)),
              const SizedBox(height: 10),
              Row(
                children: [
                  Text(
                    canThu ? 'Cần thu' : 'Tổng tiền',
                    style: const TextStyle(color: Pink.muted, fontSize: 13),
                  ),
                  const Spacer(),
                  Text(
                    fmtMoney(canThu ? soTienThu : tong),
                    style: const TextStyle(
                      color: Pink.roseDeep,
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(Icons.chevron_right_rounded, color: Pink.faint),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _line(
    IconData icon,
    String text, {
    int maxLines = 1,
    bool bold = false,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 1),
          child: Icon(icon, size: 16, color: Pink.rose),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            maxLines: maxLines,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: bold ? Pink.plum : const Color(0xCC3A1230),
              fontSize: bold ? 15 : 13.5,
              fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
              height: 1.3,
            ),
          ),
        ),
      ],
    );
  }
}

// ── Skeleton đúng hình dạng danh sách ───────────────────────────
class _OrdersSkeleton extends StatelessWidget {
  const _OrdersSkeleton();

  @override
  Widget build(BuildContext context) {
    final bottomPad = 130 + MediaQuery.of(context).padding.bottom;
    return SkeletonShimmer(
      child: SafeArea(
        bottom: false,
        child: SingleChildScrollView(
          physics: const NeverScrollableScrollPhysics(),
          padding: EdgeInsets.fromLTRB(20, 16, 20, bottomPad),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SkeletonBox(width: 170, height: 32, radius: 10),
              const SizedBox(height: 10),
              const SkeletonBox(width: 150, height: 13),
              const SizedBox(height: 16),
              const Row(
                children: [
                  SkeletonBox(width: 76, height: 34, radius: 18),
                  SizedBox(width: 8),
                  SkeletonBox(width: 88, height: 34, radius: 18),
                  SizedBox(width: 8),
                  SkeletonBox(width: 88, height: 34, radius: 18),
                ],
              ),
              const SizedBox(height: 22),
              for (int i = 0; i < 4; i++) const _OrderCardSkeleton(),
            ],
          ),
        ),
      ),
    );
  }
}

class _OrderCardSkeleton extends StatelessWidget {
  const _OrderCardSkeleton();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.only(bottom: 14),
      child: GlassContainer(
        blur: 0,
        padding: EdgeInsets.fromLTRB(16, 14, 16, 14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                SkeletonBox(width: 110, height: 18),
                Spacer(),
                SkeletonBox(width: 70, height: 24, radius: 12),
              ],
            ),
            SizedBox(height: 10),
            Row(
              children: [
                SkeletonBox(width: 52, height: 22, radius: 11),
                SizedBox(width: 6),
                SkeletonBox(width: 84, height: 22, radius: 11),
              ],
            ),
            SizedBox(height: 14),
            SkeletonBox(width: 150, height: 15),
            SizedBox(height: 8),
            SkeletonBox(width: double.infinity, height: 13),
            SizedBox(height: 8),
            SkeletonBox(width: 120, height: 13),
            SizedBox(height: 16),
            Row(
              children: [
                SkeletonBox(width: 70, height: 13),
                Spacer(),
                SkeletonBox(width: 90, height: 18),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
