// src/models/HoaDon.js

const pool = require('../config/database');
const KhachHang = require('./KhachHang');
const NhanVien = require('./NhanVien');

class HoaDon {

    constructor(row = {}) {
        this.ma_hoa_don = row.ma_hoa_don;
        this.ma_khach_hang = row.ma_khach_hang;
        this.ma_nhan_vien_giao = row.ma_nhan_vien_giao;
        this.ngay_dat = row.ngay_dat;
        this.ngay_giao = row.ngay_giao;
        this.tong_tien = row.tong_tien;
        this.trang_thai = row.trang_thai;
        this.trang_thai_thanh_toan = row.trang_thai_thanh_toan;
        this.phuong_thuc_thanh_toan = row.phuong_thuc_thanh_toan;
        this.dia_chi_giao = row.dia_chi_giao;
        this.so_dien_thoai = row.so_dien_thoai;

        // Các thuộc tính "ảo" khi JOIN
        if (row.ten_khach_hang) {
            this.khachHang = new KhachHang({
                ma_khach_hang: row.ma_khach_hang,
                ten_khach_hang: row.ten_khach_hang,
                so_dien_thoai: row.sdt_khach,
            });
        }
    }

    // Tìm theo mã
    static async findById(ma_hoa_don) {
        const [rows] = await pool.query(
            'SELECT * FROM hoa_don WHERE ma_hoa_don = ?',
            [ma_hoa_don]
        );
        if (rows.length === 0) return null;
        return new HoaDon(rows[0]);
    }

    // Tìm theo mã + check thuộc shipper (dùng check quyền)
    static async findByIdAndShipper(ma_hoa_don, ma_nhan_vien_giao) {
        const [rows] = await pool.query(
            `SELECT * FROM hoa_don
       WHERE ma_hoa_don = ? AND ma_nhan_vien_giao = ?`,
            [ma_hoa_don, ma_nhan_vien_giao]
        );
        if (rows.length === 0) return null;
        return new HoaDon(rows[0]);
    }

    // Danh sách đơn cần giao của shipper
    static async findDonCanGiao(ma_nhan_vien_giao) {
        const [rows] = await pool.query(
            `SELECT hd.*,
              kh.ten_khach_hang,
              kh.so_dien_thoai AS sdt_khach
       FROM hoa_don hd
       LEFT JOIN khach_hang kh ON kh.ma_khach_hang = hd.ma_khach_hang
       WHERE hd.ma_nhan_vien_giao = ?
         AND hd.trang_thai IN ('CONFIRMED', 'SHIPPING')
       ORDER BY hd.ngay_dat DESC`,
            [ma_nhan_vien_giao]
        );
        return rows.map(row => new HoaDon(row));
    }

    // Lịch sử giao hàng (phân trang)
    static async findLichSuGiao(ma_nhan_vien_giao, limit = 10, offset = 0) {
        const [rows] = await pool.query(
            `SELECT hd.*,
              kh.ten_khach_hang
       FROM hoa_don hd
       LEFT JOIN khach_hang kh ON kh.ma_khach_hang = hd.ma_khach_hang
       WHERE hd.ma_nhan_vien_giao = ?
         AND hd.trang_thai = 'DELIVERED'
       ORDER BY hd.ngay_giao DESC
       LIMIT ? OFFSET ?`,
            [ma_nhan_vien_giao, Number(limit), Number(offset)]
        );
        return rows.map(row => new HoaDon(row));
    }

    // Đếm tổng đơn đã giao (để tính số trang)
    static async countLichSuGiao(ma_nhan_vien_giao) {
        const [rows] = await pool.query(
            `SELECT COUNT(*) AS total
       FROM hoa_don
       WHERE ma_nhan_vien_giao = ? AND trang_thai = 'DELIVERED'`,
            [ma_nhan_vien_giao]
        );
        return Number(rows[0].total) || 0;
    }

    // Tính tiền COD cần nộp
    static async tinhTienCanNop(ma_nhan_vien_giao) {
        const [rows] = await pool.query(
            `SELECT
         COALESCE(SUM(tong_tien), 0) AS tong_tien,
         COUNT(*)                    AS so_don
       FROM hoa_don
       WHERE ma_nhan_vien_giao = ?
         AND trang_thai = 'DELIVERED'
         AND trang_thai_thanh_toan = 'DA_THANH_TOAN'
         AND phuong_thuc_thanh_toan = 'COD'`,
            [ma_nhan_vien_giao]
        );
        return {
            tongTien: Number(rows[0].tong_tien) || 0,
            soDon: Number(rows[0].so_don) || 0,
        };
    }

    // Tổng doanh thu
    static async tinhTongDoanhThu(ma_nhan_vien_giao) {
        const [rows] = await pool.query(
            `SELECT COALESCE(SUM(tong_tien), 0) AS tong
       FROM hoa_don
       WHERE ma_nhan_vien_giao = ? AND trang_thai = 'DELIVERED'`,
            [ma_nhan_vien_giao]
        );
        return Number(rows[0].tong) || 0;
    }

    // Cập nhật trạng thái (cách viết OOP hơn)
    async updateTrangThai(trang_thai, extraData = {}) {
        const fields = ['trang_thai = ?'];
        const values = [trang_thai];

        if (extraData.ngay_giao) {
            fields.push('ngay_giao = ?');
            values.push(extraData.ngay_giao);
        }
        if (extraData.trang_thai_thanh_toan) {
            fields.push('trang_thai_thanh_toan = ?');
            values.push(extraData.trang_thai_thanh_toan);
        }

        values.push(this.ma_hoa_don);

        const [result] = await pool.query(
            `UPDATE hoa_don SET ${fields.join(', ')} WHERE ma_hoa_don = ?`,
            values
        );

        if (result.affectedRows > 0) {
            // Cập nhật lại object hiện tại
            this.trang_thai = trang_thai;
            if (extraData.ngay_giao) this.ngay_giao = extraData.ngay_giao;
            if (extraData.trang_thai_thanh_toan) this.trang_thai_thanh_toan = extraData.trang_thai_thanh_toan;
        }
        return result.affectedRows > 0;
    }

    // Getter tiện ích
    get maHienThi() {
        return `#HD-${String(this.ma_hoa_don).padStart(4, '0')}`;
    }

    get canThuTienMat() {
        return this.trang_thai_thanh_toan === 'CHUA_THANH_TOAN'
            && this.phuong_thuc_thanh_toan === 'COD';
    }

    get laHoaTuoi() {
        // Sẽ chính xác nếu caller nạp chiTietHoaDon vào
        return (this._chiTiet || []).some(ct => ct.sanPham?.laHoaTuoi);
    }

}

module.exports = HoaDon;