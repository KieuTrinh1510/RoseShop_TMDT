@extends('customer.layouts.customer')

@section('title',
    ($type ?? 'san-pham') === 'mau-bo-hoa'
        ? (($boHoaMau->ten_mau ?? 'Mẫu bó hoa') . ' – Hoa Tươi Shop 🌸')
        : ((($type ?? 'san-pham') === 'bo-hoa-tu-tao')
            ? (($preview['ten_bo_hoa'] ?? 'Bó hoa tự tạo') . ' – Hoa Tươi Shop 🌸')
            : (($sanPham->ten_san_pham ?? 'Sản phẩm') . ' – Hoa Tươi Shop 🌸')))

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/ChiTietSanPham.css') }}">
@endsection

@section('content')
@if(($type ?? 'san-pham') === 'mau-bo-hoa')
{{-- ═══════════════════════════════════════════════════════════
     NHÁNH 1: MẪU BÓ HOA HỆ THỐNG
═══════════════════════════════════════════════════════════ --}}
@php $style = \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$boHoaMau->kieu_bo_hoa] ?? $boHoaMau->kieu_bo_hoa; @endphp
<div class="detail-wrap">
    <div>
        <div class="gallery-main">
            <img src="{{ \App\Http\Controllers\Admin\BoHoaController::anhMau($boHoaMau) }}" alt="{{ $boHoaMau->ten_mau }}">
        </div>
    </div>
    <div class="info-panel">
        <div class="info-category-tag">💐 Mẫu bó hoa</div>
        <h1 class="info-title">{{ $boHoaMau->ten_mau }}</h1>

        <div class="meta-strip">
            <div class="meta-chip">Kiểu dáng: <strong>{{ $style }}</strong></div>
            <div class="meta-chip">Size: <strong>{{ $boHoaMau->size }}</strong></div>
        </div>

        <div class="price-table-wrap">
            <div class="price-table-label">💰 Giá bán</div>
            <p class="price-val">{{ number_format($quote['gia_moi_bo'], 0, ',', '.') }}₫ / bó</p>
        </div>

        <div class="info-desc">
            <div class="info-desc-label">Thành phần bó hoa</div>
            <ul>
                @foreach($boHoaMau->chiTietMauBoHoa->groupBy('vai_tro') as $vaiTro => $items)
                    <li>
                        <strong>{{ \App\Http\Controllers\Admin\BoHoaController::VAI_TRO[$vaiTro] ?? $vaiTro }}:</strong>
                        {{ $items->map(fn($i) => ($i->sanPham->ten_san_pham ?? 'Nguyên liệu').' × '.$i->so_luong)->join(', ') }}
                    </li>
                @endforeach
            </ul>
            @if($boHoaMau->mo_ta)
                <p style="margin-top:12px;">{{ $boHoaMau->mo_ta }}</p>
            @endif
        </div>

                        @auth
        <form method="POST" action="{{ route('customer.mau-bo-hoa.mua', $boHoaMau->ma_mau_bo_hoa) }}" class="action-row" id="formMuaMau">
            @csrf

            {{-- ✅ FIX: hidden field để route gio.add nhận được ma_mau_bo_hoa --}}
            <input type="hidden" name="ma_mau_bo_hoa" value="{{ $boHoaMau->ma_mau_bo_hoa }}">

            {{-- ✅ FIX: hiện lỗi validation/runtime khi bấm "Thêm vào giỏ" --}}
            @if(session('error'))
                <div style="padding:10px 14px;background:#fff0ef;color:#9e2924;border-radius:8px;margin-bottom:12px;font-size:14px;">
                    ❌ {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div style="padding:10px 14px;background:#fff0ef;color:#9e2924;border-radius:8px;margin-bottom:12px;font-size:14px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <label>Số lượng (tối đa 10 bó)
                <input type="number" name="so_luong" id="soLuongMau" min="1" max="10" value="{{ old('so_luong', 1) }}" required>
            </label>

            @if(isset($dsThiep) && $dsThiep->count() > 0)
            <label>Thêm thiệp (tùy chọn, cộng vào giá bó)
                <select name="ma_thiep" id="thiepSelectMau">
                    <option value="" data-gia="0">-- Không thêm thiệp --</option>
                    @foreach($dsThiep as $t)
                        <option value="{{ $t->ma_san_pham }}" data-gia="{{ $t->gia_ban_hien_tai }}"
                            {{ old('ma_thiep') == $t->ma_san_pham ? 'selected' : '' }}>
                            {{ $t->ten_san_pham }} — +{{ number_format($t->gia_ban_hien_tai, 0, ',', '.') }}₫
                        </option>
                    @endforeach
                </select>
            </label>
            @endif

            <label>Lời nhắn gửi kèm (không bắt buộc)
                <textarea name="loi_nhan" maxlength="2000" placeholder="Ví dụ: Chúc mừng sinh nhật em!">{{ old('loi_nhan') }}</textarea>
            </label>

            <div class="action-row" style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="btn-cart" style="flex:1;min-width:180px;"
                        formaction="{{ route('customer.bo-hoa.gio.add') }}">
                    🛒 Thêm vào giỏ
                </button>
                <button type="submit" class="btn-buy-now" style="flex:1;min-width:180px;">
                    ⚡ Mua ngay
                </button>
            </div>

            {{-- ✅ FIX: link vào giỏ bó hoa --}}
            <div style="margin-top:12px;text-align:center;">
                <a href="{{ route('customer.bo-hoa.gio.index') }}"
                   style="color:#8e3555;font-size:14px;font-weight:600;text-decoration:underline;">
                    🛒 Xem giỏ bó hoa của bạn ({{ count(session('gio_bo_hoa', [])) }})
                </a>
            </div>
        </form>
        @else
        <div class="login-notice">Vui lòng <a href="{{ route('login') }}">đăng nhập</a> để đặt bó hoa.</div>
        @endauth
    </div>
</div>

@elseif(($type ?? 'san-pham') === 'bo-hoa-tu-tao')
{{-- ═══════════════════════════════════════════════════════════
     NHÁNH 2: BÓ HOA TỰ TẠO (PREVIEW TỪ SESSION)
═══════════════════════════════════════════════════════════ --}}
<div class="detail-wrap">
    <div>
        <div class="gallery-main">
            <div class="gallery-placeholder">💐</div>
        </div>
    </div>
    <div class="info-panel">
        <div class="info-category-tag">💐 Bó hoa tự tạo</div>
        <h1 class="info-title">{{ $preview['ten_bo_hoa'] }}</h1>

        <div class="meta-strip">
            <div class="meta-chip">Kiểu dáng: <strong>{{ \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$preview['kieu_bo_hoa']] ?? $preview['kieu_bo_hoa'] }}</strong></div>
            <div class="meta-chip">Size: <strong>{{ $preview['size'] }}</strong></div>
            <div class="meta-chip">Số bó: <strong>{{ $preview['so_luong'] }}</strong></div>
        </div>

        <div class="price-table-wrap">
            <div class="price-table-label">💰 Giá tạm tính</div>
            <p class="price-val">{{ number_format($quote['gia_moi_bo'], 0, ',', '.') }}₫ / bó</p>
            <p style="font-size:13px;color:#725e67;margin-top:6px;">
                Nguyên liệu: {{ number_format($quote['tien_nguyen_lieu'], 0, ',', '.') }}₫
                · Phụ phí: {{ number_format($quote['phu_phi']['tong'], 0, ',', '.') }}₫
                · Tổng: <strong>{{ number_format($quote['tong_tien'], 0, ',', '.') }}₫</strong>
            </p>
        </div>

        <div class="info-desc">
            <div class="info-desc-label">Thành phần bó hoa</div>
            <ul>
                @foreach($chiTietPreview->groupBy('vai_tro') as $vaiTro => $items)
                    <li>
                        <strong>{{ \App\Http\Controllers\Admin\BoHoaController::VAI_TRO[$vaiTro] ?? $vaiTro }}:</strong>
                        {{ $items->map(fn($i) => ($i->sanPham->ten_san_pham ?? 'Nguyên liệu').' × '.$i->so_luong)->join(', ') }}
                    </li>
                @endforeach
            </ul>
            @if(!empty($preview['concept']))
                <p style="margin-top:12px;"><em>Concept: {{ $preview['concept'] }}</em></p>
            @endif
        </div>

        <div style="margin:16px 0; padding:14px; background:#fffbeb; border-radius:10px; font-size:13px; color:#92400e;">
            ⚠️ Đây là bản xem trước — chưa tạo đơn. Nhấn "Xác nhận mua hàng" để chốt thiết kế và chuyển sang thanh toán.
        </div>

        <div class="action-row" style="margin-bottom:14px;">
            <a href="{{ route('customer.bo-hoa.tu-tao') }}" class="btn-cart" style="text-decoration:none; text-align:center; display:inline-flex; align-items:center; justify-content:center;">
                ← Thay đổi bó hoa
            </a>
        </div>

        <form method="POST" action="{{ route('customer.bo-hoa.tu-tao.mua') }}" class="action-row">
            @csrf
            <label style="display:flex;align-items:center;gap:8px;margin:0 0 6px;">
                <input type="checkbox" id="coThiepTuTao" name="co_thiep" value="1"
                       style="width:auto;" {{ old('co_thiep') ? 'checked' : '' }}>
                Thêm thiệp gửi kèm (không phụ phí)
            </label>
            <div id="thiepBlockTuTao" style="display:{{ old('co_thiep') ? 'block' : 'none' }};">
                <label>Nội dung thiệp
                    <textarea name="loi_nhan" maxlength="2000" placeholder="Ví dụ: Chúc mừng sinh nhật em!">{{ old('loi_nhan') }}</textarea>
                </label>
            </div>
            <button class="btn-buy-now">⚡ Xác nhận mua hàng</button>
        </form>
    </div>
