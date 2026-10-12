// lib/screens/order/order_detail_screen.dart
// Chi tiết đơn + luồng giao hàng.
//   GET  /orders/:id            -> dữ liệu màn hình (tiến trình, nút nào bấm được, đồng hồ...)
//   POST /orders/:id/pickup     -> Nhận hàng & bắt đầu giao   (CONFIRMED -> SHIPPING)
//   POST /orders/:id/arrived    -> Đã đến điểm giao, báo khách
//   POST /orders/:id/delivered  -> Hoàn thành đơn hàng        (SHIPPING -> DELIVERED)
//   POST /orders/:id/overdue    -> Xử lý quá 3h hoa tươi (UC4.2.10), backend đảm bảo chỉ phạt 1 lần
//
// Màn hình KHÔNG tự suy luận trạng thái: nút nào hiện = backend (hanh_dong) cho phép.

import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../services/api_service.dart';
import '../../theme/pink_theme.dart';
import '../../utils/app_strings.dart';
import '../../utils/format.dart';
import '../../widgets/common/glass_container.dart';
import '../../widgets/common/staggered_fade_slide.dart';
import '../../widgets/orders/order_action_area.dart';
import '../../widgets/orders/order_detail_skeleton.dart';
import '../../widgets/orders/order_detail_cards.dart';

class OrderDetailScreen extends StatefulWidget {
  final String maHoaDon;
  const OrderDetailScreen({super.key, required this.maHoaDon});

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  Map<String, dynamic>? _d;
  bool _loading = true; // chỉ true ở lần tải đầu -> skeleton
  bool _busy = false; // đang gọi API của 1 nút
  String? _error;

  // ── Đồng hồ 3h (chỉ chạy khi backend báo co_dong_ho) ──
  Timer? _ticker;
  final Stopwatch _sw = Stopwatch();
  int _remainBase = 0;
  final ValueNotifier<int> _remaining = ValueNotifier<int>(0);
  bool _overdueBusy = false;
  int _overdueAttempts = 0; // chặn vòng lặp nếu API lỗi liên tục

