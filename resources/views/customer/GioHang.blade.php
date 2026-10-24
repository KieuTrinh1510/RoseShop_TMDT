@extends('customer.layouts.customer')

@section('title', '🛒 Giỏ Hàng – Hoa Tươi Shop')
@section('page-id', 'gio-hang')

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/GioHang.css') }}">
@endsection

@section('content')
<div class="cart-page">

    {{-- BREADCRUMB --}}
    <div class="cart-breadcrumb">
        <a href="{{ route('customer.dashboard') }}" class="breadcrumb-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Trang chủ</span>
        </a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-item active">Giỏ hàng</span>
    </div>

    {{-- HEADER BAR --}}
    <div class="cart-header-row">
        <div class="cart-header-left">
            <h1 class="cart-main-title">Giỏ hàng của bạn</h1>
            <p class="cart-sub-title">Kiểm tra lại sản phẩm trong giỏ hàng và tiến hành đặt hàng.</p>
        </div>
        @if(!empty($gioHang) || !empty($dsBoHoa))
        <div class="cart-header-right">
            <button type="button" class="btn-clear-cart" onclick="clearEntireCart()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>
                </svg>
                <span>Xóa tất cả sản phẩm</span>
            </button>
        </div>
        @endif
    </div>

    @if(session('error'))
        <div class="alert-cart error">❌ {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert-cart success">✅ {{ session('success') }}</div>
    @endif

    @if(empty($gioHang) && empty($dsBoHoa))
    {{-- EMPTY STATE --}}
    <div class="cart-table-wrap">
        <div class="cart-empty">
            <div class="cart-empty-icon">🛒</div>
            <h3>Giỏ hàng của bạn đang trống</h3>
            <p>Hãy chọn những bó hoa hoặc hoa tươi yêu thích và thêm vào giỏ nhé!</p>
            <div style="display:flex; justify-content:center; gap:12px; margin-top:16px;">
                <a href="{{ route('customer.bo-hoa.he-thong') }}" class="btn-shop" style="background:#be185d;">💐 Khám phá bó hoa</a>
                <a href="{{ route('customer.dashboard') }}" class="btn-shop" style="background:#4b5563;">🌸 Mua hoa tươi</a>
            </div>
        </div>
    </div>
    @else

    {{-- MAIN CART LAYOUT (ĐỒNG NHẤT 1 BẢNG DUY NHẤT) --}}
    <div class="cart-layout">

        {{-- LEFT COLUMN: BẢNG SẢN PHẨM & BÓ HOA TỔNG HỢP --}}
        <div class="cart-table-wrap">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th style="width: 48px; text-align: center;">
                            <input type="checkbox" id="selectAll" class="custom-checkbox" title="Chọn tất cả">
                        </th>
                        <th style="min-width: 280px;">Sản phẩm</th>
                        <th style="width: 120px; text-align: right;">Đơn giá</th>
                        <th style="width: 140px; text-align: center;">Số lượng</th>
                        <th style="width: 130px; text-align: right;">Thành tiền</th>
                        <th style="width: 60px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="cartTableBody">

                    {{-- 1. DANH SÁCH BÓ HOA (GẮN BADGE BÓ HOA THIẾT KẾ) --}}
                    @if(!empty($dsBoHoa))
                        @foreach($dsBoHoa as $bKey => $bItem)
                        <tr id="bouquet-row-{{ $bKey }}" class="cart-item-row bouquet-row">
                            <td style="text-align: center;">
                                <input type="checkbox" class="custom-checkbox select-item"
                                       data-type="bo_hoa"
                                       data-key="{{ $bKey }}"
                                       id="sel-b-{{ $bKey }}">
                            </td>
                            <td>
                                <div class="cart-product-cell">
                                    <img src="{{ \App\Http\Controllers\Admin\BoHoaController::anhMau($bItem['mau']) }}"
                                         alt="{{ $bItem['mau']->ten_mau }}"
                                         class="cart-product-img"
                                         onerror="this.src='{{ asset('img/placeholder-flower.svg') }}';">
                                    <div class="cart-product-info">
                                        <div class="cart-product-name">{{ $bItem['mau']->ten_mau }}</div>
                                        <div class="cart-product-meta">
                                            Size: <strong>{{ $bItem['mau']->size }}</strong> ·
                                            Kiểu: <strong>{{ \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$bItem['mau']->kieu_bo_hoa] ?? $bItem['mau']->kieu_bo_hoa }}</strong>
                                        </div>
                                        <div class="cart-badge-wrap">
                                            <span class="cart-badge cart-badge-bouquet">💐 Bó hoa thiết kế</span>
                                            @if($bItem['thiep'])
                                                <span class="cart-badge cart-badge-gift">💌 Kèm thiệp: {{ $bItem['thiep']->ten_san_pham }}</span>
                                            @endif
                                        </div>
                                        @if($bItem['loi_nhan'])
                                            <div class="cart-bouquet-note">📝 Lời nhắn: "{{ $bItem['loi_nhan'] }}"</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cart-price" style="text-align: right;">
                                {{ number_format($bItem['gia_mot_bo'], 0, ',', '.') }}₫
                            </td>
                            <td style="text-align: center;">
                                <div class="cart-qty-badge">{{ $bItem['so_luong'] }} bó</div>
                            </td>
                            <td class="cart-subtotal" id="sub-b-{{ $bKey }}" style="text-align: right;">
                                {{ number_format($bItem['tong'], 0, ',', '.') }}₫
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="cart-remove-btn" onclick="removeBouquet('{{ $bKey }}')" title="Xóa bó hoa này">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    @endif

                    {{-- 2. DANH SÁCH HOA TƯƠI & SẢN PHẨM ĐƠN LẺ --}}
                    @if(!empty($gioHang))
                        @foreach($gioHang as $key => $item)
                        <tr id="row-{{ $key }}" class="cart-item-row single-product-row">
                            <td style="text-align: center;">
                                <input type="checkbox" class="custom-checkbox select-item"
                                       data-type="san_pham"
                                       data-key="{{ $key }}"
                                       id="sel-{{ $key }}">
                            </td>
                            <td>
                                <div class="cart-product-cell">
                                    @if(!empty($item['hinh_anh']))
                                        <img src="{{ asset($item['hinh_anh']) }}"
                                             alt="{{ $item['ten_san_pham'] ?? '' }}"
                                             class="cart-product-img"
                                             onerror="this.src='{{ asset('img/placeholder-flower.svg') }}';">
                                    @else
                                        <img src="{{ asset('img/placeholder-flower.svg') }}"
                                             alt="{{ $item['ten_san_pham'] ?? '' }}"
                                             class="cart-product-img">
                                    @endif
                                    <div class="cart-product-info">
                                        <div class="cart-product-name">{{ $item['ten_san_pham'] }}</div>
                                        <div class="cart-product-meta">
                                            Sản phẩm còn lại: <strong>{{ $item['so_luong_con_lai'] }}</strong>
                                        </div>
                                        <div class="cart-badge-wrap">
                                            <span class="cart-badge cart-badge-fresh">🌿 Hoa tươi 100%</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="cart-price" style="text-align: right;">
                                {{ number_format($item['gia_ban'], 0, ',', '.') }}₫
                            </td>
                            <td style="text-align: center;">
                                <div class="cart-qty-wrap">
                                    <button type="button" class="cart-qty-btn"
                                        onclick="changeQty('{{ $key }}', {{ $item['so_luong'] - 1 }}, {{ $item['so_luong_con_lai'] }})">−</button>
                                    <input class="cart-qty-input"
                                           type="number"
                                           value="{{ $item['so_luong'] }}"
                                           min="1"
                                           max="{{ $item['so_luong_con_lai'] }}"
                                           onchange="changeQty('{{ $key }}', parseInt(this.value), {{ $item['so_luong_con_lai'] }})"
                                           id="qty-{{ $key }}">
                                    <button type="button" class="cart-qty-btn"
                                        onclick="changeQty('{{ $key }}', {{ $item['so_luong'] + 1 }}, {{ $item['so_luong_con_lai'] }})">+</button>
                                </div>
                            </td>
                            <td class="cart-subtotal" id="sub-{{ $key }}" style="text-align: right;">
                                {{ number_format($item['gia_ban'] * $item['so_luong'], 0, ',', '.') }}₫
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="cart-remove-btn" onclick="removeItem('{{ $key }}')" title="Xóa sản phẩm này">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    @endif

                </tbody>
            </table>

            {{-- FOOTER BẢNG: TIẾP TỤC MUA SẮM & ĐÃ CHỌN X SẢN PHẨM --}}
            <div class="cart-table-footer">
                <a href="{{ route('customer.dashboard') }}" class="btn-continue-shopping">
                    ← Tiếp tục mua sắm
                </a>
                <div class="cart-selection-counter">
                    Đã chọn <strong id="selectedItemsText">0</strong> sản phẩm
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: TÓM TẮT GIỎ HÀNG CHUẨN DESIGN SYSTEM --}}
        <div class="cart-summary-panel">
            <h2 class="summary-panel-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Tóm tắt giỏ hàng</span>
            </h2>

            <div class="summary-row">
                <span class="lbl">Tạm tính (<span id="summaryCountLabel">0 sản phẩm</span>)</span>
                <span class="val" id="summarySubtotal">0₫</span>
            </div>
            <div class="summary-row">
                <span class="lbl">Phí vận chuyển</span>
                <span class="val ship-free">Miễn phí</span>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-row total-row">
                <span class="lbl-total">Tổng cộng</span>
                <span class="val-total" id="summaryTotal">0₫</span>
            </div>

            {{-- DÙNG ĐIỂM TÍCH LŨY ACCORDION --}}
            @auth
            @if($user && $user->khachHang)
            <div class="points-card-box">
                <div class="points-card-header">
                    <div class="points-card-title">
                        <span class="icon">🎁</span>
                        <div>
                            <strong>Dùng điểm tích lũy</strong>
                            <div class="points-avail-text">
                                Bạn có {{ number_format($user->khachHang->diem_tich_luy) }} điểm (1 điểm = 1.000₫)
                            </div>
                        </div>
                    </div>
                </div>

                <div id="pointsInputArea">
                    <div class="points-input-group">
                        <input class="points-field" type="number" id="pointsInput"
                               placeholder="Nhập số điểm muốn dùng"
                               min="0" max="{{ $user->khachHang->diem_tich_luy }}">
                        <button type="button" class="btn-points-apply" onclick="applyPoints()">Áp dụng</button>
                    </div>
                </div>

                <div id="pointsApplied" style="display:none;">
                    <div class="points-applied-alert">
                        <span>✅ Đang dùng <strong id="diemDung">0</strong> điểm (−<span id="giamHienThi">0</span>₫)</span>
                        <button type="button" class="btn-points-cancel" onclick="cancelPoints()" title="Hủy dùng điểm">✕</button>
                    </div>
                </div>
            </div>
            @endif
            @endauth

            <div class="summary-row" id="discountRow" style="{{ $diemSuDung > 0 ? '' : 'display:none;' }}">
                <span class="lbl">Giảm giá điểm</span>
                <span class="discount-val" id="summaryDiscount">
                    −{{ number_format($diemSuDung * 1000, 0, ',', '.') }}₫
                </span>
            </div>

            {{-- NÚT TIẾN HÀNH THANH TOÁN --}}
            @auth
                <button type="button" class="btn-checkout-primary" id="btnProceed" onclick="proceedToCheckout()" disabled
                        title="Vui lòng chọn ít nhất 1 sản phẩm để thanh toán">
                    <span>Tiến hành thanh toán</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            @else
                <a href="{{ route('login') }}" class="btn-checkout-primary">
                    <span>Đăng nhập để thanh toán</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            @endauth

            {{-- TRUST BADGE: BẢO MẬT TUYỆT ĐỐI --}}
            <div class="trust-badge-box">
                <div class="trust-badge-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#EC3D8F" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div class="trust-badge-content">
                    <div class="trust-badge-title">Thanh toán an toàn</div>
                    <div class="trust-badge-desc">Thông tin của bạn được bảo mật tuyệt đối.</div>
                </div>
            </div>

        </div>

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

    let diemSuDung = {{ $diemSuDung }};

    // Bảng giá và số lượng sản phẩm đơn lẻ
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

    // Bảng giá bó hoa
    const bouquetTotals = {
        @if(!empty($dsBoHoa))
            @foreach($dsBoHoa as $bKey => $bItem)
            '{{ $bKey }}': {{ $bItem['tong'] }},
            @endforeach
        @endif
    };

    let selectedItems = {};
    @foreach($gioHang as $key => $item)
    selectedItems['{{ $key }}'] = false;
    @endforeach

    let selectedBouquets = {};
    @if(!empty($dsBoHoa))
        @foreach($dsBoHoa as $bKey => $bItem)
        selectedBouquets['{{ $bKey }}'] = false;
        @endforeach
    @endif

    function updateCheckoutButtonState() {
        const btn = document.getElementById('btnProceed');
        if (!btn) return;

        const countSP = Object.values(selectedItems).filter(Boolean).length;
        const countBH = Object.values(selectedBouquets).filter(Boolean).length;
        const totalSelected = countSP + countBH;

        btn.disabled = totalSelected === 0;
        btn.title = totalSelected === 0
            ? 'Vui lòng chọn ít nhất 1 sản phẩm để thanh toán'
            : 'Tiến hành thanh toán (' + totalSelected + ' món)';
    }

    function recalcSummary() {
        let subtotal = 0;
        let count = 0;

        // Tính sản phẩm đơn lẻ đã chọn
        Object.keys(quantities).forEach(k => {
            if (selectedItems[k]) {
                subtotal += (prices[k] || 0) * (quantities[k] || 0);
                count += 1;
            }
        });

        // Tính bó hoa đã chọn
        Object.keys(bouquetTotals).forEach(bKey => {
            if (selectedBouquets[bKey]) {
                subtotal += (bouquetTotals[bKey] || 0);
                count += 1;
            }
        });

        const giam  = diemSuDung * 1000;
        const total = Math.max(0, subtotal - giam);

        const subEl = document.getElementById('summarySubtotal');
        const totEl = document.getElementById('summaryTotal');
        const cntLbl = document.getElementById('summaryCountLabel');
        const selText = document.getElementById('selectedItemsText');

        if (subEl) subEl.textContent = fmt(subtotal);
        if (totEl) totEl.textContent = fmt(total);
        if (cntLbl) cntLbl.textContent = count + ' sản phẩm';
        if (selText) selText.textContent = count;

        const discRow = document.getElementById('discountRow');
        if (discRow) {
            discRow.style.display = diemSuDung > 0 ? '' : 'none';
            document.getElementById('summaryDiscount').textContent = '−' + fmt(giam);
        }

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
            document.getElementById('sub-' + key).textContent = fmt((prices[key] || 0) * newQty);
            recalcSummary();
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(data.so_san_pham);
            }
            diemSuDung = 0;
            resetPointsUI();
        }
    }

    async function removeItem(key) {
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) return;
        const row = document.getElementById('row-' + key);
        if (row) row.style.opacity = '.4';

        const res  = await fetch('/customer/gio-hang/remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ key }),
        });
        const data = await res.json();
        if (data.success) {
            if (row) row.remove();
            delete quantities[key];
            delete prices[key];
            delete selectedItems[key];
            recalcSummary();
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(data.so_san_pham);
            }

            if (Object.keys(quantities).length === 0 && Object.keys(bouquetTotals).length === 0) {
                location.reload();
            }
        }
    }

    async function removeBouquet(key) {
        if (!confirm('Bạn có chắc muốn xóa bó hoa này khỏi giỏ hàng?')) return;
        const row = document.getElementById('bouquet-row-' + key);
        if (row) row.style.opacity = '.4';

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
                if (row) row.remove();
                delete bouquetTotals[key];
                delete selectedBouquets[key];
                recalcSummary();
                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(data.so_san_pham);
                }
                if (Object.keys(quantities).length === 0 && Object.keys(bouquetTotals).length === 0) {
                    location.reload();
                }
            } else {
                alert(data.message || 'Không thể xóa bó hoa.');
                if (row) row.style.opacity = '1';
            }
        } catch (e) {
            console.error(e);
            alert('Có lỗi xảy ra khi xóa bó hoa.');
            if (row) row.style.opacity = '1';
        }
    }

    async function clearEntireCart() {
        if (!confirm('Bạn có chắc chắn muốn xóa TOÀN BỘ sản phẩm và bó hoa trong giỏ hàng?')) return;

        // Xóa lần lượt tất cả items sản phẩm đơn lẻ
        const keysSP = Object.keys(quantities);
        for (const k of keysSP) {
            await fetch('/customer/gio-hang/remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ key: k }),
            });
        }

        // Xóa tất cả bó hoa
        const keysBH = Object.keys(bouquetTotals);
        for (const b of keysBH) {
            await fetch('{{ route("customer.bo-hoa.gio.remove") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ key: b }),
            });
        }

        location.reload();
    }

    // CHECKBOX LISTENER
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('select-item')) {
            const key = e.target.dataset.key;
            const type = e.target.dataset.type;

            if (type === 'bo_hoa') {
                selectedBouquets[key] = e.target.checked;
            } else {
                selectedItems[key] = e.target.checked;
            }

            const allItems = Array.from(document.querySelectorAll('.select-item'));
            const selAll = document.getElementById('selectAll');
            if (selAll) selAll.checked = allItems.length > 0 && allItems.every(i => i.checked);

            recalcSummary();
        }

        if (e.target && e.target.id === 'selectAll') {
            const checked = e.target.checked;
            document.querySelectorAll('.select-item').forEach(i => {
                i.checked = checked;
                const k = i.dataset.key;
                const t = i.dataset.type;
                if (t === 'bo_hoa') {
                    selectedBouquets[k] = checked;
                } else {
                    selectedItems[k] = checked;
                }
            });
            recalcSummary();
        }
    });

    async function proceedToCheckout() {
        const keysSP = Object.keys(selectedItems).filter(k => selectedItems[k]);
        const keysBH = Object.keys(selectedBouquets).filter(k => selectedBouquets[k]);

        if (!keysSP.length && !keysBH.length) {
            alert('Vui lòng chọn ít nhất một sản phẩm hoặc bó hoa để thanh toán.');
            return;
        }

        const btn = document.getElementById('btnProceed');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span>⏳ Đang xử lý...</span>'; }

        try {
            // Trường hợp 1: Chọn sản phẩm đơn lẻ (hoặc cả 2, ưu tiên chuyển sản phẩm trước)
            if (keysSP.length > 0) {
                const res = await fetch(CHECKOUT_POST_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ selected_items: keysSP })
                });

                const data = await res.json();
                if (data.success) {
                    window.location.href = CHECKOUT_PAGE_URL + '?type=san_pham';
                    return;
                } else {
                    alert(data.message || 'Không thể chuyển đến trang thanh toán.');
                }
            } else if (keysBH.length > 0) {
                // Trường hợp 2: Chỉ chọn bó hoa
                window.location.href = CHECKOUT_PAGE_URL + '?type=bo_hoa';
                return;
            }
        } catch (err) {
            console.error(err);
            alert('Không thể kết nối máy chủ, vui lòng thử lại.');
        }

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span>Tiến hành thanh toán</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';
            updateCheckoutButtonState();
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
        } else {
            alert(data.message || 'Không thể áp dụng điểm.');
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

    // Tự động tick chọn tất cả item khi mới vào giỏ hàng
    window.addEventListener('DOMContentLoaded', () => {
        const selAll = document.getElementById('selectAll');
        if (selAll) {
            selAll.checked = true;
            document.querySelectorAll('.select-item').forEach(i => {
                i.checked = true;
                const k = i.dataset.key;
                const t = i.dataset.type;
                if (t === 'bo_hoa') selectedBouquets[k] = true;
                else selectedItems[k] = true;
            });
            recalcSummary();
        }
    });
</script>
@endsection