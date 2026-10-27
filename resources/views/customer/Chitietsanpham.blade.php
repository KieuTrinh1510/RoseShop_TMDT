@extends('customer.layouts.customer')

@section('title',
    ($type ?? 'san-pham') === 'mau-bo-hoa'
        ? (($boHoaMau->ten_mau ?? 'Mẫu bó hoa') . ' – Hoa Tươi Shop 🌸')
        : ((($type ?? 'san-pham') === 'bo-hoa-tu-tao')
            ? (($preview['ten_bo_hoa'] ?? 'Bó hoa tự tạo') . ' – Hoa Tươi Shop 🌸')
            : (($sanPham->ten_san_pham ?? 'Sản phẩm') . ' – Hoa Tươi Shop 🌸')))

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/ChiTietSanPham.css') }}?v={{ time() }}">
@endsection

@section('content')
@if(($type ?? 'san-pham') === 'mau-bo-hoa')
{{-- ═══════════════════════════════════════════════════════════
     NHÁNH 1: MẪU BÓ HOA HỆ THỐNG
═══════════════════════════════════════════════════════════ --}}
@php
    $style    = \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$boHoaMau->kieu_bo_hoa] ?? $boHoaMau->kieu_bo_hoa;
    $anhBoHoa = \App\Http\Controllers\Admin\BoHoaController::anhMau($boHoaMau);
    $moTaDai  = trim(strip_tags($boHoaMau->mo_ta ?? ''));
    $moTaNgan = \Illuminate\Support\Str::limit($moTaDai, 160);
