// shipper_be/src/services/orders/shipper_failure/shipper_failure_service.js
//
// Xử lý luồng shipper báo giao hàng thất bại.
// Dùng chung database với Laravel qua Sequelize (shop_hoa).
// KHÔNG gọi API Laravel.
//
// Quy ước chống trùng / race:
//  - Mọi thao tác ghi đều chạy trong transaction và KHÓA hàng hoa_don (SELECT ... FOR UPDATE)
//    trước khi kiểm tra, nên các request song song của cùng 1 đơn bị xếp hàng.
//  - Đổi trạng thái đơn luôn là UPDATE có điều kiện trang_thai = 'SHIPPING'.
//  - hang_hoan có unique(ma_hoa_don); vi_pham_khach_hang được kiểm tra theo ma_hoa_don trước khi tạo.
//
// Luồng CUSTOMER gồm 2 giai đoạn:
//  1. baoCuaKhachHang()        → chỉ ghi nhận DANG_CHO_KHACH, đơn vẫn SHIPPING.
//  2. finalizeKhachKhongNhan() → sau 10 phút nữa mới CANCELLED + hàng hoàn + vi phạm.

'use strict';

const sequelize = require('../../../config/database');
const HoaDon = require('../../../models/HoaDon');
const ChiTietHoaDon = require('../../../models/ChiTietHoaDon');
const BoHoa = require('../../../models/BoHoa');
const GiaoHangThatBai = require('../../../models/GiaoHangThatBai');
const HangHoan = require('../../../models/HangHoan');
const ChiTietHangHoan = require('../../../models/ChiTietHangHoan');
const KhachHang = require('../../../models/KhachHang');
const ViPhamKhachHang = require('../../../models/ViPhamKhachHang');
const { locChiTietHienThi } = require('../order_helper');
const { taoLoi, parseMaHoaDon } = require('../../../utils/loi');
const { timDonHoacNemLoi } = require('../order_helper');
const customerNotification = require('../customer_notification/customer_notification_services');
const {
    CHO_LAN_1_MS,
    CHO_LAN_2_MS,
    TRANG_THAI_BAO_CAO,
    tinhTrangThaiThatBai,
} = require('./failure_state');

// ─────────────────────────────────────────────────────────────────────────────
// PRIVATE: helper
// ─────────────────────────────────────────────────────────────────────────────

// Khóa hàng hoa_don của shipper này. 404 nếu không tồn tại / không thuộc shipper.
async function _khoaDon(maHoaDon, maNhanVien, t) {
    const hoaDon = await HoaDon.findOne({
        where: { ma_hoa_don: maHoaDon, ma_nhan_vien_giao: maNhanVien },
        transaction: t,
        lock: t.LOCK.UPDATE,
    });
    if (!hoaDon) {
        throw taoLoi(404, 'ORDER_NOT_FOUND', 'Không tìm thấy đơn hàng hoặc đơn không thuộc về bạn.');
    }
    return hoaDon;
}

// Báo cáo thất bại mới nhất của đơn (mỗi đơn chỉ có tối đa 1)
function _layBaoCao(maHoaDon, t) {
    return GiaoHangThatBai.findOne({
        where: { ma_hoa_don: maHoaDon },
        order: [['ma_giao_hang_that_bai', 'DESC']],
        transaction: t,
    });
}

// Kiểm tra chung trước khi cho phép tạo báo cáo mới (gọi SAU khi đã khóa đơn)
function _kiemTraChoPhepBao(hoaDon, baoCaoCu) {
    if (hoaDon.trang_thai === 'DELIVERED') {
        throw taoLoi(422, 'ORDER_DELIVERED', 'Đơn đã giao thành công, không thể báo thất bại.');
    }
    if (baoCaoCu) {
        throw taoLoi(409, 'ALREADY_REPORTED', 'Đơn này đã được báo giao thất bại trước đó.');
    }
    if (hoaDon.trang_thai === 'CANCELLED') {
        throw taoLoi(422, 'ORDER_CANCELLED', 'Đơn đã bị hủy trước đó.');
    }
    if (hoaDon.trang_thai !== 'SHIPPING') {
        throw taoLoi(
            422,
            'INVALID_STATE',
            `Đơn #${hoaDon.ma_hoa_don} không ở trạng thái SHIPPING (hiện: ${hoaDon.trang_thai}).`
        );
    }
}

