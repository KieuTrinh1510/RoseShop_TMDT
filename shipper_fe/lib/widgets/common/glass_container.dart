import 'dart:ui';
import 'package:flutter/material.dart';

// Card kính mờ dùng lại được: blur + nền trong suốt + viền mờ + bo góc lớn.

class GlassContainer extends StatelessWidget {
  final Widget child;
  final double blur;
  final double opacity;
  final double borderRadius;
  final EdgeInsetsGeometry padding;
  final Color tint;
  final Color borderColor;

  const GlassContainer({
    super.key,
    required this.child,
    this.blur = 14,
    this.opacity = 0.18,
    this.borderRadius = 24,
    this.padding = const EdgeInsets.all(16),
    this.tint = Colors.white,
    this.borderColor = const Color(0x59FFFFFF),
  });

  @override
  Widget build(BuildContext context) {
    final radius = BorderRadius.circular(borderRadius);

    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: radius,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),

      child: ClipRRect(
        borderRadius: radius,
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: blur, sigmaY: blur),
          child: Container(
            padding: padding,
            decoration: BoxDecoration(
              color: tint.withOpacity(opacity),
              borderRadius: radius,
              border: Border.all(color: borderColor, width: 1),
            ),
            child: child,
          ),
        ),
      ),
    );
  }
}
