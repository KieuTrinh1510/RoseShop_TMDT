// src/test-shipper-status.js
// Test nhanh backend shipper: status, home, đơn hàng, nhận hàng, báo đến nơi, giao xong (COD).
// Không cần chạy server trước: script tự mở app trên cổng ngẫu nhiên.
//
// Chạy:   SHIPPER_ID=5 KHACH_ID=2 node test-shipper-status.js
//   SHIPPER_ID : mã shipper, PHẢI trùng với user mà fakeAuthMiddleware gắn vào req.user
//   KHACH_ID   : mã khách hàng bất kỳ đang có trong DB (để gắn vào đơn test)
//
// Script tạo 1 đơn test rồi xoá đi khi chạy xong. Chạy trên DB DEV, đừng chạy trên DB thật.

require('dotenv').config();

const app = require('./app');
const sequelize = require('./config/database');
const HoaDon = require('./models/HoaDon');
const NhanVien = require('./models/NhanVien');
const ThongBao = require('./models/ThongBao');
const statusService = require('./services/shipperStatusService');

const SHIPPER_ID = Number(process.env.SHIPPER_ID);
const KHACH_ID = Number(process.env.KHACH_ID);
// fakeAuthMiddleware đọc mã shipper từ header này
const HEADERS = { 'x-ma-nhan-vien': String(SHIPPER_ID) };

let base = '';
let dat = 0;
let hong = 0;

function check(ten, dieuKien, them = '') {
    if (dieuKien) {
        dat++;
        console.log(`  ✅ ${ten}`);
    } else {
        hong++;
        console.log(`  ❌ ${ten} ${them}`);
    }
}

async function call(method, path, body) {
    const res = await fetch(base + '/api/shipper' + path, {
        method,
        headers: { 'Content-Type': 'application/json', ...HEADERS },
        body: body ? JSON.stringify(body) : undefined,
    });
    const json = await res.json().catch(() => ({}));
    return { status: res.status, ...json };
}

function buoc(ten) {
    console.log(`\n▶ ${ten}`);
}

