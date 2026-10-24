@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/Admin/NguoiDung.css') }}">
    <style>
        .rs-table thead th {
            background-color: #F8F9FA !important;
            color: #475467 !important;
            font-weight: 600 !important;
            font-size: 13.5px !important;
            border-bottom: 1px solid #EAECF0 !important;
            white-space: nowrap !important;
        }
        .rs-table tbody tr:hover {
            background-color: #F9FAFB !important;
        }
        .rs-table td {
            white-space: nowrap !important;
            padding: 12px 14px !important;
        }
        .rs-code-user {
            font-family: monospace;
            font-size: 11.5px;
            font-weight: 700;
            color: #EC3D8F !important;
            white-space: nowrap !important;
        }
        .rs-card-title-bar {
            padding: 16px 20px 14px 20px;
            background: #ffffff;
            border-bottom: 1px solid #EAECF0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .rs-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            color: #101828;
        }
        .rs-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            border-radius: 12px;
            background-color: #F2F4F7;
            color: #344054;
            font-size: 12.5px;
            font-weight: 600;
            margin-left: 4px;
        }
        .rs-btn-create, .rs-btn-search {
            background: #EC3D8F !important;
            border-color: #EC3D8F !important;
        }
        .rs-btn-create:hover, .rs-btn-search:hover {
            background: #D82677 !important;
        }
        .rs-table th:last-child, .rs-table td:last-child {
            padding-right: 20px !important;
        }
    </style>
@endpush

@section('content')

