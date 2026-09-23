const pool = require('../config/database');

class NguoiDung {

    // Hàm khởi tạo: nhận 1 dòng từ DB, gán vào this

    constructor(row = {}) {
        this.ma_nguoi_dung = row.ma_nguoi_dung;
        this.ten_dang_nhap = row.ten_dang_nhap;
        this.mat_khau = row.mat_khau;
        this.vai_tro = row.vai_tro;
    }

    // Tìm theo mã, trả về instance NguoiDung hoặc null
    static async findById(ma_nguoi_dung) {
        const [rows] = await pool.query(
            'SELECT *FROM nguoi_dung WHERE ma_nguoi_dung=?',
            [ma_nguoi_dung]
        );
        if (rows.length === 0) return null
        return new NguoiDung(row[0]);
    }

    // Tìm theo tên đăng nhập (dùng khi login)
    static async findByUsername(ten_dang_nhap) {
        const [rows] = await pool.query(
            'SELECT * FROM nguoi_dung WHERE ten_dang_nhap=?',
            [ten_dang_nhap]
        );
        if (rows.length === 0) return null;
        return new NguoiDung(rows[0]);

    }
    //Static = hỏi Class "hãy tìm/tạo cho tôi một object". dùng khi CHƯA có object cụ thể
    //Không static = hỏi Object "hãy làm gì đó với chính mày" dùng khi ĐÃ có object cụ thể
    // Tạo tài khoản mới, trả về instance vừa tạo
    static async create({ ten_dang_nhap, mat_khau, vai_tro }) {
        const [result] = await pool.query(
            `INSERT INTO nguoi_dung(ten_dang_nhap,mat_khau,vai_tro
            VALUES(?,?,?)
            `,
            [ten_dang_nhap, mat_khau, vai_tro]
        );
        return NguoiDung.findById(result.insertId);
    }
    // Cập nhật mật khẩu
    async updatePassword(mat_khauMoi) {
        const [result] = await pool.query(
            'UPDATE nguoi_dung SET mat_khau=? WHERE ma_nguoi_dung =?',
            [mat_khauMoi, this.ma_nguoi_dung]
        );
        if (result.affectedRows > 0) {
            this.mat_khau = mat_khauMoi; // cap nhat trong object o tren
        }
        return result.affectedRows > 0;
    }
    // Kiểm tra vai trò (tiện dùng ở middleware)
    isShipper() {
        return this.vai_tro === 'SHIPPER';

    }
    isAdmin() {
        return this.vai_tro === 'ADMIN';
    }
    isNhanVien() {
        return this.vai_tro === 'NHAN_VIEN';
    }
    isKhachHang() {
        return this.vai_tro === 'KHACH_HANG';
    }
}
module.exports = NguoiDung;