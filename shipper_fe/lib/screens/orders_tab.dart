// lib/screens/orders_tab.dart
// Test GET /orders?trang_thai=&page=&limit=

import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../widgets/pretty_json.dart';
import '../widgets/common.dart';
import 'order_detail_screen.dart';

const _trangThaiOptions = ['(Tất cả)', 'CONFIRMED', 'SHIPPING'];

class OrdersTab extends StatefulWidget {
  const OrdersTab({super.key});

  @override
  State<OrdersTab> createState() => _OrdersTabState();
}

class _OrdersTabState extends State<OrdersTab> {
  dynamic _data;
  bool _loading = true;
  String? _error;
  String _trangThai = _trangThaiOptions.first;
  int _page = 1;
  final int _limit = 20;

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
      final data = await ApiService.get(
        '/orders',
        query: {
          if (_trangThai != _trangThaiOptions.first) 'trang_thai': _trangThai,
          'page': _page,
          'limit': _limit,
        },
      );
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

  // Cố gắng tìm danh sách item trong response dù shape chính xác chưa rõ:
  // hỗ trợ cả trường hợp data là List thẳng, hoặc { items: [...] }, { danh_sach: [...] }
  List<Map<String, dynamic>>? _extractList() {
    if (_data is List) {
      return (_data as List).cast<Map<String, dynamic>>();
    }
    if (_data is Map) {
      for (final key in ['items', 'danh_sach', 'orders', 'don_hang', 'data']) {
        final v = (_data as Map)[key];
        if (v is List) return v.cast<Map<String, dynamic>>();
      }
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
          child: Row(
            children: [
              const Text('Trạng thái: '),
              const SizedBox(width: 8),
              DropdownButton<String>(
                value: _trangThai,
                items: _trangThaiOptions
                    .map((e) => DropdownMenuItem(value: e, child: Text(e)))
                    .toList(),
                onChanged: (v) {
                  setState(() {
                    _trangThai = v!;
                    _page = 1;
                  });
                  _load();
                },
              ),
              const Spacer(),
              IconButton(
                tooltip: 'Trang trước',
                icon: const Icon(Icons.chevron_left),
                onPressed: _page > 1
                    ? () {
                        setState(() => _page--);
                        _load();
                      }
                    : null,
              ),
              Text('Trang $_page'),
              IconButton(
                tooltip: 'Trang sau',
                icon: const Icon(Icons.chevron_right),
                onPressed: () {
                  setState(() => _page++);
                  _load();
                },
              ),
            ],
          ),
        ),
        const Divider(height: 1),
        Expanded(child: _buildBody()),
      ],
    );
  }

  Widget _buildBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return ErrorBox(message: _error!, onRetry: _load);

    final list = _extractList();
    if (list == null) {
      // Không đoán được shape -> hiện JSON thô để bạn tự đối chiếu
      return RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [PrettyJson(data: _data)],
        ),
      );
    }

    if (list.isEmpty) {
      return const Center(child: Text('Không có đơn nào.'));
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: list.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, i) {
          final item = list[i];
          final id = guessId(item);
          return Card(
            child: ListTile(
              title: Text(guessTitle(item, i)),
              subtitle: Text(guessSubtitle(item) ?? 'Chạm để xem chi tiết'),
              trailing: const Icon(Icons.chevron_right),
              onTap: id == null
                  ? null
                  : () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) =>
                            OrderDetailScreen(maHoaDon: id.toString()),
                      ),
                    ),
            ),
          );
        },
      ),
    );
  }
}
