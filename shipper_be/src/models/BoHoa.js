// src/models/BoHoa.js
// Bảng bó hoa: lưu bó hoa do khách tạo (Custom Mix) hoặc bó hoa mẫu hệ thống

const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const KhachHang = require('./KhachHang');
const MauBoHoa = require('./MauBoHoa');
const HoaDon = require('./HoaDon');

class BoHoa extends Model {

    // Hằng số cho loai_bo_hoa và trang_thai
    static LOAI_HE_THONG = 'HE_THONG';
    static LOAI_TUY_CHON = 'TUY_CHON';

    static TRANG_THAI_DRAFT = 'DRAFT';
    static TRANG_THAI_ACTIVE = 'ACTIVE';

    // Tìm theo mã
    static async findById(ma_bo_hoa) {
        return BoHoa.findByPk(ma_bo_hoa);
    }

    // Lấy tất cả bó hoa của 1 hóa đơn
    // (dùng khi shipper xem đơn có Custom Mix)
    static async findByHoaDon(ma_hoa_don) {
        return BoHoa.findAll({ where: { ma_hoa_don } });
    }

    // Lấy bó hoa của 1 khách (5 cái gần nhất)
    static async findGanNhatCuaKhach(ma_khach_hang, limit = 5) {
        return BoHoa.findAll({
            where: { ma_khach_hang },
            order: [['ma_bo_hoa', 'DESC']],
            limit: Number(limit),
        });
    }

    // Lấy kèm thông tin khách + mẫu bó hoa (JOIN)
    static async findByIdWithJoins(ma_bo_hoa) {
        return BoHoa.findByPk(ma_bo_hoa, {
            include: [
                {
                    model: KhachHang,
                    as: 'khachHang',
                    attributes: ['ma_khach_hang', 'ten_khach_hang', 'so_dien_thoai'],
                },
                {
                    model: MauBoHoa,
                    as: 'mauBoHoa',
                    attributes: ['ma_mau_bo_hoa', 'ten_mau', 'kieu_bo_hoa', 'size', 'concept'],
                },
            ],
        });
    }

    // Getter tiện ích
    get laHeThong() { return this.loai_bo_hoa === BoHoa.LOAI_HE_THONG; }
    get laTuyChon() { return this.loai_bo_hoa === BoHoa.LOAI_TUY_CHON; }
    get daActive() { return this.trang_thai === BoHoa.TRANG_THAI_ACTIVE; }
    get laDraft() { return this.trang_thai === BoHoa.TRANG_THAI_DRAFT; }
}

BoHoa.init({
    ma_bo_hoa: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_khach_hang: DataTypes.INTEGER,
    ma_mau_bo_hoa: DataTypes.INTEGER,
    ma_hoa_don: DataTypes.INTEGER,
    ten_bo_hoa: DataTypes.STRING,
    so_luong: {
        type: DataTypes.INTEGER,
        defaultValue: 1,
    },
    gia_ban: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    loai_bo_hoa: {
        type: DataTypes.STRING,
        defaultValue: BoHoa.LOAI_TUY_CHON,
    },
    kieu_bo_hoa: DataTypes.STRING,
    size: DataTypes.STRING,
    concept: DataTypes.STRING,
    loi_nhan: DataTypes.TEXT,
    hinh_anh: DataTypes.STRING,
    trang_thai: {
        type: DataTypes.STRING,
        defaultValue: BoHoa.TRANG_THAI_DRAFT,
    },
}, {
    sequelize,
    modelName: 'BoHoa',
    tableName: 'bo_hoa',
    timestamps: false,
});

BoHoa.belongsTo(KhachHang, { foreignKey: 'ma_khach_hang', as: 'khachHang' });
BoHoa.belongsTo(MauBoHoa, { foreignKey: 'ma_mau_bo_hoa', as: 'mauBoHoa' });
BoHoa.belongsTo(HoaDon, { foreignKey: 'ma_hoa_don', as: 'hoaDon' });

module.exports = BoHoa;