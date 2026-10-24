// src/services/orders/order_detail/order_detail_services.js
// Chi tiết 1 đơn: khách + sản phẩm + tiền + thanh toán + tiến trình + đồng hồ + nút được bấm.

const HoaDon = require('../../../models/HoaDon');
const GiaoHangThatBai = require('../../../models/GiaoHangThatBai');
const { taoLoi, parseMaHoaDon } = require('../../../utils/loi');
const {
    INCLUDE_KHACH,
    INCLUDE_CHI_TIET,
    tomTatDon,
    coHoaTuoi,
    locChiTietHienThi,
} = require('../order_helper');
const {
    THOI_GIAN_TOI_DA_GIAY,
    tinhDongHo,
} = require('../delivery_timer/delivery_timer_services');
const { tinhTrangThaiThatBai } = require('../shipper_failure/failure_state');
const { layHangHoan } = require('../return_goods/return_goods_services');

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
    const daHuy = dh.trang_thai === 'CANCELLED';

    // Báo cáo giao thất bại (nếu có) + trạng thái chờ khách
    const baoCao = await GiaoHangThatBai.findOne({
        where: { ma_hoa_don: maHoaDon },
        order: [['ma_giao_hang_that_bai', 'DESC']],
    });
    const tt = tinhTrangThaiThatBai(dh, baoCao);
    const hangHoan = daHuy && dh.co_hang_hoan ? await layHangHoan(maHoaDon) : null;

    // ── Lọc: có bó hoa → chỉ hiển thị dòng bó; ngược lại → sản phẩm lẻ ──
    const dsChiTietHienThi = locChiTietHienThi(dh.chiTietHoaDon || []);

    const sanPham = dsChiTietHienThi.map((ct) => ({
        ma_bo_hoa: ct.ma_bo_hoa || null,
        ma_san_pham: ct.ma_san_pham || null,
        ten_san_pham: ct.tenHienThi,
        hinh_anh: ct.boHoa?.hinh_anh || ct.sanPham?.hinh_anh || null,
        size: ct.boHoa?.size || null,
        so_luong: ct.so_luong,
        gia_ban: Number(ct.gia_ban_snapshot || 0),
        thanh_tien: ct.thanhTien,
    }));
    const tamTinh = sanPham.reduce((tong, sp) => tong + sp.thanh_tien, 0);

    return {
        ...tomTatDon(dh),
        nhan_trang_thai: daHuy ? 'Đã hủy' : tomTatDon(dh).nhan_trang_thai,
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
            { key: 'SHIPPING', label: 'Đang vận chuyển', done: dangGiao || daGiao || (daHuy && Boolean(dh.ngay_bat_giao)) },
            { key: 'ARRIVED', label: 'Đã đến điểm giao', done: daDenNoi },
            { key: 'DELIVERED', label: 'Hoàn thành giao hàng', done: daGiao },
        ],
        da_bao_den_noi: daDenNoi,

        // Đồng hồ 3h
        dong_ho: daHuy
            ? { thoi_gian_toi_da_giay: THOI_GIAN_TOI_DA_GIAY, co_dong_ho: false }
            : {
                thoi_gian_toi_da_giay: THOI_GIAN_TOI_DA_GIAY,
                ...tinhDongHo(dh, laHoaTuoi),
            },

        // App dựa vào đây để bật/tắt nút
        hanh_dong: {
            co_the_nhan_hang: dh.trang_thai === 'CONFIRMED',
            co_the_bao_den_noi: dangGiao && !daDenNoi,
            co_the_giao_xong: dangGiao && daDenNoi,
            co_the_bao_loi_shipper: tt.coTheGuiLoi.SHIPPER,
            co_the_bao_loi_khach: tt.coTheGuiLoi.CUSTOMER,
            co_the_finalize_huy: tt.coTheFinalize,
        },

        // Trạng thái luồng giao thất bại
        that_bai: {
            giai_doan: tt.giaiDoan,
            loai_loi_da_bao: tt.loaiLoiDaBao,
            trang_thai_bao_loi_khach: tt.trangThaiBaoLoiKhach,
            ly_do_da_chon: tt.lyDoDaChon,
            duoi_10_phut: tt.duoi10Phut,
            giay_con_lai: tt.giayConLai,
            giay_con_lai_lan_1: tt.giayConLaiLan1,
            giay_con_lai_lan_2: tt.giayConLaiLan2,
            het_han_lan_1_luc: tt.hetHanLan1Luc,
            het_han_lan_2_luc: tt.hetHanLan2Luc,
            co_the_hoan_thanh: tt.coTheHoanThanh,
            co_the_finalize: tt.coTheFinalize,
        },

        // Chỉ có khi đơn CANCELLED
        huy: daHuy
            ? {
                nguon_huy: dh.nguon_huy || null,
                nhan_nguon_huy: dh.nguon_huy === 'SHIPPER'
                    ? 'Lỗi phía shipper'
                    : dh.nguon_huy === 'CUSTOMER' ? 'Lỗi phía khách hàng' : null,
                ly_do_huy: dh.ly_do_huy || null,
                chi_tiet_ly_do: baoCao?.ghi_chu || null,
                thoi_diem_huy: dh.thoi_diem_huy || null,
                co_hang_hoan: Boolean(dh.co_hang_hoan),
                hang_hoan: hangHoan,
            }
            : null,
    };
}

module.exports = { getOrderDetail };