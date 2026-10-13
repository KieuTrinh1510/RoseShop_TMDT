// src/services/orders/order_list/order_list_services.js
// Danh sách đơn được gán cho shipper (Tất cả / Chờ lấy / Đang giao).

const { Op } = require('sequelize');
const HoaDon = require('../../../models/HoaDon');
const {
    INCLUDE_KHACH,
    INCLUDE_CHI_TIET,
    tomTatDon,
    phanTrang,
} = require('../order_helper');
const { timShipperHoacNemLoi } = require('../../home/shipper_status/shipper_status_services');

async function getAssignedOrders(maNhanVien, { trangThai, page, limit } = {}) {
    await timShipperHoacNemLoi(maNhanVien);
    const { soTrang, soDongMoiTrang, offset } = phanTrang(page, limit);

    const where = {
        ma_nhan_vien_giao: maNhanVien,
        trang_thai: trangThai ? trangThai : { [Op.in]: ['CONFIRMED', 'SHIPPING'] },
    };

    const { rows, count } = await HoaDon.findAndCountAll({
        where,
        include: [INCLUDE_KHACH, INCLUDE_CHI_TIET],
        order: [['ngay_dat', 'DESC']],
        limit: soDongMoiTrang,
        offset,
        distinct: true, // có include hasMany: không có dòng này count đếm theo số dòng join
    });

    return {
        items: rows.map(tomTatDon),
        total: count,
        page: soTrang,
        limit: soDongMoiTrang,
        total_pages: Math.ceil(count / soDongMoiTrang),
    };
}

module.exports = { getAssignedOrders };