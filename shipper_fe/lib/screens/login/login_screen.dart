// lib/screens/login/login_screen.dart
// Splash + Đăng nhập Roseshop trong MỘT màn hình.
//
// Trình tự animation (1 AnimationController, 3000ms):
// Phần animation logo + mốc thời gian nằm ở
// widgets/login_animation/animation_text_roseshop.dart.
// Intro chạy MỖI LẦN màn này được tạo: mở app, đăng xuất, hết phiên đăng nhập.
// (Người dùng bật "giảm chuyển động" của hệ thống thì hiện form luôn.)
//
// Đăng nhập thật: AuthService.login() -> POST {baseUrl}/auth/login.
// Nhấn giữ logo để đổi địa chỉ máy chủ (dùng khi chạy thiết bị thật).

import 'package:flutter/material.dart';

import '../../config.dart';
import '../../services/auth_service.dart';
import '../../widgets/login_animation/animation_text_roseshop.dart';
import '../home/home_screen.dart';
import 'forgot_password_screen.dart';

// ── Bảng màu (tông web Roseshop) ───────────────────────────────
class _Rose {
  static const primary = Color(0xFFEC3D8F);
  static const primaryHover = Color(0xFFD8287C);
  static const primaryLight = Color(0xFFFDE8F1);
  static const border = Color(0xFFF1DFE7);
  static const borderLight = Color(0xFFF8EDF2);
  static const textDark = Color(0xFF3B1F2E);
  static const textMuted = Color(0xFF8C6F7E);
  static const danger = Color(0xFFB42318);
  static const dangerBg = Color(0xFFFDECEE);
}

