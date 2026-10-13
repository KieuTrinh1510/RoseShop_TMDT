// src/services/orders/order_services.js
// Facade cho tab Đơn hàng — controller chỉ cần require file này.
// Re-export từ các sub-service để giữ API cũ.

const { getAssignedOrders } = require('./order_list/order_list_services');
const { getOrderDetail } = require('./order_detail/order_detail_services');
const { confirmPickup } = require('./confirm_pickup/confirm_pickup_services');
const { notifyArrival } = require('./notify_arrival/notify_arrival_services');
const { confirmDelivery } = require('./confirm_delivery/confirm_delivery_services');
const {
    getTimeoutRemaining,
    THOI_GIAN_TOI_DA_GIAY,
    tinhDongHo,
    donLaHoaTuoi,
} = require('./delivery_timer/delivery_timer_services');
const { xuLyQuaHan } = require('./delivery_penalty/delivery_penalty_services');

module.exports = {
    getAssignedOrders,
    getOrderDetail,
    confirmPickup,
    notifyArrival,
    confirmDelivery,
    getTimeoutRemaining,
    xuLyQuaHan,
    THOI_GIAN_TOI_DA_GIAY,
    tinhDongHo,
    donLaHoaTuoi,
};