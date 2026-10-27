@extends('customer.layouts.customer')

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/HoaTuoi.css') }}">
@endsection

@section('title', 'Quà Tặng')

@section('content')
<main class="flower-page">
    <aside class="flower-sidebar">
        <form method="GET">
            <h3>KHOẢNG GIÁ</h3>

            <label>
                <input type="checkbox" name="price[]" value="duoi_20000"
                    {{ in_array('duoi_20000', request('price', [])) ? 'checked' : '' }}>
                Dưới 20.000đ
            </label>

            <label>
                <input type="checkbox" name="price[]" value="20000_50000"
                    {{ in_array('20000_50000', request('price', [])) ? 'checked' : '' }}>
                20.000đ - 50.000đ
            </label>

            <label>
                <input type="checkbox" name="price[]" value="50000_100000"
                    {{ in_array('50000_100000', request('price', [])) ? 'checked' : '' }}>
                50.000đ - 100.000đ
            </label>

            <label>
                <input type="checkbox" name="price[]" value="tren_100000"
                    {{ in_array('tren_100000', request('price', [])) ? 'checked' : '' }}>
                Trên 100.000đ
            </label>

            <button type="submit" class="filter-btn">
                Lọc sản phẩm
            </button>
        </form>
    </aside>

    <section class="flower-content">
        <div class="flower-grid">
            @forelse($quaTangs as $sp)
                @php
                    $loConHang = $sp->chiTietNhaps
                        ->where('so_luong_con_lai', '>', 0);

                    $gia = $sp->gia_ban_hien_tai;
                    $tonKho = $loConHang->sum('so_luong_con_lai');
                @endphp

                <div class="flower-card" onclick="if (!event.target.closest('a')) { window.location.href = '{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}'; }">
                    <div class="flower-card-img">
                        <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="flower-card-img-link">
                            <img src="{{ $sp->anh }}" alt="{{ $sp->ten_san_pham }}" loading="lazy" onerror="this.onerror=null; this.src='{{ asset('img/placeholder-flower.svg') }}';">
                        </a>
                    </div>

                    <div class="flower-card-body">
                        <h3>
                            <a href="{{ route('customer.san-pham.chi-tiet', $sp->ma_san_pham) }}" class="flower-card-title-link" title="{{ $sp->ten_san_pham }}">
                                {{ $sp->ten_san_pham }}
                            </a>
                        </h3>

                        <p class="flower-desc" title="{{ $sp->mo_ta ?? 'Chưa có mô tả' }}">
                            <strong>Mô tả:</strong>
                            {{ $sp->mo_ta ?? 'Chưa có mô tả' }}
                        </p>

                        <div class="flower-price">
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
                                        <span style="font-size:12px; font-weight:normal; color:#6b7280;">/ sản phẩm</span>
                                    </div>
                                </div>
                            @else
                                {{ $gia ? number_format($gia, 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                <span>/ sản phẩm</span>
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
            @empty
                <p style="color:#6b7280;">Chưa có sản phẩm quà tặng.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection