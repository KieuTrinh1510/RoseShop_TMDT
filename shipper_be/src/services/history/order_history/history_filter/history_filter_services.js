// src/services/history/order_history/history_filter/history_filter_services.js
// Build điều kiện WHERE cho Sequelize từ query của app.
// Không query DB — chỉ validate input và trả object where.

const { Op } = require('sequelize');
const { taoLoi } = require('../../../../utils/loi');

const REGEX_MA_DON = /^#?\d+$/;
const MOT_NGAY_MS = 24 * 60 * 60 * 1000;

// Mốc ngày tính theo giờ Việt Nam (UTC+7) — khớp với giờ app hiển thị,
// không phụ thuộc múi giờ của máy chạy server.
const VN_OFFSET_MS = 7 * 60 * 60 * 1000;

// 00:00 (giờ VN) của ngày y-mo-d  -> Date (instant)
function dauNgayVN(y, mo, d) {
    return new Date(Date.UTC(y, mo - 1, d) - VN_OFFSET_MS);
}

// YYYY-MM-DD -> Date (00:00 giờ VN)
function parseNgay(str, tenTruong) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(str));
    if (!m) {
        throw taoLoi(400, 'INVALID_DATE', `${tenTruong} phải có định dạng YYYY-MM-DD.`);
    }
    const y = Number(m[1]);
    const mo = Number(m[2]);
    const d = Number(m[3]);
    const chk = new Date(Date.UTC(y, mo - 1, d));
    if (chk.getUTCFullYear() !== y || chk.getUTCMonth() !== mo - 1 || chk.getUTCDate() !== d) {
        throw taoLoi(400, 'INVALID_DATE', `${tenTruong} không phải ngày hợp lệ.`);
    }
    return dauNgayVN(y, mo, d);
}

// YYYY-MM -> { batDau, ketThuc } (ketThuc exclusive)
function parseThang(str) {
    const m = /^(\d{4})-(\d{2})$/.exec(String(str));
    if (!m) {
        throw taoLoi(400, 'INVALID_MONTH', 'thang phải có định dạng YYYY-MM.');
    }
    const y = Number(m[1]);
    const mo = Number(m[2]);
    if (mo < 1 || mo > 12) {
        throw taoLoi(400, 'INVALID_MONTH', 'Tháng phải từ 01 đến 12.');
    }
    return {
        batDau: dauNgayVN(y, mo, 1),
        ketThuc: dauNgayVN(y, mo + 1, 1), // mo = 12 -> Date.UTC tự sang tháng 1 năm sau
    };
}

// "90" | "#90" | "#0088" -> 90 | 90 | 88 (phải > 0)
function parseMaDon(str) {
    const s = String(str).trim();
    if (!REGEX_MA_DON.test(s)) {
        throw taoLoi(400, 'INVALID_ORDER_CODE', 'Mã đơn phải là số nguyên dương (ví dụ: 90 hoặc #90).');
    }
    const so = Number(s.replace('#', ''));
    if (!Number.isInteger(so) || so <= 0) {
        throw taoLoi(400, 'INVALID_ORDER_CODE', 'Mã đơn phải là số nguyên dương (ví dụ: 90 hoặc #90).');
    }
    return so;
}

// Trả object where dùng trực tiếp cho HoaDon.findAndCountAll
function buildFilter({ ngay, tu_ngay, den_ngay, thang, ma } = {}) {
    const where = {};
    const dieuKienNgay = {};
    // ⚠️ KHÔNG dùng Object.keys(dieuKienNgay).length để kiểm tra:
    // Op.gte / Op.lt là Symbol nên Object.keys luôn trả [] -> filter ngày bị bỏ qua.
    let coLocNgay = false;

    if (ngay) {
        const batDau = parseNgay(ngay, 'ngay');
        dieuKienNgay[Op.gte] = batDau;
        dieuKienNgay[Op.lt] = new Date(batDau.getTime() + MOT_NGAY_MS);
        coLocNgay = true;
    }

    // Khoảng ngày: tu_ngay <= den_ngay (den_ngay inclusive)
    let tu = null;
    let den = null;
    if (tu_ngay) tu = parseNgay(tu_ngay, 'tu_ngay');
    if (den_ngay) den = parseNgay(den_ngay, 'den_ngay');
    if (tu && den && tu.getTime() > den.getTime()) {
        throw taoLoi(400, 'INVALID_RANGE', 'Ngày bắt đầu không được sau ngày kết thúc.');
    }
    if (tu) {
        dieuKienNgay[Op.gte] = tu;
        coLocNgay = true;
    }
    if (den) {
        dieuKienNgay[Op.lt] = new Date(den.getTime() + MOT_NGAY_MS);
        coLocNgay = true;
    }

    if (thang) {
        const { batDau, ketThuc } = parseThang(thang);
        dieuKienNgay[Op.gte] = batDau;
        dieuKienNgay[Op.lt] = ketThuc;
        coLocNgay = true;
    }

    if (coLocNgay) {
        where.ngay_giao = dieuKienNgay;
    }

    if (ma !== undefined && ma !== null && ma !== '') {
        where.ma_hoa_don = parseMaDon(ma);
    }

    return where;
}

module.exports = {
    REGEX_MA_DON,
    parseNgay,
    parseThang,
    parseMaDon,
    buildFilter,
};