</div>

@else
{{-- ═══════════════════════════════════════════════════════════
     NHÁNH 3: SẢN PHẨM ĐƠN LẺ (GIỮ NGUYÊN)
═══════════════════════════════════════════════════════════ --}}
@php
    $categoryLabels = [
        'HOA_TUOI'         => 'Hoa Tươi',
        'HOA_GIA'          => 'Hoa Giả',
        'SAN_PHAM_PREMIUM' => 'Sản Phẩm Premium',
        'CHAU_HOA_GIA'     => 'Chậu Hoa Giả',
        'CHAU_HOA_TUOI'    => 'Chậu Hoa Tươi',
        'CAY_CANH'         => 'Cây Cảnh',
        'HOA_SAP'          => 'Hoa Sáp',
        'HOA_GIAY_NHUN'    => 'Hoa Giấy Nhún',
        'TERRARIUM'        => 'Terrarium',
        'PHU_KIEN'         => 'Phụ Kiện',
        'QUA_TANG'         => 'Quà Tặng',
    ];
    $catEmoji = [
        'HOA_TUOI' => '🌹', 'HOA_GIA' => '💐', 'SAN_PHAM_PREMIUM' => '👑',
        'CHAU_HOA_GIA' => '🪴', 'CHAU_HOA_TUOI' => '🌷', 'CAY_CANH' => '🌿',
        'HOA_SAP' => '🕯️', 'HOA_GIAY_NHUN' => '🎀', 'TERRARIUM' => '🔮',
        'PHU_KIEN' => '🎁', 'QUA_TANG' => '🎀',
    ];
    $soLuong  = ($sanPham->chiTietNhaps ?? collect())->sum('so_luong_con_lai');
    $catLabel = $categoryLabels[$sanPham->loai_san_pham] ?? $sanPham->loai_san_pham;
    $emoji    = $catEmoji[$sanPham->loai_san_pham] ?? '🌸';
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a onclick="navigateTo('{{ route('customer.dashboard') }}')">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <a onclick="navigateTo('{{ route('customer.dashboard') }}?cat={{ $sanPham->loai_san_pham }}')">{{ $catLabel }}</a>
    <span class="sep">›</span>
    <span>{{ $sanPham->ten_san_pham }}</span>
