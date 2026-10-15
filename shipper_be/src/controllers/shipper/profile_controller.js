// src/controllers/shipper/profile_controller.js
// Tab HỒ SƠ: thông tin cá nhân, tài khoản, đăng xuất.

const { layMaNhanVien, chay } = require('./_helpers');
const infoService = require('../../services/profile/shipper_profile/info/shipper_info_services');
const accountService = require('../../services/profile/shipper_profile/account/shipper_account_services');
const logoutService = require('../../services/profile/shipper_profile/logout/shipper_logout_services');

// Thông tin cá nhân
exports.getInfo = (req, res) =>
    chay(res, () => infoService.getThongTin(layMaNhanVien(req)));

exports.updateInfo = (req, res) =>
    chay(res, () => infoService.updateInfo(layMaNhanVien(req), {
        ten_nhan_vien: req.body.ten_nhan_vien,
        so_dien_thoai: req.body.so_dien_thoai,
        email: req.body.email,
    }));

// Tài khoản
exports.changeUsername = (req, res) =>
    chay(res, () => accountService.changeUsername(layMaNhanVien(req), req.body.ten_dang_nhap_moi));

exports.changePassword = (req, res) =>
    chay(res, () => accountService.changePassword(
        layMaNhanVien(req),
        req.body.mat_khau_cu,
        req.body.mat_khau_moi
    ));

// Đăng xuất (placeholder)
exports.logout = (req, res) =>
    chay(res, () => logoutService.logout(layMaNhanVien(req)));