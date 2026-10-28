// src/services/history/order_history/history_list/history_list_services.js
// Danh sách đơn đã giao của shipper: tổng số + phân trang 10/trang.
// Nhận query filter từ history_filter để lọc ngày/tháng/khoảng/mã.

const { Op, fn, col } = require('sequelize');
const HoaDon = require('../../../../models/HoaDon');
const KhachHang = require('../../../../models/KhachHang');
const { phanTrang } = require('../../../orders/order_helper');
const { buildFilter } = require('../history_filter/history_filter_services');
const { timShipperHoacNemLoi } = require('../../../home/shipper_status/shipper_status_services');

const SO_DON_MOI_TRANG = 10;

// Chỉ lấy đơn hủy do SHIPPER hoặc CUSTOMER; đơn bị từ chối/hủy thông thường thì bỏ qua.
const NGUON_HUY_HIEN_TRONG_LICH_SU = ['SHIPPER', 'CUSTOMER'];

const INCLUDE_KHACH = {
    model: KhachHang,
    as: 'khachHang',
    attributes: ['ma_khach_hang', 'ten_khach_hang'],
};

// Bản gọn cho card lịch sử (DELIVERED hoặc CANCELLED do SHIPPER/CUSTOMER)
function tomTatLichSu(dh) {
    const daHuy = dh.trang_thai === 'CANCELLED';
    return {
        ma_hoa_don: dh.ma_hoa_don,
        ma_hien_thi: dh.maHienThi,
        trang_thai: dh.trang_thai,
        nhan_trang_thai: daHuy ? 'Đã hủy' : 'Đã giao',
        ngay_giao: dh.ngay_giao,
        thoi_diem_huy: dh.thoi_diem_huy || null,
        ngay_hoan_tat: daHuy ? dh.thoi_diem_huy : dh.ngay_giao, // mốc thời gian hiển thị chung
        nguon_huy: daHuy ? dh.nguon_huy : null,
        nhan_nguon_huy: daHuy
            ? (dh.nguon_huy === 'SHIPPER' ? 'Lỗi phía shipper' : 'Lỗi phía khách hàng')
            : null,
        ly_do_huy: daHuy ? dh.ly_do_huy : null,
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

// Tổng số đơn hủy do shipper/khách (hiển thị riêng nếu cần)
async function demTongDaHuy(maNhanVien) {
    return HoaDon.count({
        where: {
            ma_nhan_vien_giao: maNhanVien,
            trang_thai: 'CANCELLED',
            nguon_huy: { [Op.in]: NGUON_HUY_HIEN_TRONG_LICH_SU },
        },
    });
}

// Điều kiện lịch sử: DELIVERED, hoặc CANCELLED với nguon_huy thuộc SHIPPER/CUSTOMER.
// buildFilter() lọc ngày trên ngay_giao; đơn hủy không có ngay_giao nên điều kiện ngày
// được áp sang thoi_diem_huy cho nhánh CANCELLED.
function buildWhereLichSu(maNhanVien, boLoc) {
    const { ngay_giao: locNgay, ...locKhac } = buildFilter(boLoc) || {};
    const loai = boLoc?.loai || 'ALL';
    const allowed = ['ALL', 'DELIVERED', 'CANCELLED_SHIPPER', 'CANCELLED_CUSTOMER'];
    if (!allowed.includes(loai)) {
        const err = new Error('loai không hợp lệ.');
        err.status = 400;
        err.code = 'INVALID_HISTORY_TYPE';
        throw err;
    }
    const delivered = { trang_thai: 'DELIVERED', ...(locNgay ? { ngay_giao: locNgay } : {}) };
    const cancelled = {
        trang_thai: 'CANCELLED',
        nguon_huy: loai === 'CANCELLED_SHIPPER'
            ? 'SHIPPER'
            : loai === 'CANCELLED_CUSTOMER' ? 'CUSTOMER' : { [Op.in]: NGUON_HUY_HIEN_TRONG_LICH_SU },
        ...(locNgay ? { thoi_diem_huy: locNgay } : {}),
    };
    const trangThaiFilter = loai === 'DELIVERED'
        ? delivered
        : (loai === 'CANCELLED_SHIPPER' || loai === 'CANCELLED_CUSTOMER')
            ? cancelled
            : { [Op.or]: [delivered, cancelled] };

    return {
        [Op.and]: [
            { ma_nhan_vien_giao: maNhanVien },
            locKhac,
            trangThaiFilter,
        ],
    };
}

// Danh sách có phân trang + filter
async function getHistory(maNhanVien, { page, limit, ...boLoc } = {}) {
    await timShipperHoacNemLoi(maNhanVien);
    const { soTrang, soDongMoiTrang, offset } = phanTrang(page, limit, SO_DON_MOI_TRANG);

    const where = buildWhereLichSu(maNhanVien, boLoc);

    const { rows, count } = await HoaDon.findAndCountAll({
        where,
        include: [INCLUDE_KHACH],
        order: [[fn('COALESCE', col('HoaDon.ngay_giao'), col('HoaDon.thoi_diem_huy')), 'DESC']],
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
    NGUON_HUY_HIEN_TRONG_LICH_SU,
    demTongDaGiao,
    demTongDaHuy,
    buildWhereLichSu,
    getHistory,
};
