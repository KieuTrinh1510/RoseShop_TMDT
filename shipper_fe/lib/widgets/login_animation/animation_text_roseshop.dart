// lib/widgets/login_animation/animation_text_roseshop.dart
// Animation thương hiệu Roseshop cho màn Splash + Đăng nhập:
//   - vẽ dần biểu tượng hoa hồng line-art
//   - chữ "Roseshop" lộ dần từ trái sang phải
//   - slogan hiện ra
//   - logo thu nhỏ + dời từ giữa màn hình lên khu vực 1/3 phía trên
//
// Timeline dùng chung (1 AnimationController do LoginScreen sở hữu, giá trị 0..1):
//   0.00–0.28  vẽ biểu tượng hoa
//   0.12–0.56  chữ lộ dần
//   0.58–0.82  slogan
//   0.60–0.92  logo thu nhỏ + dời lên
//   0.62–0.86  khung form hiện ra        (LoginIntroTiming.panel)
//   0.66–1.00  từng ô của form lệch nhịp (LoginIntroTiming.formItem)
//
// Dùng trong Stack:
//   AnimationTextRoseshop(animation: _c, color: ..., taglineColor: ..., onLongPress: ...)

import 'dart:math' as math;

import 'package:flutter/material.dart';

/// Các mốc thời gian của intro, dùng chung cho logo và form để hai bên luôn đồng bộ.
class LoginIntroTiming {
  LoginIntroTiming._();

  /// Tổng thời lượng intro.
  static const Duration total = Duration(milliseconds: 3000);

  /// Chiều cao khu vực logo so với chiều cao màn hình (1/3 phía trên).
  static const double headerRatio = 0.33;

  /// Từ giá trị này trở đi form mới nhận thao tác chạm.
  static const double formReadyAt = 0.9;

  /// Tiến độ 0..1 của đoạn [a, b] trong timeline [0, 1], có áp curve.
  static double seg(
    double v,
    double a,
    double b, [
    Curve curve = Curves.linear,
  ]) => curve.transform(((v - a) / (b - a)).clamp(0.0, 1.0));

  /// Khung form (nền trắng bo góc) hiện ra.
  static double panel(double v) => seg(v, 0.62, 0.86, Curves.easeOut);

  /// Phần tử thứ [i] của form: hiện lệch nhịp nhau.
  static double formItem(double v, int i) {
    final begin = 0.66 + i * 0.04;
    final end = begin + 0.22 > 1.0 ? 1.0 : begin + 0.22;
    return seg(v, begin, end, Curves.easeOutCubic);
  }
}

double _lerp(double a, double b, double t) => a + (b - a) * t;

/// Logo Roseshop có animation. Trả về [Positioned] nên phải đặt trực tiếp trong [Stack].
class AnimationTextRoseshop extends StatelessWidget {
  /// Timeline 0..1 (AnimationController của màn hình).
  final Animation<double> animation;

  /// Màu chữ + nét vẽ hoa hồng.
  final Color color;

  /// Màu dòng slogan.
  final Color taglineColor;

  /// Nhấn giữ logo (LoginScreen dùng để mở hộp thoại đổi địa chỉ máy chủ).
  final VoidCallback? onLongPress;

  /// Độ thu nhỏ của logo ở vị trí cuối.
  final double finalScale;

  const AnimationTextRoseshop({
    super.key,
    required this.animation,
    required this.color,
    required this.taglineColor,
    this.onLongPress,
    this.finalScale = 0.68,
  });

  static const double _boxHeight = 170;

