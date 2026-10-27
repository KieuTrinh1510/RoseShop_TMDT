@extends('layouts.admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/Admin/DonHang.css') }}">
<style>
    .rs-management-page {
        padding: 24px 32px !important;
        background-color: #F8F9FC;
        min-height: 100vh;
        font-family: inherit;
        color: #344054;
    }
    .rs-header-block {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .rs-header-title {
        font-size: 22px;
        font-weight: 700;
        color: #101828;
        margin: 0 0 4px 0;
    }
    .rs-header-subtitle {
        font-size: 14px;
        color: #667085;
        margin: 0;
    }

    /* ─── TAB NAVIGATION ─── */
    .rs-tab-bar {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        padding: 6px 8px;
        border-radius: 12px;
        border: 1px solid #EAECF0;
        margin-bottom: 16px;
        overflow-x: auto;
    }
    .rs-tab-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 500;
        color: #475467;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .rs-tab-item:hover {
        background-color: #F8F9FC;
        color: #101828;
    }
    .rs-tab-item.active {
        background-color: #FDF2F8;
        color: #EC3D8F;
        font-weight: 600;
    }
    .rs-tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 1px 7px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        background-color: #F2F4F7;
        color: #344054;
        min-width: 20px;
    }
    .rs-tab-item.active .rs-tab-count {
        background-color: #EC3D8F;
        color: #ffffff;
    }
    .rs-tab-count-danger {
        background-color: #FEF3F2;
        color: #B42318;
    }
    .rs-tab-item.active .rs-tab-count-danger {
        background-color: #B42318;
        color: #ffffff;
    }

    /* ─── MAIN CARD & FILTER ─── */
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
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }
    .rs-filter-group {
        display: flex;
        flex-direction: column;
        flex: 1 1 140px;
        min-width: 120px;
    }
    .rs-filter-group-lg {
        flex: 2 1 220px;
        min-width: 200px;
    }
    .rs-filter-group label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #475467;
        margin-bottom: 5px;
        white-space: nowrap;
    }
    .rs-filter-group select, .rs-filter-group input {
        width: 100%;
        border: 1px solid #D0D5DD;
        border-radius: 8px;
        height: 38px;
        padding: 0 12px;
        font-size: 13.5px;
        color: #101828;
        background: #ffffff;
        outline: none;
        transition: all 0.15s ease;
        box-sizing: border-box;
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
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        margin-bottom: 0;
    }
    .rs-btn-filter-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    .rs-btn-filter-submit:hover {
        background-color: #D82677;
        color: #ffffff;
    }
    .rs-btn-filter-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 38px;
        padding: 0 12px;
        background-color: #FEF3F2;
        color: #B42318;
        border: 1px solid #FECDCA;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    .rs-btn-filter-reset:hover {
        background-color: #FEE4E2;
        color: #912018;
    }

    /* ─── CARD TITLE BAR ─── */
    .rs-card-title-bar {
        padding: 14px 20px;
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

    /* ─── TABLE STYLES ─── */
    .rs-table-container {
        width: 100%;
        overflow-x: auto;
        position: relative;
    }
    .rs-table {
        width: 100%;
        min-width: 1050px;
        border-collapse: separate;
        border-spacing: 0;
        text-align: left;
    }
    .rs-table thead tr {
        background-color: #F8F9FA !important;
    }
    .rs-table th {
        background-color: #F8F9FA !important;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 600;
        color: #475467 !important;
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
        padding: 12px 14px;
        font-size: 13.5px;
        color: #344054;
        vertical-align: middle;
        border-bottom: 1px solid #EAECF0;
    }
    .rs-code-order {
        font-family: monospace;
        font-size: 12.5px;
        font-weight: 700;
        color: #EC3D8F !important;
        white-space: nowrap !important;
    }

    /* ─── STATUS BADGES ─── */
    .rs-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .rs-badge-pending {
        background: #FFFAEB;
        color: #B54708;
        border: 1px solid #FEDF89;
    }
    .rs-badge-confirmed {
        background: #F0F9FF;
        color: #026AA2;
        border: 1px solid #B9E6FE;
    }
    .rs-badge-shipping {
        background: #F9F5FF;
        color: #6941C6;
        border: 1px solid #E9D7FE;
    }
    .rs-badge-delivered {
        background: #ECFDF3;
        color: #027A48;
        border: 1px solid #ABEFC6;
    }
    .rs-badge-failed {
        background: #FEF3F2;
        color: #B42318;
        border: 1px solid #FECDCA;
    }
    .rs-badge-cancelled {
        background: #F2F4F7;
        color: #475467;
        border: 1px solid #EAECF0;
    }

    /* Return goods status badges */
    .rs-badge-return-waiting {
        background: #FFFAEB;
        color: #B54708;
        border: 1px solid #FEDF89;
    }
    .rs-badge-return-received {
        background: #EFF8FF;
        color: #175CD3;
        border: 1px solid #B2DDFF;
    }
    .rs-badge-return-checking {
        background: #FDF2FA;
        color: #C11574;
        border: 1px solid #FCCEEE;
    }
    .rs-badge-return-done {
        background: #ECFDF3;
        color: #027A48;
        border: 1px solid #ABEFC6;
    }

    .rs-badge-paid {
        background: #ECFDF3;
        color: #027A48;
        border: 1px solid #ABEFC6;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .rs-badge-unpaid {
        background: #FEF3F2;
        color: #B42318;
        border: 1px solid #FECDCA;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Shipper select & Action Buttons */
    .shipper-select {
        border: 1px solid #D0D5DD !important;
        border-radius: 8px !important;
        height: 34px !important;
        padding: 0 8px !important;
        font-size: 12.5px !important;
        color: #344054 !important;
        background: #ffffff !important;
        max-width: 155px !important;
    }
    .shipper-select.error {
        border-color: #FDA29B !important;
        background-color: #FEF3F2 !important;
    }
    .rs-order-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .rs-order-btn.btn-success {
        background-color: #ECFDF3 !important;
        border-color: #ABEFC6 !important;
        color: #027A48 !important;
    }
    .rs-order-btn.btn-success:hover {
        background-color: #027A48 !important;
        color: #ffffff !important;
    }
    .rs-order-btn.btn-danger {
        background-color: #FEF3F2 !important;
        border-color: #FECDCA !important;
        color: #B42318 !important;
    }
    .rs-order-btn.btn-danger:hover {
        background-color: #B42318 !important;
        color: #ffffff !important;
    }
    .rs-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .rs-btn-action-primary {
        background-color: #FDF2F8;
        border-color: #FCCEEE;
        color: #EC3D8F;
    }
    .rs-btn-action-primary:hover {
        background-color: #EC3D8F;
        color: #ffffff;
    }
    .rs-btn-action-info {
        background-color: #EFF8FF;
        border-color: #B2DDFF;
        color: #175CD3;
    }
    .rs-btn-action-info:hover {
        background-color: #175CD3;
        color: #ffffff;
    }
    .rs-btn-action-warning {
        background-color: #FFFAEB;
        border-color: #FEDF89;
        color: #B54708;
    }
    .rs-btn-action-warning:hover {
        background-color: #B54708;
        color: #ffffff;
    }
    .rs-btn-action-ghost {
        background-color: #F2F4F7;
        border-color: #D0D5DD;
        color: #344054;
    }
    .rs-btn-action-ghost:hover {
        background-color: #E4E7EC;
        color: #101828;
    }

    /* ─── FOOTER PAGINATION ─── */
    .rs-table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 20px;
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
        padding: 0 20px 0 10px !important;
        border: 1px solid #D0D5DD !important;
        border-radius: 8px !important;
        font-size: 13.5px !important;
        font-weight: 500 !important;
        color: #101828 !important;
        background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23475467' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 6px center !important;
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
    .rs-pagination, .pagination {
        display: flex;
        align-items: center;
        gap: 4px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .rs-pagination .page-item .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 8px;
        border-radius: 8px !important;
        border: 1px solid #D0D5DD;
        background: #ffffff;
        color: #344054;
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .rs-pagination .page-item.active .page-link,
    .rs-pagination .page-item.active > .page-link {
        background-color: #EC3D8F !important;
        border-color: #EC3D8F !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    .rs-pagination .page-item.disabled .page-link {
        color: #D0D5DD;
        background-color: #F9FAFB;
        border-color: #EAECF0;
        cursor: not-allowed;
    }

    /* ─── MODAL / DIALOG ─── */
    .overlay {
        display: none !important;
        position: fixed !important;
        inset: 0 !important;
        background: rgba(16, 24, 40, 0.6) !important;
        z-index: 1050 !important;
        align-items: center !important;
        justify-content: center !important;
        backdrop-filter: blur(2px);
    }
    .overlay.show {
        display: flex !important;
    }
    .dialog {
        background: #ffffff !important;
        border-radius: 14px !important;
        padding: 24px 28px !important;
        max-width: 460px !important;
        width: 90% !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        border: 1px solid #EAECF0 !important;
    }
    .dialog h3 {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #B42318 !important;
        margin-bottom: 8px !important;
    }
    .dialog p {
        font-size: 13.5px !important;
        color: #475467 !important;
        margin-bottom: 16px !important;
        line-height: 1.5 !important;
    }
    .dialog-actions textarea {
        width: 100% !important;
        height: 70px !important;
        border: 1px solid #D0D5DD !important;
        border-radius: 8px !important;
        padding: 8px 10px !important;
        font-size: 13px !important;
        outline: none !important;
        resize: none !important;
    }

    /* ─── FAILED DETAIL DRAWER / MODAL ─── */
    .rs-drawer {
        background: #ffffff;
        border-radius: 16px;
        max-width: 760px;
        width: 95%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
        border: 1px solid #EAECF0;
        padding: 24px 28px;
    }
    .rs-drawer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #EAECF0;
        padding-bottom: 16px;
        margin-bottom: 20px;
    }
    .rs-drawer-title {
        font-size: 18px;
        font-weight: 700;
        color: #101828;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .rs-section-title {
        font-size: 13.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475467;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .rs-timeline {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin: 20px 0 24px 0;
        padding: 0 10px;
    }
    .rs-timeline::before {
        content: '';
        position: absolute;
        top: 14px;
        left: 20px;
        right: 20px;
        height: 2px;
        background-color: #EAECF0;
        z-index: 1;
    }
    .rs-timeline-step {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        width: 18%;
    }
    .rs-timeline-dot {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #F2F4F7;
        border: 2px solid #D0D5DD;
        color: #667085;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .rs-timeline-step.completed .rs-timeline-dot {
        background-color: #ECFDF3;
        border-color: #12B76A;
        color: #027A48;
    }
    .rs-timeline-step.current .rs-timeline-dot {
        background-color: #FDF2F8;
        border-color: #EC3D8F;
        color: #EC3D8F;
    }
    .rs-timeline-label {
        font-size: 11.5px;
        font-weight: 600;
        color: #344054;
        line-height: 1.3;
    }
    .rs-timeline-time {
        font-size: 10.5px;
        color: #667085;
        margin-top: 2px;
    }

    .toast {
        position: fixed !important;
        bottom: 28px !important;
        right: 28px !important;
        background: #101828 !important;
        color: #ffffff !important;
        padding: 11px 20px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        opacity: 0 !important;
        transform: translateY(12px) !important;
        transition: opacity .25s, transform .25s !important;
        pointer-events: none !important;
        z-index: 1200 !important;
        max-width: 360px !important;
    }
    .toast.show {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    .toast.error { background: #B42318 !important; }
    .toast.success { background: #027A48 !important; }
</style>
@endpush

@section('content')

<div class="rs-management-page">

    {{-- Page Header --}}
    <div class="rs-header-block">
        <div>
            <h1 class="rs-header-title">Quản lý đơn hàng</h1>
            <p class="rs-header-subtitle">Theo dõi và xử lý toàn bộ vòng đời đơn hàng</p>
        </div>
    </div>

    {{-- ════════ TAB BAR (Vòng đời đơn hàng) ════════ --}}
    <div class="rs-tab-bar">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'all' ? 'active' : '' }}">
            <i class="bi bi-collection"></i>
            Tất cả
            <span class="rs-tab-count">{{ $tabCounts['all'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'pending', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'pending' ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i>
            Chờ duyệt
            <span class="rs-tab-count">{{ $tabCounts['pending'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'confirmed', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'confirmed' ? 'active' : '' }}">
            <i class="bi bi-check2-circle"></i>
            Đã xác nhận
            <span class="rs-tab-count">{{ $tabCounts['confirmed'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'shipping', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'shipping' ? 'active' : '' }}">
            <i class="bi bi-truck"></i>
            Đang giao
            <span class="rs-tab-count">{{ $tabCounts['shipping'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'delivered', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'delivered' ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i>
            Đã giao
            <span class="rs-tab-count">{{ $tabCounts['delivered'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'failed', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'failed' ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle"></i>
            Giao thất bại
            <span class="rs-tab-count rs-tab-count-danger">{{ $tabCounts['failed'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'cancelled', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'cancelled' ? 'active' : '' }}">
            <i class="bi bi-x-circle"></i>
            Đã hủy
            <span class="rs-tab-count">{{ $tabCounts['cancelled'] }}</span>
        </a>
    </div>

    {{-- Main Unified Card --}}
    <div class="rs-main-card">

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('don-hang.index') }}" class="rs-filter-bar">
            {{-- Giữ tab hiện tại --}}
            <input type="hidden" name="tab" value="{{ $tab }}">

            {{-- Tìm kiếm chung --}}
            <div class="rs-filter-group rs-filter-group-lg">
                <label>Tìm kiếm</label>
                <input type="text" name="q"
                       placeholder="Mã đơn (#HD-...), tên khách, SĐT..."
                       value="{{ request('q') }}">
            </div>

            {{-- Trạng thái thanh toán --}}
            <div class="rs-filter-group">
                <label>Thanh Toán</label>
                <select name="trang_thai_thanh_toan">
                    <option value="">— Tất cả —</option>
                    <option value="CHUA_THANH_TOAN" {{ request('trang_thai_thanh_toan') === 'CHUA_THANH_TOAN' ? 'selected' : '' }}>
                        Chưa thanh toán
                    </option>
                    <option value="DA_THANH_TOAN" {{ request('trang_thai_thanh_toan') === 'DA_THANH_TOAN' ? 'selected' : '' }}>
                        Đã thanh toán
                    </option>
                </select>
            </div>

            {{-- Phương thức thanh toán --}}
            <div class="rs-filter-group">
                <label>Hình thức</label>
                <select name="phuong_thuc_thanh_toan">
                    <option value="">— Tất cả —</option>
                    <option value="COD" {{ request('phuong_thuc_thanh_toan') === 'COD' ? 'selected' : '' }}>COD (Tiền mặt)</option>
                    <option value="VNPAY" {{ request('phuong_thuc_thanh_toan') === 'VNPAY' ? 'selected' : '' }}>VNPay</option>
                </select>
            </div>

            {{-- Từ ngày --}}
            <div class="rs-filter-group">
                <label>Từ ngày</label>
                <input type="date" name="tu_ngay" value="{{ request('tu_ngay') }}">
            </div>

            {{-- Đến ngày --}}
            <div class="rs-filter-group">
                <label>Đến ngày</label>
                <input type="date" name="den_ngay" value="{{ request('den_ngay') }}">
            </div>

            {{-- Nút lọc & Bỏ lọc --}}
            <div class="rs-filter-btn-group">
                <button type="submit" class="rs-btn-filter-submit">
                    <i class="bi bi-search"></i>
                    <span>Lọc</span>
                </button>

                @if(request()->anyFilled(['q', 'trang_thai_thanh_toan', 'phuong_thuc_thanh_toan', 'tu_tien', 'den_tien', 'tu_ngay', 'den_ngay']))
                    <a href="{{ route('don-hang.index', ['tab' => $tab]) }}" class="rs-btn-filter-reset" title="Bỏ lọc">
                        <i class="bi bi-x-lg"></i>
                        <span>Bỏ lọc</span>
                    </a>
                @endif
            </div>
        </form>

        {{-- Cảnh báo shipper khi ở tab Chờ duyệt --}}
        @if($tab === 'pending' && $shippers->isEmpty())
            <div style="padding:12px 20px;background:#FFFAEB;border:1px solid #FEF08A;border-radius:8px;margin:16px 20px 0 20px;color:#B54708;font-size:13.5px;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Không có shipper nào đang ONLINE hoặc tất cả đã đủ 10/10 đơn. Vui lòng chờ shipper bật trạng thái hoặc giao bớt đơn.</span>
            </div>
        @endif

        {{-- Card Title Bar --}}
        <div class="rs-card-title-bar">
            <div class="rs-card-title">
                <i class="bi bi-list-ul"></i>
                <span>
                    @switch($tab)
                        @case('pending') Danh sách đơn hàng chờ duyệt @break
                        @case('confirmed') Danh sách đơn hàng đã xác nhận @break
                        @case('shipping') Danh sách đơn hàng đang giao @break
                        @case('delivered') Danh sách đơn hàng đã giao thành công @break
                        @case('failed') Danh sách đơn hàng giao thất bại & hoàn hàng @break
                        @case('cancelled') Danh sách đơn hàng đã hủy @break
                        @default Danh sách tất cả đơn hàng
                    @endswitch
                </span>
                <span class="rs-tab-count">{{ $donHangs->total() }}</span>
            </div>
            <div class="text-muted" style="font-size: 13px;">
                Trang {{ $donHangs->currentPage() }} / {{ $donHangs->lastPage() }}
            </div>
        </div>

        {{-- Table Container --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Ngày Đặt</th>
                        <th>Khách Hàng</th>
                        <th>Tổng Tiền</th>
                        <th>Thanh Toán</th>
                        <th>Shipper</th>

                        @if($tab === 'shipping')
                            <th>Bắt Đầu Giao</th>
                        @elseif($tab === 'failed')
                            <th>Nguồn Lỗi</th>
                            <th>Lý Do Thất Bại</th>
                            <th>Hàng Hoàn</th>
                        @elseif($tab === 'cancelled')
                            <th>Thời Điểm Hủy</th>
                            <th>Lý Do Hủy</th>
                        @endif

                        <th>Trạng Thái</th>
                        <th style="text-align: center;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($donHangs as $dh)
                    @php
                        $isFailedOrder = ($dh->co_hang_hoan == 1 || $dh->giaoHangThatBai->isNotEmpty());
                        $failRecord    = $dh->giaoHangThatBai->first();
                        $hangHoan      = $dh->hangHoan;
                    @endphp
                    <tr id="row-{{ $dh->ma_hoa_don }}">
                        {{-- Mã ĐH & Tóm tắt hàng --}}
                        <td style="white-space: nowrap;">
                            <span class="rs-code-order">#HD-{{ str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</span>
                            @foreach($boTheoDon->get($dh->ma_hoa_don, collect()) as $bo)
                                <div style="font-size: 11px; color: #667085; white-space: nowrap;">{{ $bo->ten_bo_hoa }} × {{ $bo->so_luong }} · {{ $bo->size }}</div>
                            @endforeach
                            @foreach($dh->chiTietHoaDon->take(2) as $ct)
                                @if($ct->sanPham)
                                    <div style="font-size: 11px; color: #667085; white-space: nowrap;">{{ $ct->sanPham->ten_san_pham }} × {{ $ct->so_luong }}</div>
                                @endif
                            @endforeach
                        </td>

                        {{-- Ngày đặt --}}
                        <td style="white-space: nowrap; font-size: 12.5px; color: #667085;">
                            {{ $dh->ngay_dat ? $dh->ngay_dat->format('d/m/Y H:i') : '—' }}
                        </td>

                        {{-- Khách hàng --}}
                        <td style="white-space: nowrap;">
                            <div style="font-weight: 500; color: #101828;">{{ $dh->khachHang?->ten_khach_hang ?? 'Khách vãng lai' }}</div>
                            <div style="font-size: 12px; color: #667085; font-family: monospace;">{{ $dh->so_dien_thoai ?? $dh->khachHang?->so_dien_thoai ?? '—' }}</div>
                        </td>

                        {{-- Tổng tiền --}}
                        <td style="white-space: nowrap; font-weight: 700; color: #101828;">
                            {{ number_format($dh->tong_tien, 0, ',', '.') }}₫
                        </td>

                        {{-- Thanh toán --}}
                        <td style="white-space: nowrap;">
                            <div style="font-size: 12px; font-weight: 500; color: #475467; margin-bottom: 2px;">
                                {{ $dh->phuong_thuc_thanh_toan ?? 'COD' }}
                            </div>
                            <span class="{{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'rs-badge-paid' : 'rs-badge-unpaid' }}">
                                <i class="bi {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'bi-check-circle' : 'bi-clock' }}"></i>
                                {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'Đã TT' : 'Chưa TT' }}
                            </span>
                        </td>

                        {{-- Shipper / Chọn shipper --}}
                        <td style="white-space: nowrap;">
                            @if($dh->trang_thai === 'PENDING')
                                <select class="shipper-select" id="shipper-{{ $dh->ma_hoa_don }}" data-id="{{ $dh->ma_hoa_don }}">
                                    <option value="">— Chọn shipper —</option>
                                    @foreach($shippers as $sp)
                                        <option value="{{ $sp->ma_nhan_vien }}" data-ten="{{ $sp->ten_nhan_vien }}">
                                            {{ $sp->ten_nhan_vien }} ({{ $sp->so_don_live }}/10)
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div style="font-weight: 500; color: #344054;">
                                    {{ $dh->shipper?->ten_nhan_vien ?? ($failRecord?->shipper?->ten_nhan_vien ?? 'Chưa phân công') }}
                                </div>
                                @if($dh->shipper?->so_dien_thoai)
                                    <div style="font-size: 11.5px; color: #667085; font-family: monospace;">{{ $dh->shipper->so_dien_thoai }}</div>
                                @endif
                            @endif
                        </td>

                        {{-- Cột bổ sung theo Tab --}}
                        @if($tab === 'shipping')
                            <td style="white-space: nowrap; font-size: 12.5px; color: #667085;">
                                {{ $dh->ngay_bat_giao ? $dh->ngay_bat_giao->format('d/m/Y H:i') : 'Đang xử lý lấy hàng' }}
                            </td>
                        @elseif($tab === 'failed')
                            {{-- Nguồn lỗi --}}
                            <td style="white-space: nowrap;">
                                @if($failRecord && $failRecord->loai_loi === 'CUSTOMER')
                                    <span class="rs-badge rs-badge-failed">
                                        <i class="bi bi-person-x"></i> Khách hàng
                                    </span>
                                @elseif($failRecord && $failRecord->loai_loi === 'SHIPPER')
                                    <span class="rs-badge rs-badge-failed">
                                        <i class="bi bi-truck"></i> Shipper
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Lý do thất bại --}}
                            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $failRecord?->ly_do }}">
                                {{ $failRecord?->ly_do ?? '—' }}
                            </td>

                            {{-- Trạng thái Hàng hoàn --}}
                            <td style="white-space: nowrap;">
                                @if($hangHoan)
                                    @switch($hangHoan->trang_thai)
                                        @case('CHO_MANG_VE_KHO')
                                            <span class="rs-badge rs-badge-return-waiting">Chờ mang về kho</span>
                                            @break
                                        @case('DA_NHAN_TAI_KHO')
                                            <span class="rs-badge rs-badge-return-received">Đã nhận tại kho</span>
                                            @break
                                        @case('DANG_KIEM_TRA')
                                            <span class="rs-badge rs-badge-return-checking">Đang kiểm tra</span>
                                            @break
                                        @case('DA_KIEM_TRA')
                                            <span class="rs-badge rs-badge-return-done">Đã kiểm tra</span>
                                            @break
                                        @default
                                            <span class="rs-badge rs-badge-cancelled">{{ $hangHoan->trang_thai }}</span>
                                    @endswitch
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @elseif($tab === 'cancelled')
                            <td style="white-space: nowrap; font-size: 12.5px; color: #667085;">
                                {{ $dh->thoi_diem_huy ? $dh->thoi_diem_huy->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $dh->ly_do_huy }}">
                                {{ $dh->ly_do_huy ?? '—' }}
                            </td>
                        @endif

                        {{-- Trạng thái --}}
                        <td style="white-space: nowrap;">
                            @if($isFailedOrder)
                                <span class="rs-badge rs-badge-failed">
                                    <i class="bi bi-x-octagon"></i> Giao thất bại
                                </span>
                            @else
                                @switch($dh->trang_thai)
                                    @case('PENDING')
                                        <span class="rs-badge rs-badge-pending">
                                            <i class="bi bi-clock"></i> Chờ duyệt
                                        </span>
                                        @break
                                    @case('CONFIRMED')
                                        <span class="rs-badge rs-badge-confirmed">
                                            <i class="bi bi-check2"></i> Đã xác nhận
                                        </span>
                                        @break
                                    @case('SHIPPING')
                                        <span class="rs-badge rs-badge-shipping">
                                            <i class="bi bi-truck"></i> Đang giao
                                        </span>
                                        @break
                                    @case('DELIVERED')
                                        <span class="rs-badge rs-badge-delivered">
                                            <i class="bi bi-check-all"></i> Đã giao
                                        </span>
                                        @break
                                    @case('CANCELLED')
                                        <span class="rs-badge rs-badge-cancelled">
                                            <i class="bi bi-slash-circle"></i> Đã hủy
                                        </span>
                                        @break
                                    @default
                                        <span class="rs-badge rs-badge-cancelled">{{ $dh->trang_thai }}</span>
                                @endswitch
                            @endif
                        </td>

                        {{-- Thao tác --}}
                        <td style="white-space: nowrap; text-align: center;">
                            @if($dh->trang_thai === 'PENDING')
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                    <button class="rs-order-btn btn-success"
                                            onclick="confirmOrder({{ $dh->ma_hoa_don }})"
                                            title="Xác nhận đơn hàng (Yêu cầu chọn shipper)">
                                        <i class="bi bi-check-lg" style="font-size: 16px; line-height: 1;"></i>
                                    </button>
                                    <button class="rs-order-btn btn-danger"
                                            onclick="openCancelDialog({{ $dh->ma_hoa_don }}, 'HD-{{ str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}')"
                                            title="Từ chối đơn hàng">
                                        <i class="bi bi-x-lg" style="font-size: 13px; line-height: 1;"></i>
                                    </button>
                                </div>
                            @elseif($isFailedOrder)
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    @if($hangHoan && $hangHoan->trang_thai === 'DA_NHAN_TAI_KHO')
                                        <a href="{{ route('admin.hoa-don.kiem-tra-hang', $dh->ma_hoa_don) }}"
                                           class="rs-btn-action rs-btn-action-primary"
                                           title="Kiểm tra chất lượng hàng hoàn">
                                            <i class="bi bi-clipboard-check"></i> Kiểm tra hàng
                                        </a>
                                    @elseif($hangHoan && $hangHoan->trang_thai === 'DA_KIEM_TRA')
                                        <button type="button"
                                                onclick="openFailedDetail({{ $dh->ma_hoa_don }})"
                                                class="rs-btn-action rs-btn-action-info"
                                                title="Xem kết quả kiểm tra chất lượng">
                                            <i class="bi bi-eye"></i> Xem kết quả
                                        </button>
                                    @endif

                                    <button type="button"
                                            onclick="openFailedDetail({{ $dh->ma_hoa_don }})"
                                            class="rs-btn-action rs-btn-action-ghost"
                                            title="Chi tiết xử lý giao thất bại">
                                        <i class="bi bi-info-circle"></i> Chi tiết
                                    </button>
                                </div>
                            @else
                                <a href="{{ route('hoa-don.show', $dh->ma_hoa_don) }}"
                                   class="rs-btn-action rs-btn-action-ghost"
                                   title="Xem chi tiết hóa đơn">
                                    <i class="bi bi-eye"></i> Chi tiết
                                </a>
                            @endif

                            {{-- Embed JSON data for failed delivery modal --}}
                            @if($isFailedOrder)
                                <script type="application/json" id="failed-data-{{ $dh->ma_hoa_don }}">
                                {!! json_encode([
                                    'ma_hoa_don'     => $dh->ma_hoa_don,
                                    'ma_hd_code'     => '#HD-'.str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT),
                                    'tong_tien'      => number_format($dh->tong_tien, 0, ',', '.').'₫',
                                    'shipper_ten'    => $failRecord?->shipper?->ten_nhan_vien ?? ($dh->shipper?->ten_nhan_vien ?? '—'),
                                    'shipper_sdt'    => $failRecord?->shipper?->so_dien_thoai ?? ($dh->shipper?->so_dien_thoai ?? '—'),
                                    'thoi_diem_bao'  => $failRecord?->thoi_diem_bao ? $failRecord->thoi_diem_bao->format('d/m/Y H:i') : '—',
                                    'loai_loi'       => $failRecord?->loai_loi ?? '—',
                                    'ly_do'          => $failRecord?->ly_do ?? '—',
                                    'ghi_chu'        => $failRecord?->ghi_chu ?? '',
                                    'khach_hang_ten' => $dh->khachHang?->ten_khach_hang ?? '—',
                                    'khach_hang_sdt' => $dh->so_dien_thoai ?? ($dh->khachHang?->so_dien_thoai ?? '—'),
                                    'quyen_cod'      => $dh->khachHang?->quyen_cod === 'GIOI_HAN' ? 'Đang khóa / Giới hạn' : 'Bình thường',
                                    'vi_pham_count'  => $dh->khachHang ? $dh->khachHang->viPhamGiaoHang->count() : 0,
                                    'is_admin'       => (Auth::check() && Auth::user()->vai_tro === 'ADMIN'),
                                    'phat_shipper'   => $dh->phatShipper->map(fn($p) => [
                                        'so_tien'                 => number_format($p->so_tien, 0, ',', '.').'₫',
                                        'tien_hang_hong'          => number_format($p->tien_hang_hong, 0, ',', '.').'₫',
                                        'tien_hao_hut_flash_sale' => number_format($p->tien_hao_hut_flash_sale, 0, ',', '.').'₫',
                                        'ly_do'                   => $p->ly_do,
                                        'trang_thai'              => $p->trang_thai,
                                    ]),
                                    'hang_hoan_trang_thai' => $hangHoan?->trang_thai ?? 'CHUA_TAO',
                                    'ngay_nhan_kho'        => $hangHoan?->thoi_diem_nhan_tai_kho ? $hangHoan->thoi_diem_nhan_tai_kho->format('d/m/Y H:i') : null,
                                    'ngay_kiem_tra'        => $hangHoan?->thoi_diem_kiem_tra ? $hangHoan->thoi_diem_kiem_tra->format('d/m/Y H:i') : null,
                                    'nguoi_kiem_tra'       => $hangHoan?->nguoiKiemTra?->ho_ten ?? ($hangHoan?->nguoiKiemTra?->ten_dang_nhap ?? '—'),
                                    'kiem_tra_url'         => route('admin.hoa-don.kiem-tra-hang', $dh->ma_hoa_don),
                                    'chi_tiet_hang_hoan'   => $hangHoan ? $hangHoan->chiTiet->map(fn($ct) => [
                                        'ten'                   => $ct->boHoa?->ten_bo_hoa ?? ($ct->sanPham?->ten_san_pham ?? 'Sản phẩm'),
                                        'so_luong_theo_don'     => $ct->so_luong_theo_don,
                                        'so_luong_con_ban_duoc' => $ct->so_luong_con_ban_duoc,
                                        'so_luong_hong'         => $ct->so_luong_hong,
                                        'ket_qua'               => $ct->ket_qua,
                                        'ghi_chu'               => $ct->ghi_chu,
                                    ]) : [],
                                ]) !!}
                                </script>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ in_array($tab, ['shipping', 'cancelled']) ? 9 : ($tab === 'failed' ? 10 : 8) }}">
                            <div class="empty-state" style="text-align: center; padding: 48px 24px; color: #667085;">
                                <div style="font-size: 32px; margin-bottom: 8px;">◻</div>
                                <p class="mb-0 fw-semibold">Không tìm thấy đơn hàng nào phù hợp với bộ lọc hiện tại.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang chuẩn RoseShop --}}
        @if($donHangs->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span>Số hàng mỗi trang:</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage, 'page' => 1]) }}"
                                        {{ (request('per_page', $donHangs->perPage()) == $perPage) ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rs-records-count">
                        {{ $donHangs->firstItem() ?? 0 }}-{{ $donHangs->lastItem() ?? 0 }} / {{ $donHangs->total() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($donHangs->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $donHangs->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $donHangs->currentPage();
                            $lastPage = $donHangs->lastPage();
                            $pages = [];

                            if ($lastPage <= 5) {
                                for ($i = 1; $i <= $lastPage; $i++) { $pages[] = $i; }
                            } else {
                                $pages[] = 1;
                                if ($currentPage > 3) { $pages[] = '...'; }
                                $start = max(2, $currentPage - 1);
                                $end   = min($lastPage - 1, $currentPage + 1);
                                for ($i = $start; $i <= $end; $i++) { $pages[] = $i; }
                                if ($currentPage < $lastPage - 2) { $pages[] = '...'; }
                                $pages[] = $lastPage;
                            }
                        @endphp

                        @foreach ($pages as $p)
                            @if ($p === '...')
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            @elseif ($p == $currentPage)
                                <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $donHangs->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($donHangs->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $donHangs->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

    </div>

    {{-- ════════ DIALOG XÁC NHẬN TỪ CHỐI ════════ --}}
    <div class="overlay" id="cancelOverlay">
        <div class="dialog">
            <h3>Xác nhận từ chối đơn hàng</h3>
            <p id="cancelMsg">Bạn có chắc muốn từ chối đơn hàng này?<br>
                Trạng thái sẽ chuyển sang <strong>CANCELLED</strong> và giải phóng hàng tồn đang giữ.<br>
                Hành động này không thể hoàn tác.
            </p>
            <div class="dialog-actions">
                <label style="font-size: 13px; font-weight: 500; color: #344054; margin-bottom: 6px;">
                    Lý do từ chối (không bắt buộc)
                </label>
                <textarea id="cancelReason" maxlength="1000" placeholder="Nhập lý do từ chối đơn..."></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                    <button class="btn btn-ghost" onclick="closeCancelDialog()" style="background:#F2F4F7; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:500; color:#344054; cursor:pointer;">Hủy bỏ</button>
                    <button class="btn btn-danger" id="cancelOkBtn" style="background:#B42318; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:600; color:#ffffff; cursor:pointer;">✕ Xác nhận từ chối</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ MODAL CHI TIẾT XỬ LÝ GIAO THẤT BẠI ════════ --}}
    <div class="overlay" id="failedModalOverlay">
        <div class="rs-drawer" id="failedModalDrawer">
            <div class="rs-drawer-header">
                <div class="rs-drawer-title">
                    <span id="fmOrderCode" style="color: #EC3D8F; font-family: monospace;">#HD-0000</span>
                    <span>— XỬ LÝ GIAO THẤT BẠI</span>
                </div>
                <button type="button" onclick="closeFailedDetail()" style="background: none; border: none; font-size: 20px; color: #667085; cursor: pointer;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- 1. TIMELINE --}}
            <div class="rs-section-title">
                <i class="bi bi-diagram-3"></i> VÒNG ĐỜI XỬ LÝ HÀNG HOÀN
            </div>
            <div class="rs-timeline" id="fmTimeline">
                {{-- Dynamic via JS --}}
            </div>

            {{-- 2. GIAO HÀNG & NGUỒN LỖI --}}
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div style="background: #F8F9FC; border: 1px solid #EAECF0; border-radius: 10px; padding: 14px;">
                        <div class="rs-section-title" style="margin-bottom: 8px;">
                            <i class="bi bi-truck"></i> GIAO HÀNG
                        </div>
                        <div style="font-size: 13px; line-height: 1.6;">
                            <div><strong>Shipper:</strong> <span id="fmShipper"></span></div>
                            <div><strong>Số điện thoại:</strong> <span id="fmShipperPhone"></span></div>
                            <div><strong>Thời gian thất bại:</strong> <span id="fmFailTime"></span></div>
                            <div><strong>Nguồn lỗi:</strong> <span id="fmSourceBadge"></span></div>
                            <div><strong>Lý do:</strong> <span id="fmReason" style="color: #B42318;"></span></div>
                            <div id="fmNoteRow" style="display: none;"><strong>Ghi chú:</strong> <span id="fmNote"></span></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div style="background: #F8F9FC; border: 1px solid #EAECF0; border-radius: 10px; padding: 14px;">
                        <div class="rs-section-title" style="margin-bottom: 8px;">
                            <i class="bi bi-shield-check"></i> TRÁCH NHIỆM & CHẾ TÀI
                        </div>
                        <div id="fmResponsibilityContent" style="font-size: 13px; line-height: 1.6;">
                            {{-- Dynamic --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. HÀNG HOÀN & KẾT QUẢ KIỂM TRA --}}
            <div style="border-top: 1px solid #EAECF0; padding-top: 16px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="rs-section-title mb-0">
                        <i class="bi bi-clipboard2-check"></i> KIỂM TRA CHẤT LƯỢNG HÀNG HOÀN
                    </div>
                    <div id="fmInspectActionSlot"></div>
                </div>
                <div id="fmInspectionInfo" style="font-size: 13px; margin-bottom: 12px; color: #475467;"></div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size: 12.5px; border-color: #EAECF0;">
                        <thead style="background-color: #F8F9FC;">
                            <tr>
                                <th>Mặt hàng</th>
                                <th style="text-align: center;">SL Đơn</th>
                                <th style="text-align: center; color: #027A48;">SL Còn tốt</th>
                                <th style="text-align: center; color: #B42318;">SL Hỏng</th>
                                <th>Kết quả / Hướng xử lý</th>
                            </tr>
                        </thead>
                        <tbody id="fmItemsTableBody">
                            {{-- Dynamic --}}
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeFailedDetail()" class="rs-btn-action rs-btn-action-ghost" style="padding: 8px 18px; font-size: 13px;">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    {{-- ════════ TOAST NOTIFICATION ════════ --}}
    <div class="toast" id="toast"></div>

</div>

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let currentCancelId = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className   = 'toast ' + type + ' show';
        clearTimeout(t._t);
        t._t = setTimeout(() => t.classList.remove('show'), 3500);
    }

    function removeRow(id) {
        const row = document.getElementById('row-' + id);
        if (!row) return;
        row.style.transition = 'opacity .35s, transform .35s';
        row.style.opacity    = '0';
        row.style.transform  = 'translateX(16px)';
        setTimeout(() => row.remove(), 370);
    }

    function updateShipperCount(maNv, soDon) {
        document.querySelectorAll('.shipper-select').forEach(sel => {
            const opt = sel.querySelector(`option[value="${maNv}"]`);
            if (!opt) return;
            if (soDon >= 10) {
                if (sel.value == maNv) sel.value = '';
                opt.remove();
            } else {
                opt.textContent = `${opt.dataset.ten} (${soDon}/10)`;
            }
        });
    }

    function confirmOrder(id) {
        const sel = document.getElementById('shipper-' + id);

        if (!sel || !sel.value) {
            sel && sel.classList.add('error');
            showToast('Vui lòng chọn shipper trước khi xác nhận!', 'error');
            sel && sel.focus();
            return;
        }
        sel.classList.remove('error');

        const btn = document.querySelector('#row-' + id + ' .btn-success');
        if (btn) { btn.disabled = true; btn.textContent = '…'; }

        fetch(`/don-hang/${id}/confirm`, {
            method:  'POST',
            headers: {
                'Content-Type':    'application/json',
                'X-CSRF-TOKEN':    CSRF,
                'Accept':          'application/json',
                'X-Requested-With':'XMLHttpRequest',
            },
            body: JSON.stringify({ ma_nhan_vien_giao: sel.value }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (res.shipper) updateShipperCount(res.shipper.ma_nhan_vien, res.shipper.so_don);
                removeRow(id);
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Có lỗi xảy ra.', 'error');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i>'; }
            }
        })
        .catch(() => {
            showToast('Lỗi kết nối, vui lòng thử lại.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i>'; }
        });
    }

    function openCancelDialog(id, label) {
        document.getElementById('cancelReason').value = '';
        currentCancelId = id;
        document.getElementById('cancelMsg').innerHTML =
            `Bạn có chắc muốn từ chối <strong>${label}</strong>?<br>
             Trạng thái sẽ chuyển sang <strong>CANCELLED</strong> và giải phóng hàng tồn đang giữ.<br>
             Hành động này không thể hoàn tác.`;
        document.getElementById('cancelOverlay').classList.add('show');
    }

    function closeCancelDialog() {
        document.getElementById('cancelOverlay').classList.remove('show');
        currentCancelId = null;
    }

    document.getElementById('cancelOkBtn').addEventListener('click', function () {
        if (!currentCancelId) return;
        const id  = currentCancelId;
        this.disabled    = true;
        this.textContent = '…';

        fetch(`/don-hang/${id}/cancel`, {
            method:  'POST',
            body: new URLSearchParams({ly_do: document.getElementById('cancelReason').value}),
            headers: {
                'X-CSRF-TOKEN':    CSRF,
                'Accept':          'application/json',
                'X-Requested-With':'XMLHttpRequest',
            },
        })
        .then(r => r.json())
        .then(res => {
            closeCancelDialog();
            this.disabled    = false;
            this.textContent = '✕ Xác nhận từ chối';

            if (res.success) {
                removeRow(id);
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Có lỗi xảy ra.', 'error');
            }
        })
        .catch(() => {
            closeCancelDialog();
            this.disabled    = false;
            this.textContent = '✕ Xác nhận từ chối';
            showToast('Lỗi kết nối, vui lòng thử lại.', 'error');
        });
    });

    document.getElementById('cancelOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeCancelDialog();
    });

    document.querySelectorAll('.shipper-select').forEach(sel => {
        sel.addEventListener('change', () => sel.classList.remove('error'));
    });

    // ─── FAILED DETAIL MODAL LOGIC ───
    function openFailedDetail(orderId) {
        const raw = document.getElementById('failed-data-' + orderId);
        if (!raw) return;

        let data = {};
        try {
            data = JSON.parse(raw.textContent);
        } catch (e) {
            console.error('Invalid JSON failed data', e);
            return;
        }

        document.getElementById('fmOrderCode').textContent = data.ma_hd_code;
        document.getElementById('fmShipper').textContent   = data.shipper_ten;
        document.getElementById('fmShipperPhone').textContent = data.shipper_sdt;
        document.getElementById('fmFailTime').textContent  = data.thoi_diem_bao;
        document.getElementById('fmReason').textContent    = data.ly_do;

        if (data.ghi_chu) {
            document.getElementById('fmNote').textContent = data.ghi_chu;
            document.getElementById('fmNoteRow').style.display = 'block';
        } else {
            document.getElementById('fmNoteRow').style.display = 'none';
        }

        // Source badge
        const srcBadge = document.getElementById('fmSourceBadge');
        if (data.loai_loi === 'CUSTOMER') {
            srcBadge.className = 'rs-badge rs-badge-failed';
            srcBadge.innerHTML = '<i class="bi bi-person-x"></i> Khách hàng';
        } else if (data.loai_loi === 'SHIPPER') {
            srcBadge.className = 'rs-badge rs-badge-failed';
            srcBadge.innerHTML = '<i class="bi bi-truck"></i> Shipper';
        } else {
            srcBadge.className = 'text-muted';
            srcBadge.textContent = '—';
        }

        // Responsibility & sanction
        const respEl = document.getElementById('fmResponsibilityContent');
        if (data.loai_loi === 'CUSTOMER') {
            respEl.innerHTML = `
                <div><strong>Trách nhiệm:</strong> <span style="color:#B42318; font-weight:600;">Khách hàng</span></div>
                <div><strong>Khách hàng:</strong> ${data.khach_hang_ten} (${data.khach_hang_sdt})</div>
                <div><strong>Vi phạm chưa xử lý:</strong> <span class="badge ${data.vi_pham_count > 0 ? 'bg-danger' : 'bg-secondary'}">${data.vi_pham_count}</span></div>
                <div><strong>Trạng thái COD:</strong> <span style="font-weight:600;">${data.quyen_cod}</span></div>
            `;
        } else if (data.loai_loi === 'SHIPPER') {
            let fineHtml = '';
            if (data.is_admin) {
                if (data.phat_shipper && data.phat_shipper.length > 0) {
                    fineHtml = data.phat_shipper.map(p => `
                        <div style="background:#FFF; border:1px solid #FECDCA; border-radius:6px; padding:6px 10px; margin-top:6px;">
                            <div><strong>Tiền phạt:</strong> <span style="color:#B42318; font-weight:700;">${p.so_tien}</span> (${p.trang_thai})</div>
                            <div style="font-size:11.5px; color:#667085;">Hàng hỏng: ${p.tien_hang_hong} · Hao hụt Flash Sale: ${p.tien_hao_hut_flash_sale}</div>
                            <div style="font-size:11.5px; color:#475467;">Lý do: ${p.ly_do}</div>
                        </div>
                    `).join('');
                } else {
                    fineHtml = '<div class="text-muted mt-1">Chưa tạo khoản phạt cho shipper.</div>';
                }
            } else {
                fineHtml = '<div class="text-muted mt-1" style="font-style:italic;">Thông tin phạt và chế tài do Quản trị viên quản lý.</div>';
            }

            respEl.innerHTML = `
                <div><strong>Trách nhiệm:</strong> <span style="color:#B42318; font-weight:600;">Shipper</span></div>
                <div><strong>Shipper:</strong> ${data.shipper_ten} (${data.shipper_sdt})</div>
                ${fineHtml}
            `;
        } else {
            respEl.innerHTML = '<div class="text-muted">Chưa xác định nguồn lỗi.</div>';
        }

        // Timeline
        const status = data.hang_hoan_trang_thai;
        const isChoMangVe  = status === 'CHO_MANG_VE_KHO';
        const isDaNhan     = status === 'DA_NHAN_TAI_KHO';
        const isDangKiemTra= status === 'DANG_KIEM_TRA';
        const isDaKiemTra  = status === 'DA_KIEM_TRA';

        const step1Class = 'completed';
        const step2Class = (isChoMangVe ? 'current' : (isDaNhan || isDangKiemTra || isDaKiemTra ? 'completed' : ''));
        const step3Class = (isDaNhan ? 'current' : (isDangKiemTra || isDaKiemTra ? 'completed' : ''));
        const step4Class = (isDangKiemTra ? 'current' : (isDaKiemTra ? 'completed' : ''));
        const step5Class = (isDaKiemTra ? 'completed' : '');

        document.getElementById('fmTimeline').innerHTML = `
            <div class="rs-timeline-step ${step1Class}">
                <div class="rs-timeline-dot">✓</div>
                <div class="rs-timeline-label">Giao thất bại</div>
                <div class="rs-timeline-time">${data.thoi_diem_bao}</div>
            </div>
            <div class="rs-timeline-step ${step2Class}">
                <div class="rs-timeline-dot">${isChoMangVe ? '●' : (step2Class === 'completed' ? '✓' : '2')}</div>
                <div class="rs-timeline-label">Chờ mang về kho</div>
            </div>
            <div class="rs-timeline-step ${step3Class}">
                <div class="rs-timeline-dot">${isDaNhan ? '●' : (step3Class === 'completed' ? '✓' : '3')}</div>
                <div class="rs-timeline-label">Kho nhận hàng</div>
                <div class="rs-timeline-time">${data.ngay_nhan_kho || '—'}</div>
            </div>
            <div class="rs-timeline-step ${step4Class}">
                <div class="rs-timeline-dot">${isDangKiemTra ? '●' : (step4Class === 'completed' ? '✓' : '4')}</div>
                <div class="rs-timeline-label">Kiểm tra chất lượng</div>
                <div class="rs-timeline-time">${data.ngay_kiem_tra || '—'}</div>
            </div>
            <div class="rs-timeline-step ${step5Class}">
                <div class="rs-timeline-dot">${isDaKiemTra ? '✓' : '5'}</div>
                <div class="rs-timeline-label">Hoàn tất</div>
            </div>
        `;

        // Inspect button or message
        const actionSlot = document.getElementById('fmInspectActionSlot');
        if (isDaNhan) {
            actionSlot.innerHTML = `
                <a href="${data.kiem_tra_url}" class="rs-btn-action rs-btn-action-primary">
                    <i class="bi bi-clipboard-check"></i> Thực hiện kiểm tra hàng
                </a>
            `;
        } else {
            actionSlot.innerHTML = '';
        }

        // Inspection info
        let infoText = '';
        if (isDaKiemTra) {
            infoText = `<strong>Người kiểm tra:</strong> ${data.nguoi_kiem_tra} · <strong>Ngày kiểm tra:</strong> ${data.ngay_kiem_tra || '—'}`;
        } else if (isDaNhan) {
            infoText = `Hàng đã được tiếp nhận tại kho lúc <strong>${data.ngay_nhan_kho || '—'}</strong>. Vui lòng bấm kiểm tra hàng để phân loại hồi kho, xả Flash Sale hoặc báo hỏng.`;
        } else {
            infoText = `Hàng hoàn đang trong quá trình shipper vận chuyển quay về kho shop.`;
        }
        document.getElementById('fmInspectionInfo').innerHTML = infoText;

        // Items table
        const tbody = document.getElementById('fmItemsTableBody');
        if (data.chi_tiet_hang_hoan && data.chi_tiet_hang_hoan.length > 0) {
            tbody.innerHTML = data.chi_tiet_hang_hoan.map(it => `
                <tr>
                    <td><strong>${it.ten}</strong></td>
                    <td style="text-align: center;">${it.so_luong_theo_don}</td>
                    <td style="text-align: center; font-weight:600; color:#027A48;">${it.so_luong_con_ban_duoc}</td>
                    <td style="text-align: center; font-weight:600; color:#B42318;">${it.so_luong_hong}</td>
                    <td>
                        <span class="badge ${it.ket_qua === 'HOAN_HOAN_THANH' ? 'bg-success' : 'bg-secondary'}">${it.ket_qua || 'Chưa kiểm tra'}</span>
                        ${it.ghi_chu ? `<span class="text-muted ms-1">(${it.ghi_chu})</span>` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Chưa có dữ liệu chi tiết hàng hoàn.</td></tr>`;
        }

        document.getElementById('failedModalOverlay').classList.add('show');
    }

    function closeFailedDetail() {
        document.getElementById('failedModalOverlay').classList.remove('show');
    }

    document.getElementById('failedModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeFailedDetail();
    });
</script>
@endpush