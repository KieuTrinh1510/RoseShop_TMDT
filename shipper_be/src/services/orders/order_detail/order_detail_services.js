// src/services/orders/order_detail/order_detail_services.js
// Chi tiết 1 đơn: khách + sản phẩm + tiền + thanh toán + tiến trình + đồng hồ + nút được bấm.

const HoaDon = require('../../../models/HoaDon');
const { taoLoi, parseMaHoaDon } = require('../../../utils/loi');
const {
    INCLUDE_KHACH,
    INCLUDE_CHI_TIET,
    tomTatDon,
    coHoaTuoi,
} = require('../order_helper');
const {
    THOI_GIAN_TOI_DA_GIAY,
    tinhDongHo,
} = require('../delivery_timer/delivery_timer_services');

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
            thoi_gian_toi_da_giay: THOI_GIAN_TOI_DA_GIAY,
            ...tinhDongHo(dh, laHoaTuoi),
        },

        // App dựa vào đây để bật/tắt nút
        hanh_dong: {
            co_the_nhan_hang: dh.trang_thai === 'CONFIRMED',
            co_the_bao_den_noi: dangGiao && !daDenNoi,
            co_the_giao_xong: dangGiao && daDenNoi,
        },
    };
}

module.exports = { getOrderDetail };