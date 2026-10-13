// src/services/orders/customer_notification/customer_notification_services.js
// Thông báo gửi cho KHÁCH HÀNG khi đơn chuyển trạng thái.
// Tách riêng khỏi home/notification (thông báo cho shipper).

const ThongBao = require('../../../models/ThongBao');
const LOAI = require('../../shared/notification_types');

// ── Nội dung ──
function noiDungDangGiao(maHienThi, dsSanPham, laHoaTuoi = false) {
    const canhBao = laHoaTuoi
        ? ' Vì đơn hàng là hoa tươi, shop sẽ giao trong vòng 3 giờ tới.'
        : '';
    return `🚚 Đơn hàng ${maHienThi} gồm ${dsSanPham} đã được shipper nhận và đang trên đường giao đến bạn!${canhBao} Vui lòng chú ý điện thoại.`;
}

function noiDungDenNoi(maHienThi, dsSanPham, diaChi) {
    return `📍 Đơn hàng ${maHienThi} gồm: ${dsSanPham} đã đến địa điểm giao ${diaChi}. Bạn hãy xuống nhận hàng nhé! 🌸`;
}

function noiDungHoanThanh(maHienThi, dsSanPham) {
    return `✅ Đơn hàng ${maHienThi} gồm ${dsSanPham} đã được giao thành công. Cảm ơn quý khách đã sử dụng dịch vụ mua hàng tại shop của chúng tôi! 🌸`;
}

function noiDungXinLoiTre(maHienThi, diem) {
    return `😔 Xin lỗi vì sự bất tiện! Đơn hàng ${maHienThi} của bạn chưa được giao đúng hẹn. Chúng tôi đã cộng thêm ${diem} điểm tích lũy vào tài khoản của bạn để bù đắp. Cảm ơn bạn đã thông cảm! 💐`;
}

// ── Ghi ──
// Truyền options.transaction để ghi chung transaction với update đơn
async function tao(maKhachHang, loai, noiDung, maHoaDon, options = {}) {
    return ThongBao.create(
        {
            ma_nguoi_dung: maKhachHang,
            loai,
            noi_dung: noiDung,
            ma_hoa_don: maHoaDon,
            da_doc: false,
            thoi_gian: new Date(),
        },
        options
    );
}

async function taoDangGiao(maKhachHang, maHoaDon, maHienThi, dsSanPham, laHoaTuoi) {
    return tao(
        maKhachHang,
        LOAI.SHIPPING,
        noiDungDangGiao(maHienThi, dsSanPham, laHoaTuoi),
        maHoaDon
    );
}

async function taoDenNoi(maKhachHang, maHoaDon, maHienThi, dsSanPham, diaChi, options = {}) {
    return tao(
        maKhachHang,
        LOAI.ARRIVED,
        noiDungDenNoi(maHienThi, dsSanPham, diaChi),
        maHoaDon,
        options
    );
}

async function taoHoanThanh(maKhachHang, maHoaDon, maHienThi, dsSanPham) {
    return tao(
        maKhachHang,
        LOAI.DELIVERED,
        noiDungHoanThanh(maHienThi, dsSanPham),
        maHoaDon
    );
}

async function taoXinLoiTre(maKhachHang, maHoaDon, maHienThi, diem, options = {}) {
    return tao(
        maKhachHang,
        LOAI.APOLOGY,
        noiDungXinLoiTre(maHienThi, diem),
        maHoaDon,
        options
    );
}

module.exports = {
    noiDungDangGiao,
    noiDungDenNoi,
    noiDungHoanThanh,
    noiDungXinLoiTre,
    tao,
    taoDangGiao,
    taoDenNoi,
    taoHoanThanh,
    taoXinLoiTre,
};