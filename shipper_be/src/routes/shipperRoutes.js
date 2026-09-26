// src/routes/shipperRoutes.js
const express = require('express');
const controller = require('../controllers/shipperController');

const router = express.Router();

// Trạng thái
router.get('/status', controller.getStatus);
router.put('/status/online', controller.setOnlineStatus);

// Xem đơn
router.get('/dashboard', controller.getDashboard);
router.get('/orders', controller.getAssignedOrders);
router.get('/orders/:id', controller.getOrderDetail);
router.get('/history', controller.getOrderHistory);

// Giao hàng
router.post('/orders/:id/pickup', controller.confirmPickup);
router.post('/orders/:id/delivered', controller.markDelivered);
router.get('/orders/:id/timeout', controller.getTimeoutRemaining);

module.exports = router;