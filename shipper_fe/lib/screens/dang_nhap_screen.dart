// lib/screens/login_screen.dart
// Thay cho màn login thật: chọn tạm mã nhân viên 9 hoặc 10, nhập base URL backend.

import 'package:flutter/material.dart';
import '../config.dart';
import 'trang_chu_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  late final TextEditingController _urlCtrl = TextEditingController(
    text: AppConfig.baseUrl,
  );
  int _selectedMa = AppConfig.maNhanVien;

  void _vaoApp() {
    setState(() {}); // no-op để chắc chắn build lại nếu cần
    AppConfig.baseUrl = _urlCtrl.text.trim();
    AppConfig.maNhanVien = _selectedMa;
    Navigator.of(
      context,
    ).pushReplacement(MaterialPageRoute(builder: (_) => const HomeScreen()));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Shipper Test App')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Chưa có đăng nhập thật, chọn tạm mã nhân viên để test:',
              style: TextStyle(fontSize: 15),
            ),
            const SizedBox(height: 12),
            SegmentedButton<int>(
              segments: const [
                ButtonSegment(value: 9, label: Text('Mã 9')),
                ButtonSegment(value: 10, label: Text('Mã 10')),
              ],
              selected: {_selectedMa},
              onSelectionChanged: (s) => setState(() => _selectedMa = s.first),
            ),
            const SizedBox(height: 24),
            const Text('Base URL backend (đến /api/shipper):'),
            const SizedBox(height: 8),
            TextField(
              controller: _urlCtrl,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                hintText: 'http://10.0.2.2:3000/api/shipper',
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              '• Android emulator: dùng 10.0.2.2 thay cho localhost\n'
              '• Thiết bị thật / iOS simulator: dùng IP LAN máy chạy Node',
              style: TextStyle(fontSize: 12, color: Colors.grey),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _vaoApp,
              child: const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text('Vào ứng dụng'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
