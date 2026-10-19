// lib/widgets/history/history_card.dart
// Card 1 đơn trong lịch sử. 3 kiểu:
//   DELIVERED                → check xanh, "Giao thành công", ngày giao
//   CANCELLED + SHIPPER      → cảnh báo cam, "Hủy do lỗi shipper", ngày hủy
//   CANCELLED + CUSTOMER     → cảnh báo đỏ,  "Hủy do khách hàng không nhận hàng", ngày hủy

import 'package:flutter/material.dart';
import '../../utils/app_strings.dart';
import '../../theme/pink_theme.dart';
import '../../utils/format.dart';
import '../common/glass_container.dart';

class _Kieu {
  final IconData icon;
  final Color color;
  final String label;
  final bool daHuy;
  const _Kieu(this.icon, this.color, this.label, this.daHuy);
}

class HistoryCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback? onTap;

  const HistoryCard({super.key, required this.item, this.onTap});

  _Kieu _kieu() {
    // Backend cũ không trả trang_thai → coi là đơn đã giao.
    final tt = jStr(item['trang_thai'], 'DELIVERED');
    if (tt != 'CANCELLED') {
      return _Kieu(Icons.check_rounded, Pink.leaf, tr('h_delivered'), false);
    }
    switch (jStr(item['nguon_huy'])) {
      case 'SHIPPER':
        return _Kieu(
          Icons.warning_amber_rounded,
          Pink.orange,
          tr('h_cancel_shipper'),
          true,
        );
      case 'CUSTOMER':
        return _Kieu(
          Icons.warning_amber_rounded,
          Pink.danger,
          tr('h_cancel_customer'),
          true,
        );
      default:
        return _Kieu(Icons.cancel_outlined, Pink.grey, tr('h_cancelled'), true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final maHienThi = jStr(
      item['ma_hien_thi'],
      '#HD-${jStr(item['ma_hoa_don'])}',
    );
    final kh = jMap(item['khach_hang']);
    final tien = jNum(item['tong_tien']);
    final k = _kieu();

    // Đơn hủy không có ngay_giao → dùng thoi_diem_huy (hoặc mốc chung ngay_hoan_tat).
    final ngay = k.daHuy
        ? (item['thoi_diem_huy'] ?? item['ngay_huy'] ?? item['ngay_hoan_tat'])
        : (item['ngay_giao'] ?? item['ngay_hoan_tat']);
    final lyDoHuy = k.daHuy ? jStr(item['ly_do_huy']) : '';

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: onTap,
        child: GlassContainer(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: k.color.withOpacity(0.15),
                ),
                alignment: Alignment.center,
                child: Icon(k.icon, color: k.color, size: 24),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      maHienThi,
                      style: const TextStyle(
                        color: Pink.plum,
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    PinkChip(label: k.label, color: k.color),
                    if (jStr(kh['ten']).isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(
                        tr('cust_prefix', {'name': '${kh['ten']}'}),
                        style: TextStyle(
                          color: Pink.plum.withOpacity(0.7),
                          fontSize: 13,
                        ),
                      ),
                    ],
                    if (ngay != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        tr(k.daHuy ? 'h_cancelled_at' : 'delivered_at', {
                          't': fmtDateTime(ngay),
                        }),
                        style: TextStyle(
                          color: Pink.plum.withOpacity(0.55),
                          fontSize: 12,
                        ),
                      ),
                    ],
                    if (lyDoHuy.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        lyDoHuy,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Pink.plum.withOpacity(0.55),
                          fontSize: 12,
                        ),
                      ),
                    ],
                    const SizedBox(height: 6),
                    Text(
                      fmtMoney(tien),
                      style: const TextStyle(
                        color: Pink.roseDeep,
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: Pink.faint),
            ],
          ),
        ),
      ),
    );
  }
}
