// src/models/ChiTietHoaDon.js
const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const SanPham = require('./SanPham');
const HoaDon = require('./HoaDon');

class ChiTietHoaDon extends Model {

    // Lấy tất cả chi tiết của 1 hóa đơn
    static async findByHoaDon(ma_hoa_don) {
        return ChiTietHoaDon.findAll({ where: { ma_hoa_don } });
    }

    // Lấy chi tiết + JOIN sản phẩm + bó hoa (nếu có)
    static async findByHoaDonWithSanPham(ma_hoa_don) {
        return ChiTietHoaDon.findAll({
            where: { ma_hoa_don },
            include: [
                {
                    model: SanPham,
                    as: 'sanPham',
                    attributes: ['ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'hinh_anh'],
                },
                {
                    model: BoHoa,
                    as: 'boHoa',
                    attributes: ['ma_bo_hoa', 'ten_bo_hoa', 'size', 'hinh_anh', 'ma_mau_bo_hoa'],
                    include: [{
                        model: MauBoHoa,
                        as: 'mauBoHoa',
                        attributes: ['ma_mau_bo_hoa', 'ten_mau'],
                    }],
                },
            ],
        });
    }

    // Tính thành tiền (số lượng × giá bán)
    get thanhTien() {
        return this.so_luong * Number(this.gia_ban_snapshot || 0);
    }

    /**
     * Tên hiển thị ưu tiên:
     *   1. Bó hoa: ten_bo_hoa → mauBoHoa.ten_mau → fallback mã
     *   2. Sản phẩm lẻ: ten_san_pham
     */
    get tenHienThi() {
        if (this.ma_bo_hoa) {
            if (this.boHoa) {
                return this.boHoa.ten_bo_hoa
                    || this.boHoa.mauBoHoa?.ten_mau
                    || `Bó hoa #${this.ma_bo_hoa}`;
            }
            return `Bó hoa #${this.ma_bo_hoa}`;
        }
        return this.sanPham?.ten_san_pham || `Sản phẩm #${this.ma_san_pham}`;
    }

    get laBoHoa() {
        return !!this.ma_bo_hoa;
    }
}

ChiTietHoaDon.init({
    ma_chi_tiet: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_hoa_don: DataTypes.INTEGER,
    ma_san_pham: DataTypes.INTEGER,
    ma_bo_hoa: DataTypes.INTEGER,   // ← [MỚI] dòng đại diện bó hoa
    ma_chi_tiet_nhap: DataTypes.INTEGER,
    so_luong: {
        type: DataTypes.INTEGER,
        defaultValue: 1,
    },
    gia_nhap_snapshot: DataTypes.DECIMAL(12, 2),
    gia_ban_snapshot: DataTypes.DECIMAL(12, 2),
}, {
    sequelize,
    modelName: 'ChiTietHoaDon',
    tableName: 'chi_tiet_hoa_don',
    timestamps: false,
});

ChiTietHoaDon.belongsTo(SanPham, { foreignKey: 'ma_san_pham', as: 'sanPham' });
ChiTietHoaDon.belongsTo(HoaDon, { foreignKey: 'ma_hoa_don', as: 'hoaDon' });

// ── Lazy require BoHoa + MauBoHoa để tránh circular require ──
// (BoHoa.js require HoaDon.js → HoaDon.js require ChiTietHoaDon qua lazy resolve)
const BoHoa = require('./BoHoa');
const MauBoHoa = require('./MauBoHoa');

ChiTietHoaDon.belongsTo(BoHoa, { foreignKey: 'ma_bo_hoa', as: 'boHoa' });

HoaDon.hasMany(ChiTietHoaDon, {
    foreignKey: 'ma_hoa_don',
    as: 'chiTietHoaDon',
});

module.exports = ChiTietHoaDon;