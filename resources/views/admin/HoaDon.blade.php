@extends('layouts.admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/Admin/HoaDon.css') }}">
<style>
    .rs-management-page {
        padding: 24px 32px 24px 32px !important;
        background-color: #f8f9fa;
        min-height: 100vh;
        font-family: inherit;
        color: #101828;
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
    .rs-main-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #EAECF0;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
        overflow: hidden;
    }
    .rs-filter-bar {
        padding: 16px 20px;
        border-bottom: 1px solid #EAECF0;
        background: #ffffff;
    }
    .rs-filter-row {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }
    .rs-filter-group {
        flex: 1;
        min-width: 140px;
    }
    .rs-filter-group label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #475467;
        margin-bottom: 6px;
        white-space: nowrap;
    }
    .rs-filter-group select, .rs-filter-group input {
        width: 100%;
        border: 1px solid #D0D5DD;
        border-radius: 8px;
        height: 38px;
        padding: 0 12px;
        font-size: 13px;
        color: #101828;
        background-color: #ffffff;
        outline: none;
        transition: all 0.15s ease;
    }
    .rs-filter-group select {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23475467' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center !important;
        padding-right: 32px !important;
        cursor: pointer;
    }
    .rs-filter-group select:focus, .rs-filter-group input:focus {
        border-color: #EC3D8F;
        box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.15);
    }
    .rs-filter-btn-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .rs-btn-filter-submit {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 16px;
        background-color: #EC3D8F;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 13.5px;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .rs-btn-filter-submit:hover {
        background-color: #D82677;
        color: #ffffff;
    }
    .rs-btn-filter-reset {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        height: 38px;
        padding: 0 12px;
        background-color: #F2F4F7;
        color: #344054;
        border: 1px solid #D0D5DD;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
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
    .rs-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 8px;
        border-radius: 12px;
        background-color: #F2F4F7;
        color: #344054;
        font-size: 12px;
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
        min-width: 1100px;
        border-collapse: separate;
        border-spacing: 0;
        text-align: left;
    }
    .rs-table thead tr {
        background-color: #F8F9FA !important;
    }
    .rs-table th {
        background-color: #F8F9FA !important;
        padding: 10px 10px !important;
        font-size: 12px !important;
        font-weight: 600;
        color: #475467 !important;
        text-transform: none;
        border-bottom: 1px solid #EAECF0 !important;
        white-space: nowrap !important;
    }
    .rs-table tbody tr {
        border-bottom: 1px solid #EAECF0;
        transition: background-color 0.15s ease;
    }
    .rs-table tbody tr:hover {
        background-color: #F9FAFB;
    }
    .rs-table td {
        padding: 10px 10px !important;
        font-size: 12px !important;
        color: #344054;
        vertical-align: middle;
        border-bottom: 1px solid #EAECF0;
    }
    .rs-code-invoice {
        font-family: monospace;
        font-size: 11.5px;
        font-weight: 700;
        color: #EC3D8F !important;
        white-space: nowrap !important;
    }
    .badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px;
        padding: 3px 8px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
        border: 1px solid transparent !important;
    }
    .badge-pending {
        background-color: #FFFAEB !important;
        color: #B54708 !important;
        border-color: #FEDF89 !important;
    }
    .badge-confirmed {
        background-color: #ECFDF3 !important;
        color: #027A48 !important;
        border-color: #ABEFC6 !important;
    }
    .badge-shipping {
        background-color: #FDF2FA !important;
        color: #C11574 !important;
        border-color: #FCCEEE !important;
    }
    .badge-delivered {
        background-color: #ECFDF3 !important;
        color: #027A48 !important;
        border-color: #ABEFC6 !important;
    }
    .badge-cancelled {
        background-color: #FEF3F2 !important;
        color: #B42318 !important;
        border-color: #FECDCA !important;
    }
    .badge-paid {
        background-color: #ECFDF3 !important;
        color: #027A48 !important;
        border-color: #ABEFC6 !important;
    }
    .badge-unpaid {
        background-color: #FEF3F2 !important;
        color: #B42318 !important;
        border-color: #FECDCA !important;
    }
    .btn-detail {
        background-color: #FDEAF3 !important;
        border: 1px solid #FCCEE2 !important;
        color: #EC3D8F !important;
        border-radius: 8px !important;
        padding: 4px 10px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
    }
    .btn-detail:hover {
        background-color: #EC3D8F !important;
        border-color: #EC3D8F !important;
        color: #ffffff !important;
    }
    .btn-delete {
        background-color: #FEF3F2 !important;
        border: 1px solid #FECDCA !important;
        color: #B42318 !important;
        border-radius: 8px !important;
        padding: 4px 10px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
    }
    .btn-delete:hover:not(:disabled) {
        background-color: #B42318 !important;
        border-color: #B42318 !important;
        color: #ffffff !important;
    }
    .btn-delete:disabled {
        opacity: 0.45 !important;
        cursor: not-allowed !important;
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
        width: 68px !important;
        height: 34px !important;
        padding: 0 24px 0 10px !important;
        border: 1px solid #D0D5DD !important;
        border-radius: 8px !important;
        font-size: 13.5px !important;
        font-weight: 500 !important;
        color: #101828 !important;
        background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23475467' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 8px center !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        outline: none !important;
        cursor: pointer !important;
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
    .active > .page-link,
    .active-page {
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

    /* Action Buttons (Icon-only with rounded border) */
    .btn-detail,
    .btn-delete {
        width: 32px !important;
        height: 32px !important;
        min-width: 32px !important;
        padding: 0 !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 14px !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
    }
    .btn-detail {
        background-color: #FDEAF3 !important;
        border: 1px solid #FCCEE2 !important;
        color: #EC3D8F !important;
    }
    .btn-detail:hover {
        background-color: #EC3D8F !important;
        border-color: #EC3D8F !important;
        color: #ffffff !important;
    }
    .btn-delete {
        background-color: #FEF3F2 !important;
        border: 1px solid #FECDCA !important;
        color: #B42318 !important;
    }
    .btn-delete:hover:not(:disabled) {
        background-color: #B42318 !important;
        border-color: #B42318 !important;
        color: #ffffff !important;
    }
    .btn-delete:disabled {
        opacity: 0.4 !important;
        cursor: not-allowed !important;
        background-color: #F9FAFB !important;
        border-color: #EAECF0 !important;
        color: #98A2B3 !important;
    }

    /* Modal Detail Sections */
    .modal-section {
        background: #ffffff;
        border: 1px solid #EAECF0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
    }
    .modal-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        font-weight: 700;
        color: #EC3D8F;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #F2F4F7;
    }
    .modal-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 8px 24px;
    }
    .modal-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #F9FAFB;
        font-size: 13px;
        gap: 12px;
    }
    .modal-info-label {
        color: #667085;
        font-weight: 500;
        font-size: 13px;
        flex-shrink: 0;
    }
    .modal-info-value {
        color: #101828;
        font-weight: 600;
        font-size: 13px;
        text-align: right;
        word-break: break-word;
    }
    .modal-product-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
        margin-top: 6px;
    }
    .modal-product-table th {
        background-color: #F8F9FA;
        color: #475467;
        font-weight: 600;
        font-size: 12.5px;
        padding: 10px 12px;
        border-bottom: 1px solid #EAECF0;
        white-space: nowrap;
    }
    .modal-product-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #F2F4F7;
        color: #344054;
        vertical-align: middle;
    }
    .modal-product-table .total-row td {
        font-weight: 700;
        border-top: 1px solid #EAECF0;
        border-bottom: none;
        color: #EC3D8F;
        font-size: 14px;
        padding-top: 12px;
    }
