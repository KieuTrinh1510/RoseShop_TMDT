// lib/screens/profile/dialogs/change_password_dialog.dart
// Dialog đổi mật khẩu. Trả null nếu huỷ, ngược lại trả Map {cu, moi}.

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../theme/pink_theme.dart';

Future<Map<String, String>?> showChangePasswordDialog(BuildContext context) {
  final cuCtrl = TextEditingController();
  final moiCtrl = TextEditingController();
  final formKey = GlobalKey<FormState>();
  bool hide1 = true;
  bool hide2 = true;

  return showDialog<Map<String, String>>(
    context: context,
    builder: (ctx) => StatefulBuilder(
      builder: (ctx, setLocal) => AlertDialog(
        backgroundColor: Pink.dialogBg,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
        title: const Text(
          'Đổi mật khẩu',
          style: TextStyle(
            color: Pink.plum,
            fontWeight: FontWeight.w800,
            fontSize: 18,
          ),
        ),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: cuCtrl,
                obscureText: hide1,
                style: const TextStyle(color: Pink.plum, fontSize: 14),
                validator: (v) =>
                    (v == null || v.isEmpty) ? 'Nhập mật khẩu cũ' : null,
                decoration: _deco(
                  'Mật khẩu cũ',
                  suffix: IconButton(
                    icon: Icon(
                      hide1
                          ? Icons.visibility_off_rounded
                          : Icons.visibility_rounded,
                      size: 18,
                      color: Pink.muted,
                    ),
                    onPressed: () => setLocal(() => hide1 = !hide1),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              TextFormField(
                controller: moiCtrl,
                obscureText: hide2,
                maxLength: 100, // THÊM
                inputFormatters: [
                  LengthLimitingTextInputFormatter(100),
                ], // THÊM
                style: const TextStyle(color: Pink.plum, fontSize: 14),
                validator: (v) {
                  final s = v ?? '';
                  if (s.isEmpty) return 'Nhập mật khẩu mới';
                  if (s.length < 6) return 'Mật khẩu mới từ 6 ký tự';
                  if (s.length > 100) return 'Mật khẩu mới quá dài';
                  if (s == cuCtrl.text) return 'Mật khẩu mới trùng mật khẩu cũ';
                  return null;
                },
                decoration: _deco(
                  'Mật khẩu mới',
                  suffix: IconButton(
                    icon: Icon(
                      hide2
                          ? Icons.visibility_off_rounded
                          : Icons.visibility_rounded,
                      size: 18,
                      color: Pink.muted,
                    ),
                    onPressed: () => setLocal(() => hide2 = !hide2),
                  ),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Huỷ', style: TextStyle(color: Pink.muted)),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Pink.rose),
            onPressed: () {
              if (formKey.currentState?.validate() != true) return;
              Navigator.pop(ctx, {
                'mat_khau_cu': cuCtrl.text,
                'mat_khau_moi': moiCtrl.text,
              });
            },
            child: const Text('Đổi'),
          ),
        ],
      ),
    ),
  );
}

InputDecoration _deco(String label, {Widget? suffix}) {
  return InputDecoration(
    labelText: label,
    labelStyle: TextStyle(color: Pink.plum.withOpacity(0.6)),
    suffixIcon: suffix,
    filled: true,
    fillColor: Colors.white,
    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
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
  );
}
