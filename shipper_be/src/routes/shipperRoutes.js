// src/routes/shipperRoutes.js
// Router mới, dùng 4 controller chia theo tab.
// Controller cũ (shipperController.js, shipperHomeController.js) không còn được mount.
// App Flutter không cần đổi path cũ — tất cả endpoint cũ vẫn giữ nguyên.

const express = require('express');
const homeController = require('../controllers/shipper/home_controller');
const orderController = require('../controllers/shipper/order_controller');
const historyController = require('../controllers/shipper/history_controller');
const profileController = require('../controllers/shipper/profile_controller');
const shipperFailureController = require('../controllers/shipper/shipper_failure_controller');

const router = express.Router();

// ── TAB TRANG CHỦ ──
router.get('/home', homeController.getHome);
router.get('/dashboard', homeController.getHome); // alias cho app cũ

router.get('/status', homeController.getStatus);
router.put('/status/online', homeController.setOnlineStatus);

router.get('/notifications', homeController.getNotifications);
router.get('/notifications/unread-count', homeController.getUnreadCount);
router.patch('/notifications/:id/read', homeController.markNotificationRead);
router.delete('/notifications/:id', homeController.deleteNotification);

// ── TAB ĐƠN HÀNG ──
router.get('/orders', orderController.getAssignedOrders);
router.get('/orders/:id', orderController.getOrderDetail);

// 3 nút hành động (đúng thứ tự pickup → arrived → delivered)
router.post('/orders/:id/pickup', orderController.confirmPickup);
router.post('/orders/:id/arrived', orderController.notifyArrival);
router.post('/orders/:id/delivered', orderController.confirmDelivery);

// Đồng hồ 3h hoa tươi
router.get('/orders/:id/timeout', orderController.getTimeoutRemaining); // chỉ đọc
router.post('/orders/:id/overdue', orderController.xuLyQuaHan);          // ghi: phạt

// ── BÁO GIAO HÀNG THẤT BẠI ──
// GET  /orders/:id/failure-check   → kiểm tra điều kiện, trả về trạng thái để Flutter render UI
// POST /orders/:id/report-failure  → shipper gửi báo cáo thất bại (body: loai_loi, ly_do, ghi_chu)
//        SHIPPER: hủy ngay. CUSTOMER: chỉ ghi nhận chờ khách 10 phút (đơn vẫn SHIPPING).
// POST /orders/:id/finalize-customer-failure → hết 10 phút chờ lần hai: hủy đơn do khách không nhận hàng
router.get('/orders/:id/failure-check', shipperFailureController.kiemTraDieuKien);
router.post('/orders/:id/report-failure', shipperFailureController.baoCaoThatBai);
router.post('/orders/:id/finalize-customer-failure', shipperFailureController.finalizeLoiKhachHang);

// ── TAB LỊCH SỬ ──
router.get('/history', historyController.getHistory);
router.get('/history/filter', historyController.getHistory); // cùng handler, filter qua query

// ── TAB HỒ SƠ ──
router.get('/profile', profileController.getInfo);
router.put('/profile', profileController.updateInfo);
router.put('/profile/username', profileController.changeUsername);
router.put('/profile/password', profileController.changePassword);
router.post('/profile/logout', profileController.logout);

module.exports = router;