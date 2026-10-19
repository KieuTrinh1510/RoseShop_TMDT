// src/config/jwt.js
// Ký / verify JWT cho app shipper. Secret CHỈ lấy từ biến môi trường JWT_SECRET.

const jwt = require('jsonwebtoken');
const { taoLoi } = require('../utils/loi');

const ALGORITHM = 'HS256';
const SECRET_MAU = 'change_this_to_a_long_random_secret'; // giá trị mẫu trong .env.example

function laySecret() {
    const secret = process.env.JWT_SECRET;
    if (!secret || secret.length < 16) {
        throw taoLoi(500, 'JWT_NOT_CONFIGURED', 'Server chưa cấu hình JWT_SECRET (tối thiểu 16 ký tự).');
    }
    if (process.env.NODE_ENV === 'production' && secret === SECRET_MAU) {
        throw taoLoi(500, 'JWT_NOT_CONFIGURED', 'JWT_SECRET đang là giá trị mẫu, phải đổi trước khi chạy production.');
    }
    return secret;
}

function kyToken(payload) {
    return jwt.sign(payload, laySecret(), {
        algorithm: ALGORITHM,
        expiresIn: process.env.JWT_EXPIRES_IN || '7d',
    });
}

function xacThucToken(token) {
    return jwt.verify(token, laySecret(), { algorithms: [ALGORITHM] });
}

module.exports = { kyToken, xacThucToken };