@extends('layouts.admin')

@push('styles')
<style>
    .ghfd-page { padding: 24px 32px 48px; background: #f8f9fa; min-height: 100vh; }
    .ghfd-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
    .ghfd-header-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: #FEF3F2; color: #B42318;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ghfd-header-title { font-size: 20px; font-weight: 700; color: #101828; margin: 0 0 4px; }
    .ghfd-header-sub { font-size: 14px; color: #667085; margin: 0; }
    .ghfd-header-actions { margin-left: auto; display: flex; gap: 8px; }

    .ghfd-btn { height: 38px; padding: 0 16px; border-radius: 8px;
                font-size: 13.5px; font-weight: 600; cursor: pointer;
                border: none; text-decoration: none;
                display: inline-flex; align-items: center; gap: 6px; }
    .ghfd-btn-primary { background: #EC3D8F; color: #fff; }
    .ghfd-btn-primary:hover { background: #D82677; color: #fff; }
    .ghfd-btn-ghost { background: #fff; color: #344054; border: 1px solid #D0D5DD; }
    .ghfd-btn-ghost:hover { background: #F9FAFB; color: #101828; }
    .ghfd-btn-warn { background: #F79009; color: #fff; }
    .ghfd-btn-warn:hover { background: #DC6803; color: #fff; }
    .ghfd-btn-danger { background: #B42318; color: #fff; }
    .ghfd-btn-danger:hover { background: #912018; color: #fff; }

    /* Card layout */
    .ghfd-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; }
    @media (max-width: 992px) { .ghfd-grid { grid-template-columns: 1fr; } }

    .ghfd-card {
        background: #fff; border: 1px solid #EAECF0; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(16,24,40,.04); margin-bottom: 20px; overflow: hidden;
    }
    .ghfd-card-header {
        padding: 14px 20px; border-bottom: 1px solid #EAECF0;
        display: flex; align-items: center; gap: 8px;
        font-size: 14.5px; font-weight: 700; color: #101828;
    }
    .ghfd-card-header svg { color: #667085; }
    .ghfd-card-body { padding: 18px 20px; }

    /* Info rows */
    .ghfd-info-row { display: flex; justify-content: space-between; gap: 16px;
                     padding: 8px 0; border-bottom: 1px dashed #F2F4F7; font-size: 13.5px; }
    .ghfd-info-row:last-child { border-bottom: none; }
    .ghfd-info-label { color: #667085; flex-shrink: 0; }
    .ghfd-info-value { color: #101828; text-align: right; word-break: break-word; }
    .ghfd-info-value.mono { font-family: 'Courier New', monospace; font-weight: 600; }
    .ghfd-info-value.bold { font-weight: 700; }
    .ghfd-info-value.red { color: #B42318; font-weight: 700; }
    .ghfd-info-value.green { color: #027A48; font-weight: 700; }

    /* Badges */
    .ghfd-badge {
        display: inline-block; padding: 3px 10px; border-radius: 12px;
        font-size: 11.5px; font-weight: 600; white-space: nowrap;
    }
    .ghfd-badge.red   { background: #FEF3F2; color: #B42318; border: 1px solid #FECDCA; }
    .ghfd-badge.amber { background: #FFFAEB; color: #B54708; border: 1px solid #FEDF89; }
    .ghfd-badge.green { background: #ECFDF3; color: #027A48; border: 1px solid #ABEFC6; }
    .ghfd-badge.gray  { background: #F2F4F7; color: #344054; border: 1px solid #D0D5DD; }
    .ghfd-badge.blue  { background: #EFF8FF; color: #175CD3; border: 1px solid #B2DDFF; }

    /* Product table */
    .ghfd-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .ghfd-table thead th {
        background: #F8F9FA; padding: 10px 12px; text-align: left;
        font-size: 12px; font-weight: 600; color: #475467;
        border-bottom: 1px solid #EAECF0; white-space: nowrap;
    }
    .ghfd-table thead th.right { text-align: right; }
    .ghfd-table thead th.center { text-align: center; }
    .ghfd-table tbody td {
        padding: 12px; border-bottom: 1px solid #EAECF0;
        color: #344054; vertical-align: middle;
    }
    .ghfd-table tbody td.right  { text-align: right; font-family: 'Courier New', monospace; font-weight: 600; }
    .ghfd-table tbody td.center { text-align: center; font-family: 'Courier New', monospace; }
    .ghfd-table tbody tr:last-child td { border-bottom: none; }

    .ghfd-prod-info { display: flex; align-items: center; gap: 10px; }
    .ghfd-prod-thumb {
        width: 44px; height: 44px; border-radius: 8px; object-fit: cover;
        background: #f5f0eb; border: 1px solid #EAECF0; flex-shrink: 0;
    }
    .ghfd-prod-placeholder {
        width: 44px; height: 44px; border-radius: 8px;
        background: #F2F4F7; border: 1px solid #EAECF0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: #98A2B3; flex-shrink: 0;
    }
    .ghfd-prod-name { font-weight: 600; color: #101828; }
    .ghfd-prod-ma { font-size: 11.5px; color: #667085; font-family: monospace; }

    /* Penalty list */
    .ghfd-penalty-item {
        padding: 12px 14px; border: 1px solid #EAECF0; border-radius: 8px;
        margin-bottom: 10px; background: #fff;
    }
    .ghfd-penalty-item:last-child { margin-bottom: 0; }
    .ghfd-penalty-header { display: flex; justify-content: space-between;
                          align-items: center; margin-bottom: 6px; }
    .ghfd-penalty-amount { font-family: 'Courier New', monospace;
                          font-weight: 700; color: #B42318; font-size: 14px; }
    .ghfd-penalty-lydo { font-size: 12.5px; color: #475467; }
    .ghfd-penalty-meta { font-size: 11.5px; color: #94a3b8; margin-top: 4px; }

    /* Violations */
    .ghfd-vp-item {
        padding: 12px 14px; border: 1px solid #FECDCA; border-radius: 8px;
        margin-bottom: 10px; background: #FFFBFA;
    }
    .ghfd-vp-item:last-child { margin-bottom: 0; }
    .ghfd-vp-header { display: flex; justify-content: space-between;
                     align-items: center; margin-bottom: 6px; }
    .ghfd-vp-lydo { font-size: 12.5px; color: #7a271a; }
    .ghfd-vp-meta { font-size: 11.5px; color: #b42318; margin-top: 4px; }

    /* Alert */
    .ghfd-alert {
        padding: 12px 16px; border-radius: 8px; font-size: 13px;
        margin-bottom: 16px; display: flex; gap: 10px; align-items: flex-start;
    }
    .ghfd-alert.info  { background: #EFF8FF; color: #175CD3; border: 1px solid #B2DDFF; }
    .ghfd-alert.warn  { background: #FFFAEB; color: #B54708; border: 1px solid #FEDF89; }
    .ghfd-alert.green { background: #ECFDF3; color: #027A48; border: 1px solid #ABEFC6; }
    .ghfd-alert.red   { background: #FEF3F2; color: #B42318; border: 1px solid #FECDCA; }

    .ghfd-empty-mini { color: #94a3b8; font-size: 13px; text-align: center;
                       padding: 20px; font-style: italic; }
</style>
@endpush

@section('content')
<div class="ghfd-page">

    {{-- Header --}}
    <div class="ghfd-header">
        <div class="ghfd-header-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div>
            <h1 class="ghfd-header-title">
                Chi tiết đơn giao thất bại
                <span style="color:#EC3D8F;">#GHTB-{{ str_pad($giaoHangThatBai->ma_giao_hang_that_bai, 4, '0', STR_PAD_LEFT) }}</span>
            </h1>
            <p class="ghfd-header-sub">
                Đơn #HD-{{ str_pad($hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}
                · {{ optional($giaoHangThatBai->thoi_diem_bao)->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="ghfd-header-actions">
            {{-- Nút "Kiểm tra hàng hoàn" nếu chưa kiểm tra --}}
            @if($hangHoan && $hangHoan->trang_thai !== 'DA_KIEM_TRA')
                <a href="{{ route('admin.hoa-don.kiem-tra-hang', $hoaDon->ma_hoa_don) }}"
                   class="ghfd-btn ghfd-btn-warn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Kiểm tra hàng hoàn
                </a>
            @endif

            {{-- Nút "Xem vi phạm" nếu lỗi CUSTOMER --}}
            @if($giaoHangThatBai->loai_loi === 'CUSTOMER' && $khachHang)
                <a href="{{ route('khach-hang.index', ['keyword' => $khachHang->so_dien_thoai]) }}"
                   class="ghfd-btn ghfd-btn-danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Xem vi phạm
                </a>
            @endif

            {{-- Nút "Xem Flash Sale" nếu có hàng còn bán được --}}
            @if($sanPhamConBan->isNotEmpty())
                <a href="{{ route('admin.hoan-hang.flash-sale.index') }}"
                   class="ghfd-btn ghfd-btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Xem Flash Sale
                </a>
            @endif

            <a href="{{ route('admin.giao-hang.that-bai') }}" class="ghfd-btn ghfd-btn-ghost">
                ← Danh sách
            </a>
        </div>
    </div>

    @php
        $laShipper = $giaoHangThatBai->loai_loi === 'SHIPPER';
    @endphp

    {{-- Banner tổng quan --}}
    @if($daQuyetToan)
        <div class="ghfd-alert {{ $laShipper ? 'red' : 'green' }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0; margin-top:1px;">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <div>
                <strong>Đã quyết toán.</strong>
                Hàng hoàn đã được kiểm tra lúc
                {{ optional($hangHoan?->thoi_diem_kiem_tra)->format('d/m/Y H:i') ?? '—' }}.
                @if($laShipper)
                    Đã tạo phiếu phạt shipper và cộng 20 điểm bồi thường cho khách (nếu lỗi do shipper).
                @else
                    Đã ghi nhận vi phạm cho khách hàng, shipper không bị phạt.
                @endif
            </div>
        </div>
    @else
        <div class="ghfd-alert warn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0; margin-top:1px;">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
                <strong>Chưa quyết toán.</strong>
                Vui lòng vào màn hình <strong>Kiểm tra hàng hoàn</strong> để nhập kết quả kiểm tra và tính phạt.
            </div>
        </div>
    @endif

    <div class="ghfd-grid">

        {{-- ═══ CỘT TRÁI ═══ --}}
        <div>

            {{-- Thông tin đơn --}}
            <div class="ghfd-card">
                <div class="ghfd-card-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Thông tin đơn hàng
                </div>
                <div class="ghfd-card-body">
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Mã hóa đơn</span>
                        <span class="ghfd-info-value mono" style="color:#EC3D8F;">#HD-{{ str_pad($hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Ngày đặt</span>
                        <span class="ghfd-info-value">{{ optional($hoaDon->ngay_dat)->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Tổng tiền đơn</span>
                        <span class="ghfd-info-value mono bold">{{ number_format((float) $hoaDon->tong_tien, 0, ',', '.') }} đ</span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Phương thức TT</span>
                        <span class="ghfd-info-value">
                            @if($hoaDon->phuong_thuc_thanh_toan === 'COD')
                                💵 COD
                            @else
                                💳 Chuyển khoản
                            @endif
                        </span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Trạng thái thanh toán</span>
                        <span class="ghfd-info-value">
                            @if($hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN')
                                <span class="ghfd-badge green">Đã thanh toán</span>
                            @else
                                <span class="ghfd-badge gray">Chưa thanh toán</span>
                            @endif
                        </span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Địa chỉ giao</span>
                        <span class="ghfd-info-value">{{ $hoaDon->dia_chi_giao ?: '—' }}</span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Số điện thoại</span>
                        <span class="ghfd-info-value mono">{{ $hoaDon->so_dien_thoai ?: '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Nguồn lỗi --}}
            <div class="ghfd-card">
                <div class="ghfd-card-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Nguồn lỗi & lý do
                </div>
                <div class="ghfd-card-body">
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Loại lỗi</span>
                        <span class="ghfd-info-value">
                            @if($giaoHangThatBai->loai_loi === 'SHIPPER')
                                <span class="ghfd-badge red">Do shipper</span>
                            @elseif($giaoHangThatBai->loai_loi === 'CUSTOMER')
                                <span class="ghfd-badge amber">Do khách hàng</span>
                            @else
                                <span class="ghfd-badge gray">{{ $giaoHangThatBai->loai_loi }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Lý do chính</span>
                        <span class="ghfd-info-value">{{ $giaoHangThatBai->ly_do ?: '—' }}</span>
                    </div>
                    @if($giaoHangThatBai->ghi_chu)
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Ghi chú</span>
                            <span class="ghfd-info-value">{{ $giaoHangThatBai->ghi_chu }}</span>
                        </div>
                    @endif
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Thời điểm báo</span>
                        <span class="ghfd-info-value">{{ optional($giaoHangThatBai->thoi_diem_bao)->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="ghfd-info-row">
                        <span class="ghfd-info-label">Trạng thái báo cáo</span>
                        <span class="ghfd-info-value">
                            @php
                                $ttBaoText = [
                                    'DA_BAO'          => 'Đã báo',
                                    'CHO_HOAN_HANG'   => 'Chờ hoàn hàng',
                                    'DA_TIEP_NHAN'    => 'Đã tiếp nhận',
                                    'DA_XU_LY'        => 'Đã xử lý',
                                    'DANG_CHO_KHACH'  => 'Đang chờ khách',
                                    'KHACH_DA_NHAN'   => 'Khách đã nhận',
                                ][$giaoHangThatBai->trang_thai] ?? $giaoHangThatBai->trang_thai;
                                $ttBaoBadge = [
                                    'DA_XU_LY'       => 'green',
                                    'KHACH_DA_NHAN'  => 'green',
                                    'DANG_CHO_KHACH' => 'amber',
                                    'CHO_HOAN_HANG'  => 'amber',
                                ][$giaoHangThatBai->trang_thai] ?? 'gray';
                            @endphp
                            <span class="ghfd-badge {{ $ttBaoBadge }}">{{ $ttBaoText }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Hàng hoàn --}}
            <div class="ghfd-card">
                <div class="ghfd-card-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Chi tiết hàng hoàn
                    @if($hangHoan)
                        @php
                            $ttHHText = [
                                'CHO_MANG_VE_KHO' => 'Chờ mang về kho',
                                'DA_NHAN_TAI_KHO' => 'Đã nhận tại kho',
                                'DANG_KIEM_TRA'   => 'Đang kiểm tra',
                                'DA_KIEM_TRA'     => 'Đã kiểm tra',
                            ][$hangHoan->trang_thai] ?? $hangHoan->trang_thai;
                            $ttHHBadge = [
                                'DA_KIEM_TRA'     => 'green',
                                'DA_NHAN_TAI_KHO' => 'blue',
                                'DANG_KIEM_TRA'   => 'amber',
                            ][$hangHoan->trang_thai] ?? 'gray';
                        @endphp
                        <span class="ghfd-badge {{ $ttHHBadge }}" style="margin-left:auto;">{{ $ttHHText }}</span>
                    @endif
                </div>
                <div class="ghfd-card-body" style="padding:0;">
                    @if($dongHangHoan->isEmpty())
                        <div class="ghfd-empty-mini">Chưa có chi tiết hàng hoàn</div>
                    @else
                        <table class="ghfd-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="center">SL đơn</th>
                                    <th class="center">SL hỏng</th>
                                    <th class="center">SL còn bán</th>
                                    <th class="center">Kết quả</th>
                                    <th class="right">Thiệt hại</th>
                                    <th class="right">Hao hụt FS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dongHangHoan as $ct)
                                    <tr>
                                        <td>
                                            <div class="ghfd-prod-info">
                                                @if(!empty($ct['anh']))
                                                    <img src="{{ $ct['anh'] }}" alt="{{ $ct['ten'] }}" class="ghfd-prod-thumb">
                                                @else
                                                    <div class="ghfd-prod-placeholder">🌸</div>
                                                @endif
                                                <div>
                                                    <div class="ghfd-prod-name">{{ $ct['ten'] }}</div>
                                                    <div class="ghfd-prod-ma">
                                                        {{ $ct['la_bo_hoa'] ? 'Bó hoa' : 'Sản phẩm' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="center">{{ $ct['so_luong_theo_don'] }}</td>
                                        <td class="center" style="color:#B42318; font-weight:700;">
                                            {{ $ct['so_luong_hong'] }}
                                        </td>
                                        <td class="center" style="color:#027A48; font-weight:700;">
                                            {{ $ct['so_luong_con_ban_duoc'] }}
                                        </td>
                                        <td class="center">
                                            @php
                                                $kqText = [
                                                    'CHO_KIEM_TRA' => 'Chờ KT',
                                                    'HONG'         => 'Hỏng',
                                                    'FLASH_SALE'   => 'Flash Sale',
                                                    'DA_XU_LY'     => 'Đã xử lý',
                                                ][$ct['ket_qua']] ?? $ct['ket_qua'];
                                                $kqBadge = [
                                                    'HONG'         => 'red',
                                                    'FLASH_SALE'   => 'amber',
                                                    'DA_XU_LY'     => 'green',
                                                    'CHO_KIEM_TRA' => 'gray',
                                                ][$ct['ket_qua']] ?? 'gray';
                                            @endphp
                                            <span class="ghfd-badge {{ $kqBadge }}">{{ $kqText }}</span>
                                        </td>
                                        <td class="right" style="color:#B42318;">
                                            @if($ct['tien_thiet_hai_hang_hong'] !== null)
                                                {{ number_format((float) $ct['tien_thiet_hai_hang_hong'], 0, ',', '.') }} đ
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="right" style="color:#B54708;">
                                            @if($ct['tien_hao_hut_flash_sale'] !== null)
                                                {{ number_format((float) $ct['tien_hao_hut_flash_sale'], 0, ',', '.') }} đ
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            {{-- Tổng kết tiền --}}
            @if($daQuyetToan)
                <div class="ghfd-card">
                    <div class="ghfd-card-header">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Tổng kết quyết toán
                    </div>
                    <div class="ghfd-card-body">
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Tổng thiệt hại hàng hỏng</span>
                            <span class="ghfd-info-value red mono">{{ number_format((float) $tongTien['tong_thiet_hai_hang_hong'], 0, ',', '.') }} đ</span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Tổng hao hụt Flash Sale</span>
                            <span class="ghfd-info-value" style="color:#B54708; font-weight:700;">{{ number_format((float) $tongTien['tong_hao_hut_flash_sale'], 0, ',', '.') }} đ</span>
                        </div>
                        <div class="ghfd-info-row" style="border-top:2px solid #EAECF0; margin-top:6px; padding-top:12px;">
                            <span class="ghfd-info-label" style="font-weight:700; color:#101828;">Shipper chịu</span>
                            <span class="ghfd-info-value red mono bold">{{ number_format((float) $tongTien['tong_shipper_chiu'], 0, ',', '.') }} đ</span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label" style="font-weight:700; color:#101828;">Hệ thống chịu</span>
                            <span class="ghfd-info-value mono bold" style="color:#B54708;">{{ number_format((float) $tongTien['tong_he_thong_chiu'], 0, ',', '.') }} đ</span>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- ═══ CỘT PHẢI ═══ --}}
        <div>

            {{-- Khách hàng --}}
            <div class="ghfd-card">
                <div class="ghfd-card-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Khách hàng
                </div>
                <div class="ghfd-card-body">
                    @if($khachHang)
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Tên</span>
                            <span class="ghfd-info-value bold">{{ $khachHang->ten_khach_hang }}</span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">SĐT</span>
                            <span class="ghfd-info-value mono">{{ $khachHang->so_dien_thoai }}</span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Email</span>
                            <span class="ghfd-info-value">{{ $khachHang->email ?: '—' }}</span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Quyền COD</span>
                            <span class="ghfd-info-value">
                                @if(($khachHang->quyen_cod ?? 'CHO_PHEP') === 'CHO_PHEP')
                                    <span class="ghfd-badge green">✓ Được phép</span>
                                @else
                                    <span class="ghfd-badge red">✕ Bị giới hạn</span>
                                @endif
                            </span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Điểm tích lũy</span>
                            <span class="ghfd-info-value mono">{{ number_format($khachHang->diem_tich_luy ?? 0) }} điểm</span>
                        </div>
                    @else
                        <div class="ghfd-empty-mini">Không tìm thấy khách hàng</div>
                    @endif
                </div>
            </div>

            {{-- Shipper --}}
            <div class="ghfd-card">
                <div class="ghfd-card-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    Shipper
                </div>
                <div class="ghfd-card-body">
                    @if($shipper)
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Tên</span>
                            <span class="ghfd-info-value bold">{{ $shipper->ten_nhan_vien }}</span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">SĐT</span>
                            <span class="ghfd-info-value mono">{{ $shipper->so_dien_thoai ?: '—' }}</span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Chức vụ</span>
                            <span class="ghfd-info-value">
                                <span class="ghfd-badge blue">Shipper</span>
                            </span>
                        </div>
                        <div class="ghfd-info-row">
                            <span class="ghfd-info-label">Tổng phạt tích lũy</span>
                            <span class="ghfd-info-value red mono">{{ number_format((float) ($shipper->tien_phat ?? 0), 0, ',', '.') }} đ</span>
                        </div>
                    @else
                        <div class="ghfd-empty-mini">Không có shipper</div>
                    @endif
                </div>
            </div>

            {{-- Phiếu phạt shipper --}}
            <div class="ghfd-card">
                <div class="ghfd-card-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Phiếu phạt shipper
                    @if($khoanPhat->isNotEmpty())
                        <span class="ghfd-badge gray" style="margin-left:auto;">{{ $khoanPhat->count() }} phiếu</span>
                    @endif
                </div>
                <div class="ghfd-card-body">
                    @if($khoanPhat->isEmpty())
                        <div class="ghfd-empty-mini">
                            @if($laShipper)
                                Chưa có phiếu phạt (chờ quyết toán).
                            @else
                                Lỗi do khách hàng — shipper không bị phạt.
                            @endif
                        </div>
                    @else
                        @foreach($khoanPhat as $phat)
                            @php
                                $ttPhatText = [
                                    'CHUA_THU'  => 'Chưa thu',
                                    'DA_THU'    => 'Đã thu',
                                    'MIEN_PHAT' => 'Miễn phạt',
                                ][$phat->trang_thai] ?? $phat->trang_thai;
                                $ttPhatBadge = [
                                    'CHUA_THU'  => 'amber',
                                    'DA_THU'    => 'green',
                                    'MIEN_PHAT' => 'gray',
                                ][$phat->trang_thai] ?? 'gray';
                            @endphp
                            <div class="ghfd-penalty-item">
                                <div class="ghfd-penalty-header">
                                    <span class="ghfd-penalty-amount">
                                        {{ number_format((float) $phat->so_tien, 0, ',', '.') }} đ
                                    </span>
                                    <span class="ghfd-badge {{ $ttPhatBadge }}">{{ $ttPhatText }}</span>
                                </div>
                                <div class="ghfd-penalty-lydo">{{ $phat->ly_do }}</div>
                                <div class="ghfd-penalty-meta">
                                    Phạt hàng hỏng: {{ number_format((float) $phat->tien_hang_hong, 0, ',', '.') }} đ ·
                                    Hao hụt FS: {{ number_format((float) $phat->tien_hao_hut_flash_sale, 0, ',', '.') }} đ
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Vi phạm khách hàng --}}
            <div class="ghfd-card">
                <div class="ghfd-card-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Vi phạm khách hàng
                    @if($viPham->isNotEmpty())
                        <span class="ghfd-badge red" style="margin-left:auto;">{{ $viPham->count() }} vi phạm</span>
                    @endif
                </div>
                <div class="ghfd-card-body">
                    @if($viPham->isEmpty())
                        <div class="ghfd-empty-mini">
                            @if($laShipper)
                                Lỗi do shipper — khách hàng không bị vi phạm.
                            @else
                                Chưa ghi nhận vi phạm.
                            @endif
                        </div>
                    @else
                        @foreach($viPham as $vp)
                            <div class="ghfd-vp-item">
                                <div class="ghfd-vp-header">
                                    <span class="ghfd-badge red">Lần vi phạm {{ $vp->lan_vi_pham }}</span>
                                    <span class="ghfd-badge
                                        @if($vp->han_che_ap_dung === 'CANH_BAO') amber
                                        @elseif($vp->han_che_ap_dung === 'CAM_COD') red
                                        @else gray
                                        @endif">
                                        @if($vp->han_che_ap_dung === 'CANH_BAO') Cảnh báo
                                        @elseif($vp->han_che_ap_dung === 'CAM_COD') Cấm COD
                                        @else Tạm khóa
                                        @endif
                                    </span>
                                </div>
                                <div class="ghfd-vp-lydo">{{ $vp->ly_do }}</div>
                                <div class="ghfd-vp-meta">
                                    Ghi nhận lúc {{ optional($vp->created_at)->format('d/m/Y H:i') ?? '—' }}
                                    @if($vp->da_go)
                                        · <span style="color:#027A48; font-weight:600;">✓ Đã gỡ</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
@endsection