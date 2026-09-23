const pool = require('../config/database');

class KhachHang {
    constructor(row = {}) {
        this.ma_khach_hang = row.ma_khach_hang;
        this.ten_khach_hang = row.ten_khach_hang;
        this.so_dien_thoai = row.so_dien_thoai;
        this.email = row.email;
        this.dia_chi = row.dia_chi;
        this.diem_tich_luy = row.diem_tich_luy;
    }

    static async findById(ma_khach_hang) {
        const [rows] = await pool.query(
            ' SELECT * FROM khach_hang WHERE ma_khach_hang=?',
            [ma_khach_hang]
        );
        if (rows.length === 0) return null;
        return new KhachHang(row[0]);

    }
    // Cộng điểm (shipper giao trễ → hệ thống cộng điểm xin lỗi)
    async congDiem(soDiem) {
        const [result] = await pool.query(
            ' UPDATE khach_hang SET diem_tich_luy =diem_tich_luy + ? WHERE ma_khach_hang =?',
            [soDiem, this.ma_khach_hang]
        );
        if (result.affectedRows > 0) {
            this.diem_tich_luy = (this.diem_tich_luy || 0) + soDiem;
        }
        return result.affectedRows > 0;
    }
}
module.exports = KhachHang;
