// src/models/ChiTietBoHoa.js
// Chi tiết thành phần của 1 bó hoa (hoa chính, giấy gói, ruy băng, ...)

const { Model, DataTypes, Op } = require('sequelize');
const sequelize = require('../config/database');
const SanPham = require('./SanPham');

class ChiTietBoHoa extends Model {

    // Hằng số vai trò thành phần trong bó hoa
    static VAI_TRO_HOA_CHINH = 'HOA_CHINH';
    static VAI_TRO_HOA_TRANG_TRI = 'HOA_TRANG_TRI';
    static VAI_TRO_GIAY_GOI = 'GIAY_GOI';
    static VAI_TRO_RUY_BANG = 'RUY_BANG';
    static VAI_TRO_PHU_KIEN_TRANG_TRI = 'PHU_KIEN_TRANG_TRI';
    static VAI_TRO_THIEP = 'THIEP';
    static VAI_TRO_XOP = 'XOP';

    static VAI_TRO = [
        ChiTietBoHoa.VAI_TRO_HOA_CHINH,
        ChiTietBoHoa.VAI_TRO_HOA_TRANG_TRI,
        ChiTietBoHoa.VAI_TRO_GIAY_GOI,
        ChiTietBoHoa.VAI_TRO_RUY_BANG,
        ChiTietBoHoa.VAI_TRO_PHU_KIEN_TRANG_TRI,
        ChiTietBoHoa.VAI_TRO_THIEP,
        ChiTietBoHoa.VAI_TRO_XOP,
    ];

    // Lấy tất cả chi tiết của 1 bó hoa
    static async findByBoHoa(ma_bo_hoa) {
        return ChiTietBoHoa.findAll({ where: { ma_bo_hoa } });
    }

    // Lấy chi tiết + JOIN sản phẩm
    static async findByBoHoaWithSanPham(ma_bo_hoa) {
        return ChiTietBoHoa.findAll({
            where: { ma_bo_hoa },
            include: [{
                model: SanPham,
                as: 'sanPham',
                attributes: ['ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'hinh_anh'],
            }],
        });
    }

    // Lọc theo vai trò (dùng khi cần lấy hoa chính, giấy gói, ...)
    static async findByBoHoaVaVaiTro(ma_bo_hoa, vai_tro) {
        return ChiTietBoHoa.findAll({ where: { ma_bo_hoa, vai_tro } });
    }

    // Lấy các chi tiết chưa phân bổ lô (còn DRAFT)
    static async findChuaCoLo(ma_bo_hoa) {
        return ChiTietBoHoa.findAll({
            where: { ma_bo_hoa, ma_chi_tiet_nhap: null },
        });
    }

    // Lấy các chi tiết đã phân bổ lô (đã trừ tồn kho)
    static async findDaCoLo(ma_bo_hoa) {
        return ChiTietBoHoa.findAll({
            where: { ma_bo_hoa, ma_chi_tiet_nhap: { [Op.ne]: null } },
        });
    }

    // Lấy tất cả thành phần XỐP của 1 bó hoa
    static async findXop(ma_bo_hoa) {
        return ChiTietBoHoa.findByBoHoaVaVaiTro(ma_bo_hoa, ChiTietBoHoa.VAI_TRO_XOP);
    }

    // Gán lô cho chi tiết (khi kích hoạt bó hoa)
    async ganLo(ma_chi_tiet_nhap) {
        await this.update({ ma_chi_tiet_nhap });
        return true;
    }

    // Xóa hết chi tiết của 1 bó hoa
    static async xoaHetCuaBoHoa(ma_bo_hoa) {
        return ChiTietBoHoa.destroy({ where: { ma_bo_hoa } });
    }

    // Getter tiện ích
    get daCoLo() { return this.ma_chi_tiet_nhap !== null && this.ma_chi_tiet_nhap !== undefined; }
    get chuaCoLo() { return !this.daCoLo; }
    get laHoaChinh() { return this.vai_tro === ChiTietBoHoa.VAI_TRO_HOA_CHINH; }
    get laXop() { return this.vai_tro === ChiTietBoHoa.VAI_TRO_XOP; }

    get tenHienThi() {
        return this.sanPham?.ten_san_pham || `Sản phẩm #${this.ma_san_pham}`;
    }
}

ChiTietBoHoa.init({
    ma_chi_tiet: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_bo_hoa: DataTypes.INTEGER,
    ma_san_pham: DataTypes.INTEGER,
    ma_chi_tiet_nhap: DataTypes.INTEGER,
    vai_tro: DataTypes.STRING,
    so_luong: {
        type: DataTypes.INTEGER,
        defaultValue: 1,
    },
}, {
    sequelize,
    modelName: 'ChiTietBoHoa',
    tableName: 'chi_tiet_bo_hoa',
    timestamps: false,
});

ChiTietBoHoa.belongsTo(SanPham, { foreignKey: 'ma_san_pham', as: 'sanPham' });

module.exports = ChiTietBoHoa;