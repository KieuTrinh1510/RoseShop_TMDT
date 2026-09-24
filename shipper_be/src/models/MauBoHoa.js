// src/models/MauBoHoa.js
// Mẫu bó hoa do hệ thống/cửa hàng định nghĩa sẵn (template)

const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class MauBoHoa extends Model {

    // Trạng thái mẫu
    static TRANG_THAI_DRAFT = 'DRAFT';
    static TRANG_THAI_ACTIVE = 'ACTIVE';
    static TRANG_THAI_INACTIVE = 'INACTIVE';

    // Tìm theo mã
    static async findById(ma_mau_bo_hoa) {
        return MauBoHoa.findByPk(ma_mau_bo_hoa);
    }

    // Lấy tất cả mẫu đang ACTIVE
    static async findActive() {
        return MauBoHoa.findAll({
            where: { trang_thai: MauBoHoa.TRANG_THAI_ACTIVE },
            order: [['ma_mau_bo_hoa', 'DESC']],
        });
    }

    // Lấy tất cả mẫu theo trạng thái
    static async findByTrangThai(trang_thai) {
        return MauBoHoa.findAll({ where: { trang_thai } });
    }

    // Lấy mẫu kèm chi tiết thành phần (JOIN)
    static async findByIdWithChiTiet(ma_mau_bo_hoa) {
        // require ở đây để tránh vòng lặp require giữa 2 model
        const ChiTietMauBoHoa = require('./ChiTietMauBoHoa');
        const SanPham = require('./SanPham');

        const mau = await MauBoHoa.findByPk(ma_mau_bo_hoa, {
            include: [{
                model: ChiTietMauBoHoa,
                as: 'chiTiet',
                include: [{
                    model: SanPham,
                    as: 'sanPham',
                    attributes: ['ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'hinh_anh'],
                }],
            }],
        });
        return mau;
    }

    // Cập nhật mẫu (instance): dùng thẳng mau.update({ ten_mau, mo_ta, ... })
    // — đây là instance method có sẵn của Sequelize, tự động chỉ ghi các field
    // được truyền vào, không cần viết lại whitelist thủ công như bản mysql2.

    // Đổi trạng thái
    async doiTrangThai(trang_thai) {
        return this.update({ trang_thai });
    }

    // Getter
    get dangActive() { return this.trang_thai === MauBoHoa.TRANG_THAI_ACTIVE; }
    get laDraft() { return this.trang_thai === MauBoHoa.TRANG_THAI_DRAFT; }
}

MauBoHoa.init({
    ma_mau_bo_hoa: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ten_mau: DataTypes.STRING,
    mo_ta: DataTypes.TEXT,
    kieu_bo_hoa: DataTypes.STRING,
    size: DataTypes.STRING,
    concept: DataTypes.STRING,
    trang_thai: {
        type: DataTypes.STRING,
        defaultValue: 'DRAFT',
    },
}, {
    sequelize,
    modelName: 'MauBoHoa',
    tableName: 'mau_bo_hoa',
    timestamps: false,
});

module.exports = MauBoHoa;