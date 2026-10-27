// lib/widgets/history/history_filter_bar.dart
// Bộ lọc lịch sử: ô tìm mã đơn (có báo lỗi) + chip nhanh (Hôm nay/Tuần/Tháng)
// + 3 nút chọn Ngày / Khoảng ngày / Tháng (luôn hiển thị, tự xuống dòng).

import 'package:flutter/material.dart';
import '../../utils/app_strings.dart';
import 'package:flutter/services.dart';
import '../../controllers/history/history_controller.dart';
import '../../theme/pink_theme.dart';
import '../common/staggered_fade_slide.dart';

class HistoryFilterBar extends StatefulWidget {
  final HistoryController controller;
  const HistoryFilterBar({super.key, required this.controller});

  @override
  State<HistoryFilterBar> createState() => _HistoryFilterBarState();
}

class _HistoryFilterBarState extends State<HistoryFilterBar> {
  /// Locale cho showDatePicker / showDateRangePicker.
  /// Để null = dùng locale của app (an toàn, không crash).
  /// Muốn lịch tiếng Việt: thêm flutter_localizations + localizationsDelegates
  /// vào MaterialApp, rồi đổi thành Locale('vi', 'VN').
  static const Locale? _pickerLocale = null;

  late final TextEditingController _searchCtrl;
  final _searchFocus = FocusNode();

  /// Lỗi validate phía app (hiện ngay dưới ô tìm, không gọi backend).
  String? _maError;

  @override
  void initState() {
    super.initState();
    _searchCtrl = TextEditingController(text: widget.controller.ma ?? '');
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _searchFocus.dispose();
    super.dispose();
  }

  // ── Format ngày ─────────────────────────────────────────────
  String _ymd(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-'
      '${d.month.toString().padLeft(2, '0')}-'
      '${d.day.toString().padLeft(2, '0')}';
  String _ym(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-'
      '${d.month.toString().padLeft(2, '0')}';
  String _short(DateTime d) =>
      '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}';
  String _shortStr(String? ymd) {
    final d = DateTime.tryParse(ymd ?? '');
    return d == null ? (ymd ?? '?') : _short(d);
  }

  // ── Validate mã đơn ─────────────────────────────────────────
  String? _validateMa(String raw) {
    final s = raw.trim();
    if (s.isEmpty) return null;
    if (RegExp(r'\s').hasMatch(s)) {
      return tr('ma_err_space');
    }
    if (s.startsWith('-')) return tr('ma_err_neg');
    final body = s.startsWith('#') ? s.substring(1) : s;
    if (body.isEmpty) return tr('ma_err_hash_missing');
    if (body.contains('#')) return tr('ma_err_hash_multi');
    if (!RegExp(r'^\d+$').hasMatch(body)) {
      return tr('ma_err_digits');
    }
    if (RegExp(r'^0+$').hasMatch(body)) return tr('ma_err_zero');
    if (body.length > 9) return tr('ma_err_long');
    return null;
  }

  // ── Áp filter ───────────────────────────────────────────────
  // setFilter() ghi đè toàn bộ → luôn truyền lại `ma` đang có.
  void _applyMa(String? ma) {
    final c = widget.controller;
    c.setFilter(
      ngay: c.ngay,
      tuNgay: c.tuNgay,
      denNgay: c.denNgay,
      thang: c.thang,
      ma: (ma?.isEmpty ?? true) ? null : ma,
      loai: c.loai,
    );
  }

  /// Ngày / khoảng / tháng loại trừ nhau; riêng `ma` luôn được giữ.
  void _setDate({String? ngay, String? tu, String? den, String? thang}) {
    final c = widget.controller;
    c.setFilter(ngay: ngay, tuNgay: tu, denNgay: den, thang: thang, ma: c.ma, loai: c.loai);
  }

  void _submitMa(String raw) {
    final err = _validateMa(raw);
    if (err != null) {
      setState(() => _maError = err);
      return;
    }
    _searchFocus.unfocus();
    setState(() => _maError = null);
    final s = raw.trim();
    final body = s.startsWith('#') ? s.substring(1) : s;
    _applyMa(body.isEmpty ? null : body);
  }

  void _clearMa() {
    _searchCtrl.clear();
    setState(() => _maError = null);
    if (widget.controller.ma != null) _applyMa(null);
  }

  void _clearAll() {
    _searchCtrl.clear();
    setState(() => _maError = null);
    widget.controller.clearFilter();
  }

  // ── Pickers ─────────────────────────────────────────────────
  // Tô màu hồng cho dialog lịch của hệ thống.
  Widget _pinkPicker(BuildContext ctx, Widget? child) {
    final theme = Theme.of(ctx);
    return Theme(
      data: theme.copyWith(
        colorScheme: theme.colorScheme.copyWith(
          primary: Pink.rose,
          onPrimary: Colors.white,
          onSurface: Pink.plum,
        ),
      ),
      child: child ?? const SizedBox.shrink(),
    );
  }

  Future<void> _pickDay() async {
    final now = DateTime.now();
    final first = DateTime(2020);
    final last = DateTime(now.year + 1, 12, 31);
    var initial = DateTime.tryParse(widget.controller.ngay ?? '') ?? now;
    if (initial.isBefore(first) || initial.isAfter(last)) initial = now;

    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: first,
      lastDate: last,
      locale: _pickerLocale,
      helpText: tr('help_day'),
      cancelText: tr('cancel'),
      confirmText: tr('choose'),
      builder: _pinkPicker,
    );
    if (!mounted || picked == null) return;
    _setDate(ngay: _ymd(picked));
  }

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final first = DateTime(2020);
    final last = DateTime(now.year + 1, 12, 31);
    final tu = DateTime.tryParse(widget.controller.tuNgay ?? '');
    final den = DateTime.tryParse(widget.controller.denNgay ?? '');

    DateTimeRange? initial;
    if (tu != null &&
        den != null &&
        !tu.isAfter(den) &&
        !tu.isBefore(first) &&
        !den.isAfter(last)) {
      initial = DateTimeRange(start: tu, end: den);
    }

    final picked = await showDateRangePicker(
      context: context,
      firstDate: first,
      lastDate: last,
      initialDateRange: initial,
      locale: _pickerLocale,
      helpText: tr('help_range'),
      saveText: tr('apply'),
      cancelText: tr('cancel'),
      confirmText: tr('choose'),
      builder: _pinkPicker,
    );
    if (!mounted || picked == null) return;
    // start <= end do picker đảm bảo; backend den_ngay là inclusive.
    _setDate(tu: _ymd(picked.start), den: _ymd(picked.end));
  }

