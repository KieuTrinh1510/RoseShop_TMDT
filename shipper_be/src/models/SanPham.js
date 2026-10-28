const { Model, DataTypes, Op } = require('sequelize');
const sequelize = require('../config/database');

class SanPham extends Model {

    static async findById(ma_san_pham) {
        return SanPham.findByPk(ma_san_pham);
    }

    // Lấy nhiều sản phẩm cùng lúc theo mảng mã
    static async findByIds(dsMaSanPham) {
        if (!dsMaSanPham || dsMaSanPham.length === 0) return [];
        return SanPham.findAll({
            where: { ma_san_pham: { [Op.in]: dsMaSanPham } },
        });
    }

    /**
     * Kiểm tra sản phẩm có phải hoa tươi không.
     *   - Ưu tiên loai_san_pham (đáng tin).
     *   - Fallback heuristic tên cho dữ liệu cũ thiếu loai_san_pham.
     */
    get laHoaTuoi() {
        if (this.loai_san_pham) {
            return [
                'HOA_TUOI',
                'SAN_PHAM_PREMIUM',
                'CHAU_HOA_TUOI',
                'CAY_CANH',
            ].includes(this.loai_san_pham);
        }
        const ten = (this.ten_san_pham || '').toLowerCase();
        return ten.includes('hoa') || ten.includes('flower') || ten.includes('bó');
    }
}

SanPham.init({
    ma_san_pham: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ten_san_pham: DataTypes.STRING,
    loai_san_pham: DataTypes.STRING,
    don_vi_tinh: DataTypes.STRING,
    gia_ban_hien_tai: DataTypes.DECIMAL(12, 2),
    trang_thai: DataTypes.STRING,
    hinh_anh: DataTypes.STRING,
    mo_ta: DataTypes.TEXT,
}, {
    sequelize,
    modelName: 'SanPham',
    tableName: 'san_pham',
    timestamps: false,
});

module.exports = SanPham;