// Đếm lần vi phạm của khách. Khóa hàng khach_hang để 2 đơn khác nhau của cùng khách không đếm trùng số.
async function _demViPham(maKhachHang, t) {
    await KhachHang.findByPk(maKhachHang, { transaction: t, lock: t.LOCK.UPDATE });

    // Chỉ đếm vi phạm CHƯA GỠ (da_go = false) — khớp với Laravel (KiemTraHangController).
    const soLanCu = await ViPhamKhachHang.count({
        where: { ma_khach_hang: maKhachHang, da_go: false },
        transaction: t,
    });
    const lanNay = soLanCu + 1;

    let hanCheApDung;
    if (lanNay === 1) hanCheApDung = 'CANH_BAO';
    else if (lanNay === 2) hanCheApDung = 'CAM_COD';
    else hanCheApDung = 'TAM_KHOA_MUA_HANG';

    return { lanNay, hanCheApDung };
}

// Tạo chi_tiet_hang_hoan từ toàn bộ dòng của đơn hàng
async function _taoChiTietHangHoan(maHangHoan, maHoaDon, t) {
    const chiTietDon = await ChiTietHoaDon.findAll({
        where: { ma_hoa_don: maHoaDon },
        transaction: t,
    });

    // Cùng quy tắc với màn chi tiết đơn: có bó hoa → chỉ lấy dòng đại diện bó,
    // bỏ dòng con nguyên liệu; không có bó → sản phẩm lẻ.
    const dongHienThi = locChiTietHienThi(chiTietDon || [])
        .filter((ct) => ct.ma_bo_hoa || ct.ma_san_pham);   // chặn dòng ma

    // Bó hệ thống cũ chưa có dòng trong chi_tiet_hoa_don (dữ liệu legacy)
    const daCoBo = new Set(dongHienThi.filter((ct) => ct.ma_bo_hoa).map((ct) => ct.ma_bo_hoa));
    const boLegacy = (await BoHoa.findAll({
        where: { ma_hoa_don: maHoaDon },
        transaction: t,
    })).filter((bo) => !daCoBo.has(bo.ma_bo_hoa));

    if (dongHienThi.length === 0 && boLegacy.length === 0) {
        throw taoLoi(422, 'ORDER_HAS_NO_ITEMS', `Đơn #${maHoaDon} không có chi tiết sản phẩm.`);
    }

    const base = {
        ma_hang_hoan: maHangHoan,
        so_luong_hong: 0, so_luong_con_ban_duoc: 0, so_luong_con_lai_flash_sale: 0,
        gia_flash_sale_snapshot: null, doanh_thu_thanh_ly: 0,
        tien_hao_hut_flash_sale: 0, tien_thiet_hai_hang_hong: 0,
        tien_he_thong_chiu: 0, tien_shipper_chiu: 0,
        mien_phat_shipper: false, ket_qua: 'CHO_KIEM_TRA', ghi_chu: null,
    };

    const rows = dongHienThi.map((ct) => ({
        ...base,
        ma_san_pham: ct.ma_bo_hoa ? null : ct.ma_san_pham,
        ma_bo_hoa: ct.ma_bo_hoa ?? null,              // ← giữ mã bó
        ma_chi_tiet_hoa_don: ct.ma_chi_tiet,
        so_luong_theo_don: ct.so_luong,               // ← SL lấy từ hóa đơn
        gia_von_snapshot: ct.gia_nhap_snapshot,
        gia_ban_snapshot: ct.gia_ban_snapshot,
    }));

    for (const bo of boLegacy) {
        rows.push({
            ...base,
            ma_san_pham: null,
            ma_bo_hoa: bo.ma_bo_hoa,
            ma_chi_tiet_hoa_don: null,
            so_luong_theo_don: Math.max(1, Number(bo.so_luong) || 0),  // không để 0
            gia_von_snapshot: bo.gia_von ?? 0,
            gia_ban_snapshot: bo.gia_ban ?? 0,
        });
    }

    await ChiTietHangHoan.bulkCreate(rows, { transaction: t });
}

