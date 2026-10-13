// src/services/orders/order_helper.js
// Utility dùng nội bộ trong tab Orders: include, tìm đơn, format tóm tắt, phân trang.
// KHÔNG phải service — chỉ là helper.

const HoaDon = require('../../models/HoaDon');
const KhachHang = require('../../models/KhachHang');
const SanPham = require('../../models/SanPham');
const ChiTietHoaDon = require('../../models/ChiTietHoaDon');
const { taoLoi } = require('../../utils/loi');

const NHAN_TRANG_THAI = {
    CONFIRMED: 'Chờ lấy',
    SHIPPING: 'Đang giao',
    DELIVERED: 'Đã giao',
};

const INCLUDE_CHI_TIET = {
    model: ChiTietHoaDon,
    as: 'chiTietHoaDon',
    include: [{
        model: SanPham,
        as: 'sanPham',
        attributes: ['ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'hinh_anh'],
    }],
};

const INCLUDE_KHACH = {
    model: KhachHang,
    as: 'khachHang',
    attributes: ['ma_khach_hang', 'ten_khach_hang', 'so_dien_thoai'],
};

// Tìm đơn theo mã VÀ phải thuộc shipper này, không có thì ném 404
async function timDonHoacNemLoi(maNhanVien, maHoaDon) {
    const hoaDon = await HoaDon.findByIdAndShipper(maHoaDon, maNhanVien);
    if (!hoaDon) {
        throw taoLoi(404, 'ORDER_NOT_FOUND', 'Không tìm thấy đơn hàng hoặc đơn không thuộc về bạn.');
    }
    return hoaDon;
}

// "Hoa hồng (×2), Bó lan (×1)"
function moTaSanPham(dsChiTiet) {
    return dsChiTiet.map((ct) => `${ct.tenHienThi} (×${ct.so_luong})`).join(', ');
}

// Cần đã nạp chiTietHoaDon -> sanPham
function coHoaTuoi(dh) {
    return (dh.chiTietHoaDon || []).some((ct) => ct.sanPham?.laHoaTuoi);
}

// Thông tin thanh toán của đơn: shipper cần thu tiền mặt hay không
function thongTinThanhToan(dh) {
    return {
        phuong_thuc: dh.phuong_thuc_thanh_toan,
        trang_thai: dh.trang_thai_thanh_toan,
        la_cod: dh.phuong_thuc_thanh_toan === 'COD',
        can_thu_tien_mat: dh.canThuTienMat,
        so_tien_can_thu: dh.canThuTienMat ? Number(dh.tong_tien) : 0,
    };
}

// Bản gọn của 1 đơn để hiện trong danh sách
function tomTatDon(dh) {
    return {
        ma_hoa_don: dh.ma_hoa_don,
        ma_hien_thi: dh.maHienThi,
        trang_thai: dh.trang_thai,
        nhan_trang_thai: NHAN_TRANG_THAI[dh.trang_thai] || dh.trang_thai,
        la_hoa_tuoi: coHoaTuoi(dh),
        ngay_dat: dh.ngay_dat,
        ngay_bat_giao: dh.ngay_bat_giao,
        tong_tien: Number(dh.tong_tien),
        dia_chi_giao: dh.dia_chi_giao,
        khach_hang: dh.khachHang && {
            ten: dh.khachHang.ten_khach_hang,
            so_dien_thoai: dh.so_dien_thoai || dh.khachHang.so_dien_thoai,
        },
        thanh_toan: thongTinThanhToan(dh),
    };
}

function phanTrang(page, limit, macDinh = 20) {
    const soTrang = Math.max(1, Number(page) || 1);
    const soDongMoiTrang = Math.min(100, Math.max(1, Number(limit) || macDinh));
    return { soTrang, soDongMoiTrang, offset: (soTrang - 1) * soDongMoiTrang };
}

module.exports = {
    NHAN_TRANG_THAI,
    INCLUDE_CHI_TIET,
    INCLUDE_KHACH,
    timDonHoacNemLoi,
    moTaSanPham,
    coHoaTuoi,
    thongTinThanhToan,
    tomTatDon,
    phanTrang,
};