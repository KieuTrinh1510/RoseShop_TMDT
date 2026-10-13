@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/Admin/Shipper.css') }}">
@endpush

@section('content')

<div class="rs-management-page">

    {{-- 1. HEADER TRANG --}}
    <div class="rs-header-block">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                    <path d="M15 18H9"/>
                    <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                    <circle cx="17" cy="18" r="2"/>
                    <circle cx="7" cy="18" r="2"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Quản lý Shipper</h1>
                <p class="rs-header-subtitle">Theo dõi danh sách, trạng thái làm việc và lịch sử giao hàng của đội ngũ giao vận</p>
            </div>
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

    {{-- 2. HÀNG 4 THẺ KPI THỐNG KÊ --}}
    <div class="rs-kpi-grid">
        <div class="rs-kpi-card">
            <div class="rs-kpi-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Tổng Shipper</span>
                <span class="rs-kpi-value">{{ $tongShipper }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Đang hoạt động</span>
                <span class="rs-kpi-value">{{ $dangHoatDong }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="2"/>
                    <path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Đang Online</span>
                <span class="rs-kpi-value">{{ $dangOnline }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon orange">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                    <path d="M15 18H9"/>
                    <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                    <circle cx="17" cy="18" r="2"/>
                    <circle cx="7" cy="18" r="2"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Đang giao hàng</span>
                <span class="rs-kpi-value">{{ $dangGiaoHang }}</span>
            </div>
        </div>
    </div>

    {{-- 3. CARD CHÍNH CHỨA BỘ LỌC VÀ BẢNG --}}
    <div class="rs-main-card">

        {{-- Form lọc: Tất cả bộ lọc + Ô tìm kiếm + Nút tìm kiếm trên cùng 1 hàng duy nhất --}}
        <form method="GET" action="{{ route('admin.shipper.index') }}" id="shipperFilterForm" class="rs-toolbar-unified">
            <input type="hidden" name="per_page" id="sp_per_page_hidden" value="{{ $perPage }}">

            {{-- 1. Lọc theo trạng thái tài khoản --}}
            <select name="trang_thai" class="rs-select" onchange="document.getElementById('shipperFilterForm').submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="HOAT_DONG" {{ request('trang_thai') == 'HOAT_DONG' ? 'selected' : '' }}>Hoạt động</option>
                <option value="BI_KHOA" {{ request('trang_thai') == 'BI_KHOA' ? 'selected' : '' }}>Bị khóa</option>
            </select>

            {{-- 2. Lọc theo trạng thái làm việc --}}
            <select name="trang_thai_hoat_dong" class="rs-select" onchange="document.getElementById('shipperFilterForm').submit()">
                <option value="">Trạng thái làm việc</option>
                <option value="ONLINE" {{ request('trang_thai_hoat_dong') == 'ONLINE' ? 'selected' : '' }}>Đang Online</option>
                <option value="OFFLINE" {{ request('trang_thai_hoat_dong') == 'OFFLINE' ? 'selected' : '' }}>Ngoại tuyến (Offline)</option>
            </select>

            {{-- 3. Sắp xếp --}}
            <select name="sort" class="rs-select" onchange="document.getElementById('shipperFilterForm').submit()">
                <option value="">Sắp xếp mặc định</option>
                <option value="don_desc" {{ request('sort') == 'don_desc' ? 'selected' : '' }}>Nhiều đơn đang giao</option>
                <option value="don_asc" {{ request('sort') == 'don_asc' ? 'selected' : '' }}>Ít đơn đang giao</option>
                <option value="luong_desc" {{ request('sort') == 'luong_desc' ? 'selected' : '' }}>Lương: Cao → Thấp</option>
                <option value="luong_asc" {{ request('sort') == 'luong_asc' ? 'selected' : '' }}>Lương: Thấp → Cao</option>
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
                       placeholder="Tìm kiếm theo tên, mã shipper, SĐT, email..."
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
            @if(request()->anyFilled(['keyword', 'trang_thai', 'trang_thai_hoat_dong', 'sort']))
                <a href="{{ route('admin.shipper.index', ['per_page' => $perPage]) }}" class="rs-btn-filter-clear" title="Đặt lại bộ lọc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Bỏ lọc
                </a>
            @endif
        </form>

        {{-- BẢNG DỮ LIỆU SHIPPER --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th style="width: 100px; text-align: center;">Mã Shipper</th>
                        <th style="min-width: 160px; text-align: center;">Tên Shipper</th>
                        <th style="min-width: 180px; text-align: center;">Số điện thoại & Email</th>
                        <th style="min-width: 130px; text-align: center;">Ca làm việc</th>
                        <th style="width: 130px; text-align: center;">Trạng thái làm việc</th>
                        <th style="width: 100px; text-align: center;">Nhận đơn</th>
                        <th style="width: 110px; text-align: center;">Đang giao</th>
                        <th style="width: 130px; text-align: center;">Tài khoản</th>
                        <th style="width: 130px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shippers as $sp)
                        @php
                            $user = $sp->nguoiDung;
                            $isLocked = ($user && $user->trang_thai === \App\Models\NguoiDung::TRANG_THAI_BI_KHOA);
                            $isOnline = ($sp->trang_thai_hoat_dong === 'ONLINE');
                            $isReceiving = ($sp->nhan_don == 1);
                        @endphp
                        <tr>
                            {{-- Mã Shipper: Đồng bộ theo mã nhân viên toàn hệ thống NV-xxxx --}}
                            <td style="text-align: center; font-weight: 700; color: #f43f5e; font-family: monospace; font-size: 13px;">
                                NV-{{ str_pad($sp->ma_nhan_vien, 4, '0', STR_PAD_LEFT) }}
                            </td>

                            {{-- Tên Shipper --}}
                            <td>
                                <div class="rs-text-bold">{{ $sp->ten_nhan_vien }}</div>
                                <div style="font-size: 12px; color: #64748b;">TK: {{ $user->ten_dang_nhap ?? 'N/A' }}</div>
                            </td>

                            {{-- SĐT & Email --}}
                            <td>
                                <div style="color: #0f172a; font-weight: 500; font-family: monospace; font-size: 13px;">{{ $sp->so_dien_thoai }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $sp->email ?: '—' }}</div>
                            </td>

                            {{-- Ca làm việc --}}
                            <td>
                                <span style="font-size: 13px; color: #475467;">
                                    {{ $sp->cong_viec ?? 'Giao hàng' }}
                                </span>
                            </td>

                            {{-- Trạng thái làm việc (Online / Offline) --}}
                            <td style="text-align: center;">
                                @if($isOnline)
                                    <span class="rs-badge rs-badge-success">
                                        <span class="rs-badge-dot"></span>
                                        Online
                                    </span>
                                @else
                                    <span class="rs-badge rs-badge-gray">
                                        <span class="rs-badge-dot"></span>
                                        Offline
                                    </span>
                                @endif
                            </td>

                            {{-- Trạng thái nhận đơn --}}
                            <td style="text-align: center;">
                                @if($isReceiving)
                                    <span class="rs-badge rs-badge-blue">Bật</span>
                                @else
                                    <span class="rs-badge rs-badge-gray">Tắt</span>
                                @endif
                            </td>

                            {{-- Số đơn đang giao --}}
                            <td style="text-align: center;">
                                @if($sp->so_don_hien_tai > 0)
                                    <span class="rs-badge rs-badge-warning" style="font-weight: 700;">
                                        {{ $sp->so_don_hien_tai }} đơn
                                    </span>
                                @else
                                    <span style="color: #94a3b8; font-size: 13px;">0</span>
                                @endif
                            </td>

                            {{-- Trạng thái tài khoản --}}
                            <td style="text-align: center;">
                                @if($isLocked)
                                    <span class="rs-status-badge locked">
                                        Bị khóa
                                    </span>
                                @else
                                    <span class="rs-status-badge active">
                                        Hoạt động
                                    </span>
                                @endif
                            </td>

                            {{-- Thao tác --}}
                            <td style="text-align: center;">
                                <div class="rs-action-group">
                                    {{-- Nút Xem chi tiết --}}
                                    <button type="button" class="rs-action-btn detail" title="Xem chi tiết Shipper" onclick="openShipperDetail({{ $sp->ma_nhan_vien }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>

                                    {{-- Nút Xem lịch sử giao hàng --}}
                                    <button type="button" class="rs-action-btn history" title="Xem lịch sử giao hàng" onclick="openLichSuGiaoHang({{ $sp->ma_nhan_vien }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                    </button>

                                    {{-- Nút Khóa / Mở khóa tài khoản --}}
                                    @if($isLocked)
                                        <button type="button" class="rs-action-btn unlock" title="Mở khóa tài khoản" onclick="confirmToggleStatus({{ $sp->ma_nhan_vien }}, '{{ addslashes($sp->ten_nhan_vien) }}', 'HOAT_DONG')">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                                            </svg>
                                        </button>
                                    @else
                                        <button type="button" class="rs-action-btn lock" title="Khóa tài khoản" onclick="confirmToggleStatus({{ $sp->ma_nhan_vien }}, '{{ addslashes($sp->ten_nhan_vien) }}', 'BI_KHOA')">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 48px 16px; color: #64748b;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="8" y1="12" x2="16" y2="12"/>
                                    </svg>
                                    <span style="font-size: 15px; font-weight: 500;">Chưa có Shipper nào trong hệ thống</span>
                                    <span style="font-size: 13px; color: #94a3b8;">Thử điều chỉnh lại từ khóa hoặc bộ lọc tìm kiếm</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER BẢNG & PHÂN TRANG (ĐỒNG BỘ 100%) --}}
        @if($shippers->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    {{-- Chọn số hàng mỗi trang --}}
                    <div class="rs-per-page-inline">
                        <label class="rs-per-page-label">Số hàng mỗi trang</label>
                        <select class="rs-perpage-select" onchange="changePerPage(this.value)">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>

                    {{-- Đếm số bản ghi hiển thị --}}
                    <div class="rs-records-count">
                        {{ $shippers->firstItem() }}-{{ $shippers->lastItem() }} / {{ $shippers->total() }} bản ghi
                    </div>
                </div>

                {{-- Phân trang thu gọn < 1 ... n > --}}
                <div class="rs-footer-pagination">
                    @php
                        $currentPage = $shippers->currentPage();
                        $lastPage    = $shippers->lastPage();
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

                    @if($lastPage > 1)
                        <ul class="rs-pagination">
                            {{-- Nút Previous --}}
                            @if($shippers->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $shippers->previousPageUrl() }}">&lsaquo;</a></li>
                            @endif

                            {{-- Danh sách trang --}}
                            @foreach ($pages as $p)
                                @if ($p === '...')
                                    <li class="page-item disabled"><span class="page-link ellipsis">&hellip;</span></li>
                                @elseif ($p == $currentPage)
                                    <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $shippers->url($p) }}">{{ $p }}</a></li>
                                @endif
                            @endforeach

                            {{-- Nút Next --}}
                            @if($shippers->hasMorePages())
                                <li class="page-item"><a class="page-link" href="{{ $shippers->nextPageUrl() }}">&rsaquo;</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        @endif

    </div>

