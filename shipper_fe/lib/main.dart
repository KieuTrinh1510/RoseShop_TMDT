// lib/main.dart
import 'package:flutter/material.dart';
import 'controllers/profile/app_settings_controller.dart';
import 'screens/dang_nhap_screen.dart';

void main() {
  runApp(const ShipperTestApp());
}

// Ma trận "đảo sáng/tối nhưng giữ nguyên tông màu" (invert + hue-rotate 180°).
// Nhờ vậy mọi màn dùng màu Pink.* cố định đều tự có giao diện tối.
const List<double> _identity = [
  1, 0, 0, 0, 0, //
  0, 1, 0, 0, 0, //
  0, 0, 1, 0, 0, //
  0, 0, 0, 1, 0,
];
const List<double> _darkMatrix = [
  0.574, -1.430, -0.144, 0, 255, //
  -0.426, -0.430, -0.144, 0, 255, //
  -0.426, -1.430, 0.856, 0, 255, //
  0, 0, 0, 1, 0,
];

List<double> _lerpMatrix(double t) => [
  for (int i = 0; i < 20; i++)
    _identity[i] + (_darkMatrix[i] - _identity[i]) * t,
];

class ShipperTestApp extends StatelessWidget {
  const ShipperTestApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: AppSettings.instance,
      builder: (context, _) {
        final mode = AppSettings.instance.themeMode;
        return MaterialApp(
          title: 'Shipper Test App',
          debugShowCheckedModeBanner: false,
          theme: ThemeData(
            useMaterial3: true,
            colorSchemeSeed: const Color(0xFF2563EB),
          ),
          builder: (context, child) {
            final dark = switch (mode) {
              ThemeMode.dark => true,
              ThemeMode.light => false,
              ThemeMode.system =>
                MediaQuery.platformBrightnessOf(context) == Brightness.dark,
            };
            // Chuyển sáng <-> tối mượt trong 350ms.
            return TweenAnimationBuilder<double>(
              tween: Tween<double>(end: dark ? 1.0 : 0.0),
              duration: const Duration(milliseconds: 350),
              curve: Curves.easeInOut,
              child: child,
              builder: (context, t, child) => ColorFiltered(
                colorFilter: ColorFilter.matrix(_lerpMatrix(t)),
                child: child,
              ),
            );
          },
          home: const LoginScreen(),
        );
      },
    );
  }
}
