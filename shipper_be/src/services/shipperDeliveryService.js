const HoaDon = require('../models/HoaDon');
const redis = require('../config/redis');
const shipperStatusService = require('./shipperStatusService');

const THOI_GIAN_TOI_DA_GIAY = 3 * 60 * 60 // 3 tieng
const TTL_REDIS_GIAY = 24 * 60 * 60; // key cua redis ton tai 3 tieng


function taoLoi(status, code, message) {
    const err = new Error(message);
    err.status = status;
    err.code = code;

    return err;
}
// Tên key Redis lưu mốc thời gian bắt đầu giao, ví dụ: shipping_start_15
function shippingStartKey(maHoaDon) {
    return `shipping_start_${maHoaDon}`;
}


// Tìm đơn theo mã VÀ phải thuộc shipper này, không có thì ném lỗi 404

async function timDonHoacNemLoi(maNhanVien, maHoaDon) {
    const hoadon = await HoaDon.findByIdAndShipper(maHoaDon, maNhanVien);

    if (!hoadon) {
        throw taoLoi(404, 'ORDER_NOT_FOUND', 'khong tim thay don hang');
    }
    return hoadon;
}

// Nút "Nhận đơn": CONFIRMED -> SHIPPING
async function confirmPickup(maNhanVien, maHoaDon) {
    const hoadon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    if (hoadon.trang_thai !== 'CONFIRMED') {
        throw taoLoi(422, 'INVALID_STATE', 'don hang khong o trang thai CONFIRMED');

    }
    await hoadon.updateTrangThai('SHIPPING');

    // Lưu mốc bắt đầu giao vào Redis (sống 24h) để sau này tính giao trễ quá 3h
    // set(key, value, 'EX', số giây) = lưu kèm thời gian tự hết hạn

    const thoiGianBatDau = new Date().toString();
    await redis.set(shippingStartKey(maHoaDon), thoiGianBatDau, 'EX', TTL_REDIS_GIAY);


    console.log(` [DELIVERY] Shipper ${maNhanVien} nhan don ${maHoaDon}`);

    return {
        ma_hoa_don: hoadon.ma_hoa_don,
        trang_thai: hoadon.trang_thai,
        thoi_gian_bat_dau: hoadon.thoiGianBatDau,
    };
}
// Nút "Giao thành công": SHIPPING -> DELIVERED

async function markDelivered(maNhanVien, maHoaDon) {
    const hoaDon = await timDonHoacNemLoi(maNhanVien, maHoaDon);

    // Đổi trạng thái + ghi ngày giao + đánh dấu đã thanh toán (giống code PHP cũ).
    // Điều kiện "trang_thai = SHIPPING" nằm ngay trong câu UPDATE: nếu shipper bấm 2 lần
    // liên tiếp thì chỉ 1 request cập nhật được (soDong = 1), request còn lại soDong = 0.
    // Nhờ vậy số đơn chỉ bị trừ đúng 1 lần.
    const ngayGiao = new Date();
    const [soDong] = await HoaDon.update(
        {
            trang_thai: 'DELIVERED',
            ngay_giao: ngayGiao,
            trang_thai_thanh_toan: 'DA_THANH_TOAN',
        },
        {
            where: {
                ma_hoa_don: maHoaDon,
                ma_nhan_vien_giao: maNhanVien,
                trang_thai: 'SHIPPING',
            },
        }
    );

    if (soDong === 0) {
        throw taoLoi(422, 'INVALID_STATE', 'Đơn hàng không ở trạng thái SHIPPING.');
    }

    // Giao xong rồi nên không cần đếm giờ nữa -> xoá key Redis
    await redis.del(shippingStartKey(maHoaDon));

    // Giảm số đơn đang giữ để shipper có thể nhận đơn mới
    await shipperStatusService.decrementOrderCount(maNhanVien);

    console.log(`[Delivery] Shipper ${maNhanVien} giao thành công đơn ${maHoaDon}`);

    return {
        ma_hoa_don: hoaDon.ma_hoa_don,
        trang_thai: 'DELIVERED',
        ngay_giao: ngayGiao,
    };
}

// Đồng hồ đếm ngược trên đơn đang giao: còn bao lâu nữa thì bị coi là trễ (3h)
async function getTimeoutRemaining(maNhanVien, maHoaDon) {
    await timDonHoacNemLoi(maNhanVien, maHoaDon); // chỉ để kiểm tra quyền sở hữu

    const batDau = await redis.get(shippingStartKey(maHoaDon));

    // Chưa có mốc = đơn chưa được nhận (hoặc key đã hết hạn)
    if (!batDau) {
        return {
            co_moc: false,
            elapsed: 0,
            remaining: THOI_GIAN_TOI_DA_GIAY,
            is_timeout: false,
        };
    }

    // Số giây đã trôi qua từ lúc bắt đầu giao
    const elapsed = Math.floor((Date.now() - new Date(batDau).getTime()) / 1000);

    return {
        co_moc: true,
        elapsed,
        remaining: Math.max(0, THOI_GIAN_TOI_DA_GIAY - elapsed),
        is_timeout: elapsed >= THOI_GIAN_TOI_DA_GIAY,
    };
}

module.exports = {
    confirmPickup,
    markDelivered,
    getTimeoutRemaining,
};