</div>

{{-- MODAL 1: XEM CHI TIẾT SHIPPER --}}
<div class="rs-modal" id="modalShipperDetail">
    <div class="rs-modal-dialog">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title">Thông tin chi tiết Shipper</h3>
            <button type="button" class="rs-modal-close" onclick="closeModal('modalShipperDetail')">&times;</button>
        </div>
        <div class="rs-modal-body">
            <div class="rs-detail-grid">
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Mã Shipper</div>
                    <div class="rs-detail-value" id="dt_ma_shipper">-</div>
                </div>
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Họ và tên</div>
                    <div class="rs-detail-value" id="dt_ten_shipper">-</div>
                </div>
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Số điện thoại</div>
                    <div class="rs-detail-value" id="dt_sdt">-</div>
                </div>
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Email liên hệ</div>
                    <div class="rs-detail-value" id="dt_email">-</div>
                </div>
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Tên đăng nhập hệ thống</div>
                    <div class="rs-detail-value" id="dt_ten_dang_nhap">-</div>
                </div>
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Ca làm việc</div>
                    <div class="rs-detail-value" id="dt_ca_lam_viec">-</div>
                </div>
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Mức lương cơ bản</div>
                    <div class="rs-detail-value" id="dt_luong">-</div>
                </div>
                <div class="rs-detail-item">
                    <div class="rs-detail-label">Trạng thái làm việc</div>
                    <div class="rs-detail-value" id="dt_trang_thai_lam_viec">-</div>
                </div>
            </div>

            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 12px;">Thống kê giao nhận</h4>
                <div class="rs-detail-grid">
                    <div class="rs-detail-item">
                        <div class="rs-detail-label">Tổng đơn đã nhận</div>
                        <div class="rs-detail-value" id="dt_tong_don">0</div>
                    </div>
                    <div class="rs-detail-item">
                        <div class="rs-detail-label">Đang giao hiện tại</div>
                        <div class="rs-detail-value" style="color:#d97706;" id="dt_so_don_dang_giao">0</div>
                    </div>
                    <div class="rs-detail-item">
                        <div class="rs-detail-label">Giao thành công</div>
                        <div class="rs-detail-value" style="color:#059669;" id="dt_so_don_da_giao">0</div>
                    </div>
                    <div class="rs-detail-item">
                        <div class="rs-detail-label">Tiền COD đang giữ</div>
                        <div class="rs-detail-value" style="color:#2563eb;" id="dt_tien_cod">0 đ</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-secondary" onclick="closeModal('modalShipperDetail')">Đóng</button>
        </div>
    </div>
