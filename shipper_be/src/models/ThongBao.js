// src/models/ThongBao.js
const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class ThongBao extends Model { }

ThongBao.init({
    ma_thong_bao: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_nguoi_dung: DataTypes.INTEGER,
    loai: {
        // order_new | payment | penalty | shipping | arrived | delivered | apology | confirmed | cancelled | info
        type: DataTypes.STRING(20),
        defaultValue: 'info',
    },
    noi_dung: DataTypes.TEXT,
    du_lieu: {
        type: DataTypes.JSON,
        allowNull: true,
    },
    ma_hoa_don: DataTypes.INTEGER,
    da_doc: {
        type: DataTypes.BOOLEAN,
        defaultValue: false,
    },
    thoi_gian: {
        type: DataTypes.DATE,
        defaultValue: DataTypes.NOW,
    },
}, {
    sequelize,
    modelName: 'ThongBao',
    tableName: 'thong_bao',
    timestamps: false,
});

module.exports = ThongBao;