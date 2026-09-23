// src/models/ChiTietBoHoa.js
// Chi tiết thành phần của 1 bó hoa (hoa chính, giấy gói, ruy băng, ...)

const pool = require('../config/database');
const SanPham = require('./SanPham');

class ChiTietBoHoa {

    // Hằng số vai trò thành phần trong bó hoa
    static VAI_TRO_HOA_CHINH = 'HOA_CHINH';
    static VAI_TRO_HOA_TRANG_TRI = 'HOA_TRANG_TRI';
    static VAI_TRO_GIAY_GOI = 'GIAY_GOI';
    static VAI_TRO_RUY_BANG = 'RUY_BANG';
    static VAI_TRO_PHU_KIEN_TRANG_TRI = 'PHU_KIEN_TRANG_TRI';
    static VAI_TRO_THIEP = 'THIEP';
    static VAI_TRO_XOP = 'XOP';

    static VAI_TRO = [
        ChiTietBoHoa.VAI_TRO_HOA_CHINH,
        ChiTietBoHoa.VAI_TRO_HOA_TRANG_TRI,
        ChiTietBoHoa.VAI_TRO_GIAY_GOI,
        ChiTietBoHoa.VAI_TRO_RUY_BANG,
        ChiTietBoHoa.VAI_TRO_PHU_KIEN_TRANG_TRI,
        ChiTietBoHoa.VAI_TRO_THIEP,
        ChiTietBoHoa.VAI_TRO_XOP,
    ];

    constructor(row = {}) {
        this.ma_chi_tiet = row.ma_chi_tiet;
        this.ma_bo_hoa = row.ma_bo_hoa;
        this.ma_san_pham = row.ma_san_pham;
        this.ma_chi_tiet_nhap = row.ma_chi_tiet_nhap;
        this.vai_tro = row.vai_tro;
        this.so_luong = row.so_luong;

        // Thuộc tính "ảo" khi JOIN với san_pham
        if (row.ten_san_pham) {
            this.sanPham = new SanPham({
                ma_san_pham: row.ma_san_pham,
                ten_san_pham: row.ten_san_pham,
                loai_san_pham: row.loai_san_pham,
                hinh_anh: row.hinh_anh,
            });
        }
    }

    // Lấy tất cả chi tiết của 1 bó hoa
    static async findByBoHoa(ma_bo_hoa) {
        const [rows] = await pool.query(
            'SELECT * FROM chi_tiet_bo_hoa WHERE ma_bo_hoa = ?',
            [ma_bo_hoa]
        );
        return rows.map(row => new ChiTietBoHoa(row));
    }

    // Lấy chi tiết + JOIN sản phẩm
    static async findByBoHoaWithSanPham(ma_bo_hoa) {
        const [rows] = await pool.query(
            `SELECT ct.*,
              sp.ten_san_pham,
              sp.loai_san_pham,
              sp.hinh_anh
       FROM chi_tiet_bo_hoa ct
       LEFT JOIN san_pham sp ON sp.ma_san_pham = ct.ma_san_pham
       WHERE ct.ma_bo_hoa = ?`,
            [ma_bo_hoa]
        );
        return rows.map(row => new ChiTietBoHoa(row));
    }

    // Lọc theo vai trò (dùng khi cần lấy hoa chính, giấy gói, ...)
    static async findByBoHoaVaVaiTro(ma_bo_hoa, vai_tro) {
        const [rows] = await pool.query(
            `SELECT * FROM chi_tiet_bo_hoa
       WHERE ma_bo_hoa = ? AND vai_tro = ?`,
            [ma_bo_hoa, vai_tro]
        );
        return rows.map(row => new ChiTietBoHoa(row));
    }

    // Lấy các chi tiết chưa phân bổ lô (còn DRAFT)
    static async findChuaCoLo(ma_bo_hoa) {
        const [rows] = await pool.query(
            `SELECT * FROM chi_tiet_bo_hoa
       WHERE ma_bo_hoa = ? AND ma_chi_tiet_nhap IS NULL`,
            [ma_bo_hoa]
        );
        return rows.map(row => new ChiTietBoHoa(row));
    }

    // Lấy các chi tiết đã phân bổ lô (đã trừ tồn kho)
    static async findDaCoLo(ma_bo_hoa) {
        const [rows] = await pool.query(
            `SELECT * FROM chi_tiet_bo_hoa
       WHERE ma_bo_hoa = ? AND ma_chi_tiet_nhap IS NOT NULL`,
            [ma_bo_hoa]
        );
        return rows.map(row => new ChiTietBoHoa(row));
    }

    // Lấy tất cả thành phần XỐP của 1 bó hoa
    static async findXop(ma_bo_hoa) {
        return ChiTietBoHoa.findByBoHoaVaVaiTro(ma_bo_hoa, ChiTietBoHoa.VAI_TRO_XOP);
    }

    // Tạo chi tiết mới
    static async create(data) {
        const [result] = await pool.query(
            `INSERT INTO chi_tiet_bo_hoa
        (ma_bo_hoa, ma_san_pham, ma_chi_tiet_nhap, vai_tro, so_luong)
       VALUES (?, ?, ?, ?, ?)`,
            [
                data.ma_bo_hoa,
                data.ma_san_pham,
                data.ma_chi_tiet_nhap ?? null,
                data.vai_tro,
                data.so_luong ?? 1,
            ]
        );
        return result.insertId;
    }

    // Gán lô cho chi tiết (khi kích hoạt bó hoa)
    async ganLo(ma_chi_tiet_nhap) {
        const [result] = await pool.query(
            `UPDATE chi_tiet_bo_hoa
       SET ma_chi_tiet_nhap = ?
       WHERE ma_chi_tiet = ?`,
            [ma_chi_tiet_nhap, this.ma_chi_tiet]
        );
        if (result.affectedRows > 0) {
            this.ma_chi_tiet_nhap = ma_chi_tiet_nhap;
        }
        return result.affectedRows > 0;
    }

    // Xóa hết chi tiết của 1 bó hoa
    static async xoaHetCuaBoHoa(ma_bo_hoa) {
        const [result] = await pool.query(
            'DELETE FROM chi_tiet_bo_hoa WHERE ma_bo_hoa = ?',
            [ma_bo_hoa]
        );
        return result.affectedRows;
    }

    // Getter tiện ích
    get daCoLo() { return this.ma_chi_tiet_nhap !== null && this.ma_chi_tiet_nhap !== undefined; }
    get chuaCoLo() { return !this.daCoLo; }
    get laHoaChinh() { return this.vai_tro === ChiTietBoHoa.VAI_TRO_HOA_CHINH; }
    get laXop() { return this.vai_tro === ChiTietBoHoa.VAI_TRO_XOP; }

    get tenHienThi() {
        return this.sanPham?.ten_san_pham || `Sản phẩm #${this.ma_san_pham}`;
    }

}

module.exports = ChiTietBoHoa;