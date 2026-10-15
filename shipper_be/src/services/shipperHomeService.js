// src/services/shipperHomeService.js
// Logic màn hình HOME của shipper.
// Số đơn đếm LIVE từ hoa_don — không đọc cột nhan_vien.so_don_hien_tai.

const NhanVien = require('../models/NhanVien');
const HoaDon = require('../models/HoaDon');
const { GIOI_HAN_DON, demDonDangGiu } = require('./shipperStatusService');
const thongBaoService = require('./thongBaoService');
const { taoLoi } = require('../utils/loi');

async function getHome(maNhanVien) {
    const shipper = await NhanVien.findById(maNhanVien);
    if (!shipper) {
        throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    }

    // 3 truy vấn độc lập -> chạy song song
    const [soDon, cod, soThongBao] = await Promise.all([
        demDonDangGiu(maNhanVien),
        HoaDon.tinhTienCanNop(maNhanVien),
        thongBaoService.demChuaDoc(maNhanVien),
    ]);

    const isFull = soDon >= GIOI_HAN_DON;
    const isOnline = shipper.trang_thai_hoat_dong === 'ONLINE';

    const tienCod = cod.tongTien;
    const tienPhat = Number(shipper.tien_phat) || 0;

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
            so_don_hien_tai: soDon,
            gioi_han_don: GIOI_HAN_DON,
            hien_thi: `${soDon}/${GIOI_HAN_DON}`,
            is_full: isFull,
            co_the_nhan_don: isOnline && !isFull,
        },
        cod: {
            tien_can_nop: tienCod + tienPhat,
            tien_cod: tienCod,
            tien_phat: tienPhat,
            so_don: cod.soDon,
        },
        thong_bao: {
            chua_doc: soThongBao,
        },
    };
}

module.exports = { getHome };