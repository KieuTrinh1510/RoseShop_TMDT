const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class ViPhamKhachHang extends Model {}

ViPhamKhachHang.init({
    ma_vi_pham: {
        type: DataTypes.BIGINT.UNSIGNED,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_khach_hang: DataTypes.INTEGER,
    ma_hoa_don: DataTypes.INTEGER,
    ma_giao_hang_that_bai: DataTypes.BIGINT.UNSIGNED,
    ly_do: DataTypes.STRING(255),
    lan_vi_pham: {
        type: DataTypes.INTEGER.UNSIGNED,
        defaultValue: 1,
    },
    han_che_ap_dung: {
        type: DataTypes.ENUM('CANH_BAO', 'CAM_COD', 'TAM_KHOA_MUA_HANG'),
        defaultValue: 'CANH_BAO',
    },
}, {
    sequelize,
    modelName: 'ViPhamKhachHang',
    tableName: 'vi_pham_khach_hang',
    timestamps: true,
    underscored: true,
});

module.exports = ViPhamKhachHang;
