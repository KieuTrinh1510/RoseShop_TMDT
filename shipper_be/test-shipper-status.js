// test-shipper-status.js
// Chạy: node test-shipper-status.js
// Yêu cầu: đã cấu hình DB (src/config/database.js) và có ít nhất 1 shipper trong bảng NhanVien.

require('dotenv').config();
const sequelize = require('./src/config/database');
const NhanVien = require('./src/models/NhanVien');
const svc = require('./src/services/shipperStatusService');

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────
let passCount = 0;
let failCount = 0;

function ok(label) {
    passCount++;
    console.log(`  ✅ PASS  ${label}`);
}

function fail(label, detail) {
    failCount++;
    console.log(`  ❌ FAIL  ${label}`);
    if (detail !== undefined) console.log(`           → ${JSON.stringify(detail)}`);
}

// So sánh nông (shallow) cho object đơn giản
function deepEqualShallow(a, b) {
    const ka = Object.keys(a).sort();
    const kb = Object.keys(b).sort();
    if (ka.length !== kb.length) return false;
    return ka.every(k => JSON.stringify(a[k]) === JSON.stringify(b[k]));
}

// Chạy 1 case bất đồng bộ, tự bắt lỗi
async function test(label, fn) {
    try {
        await fn();
        ok(label);
    } catch (e) {
        fail(label, { message: e.message, code: e.code, status: e.status });
    }
}

// Kỳ vọng hàm ném lỗi với status/code cụ thể
async function expectThrow(label, fn, { status, code }) {
    try {
        await fn();
        fail(label, { message: 'Không ném lỗi như mong đợi' });
    } catch (e) {
        if (e.status === status && e.code === code) {
            ok(label);
        } else {
            fail(label, { got: { status: e.status, code: e.code }, want: { status, code } });
        }
    }
}

function assert(cond, msg) {
    if (!cond) throw new Error(msg);
}

// ─────────────────────────────────────────────
// Chuẩn bị dữ liệu test
// ─────────────────────────────────────────────
let MA_SHIPPER = null;

async function setup() {
    console.log('\n🔧 Setup…');
    await sequelize.authenticate();

    // Lấy 1 nhân viên bất kỳ để làm shipper test
    const nv = await NhanVien.findOne({ order: [['ma_nhan_vien', 'ASC']] });
    if (!nv) {
        throw new Error('Bảng NhanVien rỗng. Hãy seed ít nhất 1 nhân viên trước khi test.');
    }
    MA_SHIPPER = nv.ma_nhan_vien;
    console.log(`   → Dùng shipper ma_nhan_vien = ${MA_SHIPPER} (${nv.ten_nhan_vien})`);

    await resetState();
}

// Đưa shipper về trạng thái mặc định trước mỗi nhóm test
async function resetState({ online = 'ONLINE', soDon = 0 } = {}) {
    await NhanVien.update(
        { trang_thai_hoat_dong: online, so_don_hien_tai: soDon },
        { where: { ma_nhan_vien: MA_SHIPPER } }
    );
}

// ─────────────────────────────────────────────
// 1. getStatus
// ─────────────────────────────────────────────
async function testGetStatus() {
    console.log('\n📦 [1] getStatus');

    await test('ONLINE + 3 đơn → co_the_nhan_don=true, is_full=false', async () => {
        await resetState({ online: 'ONLINE', soDon: 3 });
        const r = await svc.getStatus(MA_SHIPPER);
        assert(r.trang_thai_hoat_dong === 'ONLINE', 'sai trang_thai');
        assert(r.so_don_hien_tai === 3, 'sai so_don');
        assert(r.gioi_han_don === 10, 'sai gioi_han_don');
        assert(r.is_full === false, 'is_full phải false');
        assert(r.co_the_nhan_don === true, 'co_the_nhan_don phải true');
    });

    await test('ONLINE + 10 đơn → is_full=true, co_the_nhan_don=false', async () => {
        await resetState({ online: 'ONLINE', soDon: 10 });
        const r = await svc.getStatus(MA_SHIPPER);
        assert(r.is_full === true, 'is_full phải true');
        assert(r.co_the_nhan_don === false, 'co_the_nhan_don phải false');
    });

    await test('OFFLINE + 0 đơn → co_the_nhan_don=false', async () => {
        await resetState({ online: 'OFFLINE', soDon: 0 });
        const r = await svc.getStatus(MA_SHIPPER);
        assert(r.co_the_nhan_don === false, 'co_the_nhan_don phải false');
    });

    await expectThrow(
        'Shipper không tồn tại → 404 SHIPPER_NOT_FOUND',
        () => svc.getStatus(99999999),
        { status: 404, code: 'SHIPPER_NOT_FOUND' }
    );
}

