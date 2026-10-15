#!/usr/bin/env node
// test-shipper-status.js
// Test nhanh các endpoint Node của shipper — chạy trước khi mở app Flutter.
//
// Yêu cầu: Node 18+ (để có global fetch). Không cần cài thêm package.
//
// Cách chạy:
//   node test-shipper-status.js
//   node test-shipper-status.js 9
//   node test-shipper-status.js 9 http://localhost:3000/api/shipper
//   TEST_DELETE=1 node test-shipper-status.js         (test luôn DELETE)
//
// Biến môi trường:
//   SHIPPER_ID   — mã nhân viên shipper muốn test (default 9)
//   API_BASE     — base URL của shipper router     (default http://localhost:3000/api/shipper)
//   TEST_DELETE  — đặt =1 để test endpoint DELETE

const BASE_URL = process.argv[3] || process.env.API_BASE || 'http://localhost:3000/api/shipper';
const MA_NV = Number(process.argv[2] || process.env.SHIPPER_ID || 9);

const C = {
    reset: '\x1b[0m',
    dim: '\x1b[2m',
    green: '\x1b[32m',
    red: '\x1b[31m',
    cyan: '\x1b[36m',
    yellow: '\x1b[33m',
};

let passed = 0, failed = 0;

function header(title) {
    console.log(`\n${C.cyan}▶ ${title}${C.reset}`);
}
function log(ok, name, detail = '') {
    const icon = ok ? `${C.green}✅` : `${C.red}❌`;
    console.log(`  ${icon}${C.reset} ${name}${detail ? `  ${C.dim}— ${detail}${C.reset}` : ''}`);
    ok ? passed++ : failed++;
}
function skip(name, reason) {
    console.log(`  ${C.yellow}⊘${C.reset} ${name}  ${C.dim}— ${reason}${C.reset}`);
}

async function req(method, path, body) {
    const url = BASE_URL + path;
    const opts = {
        method,
        headers: {
            'Content-Type': 'application/json',
            // Trùng header mà api_service.dart gửi lên
            'x-ma-nhan-vien': String(MA_NV),
        },
    };
    if (body !== undefined) opts.body = JSON.stringify(body);

    let res;
    try {
        res = await fetch(url, opts);
    } catch (e) {
        throw new Error(`Không kết nối được ${url} — ${e.message}. Server Node đã chạy chưa?`);
    }
    const text = await res.text();
    let json = null;
    try { json = JSON.parse(text); } catch { }
    return { status: res.status, json, text };
}

