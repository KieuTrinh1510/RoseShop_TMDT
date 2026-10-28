@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/Admin/NhanVien.css') }}">
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
        .rs-truncate-cell {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
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
        .rs-code-employee {
            font-family: monospace;
            font-size: 11.5px;
            font-weight: 700;
            color: #EC3D8F !important;
            white-space: nowrap !important;
        }
        .rs-btn-search {
            background-color: #EC3D8F !important;
            border-radius: 8px !important;
        }
        .rs-btn-search:hover {
            background-color: #D82C7C !important;
        }
        .rs-table th:last-child, .rs-table td:last-child {
            padding-right: 20px !important;
        }
    </style>
@endpush

@section('content')

<div class="rs-management-page">

    {{-- 1. HEADER TRANG --}}
    <div class="rs-header-block" style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 24px;">
        <div>
            <h1 class="rs-header-title">Quản lý nhân viên</h1>
            <p class="rs-header-subtitle">Theo dõi, quản lý thông tin nhân viên và phân quyền hệ thống</p>
        </div>
    </div>

    {{-- THÔNG BÁO --}}
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
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Tổng nhân viên</span>
                <span class="rs-kpi-value">{{ $tongNhanVien }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Nhân viên</span>
                <span class="rs-kpi-value">{{ $soNhanVien }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                    <path d="M15 18H9"/>
                    <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                    <circle cx="17" cy="18" r="2"/>
                    <circle cx="7" cy="18" r="2"/>
                </svg>
            </div>
            <div class="rs-kpi-info">
                <span class="rs-kpi-label">Shipper</span>
                <span class="rs-kpi-value">{{ $soShipper }}</span>
            </div>
        </div>

        <div class="rs-kpi-card">
            <div class="rs-kpi-icon orange">
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
    </div>

    {{-- 3. CARD CHÍNH CHỨA BỘ LỌC VÀ BẢNG --}}
    <div class="rs-main-card">

        {{-- Form lọc: Tất cả bộ lọc + Ô tìm kiếm + Nút tìm kiếm trên cùng 1 hàng --}}
        <form method="GET" action="{{ route('nhan-vien.index') }}" id="nhanVienFilterForm" class="rs-toolbar-unified">
            <input type="hidden" name="per_page" id="nv_per_page_hidden" value="{{ $perPage }}">

            {{-- 1. Lọc theo chức vụ --}}
            <select name="chuc_vu" class="rs-select" onchange="document.getElementById('nhanVienFilterForm').submit()">
                <option value="">Tất cả chức vụ</option>
                @foreach($chucVus as $val => $lbl)
                    <option value="{{ $val }}" {{ request('chuc_vu') == $val ? 'selected' : '' }}>
                        {{ $lbl }}
                    </option>
                @endforeach
            </select>

            {{-- 2. Lọc theo trạng thái tài khoản --}}
            <select name="trang_thai" class="rs-select" onchange="document.getElementById('nhanVienFilterForm').submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="HOAT_DONG" {{ request('trang_thai') == 'HOAT_DONG' ? 'selected' : '' }}>Hoạt động</option>
                <option value="BI_KHOA" {{ request('trang_thai') == 'BI_KHOA' ? 'selected' : '' }}>Bị khóa</option>
            </select>

            {{-- 3. Sắp xếp mức lương --}}
            <select name="sort" class="rs-select" onchange="document.getElementById('nhanVienFilterForm').submit()">
                <option value="">Sắp xếp mức lương</option>
                <option value="luong_desc" {{ request('sort') == 'luong_desc' ? 'selected' : '' }}>Lương cao → thấp</option>
                <option value="luong_asc" {{ request('sort') == 'luong_asc' ? 'selected' : '' }}>Lương thấp → cao</option>
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
                       placeholder="Tìm kiếm theo tên, email, SĐT..."
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
            @if(request()->anyFilled(['keyword', 'chuc_vu', 'trang_thai', 'sort']))
                <a href="{{ route('nhan-vien.index', ['per_page' => $perPage]) }}" class="rs-btn-filter-clear" title="Đặt lại bộ lọc">
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
                <span>Danh sách nhân viên</span>
                <span class="rs-count-badge">{{ $nhanViens->total() }}</span>
            </div>
        </div>

        {{-- 4. BẢNG DỮ LIỆU CHUẨN HÓA VỚI CỘT MÃ NHÂN VIÊN VÀ TIỀN COD --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <colgroup>
                    <col style="width: 95px;">
                    <col style="width: 170px;">
                    <col style="width: 125px;">
                    <col style="width: 220px;">
                    <col style="width: 115px;">
                    <col style="width: 200px;">
                    <col style="width: 135px;">
                    <col style="width: 115px;">
                    <col style="width: 135px;">
                    <col style="width: 145px;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">Mã NV</th>
                        <th style="text-align: center;">Tên nhân viên</th>
                        <th style="text-align: center;">Số điện thoại</th>
                        <th style="text-align: center;">Email</th>
                        <th style="text-align: center;">Chức vụ</th>
                        <th style="text-align: center;">Công việc</th>
                        <th style="text-align: center;">Mức lương</th>
                        <th style="text-align: center;">Trạng thái</th>
                        <th style="text-align: center;">Tiền COD cần nộp</th>
                        <th style="text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nhanViens as $nv)
                        @php
                            $user = $nv->nguoiDung;
                            $isActive = $user ? ($user->trang_thai === 'HOAT_DONG') : true;
                            $isShipper = ($nv->chuc_vu === 'SHIPPER');
                            $debt = $shipperDebts[$nv->ma_nhan_vien] ?? 0;
                            $tienPhat = (float) ($nv->tien_phat ?? 0);
                            $tongCanNop = (float) $debt + $tienPhat;
                            $hasHistory = in_array($nv->ma_nhan_vien, $hasBusinessIds ?? []);
                        @endphp
                        <tr>
                            {{-- 1. Mã nhân viên --}}
                            <td style="text-align: center; white-space: nowrap;">
                                <span class="rs-code-employee">NV-{{ str_pad($nv->ma_nhan_vien, 4, '0', STR_PAD_LEFT) }}</span>
                            </td>
                            <td style="white-space: nowrap;">
                                <span class="rs-text-bold">{{ $nv->ten_nhan_vien }}</span>
                            </td>
                            <td style="white-space: nowrap; font-family: monospace; font-size: 13px;">{{ $nv->so_dien_thoai ?: '—' }}</td>
                            <td class="rs-truncate-cell" title="{{ $nv->email }}" style="font-size: 12.5px;">{{ $nv->email ?: '—' }}</td>
                            <td style="white-space: nowrap; text-align: center;">
                                <span class="rs-role-badge {{ $isShipper ? 'shipper' : '' }}">
                                    {{ $chucVus[$nv->chuc_vu] ?? $nv->chuc_vu }}
                                </span>
                            </td>
                            <td class="rs-truncate-cell" title="{{ $nv->cong_viec }}">{{ $nv->cong_viec ?: '—' }}</td>
                            <td style="text-align: right; white-space: nowrap;">
                                @if($nv->luong > 0)
                                    <span style="font-weight: 600; color: #0f172a; white-space: nowrap;">{{ number_format($nv->luong, 0, ',', '.') }} đ</span>
                                @else
                                    <span style="color: #94a3b8;">Chưa cập nhật</span>
                                @endif
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                @if($isActive)
                                    <span class="rs-status-badge active">Hoạt động</span>
                                @else
                                    <span class="rs-status-badge locked">Bị khóa</span>
                                @endif
                            </td>
                            {{-- Cột Tiền COD cần nộp --}}
                            <td style="text-align: center; white-space: nowrap; font-weight: 600;">
                                @if($isShipper)
                                    <span style="color: {{ $tongCanNop > 0 ? '#ea580c' : '#16a34a' }};">
                                        {{ number_format($tongCanNop, 0, ',', '.') }} đ
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <div class="rs-action-group">
                                    {{-- Nút Nộp tiền COD + Phạt (chỉ hiện cho SHIPPER có nợ hoặc có phạt) --}}
                                    @if($isShipper && ($debt > 0 || $tienPhat > 0))
                                        <button type="button"
                                                class="rs-action-btn payback"
                                                style="background:#e0f2fe; color:#0369a1;"
                                                title="Xác nhận đã nộp tiền COD & phạt"
                                                onclick="openPaybackModal({{ $nv->ma_nhan_vien }}, '{{ addslashes($nv->ten_nhan_vien) }}', {{ (float) $debt }}, {{ $tienPhat }})">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="2" y="5" width="20" height="14" rx="2"/>
                                                <line x1="2" y1="10" x2="22" y2="10"/>
                                                <circle cx="8" cy="15" r="1.5"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Nút Sửa --}}
                                    <button type="button"
                                             class="rs-action-btn edit"
                                             title="Sửa thông tin"
                                             onclick="openEditModal({
                                                 id: {{ $nv->ma_nhan_vien }},
                                                 ma_nv: 'NV-{{ str_pad($nv->ma_nhan_vien, 4, '0', STR_PAD_LEFT) }}',
                                                 ten: '{{ addslashes($nv->ten_nhan_vien) }}',
                                                 email: '{{ addslashes($nv->email ?? '') }}',
                                                 sdt: '{{ addslashes($nv->so_dien_thoai ?? '') }}',
                                                 chuc_vu: '{{ $nv->chuc_vu }}',
                                                 cong_viec: '{{ addslashes($nv->cong_viec ?? '') }}',
                                                 luong: {{ $nv->luong ?? 0 }},
                                                 tien_cod: '{{ $isShipper ? number_format($debt, 0, ',', '.') . ' đ' : '—' }}'
                                             })">
                                         <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                             <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                             <path d="m15 5 4 4"/>
                                         </svg>
                                     </button>

                                    {{-- Nút Khóa / Mở hoạt động --}}
                                    <button type="button"
                                             class="rs-action-btn lock"
                                             title="{{ $isActive ? 'Ngừng hoạt động (Khóa tài khoản)' : 'Mở lại hoạt động' }}"
                                             onclick="openStatusModal({{ $nv->ma_nhan_vien }}, '{{ addslashes($nv->ten_nhan_vien) }}', '{{ $chucVus[$nv->chuc_vu] ?? $nv->chuc_vu }}', '{{ addslashes($nv->email ?? '—') }}', {{ $isActive ? 'true' : 'false' }})">
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

                                    {{-- Nút Xóa --}}
                                    <button type="button"
                                             class="rs-action-btn delete"
                                             style="{{ $hasHistory ? 'opacity: 0.6;' : '' }}"
                                             title="{{ $hasHistory ? 'Bảo toàn hồ sơ: Nhân viên đã phát sinh nghiệp vụ' : 'Xóa nhân viên' }}"
                                             onclick="openDeleteModal({{ $nv->ma_nhan_vien }}, '{{ addslashes($nv->ten_nhan_vien) }}', {{ $hasHistory ? 'true' : 'false' }})">
                                         <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                             <path d="M3 6h18"/>
                                             <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                             <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                         </svg>
                                     </button>
                                 </div>
                             </td>
                         </tr>
                     @empty
                         <tr>
                             <td colspan="10" style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                                 <div style="font-size: 32px; margin-bottom: 8px;">📋</div>
                                 <div style="font-size: 14px; font-weight: 500;">Không tìm thấy nhân viên nào phù hợp với điều kiện tìm kiếm.</div>
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
                    <select class="rs-per-page-select" onchange="document.getElementById('nv_per_page_hidden').value = this.value; document.getElementById('nhanVienFilterForm').submit();">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="rs-records-count">
                    {{ $nhanViens->firstItem() ?? 0 }}-{{ $nhanViens->lastItem() ?? 0 }} / {{ $nhanViens->total() }} bản ghi
                </div>
            </div>

            <div class="rs-footer-pagination">
                <ul class="rs-pagination">
                    {{-- Nút trang trước (<) --}}
                    @if ($nhanViens->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $nhanViens->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                    @endif

                    {{-- Logic sliding window: Chỉ hiển thị trang đầu cuối, ở giữa là ... --}}
                    @php
                        $currentPage = $nhanViens->currentPage();
                        $lastPage = $nhanViens->lastPage();
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
                            <li class="page-item"><a class="page-link" href="{{ $nhanViens->url($p) }}">{{ $p }}</a></li>
                        @endif
                    @endforeach

                    {{-- Nút trang sau (>) --}}
                    @if ($nhanViens->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $nhanViens->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                    @endif
                </ul>
            </div>
        </div>

    </div>

</div>

{{-- ==========================================================================
   MODAL 1: THÊM NHÂN VIÊN MỚI
   ========================================================================== --}}
<div class="rs-modal" id="createModal">
    <div class="rs-modal-dialog">
        <form method="POST" action="{{ route('nhan-vien.store') }}">
            @csrf
            <div class="rs-modal-header">
                <h3 class="rs-modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" x2="19" y1="8" y2="14"/>
                        <line x1="22" x2="16" y1="11" y2="11"/>
                    </svg>
                    Thêm nhân viên mới
                </h3>
                <button type="button" class="rs-modal-close" onclick="closeCreateModal()">&times;</button>
            </div>

            <div class="rs-modal-body">
                <div class="rs-form-grid">
                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Mã nhân viên</label>
                        <input type="text" class="rs-form-control rs-readonly-input" value="{{ $nextMaNV }}" readonly>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Tên đăng nhập <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="ten_dang_nhap" class="rs-form-control" required placeholder="VD: nv_linh">
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Mật khẩu <span style="color:#e11d48;">*</span></label>
                        <input type="password" name="mat_khau" class="rs-form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                    </div>

                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Họ và tên nhân viên <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="ten_nhan_vien" class="rs-form-control" required placeholder="VD: Nguyễn Văn An">
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Số điện thoại <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="so_dien_thoai" class="rs-form-control" placeholder="0987654321" required>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Email</label>
                        <input type="email" name="email" class="rs-form-control" placeholder="nhanvien@example.com">
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Chức vụ <span style="color:#e11d48;">*</span></label>
                        <select name="chuc_vu" class="rs-form-control" required>
                            @foreach($chucVus as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Mức lương (VNĐ)</label>
                        <input type="number" name="luong" class="rs-form-control" step="100000" min="0" placeholder="VD: 8000000">
                    </div>

                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Mô tả công việc</label>
                        <input type="text" name="cong_viec" class="rs-form-control" placeholder="VD: Phụ trách tư vấn & CSKH fanpage">
                    </div>
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeCreateModal()">Hủy</button>
                <button type="submit" class="rs-btn-submit">Lưu nhân viên</button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
   MODAL 2: CHỈNH SỬA THÔNG TIN NHÂN VIÊN
   ========================================================================== --}}
<div class="rs-modal" id="editModal">
    <div class="rs-modal-dialog">
        <form method="POST" action="" id="editForm">
            @csrf
            @method('PUT')
            <div class="rs-modal-header">
                <h3 class="rs-modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                        <path d="m15 5 4 4"/>
                    </svg>
                    Chỉnh sửa thông tin nhân viên
                </h3>
                <button type="button" class="rs-modal-close" onclick="closeEditModal()">&times;</button>
            </div>

            <div class="rs-modal-body">
                <div class="rs-form-grid">
                    {{-- Ô Mã nhân viên & Tiền COD cần nộp (Khung xám tự hiểu không được sửa) --}}
                    <div class="rs-form-group">
                        <label class="rs-form-label">Mã nhân viên</label>
                        <input type="text" id="edit_ma_nhan_vien" class="rs-form-control rs-readonly-input" readonly>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Tiền COD cần nộp</label>
                        <input type="text" id="edit_tien_cod" class="rs-form-control rs-readonly-input" readonly>
                    </div>

                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Họ và tên nhân viên <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="ten_nhan_vien" id="edit_ten_nhan_vien" class="rs-form-control" required>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Số điện thoại <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="so_dien_thoai" id="edit_so_dien_thoai" class="rs-form-control" required>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="rs-form-control">
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Chức vụ <span style="color:#e11d48;">*</span></label>
                        <select name="chuc_vu" id="edit_chuc_vu" class="rs-form-control" required>
                            @foreach($chucVus as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rs-form-group">
                        <label class="rs-form-label">Mức lương (VNĐ)</label>
                        <input type="number" name="luong" id="edit_luong" class="rs-form-control" step="100000" min="0">
                    </div>

                    <div class="rs-form-group col-span-2">
                        <label class="rs-form-label">Mô tả công việc</label>
                        <input type="text" name="cong_viec" id="edit_cong_viec" class="rs-form-control">
                    </div>
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeEditModal()">Hủy</button>
                <button type="submit" class="rs-btn-submit">Cập nhật thay đổi</button>
            </div>
        </form>
    </div>
</div>


{{-- ==========================================================================
   MODAL 3: XÁC NHẬN XÓA / THÔNG BÁO BẢO TOÀN HỒ SƠ NHÂN VIÊN
   ========================================================================== --}}
<div class="rs-modal" id="deleteModal">
    <div class="rs-modal-dialog" style="max-width: 500px;">
        <form method="POST" action="" id="deleteForm">
            @csrf
            @method('DELETE')

            <div class="rs-modal-header">
                <h3 class="rs-modal-title" id="delete_modal_title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                    Xác nhận xóa nhân viên
                </h3>
                <button type="button" class="rs-modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>

            <div class="rs-modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 14px;">
                    <div style="font-size: 14px;"><strong>Nhân viên:</strong> <span id="delete_emp_name">—</span></div>
                </div>

                <div id="delete_modal_warning" style="font-size: 13.5px; line-height: 1.5; border-radius: 8px; padding: 12px;">
                    <!-- JS cập nhật nội dung cảnh báo -->
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeDeleteModal()">Hủy</button>
                <button type="submit" class="rs-btn-submit" id="delete_confirm_btn" style="background: #e11d48;">
                    Xác nhận xóa
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
   MODAL 4: XÁC NHẬN KHÓA / NGỪNG HOẠT ĐỘNG HOẶC MỞ LẠI NHÂN VIÊN
   ========================================================================== --}}
<div class="rs-modal" id="statusModal">
    <div class="rs-modal-dialog" style="max-width: 520px;">
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
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 14px;">
                    <div style="font-size: 14px; margin-bottom: 6px;"><strong>Họ và tên:</strong> <span id="status_emp_name">—</span></div>
                    <div style="font-size: 13.5px; color: #475467; margin-bottom: 4px;"><strong>Chức vụ:</strong> <span id="status_emp_role">—</span></div>
                    <div style="font-size: 13.5px; color: #475467;"><strong>Email:</strong> <span id="status_emp_email">—</span></div>
                </div>

                <div id="status_modal_warning" style="font-size: 13.5px; line-height: 1.5; color: #b45309; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px;">
                    <!-- Cập nhật bằng JS -->
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closeStatusModal()">Hủy</button>
                <button type="submit" class="rs-btn-submit" id="status_confirm_btn">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

{{-- ==========================================================================
   MODAL 5: XÁC NHẬN NỘP TIỀN COD + PHẠT
   ========================================================================== --}}
<div class="rs-modal" id="paybackModal">
    <div class="rs-modal-dialog" style="max-width: 540px;">
        <form method="POST" action="" id="paybackForm" onsubmit="submitPayback(event)">
            @csrf
            <div class="rs-modal-header">
                <h3 class="rs-modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                        <circle cx="8" cy="15" r="1.5"/>
                    </svg>
                    Xác nhận nộp tiền COD + Phạt
                </h3>
                <button type="button" class="rs-modal-close" onclick="closePaybackModal()">&times;</button>
            </div>

            <div class="rs-modal-body">
                <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:16px 18px; margin-bottom:16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;">
                        <span style="color:#0369a1;">Shipper:</span>
                        <strong id="pb_name" style="color:#0c4a6e;">—</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;">
                        <span style="color:#0369a1;">Tổng COD cần nộp:</span>
                        <strong id="pb_cod" style="color:#0c4a6e;">0 đ</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;">
                        <span style="color:#b45309;">Tiền phạt:</span>
                        <strong id="pb_fine" style="color:#b45309;">0 đ</strong>
                    </div>
                    <div style="border-top:1px dashed #7dd3fc; padding-top:10px; margin-top:10px; display:flex; justify-content:space-between; font-size:15px;">
                        <span style="font-weight:700; color:#0c4a6e;">TỔNG CẦN NỘP:</span>
                        <strong id="pb_total" style="color:#dc2626; font-size:16px;">0 đ</strong>
                    </div>
                </div>

                <div style="background:#fef3c7; border:1px solid #fde68a; border-radius:8px; padding:12px 14px; font-size:13px; color:#92400e; line-height:1.5;">
                    <strong>⚠️ Lưu ý:</strong> Sau khi xác nhận, tất cả đơn COD <em>đã thu tiền mặt</em> sẽ chuyển sang trạng thái <strong>"Đã nộp"</strong> và tiền phạt của shipper sẽ được <strong>xóa về 0</strong>. Thao tác này <u>không thể hoàn tác</u>.
                </div>
            </div>

            <div class="rs-modal-footer">
                <button type="button" class="rs-btn-cancel" onclick="closePaybackModal()">Hủy</button>
                <button type="submit" class="rs-btn-submit" id="pb_submit_btn" style="background:#0ea5e9;">
                    Xác nhận đã nộp
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Xử lý Modal Thêm mới
    function openCreateModal() {
        document.getElementById('createModal').classList.add('show');
    }
    function closeCreateModal() {
        document.getElementById('createModal').classList.remove('show');
    }

    // Xử lý Modal Sửa
    function openEditModal(data) {
        const form = document.getElementById('editForm');
        form.action = `/nhan-vien/${data.id}`;
        document.getElementById('edit_ma_nhan_vien').value = data.ma_nv || ('NV-' + String(data.id).padStart(4, '0'));
        document.getElementById('edit_tien_cod').value     = data.tien_cod || '—';
        document.getElementById('edit_ten_nhan_vien').value = data.ten;
        document.getElementById('edit_email').value         = data.email || '';
        document.getElementById('edit_so_dien_thoai').value = data.sdt || '';
        document.getElementById('edit_chuc_vu').value       = data.chuc_vu;
        document.getElementById('edit_luong').value         = data.luong || '';
        document.getElementById('edit_cong_viec').value     = data.cong_viec || '';

        document.getElementById('editModal').classList.add('show');
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('show');
    }

    // Xử lý Modal Khóa / Ngừng hoạt động (Hiển thị thông tin + xác nhận theo đặc tả)
    function openStatusModal(id, name, role, email, isActive) {
        const form = document.getElementById('statusForm');
        form.action = `/nhan-vien/${id}/toggle-status`;

        document.getElementById('status_emp_name').textContent = name;
        document.getElementById('status_emp_role').textContent = role;
        document.getElementById('status_emp_email').textContent = email;

        const titleEl     = document.getElementById('status_modal_title');
        const warningEl   = document.getElementById('status_modal_warning');
        const submitBtn   = document.getElementById('status_confirm_btn');
        const targetInput = document.getElementById('status_target_input');

        if (isActive) {
            targetInput.value = 'BI_KHOA';
            titleEl.innerHTML = '<span style="color:#e11d48;">⚠️ Xác nhận ngừng hoạt động nhân viên</span>';
            warningEl.style.color = '#991b1b';
            warningEl.style.background = '#fef2f2';
            warningEl.style.borderColor = '#fee2e2';
            warningEl.innerHTML = '<strong>Lưu ý:</strong> Nhân viên sẽ bị khóa quyền truy cập hệ thống ngay lập tức và không thể tiếp tục thực hiện công việc. Hồ sơ, lịch sử giao hàng và nghiệp vụ của nhân viên vẫn được <strong>bảo toàn nguyên vẹn</strong>.';
            submitBtn.textContent = 'Xác nhận ngừng hoạt động';
            submitBtn.style.background = '#e11d48';
        } else {
            targetInput.value = 'HOAT_DONG';
            titleEl.innerHTML = '<span style="color:#059669;">✓ Xác nhận mở lại hoạt động</span>';
            warningEl.style.color = '#166534';
            warningEl.style.background = '#f0fdf4';
            warningEl.style.borderColor = '#dcfce7';
            warningEl.innerHTML = 'Nhân viên sẽ được kích hoạt mở khóa tài khoản để tiếp tục đăng nhập và làm việc trên hệ thống bình thường.';
            submitBtn.textContent = 'Mở lại hoạt động';
            submitBtn.style.background = '#059669';
        }

        document.getElementById('statusModal').classList.add('show');
    }
    function closeStatusModal() {
        document.getElementById('statusModal').classList.remove('show');
    }


    // Xử lý Modal Xóa
    function openDeleteModal(id, name, hasHistory) {
        const form = document.getElementById('deleteForm');
        form.action = `/nhan-vien/${id}`;

        document.getElementById('delete_emp_name').textContent = name;

        const titleEl   = document.getElementById('delete_modal_title');
        const warningEl = document.getElementById('delete_modal_warning');
        const submitBtn = document.getElementById('delete_confirm_btn');

        if (hasHistory) {
            titleEl.innerHTML = '<span style="color:#b45309;">⚠️ Không thể xóa nhân viên này</span>';
            warningEl.style.color = '#92400e';
            warningEl.style.background = '#fef3c7';
            warningEl.style.border = '1px solid #fde68a';
            warningEl.innerHTML = '<strong>Bảo toàn hồ sơ nhân viên:</strong> Nhân viên này đã phát sinh nghiệp vụ trong hệ thống (đơn hàng giao thành công hoặc phiếu nhập kho).<br><br>Theo quy định bảo toàn dữ liệu, <strong>không được phép xóa vật lý</strong> để tránh sai lệch dữ liệu lịch sử. Vui lòng sử dụng tính năng <strong>"Ngừng hoạt động / Khóa tài khoản"</strong>.';
            submitBtn.style.display = 'none';
        } else {
            titleEl.innerHTML = '<span style="color:#e11d48;">🗑️ Xác nhận xóa nhân viên</span>';
            warningEl.style.color = '#991b1b';
            warningEl.style.background = '#fef2f2';
            warningEl.style.border = '1px solid #fee2e2';
            warningEl.innerHTML = 'Bạn có chắc chắn muốn xóa nhân viên này khỏi hệ thống? Thao tác này sẽ <strong>xóa vĩnh viễn</strong> hồ sơ nhân viên và tài khoản người dùng tương ứng khỏi hệ thống.';
            submitBtn.style.display = 'inline-flex';
        }

        document.getElementById('deleteModal').classList.add('show');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
    }

    // ============ XÁC NHẬN NỘP TIỀN COD + PHẠT ============
    function openPaybackModal(id, name, cod, fine) {
        const form = document.getElementById('paybackForm');
        form.action = `/nhan-vien/${id}/tra-tien`;
        document.getElementById('pb_name').textContent  = name;
        document.getElementById('pb_cod').textContent   = new Intl.NumberFormat('vi-VN').format(cod)  + ' đ';
        document.getElementById('pb_fine').textContent  = new Intl.NumberFormat('vi-VN').format(fine) + ' đ';
        document.getElementById('pb_total').textContent = new Intl.NumberFormat('vi-VN').format(cod + fine) + ' đ';

        const btn = document.getElementById('pb_submit_btn');
        btn.disabled = false;
        btn.textContent = 'Xác nhận đã nộp';

        document.getElementById('paybackModal').classList.add('show');
    }

    function closePaybackModal() {
        document.getElementById('paybackModal').classList.remove('show');
    }

    function submitPayback(e) {
        e.preventDefault();
        const form = document.getElementById('paybackForm');
        const btn  = document.getElementById('pb_submit_btn');
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closePaybackModal();
                alert('✓ ' + data.message);
                location.reload();
            } else {
                alert('✕ ' + (data.message || 'Có lỗi xảy ra.'));
                btn.disabled = false;
                btn.textContent = 'Xác nhận đã nộp';
            }
        })
        .catch(() => {
            alert('✕ Lỗi kết nối. Vui lòng thử lại.');
            btn.disabled = false;
            btn.textContent = 'Xác nhận đã nộp';
        });
    }

    // Đóng modal khi bấm ra ngoài nền mờ
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('rs-modal')) {
            e.target.classList.remove('show');
        }
    });
</script>
@endpush
