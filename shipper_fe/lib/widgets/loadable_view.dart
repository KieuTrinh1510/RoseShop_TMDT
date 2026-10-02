// lib/widgets/loadable_view.dart
// Quản lý 3 trạng thái: LOADING (skeleton) -> LOADED (dữ liệu thật) / ERROR (retry).
// Pull-to-refresh: giữ nguyên dữ liệu cũ, chỉ hiện vòng refresh nhỏ, không nháy skeleton.
import 'package:flutter/material.dart';
import 'common.dart';

enum LoadStatus { loading, loaded, error }

class LoadableView<T> extends StatefulWidget {
  /// Hàm lấy dữ liệu (API). Ném exception nếu lỗi.
  final Future<T> Function() loader;

  /// Skeleton hiển thị khi đang loading.
  final Widget skeleton;

  /// Dựng UI thật từ dữ liệu. Widget con nên bọc item bằng StaggeredFadeSlide.
  final Widget Function(BuildContext context, T data) builder;

  /// Thời gian skeleton tối thiểu, tránh skeleton nháy lên rồi biến mất ngay.
  final Duration minSkeletonTime;

  const LoadableView({
    super.key,
    required this.loader,
    required this.skeleton,
    required this.builder,
    this.minSkeletonTime = const Duration(milliseconds: 350),
  });

  @override
  State<LoadableView<T>> createState() => _LoadableViewState<T>();
}

class _LoadableViewState<T> extends State<LoadableView<T>> {
  LoadStatus _status = LoadStatus.loading;
  T? _data;
  String? _error;
  int _token = 0; // bỏ qua kết quả của lần load cũ nếu có lần load mới

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool refresh = false}) async {
    final token = ++_token;

    // Lần load đầu status đã là loading -> không cần setState trong initState.
    if (!refresh && _status != LoadStatus.loading) {
      setState(() {
        _status = LoadStatus.loading;
        _error = null;
      });
    }

    try {
      final minTime = Future<void>.delayed(
        refresh ? Duration.zero : widget.minSkeletonTime,
      );
      final data = await widget.loader();
      await minTime;
      if (!mounted || token != _token) return;
      setState(() {
        _data = data;
        _status = LoadStatus.loaded;
      });
    } catch (e) {
      if (!mounted || token != _token) return;
      final msg = 'Không tải được dữ liệu: $e';
      if (refresh && _status == LoadStatus.loaded) {
        // Đang có dữ liệu cũ -> giữ nguyên, chỉ báo lỗi nhỏ.
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(msg)));
      } else {
        setState(() {
          _error = msg;
          _status = LoadStatus.error;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    switch (_status) {
      case LoadStatus.loading:
        return widget.skeleton;
      case LoadStatus.error:
        return SafeArea(
          bottom: false,
          child: ErrorBox(message: _error ?? 'Đã có lỗi', onRetry: _load),
        );
      case LoadStatus.loaded:
        return RefreshIndicator(
          edgeOffset: MediaQuery.of(context).padding.top,
          onRefresh: () => _load(refresh: true),
          child: widget.builder(context, _data as T),
        );
    }
  }
}
