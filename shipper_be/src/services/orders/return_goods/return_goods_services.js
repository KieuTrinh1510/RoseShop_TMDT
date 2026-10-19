// src/services/orders/return_goods/return_goods_services.js
// Dữ liệu hàng hoàn của 1 đơn đã hủy — dùng cho chi tiết đơn / chi tiết lịch sử.

'use strict';

const HangHoan = require('../../../models/HangHoan');
const ChiTietHangHoan = require('../../../models/ChiTietHangHoan');
const SanPham = require('../../../models/SanPham');
const BoHoa = require('../../../models/BoHoa');

const NHAN_TRANG_THAI_HANG_HOAN = {
    CHO_MANG_VE_KHO: 'Chờ mang về kho',
    DA_NHAN_TAI_KHO: 'Đã nhận tại kho',
    DANG_KIEM_TRA: 'Đang kiểm tra',
    DA_KIEM_TRA: 'Đã kiểm tra',
};

/**
 * @returns {Promise<null | {
 *   ma_hang_hoan, trang_thai, nhan_trang_thai, da_kiem_tra, nhan_hien_thi,
 *   thoi_diem_kiem_tra, ghi_chu, ket_qua_kiem_tra: Array
 * }>}
 */
async function layHangHoan(maHoaDon) {
    const hangHoan = await HangHoan.findOne({
        where: { ma_hoa_don: maHoaDon },
        include: [{
            model: ChiTietHangHoan,
            as: 'chiTiet',
            include: [
                { model: SanPham, as: 'sanPham', attributes: ['ma_san_pham', 'ten_san_pham'], required: false },
                { model: BoHoa, as: 'boHoa', required: false },
            ],
        }],
        order: [[{ model: ChiTietHangHoan, as: 'chiTiet' }, 'ma_chi_tiet_hang_hoan', 'ASC']],
    });

    if (!hangHoan) return null;

    const daKiemTra = hangHoan.daKiemTra;
    return {
        ma_hang_hoan: hangHoan.ma_hang_hoan,
        trang_thai: hangHoan.trang_thai,
        nhan_trang_thai: NHAN_TRANG_THAI_HANG_HOAN[hangHoan.trang_thai] || hangHoan.trang_thai,
        da_kiem_tra: daKiemTra,
        nhan_hien_thi: daKiemTra ? 'Hàng hoàn: Đã kiểm tra' : 'Chưa kiểm tra hàng hoàn',
        thoi_diem_kiem_tra: hangHoan.thoi_diem_kiem_tra || null,
        ghi_chu: hangHoan.ghi_chu || null,
        // Chỉ trả bảng kết quả khi admin đã kiểm tra xong
        ket_qua_kiem_tra: daKiemTra
            ? (hangHoan.chiTiet || []).map((ct) => ct.toKetQuaKiemTra())
            : [],
    };
}

module.exports = { NHAN_TRANG_THAI_HANG_HOAN, layHangHoan };