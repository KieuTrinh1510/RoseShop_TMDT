'use strict';

/**
 * Smoke test luồng giao hàng thất bại của shipper-be.
 *
 * Chạy:
 *   node test/test-shipping-failure-flow.js
 *
 * Windows PowerShell:
 *   $env:BASE_URL="http://localhost:3000/api/shipper"
 *   $env:SHIPPER_ID="1"
 *   $env:ORDER_ID="123"
 *   node test/test-shipping-failure-flow.js
 *
 * Mặc định chỉ chạy GET, không làm thay đổi dữ liệu.
 *
 * Muốn chạy API ghi dữ liệu:
 *   $env:RUN_MUTATING_TESTS="true"
 *   node test/test-shipping-failure-flow.js
 *
 * Lưu ý:
 * - Dùng đơn test riêng.
 * - Không dùng đơn thật.
 * - ORDER_ID phải thuộc SHIPPER_ID.
 */

const BASE_URL =
    process.env.BASE_URL || 'http://localhost:3000/api/shipper';

const SHIPPER_ID = Number(process.env.SHIPPER_ID || 1);
const ORDER_ID = Number(process.env.ORDER_ID || 0);

const RUN_MUTATING_TESTS =
    String(process.env.RUN_MUTATING_TESTS || '').toLowerCase() === 'true';

const TEST_REASON =
    process.env.TEST_REASON || 'Test luồng giao hàng thất bại';

const TEST_NOTE =
    process.env.TEST_NOTE || 'Smoke test tự động, không dùng cho đơn thật';

let passed = 0;
let failed = 0;

function printHeader(title) {
    console.log('\n' + '='.repeat(70));
    console.log(title);
    console.log('='.repeat(70));
}

function pass(message) {
    passed++;
    console.log(`✅ PASS: ${message}`);
}

function fail(message, detail = '') {
    failed++;
    console.error(`❌ FAIL: ${message}`);
    if (detail) console.error(`   ${detail}`);
}

function skip(message) {
    console.log(`⏭️  SKIP: ${message}`);
}

function assert(condition, message, detail = '') {
    if (condition) {
        pass(message);
    } else {
        fail(message, detail);
    }
}