</div>

{{-- DETAIL SECTION --}}
<div class="detail-wrap">

    {{-- LEFT: Image --}}
    <div>
        <div class="gallery-main">
            @if($sanPham->hinh_anh)
                <img src="{{ asset($sanPham->hinh_anh) }}" alt="{{ $sanPham->ten_san_pham }}"
                     onerror="this.style.display='none'; document.getElementById('imgFallback').style.display='flex';">
                <div class="gallery-placeholder" id="imgFallback" style="display:none;">{{ $emoji }}</div>
            @else
                <div class="gallery-placeholder">{{ $emoji }}</div>
            @endif
            <span class="badge-detail-status">Đang bán</span>
            <span class="badge-detail-type">{{ $catLabel }}</span>
        </div>
    </div>

    {{-- RIGHT: Info --}}
    <div class="info-panel">

        <div>
            <div class="info-category-tag">{{ $emoji }} {{ $catLabel }}</div>
        </div>

        <h1 class="info-title">{{ $sanPham->ten_san_pham }}</h1>

        {{-- Stock status --}}
        <div class="info-stock-row">
            @if($soLuong === 0)
                <span class="stock-badge no-stock">❌ Hết hàng</span>
            @elseif($soLuong <= 5)
                <span class="stock-badge low-stock">⚠️ Còn {{ $soLuong }} sản phẩm – Sắp hết!</span>
            @else
                <!-- <span class="stock-badge in-stock">✅ Còn hàng ({{ $soLuong }} sp)</span> -->
            @endif
        </div>

        {{-- Meta chips --}}
        <div class="meta-strip">
            <div class="meta-chip">📦 Số lượng: <strong>{{ $soLuong }}</strong></div>
            <div class="meta-chip">📂 Loại: <strong>{{ $catLabel }}</strong></div>
        </div>

        {{-- Bảng giá bán theo lô nhập --}}
        @php $loGia = $sanPham->chiTietNhaps ?? collect(); @endphp

        @if($loGia->count() > 0)
        <div class="price-table-wrap">
            <div class="price-table-label">💰 Giá bán</div>
            <table class="price-table">
                <thead>
                    <tr>
                        <th>Giá bán</th>
                        <th>Còn lại</th>
                        <th>Chọn</th>
                    </tr>
                </thead>
                <tbody>

