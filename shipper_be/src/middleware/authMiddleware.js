// src/middleware/authMiddleware.js
// Auth thật cho API shipper: Authorization: Bearer <JWT>.
//  - thiếu / sai / hết hạn token -> 401
//  - token hợp lệ nhưng không phải SHIPPER -> 403
// Gắn req.user = { ma_nguoi_dung, ma_nhan_vien, vai_tro } (lấy từ token, KHÔNG từ client).

const { xacThucToken } = require('../config/jwt');

function loi(res, status, code, message) {
    return res.status(status).json({ success: false, code, message });
}

module.exports = function authMiddleware(req, res, next) {
    const header = req.header('authorization') || '';
    const match = /^Bearer\s+(\S+)$/i.exec(header.trim());
    if (!match) {
        return loi(res, 401, 'TOKEN_MISSING', 'Thiếu hoặc sai định dạng header Authorization (Bearer <token>).');
    }

    let payload;
    try {
        payload = xacThucToken(match[1]);
    } catch (err) {
        if (err.name === 'TokenExpiredError') {
            return loi(res, 401, 'TOKEN_EXPIRED', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
        }
        if (err.name === 'JsonWebTokenError' || err.name === 'NotBeforeError') {
            return loi(res, 401, 'TOKEN_INVALID', 'Token không hợp lệ.');
        }
        return next(err); // lỗi cấu hình (vd: thiếu JWT_SECRET) -> 500
    }

    if (payload.vai_tro !== 'SHIPPER') {
        return loi(res, 403, 'FORBIDDEN_ROLE', 'Tài khoản không có quyền truy cập API shipper.');
    }

    const maNhanVien = Number(payload.ma_nhan_vien);
    const maNguoiDung = Number(payload.ma_nguoi_dung);
    if (!Number.isInteger(maNhanVien) || maNhanVien <= 0 || !Number.isInteger(maNguoiDung)) {
        return loi(res, 401, 'TOKEN_INVALID', 'Token không hợp lệ.');
    }

    req.user = { ma_nguoi_dung: maNguoiDung, ma_nhan_vien: maNhanVien, vai_tro: payload.vai_tro };
    next();
};