@endphp
<div class="detail-wrap">
    {{-- LEFT: Ảnh --}}
    <div class="pd-gallery">
        <div class="gallery-main">
            <img src="{{ $anhBoHoa }}" alt="{{ $boHoaMau->ten_mau }}" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
            <button type="button" class="pd-btn-wishlist" title="Yêu thích" onclick="toggleHeart(this)"><i class="fa-regular fa-heart"></i></button>
            <button type="button" class="pd-btn-expand" title="Phóng to" onclick="window.open('{{ $anhBoHoa }}', '_blank')"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></button>
        </div>
        <div class="pd-thumbs">
            <button type="button" class="pd-thumb is-active" aria-label="Ảnh bó hoa">
                <img src="{{ $anhBoHoa }}" alt="" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
            </button>
        </div>
    </div>

    {{-- RIGHT: Thông tin --}}
    <div class="info-panel pd-info">
        <span class="pd-tag">Bó hoa</span>
        <h1 class="info-title">{{ $boHoaMau->ten_mau }}</h1>
        <p class="pd-subtitle">{{ $moTaNgan !== '' ? $moTaNgan : ($style . ' · Size ' . $boHoaMau->size) }}</p>

        <div class="pd-price-row">
            @if(isset($activeFlashSale) && $activeFlashSale)
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="text-decoration:line-through; color:#98A2B3; font-size:16px;">
                            {{ number_format($readyBo->gia_ban ?? $quote['gia_moi_bo'], 0, ',', '.') }}₫
                        </span>
                        <p class="pd-price price-val" style="color:#EC3D8F; font-size:24px; font-weight:700; margin:0;">
                            {{ number_format($activeFlashSale->gia_flash_sale, 0, ',', '.') }}₫
                            <span style="font-size:14px; font-weight:normal; color:#667085;">/ bó</span>
                        </p>
                        <span style="background:#FEE4E2; color:#D92D20; font-size:12px; font-weight:700; padding:2px 8px; border-radius:999px;">
                            -{{ $activeFlashSale->phan_tram_giam }}%
                        </span>
                    </div>
                    <div style="font-size:13px; color:#BE185D; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-bolt" style="color:#EC3D8F;"></i>
                        <span>Flash Sale kết thúc sau: <strong class="fs-countdown" data-end="{{ $activeFlashSale->ket_thuc ? $activeFlashSale->ket_thuc->toISOString() : '' }}">--:--:--</strong></span>
                        <span style="color:#667085;">· Còn {{ $activeFlashSale->available_quota }} suất sale</span>
                    </div>
                </div>
            @else
                <p class="pd-price price-val">{{ number_format($quote['gia_moi_bo'], 0, ',', '.') }}₫ / bó</p>
            @endif
            <span class="pd-badge pd-badge--ok"><i class="fa-solid fa-leaf"></i> Đang bán</span>
        </div>

        <div class="meta-strip">
            <div class="meta-chip">Kiểu dáng: <strong>{{ $style }}</strong></div>
            <div class="meta-chip">Size: <strong>{{ $boHoaMau->size }}</strong></div>
        </div>

        @include('customer.partials.pd-features')

        <section class="pd-card" aria-labelledby="pdCompTitle">
            <h2 class="pd-card-title" id="pdCompTitle">Thành phần bó hoa</h2>
            <ul class="pd-composition">
                @foreach($boHoaMau->chiTietMauBoHoa->groupBy('vai_tro') as $vaiTro => $items)
                    <li>
                        <strong>{{ \App\Http\Controllers\Admin\BoHoaController::VAI_TRO[$vaiTro] ?? $vaiTro }}:</strong>
                        {{ $items->map(fn($i) => ($i->sanPham->ten_san_pham ?? 'Nguyên liệu').' × '.$i->so_luong)->join(', ') }}
                    </li>
                @endforeach
            </ul>
            @if(mb_strlen($moTaDai) > 160)
                <p class="pd-composition-note">{{ $boHoaMau->mo_ta }}</p>
            @endif
        </section>

        @auth
        <form method="POST" action="{{ route('customer.mau-bo-hoa.mua', $boHoaMau->ma_mau_bo_hoa) }}" class="pd-card pd-options" id="formMuaMau">
            @csrf

            {{-- Hidden field để route gio.add nhận được ma_mau_bo_hoa --}}
            <input type="hidden" name="ma_mau_bo_hoa" value="{{ $boHoaMau->ma_mau_bo_hoa }}">

            <h2 class="pd-card-title">Tùy chọn đặt mua</h2>

            @if(session('error'))
                <div class="pd-alert pd-alert--danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="pd-alert pd-alert--danger">
                    <ul>
                        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="pd-options-row">
                <div class="pd-field">
                    <label for="soLuongMau">Số lượng <small>(tối đa 10 bó)</small></label>
                    <div class="pd-stepper">
                        <button type="button" onclick="pdStep('soLuongMau', -1)" aria-label="Giảm số lượng">−</button>
                        <input type="number" name="so_luong" id="soLuongMau" min="1" max="10" value="{{ old('so_luong', 1) }}" required>
                        <button type="button" onclick="pdStep('soLuongMau', 1)" aria-label="Tăng số lượng">+</button>
                    </div>
                </div>

                @if(isset($dsThiep) && $dsThiep->count() > 0)
                <div class="pd-field pd-field--grow">
                    <label for="thiepSelectMau">Thiệp đi kèm <small>(tùy chọn)</small></label>
                    <select name="ma_thiep" id="thiepSelectMau" class="pd-select">
                        <option value="" data-gia="0">Không thêm thiệp</option>
                        @foreach($dsThiep as $t)
                            <option value="{{ $t->ma_san_pham }}" data-gia="{{ $t->gia_ban_hien_tai }}"
                                {{ old('ma_thiep') == $t->ma_san_pham ? 'selected' : '' }}>
                                {{ $t->ten_san_pham }} — +{{ number_format($t->gia_ban_hien_tai, 0, ',', '.') }}₫
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="pd-field">
                <label for="loiNhanMau">Lời nhắn gửi kèm <small>(tùy chọn)</small></label>
                <div class="pd-textarea-wrap">
                    <textarea name="loi_nhan" id="loiNhanMau" class="pd-textarea" maxlength="2000" data-counter="loiNhanMauCounter"
                              placeholder="Ví dụ: Chúc mừng sinh nhật em!">{{ old('loi_nhan') }}</textarea>
                    <span class="pd-counter" id="loiNhanMauCounter">0/2000</span>
                </div>
            </div>

            <div class="pd-actions">
                <button type="submit" class="btn-cart" formaction="{{ route('customer.bo-hoa.gio.add') }}">
                    <i class="fa-solid fa-cart-shopping"></i><span class="btn-label">Thêm vào giỏ</span>
                </button>
                <button type="submit" class="btn-buy-now">
                    <i class="fa-solid fa-bolt"></i><span class="btn-label">Mua ngay</span>
                </button>
            </div>

            {{-- Link vào giỏ hàng thống nhất --}}
            <a class="pd-cart-link" href="{{ route('customer.gio-hang') }}">
                <i class="fa-solid fa-bag-shopping"></i>
                Xem giỏ hàng của bạn ({{ count(session('gio_hang', [])) + count(session('gio_bo_hoa', [])) }})
            </a>
        </form>
        @else
        <div class="login-notice">Vui lòng <a href="{{ route('login') }}">đăng nhập</a> để đặt bó hoa.</div>
        @endauth

        @include('customer.partials.pd-promises')
    </div>
</div>

