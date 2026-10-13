// lib/widgets/home/chuong_thong_bao.dart
// Icon chuông + badge đỏ hiển thị số chưa đọc. Bấm → callback.
import 'package:flutter/material.dart';

class NotificationBell extends StatelessWidget {
  final int count;
  final VoidCallback onTap;
  final Color color;

  const NotificationBell({
    super.key,
    required this.count,
    required this.onTap,
    this.color = const Color(0xFF0B0B1A),
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          onPressed: onTap,
          icon: Icon(Icons.notifications_none_rounded, color: color, size: 26),
        ),
        if (count > 0)
          Positioned(
            right: 4,
            top: 4,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              constraints: const BoxConstraints(minWidth: 18),
              decoration: BoxDecoration(
                color: Colors.red.shade600,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.white, width: 1.5),
              ),
              child: Text(
                count > 99 ? '99+' : '$count',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  height: 1.2,
                ),
              ),
            ),
          ),
      ],
    );
  }
}
