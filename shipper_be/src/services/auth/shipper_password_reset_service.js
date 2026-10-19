// src/services/auth/shipper_password_reset_service.js
// Quên mật khẩu cho shipper: gửi mã 6 số qua email (lấy từ nhan_vien.email) rồi đặt lại mật khẩu.
// Cùng quy tắc với phần khách hàng ở Laravel: bảng yeu_cau_dat_lai_mat_khau, hiệu lực 60 phút,
// yêu cầu cũ bị vô hiệu khi tạo yêu cầu mới, mã chỉ dùng được một lần.

const crypto = require('crypto');
const bcrypt = require('bcryptjs');
const sequelize = require('../../config/database');
const NguoiDung = require('../../models/NguoiDung');
const NhanVien = require('../../models/NhanVien');
const YeuCau = require('../../models/YeuCauDatLaiMatKhau');
const { taoLoi } = require('../../utils/loi');
const { guiMaDatLaiMatKhau } = require('../../utils/mailer');

const HIEU_LUC_PHUT = 60;
const CHO_GUI_LAI_GIAY = 60; // chống spam email
const TOI_DA_NHAP_SAI = 5; // sai quá số lần này thì vô hiệu mã, phải xin mã mới
const REGEX_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Đếm số lần nhập sai mã theo email (bộ nhớ tiến trình, tự hết hạn)
const demSai = new Map();

function layDemSai(email) {
    const m = demSai.get(email);
    if (!m) return 0;
    if (Date.now() > m.hetHan) {
        demSai.delete(email);
        return 0;
    }
    return m.so;
}

function tangDemSai(email) {
    const so = layDemSai(email) + 1;
    demSai.set(email, { so, hetHan: Date.now() + HIEU_LUC_PHUT * 60 * 1000 });
    return so;
}

function chuanHoaEmail(email) {
    if (typeof email !== 'string') {
        throw taoLoi(400, 'INVALID_EMAIL', 'Vui lòng nhập địa chỉ email.');
    }
    const e = email.trim();
    if (!e) throw taoLoi(400, 'INVALID_EMAIL', 'Vui lòng nhập địa chỉ email.');
    if (e.length > 100 || !REGEX_EMAIL.test(e)) {
        throw taoLoi(400, 'INVALID_EMAIL', 'Địa chỉ email không đúng định dạng.');
    }
    return e;
}

// Tìm tài khoản SHIPPER có email này (nhan_vien.email, nhan_vien.ma_nhan_vien = nguoi_dung.ma_nguoi_dung)
async function timShipperTheoEmail(email) {
    const dsNv = await NhanVien.findAll({ where: { email } });
    for (const nv of dsNv) {
        const nd = await NguoiDung.findByPk(nv.ma_nhan_vien);
        if (nd && nd.vai_tro === 'SHIPPER') return { nv, nd };
    }
    return null;
}

