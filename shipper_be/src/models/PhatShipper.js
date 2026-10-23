const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class PhatShipper extends Model {}

PhatShipper.init({
    ma_phat_shipper: {
        type: DataTypes.BIGINT.UNSIGNED,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_nhan_vien: DataTypes.INTEGER,
    ma_hoa_don: DataTypes.INTEGER,
    ma_giao_hang_that_bai: DataTypes.BIGINT.UNSIGNED,
    so_tien: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    tien_hang_hong: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    tien_hao_hut_flash_sale: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    ly_do: DataTypes.STRING(255),
    trang_thai: {
        type: DataTypes.ENUM('CHUA_THU', 'DA_THU', 'MIEN_PHAT'),
        defaultValue: 'CHUA_THU',
    },
}, {
    sequelize,
    modelName: 'PhatShipper',
    tableName: 'phat_shipper',
    timestamps: true,
    underscored: true,
});

module.exports = PhatShipper;
