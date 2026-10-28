// src/services/profile/shipper_profile/info/shipper_info_services.js
// Xem + sửa thông tin cá nhân của shipper.
// Hàm getThongTin() cũng được home_service dùng để lấy lời chào + chữ cái đầu.

const NhanVien = require('../../../../models/NhanVien');
const NguoiDung = require('../../../../models/NguoiDung');
const { taoLoi } = require('../../../../utils/loi');

// Email đã thuộc tài khoản khác chưa? (nhan_vien trừ chính mình + khach_hang)
async function emailDaDuocDung(maNhanVien, email) {
    const sequelize = NhanVien.sequelize;
    const [nv] = await sequelize.query(
        'SELECT ma_nhan_vien FROM nhan_vien WHERE LOWER(email) = LOWER(:email) AND ma_nhan_vien <> :ma LIMIT 1',
        { replacements: { email, ma: maNhanVien } }
    );
    if (nv.length > 0) return true;
    const [kh] = await sequelize.query(
        'SELECT 1 FROM khach_hang WHERE LOWER(email) = LOWER(:email) LIMIT 1',
        { replacements: { email } }
    );
    return kh.length > 0;
}

async function getThongTin(maNhanVien) {
    const nv = await NhanVien.findByPk(maNhanVien);
    if (!nv) {
        throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    }
    const nd = await NguoiDung.findById(maNhanVien);

    return {
        ma_nhan_vien: nv.ma_nhan_vien,
        ten_nhan_vien: nv.ten_nhan_vien,
        chu_cai_dau: nv.chuCaiDau,
        trang_thai_hoat_dong: nv.trang_thai_hoat_dong,
        so_dien_thoai: nv.so_dien_thoai,
        email: nv.email,
        chuc_vu: nv.chuc_vu,
        ten_dang_nhap: nd?.ten_dang_nhap || null,
        vai_tro: nd?.vai_tro || null,
    };
}

async function updateInfo(maNhanVien, { ten_nhan_vien, so_dien_thoai, email } = {}) {
    const nv = await NhanVien.findByPk(maNhanVien);
    if (!nv) {
        throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    }

    // Mặc định giữ nguyên giá trị cũ; chỉ ghi đè khi client GỬI field đó.
    let tenMoi = nv.ten_nhan_vien;
    let sdtMoi = nv.so_dien_thoai;
    let emailMoi = nv.email;

    // ── ten_nhan_vien: bắt buộc, 1–50, chỉ chữ cái + khoảng trắng ──
    if (ten_nhan_vien !== undefined) {
        const s = String(ten_nhan_vien ?? '').trim();
        if (!s) {
            throw taoLoi(400, 'INVALID_NAME', 'Tên không được để trống.');
        }
        if (s.length > 50) {
            throw taoLoi(400, 'INVALID_NAME', 'Tên tối đa 50 ký tự.');
        }
        if (!/^[\p{L} ]+$/u.test(s)) {
            throw taoLoi(400, 'INVALID_NAME', 'Tên chỉ gồm chữ cái và khoảng trắng.');
        }
        tenMoi = s;
    }

    // ── so_dien_thoai: nếu GỬI thì không được rỗng + đúng 10 chữ số ──
    if (so_dien_thoai !== undefined) {
        const s = String(so_dien_thoai ?? '').trim();
        if (!s) {
            throw taoLoi(400, 'INVALID_PHONE', 'Số điện thoại không được để trống.');
        }
        if (!/^\d{10}$/.test(s)) {
            throw taoLoi(400, 'INVALID_PHONE', 'Số điện thoại phải đủ 10 chữ số.');
        }
        sdtMoi = s;
    }

    // ── email: nếu GỬI thì không được rỗng + ≤ 100 + đúng format + không trùng ──
    if (email !== undefined) {
        const s = String(email ?? '').trim();
        if (!s) {
            throw taoLoi(400, 'INVALID_EMAIL', 'Email không được để trống.');
        }
        if (s.length > 100) {
            throw taoLoi(400, 'INVALID_EMAIL', 'Email tối đa 100 ký tự.');
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s)) {
            throw taoLoi(400, 'INVALID_EMAIL', 'Email không hợp lệ.');
        }
        const emailCu = String(nv.email || '').trim();
        if (s.toLowerCase() !== emailCu.toLowerCase()) {
            if (await emailDaDuocDung(maNhanVien, s)) {
                throw taoLoi(409, 'EMAIL_ALREADY_USED',
                    'Email này đã được sử dụng cho tài khoản khác.');
            }
        }
        emailMoi = s;
    }

    await nv.updateProfile({
        ten_nhan_vien: tenMoi,
        so_dien_thoai: sdtMoi,
        email: emailMoi,
    });

    return getThongTin(maNhanVien);
}

module.exports = { getThongTin, updateInfo };