// src/models/YeuCauDatLaiMatKhau.js
// Dùng chung bảng yeu_cau_dat_lai_mat_khau với Laravel (khách hàng dùng link + mã, shipper dùng mã 6 số).

const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class YeuCauDatLaiMatKhau extends Model {
    isExpired() {
        return new Date() > new Date(this.expires_at);
    }
}

YeuCauDatLaiMatKhau.TRANG_THAI_CO_HIEU_LUC = 'CO_HIEU_LUC';
YeuCauDatLaiMatKhau.TRANG_THAI_DA_SU_DUNG = 'DA_SU_DUNG';
YeuCauDatLaiMatKhau.TRANG_THAI_VO_HIEU_HOA = 'VO_HIEU_HOA';

YeuCauDatLaiMatKhau.init({
    id: {
        type: DataTypes.BIGINT.UNSIGNED,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_nguoi_dung: DataTypes.INTEGER,
    email: DataTypes.STRING(100),
    ma_xac_thuc: DataTypes.STRING(6),
    token: DataTypes.STRING(64),
    trang_thai: DataTypes.STRING(20),
    expires_at: DataTypes.DATE,
    created_at: DataTypes.DATE,
    updated_at: DataTypes.DATE,
}, {
    sequelize,
    modelName: 'YeuCauDatLaiMatKhau',
    tableName: 'yeu_cau_dat_lai_mat_khau',
    timestamps: false, // tự set created_at / updated_at (bảng do Laravel tạo)
});

module.exports = YeuCauDatLaiMatKhau;