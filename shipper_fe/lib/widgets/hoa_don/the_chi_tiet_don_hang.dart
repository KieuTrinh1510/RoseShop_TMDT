// lib/widgets/hoa_don/the_chi_tiet_hoa_don.dart
// Các card hiển thị thông tin trong màn chi tiết hóa đơn.
import 'dart:ui' show FontFeature;

import 'package:flutter/material.dart';
import '../../theme/pink_theme.dart';
import '../../utils/format.dart';

// ═════════════════════════════════════════════════════════════════
// Helper dùng chung cho các card
// ═════════════════════════════════════════════════════════════════

/// Khối tiêu đề + nội dung dùng chung.
class OrderSection extends StatelessWidget {
  final IconData icon;
  final String title;
  final Widget child;
  const OrderSection({
    super.key,
    required this.icon,
    required this.title,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return PinkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Pink.rose.withOpacity(0.15),
                ),
                child: Icon(icon, size: 17, color: Pink.roseDeep),
              ),
              const SizedBox(width: 10),
              Text(
                title,
                style: const TextStyle(
                  color: Pink.plum,
                  fontSize: 16.5,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class OrderInfoRow extends StatelessWidget {
  final String label;
  final String value;
  final bool bold;
  final Color? valueColor;
  final IconData? trailingIcon;
  final VoidCallback? onTap;

  const OrderInfoRow({
    super.key,
    required this.label,
    required this.value,
    this.bold = true,
    this.valueColor,
    this.trailingIcon,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 112,
            child: Text(
              label,
              style: const TextStyle(color: Pink.muted, fontSize: 13.5),
            ),
          ),
          Expanded(
            child: GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: onTap,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Flexible(
                    child: Text(
                      value,
                      textAlign: TextAlign.end,
                      style: TextStyle(
                        color: valueColor ?? Pink.plum,
                        fontSize: 14.5,
                        fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
                        height: 1.3,
                      ),
                    ),
                  ),
                  if (trailingIcon != null) ...[
                    const SizedBox(width: 6),
                    Icon(trailingIcon, size: 16, color: Pink.rose),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════
// Summary
// ═════════════════════════════════════════════════════════════════

class OrderSummaryCard extends StatelessWidget {
  final Map<String, dynamic> d;
  const OrderSummaryCard({super.key, required this.d});

  @override
  Widget build(BuildContext context) {
    final st = statusStyle(jStr(d['trang_thai']), jStr(d['nhan_trang_thai']));
    final tp = jMap(d['thanh_toan']);
    final laCod = jBool(tp['la_cod']);
    final laHoaTuoi = jBool(d['la_hoa_tuoi']);

    return PinkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  jStr(d['ma_hien_thi'], '#HD-${jStr(d['ma_hoa_don'])}'),
                  style: const TextStyle(
                    color: Pink.plum,
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              PinkChip(label: st.label, color: st.color),
            ],
          ),
          if (laCod || laHoaTuoi) ...[
            const SizedBox(height: 10),
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
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════
// Countdown + Overdue
// ═════════════════════════════════════════════════════════════════

/// Đồng hồ đếm ngược. Chỉ khối này rebuild mỗi giây (ValueListenableBuilder).
class OrderCountdownCard extends StatelessWidget {
  final ValueNotifier<int> remaining;
  const OrderCountdownCard({super.key, required this.remaining});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<int>(
      valueListenable: remaining,
      builder: (context, left, _) {
        final warn = left < 30 * 60; // còn dưới 30 phút -> cảnh báo
        final color = warn ? Pink.danger : Pink.roseDeep;
        return PinkCard(
          tint: warn ? Pink.warningBg : Colors.white,
          opacity: warn ? 0.55 : 0.42,
          child: Column(
            children: [
              Row(
                children: [
                  Text(warn ? '🚨' : '⏰', style: const TextStyle(fontSize: 22)),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Text(
                      'Thời gian giao hoa tươi còn lại',
                      style: TextStyle(
                        color: Pink.plum,
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                fmtHms(left),
                style: TextStyle(
                  color: color,
                  fontSize: 42,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 2,
                  fontFeatures: const [FontFeature.tabularFigures()],
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'Hoa tươi cần được giao trong 3 tiếng để đảm bảo chất lượng.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Pink.muted, fontSize: 12.5),
              ),
            ],
          ),
        );
      },
    );
  }
}

/// Khung thay cho đồng hồ khi đã quá 3h (BR-3: vẫn được tiếp tục giao).
class OrderOverdueCard extends StatelessWidget {
  const OrderOverdueCard({super.key});

  @override
  Widget build(BuildContext context) {
    return const PinkCard(
      tint: Pink.warningBgDeep,
      opacity: 0.55,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '⚠️ Đã quá 3 tiếng',
            style: TextStyle(
              color: Pink.danger,
              fontSize: 17,
              fontWeight: FontWeight.w800,
            ),
          ),
          SizedBox(height: 6),
          Text(
            'Đơn hoa tươi đã quá hạn giao. Hệ thống đã cộng điểm cho khách và ghi nhận khoản phạt. Bạn vẫn có thể tiếp tục giao và hoàn thành đơn.',
            style: TextStyle(color: Pink.plum, fontSize: 13, height: 1.35),
          ),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════
// Progress (stepper)
// ═════════════════════════════════════════════════════════════════

class OrderProgressCard extends StatelessWidget {
  final List<Map<String, dynamic>> steps;
  final Widget action;
  const OrderProgressCard({
    super.key,
    required this.steps,
    required this.action,
  });

  @override
  Widget build(BuildContext context) {
    return OrderSection(
      icon: Icons.local_shipping_rounded,
      title: 'Tiến trình giao hàng',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _Stepper(steps: steps),
          const SizedBox(height: 18),
          action,
        ],
      ),
    );
  }
}

class _Stepper extends StatelessWidget {
  final List<Map<String, dynamic>> steps;
  const _Stepper({required this.steps});

  @override
  Widget build(BuildContext context) {
    final cur = steps.indexWhere((s) => !jBool(s['done'])); // bước hiện tại
    final n = steps.length;

    return Column(
      children: [
        Row(
          children: [
            for (int i = 0; i < n; i++)
              Expanded(
                child: Row(
                  children: [
                    Expanded(
                      child: i == 0
                          ? const SizedBox()
                          : _Line(done: jBool(steps[i - 1]['done'])),
                    ),
                    _Dot(
                      done: jBool(steps[i]['done']),
                      current: i == cur,
                      index: i + 1,
                    ),
                    Expanded(
                      child: i == n - 1
                          ? const SizedBox()
                          : _Line(done: jBool(steps[i]['done'])),
                    ),
                  ],
                ),
              ),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (int i = 0; i < n; i++)
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 2),
                  child: Text(
                    jStr(steps[i]['label']),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: (jBool(steps[i]['done']) || i == cur)
                          ? Pink.plum
                          : Pink.faint,
                      fontSize: 11.5,
                      height: 1.25,
                      fontWeight: (jBool(steps[i]['done']) || i == cur)
                          ? FontWeight.w700
                          : FontWeight.w500,
                    ),
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }
}

class _Dot extends StatelessWidget {
  final bool done;
  final bool current;
  final int index;
  const _Dot({required this.done, required this.current, required this.index});

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      width: 34,
      height: 34,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: done ? Pink.buttonGradient : null,
        color: done ? null : Colors.white.withOpacity(current ? 0.92 : 0.35),
        border: Border.all(
          color: current
              ? Pink.rose
              : (done ? Colors.transparent : Colors.white.withOpacity(0.7)),
          width: current ? 2 : 1,
        ),
        boxShadow: current
            ? [BoxShadow(color: Pink.rose.withOpacity(0.35), blurRadius: 12)]
            : null,
      ),
      child: done
          ? const Icon(Icons.check_rounded, size: 19, color: Colors.white)
          : Text(
              '$index',
              style: TextStyle(
                color: current ? Pink.roseDeep : Pink.faint,
                fontWeight: FontWeight.w800,
                fontSize: 14,
              ),
            ),
    );
  }
}

class _Line extends StatelessWidget {
  final bool done;
  const _Line({required this.done});

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      height: 3,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(2),
        color: done ? Pink.rose : Colors.white.withOpacity(0.55),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════
// Customer / Products / Payment
// ═════════════════════════════════════════════════════════════════

class OrderCustomerCard extends StatelessWidget {
  final Map<String, dynamic> d;
  final void Function(String phone) onCopyPhone;
  const OrderCustomerCard({
    super.key,
    required this.d,
    required this.onCopyPhone,
  });

  @override
  Widget build(BuildContext context) {
    final kh = jMap(d['khach_hang']);
    final sdt = jStr(kh['so_dien_thoai']);

    return OrderSection(
      icon: Icons.person_rounded,
      title: 'Thông tin khách hàng',
      child: Column(
        children: [
          OrderInfoRow(label: 'Họ tên', value: jStr(kh['ten'], '—')),
          OrderInfoRow(
            label: 'Số điện thoại',
            value: sdt.isEmpty ? '—' : sdt,
            valueColor: sdt.isEmpty ? null : Pink.roseDeep,
            trailingIcon: sdt.isEmpty ? null : Icons.copy_rounded,
            onTap: sdt.isEmpty ? null : () => onCopyPhone(sdt),
          ),
          OrderInfoRow(
            label: 'Địa chỉ giao hàng',
            value: jStr(d['dia_chi_giao'], '—'),
          ),
          OrderInfoRow(label: 'Ngày đặt', value: fmtDateTime(d['ngay_dat'])),
          OrderInfoRow(
            label: 'Mã hóa đơn',
            value: jStr(d['ma_hien_thi'], '#HD-${jStr(d['ma_hoa_don'])}'),
          ),
        ],
      ),
    );
  }
}

class OrderProductsCard extends StatelessWidget {
  final Map<String, dynamic> d;
  const OrderProductsCard({super.key, required this.d});

  @override
  Widget build(BuildContext context) {
    final items = jList(d['san_pham']).whereType<Map>().toList();
    final phi = jNum(d['phi_van_chuyen']);

    return OrderSection(
      icon: Icons.local_florist_rounded,
      title: 'Chi tiết sản phẩm',
      child: Column(
        children: [
          for (final raw in items) _productRow(jMap(raw)),
          const SizedBox(height: 6),
          Container(height: 1, color: Colors.white.withOpacity(0.7)),
          const SizedBox(height: 8),
          OrderInfoRow(
            label: 'Tạm tính',
            value: fmtMoney(jNum(d['tam_tinh'])),
            bold: false,
          ),
          OrderInfoRow(
            label: 'Phí vận chuyển',
            value: phi <= 0 ? 'Miễn phí' : fmtMoney(phi),
            bold: false,
            valueColor: phi <= 0 ? Pink.leaf : null,
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              const Text(
                'Tổng cộng',
                style: TextStyle(
                  color: Pink.plum,
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const Spacer(),
              Text(
                fmtMoney(jNum(d['tong_cong'])),
                style: const TextStyle(
                  color: Pink.roseDeep,
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _productRow(Map<String, dynamic> sp) {
    final ten = jStr(sp['ten_san_pham'], 'Sản phẩm');
    final sl = jInt(sp['so_luong']);
    final gia = jNum(sp['gia_ban']);
    final thanhTien = jNum(sp['thanh_tien']);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Pink.blush.withOpacity(0.9),
            ),
            child: const Icon(
              Icons.local_florist_rounded,
              size: 20,
              color: Pink.rose,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  ten,
                  style: const TextStyle(
                    color: Pink.plum,
                    fontSize: 14.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'SL × $sl  •  Đơn giá ${fmtMoney(gia)}',
                  style: const TextStyle(color: Pink.muted, fontSize: 12.5),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            fmtMoney(thanhTien),
            style: const TextStyle(
              color: Pink.plum,
              fontSize: 14.5,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class OrderPaymentCard extends StatelessWidget {
  final Map<String, dynamic> d;
  const OrderPaymentCard({super.key, required this.d});

  @override
  Widget build(BuildContext context) {
    final tp = jMap(d['thanh_toan']);
    final laCod = jBool(tp['la_cod']);
    final canThu = jBool(tp['can_thu_tien_mat']);
    final soTien = jNum(tp['so_tien_can_thu']);
    final phuongThuc = jStr(tp['phuong_thuc']);

    final tenPhuongThuc = laCod
        ? 'Tiền mặt (COD)'
        : (phuongThuc == 'NGAN_HANG'
              ? 'Chuyển khoản ngân hàng'
              : (phuongThuc.isEmpty ? '—' : phuongThuc));

    return OrderSection(
      icon: Icons.payments_rounded,
      title: 'Thanh toán',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          OrderInfoRow(label: 'Phương thức', value: tenPhuongThuc),
          if (canThu) ...[
            OrderInfoRow(
              label: 'Số tiền cần thu',
              value: fmtMoney(soTien),
              valueColor: Pink.roseDeep,
            ),
            const SizedBox(height: 6),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Pink.orange.withOpacity(0.12),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Pink.orange.withOpacity(0.4)),
              ),
              child: const Row(
                children: [
                  Icon(
                    Icons.info_outline_rounded,
                    size: 18,
                    color: Pink.orange,
                  ),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Thu tiền mặt khi giao hàng',
                      style: TextStyle(
                        color: Pink.orange,
                        fontWeight: FontWeight.w700,
                        fontSize: 13.5,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ] else
            OrderInfoRow(
              label: 'Trạng thái',
              value: laCod ? 'Đã thu tiền' : 'Đã thanh toán, không cần thu',
              valueColor: Pink.leaf,
            ),
        ],
      ),
    );
  }
}