// ────────────────────────────────────────────────────────────
async function main() {
    console.log(`${C.cyan}=== Test shipper API ===${C.reset}`);
    console.log(`Base URL : ${BASE_URL}`);
    console.log(`Shipper  : #${MA_NV}`);

    // ── 1) /status ───────────────────────────────────────────
    header('1. GET /status');
    {
        const r = await req('GET', '/status');
        if (r.json?.success) {
            const d = r.json.data;
            log(true, 'Response OK',
                `online=${d.trang_thai_hoat_dong} • đơn=${d.so_don_hien_tai}/${d.gioi_han_don} • nhận được=${d.co_the_nhan_don}`);
        } else {
            log(false, 'Response lỗi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
        }
    }

    // ── 2) /home ─────────────────────────────────────────────
    header('2. GET /home (dashboard)');
    {
        const r = await req('GET', '/home');
        if (r.json?.success) {
            const d = r.json.data;
            log(true, 'Response OK',
                `tên="${d.shipper?.ten_nhan_vien}" • COD=${d.cod?.tien_can_nop} • phạt=${d.cod?.tien_phat} • TB chưa đọc=${d.thong_bao?.chua_doc}`);
        } else {
            log(false, 'Response lỗi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
        }
    }

    // ── 3) /notifications — list ────────────────────────────
    header('3. GET /notifications (danh sách thông báo)');
    let dsTb = [];
    {
        const r = await req('GET', '/notifications?limit=50');
        if (r.json?.success && Array.isArray(r.json.data)) {
            dsTb = r.json.data;
            log(true, 'Response OK', `nhận ${dsTb.length} thông báo`);

            const dem = {};
            for (const tb of dsTb) dem[tb.loai] = (dem[tb.loai] || 0) + 1;
            const phanLoai = Object.entries(dem).map(([k, v]) => `${k}:${v}`).join(', ');
            console.log(`     ${C.dim}Loại: ${phanLoai || '(trống)'}${C.reset}`);

            for (const tb of dsTb.slice(0, 3)) {
                const bullet = tb.da_doc ? '·' : '•';
                const hasDuLieu = tb.du_lieu ? ` ${C.dim}[có du_lieu]${C.reset}` : '';
                console.log(`     ${bullet} [#${tb.ma_thong_bao}] (${tb.loai}) ${String(tb.noi_dung).slice(0, 80)}${hasDuLieu}`);
            }
            if (dsTb.length > 3) console.log(`     ${C.dim}... và ${dsTb.length - 3} item nữa${C.reset}`);

            // Kiểm tra shape từng item
            const requiredKeys = ['ma_thong_bao', 'loai', 'noi_dung', 'ma_hoa_don', 'da_doc', 'thoi_gian'];
            const thieu = new Set();
            for (const tb of dsTb) {
                for (const k of requiredKeys) if (!(k in tb)) thieu.add(k);
            }
            if (thieu.size === 0) {
                log(true, 'Shape item đầy đủ', requiredKeys.join(', '));
            } else {
                log(false, 'Thiếu field', [...thieu].join(', '));
            }
        } else {
            log(false, 'Response lỗi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
        }
    }

    // ── 4) filter theo loại ─────────────────────────────────
    header('4. GET /notifications?loai=order_new (filter)');
    {
        const r = await req('GET', '/notifications?loai=order_new');
        if (r.json?.success && Array.isArray(r.json.data)) {
            const allDung = r.json.data.every(t => t.loai === 'order_new');
            if (allDung) {
                log(true, 'Response OK + filter đúng', `${r.json.data.length} item loại order_new`);
            } else {
                log(false, 'Filter sai', `có item không phải order_new trong kết quả`);
            }
        } else {
            log(false, 'Response lỗi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
        }
    }

    // ── 5) unread-count ─────────────────────────────────────
    header('5. GET /notifications/unread-count');
    let unread = 0;
    {
        const r = await req('GET', '/notifications/unread-count');
        if (r.json?.success && typeof r.json.data?.count === 'number') {
            unread = r.json.data.count;
            log(true, 'Response OK', `count = ${unread}`);
        } else {
            log(false, 'Response lỗi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
        }
    }

    // ── 6) mark-read ────────────────────────────────────────
    header('6. PATCH /notifications/:id/read (đánh dấu đã đọc)');
    {
        const tbChuaDoc = dsTb.find(t => !t.da_doc);
        if (!tbChuaDoc) {
            skip('Đánh dấu đã đọc', 'không có thông báo chưa đọc để test');
        } else {
            const id = tbChuaDoc.ma_thong_bao;
            const r = await req('PATCH', `/notifications/${id}/read`);
            if (r.json?.success && r.json.data?.ok === true) {
                log(true, `PATCH /notifications/${id}/read`, 'ok=true');

                const r2 = await req('GET', '/notifications/unread-count');
                const unreadSau = r2.json?.data?.count ?? unread;
                if (unreadSau === unread - 1) {
                    log(true, 'Số chưa đọc giảm đúng', `${unread} → ${unreadSau}`);
                } else {
                    log(false, 'Số chưa đọc sai kì vọng', `trước=${unread}, sau=${unreadSau} (kì vọng ${unread - 1})`);
                }
            } else {
                log(false, 'Response lỗi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
            }
        }
    }

    // ── 7) idempotent ───────────────────────────────────────
    header('7. PATCH /notifications/:id/read (gọi lại — không lỗi)');
    {
        const tbDaDoc = dsTb.find(t => t.da_doc) || dsTb[0];
        if (!tbDaDoc) {
            skip('Idempotent test', 'không có thông báo nào');
        } else {
            const id = tbDaDoc.ma_thong_bao;
            const r = await req('PATCH', `/notifications/${id}/read`);
            if (r.status === 200) {
                log(true, `Gọi lại PATCH id=${id}`, `HTTP 200 (ok=${r.json?.data?.ok})`);
            } else {
                log(false, 'Không idempotent', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
            }
        }
    }

    // ── 8) id không tồn tại ─────────────────────────────────
    header('8. PATCH /notifications/99999999/read (id không tồn tại)');
    {
        const r = await req('PATCH', '/notifications/99999999/read');
        if (r.status === 200 && r.json?.success) {
            log(true, 'HTTP 200 (không throw)', `ok=${r.json.data?.ok}`);
        } else {
            log(false, 'Không mong đợi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
        }
    }

    // ── 9) DELETE ───────────────────────────────────────────
    header('9. DELETE /notifications/:id');
    if (process.env.TEST_DELETE === '1') {
        const tbXoa = dsTb[dsTb.length - 1]; // item cuối cùng
        if (!tbXoa) {
            skip('Delete', 'không có thông báo nào để xoá');
        } else {
            const id = tbXoa.ma_thong_bao;
            const r = await req('DELETE', `/notifications/${id}`);
            if (r.json?.success && r.json.data?.ok === true) {
                log(true, `DELETE /notifications/${id}`, 'ok=true');
            } else {
                log(false, 'Response lỗi', `HTTP ${r.status} — ${r.text.slice(0, 200)}`);
            }
        }
    } else {
        skip('Delete', 'đặt TEST_DELETE=1 để chạy (tránh xoá ngoài ý muốn)');
    }

    // ── 10) du_lieu của payment (nếu có) ───────────────────
    header('10. Kiểm tra du_lieu của thông báo loại payment');
    {
        const tbPayment = dsTb.find(t => t.loai === 'payment' && t.du_lieu);
        if (!tbPayment) {
            skip('Breakdown payment', 'chưa có thông báo payment nào có du_lieu (cần chạy payback bên PHP trước)');
        } else {
            const d = tbPayment.du_lieu;
            const coDayDu = ['tong_cod', 'tien_phat', 'tong_nop', 'so_don', 'ds_don'].every(k => k in d);
            if (coDayDu) {
                log(true, 'du_lieu đầy đủ', `tổng=${d.tong_nop} • ${d.so_don} đơn • phạt=${d.tien_phat}`);
                if (Array.isArray(d.ds_don) && d.ds_don[0]) {
                    const first = d.ds_don[0];
                    const okShape = 'ma_hien_thi' in first && 'tien' in first;
                    log(okShape, 'Shape ds_don[0]', JSON.stringify(first));
                }
            } else {
                log(false, 'du_lieu thiếu field', JSON.stringify(d).slice(0, 200));
            }
        }
    }

    // ── Tổng kết ────────────────────────────────────────────
    console.log(`\n${C.cyan}=== Tổng kết ===${C.reset}`);
    console.log(`  ${C.green}Passed: ${passed}${C.reset}`);
    console.log(`  ${failed ? C.red : C.dim}Failed: ${failed}${C.reset}`);
    process.exit(failed > 0 ? 1 : 0);
}

main().catch((e) => {
    console.error(`\n${C.red}💥 ${e.message}${C.reset}`);
    process.exit(2);
});