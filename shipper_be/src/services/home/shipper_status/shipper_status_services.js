// src/services/home/shipper_status/shipper_status_services.js
// Nút gạt ONLINE / OFFLINE ở tab Trang chủ.
// Import logic đếm đơn từ order_count để tránh viết trùng câu COUNT.

const NhanVien = require('../../../models/NhanVien');
const { taoLoi } = require('../../../utils/loi');
const orderCountService = require('../order_count/shipper_order_count_services');

async function timShipperHoacNemLoi(maNhanVien) {
    const shipper = await NhanVien.findById(maNhanVien);
    if (!shipper) {
        throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    }
    return shipper;
}

// Trả về object trạng thái gọn cho app
async function buildStatusResponse(nhanVien) {
    const soDon = await orderCountService.demDonDangGiu(nhanVien.ma_nhan_vien);
    const isFull = soDon >= orderCountService.GIOI_HAN_DON;
    const coTheNhanDon = nhanVien.trang_thai_hoat_dong === 'ONLINE' && !isFull;

    return {
        ma_nhan_vien: nhanVien.ma_nhan_vien,
        ten_nhan_vien: nhanVien.ten_nhan_vien,
        trang_thai_hoat_dong: nhanVien.trang_thai_hoat_dong,
        so_don_hien_tai: soDon,
        gioi_han_don: orderCountService.GIOI_HAN_DON,
        is_full: isFull,
        co_the_nhan_don: coTheNhanDon,
    };
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

module.exports = {
    timShipperHoacNemLoi,
    buildStatusResponse,
    getStatus,
    setOnlineStatus,
};