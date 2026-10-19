// lib/screens/login/forgot_password_screen.dart
// Quên mật khẩu shipper (2 bước, dùng chung API Node):
//   Bước 1: nhập email  -> POST /auth/forgot-password  (server gửi mã 6 số qua email)
//   Bước 2: nhập mã + mật khẩu mới -> POST /auth/reset-password
// Thành công: Navigator.pop(context, true) để màn gọi tự quyết định đi đâu tiếp.
//
// initialEmail: điền sẵn email (vd mở từ trang hồ sơ khi đã đăng nhập).

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../services/auth_service.dart';

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
  static const successBg = Color(0xFFE8F7EE);
  static const success = Color(0xFF16803C);
}

class ForgotPasswordScreen extends StatefulWidget {
  final String? initialEmail;
  const ForgotPasswordScreen({super.key, this.initialEmail});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  static final _emailRe = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');

  late final TextEditingController _emailCtrl = TextEditingController(
    text: widget.initialEmail ?? '',
  );
  final _codeCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();

  int _step = 0; // 0: nhập email, 1: nhập mã + mật khẩu mới
  bool _loading = false;
  bool _obscure = true;
  String? _error;
  String? _info;
  String? _emailErr;
  String? _codeErr;
  String? _passErr;
  String? _confirmErr;

