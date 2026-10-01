// src/services/shipperDeliveryService.js
// Luồng giao hàng của shipper:
//   1. pickup    : CONFIRMED -> SHIPPING  (ghi ngay_bat_giao, báo khách "đang giao")
//   2. arrived   : (đang SHIPPING) shipper bấm "đã đến điểm giao" -> báo khách
//   3. delivered : SHIPPING -> DELIVERED  (ghi ngay_giao, DA_THANH_TOAN, trừ số đơn, trả tiền COD)
//   4. timeout   : đồng hồ đếm ngược 3h tính từ ngay_bat_giao
//
// Mốc bắt đầu giao lưu trong DB (hoa_don.ngay_bat_giao), không còn dùng Redis cho luồng này.

const sequelize = require('../config/database');
const HoaDon = require('../models/HoaDon');
const ChiTietHoaDon = require('../models/ChiTietHoaDon');
const shipperStatusService = require('./shipperStatusService');
const thongBaoService = require('./thongBaoService');
const { taoLoi, parseMaHoaDon } = require('../utils/loi');

const THOI_GIAN_TOI_DA_GIAY = 3 * 60 * 60; // 3 tiếng

// Tìm đơn theo mã VÀ phải thuộc shipper này, không có thì ném 404
async function timDonHoacNemLoi(maNhanVien, maHoaDon) {
    const hoaDon = await HoaDon.findByIdAndShipper(maHoaDon, maNhanVien);
    if (!hoaDon) {
        throw taoLoi(404, 'ORDER_NOT_FOUND', 'Không tìm thấy đơn hàng hoặc đơn không thuộc về bạn.');
    }
    return hoaDon;
}

// "Hoa hồng (×2), Bó lan (×1)"
async function layMoTaSanPham(maHoaDon) {
    const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
    return dsChiTiet.map((ct) => `${ct.tenHienThi} (×${ct.so_luong})`).join(', ');
}

// 1. Nút "Nhận hàng": CONFIRMED -> SHIPPING
async function confirmPickup(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'CONFIRMED') {
        throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái CONFIRMED.');
    }

    // Điều kiện trạng thái nằm ngay trong UPDATE: bấm 2 lần chỉ có 1 request thắng,
    // nên mốc ngay_bat_giao (đồng hồ 3h) không bị ghi đè.
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

    // Báo khách. Gửi thông báo lỗi thì KHÔNG làm hỏng việc nhận hàng (đơn đã SHIPPING rồi).
    try {
        const dsSanPham = await layMoTaSanPham(maHoaDon);
        await thongBaoService.tao({
            maNguoiDung: hoaDon.ma_khach_hang,
            loai: thongBaoService.LOAI.SHIPPING,
            noiDung: thongBaoService.noiDungDangGiao(hoaDon.maHienThi, dsSanPham),
            maHoaDon,
        });
    } catch (err) {
        console.error(`[Delivery] Gửi thông báo "đang giao" đơn ${maHoaDon} thất bại:`, err.message);
    }

    console.log(`[Delivery] Shipper ${maNhanVien} nhận đơn ${maHoaDon}`);

    return {
        ma_hoa_don: maHoaDon,
        trang_thai: 'SHIPPING',
        thoi_gian_bat_dau: batDau,
        thoi_gian_toi_da_giay: THOI_GIAN_TOI_DA_GIAY,
    };
}

