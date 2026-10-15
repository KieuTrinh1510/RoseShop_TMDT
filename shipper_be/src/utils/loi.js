// src/utils/loi.js
// Lỗi kèm status HTTP + mã lỗi để controller bắt và trả về client.

function taoLoi(status, code, message) {
    const err = new Error(message);
    err.status = status;
    err.code = code;
    return err;
}

// Kiểm tra :id trên URL phải là số nguyên dương
function parseMaHoaDon(id) {
    const so = Number(id);
    if (!Number.isInteger(so) || so <= 0) {
        throw taoLoi(400, 'INVALID_ID', 'Mã đơn hàng không hợp lệ.');
    }
    return so;
}

module.exports = { taoLoi, parseMaHoaDon };