@elseif(($type ?? 'san-pham') === 'bo-hoa-tu-tao')
{{-- ═══════════════════════════════════════════════════════════
     NHÁNH 2: BÓ HOA TỰ TẠO (PREVIEW TỪ SESSION)
═══════════════════════════════════════════════════════════ --}}
<div class="breadcrumb">
    <a onclick="navigateTo('{{ route('customer.dashboard') }}')">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <a onclick="navigateTo('{{ route('customer.bo-hoa.he-thong') }}')">Mua bó hoa</a>
    <span class="sep">›</span>
    <span class="current">Bó hoa tự tạo (Custom Mix)</span>
    <span class="sep">›</span>
    <span class="current">{{ $preview['ten_bo_hoa'] }}</span>
</div>

<div class="detail-wrap">
    {{-- Cột trái: Gallery ảnh mẫu bó hoa tự tạo --}}
    <div>
        @php
            $hasImgs = !empty($anhMau) && count($anhMau) > 0;
            $sampleFlowerImages = [
                'img/1791790828_hoa thác đổ.jpeg',
                'img/hoa 4.jpg',
                'img/hoa 5.jpeg',
                'img/1780566907_hongdo.jpg',
            ];
            $mainImgUrl = $hasImgs ? $anhMau[0]['url'] : asset($sampleFlowerImages[0]);
        @endphp
        <div class="custom-mix-gallery">
            <div class="custom-mix-main-box">
                <button type="button" class="custom-mix-heart-btn" title="Yêu thích" onclick="toggleHeart(this)">
                    <i class="far fa-heart"></i>
                </button>
                <button type="button" class="custom-mix-nav-btn prev" onclick="prevTuTaoImg()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <img id="mainTuTaoImg" src="{{ $mainImgUrl }}" alt="{{ $preview['ten_bo_hoa'] }}">
                <button type="button" class="custom-mix-nav-btn next" onclick="nextTuTaoImg()">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="custom-mix-thumbs">
                @if($hasImgs)
                    @foreach($anhMau as $idx => $img)
                        <div class="custom-mix-thumb-item {{ $idx === 0 ? 'active' : '' }}"
                             onclick="changeTuTaoImg('{{ $img['url'] }}', this)">
                            <img src="{{ $img['url'] }}" alt="Ảnh mẫu {{ $idx + 1 }}">
                        </div>
                    @endforeach
                @else
                    @foreach($sampleFlowerImages as $idx => $samplePath)
                        <div class="custom-mix-thumb-item {{ $idx === 0 ? 'active' : '' }}"
                             onclick="changeTuTaoImg('{{ asset($samplePath) }}', this)">
                            <img src="{{ asset($samplePath) }}" alt="Mẫu {{ $idx + 1 }}">
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- Cột phải: Thông tin bó hoa tự tạo --}}
    <div class="info-panel">
        <div class="custom-mix-tag">
            💐 Bó hoa tự tạo (Custom Mix)
        </div>

        <h1 class="info-title" style="margin-top:8px; margin-bottom:8px;">{{ $preview['ten_bo_hoa'] }}</h1>

        <div class="custom-mix-meta-row">
            <div class="custom-mix-chip">Kiểu dáng: <strong>{{ \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$preview['kieu_bo_hoa']] ?? $preview['kieu_bo_hoa'] }}</strong></div>
            <div class="custom-mix-chip">Size: <strong>{{ $preview['size'] }}</strong></div>
            <div class="custom-mix-chip">Số bó: <strong>{{ $preview['so_luong'] }}</strong></div>
        </div>

        <div class="custom-mix-rating">
            <span class="custom-mix-stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                <strong style="color:#101828; margin-left:4px;">4.8</strong>
            </span>
            <span>(120 đánh giá)</span>
            <span style="color:#D0D5DD;">|</span>
            <span>Đã bán 256+ bó</span>
        </div>

        {{-- Khối 2 ô: Giá tạm tính & Thành phần bó hoa --}}
        <div class="custom-mix-grid-cards">
            <div class="custom-mix-subcard price-card">
                <div class="custom-mix-subcard-title" style="color:#BE185D;">
                    <i class="fas fa-coins" style="color:#EC3D8F;"></i> Giá tạm tính
                </div>
                <div class="custom-mix-price-val" id="priceValTuTao">
                    {{ number_format($quote['gia_moi_bo'], 0, ',', '.') }}₫ <span>/ bó</span>
                </div>
                <div class="custom-mix-price-breakdown" id="priceBreakdownTuTao">
                    <div class="breakdown-line">Tiền hoa: <strong>{{ number_format($quote['chi_tiet_tien']['hoa'] ?? 0, 0, ',', '.') }}₫</strong></div>
                    <div class="breakdown-line">Tiền giấy gói: <strong>{{ number_format($quote['chi_tiet_tien']['giay'] ?? 0, 0, ',', '.') }}₫</strong></div>
                    <div class="breakdown-line">Ruy băng: <strong>{{ number_format($quote['chi_tiet_tien']['ruy_bang'] ?? 0, 0, ',', '.') }}₫</strong></div>
                    <div class="breakdown-line">Xốp cắm: <strong>{{ number_format($quote['chi_tiet_tien']['xop'] ?? 0, 0, ',', '.') }}₫</strong></div>
                    @if(!empty($quote['chi_tiet_tien']['phu_kien']))
                        <div class="breakdown-line">Tiền phụ kiện: <strong>{{ number_format($quote['chi_tiet_tien']['phu_kien'], 0, ',', '.') }}₫</strong></div>
                    @endif
                    <div class="breakdown-line">Phụ phí: <strong>{{ number_format($quote['phu_phi']['tong'] ?? 0, 0, ',', '.') }}₫</strong> <span class="breakdown-sub">(Size: {{ number_format($quote['phu_phi']['kich_thuoc'] ?? 0, 0, ',', '.') }}₫ · Kiểu: {{ number_format($quote['phu_phi']['kieu_dang'] ?? 0, 0, ',', '.') }}₫)</span></div>
                    <div id="thiepTotalNotice" style="display:none; color:#BE185D; font-weight:600; margin-top:4px;"></div>
                </div>
                <div class="custom-mix-price-bottom">
                    <hr style="border:none; border-top:1px dashed #FCE7F3; margin:10px 0 8px;">
                    <div class="price-bottom-total-row">
                        <span>Tổng cộng ({{ $preview['so_luong'] }} bó):</span>
                        <strong id="priceTotalTuTao" style="color:#EC3D8F; font-size:16.5px;">{{ number_format($quote['tong_tien'], 0, ',', '.') }}₫</strong>
                    </div>
                </div>
            </div>

            <div class="custom-mix-subcard spec-card">
                <div class="custom-mix-subcard-title" style="color:#101828;">
                    <i class="fas fa-cog" style="color:#EC3D8F;"></i> Thành phần bó hoa (cho 1 bó)
                </div>
                <ul class="custom-mix-spec-list">
                    @php
                        $roleIcons = [
                            'HOA_CHINH'     => '🌺',
                            'HOA_PHU'       => '🌸',
                            'LA_PHU'        => '🌿',
                            'GIAY_GOI'      => '🎁',
                            'RUY_BANG'      => '🎗',
                            'XOP_CAM'       => '💎',
                            'PHU_KIEN'      => '🎀',
                        ];
                    @endphp
                    @foreach($chiTietPreview->groupBy('vai_tro') as $vaiTro => $items)
                        <li>
                            <span class="spec-role-icon">{{ $roleIcons[$vaiTro] ?? '🌸' }}</span>
                            <span class="spec-role-text"><strong>{{ \App\Http\Controllers\Admin\BoHoaController::VAI_TRO[$vaiTro] ?? $vaiTro }}:</strong>
                            {{ $items->map(fn($i) => ($i->sanPham->ten_san_pham ?? 'Nguyên liệu').' × '.$i->so_luong)->join(', ') }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="custom-mix-spec-concept">
                    Concept thiết kế: <strong style="color:#BE185D;">{{ $preview['concept'] ?? 'ROMANTIC' }}</strong>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('customer.bo-hoa.tu-tao.mua') }}" id="formTuTaoMua">
            @csrf

            {{-- Card tùy chỉnh thiệp & ghi chú (2 cột) --}}
            <div class="custom-mix-custom-card">
                <div class="custom-mix-subcard-title" style="color:#EC3D8F;">
                    <i class="fas fa-heart"></i> Tùy chỉnh và ghi chú
                </div>
                <div class="custom-mix-custom-grid">
                    <div>
                        <label class="custom-mix-field-label">
                            <span class="label-main">Chọn thiệp chúc mừng</span>
                            <span class="label-sub">(Tùy chọn — 1 thiệp / bó)</span>
                        </label>
                        @if(isset($dsThiep) && $dsThiep->count() > 0)
                            <select name="ma_thiep" id="thiepSelectTuTao" class="custom-mix-select">
                                <option value="" data-gia="0">-- Không lấy thiệp --</option>
                                @foreach($dsThiep as $t)
                                    <option value="{{ $t->ma_san_pham }}" data-gia="{{ $t->gia_ban_hien_tai }}"
                                        {{ old('ma_thiep') == $t->ma_san_pham ? 'selected' : '' }}>
                                        {{ $t->ten_san_pham }} (+{{ number_format($t->gia_ban_hien_tai, 0, ',', '.') }}₫)
                                    </option>
                                @endforeach
                            </select>
                            <div style="font-size:11.5px; color:#667085; margin-top:5px;">
                                * Lưu ý: Giá thiệp sẽ tự động nhân với số lượng bó hoa ({{ $preview['so_luong'] }} bó).
                            </div>
                        @else
                            <select name="ma_thiep" id="thiepSelectTuTao" class="custom-mix-select" disabled>
                                <option value="" data-gia="0">-- Không lấy thiệp --</option>
                            </select>
                        @endif
                    </div>

                    <div>
                        <label class="custom-mix-field-label">
                            <span class="label-main">Lời nhắn trên thiệp</span>
                            <span class="label-sub">(Nếu có lời chúc gửi người nhận)</span>
                        </label>
                        <textarea name="loi_nhan" id="loiNhanTuTao" rows="2" maxlength="200"
                                  class="custom-mix-textarea"
                                  placeholder="Nhập lời chúc bạn muốn viết gửi người nhận..."
                                  oninput="document.getElementById('charCountTuTao').textContent = this.value.length + '/200'">{{ old('loi_nhan') }}</textarea>
                        <div id="charCountTuTao" style="text-align:right; font-size:11px; color:#98A2B3; margin-top:2px;">
                            {{ mb_strlen(old('loi_nhan', '')) }}/200
                        </div>
                    </div>
                </div>
            </div>

            <div class="custom-mix-warning">
                ⚠️ <strong>Lưu ý:</strong> Đây là bản xem trước — kho chưa bị trừ hay giữ chỗ. Sau khi nhấn "Xác nhận mua hàng", hệ thống sẽ chuyển sang bước thanh toán và giữ chỗ nguyên liệu.
            </div>

            <div class="custom-mix-action-bar">
                <div class="custom-mix-action-top-row">
                    <div class="custom-mix-qty-wrap">
                        <span>Số lượng</span>
                        <div class="custom-mix-qty-stepper">
                            <button type="button" onclick="alert('Số lượng được điều chỉnh ở bước thiết kế Custom Mix!')">−</button>
                            <span>{{ $preview['so_luong'] }}</span>
                            <button type="button" onclick="alert('Số lượng được điều chỉnh ở bước thiết kế Custom Mix!')">+</button>
                        </div>
                    </div>

                    <a href="{{ route('customer.bo-hoa.tu-tao') }}" class="custom-mix-btn-back">
                        ← Thay đổi thiết kế
                    </a>
                </div>

                <div class="custom-mix-action-bottom-row">
                    <button type="submit" class="custom-mix-btn-buy">
                        ⚡ Xác nhận mua hàng (Direct Checkout)
                    </button>
                </div>
            </div>
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

    // Dữ liệu giá / lô đại diện (giữ nguyên cách tính cũ)
    $loGia        = $sanPham->chiTietNhaps ?? collect();
    $loGiaConHang = $loGia->where('so_luong_con_lai', '>', 0);
    $giaGoc       = $sanPham->gia_ban_hien_tai;
    $giaApDung    = (isset($activeFlashSale) && $activeFlashSale) ? (float) $activeFlashSale->gia_flash_sale : $giaGoc;
    $tongTon      = $loGiaConHang->sum('so_luong_con_lai');
    $loDaiDien    = $loGiaConHang->sortBy('ma_chi_tiet_nhap')->first();
    $sanSangMua   = $loDaiDien && $tongTon > 0;

    $donVi    = trim((string) ($sanPham->don_vi_tinh_text ?? ''));
    $donVi    = $donVi !== '' ? mb_strtolower($donVi) : 'sản phẩm';
    $moTaDai  = trim(strip_tags($sanPham->mo_ta ?? ''));
    $moTaNgan = \Illuminate\Support\Str::limit($moTaDai, 160);
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

    {{-- LEFT: Ảnh + mô tả --}}
    <div class="pd-gallery">
        <div class="gallery-main">
            @if($sanPham->hinh_anh)
                <img src="{{ asset($sanPham->hinh_anh) }}" alt="{{ $sanPham->ten_san_pham }}"
                     onerror="this.style.display='none'; document.getElementById('imgFallback').style.display='flex';">
                <div class="gallery-placeholder" id="imgFallback" style="display:none;">{{ $emoji }}</div>
            @else
                <div class="gallery-placeholder">{{ $emoji }}</div>
            @endif
            <span class="badge-detail-status {{ $soLuong > 0 ? '' : 'is-out' }}">{{ $soLuong > 0 ? 'Đang bán' : 'Hết hàng' }}</span>
            <span class="badge-detail-type">{{ $catLabel }}</span>
        </div>

        @if($sanPham->hinh_anh)
        <div class="pd-thumbs">
            <button type="button" class="pd-thumb is-active" aria-label="Ảnh sản phẩm">
                <img src="{{ asset($sanPham->hinh_anh) }}" alt="" onerror="this.style.display='none'">
            </button>
        </div>
        @endif

        @if($sanPham->mo_ta)
        <section class="pd-card" aria-labelledby="pdDescTitle">
            <h2 class="pd-card-title" id="pdDescTitle">Mô tả sản phẩm</h2>
            <div class="pd-desc">{{ $sanPham->mo_ta }}</div>
        </section>
        @endif
    </div>

    {{-- RIGHT: Thông tin --}}
    <div class="info-panel pd-info">

        <span class="pd-tag">{{ $emoji }} {{ $catLabel }}</span>

        <h1 class="info-title">{{ $sanPham->ten_san_pham }}</h1>

        @if($moTaNgan !== '')
            <p class="pd-subtitle">{{ $moTaNgan }}</p>
        @endif

        {{-- Giá + tình trạng kho --}}
        <div class="pd-price-row">
            @if($sanSangMua)
                @if(isset($activeFlashSale) && $activeFlashSale)
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="text-decoration:line-through; color:#98A2B3; font-size:16px;">
                                {{ number_format($giaGoc, 0, ',', '.') }}₫
                            </span>
                            <p class="pd-price" style="color:#EC3D8F; font-size:24px; font-weight:700; margin:0;">
                                {{ number_format($activeFlashSale->gia_flash_sale, 0, ',', '.') }}₫
                                <span style="font-size:14px; font-weight:normal; color:#667085;">/ {{ $donVi }}</span>
                            </p>
                            <span style="background:#FEE4E2; color:#D92D20; font-size:12px; font-weight:700; padding:2px 8px; border-radius:999px;">
                                -{{ $activeFlashSale->phan_tram_giam }}%
                            </span>
                        </div>
                        <div style="font-size:13px; color:#BE185D; display:flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-bolt" style="color:#EC3D8F;"></i>
                            <span>Flash Sale kết thúc sau: <strong class="fs-countdown" data-end="{{ $activeFlashSale->ket_thuc ? $activeFlashSale->ket_thuc->toISOString() : '' }}">--:--:--</strong></span>
                            <span style="color:#667085;">· Còn {{ $activeFlashSale->available_quota }} suất sale</span>
                        </div>
                    </div>
                @else
                    <p class="pd-price">
                        {{ $giaApDung ? number_format($giaApDung, 0, ',', '.') . '₫' : 'Liên hệ' }}
                        <span>/ {{ $donVi }}</span>
                    </p>
                @endif
            @else
                <p class="pd-price pd-price--empty">Liên hệ để biết giá</p>
            @endif

            @if($soLuong === 0)
                <span class="pd-badge pd-badge--danger"><i class="fa-solid fa-circle-xmark"></i> Hết hàng</span>
            @elseif($soLuong <= 5)
                <span class="pd-badge pd-badge--warn"><i class="fa-solid fa-triangle-exclamation"></i> Sắp hết · còn {{ $soLuong }}</span>
            @else
                <span class="pd-badge pd-badge--ok"><i class="fa-solid fa-leaf"></i> Còn {{ $soLuong }} {{ $donVi }}</span>
            @endif
        </div>

        {{-- Meta chips --}}
        <div class="meta-strip">
            <div class="meta-chip">Số lượng: <strong>{{ $soLuong }}</strong></div>
            <div class="meta-chip">Loại: <strong>{{ $catLabel }}</strong></div>
        </div>

        @include('customer.partials.pd-features')

        {{-- Tùy chọn đặt mua --}}
        <section class="pd-card pd-options" aria-labelledby="pdOptionsTitle">
            <h2 class="pd-card-title" id="pdOptionsTitle">Tùy chọn đặt mua</h2>

            @if($soLuong > 0)
                @if($sanSangMua)
                <div class="pd-field pd-qty-row">
                    <label>Số lượng <small>(tối đa {{ $loDaiDien->so_luong_con_lai }})</small></label>
                    <div class="pd-stepper" id="lo-row-0">
                        <button type="button" class="lo-minus-btn" id="lo-minus-0"
                                onclick="changeLo(0, -1)" aria-label="Giảm số lượng">−</button>
                        <span class="lo-qty-count" id="lo-qty-0">1</span>
                        <button type="button" class="lo-plus-btn"
                                onclick="changeLo(0, 1)"
                                data-max="{{ $loDaiDien->so_luong_con_lai }}"
                                data-gia="{{ $giaApDung }}"
                                data-id="{{ $loDaiDien->ma_chi_tiet_nhap }}"
                                aria-label="Tăng số lượng">+</button>
                    </div>
                </div>

                {{-- Tạm tính --}}
                <div class="cart-summary" id="cartSummary" style="display:none;">
                    <div class="cart-summary-inner">
                        <div class="cart-summary-label">Tạm tính</div>
                        <div id="cartLines"></div>
                        <div class="cart-total-row">
                            <span>Tổng cộng:</span>
                            <span class="cart-total-price" id="cartTotal">0₫</span>
                        </div>
                    </div>
                </div>
                @else
                    <p class="price-no-data">Chưa có thông tin giá. Vui lòng liên hệ để được tư vấn.</p>
                @endif
            @endif

            {{-- Login notice if guest --}}
            @guest
            <div class="login-notice">
                💡 <span>Vui lòng <a href="{{ route('login') }}">đăng nhập</a> để đặt hoa và theo dõi đơn hàng nhé!</span>
            </div>
            @endguest

            {{-- Action Buttons --}}
            @if($soLuong > 0)
            <div class="pd-actions">
                @auth
                    <button class="btn-cart" id="btnAddCart" onclick="addToCart()" disabled>
                        <i class="fa-solid fa-cart-shopping"></i><span class="btn-label">Thêm vào giỏ</span>
                    </button>
                    <button class="btn-buy-now" id="btnBuyNow" onclick="addToCartAndRedirect()" disabled>
                        <i class="fa-solid fa-bolt"></i><span class="btn-label">Mua ngay</span>
                    </button>
                @else
                    <button class="btn-cart pd-actions-wide" onclick="navigateTo('{{ route('login') }}')">
                        <i class="fa-solid fa-right-to-bracket"></i><span class="btn-label">Đăng nhập để đặt hoa</span>
                    </button>
                @endauth
            </div>
            <a href="https://zalo.me/0357634696" target="_blank" rel="noopener" class="btn-contact">
                <i class="fa-regular fa-comments"></i> Tư vấn qua Zalo
            </a>
            @else
            <a href="https://zalo.me/0357634696" target="_blank" rel="noopener" class="btn-contact btn-contact--solid">
                <i class="fa-regular fa-comments"></i> Liên hệ để đặt trước qua Zalo
            </a>
            @endif
        </section>

        @include('customer.partials.pd-promises')
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
                <div class="card-name" title="{{ $sp->ten_san_pham }}">{{ $sp->ten_san_pham }}</div>
                <div class="card-footer">
                    <span class="card-stock {{ $spStock === 0 ? 'out' : ($spStock <= 5 ? 'low' : '') }}">
                        {{ $spStock === 0 ? 'Hết hàng' : 'Còn '.$spStock.' sp' }}
                    </span>
                    <button class="add-btn"
                            onclick="event.stopPropagation(); navigateTo('{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}')">
                        Chi tiết
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

