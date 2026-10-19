// lib/screens/profile/dialogs/change_username_dialog.dart
// Dialog "Tài khoản" (gộp đổi tên đăng nhập + đổi mật khẩu).
// Mở ra là chế độ XEM (tên đăng nhập + mật khẩu ••••••), bấm "Sửa" mới cho nhập.
// Trả null nếu huỷ/đóng, ngược lại trả Map chỉ chứa phần thực sự đổi
// (hoặc {'quen_mat_khau': '1'} khi bấm "Quên mật khẩu?"):
//   'ten_dang_nhap_moi'            (nếu đổi tên)
//   'mat_khau_cu' + 'mat_khau_moi' (nếu đổi mật khẩu)

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../controllers/profile/app_settings_controller.dart';
import '../../../theme/pink_theme.dart';
import '../../../utils/app_strings.dart';
import '../../../widgets/common/staggered_fade_slide.dart';

Future<Map<String, String>?> showAccountDialog(
  BuildContext context, {
  required String current,
}) {
  return showDialog<Map<String, String>>(
    context: context,
    builder: (_) => _AccountDialog(current: current),
  );
}

class _AccountDialog extends StatefulWidget {
  final String current;
  const _AccountDialog({required this.current});

  @override
  State<_AccountDialog> createState() => _AccountDialogState();
}

class _AccountDialogState extends State<_AccountDialog> {
  late final _userCtrl = TextEditingController(text: widget.current);
  final _oldCtrl = TextEditingController();
  final _newCtrl = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  bool _editing = false;
  bool _hideOld = true;
  bool _hideNew = true;
  String? _formError; // lỗi chung: "chưa thay đổi gì"

  static final _userRe = RegExp(r'^[a-zA-Z0-9_.]{3,30}$');

  @override
  void dispose() {
    _userCtrl.dispose();
    _oldCtrl.dispose();
    _newCtrl.dispose();
    super.dispose();
  }

  void _startEdit() => setState(() => _editing = true);

  void _cancelEdit() {
    _userCtrl.text = widget.current;
    _oldCtrl.clear();
    _newCtrl.clear();
    setState(() {
      _editing = false;
      _formError = null;
      _hideOld = true;
      _hideNew = true;
    });
  }

  void _submit() {
    if (_formKey.currentState?.validate() != true) return;

    final user = _userCtrl.text.trim();
    final userChanged = user != widget.current;
    final pwChanged = _newCtrl.text.isNotEmpty;

    if (!userChanged && !pwChanged) {
      setState(() => _formError = tr('err_nothing_changed'));
      return;
    }

    final res = <String, String>{};
    if (userChanged) res['ten_dang_nhap_moi'] = user;
    if (pwChanged) {
      res['mat_khau_cu'] = _oldCtrl.text;
      res['mat_khau_moi'] = _newCtrl.text;
    }
    Navigator.pop(context, res);
  }

  String? _vUser(String? v) {
    final s = (v ?? '').trim();
    if (s.isEmpty) return tr('err_user_empty');
    if (!_userRe.hasMatch(s)) return tr('err_user_fmt');
    return null;
  }

  String? _vOld(String? v) {
    // Chỉ bắt buộc khi người dùng định đổi mật khẩu.
    if (_newCtrl.text.isNotEmpty && (v == null || v.isEmpty)) {
      return tr('err_old_pw_empty');
    }
    return null;
  }

