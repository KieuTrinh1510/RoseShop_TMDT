// src/models/NhanVien.js
const pool = require('../config/database');  // ← bỏ require('express') thừa

class NhanVien {

    constructor(row = {}) {
        this.ma_nhan_vien = row.ma_nhan_vien;
        this.ten_nhan_vien = row.ten_nhan_vien;
        this.email = row.email;
        this.so_dien_thoai = row.so_dien_thoai;
        this.chuc_vu = row.chuc_vu;
        this.cong_viec = row.cong_viec;
        this.luong = row.luong;
    }

    // ─── Sửa lỗi 3.1 + 3.2 ───
    static async findById(ma_nhan_vien) {
        const [rows] = await pool.query(       // ← const [rows] (số nhiều)
            'SELECT * FROM nhan_vien WHERE ma_nhan_vien = ?',  // ← thêm dấu phẩy
            [ma_nhan_vien]
        );
        if (rows.length === 0) return null;
        return new NhanVien(rows[0]);          // ← rows[0]
    }

    // ─── Sửa lỗi 3.3: thêm tham số ma_nhan_vien ───
    static async findWithNguoiDung(ma_nhan_vien) {
        const [rows] = await pool.query(       // ← const [rows]
            `SELECT nv.*, nd.ten_dang_nhap, nd.vai_tro
             FROM nhan_vien nv
             JOIN nguoi_dung nd ON nd.ma_nguoi_dung = nv.ma_nhan_vien
             WHERE nv.ma_nhan_vien = ?`,
            [ma_nhan_vien]
        );
        if (rows.length === 0) return null;

        const nv = new NhanVien(rows[0]);
        nv.ten_dang_nhap = rows[0].ten_dang_nhap;
        nv.vai_tro = rows[0].vai_tro;
        return nv;
    }

    // ─── Sửa lỗi 3.4 + 3.5 ───
    async updateProfile({ ten_nhan_vien, so_dien_thoai, email }) {
        const [result] = await pool.query(
            `UPDATE nhan_vien
             SET ten_nhan_vien = ?, so_dien_thoai = ?, email = ?
             WHERE ma_nhan_vien = ?`,
            [ten_nhan_vien, so_dien_thoai, email, this.ma_nhan_vien]  // ← thêm this.ma_nhan_vien
        );
        if (result.affectedRows > 0) {   // ← affectedRows (số nhiều)
            this.ten_nhan_vien = ten_nhan_vien;
            this.so_dien_thoai = so_dien_thoai;
            this.email = email;
        }
        return result.affectedRows > 0;
    }

    get chuCaiDau() {
        return (this.ten_nhan_vien || '?').charAt(0).toUpperCase();
    }
}

module.exports = NhanVien;