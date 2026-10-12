// lib/widgets/history/history_card.dart
// Card 1 đơn trong lịch sử: check xanh + mã đơn + khách + ngày giao + tiền.

import 'package:flutter/material.dart';
import '../../utils/app_strings.dart';
import '../../theme/pink_theme.dart';
import '../../utils/format.dart';
import '../common/glass_container.dart';

class HistoryCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback? onTap;

  const HistoryCard({super.key, required this.item, this.onTap});

  @override
  Widget build(BuildContext context) {
    final maHienThi = jStr(
      item['ma_hien_thi'],
      '#HD-${jStr(item['ma_hoa_don'])}',
    );
    final kh = jMap(item['khach_hang']);
    final tien = jNum(item['tong_tien']);
    final ngayGiao = item['ngay_giao'];

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
                  color: Pink.leaf.withOpacity(0.15),
                ),
                alignment: Alignment.center,
                child: const Icon(
                  Icons.check_rounded,
                  color: Pink.leaf,
                  size: 24,
                ),
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
                    if (jStr(kh['ten']).isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        tr('cust_prefix', {'name': '${kh['ten']}'}),
                        style: TextStyle(
                          color: Pink.plum.withOpacity(0.7),
                          fontSize: 13,
                        ),
                      ),
                    ],
                    if (ngayGiao != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        tr('delivered_at', {'t': fmtDateTime(ngayGiao)}),
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