// ── Tham số có thể chỉnh ──────────────────────────────────────
// Thời lượng / tỉ lệ khu vực logo / mốc animation: xem LoginIntroTiming.
const _kPanelRadius = 28.0;

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(
    vsync: this,
    duration: LoginIntroTiming.total,
  );
  final _userCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _passFocus = FocusNode();

  bool _started = false;
  bool _obscure = true;
  bool _loading = false;
  String? _userErr;
  String? _passErr;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_started) return;
    _started = true;
    if (MediaQuery.disableAnimationsOf(context)) {
      _c.value = 1; // người dùng tắt animation hệ thống -> hiện form luôn
    } else {
      _c.forward(); // chạy intro mỗi lần vào màn này (kể cả sau đăng xuất)
    }
  }

  @override
  void dispose() {
    _c.dispose();
    _userCtrl.dispose();
    _passCtrl.dispose();
    _passFocus.dispose();
    super.dispose();
  }

  // ── Đăng nhập ────────────────────────────────────────────────
  Future<void> _submit() async {
    if (_loading) return; // chặn bấm nhiều lần
    final user = _userCtrl.text.trim();
    final pass = _passCtrl.text;

    setState(() {
      _userErr = user.isEmpty ? 'Vui lòng nhập tên đăng nhập' : null;
      _passErr = pass.isEmpty ? 'Vui lòng nhập mật khẩu' : null;
      _error = null;
    });
    if (_userErr != null || _passErr != null) return;

    FocusScope.of(context).unfocus();
    setState(() => _loading = true);

    try {
      await AuthService.login(user, pass);
      if (!mounted) return;
      Navigator.of(
        context,
      ).pushReplacement(MaterialPageRoute(builder: (_) => const HomeScreen()));
    } on AuthException catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.message;
      });
    } catch (_) {
      // Mất mạng, timeout, sai địa chỉ máy chủ...
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error =
            'Không kết nối được máy chủ. Kiểm tra mạng hoặc địa chỉ máy chủ '
            '(nhấn giữ logo để đổi).';
      });
    }
  }

  Future<void> _forgot() async {
    final ok = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const ForgotPasswordScreen()),
    );
    if (ok == true && mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(
          const SnackBar(
            behavior: SnackBarBehavior.floating,
            content: Text(
              'Đặt lại mật khẩu thành công. Hãy đăng nhập bằng mật khẩu mới.',
            ),
          ),
        );
    }
  }

  Future<void> _editServer() async {
    final saved = await showDialog<String>(
      context: context,
      builder: (_) => const _ServerDialog(),
    );
    if (saved == null) return;
    await AppConfig.setBaseUrl(saved);
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          behavior: SnackBarBehavior.floating,
          content: Text('Đã lưu địa chỉ máy chủ: ${AppConfig.baseUrl}'),
        ),
      );
  }

  // ── Build ────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    final pad = MediaQuery.paddingOf(context);
    final keyboard = MediaQuery.viewInsetsOf(context).bottom;

    final headerH = size.height * LoginIntroTiming.headerRatio;

    return Scaffold(
      // Tự xử lý bàn phím (panel form co lại theo bàn phím) để logo không bị nhảy.
      resizeToAvoidBottomInset: false,
      backgroundColor: _Rose.borderLight,
      body: AnimatedBuilder(
        animation: _c,
        builder: (context, _) {
          final v = _c.value;
          final panel = LoginIntroTiming.panel(v);

          return Stack(
            children: [
              // Nền hồng pastel + hai vòng tròn mờ trang trí
              Positioned.fill(
                child: DecoratedBox(
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [_Rose.primaryLight, _Rose.borderLight],
                    ),
                  ),
                ),
              ),
              Positioned(
                top: -70,
                right: -50,
                child: _Blob(size: 220, alpha: 0.07),
              ),
              Positioned(
                top: headerH * 0.45,
                left: -80,
                child: _Blob(size: 170, alpha: 0.05),
              ),

              // Khung form (2/3 dưới)
              Positioned(
                top: headerH,
                left: 0,
                right: 0,
                bottom: 0,
                child: IgnorePointer(
                  ignoring: v < LoginIntroTiming.formReadyAt,
                  child: Opacity(
                    opacity: panel,
                    child: Transform.translate(
                      offset: Offset(0, (1 - panel) * 40),
                      child: Container(
                        clipBehavior: Clip.antiAlias,
                        decoration: const BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.vertical(
                            top: Radius.circular(_kPanelRadius),
                          ),
                        ),
                        child: Padding(
                          // Thu vùng cuộn lại phía trên bàn phím
                          padding: EdgeInsets.only(bottom: keyboard),
                          child: SingleChildScrollView(
                            keyboardDismissBehavior:
                                ScrollViewKeyboardDismissBehavior.onDrag,
                            padding: EdgeInsets.fromLTRB(
                              24,
                              28,
                              24,
                              24 + (keyboard > 0 ? 0 : pad.bottom),
                            ),
                            child: Center(
                              child: ConstrainedBox(
                                constraints: const BoxConstraints(
                                  maxWidth: 440,
                                ),
                                child: _buildForm(v),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),

              // Logo: từ giữa màn hình dời lên khu vực 1/3 trên
              AnimationTextRoseshop(
                animation: _c,
                color: _Rose.primary,
                taglineColor: _Rose.textMuted,
                onLongPress: _editServer,
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildForm(double v) {
    // Mỗi phần tử hiện lệch nhịp: fade + trượt lên nhẹ.
    Widget item(int i, Widget child) {
      final t = LoginIntroTiming.formItem(v, i);
      return Opacity(
        opacity: t,
        child: Transform.translate(
          offset: Offset(0, (1 - t) * 18),
          child: child,
        ),
      );
    }

    return AutofillGroup(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          item(
            0,
            const Text(
              'Chào mừng đến với Roseshop',
              style: TextStyle(
                color: _Rose.textDark,
                fontSize: 24,
                fontWeight: FontWeight.w700,
                height: 1.2,
              ),
            ),
          ),
          const SizedBox(height: 8),
          item(
            1,
            const Text(
              'Đăng nhập tài khoản shipper để nhận và giao những bó hoa tươi thắm.',
              style: TextStyle(
                color: _Rose.textMuted,
                fontSize: 14.5,
                height: 1.4,
              ),
            ),
          ),
          const SizedBox(height: 24),
          item(
            2,
            TextField(
              controller: _userCtrl,
              enabled: !_loading,
              keyboardType: TextInputType.text,
              textInputAction: TextInputAction.next,
              autocorrect: false,
              enableSuggestions: false,
              autofillHints: const [AutofillHints.username],
              onSubmitted: (_) => _passFocus.requestFocus(),
              onChanged: (_) {
                if (_userErr != null) setState(() => _userErr = null);
              },
              decoration: _decoration(
                label: 'Tên đăng nhập',
                icon: Icons.person_outline_rounded,
                error: _userErr,
              ),
            ),
          ),
          const SizedBox(height: 14),
          item(
            3,
            TextField(
              controller: _passCtrl,
              focusNode: _passFocus,
              enabled: !_loading,
              obscureText: _obscure,
              textInputAction: TextInputAction.done,
              autocorrect: false,
              enableSuggestions: false,
              autofillHints: const [AutofillHints.password],
              onSubmitted: (_) => _submit(),
              onChanged: (_) {
                if (_passErr != null) setState(() => _passErr = null);
              },
              decoration: _decoration(
                label: 'Mật khẩu',
                icon: Icons.lock_outline_rounded,
                error: _passErr,
                suffix: IconButton(
                  tooltip: _obscure ? 'Hiện mật khẩu' : 'Ẩn mật khẩu',
                  onPressed: () => setState(() => _obscure = !_obscure),
                  icon: Icon(
                    _obscure
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                    color: _Rose.textMuted,
                  ),
                ),
              ),
            ),
          ),
          item(
            4,
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: _loading ? null : _forgot,
                style: TextButton.styleFrom(foregroundColor: _Rose.primary),
                child: const Text(
                  'Quên mật khẩu?',
                  style: TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
            ),
          ),
          item(
            5,
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                AnimatedSize(
                  duration: const Duration(milliseconds: 200),
                  alignment: Alignment.topCenter,
                  child: _error == null
                      ? const SizedBox(width: double.infinity)
                      : Padding(
                          padding: const EdgeInsets.only(bottom: 14),
                          child: _ErrorBox(message: _error!),
                        ),
                ),
                SizedBox(
                  height: 52,
                  child: FilledButton(
                    onPressed: _loading ? null : _submit,
                    style: ButtonStyle(
                      elevation: const WidgetStatePropertyAll(0.0),
                      foregroundColor: const WidgetStatePropertyAll(
                        Colors.white,
                      ),
                      backgroundColor: WidgetStateProperty.resolveWith((s) {
                        if (s.contains(WidgetState.disabled)) {
                          return _Rose.primary.withValues(alpha: 0.65);
                        }
                        if (s.contains(WidgetState.pressed) ||
                            s.contains(WidgetState.hovered)) {
                          return _Rose.primaryHover;
                        }
                        return _Rose.primary;
                      }),
                      shape: WidgetStatePropertyAll(
                        RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                    ),
                    child: _loading
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.4,
                              color: Colors.white,
                            ),
                          )
                        : const Text(
                            'Đăng nhập',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 22),
          item(
            6,
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                Padding(
                  padding: EdgeInsets.only(top: 1),
                  child: Icon(
                    Icons.info_outline_rounded,
                    size: 16,
                    color: _Rose.textMuted,
                  ),
                ),
                SizedBox(width: 6),
                Flexible(
                  child: Text(
                    'Tài khoản shipper do cửa hàng cấp. Cần hỗ trợ, hãy liên hệ quản trị viên.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: _Rose.textMuted,
                      fontSize: 12.5,
                      height: 1.4,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  InputDecoration _decoration({
    required String label,
    required IconData icon,
    String? error,
    Widget? suffix,
  }) {
    OutlineInputBorder border(Color c, [double w = 1]) => OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide(color: c, width: w),
    );
    return InputDecoration(
      labelText: label,
      errorText: error,
      prefixIcon: Icon(icon, color: _Rose.textMuted),
      suffixIcon: suffix,
      filled: true,
      fillColor: Colors.white,
      floatingLabelStyle: const TextStyle(color: _Rose.primary),
      labelStyle: const TextStyle(color: _Rose.textMuted),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      enabledBorder: border(_Rose.border),
      disabledBorder: border(_Rose.borderLight),
      focusedBorder: border(_Rose.primary, 1.6),
      errorBorder: border(_Rose.danger),
      focusedErrorBorder: border(_Rose.danger, 1.6),
    );
  }
}

class _Blob extends StatelessWidget {
  final double size;
  final double alpha;
  const _Blob({required this.size, required this.alpha});

  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      shape: BoxShape.circle,
      color: _Rose.primary.withValues(alpha: alpha),
    ),
  );
}

class _ErrorBox extends StatelessWidget {
  final String message;
  const _ErrorBox({required this.message});

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    decoration: BoxDecoration(
      color: _Rose.dangerBg,
      borderRadius: BorderRadius.circular(12),
      border: Border.all(color: _Rose.danger.withValues(alpha: 0.25)),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(top: 1),
          child: Icon(
            Icons.error_outline_rounded,
            size: 18,
            color: _Rose.danger,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            message,
            style: const TextStyle(
              color: _Rose.danger,
              fontSize: 13.5,
              height: 1.35,
            ),
          ),
        ),
      ],
    ),
  );
}

// ── Hộp thoại đổi địa chỉ máy chủ (nhấn giữ logo) ──────────────
class _ServerDialog extends StatefulWidget {
  const _ServerDialog();

  @override
  State<_ServerDialog> createState() => _ServerDialogState();
}

class _ServerDialogState extends State<_ServerDialog> {
  late final TextEditingController _ctrl = TextEditingController(
    text: AppConfig.baseUrl,
  );
  String? _err;

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  void _save() {
    final u = AppConfig.normalizeUrl(_ctrl.text);
    if (u == null) {
      setState(
        () => _err =
            'Địa chỉ không hợp lệ (cần bắt đầu bằng http:// hoặc https://)',
      );
      return;
    }
    Navigator.pop(context, u);
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      title: const Text(
        'Địa chỉ máy chủ',
        style: TextStyle(color: _Rose.textDark, fontWeight: FontWeight.w700),
      ),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextField(
              controller: _ctrl,
              keyboardType: TextInputType.url,
              autocorrect: false,
              enableSuggestions: false,
              decoration: InputDecoration(
                hintText: AppConfig.defaultBaseUrl,
                errorText: _err,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              '• Android emulator: http://10.0.2.2:3000/api/shipper\n'
              '• iOS simulator / Web: http://localhost:3000/api/shipper\n'
              '• Thiết bị thật: http://<IP LAN máy chạy NodeJS>:3000/api/shipper',
              style: TextStyle(
                color: _Rose.textMuted,
                fontSize: 12,
                height: 1.4,
              ),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => setState(() {
            _ctrl.text = AppConfig.defaultBaseUrl;
            _err = null;
          }),
          child: const Text('Mặc định'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Hủy'),
        ),
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: _Rose.primary),
          onPressed: _save,
          child: const Text('Lưu'),
        ),
      ],
    );
  }
}