</div>

{{-- MODAL 2: XEM LỊCH SỬ GIAO HÀNG --}}
<div class="rs-modal" id="modalLichSuGiaoHang">
    <div class="rs-modal-dialog large">
        <div class="rs-modal-header">
            <div>
                <h3 class="rs-modal-title">Lịch sử giao hàng</h3>
                <p style="font-size: 13px; color: #64748b; margin: 2px 0 0;" id="ls_shipper_info">-</p>
            </div>
            <button type="button" class="rs-modal-close" onclick="closeModal('modalLichSuGiaoHang')">&times;</button>
        </div>
        <div class="rs-modal-body" style="padding: 0;">
            <div id="ls_loading" style="text-align: center; padding: 40px; color: #64748b;">
                Đang tải lịch sử giao hàng...
            </div>
            <div id="ls_empty" style="display: none; text-align: center; padding: 48px 16px; color: #64748b;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom: 8px;">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div style="font-size: 15px; font-weight: 600; color: #334155;">Shipper chưa phát sinh lịch sử giao hàng</div>
                <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">Chưa có đơn hàng nào được phân công hoặc giao bởi shipper này.</div>
            </div>
            <div id="ls_table_container" style="display: none; max-height: 450px; overflow-y: auto;">
                <table class="rs-table">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Địa chỉ nhận</th>
                            <th style="width: 140px;">Ngày giao</th>
                            <th style="width: 120px;">Tổng tiền</th>
                            <th style="width: 110px;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="ls_tbody"></tbody>
                </table>
            </div>
        </div>
        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-secondary" onclick="closeModal('modalLichSuGiaoHang')">Đóng</button>
        </div>
    </div>
