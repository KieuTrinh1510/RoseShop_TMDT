// src/services/profile/shipper_profile/logout/shipper_logout_services.js
// JWT stateless: server không lưu phiên nên không có gì để huỷ.
// App Flutter xoá token ở client khi gọi logout. (Muốn thu hồi token ngay -> cần blacklist, chưa làm.)

async function logout(maNhanVien) {
    return {
        ok: true,
        ma_nhan_vien: maNhanVien,
        ghi_chu: 'Đăng xuất thành công. App xoá token ở client.',
    };
}

module.exports = { logout };