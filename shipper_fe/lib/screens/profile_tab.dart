// lib/screens/profile_tab.dart
// Placeholder — theo yêu cầu chưa làm gì.

import 'package:flutter/material.dart';
import '../config.dart';
import '../widgets/common/dashboard_card.dart';
import '../widgets/common/glass_container.dart';
import '../widgets/common/staggered_fade_slide.dart';
import 'dang_nhap_screen.dart';

class ProfileTab extends StatelessWidget {
  const ProfileTab({super.key});

  double _bottomSpace(BuildContext context) =>
      130 + MediaQuery.of(context).padding.bottom;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      bottom: false,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(20, 16, 20, _bottomSpace(context)),
        children: [
          const StaggeredFadeSlide(
            index: 0,
            child: Text(
              'Profile',
              style: TextStyle(
                color: kNavy,
                fontSize: 34,
                fontWeight: FontWeight.w800,
                height: 1.1,
              ),
            ),
          ),
          const SizedBox(height: 4),
          StaggeredFadeSlide(
            index: 1,
            child: Text(
              'Thông tin tài khoản',
              style: TextStyle(color: kNavy.withOpacity(0.65), fontSize: 15),
            ),
          ),
          const SizedBox(height: 22),
          StaggeredFadeSlide(
            index: 2,
            child: GlassContainer(
              child: Row(
                children: [
                  const CircleAvatar(
                    radius: 28,
                    backgroundColor: kNavy,
                    child: Icon(Icons.person_rounded, color: Colors.white),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Mã nhân viên: ${AppConfig.maNhanVien}',
                          style: const TextStyle(
                            color: kNavy,
                            fontSize: 17,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          AppConfig.baseUrl,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: kNavy.withOpacity(0.6),
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          StaggeredFadeSlide(
            index: 3,
            child: GlassContainer(
              padding: EdgeInsets.zero,
              child: ListTile(
                leading: const Icon(Icons.badge_outlined, color: kNavy),
                title: const Text('Đổi mã nhân viên / Base URL'),
                trailing: const Icon(Icons.chevron_right, color: kNavy),
                onTap: () => Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
