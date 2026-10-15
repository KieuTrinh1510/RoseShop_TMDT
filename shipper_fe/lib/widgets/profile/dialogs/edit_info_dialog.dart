// lib/screens/profile/dialogs/edit_info_dialog.dart
// Dialog "Thông tin cá nhân": mở ra là chế độ XEM, bấm "Sửa" mới cho nhập.
// Trả về null nếu huỷ/đóng, ngược lại trả Map {ten_nhan_vien, so_dien_thoai, email} đã trim.

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../theme/pink_theme.dart';
import '../../../utils/app_strings.dart';
import '../../../widgets/common/staggered_fade_slide.dart';

Future<Map<String, String>?> showEditInfoDialog(
  BuildContext context, {
  required String ten,
  required String sdt,
  required String email,
}) {
  return showDialog<Map<String, String>>(
    context: context,
    builder: (_) => _InfoDialog(ten: ten, sdt: sdt, email: email),
  );
}

class _InfoDialog extends StatefulWidget {
  final String ten, sdt, email;
  const _InfoDialog({
    required this.ten,
    required this.sdt,
    required this.email,
  });

  @override
  State<_InfoDialog> createState() => _InfoDialogState();
}

class _InfoDialogState extends State<_InfoDialog> {
  late final _tenCtrl = TextEditingController(text: widget.ten);
  late final _sdtCtrl = TextEditingController(text: widget.sdt);
  late final _emailCtrl = TextEditingController(text: widget.email);
  final _formKey = GlobalKey<FormState>();
  bool _editing = false;

  static final _nameRe = RegExp(r'^[\p{L} ]+$', unicode: true);
  static final _emailRe = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');

  @override
  void dispose() {
    _tenCtrl.dispose();
    _sdtCtrl.dispose();
    _emailCtrl.dispose();
    super.dispose();
  }

  void _startEdit() => setState(() => _editing = true);

  void _cancelEdit() {
    _tenCtrl.text = widget.ten;
    _sdtCtrl.text = widget.sdt;
    _emailCtrl.text = widget.email;
    setState(() => _editing = false);
  }

  void _submit() {
    if (_formKey.currentState?.validate() != true) return;
    Navigator.pop(context, {
      'ten_nhan_vien': _tenCtrl.text.trim(),
      'so_dien_thoai': _sdtCtrl.text.trim(),
      'email': _emailCtrl.text.trim(),
    });
  }

  String? _vName(String? v) {
    final s = (v ?? '').trim();
    if (s.isEmpty) return tr('err_name_empty');
    if (s.length > 50) return tr('err_name_len');
    if (!_nameRe.hasMatch(s)) return tr('err_name_chars');
    return null;
  }

  String? _vPhone(String? v) {
    final s = (v ?? '').trim();
    if (s.isEmpty) return tr('err_phone_empty');
    if (!RegExp(r'^\d{10}$').hasMatch(s)) return tr('err_phone_len');
    return null;
  }

  String? _vEmail(String? v) {
    final s = (v ?? '').trim();
    if (s.isEmpty) return tr('err_email_empty');
    if (!_emailRe.hasMatch(s)) return tr('err_email_fmt');
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: Pink.dialogBg,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
      title: Text(
        tr('personal_info'),
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
              Icons.person_outline_rounded,
              tr('full_name'),
              widget.ten,
            ),
          ),
          StaggeredFadeSlide(
            index: 1,
            child: _viewRow(Icons.phone_outlined, tr('phone'), widget.sdt),
          ),
          StaggeredFadeSlide(
            index: 2,
            child: _viewRow(
              Icons.mail_outline_rounded,
              tr('email'),
              widget.email,
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
                  value.isEmpty ? '—' : value,
                  style: const TextStyle(
                    color: Pink.plum,
                    fontSize: 14.5,
                    fontWeight: FontWeight.w700,
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
            children: [
              StaggeredFadeSlide(
                index: 0,
                child: _field(
                  _tenCtrl,
                  tr('full_name'),
                  validator: _vName,
                  formatters: [
                    LengthLimitingTextInputFormatter(50),
                    // Chặn ký tự đặc biệt/số ngay khi gõ: chỉ chữ cái + khoảng trắng.
                    FilteringTextInputFormatter.allow(
                      RegExp(r'[\p{L} ]', unicode: true),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              StaggeredFadeSlide(
                index: 1,
                child: _field(
                  _sdtCtrl,
                  tr('phone'),
                  keyboard: TextInputType.number,
                  validator: _vPhone,
                  formatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(10),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              StaggeredFadeSlide(
                index: 2,
                child: _field(
                  _emailCtrl,
                  tr('email'),
                  keyboard: TextInputType.emailAddress,
                  validator: _vEmail,
                  formatters: [FilteringTextInputFormatter.deny(RegExp(r'\s'))],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label, {
    TextInputType? keyboard,
    String? Function(String?)? validator,
    List<TextInputFormatter>? formatters,
  }) {
    return TextFormField(
      controller: ctrl,
      keyboardType: keyboard,
      inputFormatters: formatters,
      validator: validator,
      style: const TextStyle(color: Pink.plum, fontSize: 14),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(color: Pink.plum.withOpacity(0.6)),
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
