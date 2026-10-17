// shipper_be/src/services/orders/shipper_failure/failure_state.js
//
// Hàm THUẦN (không DB, không require model) tính trạng thái báo giao thất bại.
// Dùng chung cho failure-check, order detail và service finalize.

'use strict';

const MS_PHUT = 60 * 1000; //10 * MS_PHUT nhớ sửa lại để đúng 10 phút ,đang dùng để test
const CHO_LAN_1_MS = 30 * 1000; // từ ngay_den_noi đến khi được báo lỗi khách
const CHO_LAN_2_MS = 30 * 1000; // từ lúc ghi nhận lỗi khách đến khi được finalize hủy

// Trạng thái giao_hang_that_bai (DANG_CHO_KHACH và KHACH_DA_NHAN cần migration thêm vào enum)
const TRANG_THAI_BAO_CAO = {
    DANG_CHO_KHACH: 'DANG_CHO_KHACH', // đã ghi nhận lỗi khách, đơn vẫn SHIPPING
    KHACH_DA_NHAN: 'KHACH_DA_NHAN',   // khách xuống nhận, đơn DELIVERED
    DA_BAO: 'DA_BAO',                 // đã hủy đơn (SHIPPER, hoặc CUSTOMER sau finalize)
};

function giayConLai(denMs, nowMs) {
    return Math.max(0, Math.ceil((denMs - nowMs) / 1000));
}

/**
 * @param {object} hoaDon       cần: trang_thai, ngay_den_noi, nguon_huy, ly_do_huy
 * @param {object|null} baoCao  bản ghi giao_hang_that_bai mới nhất của đơn (hoặc null)
 * @param {Date} now
 */
function tinhTrangThaiThatBai(hoaDon, baoCao, now = new Date()) {
    const nowMs = now.getTime();
    const trangThai = hoaDon.trang_thai;
    const dangGiao = trangThai === 'SHIPPING';

    const denNoiMs = hoaDon.ngay_den_noi ? new Date(hoaDon.ngay_den_noi).getTime() : null;
    const daArrived = denNoiMs !== null;

    const coBaoCao = Boolean(baoCao);
    const baoCaoKhach = baoCao && baoCao.loai_loi === 'CUSTOMER' ? baoCao : null;
    const dangChoKhach = Boolean(baoCaoKhach) &&
        baoCaoKhach.trang_thai === TRANG_THAI_BAO_CAO.DANG_CHO_KHACH;

    // ── Lần chờ thứ nhất: 10 phút từ ngay_den_noi ──
    const giayLan1 = daArrived ? giayConLai(denNoiMs + CHO_LAN_1_MS, nowMs) : CHO_LAN_1_MS / 1000;
    const duoi10Phut = !daArrived || giayLan1 > 0;
    const hetHanLan1Luc = daArrived ? new Date(denNoiMs + CHO_LAN_1_MS) : null;

    // ── Lần chờ thứ hai: ket_thuc_dem_10_phut của báo cáo CUSTOMER ──
    const ketThucLan2Ms = dangChoKhach && baoCaoKhach.ket_thuc_dem_10_phut
        ? new Date(baoCaoKhach.ket_thuc_dem_10_phut).getTime()
        : null;
    const giayLan2 = ketThucLan2Ms !== null ? giayConLai(ketThucLan2Ms, nowMs) : null;
    const hetHanLan2Luc = ketThucLan2Ms !== null ? new Date(ketThucLan2Ms) : null;

    const coTheGuiLoi = {
        SHIPPER: dangGiao && !coBaoCao,
        CUSTOMER: dangGiao && daArrived && !duoi10Phut && !coBaoCao,
    };
    // Giữ đúng quy tắc giao thành công hiện tại: SHIPPING + đã ARRIVED
    const coTheHoanThanh = dangGiao && daArrived;
    const coTheFinalize = dangGiao && dangChoKhach && giayLan2 === 0;

    let giaiDoan;
    if (trangThai === 'DELIVERED') giaiDoan = 'DA_GIAO';
    else if (trangThai === 'CANCELLED') giaiDoan = 'DA_HUY';
    else if (!dangGiao) giaiDoan = 'CHUA_GIAO';
    else if (!daArrived) giaiDoan = 'CHUA_DEN_NOI';
    else if (dangChoKhach) giaiDoan = coTheFinalize ? 'HET_HAN_CHO_KHACH' : 'CHO_KHACH_LAN_2';
    else if (coBaoCao) giaiDoan = 'DA_BAO_LOI';
    else if (duoi10Phut) giaiDoan = 'CHO_LAN_1';
    else giaiDoan = 'SAN_SANG_BAO_LOI_KHACH';

    return {
        trangThai,
        giaiDoan,
        daArrived,
        duoi10Phut,
        giayConLai: dangChoKhach ? giayLan2 : giayLan1, // đồng hồ đang chạy
        giayConLaiLan1: giayLan1,
        giayConLaiLan2: giayLan2,
        hetHanLan1Luc,
        hetHanLan2Luc,
        trangThaiBaoLoiKhach: baoCaoKhach ? baoCaoKhach.trang_thai : null,
        loaiLoiDaBao: baoCao ? baoCao.loai_loi : null,
        lyDoDaChon: baoCao ? baoCao.ly_do : null,
        coTheGuiLoi,
        coTheHoanThanh,
        coTheFinalize,
        nguonHuy: hoaDon.nguon_huy || null,
        lyDoHuy: hoaDon.ly_do_huy || null,
    };
}

module.exports = {
    CHO_LAN_1_MS,
    CHO_LAN_2_MS,
    TRANG_THAI_BAO_CAO,
    tinhTrangThaiThatBai,
};