async function chayTest() {
    let maDon = null;
    let truoc = null; // số đơn của shipper trước khi test, để khôi phục khi dọn dẹp

    try {
        // ── 1. Status ──
        buoc('Trạng thái ONLINE/OFFLINE');
        let r = await call('PUT', '/status/online', { trang_thai: 'OFFLINE' });
        check('Đặt OFFLINE', r.status === 200 && r.data?.trang_thai_hoat_dong === 'OFFLINE', JSON.stringify(r));
        check('OFFLINE => co_the_nhan_don = false', r.data?.co_the_nhan_don === false);

        r = await call('PUT', '/status/online', { trang_thai: 'ABC' });
        check('Giá trị sai bị từ chối (400)', r.status === 400 && r.code === 'INVALID_STATUS');

        r = await call('PUT', '/status/online', { trang_thai: 'ONLINE' });
        check('Đặt ONLINE', r.status === 200 && r.data?.trang_thai_hoat_dong === 'ONLINE');

        // ── 2. Home ──
        buoc('Home');
        r = await call('GET', '/home');
        check('GET /home trả 200', r.status === 200 && r.success === true, JSON.stringify(r));
        check('Có đủ nhóm dữ liệu', !!(r.data?.shipper && r.data?.trang_thai && r.data?.don_hang && r.data?.cod && r.data?.thong_bao));
        check('gioi_han_don = 10', r.data?.don_hang?.gioi_han_don === 10);

        // ── 3. Tạo đơn test (CONFIRMED, COD, 100.000đ) và gán cho shipper ──
        buoc('Chuẩn bị đơn test');
        truoc = (await NhanVien.findById(SHIPPER_ID)).so_don_hien_tai;

        const don = await HoaDon.create({
            ma_khach_hang: KHACH_ID,
            ma_nhan_vien_giao: SHIPPER_ID,
            ngay_dat: new Date(),
            tong_tien: 100000,
            trang_thai: 'CONFIRMED',
            trang_thai_thanh_toan: 'CHUA_THANH_TOAN',
            phuong_thuc_thanh_toan: 'COD',
            dia_chi_giao: '123 Đường Test',
            so_dien_thoai: '0900000000',
        });
        maDon = don.ma_hoa_don;
        await statusService.incrementOrderCount(SHIPPER_ID); // giống bước admin gán đơn
        const saugan = (await NhanVien.findById(SHIPPER_ID)).so_don_hien_tai;
        check(`Tạo đơn #${maDon}, số đơn ${truoc} -> ${saugan}`, saugan === truoc + 1);

        // ── 4. Xem đơn ──
        buoc('Xem đơn');
        r = await call('GET', '/orders');
        check('Đơn test có trong danh sách', r.data?.items?.some((d) => d.ma_hoa_don === maDon), JSON.stringify(r).slice(0, 200));

        r = await call('GET', `/orders/${maDon}`);
        check('Chi tiết đơn trả 200', r.status === 200, JSON.stringify(r).slice(0, 300));
        check('Là COD, cần thu 100.000', r.data?.thanh_toan?.can_thu_tien_mat === true && r.data?.thanh_toan?.so_tien_can_thu === 100000);
        check('Đang CONFIRMED => chỉ được "nhận hàng"', r.data?.hanh_dong?.co_the_nhan_hang === true && r.data?.hanh_dong?.co_the_giao_xong === false);

        r = await call('GET', '/orders/abc');
        check('id không phải số => 400', r.status === 400);
        r = await call('GET', '/orders/999999999');
        check('Đơn không tồn tại/không thuộc mình => 404', r.status === 404);

        // ── 5. Sai thứ tự thao tác ──
        buoc('Thao tác sai thứ tự (phải bị chặn)');
        r = await call('POST', `/orders/${maDon}/arrived`);
        check('Báo đến nơi khi chưa nhận hàng => 422', r.status === 422);
        r = await call('POST', `/orders/${maDon}/delivered`);
        check('Giao xong khi chưa nhận hàng => 422', r.status === 422);

        // ── 6. Nhận hàng ──
        buoc('Nhận hàng (CONFIRMED -> SHIPPING)');
        r = await call('POST', `/orders/${maDon}/pickup`);
        check('Pickup thành công', r.status === 200 && r.data?.trang_thai === 'SHIPPING', JSON.stringify(r));
        let db = await HoaDon.findById(maDon);
        check('DB: trang_thai = SHIPPING', db.trang_thai === 'SHIPPING');
        check('DB: ngay_bat_giao đã được ghi', !!db.ngay_bat_giao);
        r = await call('POST', `/orders/${maDon}/pickup`);
        check('Nhận lần 2 => 422', r.status === 422);
        check('Có 1 thông báo "shipping" cho khách', (await ThongBao.count({ where: { ma_hoa_don: maDon, loai: 'shipping', ma_nguoi_dung: KHACH_ID } })) === 1);

        r = await call('GET', `/orders/${maDon}/timeout`);
        check('Đồng hồ 3h đang chạy', r.data?.co_moc === true && r.data?.remaining > 10700 && r.data?.is_timeout === false, JSON.stringify(r.data));

        // ── 7. Đã đến điểm giao ──
        buoc('Đã đến điểm giao');
        r = await call('POST', `/orders/${maDon}/arrived`);
        check('Báo đến nơi thành công', r.status === 200 && r.data?.da_gui === true, JSON.stringify(r));
        check('Có 1 thông báo "arrived" cho khách', (await ThongBao.count({ where: { ma_hoa_don: maDon, loai: 'arrived', ma_nguoi_dung: KHACH_ID } })) === 1);
        r = await call('POST', `/orders/${maDon}/arrived`);
        check('Báo lần 2 => 409', r.status === 409 && r.code === 'ALREADY_NOTIFIED');

        // ── 8. Giao thành công ──
        buoc('Giao thành công (SHIPPING -> DELIVERED)');
        r = await call('POST', `/orders/${maDon}/delivered`);
        check('Delivered thành công', r.status === 200 && r.data?.trang_thai === 'DELIVERED', JSON.stringify(r));
        check('Trả về tien_cod_vua_thu = 100.000', r.data?.thanh_toan?.tien_cod_vua_thu === 100000);
        check('Có tổng COD cần nộp', typeof r.data?.cod?.tien_can_nop === 'number' && r.data.cod.tien_can_nop >= 100000);

        db = await HoaDon.findById(maDon);
        check('DB: DELIVERED + DA_THANH_TOAN + có ngay_giao', db.trang_thai === 'DELIVERED' && db.trang_thai_thanh_toan === 'DA_THANH_TOAN' && !!db.ngay_giao);
        const sau = (await NhanVien.findById(SHIPPER_ID)).so_don_hien_tai;
        check(`Số đơn trở về ${truoc}`, sau === truoc, `(đang là ${sau})`);

        r = await call('POST', `/orders/${maDon}/delivered`);
        check('Bấm giao lần 2 => 422', r.status === 422);
        const sau2 = (await NhanVien.findById(SHIPPER_ID)).so_don_hien_tai;
        check('Bấm 2 lần KHÔNG trừ số đơn thêm', sau2 === truoc, `(đang là ${sau2})`);

        // ── 9. Lịch sử ──
        buoc('Lịch sử');
        r = await call('GET', '/history');
        check('Đơn test nằm trong lịch sử', r.data?.items?.some((d) => d.ma_hoa_don === maDon));
    } catch (err) {
        hong++;
        console.log('\n💥 Lỗi không mong đợi:', err);
    } finally {
        // Dọn dữ liệu test
        if (maDon) {
            await ThongBao.destroy({ where: { ma_hoa_don: maDon } });
            await HoaDon.destroy({ where: { ma_hoa_don: maDon } });
            console.log(`\n🧹 Đã xoá đơn test #${maDon} và thông báo liên quan`);
        }
        if (truoc !== null) {
            await NhanVien.update({ so_don_hien_tai: truoc }, { where: { ma_nhan_vien: SHIPPER_ID } });
            console.log(`🧹 Đã khôi phục so_don_hien_tai của shipper ${SHIPPER_ID} về ${truoc}`);
        }
    }
}

(async () => {
    if (!SHIPPER_ID || !KHACH_ID) {
        console.error('Thiếu biến môi trường. Ví dụ: SHIPPER_ID=5 KHACH_ID=2 node test-shipper-status.js');
        process.exit(1);
    }

    const server = app.listen(0);
    base = `http://localhost:${server.address().port}`;

    await chayTest();

    server.close();
    await sequelize.close();

    console.log(`\n══════════════════════════════\nĐạt: ${dat}   Hỏng: ${hong}`);
    process.exit(hong === 0 ? 0 : 1);
})();