  String? _vNew(String? v) {
    final s = v ?? '';
    if (s.isEmpty) return null; // để trống = không đổi mật khẩu
    if (s.length < 6) return tr('err_new_pw_len');
    if (s.length > 100) return tr('err_new_pw_long');
    if (s == _oldCtrl.text) return tr('err_new_pw_same');
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: Pink.dialogBg,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
      title: Text(
        tr('account'),
        style: const TextStyle(
          color: Pink.plum,
          fontWeight: FontWeight.w800,
          fontSize: 18,
        ),
      ),
      content: AnimatedSize(
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOutCubic,
        child: AnimatedSwitcher(
          duration: const Duration(milliseconds: 200),
          child: _editing
              ? KeyedSubtree(key: const ValueKey('edit'), child: _buildForm())
              : KeyedSubtree(key: const ValueKey('view'), child: _buildView()),
        ),
      ),
      actions: _editing
          ? [
              TextButton(
                onPressed: _cancelEdit,
                child: Text(
                  tr('cancel'),
                  style: const TextStyle(color: Pink.muted),
                ),
              ),
              FilledButton(
                style: FilledButton.styleFrom(backgroundColor: Pink.rose),
                onPressed: _submit,
                child: Text(tr('save')),
              ),
            ]
          : [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: Text(
                  tr('close'),
                  style: const TextStyle(color: Pink.muted),
                ),
              ),
              FilledButton.icon(
                style: FilledButton.styleFrom(backgroundColor: Pink.rose),
                onPressed: _startEdit,
                icon: const Icon(Icons.edit_rounded, size: 16),
                label: Text(tr('edit')),
              ),
            ],
    );
  }

  // ── Chế độ xem ──────────────────────────────────────────────
  Widget _buildView() {
    return SizedBox(
      width: double.maxFinite,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          StaggeredFadeSlide(
            index: 0,
            child: _viewRow(
              Icons.badge_outlined,
              tr('username'),
              widget.current.isEmpty ? '—' : widget.current,
            ),
          ),
          StaggeredFadeSlide(
            index: 1,
            // Backend không (và không nên) trả mật khẩu thật -> hiện cố định.
            child: _viewRow(
              Icons.lock_outline_rounded,
              tr('password'),
              '••••••••',
            ),
          ),
        ],
      ),
    );
  }

  Widget _viewRow(IconData icon, String label, String value) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Pink.plum.withOpacity(0.12)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 18, color: Pink.rose),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(color: Pink.muted, fontSize: 12),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    color: Pink.plum,
                    fontSize: 14.5,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 1,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ── Chế độ sửa ──────────────────────────────────────────────
  Widget _buildForm() {
    return SizedBox(
      width: double.maxFinite,
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              StaggeredFadeSlide(
                index: 0,
                child: _field(
                  _userCtrl,
                  tr('new_username'),
                  validator: _vUser,
                  formatters: [
                    LengthLimitingTextInputFormatter(30),
                    FilteringTextInputFormatter.allow(RegExp(r'[a-zA-Z0-9_.]')),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              StaggeredFadeSlide(
                index: 1,
                child: Text(
                  tr('password_hint'),
                  style: const TextStyle(color: Pink.muted, fontSize: 12.5),
                ),
              ),
              const SizedBox(height: 8),
              StaggeredFadeSlide(
                index: 2,
                child: _field(
                  _oldCtrl,
                  tr('old_password'),
                  obscure: _hideOld,
                  validator: _vOld,
                  onToggle: () => setState(() => _hideOld = !_hideOld),
                ),
              ),
              const SizedBox(height: 10),
              StaggeredFadeSlide(
                index: 3,
                child: _field(
                  _newCtrl,
                  tr('new_password'),
                  obscure: _hideNew,
                  validator: _vNew,
                  onToggle: () => setState(() => _hideNew = !_hideNew),
                  formatters: [LengthLimitingTextInputFormatter(100)],
                ),
              ),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () =>
                      Navigator.pop(context, {'quen_mat_khau': '1'}),
                  child: Text(
                    AppSettings.instance.language == 'vi'
                        ? 'Quên mật khẩu?'
                        : 'Forgot password?',
                    style: const TextStyle(
                      color: Pink.rose,
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ),
              if (_formError != null) ...[
                const SizedBox(height: 10),
                Text(
                  _formError!,
                  style: const TextStyle(color: Pink.danger, fontSize: 12.5),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label, {
    bool? obscure,
    VoidCallback? onToggle,
    String? Function(String?)? validator,
    List<TextInputFormatter>? formatters,
  }) {
    return TextFormField(
      controller: ctrl,
      obscureText: obscure ?? false,
      inputFormatters: formatters,
      validator: validator,
      onChanged: (_) {
        if (_formError != null) setState(() => _formError = null);
      },
      style: const TextStyle(color: Pink.plum, fontSize: 14),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(color: Pink.plum.withOpacity(0.6)),
        suffixIcon: onToggle == null
            ? null
            : IconButton(
                icon: Icon(
                  obscure == true
                      ? Icons.visibility_off_rounded
                      : Icons.visibility_rounded,
                  size: 18,
                  color: Pink.muted,
                ),
                onPressed: onToggle,
              ),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 10,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Pink.plum.withOpacity(0.15)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Pink.plum.withOpacity(0.15)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Pink.rose, width: 1.5),
        ),
      ),
    );
  }
}
