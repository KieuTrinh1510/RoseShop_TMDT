// lib/utils/format.dart
// Định dạng tiền / ngày / đồng hồ + đọc JSON an toàn (không crash khi thiếu key).

Map<String, dynamic> jMap(dynamic v) =>
    v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};

List<dynamic> jList(dynamic v) => v is List ? v : <dynamic>[];

num jNum(dynamic v) {
  if (v is num) return v;
  return num.tryParse(v?.toString() ?? '') ?? 0;
}

int jInt(dynamic v) => jNum(v).toInt();

String jStr(dynamic v, [String fallback = '']) {
  final s = v?.toString();
  return (s == null || s.isEmpty) ? fallback : s;
}

bool jBool(dynamic v) => v == true || v == 1 || v == '1';

/// 150000 -> "150.000 đ"
String fmtMoney(num v) {
  final neg = v < 0;
  final s = v.abs().round().toString().replaceAllMapped(
    RegExp(r'\B(?=(\d{3})+(?!\d))'),
    (_) => '.',
  );
  return '${neg ? '-' : ''}$s đ';
}

String _2(int n) => n.toString().padLeft(2, '0');

/// ISO string -> "05/10/2026 11:19" (giờ máy)
String fmtDateTime(dynamic v) {
  final d = DateTime.tryParse(v?.toString() ?? '');
  if (d == null) return '—';
  final l = d.toLocal();
  return '${_2(l.day)}/${_2(l.month)}/${l.year} ${_2(l.hour)}:${_2(l.minute)}';
}

/// 3725 -> "01:02:05"
String fmtHms(int totalSeconds) {
  final s = totalSeconds < 0 ? 0 : totalSeconds;
  return '${_2(s ~/ 3600)}:${_2((s % 3600) ~/ 60)}:${_2(s % 60)}';
}
