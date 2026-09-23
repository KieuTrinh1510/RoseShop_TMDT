// src/models/BoHoa.js
// Bảng bó hoa: lưu bó hoa do khách tạo (Custom Mix) hoặc bó hoa mẫu hệ thống

const pool = require('../config/database');
const KhachHang = require('./KhachHang');
const MauBoHoa = require('./MauBoHoa');
const HoaDon = require('./HoaDon');

class BoHoa {

    // Hằng số cho loai_bo_hoa và trang_thai
    static LOAI_HE_THONG = 'HE_THONG';
    static LOAI_TUY_CHON = 'TUY_CHON';

    static TRANG_THAI_DRAFT = 'DRAFT';
    static TRANG_THAI_ACTIVE = 'ACTIVE';

    constructor(row = {}) {
        this.ma_bo_hoa = row.ma_bo_hoa;
        this.ma_khach_hang = row.ma_khach_hang;
        this.ma_mau_bo_hoa = row.ma_mau_bo_hoa;
        this.ma_hoa_don = row.ma_hoa_don;
        this.ten_bo_hoa = row.ten_bo_hoa;
        this.so_luong = row.so_luong;
        this.gia_ban = row.gia_ban;
        this.loai_bo_hoa = row.loai_bo_hoa;
        this.kieu_bo_hoa = row.kieu_bo_hoa;
        this.size = row.size;
        this.concept = row.concept;
        this.loi_nhan = row.loi_nhan;
        this.hinh_anh = row.hinh_anh;
        this.trang_thai = row.trang_thai;

        // Thuộc tính "ảo" khi JOIN
        if (row.ten_khach_hang) {
            this.khachHang = new KhachHang({
                ma_khach_hang: row.ma_khach_hang,
                ten_khach_hang: row.ten_khach_hang,
                so_dien_thoai: row.sdt_khach,
            });
        }
        if (row.ten_mau) {
            this.mauBoHoa = new MauBoHoa({
                ma_mau_bo_hoa: row.ma_mau_bo_hoa,
                ten_mau: row.ten_mau,
                kieu_bo_hoa: row.kieu_mau,
                size: row.size_mau,
                concept: row.concept_mau,
            });
        }
    }

    // Tìm theo mã
    static async findById(ma_bo_hoa) {
        const [rows] = await pool.query(
            'SELECT * FROM bo_hoa WHERE ma_bo_hoa = ?',
            [ma_bo_hoa]
        );
        if (rows.length === 0) return null;
        return new BoHoa(rows[0]);
    }

    // Lấy tất cả bó hoa của 1 hóa đơn
    // (dùng khi shipper xem đơn có Custom Mix)
    static async findByHoaDon(ma_hoa_don) {
        const [rows] = await pool.query(
            'SELECT * FROM bo_hoa WHERE ma_hoa_don = ?',
            [ma_hoa_don]
        );
        return rows.map(row => new BoHoa(row));
    }

    // Lấy bó hoa của 1 khách (5 cái gần nhất)
    static async findGanNhatCuaKhach(ma_khach_hang, limit = 5) {
        const [rows] = await pool.query(
            `SELECT * FROM bo_hoa
       WHERE ma_khach_hang = ?
       ORDER BY ma_bo_hoa DESC
       LIMIT ?`,
            [ma_khach_hang, Number(limit)]
        );
        return rows.map(row => new BoHoa(row));
    }

    // Lấy kèm thông tin khách + mẫu bó hoa (JOIN)
    static async findByIdWithJoins(ma_bo_hoa) {
        const [rows] = await pool.query(
            `SELECT bh.*,
              kh.ten_khach_hang,
              kh.so_dien_thoai AS sdt_khach,
              mbh.ten_mau,
              mbh.kieu_bo_hoa  AS kieu_mau,
              mbh.size         AS size_mau,
              mbh.concept      AS concept_mau
       FROM bo_hoa bh
       LEFT JOIN khach_hang kh  ON kh.ma_khach_hang  = bh.ma_khach_hang
       LEFT JOIN mau_bo_hoa mbh ON mbh.ma_mau_bo_hoa = bh.ma_mau_bo_hoa
       WHERE bh.ma_bo_hoa = ?`,
            [ma_bo_hoa]
        );
        if (rows.length === 0) return null;
        return new BoHoa(rows[0]);
    }


    // Tạo bó hoa mới
    static async create(data) {
        const [result] = await pool.query(
            `INSERT INTO bo_hoa
        (ma_khach_hang, ma_mau_bo_hoa, ma_hoa_don, ten_bo_hoa, so_luong,
         gia_ban, loai_bo_hoa, kieu_bo_hoa, size, concept, loi_nhan,
         hinh_anh, trang_thai)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                data.ma_khach_hang ?? null,
                data.ma_mau_bo_hoa ?? null,
                data.ma_hoa_don ?? null,
                data.ten_bo_hoa ?? null,
                data.so_luong ?? 1,
                data.gia_ban ?? 0,
                data.loai_bo_hoa ?? BoHoa.LOAI_TUY_CHON,
                data.kieu_bo_hoa ?? null,
                data.size ?? null,
                data.concept ?? null,
                data.loi_nhan ?? null,
                data.hinh_anh ?? null,
                data.trang_thai ?? BoHoa.TRANG_THAI_DRAFT,
            ]
        );
        return BoHoa.findById(result.insertId);
    }

    // Cập nhật bó hoa (instance method)
    async update(data) {
        const fields = [];
        const values = [];

        const choPhep = [
            'ten_bo_hoa', 'so_luong', 'gia_ban', 'loai_bo_hoa',
            'kieu_bo_hoa', 'size', 'concept', 'loi_nhan',
            'hinh_anh', 'trang_thai', 'ma_hoa_don',
        ];

        for (const key of choPhep) {
            if (data[key] !== undefined) {
                fields.push(`${key} = ?`);
                values.push(data[key]);
                this[key] = data[key];
            }
        }

        if (fields.length === 0) return false;

        values.push(this.ma_bo_hoa);
        const [result] = await pool.query(
            `UPDATE bo_hoa SET ${fields.join(', ')} WHERE ma_bo_hoa = ?`,
            values
        );
        return result.affectedRows > 0;
    }

    // Getter tiện ích
    get laHeThong() { return this.loai_bo_hoa === BoHoa.LOAI_HE_THONG; }
    get laTuyChon() { return this.loai_bo_hoa === BoHoa.LOAI_TUY_CHON; }
    get daActive() { return this.trang_thai === BoHoa.TRANG_THAI_ACTIVE; }
    get laDraft() { return this.trang_thai === BoHoa.TRANG_THAI_DRAFT; }

}

module.exports = BoHoa;