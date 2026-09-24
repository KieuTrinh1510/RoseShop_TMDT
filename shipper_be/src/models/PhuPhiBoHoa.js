// src/models/PhuPhiBoHoa.js
// Phụ phí khi tạo bó hoa (ví dụ: phí công cắm hoa, phí gói quà, phí thiệp)
// Bảng này thường chỉ có vài dòng cấu hình sẵn.

const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

class PhuPhiBoHoa extends Model {

    // Lấy tất cả phụ phí
    static async findAllPhuPhi() {
        return PhuPhiBoHoa.findAll({ order: [['ma_phu_phi', 'ASC']] });
    }

    // Tìm theo mã
    static async findById(ma_phu_phi) {
        return PhuPhiBoHoa.findByPk(ma_phu_phi);
    }

    // Tìm theo loại (ví dụ: 'THIEP', 'CONG_CAM_HOA')
    static async findByLoai(loai) {
        return PhuPhiBoHoa.findAll({ where: { loai } });
    }

    // Lấy 1 phụ phí theo loại (thường chỉ có 1)
    static async findMotTheoLoai(loai) {
        return PhuPhiBoHoa.findOne({ where: { loai } });
    }

    // Getter tiện ích: trả về số tiền dạng number
    get soTien() {
        return Number(this.phu_phi) || 0;
    }
}

PhuPhiBoHoa.init({
    ma_phu_phi: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    loai: DataTypes.STRING,
    gia_tri: DataTypes.STRING,
    phu_phi: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
}, {
    sequelize,
    modelName: 'PhuPhiBoHoa',
    tableName: 'phu_phi_bo_hoa',
    timestamps: false,
});

module.exports = PhuPhiBoHoa;