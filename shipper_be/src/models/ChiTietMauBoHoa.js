// src/models/ChiTietMauBoHoa.js
// Chi tiết thành phần của 1 mẫu bó hoa

const pool = require('../config/database');
const SanPham = require('./SanPham');

class ChiTietMauBoHoa {

    // Hằng số vai trò (giống ChiTietBoHoa)
    static VAI_TRO_HOA_CHINH = 'HOA_CHINH';
    static VAI_TRO_HOA_TRANG_TRI = 'HOA_TRANG_TRI';
    static VAI_TRO_GIAY_GOI = 'GIAY_GOI';
    static VAI_TRO_RUY_BANG = 'RUY_BANG';
    static VAI_TRO_PHU_KIEN_TRANG_TRI = 'PHU_KIEN_TRANG_TRI';
    static VAI_TRO_THIEP = 'THIEP';
    static VAI_TRO_XOP = 'XOP';

    static VAI_TRO = [
        ChiTietMauBoHoa.VAI_TRO_HOA_CHINH,
        ChiTietMauBoHoa.VAI_TRO_HOA_TRANG_TRI,
        ChiTietMauBoHoa.VAI_TRO_GIAY_GOI,
        ChiTietMauBoHoa.VAI_TRO_RUY_BANG,
        ChiTietMauBoHoa.VAI_TRO_PHU_KIEN_TRANG_TRI,
        ChiTietMauBoHoa.VAI_TRO_THIEP,
        ChiTietMauBoHoa.VAI_TRO_XOP,
    ];

    constructor(row = {}) {
        this.ma_chi_tiet_mau = row.ma_chi_tiet_mau;
        this.ma_mau_bo_hoa = row.ma_mau_bo_hoa;
        this.ma_san_pham = row.ma_san_pham;
        this.vai_tro = row.vai_tro;
        this.so_luong = row.so_luong;

        // Thuộc tính "ảo" khi JOIN
        if (row.ten_san_pham) {
            this.sanPham = new SanPham({
                ma_san_pham: row.ma_san_pham,
                ten_san_pham: row.ten_san_pham,
                loai_san_pham: row.loai_san_pham,
                hinh_anh: row.hinh_anh,
            });
        }
    }

    // Lấy tất cả chi tiết của 1 mẫu
    static async findByMauBoHoa(ma_mau_bo_hoa) {
        const [rows] = await pool.query(
            'SELECT * FROM chi_tiet_mau_bo_hoa WHERE ma_mau_bo_hoa = ?',
            [ma_mau_bo_hoa]
        );
        return rows.map(row => new ChiTietMauBoHoa(row));
    }

    // Lấy chi tiết + JOIN sản phẩm
    static async findByMauBoHoaWithSanPham(ma_mau_bo_hoa) {
        const [rows] = await pool.query(
            `SELECT ct.*,
              sp.ten_san_pham,
              sp.loai_san_pham,
              sp.hinh_anh
       FROM chi_tiet_mau_bo_hoa ct
       LEFT JOIN san_pham sp ON sp.ma_san_pham = ct.ma_san_pham
       WHERE ct.ma_mau_bo_hoa = ?`,
            [ma_mau_bo_hoa]
        );
        return rows.map(row => new ChiTietMauBoHoa(row));
    }

    // Lọc theo vai trò
    static async findByMauVaVaiTro(ma_mau_bo_hoa, vai_tro) {
        const [rows] = await pool.query(
            `SELECT * FROM chi_tiet_mau_bo_hoa
       WHERE ma_mau_bo_hoa = ? AND vai_tro = ?`,
            [ma_mau_bo_hoa, vai_tro]
        );
        return rows.map(row => new ChiTietMauBoHoa(row));
    }

    // Lấy xốp của mẫu
    static async findXop(ma_mau_bo_hoa) {
        return ChiTietMauBoHoa.findByMauVaVaiTro(
            ma_mau_bo_hoa,
            ChiTietMauBoHoa.VAI_TRO_XOP
        );
    }

    // Tạo chi tiết mới
    static async create(data) {
        const [result] = await pool.query(
            `INSERT INTO chi_tiet_mau_bo_hoa
        (ma_mau_bo_hoa, ma_san_pham, vai_tro, so_luong)
       VALUES (?, ?, ?, ?)`,
            [
                data.ma_mau_bo_hoa,
                data.ma_san_pham,
                data.vai_tro,
                data.so_luong ?? 1,
            ]
        );
        return result.insertId;
    }

    // Xóa hết chi tiết của 1 mẫu
    static async xoaHetCuaMau(ma_mau_bo_hoa) {
        const [result] = await pool.query(
            'DELETE FROM chi_tiet_mau_bo_hoa WHERE ma_mau_bo_hoa = ?',
            [ma_mau_bo_hoa]
        );
        return result.affectedRows;
    }

    // Getter
    get laHoaChinh() { return this.vai_tro === ChiTietMauBoHoa.VAI_TRO_HOA_CHINH; }
    get laXop() { return this.vai_tro === ChiTietMauBoHoa.VAI_TRO_XOP; }

    get tenHienThi() {
        return this.sanPham?.ten_san_pham || `Sản phẩm #${this.ma_san_pham}`;
    }

}

module.exports = ChiTietMauBoHoa;