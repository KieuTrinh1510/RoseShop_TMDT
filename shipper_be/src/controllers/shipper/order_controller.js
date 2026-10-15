// src/controllers/shipper/order_controller.js
// Tab ĐƠN HÀNG: danh sách, chi tiết, 3 nút hành động, đồng hồ + xử lý quá hạn.

const { layMaNhanVien, chay } = require('./_helpers');
const orderService = require('../../services/orders/order_services');

// Danh sách + chi tiết
exports.getAssignedOrders = (req, res) =>
    chay(res, () => orderService.getAssignedOrders(layMaNhanVien(req), {
        trangThai: req.query.trang_thai,
        page: req.query.page,
        limit: req.query.limit,
    }));

exports.getOrderDetail = (req, res) =>
    chay(res, () => orderService.getOrderDetail(layMaNhanVien(req), req.params.id));

// 3 nút hành động
exports.confirmPickup = (req, res) =>
    chay(res, () => orderService.confirmPickup(layMaNhanVien(req), req.params.id));

exports.notifyArrival = (req, res) =>
    chay(res, () => orderService.notifyArrival(layMaNhanVien(req), req.params.id));

exports.confirmDelivery = (req, res) =>
    chay(res, () => orderService.confirmDelivery(layMaNhanVien(req), req.params.id));

// Đồng hồ 3h
exports.getTimeoutRemaining = (req, res) =>
    chay(res, () => orderService.getTimeoutRemaining(layMaNhanVien(req), req.params.id));

exports.xuLyQuaHan = (req, res) =>
    chay(res, () => orderService.xuLyQuaHan(layMaNhanVien(req), req.params.id));