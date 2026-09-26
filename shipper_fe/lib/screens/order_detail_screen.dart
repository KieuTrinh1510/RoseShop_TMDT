// lib/screens/order_detail_screen.dart
// Test:
//   GET  /orders/:id
//   POST /orders/:id/pickup
//   POST /orders/:id/delivered
//   GET  /orders/:id/timeout

import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../widgets/pretty_json.dart';
import '../widgets/common.dart';

class OrderDetailScreen extends StatefulWidget {
  final String maHoaDon;
  const OrderDetailScreen({super.key, required this.maHoaDon});

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  dynamic _data;
  bool _loading = true;
  bool _actionLoading = false;
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
      final data = await ApiService.get('/orders/${widget.maHoaDon}');
      if (!mounted) return;
      setState(() => _data = data);
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

  Future<void> _runAction(
    Future<dynamic> Function() action,
    String successMsg,
  ) async {
    setState(() => _actionLoading = true);
    try {
      final data = await action();
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(successMsg)));
        _showResultDialog(data);
      }
      _load();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Lỗi kết nối: $e')));
      }
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  void _showResultDialog(dynamic data) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Kết quả'),
        content: SingleChildScrollView(child: PrettyJson(data: data)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Đóng'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Đơn #${widget.maHoaDon}')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? ErrorBox(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  PrettyJson(data: _data),
                  const SizedBox(height: 20),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      FilledButton.icon(
                        onPressed: _actionLoading
                            ? null
                            : () => _runAction(
                                () => ApiService.post(
                                  '/orders/${widget.maHoaDon}/pickup',
                                ),
                                'Đã xác nhận lấy hàng',
                              ),
                        icon: const Icon(Icons.local_shipping),
                        label: const Text('Xác nhận lấy hàng'),
                      ),
                      FilledButton.icon(
                        onPressed: _actionLoading
                            ? null
                            : () => _runAction(
                                () => ApiService.post(
                                  '/orders/${widget.maHoaDon}/delivered',
                                ),
                                'Đã đánh dấu giao thành công',
                              ),
                        icon: const Icon(Icons.check_circle),
                        label: const Text('Đã giao hàng'),
                      ),
                      OutlinedButton.icon(
                        onPressed: _actionLoading
                            ? null
                            : () => _runAction(
                                () => ApiService.get(
                                  '/orders/${widget.maHoaDon}/timeout',
                                ),
                                'Đã lấy thời gian còn lại',
                              ),
                        icon: const Icon(Icons.timer_outlined),
                        label: const Text('Xem thời gian còn lại'),
                      ),
                    ],
                  ),
                  if (_actionLoading)
                    const Padding(
                      padding: EdgeInsets.only(top: 16),
                      child: Center(child: CircularProgressIndicator()),
                    ),
                ],
              ),
            ),
    );
  }
}
