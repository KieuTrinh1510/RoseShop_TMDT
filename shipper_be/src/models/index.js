// src/models/index.js
// Gom tất cả model lại để import 1 chỗ

const sequelize = require('../config/database');

module.exports = {
    sequelize, // export thêm instance Sequelize để app.js có thể gọi authenticate()/sync()

    // Tài khoản & người dùng
    NguoiDung: require('./NguoiDung'),
    NhanVien: require('./NhanVien'),
    KhachHang: require('./KhachHang'),

    // Sản phẩm & đơn hàng
    SanPham: require('./SanPham'),
    HoaDon: require('./HoaDon'),
    ChiTietHoaDon: require('./ChiTietHoaDon'),

    // Bó hoa
    BoHoa: require('./BoHoa'),
    ChiTietBoHoa: require('./ChiTietBoHoa'),
    MauBoHoa: require('./MauBoHoa'),
    ChiTietMauBoHoa: require('./ChiTietMauBoHoa'),
    PhuPhiBoHoa: require('./PhuPhiBoHoa'),
};