  Future<void> _pickMonth() async {
    final now = DateTime.now();
    int year = now.year;
    int? selected;
    if (widget.controller.thang != null) {
      final parts = widget.controller.thang!.split('-');
      if (parts.length == 2) {
        year = int.tryParse(parts[0]) ?? year;
        selected = int.tryParse(parts[1]);
      }
    }

    final res = await showDialog<String>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setLocal) => AlertDialog(
          backgroundColor: Pink.dialogBg,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(22),
          ),
          title: Text(
            tr('pick_month'),
            style: const TextStyle(
              color: Pink.plum,
              fontWeight: FontWeight.w800,
              fontSize: 18,
            ),
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    onPressed: () => setLocal(() => year--),
                    icon: const Icon(Icons.chevron_left_rounded),
                    color: Pink.plum,
                  ),
                  Text(
                    '$year',
                    style: const TextStyle(
                      color: Pink.plum,
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  IconButton(
                    onPressed: () => setLocal(() => year++),
                    icon: const Icon(Icons.chevron_right_rounded),
                    color: Pink.plum,
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: List.generate(12, (i) {
                  final m = i + 1;
                  final active = m == selected;
                  // 12 ô tháng hiện lần lượt: fade + slide
                  return StaggeredFadeSlide(
                    index: i,
                    stepMs: 30,
                    durationMs: 300,
                    slide: 0.2,
                    maxIndex: 12,
                    child: InkWell(
                      onTap: () => setLocal(() => selected = m),
                      borderRadius: BorderRadius.circular(10),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 180),
                        width: 48,
                        height: 40,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: active ? Pink.rose : Colors.white,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: active
                                ? Colors.transparent
                                : Pink.plum.withOpacity(0.2),
                          ),
                        ),
                        child: Text(
                          tr('month_short', {'m': '$m'}),
                          style: TextStyle(
                            color: active ? Colors.white : Pink.plum,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  );
                }),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: Text(
                tr('cancel'),
                style: const TextStyle(color: Pink.muted),
              ),
            ),
            FilledButton(
              style: FilledButton.styleFrom(backgroundColor: Pink.rose),
              onPressed: selected == null
                  ? null
                  : () => Navigator.pop(
                      ctx,
                      '${year.toString().padLeft(4, '0')}-'
                      '${selected.toString().padLeft(2, '0')}',
                    ),
              child: Text(tr('choose')),
            ),
          ],
        ),
      ),
    );
    if (!mounted || res == null) return;
    _setDate(thang: res);
  }

  // ── Animation đổi chip ↔ nút (fade + slide nhẹ) ─────────────
  Widget _slot(String key, Widget child) {
    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 240),
      switchInCurve: Curves.easeOutCubic,
      transitionBuilder: (w, anim) => FadeTransition(
        opacity: anim,
        child: SlideTransition(
          position: Tween<Offset>(
            begin: const Offset(0, 0.3),
            end: Offset.zero,
          ).animate(anim),
          child: w,
        ),
      ),
      child: KeyedSubtree(key: ValueKey(key), child: child),
    );
  }

  // ── Build ───────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final c = widget.controller;
    final now = DateTime.now();
    final todayStr = _ymd(now);
    final weekStart = _ymd(now.subtract(Duration(days: now.weekday - 1)));
    final thisMonth = _ym(now);

    final hasDate = c.ngay != null;
    final hasRange = c.tuNgay != null || c.denNgay != null;
    final hasMonth = c.thang != null;

    // Chip nhanh đang active?
    final isToday = hasDate && c.ngay == todayStr;
    final isWeek = hasRange && c.tuNgay == weekStart && c.denNgay == todayStr;
    final isThisMonth = hasMonth && c.thang == thisMonth;

    // Chip tuỳ chọn chỉ hiện khi KHÔNG trùng chip nhanh
    final customDay = hasDate && !isToday;
    final customRange = hasRange && !isWeek;
    final customMonth = hasMonth && !isThisMonth;

    final serverMaError = c.errorCode == 'INVALID_ORDER_CODE' ? c.error : null;
    final maError = _maError ?? serverMaError;

    final errorBorder = OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide(color: Colors.red.shade400, width: 1.5),
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Ô tìm mã
        TextField(
          controller: _searchCtrl,
          focusNode: _searchFocus,
          keyboardType: TextInputType.text,
          textInputAction: TextInputAction.search,
          onSubmitted: _submitMa,
          onChanged: (_) => setState(() => _maError = null),
          inputFormatters: [LengthLimitingTextInputFormatter(20)],
          style: const TextStyle(color: Pink.plum, fontSize: 14),
          decoration: InputDecoration(
            hintText: tr('search_hint'),
            hintStyle: TextStyle(color: Pink.plum.withOpacity(0.4)),
            prefixIcon: IconButton(
              icon: const Icon(Icons.search_rounded, size: 20),
              color: Pink.rose,
              onPressed: () => _submitMa(_searchCtrl.text),
            ),
            suffixIcon: _searchCtrl.text.isNotEmpty
                ? IconButton(
                    icon: const Icon(Icons.clear_rounded, size: 18),
                    color: Pink.muted,
                    onPressed: _clearMa,
                  )
                : null,
            errorText: maError,
            errorMaxLines: 3,
            errorStyle: TextStyle(
              color: Colors.red.shade700,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
            filled: true,
            fillColor: Colors.white.withOpacity(0.6),
            contentPadding: const EdgeInsets.symmetric(vertical: 0),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14),
              borderSide: BorderSide(color: Colors.white.withOpacity(0.7)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14),
              borderSide: BorderSide(color: Colors.white.withOpacity(0.7)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14),
              borderSide: const BorderSide(color: Pink.rose, width: 1.5),
            ),
            errorBorder: errorBorder,
            focusedErrorBorder: errorBorder,
          ),
        ),
        const SizedBox(height: 10),

        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _Chip(label: tr('f_all'), active: !c.coLoc, onTap: _clearAll),
            _Chip(label: tr('hist_delivered'), active: c.loai == 'DELIVERED', onTap: () => c.setFilter(ngay: c.ngay, tuNgay: c.tuNgay, denNgay: c.denNgay, thang: c.thang, ma: c.ma, loai: 'DELIVERED')),
            _Chip(label: tr('hist_cancel_shipper'), active: c.loai == 'CANCELLED_SHIPPER', onTap: () => c.setFilter(ngay: c.ngay, tuNgay: c.tuNgay, denNgay: c.denNgay, thang: c.thang, ma: c.ma, loai: 'CANCELLED_SHIPPER')),
            _Chip(label: tr('hist_cancel_customer'), active: c.loai == 'CANCELLED_CUSTOMER', onTap: () => c.setFilter(ngay: c.ngay, tuNgay: c.tuNgay, denNgay: c.denNgay, thang: c.thang, ma: c.ma, loai: 'CANCELLED_CUSTOMER')),
            _Chip(
              label: tr('chip_today'),
              active: isToday,
              onTap: () => _setDate(ngay: todayStr),
            ),
            _Chip(
              label: tr('chip_week'),
              active: isWeek,
              onTap: () => _setDate(tu: weekStart, den: todayStr),
            ),
            _Chip(
              label: tr('chip_month'),
              active: isThisMonth,
              onTap: () => _setDate(thang: thisMonth),
            ),
            // Ngày tuỳ chọn
            _slot(
              customDay ? 'day-${c.ngay}' : 'day-btn',
              customDay
                  ? _Chip(
                      label: '📅 ${_shortStr(c.ngay)}',
                      active: true,
                      onTap: _pickDay,
                      onClear: () => _setDate(),
                    )
                  : _IconChip(
                      icon: Icons.calendar_today_rounded,
                      label: tr('btn_day'),
                      onTap: _pickDay,
                    ),
            ),
            // Khoảng ngày
            _slot(
              customRange ? 'range-${c.tuNgay}-${c.denNgay}' : 'range-btn',
              customRange
                  ? _Chip(
                      label:
                          '📆 ${_shortStr(c.tuNgay)}-${_shortStr(c.denNgay)}',
                      active: true,
                      onTap: _pickRange,
                      onClear: () => _setDate(),
                    )
                  : _IconChip(
                      icon: Icons.date_range_rounded,
                      label: tr('btn_range'),
                      onTap: _pickRange,
                    ),
            ),
            // Tháng tuỳ chọn
            _slot(
              customMonth ? 'month-${c.thang}' : 'month-btn',
              customMonth
                  ? _Chip(
                      label: '🗓️ ${c.thang}',
                      active: true,
                      onTap: _pickMonth,
                      onClear: () => _setDate(),
                    )
                  : _IconChip(
                      icon: Icons.calendar_month_rounded,
                      label: tr('btn_month'),
                      onTap: _pickMonth,
                    ),
            ),
          ],
        ),
      ],
    );
  }
}

