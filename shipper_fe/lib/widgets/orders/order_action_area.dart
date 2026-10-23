// lib/widgets/orders/order_action_area.dart
// Khối nút hành động theo từng bước giao hàng + báo thất bại + chờ khách.
// Screen truyền vào callback onRunAction để gọi API + dialog.
// Quyền bấm nút lấy từ backend (hanh_dong, coTheGuiLoi, coTheHoanThanh, coTheFinalize).
import 'dart:ui' show FontFeature;

import 'package:flutter/material.dart';
import '../../theme/pink_theme.dart';
import '../../utils/app_strings.dart';
import '../../utils/format.dart';

typedef RunActionCallback =
    Future<void> Function({
      required String path,
      required String title,
      required String message,
      required String okMsg,
    });

typedef ReportFailureCallback = Future<void> Function();
typedef FinalizeCallback = Future<void> Function();

String _mmss(int seconds) {
  final s = seconds < 0 ? 0 : seconds;
  return '${(s ~/ 60).toString().padLeft(2, '0')}:${(s % 60).toString().padLeft(2, '0')}';
}

class OrderActionArea extends StatelessWidget {
  final String maHoaDon;
  final Map<String, dynamic> d;
  final Map<String, dynamic> hanhDong;
  final bool busy;
  final RunActionCallback onRunAction;
  final Map<String, dynamic>? failureStatus;
  final int failureRemaining;
  final bool failureBusy; // đang gọi report-failure
  final bool failureFinalizing; // đang gọi finalize-customer-failure
  final ReportFailureCallback? onReportFailure;
  final FinalizeCallback?
  onFinalize; // chỉ dùng để "thử lại" khi finalize tự động lỗi mạng

  const OrderActionArea({
    super.key,
    required this.maHoaDon,
    required this.d,
    required this.hanhDong,
    required this.busy,
    required this.onRunAction,
    this.failureStatus,
    this.failureRemaining = 0,
    this.failureBusy = false,
    this.failureFinalizing = false,
    this.onReportFailure,
    this.onFinalize,
  });

  bool get _dangChoKhach =>
      jStr(failureStatus?['trangThaiBaoLoiKhach']) == 'DANG_CHO_KHACH';