function kiemTraTrangThaiTaiKhoan(nd) {
    if (nd.trang_thai === 'BI_KHOA') {
        throw taoLoi(403, 'ACCOUNT_LOCKED', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
    }
    if (nd.trang_thai === 'CHO_XAC_THUC') {
        throw taoLoi(403, 'ACCOUNT_UNVERIFIED', 'Tài khoản chưa được xác thực. Vui lòng liên hệ quản trị viên.');
    }
}

// ── Bước 1: gửi mã ───────────────────────────────────────────
async function guiMa(emailNhap) {
    const email = chuanHoaEmail(emailNhap);

    const tk = await timShipperTheoEmail(email);
    if (!tk) {
        throw taoLoi(404, 'EMAIL_NOT_FOUND', 'Địa chỉ email này chưa được đăng ký cho tài khoản shipper nào.');
    }
    const { nv, nd } = tk;
    kiemTraTrangThaiTaiKhoan(nd);

    // Chống bấm gửi liên tục
    const ganNhat = await YeuCau.findOne({
        where: { ma_nguoi_dung: nd.ma_nguoi_dung },
        order: [['id', 'DESC']],
    });
    if (ganNhat && ganNhat.created_at) {
        const daQua = (Date.now() - new Date(ganNhat.created_at).getTime()) / 1000;
        if (daQua >= 0 && daQua < CHO_GUI_LAI_GIAY) {
            const con = Math.ceil(CHO_GUI_LAI_GIAY - daQua);
            throw taoLoi(429, 'TOO_MANY_REQUESTS', `Vui lòng đợi ${con} giây rồi yêu cầu gửi lại mã.`);
        }
    }

    const ma = String(crypto.randomInt(100000, 1000000)); // 6 chữ số
    const token = crypto.randomBytes(32).toString('hex'); // 64 ký tự (cột token bắt buộc)
    const bayGio = new Date();

    // Vô hiệu hóa yêu cầu cũ còn hiệu lực, tạo yêu cầu mới
    const yeuCau = await sequelize.transaction(async (t) => {
        await YeuCau.update(
            { trang_thai: YeuCau.TRANG_THAI_VO_HIEU_HOA, updated_at: bayGio },
            {
                where: {
                    ma_nguoi_dung: nd.ma_nguoi_dung,
                    trang_thai: YeuCau.TRANG_THAI_CO_HIEU_LUC,
                },
                transaction: t,
            }
        );
        return YeuCau.create({
            ma_nguoi_dung: nd.ma_nguoi_dung,
            email: nv.email,
            ma_xac_thuc: ma,
            token,
            trang_thai: YeuCau.TRANG_THAI_CO_HIEU_LUC,
            expires_at: new Date(bayGio.getTime() + HIEU_LUC_PHUT * 60 * 1000),
            created_at: bayGio,
            updated_at: bayGio,
        }, { transaction: t });
    });

    try {
        await guiMaDatLaiMatKhau({
            email: nv.email,
            ten: nv.ten_nhan_vien,
            ma,
            phut: HIEU_LUC_PHUT,
        });
    } catch (err) {
        // Gửi mail lỗi: xóa yêu cầu, mật khẩu không bị đổi
        await yeuCau.destroy().catch(() => { });
        if (err.status) throw err; // vd MAIL_NOT_CONFIGURED
        console.error('[Auth] Lỗi gửi email đặt lại mật khẩu:', err.message);
        throw taoLoi(502, 'MAIL_SEND_FAILED', 'Hệ thống gửi thư tạm thời gặp sự cố. Mật khẩu của bạn không bị thay đổi, vui lòng thử lại sau.');
    }

    demSai.delete(email.toLowerCase());
    return {
        message: 'Mã xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư (kể cả mục Thư rác).',
        het_han_sau_phut: HIEU_LUC_PHUT,
        gui_lai_sau_giay: CHO_GUI_LAI_GIAY,
    };
}

// ── Bước 2: đặt lại mật khẩu ─────────────────────────────────
async function datLaiMatKhau(emailNhap, maNhap, matKhauMoi) {
    const email = chuanHoaEmail(emailNhap);
    const khoa = email.toLowerCase();
    const ma = typeof maNhap === 'string' ? maNhap.trim() : '';

    if (!/^\d{6}$/.test(ma)) {
        throw taoLoi(400, 'INVALID_CODE', 'Mã xác thực gồm đúng 6 chữ số.');
    }
    if (typeof matKhauMoi !== 'string' || matKhauMoi.length < 6) {
        throw taoLoi(400, 'WEAK_PASSWORD', 'Mật khẩu mới phải có tối thiểu 6 ký tự.');
    }
    if (matKhauMoi.length > 100) {
        throw taoLoi(400, 'WEAK_PASSWORD', 'Mật khẩu mới không được vượt quá 100 ký tự.');
    }

    const tk = await timShipperTheoEmail(email);
    if (!tk) {
        throw taoLoi(404, 'EMAIL_NOT_FOUND', 'Không tìm thấy tài khoản shipper tương ứng với email này.');
    }
    const { nd } = tk;
    kiemTraTrangThaiTaiKhoan(nd);

    if (layDemSai(khoa) >= TOI_DA_NHAP_SAI) {
        await YeuCau.update(
            { trang_thai: YeuCau.TRANG_THAI_VO_HIEU_HOA, updated_at: new Date() },
            { where: { ma_nguoi_dung: nd.ma_nguoi_dung, trang_thai: YeuCau.TRANG_THAI_CO_HIEU_LUC } }
        );
        throw taoLoi(429, 'TOO_MANY_ATTEMPTS', 'Bạn đã nhập sai mã quá nhiều lần. Vui lòng yêu cầu gửi mã mới.');
    }

    const yeuCau = await YeuCau.findOne({
        where: { ma_nguoi_dung: nd.ma_nguoi_dung, ma_xac_thuc: ma },
        order: [['id', 'DESC']],
    });

    if (!yeuCau) {
        const so = tangDemSai(khoa);
        const con = Math.max(TOI_DA_NHAP_SAI - so, 0);
        throw taoLoi(400, 'INVALID_CODE', con > 0
            ? `Mã xác thực không chính xác. Bạn còn ${con} lần thử.`
            : 'Mã xác thực không chính xác. Vui lòng yêu cầu gửi mã mới.');
    }
    if (yeuCau.trang_thai === YeuCau.TRANG_THAI_DA_SU_DUNG) {
        throw taoLoi(400, 'CODE_USED', 'Mã xác thực này đã được sử dụng. Vui lòng gửi yêu cầu mới nếu cần đổi lại mật khẩu.');
    }
    if (yeuCau.trang_thai === YeuCau.TRANG_THAI_VO_HIEU_HOA) {
        throw taoLoi(400, 'CODE_REPLACED', 'Mã này đã bị vô hiệu hóa do có yêu cầu mới hơn. Vui lòng dùng mã trong email mới nhất.');
    }
    if (yeuCau.trang_thai !== YeuCau.TRANG_THAI_CO_HIEU_LUC) {
        throw taoLoi(400, 'CODE_INACTIVE', 'Yêu cầu đặt lại mật khẩu hiện không còn hiệu lực.');
    }
    if (yeuCau.isExpired()) {
        throw taoLoi(400, 'CODE_EXPIRED', `Mã xác thực đã hết hạn (chỉ có hiệu lực trong ${HIEU_LUC_PHUT} phút). Vui lòng gửi yêu cầu mới.`);
    }

    const hash = await bcrypt.hash(matKhauMoi, 10);
    const bayGio = new Date();

    await sequelize.transaction(async (t) => {
        await nd.update({ mat_khau: hash }, { fields: ['mat_khau'], transaction: t });
        await yeuCau.update(
            { trang_thai: YeuCau.TRANG_THAI_DA_SU_DUNG, updated_at: bayGio },
            { transaction: t }
        );
    });

    demSai.delete(khoa);
    return { message: 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập bằng mật khẩu mới.' };
}

module.exports = { guiMa, datLaiMatKhau };