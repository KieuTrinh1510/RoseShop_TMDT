@extends('customer.layouts.customer')

@section('title', '🛒 Giỏ Hàng – Hoa Tươi Shop')
@section('page-id', 'gio-hang')

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/GioHang.css') }}?v={{ filemtime(public_path('css/Customer/GioHang.css')) }}">
@endsection

@section('content')
@php
    $tongTienBoHoa = !empty($dsBoHoa) ? collect($dsBoHoa)->sum('tong') : 0;
    $tongSoBoHoa   = !empty($dsBoHoa) ? collect($dsBoHoa)->sum('so_luong') : 0;
@endphp
<div class="cart-page">

    {{-- TIÊU ĐỀ TRANG --}}
    <header class="cart-header">
        <span class="cart-header-icon"><i class="fa-solid fa-cart-shopping"></i></span>
        <div class="cart-header-text">
            <h1 class="cart-heading">Giỏ Hàng Của Bạn</h1>
            <p>Kiểm tra lại các sản phẩm đã chọn và hoàn tất đơn hàng</p>
        </div>
        <nav class="cart-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('customer.dashboard') }}"><i class="fa-solid fa-house"></i> Trang chủ</a>
            <span>›</span>
            <strong>Giỏ hàng</strong>
        </nav>
    </header>

    @if(session('error'))
        <div class="alert-cart error"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert-cart success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif

    @if(empty($gioHang) && empty($dsBoHoa))
    {{-- EMPTY STATE --}}
    <div class="cart-card">
        <div class="cart-empty">
            <div class="cart-empty-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <h3>Giỏ hàng của bạn đang trống</h3>
            <p>Hãy chọn những bó hoa hoặc hoa tươi yêu thích và thêm vào giỏ nhé!</p>
            <div class="cart-empty-actions">
                <a href="{{ route('customer.bo-hoa.he-thong') }}" class="btn-shop">💐 Khám phá bó hoa</a>
                <a href="{{ route('customer.dashboard') }}" class="btn-shop btn-shop--ghost">🌸 Mua hoa tươi</a>
            </div>
        </div>
    </div>
    @else

    <div class="cart-layout">

        {{-- ===================== CỘT TRÁI ===================== --}}
        <div class="cart-main">

            {{-- BÓ HOA TRONG GIỎ --}}
            @if(!empty($dsBoHoa))
            <section class="cart-card" aria-labelledby="cartBouquetTitle">
                <div class="cart-card-head">
                    <h2 id="cartBouquetTitle"><span class="cart-title-icon">💐</span> Bó Hoa Đã Chọn ({{ count($dsBoHoa) }})</h2>
                    <a href="{{ route('customer.bo-hoa.he-thong') }}" class="cart-link-add">
                        <i class="fa-solid fa-circle-plus"></i> Thêm bó hoa khác
                    </a>
                </div>

                <div class="cart-bouquet-list">
                    @foreach($dsBoHoa as $bKey => $bItem)
                    <article class="cart-bouquet-item" id="bouquet-card-{{ $bKey }}">
                        <img class="cart-bouquet-img"
                             src="{{ \App\Http\Controllers\Admin\BoHoaController::anhMau($bItem['mau']) }}"
                             alt="{{ $bItem['mau']->ten_mau }}"
                             onerror="this.style.visibility='hidden'">

                        <div class="cart-bouquet-info">
                            <div class="cart-product-name">{{ $bItem['mau']->ten_mau }}</div>
                            <div class="cart-bouquet-meta">
                                <span>Size: <b>{{ $bItem['mau']->size }}</b></span>
                                <i></i>
                                <span>Kiểu dáng: <b>{{ \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$bItem['mau']->kieu_bo_hoa] ?? $bItem['mau']->kieu_bo_hoa }}</b></span>
                                <i></i>
                                <span>Số lượng: <b class="is-pink">{{ $bItem['so_luong'] }} bó</b></span>
                            </div>
                            @if($bItem['thiep'])
                                <div class="cart-bouquet-note">
                                    💌 Kèm thiệp: <strong>{{ $bItem['thiep']->ten_san_pham }}</strong>
                                    (+{{ number_format($bItem['thiep']->gia_ban_hien_tai, 0, ',', '.') }}₫)
                                </div>
                            @endif
                            @if($bItem['loi_nhan'])
                                <div class="cart-bouquet-note is-quote">📝 Lời nhắn: "{{ $bItem['loi_nhan'] }}"</div>
                            @endif
                        </div>

                        <div class="cart-bouquet-price">
                            <strong>{{ number_format($bItem['tong'], 0, ',', '.') }}₫</strong>
                            @if($bItem['so_luong'] > 1)
                                <small>{{ number_format($bItem['gia_mot_bo'], 0, ',', '.') }}₫ / bó</small>
                            @endif
                        </div>

                        <button type="button" class="cart-trash-btn" onclick="removeBouquet('{{ $bKey }}')" title="Xóa bó hoa này" aria-label="Xóa bó hoa này">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </article>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- SẢN PHẨM ĐƠN LẺ TRONG GIỎ --}}
            @if(!empty($gioHang))
            <section class="cart-card" aria-labelledby="cartItemsTitle">
                <div class="cart-card-head">
                    <h2 id="cartItemsTitle"><span class="cart-title-icon">🌸</span> Hoa &amp; Sản Phẩm Đơn Lẻ ({{ count($gioHang) }})</h2>
                    <a href="{{ route('customer.dashboard') }}" class="cart-link-add">
                        <i class="fa-solid fa-circle-plus"></i> Thêm sản phẩm khác
                    </a>
                </div>

                {{-- Header bảng --}}
                <div class="cart-items-head">
                    <label class="cart-check">
                        <input type="checkbox" id="selectAll" title="Chọn tất cả">
                    </label>
                    <span>Sản phẩm</span>
                    <span>Giá</span>
                    <span>Số lượng</span>
                    <span class="is-right">Tạm tính</span>
                    <span></span>
                </div>

                <div class="cart-items" id="cartTableBody">
                    @foreach($gioHang as $key => $item)
                    <div class="cart-item" id="row-{{ $key }}">
                        <label class="cart-check">
                            <input type="checkbox" class="select-item" data-key="{{ $key }}" id="sel-{{ $key }}">
                        </label>

                        <div class="cart-product-cell">
                            @if(!empty($item['hinh_anh']))
                                <img src="{{ asset($item['hinh_anh']) }}"
                                     alt="{{ $item['ten_san_pham'] ?? '' }}"
                                     class="cart-product-img"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <div class="cart-product-placeholder" style="display:none;">🌸</div>
                            @else
                                <div class="cart-product-placeholder">🌸</div>
                            @endif
                            <div class="cart-product-text">
                                <div class="cart-product-name">{{ $item['ten_san_pham'] }}</div>
                                <div class="cart-product-lot">Sản phẩm: {{ $item['so_luong_con_lai'] }}</div>
                            </div>
                        </div>

                        <div class="cart-price" data-label="Giá">{{ number_format($item['gia_ban'], 0, ',', '.') }}₫</div>

                        <div class="cart-qty-wrap">
                            <button class="cart-qty-btn" aria-label="Giảm số lượng"
                                onclick="changeQty('{{ $key }}', {{ $item['so_luong'] - 1 }}, {{ $item['so_luong_con_lai'] }})">−</button>
                            <input class="cart-qty-input"
                                   type="number"
                                   value="{{ $item['so_luong'] }}"
                                   min="1"
                                   max="{{ $item['so_luong_con_lai'] }}"
                                   onchange="changeQty('{{ $key }}', parseInt(this.value), {{ $item['so_luong_con_lai'] }})"
                                   id="qty-{{ $key }}">
                            <button class="cart-qty-btn" aria-label="Tăng số lượng"
                                onclick="changeQty('{{ $key }}', {{ $item['so_luong'] + 1 }}, {{ $item['so_luong_con_lai'] }})">+</button>
                        </div>

                        <div class="cart-subtotal" id="sub-{{ $key }}">
                            {{ number_format($item['gia_ban'] * $item['so_luong'], 0, ',', '.') }}₫
                        </div>

                        <button class="cart-trash-btn" onclick="removeItem('{{ $key }}')" title="Xóa" aria-label="Xóa sản phẩm">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>
                    @endforeach
                </div>

                <div class="cart-card-foot">
                    <a href="{{ route('customer.dashboard') }}" class="cart-link-back">← Tiếp tục xem sản phẩm</a>
                </div>
            </section>
            @endif
        </div>

        {{-- ===================== CỘT PHẢI ===================== --}}
        <aside class="cart-side">

            {{-- Tóm tắt bó hoa --}}
            @if(!empty($dsBoHoa))
            <section class="cart-summary-panel" aria-labelledby="cartBouquetSummary">
                <h2 id="cartBouquetSummary"><span class="cart-title-icon is-pink"><i class="fa-solid fa-file-invoice"></i></span> Tóm Tắt Bó Hoa</h2>

                <div class="summary-box">
                    <div class="summary-row">
                        <span>Tạm tính ({{ $tongSoBoHoa }} bó hoa)</span>
                        <span class="val">{{ number_format($tongTienBoHoa, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="summary-row total">
                        <span><strong>Tổng tiền bó hoa</strong></span>
                        <span class="val">{{ number_format($tongTienBoHoa, 0, ',', '.') }}₫</span>
                    </div>
                </div>

                <a href="{{ route('customer.thanh-toan', ['type' => 'bo_hoa']) }}" class="btn-checkout">
                    <i class="fa-regular fa-credit-card"></i> Thanh Toán Bó Hoa Này →
                </a>
            </section>
            @endif

            {{-- Tóm tắt sản phẩm đơn lẻ --}}
            @if(!empty($gioHang))
            <section class="cart-summary-panel" aria-labelledby="cartSummaryTitle">
                <h2 id="cartSummaryTitle"><span class="cart-title-icon is-pink"><i class="fa-solid fa-file-invoice"></i></span> Tổng Cộng Giỏ Hàng</h2>

                <div class="summary-box">
                    <div class="summary-row">
                        <span>Đã chọn</span>
                        <span class="val" id="selectedCount">0</span>
                    </div>

                    <div class="summary-row">
                        <span>Tạm tính</span>
                        <span class="val" id="summarySubtotal">
                            {{ number_format(collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']), 0, ',', '.') }}₫
                        </span>
                    </div>
                    <div class="summary-row">
                        <span>Vận chuyển</span>
                        <span class="ship-free">Miễn phí</span>
                    </div>

                    {{-- Điểm giảm giá --}}
                    @auth
                    @if($user && $user->khachHang)
                    <div class="points-section">
                        <div class="points-header">
                            <span class="points-label">🎁 Dùng điểm tích lũy</span>
                            <span class="points-avail">Bạn có: <strong>{{ number_format($user->khachHang->diem_tich_luy) }}</strong> điểm</span>
                        </div>

                        <div id="pointsInputArea">
                            <div class="points-input-row">
                                <input class="points-input" type="number" id="pointsInput"
                                       placeholder="Nhập số điểm muốn dùng"
                                       min="0" max="{{ $user->khachHang->diem_tich_luy }}">
                                <button class="points-apply-btn" onclick="applyPoints()">Áp dụng</button>
                            </div>
                            <div class="points-note">1 điểm = 1,000₫ &nbsp;|&nbsp; Tối đa {{ number_format($user->khachHang->diem_tich_luy) }} điểm</div>
                        </div>

                        <div id="pointsApplied" style="display:none;">
                            <div class="points-applied">
                                ✅ Đang dùng <strong id="diemDung">0</strong> điểm (−<span id="giamHienThi">0</span>₫)
                                <button class="points-cancel-btn" onclick="cancelPoints()">✕</button>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endauth

                    <div class="summary-row" id="discountRow" style="{{ $diemSuDung > 0 ? '' : 'display:none;' }}">
                        <span>Giảm giá (điểm)</span>
                        <span class="discount-val" id="summaryDiscount">
                            −{{ number_format($diemSuDung * 1000, 0, ',', '.') }}₫
                        </span>
                    </div>

                    <div class="summary-row total">
                        <span><strong>Tổng</strong></span>
                        <span class="val" id="summaryTotal">
                            @php
                                $tongTienGoc = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
                                $giamGia     = $diemSuDung * 1000;
                                $tongCuoi    = max(0, $tongTienGoc - $giamGia);
                            @endphp
                            {{ number_format($tongCuoi, 0, ',', '.') }}₫
                        </span>
                    </div>
                </div>

                @auth
                    {{-- Ban đầu disabled vì chưa chọn sản phẩm nào --}}
                    <button class="btn-checkout" id="btnProceed" onclick="proceedToCheckout()" disabled
                            title="Vui lòng chọn ít nhất 1 sản phẩm để thanh toán">
                        <i class="fa-regular fa-credit-card"></i> Tiến Hành Thanh Toán
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn-checkout">
                        <i class="fa-solid fa-key"></i> Đăng nhập để thanh toán
                    </a>
                @endauth

                <a href="{{ route('customer.dashboard') }}" class="btn-continue">
                    ← Tiếp tục mua sắm
                </a>
            </section>
            @endif

            {{-- Cam kết --}}
            <ul class="cart-trust">
                <li><i class="fa-solid fa-truck-fast"></i><span>Giao hàng<br>nhanh chóng</span></li>
                <li><i class="fa-solid fa-shield-heart"></i><span>Sản phẩm<br>tươi mới</span></li>
                <li><i class="fa-solid fa-gift"></i><span>Hỗ trợ<br>tận tình</span></li>
            </ul>
        </aside>

    </div>
    @endif

</div>
@endsection

@section('scripts')
<script>
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const fmt  = v => new Intl.NumberFormat('vi-VN').format(v) + '₫';
    const CHECKOUT_POST_URL = '{{ route("customer.gio-hang.checkout") }}';
    const CHECKOUT_PAGE_URL = '{{ route("customer.thanh-toan") }}';

    const BTN_CHECKOUT_HTML = '<i class="fa-regular fa-credit-card"></i> Tiến Hành Thanh Toán';
    const BTN_LOADING_HTML  = '<i class="fa-solid fa-spinner fa-spin"></i> Đang chuyển...';

    let diemSuDung = {{ $diemSuDung }};

    const prices = {
        @foreach($gioHang as $key => $item)
        '{{ $key }}': {{ $item['gia_ban'] }},
        @endforeach
    };
    let quantities = {
        @foreach($gioHang as $key => $item)
        '{{ $key }}': {{ $item['so_luong'] }},
        @endforeach
    };
    let selectedItems = {};
    @foreach($gioHang as $key => $item)
    selectedItems['{{ $key }}'] = false;
    @endforeach

    // ✅ Bật/tắt nút thanh toán dựa trên số item đang chọn
    function updateCheckoutButtonState() {
        const btn = document.getElementById('btnProceed');
        if (!btn) return;

        const soChon = Object.values(selectedItems).filter(Boolean).length;
        btn.disabled = soChon === 0;
        btn.title = soChon === 0
            ? 'Vui lòng chọn ít nhất 1 sản phẩm để thanh toán'
            : 'Tiến hành thanh toán ' + soChon + ' sản phẩm';
    }

    function recalcSummary() {
        let subtotal = 0;
        let count = 0;
        Object.keys(quantities).forEach(k => {
            if (selectedItems[k]) {
                subtotal += (prices[k] || 0) * (quantities[k] || 0);
                count += 1;
            }
        });
        const giam  = diemSuDung * 1000;
        const total = Math.max(0, subtotal - giam);

        document.getElementById('summarySubtotal').textContent = fmt(subtotal);
        document.getElementById('summaryTotal').textContent    = fmt(total);
        document.getElementById('selectedCount').textContent   = count;

        const discRow = document.getElementById('discountRow');
        if (discRow) {
            discRow.style.display = diemSuDung > 0 ? '' : 'none';
            document.getElementById('summaryDiscount').textContent = '−' + fmt(giam);
        }

        // ✅ Cập nhật trạng thái nút thanh toán
        updateCheckoutButtonState();
    }

    async function changeQty(key, newQty, maxQty) {
        newQty = Math.max(1, Math.min(newQty, maxQty));

        const res  = await fetch('/customer/gio-hang/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ key, so_luong: newQty }),
        });
        const data = await res.json();
        if (data.success) {
            quantities[key] = newQty;
            document.getElementById('qty-' + key).value = newQty;
            document.getElementById('sub-' + key).textContent =
                fmt((prices[key] || 0) * newQty);
            recalcSummary();
            updateCartBadge(data.so_san_pham);
            diemSuDung = 0;
            resetPointsUI();
        }
    }

    async function removeItem(key) {
        const row = document.getElementById('row-' + key);
        row.style.opacity = '.4';

        const res  = await fetch('/customer/gio-hang/remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ key }),
        });
        const data = await res.json();
        if (data.success) {
            row.remove();
            delete quantities[key];
            delete prices[key];
            delete selectedItems[key];   // ✅ Xoá khỏi danh sách đã chọn
            recalcSummary();
            updateCartBadge(data.so_san_pham);

            if (Object.keys(quantities).length === 0) {
                location.reload();
            }
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('select-item')) {
            const key = e.target.dataset.key;
            selectedItems[key] = e.target.checked;
            const all = Array.from(document.querySelectorAll('.select-item'));
            const selAll = document.getElementById('selectAll');
            if (selAll) selAll.checked = all.length > 0 && all.every(i => i.checked);
            recalcSummary();
        }
        if (e.target && e.target.id === 'selectAll') {
            const checked = e.target.checked;
            document.querySelectorAll('.select-item').forEach(i => {
                i.checked = checked;
                selectedItems[i.dataset.key] = checked;
            });
            recalcSummary();
        }
    });

    async function proceedToCheckout() {
        const keys = Object.keys(selectedItems).filter(k => selectedItems[k]);
        if (!keys.length) {
            showToast('❌ Vui lòng chọn ít nhất một sản phẩm để thanh toán.');
            return;
        }

        const btn = document.getElementById('btnProceed');
        if (btn) { btn.disabled = true; btn.innerHTML = BTN_LOADING_HTML; }

        try {
            const res = await fetch(CHECKOUT_POST_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ selected_items: keys })
            });

            const data = await res.json();
            if (data.success) {
                window.location.href = CHECKOUT_PAGE_URL + '?type=san_pham';
            } else {
                showToast('❌ ' + (data.message || 'Không thể chuyển đến trang thanh toán.'));
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = BTN_CHECKOUT_HTML;
                    updateCheckoutButtonState();
                }
            }
        } catch (err) {
            showToast('❌ Không thể kết nối, vui lòng thử lại.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = BTN_CHECKOUT_HTML;
                updateCheckoutButtonState();
            }
        }
    }

    async function removeBouquet(key) {
        if (!confirm('Bạn có chắc muốn xóa bó hoa này khỏi giỏ hàng?')) return;
        const card = document.getElementById('bouquet-card-' + key);
        if (card) card.style.opacity = '.4';

        try {
            const res = await fetch('{{ route("customer.bo-hoa.gio.remove") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ key })
            });
            const data = await res.json();
            if (data.success) {
                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(data.so_san_pham);
                }
                location.reload();
            } else {
                alert(data.message || 'Không thể xóa bó hoa.');
                if (card) card.style.opacity = '1';
            }
        } catch (e) {
            console.error(e);
            alert('Có lỗi xảy ra khi xóa bó hoa.');
            if (card) card.style.opacity = '1';
        }
    }

    async function applyPoints() {
        const diem = parseInt(document.getElementById('pointsInput').value) || 0;
        if (diem <= 0) return;

        const res  = await fetch('/customer/gio-hang/apply-points', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ diem }),
        });
        const data = await res.json();
        if (data.success) {
            diemSuDung = data.diem_su_dung;
            document.getElementById('pointsInputArea').style.display = 'none';
            document.getElementById('pointsApplied').style.display   = '';
            document.getElementById('diemDung').textContent           = new Intl.NumberFormat('vi-VN').format(diemSuDung);
            document.getElementById('giamHienThi').textContent        = new Intl.NumberFormat('vi-VN').format(data.giam_gia);
            recalcSummary();
            showToast('✅ Áp dụng ' + diemSuDung + ' điểm thành công!');
        }
    }

    function cancelPoints() {
        diemSuDung = 0;
        resetPointsUI();
        recalcSummary();

        fetch('/customer/gio-hang/apply-points', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ diem: 0 }),
        });
    }

    function resetPointsUI() {
        const inp = document.getElementById('pointsInputArea');
        const app = document.getElementById('pointsApplied');
        if (inp) inp.style.display = '';
        if (app) app.style.display = 'none';
        const pi = document.getElementById('pointsInput');
        if (pi) pi.value = '';
    }

    @if($diemSuDung > 0)
    window.addEventListener('DOMContentLoaded', () => {
        const area = document.getElementById('pointsInputArea');
        const appl = document.getElementById('pointsApplied');
        if (area) area.style.display = 'none';
        if (appl) {
            appl.style.display = '';
            document.getElementById('diemDung').textContent    = '{{ number_format($diemSuDung) }}';
            document.getElementById('giamHienThi').textContent = '{{ number_format($diemSuDung * 1000) }}';
        }
    });
    @endif

    // ✅ Khởi tạo trạng thái nút khi vào trang (chưa chọn gì → disabled)
    updateCheckoutButtonState();
</script>
@endsection