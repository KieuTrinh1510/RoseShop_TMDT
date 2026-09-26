// src/models/ChiTietMauBoHoa.js
// Chi tiết thành phần của 1 mẫu bó hoa

const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const SanPham = require('./SanPham');
const MauBoHoa = require('./MauBoHoa');

class ChiTietMauBoHoa extends Model {

    // Hằng số vai trò (giống ChiTietBoHoa)
    static VAI_TRO_HOA_CHINH = 'HOA_CHINH';
    static VAI_TRO_HOA_TRANG_TRI = 'HOA_TRANG_TRI';
    static VAI_TRO_GIAY_GOI = 'GIAY_GOI';
    static VAI_TRO_RUY_BANG = 'RUY_BANG';
    static VAI_TRO_PHU_KIEN_TRANG_TRI = 'PHU_KIEN_TRANG_TRI';
    static VAI_TRO_THIEP = 'THIEP';
    static VAI_TRO_XOP = 'XOP';

    static VAI_TRO = [
        ChiTietMauBoHoa.VAI_TRO_HOA_CHINH,
        ChiTietMauBoHoa.VAI_TRO_HOA_TRANG_TRI,
        ChiTietMauBoHoa.VAI_TRO_GIAY_GOI,
        ChiTietMauBoHoa.VAI_TRO_RUY_BANG,
        ChiTietMauBoHoa.VAI_TRO_PHU_KIEN_TRANG_TRI,
        ChiTietMauBoHoa.VAI_TRO_THIEP,
        ChiTietMauBoHoa.VAI_TRO_XOP,
    ];

    // Lấy tất cả chi tiết của 1 mẫu
    static async findByMauBoHoa(ma_mau_bo_hoa) {
        return ChiTietMauBoHoa.findAll({ where: { ma_mau_bo_hoa } });
    }

    // Lấy chi tiết + JOIN sản phẩm
    static async findByMauBoHoaWithSanPham(ma_mau_bo_hoa) {
        return ChiTietMauBoHoa.findAll({
            where: { ma_mau_bo_hoa },
            include: [{
                model: SanPham,
                as: 'sanPham',
                attributes: ['ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'hinh_anh'],
            }],
        });
    }

    // Lọc theo vai trò
    static async findByMauVaVaiTro(ma_mau_bo_hoa, vai_tro) {
        return ChiTietMauBoHoa.findAll({ where: { ma_mau_bo_hoa, vai_tro } });
    }

    // Lấy xốp của mẫu
    static async findXop(ma_mau_bo_hoa) {
        return ChiTietMauBoHoa.findByMauVaVaiTro(
            ma_mau_bo_hoa,
            ChiTietMauBoHoa.VAI_TRO_XOP
        );
    }

    // Xóa hết chi tiết của 1 mẫu
    static async xoaHetCuaMau(ma_mau_bo_hoa) {
        return ChiTietMauBoHoa.destroy({ where: { ma_mau_bo_hoa } });
    }

    // Getter
    get laHoaChinh() { return this.vai_tro === ChiTietMauBoHoa.VAI_TRO_HOA_CHINH; }
    get laXop() { return this.vai_tro === ChiTietMauBoHoa.VAI_TRO_XOP; }

    get tenHienThi() {
        return this.sanPham?.ten_san_pham || `Sản phẩm #${this.ma_san_pham}`;
    }
}

ChiTietMauBoHoa.init({
    ma_chi_tiet_mau: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_mau_bo_hoa: DataTypes.INTEGER,
    ma_san_pham: DataTypes.INTEGER,
    vai_tro: DataTypes.STRING,
    so_luong: {
        type: DataTypes.INTEGER,
        defaultValue: 1,
    },
}, {
    sequelize,
    modelName: 'ChiTietMauBoHoa',
    tableName: 'chi_tiet_mau_bo_hoa',
    timestamps: false,
});

ChiTietMauBoHoa.belongsTo(SanPham, { foreignKey: 'ma_san_pham', as: 'sanPham' });
ChiTietMauBoHoa.belongsTo(MauBoHoa, { foreignKey: 'ma_mau_bo_hoa', as: 'mauBoHoa' });
MauBoHoa.hasMany(ChiTietMauBoHoa, { foreignKey: 'ma_mau_bo_hoa', as: 'chiTiet' });

module.exports = ChiTietMauBoHoa;