// ─────────────────────────────────────────────
// 2. setOnlineStatus
// ─────────────────────────────────────────────
async function testSetOnlineStatus() {
    console.log('\n📦 [2] setOnlineStatus');

    await test('ONLINE → OFFLINE', async () => {
        await resetState({ online: 'ONLINE' });
        const r = await svc.setOnlineStatus(MA_SHIPPER, 'OFFLINE');
        assert(r.trang_thai_hoat_dong === 'OFFLINE', 'phải là OFFLINE');

        // Xác nhận đã ghi DB
        const db = await NhanVien.findByPk(MA_SHIPPER);
        assert(db.trang_thai_hoat_dong === 'OFFLINE', 'DB chưa cập nhật');
    });

    await test('OFFLINE → ONLINE', async () => {
        await resetState({ online: 'OFFLINE' });
        const r = await svc.setOnlineStatus(MA_SHIPPER, 'ONLINE');
        assert(r.trang_thai_hoat_dong === 'ONLINE', 'phải là ONLINE');
    });

    await test('Đổi trạng thái KHÔNG làm thay đổi số đơn', async () => {
        await resetState({ online: 'ONLINE', soDon: 5 });
        await svc.setOnlineStatus(MA_SHIPPER, 'OFFLINE');
        const r = await svc.getStatus(MA_SHIPPER);
        assert(r.so_don_hien_tai === 5, 'so_don phải giữ nguyên 5');
    });

    await expectThrow(
        'Trạng thái sai (BUSY) → 400 INVALID_STATUS',
        () => svc.setOnlineStatus(MA_SHIPPER, 'BUSY'),
        { status: 400, code: 'INVALID_STATUS' }
    );

    await expectThrow(
        'Trạng thái null → 400 INVALID_STATUS',
        () => svc.setOnlineStatus(MA_SHIPPER, null),
        { status: 400, code: 'INVALID_STATUS' }
    );

    await expectThrow(
        'Shipper không tồn tại → 404',
        () => svc.setOnlineStatus(99999999, 'ONLINE'),
        { status: 404, code: 'SHIPPER_NOT_FOUND' }
    );
}

// ─────────────────────────────────────────────
// 3. canReceiveNewOrder
// ─────────────────────────────────────────────
async function testCanReceiveNewOrder() {
    console.log('\n📦 [3] canReceiveNewOrder');

    const cases = [
        { online: 'ONLINE', soDon: 0, want: true, label: 'ONLINE, 0 đơn → true' },
        { online: 'ONLINE', soDon: 9, want: true, label: 'ONLINE, 9 đơn → true' },
        { online: 'ONLINE', soDon: 10, want: false, label: 'ONLINE, 10 đơn → false' },
        { online: 'OFFLINE', soDon: 0, want: false, label: 'OFFLINE, 0 đơn → false' },
        { online: 'OFFLINE', soDon: 5, want: false, label: 'OFFLINE, 5 đơn → false' },
    ];

    for (const c of cases) {
        await test(c.label, async () => {
            await resetState({ online: c.online, soDon: c.soDon });
            const r = await svc.canReceiveNewOrder(MA_SHIPPER);
            assert(r === c.want, `phải là ${c.want}, nhận ${r}`);
        });
    }

    await expectThrow(
        'Shipper không tồn tại → 404',
        () => svc.canReceiveNewOrder(99999999),
        { status: 404, code: 'SHIPPER_NOT_FOUND' }
    );
}

