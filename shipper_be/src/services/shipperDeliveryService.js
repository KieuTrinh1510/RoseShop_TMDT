// src/services/shipperDeliveryService.js
// Luồng giao hàng của shipper:
//   1. pickup    : CONFIRMED -> SHIPPING  (ghi ngay_bat_giao, báo khách "đang giao")
//   2. arrived   : (đang SHIPPING) ghi ngay_den_noi + báo khách xuống nhận hàng
//   3. delivered : SHIPPING -> DELIVERED  (bắt buộc đã arrived; ghi ngay_giao, DA_THANH_TOAN,
//                  trừ số đơn, báo khách hoàn thành)
//   4. timeout   : chỉ đơn hoa tươi. Đồng hồ 3h tính từ ngay_bat_giao (lưu DB, không dùng Redis).
//   5. overdue   : UC4.2.10 – app gọi khi đồng hồ về 0 / mở lại màn hình mà đã quá 3h:
//                  cộng 15 điểm cho khách, phạt shipper 15.000đ, mỗi đơn đúng 1 lần.
//
// Cần thêm cột: ALTER TABLE hoa_don ADD COLUMN ngay_den_noi DATETIME NULL;

const { Op } = require('sequelize');
const sequelize = require('../config/database');
const HoaDon = require('../models/HoaDon');
const KhachHang = require('../models/KhachHang');
const NhanVien = require('../models/NhanVien');
const ChiTietHoaDon = require('../models/ChiTietHoaDon');
const shipperStatusService = require('./shipperStatusService');
const thongBaoService = require('./thongBaoService');
const { taoLoi, parseMaHoaDon } = require('../utils/loi');

const THOI_GIAN_TOI_DA_GIAY = 3 * 60 * 60;   // hạn giao hoa tươi: 3 tiếng (BR-1)
const NGUONG_CANH_BAO_GIAY = 30 * 60;        // còn dưới 30 phút -> app đổi sang 🚨
const GIOI_HAN_GHI_NHAN_GIAY = 24 * 60 * 60; // A7: quá 24h thì không còn đồng hồ/phạt
const DIEM_BU_CHO_KHACH = 15;                // BR-2
const TIEN_PHAT_SHIPPER = 15000;             // BR-2

// ── Helpers ──

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

// Đơn có hoa tươi nếu có ít nhất 1 sản phẩm là hoa tươi (A6: không có thì không đồng hồ, không phạt)
function donLaHoaTuoi(dsChiTiet) {
    return dsChiTiet.some((ct) => ct.sanPham?.laHoaTuoi);
}

// Tính trạng thái đồng hồ từ dữ liệu đơn (hàm thuần, dùng chung với getOrderDetail)
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
    if (hoaDon.trang_thai !== 'SHIPPING' || !hoaDon.ngay_bat_giao) return khongCoDongHo('KHONG_DANG_GIAO');

    const elapsed = Math.max(0, Math.floor((Date.now() - new Date(hoaDon.ngay_bat_giao).getTime()) / 1000));
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

// ── 1. Nhận hàng: CONFIRMED -> SHIPPING ──
async function confirmPickup(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'CONFIRMED') {
        throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái CONFIRMED.');
    }

    // Đọc chi tiết TRƯỚC khi đổi trạng thái: lỗi đọc thì chưa đổi gì
    const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
    const laHoaTuoi = donLaHoaTuoi(dsChiTiet);

    // Điều kiện trạng thái nằm ngay trong UPDATE: bấm 2 lần chỉ 1 request thắng,
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

    // Gửi thông báo lỗi thì KHÔNG làm hỏng việc nhận hàng (đơn đã SHIPPING rồi).
    try {
        await thongBaoService.tao({
            maNguoiDung: hoaDon.ma_khach_hang,
            loai: thongBaoService.LOAI.SHIPPING,
            noiDung: thongBaoService.noiDungDangGiao(hoaDon.maHienThi, moTaSanPham(dsChiTiet)),
            maHoaDon,
        });
    } catch (err) {
        console.error(`[Delivery] Gửi thông báo "đang giao" đơn ${maHoaDon} thất bại:`, err.message);
    }

    console.log(`[Delivery] Shipper ${maNhanVien} nhận đơn ${maHoaDon} (hoa tươi: ${laHoaTuoi})`);

    return {
        ma_hoa_don: maHoaDon,
        trang_thai: 'SHIPPING',
        la_hoa_tuoi: laHoaTuoi,
        thoi_gian_bat_dau: batDau,
        // null = đơn không có đồng hồ
        thoi_gian_toi_da_giay: laHoaTuoi ? THOI_GIAN_TOI_DA_GIAY : null,
    };
}

