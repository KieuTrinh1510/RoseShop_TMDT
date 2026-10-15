// src/controllers/shipperHomeController.js
// Controller mỏng cho màn hình Home: request -> service -> JSON.
//
// Route (thêm vào shipperRoutes.js):
//   const homeController = require('../controllers/shipperHomeController');
//   router.get('/home', homeController.getHome);
//
// Trạng thái ONLINE/OFFLINE vẫn dùng route sẵn có:
//   PUT /status/online   body: { "trang_thai": "ONLINE" }

const homeService = require('../services/shipperHomeService');

exports.getHome = async (req, res) => {
    try {
        const maNhanVien = Number(req.user.ma_nguoi_dung);
        const data = await homeService.getHome(maNhanVien);
        res.json({ success: true, data });
    } catch (err) {
        console.error(err);
        res.status(err.status || 500).json({
            success: false,
            code: err.code || 'SERVER_ERROR',
            message: err.message,
        });
    }
};