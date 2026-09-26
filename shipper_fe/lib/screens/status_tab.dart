// lib/screens/status_tab.dart
// Test GET /status và PUT /status/online

import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../config.dart';

class StatusTab extends StatefulWidget {
  const StatusTab({super.key});

  @override
  State<StatusTab> createState() => _StatusTabState();
}

class _StatusTabState extends State<StatusTab> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  bool _toggling = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await ApiService.get('/status');
      if (!mounted) return;
      setState(() => _data = Map<String, dynamic>.from(data));
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.toString());
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'Lỗi kết nối: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _toggle(bool online) async {
    setState(() => _toggling = true);
    try {
      final data = await ApiService.put(
        '/status/online',
        body: {'trang_thai': online ? 'ONLINE' : 'OFFLINE'},
      );
      if (!mounted) return;
      setState(() => _data = Map<String, dynamic>.from(data));
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _toggling = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return _ErrorBox(message: _error!, onRetry: _load);
    }

    final d = _data!;
    final isOnline = d['trang_thai_hoat_dong'] == 'ONLINE';
    final soDon = d['so_don_hien_tai'];
    final gioiHan = d['gioi_han_don'];
    final isFull = d['is_full'] == true;
    final coTheNhan = d['co_the_nhan_don'] == true;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            'Đang test với mã nhân viên: ${AppConfig.maNhanVien}',
            style: const TextStyle(color: Colors.grey, fontSize: 12),
          ),
          const SizedBox(height: 8),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    d['ten_nhan_vien']?.toString() ?? '(chưa có tên)',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text('Mã nhân viên: ${d['ma_nhan_vien']}'),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        isOnline ? 'Đang ONLINE' : 'Đang OFFLINE',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: isOnline ? Colors.green : Colors.grey,
                        ),
                      ),
                      _toggling
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Switch(value: isOnline, onChanged: _toggle),
                    ],
                  ),
                  const Divider(height: 28),
                  _row('Số đơn hiện tại', '$soDon / $gioiHan'),
                  _row('Đã đầy đơn?', isFull ? 'Có' : 'Chưa'),
                  _row('Có thể nhận đơn mới?', coTheNhan ? 'Có' : 'Không'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 8),
          TextButton.icon(
            onPressed: _load,
            icon: const Icon(Icons.refresh),
            label: const Text('Tải lại'),
          ),
        ],
      ),
    );
  }

  Widget _row(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    ),
  );
}

class _ErrorBox extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorBox({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, color: Colors.red, size: 40),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(onPressed: onRetry, child: const Text('Thử lại')),
          ],
        ),
      ),
    );
  }
}
