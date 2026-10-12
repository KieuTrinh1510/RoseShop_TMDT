// src/controllers/shipper/home_controller.js
// Tab TRANG CHỦ: home + trạng thái + chuông thông báo shipper.

const { layMaNhanVien, chay } = require('./_helpers');
const homeService = require('../../services/home/home_service');
const statusService = require('../../services/home/shipper_status/shipper_status_services');
const notifService = require('../../services/home/notification/notification_services');

// Trang chủ
exports.getHome = (req, res) =>
    chay(res, () => homeService.getHome(layMaNhanVien(req)));

// Trạng thái ONLINE/OFFLINE
exports.getStatus = (req, res) =>
    chay(res, () => statusService.getStatus(layMaNhanVien(req)));

exports.setOnlineStatus = (req, res) =>
    chay(res, () => statusService.setOnlineStatus(layMaNhanVien(req), req.body.trang_thai));

// Chuông thông báo shipper
exports.getNotifications = (req, res) =>
    chay(res, () => notifService.danhSach(layMaNhanVien(req), {
        loai: req.query.loai,
        limit: req.query.limit,
    }));

exports.getUnreadCount = (req, res) =>
    chay(res, async () => ({
        count: await notifService.demChuaDoc(layMaNhanVien(req)),
    }));

exports.markNotificationRead = (req, res) =>
    chay(res, async () => ({
        ok: await notifService.danhDauDaDoc(Number(req.params.id), layMaNhanVien(req)),
    }));

exports.deleteNotification = (req, res) =>
    chay(res, async () => ({
        ok: await notifService.xoa(Number(req.params.id), layMaNhanVien(req)),
    }));