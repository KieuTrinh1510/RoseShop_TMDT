// lib/widgets/common/user_avatar.dart
// Avatar chung cho mọi màn: vòng tròn tím + icon hình người.
// Dùng: const UserAvatar(size: 60). Không phụ thuộc tên nên đổi tên không bao giờ sai.

import 'package:flutter/material.dart';
import '../../theme/pink_theme.dart';

class UserAvatar extends StatelessWidget {
  final double size;
  const UserAvatar({super.key, this.size = 60});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: const BoxDecoration(shape: BoxShape.circle, color: Pink.plum),
      alignment: Alignment.center,
      child: Icon(Icons.person_rounded, color: Colors.white, size: size * 0.56),
    );
  }
}
