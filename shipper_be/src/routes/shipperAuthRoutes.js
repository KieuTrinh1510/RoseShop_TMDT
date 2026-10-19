// src/routes/shipperAuthRoutes.js
// Route công khai (không cần token) cho app shipper.

const express = require('express');
const authController = require('../controllers/shipper/auth_controller');

const router = express.Router();
router.post('/login', authController.login);
router.post('/forgot-password', authController.forgotPassword);
router.post('/reset-password', authController.resetPassword);

module.exports = router;