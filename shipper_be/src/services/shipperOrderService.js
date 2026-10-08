// src/services/shipperOrderService.js
// Xem đơn: dashboard, danh sách đơn được gán, chi tiết đơn, lịch sử.

const { Op } = require('sequelize');
const HoaDon = require('../models/HoaDon');
const NhanVien = require('../models/NhanVien');
const KhachHang = require('../models/KhachHang');
const SanPham = require('../models/SanPham');
// Chỉ cần require để đăng ký quan hệ HoaDon.hasMany(ChiTietHoaDon, as: 'chiTietHoaDon')
const ChiTietHoaDon = require('../models/ChiTietHoaDon');
const deliveryService = require('./shipperDeliveryService');
const { taoLoi, parseMaHoaDon } = require('../utils/loi');

const NHAN_TRANG_THAI = {
    CONFIRMED: 'Chờ lấy',
    SHIPPING: 'Đang giao',
    DELIVERED: 'Đã giao',
};

// include dùng chung để biết đơn có hoa tươi hay không
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

async function timShipperHoacNemLoi(maNhanVien) {
    const shipper = await NhanVien.findById(maNhanVien);
    if (!shipper) throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    return shipper;
}

function phanTrang(page, limit) {
    const soTrang = Math.max(1, Number(page) || 1);
    const soDongMoiTrang = Math.min(100, Math.max(1, Number(limit) || 20));
    return { soTrang, soDongMoiTrang, offset: (soTrang - 1) * soDongMoiTrang };
}

// Thông tin thanh toán của đơn: shipper cần thu tiền mặt hay không
function thongTinThanhToan(dh) {
    const laCod = dh.phuong_thuc_thanh_toan === 'COD';
    return {
        phuong_thuc: dh.phuong_thuc_thanh_toan,
        trang_thai: dh.trang_thai_thanh_toan,
        la_cod: laCod,
        can_thu_tien_mat: dh.canThuTienMat,
        so_tien_can_thu: dh.canThuTienMat ? Number(dh.tong_tien) : 0,
    };
}

// Cần đã nạp chiTietHoaDon -> sanPham (nếu chưa nạp thì coi như không phải hoa tươi)
function coHoaTuoi(dh) {
    return (dh.chiTietHoaDon || []).some((ct) => ct.sanPham?.laHoaTuoi);
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
            // Ưu tiên SĐT ghi trên đơn (số shipper cần gọi), thiếu thì dùng SĐT tài khoản khách
            so_dien_thoai: dh.so_dien_thoai || dh.khachHang.so_dien_thoai,
        },
        thanh_toan: thongTinThanhToan(dh),
    };
}

// Tổng quan
async function getDashboard(maNhanVien) {
    const shipper = await timShipperHoacNemLoi(maNhanVien);
    // Lưu ý: HoaDon.findDonCanGiao cần include thêm chiTietHoaDon -> sanPham thì la_hoa_tuoi mới đúng
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
        tien_phat: Number(shipper.tien_phat) || 0,
        don_gan_day: donCanGiao.slice(0, 5).map(tomTatDon),
    };
}

// Danh sách đơn được gán (mặc định CONFIRMED + SHIPPING)
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
        distinct: true, // có include hasMany: không có dòng này count sẽ đếm theo số dòng join
    });

    return {
        items: rows.map(tomTatDon),
        total: count,
        page: soTrang,
        limit: soDongMoiTrang,
        total_pages: Math.ceil(count / soDongMoiTrang),
    };
}

// Chi tiết 1 đơn: khách + sản phẩm + tiền + thanh toán + tiến trình + đồng hồ + nút nào được bấm
async function getOrderDetail(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);

    const dh = await HoaDon.findOne({
        where: { ma_hoa_don: maHoaDon, ma_nhan_vien_giao: maNhanVien },
        include: [INCLUDE_KHACH, INCLUDE_CHI_TIET],
    });

    if (!dh) {
        throw taoLoi(404, 'ORDER_NOT_FOUND', 'Không tìm thấy đơn hoặc đơn không thuộc về bạn.');
    }

    const laHoaTuoi = coHoaTuoi(dh);
    const daDenNoi = Boolean(dh.ngay_den_noi) || dh.trang_thai === 'DELIVERED';
    const dangGiao = dh.trang_thai === 'SHIPPING';
    const daGiao = dh.trang_thai === 'DELIVERED';

    const sanPham = (dh.chiTietHoaDon || []).map((ct) => ({
        ma_san_pham: ct.ma_san_pham,
        ten_san_pham: ct.tenHienThi,
        hinh_anh: ct.sanPham?.hinh_anh || null,
        so_luong: ct.so_luong,
        gia_ban: Number(ct.gia_ban_snapshot || 0),
        thanh_tien: ct.thanhTien,
    }));
    const tamTinh = sanPham.reduce((tong, sp) => tong + sp.thanh_tien, 0);

    return {
        ...tomTatDon(dh),
        ngay_giao: dh.ngay_giao,
        ngay_den_noi: dh.ngay_den_noi || null,
        san_pham: sanPham,

        // Tiền: phí vận chuyển luôn miễn phí
        tam_tinh: tamTinh,
        phi_van_chuyen: 0,
        tong_cong: Number(dh.tong_tien),

        // Tiến trình 4 bước cho app vẽ
        tien_trinh: [
            { key: 'CONFIRMED', label: 'Xác nhận đơn hàng', done: true },
            { key: 'SHIPPING', label: 'Đang vận chuyển', done: dangGiao || daGiao },
            { key: 'ARRIVED', label: 'Đã đến điểm giao', done: daDenNoi },
            { key: 'DELIVERED', label: 'Hoàn thành giao hàng', done: daGiao },
        ],
        da_bao_den_noi: daDenNoi,

        // Đồng hồ 3h (co_dong_ho = false thì app ẩn khung đồng hồ)
        dong_ho: {
            thoi_gian_toi_da_giay: deliveryService.THOI_GIAN_TOI_DA_GIAY,
            ...deliveryService.tinhDongHo(dh, laHoaTuoi),
        },

        // App dựa vào đây để bật/tắt nút
        hanh_dong: {
            co_the_nhan_hang: dh.trang_thai === 'CONFIRMED',
            co_the_bao_den_noi: dangGiao && !daDenNoi,
            co_the_giao_xong: dangGiao && daDenNoi,
        },
    };
}

// Lịch sử đơn đã giao
async function getOrderHistory(maNhanVien, { page, limit } = {}) {
    await timShipperHoacNemLoi(maNhanVien);
    const { soTrang, soDongMoiTrang, offset } = phanTrang(page, limit);

    const [items, total] = await Promise.all([
        HoaDon.findLichSuGiao(maNhanVien, soDongMoiTrang, offset),
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