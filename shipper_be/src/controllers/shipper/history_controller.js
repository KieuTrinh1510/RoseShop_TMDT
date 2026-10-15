// src/controllers/shipper/history_controller.js
// Tab LỊCH SỬ: danh sách đơn đã giao, có filter ngày/khoảng/tháng/mã.

const { layMaNhanVien, chay } = require('./_helpers');
const historyService = require('../../services/history/order_history/history_list/history_list_services');

// GET /history?page=1&limit=10&ngay=...&tu_ngay=...&den_ngay=...&thang=...&ma=...
exports.getHistory = (req, res) =>
    chay(res, () => historyService.getHistory(layMaNhanVien(req), {
        page: req.query.page,
        limit: req.query.limit,
        ngay: req.query.ngay,
        tu_ngay: req.query.tu_ngay,
        den_ngay: req.query.den_ngay,
        thang: req.query.thang,
        ma: req.query.ma,
    }));