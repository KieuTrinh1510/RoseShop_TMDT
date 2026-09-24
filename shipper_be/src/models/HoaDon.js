// src/models/HoaDon.js

const { Model, DataTypes, Op, fn, col } = require('sequelize');
const sequelize = require('../config/database');
const KhachHang = require('./KhachHang');
const NhanVien = require('./NhanVien');

class HoaDon extends Model {

    // Tìm theo mã
    static async findById(ma_hoa_don) {
        return HoaDon.findByPk(ma_hoa_don);
    }

    // Tìm theo mã + check thuộc shipper (dùng check quyền)
    static async findByIdAndShipper(ma_hoa_don, ma_nhan_vien_giao) {
        return HoaDon.findOne({
            where: { ma_hoa_don, ma_nhan_vien_giao },
        });
    }

    // Danh sách đơn cần giao của shipper
    static async findDonCanGiao(ma_nhan_vien_giao) {
        return HoaDon.findAll({
            where: {
                ma_nhan_vien_giao,
                trang_thai: { [Op.in]: ['CONFIRMED', 'SHIPPING'] },
            },
            include: [{
                model: KhachHang,
                as: 'khachHang',
                attributes: ['ma_khach_hang', 'ten_khach_hang', 'so_dien_thoai'],
            }],
            order: [['ngay_dat', 'DESC']],
        });
    }

    // Lịch sử giao hàng (phân trang)
    static async findLichSuGiao(ma_nhan_vien_giao, limit = 10, offset = 0) {
        return HoaDon.findAll({
            where: { ma_nhan_vien_giao, trang_thai: 'DELIVERED' },
            include: [{
                model: KhachHang,
                as: 'khachHang',
                attributes: ['ma_khach_hang', 'ten_khach_hang'],
            }],
            order: [['ngay_giao', 'DESC']],
            limit: Number(limit),
            offset: Number(offset),
        });
    }

    // Đếm tổng đơn đã giao (để tính số trang)
    static async countLichSuGiao(ma_nhan_vien_giao) {
        return HoaDon.count({
            where: { ma_nhan_vien_giao, trang_thai: 'DELIVERED' },
        });
    }

    // Tính tiền COD cần nộp
    static async tinhTienCanNop(ma_nhan_vien_giao) {
        const result = await HoaDon.findOne({
            attributes: [
                [fn('COALESCE', fn('SUM', col('tong_tien')), 0), 'tong_tien'],
                [fn('COUNT', col('ma_hoa_don')), 'so_don'],
            ],
            where: {
                ma_nhan_vien_giao,
                trang_thai: 'DELIVERED',
                trang_thai_thanh_toan: 'DA_THANH_TOAN',
                phuong_thuc_thanh_toan: 'COD',
            },
            raw: true,
        });
        return {
            tongTien: Number(result?.tong_tien) || 0,
            soDon: Number(result?.so_don) || 0,
        };
    }

    // Tổng doanh thu
    static async tinhTongDoanhThu(ma_nhan_vien_giao) {
        const result = await HoaDon.findOne({
            attributes: [
                [fn('COALESCE', fn('SUM', col('tong_tien')), 0), 'tong'],
            ],
            where: { ma_nhan_vien_giao, trang_thai: 'DELIVERED' },
            raw: true,
        });
        return Number(result?.tong) || 0;
    }

    // Cập nhật trạng thái (cách viết OOP hơn)
    async updateTrangThai(trang_thai, extraData = {}) {
        const data = { trang_thai };
        if (extraData.ngay_giao) data.ngay_giao = extraData.ngay_giao;
        if (extraData.trang_thai_thanh_toan) data.trang_thai_thanh_toan = extraData.trang_thai_thanh_toan;

        await this.update(data);
        return true;
    }

    // Getter tiện ích
    get maHienThi() {
        return `#HD-${String(this.ma_hoa_don).padStart(4, '0')}`;
    }

    get canThuTienMat() {
        return this.trang_thai_thanh_toan === 'CHUA_THANH_TOAN'
            && this.phuong_thuc_thanh_toan === 'COD';
    }

    get laHoaTuoi() {
        // Sẽ chính xác nếu caller nạp chiTietHoaDon vào
        return (this._chiTiet || []).some(ct => ct.sanPham?.laHoaTuoi);
    }
}

HoaDon.init({
    ma_hoa_don: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_khach_hang: DataTypes.INTEGER,
    ma_nhan_vien_giao: DataTypes.INTEGER,
    ngay_dat: DataTypes.DATE,
    ngay_giao: DataTypes.DATE,
    tong_tien: DataTypes.DECIMAL(12, 2),
    trang_thai: DataTypes.STRING,
    trang_thai_thanh_toan: DataTypes.STRING,
    phuong_thuc_thanh_toan: DataTypes.STRING,
    dia_chi_giao: DataTypes.TEXT,
    so_dien_thoai: DataTypes.STRING(20),
}, {
    sequelize,
    modelName: 'HoaDon',
    tableName: 'hoa_don',
    timestamps: false,
});

HoaDon.belongsTo(KhachHang, { foreignKey: 'ma_khach_hang', as: 'khachHang' });
HoaDon.belongsTo(NhanVien, { foreignKey: 'ma_nhan_vien_giao', as: 'nhanVienGiao' });

module.exports = HoaDon;