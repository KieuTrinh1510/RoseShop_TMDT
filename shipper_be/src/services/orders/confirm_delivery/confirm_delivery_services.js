// src/services/orders/confirm_delivery/confirm_delivery_services.js
// Nút "Hoàn thành đơn": SHIPPING -> DELIVERED. Bắt buộc đã báo đến điểm giao.
// Ghi ngay_giao, DA_THANH_TOAN (nếu thu tiền mặt), báo khách hoàn thành.

const { Op } = require('sequelize');
const sequelize = require('../../../config/database');
const HoaDon = require('../../../models/HoaDon');
const NhanVien = require('../../../models/NhanVien');
const ChiTietHoaDon = require('../../../models/ChiTietHoaDon');
const { taoLoi, parseMaHoaDon } = require('../../../utils/loi');
const {
    timDonHoacNemLoi,
    moTaSanPham,
    locChiTietHienThi,
} = require('../order_helper');
const customerNotif = require('../customer_notification/customer_notification_services');
const failureService = require('../shipper_failure/shipper_failure_service');

async function confirmDelivery(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'SHIPPING') {
        throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái SHIPPING.');
    }
    if (!hoaDon.ngay_den_noi) {
        throw taoLoi(422, 'NOT_ARRIVED', 'Bạn cần báo "đã đến điểm giao" trước khi hoàn thành đơn hàng.');
    }

    // CHUA_THANH_TOAN → shipper vừa thu tiền mặt; DA_THANH_TOAN → không giữ tiền
    const thuTienMat = hoaDon.trang_thai_thanh_toan === 'CHUA_THANH_TOAN';
    const ngayGiao = new Date();

    await sequelize.transaction(async (t) => {
        const updateFields = {
            trang_thai: 'DELIVERED',
            ngay_giao: ngayGiao,
        };

        if (thuTienMat) {
            updateFields.trang_thai_thanh_toan = 'DA_THANH_TOAN';
            updateFields.da_thu_tien_mat = true;
        }

        const [soDong] = await HoaDon.update(updateFields, {
            where: {
                ma_hoa_don: maHoaDon,
                ma_nhan_vien_giao: maNhanVien,
                trang_thai: 'SHIPPING',
                ngay_den_noi: { [Op.ne]: null },
            },
            transaction: t,
        });
        if (soDong === 0) {
            throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái SHIPPING hoặc chưa báo đến điểm giao.');
        }
    });

    // Đóng báo cáo "chờ khách" nếu có (không làm hỏng giao hàng nếu lỗi)
    try {
        await failureService.dongBaoCaoKhiDaGiao(maHoaDon);
    } catch (err) {
        console.error(`[Delivery] Đóng báo cáo chờ khách đơn ${maHoaDon} thất bại:`, err.message);
    }

    // Gửi notification hoàn thành
    try {
        const dsChiTietRaw = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
        const dsChiTietMoTa = locChiTietHienThi(dsChiTietRaw);

        await customerNotif.taoHoanThanh(
            hoaDon.ma_khach_hang,
            maHoaDon,
            hoaDon.maHienThi,
            moTaSanPham(dsChiTietMoTa)
        );
    } catch (err) {
        console.error(`[Delivery] Gửi thông báo "hoàn thành" đơn ${maHoaDon} thất bại:`, err.message);
    }

    // Tiền cần nộp sau khi giao + tiền phạt tích lũy
    const [cod, shipper] = await Promise.all([
        HoaDon.tinhTienCanNop(maNhanVien),
        NhanVien.findById(maNhanVien),
    ]);
    const tienPhat = Number(shipper?.tien_phat) || 0;

    console.log(`[Delivery] Shipper ${maNhanVien} giao thành công đơn ${maHoaDon} (thu tiền mặt: ${thuTienMat})`);

    return {
        ma_hoa_don: maHoaDon,
        trang_thai: 'DELIVERED',
        ngay_giao: ngayGiao,
        thanh_toan: {
            phuong_thuc: hoaDon.phuong_thuc_thanh_toan,
            da_thu_tien_mat: thuTienMat,
            tien_vua_thu: thuTienMat ? Number(hoaDon.tong_tien) : 0,
        },
        cod: {
            tien_cod: cod.tongTien,
            tien_phat: tienPhat,
            tien_can_nop: cod.tongTien + tienPhat,
            so_don: cod.soDon,
        },
    };
}

module.exports = { confirmDelivery };