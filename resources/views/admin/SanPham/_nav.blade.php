{{-- 3 nút chuyển trang: SP / Bó hoa / NVL — dùng chung cho 3 view --}}
@php $activeTab = $activeTab ?? 'san-pham'; @endphp

<ul class="nav nav-pills nav-fill mb-4 khu-vuc-tab-chinh" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $activeTab === 'san-pham' ? 'active' : '' }}"
           href="{{ route('san-pham.index') }}">
            🌷 Quản lý sản phẩm
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $activeTab === 'bo-hoa' ? 'active' : '' }}"
           href="{{ route('san-pham.bo-hoa.index') }}">
            💐 Quản lý bó hoa
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $activeTab === 'nguyen-vat-lieu' ? 'active' : '' }}"
           href="{{ route('san-pham.nguyen-vat-lieu.index') }}">
            🧵 Quản lý nguyên vật liệu
            @if(($soNguyenLieuHetHang ?? 0) > 0)
                <span class="badge bg-danger ms-1">{{ $soNguyenLieuHetHang }} hết hàng</span>
            @elseif(($soNguyenLieuSapHet ?? 0) > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $soNguyenLieuSapHet }} sắp hết</span>
            @endif
        </a>
    </li>
</ul>