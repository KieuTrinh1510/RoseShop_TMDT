// src/services/auth/shipper_auth_service.js
// Đăng nhập shipper bằng bảng nguoi_dung (dùng chung DB với Laravel).
// Không bao giờ trả / log mat_khau.

const crypto = require('crypto');
const bcrypt = require('bcryptjs');
const NguoiDung = require('../../models/NguoiDung');
const NhanVien = require('../../models/NhanVien');
const { taoLoi } = require('../../utils/loi');
const { kyToken } = require('../../config/jwt');

const REGEX_BCRYPT = /^\$2[aby]\$/; // Laravel: $2y$ ; bcryptjs: $2a$/$2b$ — bcryptjs đọc được cả $2y$
const DUMMY_HASH = bcrypt.hashSync('dummy-password-for-timing', 10); // chống dò username qua thời gian phản hồi

// Trả { dung, plaintext }. Plaintext chỉ là đường tương thích cho dữ liệu cũ (Laravel cũng xử lý như vậy).
async function kiemTraMatKhau(nhap, luu) {
    if (!luu) return { dung: false, plaintext: false };
    if (REGEX_BCRYPT.test(luu)) {
        return { dung: await bcrypt.compare(nhap, luu), plaintext: false };
    }
    const a = Buffer.from(nhap);
    const b = Buffer.from(luu);
    const dung = a.length === b.length && crypto.timingSafeEqual(a, b);
    return { dung, plaintext: true };
}

async function login(tenDangNhap, matKhau) {
    if (typeof tenDangNhap !== 'string' || typeof matKhau !== 'string'
        || !tenDangNhap.trim() || !matKhau) {
        throw taoLoi(400, 'MISSING_CREDENTIALS', 'Vui lòng nhập tên đăng nhập và mật khẩu.');
    }
    if (tenDangNhap.length > 50 || matKhau.length > 100) {
        throw taoLoi(401, 'INVALID_CREDENTIALS', 'Tên đăng nhập hoặc mật khẩu không đúng.');
    }

    const nd = await NguoiDung.findByUsername(tenDangNhap.trim());

    let ketQua;
    if (nd) {
        ketQua = await kiemTraMatKhau(matKhau, nd.mat_khau);
    } else {
        await bcrypt.compare(matKhau, DUMMY_HASH);
        ketQua = { dung: false, plaintext: false };
    }
    // Sai username và sai password trả CÙNG một thông báo
    if (!nd || !ketQua.dung) {
        throw taoLoi(401, 'INVALID_CREDENTIALS', 'Tên đăng nhập hoặc mật khẩu không đúng.');
    }

    if (nd.vai_tro !== 'SHIPPER') {
        throw taoLoi(403, 'FORBIDDEN_ROLE', 'Tài khoản này không phải tài khoản shipper.');
    }

    if (nd.trang_thai === 'CHO_XAC_THUC') {
        throw taoLoi(403, 'ACCOUNT_UNVERIFIED', 'Tài khoản chưa được xác thực. Vui lòng kiểm tra email để kích hoạt tài khoản.');
    }
    if (nd.trang_thai === 'BI_KHOA') {
        throw taoLoi(403, 'ACCOUNT_LOCKED', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
    }
    if (nd.trang_thai !== 'HOAT_DONG') {
        throw taoLoi(403, 'ACCOUNT_INACTIVE', 'Tài khoản hiện không thể đăng nhập.');
    }

    const nv = await NhanVien.findByPk(nd.ma_nguoi_dung);
    if (!nv) {
        throw taoLoi(404, 'SHIPPER_PROFILE_NOT_FOUND', 'Tài khoản chưa có hồ sơ nhân viên shipper. Vui lòng liên hệ quản trị viên.');
    }

    // Mật khẩu cũ dạng plaintext + đăng nhập thành công -> nâng cấp lên bcrypt (chỉ tài khoản này)
    if (ketQua.plaintext) {
        try {
            await nd.updatePassword(await bcrypt.hash(matKhau, 10));
        } catch (err) {
            console.error('[Auth] Không nâng cấp được hash mật khẩu cho tài khoản', nd.ma_nguoi_dung);
        }
    }

    const token = kyToken({
        ma_nguoi_dung: nd.ma_nguoi_dung,
        ma_nhan_vien: nv.ma_nhan_vien,
        vai_tro: nd.vai_tro,
    });

    return {
        token,
        shipper: {
            ma_nhan_vien: nv.ma_nhan_vien,
            ten_nhan_vien: nv.ten_nhan_vien,
            email: nv.email,
            so_dien_thoai: nv.so_dien_thoai,
        },
    };
}

module.exports = { login };