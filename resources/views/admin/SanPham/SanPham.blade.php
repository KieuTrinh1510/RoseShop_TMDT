@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .rs-management-page {
            padding: 24px 32px 32px 32px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .rs-header-block {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .rs-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .rs-header-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: #FDEAF3;
            color: #EC3D8F;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .rs-header-title {
            font-size: 20px;
            font-weight: 700;
            color: #101828;
            margin: 0 0 4px 0;
        }
        .rs-header-subtitle {
            font-size: 14px;
            color: #667085;
            margin: 0;
        }
        .rs-btn-create {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: #EC3D8F;
            color: #ffffff;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .rs-btn-create:hover {
            background-color: #D82677;
            color: #ffffff;
        }
        .rs-main-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #EAECF0;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
            overflow: hidden;
        }
        .rs-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #EAECF0;
            background: #ffffff;
            flex-wrap: wrap;
        }
        .rs-filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rs-filter-label {
            font-size: 13.5px;
            font-weight: 500;
            color: #344054;
            white-space: nowrap;
            margin: 0;
        }
        .rs-filter-select {
            height: 40px;
            padding: 0 36px 0 12px !important;
            min-width: 195px;
            border-radius: 8px;
            border: 1px solid #D0D5DD;
            font-size: 14px;
            color: #101828;
            background-color: #ffffff;
            outline: none;
            cursor: pointer;
        }
        .rs-filter-select:focus, .rs-filter-search-box input:focus {
            border-color: #EC3D8F;
            box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.15);
        }
        .rs-filter-search-box {
            position: relative;
            flex: 1;
            min-width: 260px;
            display: flex;
            align-items: center;
        }
        .rs-filter-search-box svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #667085;
            pointer-events: none;
            z-index: 2;
            width: 16px;
            height: 16px;
        }
        .rs-filter-search-box input {
            width: 100%;
            height: 40px;
            padding-left: 40px !important;
            padding-right: 14px;
            border-radius: 8px;
            border: 1px solid #D0D5DD;
            font-size: 14px;
            color: #101828;
            background-color: #ffffff;
            outline: none;
        }
        .rs-btn-filter-submit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 40px;
            padding: 0 18px;
            background-color: #EC3D8F;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        .rs-btn-filter-submit:hover {
            background-color: #D82677;
        }
        .rs-btn-filter-reset {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 40px;
            padding: 0 14px;
            background-color: #F2F4F7;
            color: #344054;
            border: 1px solid #D0D5DD;
            border-radius: 8px;
            font-size: 13.5px;
            text-decoration: none;
        }
        .rs-btn-filter-reset:hover {
            background-color: #E4E7EC;
            color: #101828;
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
        .rs-card-title svg {
            color: #475467;
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
        .rs-table-container {
            width: 100%;
            overflow-x: auto;
            position: relative;
        }
        .rs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
            table-layout: fixed;
        }
        .rs-table thead tr {
            background-color: #F8F9FA !important;
        }
        .rs-table th {
            background-color: #F8F9FA !important;
            padding: 14px 16px;
            font-size: 13.5px;
            font-weight: 600;
            color: #475467 !important;
            text-transform: none;
            border-bottom: 1px solid #EAECF0 !important;
        }
        .rs-table tbody tr {
            border-bottom: 1px solid #EAECF0;
            transition: background-color 0.15s ease;
        }
        .rs-table tbody tr:hover {
            background-color: #F9FAFB;
        }
        .rs-table td {
            padding: 14px 16px;
            font-size: 14px;
            color: #344054;
            vertical-align: middle;
            word-wrap: break-word;
            border-bottom: 1px solid #EAECF0;
        }
        .badge-confirmed {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 12px;
            background-color: #ECFDF3;
            color: #027A48;
            border: 1px solid #ABEFC6;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-cancelled {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 12px;
            background-color: #FEF3F2;
            color: #B42318;
            border: 1px solid #FECDCA;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-draft {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 12px;
            background-color: #FFFAEB;
            color: #B54708;
            border: 1px solid #FEDF89;
            font-size: 12px;
            font-weight: 500;
        }
        .rs-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .rs-action-btn {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #D0D5DD;
            background: #ffffff;
            color: #667085;
            cursor: pointer;
            transition: all 0.15s ease;
            padding: 0;
            text-decoration: none;
        }
        .rs-action-btn svg {
            width: 15px !important;
            height: 15px !important;
            stroke-width: 1.8px;
            stroke: currentColor;
            fill: none;
            display: block;
            flex-shrink: 0;
        }
        .rs-action-btn:hover {
            background-color: #F2F4F7 !important;
            color: #101828 !important;
            border-color: #98A2B3 !important;
        }
        .rs-table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: #ffffff;
            border-top: 1px solid #EAECF0;
            flex-wrap: wrap;
            gap: 12px;
        }
        .rs-footer-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .rs-per-page-inline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: #475467;
        }
        .rs-per-page-select {
            height: 36px;
            padding: 0 10px;
            border: 1px solid #D0D5DD;
            border-radius: 8px;
            font-size: 13.5px;
            color: #101828;
            background: #ffffff;
            outline: none;
            cursor: pointer;
        }
        .rs-records-count {
            font-size: 13.5px;
            color: #667085;
            white-space: nowrap;
        }
        .rs-table-footer .rs-pagination,
        .rs-table-footer .pagination {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .rs-table-footer .page-item .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 8px !important;
            border: 1px solid #D0D5DD;
            background: #ffffff;
            color: #344054;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .rs-table-footer .page-item.active .page-link,
        .rs-table-footer .page-item.active > .page-link,
        .rs-pagination .page-item.active .page-link,
        .rs-pagination .page-item.active > .page-link,
        .pagination .page-item.active .page-link,
        .pagination .page-item.active > .page-link,
        ul.pagination li.active span.page-link,
        ul.pagination li.active a.page-link,
        .page-item.active .page-link,
        .active > .page-link {
            background-color: #EC3D8F !important;
            background: #EC3D8F !important;
            border-color: #EC3D8F !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            box-shadow: 0 1px 3px rgba(236, 61, 143, 0.25) !important;
        }
        .rs-table-footer .page-item.disabled .page-link {
            color: #D0D5DD;
            background-color: #F9FAFB;
            border-color: #EAECF0;
            cursor: not-allowed;
        }
        .rs-table-footer .page-item .page-link:hover:not(.active):not(.disabled) {
            background-color: #F2F4F7;
            border-color: #D0D5DD;
            color: #101828;
        }
        .rs-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(16, 24, 40, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1050;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .rs-modal.show {
            display: flex;
        }
        .rs-modal-dialog {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 24px -4px rgba(16, 24, 40, 0.1), 0 8px 8px -4px rgba(16, 24, 40, 0.04);
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .rs-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #EAECF0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rs-modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #EC3D8F !important;
            margin: 0;
        }
        .rs-modal-close {
            background: transparent;
            border: none;
            color: #667085;
            font-size: 20px;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .rs-modal-close:hover {
            background-color: #F2F4F7;
            color: #101828;
        }
        .rs-modal-body {
            padding: 24px;
            overflow-y: auto;
        }
        .rs-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #EAECF0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background-color: #F8F9FA;
        }
        .rs-btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #EC3D8F !important;
            color: #ffffff !important;
            border-radius: 8px;
            border: none !important;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .rs-btn-submit:hover {
            background-color: #D82677 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
<div class="rs-management-page">

    {{-- 1. HEADER TRANG: Tiêu đề + Nút Thêm sản phẩm mới (Đồng bộ Người dùng, Nhân viên, Khách hàng) --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m7.5 4.27 9 5.15"/>
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                    <path d="m3.3 7 8.7 5 8.7-5"/>
                    <path d="M12 22V12"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Quản lý sản phẩm</h1>
                <p class="rs-header-subtitle">Theo dõi danh sách sản phẩm, thiết lập giá bán, kiểm soát tồn kho và trạng thái kinh doanh</p>
            </div>
        </div>
        <button type="button" class="rs-btn-create" onclick="openModalCreateSanPham()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
            </svg>
            <span>Thêm sản phẩm mới</span>
        </button>
    </div>

    {{-- THÔNG BÁO HỆ THỐNG --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Có lỗi xảy ra:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- 2. CARD CHÍNH: CHỨA BỘ LỌC TINH GỌN VÀ BẢNG DỮ LIỆU CÂN BẰNG CỘT CHUẨN ROSESHOP --}}
    <div class="rs-main-card">

        {{-- BỘ LỌC TINH GỌN FIT TRÊN 1 HÀNG HÀI HÒA --}}
        <form method="GET" action="{{ route('san-pham.index') }}" class="rs-filter-bar">
            
            <div class="rs-filter-item">
                <label class="rs-filter-label">Loại sản phẩm:</label>
                <select name="loai_san_pham" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả loại --</option>
                    @foreach($danhSachLoaiBanRa as $value => $label)
                        <option value="{{ $value }}" {{ request('loai_san_pham') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="rs-filter-search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tên sản phẩm, mã SP, mô tả..." autocomplete="off">
            </div>

            <button type="submit" class="rs-btn-filter-submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <span>Lọc</span>
            </button>

            @if(request()->anyFilled(['keyword', 'loai_san_pham']))
                <a href="{{ route('san-pham.index') }}" class="rs-btn-filter-reset" title="Bỏ lọc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    <span>Bỏ lọc</span>
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
                <span>Danh sách sản phẩm</span>
                <span class="rs-count-badge">{{ $danhSachSanPham->total() }}</span>
            </div>
        </div>

        {{-- BẢNG DANH SÁCH --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <colgroup>
                    <col style="width: 90px;">
                    <col style="width: 80px;">
                    <col style="width: 220px;">
                    <col style="width: 140px;">
                    <col style="width: 100px;">
                    <col style="width: 160px;">
                    <col style="width: 130px;">
                    <col style="width: 100px;">
                    <col style="width: 120px;">
                    <col style="width: 125px;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">Mã SP</th>
                        <th style="text-align: center;">Hình ảnh</th>
                        <th style="text-align: left;">Tên sản phẩm</th>
                        <th style="text-align: center;">Loại sản phẩm</th>
                        <th style="text-align: center;">Đơn vị</th>
                        <th style="text-align: right;">Giá nhập / niêm yết</th>
                        <th style="text-align: right;">Giá bán hiện tại</th>
                        <th style="text-align: center;">Tồn kho</th>
                        <th style="text-align: center;">Trạng thái</th>
                        <th style="text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($danhSachSanPham as $sp)
                        @php
                            $loCoHang = $sp->chiTietNhaps;
                            $tongConLai = $loCoHang->sum('so_luong_con_lai');
                        @endphp
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #EC3D8F; font-family: monospace; font-size: 13px;">
                                SP-{{ str_pad($sp->ma_san_pham, 4, '0', STR_PAD_LEFT) }}
                            </td>

                            <td style="text-align:center;">
                                @if($sp->hinh_anh)
                                    <img src="{{ asset($sp->hinh_anh) }}" alt="{{ $sp->ten_san_pham }}" style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0;">
                                @else
                                    <div style="width:48px; height:48px; background:#f1f5f9; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; color:#94a3b8;">
                                        <i class="bi bi-image fs-5"></i>
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-bold text-dark" style="font-size:13.5px;">{{ $sp->ten_san_pham }}</div>
                                @if($sp->mo_ta)
                                    <small class="text-muted d-block text-truncate" style="max-width: 200px; font-size:11.5px;">{{ $sp->mo_ta }}</small>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                <span class="badge bg-light text-dark border" style="font-size:12px; font-weight:600;">
                                    {{ $danhSachLoai[$sp->loai_san_pham] ?? $sp->loai_san_pham }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size:12px;">
                                    {{ $sp->don_vi_tinh_text ?: '—' }}
                                </span>
                            </td>

                            <td style="text-align:right;">
                                @if($loCoHang->isNotEmpty())
                                    <div class="d-flex flex-column align-items-end gap-1">
                                        @foreach($loCoHang as $ct)
                                            <div style="font-size:12px;">
                                                <span class="fw-semibold" style="color:#059669;">{{ number_format($ct->gia_ban, 0, ',', '.') }} ₫</span>
                                                <small class="text-muted">({{ $ct->so_luong_con_lai }} sp)</small>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted fst-italic" style="font-size:12px;">Chưa nhập</span>
                                @endif
                            </td>

                            <td style="text-align:right;" class="fw-bold text-success">
                                {{ $sp->gia_ban_hien_tai ? number_format($sp->gia_ban_hien_tai, 0, ',', '.') . ' ₫' : '—' }}
                            </td>

                            <td style="text-align:center;">
                                @if($tongConLai > 0)
                                    <span class="badge-confirmed">{{ $tongConLai }}</span>
                                @else
                                    <span class="badge-cancelled">0 (Hết)</span>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                @if($sp->trang_thai === 'DANG_BAN')
                                    <span class="badge-confirmed"><i class="bi bi-check-circle me-1"></i>Đang bán</span>
                                @else
                                    <span class="badge-cancelled"><i class="bi bi-slash-circle me-1"></i>Ngừng bán</span>
                                @endif
                            </td>

                            {{-- CỘT THAO TÁC ĐỒNG BỘ NÚT ICON CHUẨN --}}
                            <td style="text-align: center; white-space: nowrap;">
                                <div class="rs-action-group">
                                    {{-- 1. Nút Sửa sản phẩm --}}
                                    <button type="button"
                                            class="rs-action-btn edit"
                                            title="Sửa thông tin sản phẩm"
                                            onclick="chuyenCheDeSua({{ $sp->ma_san_pham }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        </svg>
                                    </button>

                                    {{-- 2. Nút Bật/Tắt trạng thái kinh doanh --}}
                                    <form action="{{ route('san-pham.toggle', $sp->ma_san_pham) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ $sp->trang_thai === 'DANG_BAN' ? 'Ẩn sản phẩm này? Khách hàng sẽ không thể mua.' : 'Hiện lại sản phẩm này để khách hàng mua được?' }}')">
                                        @csrf
                                        @method('PATCH')
                                        @if($sp->trang_thai === 'DANG_BAN')
                                            <button type="submit" class="rs-action-btn cancel" title="Ngừng kinh doanh sản phẩm này">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                                    <path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                                    <line x1="2" y1="2" x2="22" y2="22"/>
                                                </svg>
                                            </button>
                                        @else
                                            <button type="submit" class="rs-action-btn confirm" title="Mở bán lại sản phẩm này">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </form>

                                    {{-- 3. Nút Báo cáo hàng hỏng --}}
                                    <button type="button"
                                            class="rs-action-btn delete"
                                            onclick="moModalHangHong({{ $sp->ma_san_pham }}, '{{ addslashes($sp->ten_san_pham) }}', {{ $tongConLai }})"
                                            title="Báo cáo hàng hỏng / hao hụt kho"
                                            {{ $tongConLai == 0 ? 'disabled style=opacity:0.4;cursor:not-allowed;' : '' }}>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                            <line x1="12" y1="9" x2="12" y2="13"/>
                                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Không tìm thấy sản phẩm nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN --}}
        @if($danhSachSanPham->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 20, 50, 100] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage]) }}" {{ request('per_page', 10) == $perPage ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rs-records-count">
                        {{ $danhSachSanPham->firstItem() ?? 0 }}-{{ $danhSachSanPham->lastItem() ?? 0 }} / {{ $danhSachSanPham->total() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($danhSachSanPham->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $danhSachSanPham->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $danhSachSanPham->currentPage();
                            $lastPage = $danhSachSanPham->lastPage();
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
                                <li class="page-item active"><span class="page-link" style="background-color: #EC3D8F !important; background: #EC3D8F !important; border-color: #EC3D8F !important; color: #ffffff !important; font-weight: 600 !important; box-shadow: 0 1px 3px rgba(236, 61, 143, 0.25) !important;">{{ $p }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $danhSachSanPham->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($danhSachSanPham->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $danhSachSanPham->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

    </div>

</div>

{{-- MODAL POPUP THÊM / SỬA SẢN PHẨM (ĐÈ LÊN MÀN HÌNH BẢNG DANH SÁCH) --}}
<div class="rs-modal" id="modalSanPham" onclick="if(event.target === this) dongModalSanPham();">
    <div class="rs-modal-dialog" style="max-width: 820px;">
        <div class="rs-modal-header" id="cardFormHeader">
            <h3 class="rs-modal-title" id="tieuDeForm" style="color: #EC3D8F !important;">
                <i class="bi bi-plus-circle me-1"></i>Thêm Sản Phẩm Mới
            </h3>
            <button type="button" class="rs-modal-close" onclick="dongModalSanPham()">&times;</button>
        </div>
        <div class="rs-modal-body">
            <form id="formChung" action="{{ route('san-pham.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Tên sản phẩm <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="ten_san_pham" id="inp_ten" class="form-control"
                               value="{{ old('ten_san_pham') }}"
                               placeholder="Ví dụ: Hoa hồng đỏ Đà Lạt" required style="border-radius:8px; padding:8px 12px;">
                    </div>

                    <div class="col-md-6" id="khuVucLoai">
                        <label class="form-label fw-semibold">
                            Loại sản phẩm <span class="text-danger" id="loaiRequired">*</span>
                        </label>
                        <select name="loai_san_pham" id="inp_loai" class="form-select" required style="border-radius:8px; padding:8px 12px;">
                            <option value="">-- Chọn loại sản phẩm --</option>
                            @foreach($danhSachLoaiBanRa as $value => $label)
                                <option value="{{ $value }}" {{ old('loai_san_pham') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <div id="loaiHienThi" style="display:none;">
                            <input type="text" id="inp_loai_text" class="form-control bg-light" readonly style="border-radius:8px;">
                            <small class="text-muted"><i class="bi bi-lock-fill me-1"></i>Không thể thay đổi loại sản phẩm khi chỉnh sửa</small>
                        </div>
                    </div>

                    <div class="col-md-4" id="khuVucDonVi">
                        <label class="form-label fw-semibold">Đơn vị tính</label>
                        <input type="text" class="form-control" id="donViTinhHienThi" readonly
                               placeholder="Chọn loại sản phẩm..." style="border-radius:8px;">
                    </div>

                    <div class="col-md-4" id="khuVucSoLuong" style="display:none;">
                        <label class="form-label fw-semibold">Số lượng tồn kho</label>
                        <input type="text" id="inp_so_luong" class="form-control bg-light" readonly style="border-radius:8px;">
                        <small class="text-muted"><i class="bi bi-lock-fill me-1"></i>Chỉ thay đổi qua phiếu nhập hàng</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Giá bán hiện tại (₫)</label>
                        <input type="number" name="gia_ban_hien_tai" id="inp_gia_ban"
                               class="form-control" min="0" step="1000"
                               placeholder="Ví dụ: 25000" style="border-radius:8px; padding:8px 12px;">
                        <small class="text-muted" style="font-size:11px;">Giá niêm yết khách hàng nhìn thấy</small>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Mô tả sản phẩm</label>
                        <textarea name="mo_ta" id="inp_mo_ta" class="form-control" rows="3"
                                  placeholder="Nhập mô tả chi tiết sản phẩm..." style="border-radius:8px; padding:8px 12px;">{{ old('mo_ta') }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold" id="labelAnh">Hình ảnh sản phẩm</label>
                        <input type="file" name="hinh_anh" id="inp_anh" class="form-control"
                               accept="image/jpg,image/jpeg,image/png,image/gif,image/webp"
                               onchange="xemTruocAnh(this)" style="border-radius:8px;">
                        <small class="text-muted" style="font-size:11px;">
                            Chọn ảnh từ máy tính (JPG, PNG, GIF, WEBP — tối đa 2MB).
                        </small>
                        <div id="khuVucAnhHienTai" style="display:none; margin-top:6px;">
                            <small class="text-muted d-block mb-1">Ảnh hiện tại:</small>
                            <img id="anhHienTai" src="" alt="Ảnh hiện tại" style="max-height:80px; border-radius:8px; border:1px solid #e2e8f0;">
                        </div>
                        <img id="xemTruocAnh" class="preview-img mt-2" alt="Xem trước ảnh mới" style="display:none; max-height:100px; border-radius:8px; border:1px solid #e2e8f0;">
                    </div>
                </div>
            </form>
        </div>
        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="dongModalSanPham()" id="btnHuy">Hủy bỏ</button>
            <button type="submit" form="formChung" id="btnSubmit" class="rs-btn-submit">
                <i class="bi bi-plus-lg me-1" id="iconSubmit"></i>
                <span id="textSubmit">Thêm sản phẩm</span>
            </button>
        </div>
    </div>
</div>

{{-- MODAL POPUP BÁO CÁO HÀNG HỎNG (SẢN PHẨM ĐƠN LẺ) --}}
<div class="rs-modal" id="modalHangHong" onclick="if(event.target === this) dongModalHangHong();">
    <div class="rs-modal-dialog" style="max-width: 600px;">
        <div class="rs-modal-header" style="background:#FEF3F2 !important; border-bottom:1px solid #FECDCA !important;">
            <h3 class="rs-modal-title" style="color:#B42318 !important;">
                <i class="bi bi-trash3 me-1"></i>Báo Cáo Hàng Hỏng / Hao Hụt Kho
            </h3>
            <button type="button" class="rs-modal-close" style="color:#B42318 !important; border-color:#FECDCA !important;" onclick="dongModalHangHong()">&times;</button>
        </div>
        <div class="rs-modal-body">
            <form action="{{ route('san-pham.bao-hang-hong') }}" method="POST" id="formHangHong">
                @csrf
                <input type="hidden" name="ma_san_pham" id="modal_ma_sp">

                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">Sản phẩm:</span>
                        <strong id="modal_ten_sp" class="text-dark">—</strong>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:13px;">
                        <span class="text-muted">Tồn kho lô đã chọn:</span>
                        <span class="badge bg-secondary" id="modal_so_luong_badge">0</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Chọn lô nhập <span class="text-danger">*</span>
                    </label>
                    <select name="ma_chi_tiet_nhap" id="modal_lo_nhap" class="form-select" required style="border-radius:8px; padding:8px 12px;">
                        <option value="">-- Đang tải lô... --</option>
                    </select>
                    <small class="text-muted" style="font-size:11px;">
                        * Chọn đúng lô bị hỏng để trừ tồn kho thực tế của lô đó.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Số lượng hỏng <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="so_luong_hong" id="modal_so_luong_hong"
                           class="form-control" min="1" required style="border-radius:8px; padding:8px 12px;"
                           placeholder="Nhập số lượng hỏng...">
                    <div class="form-text text-danger" id="modal_sl_error" style="display:none; font-size:11px;">
                        * Số lượng hỏng không được vượt quá tồn kho còn lại của lô này!
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Lý do hỏng <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="ly_do" id="modal_ly_do" class="form-control"
                           maxlength="255" required style="border-radius:8px; padding:8px 12px;"
                           placeholder="VD: Hoa héo dập, mốc hỏng, bể vỡ...">
                    <div class="form-text text-danger" id="modal_ly_do_error" style="display:none; font-size:11px;">
                        * Vui lòng nhập lý do hỏng.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">Ghi chú thêm</label>
                    <textarea name="ghi_chu" class="form-control" rows="2" maxlength="1000" style="border-radius:8px; padding:8px 12px;"
                              placeholder="Ghi chú chi tiết nếu cần..."></textarea>
                </div>

                <div class="p-2 bg-light rounded-3 d-flex justify-content-between text-muted" style="font-size:12px;">
                    <span><i class="bi bi-person me-1"></i> Báo cáo bởi: <strong>{{ Auth::user()?->nhanVien?->ten_nhan_vien ?? Auth::user()?->name ?? 'Nhân viên' }}</strong></span>
                    <span><i class="bi bi-clock me-1"></i> Tự động ghi nhận</span>
                </div>
            </form>
        </div>
        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="dongModalHangHong()">Hủy bỏ</button>
            <button type="submit" form="formHangHong" class="rs-btn-submit" id="btnGuiBaoCao" disabled style="background:#B42318 !important; border-color:#B42318 !important;">
                <i class="bi bi-trash3 me-1"></i> Xác nhận báo cáo hỏng
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const danhSachLoai = @json($danhSachLoai);

    const DON_VI_THEO_LOAI = {
        HOA_TUOI: 'Cành', HOA_GIA: 'Cành', HOA_SAP: 'Cành', HOA_GIAY_NHUN: 'Cành',
        CHAU_HOA_GIA: 'Chậu', CHAU_HOA_TUOI: 'Chậu', CAY_CANH: 'Chậu',
        SAN_PHAM_PREMIUM: 'Cái', TERRARIUM: 'Cái', QUA_TANG: 'Cái', PHU_KIEN: 'Cái',
        GIAY_GOI: 'Tờ', RUY_BANG: 'Mét',
        PHU_KIEN_TRANG_TRI: 'Cái', THIEP: 'Cái', MIENG_XOP: 'Miếng',
    };

    function openModalCreateSanPham() {
        chuyenVeCheDoDaThem();
        document.getElementById('modalSanPham').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function dongModalSanPham() {
        document.getElementById('modalSanPham').classList.remove('show');
        document.body.style.overflow = '';
    }

    function dongModalHangHong() {
        document.getElementById('modalHangHong').classList.remove('show');
        document.body.style.overflow = '';
    }

    function xemTruocAnh(input) {
        const imgEl = document.getElementById('xemTruocAnh');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imgEl.src = e.target.result;
                imgEl.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function chuyenCheDeSua(id) {
        fetch('/san-pham/' + id + '/edit-data')
            .then(function(res) { return res.json(); })
            .then(function(sp) {
                document.getElementById('inp_ten').value   = sp.ten_san_pham;
                document.getElementById('inp_mo_ta').value = sp.mo_ta ?? '';
                document.getElementById('inp_gia_ban').value = sp.gia_ban_hien_tai ?? '';

                document.getElementById('donViTinhHienThi').value =
                    DON_VI_THEO_LOAI[sp.loai_san_pham] ?? sp.don_vi_tinh ?? '';

                document.getElementById('khuVucSoLuong').style.display = 'block';
                document.getElementById('inp_so_luong').value = sp.ton_kho_thuc_te ?? sp.so_luong;

                document.getElementById('inp_loai').style.display = 'none';
                document.getElementById('inp_loai').removeAttribute('required');
                document.getElementById('loaiHienThi').style.display = 'block';
                document.getElementById('inp_loai_text').value = danhSachLoai[sp.loai_san_pham] ?? sp.loai_san_pham;
                document.getElementById('loaiRequired').style.display = 'none';

                if (sp.hinh_anh) {
                    document.getElementById('anhHienTai').src = '/' + sp.hinh_anh;
                    document.getElementById('khuVucAnhHienTai').style.display = 'block';
                } else {
                    document.getElementById('khuVucAnhHienTai').style.display = 'none';
                }

                document.getElementById('xemTruocAnh').style.display = 'none';
                document.getElementById('inp_anh').value = '';

                document.getElementById('formChung').action = '/san-pham/' + id;
                document.getElementById('methodField').innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';

                document.getElementById('tieuDeForm').innerHTML =
                    '<i class="bi bi-pencil-fill me-2"></i>Sửa Sản Phẩm: <strong>' + sp.ten_san_pham + '</strong>';

                document.getElementById('btnSubmit').className = 'rs-btn-submit';
                document.getElementById('iconSubmit').className = 'bi bi-check-lg me-1';
                document.getElementById('textSubmit').textContent = 'Lưu thay đổi';
                document.getElementById('labelAnh').textContent = 'Hình ảnh mới (để trống nếu không đổi)';
                
                document.getElementById('modalSanPham').classList.add('show');
                document.body.style.overflow = 'hidden';
            })
            .catch(function() {
                alert('Không thể tải dữ liệu sản phẩm! Vui lòng thử lại.');
            });
    }

    function chuyenVeCheDoDaThem() {
        document.getElementById('formChung').reset();
        document.getElementById('inp_gia_ban').value = '';
        document.getElementById('formChung').action = '{{ route('san-pham.store') }}';
        document.getElementById('methodField').innerHTML = '';

        document.getElementById('inp_loai').style.display = 'block';
        document.getElementById('inp_loai').setAttribute('required', 'required');
        document.getElementById('loaiHienThi').style.display = 'none';
        document.getElementById('loaiRequired').style.display = 'inline';
        document.getElementById('khuVucSoLuong').style.display = 'none';
        document.getElementById('khuVucAnhHienTai').style.display = 'none';
        document.getElementById('xemTruocAnh').style.display = 'none';
        document.getElementById('donViTinhHienThi').value = '';

        document.getElementById('tieuDeForm').innerHTML =
            '<i class="bi bi-plus-circle me-2"></i>Thêm Sản Phẩm Mới';
        document.getElementById('btnSubmit').className = 'rs-btn-submit';
        document.getElementById('iconSubmit').className = 'bi bi-plus-lg me-1';
        document.getElementById('textSubmit').textContent = 'Thêm sản phẩm';
        document.getElementById('labelAnh').textContent = 'Hình ảnh';
    }

    // ── Modal báo cáo hàng hỏng (sản phẩm đơn lẻ) ──────────────
    let _soLuongKho = 0;

    function moModalHangHong(maSP, tenSP, soLuong) {
        _soLuongKho = soLuong;

        document.getElementById('modal_ma_sp').value         = maSP;
        document.getElementById('modal_ten_sp').textContent  = tenSP;
        document.getElementById('modal_so_luong_badge').textContent = soLuong;
        document.getElementById('modal_so_luong_hong').value = '';
        document.getElementById('modal_so_luong_hong').max   = soLuong;
        document.getElementById('modal_sl_error').style.display = 'none';
        document.getElementById('modal_ly_do').value = '';
        document.getElementById('modal_ly_do_error').style.display = 'none';
        document.getElementById('btnGuiBaoCao').disabled = true;

        const selectLo = document.getElementById('modal_lo_nhap');
        selectLo.innerHTML = '<option value="">-- Đang tải lô nhập... --</option>';

        fetch('/san-pham/' + maSP + '/lo-nhap')
            .then(res => res.json())
            .then(data => {
                selectLo.innerHTML = '<option value="">-- Chọn lô nhập --</option>';

                if (data.lo_nhaps.length === 0) {
                    selectLo.innerHTML = '<option value="">Không có lô nào còn hàng</option>';
                    return;
                }

                data.lo_nhaps.forEach(lo => {
                    const opt = document.createElement('option');
                    opt.value = lo.ma_chi_tiet_nhap;
                    opt.textContent = lo.label;
                    opt.dataset.soLuongConLai = lo.so_luong_con_lai;
                    selectLo.appendChild(opt);
                });
            })
            .catch(() => {
                selectLo.innerHTML = '<option value="">Lỗi tải lô, vui lòng thử lại</option>';
            });

        selectLo.onchange = function () {
            const opt = this.options[this.selectedIndex];
            const slConLai = parseInt(opt.dataset.soLuongConLai) || 0;
            _soLuongKho = slConLai;
            document.getElementById('modal_so_luong_hong').max = slConLai;
            document.getElementById('modal_so_luong_badge').textContent = slConLai;
            document.getElementById('modal_so_luong_hong').value = '';
            document.getElementById('modal_sl_error').style.display = 'none';
            validateSanPhamHH();
        };

        document.getElementById('modalHangHong').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function validateSanPhamHH() {
        const val    = parseInt(document.getElementById('modal_so_luong_hong').value) || 0;
        const lyDo   = document.getElementById('modal_ly_do').value.trim();
        const errEl  = document.getElementById('modal_sl_error');
        const lyErr  = document.getElementById('modal_ly_do_error');
        const btnGui = document.getElementById('btnGuiBaoCao');
        const loVal  = document.getElementById('modal_lo_nhap').value;

        let ok = true;

        if (!loVal) {
            ok = false;
        }
        if (val < 1 || val > _soLuongKho) {
            errEl.style.display = 'block';
            ok = false;
        } else {
            errEl.style.display = 'none';
        }
        if (!lyDo) {
            lyErr.style.display = 'block';
            ok = false;
        } else {
            lyErr.style.display = 'none';
        }

        btnGui.disabled = !ok;
    }

    document.getElementById('modal_so_luong_hong').addEventListener('input', validateSanPhamHH);
    document.getElementById('modal_ly_do').addEventListener('input', validateSanPhamHH);

    document.getElementById('inp_loai')?.addEventListener('change', function () {
        const target = document.getElementById('donViTinhHienThi');
        if (target) target.value = DON_VI_THEO_LOAI[this.value] ?? '';
    });
</script>
@endpush
@endsection