// UI helper: đổi nhãn nút nhưng giữ nguyên icon
function setBtnLabel(btn, text) {
    if (!btn) return;
    const label = btn.querySelector('.btn-label');
    if (label) label.textContent = text; else btn.textContent = text;
}

// UI helper: nút +/- cho ô số lượng của form mẫu bó hoa
function pdStep(inputId, delta) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const min = parseInt(input.min || 1);
    const max = parseInt(input.max || 999);
    const cur = parseInt(input.value || min);
    input.value = Math.max(min, Math.min(max, (isNaN(cur) ? min : cur) + delta));
}

// UI helper: đếm ký tự lời nhắn
document.querySelectorAll('[data-counter]').forEach(function (ta) {
    const out = document.getElementById(ta.dataset.counter);
    if (!out) return;
    const render = () => out.textContent = ta.value.length + '/' + (ta.maxLength > 0 ? ta.maxLength : '');
    ta.addEventListener('input', render);
    render();
});

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

    cart[idx].qty = Math.max(1, Math.min(cart[idx].qty + delta, cart[idx].maxQty));

    const qtyEl   = document.getElementById('lo-qty-' + idx);
    const minusEl = document.getElementById('lo-minus-' + idx);

    if (qtyEl) qtyEl.textContent = cart[idx].qty;
    if (minusEl) minusEl.disabled = cart[idx].qty <= 1;
    plusEl.disabled = cart[idx].qty >= cart[idx].maxQty;

    renderSummary();
}

