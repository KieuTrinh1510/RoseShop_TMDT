// src/routes/shipperRoutes.js
const express = require('express');
const controller = require('../controllers/shipperController');
const homeController = require('../controllers/shipperHomeController');

const router = express.Router();

// LƯU Ý: file này chưa có authenticate / check vai trò SHIPPER.
// Phải chắc chắn app.js đã gắn middleware đó trước khi mount router này, ví dụ:
//   app.use('/api/shipper', authenticate, requireRole('SHIPPER'), shipperRoutes);

// Home
router.get('/home', homeController.getHome);

// Trạng thái
router.get('/status', controller.getStatus);
router.put('/status/online', controller.setOnlineStatus);

// Xem đơn
router.get('/dashboard', controller.getDashboard);
router.get('/orders', controller.getAssignedOrders);
router.get('/orders/:id', controller.getOrderDetail);
router.get('/history', controller.getOrderHistory);

// Giao hàng (thứ tự thao tác: pickup -> arrived -> delivered)
router.post('/orders/:id/pickup', controller.confirmPickup);     // CONFIRMED -> SHIPPING
router.post('/orders/:id/arrived', controller.baoDenNoi);        // báo khách "đã đến điểm giao"
router.post('/orders/:id/delivered', controller.markDelivered);  // SHIPPING -> DELIVERED (phải đã arrived)

// Đồng hồ 3h hoa tươi
router.get('/orders/:id/timeout', controller.getTimeoutRemaining); // chỉ đọc
router.post('/orders/:id/overdue', controller.xuLyQuaHan);

module.exports = router;