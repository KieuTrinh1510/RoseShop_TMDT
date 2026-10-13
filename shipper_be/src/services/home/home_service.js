// src/services/home/home_service.js
// Orchestrator cho tab Trang chủ. Gọi song song 4 sub-service rồi ghép response.

const infoService = require('../profile/shipper_profile/info/shipper_info_services');
const orderCountService = require('./order_count/shipper_order_count_services');
const codService = require('./COD_money/shipper_COD_money_services');
const notifService = require('./notification/notification_services');

async function getHome(maNhanVien) {
    // 4 truy vấn độc lập -> chạy song song
    const [shipper, orderCount, cod, soThongBao] = await Promise.all([
        infoService.getThongTin(maNhanVien),
        orderCountService.buildOrderCount(maNhanVien),
        codService.getCodInfo(maNhanVien),
        notifService.demChuaDoc(maNhanVien),
    ]);

    const isOnline = shipper.trang_thai_hoat_dong === 'ONLINE';

    return {
        shipper: {
            ma_nhan_vien: shipper.ma_nhan_vien,
            ten_nhan_vien: shipper.ten_nhan_vien,
            chu_cai_dau: shipper.chuCaiDau,
        },
        trang_thai: {
            trang_thai_hoat_dong: shipper.trang_thai_hoat_dong,
            is_online: isOnline,
        },
        don_hang: {
            so_don_hien_tai: orderCount.so_don_hien_tai,
            gioi_han_don: orderCount.gioi_han_don,
            hien_thi: orderCount.hien_thi,
            is_full: orderCount.is_full,
            co_the_nhan_don: isOnline && !orderCount.is_full,
        },
        cod: {
            tien_can_nop: cod.tien_can_nop,
            tien_cod: cod.tien_cod,
            tien_phat: cod.tien_phat,
            so_don: cod.so_don,
        },
        thong_bao: {
            chua_doc: soThongBao,
        },
    };
}

module.exports = { getHome };