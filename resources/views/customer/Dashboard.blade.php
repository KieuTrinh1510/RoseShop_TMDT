@extends('customer.layouts.customer')

@section('title', '🌸 Cửa Hàng Hoa Tươi')
@section('page-id', 'dashboard')

@section('content')
<div class="main-wrap">
    <!-- <section class="home-block">
        <div class="home-block-title">
            <h2>Mua bó hoa</h2>
            <a href="{{ route('customer.bo-hoa.he-thong') }}">Khám phá bó hoa →</a>
        </div>
        <p>
            Chọn <a href="{{ route('customer.bo-hoa.he-thong') }}">bó hoa cửa hàng đề xuất</a>
            hoặc <a href="{{ route('customer.bo-hoa.tu-tao') }}">tự tạo bó theo sở thích</a>.
        </p>
    </section> -->

    @if(request('q'))

        {{-- KẾT QUẢ TÌM KIẾM --}}
        <section class="home-block">
            <div class="home-block-title">
                <h2>🔍 Kết quả tìm kiếm: "{{ request('q') }}"</h2>
                <a href="{{ route('customer.dashboard') }}">Quay lại trang chủ</a>
            </div>

            @if($sanPhams->isEmpty())
                <p style="padding:20px;color:#ef4444;font-size:16px;background:#fff;border-radius:14px;">
                    Không tìm thấy sản phẩm phù hợp với từ khóa
                    <strong>"{{ request('q') }}"</strong>.
                </p>
            @else
                <div class="home-product-grid">
                    @foreach($sanPhams as $sp)
                        @php
                            $loConHang = $sp->chiTietNhaps->where('so_luong_con_lai', '>', 0);
                            $gia = $sp->gia_ban_hien_tai;
                            $tonKho = $loConHang->sum('so_luong_con_lai');
                        @endphp

                        <div class="home-product-card" onclick="if (!event.target.closest('a')) { window.location.href = '{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}'; }">
                            <div class="home-product-img">
                                <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-img-link">
                                    <img src="{{ $sp->anh }}" alt="{{ $sp->ten_san_pham }}" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
                                </a>
                            </div>

                            <div class="home-product-info">
                                <h3>
                                    <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-title-link" title="{{ $sp->ten_san_pham }}">
                                        {{ $sp->ten_san_pham }}
                                    </a>
                                </h3>

                                <p class="home-product-desc" title="{{ $sp->mo_ta ?? 'Chưa có mô tả' }}">
                                    <strong>Mô tả:</strong>
                                    {{ $sp->mo_ta ?? 'Chưa có mô tả' }}
                                </p>

                                <div class="price">
                                    @php $fs = $sp->active_flash_sale; @endphp
                                    @if($fs && $fs->available_quota > 0)
                                        <div style="display:flex; flex-direction:column; gap:2px;">
                                            <div style="display:flex; align-items:center; gap:6px;">
                                                <span style="text-decoration:line-through; color:#98A2B3; font-size:12px;">
                                                    {{ number_format($gia, 0, ',', '.') }}đ
                                                </span>
                                                <span style="background:#FEE4E2; color:#D92D20; font-size:10.5px; font-weight:700; padding:1px 5px; border-radius:999px;">
                                                    -{{ $fs->phan_tram_giam }}%
                                                </span>
                                            </div>
                                            <div style="color:#EC3D8F; font-weight:700; font-size:15px;">
                                                {{ number_format($fs->gia_flash_sale, 0, ',', '.') }}đ
                                            </div>
                                        </div>
                                    @else
                                        {{ $gia ? number_format($gia, 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                    @endif
                                </div>

                                <div class="home-card-bottom">
                                    <span>Sản phẩm: {{ $tonKho }}</span>

                                    <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="detail-btn">
                                        Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    @else

        {{-- SLIDER --}}
        <section class="home-slider">
            <div class="slider-track" id="sliderTrack">
                <div class="slide-item">
                    <img src="{{ asset('img/ban1.png') }}" alt="Banner 1">
                </div>

                <div class="slide-item">
                    <img src="{{ asset('img/ban2.png') }}" alt="Banner 2">
                </div>

                <div class="slide-item">
                    <img src="{{ asset('img/ban3.png') }}" alt="Banner 3">
                </div>
            </div>

            <button class="slider-btn slider-prev" type="button" onclick="prevSlide()">‹</button>
            <button class="slider-btn slider-next" type="button" onclick="nextSlide()">›</button>

            <div class="slider-dots">
                <span class="slider-dot active" onclick="goSlide(0)"></span>
                <span class="slider-dot" onclick="goSlide(1)"></span>
                <span class="slider-dot" onclick="goSlide(2)"></span>
            </div>
        </section>

        {{-- POLICY --}}
        <section class="home-policy">
            <div class="policy-item">
                <div class="policy-icon">🚚</div>
                <div>
                    <h4>Giao hàng nhanh</h4>
                    <p>Giao 2h-4h trong nội thành</p>
                </div>
            </div>

            <div class="policy-item">
                <div class="policy-icon">💐</div>
                <div>
                    <h4>Hoa tươi mỗi ngày</h4>
                    <p>Cam kết hoa tươi 100%</p>
                </div>
            </div>

            <div class="policy-item">
                <div class="policy-icon">💳</div>
                <div>
                    <h4>Thanh toán dễ dàng</h4>
                    <p>Nhiều hình thức thanh toán</p>
                </div>
            </div>

            <div class="policy-item">
                <div class="policy-icon">☎️</div>
                <div>
                    <h4>Hỗ trợ 24/7</h4>
                    <p>Hotline: 0357 634 696</p>
                </div>
            </div>
        </section>

        @if(isset($activeFlashSales) && $activeFlashSales->isNotEmpty())
        {{-- FLASH SALE SECTION (Shopee-inspired, RoseShop styling) --}}
        <section class="home-block home-flash-sale-section" style="background: linear-gradient(180deg, #FFF5F8 0%, #FFFFFF 100%); padding: 24px 20px; border-radius: 16px; border: 1px solid #FFE4EC; margin-bottom: 32px;">
            <div class="home-block-title" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; border-bottom:none;">
                <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <h2 style="color:#EC3D8F; font-size:22px; font-weight:800; display:flex; align-items:center; gap:8px; margin:0; letter-spacing:0.5px;">
                        <i class="fa-solid fa-bolt" style="color:#F04438;"></i> FLASH SALE
                    </h2>
                    @if($nearestFlashSaleEnd)
                    <div style="display:flex; align-items:center; gap:8px; background:#FFFFFF; padding:4px 12px; border-radius:20px; border:1px solid #FECDD6; font-size:13px; color:#475467;">
                        <span>Kết thúc sau</span>
                        <div class="fs-countdown-box" style="display:flex; gap:4px; font-weight:700; color:#EC3D8F;">
                            <span class="fs-countdown" data-end="{{ $nearestFlashSaleEnd->toISOString() }}">--:--:--</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="home-product-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                @foreach($activeFlashSales as $fs)
                    @php
                        $isBoHoa = !empty($fs->ma_bo_hoa);
                        $itemTitle = $isBoHoa ? ($fs->boHoa->ten_bo_hoa ?? 'Bó hoa') : ($fs->sanPham->ten_san_pham ?? 'Sản phẩm');
                        $itemImg = $isBoHoa
                            ? (\App\Http\Controllers\Admin\BoHoaController::anhMau($fs->boHoa->mauBoHoa ?? null))
                            : ($fs->sanPham->anh ?? asset('img/placeholder-flower.svg'));
                        $detailUrl = $isBoHoa
                            ? ($fs->boHoa->ma_mau_bo_hoa ? route('customer.mau-bo-hoa.chi-tiet', $fs->boHoa->ma_mau_bo_hoa) : 'javascript:void(0)')
                            : route('customer.san-pham.chi-tiet', $fs->ma_san_pham);
                        $giaGoc = $isBoHoa ? (float)$fs->boHoa->gia_ban : (float)($fs->sanPham->gia_ban_hien_tai ?? 0);
                        $giaSale = (float)$fs->gia_flash_sale;
                        $quotaConLai = $fs->available_quota;
                    @endphp

                    <div class="home-product-card fs-product-card" style="position:relative; border-color:#FECDD6; overflow:hidden;" onclick="if (!event.target.closest('a')) { window.location.href = '{{ $detailUrl }}'; }">
                        <div style="position:absolute; top:8px; left:8px; z-index:3; background:#F04438; color:#fff; font-size:11px; font-weight:700; padding:3px 8px; border-radius:4px; box-shadow:0 2px 4px rgba(240,68,56,0.25);">
                            -{{ $fs->phan_tram_giam }}%
                        </div>

                        <div class="home-product-img">
                            <a href="{{ $detailUrl }}" class="home-product-img-link">
                                <img src="{{ $itemImg }}" alt="{{ $itemTitle }}" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
                            </a>
                        </div>

                        <div class="home-product-info">
                            <h3>
                                <a href="{{ $detailUrl }}" class="home-product-title-link" title="{{ $itemTitle }}">
                                    {{ $itemTitle }}
                                </a>
                            </h3>

                            <div class="price" style="display:flex; flex-direction:column; gap:2px; margin-top:6px;">
                                <span style="text-decoration:line-through; color:#98A2B3; font-size:13px;">
                                    {{ number_format($giaGoc, 0, ',', '.') }}đ
                                </span>
                                <div style="color:#EC3D8F; font-weight:800; font-size:18px;">
                                    {{ number_format($giaSale, 0, ',', '.') }}đ
                                </div>
                            </div>

                            <div style="margin-top:8px;">
                                <div style="display:flex; justify-content:space-between; font-size:11.5px; color:#667085; margin-bottom:4px;">
                                    <span>Đã bán: {{ $fs->so_luong_da_ban }}</span>
                                    <span>Còn lại: <strong style="color:#EC3D8F;">{{ $quotaConLai }}</strong></span>
                                </div>
                                <div style="width:100%; height:6px; background:#F2F4F7; border-radius:3px; overflow:hidden;">
                                    @php
                                        $percentSold = $fs->so_luong > 0 ? min(100, round(($fs->so_luong_da_ban / $fs->so_luong) * 100)) : 0;
                                    @endphp
                                    <div style="width:{{ max(8, $percentSold) }}%; height:100%; background:linear-gradient(90deg, #F04438 0%, #EC3D8F 100%); border-radius:3px;"></div>
                                </div>
                            </div>

                            <div class="home-card-bottom" style="margin-top:12px;">
                                <a href="{{ $detailUrl }}" class="detail-btn" style="background:#EC3D8F; color:#fff; width:100%; text-align:center; padding:7px 0; border-radius:6px; font-weight:600;">
                                    Mua ngay
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- HOA TƯƠI BÁN CHẠY --}}
        <section class="home-block">
            <div class="home-block-title">
                <h2>🌸 Hoa tươi bán chạy</h2>
                <a href="{{ route('customer.hoa-tuoi') }}">Xem tất cả</a>
            </div>

            <div class="home-product-grid">
                @foreach($hoaTuoiBanChay as $sp)
                    @php
                        $loConHang = $sp->chiTietNhaps->where('so_luong_con_lai', '>', 0);
                        $gia = $sp->gia_ban_hien_tai;
                        $tonKho = $loConHang->sum('so_luong_con_lai');
                    @endphp

                    <div class="home-product-card" onclick="if (!event.target.closest('a')) { window.location.href = '{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}'; }">
                        <div class="home-product-img">
                            <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-img-link">
                                <img src="{{ $sp->anh }}" alt="{{ $sp->ten_san_pham }}" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
                            </a>
                        </div>

                        <div class="home-product-info">
                            <h3>
                                <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-title-link" title="{{ $sp->ten_san_pham }}">
                                    {{ $sp->ten_san_pham }}
                                </a>
                            </h3>

                            <p class="home-product-desc" title="{{ $sp->mo_ta ?? 'Chưa có mô tả' }}">
                                <strong>Mô tả:</strong>
                                {{ $sp->mo_ta ?? 'Chưa có mô tả' }}
                            </p>

                            <div class="price">
                                @php $fs = $sp->active_flash_sale; @endphp
                                @if($fs && $fs->available_quota > 0)
                                    <div style="display:flex; flex-direction:column; gap:2px;">
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <span style="text-decoration:line-through; color:#98A2B3; font-size:12px;">
                                                {{ number_format($gia, 0, ',', '.') }}đ
                                            </span>
                                            <span style="background:#FEE4E2; color:#D92D20; font-size:10.5px; font-weight:700; padding:1px 5px; border-radius:999px;">
                                                -{{ $fs->phan_tram_giam }}%
                                            </span>
                                        </div>
                                        <div style="color:#EC3D8F; font-weight:700; font-size:15px;">
                                            {{ number_format($fs->gia_flash_sale, 0, ',', '.') }}đ
                                            <span style="font-size:12px; font-weight:normal; color:#6b7280;">/ {{ $sp->loai_san_pham === 'PHU_KIEN' ? 'cái' : 'cành' }}</span>
                                        </div>
                                    </div>
                                @else
                                    {{ $gia ? number_format($gia, 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                    <span>/ {{ $sp->loai_san_pham === 'PHU_KIEN' ? 'cái' : 'cành' }}</span>
                                @endif
                            </div>

                            <div class="home-card-bottom">
                                <span>Sản phẩm: {{ $tonKho }}</span>

                                <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="detail-btn">
                                    Chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- PHỤ KIỆN NỔI BẬT --}}
        <section class="home-block">
            <div class="home-block-title">
                <h2>🎀 Phụ kiện nổi bật</h2>
                <a href="{{ route('customer.phu-kien') }}">Xem tất cả</a>
            </div>

            @if($phuKienNoiBat->isEmpty())
                <p style="color:#6b7280;font-size:15px;">Chưa có phụ kiện nổi bật.</p>
            @else
                <div class="home-product-grid">
                    @foreach($phuKienNoiBat as $sp)
                        @php
                            $loConHang = $sp->chiTietNhaps->where('so_luong_con_lai', '>', 0);
                            $gia = $sp->gia_ban_hien_tai;
                            $tonKho = $loConHang->sum('so_luong_con_lai');
                        @endphp

                        <div class="home-product-card" onclick="if (!event.target.closest('a')) { window.location.href = '{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}'; }">
                            <div class="home-product-img">
                                <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-img-link">
                                    <img src="{{ $sp->anh }}" alt="{{ $sp->ten_san_pham }}" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
                                </a>
                            </div>

                            <div class="home-product-info">
                                <h3>
                                    <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-title-link" title="{{ $sp->ten_san_pham }}">
                                        {{ $sp->ten_san_pham }}
                                    </a>
                                </h3>

                                <p class="home-product-desc" title="{{ $sp->mo_ta ?? 'Chưa có mô tả' }}">
                                    <strong>Mô tả:</strong>
                                    {{ $sp->mo_ta ?? 'Chưa có mô tả' }}
                                </p>

                                <div class="price">
                                    @php $fs = $sp->active_flash_sale; @endphp
                                    @if($fs && $fs->available_quota > 0)
                                        <div style="display:flex; flex-direction:column; gap:2px;">
                                            <div style="display:flex; align-items:center; gap:6px;">
                                                <span style="text-decoration:line-through; color:#98A2B3; font-size:12px;">
                                                    {{ number_format($gia, 0, ',', '.') }}đ
                                                </span>
                                                <span style="background:#FEE4E2; color:#D92D20; font-size:10.5px; font-weight:700; padding:1px 5px; border-radius:999px;">
                                                    -{{ $fs->phan_tram_giam }}%
                                                </span>
                                            </div>
                                            <div style="color:#EC3D8F; font-weight:700; font-size:15px;">
                                                {{ number_format($fs->gia_flash_sale, 0, ',', '.') }}đ
                                                <span style="font-size:12px; font-weight:normal; color:#6b7280;">/ cái</span>
                                            </div>
                                        </div>
                                    @else
                                        {{ $gia ? number_format($gia, 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                        <span>/ cái</span>
                                    @endif
                                </div>

                                <div class="home-card-bottom">
                                    <span>Sản phẩm: {{ $tonKho }}</span>

                                    <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="detail-btn">
                                        Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- QUÀ TẶNG NỔI BẬT --}}
        <section class="home-block">
            <div class="home-block-title">
                <h2>🎁 Quà tặng nổi bật</h2>
                <a href="{{ route('customer.qua-tang') }}">Xem tất cả</a>
            </div>

            @if($quaTangNoiBat->isEmpty())
                <p style="color:#6b7280;font-size:15px;">Chưa có quà tặng nổi bật.</p>
            @else
                <div class="home-product-grid">
                    @foreach($quaTangNoiBat as $sp)
                        @php
                            $loConHang = $sp->chiTietNhaps->where('so_luong_con_lai', '>', 0);
                            $gia = $sp->gia_ban_hien_tai;
                            $tonKho = $loConHang->sum('so_luong_con_lai');
                        @endphp

                        <div class="home-product-card" onclick="if (!event.target.closest('a')) { window.location.href = '{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}'; }">
                            <div class="home-product-img">
                                <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-img-link">
                                    <img src="{{ $sp->anh }}" alt="{{ $sp->ten_san_pham }}" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
                                </a>
                            </div>

                            <div class="home-product-info">
                                <h3>
                                    <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="home-product-title-link" title="{{ $sp->ten_san_pham }}">
                                        {{ $sp->ten_san_pham }}
                                    </a>
                                </h3>

                                <p class="home-product-desc" title="{{ $sp->mo_ta ?? 'Chưa có mô tả' }}">
                                    <strong>Mô tả:</strong>
                                    {{ $sp->mo_ta ?? 'Chưa có mô tả' }}
                                </p>

                                <div class="price">
                                    {{ $gia ? number_format($gia, 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                    <span>/ sản phẩm</span>
                                </div>

                                <div class="home-card-bottom">
                                    <span>Sản phẩm: {{ $tonKho }}</span>

                                    <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="detail-btn">
                                        Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- TIN TỨC NỔI BẬT --}}
        <section class="home-block news-home-block">
            <div class="home-block-title">
                <h2>📰 Tin tức nổi bật</h2>
                <a href="{{ route('customer.tin-tuc') }}">Xem tất cả</a>
            </div>

            <div class="news-grid">
                <div class="news-card">
                    <img src="{{ asset('img/TULIP.jpg') }}" alt="Cách bảo quản hoa">
                    <div>
                        <h3>Cách bảo quản hoa hồng lâu tàn</h3>
                        <p>Mẹo nhỏ giúp hoa hồng tươi lâu hơn và giữ màu đẹp.</p>
                        <a href="{{ route('customer.tin-tuc.chi-tiet', 'bao-quan-hoa-hong') }}">
                            Xem chi tiết →
                        </a>
                    </div>
                </div>

                <div class="news-card">
                    <img src="{{ asset('img/hoa tulip.jpeg') }}" alt="Ý nghĩa hoa">
                    <div>
                        <h3>Ý nghĩa các màu hoa tươi</h3>
                        <p>Mỗi loài hoa và màu sắc đều mang một thông điệp riêng.</p>
                        <a href="{{ route('customer.tin-tuc.chi-tiet', 'y-nghia-mau-hoa') }}">
                            Xem chi tiết →
                        </a>
                    </div>
                </div>

                <div class="news-card">
                    <img src="{{ asset('img/hoa ly.jpeg') }}" alt="Hoa sinh nhật">
                    <div>
                        <h3>Gợi ý hoa tặng sinh nhật</h3>
                        <p>Những mẫu hoa đẹp, dễ tặng và phù hợp nhiều đối tượng.</p>
                        <a href="{{ route('customer.tin-tuc.chi-tiet', 'goi-y-hoa-tang-sinh-nhat') }}">
                            Xem chi tiết →
                        </a>
                    </div>
                </div>
            </div>
        </section>

    @endif

</div>
@endsection

@section('scripts')
<script>
let currentSlide = 0;
const totalSlides = 3;

function updateSlider() {
    const track = document.getElementById('sliderTrack');
    const dots = document.querySelectorAll('.slider-dot');

    if (!track) return;

    track.style.transform = `translateX(-${currentSlide * 100}%)`;

    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    updateSlider();
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    updateSlider();
}

function goSlide(index) {
    currentSlide = index;
    updateSlider();
}

document.addEventListener('DOMContentLoaded', function () {
    updateSlider();

    setInterval(function () {
        nextSlide();
    }, 3000);

    function updateFsCountdowns() {
        document.querySelectorAll('.fs-countdown').forEach(el => {
            const endStr = el.getAttribute('data-end');
            if (!endStr) return;
            const end = new Date(endStr).getTime();
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
});
</script>
@endsection