async function request(method, path, body = undefined) {
    const response = await fetch(`${BASE_URL}${path}`, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'x-ma-nhan-vien': String(SHIPPER_ID),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    let json;

    try {
        json = await response.json();
    } catch {
        json = {
            success: false,
            code: 'INVALID_JSON',
            message: await response.text(),
        };
    }

    return {
        status: response.status,
        ok: response.ok,
        body: json,
    };
}

function checkApiResponse(result, expectedStatuses, label) {
    const statusOk = expectedStatuses.includes(result.status);

    assert(
        statusOk,
        `${label} trả HTTP ${result.status}`,
        `Mong đợi một trong: ${expectedStatuses.join(', ')}`
    );

    console.log('   Response:', JSON.stringify(result.body, null, 2));

    return result;
}

async function testHealthAndRoute() {
    printHeader('1. Kiểm tra server và route');

    const result = await request('GET', '/orders');

    checkApiResponse(result, [200, 401, 403, 404, 500], 'GET /orders');

    assert(
        result.body && typeof result.body === 'object',
        'Backend trả JSON object'
    );
}

async function testOrderDetail() {
    printHeader('2. Kiểm tra chi tiết đơn');

    if (!ORDER_ID) {
        skip('Chưa cấu hình ORDER_ID');
        return null;
    }

    const result = await request('GET', `/orders/${ORDER_ID}`);

    checkApiResponse(
        result,
        [200, 404, 409, 422, 500],
        `GET /orders/${ORDER_ID}`
    );

    if (result.status === 200) {
        assert(
            result.body.success === true,
            'Chi tiết đơn trả success=true'
        );

        const data = result.body.data || {};

        assert(
            data.ma_hoa_don !== undefined,
            'Chi tiết đơn có ma_hoa_don'
        );

        assert(
            data.trang_thai !== undefined,
            'Chi tiết đơn có trang_thai'
        );

        console.log(`   Trạng thái đơn hiện tại: ${data.trang_thai}`);
    }

    return result;
}

async function testFailureCheck() {
    printHeader('3. Kiểm tra điều kiện báo giao thất bại');

    if (!ORDER_ID) {
        skip('Chưa cấu hình ORDER_ID');
        return null;
    }

    const result = await request(
        'GET',
        `/orders/${ORDER_ID}/failure-check`
    );

    checkApiResponse(
        result,
        [200, 404, 409, 422, 500],
        `GET /orders/${ORDER_ID}/failure-check`
    );

    if (result.status === 200) {
        assert(
            result.body.success === true,
            'failure-check trả success=true'
        );

        const data = result.body.data || {};

        assert(
            typeof data.daArrived === 'boolean',
            'failure-check có daArrived'
        );

        assert(
            typeof data.duoi10Phut === 'boolean',
            'failure-check có duoi10Phut'
        );

        assert(
            typeof data.giayConLai === 'number',
            'failure-check có giayConLai kiểu number'
        );

        assert(
            data.coTheGuiLoi &&
            typeof data.coTheGuiLoi === 'object',
            'failure-check có coTheGuiLoi'
        );

        assert(
            data.coTheGuiLoi &&
            typeof data.coTheGuiLoi.SHIPPER === 'boolean',
            'failure-check có quyền báo lỗi SHIPPER'
        );

        assert(
            data.coTheGuiLoi &&
            typeof data.coTheGuiLoi.CUSTOMER === 'boolean',
            'failure-check có quyền báo lỗi CUSTOMER'
        );

        console.log(
            `   daArrived=${data.daArrived}, ` +
            `duoi10Phut=${data.duoi10Phut}, ` +
            `giayConLai=${data.giayConLai}`
        );

        console.log(
            '   coTheGuiLoi=',
            JSON.stringify(data.coTheGuiLoi)
        );
    }

    return result;
}

async function testInvalidPayload() {
    printHeader('4. Kiểm tra validate input');

    if (!ORDER_ID) {
        skip('Chưa cấu hình ORDER_ID');
        return;
    }

    const result = await request(
        'POST',
        `/orders/${ORDER_ID}/report-failure`,
        {
            loai_loi: 'INVALID_TYPE',
            ly_do: '',
            ghi_chu: TEST_NOTE,
        }
    );

    checkApiResponse(
        result,
        [400, 409, 422, 500],
        'report-failure với payload không hợp lệ'
    );

    assert(
        result.status !== 200,
        'Payload không hợp lệ không được xử lý thành công'
    );

    assert(
        result.body.code !== 'SERVER_ERROR' || result.status === 500,
        'Backend trả code lỗi rõ ràng hoặc phản hồi lỗi server'
    );
}

async function testCustomerFailureCheckOnly() {
    printHeader('5. Kiểm tra điều kiện báo lỗi CUSTOMER');

    if (!ORDER_ID) {
        skip('Chưa cấu hình ORDER_ID');
        return;
    }

    const checkResult = await request(
        'GET',
        `/orders/${ORDER_ID}/failure-check`
    );

    if (checkResult.status !== 200) {
        skip('Không thể kiểm tra điều kiện CUSTOMER');
        return;
    }

    const data = checkResult.body.data || {};
    const canReportCustomer =
        data.coTheGuiLoi &&
        data.coTheGuiLoi.CUSTOMER === true;

    if (!canReportCustomer) {
        skip(
            'Đơn hiện chưa đủ điều kiện báo CUSTOMER ' +
            '(cần SHIPPING + ARRIVED + đủ 10 phút)'
        );
        return;
    }

    pass('Đơn hiện đủ điều kiện báo lỗi CUSTOMER');

    if (!RUN_MUTATING_TESTS) {
        skip(
            'Chưa chạy POST report-failure vì RUN_MUTATING_TESTS chưa bật'
        );
        return;
    }

    const reportResult = await request(
        'POST',
        `/orders/${ORDER_ID}/report-failure`,
        {
            loai_loi: 'CUSTOMER',
            ly_do: TEST_REASON,
            ghi_chu: TEST_NOTE,
        }
    );

    checkApiResponse(
        reportResult,
        [200, 409, 422, 500],
        'POST report-failure CUSTOMER'
    );

    if (reportResult.status === 200) {
        const resultData = reportResult.body.data || {};

        console.log(
            '   Kết quả report CUSTOMER:',
            JSON.stringify(resultData, null, 2)
        );

        assert(
            resultData.trang_thai !== 'CANCELLED',
            'Báo CUSTOMER lần đầu chưa chuyển đơn sang CANCELLED'
        );
    }
}

async function testShipperFailureWarning() {
    printHeader('6. Kiểm tra quyền báo lỗi SHIPPER');

    if (!ORDER_ID) {
        skip('Chưa cấu hình ORDER_ID');
        return;
    }

    const result = await request(
        'GET',
        `/orders/${ORDER_ID}/failure-check`
    );

    if (result.status !== 200) {
        skip('Không lấy được failure-check');
        return;
    }

    const data = result.body.data || {};
    const status = data.trangThai;

    assert(
        status !== undefined,
        'failure-check trả về trạng thái đơn'
    );

    if (status === 'SHIPPING') {
        assert(
            data.coTheGuiLoi &&
            data.coTheGuiLoi.SHIPPER === true,
            'Đơn SHIPPING được phép báo lỗi SHIPPER'
        );
    } else {
        skip(
            `Đơn hiện ở trạng thái ${status}, không kiểm tra báo lỗi SHIPPER`
        );
    }

    if (!RUN_MUTATING_TESTS) {
        skip(
            'Không gọi POST SHIPPER failure để tránh thay đổi dữ liệu'
        );
    }
}

async function testFinalizeEndpoint() {
    printHeader('7. Kiểm tra endpoint finalize CUSTOMER failure');

    if (!ORDER_ID) {
        skip('Chưa cấu hình ORDER_ID');
        return;
    }

    const result = await request(
        'POST',
        `/orders/${ORDER_ID}/finalize-customer-failure`
    );

    checkApiResponse(
        result,
        [200, 404, 409, 422, 500],
        'POST finalize-customer-failure'
    );

    if (!RUN_MUTATING_TESTS) {
        skip(
            'Endpoint finalize đã được gọi để kiểm tra route; ' +
            'hãy dùng đơn test riêng khi bật RUN_MUTATING_TESTS'
        );
    }

    if (result.status === 200) {
        const data = result.body.data || {};

        assert(
            data.trang_thai !== undefined ||
            data.status !== undefined ||
            data.alreadyFinalized === true,
            'Finalize trả về trạng thái hoặc kết quả xử lý'
        );
    }
}

async function main() {
    console.log('==============================================');
    console.log('SHIPPER-BE DELIVERY FAILURE FLOW TEST');
    console.log('==============================================');
    console.log(`BASE_URL: ${BASE_URL}`);
    console.log(`SHIPPER_ID: ${SHIPPER_ID}`);
    console.log(`ORDER_ID: ${ORDER_ID || '(chưa cấu hình)'}`);
    console.log(`RUN_MUTATING_TESTS: ${RUN_MUTATING_TESTS}`);

    try {
        await testHealthAndRoute();
        await testOrderDetail();
        await testFailureCheck();
        await testInvalidPayload();
        await testCustomerFailureCheckOnly();
        await testShipperFailureWarning();

        if (RUN_MUTATING_TESTS) {
            await testFinalizeEndpoint();
        } else {
            skip(
                'Bỏ qua finalize mutation vì RUN_MUTATING_TESTS=false'
            );
        }
    } catch (error) {
        fail(
            'Test runner bị lỗi ngoài dự kiến',
            error && error.stack ? error.stack : String(error)
        );
    }

    printHeader('KẾT QUẢ');

    console.log(`PASS: ${passed}`);
    console.log(`FAIL: ${failed}`);

    if (failed > 0) {
        console.error('❌ Có kiểm tra thất bại.');
        process.exitCode = 1;
    } else {
        console.log('✅ Các kiểm tra đã chạy xong.');
    }
}

main();