  Map<String, dynamic> get _dongHo => jMap(_d?['dong_ho']);
  Map<String, dynamic> get _hanhDong => jMap(_d?['hanh_dong']);

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _ticker?.cancel();
    _remaining.dispose();
    super.dispose();
  }

  // ───────────────────────── Tải dữ liệu ─────────────────────────
  /// silent = true: giữ nguyên màn hình, chỉ cập nhật dữ liệu (không skeleton).
  Future<void> _load({bool silent = false}) async {
    if (!mounted) return;
    if (!silent) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final res = await ApiService.get('/orders/${widget.maHoaDon}');
      if (!mounted) return;
      setState(() {
        _d = jMap(res);
        _error = null;
        _loading = false;
      });
      _setupClock();
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        if (_d == null) _error = e.message;
      });
      if (_d != null) _snack(e.message);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        if (_d == null) _error = tr('conn_err', {'e': '$e'});
      });
      if (_d != null) _snack(tr('conn_err', {'e': '$e'}));
    }
  }

  // ───────────────────────── Đồng hồ ─────────────────────────
  void _setupClock() {
    _ticker?.cancel();
    _ticker = null;
    _sw
      ..stop()
      ..reset();

    final dh = _dongHo;
    if (!jBool(dh['co_dong_ho'])) {
      _remaining.value = 0;
      return;
    }

    // Backend nói đã quá 3h
    if (jBool(dh['is_timeout'])) {
      _remaining.value = 0;
      if (!jBool(dh['da_phat_tre']))
        _handleOverdue(); // A5: mở lại màn hình khi đã quá hạn
      return;
    }

    // Đếm ngược từ số giây còn lại do SERVER tính, không đếm lại từ 3h
    _remainBase = jInt(dh['remaining']);
    _remaining.value = _remainBase;
    _sw.start();
    _ticker = Timer.periodic(const Duration(seconds: 1), (_) => _tick());
  }

  void _tick() {
    final left = math.max(0, _remainBase - _sw.elapsed.inSeconds);
    _remaining.value = left;
    if (left <= 0) {
      _ticker?.cancel();
      _ticker = null;
      _handleOverdue();
    }
  }

  /// Đồng hồ về 0 (hoặc mở lại mà đã quá hạn): nhờ backend xử lý phạt, rồi tải lại màn hình.
  Future<void> _handleOverdue() async {
    if (_overdueBusy || _overdueAttempts >= 3) return;
    _overdueBusy = true;
    _overdueAttempts++;
    try {
      final res = jMap(
        await ApiService.post('/orders/${widget.maHoaDon}/overdue'),
      );
      if (mounted && jBool(res['da_xu_ly'])) {
        _snack(
          tr('od_overdue_snack', {'amt': fmtMoney(jNum(res['tien_phat']))}),
          long: true,
        );
      }
    } catch (_) {
      // Lỗi mạng: bỏ qua, lần mở màn hình sau sẽ thử lại (E1)
    } finally {
      _overdueBusy = false;
    }
    if (mounted) await _load(silent: true);
  }

  // ───────────────────────── Hành động ─────────────────────────
  Future<void> _runAction({
    required String path,
    required String title,
    required String message,
    required String okMsg,
  }) async {
    if (_busy) return;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Pink.dialogBg,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
        title: Text(
          title,
          style: const TextStyle(
            color: Pink.plum,
            fontWeight: FontWeight.w800,
            fontSize: 18,
          ),
        ),
        content: Text(message, style: const TextStyle(color: Pink.plum)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              tr('cancel'),
              style: const TextStyle(color: Pink.muted),
            ),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Pink.rose),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(tr('confirm')),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() => _busy = true);
    String msg;
    bool reload = true;
    try {
      await ApiService.post(path);
      msg = okMsg;
    } on ApiException catch (e) {
      msg = e.message; // vd ALREADY_NOTIFIED: tải lại để đồng bộ trạng thái
    } catch (e) {
      msg = tr('conn_err', {'e': '$e'});
      reload = false;
    }
    if (!mounted) return;
    setState(() => _busy = false);
    _snack(msg);
    if (reload) await _load(silent: true);
  }

  void _snack(String msg, {bool long = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(msg),
          behavior: SnackBarBehavior.floating,
          backgroundColor: Pink.plum,
          duration: Duration(seconds: long ? 6 : 3),
        ),
      );
  }

  // ───────────────────────── Giao diện ─────────────────────────
  @override
  Widget build(BuildContext context) {
    final title = jStr(
      _d?['ma_hien_thi'],
      tr('od_fallback_title', {'id': widget.maHoaDon}),
    );

    return Scaffold(
      backgroundColor: const Color(0xFFFAC3D6),
      body: Stack(
        children: [
          const PinkBackground(),
          SafeArea(
            bottom: false,
            child: Column(
              children: [
                _TopBar(
                  title: _loading && _d == null ? tr('od_title') : title,
                  onBack: () => Navigator.maybePop(context),
                ),
                Expanded(child: _buildBody(context)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBody(BuildContext context) {
    if (_loading && _d == null) return const OrderDetailSkeleton();
    if (_error != null && _d == null) {
      return PinkErrorBox(message: _error!, onRetry: () => _load());
    }
    final d = _d!;
    final sections = _sections(d);
    final bottom = 32 + MediaQuery.of(context).padding.bottom;

    return RefreshIndicator(
      color: Pink.rose,
      onRefresh: () => _load(silent: true),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(20, 8, 20, bottom),
        children: [
          for (int i = 0; i < sections.length; i++)
            // Key theo tên khối: tải lại không làm các khối cũ fade-in lại
            StaggeredFadeSlide(
              key: ValueKey(sections[i].key),
              index: i,
              child: Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: sections[i].value,
              ),
            ),
        ],
      ),
    );
  }

  List<MapEntry<String, Widget>> _sections(Map<String, dynamic> d) {
    final dh = _dongHo;
    final coDongHo = jBool(dh['co_dong_ho']);
    final quaGio = coDongHo && jBool(dh['is_timeout']);

    return <MapEntry<String, Widget>>[
      MapEntry('summary', OrderSummaryCard(d: d)),
      if (quaGio)
        const MapEntry('overdue', OrderOverdueCard())
      else if (coDongHo)
        MapEntry('clock', OrderCountdownCard(remaining: _remaining)),
      MapEntry(
        'progress',
        OrderProgressCard(
          steps: _stepsFrom(d),
          action: OrderActionArea(
            maHoaDon: widget.maHoaDon,
            d: d,
            hanhDong: _hanhDong,
            busy: _busy,
            onRunAction: _runAction,
          ),
        ),
      ),
      MapEntry(
        'customer',
        OrderCustomerCard(
          d: d,
          onCopyPhone: (phone) async {
            await Clipboard.setData(ClipboardData(text: phone));
            _snack(tr('od_phone_copied'));
          },
        ),
      ),
      MapEntry('products', OrderProductsCard(d: d)),
      MapEntry('payment', OrderPaymentCard(d: d)),
    ];
  }

  List<Map<String, dynamic>> _stepsFrom(Map<String, dynamic> d) {
    final raw = jList(
      d['tien_trinh'],
    ).whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
    if (raw.isNotEmpty) return raw;

    // Dự phòng nếu backend chưa trả tien_trinh
    final tt = jStr(d['trang_thai']);
    final shipping = tt == 'SHIPPING' || tt == 'DELIVERED';
    final den = jBool(d['da_bao_den_noi']) || tt == 'DELIVERED';
    return [
      {'label': tr('od_step_1'), 'done': true},
      {'label': tr('od_step_2'), 'done': shipping},
      {'label': tr('od_step_3'), 'done': den},
      {'label': tr('od_step_4'), 'done': tt == 'DELIVERED'},
    ];
  }
}

// ═════════════════════════════════════════════════════════════════
// TopBar (nhỏ, giữ trong screen)
// ═════════════════════════════════════════════════════════════════
class _TopBar extends StatelessWidget {
  final String title;
  final VoidCallback onBack;
  const _TopBar({required this.title, required this.onBack});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 20, 8),
      child: Row(
        children: [
          GestureDetector(
            onTap: onBack,
            child: const GlassContainer(
              borderRadius: 16,
              opacity: 0.42,
              padding: EdgeInsets.all(10),
              borderColor: Color(0xB3FFFFFF),
              child: Icon(
                Icons.arrow_back_ios_new_rounded,
                size: 18,
                color: Pink.plum,
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: Pink.plum,
                fontSize: 22,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