// Tạo hang_hoan + chi tiết, idempotent (đã có thì dùng lại, không tạo thêm).
async function _taoHangHoanNeuChuaCo(maHoaDon, maBaoCao, t) {
    const cu = await HangHoan.findOne({ where: { ma_hoa_don: maHoaDon }, transaction: t });
    if (cu) return cu;

    const hangHoan = await HangHoan.create({
        ma_hoa_don: maHoaDon,
        ma_giao_hang_that_bai: maBaoCao,
        trang_thai: 'CHO_MANG_VE_KHO',
        thoi_diem_nhan_tai_kho: null,
        thoi_diem_kiem_tra: null,
    }, { transaction: t });

    await _taoChiTietHangHoan(hangHoan.ma_hang_hoan, maHoaDon, t);
    return hangHoan;
}

// Thông báo lỗi KHÔNG được làm rollback giao dịch chính — chỉ log.
async function _guiThongBao(tenThongBao, maHoaDon, guiFn) {
    try {
        await guiFn();
    } catch (err) {
        console.error(`[Failure] Gửi thông báo "${tenThongBao}" đơn ${maHoaDon} thất bại:`, err.message);
    }
}

function _daThanhToan(hoaDon) {
    return hoaDon.trang_thai_thanh_toan === 'DA_THANH_TOAN';
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. KIỂM TRA ĐIỀU KIỆN (chỉ đọc) — Flutter dùng để render UI
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Không ghi dữ liệu, không throw khi đơn DELIVERED/CANCELLED (trả trạng thái để UI hiển thị).
 * Giữ nguyên các key cũ: maHoaDon, trangThai, daArrived, duoi10Phut, giayConLai, coTheGuiLoi.
 */
async function kiemTraDieuKien(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);
    const baoCao = await _layBaoCao(maHoaDon);

    return {
        maHoaDon: hoaDon.ma_hoa_don,
        ...tinhTrangThaiThatBai(hoaDon, baoCao),
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. LỖI KHÁCH HÀNG — GIAI ĐOẠN 1: ghi nhận chờ khách (CHƯA hủy)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Điều kiện: SHIPPING, đã ARRIVED, đủ 10 phút từ ngay_den_noi, chưa có báo cáo nào.
 * Ghi: giao_hang_that_bai (DANG_CHO_KHACH). KHÔNG đụng hoa_don, KHÔNG tạo hàng hoàn/vi phạm.
 */
async function baoCuaKhachHang(maNhanVien, id, lyDo, ghiChu = null) {
    const maHoaDon = parseMaHoaDon(id);
    const now = new Date();

    const { hoaDon, baoCao } = await sequelize.transaction(async (t) => {
        const hd = await _khoaDon(maHoaDon, maNhanVien, t);
        _kiemTraChoPhepBao(hd, await _layBaoCao(maHoaDon, t));

        if (!hd.ngay_den_noi) {
            throw taoLoi(422, 'NOT_ARRIVED', 'Bạn cần báo "đã đến điểm giao" trước khi báo lỗi do khách hàng.');
        }
        const troiQua = now.getTime() - new Date(hd.ngay_den_noi).getTime();
        if (troiQua < CHO_LAN_1_MS) {
            const conLai = Math.ceil((CHO_LAN_1_MS - troiQua) / 1000);
            throw taoLoi(
                422,
                'WAIT_NOT_ENOUGH',
                `Chưa đủ 10 phút kể từ khi đến điểm giao (còn ${conLai} giây).`
            );
        }

        const bc = await GiaoHangThatBai.create({
            ma_hoa_don: maHoaDon,
            ma_nhan_vien_giao: maNhanVien,
            loai_loi: 'CUSTOMER',
            ly_do: lyDo,
            ghi_chu: ghiChu,
            thoi_diem_bao: now,
            // Lần chờ thứ HAI bắt đầu từ lúc ghi nhận
            bat_dau_dem_10_phut: now,
            ket_thuc_dem_10_phut: new Date(now.getTime() + CHO_LAN_2_MS),
            trang_thai: TRANG_THAI_BAO_CAO.DANG_CHO_KHACH,
        }, { transaction: t });

        return { hoaDon: hd, baoCao: bc };
    });

    await _guiThongBao('chờ khách', maHoaDon, () =>
        customerNotification.taoChoKhach(hoaDon.ma_khach_hang, maHoaDon, hoaDon.maHienThi)
    );

    return {
        giaoHangThatBai: baoCao,
        ma_hoa_don: maHoaDon,
        trang_thai: 'SHIPPING', // đơn chưa đổi
        giai_doan: 'CHO_KHACH_LAN_2',
        giay_con_lai: Math.ceil(CHO_LAN_2_MS / 1000),
        het_han_luc: baoCao.ket_thuc_dem_10_phut,
        co_the_hoan_thanh: true,
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. LỖI KHÁCH HÀNG — GIAI ĐOẠN 2: finalize hủy đơn
// ─────────────────────────────────────────────────────────────────────────────

/**
 * - Chưa hết 10 phút lần hai → 422 WAIT_NOT_EXPIRED.
 * - Đơn đã DELIVERED          → trả "giao thành công", KHÔNG hủy.
 * - Đơn đã hủy do khách rồi   → trả kết quả idempotent (daFinalize = true), không ghi gì thêm.
 * - Đơn vẫn SHIPPING          → CANCELLED + hang_hoan + chi_tiet + vi_pham.
 */
async function finalizeKhachKhongNhan(maNhanVien, id) {
    const maHoaDon = parseMaHoaDon(id);
    const now = new Date();

    const ketQua = await sequelize.transaction(async (t) => {
        const hd = await _khoaDon(maHoaDon, maNhanVien, t);
        const baoCao = await _layBaoCao(maHoaDon, t);

        if (!baoCao || baoCao.loai_loi !== 'CUSTOMER') {
            if (hd.trang_thai === 'CANCELLED') {
                throw taoLoi(409, 'ALREADY_CANCELLED', 'Đơn đã bị hủy, không có báo cáo lỗi khách hàng đang chờ.');
            }
            throw taoLoi(409, 'NO_PENDING_REPORT', 'Đơn không có báo cáo lỗi khách hàng đang chờ.');
        }

        // Khách đã nhận hàng trong thời gian chờ → giữ DELIVERED, đóng báo cáo
        if (hd.trang_thai === 'DELIVERED') {
            if (baoCao.trang_thai === TRANG_THAI_BAO_CAO.DANG_CHO_KHACH) {
                await baoCao.update({ trang_thai: TRANG_THAI_BAO_CAO.KHACH_DA_NHAN }, { transaction: t });
            }
            return { huy: false, daFinalize: false, ketQua: 'DA_GIAO_THANH_CONG', hoaDon: hd };
        }

        // Đã finalize trước đó → idempotent
        if (hd.trang_thai === 'CANCELLED') {
            if (hd.nguon_huy === 'CUSTOMER') {
                return { huy: true, daFinalize: true, ketQua: 'DA_HUY_DO_KHACH', hoaDon: hd };
            }
            throw taoLoi(409, 'ALREADY_CANCELLED', 'Đơn đã bị hủy với nguyên nhân khác.');
        }

        if (hd.trang_thai !== 'SHIPPING') {
            throw taoLoi(422, 'INVALID_STATE', `Đơn không ở trạng thái SHIPPING (hiện: ${hd.trang_thai}).`);
        }
        if (baoCao.trang_thai !== TRANG_THAI_BAO_CAO.DANG_CHO_KHACH) {
            throw taoLoi(409, 'NO_PENDING_REPORT', 'Báo cáo lỗi khách hàng không còn ở trạng thái chờ.');
        }

        const hetHanMs = baoCao.ket_thuc_dem_10_phut
            ? new Date(baoCao.ket_thuc_dem_10_phut).getTime()
            : new Date(baoCao.thoi_diem_bao).getTime() + CHO_LAN_2_MS;
        if (now.getTime() < hetHanMs) {
            const conLai = Math.ceil((hetHanMs - now.getTime()) / 1000);
            throw taoLoi(422, 'WAIT_NOT_EXPIRED', `Chưa hết thời gian chờ khách (còn ${conLai} giây).`);
        }

        // 1. Hủy đơn — UPDATE có điều kiện trạng thái hiện tại
        const [soDong] = await HoaDon.update({
            trang_thai: 'CANCELLED',
            nguon_huy: 'CUSTOMER',
            ly_do_huy: baoCao.ly_do,
            thoi_diem_huy: now,
            co_hang_hoan: true,
        }, {
            where: {
                ma_hoa_don: maHoaDon,
                ma_nhan_vien_giao: maNhanVien,
                trang_thai: 'SHIPPING',
            },
            transaction: t,
        });
        if (soDong === 0) {
            throw taoLoi(409, 'STATE_CHANGED', 'Trạng thái đơn vừa thay đổi, vui lòng tải lại.');
        }

        // 2. Đóng báo cáo chờ (đã chốt hủy)
        await baoCao.update({ trang_thai: TRANG_THAI_BAO_CAO.DA_BAO }, { transaction: t });

        // 3. Hàng hoàn + chi tiết (idempotent theo unique ma_hoa_don)
        const hangHoan = await _taoHangHoanNeuChuaCo(maHoaDon, baoCao.ma_giao_hang_that_bai, t);

        // 4. Vi phạm khách hàng (idempotent theo ma_hoa_don). KHÔNG cộng điểm bù.
        // Đơn đã thanh toán trước thì KHÔNG tích vi phạm (viPham = null).
        let viPham = null;
        if (!_daThanhToan(hd)) {
            viPham = await ViPhamKhachHang.findOne({ where: { ma_hoa_don: maHoaDon }, transaction: t });
        }
        if (!viPham && !_daThanhToan(hd)) {
            const { lanNay, hanCheApDung } = await _demViPham(hd.ma_khach_hang, t);
            viPham = await ViPhamKhachHang.create({
                ma_khach_hang: hd.ma_khach_hang,
                ma_hoa_don: maHoaDon,
                ma_giao_hang_that_bai: baoCao.ma_giao_hang_that_bai,
                ly_do: `Không nhận hàng: ${baoCao.ly_do}`,
                lan_vi_pham: lanNay,
                han_che_ap_dung: hanCheApDung,
            }, { transaction: t });

            // Đủ 2 vi phạm chưa gỡ → hạn chế COD ngay lúc hủy đơn (không chờ admin kiểm tra hàng hoàn).
            // Dùng query thô vì model KhachHang phía Node có thể chưa khai báo cột quyen_cod.
            if (lanNay >= 2) {
                await sequelize.query(
                    "UPDATE khach_hang SET quyen_cod = 'GIOI_HAN' WHERE ma_khach_hang = :ma",
                    { replacements: { ma: hd.ma_khach_hang }, transaction: t }
                );
            }
        }

        return {
            huy: true,
            daFinalize: false,
            ketQua: 'DA_HUY_DO_KHACH',
            hoaDon: hd,
            baoCao,
            hangHoan,
            viPham,
        };
    });

    // Thông báo chỉ gửi đúng lần finalize thật sự (không gửi lại khi idempotent)
    if (ketQua.huy && !ketQua.daFinalize) {
        const { hoaDon, viPham } = ketQua;
        await _guiThongBao('hủy do khách', maHoaDon, () =>
            customerNotification.taoHuyDoKhach(
                hoaDon.ma_khach_hang,
                maHoaDon,
                hoaDon.maHienThi,
                { daThanhToan: _daThanhToan(hoaDon), lanViPham: viPham ? viPham.lan_vi_pham : 0 }
            )
        );
    }

    return {
        ma_hoa_don: maHoaDon,
        ket_qua: ketQua.ketQua,
        trang_thai: ketQua.huy ? 'CANCELLED' : 'DELIVERED',
        da_finalize_truoc_do: ketQua.daFinalize,
        nguon_huy: ketQua.huy ? 'CUSTOMER' : null,
        giaoHangThatBai: ketQua.baoCao || null,
        hangHoan: ketQua.hangHoan || null,
        viPham: ketQua.viPham || null,
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. LỖI SHIPPER — hủy ngay
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Chỉ khi đơn SHIPPING (đã hay chưa ARRIVED đều được). KHÔNG tạo vi phạm khách, KHÔNG tạo phạt shipper.
 */
async function baoCuaShipper(maNhanVien, id, lyDo, ghiChu = null) {
    const maHoaDon = parseMaHoaDon(id);
    const now = new Date();

    const { hoaDon, giaoHangThatBai, hangHoan } = await sequelize.transaction(async (t) => {
        const hd = await _khoaDon(maHoaDon, maNhanVien, t);
        _kiemTraChoPhepBao(hd, await _layBaoCao(maHoaDon, t));

        const [soDong] = await HoaDon.update({
            trang_thai: 'CANCELLED',
            nguon_huy: 'SHIPPER',
            ly_do_huy: lyDo,
            thoi_diem_huy: now,
            co_hang_hoan: true,
        }, {
            where: {
                ma_hoa_don: maHoaDon,
                ma_nhan_vien_giao: maNhanVien,
                trang_thai: 'SHIPPING',
            },
            transaction: t,
        });
        if (soDong === 0) {
            throw taoLoi(409, 'STATE_CHANGED', 'Trạng thái đơn vừa thay đổi, vui lòng tải lại.');
        }

        const bc = await GiaoHangThatBai.create({
            ma_hoa_don: maHoaDon,
            ma_nhan_vien_giao: maNhanVien,
            loai_loi: 'SHIPPER',
            ly_do: lyDo,
            ghi_chu: ghiChu,
            thoi_diem_bao: now,
            // Nếu shipper đã đến nơi trước khi báo lỗi thì ghi lại để tham khảo
            bat_dau_dem_10_phut: hd.ngay_den_noi ?? null,
            ket_thuc_dem_10_phut: null,
            trang_thai: TRANG_THAI_BAO_CAO.DA_BAO,
        }, { transaction: t });

        const hh = await _taoHangHoanNeuChuaCo(maHoaDon, bc.ma_giao_hang_that_bai, t);

        // KHÔNG tạo phạt shipper ngay — admin kiểm tra hàng hoàn xong mới tính.
        return { hoaDon: hd, giaoHangThatBai: bc, hangHoan: hh };
    });

    await _guiThongBao('giao thất bại do shipper', maHoaDon, () =>
        customerNotification.taoGiaoThatBai(
            hoaDon.ma_khach_hang,
            maHoaDon,
            hoaDon.maHienThi,
            'SHIPPER',
            { daThanhToan: _daThanhToan(hoaDon) }
        )
    );

    return {
        ma_hoa_don: maHoaDon,
        trang_thai: 'CANCELLED',
        nguon_huy: 'SHIPPER',
        giaoHangThatBai,
        hangHoan,
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. Được confirmDelivery gọi sau khi giao thành công (best-effort, ngoài transaction giao hàng)
// ─────────────────────────────────────────────────────────────────────────────

// Đóng báo cáo "chờ khách" vì khách đã xuống nhận. Idempotent (chỉ đổi khi còn DANG_CHO_KHACH).
async function dongBaoCaoKhiDaGiao(maHoaDon) {
    const [soDong] = await GiaoHangThatBai.update(
        { trang_thai: TRANG_THAI_BAO_CAO.KHACH_DA_NHAN },
        {
            where: {
                ma_hoa_don: maHoaDon,
                loai_loi: 'CUSTOMER',
                trang_thai: TRANG_THAI_BAO_CAO.DANG_CHO_KHACH,
            },
        }
    );
    return soDong;
}

module.exports = {
    kiemTraDieuKien,
    baoCuaKhachHang,
    finalizeKhachKhongNhan,
    baoCuaShipper,
    dongBaoCaoKhiDaGiao,
};