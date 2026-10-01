// src/services/shipperStatusService.js
const sequelize = require('../config/database');
const NhanVien = require('../models/NhanVien');
const { taoLoi } = require('../utils/loi');

const GIOI_HAN_DON = 10;

// Biến row DB thành object gọn để trả cho app
function buildStatusResponse(nhanVien) {
    const isFull = nhanVien.so_don_hien_tai >= GIOI_HAN_DON;
    const coTheNhanDon = nhanVien.trang_thai_hoat_dong === 'ONLINE' && !isFull;

    return {
        ma_nhan_vien: nhanVien.ma_nhan_vien,
        ten_nhan_vien: nhanVien.ten_nhan_vien,
        trang_thai_hoat_dong: nhanVien.trang_thai_hoat_dong,
        so_don_hien_tai: nhanVien.so_don_hien_tai,
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

    // Đang có đơn mà chuyển OFFLINE vẫn được: đơn hiện tại không bị huỷ, chỉ bị chặn nhận đơn mới.
    await shipper.update({ trang_thai_hoat_dong: trangThaiMoi });

    console.log(`[Status] Shipper ${maNhanVien}: ${trangThaiCu} -> ${trangThaiMoi}`);
    return buildStatusResponse(shipper);
}

// ── Phía quản lý đơn (admin/nhân viên phân công) ──

async function canReceiveNewOrder(maNhanVien) {
    const shipper = await timShipperHoacNemLoi(maNhanVien);
    return buildStatusResponse(shipper).co_the_nhan_don;
}

// Gọi khi nhân viên gán đơn thành công cho shipper: số đơn +1
async function incrementOrderCount(maNhanVien) {
    const shipper = await sequelize.transaction(async (t) => {
        // Khoá dòng shipper (SELECT ... FOR UPDATE) để 2 nhân viên không gán cùng lúc
        const nv = await NhanVien.findByPk(maNhanVien, { transaction: t, lock: t.LOCK.UPDATE });
        if (!nv) throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');

        // Kiểm tra lại sau khi khoá (chống race condition)
        if (nv.trang_thai_hoat_dong !== 'ONLINE') {
            throw taoLoi(409, 'SHIPPER_OFFLINE', 'Shipper đang OFFLINE, không thể chỉ định đơn.');
        }
        if (nv.so_don_hien_tai >= GIOI_HAN_DON) {
            throw taoLoi(409, 'SHIPPER_FULL', `Shipper đã đủ ${GIOI_HAN_DON}/${GIOI_HAN_DON} đơn.`);
        }

        await nv.update({ so_don_hien_tai: nv.so_don_hien_tai + 1 }, { transaction: t });
        return nv;
    });

    console.log(`[Status] Shipper ${maNhanVien}: số đơn tăng lên ${shipper.so_don_hien_tai}`);
    return buildStatusResponse(shipper);
}

// ── Phía shipper ──

// Gọi khi đơn rời khỏi tay shipper (giao thành công / thất bại): số đơn -1.
// Truyền `transaction` nếu muốn trừ cùng transaction với việc cập nhật đơn
// (đơn đổi trạng thái và số đơn giảm hoặc cùng thành công, hoặc cùng rollback).
async function decrementOrderCount(maNhanVien, transaction = null) {
    const lam = async (t) => {
        const nv = await NhanVien.findByPk(maNhanVien, { transaction: t, lock: t.LOCK.UPDATE });
        if (!nv) throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');

        if (nv.so_don_hien_tai <= 0) {
            console.warn(`[Status] Shipper ${maNhanVien}: số đơn đã là 0, không giảm thêm`);
        } else {
            await nv.update({ so_don_hien_tai: nv.so_don_hien_tai - 1 }, { transaction: t });
        }
        return nv;
    };

    const shipper = transaction ? await lam(transaction) : await sequelize.transaction(lam);

    // Không đụng tới ONLINE/OFFLINE: từ 10 -> 9 là tự nhận đơn được lại
    console.log(`[Status] Shipper ${maNhanVien}: số đơn còn ${shipper.so_don_hien_tai}`);
    return buildStatusResponse(shipper);
}

module.exports = {
    GIOI_HAN_DON,
    getStatus,
    setOnlineStatus,
    canReceiveNewOrder,
    incrementOrderCount,
    decrementOrderCount,
};