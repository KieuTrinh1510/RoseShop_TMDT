// lib/widgets/liquid_bottom_nav.dart
// Thanh điều hướng nổi, kính mờ, có "vết lõm" chạy theo tab active
// và vòng tròn trắng nổi lên chứa icon (giống ảnh Liquid Navigation).
import 'dart:ui';
import 'package:flutter/material.dart';

class NavItemData {
  final IconData icon;
  final String label;
  const NavItemData({required this.icon, required this.label});
}

class LiquidBottomNav extends StatefulWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;
  final List<NavItemData> items;

  const LiquidBottomNav({
    super.key,
    required this.currentIndex,
    required this.onTap,
    required this.items,
  });

  @override
  State<LiquidBottomNav> createState() => _LiquidBottomNavState();
}

class _LiquidBottomNavState extends State<LiquidBottomNav>
    with SingleTickerProviderStateMixin {
  static const double barH = 72;
  static const double topExtra = 30; // vùng phía trên cho vòng tròn nổi
  static const double circleD = 54;
  static const Color navy = Color(0xFF3A1230);

  // value = vị trí (theo index, số thực) của indicator -> luôn trượt mượt,
  // bấm liên tục vẫn retarget từ vị trí hiện tại, không bị giật.
  late final AnimationController _c = AnimationController(
    vsync: this,
    lowerBound: 0,
    upperBound: (widget.items.length - 1).toDouble(),
    value: widget.currentIndex.toDouble(),
  );

  @override
  void didUpdateWidget(covariant LiquidBottomNav old) {
    super.didUpdateWidget(old);
    if (old.currentIndex != widget.currentIndex) {
      _c.animateTo(
        widget.currentIndex.toDouble(),
        duration: const Duration(milliseconds: 350),
        curve: Curves.easeOutCubic,
      );
    }
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final n = widget.items.length;
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        final itemW = width / n;

        return SizedBox(
          height: barH + topExtra,
          child: AnimatedBuilder(
            animation: _c,
            builder: (context, _) {
              final pos = _c.value;
              final cx = (pos + 0.5) * itemW;
              final barSize = Size(width, barH);

              return Stack(
                clipBehavior: Clip.none,
                children: [
                  // 1) Bóng + viền
                  Positioned(
                    top: topExtra,
                    left: 0,
                    right: 0,
                    height: barH,
                    child: IgnorePointer(
                      child: CustomPaint(painter: _NavBorderPainter(cx)),
                    ),
                  ),
                  // 2) Nền kính mờ theo hình có vết lõm
                  Positioned(
                    top: topExtra,
                    left: 0,
                    right: 0,
                    height: barH,
                    child: ClipPath(
                      clipper: _NavClipper(cx),
                      child: BackdropFilter(
                        filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
                        child: Container(color: navy.withOpacity(0.82)),
                      ),
                    ),
                  ),
                  // 3) Icon inactive + label active
                  for (int i = 0; i < n; i++)
                    Positioned(
                      top: topExtra,
                      left: i * itemW,
                      width: itemW,
                      height: barH,
                      child: _buildItem(i, pos),
                    ),
                  // 4) Vòng tròn trắng nổi
                  Positioned(
                    left: cx - circleD / 2,
                    top: topExtra + 2 - circleD / 2,
                    width: circleD,
                    height: circleD,
                    child: IgnorePointer(child: _buildCircle(pos)),
                  ),
                  // 5) Lớp bắt chạm
                  Positioned.fill(
                    top: 0,
                    child: Row(
                      children: [
                        for (int i = 0; i < n; i++)
                          Expanded(
                            child: Semantics(
                              button: true,
                              selected: i == widget.currentIndex,
                              label: widget.items[i].label,
                              child: GestureDetector(
                                behavior: HitTestBehavior.opaque,
                                onTap: () => widget.onTap(i),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              );
            },
          ),
        );
      },
    );
  }

  double _strength(int i, double pos) => (1 - (pos - i).abs()).clamp(0.0, 1.0);

  Widget _buildItem(int i, double pos) {
    final s = _strength(i, pos);
    final item = widget.items[i];
    return Stack(
      alignment: Alignment.center,
      children: [
        // icon inactive (mờ dần khi trở thành active)
        Opacity(
          opacity: (1 - s) * 0.7,
          child: Icon(item.icon, size: 24, color: Colors.white),
        ),
        // label active (fade + trượt lên nhẹ)
        Positioned(
          bottom: 9,
          child: Opacity(
            opacity: s,
            child: Transform.translate(
              offset: Offset(0, (1 - s) * 6),
              child: Text(
                item.label,
                maxLines: 1,
                overflow: TextOverflow.clip,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCircle(double pos) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.22),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          for (int i = 0; i < widget.items.length; i++)
            Opacity(
              opacity: _strength(i, pos),
              child: Transform.scale(
                scale: 0.9 + 0.1 * _strength(i, pos),
                child: Icon(widget.items[i].icon, size: 26, color: navy),
              ),
            ),
        ],
      ),
    );
  }
}

/// Tạo hình thanh bar bo góc với vết lõm mượt quanh vị trí cx.
Path _navPath(Size size, double cx) {
  const double hw = 46; // nửa bề rộng vết lõm
  const double depth = 36; // độ sâu vết lõm
  const double radius = 28; // bo góc thanh
  final w = size.width;
  final h = size.height;

  final notched = Path()
    ..moveTo(0, 0)
    ..lineTo(cx - hw, 0)
    ..cubicTo(cx - hw + 24, 0, cx - 26, depth, cx, depth)
    ..cubicTo(cx + 26, depth, cx + hw - 24, 0, cx + hw, 0)
    ..lineTo(w, 0)
    ..lineTo(w, h)
    ..lineTo(0, h)
    ..close();

  final rrect = Path()
    ..addRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTWH(0, 0, w, h),
        const Radius.circular(radius),
      ),
    );

  return Path.combine(PathOperation.intersect, rrect, notched);
}

class _NavClipper extends CustomClipper<Path> {
  final double cx;
  _NavClipper(this.cx);

  @override
  Path getClip(Size size) => _navPath(size, cx);

  @override
  bool shouldReclip(covariant _NavClipper old) => old.cx != cx;
}

class _NavBorderPainter extends CustomPainter {
  final double cx;
  _NavBorderPainter(this.cx);

  @override
  void paint(Canvas canvas, Size size) {
    final path = _navPath(size, cx);
    canvas.drawShadow(path, Colors.black.withOpacity(0.45), 12, true);
    canvas.drawPath(
      path,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1
        ..color = Colors.white.withOpacity(0.14),
    );
  }

  @override
  bool shouldRepaint(covariant _NavBorderPainter old) => old.cx != cx;
}
