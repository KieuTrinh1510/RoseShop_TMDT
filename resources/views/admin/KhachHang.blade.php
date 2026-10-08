@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/Admin/KhachHang.css') }}">
@endpush

@section('content')

<div class="rs-management-page">

    {{-- 1. HEADER TRANG --}}
    <div class="rs-header-block">
        <div class="rs-header-icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div>
            <h1 class="rs-header-title">Quản lý khách hàng</h1>
            <p class="rs-header-subtitle">Theo dõi hồ sơ, quyền COD, lịch sử vi phạm và điểm tích lũy</p>
        </div>
    </div>

    {{-- THÔNG BÁO FLASH --}}
    @if(session('success'))
        <div style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; border-radius:10px; padding:12px 18px; margin-bottom:18px; font-size:13.5px; font-weight:500;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fee2e2; color:#b91c1c; border:1px solid #fecdd3; border-radius:10px; padding:12px 18px; margin-bottom:18px; font-size:13.5px; font-weight:500;">
            ✕ {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fee2e2; color:#b91c1c; border:1px solid #fecdd3; border-radius:10px; padding:12px 18px; margin-bottom:18px; font-size:13.5px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2. HÀNG 4 THẺ KPI THỐNG KÊ --}}
    <div class="rs-kpi-grid">
        <div class="rs-kpi-card">
            <div class="rs-kpi-icon pink">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Tổng KH</span>
                <span class="rs-kpi-val">{{ number_format($tongKhachHang) }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon amber">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Khách thân thiết</span>
                <span class="rs-kpi-val">{{ number_format($khachThanThiet) }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 3h12l4 6-10 12L2 9z"/>
                    <path d="M11 3 8 9l4 12 4-12-3-6"/>
                    <path d="M2 9h20"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Tổng điểm</span>
                <span class="rs-kpi-val">{{ number_format($tongDiemTichLuy) }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Đang hoạt động</span>
                <span class="rs-kpi-val">{{ number_format($dangHoatDong) }}</span>
            </div>
        </div>
    </div>

    {{-- 3. CARD CHÍNH: TOOLBAR 2 TẦNG + TABLE 8 CỘT --}}
    <div class="rs-card">

        {{-- Form lọc: Tất cả bộ lọc + Ô tìm kiếm + Nút tìm kiếm trên cùng 1 hàng --}}
        <form method="GET" action="{{ route('khach-hang.index') }}" id="filterForm" class="rs-toolbar-unified">
            <input type="hidden" name="per_page" id="kh_per_page_hidden" value="{{ $perPage }}">

            {{-- 1. Lọc trạng thái tài khoản --}}
            <select name="trang_thai" class="rs-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="HOAT_DONG" {{ request('trang_thai') === 'HOAT_DONG' ? 'selected' : '' }}>Hoạt động</option>
                <option value="BI_KHOA" {{ request('trang_thai') === 'BI_KHOA' ? 'selected' : '' }}>Bị khóa</option>
            </select>

            {{-- 2. Lọc quyền COD --}}
            <select name="quyen_cod" class="rs-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Tất cả quyền COD</option>
                <option value="CHO_PHEP" {{ request('quyen_cod') === 'CHO_PHEP' ? 'selected' : '' }}>Được phép COD</option>
                <option value="GIOI_HAN" {{ request('quyen_cod') === 'GIOI_HAN' ? 'selected' : '' }}>Bị giới hạn COD</option>
            </select>

            {{-- 3. Sắp xếp điểm tích lũy --}}
            <select name="sort" class="rs-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Sắp xếp điểm</option>
                <option value="diem_desc" {{ request('sort') === 'diem_desc' ? 'selected' : '' }}>Điểm: Cao → Thấp</option>
                <option value="diem_asc" {{ request('sort') === 'diem_asc' ? 'selected' : '' }}>Điểm: Thấp → Cao</option>
            </select>

            {{-- 4. Ô tìm kiếm nội dung --}}
            <div class="rs-search-box-unified">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text"
                       name="keyword"
                       value="{{ request('keyword') }}"
                       placeholder="Tìm kiếm theo tên, mã KH, SĐT, email..."
                       autocomplete="off">
            </div>

            {{-- 5. Nút tìm kiếm --}}
            <button type="submit" class="rs-btn-search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Tìm kiếm
            </button>

            {{-- 6. Nút Bỏ lọc nếu đang có filter --}}
            @if(request()->anyFilled(['keyword', 'trang_thai', 'quyen_cod', 'sort']))
                <a href="{{ route('khach-hang.index', ['per_page' => $perPage]) }}" class="rs-btn-filter-clear" title="Đặt lại bộ lọc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Bỏ lọc
                </a>
            @endif
        </form>

        {{-- BẢNG KHÁCH HÀNG 7 CỘT TINH GỌN (THÔNG TIN CẦN NHÌN NHANH, KHÔNG TRÀN MÀN HÌNH) --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <colgroup>
                    <col style="width: 110px;">
                    <col style="width: 230px;">
                    <col style="width: 150px;">
                    <col style="width: 110px;">
                    <col style="width: 120px;">
                    <col style="width: 130px;">
                    <col style="width: 110px;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">Mã KH</th>
                        <th style="text-align: center;">Khách hàng</th>
                        <th style="text-align: center;">Liên hệ</th>
                        <th style="text-align: center;">Điểm</th>
                        <th style="text-align: center;">COD</th>
                        <th style="text-align: center;">Trạng thái</th>
                        <th style="text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($khachHangs as $index => $kh)
                        @php
                            $user = $kh->nguoiDung;
                            $status = $user ? $user->trang_thai : 'HOAT_DONG';
                            $quyenCod = $kh->quyen_cod ?? 'CHO_PHEP';
                            $hasOrders = in_array($kh->ma_khach_hang, $hasOrderCustomerIds);
                            $initial = mb_strtoupper(mb_substr($kh->ten_khach_hang ?? 'K', 0, 1, 'UTF-8'), 'UTF-8');
                            $tongTienMua = $kh->hoa_dons_sum_tong_tien ?? 0;
                            $soDonHang = $kh->hoa_dons_count ?? 0;
                        @endphp
                        <tr>
                            {{-- 1. Mã khách hàng --}}
                            <td style="text-align: center; font-weight: 700; color: #EC3D8F; font-family: monospace; font-size: 13px;">
                                KH-{{ str_pad($kh->ma_khach_hang, 4, '0', STR_PAD_LEFT) }}
                            </td>

                            {{-- 3. Khách hàng (Tên) --}}
                            <td>
                                <div class="rs-customer-cell">
                                    <div class="rs-customer-avatar">{{ $initial }}</div>
                                    <div class="rs-customer-name">{{ $kh->ten_khach_hang }}</div>
                                </div>
                            </td>

                            {{-- 4. Liên hệ (Số điện thoại) --}}
                            <td style="font-weight: 500; font-family: monospace; font-size: 13px;">
                                {{ $kh->so_dien_thoai }}
                            </td>

                            {{-- 5. Điểm --}}
                            <td style="text-align: center;">
                                <span class="rs-points-badge {{ $kh->diem_tich_luy >= 50 ? 'gold' : '' }}">
                                    {{ number_format($kh->diem_tich_luy) }}
                                </span>
                            </td>

                            {{-- 6. COD (Quyền COD) --}}
                            <td style="text-align: center;">
                                @if($quyenCod === 'CHO_PHEP')
                                    <span class="rs-cod-badge ok" title="Được phép sử dụng COD">
                                        ✓ COD
                                    </span>
                                @else
                                    <span class="rs-cod-badge restricted" title="Bị giới hạn quyền COD">
                                        ✕ Giới hạn
                                    </span>
                                @endif
                            </td>

                            {{-- 7. Trạng thái --}}
                            <td style="text-align: center;">
                                @if($status === 'HOAT_DONG')
                                    <span class="rs-badge green">
                                        <span class="rs-badge-dot"></span>
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="rs-badge red">
                                        <span class="rs-badge-dot"></span>
                                        Bị khóa
                                    </span>
                                @endif
                            </td>

                            {{-- 8. Thao tác (👁, ✏, ⋮) --}}
                            <td style="text-align: center;">
                                <div class="rs-actions" style="justify-content: center;">
                                    {{-- Nút 1: 👁 Xem chi tiết đầy đủ hồ sơ, email, địa chỉ, vi phạm COD --}}
                                    <button type="button"
                                            class="rs-action-btn view"
                                            title="Xem chi tiết khách hàng"
                                            onclick='openDetailModal(@json($kh), {{ $soDonHang }}, {{ $tongTienMua }})'>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>

                                    {{-- Nút 2: ✏ Chỉnh sửa thông tin hồ sơ --}}
                                    <button type="button"
                                            class="rs-action-btn edit"
                                            title="Sửa hồ sơ khách hàng"
                                            onclick='openEditModal(@json($kh))'>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="m15 5 4 4"/>
                                        </svg>
                                    </button>

                                    {{-- Nút 3: ⋮ Menu hành động khác (Giới hạn COD, Khóa/Mở, Xóa) --}}
                                    <div class="rs-dropdown">
                                        <button type="button"
                                                class="rs-action-btn more"
                                                title="Tùy chọn khác"
                                                onclick="toggleDropdown(event, 'dropdown-{{ $kh->ma_khach_hang }}')">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="5" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="12" cy="19" r="2"/>
                                            </svg>
                                        </button>
                                        <div class="rs-dropdown-menu" id="dropdown-{{ $kh->ma_khach_hang }}">
                                            @if($quyenCod === 'CHO_PHEP')
                                                <button type="button" class="rs-dropdown-item" onclick='openLimitCodModal(@json($kh))'>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                    Giới hạn quyền COD
                                                </button>
                                            @else
                                                <button type="button" class="rs-dropdown-item" onclick='openRestoreCodModal(@json($kh))'>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                                                    Khôi phục quyền COD
                                                </button>
                                            @endif

                                            @if($status === 'HOAT_DONG')
                                                <button type="button" class="rs-dropdown-item" onclick='openStatusModal(@json($kh), "BI_KHOA")'>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                    Khóa tài khoản
                                                </button>
                                            @else
                                                <button type="button" class="rs-dropdown-item" onclick='openStatusModal(@json($kh), "HOAT_DONG")'>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                                                    Mở khóa tài khoản
                                                </button>
                                            @endif

                                            <button type="button" class="rs-dropdown-item danger" onclick='openDeleteModal(@json($kh), {{ $hasOrders ? "true" : "false" }})'>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                Xóa khách hàng
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="rs-empty-box">
                                    <div class="rs-empty-icon">👥</div>
                                    <div style="font-size:15px; font-weight:600; color:#475467; margin-bottom:4px;">Không tìm thấy khách hàng nào</div>
                                    <div style="font-size:13px; color:#94a3b8;">Vui lòng thử điều chỉnh lại từ khóa hoặc xóa bớt bộ lọc</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 4. FOOTER: Chuẩn hóa theo hình ảnh (Số hàng mỗi trang [10 ▼]   X-Y / Z bản ghi   < 1 ... n >) --}}
        <div class="rs-table-footer">
            <div class="rs-footer-left">
                <div class="rs-per-page-inline">
                    <span class="rs-per-page-label">Số hàng mỗi trang</span>
                    <select class="rs-per-page-select" onchange="document.getElementById('kh_per_page_hidden').value = this.value; document.getElementById('filterForm').submit();">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="rs-records-count">
                    {{ $khachHangs->firstItem() ?? 0 }}-{{ $khachHangs->lastItem() ?? 0 }} / {{ $khachHangs->total() }} bản ghi
                </div>
            </div>

            <div class="rs-footer-pagination">
                <ul class="rs-pagination">
                    {{-- Nút trang trước (<) --}}
                    @if ($khachHangs->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $khachHangs->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                    @endif

                    {{-- Logic sliding window: Chỉ hiển thị trang đầu cuối, ở giữa là ... --}}
                    @php
                        $currentPage = $khachHangs->currentPage();
                        $lastPage = $khachHangs->lastPage();
                        $pages = [];

                        if ($lastPage <= 3) {
                            for ($i = 1; $i <= $lastPage; $i++) {
                                $pages[] = $i;
                            }
                        } else {
                            if ($currentPage == 1) {
                                $pages = [1, '...', $lastPage];
                            } elseif ($currentPage == $lastPage) {
                                $pages = [1, '...', $lastPage];
                            } else {
                                $pages[] = 1;
                                if ($currentPage > 2) {
                                    $pages[] = '...';
                                }
                                $pages[] = $currentPage;
                                if ($currentPage < $lastPage - 1) {
                                    $pages[] = '...';
                                }
                                $pages[] = $lastPage;
                            }
                        }
                    @endphp

                    @foreach ($pages as $p)
                        @if ($p === '...')
                            <li class="page-item disabled"><span class="page-link ellipsis">&hellip;</span></li>
                        @elseif ($p == $currentPage)
                            <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $khachHangs->url($p) }}">{{ $p }}</a></li>
                        @endif
                    @endforeach

                    {{-- Nút trang sau (>) --}}
                    @if ($khachHangs->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $khachHangs->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                    @endif
                </ul>
            </div>
        </div>

    </div>

</div>

{{-- ==========================================================================
     MODAL 1: CHI TIẾT KHÁCH HÀNG (Hiển thị Email, Địa chỉ, Giao dịch & Lịch sử COD)
     ========================================================================== --}}
<div class="rs-modal" id="detailModal">
    <div class="rs-modal-dialog" style="max-width: 660px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Chi tiết khách hàng <span id="detail_ma_khach_hang" style="color:#EC3D8F;"></span>
            </h3>
            <button type="button" class="rs-modal-close" onclick="closeModal('detailModal')">&times;</button>
        </div>

        <div class="rs-modal-body">
            {{-- THÔNG TIN HỒ SƠ --}}
            <div style="margin-bottom: 20px;">
                <h4 style="font-size: 13px; text-transform: uppercase; color: #64748b; font-weight: 700; margin: 0 0 10px; letter-spacing: 0.5px;">Hồ sơ khách hàng</h4>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <div><span style="color:#64748b; font-size:12.5px;">Họ và tên:</span> <strong style="color:#0f172a; display:block;" id="detail_ten_khach_hang">—</strong></div>
                    <div><span style="color:#64748b; font-size:12.5px;">Số điện thoại:</span> <strong style="color:#0f172a; display:block; font-family:monospace;" id="detail_so_dien_thoai">—</strong></div>
                    <div><span style="color:#64748b; font-size:12.5px;">Email:</span> <span style="color:#0f172a; display:block;" id="detail_email">—</span></div>
                    <div><span style="color:#64748b; font-size:12.5px;">Trạng thái tài khoản:</span> <span id="detail_trang_thai_badge" style="display:block; margin-top:2px;">—</span></div>
                    <div style="grid-column: span 2;"><span style="color:#64748b; font-size:12.5px;">Địa chỉ nhận hàng:</span> <span style="color:#334155; display:block;" id="detail_dia_chi">—</span></div>
                </div>
            </div>

            {{-- DỮ LIỆU GIAO DỊCH --}}
            <div style="margin-bottom: 20px;">
                <h4 style="font-size: 13px; text-transform: uppercase; color: #64748b; font-weight: 700; margin: 0 0 10px; letter-spacing: 0.5px;">
                    Tổng quan giao dịch
                </h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <div style="background:#fff; border:1px solid #f1f5f9; border-radius:10px; padding:12px; text-align:center;">
                        <span style="font-size:12px; color:#64748b; display:block; margin-bottom:4px;">Điểm tích lũy</span>
                        <strong style="font-size:18px; color:#db2777;" id="detail_diem_tich_luy">0</strong>
                    </div>
                    <div style="background:#fff; border:1px solid #f1f5f9; border-radius:10px; padding:12px; text-align:center;">
                        <span style="font-size:12px; color:#64748b; display:block; margin-bottom:4px;">Tổng đơn đã mua</span>
                        <strong style="font-size:18px; color:#0284c7;" id="detail_so_don_hang">0</strong>
                    </div>
                    <div style="background:#fff; border:1px solid #f1f5f9; border-radius:10px; padding:12px; text-align:center;">
                        <span style="font-size:12px; color:#64748b; display:block; margin-bottom:4px;">Tổng chi tiêu</span>
                        <strong style="font-size:18px; color:#15803d;" id="detail_tong_tien_mua">0 đ</strong>
                    </div>
                </div>
            </div>

            {{-- QUYỀN COD & LỊCH SỬ VI PHẠM COD --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="font-size: 13px; text-transform: uppercase; color: #64748b; font-weight: 700; margin: 0; letter-spacing: 0.5px;">
                        Quyền COD & Lịch sử vi phạm
                    </h4>
                    <span id="detail_quyen_cod_badge"></span>
                </div>

                {{-- Bảng lịch sử vi phạm COD --}}
                <div id="detail_vi_pham_table_container" style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                    <table class="rs-table-mini">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Thời gian</th>
                                <th style="width: 130px;">Loại ghi nhận</th>
                                <th>Lý do</th>
                                <th style="width: 90px;">Thực hiện</th>
                            </tr>
                        </thead>
                        <tbody id="detail_vi_pham_tbody">
                        </tbody>
                    </table>
                </div>

                {{-- Empty state vi phạm COD --}}
                <div id="detail_vi_pham_empty" style="display:none; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:24px; margin-bottom:4px;">🛡️</div>
                    <strong style="color:#334155; font-size:13.5px;">Khách hàng chưa có vi phạm được ghi nhận</strong>
                    <div style="font-size:12.5px; color:#94a3b8; margin-top:2px;">Lịch sử nhận hàng và thanh toán COD hoàn toàn tốt.</div>
                </div>
            </div>
        </div>

        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="closeModal('detailModal')">Đóng</button>
        </div>
    </div>
</div>

{{-- ==========================================================================
     MODAL 2: CHỈNH SỬA THÔNG TIN KHÁCH HÀNG (#editModal)
     (Tự nhiên, không có thông báo nghiệp vụ rườm rà, ô xám tự hiểu không được sửa)
     ========================================================================== --}}
<div class="rs-modal" id="editModal">
    <div class="rs-modal-dialog">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Chỉnh sửa thông tin khách hàng
            </h3>
            <button type="button" class="rs-modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>

        <form method="POST" id="editCustomerForm" onsubmit="return validateEditCustomer(event)">
            @csrf
            @method('PUT')

            <div class="rs-modal-body">
                <div class="rs-form-grid">
                    {{-- Mã khách hàng (Khung xám readonly tự hiểu không được sửa) --}}
                    <div class="rs-form-group">
                        <label class="rs-form-label">Mã khách hàng</label>
                        <input type="text" id="edit_ma_khach_hang" class="rs-form-control rs-readonly-input" readonly>
                    </div>

                    {{-- Điểm tích lũy (Khung xám readonly tự hiểu không được sửa) --}}
                    <div class="rs-form-group">
                        <label class="rs-form-label">Điểm tích lũy</label>
                        <input type="text" id="edit_diem_tich_luy" class="rs-form-control rs-readonly-input" readonly>
                    </div>

                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Họ và tên khách hàng <span style="color:#EC3D8F;">*</span></label>
                        <input type="text" name="ten_khach_hang" id="edit_ten_khach_hang" class="rs-form-control" oninput="clearErrorKh('edit_ten_khach_hang')">
                        <div class="rs-field-error" id="err_edit_ten_khach_hang"></div>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Số điện thoại <span style="color:#EC3D8F;">*</span></label>
                        <input type="text" name="so_dien_thoai" id="edit_so_dien_thoai" class="rs-form-control" oninput="clearErrorKh('edit_so_dien_thoai')">
                        <div class="rs-field-error" id="err_edit_so_dien_thoai"></div>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Email <span style="color:#EC3D8F;">*</span></label>
                        <input type="email" name="email" id="edit_email" class="rs-form-control" oninput="clearErrorKh('edit_email')">
                        <div class="rs-field-error" id="err_edit_email"></div>
                    </div>

                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Địa chỉ nhận hàng <span style="color:#EC3D8F;">*</span></label>
                        <input type="text" name="dia_chi" id="edit_dia_chi" class="rs-form-control" oninput="clearErrorKh('edit_dia_chi')">
                        <div class="rs-field-error" id="err_edit_dia_chi"></div>
                    </div>
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeModal('editModal')">Hủy</button>
                <button type="submit" class="rs-btn-submit" id="btnSubmitEdit">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
     MODAL 3: GIỚI HẠN QUYỀN COD (#limitCodModal)
     ========================================================================== --}}
<div class="rs-modal" id="limitCodModal">
    <div class="rs-modal-dialog" style="max-width: 480px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title" style="color: #ea580c;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Giới hạn quyền đặt hàng COD
            </h3>
            <button type="button" class="rs-modal-close" onclick="closeModal('limitCodModal')">&times;</button>
        </div>

        <form method="POST" id="limitCodForm">
            @csrf
            @method('PATCH')

            <div class="rs-modal-body">
                <div style="background: #fff7ed; border: 1px solid #ffedd5; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; font-size: 13.5px; color: #9a3412;">
                    Khách hàng: <strong id="limit_cod_customer_name">—</strong>
                </div>

                <div class="rs-form-group" style="margin-bottom: 14px;">
                    <label class="rs-form-label">Lý do vi phạm <span style="color:#D82677;">*</span></label>
                    <select name="loai_vi_pham" class="rs-form-control" required>
                        <option value="BOM_HANG">Bom hàng (Không nhận hàng khi giao)</option>
                        <option value="TU_CHOI_NHAN">Từ chối nhận hàng không có lý do</option>
                        <option value="HUY_KHI_GIAO">Hủy đơn khi hoa đã hoàn thiện/đang giao</option>
                        <option value="KHAC">Vi phạm chính sách nhận hàng khác</option>
                    </select>
                </div>

                <div class="rs-form-group">
                    <label class="rs-form-label">Ghi chú chi tiết <span style="color:#D82677;">*</span></label>
                    <textarea name="ly_do" class="rs-form-control" rows="3" style="height:auto !important; padding:8px 12px;" required placeholder="Nhập lý do giới hạn quyền COD..."></textarea>
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeModal('limitCodModal')">Hủy</button>
                <button type="submit" class="rs-btn-submit orange">Xác nhận giới hạn</button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
     MODAL 4: KHÔI PHỤC QUYỀN COD (#restoreCodModal)
     ========================================================================== --}}
<div class="rs-modal" id="restoreCodModal">
    <div class="rs-modal-dialog" style="max-width: 480px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title" style="color: #15803d;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                Khôi phục quyền đặt hàng COD
            </h3>
            <button type="button" class="rs-modal-close" onclick="closeModal('restoreCodModal')">&times;</button>
        </div>

        <form method="POST" id="restoreCodForm">
            @csrf
            @method('PATCH')

            <div class="rs-modal-body">
                <div style="background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; font-size: 13.5px; color: #166534;">
                    Khách hàng: <strong id="restore_cod_customer_name">—</strong>
                </div>

                <div class="rs-form-group">
                    <label class="rs-form-label">Lý do khôi phục quyền COD <span style="color:#D82677;">*</span></label>
                    <textarea name="ly_do" class="rs-form-control" rows="3" style="height:auto !important; padding:8px 12px;" required placeholder="Nhập lý do khôi phục quyền COD..."></textarea>
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeModal('restoreCodModal')">Hủy</button>
                <button type="submit" class="rs-btn-submit green">Khôi phục quyền COD</button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
     MODAL 5: XÁC NHẬN KHÓA / MỞ KHÓA TÀI KHOẢN (#statusModal)
     ========================================================================== --}}
<div class="rs-modal" id="statusModal">
    <div class="rs-modal-dialog" style="max-width: 460px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title" id="statusModalTitle">
                Xác nhận đổi trạng thái
            </h3>
            <button type="button" class="rs-modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>

        <form method="POST" id="statusCustomerForm" onsubmit="handleStatusSubmit(event)">
            @csrf
            @method('PATCH')
            <input type="hidden" name="target_status" id="statusTargetStatus" value="">

            <div class="rs-modal-body">
                <div style="display:flex; gap:16px; align-items:flex-start;">
                    <div id="statusModalIcon" style="width:44px; height:44px; min-width:44px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    </div>
                    <div>
                        <div style="font-size:14.5px; font-weight:600; color:#0f172a; margin-bottom:6px;" id="statusModalCustomerName"></div>
                        <p style="font-size:13.5px; color:#475467; margin:0; line-height:1.5;" id="statusModalMessage"></p>
                    </div>
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeModal('statusModal')">Hủy</button>
                <button type="submit" class="rs-btn-submit" id="btnConfirmStatus">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
     MODAL 6: XÁC NHẬN XÓA / BẢO TOÀN HỒ SƠ (#deleteModal)
     ========================================================================== --}}
<div class="rs-modal" id="deleteModal">
    <div class="rs-modal-dialog" style="max-width: 480px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D82677" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
                Xác nhận xóa khách hàng
            </h3>
            <button type="button" class="rs-modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>

        {{-- Khi khách hàng đã có đơn hàng: CHẶN XÓA & HƯỚNG DẪN KHÓA --}}
        <div id="deleteBlockedContainer" style="display:none;">
            <div class="rs-modal-body">
                <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:10px; padding:14px; margin-bottom:14px;">
                    <div style="display:flex; gap:10px; align-items:center; color:#be123c; font-weight:700; font-size:14px; margin-bottom:6px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Bảo toàn hồ sơ đơn hàng
                    </div>
                    <div style="font-size:13px; color:#475467; line-height:1.5;">
                        Khách hàng <strong id="deleteBlockedName"></strong> đã phát sinh đơn hàng trong hệ thống. Để bảo toàn lịch sử giao dịch và đối soát, hệ thống <strong style="color:#D82677;">không cho phép xóa vật lý</strong>.
                    </div>
                </div>
                <p style="font-size:13.5px; color:#334155; margin:0; line-height:1.5;">
                    💡 Bạn có thể chuyển tài khoản sang trạng thái <strong>Bị khóa</strong> để ngừng đăng nhập và đặt hàng.
                </p>
            </div>
            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeModal('deleteModal')">Đóng</button>
                <button type="button" class="rs-btn-submit orange" id="btnSwitchToLock">Khóa tài khoản</button>
            </div>
        </div>

        {{-- Khi khách hàng chưa có đơn hàng: CHO PHÉP XÓA VĨNH VIỄN --}}
        <form method="POST" id="deleteCustomerForm" onsubmit="handleDeleteSubmit(event)">
            @csrf
            @method('DELETE')

            <div id="deleteAllowedContainer" style="display:none;">
                <div class="rs-modal-body">
                    <p style="font-size:14px; color:#1e293b; margin:0 0 12px; line-height:1.5;">
                        Bạn có chắc chắn muốn xóa vĩnh viễn khách hàng <strong id="deleteAllowedName" style="color:#0f172a;"></strong>?
                    </p>
                    <div style="background:#fee2e2; border-left:4px solid #ef4444; border-radius:6px; padding:10px 14px; font-size:13px; color:#991b1b; line-height:1.4;">
                        ⚠️ Thao tác này sẽ xóa vĩnh viễn thông tin khách hàng và tài khoản đăng nhập tương ứng.
                    </div>
                </div>
                <div class="rs-modal-footer">
                    <button type="button" class="rs-btn-cancel" onclick="closeModal('deleteModal')">Hủy</button>
                    <button type="submit" class="rs-btn-submit danger" id="btnConfirmDelete">Xóa vĩnh viễn</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- JAVASCRIPT ĐIỀU KHIỂN GIAO DIỆN & MODAL --}}
<script>
    // 1. Quản lý Modal chung
    function openModal(modalId) {
        closeAllDropdowns();
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    // 2. Dropdown Menu ⋮
    function toggleDropdown(e, menuId) {
        e.stopPropagation();
        const menu = document.getElementById(menuId);
        const isShown = menu.classList.contains('show');
        closeAllDropdowns();
        if (!isShown) {
            menu.classList.add('show');
        }
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.rs-dropdown-menu.show').forEach(m => m.classList.remove('show'));
    }

    window.addEventListener('click', function(e) {
        closeAllDropdowns();
        if (e.target.classList.contains('rs-modal')) {
            e.target.classList.remove('show');
            document.body.style.overflow = '';
        }
    });

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllDropdowns();
            document.querySelectorAll('.rs-modal.show').forEach(m => m.classList.remove('show'));
            document.body.style.overflow = '';
        }
    });

    // 3. Inline validation helpers
    function showKhError(fieldId, msg) {
        const input = document.getElementById(fieldId);
        const errDiv = document.getElementById('err_' + fieldId);
        if (input) input.classList.add('is-invalid');
        if (errDiv) {
            errDiv.textContent = '⚠ ' + msg;
            errDiv.classList.add('show');
        }
    }

    function clearErrorKh(fieldId) {
        const input = document.getElementById(fieldId);
        const errDiv = document.getElementById('err_' + fieldId);
        if (input) input.classList.remove('is-invalid');
        if (errDiv) {
            errDiv.textContent = '';
            errDiv.classList.remove('show');
        }
    }

    function validateEditCustomer(e) {
        let valid = true;
        const ten  = document.getElementById('edit_ten_khach_hang').value.trim();
        const email = document.getElementById('edit_email').value.trim();
        const sdt  = document.getElementById('edit_so_dien_thoai').value.trim();
        const diaChi = document.getElementById('edit_dia_chi').value.trim();

        if (!ten) {
            showKhError('edit_ten_khach_hang', 'Vui lòng nhập họ và tên.');
            valid = false;
        }

        if (!email) {
            showKhError('edit_email', 'Vui lòng nhập email.');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showKhError('edit_email', 'Email không đúng định dạng.');
            valid = false;
        }

        if (!sdt) {
            showKhError('edit_so_dien_thoai', 'Vui lòng nhập số điện thoại.');
            valid = false;
        } else if (!/^\d{10,11}$/.test(sdt)) {
            showKhError('edit_so_dien_thoai', 'Số điện thoại chỉ được chứa chữ số và phải có độ dài hợp lệ.');
            valid = false;
        }

        if (!diaChi) {
            showKhError('edit_dia_chi', 'Vui lòng nhập địa chỉ.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    // 4. Modal Chi tiết khách hàng (Xem đầy đủ Email, Địa chỉ, Giao dịch & Vi phạm COD)
    function openDetailModal(kh, soDon, tongTien) {
        document.getElementById('detail_ma_khach_hang').textContent = 'KH-' + String(kh.ma_khach_hang).padStart(4, '0');
        document.getElementById('detail_ten_khach_hang').textContent = kh.ten_khach_hang || '—';
        document.getElementById('detail_so_dien_thoai').textContent = kh.so_dien_thoai || '—';
        document.getElementById('detail_email').textContent = kh.email || '—';
        document.getElementById('detail_dia_chi').textContent = kh.dia_chi || 'Chưa cập nhật';
        document.getElementById('detail_diem_tich_luy').textContent = (kh.diem_tich_luy || 0).toLocaleString('vi-VN');
        document.getElementById('detail_so_don_hang').textContent = soDon;
        document.getElementById('detail_tong_tien_mua').textContent = Number(tongTien).toLocaleString('vi-VN') + ' đ';

        // Trạng thái tài khoản
        const status = kh.nguoi_dung ? kh.nguoi_dung.trang_thai : 'HOAT_DONG';
        const stBadge = document.getElementById('detail_trang_thai_badge');
        if (status === 'HOAT_DONG') {
            stBadge.innerHTML = '<span class="rs-badge green"><span class="rs-badge-dot"></span>Hoạt động</span>';
        } else {
            stBadge.innerHTML = '<span class="rs-badge red"><span class="rs-badge-dot"></span>Bị khóa</span>';
        }

        // Quyền COD
        const quyenCod = kh.quyen_cod || 'CHO_PHEP';
        const codBadge = document.getElementById('detail_quyen_cod_badge');
        if (quyenCod === 'CHO_PHEP') {
            codBadge.innerHTML = '<span class="rs-cod-badge ok">✓ Được phép COD</span>';
        } else {
            codBadge.innerHTML = '<span class="rs-cod-badge restricted">✕ Bị giới hạn COD</span>';
        }

        // Lịch sử vi phạm COD
        const viPhamList = kh.lich_su_vi_pham_cod || [];
        const tbody = document.getElementById('detail_vi_pham_tbody');
        const emptyBox = document.getElementById('detail_vi_pham_empty');
        const tableBox = document.getElementById('detail_vi_pham_table_container');

        tbody.innerHTML = '';
        if (viPhamList.length > 0) {
            emptyBox.style.display = 'none';
            tableBox.style.display = 'block';

            viPhamList.forEach(item => {
                const dateStr = item.created_at ? new Date(item.created_at).toLocaleString('vi-VN') : '—';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style="font-size:12px; color:#64748b;">${dateStr}</td>
                    <td><span class="rs-badge ${item.hanh_dong === 'KHOI_PHUC_COD' ? 'green' : 'red'}" style="font-size:11px;">${item.loai_vi_pham}</span></td>
                    <td style="font-size:12.5px; color:#334155;">${item.ly_do}</td>
                    <td style="font-size:12px; color:#64748b;">${item.nguoi_thuc_hien || 'Admin'}</td>
                `;
                tbody.appendChild(row);
            });
        } else {
            emptyBox.style.display = 'block';
            tableBox.style.display = 'none';
        }

        openModal('detailModal');
    }

    // 5. Modal Chỉnh sửa
    function openEditModal(kh) {
        ['edit_ten_khach_hang', 'edit_email', 'edit_so_dien_thoai', 'edit_dia_chi'].forEach(clearErrorKh);
        const form = document.getElementById('editCustomerForm');
        form.action = `/khach-hang/${kh.ma_khach_hang}`;

        document.getElementById('edit_ma_khach_hang').value = 'KH-' + String(kh.ma_khach_hang).padStart(4, '0');
        document.getElementById('edit_diem_tich_luy').value  = (kh.diem_tich_luy || 0) + ' điểm';
        document.getElementById('edit_ten_khach_hang').value = kh.ten_khach_hang || '';
        document.getElementById('edit_so_dien_thoai').value  = kh.so_dien_thoai || '';
        document.getElementById('edit_email').value          = kh.email || '';
        document.getElementById('edit_dia_chi').value        = kh.dia_chi || '';

        openModal('editModal');
    }

    // 6. Modal Giới hạn quyền COD
    function openLimitCodModal(kh) {
        const form = document.getElementById('limitCodForm');
        form.action = `/khach-hang/${kh.ma_khach_hang}/limit-cod`;
        document.getElementById('limit_cod_customer_name').textContent = `${kh.ten_khach_hang} (KH-${String(kh.ma_khach_hang).padStart(4, '0')})`;
        openModal('limitCodModal');
    }

    // 7. Modal Khôi phục quyền COD
    function openRestoreCodModal(kh) {
        const form = document.getElementById('restoreCodForm');
        form.action = `/khach-hang/${kh.ma_khach_hang}/restore-cod`;
        document.getElementById('restore_cod_customer_name').textContent = `${kh.ten_khach_hang} (KH-${String(kh.ma_khach_hang).padStart(4, '0')})`;
        openModal('restoreCodModal');
    }

    // 8. Modal Đổi trạng thái (Khóa/Mở)
    function openStatusModal(kh, targetStatus) {
        const form = document.getElementById('statusCustomerForm');
        form.action = `/khach-hang/${kh.ma_khach_hang}/toggle-status`;
        document.getElementById('statusTargetStatus').value = targetStatus;

        const titleEl = document.getElementById('statusModalTitle');
        const iconEl  = document.getElementById('statusModalIcon');
        const nameEl  = document.getElementById('statusModalCustomerName');
        const msgEl   = document.getElementById('statusModalMessage');
        const btnEl   = document.getElementById('btnConfirmStatus');

        nameEl.textContent = `${kh.ten_khach_hang} (KH-${String(kh.ma_khach_hang).padStart(4, '0')})`;

        if (targetStatus === 'BI_KHOA') {
            titleEl.textContent = 'Khóa tài khoản khách hàng';
            iconEl.style.background = '#ffedd5';
            iconEl.style.color = '#ea580c';
            iconEl.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>`;
            msgEl.innerHTML = 'Khách hàng sẽ bị tạm dừng quyền đăng nhập và không thể tạo đơn hàng mới trên hệ thống.';
            btnEl.className = 'rs-btn-submit orange';
            btnEl.textContent = 'Khóa tài khoản';
        } else {
            titleEl.textContent = 'Mở khóa tài khoản khách hàng';
            iconEl.style.background = '#dcfce7';
            iconEl.style.color = '#16a34a';
            iconEl.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>`;
            msgEl.innerHTML = 'Khôi phục quyền truy cập. Khách hàng có thể đăng nhập và tiếp tục mua sắm bình thường.';
            btnEl.className = 'rs-btn-submit green';
            btnEl.textContent = 'Mở khóa tài khoản';
        }

        btnEl.disabled = false;
        openModal('statusModal');
    }

    function handleStatusSubmit(e) {
        const btn = document.getElementById('btnConfirmStatus');
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';
    }

    // 9. Modal Xóa & Bảo toàn hồ sơ
    function openDeleteModal(kh, hasOrders) {
        const blockedContainer = document.getElementById('deleteBlockedContainer');
        const allowedContainer = document.getElementById('deleteAllowedContainer');

        if (hasOrders) {
            blockedContainer.style.display = 'block';
            allowedContainer.style.display = 'none';
            document.getElementById('deleteBlockedName').textContent = `"${kh.ten_khach_hang}" (KH-${String(kh.ma_khach_hang).padStart(4, '0')})`;

            document.getElementById('btnSwitchToLock').onclick = function() {
                closeModal('deleteModal');
                openStatusModal(kh, 'BI_KHOA');
            };
        } else {
            blockedContainer.style.display = 'none';
            allowedContainer.style.display = 'block';
            document.getElementById('deleteAllowedName').textContent = `"${kh.ten_khach_hang}" (KH-${String(kh.ma_khach_hang).padStart(4, '0')})`;

            const form = document.getElementById('deleteCustomerForm');
            form.action = `/khach-hang/${kh.ma_khach_hang}`;
            const btn = document.getElementById('btnConfirmDelete');
            btn.disabled = false;
            btn.textContent = 'Xóa vĩnh viễn';
        }

        openModal('deleteModal');
    }

    function handleDeleteSubmit(e) {
        const btn = document.getElementById('btnConfirmDelete');
        btn.disabled = true;
        btn.textContent = 'Đang xóa...';
    }
</script>

@endsection