// ── 2. Đã đến điểm giao: ghi mốc + báo khách ──
async function baoDenNoi(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'SHIPPING') {
        throw taoLoi(422, 'INVALID_STATE', 'Chỉ có thể gửi thông báo khi đơn hàng đang được vận chuyển.');
    }
    if (hoaDon.ngay_den_noi) {
        throw taoLoi(409, 'ALREADY_NOTIFIED', 'Bạn đã báo khách là đã đến điểm giao rồi.');
    }

    const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
    const noiDung = thongBaoService.noiDungDenNoi(hoaDon.maHienThi, moTaSanPham(dsChiTiet), hoaDon.dia_chi_giao);
    const luc = new Date();

    // Ghi mốc + gửi thông báo trong CÙNG transaction: không có chuyện đã ghi mốc mà khách không nhận được tin.
    // "ngay_den_noi IS NULL" trong UPDATE chặn bấm 2 lần cùng lúc.
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

        await thongBaoService.tao({
            maNguoiDung: hoaDon.ma_khach_hang,
            loai: thongBaoService.LOAI.ARRIVED,
            noiDung,
            maHoaDon,
        }, { transaction: t });
    });

    console.log(`[Delivery] Shipper ${maNhanVien} báo đã đến điểm giao đơn ${maHoaDon}`);

    return { ma_hoa_don: maHoaDon, da_gui: true, ngay_den_noi: luc, noi_dung: noiDung };
}

// ── 3. Hoàn thành: SHIPPING -> DELIVERED (phải đã báo đến điểm giao) ──
async function markDelivered(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoaDon.trang_thai !== 'SHIPPING') {
        throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái SHIPPING.');
    }
    if (!hoaDon.ngay_den_noi) {
        throw taoLoi(422, 'NOT_ARRIVED', 'Bạn cần báo "đã đến điểm giao" trước khi hoàn thành đơn hàng.');
    }

    const ngayGiao = new Date();

    // Đổi trạng thái đơn + trừ số đơn của shipper trong CÙNG 1 transaction.
    // Điều kiện trong UPDATE chống bấm 2 lần trừ số đơn 2 lần.
    // BR-3: quá 3h vẫn được hoàn thành, nên không chặn theo da_phat_tre / thời gian.
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
                    ngay_den_noi: { [Op.ne]: null },
                },
                transaction: t,
            }
        );
        if (soDong === 0) {
            throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái SHIPPING hoặc chưa báo đến điểm giao.');
        }

        await shipperStatusService.decrementOrderCount(maNhanVien, t);
    });

    // Báo khách hoàn thành. Lỗi gửi thông báo không làm hỏng việc giao hàng.
    try {
        const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
        await thongBaoService.tao({
            maNguoiDung: hoaDon.ma_khach_hang,
            loai: thongBaoService.LOAI.DELIVERED,
            noiDung: thongBaoService.noiDungHoanThanh(hoaDon.maHienThi, moTaSanPham(dsChiTiet)),
            maHoaDon,
        });
    } catch (err) {
        console.error(`[Delivery] Gửi thông báo "hoàn thành" đơn ${maHoaDon} thất bại:`, err.message);
    }

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
            tien_cod_vua_thu: laCod ? Number(hoaDon.tong_tien) : 0,
        },
        cod: {
            tien_can_nop: cod.tongTien,
            so_don: cod.soDon,
        },
    };
}

// ── 4. Đồng hồ đếm ngược (chỉ đọc, không ghi gì) ──
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

// ── 5. Xử lý quá hạn (UC4.2.10 bước 3): app gọi khi đồng hồ về 0 hoặc mở lại màn hình mà đã quá 3h ──
// Idempotent: gọi bao nhiêu lần cũng chỉ phạt 1 lần (A4).
// Không có cron: chỉ chạy khi shipper mở trang chi tiết (E1).
async function xuLyQuaHan(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);
    const dsChiTiet = await ChiTietHoaDon.findByHoaDonWithSanPham(maHoaDon);
    const dongHo = tinhDongHo(hoaDon, donLaHoaTuoi(dsChiTiet));

    const khongXuLy = (lyDo) => ({ ma_hoa_don: maHoaDon, da_xu_ly: false, ly_do: lyDo });

    if (!dongHo.co_dong_ho) return khongXuLy(dongHo.ly_do);   // A3, A6, A7
    if (dongHo.da_phat_tre) return khongXuLy('DA_XU_LY');      // A4
    if (!dongHo.is_timeout) return khongXuLy('CHUA_QUA_HAN');  // A1

    const daXuLy = await sequelize.transaction(async (t) => {
        // "Giành quyền phạt": chỉ 1 request đổi được da_phat_tre false -> true.
        // Điều kiện SHIPPING chặn trường hợp đơn vừa được giao xong (A3).
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

        await thongBaoService.tao({
            maNguoiDung: hoaDon.ma_khach_hang,
            loai: thongBaoService.LOAI.APOLOGY,
            noiDung: thongBaoService.noiDungXinLoiTre(hoaDon.maHienThi, DIEM_BU_CHO_KHACH),
            maHoaDon,
        }, { transaction: t });
        await thongBaoService.tao({
            maNguoiDung: maNhanVien,
            loai: thongBaoService.LOAI.PENALTY,
            noiDung: thongBaoService.noiDungPhatTre(hoaDon.maHienThi, TIEN_PHAT_SHIPPER),
            maHoaDon,
        }, { transaction: t });

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
    THOI_GIAN_TOI_DA_GIAY,
    tinhDongHo,
    donLaHoaTuoi,
    confirmPickup,
    baoDenNoi,
    markDelivered,
    getTimeoutRemaining,
    xuLyQuaHan,
};