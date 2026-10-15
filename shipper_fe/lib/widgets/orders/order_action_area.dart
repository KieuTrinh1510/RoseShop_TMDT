// lib/widgets/order/order_action_area.dart
// Khối nút hành động theo từng bước giao hàng.
// Screen truyền vào callback onRunAction để gọi API + dialog.
import 'package:flutter/material.dart';
import '../../theme/pink_theme.dart';
import '../../utils/app_strings.dart';
import '../../utils/format.dart';

typedef RunActionCallback =
    Future<void> Function({
      required String path,
      required String title,
      required String message,
      required String okMsg,
    });

class OrderActionArea extends StatelessWidget {
  final String maHoaDon;
  final Map<String, dynamic> d;
  final Map<String, dynamic> hanhDong;
  final bool busy;
  final RunActionCallback onRunAction;

  const OrderActionArea({
    super.key,
    required this.maHoaDon,
    required this.d,
    required this.hanhDong,
    required this.busy,
    required this.onRunAction,
  });

  @override
  Widget build(BuildContext context) {
    final tp = jMap(d['thanh_toan']);
    final canThu = jBool(tp['can_thu_tien_mat']);
    final soTien = jNum(tp['so_tien_can_thu']);
    final laHoaTuoi = jBool(d['la_hoa_tuoi']);
    final id = maHoaDon;

    // Bước 1: Nhận hàng
    if (jBool(hanhDong['co_the_nhan_hang'])) {
      return _ActionBlock(
        button: PinkGradientButton(
          label: tr('ac_pickup_btn'),
          icon: Icons.local_shipping_rounded,
          busy: busy,
          onPressed: () => onRunAction(
            path: '/orders/$id/pickup',
            title: tr('ac_pickup_title'),
            message: laHoaTuoi
                ? tr('ac_pickup_msg_fresh')
                : tr('ac_pickup_msg'),
            okMsg: tr('ac_pickup_ok'),
          ),
        ),
        hint: laHoaTuoi ? tr('ac_pickup_hint_fresh') : tr('ac_pickup_hint'),
      );
    }

    // Bước 2: Đã đến điểm giao
    if (jBool(hanhDong['co_the_bao_den_noi'])) {
      return _ActionBlock(
        button: PinkGradientButton(
          label: tr('ac_arrived_btn'),
          icon: Icons.location_on_rounded,
          busy: busy,
          onPressed: () => onRunAction(
            path: '/orders/$id/arrived',
            title: tr('ac_arrived_title'),
            message: tr('ac_arrived_msg'),
            okMsg: tr('ac_arrived_ok'),
          ),
        ),
        hint: tr('ac_arrived_hint'),
      );
    }

    // Bước 3: Hoàn thành
    if (jBool(hanhDong['co_the_giao_xong'])) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Pink.leaf.withOpacity(0.12),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Pink.leaf.withOpacity(0.4)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  tr('ac_sent_title'),
                  style: const TextStyle(
                    color: Pink.leaf,
                    fontWeight: FontWeight.w800,
                    fontSize: 14.5,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  tr('ac_sent_body'),
                  style: const TextStyle(color: Pink.plum, fontSize: 13),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          _ActionBlock(
            button: PinkGradientButton(
              label: tr('ac_done_btn'),
              icon: Icons.check_circle_rounded,
              busy: busy,
              onPressed: () => onRunAction(
                path: '/orders/$id/delivered',
                title: tr('ac_done_title'),
                message: canThu
                    ? tr('ac_done_msg_cod', {'amt': fmtMoney(soTien)})
                    : tr('ac_done_msg'),
                okMsg: tr('ac_done_ok'),
              ),
            ),
            hint: canThu
                ? tr('ac_done_hint_cod', {'amt': fmtMoney(soTien)})
                : tr('ac_done_hint'),
          ),
        ],
      );
    }

    // Đã giao xong
    if (jStr(d['trang_thai']) == 'DELIVERED') {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Pink.leaf.withOpacity(0.12),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Pink.leaf.withOpacity(0.4)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              tr('ac_completed_title'),
              style: const TextStyle(
                color: Pink.leaf,
                fontWeight: FontWeight.w800,
                fontSize: 14.5,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              tr('ac_delivered_at', {'t': fmtDateTime(d['ngay_giao'])}),
              style: const TextStyle(color: Pink.plum, fontSize: 13),
            ),
          ],
        ),
      );
    }

    return const SizedBox.shrink();
  }
}

class _ActionBlock extends StatelessWidget {
  final Widget button;
  final String hint;
  const _ActionBlock({required this.button, required this.hint});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        button,
        const SizedBox(height: 8),
        Text(
          hint,
          style: const TextStyle(
            color: Pink.muted,
            fontSize: 12.5,
            height: 1.35,
          ),
        ),
      ],
    );
  }
}
