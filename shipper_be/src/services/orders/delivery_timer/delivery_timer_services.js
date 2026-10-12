// src/services/orders/delivery_timer/delivery_timer_services.js
// Đồng hồ 3h cho đơn hoa tươi — CHỈ ĐỌC, không ghi DB.
// Tách khỏi delivery_penalty để endpoint GET /timeout không vô tình ghi dữ liệu.

const HoaDon = require('../../../models/HoaDon');
const ChiTietHoaDon = require('../../../models/ChiTietHoaDon');
const { taoLoi, parseMaHoaDon } = require('../../../utils/loi');
const { timDonHoacNemLoi } = require('../order_helper');

const THOI_GIAN_TOI_DA_GIAY = 3 * 60 * 60;   // hạn giao hoa tươi: 3 tiếng (BR-1)
const NGUONG_CANH_BAO_GIAY = 30 * 60;        // còn dưới 30 phút -> app đổi sang 🚨
const GIOI_HAN_GHI_NHAN_GIAY = 24 * 60 * 60; // A7: quá 24h thì không còn đồng hồ/phạt

// Đơn có hoa tươi nếu có ít nhất 1 sản phẩm là hoa tươi (A6)
function donLaHoaTuoi(dsChiTiet) {
    return dsChiTiet.some((ct) => ct.sanPham?.laHoaTuoi);
}

// Tính trạng thái đồng hồ từ dữ liệu đơn — hàm thuần, dùng chung với getOrderDetail
function tinhDongHo(hoaDon, laHoaTuoi) {
    const daPhat = Boolean(hoaDon.da_phat_tre);

    const khongCoDongHo = (lyDo) => ({
        co_dong_ho: false,
        ly_do: lyDo,
        la_hoa_tuoi: laHoaTuoi,
        elapsed: 0,
        remaining: 0,
        is_timeout: false,
        canh_bao: false,
        da_phat_tre: daPhat,
    });

    if (!laHoaTuoi) return khongCoDongHo('KHONG_PHAI_HOA_TUOI');
    if (hoaDon.trang_thai !== 'SHIPPING' || !hoaDon.ngay_bat_giao) {
        return khongCoDongHo('KHONG_DANG_GIAO');
    }

    const elapsed = Math.max(
        0,
        Math.floor((Date.now() - new Date(hoaDon.ngay_bat_giao).getTime()) / 1000)
    );
    if (elapsed > GIOI_HAN_GHI_NHAN_GIAY) return khongCoDongHo('QUA_24H');

    const remaining = Math.max(0, THOI_GIAN_TOI_DA_GIAY - elapsed);
    return {
        co_dong_ho: true,
        ly_do: null,
        la_hoa_tuoi: true,
        elapsed,
        remaining,
        is_timeout: elapsed >= THOI_GIAN_TOI_DA_GIAY,
        canh_bao: remaining > 0 && remaining < NGUONG_CANH_BAO_GIAY,
        da_phat_tre: daPhat,
    };
}

// Endpoint app poll định kỳ — chỉ đọc
async function getTimeoutRemaining(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);
    const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);

    return {
        ma_hoa_don: maHoaDon,
        thoi_gian_toi_da_giay: THOI_GIAN_TOI_DA_GIAY,
        ...tinhDongHo(hoaDon, donLaHoaTuoi(dsChiTiet)),
    };
}

module.exports = {
    THOI_GIAN_TOI_DA_GIAY,
    NGUONG_CANH_BAO_GIAY,
    GIOI_HAN_GHI_NHAN_GIAY,
    donLaHoaTuoi,
    tinhDongHo,
    getTimeoutRemaining,
};