// 2. Nút "Đã đến điểm giao": gửi thông báo cho khách (đơn phải đang SHIPPING)
async function baoDenNoi(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'SHIPPING') {
        throw taoLoi(422, 'INVALID_STATE', 'Chỉ có thể gửi thông báo khi đơn hàng đang được vận chuyển.');
    }

    // Mỗi đơn chỉ báo 1 lần, tránh shipper bấm liên tục làm khách bị spam
    if (await thongBaoService.daCoThongBaoDon(maHoaDon, thongBaoService.LOAI.ARRIVED)) {
        throw taoLoi(409, 'ALREADY_NOTIFIED', 'Bạn đã báo khách là đã đến điểm giao rồi.');
    }

    const dsSanPham = await layMoTaSanPham(maHoaDon);
    const noiDung = thongBaoService.noiDungDenNoi(hoaDon.maHienThi, dsSanPham, hoaDon.dia_chi_giao);

    // Đây chính là hành động shipper yêu cầu nên lỗi thì báo lỗi, không nuốt
    await thongBaoService.tao({
        maNguoiDung: hoaDon.ma_khach_hang,
        loai: thongBaoService.LOAI.ARRIVED,
        noiDung,
        maHoaDon,
    });

    console.log(`[Delivery] Shipper ${maNhanVien} báo đã đến điểm giao đơn ${maHoaDon}`);

    return { ma_hoa_don: maHoaDon, da_gui: true, noi_dung: noiDung };
}

// 3. Nút "Giao thành công": SHIPPING -> DELIVERED
async function markDelivered(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    const ngayGiao = new Date();

    // Đổi trạng thái đơn + trừ số đơn của shipper trong CÙNG 1 transaction:
    // hoặc cùng thành công, hoặc cùng rollback (không còn cảnh đơn DELIVERED mà vẫn bị tính đủ slot).
    // Điều kiện "trang_thai = SHIPPING" trong UPDATE chống bấm 2 lần trừ số đơn 2 lần.
    await sequelize.transaction(async (t) => {
        const [soDong] = await HoaDon.update(
            {
                trang_thai: 'DELIVERED',
                ngay_giao: ngayGiao,
                trang_thai_thanh_toan: 'DA_THANH_TOAN',
            },
            {
                where: {
                    ma_hoa_don: maHoaDon,
                    ma_nhan_vien_giao: maNhanVien,
                    trang_thai: 'SHIPPING',
                },
                transaction: t,
            }
        );
        if (soDong === 0) {
            throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái SHIPPING.');
        }

        await shipperStatusService.decrementOrderCount(maNhanVien, t);
    });

    // Tiền COD shipper phải nộp sau khi giao (để app hiện ngay)
    const cod = await HoaDon.tinhTienCanNop(maNhanVien);
    const laCod = hoaDon.phuong_thuc_thanh_toan === 'COD';

    console.log(`[Delivery] Shipper ${maNhanVien} giao thành công đơn ${maHoaDon}`);

    return {
        ma_hoa_don: maHoaDon,
        trang_thai: 'DELIVERED',
        ngay_giao: ngayGiao,
        thanh_toan: {
            phuong_thuc: hoaDon.phuong_thuc_thanh_toan,
            la_cod: laCod,
            tien_cod_vua_thu: laCod ? Number(hoaDon.tong_tien) : 0, // tiền đơn này shipper vừa thu của khách
        },
        cod: {
            tien_can_nop: cod.tongTien, // tổng COD shipper đang giữ, cần nộp lại
            so_don: cod.soDon,
        },
    };
}

// 4. Đồng hồ đếm ngược: còn bao lâu thì đơn bị coi là giao trễ (3h)
async function getTimeoutRemaining(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    // Chỉ có đồng hồ khi đơn đang SHIPPING và đã có mốc bắt đầu
    if (hoaDon.trang_thai !== 'SHIPPING' || !hoaDon.ngay_bat_giao) {
        return {
            co_moc: false,
            elapsed: 0,
            remaining: THOI_GIAN_TOI_DA_GIAY,
            is_timeout: false,
            da_phat_tre: Boolean(hoaDon.da_phat_tre),
        };
    }

    const elapsed = Math.floor((Date.now() - new Date(hoaDon.ngay_bat_giao).getTime()) / 1000);

    return {
        co_moc: true,
        elapsed,
        remaining: Math.max(0, THOI_GIAN_TOI_DA_GIAY - elapsed),
        is_timeout: elapsed >= THOI_GIAN_TOI_DA_GIAY,
        da_phat_tre: Boolean(hoaDon.da_phat_tre),
    };
}

module.exports = {
    THOI_GIAN_TOI_DA_GIAY,
    confirmPickup,
    baoDenNoi,
    markDelivered,
    getTimeoutRemaining,
};