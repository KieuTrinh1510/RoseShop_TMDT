// src/services/home/order_count/shipper_order_count_services.js
// Đếm số đơn shipper đang thực sự giữ — đọc LIVE từ bảng hoa_don.
// Đây là NGUỒN DUY NHẤT định nghĩa GIOI_HAN_DON và cách đếm đơn.
// Không tin cột nhan_vien.so_don_hien_tai (có thể lệch nếu PHP quên +1/-1).

const { Op } = require('sequelize');
const HoaDon = require('../../../models/HoaDon');

const GIOI_HAN_DON = 10;

// Đếm đơn CONFIRMED + SHIPPING — đây là 2 trạng thái shipper "đang giữ"
async function demDonDangGiu(maNhanVien) {
    return HoaDon.count({
        where: {
            ma_nhan_vien_giao: maNhanVien,
            trang_thai: { [Op.in]: ['CONFIRMED', 'SHIPPING'] },
        },
    });
}

// Trả object gọn cho tab Home hiển thị "8/10"
async function buildOrderCount(maNhanVien) {
    const soDon = await demDonDangGiu(maNhanVien);
    const isFull = soDon >= GIOI_HAN_DON;

    return {
        so_don_hien_tai: soDon,
        gioi_han_don: GIOI_HAN_DON,
        hien_thi: `${soDon}/${GIOI_HAN_DON}`,
        is_full: isFull,
    };
}

module.exports = {
    GIOI_HAN_DON,
    demDonDangGiu,
    buildOrderCount,
};