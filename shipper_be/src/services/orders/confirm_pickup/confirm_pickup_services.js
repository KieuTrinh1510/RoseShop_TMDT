// src/services/orders/confirm_pickup/confirm_pickup_services.js
// Nút "Nhận hàng & bắt đầu giao": CONFIRMED -> SHIPPING.
// Ghi ngay_bat_giao + báo khách "đang giao".

const HoaDon = require('../../../models/HoaDon');
const ChiTietHoaDon = require('../../../models/ChiTietHoaDon');
const { taoLoi, parseMaHoaDon } = require('../../../utils/loi');
const { timDonHoacNemLoi, moTaSanPham } = require('../order_helper');
const customerNotif = require('../customer_notification/customer_notification_services');
const { THOI_GIAN_TOI_DA_GIAY, donLaHoaTuoi } = require('../delivery_timer/delivery_timer_services');

async function confirmPickup(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'CONFIRMED') {
        throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái CONFIRMED.');
    }

    // Đọc chi tiết TRƯỚC khi đổi trạng thái: lỗi đọc thì chưa đổi gì
    const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
    const laHoaTuoi = donLaHoaTuoi(dsChiTiet);

    // Điều kiện trạng thái nằm trong UPDATE: bấm 2 lần chỉ 1 request thắng,
    // nên mốc ngay_bat_giao không bị ghi đè.
    const batDau = new Date();
    const [soDong] = await HoaDon.update(
        { trang_thai: 'SHIPPING', ngay_bat_giao: batDau },
        {
            where: {
                ma_hoa_don: maHoaDon,
                ma_nhan_vien_giao: maNhanVien,
                trang_thai: 'CONFIRMED',
            },
        }
    );
    if (soDong === 0) {
        throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái CONFIRMED.');
    }

    // Lỗi gửi thông báo không làm hỏng việc nhận hàng (đơn đã SHIPPING rồi).
    try {
        await customerNotif.taoDangGiao(
            hoaDon.ma_khach_hang,
            maHoaDon,
            hoaDon.maHienThi,
            moTaSanPham(dsChiTiet),
            laHoaTuoi
        );
    } catch (err) {
        console.error(`[Delivery] Gửi thông báo "đang giao" đơn ${maHoaDon} thất bại:`, err.message);
    }

    console.log(`[Delivery] Shipper ${maNhanVien} nhận đơn ${maHoaDon} (hoa tươi: ${laHoaTuoi})`);

    return {
        ma_hoa_don: maHoaDon,
        trang_thai: 'SHIPPING',
        la_hoa_tuoi: laHoaTuoi,
        thoi_gian_bat_dau: batDau,
        thoi_gian_toi_da_giay: laHoaTuoi ? THOI_GIAN_TOI_DA_GIAY : null,
    };
}

module.exports = { confirmPickup };