// src/services/thongBaoService.js
// Lõi gửi/đếm/đọc thông báo. Dùng chung cho cả khách hàng và shipper.

const { Op } = require('sequelize');
const ThongBao = require('../models/ThongBao');

const LOAI = {
    SHIPPING: 'shipping',
    ARRIVED: 'arrived',
    DELIVERED: 'delivered',
    APOLOGY: 'apology',
    PENALTY: 'penalty',
    INFO: 'info',
    // 2 loại chủ yếu bên PHP ghi, Node chỉ đọc để hiển thị:
    ORDER_NEW: 'order_new',
    PAYMENT: 'payment',
};

// ── Ghi ──
async function tao(
    { maNguoiDung, loai = LOAI.INFO, noiDung, maHoaDon = null, duLieu = null },
    options = {}
) {
    return ThongBao.create({
        ma_nguoi_dung: maNguoiDung,
        loai,
        noi_dung: noiDung,
        du_lieu: duLieu,
        ma_hoa_don: maHoaDon,
        da_doc: false,
        thoi_gian: new Date(),
    }, options);
}

// ── Đọc (dùng cho controller shipper) ──
async function danhSach(maNguoiDung, { loai, limit = 30 } = {}) {
    const where = { ma_nguoi_dung: maNguoiDung };
    if (loai && loai !== 'all') where.loai = loai;

    const rows = await ThongBao.findAll({
        where,
        order: [['thoi_gian', 'DESC'], ['ma_thong_bao', 'DESC']],
        limit: Number(limit),
    });
    return rows.map(row => ({
        ma_thong_bao: row.ma_thong_bao,
        loai: row.loai,
        noi_dung: row.noi_dung,
        du_lieu: row.du_lieu || null,
        ma_hoa_don: row.ma_hoa_don,
        da_doc: row.da_doc,
        thoi_gian: row.thoi_gian,
    }));
}

async function demChuaDoc(maNguoiDung) {
    return ThongBao.count({ where: { ma_nguoi_dung: maNguoiDung, da_doc: false } });
}

async function danhDauDaDoc(maThongBao, maNguoiDung) {
    const [soDong] = await ThongBao.update(
        { da_doc: true },
        { where: { ma_thong_bao: maThongBao, ma_nguoi_dung: maNguoiDung } }
    );
    return soDong > 0;
}

async function danhDauTatCaDaDoc(maNguoiDung) {
    await ThongBao.update(
        { da_doc: true },
        { where: { ma_nguoi_dung: maNguoiDung, da_doc: false } }
    );
}

async function xoa(maThongBao, maNguoiDung) {
    const soDong = await ThongBao.destroy({
        where: { ma_thong_bao: maThongBao, ma_nguoi_dung: maNguoiDung },
    });
    return soDong > 0;
}

// Đơn này đã có thông báo loại X chưa (không dùng để suy ra trạng thái đơn).
async function daCoThongBaoDon(maHoaDon, loai) {
    const so = await ThongBao.count({ where: { ma_hoa_don: maHoaDon, loai } });
    return so > 0;
}

// ── Nội dung ──
function noiDungDangGiao(maHienThi, dsSanPham, laHoaTuoi = false) {
    const canhBaoHoaTuoi = laHoaTuoi
        ? ' Vì đơn hàng là hoa tươi, shop sẽ giao trong vòng 3 giờ tới.'
        : '';
    return `🚚 Đơn hàng ${maHienThi} gồm ${dsSanPham} đã được shipper nhận và đang trên đường giao đến bạn!${canhBaoHoaTuoi} Vui lòng chú ý điện thoại.`;
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

function noiDungPhatTre(maHienThi, tienPhat) {
    const tien = Number(tienPhat).toLocaleString('vi-VN');
    return `⚠️ Đơn hàng ${maHienThi} quá 3 tiếng chưa giao thành công. Bạn đã bị trừ thêm ${tien} đ vào khoản cần nộp vì giao trễ.`;
}

module.exports = {
    LOAI,
    tao,
    danhSach,
    demChuaDoc,
    danhDauDaDoc,
    danhDauTatCaDaDoc,
    xoa,
    daCoThongBaoDon,
    noiDungDangGiao,
    noiDungDenNoi,
    noiDungHoanThanh,
    noiDungXinLoiTre,
    noiDungPhatTre,
};