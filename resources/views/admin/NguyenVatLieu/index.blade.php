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
            flex-shrink: 0;
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
            flex-shrink: 0;
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
            position: sticky;
            top: 0;
            z-index: 10;
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

@php
    $dsNguyenVatLieu = $nguyenVatLieu;
    $dsNguyenVatLieu->each(function ($vl) {
        $vl->ton_kho_hien_thi = $vl->ton_kho_thuc_te
            ?? ($vl->relationLoaded('chiTietNhaps') ? $vl->chiTietNhaps->sum('so_luong_con_lai') : $vl->so_luong);
        $vl->tong_gia_von = $vl->relationLoaded('chiTietNhaps')
            ? $vl->chiTietNhaps->sum(fn ($ct) => ($ct->gia_nhap ?? 0) * $ct->so_luong_con_lai)
            : 0;
    });
    $soNguyenLieuSapHet  = $dsNguyenVatLieu->where('ton_kho_hien_thi', '>', 0)->where('ton_kho_hien_thi', '<', 5)->count();
    $soNguyenLieuHetHang = $dsNguyenVatLieu->where('ton_kho_hien_thi', '<=', 0)->count();
@endphp

<div class="rs-management-page">

    {{-- 1. HEADER TRANG: Tiêu đề + Nút Thêm mới (Đồng bộ Người dùng, Nhân viên, Khách hàng) --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Quản lý nguyên vật liệu</h1>
                <p class="rs-header-subtitle">Kiểm soát tồn kho nguyên vật liệu (giấy gói, ruy băng, phụ kiện, thiệp, mút xốp) phục vụ bó hoa</p>
            </div>
        </div>
        <button type="button" class="rs-btn-create" onclick="openModalCreateNVL()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
            </svg>
            <span>Thêm nguyên vật liệu</span>
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

    @if($soNguyenLieuHetHang > 0 || $soNguyenLieuSapHet > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius:12px; font-size:13.5px;">
            <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
            @if($soNguyenLieuHetHang > 0)
                <strong>{{ $soNguyenLieuHetHang }}</strong> nguyên vật liệu đã <strong>HẾT HÀNG</strong>.
            @endif
            @if($soNguyenLieuSapHet > 0)
                <strong>{{ $soNguyenLieuSapHet }}</strong> nguyên vật liệu <strong>SẮP HẾT HÀNG</strong> (dưới 5 đơn vị).
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- 2. CARD CHÍNH: CHỨA BỘ LỌC TINH GỌN VÀ BẢNG DỮ LIỆU CÂN BẰNG CỘT CHUẨN ROSESHOP --}}
    <div class="rs-main-card">

        {{-- BỘ LỌC TINH GỌN FIT TRÊN 1 HÀNG HÀI HÒA --}}
        <form method="GET" action="{{ route('admin.nguyen-vat-lieu') }}" class="rs-filter-bar">
            
            <div class="rs-filter-item">
                <label class="rs-filter-label">Loại nguyên vật liệu:</label>
                <select name="loai_san_pham" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả loại NVL --</option>
                    @foreach($danhSachLoaiNguyenLieu as $value => $label)
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
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tên nguyên vật liệu, mô tả..." autocomplete="off">
            </div>

            <button type="submit" class="rs-btn-filter-submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <span>Lọc</span>
            </button>

            @if(request()->anyFilled(['keyword', 'loai_san_pham']))
                <a href="{{ route('admin.nguyen-vat-lieu') }}" class="rs-btn-filter-reset" title="Bỏ lọc">
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
                <span>Danh sách nguyên vật liệu</span>
                <span class="rs-count-badge">{{ $nguyenVatLieu->total() }}</span>
            </div>
        </div>

        {{-- BẢNG DANH SÁCH --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <colgroup>
                    <col style="width: 90px;">
                    <col style="width: 80px;">
                    <col style="width: 200px;">
                    <col style="width: 140px;">
                    <col style="width: 80px;">
                    <col style="width: 150px;">
                    <col style="width: 120px;">
                    <col style="width: 90px;">
                    <col style="width: 130px;">
                    <col style="width: 110px;">
                    <col style="width: 110px;">
                    <col style="width: 140px;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">Mã NVL</th>
                        <th style="text-align: center;">Hình ảnh</th>
                        <th style="text-align: left;">Tên nguyên vật liệu</th>
                        <th style="text-align: center;">Loại NVL</th>
                        <th style="text-align: center;">ĐVT</th>
                        <th style="text-align: right;">Giá nhập lô</th>
                        <th style="text-align: right;">Giá hiện tại</th>
                        <th style="text-align: center;">Tồn kho</th>
                        <th style="text-align: right;">Tổng giá vốn</th>
                        <th style="text-align: center;">Trạng thái kho</th>
                        <th style="text-align: center;">Kinh doanh</th>
                        <th style="text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dsNguyenVatLieu as $vl)
                        @php $loCoHang = $vl->chiTietNhaps; @endphp
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #EC3D8F; font-family: monospace; font-size: 13px;">
                                NVL-{{ str_pad($vl->ma_san_pham, 4, '0', STR_PAD_LEFT) }}
                            </td>

                            <td style="text-align:center;">
                                @if($vl->hinh_anh)
                                    <img src="{{ asset($vl->hinh_anh) }}" alt="{{ $vl->ten_san_pham }}" style="width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0;">
                                @else
                                    <div style="width:42px; height:42px; background:#f1f5f9; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; color:#94a3b8;">
                                        <i class="bi bi-boxes fs-5"></i>
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-bold text-dark" style="font-size:13.5px;">{{ $vl->ten_san_pham }}</div>
                                @if($vl->mo_ta)
                                    <small class="text-muted d-block text-truncate" style="max-width: 180px; font-size:11.5px;">{{ $vl->mo_ta }}</small>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                <span class="badge bg-light text-dark border" style="font-size:12px; font-weight:600;">
                                    {{ $danhSachLoaiNguyenLieu[$vl->loai_san_pham] ?? $vl->loai_san_pham }}
                                </span>
                            </td>

                            <td style="text-align:center;">
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size:12px;">
                                    {{ $vl->don_vi_tinh_text ?: '—' }}
                                </span>
                            </td>

                            <td style="text-align:right;">
                                @if($loCoHang->isNotEmpty())
                                    <div class="d-flex flex-column align-items-end gap-1">
                                        @foreach($loCoHang as $ct)
                                            <div class="d-flex align-items-center justify-content-end gap-1" style="font-size:12px;">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold" style="font-size:11.5px; padding:3px 6px;">
                                                    {{ number_format($ct->gia_ban, 0, ',', '.') }} đ
                                                </span>
                                                <span class="badge bg-dark text-white" style="font-size:11px; padding:3px 6px; border-radius:6px;">
                                                    <i class="bi bi-box-seam me-1"></i>{{ $ct->so_luong_con_lai }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted fst-italic" style="font-size:12px;">Chưa nhập</span>
                                @endif
                            </td>

                            <td style="text-align:right;" class="fw-bold text-success">
                                {{ $vl->gia_ban_hien_tai ? number_format($vl->gia_ban_hien_tai, 0, ',', '.') . ' ₫' : '—' }}
                            </td>

                            <td style="text-align:center;">
                                @if($vl->ton_kho_hien_thi > 0)
                                    <span class="badge-confirmed">{{ $vl->ton_kho_hien_thi }}</span>
                                @else
                                    <span class="badge-cancelled">0 (Hết)</span>
                                @endif
                            </td>

                            <td style="text-align:right;" class="fw-semibold text-primary">
                                {{ number_format($vl->tong_gia_von, 0, ',', '.') }} ₫
                            </td>

                            <td style="text-align:center;">
                                @if($vl->ton_kho_hien_thi <= 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size:11.5px;">Hết hàng</span>
                                @elseif($vl->ton_kho_hien_thi < 5)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size:11.5px;">Sắp hết</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:11.5px;">Đủ hàng</span>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                @if($vl->trang_thai === 'DANG_BAN')
                                    <span class="badge-confirmed"><i class="bi bi-check-circle me-1"></i>Đang bán</span>
                                @else
                                    <span class="badge-cancelled"><i class="bi bi-slash-circle me-1"></i>Ngừng bán</span>
                                @endif
                            </td>

                            {{-- CỘT THAO TÁC ĐỒNG BỘ NÚT ICON CHUẨN --}}
                            <td style="text-align: center; white-space: nowrap;">
                                <div class="rs-action-group">
                                    {{-- 1. Nút Sửa NVL --}}
                                    <button type="button"
                                            class="rs-action-btn"
                                            title="Sửa thông tin nguyên vật liệu"
                                            onclick="vlChuyenCheDoSua({{ $vl->ma_san_pham }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        </svg>
                                    </button>

                                    {{-- 2. Nút Bật/Tắt sử dụng --}}
                                    <form action="{{ route('san-pham.toggle', $vl->ma_san_pham) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ $vl->trang_thai === 'DANG_BAN' ? 'Ẩn nguyên vật liệu này? Nó sẽ không được chọn khi tạo bó hoa.' : 'Hiện lại nguyên vật liệu này để chọn khi tạo bó hoa?' }}')">
                                        @csrf
                                        @method('PATCH')
                                        @if($vl->trang_thai === 'DANG_BAN')
                                            <button type="submit" class="rs-action-btn" title="Ẩn khỏi danh sách nguyên liệu tạo bó hoa">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                                    <path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                                    <line x1="2" y1="2" x2="22" y2="22"/>
                                                </svg>
                                            </button>
                                        @else
                                            <button type="submit" class="rs-action-btn" title="Cho phép chọn khi tạo bó hoa">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </form>

                                    {{-- 3. Nút Nhập hàng thêm --}}
                                    <a href="{{ route('phieu-nhap.index') }}" class="rs-action-btn" title="Tạo phiếu nhập mới cho NVL này">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                    </a>

                                    {{-- 4. Nút Báo hỏng / Rách --}}
                                    <button type="button"
                                            class="rs-action-btn"
                                            onclick="moModalHangHong({{ $vl->ma_san_pham }}, '{{ addslashes($vl->ten_san_pham) }}', {{ $vl->ton_kho_hien_thi }})"
                                            title="Báo cáo hàng hỏng / rách / hao hụt"
                                            {{ $vl->ton_kho_hien_thi <= 0 ? 'disabled style=opacity:0.4;cursor:not-allowed;' : '' }}>
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
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Chưa có nguyên vật liệu nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN ROSESHOP --}}
        @if($nguyenVatLieu->total() > 0)
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
                        {{ $nguyenVatLieu->firstItem() ?? 0 }}-{{ $nguyenVatLieu->lastItem() ?? 0 }} / {{ $nguyenVatLieu->total() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($nguyenVatLieu->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $nguyenVatLieu->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $nguyenVatLieu->currentPage();
                            $lastPage = $nguyenVatLieu->lastPage();
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
                                <li class="page-item"><a class="page-link" href="{{ $nguyenVatLieu->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($nguyenVatLieu->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $nguyenVatLieu->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

    </div>

</div>

{{-- MODAL POPUP THÊM / SỬA NGUYÊN VẬT LIỆU (ĐÈ LÊN MÀN HÌNH BẢNG DANH SÁCH) --}}
<div class="rs-modal" id="modalNguyenVatLieu" onclick="if(event.target === this) dongModalNVL();">
    <div class="rs-modal-dialog" style="max-width: 820px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title" id="vl_tieuDeForm" style="color: #EC3D8F !important;">
                <i class="bi bi-boxes me-1"></i>Thêm Nguyên Vật Liệu Mới
            </h3>
            <button type="button" class="rs-modal-close" onclick="dongModalNVL()">&times;</button>
        </div>
        <div class="rs-modal-body">
            <form id="formNguyenLieu" action="{{ route('san-pham.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="vl_methodField"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tên nguyên vật liệu <span class="text-danger">*</span></label>
                        <input type="text" name="ten_san_pham" id="vl_inp_ten" class="form-control"
                               placeholder="Ví dụ: Giấy gói Hàn Quốc hoa văn" required style="border-radius:8px; padding:8px 12px;">
                    </div>

                    <div class="col-md-6" id="vl_khuVucLoai">
                        <label class="form-label fw-semibold">Loại nguyên vật liệu <span class="text-danger" id="vl_loaiRequired">*</span></label>
                        <select name="loai_san_pham" id="vl_inp_loai" class="form-select" required style="border-radius:8px; padding:8px 12px;">
                            <option value="">-- Chọn loại --</option>
                            @foreach($danhSachLoaiNguyenLieu as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div id="vl_loaiHienThi" style="display:none;">
                            <input type="text" id="vl_inp_loai_text" class="form-control bg-light" readonly style="border-radius:8px;">
                            <small class="text-muted"><i class="bi bi-lock-fill me-1"></i>Không thể thay đổi loại nguyên vật liệu</small>
                        </div>
                    </div>

                    <div class="col-md-4" id="vl_khuVucDonVi">
                        <label class="form-label fw-semibold">Đơn vị tính</label>
                        <input type="text" class="form-control" id="vl_donViTinhHienThi" readonly
                               placeholder="Chọn loại NVL..." style="border-radius:8px;">
                    </div>

                    <div class="col-md-4" id="vl_khuVucSoLuong" style="display:none;">
                        <label class="form-label fw-semibold">Tồn kho</label>
                        <input type="text" id="vl_inp_so_luong" class="form-control bg-light" readonly style="border-radius:8px;">
                        <small class="text-muted"><i class="bi bi-lock-fill me-1"></i>Chỉ thay đổi qua nhập hàng</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Giá bán hiện tại (₫)</label>
                        <input type="number" name="gia_ban_hien_tai" id="vl_inp_gia_ban"
                               class="form-control" min="0" step="1000"
                               placeholder="Ví dụ: 5000" style="border-radius:8px; padding:8px 12px;">
                        <small class="text-muted" style="font-size:11px;">Giá tham khảo cấu thành bó hoa</small>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Mô tả nguyên vật liệu</label>
                        <textarea name="mo_ta" id="vl_inp_mo_ta" class="form-control" rows="3"
                                  placeholder="Ghi chú thêm về NVL (màu sắc, kích thước, quy cách...)" style="border-radius:8px; padding:8px 12px;"></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Hình ảnh minh họa</label>
                        <input type="file" name="hinh_anh" id="vl_inp_anh" class="form-control"
                               accept="image/jpg,image/jpeg,image/png,image/gif,image/webp" style="border-radius:8px;">
                        <small class="text-muted" style="font-size:11px;">Tối đa 2MB (JPG, PNG, GIF, WEBP).</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="dongModalNVL()" id="vl_btnHuy">Hủy bỏ</button>
            <button type="submit" form="formNguyenLieu" id="vl_btnSubmit" class="rs-btn-submit">
                <i class="bi bi-plus-lg me-1" id="vl_iconSubmit"></i>
                <span id="vl_textSubmit">Thêm nguyên vật liệu</span>
            </button>
        </div>
    </div>
</div>

{{-- MODAL POPUP BÁO CÁO HÀNG HỎNG (NVL) --}}
<div class="rs-modal" id="modalHangHong" onclick="if(event.target === this) dongModalHangHong();">
    <div class="rs-modal-dialog" style="max-width: 600px;">
        <div class="rs-modal-header" style="background:#FEF3F2 !important; border-bottom:1px solid #FECDCA !important;">
            <h3 class="rs-modal-title" style="color:#B42318 !important;">
                <i class="bi bi-trash3 me-1"></i>Báo Cáo Nguyên Vật Liệu Hỏng / Rách
            </h3>
            <button type="button" class="rs-modal-close" style="color:#B42318 !important; border-color:#FECDCA !important;" onclick="dongModalHangHong()">&times;</button>
        </div>
        <div class="rs-modal-body">
            <form action="{{ route('san-pham.bao-hang-hong') }}" method="POST" id="formHangHong">
                @csrf
                <input type="hidden" name="ma_san_pham" id="modal_ma_sp">

                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">Nguyên vật liệu:</span>
                        <strong id="modal_ten_sp" class="text-dark">—</strong>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:13px;">
                        <span class="text-muted">Tồn kho lô đang chọn:</span>
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
                        * Số lượng hỏng không được vượt tồn kho còn lại của lô này!
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Lý do hỏng <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="ly_do" id="modal_ly_do" class="form-control"
                           maxlength="255" required style="border-radius:8px; padding:8px 12px;"
                           placeholder="VD: Rách, ẩm mốc, hết hạn...">
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
    const danhSachLoaiVL = @json($danhSachLoaiNguyenLieu);

    const DON_VI_THEO_LOAI = {
        GIAY_GOI: 'Tờ', RUY_BANG: 'Mét',
        PHU_KIEN_TRANG_TRI: 'Cái', THIEP: 'Cái', MIENG_XOP: 'Miếng',
    };

    function openModalCreateNVL() {
        vlChuyenVeCheDoDaThem();
        document.getElementById('modalNguyenVatLieu').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function dongModalNVL() {
        document.getElementById('modalNguyenVatLieu').classList.remove('show');
        document.body.style.overflow = '';
    }

    function dongModalHangHong() {
        document.getElementById('modalHangHong').classList.remove('show');
        document.body.style.overflow = '';
    }

    function vlChuyenCheDoSua(id) {
        fetch('/san-pham/' + id + '/edit-data')
            .then(res => res.json())
            .then(sp => {
                document.getElementById('vl_inp_ten').value   = sp.ten_san_pham;
                document.getElementById('vl_inp_mo_ta').value = sp.mo_ta ?? '';
                document.getElementById('vl_inp_gia_ban').value = sp.gia_ban_hien_tai ?? '';

                document.getElementById('vl_donViTinhHienThi').value =
                    DON_VI_THEO_LOAI[sp.loai_san_pham] ?? sp.don_vi_tinh ?? '';

                document.getElementById('vl_khuVucSoLuong').style.display = 'block';
                document.getElementById('vl_inp_so_luong').value = sp.ton_kho_thuc_te ?? sp.so_luong;

                document.getElementById('vl_inp_loai').style.display = 'none';
                document.getElementById('vl_inp_loai').removeAttribute('required');
                document.getElementById('vl_loaiHienThi').style.display = 'block';
                document.getElementById('vl_inp_loai_text').value =
                    danhSachLoaiVL[sp.loai_san_pham] ?? sp.loai_san_pham;
                document.getElementById('vl_loaiRequired').style.display = 'none';

                document.getElementById('vl_inp_anh').value = '';

                document.getElementById('formNguyenLieu').action = '/san-pham/' + id;
                document.getElementById('vl_methodField').innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';

                document.getElementById('vl_tieuDeForm').innerHTML =
                    '<i class="bi bi-pencil-fill me-2"></i>Sửa Nguyên Vật Liệu: <strong>' + sp.ten_san_pham + '</strong>';

                document.getElementById('vl_btnSubmit').className = 'rs-btn-submit';
                document.getElementById('vl_iconSubmit').className = 'bi bi-check-lg me-1';
                document.getElementById('vl_textSubmit').textContent = 'Lưu thay đổi';

                document.getElementById('modalNguyenVatLieu').classList.add('show');
                document.body.style.overflow = 'hidden';
            })
            .catch(() => alert('Không thể tải dữ liệu nguyên vật liệu! Vui lòng thử lại.'));
    }

    function vlChuyenVeCheDoDaThem() {
        const form = document.getElementById('formNguyenLieu');
        form.reset();
        form.action = '{{ route('san-pham.store') }}';
        document.getElementById('vl_methodField').innerHTML = '';

        document.getElementById('vl_inp_loai').style.display = 'block';
        document.getElementById('vl_inp_loai').setAttribute('required', 'required');
        document.getElementById('vl_loaiHienThi').style.display = 'none';
        document.getElementById('vl_loaiRequired').style.display = 'inline';
        document.getElementById('vl_khuVucSoLuong').style.display = 'none';
        document.getElementById('vl_donViTinhHienThi').value = '';
        document.getElementById('vl_inp_gia_ban').value = '';

        document.getElementById('vl_tieuDeForm').innerHTML =
            '<i class="bi bi-boxes me-2"></i>Thêm Nguyên Vật Liệu Mới';
        document.getElementById('vl_iconSubmit').className = 'bi bi-plus-lg me-1';
        document.getElementById('vl_textSubmit').textContent = 'Thêm nguyên vật liệu';
    }

    document.getElementById('vl_inp_loai')?.addEventListener('change', function () {
        const target = document.getElementById('vl_donViTinhHienThi');
        if (target) target.value = DON_VI_THEO_LOAI[this.value] ?? '';
    });

    // ── Modal báo cáo hàng hỏng (dùng chung với SP) ──────────
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

        if (!loVal) ok = false;
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
</script>
@endpush
@endsection