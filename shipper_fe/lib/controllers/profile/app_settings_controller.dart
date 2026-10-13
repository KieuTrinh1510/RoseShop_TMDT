// lib/controllers/profile/app_settings_controller.dart
// Cài đặt toàn app: ngôn ngữ + giao diện. Chỉ giữ trong bộ nhớ, KHÔNG gọi backend.
// TODO: muốn nhớ sau khi tắt app thì thêm shared_preferences vào setLanguage/setTheme.

import 'package:flutter/material.dart';

class AppSettings extends ChangeNotifier {
  AppSettings._();
  static final AppSettings instance = AppSettings._();

  String _language = 'vi'; // 'vi' | 'en'
  String _theme = 'light'; // 'light' | 'dark' | 'system'

  String get language => _language;
  String get theme => _theme;

  ThemeMode get themeMode => switch (_theme) {
    'dark' => ThemeMode.dark,
    'system' => ThemeMode.system,
    _ => ThemeMode.light,
  };

  void setLanguage(String v) {
    if (v == _language) return;
    _language = v;
    notifyListeners();
  }

  void setTheme(String v) {
    if (v == _theme) return;
    _theme = v;
    notifyListeners();
  }
}