  @override
  Widget build(BuildContext context) {
    final status = jStr(d['trang_thai']);

    // Đơn đã hủy: không có nút nào (thông tin hủy nằm ở OrderCancelCard).
    if (status == 'CANCELLED') return const SizedBox.shrink();

    final tp = jMap(d['thanh_toan']);
    final canThu = jBool(tp['can_thu_tien_mat']);
    final soTien = jNum(tp['so_tien_can_thu']);
    final laHoaTuoi = jBool(d['la_hoa_tuoi']);
    final id = maHoaDon;
    final failBusy = failureBusy || failureFinalizing;

    // Bước 1: Nhận hàng
    if (jBool(hanhDong['co_the_nhan_hang'])) {
      return _withFailure(
        _ActionBlock(
          button: PinkGradientButton(
            label: tr('ac_pickup_btn'),
            icon: Icons.local_shipping_rounded,
            busy: busy,
            onPressed: () => onRunAction(
              path: '/orders/$id/pickup',
              title: tr('ac_pickup_title'),
              message: laHoaTuoi
                  ? tr('ac_pickup_msg_fresh')
                  : tr('ac_pickup_msg'),
              okMsg: tr('ac_pickup_ok'),
            ),
          ),
          hint: laHoaTuoi ? tr('ac_pickup_hint_fresh') : tr('ac_pickup_hint'),
        ),
      );
    }

    // Bước 2: Đã đến điểm giao
    if (jBool(hanhDong['co_the_bao_den_noi'])) {
      return _withFailure(
        _ActionBlock(
          button: PinkGradientButton(
            label: tr('ac_arrived_btn'),
            icon: Icons.location_on_rounded,
            busy: busy,
            onPressed: () => onRunAction(
              path: '/orders/$id/arrived',
              title: tr('ac_arrived_title'),
              message: tr('ac_arrived_msg'),
              okMsg: tr('ac_arrived_ok'),
            ),
          ),
          hint: tr('ac_arrived_hint'),
        ),
      );
    }

    // Bước 3: Hoàn thành — vẫn bật khi đang chờ khách (backend: coTheHoanThanh).
    final canComplete =
        status == 'SHIPPING' &&
        (jBool(hanhDong['co_the_giao_xong']) ||
            jBool(failureStatus?['coTheHoanThanh']));
    if (canComplete) {
      return _withFailure(
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _dangChoKhach ? _waitingCard() : _sentCard(),
            const SizedBox(height: 14),
            _ActionBlock(
              button: PinkGradientButton(
                label: tr('ac_done_btn'),
                icon: Icons.check_circle_rounded,
                busy: busy,
                onPressed: failBusy
                    ? null
                    : () => onRunAction(
                        path: '/orders/$id/delivered',
                        title: tr('ac_done_title'),
                        message: canThu
                            ? tr('ac_done_msg_cod', {'amt': fmtMoney(soTien)})
                            : tr('ac_done_msg'),
                        okMsg: tr('ac_done_ok'),
                      ),
              ),
              hint: canThu
                  ? tr('ac_done_hint_cod', {'amt': fmtMoney(soTien)})
                  : tr('ac_done_hint'),
            ),
          ],
        ),
      );
    }

    // Đã giao xong
    if (status == 'DELIVERED') {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Pink.leaf.withOpacity(0.12),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Pink.leaf.withOpacity(0.4)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              tr('ac_completed_title'),
              style: const TextStyle(
                color: Pink.leaf,
                fontWeight: FontWeight.w800,
                fontSize: 14.5,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              tr('ac_delivered_at', {'t': fmtDateTime(d['ngay_giao'])}),
              style: const TextStyle(color: Pink.plum, fontSize: 13),
            ),
          ],
        ),
      );
    }

    return const SizedBox.shrink();
  }

  /// Thẻ xanh "Đã gửi thông báo đến khách" (giữ nguyên giao diện cũ).
  Widget _sentCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Pink.leaf.withOpacity(0.12),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Pink.leaf.withOpacity(0.4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            tr('ac_sent_title'),
            style: const TextStyle(
              color: Pink.leaf,
              fontWeight: FontWeight.w800,
              fontSize: 14.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            tr('ac_sent_body'),
            style: const TextStyle(color: Pink.plum, fontSize: 13),
          ),
        ],
      ),
    );
  }

  /// Thẻ cam "Đang chờ khách nhận hàng" + đồng hồ lần hai. Đơn KHÔNG bị coi là đã hủy.
  Widget _waitingCard() {
    final heTime = failureRemaining <= 0;
    final canRetry =
        jBool(failureStatus?['coTheFinalize']) &&
        !failureFinalizing &&
        onFinalize != null;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Pink.orange.withOpacity(0.12),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Pink.orange.withOpacity(0.45)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.hourglass_bottom_rounded,
                size: 20,
                color: Pink.orange,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  tr('fl_wait_title'),
                  style: const TextStyle(
                    color: Pink.orange,
                    fontWeight: FontWeight.w800,
                    fontSize: 14.5,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            tr('fl_wait_body'),
            style: const TextStyle(
              color: Pink.plum,
              fontSize: 13,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 10),
          Center(
            child: Text(
              heTime ? '00:00' : _mmss(failureRemaining),
              style: const TextStyle(
                color: Pink.orange,
                fontSize: 34,
                fontWeight: FontWeight.w800,
                letterSpacing: 2,
                fontFeatures: [FontFeature.tabularFigures()],
              ),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            heTime
                ? tr('fl_wait_expired')
                : tr('fl_wait_remain', {'t': _mmss(failureRemaining)}),
            textAlign: TextAlign.center,
            style: const TextStyle(color: Pink.muted, fontSize: 12.5),
          ),
          if (failureFinalizing) ...[
            const SizedBox(height: 10),
            const Center(
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2.4,
                  color: Pink.orange,
                ),
              ),
            ),
          ],
          if (canRetry) ...[
            const SizedBox(height: 10),
            OutlinedButton.icon(
              icon: const Icon(Icons.refresh_rounded),
              label: Text(tr('fl_retry_finalize')),
              style: OutlinedButton.styleFrom(
                foregroundColor: Pink.orange,
                side: BorderSide(color: Pink.orange.withOpacity(0.6)),
                minimumSize: const Size(double.infinity, 44),
              ),
              onPressed: () => onFinalize!(),
            ),
          ],
        ],
      ),
    );
  }

  Widget _withFailure(Widget primary) {
    final status = jStr(d['trang_thai']);
    final canShow =
        status != 'DELIVERED' &&
        status != 'CANCELLED' &&
        failureStatus != null &&
        onReportFailure != null;
    if (!canShow) return primary;

    // Đã báo lỗi khách và đang chờ: ẩn nút báo lỗi (backend cũng trả coTheGuiLoi = false).
    if (_dangChoKhach) return primary;

    final allowed = jMap(failureStatus?['coTheGuiLoi']);
    final allowShipper = jBool(allowed['SHIPPER']);
    final allowCustomer = jBool(allowed['CUSTOMER']);
    if (!allowShipper && !allowCustomer) return primary;

    final canCustomer = allowCustomer;
    final remaining = failureRemaining > 0
        ? failureRemaining
        : jInt(failureStatus?['giayConLai']);
    final timerText = _mmss(remaining);
    final disabled =
        failureBusy || failureFinalizing || onReportFailure == null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        primary,
        const SizedBox(height: 14),
        OutlinedButton.icon(
          icon: const Icon(Icons.report_problem_outlined),
          label: Text(
            canCustomer
                ? tr('fl_report_btn_both')
                : tr('fl_report_btn_shipper'),
          ),
          style: OutlinedButton.styleFrom(
            foregroundColor: Colors.red.shade700,
            side: BorderSide(color: Colors.red.shade300),
            minimumSize: const Size(double.infinity, 48),
          ),
          onPressed: disabled ? null : () => onReportFailure!(),
        ),
        const SizedBox(height: 6),
        Text(
          canCustomer
              ? tr('fl_hint_customer_ready')
              : remaining > 0
              ? tr('fl_hint_shipper_wait', {'t': timerText})
              : tr('fl_hint_shipper_only'),
          style: const TextStyle(
            color: Pink.muted,
            fontSize: 12.5,
            height: 1.35,
          ),
        ),
      ],
    );
  }
}

class _ActionBlock extends StatelessWidget {
  final Widget button;
  final String hint;
  const _ActionBlock({required this.button, required this.hint});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        button,
        const SizedBox(height: 8),
        Text(
          hint,
          style: const TextStyle(
            color: Pink.muted,
            fontSize: 12.5,
            height: 1.35,
          ),
        ),
      ],
    );
  }
}
