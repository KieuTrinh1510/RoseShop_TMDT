// src/models/KhachHang.js

const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class KhachHang extends Model {

    // Tìm theo mã (giữ tên cũ để tương thích với code gọi nó)
    static async findById(ma_khach_hang) {
        return KhachHang.findByPk(ma_khach_hang);
    }

    // Cộng điểm (shipper giao trễ → hệ thống cộng điểm xin lỗi)
    async congDiem(soDiem) {
        const truoc = this.diem_tich_luy || 0;
        const [affectedRows] = await KhachHang.increment('diem_tich_luy', {
            by: soDiem,
            where: { ma_khach_hang: this.ma_khach_hang },
        });
        this.diem_tich_luy = truoc + soDiem;
        return true;
    }
}

KhachHang.init({
    ma_khach_hang: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ten_khach_hang: DataTypes.STRING,
    so_dien_thoai: DataTypes.STRING(20),
    email: DataTypes.STRING,
    dia_chi: DataTypes.TEXT,
    diem_tich_luy: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
    },
}, {
    sequelize,
    modelName: 'KhachHang',
    tableName: 'khach_hang',
    timestamps: false,
});

module.exports = KhachHang;