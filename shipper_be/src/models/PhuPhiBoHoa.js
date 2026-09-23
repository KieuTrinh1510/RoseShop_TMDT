// src/models/PhuPhiBoHoa.js
// Phụ phí khi tạo bó hoa (ví dụ: phí công cắm hoa, phí gói quà, phí thiệp)
// Bảng này thường chỉ có vài dòng cấu hình sẵn.

const pool = require('../config/database');

class PhuPhiBoHoa {

    constructor(row = {}) {
        this.ma_phu_phi = row.ma_phu_phi;
        this.loai = row.loai;
        this.gia_tri = row.gia_tri;
        this.phu_phi = row.phu_phi;
    }

    // Lấy tất cả phụ phí
    static async findAll() {
        const [rows] = await pool.query(
            'SELECT * FROM phu_phi_bo_hoa ORDER BY ma_phu_phi ASC'
        );
        return rows.map(row => new PhuPhiBoHoa(row));
    }

    // Tìm theo mã
    static async findById(ma_phu_phi) {
        const [rows] = await pool.query(
            'SELECT * FROM phu_phi_bo_hoa WHERE ma_phu_phi = ?',
            [ma_phu_phi]
        );
        if (rows.length === 0) return null;
        return new PhuPhiBoHoa(rows[0]);
    }

    // Tìm theo loại (ví dụ: 'THIEP', 'CONG_CAM_HOA')
    static async findByLoai(loai) {
        const [rows] = await pool.query(
            'SELECT * FROM phu_phi_bo_hoa WHERE loai = ?',
            [loai]
        );
        return rows.map(row => new PhuPhiBoHoa(row));
    }

    // Lấy 1 phụ phí theo loại (thường chỉ có 1)
    static async findMotTheoLoai(loai) {
        const [rows] = await pool.query(
            'SELECT * FROM phu_phi_bo_hoa WHERE loai = ? LIMIT 1',
            [loai]
        );
        if (rows.length === 0) return null;
        return new PhuPhiBoHoa(rows[0]);
    }

    // Tạo phụ phí mới
    static async create({ loai, gia_tri, phu_phi }) {
        const [result] = await pool.query(
            `INSERT INTO phu_phi_bo_hoa (loai, gia_tri, phu_phi)
       VALUES (?, ?, ?)`,
            [loai, gia_tri ?? null, phu_phi ?? 0]
        );
        return PhuPhiBoHoa.findById(result.insertId);
    }

    // Cập nhật phụ phí (instance)
    async update({ loai, gia_tri, phu_phi }) {
        const fields = [];
        const values = [];

        if (loai !== undefined) { fields.push('loai = ?'); values.push(loai); this.loai = loai; }
        if (gia_tri !== undefined) { fields.push('gia_tri = ?'); values.push(gia_tri); this.gia_tri = gia_tri; }
        if (phu_phi !== undefined) { fields.push('phu_phi = ?'); values.push(phu_phi); this.phu_phi = phu_phi; }

        if (fields.length === 0) return false;

        values.push(this.ma_phu_phi);
        const [result] = await pool.query(
            `UPDATE phu_phi_bo_hoa SET ${fields.join(', ')} WHERE ma_phu_phi = ?`,
            values
        );
        return result.affectedRows > 0;
    }

    // Getter tiện ích: trả về số tiền dạng number
    get soTien() {
        return Number(this.phu_phi) || 0;
    }

}

module.exports = PhuPhiBoHoa;