<div class="rs-management-page">

    {{-- 1. HEADER TRANG: Tiêu đề + Nút Thêm tài khoản --}}
    <div class="rs-header-block">
        <div>
            <h1 class="rs-header-title">Tài khoản hệ thống</h1>
            <p class="rs-header-subtitle">Quản lý định danh đăng nhập, phân quyền vai trò và trạng thái Khóa / Mở khóa tài khoản</p>
        </div>

        {{-- Nút Thêm tài khoản mới --}}
        <button type="button" class="rs-btn-create" onclick="openCreateModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
            </svg>
            Thêm tài khoản
        </button>
    </div>

    {{-- THÔNG BÁO HỆ THỐNG --}}
    @if(session('success'))
        <div class="rs-alert-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rs-alert-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rs-alert-error" style="flex-direction: column; align-items: flex-start;">
            <div style="font-weight: 600; margin-bottom: 4px;">Có lỗi xảy ra, vui lòng kiểm tra lại:</div>
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2. HÀNG 4 THẺ KPI THỐNG KÊ TRẠNG THÁI & VAI TRÒ --}}
    <div class="rs-kpi-grid">
        <div class="rs-kpi-card">
            <div class="rs-kpi-icon pink">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Tổng tài khoản</span>
                <span class="rs-kpi-value">{{ $tongTaiKhoan }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Quản trị viên</span>
                <span class="rs-kpi-value">{{ $soAdmin }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Nhân sự (NV & Shipper)</span>
                <span class="rs-kpi-value">{{ $soNhanSu }}</span>
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
                <span class="rs-kpi-label">Hoạt động / Bị khóa</span>
                <span class="rs-kpi-value">{{ $soDangHoatDong }} <small style="font-size:14px; color:#ef4444; font-weight:600;">/ {{ $soBiKhoa }} khóa</small></span>
            </div>
        </div>
    </div>

    {{-- 3. CARD CHÍNH CHỨA BỘ LỌC VÀ BẢNG --}}
    <div class="rs-main-card">

        {{-- Form lọc: Tất cả bộ lọc + Ô tìm kiếm + Nút tìm kiếm trên cùng 1 hàng theo đúng hình ảnh --}}
        <form method="GET" action="{{ route('nguoi-dung.index') }}" id="userFilterForm" class="rs-toolbar-unified">
            <input type="hidden" name="per_page" id="per_page_hidden" value="{{ $perPage }}">

            {{-- 1. Bộ lọc vai trò --}}
            <select name="vai_tro" class="rs-select" onchange="document.getElementById('userFilterForm').submit()">
                <option value="">Tất cả vai trò</option>
                <option value="ADMIN" {{ request('vai_tro') === 'ADMIN' ? 'selected' : '' }}>Admin (Quản trị)</option>
                <option value="NHAN_VIEN" {{ request('vai_tro') === 'NHAN_VIEN' ? 'selected' : '' }}>Nhân viên</option>
                <option value="SHIPPER" {{ request('vai_tro') === 'SHIPPER' ? 'selected' : '' }}>Shipper</option>
                <option value="KHACH_HANG" {{ request('vai_tro') === 'KHACH_HANG' ? 'selected' : '' }}>Khách hàng</option>
            </select>

            {{-- 2. Bộ lọc trạng thái tài khoản --}}
            <select name="trang_thai" class="rs-select" onchange="document.getElementById('userFilterForm').submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="HOAT_DONG" {{ request('trang_thai') === 'HOAT_DONG' ? 'selected' : '' }}>Hoạt động</option>
                <option value="BI_KHOA" {{ request('trang_thai') === 'BI_KHOA' ? 'selected' : '' }}>Bị khóa</option>
            </select>

            {{-- 3. Bộ lọc sắp xếp --}}
            <select name="sort" class="rs-select" onchange="document.getElementById('userFilterForm').submit()">
                <option value="">Sắp xếp mặc định</option>
                <option value="id_desc" {{ request('sort') === 'id_desc' ? 'selected' : '' }}>Mã TK: Mới nhất</option>
                <option value="id_asc" {{ request('sort') === 'id_asc' ? 'selected' : '' }}>Mã TK: Cũ nhất</option>
                <option value="username_asc" {{ request('sort') === 'username_asc' ? 'selected' : '' }}>Tên đăng nhập: A → Z</option>
                <option value="username_desc" {{ request('sort') === 'username_desc' ? 'selected' : '' }}>Tên đăng nhập: Z → A</option>
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
                       placeholder="Nhập tên đăng nhập, email, SĐT..."
                       autocomplete="off">
            </div>

            {{-- 5. Nút tìm kiếm (thay thế vị trí ô chọn dòng/trang cũ) --}}
            <button type="submit" class="rs-btn-search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Tìm kiếm
            </button>

            {{-- 6. Nút Bỏ lọc nếu đang có filter --}}
            @if(request()->anyFilled(['keyword', 'vai_tro', 'trang_thai', 'sort']))
                <a href="{{ route('nguoi-dung.index', ['per_page' => $perPage]) }}" class="rs-btn-filter-clear" title="Đặt lại bộ lọc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Bỏ lọc
                </a>
            @endif

        </form>

        {{-- TIÊU ĐỀ BẢNG DANH SÁCH CHUẨN --}}
        <div class="rs-card-title-bar">
            <div class="rs-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                <span>Danh sách tài khoản người dùng</span>
                <span class="rs-count-badge">{{ $nguoiDungs->total() }}</span>
            </div>
        </div>

        {{-- 4. BẢNG DỮ LIỆU TÀI KHOẢN HỆ THỐNG --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <colgroup>
                    <col style="width: 105px;">
                    <col style="width: 175px;">
                    <col style="width: 135px;">
                    <col style="width: 185px;">
                    <col style="width: 235px;">
                    <col style="width: 135px;">
                    <col style="width: 130px;">
                    <col style="width: 110px;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center; white-space: nowrap;">Mã TK</th>
                        <th style="text-align: left; white-space: nowrap;">Tên đăng nhập</th>
                        <th style="text-align: center; white-space: nowrap;">Vai trò</th>
                        <th style="text-align: left; white-space: nowrap;">Chủ sở hữu</th>
                        <th style="text-align: left; white-space: nowrap;">Email liên kết</th>
                        <th style="text-align: center; white-space: nowrap;">Số điện thoại</th>
                        <th style="text-align: center; white-space: nowrap;">Trạng thái</th>
                        <th style="text-align: center; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nguoiDungs as $user)
                        @php
                            $isActive = ($user->trang_thai === \App\Models\NguoiDung::TRANG_THAI_HOAT_DONG);
                            $isCurrentAuth = ($user->ma_nguoi_dung === auth()->id());

                            // Thông tin chủ sở hữu tài khoản (Chỉ đọc để nhận diện)
                            $chuSoHuu = '—';
                            $email    = '—';
                            $sdt      = '—';

                            if ($user->isKhachHang() && $user->khachHang) {
                                $chuSoHuu = $user->khachHang->ten_khach_hang;
                                $email    = $user->khachHang->email;
                                $sdt      = $user->khachHang->so_dien_thoai;
                            } elseif ($user->isNhanVien() && $user->nhanVien) {
                                $chuSoHuu = $user->nhanVien->ten_nhan_vien;
                                $email    = $user->nhanVien->email;
                                $sdt      = $user->nhanVien->so_dien_thoai;
                            } elseif ($user->isAdmin()) {
                                $chuSoHuu = 'Quản trị viên hệ thống';
                            }
                        @endphp
                        <tr>
                            {{-- 1. Mã tài khoản --}}
                            <td style="text-align: center; white-space: nowrap;">
                                <span class="rs-code-user">TK-{{ str_pad($user->ma_nguoi_dung, 4, '0', STR_PAD_LEFT) }}</span>
                            </td>

                            {{-- 2. Tên đăng nhập --}}
                            <td style="white-space: nowrap;">
                                <span class="rs-text-bold" style="font-family: monospace; font-size: 13px; color: #0f172a;">
                                    {{ $user->ten_dang_nhap }}
                                </span>
                                @if($isCurrentAuth)
                                    <span style="font-size:11px; color:#EC3D8F; font-weight:600; background:#fff1f2; padding:2px 6px; border-radius:4px; margin-left:4px;">(Bạn)</span>
                                @endif
                            </td>

                            {{-- 3. Vai trò --}}
                            <td style="text-align: center; white-space: nowrap;">
                                @if($user->vai_tro === 'ADMIN')
                                    <span class="rs-role-badge admin">Quản trị viên</span>
                                @elseif($user->vai_tro === 'NHAN_VIEN')
                                    <span class="rs-role-badge nhan_vien">Nhân viên</span>
                                @elseif($user->vai_tro === 'SHIPPER')
                                    <span class="rs-role-badge shipper">Shipper</span>
                                @else
                                    <span class="rs-role-badge khach_hang">Khách hàng</span>
                                @endif
                            </td>

                            {{-- 4. Chủ sở hữu (Họ và tên người dùng liên kết - Chỉ đọc) --}}
                            <td style="white-space: nowrap;">
                                <span class="rs-text-bold" style="color: #334155;">{{ $chuSoHuu }}</span>
                            </td>

                            {{-- 5. Email --}}
                            <td style="font-size: 12.5px; color: #64748b; white-space: nowrap;">{{ $email ?: '—' }}</td>

                            {{-- 6. Số điện thoại --}}
                            <td style="text-align: center; white-space: nowrap; font-family: monospace; font-size: 13px; color: #334155;">
                                {{ $sdt ?: '—' }}
                            </td>

                            {{-- 7. Trạng thái tài khoản (Đặc tả: Hoạt động / Bị khóa) --}}
                            <td style="text-align: center; white-space: nowrap;">
                                @if($isActive)
                                    <span class="rs-status-badge active">Hoạt động</span>
                                @else
                                    <span class="rs-status-badge locked">Bị khóa</span>
                                @endif
                            </td>

                            {{-- 8. Thao tác: CHỈ DUY NHẤT KHÓA / MỞ KHÓA TÀI KHOẢN --}}
                            <td style="text-align: center; white-space: nowrap;">
                                @if($isCurrentAuth)
                                    <span style="font-size: 12px; color: #94a3b8; font-style: italic;">Đang đăng nhập</span>
                                @else
                                    <button type="button"
                                            class="rs-action-btn {{ $isActive ? 'lock' : 'unlock' }}"
                                            title="{{ $isActive ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}"
                                            onclick="openStatusModal({{ $user->ma_nguoi_dung }}, '{{ addslashes($user->ten_dang_nhap) }}', '{{ addslashes($chuSoHuu) }}', '{{ $user->vai_tro }}', {{ $isActive ? 'true' : 'false' }})">
                                        @if($isActive)
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                            </svg>
                                        @else
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                                            </svg>
                                        @endif
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 8px;">📋</div>
                                <div style="font-size: 14px; font-weight: 500;">Không tìm thấy tài khoản nào phù hợp với điều kiện tìm kiếm/lọc.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 5. FOOTER: Chuẩn hóa theo hình ảnh (Số hàng mỗi trang [10 ▼]   X-Y / Z bản ghi   < 1 ... n >) --}}
        <div class="rs-table-footer">
            <div class="rs-footer-left">
                <div class="rs-per-page-inline">
                    <span class="rs-per-page-label">Số hàng mỗi trang</span>
                    <select class="rs-per-page-select" onchange="document.getElementById('per_page_hidden').value = this.value; document.getElementById('userFilterForm').submit();">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="rs-records-count">
                    {{ $nguoiDungs->firstItem() ?? 0 }}-{{ $nguoiDungs->lastItem() ?? 0 }} / {{ $nguoiDungs->total() }} bản ghi
                </div>
            </div>

            <div class="rs-footer-pagination">
                <ul class="rs-pagination">
                    {{-- Nút Previous (<) --}}
                    @if ($nguoiDungs->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $nguoiDungs->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                    @endif

                    {{-- Logic sliding window: Chỉ hiển thị trang đầu cuối, ở giữa là ... --}}
                    @php
                        $currentPage = $nguoiDungs->currentPage();
                        $lastPage = $nguoiDungs->lastPage();
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
                            <li class="page-item"><a class="page-link" href="{{ $nguoiDungs->url($p) }}">{{ $p }}</a></li>
                        @endif
                    @endforeach

                    {{-- Nút Next (>) --}}
                    @if ($nguoiDungs->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $nguoiDungs->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                    @endif
                </ul>
            </div>
        </div>

    </div>

</div>

{{-- ==========================================================================
   MODAL 1: THÊM TÀI KHOẢN MỚI
   ========================================================================== --}}
<div class="rs-modal" id="createModal">
    <div class="rs-modal-dialog">
        <form method="POST" action="{{ route('nguoi-dung.store') }}" id="createForm">
            @csrf
            <div class="rs-modal-header">
                <h3 class="rs-modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" x2="19" y1="8" y2="14"/>
                        <line x1="22" x2="16" y1="11" y2="11"/>
                    </svg>
                    Thêm tài khoản mới
                </h3>
                <button type="button" class="rs-modal-close" onclick="closeCreateModal()">&times;</button>
            </div>

            <div class="rs-modal-body">
                <div class="rs-form-grid">
                    {{-- Mã tài khoản & Mã đối tượng tương ứng --}}
                    <div class="rs-form-group col-span-2">
                        <div style="display: flex; gap: 12px; width: 100%;">
                            <div style="flex: 1;">
                                <label class="rs-form-label">Mã tài khoản</label>
                                <input type="text" class="rs-form-control rs-readonly-input" value="{{ $nextMaTK }}" readonly>
                            </div>
                            <div style="flex: 1; display: none;" id="box_ma_doi_tuong">
                                <label class="rs-form-label" id="label_ma_doi_tuong">Mã đối tượng dự kiến</label>
                                <input type="text" class="rs-form-control rs-readonly-input" id="input_ma_doi_tuong" value="{{ $nextMaNV }}" readonly style="color: #f43f5e; font-weight: 700;">
                            </div>
                        </div>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Tên đăng nhập <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="ten_dang_nhap" id="create_ten_dang_nhap" class="rs-form-control" required placeholder="VD: admin_rose, nv_thao" autocomplete="off">
                        <div id="create_username_feedback" class="rs-feedback-msg" style="display:none;"></div>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Mật khẩu khởi tạo <span style="color:#e11d48;">*</span></label>
                        <input type="password" name="mat_khau" class="rs-form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                    </div>

                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Phân quyền vai trò <span style="color:#e11d48;">*</span></label>
                        <select name="vai_tro" id="create_vai_tro" class="rs-form-control" required onchange="toggleCreateRoleFields()">
                            <option value="">-- Chọn vai trò --</option>
                            <option value="ADMIN">Admin (Quản trị viên)</option>
                            <option value="NHAN_VIEN">Nhân viên</option>
                            <option value="SHIPPER">Shipper</option>
                            <option value="KHACH_HANG">Khách hàng</option>
                        </select>
                    </div>

                    {{-- Nhóm trường thông tin Khách hàng (nếu tạo KH) --}}
                    <div id="createKhachHangFields" class="col-span-2" style="display:none; border-top:1px dashed #e2e8f0; padding-top:16px; margin-top:6px; width: 100%;">
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <span style="color:#f43f5e;">👤</span> Thông tin chi tiết khách hàng
                        </div>
                        <div class="rs-form-grid" style="width: 100%;">
                            <div class="rs-form-group col-span-2">
                                <label class="rs-form-label">Họ và tên khách hàng <span style="color:#e11d48;">*</span></label>
                                <input type="text" name="ten_khach_hang" id="create_ten_khach_hang" class="rs-form-control" placeholder="Nguyễn Văn A">
                            </div>
                            <div class="rs-form-group">
                                <label class="rs-form-label">Số điện thoại <span style="color:#e11d48;">*</span></label>
                                <input type="text" name="so_dien_thoai" id="create_so_dien_thoai" class="rs-form-control" placeholder="0987654321">
                            </div>
                            <div class="rs-form-group">
                                <label class="rs-form-label">Email <span style="color:#e11d48;">*</span></label>
                                <input type="email" name="email" id="create_email" class="rs-form-control" placeholder="khachhang@gmail.com">
                            </div>
                            <div class="rs-form-group col-span-2">
                                <label class="rs-form-label">Địa chỉ</label>
                                <input type="text" name="dia_chi" class="rs-form-control" placeholder="Địa chỉ giao hàng">
                            </div>
                        </div>
                    </div>

                    {{-- Nhóm trường thông tin Nhân sự (Nhân viên / Shipper) --}}
                    <div id="createNhanVienFields" class="col-span-2" style="display:none; border-top:1px dashed #e2e8f0; padding-top:16px; margin-top:6px; width: 100%;">
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <span style="color:#f43f5e;">📋</span> Thông tin chi tiết nhân sự
                        </div>
                        <div class="rs-form-grid" style="width: 100%;">
                            <div class="rs-form-group col-span-2">
                                <label class="rs-form-label">Họ và tên nhân viên <span style="color:#e11d48;">*</span></label>
                                <input type="text" name="ten_nhan_vien" id="create_ten_nhan_vien" class="rs-form-control" placeholder="Trần Thị B">
                            </div>
                            <div class="rs-form-group">
                                <label class="rs-form-label">Số điện thoại <span style="color:#e11d48;">*</span></label>
                                <input type="text" name="so_dien_thoai_nv" id="create_so_dien_thoai_nv" class="rs-form-control" placeholder="0912345678">
                            </div>
                            <div class="rs-form-group">
                                <label class="rs-form-label">Email</label>
                                <input type="email" name="email_nv" class="rs-form-control" placeholder="nhanvien@example.com">
                            </div>
                            <div class="rs-form-group">
                                <label class="rs-form-label">Chức vụ <span style="color:#e11d48;">*</span></label>
                                <select name="chuc_vu" id="create_chuc_vu" class="rs-form-control">
                                    <option value="CSKH">CSKH</option>
                                    <option value="VAN_HANH">Vận hành</option>
                                    <option value="THIET_KE">Thiết kế</option>
                                    <option value="ONLINE">Online</option>
                                    <option value="SHIPPER">Shipper</option>
                                    <option value="KHAC">Khác</option>
                                </select>
                            </div>
                            <div class="rs-form-group">
                                <label class="rs-form-label">Mức lương (VNĐ)</label>
                                <input type="number" name="luong" class="rs-form-control" step="100000" min="0" placeholder="VD: 8000000">
                            </div>
                            <div class="rs-form-group col-span-2">
                                <label class="rs-form-label">Mô tả công việc</label>
                                <input type="text" name="cong_viec" class="rs-form-control" placeholder="VD: Phụ trách CSKH fanpage">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeCreateModal()">Hủy</button>
                <button type="submit" class="rs-btn-submit">Lưu tài khoản</button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
   MODAL 2: XÁC NHẬN KHÓA / MỞ KHÓA TÀI KHOẢN (ĐẶC TẢ UC33 / BẢO MẬT)
   ========================================================================== --}}
<div class="rs-modal" id="statusModal">
    <div class="rs-modal-dialog" style="max-width: 500px;">
        <form method="POST" action="" id="statusForm">
            @csrf
            @method('PATCH')
            <input type="hidden" name="target_status" id="status_target_input" value="">

            <div class="rs-modal-header">
                <h3 class="rs-modal-title" id="status_modal_title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Xác nhận thay đổi trạng thái
                </h3>
                <button type="button" class="rs-modal-close" onclick="closeStatusModal()">&times;</button>
            </div>

            <div class="rs-modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b; font-size: 13px;">Tên đăng nhập:</span>
                        <strong id="status_username" style="font-family: monospace; color: #0f172a; font-size: 14px;">—</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b; font-size: 13px;">Chủ sở hữu:</span>
                        <strong id="status_fullname" style="color: #334155; font-size: 13.5px;">—</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b; font-size: 13px;">Vai trò:</span>
                        <span id="status_role" style="font-size: 13px; font-weight: 600;">—</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b; font-size: 13px;">Thay đổi:</span>
                        <span id="status_transition" style="font-size: 13px; font-weight: 700;">—</span>
                    </div>
                </div>

                <div id="status_modal_warning" style="font-size: 13.5px; line-height: 1.5; border-radius: 10px; padding: 12px 16px;">
                    <!-- Nội dung cảnh báo cập nhật bằng JS -->
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeStatusModal()">Hủy</button>
                <button type="submit" class="rs-btn-submit" id="status_confirm_btn">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Modal Thêm tài khoản
    function openCreateModal() {
        document.getElementById('createForm').reset();
        toggleCreateRoleFields();
        document.getElementById('createModal').classList.add('show');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('show');
    }

    function toggleCreateRoleFields() {
        const role = document.getElementById('create_vai_tro').value;
        const khFields = document.getElementById('createKhachHangFields');
        const nvFields = document.getElementById('createNhanVienFields');
        const boxDoiTuong = document.getElementById('box_ma_doi_tuong');
        const labelDoiTuong = document.getElementById('label_ma_doi_tuong');
        const inputDoiTuong = document.getElementById('input_ma_doi_tuong');

        if (role === 'KHACH_HANG') {
            khFields.style.display = 'block';
            nvFields.style.display = 'none';
            boxDoiTuong.style.display = 'block';
            labelDoiTuong.textContent = 'Mã khách hàng dự kiến';
            inputDoiTuong.value = '{{ $nextMaKH }}';
            document.getElementById('create_ten_khach_hang').setAttribute('required', 'required');
            document.getElementById('create_so_dien_thoai').setAttribute('required', 'required');
            document.getElementById('create_email').setAttribute('required', 'required');
            document.getElementById('create_ten_nhan_vien').removeAttribute('required');
            document.getElementById('create_so_dien_thoai_nv').removeAttribute('required');
        } else if (role === 'NHAN_VIEN' || role === 'SHIPPER') {
            khFields.style.display = 'none';
            nvFields.style.display = 'block';
            boxDoiTuong.style.display = 'block';
            if (role === 'SHIPPER') {
                labelDoiTuong.textContent = 'Mã Shipper (NV) dự kiến';
                inputDoiTuong.value = '{{ $nextMaNV }}';
                document.getElementById('create_chuc_vu').value = 'SHIPPER';
            } else {
                labelDoiTuong.textContent = 'Mã nhân viên dự kiến';
                inputDoiTuong.value = '{{ $nextMaNV }}';
            }
            document.getElementById('create_ten_nhan_vien').setAttribute('required', 'required');
            document.getElementById('create_so_dien_thoai_nv').setAttribute('required', 'required');
            document.getElementById('create_ten_khach_hang').removeAttribute('required');
            document.getElementById('create_so_dien_thoai').removeAttribute('required');
            document.getElementById('create_email').removeAttribute('required');
        } else {
            khFields.style.display = 'none';
            nvFields.style.display = 'none';
            boxDoiTuong.style.display = 'none';
            document.getElementById('create_ten_khach_hang').removeAttribute('required');
            document.getElementById('create_so_dien_thoai').removeAttribute('required');
            document.getElementById('create_email').removeAttribute('required');
            document.getElementById('create_ten_nhan_vien').removeAttribute('required');
            document.getElementById('create_so_dien_thoai_nv').removeAttribute('required');
        }
    }

    // Real-time kiểm tra trùng Tên đăng nhập ngay khi Admin gõ
    let usernameTimer = null;
    const usernameInput = document.getElementById('create_ten_dang_nhap');
    const usernameFeedback = document.getElementById('create_username_feedback');
    const createSubmitBtn = document.querySelector('#createModal button[type="submit"]');

    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            clearTimeout(usernameTimer);
            const val = this.value.trim();

            if (val.length === 0) {
                usernameFeedback.style.display = 'none';
                usernameInput.classList.remove('is-invalid', 'is-valid');
                if (createSubmitBtn) createSubmitBtn.disabled = false;
                return;
            }

            if (val.length < 3) {
                usernameFeedback.style.display = 'flex';
                usernameFeedback.className = 'rs-feedback-msg error';
                usernameFeedback.innerHTML = '✕ Tên đăng nhập phải có ít nhất 3 ký tự';
                usernameInput.classList.add('is-invalid');
                usernameInput.classList.remove('is-valid');
                if (createSubmitBtn) createSubmitBtn.disabled = true;
                return;
            }

            usernameTimer = setTimeout(() => {
                fetch(`{{ route('nguoi-dung.check-username') }}?ten_dang_nhap=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(data => {
                        usernameFeedback.style.display = 'flex';
                        if (data.exists) {
                            usernameFeedback.className = 'rs-feedback-msg error';
                            usernameFeedback.innerHTML = '✕ Tên đăng nhập này đã được sử dụng! Vui lòng chọn tên khác.';
                            usernameInput.classList.add('is-invalid');
                            usernameInput.classList.remove('is-valid');
                            if (createSubmitBtn) createSubmitBtn.disabled = true;
                        } else {
                            usernameFeedback.className = 'rs-feedback-msg success';
                            usernameFeedback.innerHTML = '✓ Tên đăng nhập hợp lệ, có thể sử dụng.';
                            usernameInput.classList.remove('is-invalid');
                            usernameInput.classList.add('is-valid');
                            if (createSubmitBtn) createSubmitBtn.disabled = false;
                        }
                    })
                    .catch(() => {
                        usernameFeedback.style.display = 'none';
                        if (createSubmitBtn) createSubmitBtn.disabled = false;
                    });
            }, 300);
        });
    }

    // Modal Khóa / Mở khóa tài khoản
    function openStatusModal(userId, username, fullname, role, isCurrentlyActive) {
        const form = document.getElementById('statusForm');
        form.action = `/nguoi-dung/${userId}/toggle-status`;

        document.getElementById('status_username').textContent = username;
        document.getElementById('status_fullname').textContent = fullname || '—';

        // Hiển thị tên vai trò
        let roleName = 'Khách hàng';
        if (role === 'ADMIN') roleName = 'Quản trị viên';
        else if (role === 'NHAN_VIEN') roleName = 'Nhân viên';
        else if (role === 'SHIPPER') roleName = 'Shipper';
        document.getElementById('status_role').textContent = roleName;

        const titleElem = document.getElementById('status_modal_title');
        const targetInput = document.getElementById('status_target_input');
        const transitionElem = document.getElementById('status_transition');
        const warningElem = document.getElementById('status_modal_warning');
        const confirmBtn = document.getElementById('status_confirm_btn');

        if (isCurrentlyActive) {
            // Chuẩn bị Khóa
            targetInput.value = 'BI_KHOA';
            titleElem.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Xác nhận khóa tài khoản
            `;
            transitionElem.innerHTML = `<span style="color:#10b981;">Hoạt động</span> &rarr; <span style="color:#e11d48;">Bị khóa</span>`;
            warningElem.style.background = '#fff1f2';
            warningElem.style.border = '1px solid #fecdd3';
            warningElem.style.color = '#9f1239';
            warningElem.innerHTML = `
                <strong>⚠️ Cảnh báo nghiệp vụ:</strong><br>
                Tài khoản sau khi bị khóa sẽ lập tức bị đăng xuất khỏi hệ thống và bị chặn toàn bộ các thao tác yêu cầu đăng nhập. Dữ liệu lịch sử của người dùng vẫn được bảo toàn toàn vẹn.
            `;
            confirmBtn.textContent = 'Khóa tài khoản';
            confirmBtn.style.background = '#e11d48';
            confirmBtn.style.boxShadow = '0 4px 12px rgba(225, 29, 72, 0.25)';
        } else {
            // Chuẩn bị Mở khóa
            targetInput.value = 'HOAT_DONG';
            titleElem.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                </svg>
                Xác nhận mở khóa tài khoản
            `;
            transitionElem.innerHTML = `<span style="color:#e11d48;">Bị khóa</span> &rarr; <span style="color:#10b981;">Hoạt động</span>`;
            warningElem.style.background = '#ecfdf5';
            warningElem.style.border = '1px solid #a7f3d0';
            warningElem.style.color = '#065f46';
            warningElem.innerHTML = `
                <strong>✓ Xác nhận mở khóa:</strong><br>
                Tài khoản sẽ được khôi phục quyền truy cập vào hệ thống bình thường với các chức năng tương ứng vai trò đã phân quyền.
            `;
            confirmBtn.textContent = 'Mở khóa tài khoản';
            confirmBtn.style.background = '#10b981';
            confirmBtn.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.25)';
        }

        document.getElementById('statusModal').classList.add('show');
    }

    function closeStatusModal() {
        document.getElementById('statusModal').classList.remove('show');
    }

    // Đóng modal khi bấm ra ngoài backdrop hoặc phím ESC
    window.addEventListener('click', function(e) {
        if (e.target === document.getElementById('createModal')) {
            closeCreateModal();
        }
        if (e.target === document.getElementById('statusModal')) {
            closeStatusModal();
        }
    });

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeStatusModal();
        }
    });
</script>
@endpush