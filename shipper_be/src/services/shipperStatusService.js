// src/services/shipperStatusService.js
const { Op } = require('sequelize');
const sequelize = require('../config/database');
const NhanVien = require('../models/NhanVien');
const HoaDon = require('../models/HoaDon');
const { taoLoi } = require('../utils/loi');

const GIOI_HAN_DON = 10;

// Đếm số đơn shipper đang thực sự giữ, dựa vào bảng hoa_don.
// Không tin cột nhan_vien.so_don_hien_tai (có thể lệch nếu PHP quên +1/-1).
async function demDonDangGiu(maNhanVien) {
    return HoaDon.count({
        where: {
            ma_nhan_vien_giao: maNhanVien,
            trang_thai: { [Op.in]: ['CONFIRMED', 'SHIPPING'] },
        },
    });
}

// Biến row DB thành object gọn để trả cho app.
// Giờ là async vì phải COUNT từ hoa_don.
async function buildStatusResponse(nhanVien) {
    const soDon = await demDonDangGiu(nhanVien.ma_nhan_vien);
    const isFull = soDon >= GIOI_HAN_DON;
    const coTheNhanDon = nhanVien.trang_thai_hoat_dong === 'ONLINE' && !isFull;

    return {
        ma_nhan_vien: nhanVien.ma_nhan_vien,
        ten_nhan_vien: nhanVien.ten_nhan_vien,
        trang_thai_hoat_dong: nhanVien.trang_thai_hoat_dong,
        so_don_hien_tai: soDon,
        gioi_han_don: GIOI_HAN_DON,
        is_full: isFull,
        co_the_nhan_don: coTheNhanDon,
    };
}

async function timShipperHoacNemLoi(maNhanVien) {
    const shipper = await NhanVien.findById(maNhanVien);
    if (!shipper) {
        throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    }
    return shipper;
}

async function getStatus(maNhanVien) {
    const shipper = await timShipperHoacNemLoi(maNhanVien);
    return buildStatusResponse(shipper);
}

// Nút gạt ONLINE / OFFLINE
async function setOnlineStatus(maNhanVien, trangThaiMoi) {
    if (trangThaiMoi !== 'ONLINE' && trangThaiMoi !== 'OFFLINE') {
        throw taoLoi(400, 'INVALID_STATUS', 'Trạng thái chỉ được là ONLINE hoặc OFFLINE.');
    }

    const shipper = await timShipperHoacNemLoi(maNhanVien);
    const trangThaiCu = shipper.trang_thai_hoat_dong;

    await shipper.update({ trang_thai_hoat_dong: trangThaiMoi });

    console.log(`[Status] Shipper ${maNhanVien}: ${trangThaiCu} -> ${trangThaiMoi}`);
    return buildStatusResponse(shipper);
}

// ── Phía quản lý đơn (admin/nhân viên phân công) ──

async function canReceiveNewOrder(maNhanVien) {
    const shipper = await timShipperHoacNemLoi(maNhanVien);
    const status = await buildStatusResponse(shipper);
    return status.co_the_nhan_don;
}

// Đếm thực tế từ hoa_don — dùng cho nơi khác cần check giới hạn
async function demDonHienTai(maNhanVien) {
    return demDonDangGiu(maNhanVien);
}

module.exports = {
    GIOI_HAN_DON,
    demDonDangGiu,
    demDonHienTai,
    getStatus,
    setOnlineStatus,
    canReceiveNewOrder,
    buildStatusResponse,
};