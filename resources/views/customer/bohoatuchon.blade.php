@extends('customer.layouts.customer')
@section('title', 'Tự tạo bó hoa – Hoa Tươi Shop')
@section('page-id', 'bo-hoa-tu-tao')

@section('head-styles')
<style>
.bh-form{max-width:900px;margin:40px auto;padding:0 20px 60px;color:#33282e}
.bh-form h1{font:700 32px 'Playfair Display',serif;margin:0 0 8px}
.bh-form .lead{color:#725e67;margin:0 0 28px;line-height:1.6}
.bh-form section{padding:22px;border:1px solid #eadbe1;border-radius:14px;background:#fff;margin-bottom:18px}
.bh-form section h2{font-size:18px;margin:0 0 12px;color:#8e3555;font-family:inherit}
.bh-form label{display:block;font-weight:600;margin:12px 0 6px;font-size:14px}
.bh-form input[type=text],.bh-form input[type=number],.bh-form select,.bh-form textarea{box-sizing:border-box;width:100%;padding:10px 12px;border:1px solid #ccb9c1;border-radius:8px;font:inherit;background:#fff}
.bh-form .row{display:grid;grid-template-columns:1fr 110px 40px;gap:8px;margin:8px 0;align-items:center}
.bh-form .row button{border:1px solid #e0cfd6;background:#fff;border-radius:8px;cursor:pointer;height:40px;font-size:16px}
.bh-form .row button:disabled{opacity:.35;cursor:not-allowed}
.bh-form .btn-add{border:1px dashed #ccb9c1;background:#fbf5f8;color:#8e3555;border-radius:8px;padding:9px 14px;cursor:pointer;font:inherit;font-size:13px}
.bh-form .btn-add:hover{background:#f8eef2}
.bh-form .btn-row{display:flex;gap:12px;margin-top:16px;flex-wrap:wrap}
.bh-form .btn-primary{background:#8e3555;color:#fff;border:0;border-radius:10px;padding:13px 26px;font:inherit;font-weight:600;cursor:pointer}
.bh-form .btn-primary:disabled{opacity:.6;cursor:wait}
.bh-form .btn-secondary{background:#f3e8ed;color:#78304c;border:0;border-radius:10px;padding:13px 26px;font:inherit;font-weight:600;cursor:pointer}
.bh-form .note{font-size:13px;color:#725e67;margin-top:8px}
.bh-form .alert{padding:12px 14px;background:#f8eef2;border-radius:8px;margin-top:14px;font-size:14px}
.bh-form .alert.err{background:#fff0ef;color:#9e2924}
.bh-form .alert.ok{background:#eaf7ee;color:#1f7a3a}
.bh-form .flower-hint{font-size:13px;color:#8e3555;font-weight:600;margin-top:6px}
</style>
@endsection

@section('content')
@php
    $CONCEPT_LIST = \App\Http\Controllers\Admin\BoHoaController::CONCEPT;
@endphp

<div class="bh-form">
    <h1>Tự tạo bó hoa</h1>
    <p class="lead">Chọn nguyên liệu theo từng bước. Hoa chính + hoa trang trí phải bằng đúng số bông của size. Giấy gói, ruy băng, xốp là bắt buộc và hệ thống tự áp đúng số lượng theo size.</p>

    <form id="custom-bouquet" method="POST" action="{{ route('customer.bo-hoa.tu-tao.xac-nhan') }}">
        @csrf

        <section>
            <h2>1. Kiểu dáng</h2>
            <select name="kieu_bo_hoa" required>
                <option value="">-- Chọn kiểu dáng --</option>
                @foreach($phuPhi->where('loai','KIEU_DANG') as $p)
                    <option value="{{ $p->gia_tri }}" @selected(($duLieuCu['kieu_bo_hoa'] ?? '') === $p->gia_tri)>
                        {{ $KIEU_DANG[$p->gia_tri] ?? $p->gia_tri }} · +{{ number_format($p->phu_phi,0,',','.') }}đ
                    </option>
                @endforeach
            </select>
        </section>

        <section>
            <h2>2. Size</h2>
            <select name="size" id="bo-size" required>
                <option value="">-- Chọn size --</option>
                @foreach($phuPhi->where('loai','SIZE') as $p)
                    <option value="{{ $p->gia_tri }}" @selected(($duLieuCu['size'] ?? '') === $p->gia_tri)>
                        {{ $p->gia_tri }} · {{ $SO_BONG[$p->gia_tri] ?? '?' }} bông · +{{ number_format($p->phu_phi,0,',','.') }}đ
                    </option>
                @endforeach
            </select>
            <p class="flower-hint" id="flower-count">Chọn size để biết số bông cần thiết.</p>
        </section>

        @foreach([
            ['role' => 'HOA_CHINH', 'label' => '3. Hoa chính', 'multi' => true,
             'note' => 'Cần đủ số bông theo size cùng với hoa trang trí.'],
            ['role' => 'HOA_TRANG_TRI', 'label' => '4. Hoa trang trí', 'multi' => true,
             'note' => 'Không bắt buộc. Không được trùng loại hoa với hoa chính.'],
            ['role' => 'GIAY_GOI', 'label' => '5. Giấy gói', 'multi' => false,
             'note' => 'Số tờ giấy gói tự động theo size.'],
            ['role' => 'RUY_BANG', 'label' => '6. Ruy băng', 'multi' => false,
             'note' => 'Số mét ruy băng tự động theo size.'],
            ['role' => 'PHU_KIEN_TRANG_TRI', 'label' => '7. Phụ kiện trang trí', 'multi' => true,
             'note' => 'Không bắt buộc, tối đa theo size. Chỉ nguyên vật liệu trang trí.'],
            ['role' => 'XOP', 'label' => '8. Xốp', 'multi' => false,
             'note' => 'Số miếng xốp tự động theo size.'],
        ] as $sec)
        <section class="role-section" data-role="{{ $sec['role'] }}" data-multi="{{ $sec['multi'] ? '1' : '0' }}">
            <h2>{{ $sec['label'] }}</h2>
            <div class="rows"></div>
            @if($sec['multi'])<button type="button" class="btn-add add-row">+ Thêm</button>@endif
            <p class="note">{{ $sec['note'] }}</p>
        </section>
        @endforeach

        <section>
            <h2>9. Concept & thông tin</h2>
            <label>Tên bó hoa *</label>
            <input type="text" name="ten_bo_hoa" required maxlength="100" value="{{ $duLieuCu['ten_bo_hoa'] ?? 'Bó hoa của tôi' }}">

            <label>Concept / chủ đề *</label>
            <select name="concept" required>
                <option value="">-- Chọn concept --</option>
                @foreach($CONCEPT_LIST as $key => $label)
                    <option value="{{ $key }}" @selected(($duLieuCu['concept'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <label>Số bó cùng thiết kế *</label>
            <input type="number" name="so_luong" min="1" max="10" value="{{ $duLieuCu['so_luong'] ?? 1 }}" required>
        </section>

        <div class="alert" id="quote-result" aria-live="polite">Bấm <strong>Tính giá</strong> để xem giá tạm tính.</div>

        <div class="btn-row">
            <button type="button" class="btn-secondary" id="quote-btn">Tính giá</button>
            <button type="submit" class="btn-primary" id="submit-btn">Xác nhận mua hàng</button>
        </div>
        <p class="note">Sau khi xác nhận, bạn sẽ xem lại thiết kế và có thể chọn thêm thiệp. Khi đó mới chính thức chuyển sang thanh toán.</p>
    </form>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const SO_BONG       = @json($SO_BONG);
    const GIAY_GOI_QTY  = @json($GIAY_GOI_QTY);
    const RUY_BANG_QTY  = @json($RUY_BANG_QTY);
    const XOP_QTY       = @json($XOP_QTY);
    const PHU_KIEN_MAX  = @json($PHU_KIEN_MAX);
    const ROLE_TYPES = {
        'HOA_CHINH': ['HOA_TUOI','HOA_GIA','HOA_SAP','HOA_GIAY_NHUN'],
        'HOA_TRANG_TRI': ['HOA_TUOI','HOA_GIA','HOA_SAP','HOA_GIAY_NHUN'],
        'GIAY_GOI': ['GIAY_GOI'],
        'RUY_BANG': ['RUY_BANG'],
        'XOP': ['MIENG_XOP'],
        // ─── FIX: chỉ dùng PHU_KIEN_TRANG_TRI (NVL), KHÔNG bao gồm PHU_KIEN (bán ra)
        'PHU_KIEN_TRANG_TRI': ['PHU_KIEN_TRANG_TRI'],
    };
    const SINGLE_ROW = ['GIAY_GOI','RUY_BANG','XOP'];
    const PRODUCTS = @json($nguyenLieu);
    const CU = @json($duLieuCu);
    const form = document.getElementById('custom-bouquet');
    const sizeSel = document.getElementById('bo-size');
    const result = document.getElementById('quote-result');
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    let nextIdx = 0;

    const money = n => Number(n).toLocaleString('vi-VN') + 'đ';
    const productsFor = role => PRODUCTS.filter(p => (ROLE_TYPES[role] || []).includes(p.loai_san_pham));

    function markDirty() {
        result.textContent = 'Thiết kế đã thay đổi. Bấm "Tính giá" để cập nhật.';
        result.className = 'alert';
    }

    function refreshFlowerCount() {
        const soBong = SO_BONG[sizeSel.value] || 0;
        let chinh = 0, tt = 0;
        form.querySelectorAll('[data-role="HOA_CHINH"] .qty').forEach(i => chinh += Number(i.value)||0);
        form.querySelectorAll('[data-role="HOA_TRANG_TRI"] .qty').forEach(i => tt += Number(i.value)||0);
        const el = document.getElementById('flower-count');
        if (!soBong) { el.textContent = 'Chọn size để biết số bông cần thiết.'; el.style.color = '#8e3555'; return; }
        const ok = (chinh + tt === soBong);
        el.textContent = `Hoa chính: ${chinh} · Trang trí: ${tt} · Cần: ${soBong} bông${ok ? ' ✓' : ''}`;
        el.style.color = ok ? '#1f7a3a' : '#8e3555';
    }

    function applySingleRowQty() {
        const size = sizeSel.value;
        SINGLE_ROW.forEach(role => {
            const row = form.querySelector(`.role-section[data-role="${role}"] .row`);
            if (!row) return;
            const qtyInput = row.querySelector('.qty');
            const map = role === 'GIAY_GOI' ? GIAY_GOI_QTY : (role === 'RUY_BANG' ? RUY_BANG_QTY : XOP_QTY);
            qtyInput.value = map[size] || 0;
            qtyInput.readOnly = true;
        });
    }

    function buildRow(role, values = {}) {
        const idx = nextIdx++;
        const row = document.createElement('div');
        row.className = 'row';

        const sel = document.createElement('select');
        sel.name = `thanh_phan[${idx}][ma_san_pham]`;
        sel.required = true;
        sel.innerHTML = '<option value="">-- Chọn nguyên liệu --</option>';
        productsFor(role).forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.ma_san_pham;
            opt.textContent = p.gia_ban_hien_tai == null
                ? p.ten_san_pham
                : `${p.ten_san_pham} · ${money(p.gia_ban_hien_tai)}`;
            sel.appendChild(opt);
        });
        if (values.ma_san_pham) sel.value = values.ma_san_pham;

        const qty = document.createElement('input');
        qty.type = 'number';
        qty.className = 'qty';
        qty.name = `thanh_phan[${idx}][so_luong]`;
        qty.min = '1'; qty.max = '1000'; qty.required = true;
        qty.value = values.so_luong || 1;
        if (SINGLE_ROW.includes(role)) qty.readOnly = true;

        const roleHidden = document.createElement('input');
        roleHidden.type = 'hidden';
        roleHidden.name = `thanh_phan[${idx}][vai_tro]`;
        roleHidden.value = role;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = '×';
        if (SINGLE_ROW.includes(role)) removeBtn.disabled = true;
        removeBtn.addEventListener('click', () => {
            if (SINGLE_ROW.includes(role)) return;
            row.remove();
            refreshFlowerCount();
            markDirty();
        });

        row.append(sel, qty, removeBtn, roleHidden);
        qty.addEventListener('input', () => { refreshFlowerCount(); markDirty(); });
        sel.addEventListener('change', markDirty);
        return row;
    }

    function ensureRow(role) {
        const sec = form.querySelector(`.role-section[data-role="${role}"]`);
        if (!sec) return;
        const rowsBox = sec.querySelector('.rows');
        if (rowsBox.children.length === 0) rowsBox.appendChild(buildRow(role));
    }

    form.querySelectorAll('.role-section').forEach(sec => {
        const role = sec.dataset.role;
        const isMulti = sec.dataset.multi === '1';
        const addBtn = sec.querySelector('.add-row');
        if (addBtn) addBtn.addEventListener('click', () => sec.querySelector('.rows').appendChild(buildRow(role)));
        if (!isMulti) ensureRow(role);
        if (isMulti && role === 'HOA_CHINH') ensureRow(role);
    });

    if (CU && Array.isArray(CU.thanh_phan) && CU.thanh_phan.length) {
        form.querySelectorAll('.role-section .rows').forEach(r => r.innerHTML = '');
        CU.thanh_phan.forEach(tp => {
            const sec = form.querySelector(`.role-section[data-role="${tp.vai_tro}"]`);
            if (!sec) return;
            sec.querySelector('.rows').appendChild(buildRow(tp.vai_tro, { ma_san_pham: tp.ma_san_pham, so_luong: tp.so_luong }));
        });
    }

    sizeSel.addEventListener('change', () => { applySingleRowQty(); refreshFlowerCount(); markDirty(); });

    function collect() {
        const chinhIds = new Set();
        form.querySelectorAll('[data-role="HOA_CHINH"] select').forEach(s => { if (s.value) chinhIds.add(s.value); });
        let trung = false;
        form.querySelectorAll('[data-role="HOA_TRANG_TRI"] select').forEach(s => { if (s.value && chinhIds.has(s.value)) trung = true; });
        if (trung) {
            result.textContent = '❌ Hoa trang trí không được trùng với hoa chính.';
            result.className = 'alert err'; return null;
        }
        const soBong = SO_BONG[sizeSel.value] || 0;
        let chinh = 0, tt = 0;
        form.querySelectorAll('[data-role="HOA_CHINH"] .qty').forEach(i => chinh += Number(i.value)||0);
        form.querySelectorAll('[data-role="HOA_TRANG_TRI"] .qty').forEach(i => tt += Number(i.value)||0);
        if (soBong && (chinh + tt !== soBong)) {
            result.textContent = `❌ Tổng hoa chính + trang trí phải đúng ${soBong} bông.`;
            result.className = 'alert err'; return null;
        }
        const maxPK = PHU_KIEN_MAX[sizeSel.value] || 0;
        let pk = 0;
        form.querySelectorAll('[data-role="PHU_KIEN_TRANG_TRI"] .qty').forEach(i => pk += Number(i.value)||0);
        if (pk > maxPK) {
            result.textContent = `❌ Size này chỉ cho tối đa ${maxPK} phụ kiện.`;
            result.className = 'alert err'; return null;
        }
        return true;
    }

    document.getElementById('quote-btn').addEventListener('click', async function() {
        if (!form.reportValidity()) return;
        if (collect() === null) return;
        this.disabled = true;
        result.textContent = 'Đang tính...'; result.className = 'alert';
        try {
            const res = await fetch(@json(route('customer.bo-hoa.quote')), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: new FormData(form),
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Không tính được giá.');
            const d = json.data;
            result.innerHTML = `💰 Nguyên liệu: <strong>${money(d.tien_nguyen_lieu)}</strong> · Phụ phí mỗi bó: <strong>${money(d.phu_phi.tong)}</strong> · Giá mỗi bó: <strong>${money(d.gia_moi_bo)}</strong> · Tổng: <strong>${money(d.tong_tien)}</strong>`;
            result.className = 'alert ok';
        } catch (e) {
            result.textContent = '❌ ' + e.message;
            result.className = 'alert err';
        } finally { this.disabled = false; }
    });

    form.addEventListener('submit', e => {
        if (collect() === null) { e.preventDefault(); return; }
        const b = document.getElementById('submit-btn');
        b.disabled = true; b.textContent = 'Đang xử lý...';
    });

    if (sizeSel.value) applySingleRowQty();
    refreshFlowerCount();
})();
</script>
@endsection