// lib/screens/history/history_tab.dart
// Tab Lịch sử: danh sách đơn đã giao + filter ngày/khoảng/tháng/mã + phân trang 10/trang.
// Đổi filter / trang → skeleton → kết quả hiện ra theo kiểu fade + slide.

import 'package:flutter/material.dart';
import '../../controllers/profile/app_settings_controller.dart';
import '../../utils/app_strings.dart';
import '../../controllers/history/history_controller.dart';
import '../../theme/pink_theme.dart';
import '../../utils/format.dart';
import '../../widgets/common/common.dart';
import '../../widgets/common/glass_container.dart';
import '../../widgets/common/skeleton.dart';
import '../../widgets/common/staggered_fade_slide.dart';
import '../../widgets/history/history_card.dart';
import '../../widgets/history/history_filter_bar.dart';
import '../orders/order_detail_screen.dart';

class HistoryTab extends StatefulWidget {
  const HistoryTab({super.key});
  @override
  State<HistoryTab> createState() => _HistoryTabState();
}

class _HistoryTabState extends State<HistoryTab> {
  late final HistoryController _c = HistoryController();

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

  void _openOrder(Map<String, dynamic> item) {
    final maHD = jStr(item['ma_hoa_don']);
    if (maHD.isEmpty) return;
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => OrderDetailScreen(maHoaDon: maHD)),
    ).then((_) {
      if (mounted) _c.refresh();
    });
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: Listenable.merge([_c, AppSettings.instance]),
      builder: (context, _) {
        // Chỉ lần tải đầu tiên mới thay cả màn hình.
        if (!_c.hasLoaded) {
          if (_c.error != null) {
            return SafeArea(
              bottom: false,
              child: ErrorBox(message: _c.error!, onRetry: () => _c.load()),
            );
          }
          return const ListSkeleton();
        }

        return SafeArea(
          bottom: false,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
                child: _buildHeader(),
              ),
              Expanded(child: _buildBody()),
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
        Text(
          tr('hist_title'),
          style: const TextStyle(
            color: Pink.plum,
            fontSize: 32,
            fontWeight: FontWeight.w800,
            height: 1.1,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          tr('hist_sub', {'n': '${_c.total}'}),
          style: const TextStyle(color: Pink.muted, fontSize: 13.5),
        ),
        const SizedBox(height: 12),
        HistoryFilterBar(controller: _c),
        if (_c.totalPages > 1) ...[const SizedBox(height: 6), _buildPager()],
      ],
    );
  }

  Widget _buildPager() {
    return Row(
      children: [
        IconButton(
          color: Pink.plum,
          onPressed: _c.page > 1 && !_c.loading ? _c.prevPage : null,
          icon: const Icon(Icons.chevron_left_rounded),
        ),
        Text(
          tr('page', {'x': '${_c.page}', 'y': '${_c.totalPages}'}),
          style: const TextStyle(color: Pink.plum, fontWeight: FontWeight.w600),
        ),
        IconButton(
          color: Pink.plum,
          onPressed: _c.page < _c.totalPages && !_c.loading
              ? _c.nextPage
              : null,
          icon: const Icon(Icons.chevron_right_rounded),
        ),
      ],
    );
  }

  /// Thông báo giữa màn hình (rỗng / lỗi) — vẫn kéo để refresh được.
  Widget _buildMessage({
    required IconData icon,
    required String text,
    Color iconColor = Pink.faint,
    Color textColor = Pink.muted,
    Widget? action,
  }) {
    return RefreshIndicator(
      color: Pink.rose,
      onRefresh: _c.refresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.4,
            child: Center(
              // Fade + slide cho trạng thái rỗng / lỗi
              child: StaggeredFadeSlide(
                index: 0,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 32),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(icon, size: 44, color: iconColor),
                      const SizedBox(height: 8),
                      Text(
                        text,
                        textAlign: TextAlign.center,
                        style: TextStyle(color: textColor, fontSize: 14),
                      ),
                      if (action != null) ...[
                        const SizedBox(height: 8),
                        action,
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    final bottomPad = 130 + MediaQuery.of(context).padding.bottom;

    // 1) Đang tải (đổi filter / đổi trang / thử lại) → skeleton.
    //    Pull-to-refresh không set loading nên không nháy skeleton.
    if (_c.loading) return const _HistoryListSkeleton();

    // 2) Lỗi (filter sai / mất mạng) → báo rõ ngay dưới filter bar
    if (_c.error != null) {
      return _buildMessage(
        icon: Icons.error_outline_rounded,
        iconColor: Colors.red.shade400,
        textColor: Colors.red.shade700,
        text: _c.error!,
        action: TextButton(
          onPressed: _c.coLoc ? _c.clearFilter : () => _c.load(),
          child: Text(
            _c.coLoc ? tr('clear_filter') : tr('retry'),
            style: const TextStyle(
              color: Pink.rose,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      );
    }

    // 3) Rỗng
    if (_c.items.isEmpty) {
      return _buildMessage(
        icon: _c.coLoc ? Icons.search_off_rounded : Icons.history_rounded,
        text: _c.coLoc ? tr('hist_no_match') : tr('hist_empty'),
      );
    }

    // 4) Danh sách — skeleton vừa bị gỡ nên mọi card được dựng mới
    //    → StaggeredFadeSlide chạy lại (fade + slide lên lần lượt).
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
          return StaggeredFadeSlide(
            key: ValueKey('ls-$id'),
            index: i,
            child: HistoryCard(item: item, onTap: () => _openOrder(item)),
          );
        },
      ),
    );
  }
}

/// Skeleton dạng card lịch sử (chỉ phần danh sách, không có tiêu đề trang).
class _HistoryListSkeleton extends StatelessWidget {
  const _HistoryListSkeleton();

  @override
  Widget build(BuildContext context) {
    final bottomPad = 130 + MediaQuery.of(context).padding.bottom;
    return SkeletonShimmer(
      child: ListView.builder(
        physics: const NeverScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(20, 4, 20, bottomPad),
        itemCount: 6,
        itemBuilder: (_, __) => const Padding(
          padding: EdgeInsets.only(bottom: 12),
          child: GlassContainer(
            blur: 0,
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                SkeletonBox(width: 44, height: 44, radius: 22),
                SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SkeletonBox(width: 90, height: 16),
                      SizedBox(height: 8),
                      SkeletonBox(width: 150, height: 12),
                      SizedBox(height: 6),
                      SkeletonBox(width: 120, height: 11),
                      SizedBox(height: 8),
                      SkeletonBox(width: 80, height: 16),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
