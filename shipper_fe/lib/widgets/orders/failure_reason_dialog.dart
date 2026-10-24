// lib/widgets/orders/failure_reason_dialog.dart
// Dialog báo giao hàng thất bại. Nội dung cảnh báo khác nhau theo loại lỗi.
// Nếu truyền [onSubmit], dialog GIỮ MỞ + khóa nút trong lúc gọi API, rồi đóng và trả
// FailureReasonResult (có thể kèm error) → không thể bấm xác nhận 2 lần.

import 'package:flutter/material.dart';
import '../../theme/pink_theme.dart';
import '../../utils/app_strings.dart';

class FailureReasonResult {
  final String loaiLoi;
  final String lyDo;
  final String? ghiChu;

  /// Message lỗi nếu [FailureReasonDialog.onSubmit] trả về lỗi; null = thành công.
  final String? error;

  const FailureReasonResult({
    required this.loaiLoi,
    required this.lyDo,
    this.ghiChu,
    this.error,
  });

  FailureReasonResult withError(String? error) => FailureReasonResult(
    loaiLoi: loaiLoi,
    lyDo: lyDo,
    ghiChu: ghiChu,
    error: error,
  );
}

class FailureReasonDialog extends StatefulWidget {
  final bool allowShipper;
  final bool allowCustomer;

  /// Gọi API bên trong dialog. Trả về null nếu thành công, hoặc message lỗi.
  final Future<String?> Function(FailureReasonResult result)? onSubmit;

  const FailureReasonDialog({
    super.key,
    required this.allowShipper,
    required this.allowCustomer,
    this.onSubmit,
  });

  @override
  State<FailureReasonDialog> createState() => _FailureReasonDialogState();
}

class _FailureReasonDialogState extends State<FailureReasonDialog> {
  /// Backend giới hạn ly_do 150 ký tự.
  static const int _lyDoMax = 150;
  static const int _ghiChuMax = 500; // backend cho tối đa 1000

  static const _otherReason = 'Lý do khác';

  final _otherController = TextEditingController();
  final _noteController = TextEditingController();
  String? _source;
  String? _selectedReason;
  bool _submitting = false;

  static const _shipperReasons = [
    'Xe hỏng hoặc sự cố phương tiện',
    'Tai nạn hoặc vấn đề an toàn',
    'Hàng hóa bị hư hỏng khi vận chuyển',
    'Không thể tiếp tục giao do sự cố phát sinh',
    _otherReason,
  ];

  static const _customerReasons = [
    'Không liên lạc được với khách hàng',
    'Khách hàng không có mặt tại địa chỉ',
    'Khách hàng từ chối nhận hàng',
    'Địa chỉ giao hàng không chính xác',
    'Không tìm thấy địa chỉ giao hàng',
    'Khách hẹn lại nhưng quá thời gian',
    _otherReason,
  ];

  @override
  void initState() {
    super.initState();
    if (widget.allowShipper) {
      _source = 'SHIPPER';
    } else if (widget.allowCustomer) {
      _source = 'CUSTOMER';
    }
  }

