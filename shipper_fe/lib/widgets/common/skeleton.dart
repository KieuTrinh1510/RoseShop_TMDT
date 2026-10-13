// lib/widgets/skeleton.dart
// Skeleton loading + shimmer, không dùng package ngoài.
import 'package:flutter/material.dart';
import 'glass_container.dart';

/// Cung cấp giá trị animation (0..1, lặp) cho mọi SkeletonBox bên dưới.
/// Dùng 1 controller chung để các ô shimmer chạy đồng bộ.
class SkeletonShimmer extends StatefulWidget {
  final Widget child;
  const SkeletonShimmer({super.key, required this.child});

  @override
  State<SkeletonShimmer> createState() => _SkeletonShimmerState();
}

class _SkeletonShimmerState extends State<SkeletonShimmer>
    with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1300),
  )..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return _ShimmerScope(animation: _c, child: widget.child);
  }
}

class _ShimmerScope extends InheritedWidget {
  final Animation<double> animation;
  const _ShimmerScope({required this.animation, required super.child});

  static Animation<double>? of(BuildContext context) =>
      context.dependOnInheritedWidgetOfExactType<_ShimmerScope>()?.animation;

  @override
  bool updateShouldNotify(_ShimmerScope old) => old.animation != animation;
}

class _SlideGradient extends GradientTransform {
  final double t; // 0..1
  const _SlideGradient(this.t);

  @override
  Matrix4? transform(Rect bounds, {TextDirection? textDirection}) {
    return Matrix4.translationValues(bounds.width * (2 * t - 1), 0, 0);
  }
}

/// Một khối xám có ánh sáng quét ngang.
class SkeletonBox extends StatelessWidget {
  final double? width;
  final double height;
  final double radius;

  const SkeletonBox({super.key, this.width, this.height = 14, this.radius = 8});

  @override
  Widget build(BuildContext context) {
    final anim = _ShimmerScope.of(context);
    final base = Colors.white.withOpacity(0.22);
    final light = Colors.white.withOpacity(0.48);

    Widget box(double t) => Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(radius),
        gradient: LinearGradient(
          colors: [base, light, base],
          stops: const [0.3, 0.5, 0.7],
          transform: _SlideGradient(t),
        ),
      ),
    );

    if (anim == null) return box(0.5);
    return AnimatedBuilder(
      animation: anim,
      builder: (_, __) => box(anim.value),
    );
  }
}

// ---------------------------------------------------------------------------
// Các skeleton theo từng loại trang. Padding giống trang thật để không bị
// "nhảy layout" khi nội dung thật hiện ra.

double _bottomSpace(BuildContext context) =>
    130 + MediaQuery.of(context).padding.bottom;

class _SkeletonPage extends StatelessWidget {
  final List<Widget> children;
  const _SkeletonPage({required this.children});

  @override
  Widget build(BuildContext context) {
    return SkeletonShimmer(
      child: SafeArea(
        bottom: false,
        child: SingleChildScrollView(
          physics: const NeverScrollableScrollPhysics(),
          padding: EdgeInsets.fromLTRB(20, 16, 20, _bottomSpace(context)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SkeletonBox(width: 190, height: 34, radius: 10),
              const SizedBox(height: 10),
              const SkeletonBox(width: 150, height: 14),
              const SizedBox(height: 26),
              ...children,
            ],
          ),
        ),
      ),
    );
  }
}

class _SkeletonStatCard extends StatelessWidget {
  const _SkeletonStatCard();

  @override
  Widget build(BuildContext context) {
    return const GlassContainer(
      blur: 0,
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SkeletonBox(width: 80, height: 12),
          SizedBox(height: 18),
          SkeletonBox(width: 70, height: 30, radius: 10),
          SizedBox(height: 14),
          SkeletonBox(height: 10),
        ],
      ),
    );
  }
}

class _SkeletonTile extends StatelessWidget {
  const _SkeletonTile();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.only(bottom: 12),
      child: GlassContainer(
        blur: 0,
        padding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        child: Row(
          children: [
            SkeletonBox(width: 28, height: 28, radius: 8),
            SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SkeletonBox(width: 110, height: 15),
                  SizedBox(height: 8),
                  SkeletonBox(width: 170, height: 12),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class HomeSkeleton extends StatelessWidget {
  const HomeSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return _SkeletonPage(
      children: [
        LayoutBuilder(
          builder: (context, c) {
            const gap = 12.0;
            final w = (c.maxWidth - gap) / 2;
            return Wrap(
              spacing: gap,
              runSpacing: gap,
              children: [
                for (int i = 0; i < 4; i++)
                  SizedBox(width: w, child: const _SkeletonStatCard()),
              ],
            );
          },
        ),
        const SizedBox(height: 12),
        const _SkeletonTile(),
      ],
    );
  }
}

class ListSkeleton extends StatelessWidget {
  const ListSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return _SkeletonPage(
      children: [for (int i = 0; i < 6; i++) const _SkeletonTile()],
    );
  }
}

class ProfileSkeleton extends StatelessWidget {
  const ProfileSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return const _SkeletonPage(
      children: [
        GlassContainer(
          blur: 0,
          child: Row(
            children: [
              SkeletonBox(width: 56, height: 56, radius: 28),
              SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SkeletonBox(width: 140, height: 16),
                    SizedBox(height: 8),
                    SkeletonBox(width: 200, height: 12),
                  ],
                ),
              ),
            ],
          ),
        ),
        SizedBox(height: 12),
        _SkeletonTile(),
      ],
    );
  }
}
