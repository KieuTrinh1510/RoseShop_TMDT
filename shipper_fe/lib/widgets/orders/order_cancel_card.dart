// lib/widgets/orders/order_cancel_card.dart
// Chi tiết đơn ĐÃ HỦY: nguồn hủy, lý do, hàng hoàn + bảng kết quả kiểm tra.
// Đọc khóa `huy` do GET /orders/:id trả về (snake_case):
//   huy.nguon_huy, huy.ly_do_huy, huy.chi_tiet_ly_do, huy.thoi_diem_huy, huy.co_hang_hoan,
//   huy.hang_hoan.{da_kiem_tra, nhan_trang_thai, ket_qua_kiem_tra[]}
//   mỗi dòng: ten, so_luong_theo_don, so_luong_hong, so_luong_con_ban_duoc, ket_qua, ghi_chu

import 'package:flutter/material.dart';
import '../../theme/pink_theme.dart';
import '../../utils/app_strings.dart';
import '../../utils/format.dart';
import 'order_detail_cards.dart';

class OrderCancelCard extends StatelessWidget {
  final Map<String, dynamic> d;
  const OrderCancelCard({super.key, required this.d});

  @override
  Widget build(BuildContext context) {
    final huy = jMap(d['huy']);
    if (huy.isEmpty) return const SizedBox.shrink();

    final nguon = jStr(huy['nguon_huy']);
    final nguonLabel = nguon == 'SHIPPER'
        ? tr('cx_src_shipper')
        : nguon == 'CUSTOMER'
        ? tr('cx_src_customer')
        : jStr(huy['nhan_nguon_huy'], '—');
    final lyDo = jStr(huy['ly_do_huy'], '—');
    final chiTiet = jStr(huy['chi_tiet_ly_do']);

    return OrderSection(
      icon: Icons.cancel_outlined,
      title: tr('cx_title'),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          OrderInfoRow(
            label: tr('od_status'),
            value: tr('st_cancelled'),
            valueColor: Pink.danger,
          ),
          OrderInfoRow(label: tr('cx_source'), value: nguonLabel),
          OrderInfoRow(label: tr('cx_reason'), value: lyDo),
          if (chiTiet.isNotEmpty)
            OrderInfoRow(label: tr('cx_note'), value: chiTiet, bold: false),
          OrderInfoRow(
            label: tr('cx_time'),
            value: fmtDateTime(huy['thoi_diem_huy']),
            bold: false,
          ),
          const SizedBox(height: 8),
          Container(height: 1, color: Colors.white.withOpacity(0.7)),
          const SizedBox(height: 12),
          _ReturnGoods(huy: huy),
        ],
      ),
    );
  }
}

class _ReturnGoods extends StatelessWidget {
  final Map<String, dynamic> huy;
  const _ReturnGoods({required this.huy});

  @override
  Widget build(BuildContext context) {
    final coHangHoan = jBool(huy['co_hang_hoan']);
    final hh = jMap(huy['hang_hoan']);

    if (!coHangHoan && hh.isEmpty) {
      return Text(
        tr('cx_no_return'),
        style: const TextStyle(color: Pink.muted, fontSize: 13.5),
      );
    }

    final daKiemTra = jBool(hh['da_kiem_tra']);
    final rows = jList(hh['ket_qua_kiem_tra']).whereType<Map>().toList();
    final trangThaiHangHoan = jStr(hh['nhan_trang_thai']);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                tr('cx_return'),
                style: const TextStyle(
                  color: Pink.plum,
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
            PinkChip(
              label: daKiemTra ? tr('cx_checked') : tr('cx_not_checked'),
              color: daKiemTra ? Pink.leaf : Pink.orange,
              icon: daKiemTra
                  ? Icons.fact_check_rounded
                  : Icons.hourglass_empty_rounded,
            ),
          ],
        ),
        if (!daKiemTra && trangThaiHangHoan.isNotEmpty) ...[
          const SizedBox(height: 6),
          Text(
            trangThaiHangHoan,
            style: const TextStyle(color: Pink.muted, fontSize: 12.5),
          ),
        ],
        if (daKiemTra) ...[
          const SizedBox(height: 12),
          Text(
            tr('cx_result_title'),
            style: const TextStyle(
              color: Pink.plum,
              fontSize: 14,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          if (rows.isEmpty)
            Text('—', style: const TextStyle(color: Pink.muted, fontSize: 13))
          else
            for (final r in rows) _ResultRow(row: jMap(r)),
        ],
      ],
    );
  }
}

class _ResultRow extends StatelessWidget {
  final Map<String, dynamic> row;
  const _ResultRow({required this.row});

  static Color _color(String kq) {
    switch (kq) {
      case 'HONG':
        return Pink.danger;
      case 'CON_BAN':
      case 'DA_XU_LY':
        return Pink.leaf;
      case 'FLASH_SALE':
        return Pink.orange;
      default:
        return Pink.grey;
    }
  }

  /// HONG / CON_BAN / FLASH_SALE / XA_BO / DA_XU_LY / CHO_KIEM_TRA; giá trị lạ → hiện nguyên văn.
  static String _label(String kq) {
    const known = {
      'HONG': 'rs_hong',
      'CON_BAN': 'rs_con_ban',
      'FLASH_SALE': 'rs_flash_sale',
      'XA_BO': 'rs_xa_bo',
      'DA_XU_LY': 'rs_da_xu_ly',
      'CHO_KIEM_TRA': 'rs_cho_kiem_tra',
    };
    final key = known[kq];
    return key == null ? (kq.isEmpty ? '—' : kq) : tr(key);
  }

  @override
  Widget build(BuildContext context) {
    final kq = jStr(row['ket_qua']);
    final ghiChu = jStr(row['ghi_chu']);

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.55),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            jStr(row['ten'], tr('od_product_default')),
            style: const TextStyle(
              color: Pink.plum,
              fontSize: 14.5,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 14,
            runSpacing: 6,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              _stat(tr('cx_col_qty'), '${jInt(row['so_luong_theo_don'])}'),
              _stat(tr('cx_col_bad'), '${jInt(row['so_luong_hong'])}'),
              _stat(tr('cx_col_ok'), '${jInt(row['so_luong_con_ban_duoc'])}'),
              PinkChip(label: _label(kq), color: _color(kq)),
            ],
          ),
          if (ghiChu.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              ghiChu,
              style: const TextStyle(
                color: Pink.muted,
                fontSize: 12.5,
                height: 1.3,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _stat(String label, String value) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          '$label: ',
          style: const TextStyle(color: Pink.muted, fontSize: 12.5),
        ),
        Text(
          value,
          style: const TextStyle(
            color: Pink.plum,
            fontSize: 13.5,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    );
  }
}