</style>
@endpush

@section('content')

<div class="rs-management-page">

    {{-- Page Header --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div>
                <h1 class="rs-header-title">Quản lý hóa đơn</h1>
                <p class="rs-header-subtitle">Danh sách hóa đơn bán hàng, lịch sử thanh toán và trạng thái xử lý.</p>
            </div>
        </div>
    </div>

    {{-- Main Unified Card --}}
    <div class="rs-main-card">

        {{-- Filter Bar (Đẩy lên cùng 1 hàng, có mũi tên xuống cho dropdown) --}}
        <form method="GET" action="{{ route('hoa-don.index') }}" class="rs-filter-bar" id="filter-form">
            <div class="rs-filter-row">

                {{-- Khách hàng --}}
                <div class="rs-filter-group">
                    <label>Khách Hàng</label>
                    <select name="ma_khach_hang">
                        <option value="">— Tất cả —</option>
                        @foreach ($khachHangs as $kh)
                            <option value="{{ $kh->ma_khach_hang }}"
                                {{ request('ma_khach_hang') == $kh->ma_khach_hang ? 'selected' : '' }}>
                                {{ $kh->ten_khach_hang }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Trạng thái đơn --}}
                <div class="rs-filter-group">
                    <label>Trạng Thái Đơn</label>
                    <select name="trang_thai">
                        <option value="">— Tất cả —</option>
                        @foreach (['PENDING'=>'Chờ xử lý','CONFIRMED'=>'Đã xác nhận','SHIPPING'=>'Đang giao','DELIVERED'=>'Đã giao','CANCELLED'=>'Đã hủy'] as $val => $label)
                            <option value="{{ $val }}" {{ request('trang_thai') == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Thanh toán --}}
                <div class="rs-filter-group">
                    <label>Thanh Toán</label>
                    <select name="trang_thai_thanh_toan">
                        <option value="">— Tất cả —</option>
                        <option value="CHUA_THANH_TOAN" {{ request('trang_thai_thanh_toan') == 'CHUA_THANH_TOAN' ? 'selected' : '' }}>Chưa thanh toán</option>
                        <option value="DA_THANH_TOAN"   {{ request('trang_thai_thanh_toan') == 'DA_THANH_TOAN'   ? 'selected' : '' }}>Đã thanh toán</option>
                    </select>
                </div>

                {{-- Từ ngày --}}
                <div class="rs-filter-group">
                    <label>Từ Ngày</label>
                    <input type="date" name="tu_ngay" value="{{ request('tu_ngay') }}">
                </div>

                {{-- Đến ngày --}}
                <div class="rs-filter-group">
                    <label>Đến Ngày</label>
                    <input type="date" name="den_ngay" value="{{ request('den_ngay') }}">
                </div>

                {{-- Nút hành động (Lọc + Đặt lại trên cùng 1 hàng) --}}
                <div class="rs-filter-btn-group">
                    <button type="submit" class="rs-btn-filter-submit"><i class="bi bi-search"></i> Lọc kết quả</button>
                    @if(request()->hasAny(['ma_khach_hang', 'trang_thai', 'trang_thai_thanh_toan', 'tu_ngay', 'den_ngay']))
                        <a href="{{ route('hoa-don.index') }}" class="rs-btn-filter-reset"><i class="bi bi-arrow-clockwise"></i> Đặt lại</a>
                    @endif
                </div>

            </div>
        </form>

        {{-- Card Title Bar --}}
        <div class="rs-card-title-bar">
            <div class="rs-card-title">
                <i class="bi bi-receipt"></i>
                Danh sách hóa đơn
                <span class="rs-count-badge">{{ $hoaDons->total() }}</span>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th style="white-space: nowrap;">Mã HD</th>
                        <th style="white-space: nowrap;">Khách Hàng</th>
                        <th style="white-space: nowrap;">Ngày Đặt</th>
                        <th style="white-space: nowrap;">Tổng Tiền</th>
                        <th style="white-space: nowrap;">Trạng Thái</th>
                        <th style="white-space: nowrap;">Thanh Toán</th>
                        <th style="white-space: nowrap;">PT Thanh Toán</th>
                        <th>Địa Chỉ Giao</th>
                        <th style="white-space: nowrap;">SĐT</th>
                        <th style="white-space: nowrap;">Ngày Giao</th>
                        <th style="white-space: nowrap;">Nhân Viên Giao</th>
                        <th style="white-space: nowrap; text-align: center;">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="invoice-table-body">
                @forelse ($hoaDons as $hd)
                    <tr id="row-{{ $hd->ma_hoa_don }}">
                        <td style="white-space: nowrap;"><span class="rs-code-invoice">#HD-{{ str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</span></td>
                        <td style="white-space: nowrap; font-weight: 600; color: #101828;">{{ $hd->khachHang->ten_khach_hang ?? '—' }}</td>
                        <td style="white-space: nowrap; color: #475467; font-size: 12px;">{{ optional($hd->ngay_dat)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td style="white-space: nowrap; font-weight: 700; color: #101828;">{{ number_format($hd->tong_tien, 0, ',', '.') }}₫</td>
                        <td style="white-space: nowrap;">
                            @php
                                $statusMap = ['PENDING'=>'badge-pending','CONFIRMED'=>'badge-confirmed','SHIPPING'=>'badge-shipping','DELIVERED'=>'badge-delivered','CANCELLED'=>'badge-cancelled'];
                                $statusLabel = ['PENDING'=>'Chờ xử lý','CONFIRMED'=>'Xác nhận','SHIPPING'=>'Đang giao','DELIVERED'=>'Đã giao','CANCELLED'=>'Đã hủy'];
                            @endphp
                            <span class="badge {{ $statusMap[$hd->trang_thai] ?? '' }}">
                                {{ $statusLabel[$hd->trang_thai] ?? $hd->trang_thai }}
                            </span>
                        </td>
                        <td style="white-space: nowrap;">
                            <span class="badge {{ in_array($hd->trang_thai_thanh_toan, ['DA_THANH_TOAN','DA_NOP']) ? 'badge-paid' : 'badge-unpaid' }}">
                                <i class="bi {{ in_array($hd->trang_thai_thanh_toan, ['DA_THANH_TOAN','DA_NOP']) ? 'bi-check-circle' : 'bi-clock' }}"></i>
                                {{ in_array($hd->trang_thai_thanh_toan, ['DA_THANH_TOAN','DA_NOP']) ? 'Đã TT' : 'Chưa TT' }}
                            </span>
                        </td>
                        <td style="white-space: nowrap; font-size: 12px; color: #344054;">{{ $hd->phuong_thuc_thanh_toan ?? '—' }}</td>
                        <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $hd->dia_chi_giao }}">{{ $hd->dia_chi_giao ?? '—' }}</td>
                        <td style="white-space: nowrap; font-family: monospace; font-size: 12px; color: #475467;">{{ $hd->so_dien_thoai ?? '—' }}</td>
                        <td style="white-space: nowrap; font-size: 12px; color: #475467;">{{ optional($hd->ngay_giao)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td style="white-space: nowrap; font-size: 12px; color: #344054;">{{ $hd->shipper->ten_nhan_vien ?? '—' }}</td>
                        <td style="white-space: nowrap; text-align: center;">
                            <div style="display:flex; gap:6px; align-items:center; justify-content:center;">
                                {{-- Nút Chi Tiết (Icon-only) --}}
                                <button class="btn btn-detail"
                                        data-id="{{ $hd->ma_hoa_don }}"
                                        onclick="openDetail({{ $hd->ma_hoa_don }}, this)"
                                        title="Xem chi tiết hóa đơn #HD-{{ str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}">
                                    <i class="bi bi-eye"></i>
                                </button>

                                {{-- Nút Xóa (Icon-only) --}}
                                @php
                                    $canDelete = ($hd->trang_thai_thanh_toan === 'CHUA_THANH_TOAN'
                                              || $hd->trang_thai === 'CANCELLED')
                                              && $hd->trang_thai !== 'DELIVERED';
                                @endphp
                                <button class="btn btn-delete"
                                        data-id="{{ $hd->ma_hoa_don }}"
                                        {{ !$canDelete ? 'disabled' : '' }}
                                        onclick="deleteInvoice({{ $hd->ma_hoa_don }}, this)"
                                        title="Xóa hóa đơn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">
                            <div style="text-align: center; padding: 48px 24px; color: #667085;">
                                <div style="font-size: 32px; margin-bottom: 8px;">◻</div>
                                <p class="mb-0 fw-semibold">Không tìm thấy hóa đơn nào.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN ROSESHOP --}}
        @if($hoaDons->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage]) }}" {{ (request('per_page', $hoaDons->perPage()) == $perPage) ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rs-records-count">
                        {{ $hoaDons->firstItem() ?? 0 }}-{{ $hoaDons->lastItem() ?? 0 }} / {{ $hoaDons->total() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($hoaDons->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $hoaDons->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $hoaDons->currentPage();
                            $lastPage = $hoaDons->lastPage();
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
                                <li class="page-item"><a class="page-link" href="{{ $hoaDons->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($hoaDons->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $hoaDons->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    </div>

</div>

{{-- ── MODAL CHI TIẾT HÓA ĐƠN (ĐÈ LÊN GIAO DIỆN, KHÔNG LÀM BÓ HẸP BẢNG) ── --}}
<div class="modal fade" id="modalDetailHoaDon" tabindex="-1" aria-labelledby="modalDetailHoaDonLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #EAECF0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden;">
            <div class="modal-header" style="background: #ffffff; border-bottom: 1px solid #EAECF0; padding: 18px 24px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #FDEAF3; color: #EC3D8F; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailHoaDonLabel" style="font-size: 17px;">
                            Chi Tiết Hóa Đơn <span id="modal-invoice-code" class="rs-code-invoice" style="font-size: 15px; margin-left: 4px;">#HD-....</span>
                        </h5>
                        <span class="text-muted" style="font-size: 12.5px;">Chi tiết giao dịch, khách hàng &amp; thông tin bó hoa thiết kế</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modal-detail-body" style="background: #F8F9FA;">
                <!-- Dữ liệu được nạp động từ AJAX -->
            </div>
            <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #EAECF0; padding: 14px 24px;">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="font-weight: 500; font-size: 13.5px; border-radius: 8px;">Đóng</button>
            </div>
        </div>
    </div>
</div>

{{-- ── TOAST ── --}}
<div id="toast"></div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let detailModal = null;

    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modalDetailHoaDon');
        if (modalEl) {
            detailModal = new bootstrap.Modal(modalEl);
        }
    });

    function showToast(msg, isError = false) {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className   = 'show' + (isError ? ' error' : '');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = ''; }, 3000);
    }

    function openDetail(id, btn) {
        const modalBody = document.getElementById('modal-detail-body');
        document.getElementById('modal-invoice-code').textContent = `#HD-${String(id).padStart(4, '0')}`;
        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-danger mb-2" role="status" style="color: #EC3D8F !important;"></div>
                <div class="text-muted" style="font-size: 13.5px;">Đang tải chi tiết hóa đơn...</div>
            </div>
        `;

        if (!detailModal) {
            detailModal = new bootstrap.Modal(document.getElementById('modalDetailHoaDon'));
        }
        detailModal.show();

        fetch(`/hoa-don/${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0">Không tải được dữ liệu hóa đơn.</div>';
                return;
            }
            renderModalContent(res.data);
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="alert alert-danger mb-0">Lỗi kết nối máy chủ khi tải dữ liệu.</div>';
        });
    }
    // ── Panel xem log kiểm tra hàng hoàn ─────────────────────────────
