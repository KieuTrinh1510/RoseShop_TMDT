const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const ChiTietHangHoan = require('./ChiTietHangHoan');

class HangHoan extends Model {
    // Admin đã kiểm tra hàng hoàn xong chưa
    get daKiemTra() {
        return this.trang_thai === 'DA_KIEM_TRA';
    }
}

HangHoan.init({
    ma_hang_hoan: {
        type: DataTypes.BIGINT.UNSIGNED,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_hoa_don: DataTypes.INTEGER,
    ma_giao_hang_that_bai: DataTypes.BIGINT.UNSIGNED,
    trang_thai: {
        type: DataTypes.ENUM('CHO_MANG_VE_KHO', 'DA_NHAN_TAI_KHO', 'DANG_KIEM_TRA', 'DA_KIEM_TRA'),
        defaultValue: 'CHO_MANG_VE_KHO',
    },
    nguoi_kiem_tra: DataTypes.INTEGER,
    thoi_diem_nhan_tai_kho: DataTypes.DATE,
    thoi_diem_kiem_tra: DataTypes.DATE,
    ghi_chu: DataTypes.TEXT,
}, {
    sequelize,
    modelName: 'HangHoan',
    tableName: 'hang_hoan',
    timestamps: true,
    underscored: true,
});

HangHoan.hasMany(ChiTietHangHoan, { foreignKey: 'ma_hang_hoan', as: 'chiTiet' });
ChiTietHangHoan.belongsTo(HangHoan, { foreignKey: 'ma_hang_hoan', as: 'hangHoan' });

module.exports = HangHoan;