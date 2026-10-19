// src/controllers/shipper/auth_controller.js
// POST /api/shipper/auth/login            (public, không qua authMiddleware)
// POST /api/shipper/auth/forgot-password  (public) body: { email }
// POST /api/shipper/auth/reset-password   (public) body: { email, ma_xac_thuc, mat_khau_moi }

const authService = require('../../services/auth/shipper_auth_service');
const resetService = require('../../services/auth/shipper_password_reset_service');

function traLoi(res, err, tag) {
    if (!err.status) console.error(`[Auth] ${tag} error:`, err.message);
    res.status(err.status || 500).json({
        success: false,
        code: err.code || 'SERVER_ERROR',
        message: err.status ? err.message : 'Lỗi máy chủ.',
    });
}

exports.login = async (req, res) => {
    try {
        const body = req.body || {};
        const { token, shipper } = await authService.login(body.ten_dang_nhap, body.mat_khau);
        res.json({ success: true, token, shipper });
    } catch (err) {
        traLoi(res, err, 'login');
    }
};

exports.forgotPassword = async (req, res) => {
    try {
        const body = req.body || {};
        const data = await resetService.guiMa(body.email);
        res.json({ success: true, ...data });
    } catch (err) {
        traLoi(res, err, 'forgotPassword');
    }
};

exports.resetPassword = async (req, res) => {
    try {
        const body = req.body || {};
        const data = await resetService.datLaiMatKhau(body.email, body.ma_xac_thuc, body.mat_khau_moi);
        res.json({ success: true, ...data });
    } catch (err) {
        traLoi(res, err, 'resetPassword');
    }
};