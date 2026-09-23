// src/models/MauBoHoa.js
// Mẫu bó hoa do hệ thống/cửa hàng định nghĩa sẵn (template)

const pool = require('../config/database');

class MauBoHoa {

    // Trạng thái mẫu
    static TRANG_THAI_DRAFT = 'DRAFT';
    static TRANG_THAI_ACTIVE = 'ACTIVE';
    static TRANG_THAI_INACTIVE = 'INACTIVE';

    constructor(row = {}) {
        this.ma_mau_bo_hoa = row.ma_mau_bo_hoa;
        this.ten_mau = row.ten_mau;
        this.mo_ta = row.mo_ta;
        this.kieu_bo_hoa = row.kieu_bo_hoa;
        this.size = row.size;
        this.concept = row.concept;
        this.trang_thai = row.trang_thai;
    }

    // Tìm theo mã
    static async findById(ma_mau_bo_hoa) {
        const [rows] = await pool.query(
            'SELECT * FROM mau_bo_hoa WHERE ma_mau_bo_hoa = ?',
            [ma_mau_bo_hoa]
        );
        if (rows.length === 0) return null;
        return new MauBoHoa(rows[0]);
    }

    // Lấy tất cả mẫu đang ACTIVE
    static async findActive() {
        const [rows] = await pool.query(
            "SELECT * FROM mau_bo_hoa WHERE trang_thai = 'ACTIVE' ORDER BY ma_mau_bo_hoa DESC"
        );
        return rows.map(row => new MauBoHoa(row));
    }

    // Lấy tất cả mẫu theo trạng thái
    static async findByTrangThai(trang_thai) {
        const [rows] = await pool.query(
            'SELECT * FROM mau_bo_hoa WHERE trang_thai = ?',
            [trang_thai]
        );
        return rows.map(row => new MauBoHoa(row));
    }

    // Lấy mẫu kèm chi tiết thành phần (JOIN)
    static async findByIdWithChiTiet(ma_mau_bo_hoa) {
        // Bước 1: lấy mẫu
        const mau = await MauBoHoa.findById(ma_mau_bo_hoa);
        if (!mau) return null;

        // Bước 2: lấy chi tiết
        const [chiTietRows] = await pool.query(
            `SELECT ct.*,
              sp.ten_san_pham,
              sp.loai_san_pham,
              sp.hinh_anh
       FROM chi_tiet_mau_bo_hoa ct
       LEFT JOIN san_pham sp ON sp.ma_san_pham = ct.ma_san_pham
       WHERE ct.ma_mau_bo_hoa = ?`,
            [ma_mau_bo_hoa]
        );

        mau.chiTiet = chiTietRows;
        return mau;
    }

    // Tạo mẫu mới
    static async create(data) {
        const [result] = await pool.query(
            `INSERT INTO mau_bo_hoa
        (ten_mau, mo_ta, kieu_bo_hoa, size, concept, trang_thai)
       VALUES (?, ?, ?, ?, ?, ?)`,
            [
                data.ten_mau,
                data.mo_ta ?? null,
                data.kieu_bo_hoa ?? null,
                data.size ?? null,
                data.concept ?? null,
                data.trang_thai ?? MauBoHoa.TRANG_THAI_DRAFT,
            ]
        );
        return MauBoHoa.findById(result.insertId);
    }

    // Cập nhật mẫu (instance)
    async update(data) {
        const fields = [];
        const values = [];

        const choPhep = ['ten_mau', 'mo_ta', 'kieu_bo_hoa', 'size', 'concept', 'trang_thai'];

        for (const key of choPhep) {
            if (data[key] !== undefined) {
                fields.push(`${key} = ?`);
                values.push(data[key]);
                this[key] = data[key];
            }
        }

        if (fields.length === 0) return false;

        values.push(this.ma_mau_bo_hoa);
        const [result] = await pool.query(
            `UPDATE mau_bo_hoa SET ${fields.join(', ')} WHERE ma_mau_bo_hoa = ?`,
            values
        );
        return result.affectedRows > 0;
    }

    // Đổi trạng thái
    async doiTrangThai(trang_thai) {
        return this.update({ trang_thai });
    }

    // Getter
    get dangActive() { return this.trang_thai === MauBoHoa.TRANG_THAI_ACTIVE; }
    get laDraft() { return this.trang_thai === MauBoHoa.TRANG_THAI_DRAFT; }

}

module.exports = MauBoHoa;