</div>

{{-- MODAL 3: XÁC NHẬN TẠM KHÓA / MỞ KHÓA SHIPPER --}}
<div class="rs-modal" id="modalConfirmToggle">
    <div class="rs-modal-dialog">
        <form method="POST" id="formToggleStatus">
            @csrf
            @method('PATCH')
            <input type="hidden" name="target_status" id="tg_target_status">

            <div class="rs-modal-header">
                <h3 class="rs-modal-title" id="tg_title">Xác nhận thao tác</h3>
                <button type="button" class="rs-modal-close" onclick="closeModal('modalConfirmToggle')">&times;</button>
            </div>
            <div class="rs-modal-body">
                <p id="tg_message" style="font-size: 14.5px; color: #334155; line-height: 1.5; margin: 0;"></p>
                <div id="tg_warning" style="margin-top: 12px; font-size: 13px; color: #dc2626; background: #fff1f2; padding: 10px 14px; border-radius: 8px; display: none;">
                    ⚠️ Chú ý: Khi tạm khóa, Shipper sẽ tự động bị ngắt nhận đơn và không thể truy cập ứng dụng giao hàng cho đến khi được mở khóa lại.
                </div>
            </div>
            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-secondary" onclick="closeModal('modalConfirmToggle')">Hủy</button>
                <button type="submit" class="rs-btn-danger" id="tg_submit_btn">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // 1. Thay đổi số hàng mỗi trang
    function changePerPage(val) {
        document.getElementById('sp_per_page_hidden').value = val;
        document.getElementById('shipperFilterForm').submit();
    }

    // 2. Mở / Đóng modal
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // Đóng khi click ngoài backdrop
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('rs-modal')) {
            e.target.classList.remove('active');
        }
    });

    // 3. Mở Modal Chi tiết Shipper (AJAX)
    function openShipperDetail(id) {
        openModal('modalShipperDetail');
        fetch(`{{ url('admin/quan-ly-shipper') }}/${id}/detail`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const d = res.data;
                    document.getElementById('dt_ma_shipper').textContent = 'SP-' + String(d.ma_nhan_vien).padStart(4, '0');
                    document.getElementById('dt_ten_shipper').textContent = d.ten_nhan_vien;
                    document.getElementById('dt_sdt').textContent = d.so_dien_thoai || 'Chưa cập nhật';
                    document.getElementById('dt_email').textContent = d.email || 'Chưa cập nhật';
                    document.getElementById('dt_ten_dang_nhap').textContent = d.ten_dang_nhap;
                    document.getElementById('dt_ca_lam_viec').textContent = d.cong_viec;
                    document.getElementById('dt_luong').textContent = new Intl.NumberFormat('vi-VN').format(d.luong) + ' đ';
                    document.getElementById('dt_trang_thai_lam_viec').textContent = d.trang_thai_hoat_dong === 'ONLINE' ? '🟢 Đang Online' : '⚪ Offline';
                    
                    document.getElementById('dt_tong_don').textContent = d.thong_ke.tong_don + ' đơn';
                    document.getElementById('dt_so_don_dang_giao').textContent = d.thong_ke.so_don_dang_giao + ' đơn';
                    document.getElementById('dt_so_don_da_giao').textContent = d.thong_ke.so_don_da_giao + ' đơn';
                    document.getElementById('dt_tien_cod').textContent = new Intl.NumberFormat('vi-VN').format(d.thong_ke.tien_cod_dang_giu) + ' đ';
                }
            })
            .catch(() => alert('Không thể tải thông tin chi tiết Shipper!'));
    }

    // 4. Mở Modal Lịch sử giao hàng (AJAX)
    function openLichSuGiaoHang(id) {
        openModal('modalLichSuGiaoHang');
        const loading = document.getElementById('ls_loading');
        const empty   = document.getElementById('ls_empty');
        const table   = document.getElementById('ls_table_container');
        const tbody   = document.getElementById('ls_tbody');

        loading.style.display = 'block';
        empty.style.display = 'none';
        table.style.display = 'none';
        tbody.innerHTML = '';

        fetch(`{{ url('admin/quan-ly-shipper') }}/${id}/lich-su-giao-hang`)
            .then(res => res.json())
            .then(res => {
                loading.style.display = 'none';
                if (res.success) {
                    document.getElementById('ls_shipper_info').textContent = `Shipper: ${res.shipper.ten_nhan_vien} (SP-${String(res.shipper.ma_nhan_vien).padStart(4, '0')}) • Tổng số: ${res.total} đơn`;

                    if (res.total === 0) {
                        empty.style.display = 'block';
                    } else {
                        table.style.display = 'block';
                        let html = '';
                        res.don_hang.forEach(dh => {
                            let badgeClass = 'rs-badge-gray';
                            let statusText = dh.trang_thai;
                            if (dh.trang_thai === 'DELIVERED') {
                                badgeClass = 'rs-badge-success';
                                statusText = 'Đã giao';
                            } else if (dh.trang_thai === 'SHIPPING') {
                                badgeClass = 'rs-badge-warning';
                                statusText = 'Đang giao';
                            } else if (dh.trang_thai === 'CANCELLED') {
                                badgeClass = 'rs-badge-danger';
                                statusText = 'Đã hủy';
                            }

                            html += `
                                <tr>
                                    <td><strong style="color:#2563eb;">#${dh.ma_hoa_don}</strong></td>
                                    <td>
                                        <div style="font-weight:600; color:#0f172a;">${dh.ten_khach_hang}</div>
                                        <div style="font-size:12px; color:#64748b;">${dh.so_dien_thoai}</div>
                                    </td>
                                    <td><span style="font-size:13px; color:#475467;">${dh.dia_chi_giao || 'Tại cửa hàng'}</span></td>
                                    <td><span style="font-size:13px; color:#64748b;">${dh.ngay_giao || dh.ngay_dat}</span></td>
                                    <td><strong style="color:#0f172a;">${new Intl.NumberFormat('vi-VN').format(dh.tong_tien)} đ</strong></td>
                                    <td><span class="rs-badge ${badgeClass}">${statusText}</span></td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    }
                }
            })
            .catch(() => {
                loading.style.display = 'none';
                alert('Không thể tải lịch sử giao hàng!');
            });
    }

    // 5. Xác nhận Tạm khóa / Mở khóa Shipper
    function confirmToggleStatus(id, name, targetStatus) {
        const form = document.getElementById('formToggleStatus');
        form.action = `{{ url('admin/quan-ly-shipper') }}/${id}/toggle-status`;
        document.getElementById('tg_target_status').value = targetStatus;

        const title = document.getElementById('tg_title');
        const msg   = document.getElementById('tg_message');
        const warn  = document.getElementById('tg_warning');
        const btn   = document.getElementById('tg_submit_btn');

        if (targetStatus === 'BI_KHOA') {
            title.textContent = 'Tạm khóa hoạt động Shipper';
            msg.innerHTML = `Bạn có chắc chắn muốn <strong>tạm khóa</strong> tài khoản của Shipper <strong>"${name}"</strong> không?`;
            warn.style.display = 'block';
            btn.className = 'rs-btn-danger';
            btn.textContent = 'Tạm khóa';
        } else {
            title.textContent = 'Mở khóa hoạt động Shipper';
            msg.innerHTML = `Bạn có chắc chắn muốn <strong>mở khóa</strong> cho Shipper <strong>"${name}"</strong> hoạt động trở lại không?`;
            warn.style.display = 'none';
            btn.className = 'rs-btn-success';
            btn.textContent = 'Mở khóa';
        }

        openModal('modalConfirmToggle');
    }
</script>
@endpush

@endsection
