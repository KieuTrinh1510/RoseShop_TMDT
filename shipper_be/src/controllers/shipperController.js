// src/controllers/shipperController.js
// Controller mỏng: lấy dữ liệu từ request -> gọi service -> trả JSON.

const statusService = require('../services/shipperStatusService');
const orderService = require('../services/shipperOrderService');
const deliveryService = require('../services/shipperDeliveryService');

// Mã shipper lấy từ user đã đăng nhập (giống Auth::user()->ma_nguoi_dung bên PHP)
function layMaNhanVien(req) {
    return Number(req.user.ma_nguoi_dung);
}

// Chạy 1 việc, thành công thì trả data, lỗi thì trả status + code mà service đã ném
async function chay(res, congViec) {
    try {
        const data = await congViec();
        res.json({ success: true, data });
    } catch (err) {
        console.error(err);
        res.status(err.status || 500).json({
            success: false,
            code: err.code || 'SERVER_ERROR',
            message: err.message,
        });
    }
}

// ── Trạng thái ──
exports.getStatus = (req, res) =>
    chay(res, () => statusService.getStatus(layMaNhanVien(req)));

// body: { "trang_thai": "ONLINE" }
exports.setOnlineStatus = (req, res) =>
    chay(res, () => statusService.setOnlineStatus(layMaNhanVien(req), req.body.trang_thai));

// ── Xem đơn ──
exports.getDashboard = (req, res) =>
    chay(res, () => orderService.getDashboard(layMaNhanVien(req)));

// query: ?trang_thai=SHIPPING&page=1&limit=20
exports.getAssignedOrders = (req, res) =>
    chay(res, () => orderService.getAssignedOrders(layMaNhanVien(req), {
        trangThai: req.query.trang_thai,
        page: req.query.page,
        limit: req.query.limit,
    }));

exports.getOrderDetail = (req, res) =>
    chay(res, () => orderService.getOrderDetail(layMaNhanVien(req), req.params.id));

exports.getOrderHistory = (req, res) =>
    chay(res, () => orderService.getOrderHistory(layMaNhanVien(req), {
        page: req.query.page,
        limit: req.query.limit,
    }));

// ── Giao hàng ──
exports.confirmPickup = (req, res) =>
    chay(res, () => deliveryService.confirmPickup(layMaNhanVien(req), req.params.id));

exports.baoDenNoi = (req, res) =>
    chay(res, () => deliveryService.baoDenNoi(layMaNhanVien(req), req.params.id));

exports.markDelivered = (req, res) =>
    chay(res, () => deliveryService.markDelivered(layMaNhanVien(req), req.params.id));

exports.getTimeoutRemaining = (req, res) =>
    chay(res, () => deliveryService.getTimeoutRemaining(layMaNhanVien(req), req.params.id));

exports.xuLyQuaHan = (req, res) =>
    chay(res, () => deliveryService.xuLyQuaHan(layMaNhanVien(req), req.params.id));