// lib/main.dart
import 'package:flutter/material.dart';
import 'screens/dang_nhap_screen.dart';

void main() {
  runApp(const ShipperTestApp());
}

class ShipperTestApp extends StatelessWidget {
  const ShipperTestApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Shipper Test App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: const Color(0xFF2563EB),
      ),
      home: const LoginScreen(),
    );
  }
}
