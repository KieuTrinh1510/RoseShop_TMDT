const pool = require('../config/database');
const SanPham = require('./SanPham');

class ChiTietHoaDon {
    constructor(row = {}) {
        this.ma_hoa_don = row.ma_hoa_don;
        this.ma_chi_tiet = row.ma_chi_tiet;
        this.ma_san_pham = row.ma_san_pham;
        this.ma_chi_tiet_nhap = row.ma_chi_tiet_nhap;
        this.so_luong = row.so_luong;
        this.gia_nhap_snapshot = row.gia_nhap_snapshot;
        this.gia_ban_snapshot = row.gia_ban_snapshot;

        // Thuộc tính "ảo" — gán khi JOIN với san_pham
        this.sanPham = row.ten_san_pham ? new SanPham({
            ma_san_pham: row.ma_san_pham,
            ten_san_pham: row.ten_san_pham,
            loai_san_pham: row.loai_san_pham,
            hinh_anh: row.hinh_anh,
        }) : null;

    }
    // Lấy tất cả chi tiết của 1 hóa đơn
    static async findByHoaDon(ma_hoa_don) {
        const [rows] = await pool.query(
            'SELECT * FROM chi_tiet_hoa_don WHERE ma_hoa_don = ?',
            [ma_hoa_don]
        );
        return rows.map(row => new ChiTietHoaDon(row));
    }
    // Lấy chi tiết + JOIN sản phẩm
    static async findByHoaDonWithSanPham(ma_hoa_don) {
        const [rows] = await pool.query(
            `SELECT ct.*,
              sp.ten_san_pham,
              sp.loai_san_pham,
              sp.hinh_anh
       FROM chi_tiet_hoa_don ct
       LEFT JOIN san_pham sp ON sp.ma_san_pham = ct.ma_san_pham
       WHERE ct.ma_hoa_don = ?`,
            [ma_hoa_don]
        );
        return rows.map(row => new ChiTietHoaDon(row));
    }

    // Tính thành tiền (số lượng × giá bán)
    get thanhTien() {
        return this.so_luong * Number(this.gia_ban_snapshot || 0);
    }

    // Tên sản phẩm hiển thị (fallback nếu thiếu)
    get tenHienThi() {
        return this.sanPham?.ten_san_pham || `Sản phẩm #${this.ma_san_pham}`;
    }

}

module.exports = ChiTietHoaDon;


