// lib/screens/history_tab.dart
// Test GET /history?page=&limit=

import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../widgets/pretty_json.dart';
import '../widgets/common.dart';
import 'order_detail_screen.dart';

class HistoryTab extends StatefulWidget {
  const HistoryTab({super.key});

  @override
  State<HistoryTab> createState() => _HistoryTabState();
}

class _HistoryTabState extends State<HistoryTab> {
  dynamic _data;
  bool _loading = true;
  String? _error;
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
        '/history',
        query: {'page': _page, 'limit': _limit},
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

  List<Map<String, dynamic>>? _extractList() {
    if (_data is List) return (_data as List).cast<Map<String, dynamic>>();
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
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return ErrorBox(message: _error!, onRetry: _load);

    final list = _extractList();

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Trang $_page'),
              Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.chevron_left),
                    onPressed: _page > 1
                        ? () {
                            setState(() => _page--);
                            _load();
                          }
                        : null,
                  ),
                  IconButton(
                    icon: const Icon(Icons.chevron_right),
                    onPressed: () {
                      setState(() => _page++);
                      _load();
                    },
                  ),
                ],
              ),
            ],
          ),
        ),
        const Divider(height: 1),
        Expanded(
          child: list == null
              ? RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [PrettyJson(data: _data)],
                  ),
                )
              : list.isEmpty
              ? const Center(child: Text('Chưa có đơn nào đã giao.'))
              : RefreshIndicator(
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
                          subtitle: Text(guessSubtitle(item) ?? ''),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: id == null
                              ? null
                              : () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => OrderDetailScreen(
                                      maHoaDon: id.toString(),
                                    ),
                                  ),
                                ),
                        ),
                      );
                    },
                  ),
                ),
        ),
      ],
    );
  }
}
