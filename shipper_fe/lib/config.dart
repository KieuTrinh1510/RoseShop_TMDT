// lib/config.dart
// Cấu hình dùng chung: URL backend + mã nhân viên (shipper) đang giả lập đăng nhập.
// Vì backend chưa có login thật, ta gửi mã này qua header 'x-ma-nhan-vien'
// (xem middleware fakeAuth ở phần backend mình gửi kèm trong README).

class AppConfig {
  // Chạy Chrome/web hoặc iOS simulator: dùng localhost.
  // Chạy Android emulator: đổi thành http://10.0.2.2:3000/api/shipper
  // Chạy trên thiết bị thật: đổi thành IP LAN máy chạy Node, vd http://192.168.1.5:3000/api/shipper
  static String baseUrl = 'http://localhost:3000/api/shipper';

  // Mã nhân viên (shipper) dùng để test, theo yêu cầu: 9 hoặc 10
  static int maNhanVien = 9;
}
