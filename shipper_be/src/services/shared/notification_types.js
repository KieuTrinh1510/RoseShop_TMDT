// src/services/shared/notification_types.js
// Chỉ chứa hằng số loại thông báo — KHÔNG phải service, không chứa logic.
// Cả home/ (thông báo shipper) và orders/ (thông báo khách) đều dùng chung các mã này.

const LOAI = {
    SHIPPING: 'shipping',
    ARRIVED: 'arrived',
    DELIVERED: 'delivered',
    APOLOGY: 'apology',
    FAILURE: 'failure',
    PENALTY: 'penalty',
    INFO: 'info',
    // 2 loại chủ yếu bên PHP ghi, Node chỉ đọc để hiển thị:
    ORDER_NEW: 'order_new',
    PAYMENT: 'payment',
};

module.exports = LOAI;