  @override
  Widget build(BuildContext context) {
    final h = MediaQuery.sizeOf(context).height;
    final topPad = MediaQuery.paddingOf(context).top;
    final headerH = h * LoginIntroTiming.headerRatio;
    final startY = h / 2; // giữa màn hình
    final endY = topPad + (headerH - topPad) / 2; // giữa khu vực 1/3 trên

    return AnimatedBuilder(
      animation: animation,
      builder: (context, _) {
        final v = animation.value;
        final fade = LoginIntroTiming.seg(v, 0.0, 0.12);
        final intro = LoginIntroTiming.seg(v, 0.0, 0.40, Curves.easeOut);
        final draw = LoginIntroTiming.seg(v, 0.0, 0.28, Curves.easeInOut);
        final write = LoginIntroTiming.seg(v, 0.12, 0.56, Curves.easeInOut);
        final tagline = LoginIntroTiming.seg(v, 0.58, 0.82, Curves.easeOut);
        final move = LoginIntroTiming.seg(v, 0.60, 0.92, Curves.easeInOutCubic);

        final logoY = _lerp(startY, endY, move);
        final scale = _lerp(0.92, 1.0, intro) * _lerp(1.0, finalScale, move);

        return Positioned(
          left: 0,
          right: 0,
          top: logoY - _boxHeight / 2,
          height: _boxHeight,
          child: Opacity(
            opacity: fade,
            child: Transform.scale(
              scale: scale,
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onLongPress: onLongPress,
                child: _BrandHeader(
                  draw: draw,
                  write: write,
                  tagline: tagline,
                  color: color,
                  taglineColor: taglineColor,
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

// ── Logo + thương hiệu ─────────────────────────────────────────
class _BrandHeader extends StatelessWidget {
  final double draw; // 0..1 vẽ biểu tượng hoa
  final double write; // 0..1 chữ lộ dần trái -> phải
  final double tagline; // 0..1 slogan
  final Color color;
  final Color taglineColor;

  const _BrandHeader({
    required this.draw,
    required this.write,
    required this.tagline,
    required this.color,
    required this.taglineColor,
  });

  @override
  Widget build(BuildContext context) {
    // Lộ chữ từ trái sang phải với mép mờ 15% (không làm lệch bố cục).
    const feather = 0.15;
    final head = -feather + write * (1 + feather);
    final s1 = head.clamp(0.0, 1.0);
    final s2 = (head + feather).clamp(0.0, 1.0);

    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          SizedBox(
            width: 62,
            height: 70,
            child: CustomPaint(
              painter: _RosePainter(progress: draw, color: color),
            ),
          ),
          const SizedBox(height: 6),
          ShaderMask(
            blendMode: BlendMode.dstIn,
            shaderCallback: (rect) => LinearGradient(
              colors: const [
                Colors.white,
                Colors.white,
                Color(0x00FFFFFF),
                Color(0x00FFFFFF),
              ],
              stops: [0.0, s1, s2, 1.0],
            ).createShader(rect),
            child: Text(
              'Roseshop',
              style: TextStyle(
                fontFamily: 'Georgia',
                fontFamilyFallback: const ['Times New Roman', 'serif'],
                fontSize: 44,
                fontWeight: FontWeight.w600,
                letterSpacing: 1.2,
                color: color,
              ),
            ),
          ),
          const SizedBox(height: 6),
          Opacity(
            opacity: tagline,
            child: Text(
              'Gửi yêu thương qua từng đóa hoa',
              style: TextStyle(
                color: taglineColor,
                fontSize: 13.5,
                fontStyle: FontStyle.italic,
                letterSpacing: 0.3,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Hoa hồng line-art: xoắn ốc cánh hoa + viền cánh + cuống + lá, vẽ dần theo [progress].
class _RosePainter extends CustomPainter {
  final double progress;
  final Color color;
  _RosePainter({required this.progress, required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    if (progress <= 0) return;
    final w = size.width;
    final h = size.height;
    final c = Offset(w / 2, h * 0.36);
    final r = w * 0.30; // bán kính "nụ hoa"

    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2.2
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..color = color;

    // Xoắn ốc ở tâm
    final spiral = Path();
    const steps = 120;
    for (var i = 0; i <= steps; i++) {
      final t = i / steps;
      final theta = t * 3 * 2 * math.pi;
      final rad = r * 0.85 * t;
      final x = c.dx + rad * math.cos(theta);
      final y = c.dy + rad * math.sin(theta) * 0.9;
      if (i == 0) {
        spiral.moveTo(x, y);
      } else {
        spiral.lineTo(x, y);
      }
    }

    // Viền trên và đáy chén hoa
    final left = Offset(c.dx - r * 1.15, c.dy - r * 0.2);
    final right = Offset(c.dx + r * 1.15, c.dy - r * 0.2);
    final top = Path()
      ..moveTo(left.dx, left.dy)
      ..cubicTo(
        c.dx - r * 1.25,
        c.dy - r * 1.45,
        c.dx + r * 1.25,
        c.dy - r * 1.45,
        right.dx,
        right.dy,
      );
    final cup = Path()
      ..moveTo(left.dx, left.dy)
      ..cubicTo(
        c.dx - r * 1.3,
        c.dy + r * 1.5,
        c.dx + r * 1.3,
        c.dy + r * 1.5,
        right.dx,
        right.dy,
      );

    // Cuống + lá
    final cupBottom = Offset(c.dx, c.dy + r * 1.075);
    final stem = Path()
      ..moveTo(cupBottom.dx, cupBottom.dy)
      ..lineTo(c.dx, h * 0.98);
    final ly = h * 0.80;
    final leaf = Path()
      ..moveTo(c.dx, ly)
      ..quadraticBezierTo(
        c.dx + r * 0.9,
        ly - r * 0.05,
        c.dx + r * 1.25,
        ly - r * 0.75,
      )
      ..quadraticBezierTo(c.dx + r * 0.35, ly - r * 0.75, c.dx, ly);

    for (final p in [spiral, top, cup, stem, leaf]) {
      for (final m in p.computeMetrics()) {
        canvas.drawPath(m.extractPath(0, m.length * progress), paint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _RosePainter old) =>
      old.progress != progress || old.color != color;
}
