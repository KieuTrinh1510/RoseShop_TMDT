// src/models/NhanVien.js
const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const NguoiDung = require('./NguoiDung');

class NhanVien extends Model {

    static async findById(ma_nhan_vien) {
        return NhanVien.findByPk(ma_nhan_vien);
    }

    // Lấy nhân viên kèm tài khoản đăng nhập
    // (nd.ma_nguoi_dung = nv.ma_nhan_vien)
    static async findWithNguoiDung(ma_nhan_vien) {
        const nv = await NhanVien.findByPk(ma_nhan_vien, {
            include: [{
                model: NguoiDung,
                as: 'nguoiDung',
                attributes: ['ten_dang_nhap', 'vai_tro'],
            }],
        });

        if (!nv) return null;

        if (nv.nguoiDung) {
            nv.ten_dang_nhap = nv.nguoiDung.ten_dang_nhap;
            nv.vai_tro = nv.nguoiDung.vai_tro;
        }

        return nv;
    }

    async updateProfile({ ten_nhan_vien, so_dien_thoai, email }) {
        await this.update({
            ten_nhan_vien,
            so_dien_thoai,
            email
        });

        return true;
    }

    get chuCaiDau() {
        return (this.ten_nhan_vien || '?').charAt(0).toUpperCase();
    }
}

NhanVien.init({
    ma_nhan_vien: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },

    ten_nhan_vien: DataTypes.STRING,
    email: DataTypes.STRING,
    so_dien_thoai: DataTypes.STRING(20),
    chuc_vu: DataTypes.STRING,
    cong_viec: DataTypes.STRING,
    luong: DataTypes.DECIMAL(12, 2),

    // Trạng thái hoạt động của shipper
    trang_thai_hoat_dong: {
        type: DataTypes.STRING(10),
        defaultValue: 'OFFLINE',
    },



    // Số đơn shipper đang xử lý
    so_don_hien_tai: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
    },
    tien_phat: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },

}, {
    sequelize,
    modelName: 'NhanVien',
    tableName: 'nhan_vien',
    timestamps: false,
});

// nv.ma_nhan_vien dùng chung giá trị với nd.ma_nguoi_dung
NhanVien.belongsTo(NguoiDung, {
    foreignKey: 'ma_nhan_vien',
    targetKey: 'ma_nguoi_dung',
    as: 'nguoiDung',
    constraints: false,
});

module.exports = NhanVien;