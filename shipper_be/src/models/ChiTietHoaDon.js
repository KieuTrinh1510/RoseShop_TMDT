const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const SanPham = require('./SanPham');
const HoaDon = require('./HoaDon');

class ChiTietHoaDon extends Model {

    // Lấy tất cả chi tiết của 1 hóa đơn
    static async findByHoaDon(ma_hoa_don) {
        return ChiTietHoaDon.findAll({ where: { ma_hoa_don } });
    }

    // Lấy chi tiết + JOIN sản phẩm
    static async findByHoaDonWithSanPham(ma_hoa_don) {
        return ChiTietHoaDon.findAll({
            where: { ma_hoa_don },
            include: [{
                model: SanPham,
                as: 'sanPham',
                attributes: ['ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'hinh_anh'],
            }],
        });
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

ChiTietHoaDon.init({
    ma_chi_tiet: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_hoa_don: DataTypes.INTEGER,
    ma_san_pham: DataTypes.INTEGER,
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
HoaDon.hasMany(ChiTietHoaDon, {
    foreignKey: 'ma_hoa_don',
    as: 'chiTietHoaDon'
});

module.exports = ChiTietHoaDon;