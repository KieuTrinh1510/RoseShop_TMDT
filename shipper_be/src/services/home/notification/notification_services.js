// src/services/home/notification/notification_services.js
// Hộp thư thông báo của SHIPPER (chuông ở tab Trang chủ).
// Chỉ phục vụ shipper — thông báo cho khách nằm ở orders/customer_notification/.
// Shipper nhận: thông báo phạt giao trễ (penalty), thông báo hệ thống (info).

const ThongBao = require('../../../models/ThongBao');
const LOAI = require('../../shared/notification_types');

// ── Đọc ──
async function danhSach(maNguoiDung, { loai, limit = 30 } = {}) {
    const where = { ma_nguoi_dung: maNguoiDung };
    if (loai && loai !== 'all') where.loai = loai;

    const rows = await ThongBao.findAll({
        where,
        order: [['thoi_gian', 'DESC'], ['ma_thong_bao', 'DESC']],
        limit: Number(limit),
    });

    return rows.map((row) => ({
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
    return ThongBao.count({
        where: { ma_nguoi_dung: maNguoiDung, da_doc: false },
    });
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

// ── Ghi (cho shipper) ──

// Tạo thông báo phạt giao trễ — gọi từ orders/delivery_penalty/
async function taoThongBaoPhat(maNhanVien, maHoaDon, maHienThi, tienPhat, options = {}) {
    const tien = Number(tienPhat).toLocaleString('vi-VN');
    const noiDung =
        `⚠️ Đơn hàng ${maHienThi} quá 3 tiếng chưa giao thành công. ` +
        `Bạn đã bị trừ thêm ${tien} đ vào khoản cần nộp vì giao trễ.`;

    return ThongBao.create(
        {
            ma_nguoi_dung: maNhanVien,
            loai: LOAI.PENALTY,
            noi_dung: noiDung,
            ma_hoa_don: maHoaDon,
            da_doc: false,
            thoi_gian: new Date(),
        },
        options
    );
}

module.exports = {
    danhSach,
    demChuaDoc,
    danhDauDaDoc,
    danhDauTatCaDaDoc,
    xoa,
    taoThongBaoPhat,
};