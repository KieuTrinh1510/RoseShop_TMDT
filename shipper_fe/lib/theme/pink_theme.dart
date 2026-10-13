// lib/theme/pink_theme.dart
// Bảng màu hồng + các widget kính mờ dùng chung cho trang Đơn hàng / Chi tiết đơn.

import 'package:flutter/material.dart';
import '../widgets/common/glass_container.dart';
import '../utils/app_strings.dart';
import '../controllers/profile/app_settings_controller.dart';

class Pink {
  Pink._();

  // ── Màu chủ đạo (primary) ────────────────────────────────────
  static const rose = Color(0xFFEC3D8F);
  static const roseDeep = Color(0xFFD8287C);
  static const blush = Color(0xFFFDE8F1); // primary light (nền nhạt)

  // ── Viền ─────────────────────────────────────────────────────
  static const roseBorder = Color(0xFFF1DFE7);
  static const roseBorderLight = Color(0xFFF8EDF2);
  // ── Cảnh báo (cảnh báo quá hạn, dialog nền) ──────────────────
  static const warningBg = Color(0xFFFFD6E0); // nền hồng cảnh báo
  static const warningBgDeep = Color(0xFFFFC1CE); // nền đậm hơn cho overdue
  static const dialogBg = Color(0xFFFFF5F9); // nền dialog

  // ── Chữ ──────────────────────────────────────────────────────
  static const plum = Color(0xFF3A1230);
  static const muted = Color(0x993A1230);
  static const faint = Color(0x663A1230); // chữ mờ (40%)

  // ── Màu phụ trợ ──────────────────────────────────────────────
  static const orange = Color(0xFFC2620A);
  static const violet = Color(0xFF7A3E9D);
  static const leaf = Color(0xFF2E7D4F);
  static const danger = Color(0xFFC62828);
  static const grey = Color(0xFF6B5A66);

  // ── Gradient nút ─────────────────────────────────────────────
  static const buttonGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFF2579F), Color(0xFFD8287C)],
  );
}

/// Nền hồng + vài đốm sáng. Trả về Positioned.fill nên đặt trực tiếp trong Stack.
class PinkBackground extends StatelessWidget {
  const PinkBackground({super.key});

  @override
  Widget build(BuildContext context) {
    return Positioned.fill(
      child: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFFFF5F9), Color(0xFFFDE8F1), Color(0xFFF5C5DA)],
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              top: -70,
              right: -60,
              child: _blob(240, Colors.white.withOpacity(0.35)),
            ),
            Positioned(
              top: 300,
              left: -90,
              child: _blob(260, const Color(0xFFEC3D8F).withOpacity(0.16)),
            ),
            Positioned(
              bottom: -80,
              right: -40,
              child: _blob(220, Colors.white.withOpacity(0.22)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _blob(double size, Color color) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(shape: BoxShape.circle, color: color),
  );
}

/// Card kính mờ tông hồng (bọc GlassContainer có sẵn).
class PinkCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;
  final double radius;
  final Color tint;
  final double opacity;
  final double blur;

  const PinkCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.radius = 24,
    this.tint = Colors.white,
    this.opacity = 0.42,
    this.blur = 16,
  });

  @override
  Widget build(BuildContext context) {
    return GlassContainer(
      blur: blur,
      opacity: opacity,
      borderRadius: radius,
      padding: padding,
      tint: tint,
      borderColor: const Color(0xB3FFFFFF),
      child: child,
    );
  }
}

/// Nhãn nhỏ bo tròn: "Chờ lấy", "COD", "🌸 Hoa tươi"...
class PinkChip extends StatelessWidget {
  final String label;
  final Color color;
  final IconData? icon;

  const PinkChip({
    super.key,
    required this.label,
    required this.color,
    this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.14),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withOpacity(0.45)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 13, color: color),
            const SizedBox(width: 4),
          ],
          Text(
            label,
            style: TextStyle(
              color: color,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class StatusStyle {
  final String label;
  final Color color;
  const StatusStyle(this.label, this.color);
}

/// Nhãn + màu theo trạng thái đơn. [nhan] là nhan_trang_thai backend trả về (nếu có).
StatusStyle statusStyle(String tt, [String? nhan]) {
  // Nhãn từ backend là tiếng Việt -> chỉ dùng khi app đang ở tiếng Việt.
  String pick(String fallback) =>
      (AppSettings.instance.language == 'vi' && nhan != null && nhan.isNotEmpty)
      ? nhan
      : fallback;
  switch (tt) {
    case 'CONFIRMED':
      return StatusStyle(pick(tr('f_confirmed')), Pink.orange);
    case 'SHIPPING':
      return StatusStyle(pick(tr('f_shipping')), Pink.roseDeep);
    case 'DELIVERED':
      return StatusStyle(pick(tr('st_delivered')), Pink.leaf);
    default:
      return StatusStyle(pick(tt.isEmpty ? '—' : tt), Pink.grey);
  }
}

/// Nút gradient hồng. busy = true thì hiện spinner và khóa bấm.
class PinkGradientButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback? onPressed;
  final bool busy;
  final bool expand;

  const PinkGradientButton({
    super.key,
    required this.label,
    required this.icon,
    required this.onPressed,
    this.busy = false,
    this.expand = true,
  });

  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null && !busy;
    return SizedBox(
      width: expand ? double.infinity : null,
      child: Opacity(
        opacity: disabled ? 0.5 : 1,
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(18),
            onTap: busy ? null : onPressed,
            child: Ink(
              padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 15),
              decoration: BoxDecoration(
                gradient: Pink.buttonGradient,
                borderRadius: BorderRadius.circular(18),
                boxShadow: [
                  BoxShadow(
                    color: Pink.rose.withOpacity(0.35),
                    blurRadius: 16,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (busy)
                    const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.4,
                        color: Colors.white,
                      ),
                    )
                  else
                    Icon(icon, color: Colors.white, size: 20),
                  const SizedBox(width: 10),
                  Flexible(
                    child: Text(
                      label,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 15.5,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class PinkErrorBox extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const PinkErrorBox({super.key, required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: PinkCard(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.cloud_off_rounded,
                size: 40,
                color: Pink.roseDeep,
              ),
              const SizedBox(height: 10),
              Text(
                message,
                textAlign: TextAlign.center,
                style: const TextStyle(color: Pink.plum, fontSize: 14),
              ),
              const SizedBox(height: 16),
              PinkGradientButton(
                label: tr('retry'),
                icon: Icons.refresh_rounded,
                onPressed: onRetry,
                expand: false,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