@php
    $loGiaConHang = $loGia->where('so_luong_con_lai', '>', 0);
    $giaApDung    = $sanPham->gia_ban_hien_tai;
    $tongTon      = $loGiaConHang->sum('so_luong_con_lai');
    $loDaiDien    = $loGiaConHang->sortBy('ma_chi_tiet_nhap')->first();
@endphp

@if($loDaiDien && $tongTon > 0)
<tr id="lo-row-0">
    <td class="price-val">
        {{ number_format($giaApDung, 0, ',', '.') }}₫
    </td>
    <td>
        <span class="stock-pill {{ $tongTon <= 5 ? 'low' : '' }}">
            {{ $tongTon }} sp
        </span>
    </td>
    <td>
        <div class="lo-qty-row">
            <button class="lo-minus-btn"
                    onclick="changeLo(0, -1)"
                    id="lo-minus-0"
                    style="display:none;">
                −
            </button>
            <span class="lo-qty-count" id="lo-qty-0">0</span>
            <button class="lo-plus-btn"
                    onclick="changeLo(0, 1)"
                    data-max="{{ $loDaiDien->so_luong_con_lai }}"
                    data-gia="{{ $giaApDung }}"
                    data-id="{{ $loDaiDien->ma_chi_tiet_nhap }}">
                +
            </button>
        </div>
    </td>
</tr>
@endif

                </tbody>
            </table>

            {{-- Cart summary --}}
            <div class="cart-summary" id="cartSummary" style="display:none;">
                <div class="cart-summary-inner">
                    <div class="cart-summary-label">🛒 Đã chọn</div>
                    <div id="cartLines"></div>
                    <div class="cart-total-row">
                        <span>Tổng cộng:</span>
                        <span class="cart-total-price" id="cartTotal">0₫</span>
                    </div>
                    <button class="cart-reset-btn" onclick="resetCart()">✕ Xóa chọn</button>
                </div>
            </div>
        </div>
        @else
        <div class="price-table-wrap">
            <div class="price-table-label">💰 Giá bán</div>
            <p class="price-no-data">Chưa có thông tin giá. Vui lòng liên hệ để được tư vấn.</p>
        </div>
        @endif

        {{-- Description --}}
        @if($sanPham->mo_ta)
        <div class="info-desc">
            <div class="info-desc-label">📝 Mô tả sản phẩm</div>
            {{ $sanPham->mo_ta }}
        </div>
        @endif

        {{-- Login notice if guest --}}
        @guest
        <div class="login-notice">
            💡 <span>Vui lòng <a href="{{ route('login') }}">đăng nhập</a> để đặt hoa và theo dõi đơn hàng nhé!</span>
        </div>
        @endguest

        {{-- Action Buttons --}}
        @if($soLuong > 0)
        <div class="action-row">
            @auth
                <button class="btn-cart" id="btnAddCart" onclick="addToCart()" disabled>
                    🛒 Thêm vào giỏ hàng
                </button>
                <button class="btn-buy-now" id="btnBuyNow" onclick="addToCartAndRedirect()" disabled>
                    ⚡ Mua ngay
                </button>
            @else
                <button class="btn-cart" onclick="navigateTo('{{ route('login') }}')">
                    🛒 Đăng nhập để đặt hoa
                </button>
            @endauth

            <a href="https://zalo.me/0357634696" target="_blank" class="btn-contact">
                💬 Tư vấn Zalo
            </a>
        </div>
        @else
        <div class="action-row">
            <a href="https://zalo.me/0357634696" target="_blank" class="btn-contact" style="flex:1; justify-content:center;">
                💬 Liên hệ để đặt trước qua Zalo
            </a>
        </div>
        @endif

    </div>
