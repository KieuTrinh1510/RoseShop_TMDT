{{--
    resources/views/customer/FlashSale/index.blade.php
    - Nút "Mua ngay" → toggle form inline (không qua trang chi tiết).
    - Sản phẩm lẻ: chỉ có +/- số lượng.
    - Bó hoa: có thêm ô "Kèm thiệp" + select thiệp.
--}}
@extends('customer.layouts.customer')

@section('title', 'Flash Sale')
@section('page-id', 'flash-sale')

@section('head-styles')
<style>
    #flash-sale-page {
        padding: 32px 24px 64px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .fs-hero { text-align: center; margin-bottom: 36px; }
    .fs-hero-label {
        display: inline-block; background: #e53935; color: #fff;
        font-size: 0.7rem; font-weight: 600; letter-spacing: 0.12em;
        padding: 4px 12px; border-radius: 20px; margin-bottom: 10px;
        text-transform: uppercase;
    }
    .fs-hero h2 {
        font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 700;
        color: var(--ink, #1a1714); margin-bottom: 8px; line-height: 1.2;
    }
    .fs-hero p {
        color: var(--ink-soft, #6b6560); font-size: 0.9rem;
        max-width: 480px; margin: 0 auto;
    }

    /* ── Grid ── */
    .fs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    /* ── Card ── */
    .fs-card {
        background: #fff; border: 1px solid var(--border, #e0dbd0);
        border-radius: 10px; overflow: hidden; display: flex; flex-direction: column;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .fs-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.10);
        transform: translateY(-2px);
    }

    /* Ảnh */
    .fs-card-img {
        position: relative; aspect-ratio: 4 / 3;
        overflow: hidden; background: #f5f0eb;
    }
    .fs-card-img img {
        width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .fs-card-img-placeholder {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        font-size: 3.5rem; color: #c8b8a0;
    }

    .fs-badge-wrap {
        position: absolute; top: 10px; left: 10px;
        display: flex; flex-direction: column; gap: 4px;
    }
    .fs-badge {
        display: inline-block; background: #e53935; color: #fff;
        font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
        padding: 3px 8px; border-radius: 4px; text-transform: uppercase;
    }
    .fs-discount-pct {
        display: inline-block; background: #fff; color: #e53935;
        font-size: 0.72rem; font-weight: 700; padding: 2px 7px;
        border-radius: 4px; border: 1px solid #e53935;
    }
    .fs-type-tag {
        position: absolute; bottom: 8px; right: 8px;
        background: rgba(0,0,0,0.45); color: #fff;
        font-size: 0.62rem; padding: 2px 7px; border-radius: 3px;
        backdrop-filter: blur(2px);
    }

    /* Body */
    .fs-card-body {
        padding: 14px 16px; flex: 1; display: flex;
        flex-direction: column; gap: 8px;
    }
    .fs-card-name {
        font-size: 0.95rem; font-weight: 600;
        color: var(--ink, #1a1714); line-height: 1.35;
        overflow: hidden; display: -webkit-box;
        -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    }

    /* Giá */
    .fs-price-row {
        display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;
    }
    .fs-price-original {
        font-size: 0.8rem; color: var(--ink-muted, #a09890);
        text-decoration: line-through;
    }
    .fs-price-sale {
        font-size: 1.15rem; font-weight: 700; color: #e53935;
    }

    /* Tiến trình */
    .fs-stock { display: flex; flex-direction: column; gap: 4px; }
    .fs-stock-bar-bg {
        background: #f0ece4; border-radius: 4px;
        height: 6px; overflow: hidden;
    }
    .fs-stock-bar-fill {
        background: #e53935; height: 100%; border-radius: 4px;
        transition: width 0.4s;
    }
    .fs-stock-label { font-size: 0.7rem; color: var(--ink-soft, #6b6560); }
    .fs-stock-label strong { color: #e53935; }

    /* Countdown */
    .fs-countdown {
        font-size: 0.72rem; color: var(--ink-soft, #6b6560);
        display: flex; align-items: center; gap: 4px;
    }
    .fs-countdown .countdown-timer {
        font-family: monospace; font-weight: 600;
        color: var(--ink, #1a1714);
        background: #f5f0eb; padding: 1px 6px; border-radius: 3px;
    }

    /* Buttons */
    .fs-card-actions {
        display: flex; gap: 8px; margin-top: auto; padding-top: 10px;
    }
    .fs-btn {
        flex: 1; display: inline-flex; align-items: center;
        justify-content: center; gap: 5px;
        padding: 8px 12px; border-radius: 6px;
        font-size: 0.8rem; font-weight: 500;
        cursor: pointer; border: none;
        text-decoration: none; transition: background 0.15s, opacity 0.15s;
        text-align: center;
    }
    .fs-btn-primary { background: #e53935; color: #fff; }
    .fs-btn-primary:hover { background: #c62828; }
    .fs-btn-primary:disabled, .fs-btn-primary.disabled {
        background: #ccc; cursor: not-allowed;
    }

    /* Form mua ngay (inline toggle) */
    .fs-buy-form {
        margin-top: 10px; padding: 12px 14px;
        background: #fff9fb; border: 1px solid #fce7f3;
        border-radius: 8px; animation: fadeIn 0.15s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .fs-qty-row {
        display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
    }
    .fs-qty-label {
        font-size: 0.8rem; color: #6b6560; font-weight: 600;
    }
    .fs-stepper {
        display: inline-flex; align-items: center;
        border: 1px solid #e0dbd0; border-radius: 6px;
        overflow: hidden; background: #fff;
    }
    .fs-step {
        width: 34px; height: 34px; border: none; background: transparent;
        font-size: 1.1rem; font-weight: 700; color: #6b6560;
        cursor: pointer; transition: background 0.15s;
    }
    .fs-step:hover:not(:disabled) { background: #f5f0eb; color: #1a1714; }
    .fs-step:disabled { opacity: 0.35; cursor: not-allowed; }
    .fs-stepper input {
        width: 52px; height: 34px; border: none; text-align: center;
        font-weight: 600; font-size: 0.92rem; outline: none;
        border-left: 1px solid #e0dbd0; border-right: 1px solid #e0dbd0;
        background: #fff;
    }
    .fs-stepper input::-webkit-outer-spin-button,
    .fs-stepper input::-webkit-inner-spin-button {
        -webkit-appearance: none; margin: 0;
    }
    .fs-stepper input[type=number] { -moz-appearance: textfield; }

    /* Thiệp */
    .fs-thiep-row {
        margin-bottom: 10px;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #f3e8ff;
        border-radius: 6px;
    }
    .fs-check {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 0.82rem; cursor: pointer;
        color: #6b21a8; font-weight: 600;
    }
    .fs-check input[type="checkbox"] {
        width: 15px; height: 15px; accent-color: #e53935; cursor: pointer;
    }
    .fs-thiep-select {
        width: 100%; margin-top: 8px; padding: 7px 10px;
        border: 1px solid #d8b4fe; border-radius: 6px;
        font-size: 0.82rem; background: #fff;
        color: #1a1714; outline: none;
    }
    .fs-thiep-select:focus { border-color: #e53935; }

    .fs-buy-form .fs-btn-primary { width: 100%; padding: 10px; font-weight: 600; }

    /* Empty */
    .fs-empty {
        text-align: center; padding: 60px 20px;
        color: var(--ink-muted, #a09890);
    }
    .fs-empty-icon { font-size: 3rem; margin-bottom: 12px; }
    .fs-empty h3 {
        font-size: 1rem; color: var(--ink-soft, #6b6560); margin-bottom: 6px;
    }
    .fs-empty p { font-size: 0.85rem; }

    /* Modal login */
    .fs-login-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.4); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .fs-login-overlay.show { display: flex; }
    .fs-login-modal {
        background: #fff; border-radius: 10px;
        padding: 28px 32px; max-width: 380px; width: 90%;
        text-align: center;
    }
    .fs-login-modal h3 { font-size: 1.1rem; margin-bottom: 10px; }
    .fs-login-modal p {
        font-size: 0.85rem; color: var(--ink-soft, #6b6560);
        margin-bottom: 20px;
    }
    .fs-login-modal .modal-actions {
        display: flex; gap: 10px; justify-content: center;
    }
    .fs-login-modal .fs-btn {
        flex: none; padding: 8px 18px;
    }
    .fs-login-modal .fs-btn-outline {
        background: transparent; border: 1px solid #e0dbd0; color: #1a1714;
    }
    .fs-login-modal .fs-btn-outline:hover { background: #f5f0eb; }

        /* Mô tả sản phẩm trong card */
    .fs-card-desc {
        font-size: 0.76rem;
        color: #6b6560;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Ô nhập lời nhắn kèm thiệp */
    .fs-thiep-loinhan {
        width: 100%;
        margin-top: 6px;
        padding: 7px 10px;
        border: 1px solid #d8b4fe;
        border-radius: 6px;
        font-size: 0.82rem;
        background: #fff;
        font-family: inherit;
        resize: vertical;
        outline: none;
    }
    .fs-thiep-loinhan:focus { border-color: #e53935; }

    @media (max-width: 520px) {
        .fs-grid { grid-template-columns: 1fr; gap: 14px; }
        .fs-card-body { padding: 12px 14px; }
        #flash-sale-page { padding: 20px 16px 48px; }
    }
</style>
@endsection

@section('content')
<div id="flash-sale-page">

    {{-- ── Hero ── --}}
    <div class="fs-hero">
        <span class="fs-hero-label">⚡ Flash Sale</span>
        <h2>Giá Tốt Hôm Nay</h2>
        <p>Sản phẩm thanh lý chất lượng với số lượng có hạn — nhanh tay kẻo hết.</p>
    </div>

    {{-- Flash messages --}}
    @if(session('error'))
        <div class="alert alert-danger" style="padding:10px 14px;background:#fef2f2;color:#b42318;border-radius:8px;margin-bottom:16px;font-size:0.85rem;">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- ── Danh sách ── --}}
    @if($flashSales->isEmpty())
        <div class="fs-empty">
            <div class="fs-empty-icon">🌸</div>
            <h3>Hiện chưa có sản phẩm Flash Sale</h3>
            <p>Vui lòng quay lại sau.</p>
        </div>
    @else
        <div class="fs-grid">
            @foreach($flashSales as $fs)
            @php
                $laBoHoa = !is_null($fs->ma_bo_hoa);
                $ten     = $laBoHoa
                    ? ($fs->boHoa?->ten_bo_hoa ?? 'Bó hoa')
                    : ($fs->sanPham?->ten_san_pham ?? 'Sản phẩm');
                $hinh    = $laBoHoa
                    ? ($fs->boHoa ? \App\Http\Controllers\Admin\BoHoaController::anh($fs->boHoa) : null)
                    : ($fs->sanPham?->hinh_anh ? asset($fs->sanPham->hinh_anh) : null);
                $conLai  = max(0, $fs->so_luong - $fs->so_luong_da_ban);
                $pctSold = $fs->so_luong > 0
                    ? round($fs->so_luong_da_ban / $fs->so_luong * 100)
                    : 0;
                $pctGiam = $fs->gia_goc > 0
                    ? round((1 - $fs->gia_flash_sale / $fs->gia_goc) * 100)
                    : 0;
            @endphp

            <div class="fs-card" id="fs-card-{{ $fs->ma_flash_sale }}">

                {{-- Ảnh --}}
                <div class="fs-card-img">
                    @if($hinh)
                        <img src="{{ $hinh }}" alt="{{ $ten }}" loading="lazy">
                    @else
                        <div class="fs-card-img-placeholder">🌸</div>
                    @endif

                    <div class="fs-badge-wrap">
                        <span class="fs-badge">Flash Sale</span>
                        @if($pctGiam > 0)
                            <span class="fs-discount-pct">-{{ $pctGiam }}%</span>
                        @endif
                    </div>

                    <span class="fs-type-tag">{{ $laBoHoa ? 'Bó hoa' : 'Sản phẩm lẻ' }}</span>
                </div>

                {{-- Body --}}
                <div class="fs-card-body">
                    <div class="fs-card-name">{{ $ten }}</div>

                    @php
                        $moTa = $laBoHoa
                            ? ($fs->boHoa?->concept
                                ? (\App\Http\Controllers\Admin\BoHoaController::CONCEPT[$fs->boHoa->concept] ?? $fs->boHoa->concept)
                                : null)
                            : $fs->sanPham?->mo_ta;
                    @endphp
                    @if($moTa)
                        <div class="fs-card-desc">
                            {{ \Illuminate\Support\Str::limit($moTa, 90) }}
                        </div>
                    @endif

                    {{-- Giá --}}
                    <div class="fs-price-row">
                        <span class="fs-price-original">
                            {{ number_format($fs->gia_goc, 0, ',', '.') }}đ
                        </span>
                        <span class="fs-price-sale">
                            {{ number_format($fs->gia_flash_sale, 0, ',', '.') }}đ
                        </span>
                    </div>

                    {{-- Số lượng còn --}}
                    <div class="fs-stock">
                        <div class="fs-stock-bar-bg">
                            <div class="fs-stock-bar-fill" style="width: {{ $pctSold }}%"></div>
                        </div>
                        <div class="fs-stock-label">
                            Còn lại: <strong>{{ $conLai }}</strong> / {{ $fs->so_luong }}
                        </div>
                    </div>

                    {{-- Countdown --}}
                    <div class="fs-countdown">
                        <span>Kết thúc:</span>
                        @if($fs->ket_thuc)
                            <span class="countdown-timer"
                                  data-end="{{ \Carbon\Carbon::parse($fs->ket_thuc)->toISOString() }}"
                                  data-id="{{ $fs->ma_flash_sale }}">
                                {{ \Carbon\Carbon::parse($fs->ket_thuc)->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="countdown-timer">Không giới hạn</span>
                        @endif
                    </div>

                    {{-- Nút hành động --}}
                    <div class="fs-card-actions">
                        @if($conLai > 0)
                            @auth
                                <button type="button"
                                        class="fs-btn fs-btn-primary"
                                        onclick="toggleBuyForm({{ $fs->ma_flash_sale }})">
                                    🛒 Mua ngay
                                </button>
                            @else
                                <button type="button"
                                        class="fs-btn fs-btn-primary"
                                        onclick="showLoginPrompt()">
                                    🛒 Mua ngay
                                </button>
                            @endauth
                        @else
                            <button class="fs-btn fs-btn-primary disabled" disabled>Hết hàng</button>
                        @endif
                    </div>

                    {{-- Form mua ngay (inline) --}}
                    @auth
                    @if($conLai > 0)
                    <div class="fs-buy-form"
                         id="fs-buy-{{ $fs->ma_flash_sale }}"
                         style="display:none;">
                        <form method="POST" action="{{ route('customer.flash-sale.add') }}">
                            @csrf
                            <input type="hidden" name="ma_flash_sale" value="{{ $fs->ma_flash_sale }}">

                            {{-- Số lượng +/- --}}
                            <div class="fs-qty-row">
                                <span class="fs-qty-label">Số lượng:</span>
                                <div class="fs-stepper">
                                    <button type="button" class="fs-step"
                                            onclick="stepQty({{ $fs->ma_flash_sale }}, -1)">−</button>
                                    <input type="number"
                                           id="qty-{{ $fs->ma_flash_sale }}"
                                           name="so_luong"
                                           value="1" min="1" max="{{ $conLai }}"
                                           readonly>
                                    <button type="button" class="fs-step"
                                            onclick="stepQty({{ $fs->ma_flash_sale }}, 1)">+</button>
                                </div>
                            </div>

                                                        {{-- Ô chọn thiệp + lời nhắn (chỉ bó hoa) --}}
                            @if($laBoHoa && isset($dsThiep) && $dsThiep->count() > 0)
                            <div class="fs-thiep-row">
                                <label class="fs-check">
                                    <input type="checkbox"
                                           id="thiep-check-{{ $fs->ma_flash_sale }}"
                                           onchange="toggleThiep({{ $fs->ma_flash_sale }}, this.checked)">
                                    <span>Kèm thiệp</span>
                                </label>

                                <div id="thiep-fields-{{ $fs->ma_flash_sale }}" style="display:none;">
                                    <select name="ma_thiep"
                                            id="thiep-sel-{{ $fs->ma_flash_sale }}"
                                            class="fs-thiep-select">
                                        <option value="">-- Chọn thiệp --</option>
                                        @foreach($dsThiep as $t)
                                            <option value="{{ $t->ma_san_pham }}">
                                                {{ $t->ten_san_pham }} (+{{ number_format($t->gia_ban_hien_tai, 0, ',', '.') }}đ)
                                            </option>
                                        @endforeach
                                    </select>

                                    <textarea name="loi_nhan"
                                              id="thiep-loinhan-{{ $fs->ma_flash_sale }}"
                                              class="fs-thiep-loinhan"
                                              maxlength="2000"
                                              rows="2"
                                              placeholder="Lời nhắn gửi kèm (tùy chọn)..."></textarea>
                                </div>
                            </div>
                            @endif

                            <button type="submit" class="fs-btn fs-btn-primary">
                                Xác nhận mua
                            </button>
                        </form>
                    </div>
                    @endif
                    @endauth
                </div>

            </div>
            @endforeach
        </div>
    @endif

</div>

{{-- ── Modal nhắc đăng nhập ── --}}
<div class="fs-login-overlay" id="loginOverlay">
    <div class="fs-login-modal">
        <h3>Vui lòng đăng nhập</h3>
        <p>Bạn cần đăng nhập để mua sản phẩm Flash Sale.</p>
        <div class="modal-actions">
            <button type="button"
                    onclick="document.getElementById('loginOverlay').classList.remove('show')"
                    class="fs-btn fs-btn-outline">
                Để sau
            </button>
            <a href="{{ route('login') }}" class="fs-btn fs-btn-primary">
                Đăng nhập
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Toggle form mua ngay (chỉ mở 1 form tại 1 thời điểm) ─────────────────
function toggleBuyForm(id) {
    const form = document.getElementById('fs-buy-' + id);
    if (!form) return;

    const isOpen = form.style.display !== 'none';

    // Đóng tất cả form khác trước
    document.querySelectorAll('.fs-buy-form').forEach(f => {
        f.style.display = 'none';
    });

    if (!isOpen) {
        form.style.display = 'block';
    }
}

// ── Điều chỉnh số lượng +/– ──────────────────────────────────────────────
function stepQty(id, delta) {
    const input = document.getElementById('qty-' + id);
    if (!input) return;

    const max = Number(input.max) || 1;
    let v = Number(input.value) + delta;
    v = Math.max(1, Math.min(max, v));
    input.value = v;
}

// ── Toggle ô chọn thiệp ──────────────────────────────────────────────────
function toggleThiep(id, checked) {
    const wrap = document.getElementById('thiep-fields-' + id);
    const sel  = document.getElementById('thiep-sel-' + id);
    const note = document.getElementById('thiep-loinhan-' + id);
    if (!wrap) return;

    wrap.style.display = checked ? 'block' : 'none';
    if (sel)  sel.required = checked;
    if (!checked) {
        if (sel)  sel.value  = '';
        if (note) note.value = '';
    }
}

// ── Modal đăng nhập ──────────────────────────────────────────────────────
function showLoginPrompt() {
    document.getElementById('loginOverlay').classList.add('show');
}
document.getElementById('loginOverlay').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});

// ── Đồng hồ đếm ngược ────────────────────────────────────────────────────
function formatCountdown(ms) {
    if (ms <= 0) return 'Đã kết thúc';
    const s = Math.floor(ms / 1000);
    const m = Math.floor(s / 60);
    const h = Math.floor(m / 60);
    const d = Math.floor(h / 24);
    if (d > 0) return `${d}n ${h % 24}g ${m % 60}p`;
    if (h > 0) return `${h}g ${String(m % 60).padStart(2,'0')}p ${String(s % 60).padStart(2,'0')}s`;
    return `${String(m).padStart(2,'0')}:${String(s % 60).padStart(2,'0')}`;
}

function tickAll() {
    document.querySelectorAll('.countdown-timer[data-end]').forEach(el => {
        const end  = new Date(el.dataset.end).getTime();
        const diff = end - Date.now();
        const id   = el.dataset.id;

        if (diff <= 0) {
            el.textContent = 'Đã kết thúc';
            const card = document.getElementById('fs-card-' + id);
            if (card) {
                const buyBtn = card.querySelector('.fs-btn-primary:not(.disabled)');
                if (buyBtn && buyBtn.tagName === 'BUTTON') {
                    buyBtn.disabled = true;
                    buyBtn.classList.add('disabled');
                    buyBtn.textContent = 'Đã kết thúc';
                }
                const form = document.getElementById('fs-buy-' + id);
                if (form) form.style.display = 'none';
            }
        } else {
            el.textContent = formatCountdown(diff);
        }
    });
}

tickAll();
setInterval(tickAll, 1000);
</script>
@endsection