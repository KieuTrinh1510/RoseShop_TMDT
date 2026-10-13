// src/services/profile/shipper_profile/logout/shipper_logout_services.js
// Placeholder — chưa có auth thật (JWT/session) nên chỉ trả về success.
// Khi có auth thật: revoke token / xoá session ở đây.

async function logout(maNhanVien) {
    // TODO: khi có JWT/session thật:
    //   - Xoá refresh token khỏi DB
    //   - Thêm access token vào blacklist (nếu có)
    return {
        ok: true,
        ma_nhan_vien: maNhanVien,
        ghi_chu: 'Chưa có auth thật, hiện chỉ trả về thành công. App tự xoá token ở client.',
    };
}

module.exports = { logout };