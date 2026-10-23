const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class NguoiDung extends Model {

    // Tìm theo mã, trả về instance NguoiDung hoặc null
    static async findById(ma_nguoi_dung) {
        return NguoiDung.findByPk(ma_nguoi_dung);
    }

    // Tìm theo tên đăng nhập (dùng khi login)
    static async findByUsername(ten_dang_nhap) {
        return NguoiDung.findOne({ where: { ten_dang_nhap } });
    }

    // Static = hỏi Class "hãy tìm/tạo cho tôi một object". dùng khi CHƯA có object cụ thể
    // Không static = hỏi Object "hãy làm gì đó với chính mày" dùng khi ĐÃ có object cụ thể
    // Tạo tài khoản mới: dùng thẳng NguoiDung.create({ ten_dang_nhap, mat_khau, vai_tro })
    // (Sequelize đã có sẵn static create() — không override lại để tránh đè mất hàm gốc)

    // Cập nhật mật khẩu
    async updatePassword(mat_khauMoi) {
        this.mat_khau = mat_khauMoi;
        await this.save({ fields: ['mat_khau'] });
        return true;
    }

    // Kiểm tra vai trò (tiện dùng ở middleware)
    isShipper() {
        return this.vai_tro === 'SHIPPER';
    }
    isAdmin() {
        return this.vai_tro === 'ADMIN';
    }
    isNhanVien() {
        return this.vai_tro === 'NHAN_VIEN';
    }
    isKhachHang() {
        return this.vai_tro === 'KHACH_HANG';
    }
}

NguoiDung.init({
    ma_nguoi_dung: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ten_dang_nhap: {
        type: DataTypes.STRING,
        unique: true,
    },
    mat_khau: DataTypes.STRING,
    vai_tro: DataTypes.STRING,
    // Cột đã có sẵn trong DB (Laravel dùng): CHO_XAC_THUC | HOAT_DONG | BI_KHOA
    trang_thai: DataTypes.STRING(30),
    email_verified_at: DataTypes.DATE,
}, {
    sequelize,
    modelName: 'NguoiDung',
    tableName: 'nguoi_dung',
    timestamps: false,
});

module.exports = NguoiDung;