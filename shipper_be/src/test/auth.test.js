'use strict';

process.env.JWT_SECRET = 'test_secret_test_secret_123456';
process.env.JWT_EXPIRES_IN = '7d';

const test = require('node:test');
const assert = require('node:assert');
const path = require('path');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const express = require('express');

const HASH = bcrypt.hashSync('pass123', 10);

const users = {
    shipper9: {
        ma_nguoi_dung: 9,
        ten_dang_nhap: 'shipper9',
        mat_khau: HASH,
        vai_tro: 'SHIPPER',
        trang_thai: 'HOAT_DONG',
    },
    shipper10: {
        ma_nguoi_dung: 10,
        ten_dang_nhap: 'shipper10',
        mat_khau: HASH,
        vai_tro: 'SHIPPER',
        trang_thai: 'HOAT_DONG',
    },
    admin: {
        ma_nguoi_dung: 1,
        ten_dang_nhap: 'admin',
        mat_khau: HASH,
        vai_tro: 'ADMIN',
        trang_thai: 'HOAT_DONG',
    },
    nv: {
        ma_nguoi_dung: 2,
        ten_dang_nhap: 'nv',
        mat_khau: HASH,
        vai_tro: 'NHAN_VIEN',
        trang_thai: 'HOAT_DONG',
    },
    locked: {
        ma_nguoi_dung: 11,
        ten_dang_nhap: 'locked',
        mat_khau: HASH,
        vai_tro: 'SHIPPER',
        trang_thai: 'BI_KHOA',
    },
    pending: {
        ma_nguoi_dung: 12,
        ten_dang_nhap: 'pending',
        mat_khau: HASH,
        vai_tro: 'SHIPPER',
        trang_thai: 'CHO_XAC_THUC',
    },
    nohr: {
        ma_nguoi_dung: 13,
        ten_dang_nhap: 'nohr',
        mat_khau: HASH,
        vai_tro: 'SHIPPER',
        trang_thai: 'HOAT_DONG',
    },
    plain: {
        ma_nguoi_dung: 14,
        ten_dang_nhap: 'plain',
        mat_khau: '1234',
        vai_tro: 'SHIPPER',
        trang_thai: 'HOAT_DONG',
    },
};

for (const user of Object.values(users)) {
    user.updatePassword = async function (hash) {
        this.mat_khau = hash;
        return true;
    };
}

const nhanVien = {
    9: {
        ma_nhan_vien: 9,
        ten_nhan_vien: 'Shipper Chín',
        email: 'a@x.vn',
        so_dien_thoai: '0900000009',
    },
    10: {
        ma_nhan_vien: 10,
        ten_nhan_vien: 'Shipper Mười',
        email: 'b@x.vn',
        so_dien_thoai: '0900000010',
    },
    11: {
        ma_nhan_vien: 11,
        ten_nhan_vien: 'K',
    },
    12: {
        ma_nhan_vien: 12,
        ten_nhan_vien: 'P',
    },
    14: {
        ma_nhan_vien: 14,
        ten_nhan_vien: 'Plain',
    },
};

function stub(relativePath, exports) {
    const filePath = require.resolve(
        path.join(__dirname, '..', relativePath)
    );

    require.cache[filePath] = {
        id: filePath,
        filename: filePath,
        loaded: true,
        exports,
    };
}

stub('models/NguoiDung.js', {
    findByUsername: async (username) => users[username] || null,
});

stub('models/NhanVien.js', {
    findByPk: async (id) => nhanVien[id] || null,
});

const authRoutes = require('../routes/shipperAuthRoutes');
const authMiddleware = require('../middleware/authMiddleware');
const { layMaNhanVien } = require('../controllers/shipper/_helpers');

const app = express();

app.use(express.json());

app.use('/api/shipper/auth', authRoutes);

const router = express.Router();

router.get('/home', (req, res) => {
    res.json({
        success: true,
        data: {
            ma: layMaNhanVien(req),
        },
    });
});

app.use('/api/shipper', authMiddleware, router);

app.use((err, req, res, next) => {
    res.status(err.status || 500).json({
        success: false,
        code: err.code || 'SERVER_ERROR',
        message: err.message,
    });
});

let server;
let baseUrl;

test.before(async () => {
    await new Promise((resolve) => {
        server = app.listen(0, resolve);
    });

    baseUrl = `http://127.0.0.1:${server.address().port}/api/shipper`;
});

test.after(() => {
    server.close();
});