</div>

{{-- RELATED PRODUCTS --}}
@if($sanPhamLienQuan->count() > 0)
<div class="related-section">
    <div class="section-head">
        <h2 class="section-title">Sản Phẩm Liên Quan</h2>
        <span class="result-count">{{ $sanPhamLienQuan->count() }} sản phẩm</span>
    </div>
    <div class="related-grid">
        @foreach($sanPhamLienQuan as $sp)
        @php
            $spStock = ($sp->chiTietNhaps ?? collect())->sum('so_luong_con_lai');
            $spEmoji = $catEmoji[$sp->loai_san_pham] ?? '🌸';
        @endphp
        <div class="product-card"
             onclick="navigateTo('{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}')"
             style="cursor:pointer;">
            <div class="card-img-wrap">
                @if($sp->hinh_anh)
                    <img src="{{ asset($sp->hinh_anh) }}" alt="{{ $sp->ten_san_pham }}"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="card-img-placeholder" style="display:none;">{{ $spEmoji }}</div>
                @else
                    <div class="card-img-placeholder">{{ $spEmoji }}</div>
                @endif
                <span class="badge-status on-sale">Đang bán</span>
            </div>
            <div class="card-body">
                <div class="card-name">{{ $sp->ten_san_pham }}</div>
                <div class="card-footer">
                    <span class="card-stock {{ $spStock === 0 ? 'out' : ($spStock <= 5 ? 'low' : '') }}">
                        {{ $spStock === 0 ? 'Hết hàng' : 'Còn '.$spStock.' sp' }}
                    </span>
                    <button class="add-btn"
                            onclick="event.stopPropagation(); navigateTo('{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}')">
                        Xem chi tiết
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endif
@endsection

@section('scripts')
<script>
// ── Toggle khối thiệp (chỉ tồn tại ở nhánh mẫu / tự tạo) ─────────────────────
document.getElementById('coThiepMau')?.addEventListener('change', function() {
    const box = document.getElementById('thiepBlockMau');
    if (box) box.style.display = this.checked ? 'block' : 'none';
});
document.getElementById('coThiepTuTao')?.addEventListener('change', function() {
    const box = document.getElementById('thiepBlockTuTao');
    if (box) box.style.display = this.checked ? 'block' : 'none';
});

// ── Script sản phẩm đơn lẻ (chỉ chạy khi có nút giỏ hàng) ──────────────────
const CSRF          = document.querySelector('meta[name=csrf-token]')?.content ?? '';
const ADD_CART_URL  = '{{ route("customer.gio-hang.add") }}';
const BUY_NOW_URL   = '{{ route("customer.mua-ngay") }}';
const CHECKOUT_URL  = '{{ route("customer.thanh-toan") }}';

const fmt = v => new Intl.NumberFormat('vi-VN').format(v) + '₫';

const cart = {};

function changeLo(idx, delta) {
    const row = document.getElementById('lo-row-' + idx);
    if (!row) return;

    const plusEl = row.querySelector('.lo-plus-btn');
    if (!plusEl) return;

    const max = parseInt(plusEl.dataset.max ?? 0);
    const gia = parseFloat(plusEl.dataset.gia ?? 0);
    const id  = parseInt(plusEl.dataset.id ?? 0);

    if (!cart[idx]) {
        cart[idx] = { qty: 0, maxQty: max, giaBan: gia, maChiTietNhap: id };
    }

    cart[idx].qty = Math.max(0, Math.min(cart[idx].qty + delta, cart[idx].maxQty));

    const qtyEl   = document.getElementById('lo-qty-' + idx);
    const minusEl = document.getElementById('lo-minus-' + idx);

    if (qtyEl) qtyEl.textContent = cart[idx].qty;
    if (minusEl) minusEl.style.display = cart[idx].qty > 0 ? '' : 'none';

    renderSummary();
}

