const { includes } = require('zod');
const pool = require('../config/database');


class SanPham {

    constructor(row = {}) {
        this.ma_san_pham = row.ma_san_pham;
        this.ten_san_pham = row.ten_san_pham;
        this.loai_san_pham = row.loai_san_pham;
        this.don_vi_tinh = row.don_vi_tinh;
        this.gia_ban_hien_tai = row.gia_ban_hien_tai;
        this.trang_thai = row.trang_thai;
        this.hinh_anh = row.hinh_anh;
        this.mo_ta = row.mo_ta;
    }
    static async findById(ma_san_pham) {
        const [rows] = await pool.query(
            'SELECT *FROM san_pham WHERE ma_san_pham=?',
            [ma_san_pham]
        );
        if (rows.length === 0) return null;
        return new SanPham(row[0]);
    }
    // Lấy nhiều sản phẩm cùng lúc theo mảng mã
    static async findByIds(dsMaSanPham) {
        if (!dsMaSanPham || dsMaSanPham.length === 0) return [];
        const placeholders = dsMaSanPham.map(() => ' ?').join(',');
        const [rows] = await pool.query(
            ` SELECT *FROM san_pham WHERE ma_san_pham IN (${placeholders})`,
            dsMaSanPham
        );
        return rows.map(row => new SanPham(row));
    }
    // Tiện ích: kiểm tra sản phẩm có phải hoa tươi không
    get laHoaTuoi() {
        const ten = (this.ten_san_pham || '').toLowerCase();
        return ten.includes('hoa') || ten.includes('flower') || ten.includes('bó');
    }

}
module.exports = SanPham;