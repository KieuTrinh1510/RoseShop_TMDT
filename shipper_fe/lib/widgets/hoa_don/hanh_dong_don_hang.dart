// lib/widgets/hoa_don/hanh_dong_hoa_don.dart
// Khối nút hành động theo từng bước giao hàng.
// Screen truyền vào callback onRunAction để gọi API + dialog.
import 'package:flutter/material.dart';
import '../../theme/pink_theme.dart';
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
          label: 'Nhận hàng & bắt đầu giao',
          icon: Icons.local_shipping_rounded,
          busy: busy,
          onPressed: () => onRunAction(
            path: '/orders/$id/pickup',
            title: 'Nhận hàng & bắt đầu giao?',
            message: laHoaTuoi
                ? 'Đơn sẽ chuyển sang "Đang giao", khách được thông báo và đồng hồ 3 tiếng bắt đầu đếm ngược.'
                : 'Đơn sẽ chuyển sang "Đang giao" và khách sẽ nhận được thông báo.',
            okMsg: 'Đã nhận hàng, bắt đầu giao',
          ),
        ),
        hint: laHoaTuoi
            ? 'Đơn hoa tươi: đồng hồ 3 tiếng bắt đầu khi bạn nhận hàng.'
            : 'Khách sẽ nhận thông báo đơn hàng bắt đầu được giao.',
      );
    }

    // Bước 2: Đã đến điểm giao
    if (jBool(hanhDong['co_the_bao_den_noi'])) {
      return _ActionBlock(
        button: PinkGradientButton(
          label: 'Đã đến điểm giao – thông báo khách',
          icon: Icons.location_on_rounded,
          busy: busy,
          onPressed: () => onRunAction(
            path: '/orders/$id/arrived',
            title: 'Báo đã đến điểm giao?',
            message:
                'Khách sẽ nhận thông báo xuống lấy hàng. Thông báo này chỉ gửi được 1 lần.',
            okMsg: 'Đã gửi thông báo đến khách',
          ),
        ),
        hint: 'Khách sẽ nhận thông báo xuống lấy hàng.',
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
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '✓ Đã gửi thông báo đến khách',
                  style: TextStyle(
                    color: Pink.leaf,
                    fontWeight: FontWeight.w800,
                    fontSize: 14.5,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'Khách hàng đã được thông báo. Hãy chờ khách xuống nhận hàng.',
                  style: TextStyle(color: Pink.plum, fontSize: 13),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          _ActionBlock(
            button: PinkGradientButton(
              label: 'Hoàn thành đơn hàng',
              icon: Icons.check_circle_rounded,
              busy: busy,
              onPressed: () => onRunAction(
                path: '/orders/$id/delivered',
                title: 'Hoàn thành đơn hàng?',
                message: canThu
                    ? 'Xác nhận đã giao hàng và thu ${fmtMoney(soTien)} tiền mặt từ khách?'
                    : 'Xác nhận đã giao hàng thành công cho khách?',
                okMsg: 'Đã hoàn thành đơn hàng',
              ),
            ),
            hint: canThu
                ? 'Nhấn khi đã giao hàng thành công cho khách hàng. Nhớ thu ${fmtMoney(soTien)} tiền mặt.'
                : 'Nhấn khi đã giao hàng thành công cho khách hàng.',
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
            const Text(
              '🎉 Đơn hàng đã hoàn thành',
              style: TextStyle(
                color: Pink.leaf,
                fontWeight: FontWeight.w800,
                fontSize: 14.5,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Giao lúc ${fmtDateTime(d['ngay_giao'])}',
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