// ── Chip cơ bản ────────────────────────────────────────────────
class _Chip extends StatelessWidget {
  final String label;
  final bool active;
  final VoidCallback onTap;
  final VoidCallback? onClear;

  const _Chip({
    required this.label,
    required this.active,
    required this.onTap,
    this.onClear,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 220),
        curve: Curves.easeOut,
        padding: EdgeInsets.fromLTRB(14, 8, onClear != null ? 8 : 14, 8),
        decoration: BoxDecoration(
          gradient: active ? Pink.buttonGradient : null,
          color: active ? null : Colors.white.withOpacity(0.5),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: active ? Colors.transparent : Colors.white.withOpacity(0.7),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: TextStyle(
                color: active ? Colors.white : Pink.plum,
                fontWeight: FontWeight.w700,
                fontSize: 13,
              ),
            ),
            if (onClear != null) ...[
              const SizedBox(width: 4),
              GestureDetector(
                onTap: onClear,
                child: const Icon(
                  Icons.close_rounded,
                  size: 16,
                  color: Colors.white,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _IconChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _IconChip({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.5),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white.withOpacity(0.7)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: Pink.rose),
            const SizedBox(width: 4),
            Text(
              label,
              style: const TextStyle(
                color: Pink.plum,
                fontWeight: FontWeight.w700,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
