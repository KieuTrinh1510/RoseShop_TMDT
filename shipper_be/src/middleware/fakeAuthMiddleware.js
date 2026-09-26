// fakeAuthMiddleware.js
// CHỈ DÙNG ĐỂ TEST - xoá/khoá file này khi có middleware đăng nhập thật.
//
// Đọc mã nhân viên từ header 'x-ma-nhan-vien' (app Flutter gửi header này),
// gắn vào req.user.ma_nguoi_dung giống hệt như khi đã đăng nhập thật,
// để shipperController.js dùng layMaNhanVien(req) không bị lỗi.

module.exports = function fakeAuthMiddleware(req, res, next) {
    const ma = req.header('x-ma-nhan-vien');
    if (!ma) {
        return res.status(401).json({
            success: false,
            code: 'NO_FAKE_AUTH_HEADER',
            message: "Thiếu header 'x-ma-nhan-vien' (chỉ dùng khi test, chưa có login thật).",
        });
    }
    req.user = { ma_nguoi_dung: Number(ma) };
    next();
};

// Cách gắn vào app.js / server.js, TRƯỚC route shipper:
//
//   const fakeAuthMiddleware = require('./fakeAuthMiddleware');
//   const shipperRoutes = require('./src/routes/shipperRoutes');
//
//   app.use('/api/shipper', fakeAuthMiddleware, shipperRoutes);
//
// Nhớ: trong DB phải có sẵn nhân viên (bảng nhan_vien) với ma_nhan_vien = 9 và 10
// để test, vì shipperStatusService.timShipperHoacNemLoi() sẽ báo 404 nếu không có.