async function login(username, password) {
    const response = await fetch(`${baseUrl}/auth/login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ten_dang_nhap: username,
            mat_khau: password,
        }),
    });

    return {
        status: response.status,
        body: await response.json(),
    };
}

async function home(headers = {}) {
    const response = await fetch(`${baseUrl}/home`, {
        headers,
    });

    return {
        status: response.status,
        body: await response.json(),
    };
}

function bearer(token) {
    return {
        Authorization: `Bearer ${token}`,
    };
}

test('Đăng nhập đúng SHIPPER và gọi home', async () => {
    const result = await login('shipper9', 'pass123');

    assert.equal(result.status, 200);
    assert.equal(result.body.success, true);
    assert.equal(result.body.shipper.ma_nhan_vien, 9);

    assert.ok(!JSON.stringify(result.body).includes('mat_khau'));
    assert.ok(!JSON.stringify(result.body).includes(HASH));

    const payload = jwt.decode(result.body.token);

    assert.deepEqual(
        [
            payload.ma_nguoi_dung,
            payload.ma_nhan_vien,
            payload.vai_tro,
        ],
        [9, 9, 'SHIPPER']
    );

    assert.equal(
        payload.exp - payload.iat,
        7 * 24 * 60 * 60
    );

    const homeResult = await home(bearer(result.body.token));

    assert.equal(homeResult.status, 200);
    assert.equal(homeResult.body.data.ma, 9);
});

test('Sai username', async () => {
    const result = await login('khongco', 'pass123');

    assert.equal(result.status, 401);
    assert.equal(result.body.code, 'INVALID_CREDENTIALS');
});

test('Sai password', async () => {
    const result = await login('shipper9', 'sai');

    assert.equal(result.status, 401);
    assert.equal(result.body.code, 'INVALID_CREDENTIALS');
});

test('ADMIN bị từ chối', async () => {
    const result = await login('admin', 'pass123');

    assert.equal(result.status, 403);
    assert.equal(result.body.code, 'FORBIDDEN_ROLE');
});

test('NHAN_VIEN bị từ chối', async () => {
    const result = await login('nv', 'pass123');

    assert.equal(result.status, 403);
    assert.equal(result.body.code, 'FORBIDDEN_ROLE');
});

test('Tài khoản bị khóa', async () => {
    const result = await login('locked', 'pass123');

    assert.equal(result.status, 403);
    assert.equal(result.body.code, 'ACCOUNT_LOCKED');
});

test('Tài khoản chưa xác thực', async () => {
    const result = await login('pending', 'pass123');

    assert.equal(result.status, 403);
    assert.equal(result.body.code, 'ACCOUNT_UNVERIFIED');
});

test('Không có hồ sơ nhân viên', async () => {
    const result = await login('nohr', 'pass123');

    assert.equal(result.status, 404);
    assert.equal(result.body.code, 'SHIPPER_PROFILE_NOT_FOUND');
});

test('Thiếu body', async () => {
    const result = await login(undefined, undefined);

    assert.equal(result.status, 400);
});

test('Không có Authorization', async () => {
    const result = await home();

    assert.equal(result.status, 401);
    assert.equal(result.body.code, 'TOKEN_MISSING');
});

test('Token sai', async () => {
    const invalid = await home(bearer('abc.def.ghi'));

    assert.equal(invalid.status, 401);
    assert.equal(invalid.body.code, 'TOKEN_INVALID');

    const forgedToken = jwt.sign(
        {
            ma_nguoi_dung: 9,
            ma_nhan_vien: 9,
            vai_tro: 'SHIPPER',
        },
        'secret-khac-xxxxxxxxxxx'
    );

    const forged = await home(bearer(forgedToken));

    assert.equal(forged.status, 401);
});

test('Token hết hạn', async () => {
    const token = jwt.sign(
        {
            ma_nguoi_dung: 9,
            ma_nhan_vien: 9,
            vai_tro: 'SHIPPER',
        },
        process.env.JWT_SECRET,
        {
            expiresIn: -10,
        }
    );

    const result = await home(bearer(token));

    assert.equal(result.status, 401);
    assert.equal(result.body.code, 'TOKEN_EXPIRED');
});

test('Token ADMIN bị từ chối', async () => {
    const token = jwt.sign(
        {
            ma_nguoi_dung: 1,
            ma_nhan_vien: 1,
            vai_tro: 'ADMIN',
        },
        process.env.JWT_SECRET
    );

    const result = await home(bearer(token));

    assert.equal(result.status, 403);
});

test('Không thể giả mạo shipper bằng header cũ', async () => {
    const loginA = await login('shipper9', 'pass123');

    const result = await home({
        ...bearer(loginA.body.token),
        'x-ma-nhan-vien': '10',
    });

    assert.equal(result.status, 200);
    assert.equal(result.body.data.ma, 9);
});

test('Chỉ có header cũ thì không được vào API', async () => {
    const result = await home({
        'x-ma-nhan-vien': '9',
    });

    assert.equal(result.status, 401);
});

test('Mật khẩu plaintext cũ được nâng cấp lên bcrypt', async () => {
    const result = await login('plain', '1234');

    assert.equal(result.status, 200);
    assert.match(users.plain.mat_khau, /^\$2[aby]\$/);

    const wrongPassword = await login('plain', '12345');

    assert.equal(wrongPassword.status, 401);
});

test('bcrypt $2y$ của Laravel được bcryptjs chấp nhận', async () => {
    const laravelHash = HASH.replace(/^\$2[ab]\$/, '$2y$');

    assert.equal(
        await bcrypt.compare('pass123', laravelHash),
        true
    );
});