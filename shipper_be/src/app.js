// src/app.js
const express = require("express");
const cors = require("cors");
const app = express();

const shipperRoutes = require("./routes/shipperRoutes");
// Middleware đăng nhập tạm: phải gắn req.user = { ma_nguoi_dung, ... }
// Khi có đăng nhập thật thì thay bằng authMiddleware + kiểm tra vai_tro === 'SHIPPER'
const fakeAuthMiddleware = require("./middleware/fakeAuthMiddleware");

app.use(cors());
app.use(express.json());

app.use("/api/shipper", fakeAuthMiddleware, shipperRoutes);

// Route không tồn tại
app.use((req, res) => {
    res.status(404).json({
        success: false,
        code: "NOT_FOUND",
        message: `Không tìm thấy ${req.method} ${req.originalUrl}`,
    });
});

// Lỗi chưa được bắt ở đâu (vd: JSON body sai định dạng)
app.use((err, req, res, next) => {
    console.error(err);
    res.status(err.status || 500).json({
        success: false,
        code: err.code || "SERVER_ERROR",
        message: err.message,
    });
});

module.exports = app;