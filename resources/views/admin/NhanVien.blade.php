@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/Admin/NhanVien.css') }}">
@endpush

@section('content')

<div class="rs-management-page">

    {{-- 1. HEADER TRANG --}}
    <div class="rs-header-block">
        <div class="rs-header-icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
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

        {{-- Form lọc và tìm kiếm theo đặc tả R2.2 --}}
        <form method="GET" action="{{ route('nhan-vien.index') }}" id="nhanVienFilterForm">

            <div class="rs-filter-toolbar">
                {{-- Ô tìm kiếm --}}
                <div class="rs-search-wrapper">
                    <span class="rs-search-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </span>
                    <input type="text"
                           name="keyword"
                           value="{{ request('keyword') }}"
                           class="rs-input-search"
                           placeholder="Tìm kiếm theo tên, email, SĐT...">
                </div>

                {{-- Lọc theo chức vụ (Đặc tả R2.2) --}}
                <select name="chuc_vu" class="rs-select-filter">
                    <option value="">Tất cả chức vụ</option>
                    @foreach($chucVus as $val => $lbl)
                        <option value="{{ $val }}" {{ request('chuc_vu') == $val ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>

                {{-- Sắp xếp theo mức lương (Đặc tả R2.2) --}}
                <select name="sort" class="rs-select-filter">
                    <option value="">Sắp xếp mức lương</option>
                    <option value="luong_desc" {{ request('sort') == 'luong_desc' ? 'selected' : '' }}>Lương cao → thấp</option>
                    <option value="luong_asc" {{ request('sort') == 'luong_asc' ? 'selected' : '' }}>Lương thấp → cao</option>
                </select>

                {{-- Lọc theo trạng thái tài khoản --}}
                <select name="trang_thai" class="rs-select-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="HOAT_DONG" {{ request('trang_thai') == 'HOAT_DONG' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="BI_KHOA" {{ request('trang_thai') == 'BI_KHOA' ? 'selected' : '' }}>Bị khóa</option>
                </select>

                {{-- Nút Tìm kiếm --}}
                <button type="submit" class="rs-btn-search">
                    Tìm kiếm
                </button>

                @if(request()->anyFilled(['keyword', 'chuc_vu', 'trang_thai', 'sort']))
                    <a href="{{ route('nhan-vien.index') }}" style="height:42px; padding:0 14px; display:inline-flex; align-items:center; border:1px solid #e2e8f0; border-radius:10px; color:#64748b; font-size:13.5px; text-decoration:none; background:#fff;">
                        Bỏ lọc
                    </a>
                @endif

                {{-- Nút Thêm nhân viên --}}
                <button type="button" class="rs-btn-create" onclick="openCreateModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/>
                        <path d="M12 5v14"/>
                    </svg>
                    Thêm nhân viên
                </button>
            </div>

            {{-- Bộ chọn số lượng hiển thị (STT/trang) --}}
            <div class="rs-sub-toolbar">
                <div class="rs-per-page-box">
                    <span>Hiển thị</span>
                    <select name="per_page" class="rs-per-page-select" onchange="document.getElementById('nhanVienFilterForm').submit()">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <span>dòng/trang</span>
                </div>
            </div>

        </form>

        {{-- 4. BẢNG DỮ LIỆU CHUẨN HÓA VỚI THANH CUỘN NGANG --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <colgroup>
                    <col style="width: 60px;">
                    <col style="width: 210px;">
                    <col style="width: 135px;">
                    <col style="width: 210px;">
                    <col style="width: 135px;">
                    <col style="width: 190px;">
                    <col style="width: 145px;">
                    <col style="width: 145px;">
                    <col style="width: 140px;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">#</th>
                        <th>Tên nhân viên</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Chức vụ</th>
                        <th>Công việc</th>
                        <th style="text-align: right;">Mức lương</th>
                        <th style="text-align: center;">Trạng thái</th>
                        <th style="text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nhanViens as $nv)
                        @php
                            $stt = ($nhanViens->currentPage() - 1) * $nhanViens->perPage() + $loop->iteration;
                            $user = $nv->nguoiDung;
                            $isActive = $user ? ($user->trang_thai === 'HOAT_DONG') : true;
                            $isShipper = ($nv->chuc_vu === 'SHIPPER');
                        @endphp
                        <tr>
                            <td style="text-align: center; color: #94a3b8; font-weight: 500;">
                                {{ $stt }}
                            </td>
                            <td>
                                <span class="rs-text-bold">{{ $nv->ten_nhan_vien }}</span>
                            </td>
                            <td style="white-space: nowrap;">{{ $nv->so_dien_thoai ?: '—' }}</td>
                            <td>{{ $nv->email ?: '—' }}</td>
                            <td style="white-space: nowrap;">
                                <span class="rs-role-badge {{ $isShipper ? 'shipper' : '' }}">
                                    {{ $chucVus[$nv->chuc_vu] ?? $nv->chuc_vu }}
                                </span>
                            </td>
                            <td>{{ $nv->cong_viec ?: '—' }}</td>
                            <td style="text-align: right; white-space: nowrap;">
                                @if($nv->luong > 0)
                                    <span style="font-weight: 600; color: #0f172a;">{{ number_format($nv->luong, 0, ',', '.') }} đ</span>
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
                            <td style="text-align: center; white-space: nowrap;">
                                <div class="rs-action-group">
                                    {{-- Nút Sửa --}}
                                    <button type="button"
                                            class="rs-action-btn edit"
                                            title="Sửa thông tin"
                                            onclick="openEditModal({
                                                id: {{ $nv->ma_nhan_vien }},
                                                ten: '{{ addslashes($nv->ten_nhan_vien) }}',
                                                email: '{{ addslashes($nv->email ?? '') }}',
                                                sdt: '{{ addslashes($nv->so_dien_thoai ?? '') }}',
                                                chuc_vu: '{{ $nv->chuc_vu }}',
                                                cong_viec: '{{ addslashes($nv->cong_viec ?? '') }}',
                                                luong: {{ $nv->luong ?? 0 }}
                                            })">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="m15 5 4 4"/>
                                        </svg>
                                    </button>

                                    {{-- Nút Khóa / Mở hoạt động (Mở Modal hiển thị thông tin xác nhận theo đặc tả) --}}
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

                                    {{-- Nút Xóa (BẢO TOÀN HỒ SƠ: Chặn xóa nếu đã phát sinh nghiệp vụ) --}}
                                    @php $hasHistory = in_array($nv->ma_nhan_vien, $hasBusinessIds ?? []); @endphp
                                    @if($hasHistory)
                                        <button type="button"
                                                class="rs-action-btn delete"
                                                style="opacity: 0.45; cursor: not-allowed;"
                                                title="Bảo toàn hồ sơ: Nhân viên đã phát sinh nghiệp vụ (hóa đơn / phiếu nhập), không thể xóa vật lý."
                                                onclick="alert('Bảo toàn hồ sơ: Nhân viên \'{{ addslashes($nv->ten_nhan_vien) }}\' đã phát sinh nghiệp vụ trong hệ thống, không được phép xóa vật lý! Vui lòng chọn Ngừng hoạt động.')">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    @else
                                        <form action="{{ route('nhan-vien.destroy', $nv->ma_nhan_vien) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rs-action-btn delete"
                                                    title="Xóa nhân viên chưa có nghiệp vụ"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa nhân viên \'{{ $nv->ten_nhan_vien }}\'? Thao tác này sẽ xóa tài khoản hệ thống liên quan.')">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18"/>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 8px;">📋</div>
                                <div style="font-size: 14px; font-weight: 500;">Không tìm thấy nhân viên nào phù hợp với điều kiện tìm kiếm.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 5. FOOTER PHÂN TRANG --}}
        <div class="rs-table-footer">
            <div class="rs-footer-summary">
                Hiển thị <span class="rs-text-bold">{{ $nhanViens->firstItem() ?? 0 }}</span> - <span class="rs-text-bold">{{ $nhanViens->lastItem() ?? 0 }}</span> trong tổng số <span class="rs-text-bold">{{ $nhanViens->total() }}</span> bản ghi
            </div>

            <div class="rs-footer-pagination">
                <ul class="rs-pagination">
                    {{-- Nút trang trước (<) --}}
                    @if ($nhanViens->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $nhanViens->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                    @endif

                    {{-- Các nút số trang (1, 2, 3...) --}}
                    @for ($p = 1; $p <= $nhanViens->lastPage(); $p++)
                        @if ($p == $nhanViens->currentPage())
                            <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $nhanViens->url($p) }}">{{ $p }}</a></li>
                        @endif
                    @endfor

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


    // Đóng modal khi bấm ra ngoài nền mờ
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('rs-modal')) {
            e.target.classList.remove('show');
        }
    });
</script>
@endpush
