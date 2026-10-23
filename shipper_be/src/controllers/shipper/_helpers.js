// src/controllers/shipper/_helpers.js
// 2 helper dùng chung cho 4 controller tab.

// Mã shipper lấy từ JWT đã verify (authMiddleware gắn req.user). Không đọc từ header/body của client.
function layMaNhanVien(req) {
    return Number(req.user.ma_nhan_vien);
}

// Chạy 1 việc, thành công thì trả data, lỗi thì trả status + code mà service đã ném
async function chay(res, congViec) {
    try {
        const data = await congViec();
        res.json({ success: true, data });
    } catch (err) {
        console.error(err);
        res.status(err.status || 500).json({
            success: false,
            code: err.code || 'SERVER_ERROR',
            message: err.message,
        });
    }
}

module.exports = { layMaNhanVien, chay };