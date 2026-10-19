// src/app.js
const express = require("express");
const cors = require("cors");
const app = express();

const shipperRoutes = require("./routes/shipperRoutes");
const shipperAuthRoutes = require("./routes/shipperAuthRoutes");
// Auth thật: JWT Bearer, yêu cầu vai_tro === 'SHIPPER' (fakeAuthMiddleware không còn được mount)
const authMiddleware = require("./middleware/authMiddleware");

app.use(cors());
app.use(express.json());

// Login: công khai, phải mount TRƯỚC route yêu cầu token
app.use("/api/shipper/auth", shipperAuthRoutes);
app.use("/api/shipper", authMiddleware, shipperRoutes);

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