function openKiemTraLog(id, btn) {
    const panel = document.getElementById('detail-panel');
    const body  = document.getElementById('panel-body');

    // Reset panel chi tiết nếu đang mở
    if (activeDetailId && activeDetailId !== id) closeDetail();
    activeDetailId = null;
    activeBtn = btn;

    document.querySelectorAll('tr.highlighted').forEach(r => r.classList.remove('highlighted'));
    const row = document.getElementById('row-' + id);
    if (row) row.classList.add('highlighted');

    document.getElementById('panel-title').textContent =
        'Log kiểm tra hàng hoàn — #HD-' + String(id).padStart(4, '0');

    body.innerHTML = '<div class="panel-loading">Đang tải...</div>';
    panel.classList.add('open');
    document.getElementById('page-body').classList.add('panel-open');

    fetch(`/admin/hoa-don/${id}/kiem-tra-hang`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            body.innerHTML = '<p style="padding:20px;color:#888">Không tải được dữ liệu.</p>';
            return;
        }
        renderKiemTraLog(res.data);
    })
    .catch(() => {
        body.innerHTML = '<p style="padding:20px;color:#888">Lỗi kết nối.</p>';
    });
}

function renderKiemTraLog(d) {
    const esc = v => String(v ?? '—').replace(/[&<>"']/g, c =>
        ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

    const hangHoan = d.hangHoan || {};
    const thoiDiem = hangHoan.thoi_diem_kiem_tra || null;

    const rows = (d.dongKiemTra || []).map(x => `
        <tr>
            <td>${esc(x.la_bo_hoa ? 'Bó hoa' : 'Sản phẩm')}: ${esc(x.ten)}</td>
            <td style="text-align:center">${Number(x.so_luong_theo_don) || 0}</td>
            <td style="text-align:center;color:#c0392b">${Number(x.so_luong_hong) || 0}</td>
            <td style="text-align:center;color:#2e7d52">${Number(x.so_luong_con_ban_duoc) || 0}</td>
            <td>${esc(x.ket_qua)}</td>
            <td>${esc(x.ghi_chu)}</td>
        </tr>
    `).join('') || '<tr><td colspan="6" style="text-align:center;color:#888">Chưa có dữ liệu</td></tr>';

    document.getElementById('panel-body').innerHTML = `
        <div class="panel-section">
            <div class="panel-section-title">Kết quả kiểm tra hàng hoàn</div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align:center">SL đơn</th>
                        <th style="text-align:center">Hỏng</th>
                        <th style="text-align:center">Còn bán</th>
                        <th>Kết quả</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
        <div class="panel-section" style="padding-top:0;">
            <small style="color:#64748b;font-size:12px;">
                ${thoiDiem
                    ? 'Log đã lưu cố định vào ' + esc(thoiDiem) + ' — không thể chỉnh sửa.'
                    : 'Log kiểm tra — chỉ đọc.'}
            </small>
        </div>
    `;
}

    function renderModalContent(d) {
        const statusLabel = {PENDING:'Chờ xử lý',CONFIRMED:'Đã xác nhận',SHIPPING:'Đang giao',DELIVERED:'Đã giao',CANCELLED:'Đã hủy'};
        const fmt = v => v ? Number(v).toLocaleString('vi-VN') + '₫' : '—';
        const escapeHtml = v => String(v ?? '—').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        // 1. Nhóm sản phẩm / vật phẩm
        let productsHtml = '';
        let grandTotal = 0;
        if (d.chi_tiet_hoa_don && d.chi_tiet_hoa_don.length) {
            const grouped = {};
            const order   = [];

            d.chi_tiet_hoa_don.forEach(ct => {
                const key = ct.ma_bo_hoa ? 'bo_' + ct.ma_bo_hoa : (ct.ma_san_pham !== undefined && ct.ma_san_pham !== null)
                    ? 'sp_' + ct.ma_san_pham
                    : 'ten_' + (ct.san_pham?.ten_san_pham ?? 'unknown');

                if (!grouped[key]) {
                    grouped[key] = {
                        ten_san_pham:     ct.san_pham?.ten_san_pham ?? '—',
                        so_luong:         0,
                        gia_ban_snapshot: ct.gia_ban_snapshot,
                        thanh_tien:       0,
                    };
                    order.push(key);
                }

                grouped[key].so_luong   += Number(ct.so_luong);
                grouped[key].thanh_tien += Number(ct.thanh_tien);
            });

            order.forEach(key => {
                const g = grouped[key];
                grandTotal += g.thanh_tien;
                productsHtml += `
                <tr>
                    <td style="font-weight:500;color:#101828;">${String(g.ten_san_pham).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}</td>
                    <td style="text-align:center;"><span style="display:inline-block;padding:2px 8px;background:#F2F4F7;border-radius:10px;font-size:11.5px;font-weight:600;color:#344054;">x${g.so_luong}</span></td>
                    <td style="text-align:right;white-space:nowrap;color:#475467;font-size:12.5px;">${fmt(g.gia_ban_snapshot)}</td>
                    <td style="text-align:right;white-space:nowrap;font-weight:700;color:#101828;">${fmt(g.thanh_tien)}</td>
                </tr>`;
            });

            productsHtml += `
                <tr class="total-row">
                    <td colspan="3" style="font-weight:700;color:#101828;padding-top:12px;">Tổng giá trị sản phẩm</td>
                    <td style="text-align:right;font-weight:700;color:#EC3D8F;font-size:15px;padding-top:12px;">${fmt(grandTotal)}</td>
                </tr>`;
        } else {
            productsHtml = '<tr><td colspan="4" style="text-align:center;color:#888;padding:16px;">Không có sản phẩm trong đơn</td></tr>';
        }

        // 2. Thông tin bó hoa thiết kế (Custom Mix) - CHỈ hiển thị khi đơn hàng có bó hoa tự thiết kế (custom mix)
        let customBouquetHtml = '';
        let sampleImagesHtml = '';

        if (d.custom_bouquet) {
            const cb = d.custom_bouquet;

            // Bản đồ hiển thị tiếng Việt chuẩn hóa
            const conceptMap = {
                'ROMANTIC': 'Lãng mạn',
                'ELEGANT': 'Thanh lịch',
                'VIBRANT': 'Rực rỡ',
                'PASTEL': 'Dịu dàng / Pastel',
                'VINTAGE': 'Cổ điển / Vintage',
                'CUTE': 'Đáng yêu',
                'LUXURY': 'Sang trọng',
                'NATURAL': 'Tự nhiên',
                'MINIMALIST': 'MINIMALIST'
            };
            const styleMap = {
                'ROUND': 'Tròn (+15.000₫)',
                'Tròn': 'Tròn (+15.000₫)',
                'LONG': 'Dáng dài (+20.000₫)',
                'HEART': 'Trái tim (+25.000₫)',
                'FREE_STYLE': 'Tự do (+15.000₫)',
                'CASCADING': 'Dáng suối (+25.000₫)',
                'ASYMMETRIC': 'Bất đối xứng (+20.000₫)'
            };
            const sizeMap = {
                'S': 'Size S — Định mức: 7 bông (+5.000₫)',
                'M': 'Size M — Định mức: 12 bông (+10.000₫)',
                'L': 'Size L — Định mức: 18 bông (+15.000₫)',
                'XL': 'Size XL — Định mức: 25 bông (+20.000₫)',
                'SPECIAL': 'Size Đặc Biệt — Định mức: >25 bông (+30.000₫)'
            };

            const concept = conceptMap[cb.concept] || (cb.concept || 'MINIMALIST');

            let kieuBoHoa = styleMap[cb.kieu_bo_hoa] || (cb.kieu_bo_hoa || 'Tròn (+15.000₫)');
            if (!kieuBoHoa.includes('(')) {
                kieuBoHoa += ' (+15.000₫)';
            }

            let sizeBoHoa = sizeMap[cb.size] || (cb.size || 'Size S — Định mức: 7 bông (+5.000₫)');
            if (!sizeBoHoa.includes('—')) {
                sizeBoHoa = `Size ${cb.size || 'S'} — Định mức: 7 bông (+5.000₫)`;
            }

            const tenBoHoa = cb.ten_bo_hoa || 'Bó hoa của tôi';
            const soLuongBoHoa = cb.so_luong || 1;

            customBouquetHtml = `
            <div class="modal-section" style="border-left: 4px solid #EC3D8F;">
                <div class="modal-section-title">
                    <i class="bi bi-flower1"></i>
                    Thông Tin Bó Hoa Thiết Kế (Custom Mix)
                </div>
                <div class="modal-info-grid">
                    <div class="modal-info-row">
                        <span class="modal-info-label">Tên bó hoa thiết kế <span class="text-danger">*</span>:</span>
                        <span class="modal-info-value" style="color:#EC3D8F;font-weight:700;">${tenBoHoa}</span>
                    </div>
                    <div class="modal-info-row">
                        <span class="modal-info-label">Concept / Chủ đề thiết kế <span class="text-danger">*</span>:</span>
                        <span class="modal-info-value">${concept}</span>
                    </div>
                    <div class="modal-info-row">
                        <span class="modal-info-label">Kiểu dáng bó hoa <span class="text-danger">*</span>:</span>
                        <span class="modal-info-value">${kieuBoHoa}</span>
                    </div>
                    <div class="modal-info-row">
                        <span class="modal-info-label">Kích thước (Size) <span class="text-danger">*</span>:</span>
                        <span class="modal-info-value"><span class="badge" style="background:#FDEAF3;color:#EC3D8F;border:1px solid #FCCEE2;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700;">${sizeBoHoa}</span></span>
                    </div>
                    <div class="modal-info-row">
                        <span class="modal-info-label">Số lượng bó hoa cần làm <span class="text-danger">*</span>:</span>
                        <span class="modal-info-value"><span class="badge" style="background:#F2F4F7;color:#344054;border:1px solid #EAECF0;padding:4px 10px;border-radius:6px;font-size:12.5px;font-weight:700;">${soLuongBoHoa}</span></span>
                    </div>
                </div>
                ${cb.loi_nhan ? `
                <div class="mt-3 pt-2 border-top">
                    <span class="modal-info-label" style="display:block;margin-bottom:4px;color:#667085;font-size:12.5px;">Lời nhắn thiệp tặng kèm:</span>
                    <div style="background:#F9FAFB;border:1px dashed #D0D5DD;border-radius:8px;padding:10px 14px;font-size:13px;color:#344054;font-style:italic;">
                        "${cb.loi_nhan}"
                    </div>
                </div>` : ''}
            </div>`;

            // 3. Ảnh mẫu khách gửi (nếu có)
            if (d.anh_mau_custom && d.anh_mau_custom.length > 0) {
                sampleImagesHtml = `
                <div class="modal-section">
                    <div class="modal-section-title">
                        <i class="bi bi-images"></i>
                        Ảnh Mẫu Khách Gửi (Custom Mix)
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        ${d.anh_mau_custom.map(img => `
                            <a href="${img.url}" target="_blank" title="${img.name || 'Xem ảnh gốc'}" style="display:block; border:1px solid #EAECF0; border-radius:8px; overflow:hidden; width:84px; height:84px; box-shadow:0 1px 3px rgba(0,0,0,0.06); transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <img src="${img.url}" alt="${img.name || 'Ảnh mẫu'}" style="width:100%; height:100%; object-fit:cover;" />
                            </a>
                        `).join('')}
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size:12px;">Ảnh mẫu dùng để thợ cắm hoa tham khảo phong cách phối bó tự chọn.</small>
                </div>`;
            }
        }

        document.getElementById('modal-detail-body').innerHTML = `
        <div class="modal-section">
            <div class="modal-section-title">
                <i class="bi bi-info-circle"></i>
                Thông Tin Hóa Đơn &amp; Thanh Toán
            </div>
            <div class="modal-info-grid">
                <div class="modal-info-row"><span class="modal-info-label">Mã hóa đơn:</span><span class="modal-info-value"><span class="rs-code-invoice">#HD-${String(d.ma_hoa_don).padStart(4,'0')}</span></span></div>
                <div class="modal-info-row"><span class="modal-info-label">Ngày đặt hàng:</span><span class="modal-info-value">${d.ngay_dat ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">Ngày giao hàng:</span><span class="modal-info-value">${d.ngay_giao ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">Trạng thái đơn:</span><span class="modal-info-value"><span class="badge ${d.trang_thai ? 'badge-' + d.trang_thai.toLowerCase() : ''}">${statusLabel[d.trang_thai] ?? d.trang_thai}</span></span></div>
                <div class="modal-info-row"><span class="modal-info-label">Thanh toán:</span><span class="modal-info-value"><span class="badge ${['DA_THANH_TOAN','DA_NOP'].includes(d.trang_thai_thanh_toan) ? 'badge-paid' : 'badge-unpaid'}">${['DA_THANH_TOAN','DA_NOP'].includes(d.trang_thai_thanh_toan) ? 'Đã thanh toán' : 'Chưa thanh toán'}</span></span></div>
                <div class="modal-info-row"><span class="modal-info-label">Phương thức:</span><span class="modal-info-value">${d.phuong_thuc_thanh_toan ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">Tổng thanh toán:</span><span class="modal-info-value" style="color:#EC3D8F;font-weight:700;font-size:15px;">${fmt(d.tong_tien)}</span></div>
            </div>
        </div>

        <div class="modal-section">
            <div class="modal-section-title">
                <i class="bi bi-person-badge"></i>
                Thông Tin Khách Hàng &amp; Nhận Hàng
            </div>
            <div class="modal-info-grid">
                <div class="modal-info-row"><span class="modal-info-label">Khách hàng:</span><span class="modal-info-value" style="font-weight:600;">${d.khach_hang?.ten_khach_hang ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">SĐT liên hệ:</span><span class="modal-info-value">${d.khach_hang?.so_dien_thoai ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">Email:</span><span class="modal-info-value">${d.khach_hang?.email ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">Địa chỉ giao:</span><span class="modal-info-value" style="max-width:260px;text-align:right;">${d.dia_chi_giao ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">SĐT nhận hàng:</span><span class="modal-info-value">${d.so_dien_thoai ?? '—'}</span></div>
                <div class="modal-info-row"><span class="modal-info-label">Shipper phụ trách:</span><span class="modal-info-value">${d.nhan_vien_giao?.ten_nhan_vien ?? '—'}</span></div>
            </div>
        </div>

        ${customBouquetHtml}

        ${sampleImagesHtml}

        <div class="modal-section">
            <div class="modal-section-title">
                <i class="bi bi-cart3"></i>
                Chi Tiết Sản Phẩm Trong Đơn
            </div>
            <table class="modal-product-table">
                <thead>
                    <tr>
                        <th>Sản Phẩm</th>
                        <th style="text-align:center;">SL</th>
                        <th style="text-align:right;">Đơn Giá</th>
                        <th style="text-align:right;">Thành Tiền</th>
                    </tr>
                </thead>
                <tbody>${productsHtml}</tbody>
            </table>
        </div>
        `;
    }

    function deleteInvoice(id, btn) {
        if (!confirm(`Bạn chắc chắn muốn xóa hóa đơn #HD-${String(id).padStart(4,'0')}?\nChi tiết hóa đơn cũng sẽ bị xóa theo.`)) return;

        btn.disabled = true;
        btn.textContent = '...';

        fetch(`/hoa-don/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN':    CSRF,
                'X-Requested-With':'XMLHttpRequest',
                'Accept':          'application/json',
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const row = document.getElementById('row-' + id);
                if (row) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity    = '0';
                    setTimeout(() => row.remove(), 300);
                }
                showToast(res.message);
            } else {
                btn.disabled    = false;
                btn.textContent = 'Xóa';
                showToast(res.message, true);
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'Xóa';
            showToast('Lỗi kết nối máy chủ.', true);
        });
    }
</script>
@endpush
