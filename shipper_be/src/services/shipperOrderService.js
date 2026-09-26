// src/services/shipperOrderService.js
// LƯU Ý: mình không có file gốc của service này (bạn chưa gửi), đây là bản
// viết tạm dựa theo các hàm sẵn có trong model HoaDon/NhanVien, đủ để test
// route dashboard/orders/history. Sửa lại logic cho khớp nghiệp vụ thật của bạn.

const { Op } = require('sequelize');
const HoaDon = require('../models/HoaDon');
const NhanVien = require('../models/NhanVien');

function taoLoi(status, code, message) {
    const err = new Error(message);
    err.status = status;
    err.code = code;
    return err;
}

async function timShipperHoacNemLoi(maNhanVien) {
    const shipper = await NhanVien.findById(maNhanVien);
    if (!shipper) throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    return shipper;
}

// Tổng quan cho màn hình chính của shipper
async function getDashboard(maNhanVien) {
    const shipper = await timShipperHoacNemLoi(maNhanVien);
    const donCanGiao = await HoaDon.findDonCanGiao(maNhanVien);
    const { tongTien, soDon } = await HoaDon.tinhTienCanNop(maNhanVien);
    const tongDoanhThu = await HoaDon.tinhTongDoanhThu(maNhanVien);

    return {
        ma_nhan_vien: shipper.ma_nhan_vien,
        ten_nhan_vien: shipper.ten_nhan_vien,
        trang_thai_hoat_dong: shipper.trang_thai_hoat_dong,
        so_don_hien_tai: shipper.so_don_hien_tai,
        so_don_dang_giao: donCanGiao.length,
        tien_cod_can_nop: tongTien,
        so_don_cod_da_giao: soDon,
        tong_doanh_thu_da_giao: tongDoanhThu,
        don_gan_day: donCanGiao.slice(0, 5),
    };
}

// Danh sách đơn được giao, lọc theo trạng thái + phân trang
async function getAssignedOrders(maNhanVien, { trangThai, page, limit } = {}) {
    await timShipperHoacNemLoi(maNhanVien);

    const soTrang = Math.max(1, Number(page) || 1);
    const soDongMoiTrang = Math.min(100, Math.max(1, Number(limit) || 20));

    const where = { ma_nhan_vien_giao: maNhanVien };
    if (trangThai) {
        where.trang_thai = trangThai;
    } else {
        where.trang_thai = { [Op.in]: ['CONFIRMED', 'SHIPPING'] };
    }

    const { rows, count } = await HoaDon.findAndCountAll({
        where,
        order: [['ngay_dat', 'DESC']],
        limit: soDongMoiTrang,
        offset: (soTrang - 1) * soDongMoiTrang,
    });

    return {
        items: rows,
        total: count,
        page: soTrang,
        limit: soDongMoiTrang,
        total_pages: Math.ceil(count / soDongMoiTrang),
    };
}

// Chi tiết 1 đơn, chỉ cho xem nếu đúng là đơn của shipper này
async function getOrderDetail(maNhanVien, maHoaDon) {
    const donHang = await HoaDon.findByIdAndShipper(maHoaDon, maNhanVien);
    if (!donHang) {
        throw taoLoi(404, 'ORDER_NOT_FOUND', 'Không tìm thấy đơn hoặc đơn không thuộc về bạn.');
    }
    return donHang;
}

// Lịch sử đơn đã giao, phân trang
async function getOrderHistory(maNhanVien, { page, limit } = {}) {
    await timShipperHoacNemLoi(maNhanVien);

    const soTrang = Math.max(1, Number(page) || 1);
    const soDongMoiTrang = Math.min(100, Math.max(1, Number(limit) || 20));

    const [items, total] = await Promise.all([
        HoaDon.findLichSuGiao(maNhanVien, soDongMoiTrang, (soTrang - 1) * soDongMoiTrang),
        HoaDon.countLichSuGiao(maNhanVien),
    ]);

    return {
        items,
        total,
        page: soTrang,
        limit: soDongMoiTrang,
        total_pages: Math.ceil(total / soDongMoiTrang),
    };
}

module.exports = {
    getDashboard,
    getAssignedOrders,
    getOrderDetail,
    getOrderHistory,
};