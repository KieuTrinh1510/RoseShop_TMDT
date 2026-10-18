// lib/screens/profile/profile_tab.dart
// Tab Hồ sơ: header (avatar chung) + menu (thông tin cá nhân / tài khoản / ngôn ngữ / giao diện / đăng xuất).
// Ngôn ngữ + giao diện nằm ở AppSettings (toàn app, chỉ trong bộ nhớ, không gọi backend).

import 'package:flutter/material.dart';
import '../../controllers/profile/app_settings_controller.dart';
import '../../controllers/profile/profile_controller.dart';
import '../../theme/pink_theme.dart';
import '../../utils/app_strings.dart';
import '../../widgets/common/common.dart';
import '../../widgets/common/glass_container.dart';
import '../../widgets/common/skeleton.dart';
import '../../widgets/common/staggered_fade_slide.dart';
import '../../widgets/common/user_avatar.dart';
import '../../widgets/profile/profile_menu_tile.dart';
import '../login/login_screen.dart';
import '../login/forgot_password_screen.dart';
import '../home/home_screen.dart';
import '../../widgets/profile/dialogs/edit_info_dialog.dart';
import '../../widgets/profile/dialogs/change_username_dialog.dart';

class ProfileTab extends StatefulWidget {
  const ProfileTab({super.key});
  @override
  State<ProfileTab> createState() => _ProfileTabState();
}

class _ProfileTabState extends State<ProfileTab> {
  late final ProfileController _c = ProfileController();
  final AppSettings _settings = AppSettings.instance;

