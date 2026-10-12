// src/services/profile/shipper_profile/account/shipper_account_services.js
// Đổi tên đăng nhập + đổi mật khẩu.
// Hash mật khẩu: dùng bcryptjs nếu đã cài; nếu chưa cài thì fallback plaintext (có cảnh báo).

const NguoiDung = require('../../../../models/NguoiDung');
const { taoLoi } = require('../../../../utils/loi');

let bcrypt = null;
try {
    bcrypt = require('bcryptjs');
} catch {
    console.warn('[Account] Chưa cài bcryptjs — mật khẩu đang lưu dạng plaintext. Cài: npm i bcryptjs');
}

const REGEX_USERNAME = /^[a-zA-Z0-9_.]{3,30}$/;

async function kiemTraMatKhau(nhap, luu) {
    if (!luu) return false;
    // Nếu DB đang lưu hash bcrypt ($2a$/$2b$/$2y$)
    if (bcrypt && /^\$2[aby]\$/.test(luu)) {
        return bcrypt.compare(nhap, luu);
    }
    // Còn lại coi như plaintext
    return nhap === luu;
}

async function hashMatKhau(matKhau) {
    if (bcrypt) return bcrypt.hash(matKhau, 10);
    return matKhau;
}

async function changeUsername(maNhanVien, tenDangNhapMoi) {
    if (!tenDangNhapMoi || !REGEX_USERNAME.test(String(tenDangNhapMoi))) {
        throw taoLoi(400, 'INVALID_USERNAME',
            'Tên đăng nhập 3–30 ký tự, chỉ gồm chữ, số, dấu chấm và gạch dưới.');
    }
    const moi = String(tenDangNhapMoi);

    const nd = await NguoiDung.findById(maNhanVien);
    if (!nd) throw taoLoi(404, 'ACCOUNT_NOT_FOUND', 'Không tìm thấy tài khoản.');

    if (nd.ten_dang_nhap === moi) {
        return { ten_dang_nhap: nd.ten_dang_nhap, ghi_chu: 'Tên đăng nhập không đổi.' };
    }

    const trung = await NguoiDung.findOne({ where: { ten_dang_nhap: moi } });
    if (trung && trung.ma_nguoi_dung !== maNhanVien) {
        throw taoLoi(409, 'USERNAME_TAKEN', 'Tên đăng nhập đã được sử dụng.');
    }

    await nd.update({ ten_dang_nhap: moi });
    return { ten_dang_nhap: nd.ten_dang_nhap };
}

async function changePassword(maNhanVien, matKhauCu, matKhauMoi) {
    if (!matKhauCu || !matKhauMoi) {
        throw taoLoi(400, 'MISSING_PASSWORD', 'Cần nhập đủ mật khẩu cũ và mật khẩu mới.');
    }
    if (String(matKhauMoi).length < 6) {
        throw taoLoi(400, 'INVALID_PASSWORD', 'Mật khẩu mới phải từ 6 ký tự.');
    }
    if (String(matKhauMoi).length > 100) {
        throw taoLoi(400, 'INVALID_PASSWORD', 'Mật khẩu mới quá dài.');
    }
    if (matKhauCu === matKhauMoi) {
        throw taoLoi(400, 'SAME_PASSWORD', 'Mật khẩu mới không được trùng mật khẩu cũ.');
    }

    const nd = await NguoiDung.findById(maNhanVien);
    if (!nd) throw taoLoi(404, 'ACCOUNT_NOT_FOUND', 'Không tìm thấy tài khoản.');

    const dung = await kiemTraMatKhau(String(matKhauCu), nd.mat_khau);
    if (!dung) {
        throw taoLoi(401, 'WRONG_PASSWORD', 'Mật khẩu cũ không đúng.');
    }

    const hash = await hashMatKhau(String(matKhauMoi));
    await nd.updatePassword(hash);
    return { ok: true };
}

module.exports = { changeUsername, changePassword };