  int _cooldown = 0; // giây còn lại trước khi được gửi lại mã
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    _emailCtrl.dispose();
    _codeCtrl.dispose();
    _passCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  void _startCooldown([int seconds = 60]) {
    _timer?.cancel();
    setState(() => _cooldown = seconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return t.cancel();
      if (_cooldown <= 1) {
        t.cancel();
        setState(() => _cooldown = 0);
      } else {
        setState(() => _cooldown--);
      }
    });
  }

  // ── Bước 1: gửi mã ───────────────────────────────────────────
  Future<void> _sendCode() async {
    if (_loading) return;
    final email = _emailCtrl.text.trim();
    if (email.isEmpty) {
      setState(() => _emailErr = 'Vui lòng nhập email');
      return;
    }
    if (!_emailRe.hasMatch(email)) {
      setState(() => _emailErr = 'Email không đúng định dạng');
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _loading = true;
      _emailErr = null;
      _error = null;
      _info = null;
    });

    try {
      final msg = await AuthService.forgotPassword(email);
      if (!mounted) return;
      setState(() {
        _loading = false;
        _step = 1;
        _info = msg;
      });
      _startCooldown();
    } on AuthException catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error =
            'Không kết nối được máy chủ. Kiểm tra mạng hoặc địa chỉ máy chủ.';
      });
    }
  }

  // ── Bước 2: đặt lại mật khẩu ─────────────────────────────────
  Future<void> _reset() async {
    if (_loading) return;
    final code = _codeCtrl.text.trim();
    final pass = _passCtrl.text;
    final confirm = _confirmCtrl.text;

    setState(() {
      _codeErr = RegExp(r'^\d{6}$').hasMatch(code)
          ? null
          : 'Mã xác thực gồm đúng 6 chữ số';
      _passErr = pass.length >= 6 ? null : 'Mật khẩu tối thiểu 6 ký tự';
      _confirmErr = confirm == pass ? null : 'Xác nhận mật khẩu không khớp';
      _error = null;
      _info = null;
    });
    if (_codeErr != null || _passErr != null || _confirmErr != null) return;

    FocusScope.of(context).unfocus();
    setState(() => _loading = true);

    try {
      await AuthService.resetPassword(
        email: _emailCtrl.text,
        maXacThuc: code,
        matKhauMoi: pass,
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on AuthException catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error =
            'Không kết nối được máy chủ. Kiểm tra mạng hoặc địa chỉ máy chủ.';
      });
    }
  }

  void _backToEmail() {
    _timer?.cancel();
    setState(() {
      _step = 0;
      _cooldown = 0;
      _error = null;
      _info = null;
      _codeCtrl.clear();
      _passCtrl.clear();
      _confirmCtrl.clear();
      _codeErr = _passErr = _confirmErr = null;
    });
  }

  // ── Build ────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _Rose.borderLight,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        foregroundColor: _Rose.textDark,
        title: const Text(
          'Quên mật khẩu',
          style: TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
            padding: const EdgeInsets.all(20),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(24),
                ),
                child: _step == 0 ? _buildStepEmail() : _buildStepReset(),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStepEmail() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text(
          '🌸 Quên mật khẩu?',
          style: TextStyle(
            color: _Rose.textDark,
            fontSize: 22,
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 8),
        const Text(
          'Nhập email đã đăng ký cho tài khoản shipper. Hệ thống sẽ gửi mã xác thực 6 số tới hộp thư của bạn.',
          style: TextStyle(color: _Rose.textMuted, fontSize: 14, height: 1.4),
        ),
        const SizedBox(height: 20),
        TextField(
          controller: _emailCtrl,
          enabled: !_loading,
          keyboardType: TextInputType.emailAddress,
          textInputAction: TextInputAction.done,
          autocorrect: false,
          enableSuggestions: false,
          autofillHints: const [AutofillHints.email],
          onSubmitted: (_) => _sendCode(),
          onChanged: (_) {
            if (_emailErr != null) setState(() => _emailErr = null);
          },
          decoration: _decoration(
            label: 'Email',
            icon: Icons.mail_outline_rounded,
            error: _emailErr,
          ),
        ),
        const SizedBox(height: 16),
        _messages(),
        _primaryButton('Gửi mã xác thực', _sendCode),
      ],
    );
  }

  Widget _buildStepReset() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text(
          '🔑 Đặt lại mật khẩu',
          style: TextStyle(
            color: _Rose.textDark,
            fontSize: 22,
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Mã đã được gửi tới ${_emailCtrl.text.trim()}. Mã có hiệu lực trong 60 phút.',
          style: const TextStyle(
            color: _Rose.textMuted,
            fontSize: 14,
            height: 1.4,
          ),
        ),
        const SizedBox(height: 20),
        TextField(
          controller: _codeCtrl,
          enabled: !_loading,
          keyboardType: TextInputType.number,
          textInputAction: TextInputAction.next,
          maxLength: 6,
          textAlign: TextAlign.center,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w700,
            letterSpacing: 8,
            color: _Rose.textDark,
          ),
          onChanged: (_) {
            if (_codeErr != null) setState(() => _codeErr = null);
          },
          decoration: _decoration(
            label: 'Mã xác thực 6 số',
            icon: Icons.pin_outlined,
            error: _codeErr,
          ).copyWith(counterText: ''),
        ),
        const SizedBox(height: 14),
        TextField(
          controller: _passCtrl,
          enabled: !_loading,
          obscureText: _obscure,
          textInputAction: TextInputAction.next,
          autocorrect: false,
          enableSuggestions: false,
          onChanged: (_) {
            if (_passErr != null) setState(() => _passErr = null);
          },
          decoration: _decoration(
            label: 'Mật khẩu mới (tối thiểu 6 ký tự)',
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
        const SizedBox(height: 14),
        TextField(
          controller: _confirmCtrl,
          enabled: !_loading,
          obscureText: _obscure,
          textInputAction: TextInputAction.done,
          autocorrect: false,
          enableSuggestions: false,
          onSubmitted: (_) => _reset(),
          onChanged: (_) {
            if (_confirmErr != null) setState(() => _confirmErr = null);
          },
          decoration: _decoration(
            label: 'Xác nhận mật khẩu mới',
            icon: Icons.lock_reset_rounded,
            error: _confirmErr,
          ),
        ),
        const SizedBox(height: 16),
        _messages(),
        _primaryButton('Lưu mật khẩu mới', _reset),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            TextButton(
              onPressed: _loading ? null : _backToEmail,
              style: TextButton.styleFrom(foregroundColor: _Rose.textMuted),
              child: const Text('← Đổi email'),
            ),
            TextButton(
              onPressed: (_loading || _cooldown > 0) ? null : _sendCode,
              style: TextButton.styleFrom(foregroundColor: _Rose.primary),
              child: Text(
                _cooldown > 0 ? 'Gửi lại mã (${_cooldown}s)' : 'Gửi lại mã',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _messages() {
    return AnimatedSize(
      duration: const Duration(milliseconds: 200),
      alignment: Alignment.topCenter,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_error != null)
            _MessageBox(
              message: _error!,
              icon: Icons.error_outline_rounded,
              color: _Rose.danger,
              bg: _Rose.dangerBg,
            ),
          if (_info != null)
            _MessageBox(
              message: _info!,
              icon: Icons.check_circle_outline_rounded,
              color: _Rose.success,
              bg: _Rose.successBg,
            ),
        ],
      ),
    );
  }

  Widget _primaryButton(String label, VoidCallback onPressed) {
    return SizedBox(
      height: 52,
      child: FilledButton(
        onPressed: _loading ? null : onPressed,
        style: ButtonStyle(
          elevation: const WidgetStatePropertyAll(0.0),
          foregroundColor: const WidgetStatePropertyAll(Colors.white),
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
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
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
            : Text(
                label,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
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

class _MessageBox extends StatelessWidget {
  final String message;
  final IconData icon;
  final Color color;
  final Color bg;
  const _MessageBox({
    required this.message,
    required this.icon,
    required this.color,
    required this.bg,
  });

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 14),
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    decoration: BoxDecoration(
      color: bg,
      borderRadius: BorderRadius.circular(12),
      border: Border.all(color: color.withValues(alpha: 0.25)),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 1),
          child: Icon(icon, size: 18, color: color),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            message,
            style: TextStyle(color: color, fontSize: 13.5, height: 1.35),
          ),
        ),
      ],
    ),
  );
}
