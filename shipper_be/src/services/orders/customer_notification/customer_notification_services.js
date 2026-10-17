// src/services/orders/customer_notification/customer_notification_services.js
// Thông báo gửi cho KHÁCH HÀNG khi đơn chuyển trạng thái.
// Tách riêng khỏi home/notification (thông báo cho shipper).

const ThongBao = require('../../../models/ThongBao');
const LOAI = require('../../shared/notification_types');

// ── Nội dung ──
function noiDungDangGiao(maHienThi, dsSanPham, laHoaTuoi = false) {
    const canhBao = laHoaTuoi
        ? ' Vì đơn hàng là hoa tươi, shop sẽ giao trong vòng 3 giờ tới.'
        : '';
    return `🚚 Đơn hàng ${maHienThi} gồm ${dsSanPham} đã được shipper nhận và đang trên đường giao đến bạn!${canhBao} Vui lòng chú ý điện thoại.`;
}

function noiDungDenNoi(maHienThi, dsSanPham, diaChi) {
    return `📍 Đơn hàng ${maHienThi} gồm: ${dsSanPham} đã đến địa điểm giao ${diaChi}. Bạn hãy xuống nhận hàng nhé! 🌸`;
}

function noiDungHoanThanh(maHienThi, dsSanPham) {
    return `✅ Đơn hàng ${maHienThi} gồm ${dsSanPham} đã được giao thành công. Cảm ơn quý khách đã sử dụng dịch vụ mua hàng tại shop của chúng tôi! 🌸`;
}

function noiDungXinLoiTre(maHienThi, diem) {
    return `😔 Xin lỗi vì sự bất tiện! Đơn hàng ${maHienThi} của bạn chưa được giao đúng hẹn. Chúng tôi đã cộng thêm ${diem} điểm tích lũy vào tài khoản của bạn để bù đắp. Cảm ơn bạn đã thông cảm! 💐`;
}

// Giai đoạn 1 lỗi khách: CHƯA hủy, chỉ nhắc khách liên hệ shipper.
function noiDungChoKhach(maHienThi) {
    return `📍 Shipper đã đến địa điểm giao đơn hàng ${maHienThi} nhưng chưa liên hệ được với bạn. Bạn vui lòng nhanh chóng liên hệ shipper để nhận hàng nhé! Đơn hàng hiện chưa bị hủy, nhưng nếu sau 10 phút nữa bạn vẫn chưa nhận hàng thì đơn sẽ bị hủy.`;
}

// Hủy do shipper: xin lỗi + hoàn tiền sau (chỉ nói hoàn tiền khi đơn đã thanh toán).
function noiDungGiaoThatBaiShipper(maHienThi, { daThanhToan = false } = {}) {
    const hoanTien = daThanhToan
        ? ' Vì đơn hàng đã được thanh toán, hệ thống sẽ xử lý hoàn tiền cho bạn sau.'
        : '';
    return `😔 Xin lỗi quý khách! Đơn hàng ${maHienThi} đã bị hủy do sự cố phía shipper trong quá trình giao hàng, và được chuyển sang quy trình hoàn hàng.${hoanTien}`;
}

// Hủy cuối cùng do khách không nhận hàng sau 2 lần chờ.
function noiDungHuyDoKhach(maHienThi, { daThanhToan = false, lanViPham = 1 } = {}) {
    const hanChe = lanViPham >= 2
        ? ` Đây là lần vi phạm thứ ${lanViPham} của bạn, tài khoản đã bị hạn chế mua hàng COD.`
        : ' Nếu đủ 2 lần vi phạm, tài khoản của bạn sẽ bị hạn chế mua hàng COD.';
    // Đơn đã thanh toán trước: KHÔNG tích vi phạm, chỉ báo hủy + hoàn tiền.
    if (daThanhToan) {
        return `⚠️ Đơn hàng ${maHienThi} đã bị hủy vì bạn không nhận hàng. Vì đơn đã được thanh toán trước nên tài khoản của bạn không bị ghi nhận vi phạm, hệ thống sẽ xử lý hoàn tiền cho bạn sau.`;
    }
    // Đơn COD: ghi nhận vi phạm + hướng dẫn thanh toán để gỡ.
    return `⚠️ Đơn hàng ${maHienThi} đã bị hủy vì bạn không nhận hàng. Tài khoản của bạn bị ghi nhận vi phạm (${lanViPham}/2).${hanChe} Bạn có thể thanh toán lại giá trị đơn hàng tại mục "Vi phạm & Thanh toán" để được gỡ vi phạm.`;
}