  @override
  void dispose() {
    _otherController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  List<String> get _reasons =>
      _source == 'CUSTOMER' ? _customerReasons : _shipperReasons;

  bool get _isOther => _selectedReason == _otherReason;

  String get _lyDo {
    if (_selectedReason == null) return '';
    return _isOther ? _otherController.text.trim() : _selectedReason!;
  }

  bool get _canSubmit =>
      !_submitting &&
      _source != null &&
      _lyDo.isNotEmpty &&
      _lyDo.length <= _lyDoMax;

  void _selectSource(String value) {
    if (_submitting) return;
    setState(() {
      _source = value;
      _selectedReason = null;
      _otherController.clear();
    });
  }

  Future<void> _submit() async {
    if (!_canSubmit) return;
    setState(() => _submitting = true);

    final note = _noteController.text.trim();
    var result = FailureReasonResult(
      loaiLoi: _source!,
      lyDo: _lyDo,
      ghiChu: note.isNotEmpty ? note : (_isOther ? 'Lý do tự nhập' : null),
    );

    final submit = widget.onSubmit;
    if (submit != null) {
      String? err;
      try {
        err = await submit(result);
      } catch (e) {
        err = tr('conn_err', {'e': '$e'});
      }
      result = result.withError(err);
    }

    if (!mounted) return;
    Navigator.pop(context, result);
  }

  @override
  Widget build(BuildContext context) {
    final isCustomer = _source == 'CUSTOMER';

    return PopScope(
      canPop: !_submitting,
      child: AlertDialog(
        backgroundColor: Pink.dialogBg,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
        title: Text(
          tr('fl_title'),
          style: const TextStyle(color: Pink.plum, fontWeight: FontWeight.w800),
        ),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                tr('fl_source'),
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              if (widget.allowShipper)
                RadioListTile<String>(
                  contentPadding: EdgeInsets.zero,
                  title: Text(tr('fl_src_shipper')),
                  value: 'SHIPPER',
                  groupValue: _source,
                  onChanged: _submitting ? null : (v) => _selectSource(v!),
                ),
              if (widget.allowCustomer)
                RadioListTile<String>(
                  contentPadding: EdgeInsets.zero,
                  title: Text(tr('fl_src_customer')),
                  value: 'CUSTOMER',
                  groupValue: _source,
                  onChanged: _submitting ? null : (v) => _selectSource(v!),
                ),
              if (_source != null) ...[
                const SizedBox(height: 4),
                _NoticeBox(
                  color: isCustomer ? Pink.orange : Pink.danger,
                  icon: isCustomer
                      ? Icons.hourglass_bottom_rounded
                      : Icons.warning_amber_rounded,
                  text: isCustomer
                      ? tr('fl_warn_customer')
                      : tr('fl_warn_shipper'),
                ),
              ],
              const SizedBox(height: 12),
              Text(
                tr('fl_pick_reason'),
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              ..._reasons.map(
                (reason) => RadioListTile<String>(
                  contentPadding: EdgeInsets.zero,
                  dense: true,
                  title: Text(reason),
                  value: reason,
                  groupValue: _selectedReason,
                  onChanged: _submitting
                      ? null
                      : (v) => setState(() => _selectedReason = v),
                ),
              ),
              if (_isOther)
                TextField(
                  controller: _otherController,
                  enabled: !_submitting,
                  maxLines: 3,
                  maxLength: _lyDoMax,
                  onChanged: (_) => setState(() {}),
                  decoration: InputDecoration(
                    labelText: tr('fl_other_label'),
                    border: const OutlineInputBorder(),
                  ),
                ),
              const SizedBox(height: 8),
              TextField(
                controller: _noteController,
                enabled: !_submitting,
                maxLines: 2,
                maxLength: _ghiChuMax,
                decoration: InputDecoration(
                  labelText: tr('fl_note_label'),
                  border: const OutlineInputBorder(),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: _submitting ? null : () => Navigator.pop(context),
            child: Text(tr('cancel')),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: isCustomer ? Pink.orange : Pink.rose,
            ),
            onPressed: _canSubmit ? _submit : null,
            child: _submitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.2,
                      color: Colors.white,
                    ),
                  )
                : Text(
                    isCustomer ? tr('fl_btn_customer') : tr('fl_btn_shipper'),
                  ),
          ),
        ],
      ),
    );
  }
}

class _NoticeBox extends StatelessWidget {
  final Color color;
  final IconData icon;
  final String text;
  const _NoticeBox({
    required this.color,
    required this.icon,
    required this.text,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.10),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withOpacity(0.4)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: TextStyle(color: color, fontSize: 13, height: 1.35),
            ),
          ),
        ],
      ),
    );
  }
}
