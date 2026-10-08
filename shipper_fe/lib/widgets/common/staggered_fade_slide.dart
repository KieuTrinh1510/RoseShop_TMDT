// lib/widgets/staggered_fade_slide.dart
// Fade in + slide up nhẹ, tự tính delay theo index.
//
//   StaggeredFadeSlide(index: i, child: MyItem())
//
// Chỉnh nhanh: stepMs (khoảng cách giữa 2 item), durationMs, slide, maxIndex.
import 'package:flutter/material.dart';

class StaggeredFadeSlide extends StatefulWidget {
  final int index;
  final Widget child;

  /// Delay giữa các item liên tiếp (ms). index 0 = 0ms, 1 = stepMs, ...
  final int stepMs;

  /// Thời gian animation của mỗi item (ms).
  final int durationMs;

  /// Độ trượt lên, tính theo tỉ lệ chiều cao của widget (0.08 = 8%).
  final double slide;

  /// Item có index > maxIndex hiện ra ngay, không animation
  /// (tránh tạo quá nhiều controller / kéo dài animation với list dài).
  final int maxIndex;

  const StaggeredFadeSlide({
    super.key,
    required this.index,
    required this.child,
    this.stepMs = 70,
    this.durationMs = 400,
    this.slide = 0.08,
    this.maxIndex = 8,
  });

  @override
  State<StaggeredFadeSlide> createState() => _StaggeredFadeSlideState();
}

class _StaggeredFadeSlideState extends State<StaggeredFadeSlide>
    with SingleTickerProviderStateMixin {
  AnimationController? _controller;
  Animation<double>? _opacity;
  Animation<Offset>? _position;

  @override
  void initState() {
    super.initState();
    if (widget.index > widget.maxIndex) return;

    final delayMs = widget.index * widget.stepMs;
    final totalMs = delayMs + widget.durationMs;

    // 1 controller: phần đầu là "chờ" (delay), phần sau là animation thật.
    final controller = AnimationController(
      vsync: this,
      duration: Duration(milliseconds: totalMs),
    );
    final curved = CurvedAnimation(
      parent: controller,
      curve: Interval(delayMs / totalMs, 1.0, curve: Curves.easeOutCubic),
    );

    _controller = controller;
    _opacity = curved;
    _position = Tween<Offset>(
      begin: Offset(0, widget.slide),
      end: Offset.zero,
    ).animate(curved);

    controller.forward();
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_controller == null) return widget.child;
    return FadeTransition(
      opacity: _opacity!,
      child: SlideTransition(position: _position!, child: widget.child),
    );
  }
}
