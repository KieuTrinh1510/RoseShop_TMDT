// lib/widgets/pretty_json.dart
// Vì mình không có source của shipperOrderService/shipperDeliveryService nên
// không biết chắc shape dữ liệu trả về. Widget này in JSON ra đẹp, dễ đọc,
// đủ để test route mà không cần biết trước field nào.

import 'dart:convert';
import 'package:flutter/material.dart';

class PrettyJson extends StatelessWidget {
  final dynamic data;
  const PrettyJson({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    const encoder = JsonEncoder.withIndent('  ');
    final text = encoder.convert(data);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(10),
      ),
      child: SelectableText(
        text,
        style: const TextStyle(
          fontFamily: 'monospace',
          fontSize: 12.5,
          color: Color(0xFFE2E8F0),
          height: 1.4,
        ),
      ),
    );
  }
}

// Cố gắng đoán một vài field thường gặp để hiển thị tiêu đề gọn cho mỗi item
// trong danh sách (orders/history), fallback về mã hoá đơn hoặc index.
String guessTitle(Map<String, dynamic> item, int index) {
  if (item.containsKey('ma_hien_thi')) return item['ma_hien_thi'].toString();
  if (item.containsKey('ma_hoa_don')) return 'Đơn #${item['ma_hoa_don']}';
  return 'Mục #$index';
}

String? guessSubtitle(Map<String, dynamic> item) {
  final parts = <String>[];
  if (item['trang_thai'] != null) parts.add('Trạng thái: ${item['trang_thai']}');
  if (item['tong_tien'] != null) parts.add('Tiền: ${item['tong_tien']}');
  final khachHang = item['khachHang'];
  if (khachHang is Map && khachHang['ten_khach_hang'] != null) {
    parts.add('KH: ${khachHang['ten_khach_hang']}');
  }
  return parts.isEmpty ? null : parts.join(' • ');
}

dynamic guessId(Map<String, dynamic> item) {
  return item['ma_hoa_don'];
}