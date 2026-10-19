const { Model, DataTypes } = require('sequelize');
const sequelize = require('../config/database');

// Trạng thái báo cáo:
//  DA_BAO          : đã báo / đã chốt hủy (SHIPPER, hoặc CUSTOMER sau finalize)
//  DANG_CHO_KHACH  : lỗi CUSTOMER giai đoạn 1 — đơn vẫn SHIPPING, đang chờ khách 10 phút nữa
//  KHACH_DA_NHAN   : khách xuống nhận hàng trong thời gian chờ, đơn DELIVERED
//  (DANG_CHO_KHACH, KHACH_DA_NHAN cần migration thêm vào enum của cột trang_thai)
class GiaoHangThatBai extends Model {
    get dangChoKhach() {
        return this.loai_loi === 'CUSTOMER' && this.trang_thai === 'DANG_CHO_KHACH';
    }
}

GiaoHangThatBai.init({
    ma_giao_hang_that_bai: {
        type: DataTypes.BIGINT.UNSIGNED,
        primaryKey: true,
        autoIncrement: true,
    },
    ma_hoa_don: DataTypes.INTEGER,
    ma_nhan_vien_giao: DataTypes.INTEGER,
    loai_loi: DataTypes.ENUM('SHIPPER', 'CUSTOMER'),
    ly_do: DataTypes.STRING(150),
    ghi_chu: DataTypes.TEXT,
    thoi_diem_bao: DataTypes.DATE,
    // CUSTOMER: khung chờ LẦN HAI (bắt đầu = lúc ghi nhận, kết thúc = +10 phút).
    // SHIPPER : bat_dau = ngay_den_noi (nếu có), ket_thuc = null.
    bat_dau_dem_10_phut: DataTypes.DATE,
    ket_thuc_dem_10_phut: DataTypes.DATE,
    trang_thai: {
        type: DataTypes.ENUM(
            'DA_BAO',
            'CHO_HOAN_HANG',
            'DA_TIEP_NHAN',
            'DA_XU_LY',
            'DANG_CHO_KHACH',
            'KHACH_DA_NHAN'
        ),
        defaultValue: 'DA_BAO',
    },
}, {
    sequelize,
    modelName: 'GiaoHangThatBai',
    tableName: 'giao_hang_that_bai',
    timestamps: true,
    underscored: true,
});

module.exports = GiaoHangThatBai;