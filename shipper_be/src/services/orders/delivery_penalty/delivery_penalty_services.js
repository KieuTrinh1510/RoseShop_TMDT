// src/services/orders/delivery_penalty/delivery_penalty_services.js
// Xử lý quá hạn 3h (UC4.2.10 bước 3):
//   +15 điểm cho khách, -15.000đ phạt shipper, mỗi đơn đúng 1 lần.
// Idempotent: gọi bao nhiêu lần cũng chỉ phạt 1 lần.
// Không có cron: chỉ chạy khi shipper mở trang chi tiết.

const { Op } = require('sequelize');
const sequelize = require('../../../config/database');
const HoaDon = require('../../../models/HoaDon');
const KhachHang = require('../../../models/KhachHang');
const NhanVien = require('../../../models/NhanVien');
const ChiTietHoaDon = require('../../../models/ChiTietHoaDon');
const { parseMaHoaDon } = require('../../../utils/loi');
const { timDonHoacNemLoi } = require('../order_helper');
const customerNotif = require('../customer_notification/customer_notification_services');
const shipperNotif = require('../../home/notification/notification_services');
const {
    tinhDongHo,
    donLaHoaTuoi,
} = require('../delivery_timer/delivery_timer_services');

const DIEM_BU_CHO_KHACH = 15;    // BR-2
const TIEN_PHAT_SHIPPER = 15000; // BR-2

async function xuLyQuaHan(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);
    const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
    const dongHo = tinhDongHo(hoaDon, donLaHoaTuoi(dsChiTiet));

    const khongXuLy = (lyDo) => ({ ma_hoa_don: maHoaDon, da_xu_ly: false, ly_do: lyDo });

    if (!dongHo.co_dong_ho) return khongXuLy(dongHo.ly_do); // A3, A6, A7
    if (dongHo.da_phat_tre) return khongXuLy('DA_XU_LY');    // A4
    if (!dongHo.is_timeout) return khongXuLy('CHUA_QUA_HAN'); // A1

    const daXuLy = await sequelize.transaction(async (t) => {
        // "Giành quyền phạt": chỉ 1 request đổi được da_phat_tre false -> true
        const [soDong] = await HoaDon.update(
            { da_phat_tre: true },
            {
                where: {
                    ma_hoa_don: maHoaDon,
                    ma_nhan_vien_giao: maNhanVien,
                    trang_thai: 'SHIPPING',
                    da_phat_tre: { [Op.or]: [false, null] },
                },
                transaction: t,
            }
        );
        if (soDong === 0) return false;

        await KhachHang.increment('diem_tich_luy', {
            by: DIEM_BU_CHO_KHACH,
            where: { ma_khach_hang: hoaDon.ma_khach_hang },
            transaction: t,
        });
        await NhanVien.increment('tien_phat', {
            by: TIEN_PHAT_SHIPPER,
            where: { ma_nhan_vien: maNhanVien },
            transaction: t,
        });

        // Thông báo xin lỗi khách
        await customerNotif.taoXinLoiTre(
            hoaDon.ma_khach_hang,
            maHoaDon,
            hoaDon.maHienThi,
            DIEM_BU_CHO_KHACH,
            { transaction: t }
        );
        // Thông báo phạt shipper
        await shipperNotif.taoThongBaoPhat(
            maNhanVien,
            maHoaDon,
            hoaDon.maHienThi,
            TIEN_PHAT_SHIPPER,
            { transaction: t }
        );

        return true;
    });

    if (!daXuLy) return khongXuLy('DA_XU_LY');

    console.log(`[Delivery] Đơn ${maHoaDon} quá 3h: +${DIEM_BU_CHO_KHACH} điểm khách, +${TIEN_PHAT_SHIPPER}đ phạt shipper ${maNhanVien}`);

    return {
        ma_hoa_don: maHoaDon,
        da_xu_ly: true,
        diem_cong_cho_khach: DIEM_BU_CHO_KHACH,
        tien_phat: TIEN_PHAT_SHIPPER,
    };
}

module.exports = {
    DIEM_BU_CHO_KHACH,
    TIEN_PHAT_SHIPPER,
    xuLyQuaHan,
};