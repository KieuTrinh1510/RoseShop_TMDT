@extends('customer.layouts.customer')
@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/HoaTuoi.css') }}">
<style>
.bh-pager {
    display: flex;
    justify-content: center;
    margin-top: 36px;
    gap: 8px;
}
.bh-pager .pagination {
    display: flex;
    gap: 6px;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
    justify-content: center;
}
.bh-pager .page-item .page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0 14px;
    border: 1px solid #f3d4dc;
    border-radius: 10px;
    color: var(--primary, #e8436a);
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    background: #fff;
    transition: all .2s;
}
.bh-pager .page-item.active .page-link {
    background: var(--primary, #e8436a);
    color: #fff;
    border-color: var(--primary, #e8436a);
}
.bh-pager .page-item.disabled .page-link {
    color: #c4b5bb;
    border-color: #f0e2e7;
    cursor: not-allowed;
    background: #faf7f8;
}
.bh-cart-notice {
    margin-bottom: 22px;
    padding: 14px 22px;
    background: #fff;
    border: 1px solid #fce7f3;
    border-radius: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    box-shadow: 0 8px 22px rgba(232, 67, 106, .08);
}
.bh-cart-notice-btn {
    background: var(--primary, #e8436a);
    color: #fff;
    padding: 9px 20px;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    transition: background .2s;
}
.bh-cart-notice-btn:hover {
    background: var(--primary-dark, #b82d4f);
}
</style>
@endsection

@section('title', '💐 Bó hoa đề xuất – Hoa Tươi Shop')
@section('page-id', 'bo-hoa-he-thong')

@section('content')
<main class="flower-page">
    <aside class="flower-sidebar">
        <form method="GET" action="{{ route('customer.bo-hoa.he-thong') }}">
            <h3>DANH MỤC</h3>

            <label>
                <input
                    type="checkbox"
                    id="checkAll"
                    {{ empty(request('loai')) ? 'checked' : '' }}
                >
                Tất cả sản phẩm
            </label>

            @foreach($categories as $category)
            <label>
                <input
                    type="checkbox"
                    class="category-checkbox"
                    name="loai[]"
                    value="{{ $category }}"
                    {{ in_array($category, request('loai', [])) ? 'checked' : '' }}
                >
                {{ $category }}
            </label>
            @endforeach

            <hr>

            <h3>KHOẢNG GIÁ</h3>
            <label>
                <input type="checkbox"
                       name="price[]"
                       value="duoi_350000"
                       {{ in_array('duoi_350000', request('price', [])) ? 'checked' : '' }}>
                Dưới 350.000đ
            </label>

            <label>
                <input type="checkbox"
                       name="price[]"
                       value="350000_450000"
                       {{ in_array('350000_450000', request('price', [])) ? 'checked' : '' }}>
                350.000đ - 450.000đ
            </label>

            <label>
                <input type="checkbox"
                       name="price[]"
                       value="450000_550000"
                       {{ in_array('450000_550000', request('price', [])) ? 'checked' : '' }}>
                450.000đ - 550.000đ
            </label>

            <label>
                <input type="checkbox"
                       name="price[]"
                       value="tren_550000"
                       {{ in_array('tren_550000', request('price', [])) ? 'checked' : '' }}>
                Trên 550.000đ
            </label>

            <hr>

            <h3>KÍCH THƯỚC</h3>
            @foreach(['S' => 'Size S (Nhỏ)', 'M' => 'Size M (Vừa)', 'L' => 'Size L (Lớn)'] as $sKey => $sLabel)
            <label>
                <input type="checkbox"
                       name="size[]"
                       value="{{ $sKey }}"
                       {{ in_array($sKey, request('size', [])) ? 'checked' : '' }}>
                {{ $sLabel }}
            </label>
            @endforeach

            <button type="submit" class="filter-btn">
                Lọc sản phẩm
            </button>
        </form>
    </aside>

    <section class="flower-content">
        @auth
        @php $soBoTrongGio = count(session('gio_bo_hoa', [])); @endphp
        @if($soBoTrongGio > 0)
        <div class="bh-cart-notice">
            <span style="color:#725e67;font-size:14px;">
                💐 Bạn có <strong>{{ $soBoTrongGio }}</strong> bó hoa mẫu trong giỏ
            </span>
            <a href="{{ route('customer.gio-hang') }}" class="bh-cart-notice-btn">
                Xem giỏ hàng →
            </a>
        </div>
        @endif
        @endauth

        @if($mauBoHoas->isEmpty())
            <p style="padding:24px;color:#725e67;font-size:15px;background:#fff;border-radius:18px;text-align:center;box-shadow: 0 8px 22px rgba(232, 67, 106, .10);">
                Không tìm thấy mẫu bó hoa nào phù hợp với bộ lọc đã chọn.
            </p>
        @else
            <div class="flower-grid">
                @foreach($mauBoHoas as $mau)
                    @php
                        $anh = \App\Http\Controllers\Admin\BoHoaController::anhMau($mau);
                        $kieuDangText = \App\Http\Controllers\Admin\BoHoaController::KIEU_DANG[$mau->kieu_bo_hoa] ?? $mau->kieu_bo_hoa;
                    @endphp

                    <div class="flower-card">
                        <img src="{{ $anh }}" alt="{{ $mau->ten_mau }}" loading="lazy">

                        <div class="flower-card-body">
                            <h3>{{ $mau->ten_mau }}</h3>
                            <p class="flower-desc">
                                <strong>Mô tả:</strong>
                                {{ $mau->mo_ta ?: ($kieuDangText . ' · Size ' . $mau->size) }}
                            </p>

                            <p class="flower-price">
                                {{ number_format($mau->gia_hien_thi ?? 0, 0, ',', '.') }}đ
                                <span>/ bó</span>
                            </p>
                            <div class="home-card-bottom">
                                <span>Size: {{ $mau->size }}</span>
                                <a href="{{ route('customer.mau-bo-hoa.chi-tiet', $mau->ma_mau_bo_hoa) }}" class="detail-btn">
                                    Chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($mauBoHoas instanceof \Illuminate\Pagination\LengthAwarePaginator && $mauBoHoas->hasPages())
                <div class="bh-pager">
                    {{ $mauBoHoas->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const categories = document.querySelectorAll('.category-checkbox');

    if (!checkAll) return;

    checkAll.addEventListener('change', function () {
        categories.forEach(item => {
            item.checked = this.checked;
        });
    });

    categories.forEach(item => {
        item.addEventListener('change', function () {
            const checkedCount = document.querySelectorAll('.category-checkbox:checked').length;
            checkAll.checked = checkedCount === categories.length;
        });
    });
});
</script>
@endsection