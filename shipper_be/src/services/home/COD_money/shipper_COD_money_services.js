// src/services/home/COD_money/shipper_COD_money_services.js
// Khoản tiền shipper cần nộp = tiền COD đã thu + tiền phạt tích lũy.
// Đọc thẳng cột nhan_vien.tien_phat — không phụ thuộc chéo vào delivery_penalty.
// DB lo tính atomic cho cột số nguyên.

const HoaDon = require('../../../models/HoaDon');
const NhanVien = require('../../../models/NhanVien');

async function getCodInfo(maNhanVien) {
    // 2 truy vấn độc lập -> chạy song song
    const [cod, shipper] = await Promise.all([
        HoaDon.tinhTienCanNop(maNhanVien),
        NhanVien.findById(maNhanVien),
    ]);

    const tienCod = Number(cod?.tongTien) || 0;
    const tienPhat = Number(shipper?.tien_phat) || 0;

    return {
        tien_cod: tienCod,
        tien_phat: tienPhat,
        tien_can_nop: tienCod + tienPhat,
        so_don: Number(cod?.soDon) || 0,
    };
}

module.exports = { getCodInfo };