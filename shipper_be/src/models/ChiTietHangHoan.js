const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');
const SanPham = require('./SanPham');
const BoHoa = require('./BoHoa');

class ChiTietHangHoan extends Model {
    // Cần đã nạp include sanPham / boHoa. Dòng bó hoa hiển thị dạng "Bó hoa: <tên>".
    get tenHienThi() {
        if (this.boHoa) {
            const ten = this.boHoa.ten_bo_hoa || this.boHoa.ten || `#${this.ma_bo_hoa}`;
            return `Bó hoa: ${ten}`;
        }
        if (this.sanPham) return this.sanPham.ten_san_pham;
        return this.ma_bo_hoa ? `Bó hoa #${this.ma_bo_hoa}` : `Sản phẩm #${this.ma_san_pham}`;
    }

    // 1 dòng trong bảng "Kết quả kiểm tra hàng hoàn": Sản phẩm | SL đơn | Hỏng | Còn bán | Kết quả | Ghi chú
    toKetQuaKiemTra() {
        return {
            ma_chi_tiet_hang_hoan: this.ma_chi_tiet_hang_hoan,
            loai: this.ma_bo_hoa ? 'BO_HOA' : 'SAN_PHAM',
            ten: this.tenHienThi,
            so_luong_theo_don: this.so_luong_theo_don,
            so_luong_hong: this.so_luong_hong,
            so_luong_con_ban_duoc: this.so_luong_con_ban_duoc,
            ket_qua: this.ket_qua,
            ghi_chu: this.ghi_chu || null,
        };
    }
}

ChiTietHangHoan.init({
    ma_chi_tiet_hang_hoan: {
        type: DataTypes.BIGINT.UNSIGNED,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_hang_hoan: DataTypes.BIGINT.UNSIGNED,
    ma_san_pham: DataTypes.INTEGER,
    ma_bo_hoa: DataTypes.BIGINT.UNSIGNED,
    ma_chi_tiet_hoa_don: DataTypes.BIGINT.UNSIGNED,
    so_luong_theo_don: DataTypes.INTEGER,
    so_luong_hong: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
    },
    so_luong_con_ban_duoc: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
    },
    so_luong_con_lai_flash_sale: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
    },
    gia_von_snapshot: DataTypes.DECIMAL(12, 2),
    gia_ban_snapshot: DataTypes.DECIMAL(12, 2),
    gia_flash_sale_snapshot: DataTypes.DECIMAL(12, 2),
    doanh_thu_thanh_ly: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    tien_hao_hut_flash_sale: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    tien_thiet_hai_hang_hong: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    tien_he_thong_chiu: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    tien_shipper_chiu: {
        type: DataTypes.DECIMAL(12, 2),
        defaultValue: 0,
    },
    mien_phat_shipper: {
        type: DataTypes.BOOLEAN,
        defaultValue: false,
    },
    ket_qua: {
        type: DataTypes.ENUM('CHO_KIEM_TRA', 'HONG', 'FLASH_SALE', 'DA_XU_LY'),
        defaultValue: 'CHO_KIEM_TRA',
    },
    ghi_chu: DataTypes.TEXT,
}, {
    sequelize,
    modelName: 'ChiTietHangHoan',
    tableName: 'chi_tiet_hang_hoan',
    timestamps: true,
    underscored: true,
});

ChiTietHangHoan.belongsTo(SanPham, { foreignKey: 'ma_san_pham', as: 'sanPham' });
ChiTietHangHoan.belongsTo(BoHoa, { foreignKey: 'ma_bo_hoa', as: 'boHoa' });

module.exports = ChiTietHangHoan;