// ─────────────────────────────────────────────
// 4. incrementOrderCount
// ─────────────────────────────────────────────
async function testIncrementOrderCount() {
    console.log('\n📦 [4] incrementOrderCount');

    await test('ONLINE, 5 đơn → 6 đơn', async () => {
        await resetState({ online: 'ONLINE', soDon: 5 });
        const r = await svc.incrementOrderCount(MA_SHIPPER);
        assert(r.so_don_hien_tai === 6, `phải là 6, nhận ${r.so_don_hien_tai}`);
        const db = await NhanVien.findByPk(MA_SHIPPER);
        assert(db.so_don_hien_tai === 6, 'DB chưa cập nhật');
    });

    await test('ONLINE, 9 đơn → 10 đơn, sau đó is_full=true', async () => {
        await resetState({ online: 'ONLINE', soDon: 9 });
        const r = await svc.incrementOrderCount(MA_SHIPPER);
        assert(r.so_don_hien_tai === 10, 'phải là 10');
        assert(r.is_full === true, 'is_full phải true');
        assert(r.co_the_nhan_don === false, 'co_the_nhan_don phải false');
    });

    await expectThrow(
        'ONLINE, đã 10 đơn → 409 SHIPPER_FULL',
        async () => {
            await resetState({ online: 'ONLINE', soDon: 10 });
            return svc.incrementOrderCount(MA_SHIPPER);
        },
        { status: 409, code: 'SHIPPER_FULL' }
    );

    await expectThrow(
        'OFFLINE → 409 SHIPPER_OFFLINE',
        async () => {
            await resetState({ online: 'OFFLINE', soDon: 0 });
            return svc.incrementOrderCount(MA_SHIPPER);
        },
        { status: 409, code: 'SHIPPER_OFFLINE' }
    );

    await expectThrow(
        'Shipper không tồn tại → 404',
        () => svc.incrementOrderCount(99999999),
        { status: 404, code: 'SHIPPER_NOT_FOUND' }
    );

    await test('Race condition: chỉ 1 trong 2 request song song thành công khi còn 1 slot', async () => {
        await resetState({ online: 'ONLINE', soDon: 9 });
        const results = await Promise.allSettled([
            svc.incrementOrderCount(MA_SHIPPER),
            svc.incrementOrderCount(MA_SHIPPER),
        ]);
        const fulfilled = results.filter(r => r.status === 'fulfilled').length;
        const rejected = results.filter(r => r.status === 'rejected').length;
        assert(fulfilled === 1 && rejected === 1,
            `phải 1 thành công 1 thất bại, nhận ${fulfilled}/${rejected}`);

        const db = await NhanVien.findByPk(MA_SHIPPER);
        assert(db.so_don_hien_tai === 10,
            `DB phải là 10 (không vượt), nhận ${db.so_don_hien_tai}`);
    });
}

// ─────────────────────────────────────────────
// 5. decrementOrderCount
// ─────────────────────────────────────────────
async function testDecrementOrderCount() {
    console.log('\n📦 [5] decrementOrderCount');

    await test('ONLINE, 5 đơn → 4 đơn', async () => {
        await resetState({ online: 'ONLINE', soDon: 5 });
        const r = await svc.decrementOrderCount(MA_SHIPPER);
        assert(r.so_don_hien_tai === 4, `phải là 4, nhận ${r.so_don_hien_tai}`);
    });

    await test('Không cho số đơn âm: 0 đơn → vẫn 0', async () => {
        await resetState({ online: 'ONLINE', soDon: 0 });
        const r = await svc.decrementOrderCount(MA_SHIPPER);
        assert(r.so_don_hien_tai === 0, `phải là 0, nhận ${r.so_don_hien_tai}`);
        const db = await NhanVien.findByPk(MA_SHIPPER);
        assert(db.so_don_hien_tai === 0, 'DB không được âm');
    });

    await test('Giảm từ 10 → 9 thì co_the_nhan_don=true trở lại', async () => {
        await resetState({ online: 'ONLINE', soDon: 10 });
        const before = await svc.getStatus(MA_SHIPPER);
        assert(before.co_the_nhan_don === false, 'trước: phải false');

        const after = await svc.decrementOrderCount(MA_SHIPPER);
        assert(after.co_the_nhan_don === true, 'sau: phải true');
    });

    await test('Decrement KHÔNG đổi trạng thái ONLINE/OFFLINE', async () => {
        await resetState({ online: 'OFFLINE', soDon: 3 });
        const r = await svc.decrementOrderCount(MA_SHIPPER);
        assert(r.trang_thai_hoat_dong === 'OFFLINE',
            'phải giữ OFFLINE');
    });

    await expectThrow(
        'Shipper không tồn tại → 404',
        () => svc.decrementOrderCount(99999999),
        { status: 404, code: 'SHIPPER_NOT_FOUND' }
    );
}

// ─────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────
(async () => {
    console.log('═══════════════════════════════════════════');
    console.log('  TEST shipperStatusService.js');
    console.log('═══════════════════════════════════════════');

    try {
        await setup();

        await testGetStatus();
        await testSetOnlineStatus();
        await testCanReceiveNewOrder();
        await testIncrementOrderCount();
        await testDecrementOrderCount();

        console.log('\n═══════════════════════════════════════════');
        console.log(`  TỔNG KẾT:  ✅ ${passCount} PASS   ❌ ${failCount} FAIL`);
        console.log('═══════════════════════════════════════════\n');

        process.exitCode = failCount === 0 ? 0 : 1;
    } catch (err) {
        console.error('\n💥 LỖI SETUP / KHÔNG MONG ĐỢI:');
        console.error(err);
        process.exitCode = 2;
    } finally {
        await sequelize.close();
    }
})();