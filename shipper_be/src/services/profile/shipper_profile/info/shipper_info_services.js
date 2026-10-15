// src/services/profile/shipper_profile/info/shipper_info_services.js
// Xem + sửa thông tin cá nhân của shipper.
// Hàm getThongTin() cũng được home_service dùng để lấy lời chào + chữ cái đầu.

const NhanVien = require('../../../../models/NhanVien');
const NguoiDung = require('../../../../models/NguoiDung');
const { taoLoi } = require('../../../../utils/loi');

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

    if (ten_nhan_vien !== undefined) {
        if (!String(ten_nhan_vien).trim()) {
            throw taoLoi(400, 'INVALID_NAME', 'Tên không được để trống.');
        }
        if (String(ten_nhan_vien).trim().length > 50) {
            throw taoLoi(400, 'INVALID_NAME', 'Tên tối đa 50 ký tự.');
        }
        if (!/^[\p{L} ]+$/u.test(String(ten_nhan_vien).trim())) {
            throw taoLoi(400, 'INVALID_NAME', 'Tên chỉ gồm chữ cái và khoảng trắng.');
        }
    }

    if (so_dien_thoai !== undefined && so_dien_thoai !== null && so_dien_thoai !== '') {
        if (!/^\d{10}$/.test(String(so_dien_thoai))) {
            throw taoLoi(400, 'INVALID_PHONE', 'Số điện thoại phải đủ 10 chữ số.');
        }
    }

    if (email !== undefined && email !== null && email !== '') {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email))) {
            throw taoLoi(400, 'INVALID_EMAIL', 'Email không hợp lệ.');
        }
    }

    await nv.updateProfile({
        ten_nhan_vien: ten_nhan_vien ?? nv.ten_nhan_vien,
        so_dien_thoai: so_dien_thoai ?? nv.so_dien_thoai,
        email: email ?? nv.email,
    });

    return getThongTin(maNhanVien);
}

module.exports = { getThongTin, updateInfo };