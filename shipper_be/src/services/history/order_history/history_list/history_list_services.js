// src/services/history/order_history/history_list/history_list_services.js
// Danh sách đơn đã giao của shipper: tổng số + phân trang 10/trang.
// Nhận query filter từ history_filter để lọc ngày/tháng/khoảng/mã.

const HoaDon = require('../../../../models/HoaDon');
const KhachHang = require('../../../../models/KhachHang');
const { phanTrang } = require('../../../orders/order_helper');
const { buildFilter } = require('../history_filter/history_filter_services');
const { timShipperHoacNemLoi } = require('../../../home/shipper_status/shipper_status_services');

const SO_DON_MOI_TRANG = 10;

const INCLUDE_KHACH = {
    model: KhachHang,
    as: 'khachHang',
    attributes: ['ma_khach_hang', 'ten_khach_hang'],
};

// Bản gọn cho card lịch sử
function tomTatLichSu(dh) {
    return {
        ma_hoa_don: dh.ma_hoa_don,
        ma_hien_thi: dh.maHienThi,
        ngay_giao: dh.ngay_giao,
        tong_tien: Number(dh.tong_tien),
        khach_hang: dh.khachHang && {
            ten: dh.khachHang.ten_khach_hang,
        },
    };
}

// Tổng số đơn đã giao (không filter) — dùng hiển thị "Tổng 40"
async function demTongDaGiao(maNhanVien) {
    return HoaDon.count({
        where: { ma_nhan_vien_giao: maNhanVien, trang_thai: 'DELIVERED' },
    });
}

// Danh sách có phân trang + filter
async function getHistory(maNhanVien, { page, limit, ...boLoc } = {}) {
    await timShipperHoacNemLoi(maNhanVien);
    const { soTrang, soDongMoiTrang, offset } = phanTrang(page, limit, SO_DON_MOI_TRANG);

    const where = {
        ma_nhan_vien_giao: maNhanVien,
        trang_thai: 'DELIVERED',
        ...buildFilter(boLoc),
    };

    const { rows, count } = await HoaDon.findAndCountAll({
        where,
        include: [INCLUDE_KHACH],
        order: [['ngay_giao', 'DESC']],
        limit: soDongMoiTrang,
        offset,
    });

    return {
        items: rows.map(tomTatLichSu),
        total: count,
        page: soTrang,
        limit: soDongMoiTrang,
        total_pages: Math.ceil(count / soDongMoiTrang),
    };
}

module.exports = {
    SO_DON_MOI_TRANG,
    demTongDaGiao,
    getHistory,
};