function resetCart() {
    Object.keys(cart).forEach(idx => {
        cart[idx].qty = 0;

        const qtyEl   = document.getElementById('lo-qty-' + idx);
        const minusEl = document.getElementById('lo-minus-' + idx);

        if (qtyEl) qtyEl.textContent = 0;
        if (minusEl) minusEl.style.display = 'none';
    });

    renderSummary();
}

function renderSummary() {
    const summary = document.getElementById('cartSummary');
    const linesEl = document.getElementById('cartLines');
    const totalEl = document.getElementById('cartTotal');
    const btnCart = document.getElementById('btnAddCart');
    const btnBuy  = document.getElementById('btnBuyNow');

    if (!summary) return;

    let totalQty = 0;
    let totalPrice = 0;
    let linesHtml = '';

    Object.entries(cart).forEach(([idx, item]) => {
        if (item.qty > 0) {
            const sub = item.qty * item.giaBan;
            totalQty += item.qty;
            totalPrice += sub;
            linesHtml += `
                <div class="cart-line">
                    <span>${fmt(item.giaBan)} × ${item.qty} sp</span>
                    <span>${fmt(sub)}</span>
                </div>
            `;
        }
    });

    if (totalQty > 0) {
        if (linesEl) linesEl.innerHTML = linesHtml;
        if (totalEl) totalEl.textContent = fmt(totalPrice);
        summary.style.display = 'block';

        if (btnCart) {
            btnCart.disabled = false;
            btnCart.textContent = `🛒 Thêm vào giỏ (${totalQty} sp – ${fmt(totalPrice)})`;
        }
        if (btnBuy) btnBuy.disabled = false;
    } else {
        summary.style.display = 'none';
        if (btnCart) {
            btnCart.disabled = true;
            btnCart.textContent = '🛒 Thêm vào giỏ hàng';
        }
        if (btnBuy) btnBuy.disabled = true;
    }
}

function buildItems() {
    return Object.values(cart)
        .filter(item => item.qty > 0)
        .map(item => ({
            ma_chi_tiet_nhap: item.maChiTietNhap,
            so_luong: item.qty
        }));
}

async function addToCart() {
    const items = buildItems();
    if (!items.length) return;

    const btn = document.getElementById('btnAddCart');
    const btnBuy = document.getElementById('btnBuyNow');

    if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ Đang thêm...';
    }

    try {
        const res = await fetch(ADD_CART_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ items })
        });

        const data = await res.json();

        if (data.success) {
            if (typeof updateCartBadge === 'function') {
                updateCartBadge(data.so_san_pham);
            }

            showSuccessToast('Đã thêm sản phẩm vào giỏ hàng thành công!');
            resetCart();

            if (btn) {
                btn.disabled = true;
                btn.textContent = '🛒 Thêm vào giỏ hàng';
            }
            if (btnBuy) btnBuy.disabled = true;
        } else {
            alert(data.message || 'Có lỗi xảy ra!');
            renderSummary();
        }
    } catch (error) {
        console.error(error);
        alert('Không thể kết nối tới máy chủ!');
        renderSummary();
    }
}

async function addToCartAndRedirect() {
    const items = buildItems();
    if (!items.length) return;

    const btn = document.getElementById('btnBuyNow');

    if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ Đang xử lý...';
    }

    try {
        const res = await fetch(BUY_NOW_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ items })
        });

        const data = await res.json();

        if (data.success) {
            navigateTo(CHECKOUT_URL);
        } else {
            alert(data.message || 'Có lỗi xảy ra!');
            renderSummary();
        }
    } catch (error) {
        console.error(error);
        alert('Không thể kết nối tới máy chủ!');
        renderSummary();
    }
}
(function() {
    const giaGoc = {{ isset($quote) && $type === 'mau-bo-hoa' ? (float) $quote['gia_moi_bo'] : 0 }};
    const sel = document.getElementById('thiepSelectMau');
    const priceEl = document.querySelector('.price-val');
    if (!sel || !priceEl || !giaGoc) return;

    function update() {
        const opt = sel.options[sel.selectedIndex];
        const them = parseInt(opt.dataset.gia || 0);
        const tong = giaGoc + them;
        priceEl.innerHTML = new Intl.NumberFormat('vi-VN').format(tong) + '₫ / bó';
    }
    sel.addEventListener('change', update);
})();
</script>
@endsection