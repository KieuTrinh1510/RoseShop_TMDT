// src/services/orders/notify_arrival/notify_arrival_services.js
// Nút "Đã đến điểm giao": ghi ngay_den_noi + báo khách xuống nhận hàng.
// Ghi mốc + gửi thông báo trong CÙNG transaction.

const sequelize = require('../../../config/database');
const HoaDon = require('../../../models/HoaDon');
const ChiTietHoaDon = require('../../../models/ChiTietHoaDon');
const { taoLoi, parseMaHoaDon } = require('../../../utils/loi');
const {
    timDonHoacNemLoi,
    moTaSanPham,
    locChiTietHienThi,
} = require('../order_helper');
const customerNotif = require('../customer_notification/customer_notification_services');

async function notifyArrival(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'SHIPPING') {
        throw taoLoi(422, 'INVALID_STATE', 'Chỉ có thể gửi thông báo khi đơn hàng đang được vận chuyển.');
    }
    if (hoaDon.ngay_den_noi) {
        throw taoLoi(409, 'ALREADY_NOTIFIED', 'Bạn đã báo khách là đã đến điểm giao rồi.');
    }

    const dsChiTietRaw = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
    const dsChiTietMoTa = locChiTietHienThi(dsChiTietRaw);
    const luc = new Date();

    // "ngay_den_noi IS NULL" trong UPDATE chặn bấm 2 lần cùng lúc
    await sequelize.transaction(async (t) => {
        const [soDong] = await HoaDon.update(
            { ngay_den_noi: luc },
            {
                where: {
                    ma_hoa_don: maHoaDon,
                    ma_nhan_vien_giao: maNhanVien,
                    trang_thai: 'SHIPPING',
                    ngay_den_noi: null,
                },
                transaction: t,
            }
        );
        if (soDong === 0) {
            throw taoLoi(409, 'ALREADY_NOTIFIED', 'Bạn đã báo khách là đã đến điểm giao rồi.');
        }

        await customerNotif.taoDenNoi(
            hoaDon.ma_khach_hang,
            maHoaDon,
            hoaDon.maHienThi,
            moTaSanPham(dsChiTietMoTa),
            hoaDon.dia_chi_giao,
            { transaction: t }
        );
    });

    console.log(`[Delivery] Shipper ${maNhanVien} báo đã đến điểm giao đơn ${maHoaDon}`);

    return {
        ma_hoa_don: maHoaDon,
        da_gui: true,
        ngay_den_noi: luc,
        noi_dung: customerNotif.noiDungDenNoi(hoaDon.maHienThi, moTaSanPham(dsChiTietMoTa), hoaDon.dia_chi_giao),
    };
}

module.exports = { notifyArrival };