function resetCart() {
    Object.keys(cart).forEach(idx => {
        // Sau khi thêm giỏ: trả số lượng về mặc định 1
        cart[idx].qty = Math.min(1, cart[idx].maxQty);

        const qtyEl   = document.getElementById('lo-qty-' + idx);
        const minusEl = document.getElementById('lo-minus-' + idx);

        if (qtyEl) qtyEl.textContent = cart[idx].qty;
        if (minusEl) minusEl.disabled = true;
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
            setBtnLabel(btnCart, 'Thêm vào giỏ');
        }
        if (btnBuy) {
            btnBuy.disabled = false;
            setBtnLabel(btnBuy, 'Mua ngay');
        }
    } else {
        summary.style.display = 'none';
        if (btnCart) {
            btnCart.disabled = true;
            setBtnLabel(btnCart, 'Thêm vào giỏ');
        }
        if (btnBuy) {
            btnBuy.disabled = true;
            setBtnLabel(btnBuy, 'Mua ngay');
        }
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
        setBtnLabel(btn, 'Đang thêm...');
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
            // Giữ nút bấm được cho lần thêm tiếp theo (số lượng đã về mặc định 1)
            renderSummary();
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
        setBtnLabel(btn, 'Đang xử lý...');
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

// Khởi tạo số lượng mặc định = 1 cho sản phẩm đơn lẻ (chỉ UI)
if (document.getElementById('lo-row-0')) {
    changeLo(0, 1);
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

(function() {
    const giaMoiBoGoc = {{ isset($quote) && ($type ?? '') === 'bo-hoa-tu-tao' ? (float) $quote['gia_moi_bo'] : 0 }};
    const soLuongBo = {{ isset($preview) && isset($preview['so_luong']) ? (int) $preview['so_luong'] : 1 }};
    const sel = document.getElementById('thiepSelectTuTao');
    const priceVal = document.getElementById('priceValTuTao');
    const priceTotal = document.getElementById('priceTotalTuTao');
    const thiepNotice = document.getElementById('thiepTotalNotice');

    if (!sel || !priceVal || !priceTotal || !giaMoiBoGoc) return;

    function recalc() {
        const opt = sel.options[sel.selectedIndex];
        const giaThiep = parseInt(opt ? (opt.dataset.gia || 0) : 0);
        const giaMoiBoMoi = giaMoiBoGoc + giaThiep;
        const tongMoi = giaMoiBoMoi * soLuongBo;

        priceVal.innerHTML = new Intl.NumberFormat('vi-VN').format(giaMoiBoMoi) + '₫ <span style="font-size:15px; font-weight:400; color:#6b7280;">/ bó</span>';
        priceTotal.textContent = new Intl.NumberFormat('vi-VN').format(tongMoi) + '₫';

        if (giaThiep > 0) {
            thiepNotice.style.display = 'block';
            thiepNotice.textContent = '• Thiệp (' + soLuongBo + ' cái): +' + new Intl.NumberFormat('vi-VN').format(giaThiep * soLuongBo) + '₫ (' + new Intl.NumberFormat('vi-VN').format(giaThiep) + '₫/cái)';
        } else {
            thiepNotice.style.display = 'none';
        }
    }

    sel.addEventListener('change', recalc);
    recalc();
})();

function changeTuTaoImg(url, el) {
    const main = document.getElementById('mainTuTaoImg');
    if (main) main.src = url;
    document.querySelectorAll('.custom-mix-thumb-item').forEach(item => item.classList.remove('active'));
    if (el) el.classList.add('active');
}

function prevTuTaoImg() {
    const thumbs = Array.from(document.querySelectorAll('.custom-mix-thumb-item'));
    if (!thumbs.length) return;
    const activeIdx = thumbs.findIndex(t => t.classList.contains('active'));
    const prevIdx = (activeIdx - 1 + thumbs.length) % thumbs.length;
    thumbs[prevIdx].click();
}

function nextTuTaoImg() {
    const thumbs = Array.from(document.querySelectorAll('.custom-mix-thumb-item'));
    if (!thumbs.length) return;
    const activeIdx = thumbs.findIndex(t => t.classList.contains('active'));
    const nextIdx = (activeIdx + 1) % thumbs.length;
    thumbs[nextIdx].click();
}

function nextTuTaoThumb() {
    nextTuTaoImg();
}

function toggleHeart(btn) {
    const icon = btn.querySelector('i');
    if (icon) {
        if (icon.classList.contains('far')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.style.color = '#EC3D8F';
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            icon.style.color = '';
        }
    }
}

// Flash Sale Countdown Timer
(function() {
    function updateFsCountdowns() {
        document.querySelectorAll('.fs-countdown').forEach(el => {
            const end = new Date(el.getAttribute('data-end')).getTime();
            const now = new Date().getTime();
            const diff = Math.max(0, end - now);
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            el.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        });
    }
    updateFsCountdowns();
    setInterval(updateFsCountdowns, 1000);
})();
</script>
@endsection
