// src/services/thongBaoService.js
// Lõi gửi/đếm thông báo. Phần xem danh sách, đánh dấu đã đọc, xóa
// (thongBaoController cho shipper và khách) sẽ thêm sau, dùng chung service này.

const ThongBao = require('../models/ThongBao');

const LOAI = {
    SHIPPING: 'shipping',   // đơn đã được shipper nhận, đang trên đường giao
    ARRIVED: 'arrived',     // shipper đã đến điểm giao
    DELIVERED: 'delivered', // đơn đã giao thành công
    APOLOGY: 'apology',     // xin lỗi khách do giao trễ
    PENALTY: 'penalty',     // báo shipper bị phạt
    INFO: 'info',
};

// options có thể truyền { transaction } nếu muốn gửi trong cùng transaction
async function tao({ maNguoiDung, loai = LOAI.INFO, noiDung, maHoaDon = null }, options = {}) {
    return ThongBao.create({
        ma_nguoi_dung: maNguoiDung,
        loai,
        noi_dung: noiDung,
        ma_hoa_don: maHoaDon,
        da_doc: false,
        thoi_gian: new Date(),
    }, options);
}

async function demChuaDoc(maNguoiDung) {
    return ThongBao.count({ where: { ma_nguoi_dung: maNguoiDung, da_doc: false } });
}

// Đơn này đã có thông báo loại X chưa.
// LƯU Ý: không dùng hàm này để suy ra "đã đến điểm giao" vì khách có thể xóa thông báo.
// Trạng thái đó nằm ở cột hoa_don.ngay_den_noi.
async function daCoThongBaoDon(maHoaDon, loai) {
    const so = await ThongBao.count({ where: { ma_hoa_don: maHoaDon, loai } });
    return so > 0;
}

// ── Nội dung ──
function noiDungDangGiao(maHienThi, dsSanPham) {
    return `🚚 Đơn hàng ${maHienThi} gồm ${dsSanPham} đã được shipper nhận và đang trên đường giao đến bạn! Vui lòng chú ý điện thoại.`;
}

function noiDungDenNoi(maHienThi, dsSanPham, diaChi) {
    return `Đơn hàng ${maHienThi} gồm: ${dsSanPham} đã đến địa điểm giao ${diaChi}. Bạn hãy xuống nhận hàng nhé! 🌸`;
}

function noiDungHoanThanh(maHienThi, dsSanPham) {
    return `✅ Đơn hàng ${maHienThi} gồm ${dsSanPham} đã được giao thành công. Cảm ơn bạn đã mua hàng! 🌸`;
}

// UC4.2.10 – gửi khách
function noiDungXinLoiTre(maHienThi, diem) {
    return `😔 Xin lỗi vì sự bất tiện! Đơn hàng ${maHienThi} của bạn chưa được giao đúng hẹn. Chúng tôi đã cộng thêm ${diem} điểm tích lũy vào tài khoản của bạn để bù đắp. Cảm ơn bạn đã thông cảm! 💐`;
}

// UC4.2.10 – gửi shipper
function noiDungPhatTre(maHienThi, tienPhat) {
    const tien = Number(tienPhat).toLocaleString('vi-VN');
    return `⚠️ Đơn hàng ${maHienThi} quá 3 tiếng chưa giao thành công. Bạn đã bị trừ thêm ${tien} đ vào khoản cần nộp vì giao trễ.`;
}

module.exports = {
    LOAI,
    tao,
    demChuaDoc,
    daCoThongBaoDon,
    noiDungDangGiao,
    noiDungDenNoi,
    noiDungHoanThanh,
    noiDungXinLoiTre,
    noiDungPhatTre,
};