  @override
  void initState() {
    super.initState();
    _c.load();
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  void _snack(String msg, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(msg),
          behavior: SnackBarBehavior.floating,
          backgroundColor: error ? Pink.danger : Pink.plum,
        ),
      );
  }

  // ── Actions ─────────────────────────────────────────────────
  Future<void> _editInfo() async {
    final res = await showEditInfoDialog(
      context,
      ten: _c.tenNhanVien,
      sdt: _c.soDienThoai,
      email: _c.email,
    );
    if (res == null) return;

    // Chỉ gửi field thực sự thay đổi.
    final ten = res['ten_nhan_vien'];
    final sdt = res['so_dien_thoai'];
    final email = res['email'];
    final tenMoi = ten != _c.tenNhanVien ? ten : null;
    final sdtMoi = sdt != _c.soDienThoai ? sdt : null;
    final emailMoi = email != _c.email ? email : null;

    if (tenMoi == null && sdtMoi == null && emailMoi == null) {
      _snack(tr('msg_no_change'));
      return;
    }

    final err = await _c.updateInfo(
      tenNhanVien: tenMoi,
      soDienThoai: sdtMoi,
      email: emailMoi,
    );
    if (!mounted) return;
    _snack(err ?? tr('msg_info_updated'), error: err != null);
  }

  Future<void> _editAccount() async {
    final res = await showAccountDialog(context, current: _c.tenDangNhap);
    if (res == null) return;

    // Bấm "Quên mật khẩu?" -> mở màn đặt lại mật khẩu bằng mã gửi qua email.
    if (res.containsKey('quen_mat_khau')) {
      final ok = await Navigator.push<bool>(
        context,
        MaterialPageRoute(
          builder: (_) => ForgotPasswordScreen(initialEmail: _c.email),
        ),
      );
      if (!mounted) return;
      if (ok == true) {
        _snack(tr('msg_account_updated'));
        _goHome();
      }
      return;
    }

    String? err;
    if (res.containsKey('ten_dang_nhap_moi')) {
      err = await _c.changeUsername(res['ten_dang_nhap_moi']!);
    }
    if (err == null && res.containsKey('mat_khau_moi')) {
      err = await _c.changePassword(res['mat_khau_cu']!, res['mat_khau_moi']!);
    }
    if (!mounted) return;
    _snack(err ?? tr('msg_account_updated'), error: err != null);
    // Đổi mật khẩu thành công -> về trang chủ.
    if (err == null && res.containsKey('mat_khau_moi')) _goHome();
  }

  void _goHome() {
    context.findAncestorStateOfType<HomeScreenState>()?.goHome();
  }

  Future<void> _pickLanguage() async {
    final res = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => _SheetOptions(
        title: tr('language'),
        options: const [
          _Opt('vi', '🇻🇳 Tiếng Việt'),
          _Opt('en', '🇺🇸 English'),
        ],
        current: _settings.language,
      ),
    );
    if (res != null) _settings.setLanguage(res);
  }

  Future<void> _pickTheme() async {
    final res = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => _SheetOptions(
        title: tr('theme'),
        options: [
          _Opt('light', '☀️ ${tr('theme_light')}'),
          _Opt('dark', '🌙 ${tr('theme_dark')}'),
          _Opt('system', '⚙️ ${tr('theme_system')}'),
        ],
        current: _settings.theme,
      ),
    );
    if (res != null) _settings.setTheme(res);
  }

  Future<void> _logout() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Pink.dialogBg,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
        title: Text(
          tr('logout_title'),
          style: const TextStyle(
            color: Pink.plum,
            fontWeight: FontWeight.w800,
            fontSize: 18,
          ),
        ),
        content: Text(
          tr('logout_body'),
          style: const TextStyle(color: Pink.plum),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              tr('cancel'),
              style: const TextStyle(color: Pink.muted),
            ),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Pink.danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(tr('logout')),
          ),
        ],
      ),
    );
    if (ok != true) return;

    final err = await _c.logout();
    if (!mounted) return;
    if (err != null) {
      _snack(err, error: true);
      return;
    }
    // Chưa có auth thật — điều hướng về màn đăng nhập.
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  // ── Build ───────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      // Nghe cả controller dữ liệu lẫn cài đặt ngôn ngữ/giao diện.
      listenable: Listenable.merge([_c, _settings]),
      builder: (context, _) {
        if (_c.loading && _c.data == null) return const ProfileSkeleton();

        if (_c.error != null && _c.data == null) {
          return SafeArea(
            bottom: false,
            child: ErrorBox(message: _c.error!, onRetry: () => _c.load()),
          );
        }

        final bottomPad = 130 + MediaQuery.of(context).padding.bottom;

        return SafeArea(
          bottom: false,
          child: RefreshIndicator(
            color: Pink.rose,
            onRefresh: _c.refresh,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: EdgeInsets.fromLTRB(20, 16, 20, bottomPad),
              children: [
                StaggeredFadeSlide(
                  index: 0,
                  child: Text(
                    tr('profile_title'),
                    style: const TextStyle(
                      color: Pink.plum,
                      fontSize: 32,
                      fontWeight: FontWeight.w800,
                      height: 1.1,
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                StaggeredFadeSlide(
                  index: 1,
                  child: Text(
                    tr('profile_subtitle'),
                    style: TextStyle(
                      color: Pink.plum.withOpacity(0.6),
                      fontSize: 13.5,
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                StaggeredFadeSlide(index: 2, child: _buildHeaderCard()),
                const SizedBox(height: 18),
                ..._buildMenu(),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildHeaderCard() {
    return GlassContainer(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          const UserAvatar(size: 60),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _c.tenNhanVien.isEmpty ? '—' : _c.tenNhanVien,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Pink.plum,
                    fontSize: 19,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  '${tr('employee_id')}: ${_c.maNhanVien}',
                  style: TextStyle(
                    color: Pink.plum.withOpacity(0.6),
                    fontSize: 13,
                  ),
                ),
                if (_c.chucVu.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    _c.chucVu,
                    style: const TextStyle(color: Pink.muted, fontSize: 12.5),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  // Mỗi ô menu là 1 StaggeredFadeSlide (index tăng dần) -> hiện lần lượt.
  List<Widget> _buildMenu() {
    final tiles = <Widget>[
      ProfileMenuTile(
        icon: Icons.person_outline_rounded,
        title: tr('personal_info'),
        subtitle: tr('personal_info_sub'),
        onTap: _editInfo,
      ),
      ProfileMenuTile(
        icon: Icons.manage_accounts_outlined,
        title: tr('account'),
        subtitle: tr('account_sub'),
        onTap: _editAccount,
      ),
      ProfileMenuTile(
        icon: Icons.language_rounded,
        title: tr('language'),
        subtitle: _settings.language == 'vi' ? 'Tiếng Việt' : 'English',
        onTap: _pickLanguage,
      ),
      ProfileMenuTile(
        icon: Icons.palette_outlined,
        title: tr('theme'),
        subtitle: switch (_settings.theme) {
          'dark' => tr('theme_dark'),
          'system' => tr('theme_system'),
          _ => tr('theme_light'),
        },
        onTap: _pickTheme,
      ),
      ProfileMenuTile(
        icon: Icons.logout_rounded,
        title: tr('logout'),
        iconColor: Pink.danger,
        titleColor: Pink.danger,
        trailing: const SizedBox.shrink(),
        onTap: _logout,
      ),
    ];

    return [
      for (int i = 0; i < tiles.length; i++)
        StaggeredFadeSlide(index: 3 + i, child: tiles[i]),
    ];
  }
}

// ── Bottom sheet chọn option ──────────────────────────────────
class _Opt {
  final String key;
  final String label;
  const _Opt(this.key, this.label);
}

class _SheetOptions extends StatelessWidget {
  final String title;
  final List<_Opt> options;
  final String current;

  const _SheetOptions({
    required this.title,
    required this.options,
    required this.current,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        24,
        20,
        24,
        24 + MediaQuery.of(context).padding.bottom,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: Pink.plum,
              fontSize: 18,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          for (int i = 0; i < options.length; i++)
            StaggeredFadeSlide(
              index: i,
              child: RadioListTile<String>(
                value: options[i].key,
                groupValue: current,
                onChanged: (v) => Navigator.pop(context, v),
                activeColor: Pink.rose,
                title: Text(
                  options[i].label,
                  style: const TextStyle(
                    color: Pink.plum,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                contentPadding: EdgeInsets.zero,
              ),
            ),
        ],
      ),
    );
  }
}