// Giữ tên cũ để tương thích: nguonLoi = 'SHIPPER' | 'CUSTOMER'
function noiDungGiaoThatBai(maHienThi, nguonLoi, thongTin = {}) {
    return nguonLoi === 'CUSTOMER'
        ? noiDungHuyDoKhach(maHienThi, thongTin)
        : noiDungGiaoThatBaiShipper(maHienThi, thongTin);
}

// ── Ghi ──
// Truyền options.transaction để ghi chung transaction với update đơn
async function tao(maKhachHang, loai, noiDung, maHoaDon, options = {}) {
    return ThongBao.create(
        {
            ma_nguoi_dung: maKhachHang,
            loai,
            noi_dung: noiDung,
            ma_hoa_don: maHoaDon,
            da_doc: false,
            thoi_gian: new Date(),
        },
        options
    );
}

async function taoDangGiao(maKhachHang, maHoaDon, maHienThi, dsSanPham, laHoaTuoi) {
    return tao(
        maKhachHang,
        LOAI.SHIPPING,
        noiDungDangGiao(maHienThi, dsSanPham, laHoaTuoi),
        maHoaDon
    );
}

async function taoDenNoi(maKhachHang, maHoaDon, maHienThi, dsSanPham, diaChi, options = {}) {
    return tao(
        maKhachHang,
        LOAI.ARRIVED,
        noiDungDenNoi(maHienThi, dsSanPham, diaChi),
        maHoaDon,
        options
    );
}

async function taoHoanThanh(maKhachHang, maHoaDon, maHienThi, dsSanPham) {
    return tao(
        maKhachHang,
        LOAI.DELIVERED,
        noiDungHoanThanh(maHienThi, dsSanPham),
        maHoaDon
    );
}

async function taoXinLoiTre(maKhachHang, maHoaDon, maHienThi, diem, options = {}) {
    return tao(
        maKhachHang,
        LOAI.APOLOGY,
        noiDungXinLoiTre(maHienThi, diem),
        maHoaDon,
        options
    );
}

// Đang chờ khách (chưa hủy). Dùng loại ARRIVED để không cần đổi enum/ghi chú loại thông báo ở DB.
async function taoChoKhach(maKhachHang, maHoaDon, maHienThi, options = {}) {
    return tao(
        maKhachHang,
        LOAI.ARRIVED,
        noiDungChoKhach(maHienThi),
        maHoaDon,
        options
    );
}

// Hủy cuối cùng do khách không nhận hàng
async function taoHuyDoKhach(maKhachHang, maHoaDon, maHienThi, thongTin = {}, options = {}) {
    return tao(
        maKhachHang,
        LOAI.FAILURE,
        noiDungHuyDoKhach(maHienThi, thongTin),
        maHoaDon,
        options
    );
}

// Hủy do shipper (nguonLoi = 'SHIPPER'); nguonLoi = 'CUSTOMER' → nội dung hủy do khách.
// thongTin: { daThanhToan, lanViPham }
async function taoGiaoThatBai(maKhachHang, maHoaDon, maHienThi, nguonLoi, thongTin = {}, options = {}) {
    return tao(
        maKhachHang,
        LOAI.FAILURE,
        noiDungGiaoThatBai(maHienThi, nguonLoi, thongTin),
        maHoaDon,
        options
    );
}

module.exports = {
    noiDungDangGiao,
    noiDungDenNoi,
    noiDungHoanThanh,
    noiDungXinLoiTre,
    noiDungChoKhach,
    noiDungGiaoThatBaiShipper,
    noiDungHuyDoKhach,
    noiDungGiaoThatBai,
    tao,
    taoDangGiao,
    taoDenNoi,
    taoHoanThanh,
    taoXinLoiTre,
    taoChoKhach,
    taoHuyDoKhach,
    taoGiaoThatBai,
};