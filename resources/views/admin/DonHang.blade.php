@extends('layouts.admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/Admin/DonHang.css') }}">
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
        padding: 20px 24px;
        border-bottom: 1px solid #EAECF0;
        background: #ffffff;
    }
    .rs-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        align-items: flex-end;
    }
    .rs-filter-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #475467;
        margin-bottom: 6px;
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
    .rs-filter-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px dashed #EAECF0;
    }
    .rs-btn-filter-submit {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 18px;
        background-color: #EC3D8F;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 13.5px;
        cursor: pointer;
        transition: all 0.2s ease;
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
        padding: 0 14px;
        background-color: #F2F4F7;
        color: #344054;
        border: 1px solid #D0D5DD;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .rs-btn-filter-reset:hover {
        background-color: #E4E7EC;
        color: #101828;
    }
    .filter-result-hint {
        font-size: 13px;
        color: #667085;
        margin-left: 6px;
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
        font-size: 13.5px;
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
        padding: 12px 14px;
        font-size: 13.5px;
        color: #344054;
        vertical-align: middle;
        border-bottom: 1px solid #EAECF0;
    }
    .rs-code-order {
        font-family: monospace;
        font-size: 12px;
        font-weight: 700;
        color: #EC3D8F !important;
        white-space: nowrap !important;
    }
    .rs-badge-paid {
        background: #ECFDF3;
        color: #027A48;
        border: 1px solid #ABEFC6;
        padding: 3px 10px;
        border-radius: 12px;
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
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .shipper-select {
        border: 1px solid #D0D5DD !important;
        border-radius: 8px !important;
        height: 36px !important;
        padding: 0 10px !important;
        font-size: 13px !important;
        color: #344054 !important;
        background: #ffffff !important;
        max-width: 160px !important;
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
    .active-page,
    span.current {
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

    /* Modal / Dialog Overlay (Ẩn mặc định, không bị đè cuối trang) */
    .overlay {
        display: none !important;
        position: fixed !important;
        inset: 0 !important;
        background: rgba(0, 0, 0, 0.45) !important;
        z-index: 1050 !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .overlay.show {
        display: flex !important;
    }
    .dialog {
        background: #ffffff !important;
        border-radius: 12px !important;
        padding: 24px 28px !important;
        max-width: 440px !important;
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
    .dialog-actions {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
    }
    .dialog-actions label {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #344054 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
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
    .toast.error {
        background: #B42318 !important;
    }
    .toast.success {
        background: #027A48 !important;
    }
</style>
@endpush

@section('content')

<div class="rs-management-page">

    {{-- Page Header --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div>
                <h1 class="rs-header-title">Quản lý đơn hàng</h1>
                <p class="rs-header-subtitle">Theo dõi, xác nhận đơn hàng và chỉ định shipper giao hàng.</p>
            </div>
        </div>
    </div>

    {{-- Main Unified Card --}}
    <div class="rs-main-card">

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('don-hang.index') }}" class="rs-filter-bar">
            <div class="rs-filter-grid">

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

                {{-- Tổng tiền từ --}}
                <div class="rs-filter-group">
                    <label>Tổng tiền từ (₫)</label>
                    <input type="number" name="tu_tien"
                           placeholder="VD: 100000"
                           value="{{ request('tu_tien') }}"
                           min="0" step="1000">
                </div>

                {{-- Tổng tiền đến --}}
                <div class="rs-filter-group">
                    <label>Tổng tiền đến (₫)</label>
                    <input type="number" name="den_tien"
                           placeholder="VD: 500000"
                           value="{{ request('den_tien') }}"
                           min="0" step="1000">
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

            </div>

            <div class="rs-filter-actions">
                <button type="submit" class="rs-btn-filter-submit"><i class="bi bi-search"></i> Lọc kết quả</button>
                @if(request()->hasAny(['trang_thai_thanh_toan','tu_tien','den_tien','tu_ngay','den_ngay']))
                    <a href="{{ route('don-hang.index') }}" class="rs-btn-filter-reset"><i class="bi bi-arrow-clockwise"></i> Đặt lại</a>
                    <span class="filter-result-hint">
                        Đang lọc — <strong>{{ $donHangs->total() }}</strong> kết quả
                    </span>
                @endif
            <div class="table-meta">
        <span>Tổng: <strong>{{ $donHangs->total() }}</strong> đơn đang chờ duyệt</span>
        <span>Trang {{ $donHangs->currentPage() }} / {{ $donHangs->lastPage() }}</span>
    </div>
    {{-- ⚠️ Cảnh báo không có shipper khả dụng --}}
    @if($shippers->isEmpty())
        <div style="padding:12px;background:#fff3cd;border-radius:6px;margin:0 0 12px 0;color:#856404">
            ⚠️ Không có shipper nào đang ONLINE hoặc tất cả đã đủ 10/10 đơn.
            Vui lòng chờ shipper bật trạng thái hoặc giao bớt đơn.
        </div>
    @endif

            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Mã ĐH</th>
                            <th>Khách Hàng</th>
                            <th>Ngày Đặt</th>
                            <th>Tổng Tiền</th>
                            <th>Thanh Toán</th>
                            <th>Địa Chỉ Giao</th>
                            <th>SĐT</th>
                            <th>Chọn Shipper</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($donHangs as $index => $dh)
                        <tr id="row-{{ $dh->ma_hoa_don }}">
                            {{-- STT --}}
                            <td class="td-mono" style="color:var(--ink-muted)">
                                {{ $donHangs->firstItem() + $index }}
                            </td>

                            {{-- Mã đơn hàng --}}
                            <td class="td-mono">
                                HD-{{ str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}
                                @foreach($boTheoDon->get($dh->ma_hoa_don, collect()) as $bo)
                                    <small style="display:block;white-space:normal">{{ $bo->ten_bo_hoa }} × {{ $bo->so_luong }} · {{ $bo->size }} · {{ $bo->concept }}</small>
                                    @if($bo->loi_nhan)<small style="display:block;white-space:normal">Lời nhắn: {{ $bo->loi_nhan }}</small>@endif
                                @endforeach
                            </td>

                            {{-- Tên khách hàng --}}
                            <td>{{ $dh->khachHang?->ten_khach_hang ?? '—' }}</td>

                            {{-- Ngày đặt --}}
                            <td class="td-mono">
                                {{ $dh->ngay_dat?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            {{-- Tổng tiền --}}
                            <td class="td-mono" style="font-weight:700">
                                {{ number_format($dh->tong_tien, 0, ',', '.') }}₫
                            </td>

                            {{-- Trạng thái thanh toán --}}
                            <td>
                                <span class="badge {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'badge-paid' : 'badge-unpaid' }}">
                                    {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'Đã TT' : 'Chưa TT' }}
                                </span>
                            </td>

                            {{-- Địa chỉ giao (rút gọn, hover xem đầy đủ) --}}
                            <td title="{{ $dh->dia_chi_giao }}" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $dh->dia_chi_giao ?? '—' }}
                            </td>

                            {{-- Số điện thoại --}}
                            <td class="td-mono">{{ $dh->so_dien_thoai ?? '—' }}</td>

                            {{-- Dropdown chọn shipper ──────────────────────────────
                                 Bắt buộc phải chọn trước khi ấn "Xác nhận".
                                 ngay_giao sẽ null cho đến khi shipper hoàn tất giao.
                            --}}
                            <td>
                                <select class="shipper-select"
                            id="shipper-{{ $dh->ma_hoa_don }}"
                    data-id="{{ $dh->ma_hoa_don }}">
                     <option value="">— Chọn shipper —</option>
                         @foreach($shippers as $sp)
                      <option value="{{ $sp->ma_nhan_vien }}" data-ten="{{ $sp->ten_nhan_vien }}">
                      {{ $sp->ten_nhan_vien }} ({{ $sp->so_don_live }}/10)
                       </option>
                             @endforeach
                            </select>
                            </td>

                            {{-- Hành động --}}
                            <td>
                                <div class="td-actions">
                                    <button class="btn btn-success"
                                            onclick="confirmOrder({{ $dh->ma_hoa_don }})"
                                            title="Phải chọn shipper trước. Xác nhận → trạng thái chuyển CONFIRMED.">
                                        ✔ Xác nhận
                                    </button>
                                    <button class="btn btn-danger"
                                            onclick="openCancelDialog({{ $dh->ma_hoa_don }}, 'HD-{{ str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}')"
                                            title="Từ chối → trạng thái chuyển CANCELLED.">
                                        ✕ Từ chối
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <div class="empty-icon">◻</div>
                                    <p>Không có đơn hàng nào đang chờ xử lý.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </form>

        {{-- Card Title Bar --}}
        <div class="rs-card-title-bar">
            <div class="rs-card-title">
                <i class="bi bi-list-ul"></i>
                Danh sách đơn hàng chờ duyệt
                <span class="rs-count-badge">{{ $donHangs->total() }}</span>
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
                        <th style="white-space: nowrap;">Mã ĐH</th>
                        <th style="white-space: nowrap;">Khách Hàng</th>
                        <th style="white-space: nowrap;">Ngày Đặt</th>
                        <th style="white-space: nowrap;">Tổng Tiền</th>
                        <th style="white-space: nowrap;">Thanh Toán</th>
                        <th>Địa Chỉ Giao</th>
                        <th style="white-space: nowrap;">SĐT</th>
                        <th style="white-space: nowrap;">Chọn Shipper</th>
                        <th style="white-space: nowrap; text-align: center;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($donHangs as $index => $dh)
                    <tr id="row-{{ $dh->ma_hoa_don }}">
                        {{-- Mã đơn hàng --}}
                        <td style="white-space: nowrap;">
                            <span class="rs-code-order">#HD-{{ str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</span>
                            @foreach($boTheoDon->get($dh->ma_hoa_don, collect()) as $bo)
                                <div style="font-size: 11px; color: #667085; white-space: nowrap;">{{ $bo->ten_bo_hoa }} × {{ $bo->so_luong }} · {{ $bo->size }}</div>
                                @if($bo->loi_nhan)<div style="font-size: 11px; color: #94a3b8; white-space: nowrap;">Lời nhắn: {{ $bo->loi_nhan }}</div>@endif
                            @endforeach
                        </td>

                        {{-- Tên khách hàng --}}
                        <td style="white-space: nowrap; font-weight: 500;">{{ $dh->khachHang?->ten_khach_hang ?? '—' }}</td>

                        {{-- Ngày đặt --}}
                        <td style="white-space: nowrap; font-size: 12.5px; color: #667085;">
                            {{ $dh->ngay_dat?->format('d/m/Y H:i') ?? '—' }}
                        </td>

                        {{-- Tổng tiền --}}
                        <td style="white-space: nowrap; font-weight: 700; color: #101828;">
                            {{ number_format($dh->tong_tien, 0, ',', '.') }}₫
                        </td>

                        {{-- Trạng thái thanh toán --}}
                        <td style="white-space: nowrap;">
                            <span class="{{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'rs-badge-paid' : 'rs-badge-unpaid' }}">
                                <i class="bi {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'bi-check-circle' : 'bi-clock' }}"></i>
                                {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'Đã TT' : 'Chưa TT' }}
                            </span>
                        </td>

                        {{-- Địa chỉ giao (rút gọn, hover xem đầy đủ) --}}
                        <td title="{{ $dh->dia_chi_giao }}" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $dh->dia_chi_giao ?? '—' }}
                        </td>

                        {{-- Số điện thoại --}}
                        <td style="white-space: nowrap; font-family: monospace; font-size: 12.5px;">{{ $dh->so_dien_thoai ?? '—' }}</td>

                        {{-- Dropdown chọn shipper --}}
                        <td style="white-space: nowrap;">
                            <select class="shipper-select"
                                    id="shipper-{{ $dh->ma_hoa_don }}"
                                    data-id="{{ $dh->ma_hoa_don }}">
                                <option value="">— Chọn shipper —</option>
                                @foreach($shippers as $sp)
                                    <option value="{{ $sp->ma_nhan_vien }}">
                                        {{ $sp->ten_nhan_vien }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Hành động (Icon-only với khung bo góc) --}}
                        <td style="white-space: nowrap; text-align: center;">
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
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state" style="text-align: center; padding: 48px 24px; color: #667085;">
                                <div class="empty-icon" style="font-size: 32px; margin-bottom: 8px;">◻</div>
                                <p class="mb-0 fw-semibold">Không có đơn hàng nào đang chờ xử lý.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN ROSESHOP --}}
        @if($donHangs->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage]) }}" {{ (request('per_page', $donHangs->perPage()) == $perPage) ? 'selected' : '' }}>
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
                Trạng thái sẽ chuyển sang <strong>CANCELLED</strong>.<br>
                Hành động này không thể hoàn tác.
            </p>
            <div class="dialog-actions">
                <label>Lý do từ chối (không bắt buộc)<textarea id="cancelReason" maxlength="1000"></textarea></label>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px;">
                    <button class="btn btn-ghost" onclick="closeCancelDialog()" style="background:#F2F4F7; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:500; color:#344054; cursor:pointer;">Hủy bỏ</button>
                    <button class="btn btn-danger" id="cancelOkBtn" style="background:#B42318; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:600; color:#ffffff; cursor:pointer;">✕ Xác nhận từ chối</button>
                </div>
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

    
    // Cập nhật số đơn của shipper ở TẤT CẢ dropdown trong trang (không cần reload).
    // Đủ 10/10 thì gỡ shipper khỏi danh sách.
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
                if (btn) { btn.disabled = false; btn.textContent = '✔ Xác nhận'; }
            }
        })
        .catch(() => {
            showToast('Lỗi kết nối, vui lòng thử lại.', 'error');
            if (btn) { btn.disabled = false; btn.textContent = '✔ Xác nhận'; }
        });
    }

   
    function openCancelDialog(id, label) {
        document.getElementById('cancelReason').value = '';
        currentCancelId = id;
        document.getElementById('cancelMsg').innerHTML =
            `Bạn có chắc muốn từ chối <strong>${label}</strong>?<br>
             Trạng thái sẽ chuyển sang <strong>CANCELLED</strong>.<br>
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
</script>
@endpush