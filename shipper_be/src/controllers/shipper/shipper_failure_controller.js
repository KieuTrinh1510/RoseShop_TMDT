// shipper_be/src/controllers/shipper/shipper_failure_controller.js
//
// Controller mỏng cho luồng báo giao thất bại.
// Chỉ validate input rồi gọi service; lỗi nghiệp vụ do service ném bằng taoLoi() (status + code).

'use strict';

const { layMaNhanVien, chay } = require('./_helpers');
const { taoLoi, parseMaHoaDon } = require('../../utils/loi');
const failureService = require('../../services/orders/shipper_failure/shipper_failure_service');

const LOAI_LOI_HOP_LE = ['CUSTOMER', 'SHIPPER'];
const LY_DO_TOI_DA = 150;
const GHI_CHU_TOI_DA = 1000;

// Chuẩn hoá + validate body của report-failure. Không tin dữ liệu từ client.
function chuanHoaBody(body) {
    const { loai_loi, ly_do, ghi_chu } = body || {};

    if (typeof loai_loi !== 'string' || !LOAI_LOI_HOP_LE.includes(loai_loi)) {
        throw taoLoi(400, 'INVALID_LOAI_LOI', 'loai_loi phải là CUSTOMER hoặc SHIPPER.');
    }

    if (typeof ly_do !== 'string' || !ly_do.trim()) {
        throw taoLoi(400, 'INVALID_LY_DO', 'ly_do không được để trống.');
    }
    const lyDo = ly_do.trim();
    if (lyDo.length > LY_DO_TOI_DA) {
        throw taoLoi(400, 'LY_DO_TOO_LONG', `ly_do không được vượt quá ${LY_DO_TOI_DA} ký tự.`);
    }

    let ghiChu = null;
    if (ghi_chu !== undefined && ghi_chu !== null) {
        if (typeof ghi_chu !== 'string') {
            throw taoLoi(400, 'INVALID_GHI_CHU', 'ghi_chu phải là chuỗi.');
        }
        ghiChu = ghi_chu.trim() || null;
        if (ghiChu && ghiChu.length > GHI_CHU_TOI_DA) {
            throw taoLoi(400, 'GHI_CHU_TOO_LONG', `ghi_chu không được vượt quá ${GHI_CHU_TOI_DA} ký tự.`);
        }
    }

    return { loaiLoi: loai_loi, lyDo, ghiChu };
}

// GET /orders/:id/failure-check
exports.kiemTraDieuKien = (req, res) =>
    chay(res, () =>
        failureService.kiemTraDieuKien(layMaNhanVien(req), parseMaHoaDon(req.params.id))
    );

// POST /orders/:id/report-failure
// Body: { "loai_loi": "CUSTOMER" | "SHIPPER", "ly_do": "...", "ghi_chu": "..." }
//  - SHIPPER  → hủy đơn ngay.
//  - CUSTOMER → chỉ ghi nhận chờ khách (đơn vẫn SHIPPING); hủy thật qua finalize-customer-failure.
exports.baoCaoThatBai = (req, res) =>
    chay(res, async () => {
        const maNhanVien = layMaNhanVien(req);
        const maHoaDon = parseMaHoaDon(req.params.id);
        const { loaiLoi, lyDo, ghiChu } = chuanHoaBody(req.body);

        if (loaiLoi === 'CUSTOMER') {
            return failureService.baoCuaKhachHang(maNhanVien, maHoaDon, lyDo, ghiChu);
        }
        return failureService.baoCuaShipper(maNhanVien, maHoaDon, lyDo, ghiChu);
    });

// POST /orders/:id/finalize-customer-failure
// Gọi khi hết 10 phút chờ lần hai. Backend tự kiểm tra mọi điều kiện, không nhận body.
exports.finalizeLoiKhachHang = (req, res) =>
    chay(res, () =>
        failureService.finalizeKhachKhongNhan(layMaNhanVien(req), parseMaHoaDon(req.params.id))
    );