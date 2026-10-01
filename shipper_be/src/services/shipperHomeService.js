// src/services/shipperHomeService.js
// Logic màn hình HOME của shipper:
//   - trạng thái ONLINE/OFFLINE
//   - số đơn đang giữ -/10 (đủ 10 thì không nhận thêm)
//   - tiền COD cần nộp
//   - số thông báo chưa đọc (tạm = 0, sẽ nối với thongBaoService sau)

const NhanVien = require('../models/NhanVien');
const HoaDon = require('../models/HoaDon');
const { GIOI_HAN_DON } = require('./shipperStatusService');

function taoLoi(status, code, message) {
    const err = new Error(message);
    err.status = status;
    err.code = code;
    return err;
}

// TODO: khi làm thongBaoService thì thay bằng
// require('./thongBaoService').demChuaDoc(maNhanVien)
async function demThongBaoChuaDoc(maNhanVien) {
    return 0;
}

async function getHome(maNhanVien) {
    const shipper = await NhanVien.findById(maNhanVien);
    if (!shipper) {
        throw taoLoi(404, 'SHIPPER_NOT_FOUND', 'Không tìm thấy shipper.');
    }

    // 2 truy vấn độc lập -> chạy song song
    const [cod, soThongBao] = await Promise.all([
        HoaDon.tinhTienCanNop(maNhanVien),
        demThongBaoChuaDoc(maNhanVien),
    ]);

    const soDon = shipper.so_don_hien_tai || 0;
    const isFull = soDon >= GIOI_HAN_DON;
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
            so_don_hien_tai: soDon,
            gioi_han_don: GIOI_HAN_DON,
            hien_thi: `${soDon}/${GIOI_HAN_DON}`,
            is_full: isFull,
            co_the_nhan_don: isOnline && !isFull,
        },
        cod: {
            tien_can_nop: cod.tongTien,
            so_don: cod.soDon,
        },
        thong_bao: {
            chua_doc: soThongBao,
        },
    };
}

module.exports = { getHome };