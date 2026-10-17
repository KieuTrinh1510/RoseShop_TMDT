@extends('layouts.admin')

@push('styles')
<style>
    .ghf-page { padding: 24px 32px 32px; background: #f8f9fa; min-height: 100vh; }
    .ghf-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
    .ghf-header-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: #FEF3F2; color: #B42318;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ghf-header-title { font-size: 20px; font-weight: 700; color: #101828; margin: 0 0 4px; }
    .ghf-header-sub { font-size: 14px; color: #667085; margin: 0; }

    /* KPI */
    .ghf-kpis {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px; margin-bottom: 20px;
    }
    .ghf-kpi {
        background: #fff; border: 1px solid #EAECF0; border-radius: 12px;
        padding: 16px 18px; display: flex; align-items: center; gap: 14px;
        box-shadow: 0 1px 3px rgba(16,24,40,.04);
    }
    .ghf-kpi-icon {
        width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .ghf-kpi-icon.red   { background: #FEF3F2; color: #B42318; }
    .ghf-kpi-icon.green { background: #ECFDF3; color: #027A48; }
    .ghf-kpi-icon.amber { background: #FFFAEB; color: #B54708; }
    .ghf-kpi-icon.blue  { background: #EFF8FF; color: #175CD3; }
    .ghf-kpi-info { display: flex; flex-direction: column; }
    .ghf-kpi-label { font-size: 12.5px; color: #667085; }
    .ghf-kpi-val   { font-size: 19px; font-weight: 700; color: #101828; }

    /* Filter */
    .ghf-filter {
        background: #fff; border: 1px solid #EAECF0; border-radius: 12px;
        padding: 16px 20px; margin-bottom: 20px;
        display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
    }
    .ghf-filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 160px; }
    .ghf-filter-group label { font-size: 12.5px; font-weight: 600; color: #344054; }
    .ghf-filter-group select,
    .ghf-filter-group input {
        height: 38px; padding: 0 12px; border: 1px solid #D0D5DD;
        border-radius: 8px; font-size: 13.5px; background: #fff;
        color: #101828; outline: none;
    }
    .ghf-filter-group select:focus,
    .ghf-filter-group input:focus { border-color: #EC3D8F; box-shadow: 0 0 0 3px rgba(236,61,143,.15); }
    .ghf-btn { height: 38px; padding: 0 18px; border-radius: 8px; font-size: 13.5px;
               font-weight: 600; cursor: pointer; border: none; text-decoration: none;
               display: inline-flex; align-items: center; gap: 6px; }
    .ghf-btn-primary { background: #EC3D8F; color: #fff; }
    .ghf-btn-primary:hover { background: #D82677; }
    .ghf-btn-ghost { background: #F2F4F7; color: #344054; border: 1px solid #D0D5DD; }
    .ghf-btn-ghost:hover { background: #E4E7EC; }

    /* Table */
    .ghf-card { background: #fff; border: 1px solid #EAECF0; border-radius: 12px;
                box-shadow: 0 1px 3px rgba(16,24,40,.04); overflow: hidden; }
    .ghf-table-wrap { width: 100%; overflow-x: auto; }
    .ghf-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .ghf-table thead th {
        background: #F8F9FA; padding: 12px 14px; text-align: left;
        font-size: 12.5px; font-weight: 600; color: #475467;
        border-bottom: 1px solid #EAECF0; white-space: nowrap;
    }
    .ghf-table tbody td {
        padding: 12px 14px; border-bottom: 1px solid #EAECF0;
        color: #344054; vertical-align: middle;
    }
    .ghf-table tbody tr:hover { background: #F9FAFB; }
    .ghf-table tbody tr:last-child td { border-bottom: none; }

    .ghf-badge {
        display: inline-block; padding: 3px 9px; border-radius: 12px;
        font-size: 11.5px; font-weight: 600; white-space: nowrap;
    }
    .ghf-badge.red    { background: #FEF3F2; color: #B42318; border: 1px solid #FECDCA; }
    .ghf-badge.amber  { background: #FFFAEB; color: #B54708; border: 1px solid #FEDF89; }
    .ghf-badge.green  { background: #ECFDF3; color: #027A48; border: 1px solid #ABEFC6; }
    .ghf-badge.gray   { background: #F2F4F7; color: #344054; border: 1px solid #D0D5DD; }
    .ghf-badge.blue   { background: #EFF8FF; color: #175CD3; border: 1px solid #B2DDFF; }

    .ghf-code { font-family: 'Courier New', monospace; font-weight: 700; color: #EC3D8F; font-size: 12.5px; }
    .ghf-money { font-family: 'Courier New', monospace; font-weight: 600; text-align: right; white-space: nowrap; }
    .ghf-money.red { color: #B42318; }
    .ghf-money.green { color: #027A48; }

    .ghf-btn-detail {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 5px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 600;
        background: #EC3D8F; color: #fff; text-decoration: none; border: none; cursor: pointer;
    }
    .ghf-btn-detail:hover { background: #D82677; color: #fff; }

    .ghf-empty {
        text-align: center; padding: 60px 20px; color: #94a3b8;
    }
    .ghf-empty-icon { font-size: 3rem; margin-bottom: 12px; }

    /* Pagination */
    .ghf-pagination { padding: 14px 20px; border-top: 1px solid #EAECF0;
                      display: flex; justify-content: space-between; align-items: center;
                      flex-wrap: wrap; gap: 12px; font-size: 13.5px; }
    .ghf-pagination .pages { display: flex; gap: 4px; }
    .ghf-pagination .pages a,
    .ghf-pagination .pages span {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 34px; height: 34px; padding: 0 10px;
        border-radius: 8px; border: 1px solid #D0D5DD; background: #fff;
        color: #344054; text-decoration: none; font-weight: 500;
    }
    .ghf-pagination .pages .active { background: #EC3D8F; border-color: #EC3D8F; color: #fff; font-weight: 700; }
    .ghf-pagination .pages .disabled { color: #D0D5DD; background: #F9FAFB; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="ghf-page">

    {{-- Header --}}
    <div class="ghf-header">
        <div class="ghf-header-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div>
            <h1 class="ghf-header-title">Đơn giao thất bại</h1>
            <p class="ghf-header-sub">Danh sách các đơn bị hủy do lỗi shipper hoặc khách hàng không nhận hàng</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:10px;padding:12px 18px;margin-bottom:18px;font-size:13.5px;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2;color:#b91c1c;border:1px solid #fecdd3;border-radius:10px;padding:12px 18px;margin-bottom:18px;font-size:13.5px;">
            ✕ {{ session('error') }}
        </div>
    @endif

    {{-- KPI --}}
    @php
        $col = $danhSach->getCollection();
        $soDaXuLy   = $col->where('da_quyet_toan', true)->count();
        $soChuaXuLy = $col->where('da_quyet_toan', false)->count();
        $tongPhatShipper = $col->sum(fn ($g) => (float) ($g->tong_shipper_chiu ?? 0));
        $tongHeThongChiu = $col->sum(fn ($g) => (float) ($g->tong_he_thong_chiu ?? 0));
    @endphp

    <div class="ghf-kpis">
        <div class="ghf-kpi">
            <div class="ghf-kpi-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 9h16"/><path d="M9 4v16"/></svg>
            </div>
            <div class="ghf-kpi-info">
                <span class="ghf-kpi-label">Tổng đơn (trang này)</span>
                <span class="ghf-kpi-val">{{ $danhSach->total() }}</span>
            </div>
        </div>
        <div class="ghf-kpi">
            <div class="ghf-kpi-icon amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="ghf-kpi-info">
                <span class="ghf-kpi-label">Chưa quyết toán</span>
                <span class="ghf-kpi-val">{{ $soChuaXuLy }}</span>
            </div>
        </div>
        <div class="ghf-kpi">
            <div class="ghf-kpi-icon green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="ghf-kpi-info">
                <span class="ghf-kpi-label">Đã quyết toán</span>
                <span class="ghf-kpi-val">{{ $soDaXuLy }}</span>
            </div>
        </div>
        <div class="ghf-kpi">
            <div class="ghf-kpi-icon red">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="ghf-kpi-info">
                <span class="ghf-kpi-label">Tổng phạt shipper (trang này)</span>
                <span class="ghf-kpi-val" style="color:#B42318;">{{ number_format($tongPhatShipper, 0, ',', '.') }} đ</span>
            </div>
        </div>
        <div class="ghf-kpi">
            <div class="ghf-kpi-icon amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v13H3z"/><path d="M8 21h8"/><path d="M12 16v5"/></svg>
            </div>
            <div class="ghf-kpi-info">
                <span class="ghf-kpi-label">Tổng thiệt hại hệ thống</span>
                <span class="ghf-kpi-val" style="color:#B54708;">{{ number_format($tongHeThongChiu, 0, ',', '.') }} đ</span>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.giao-hang.that-bai') }}" class="ghf-filter">
        <div class="ghf-filter-group">
            <label>Loại lỗi</label>
            <select name="loai_loi">
                <option value="">— Tất cả —</option>
                <option value="CUSTOMER" {{ request('loai_loi') === 'CUSTOMER' ? 'selected' : '' }}>Do khách hàng</option>
                <option value="SHIPPER"  {{ request('loai_loi') === 'SHIPPER'  ? 'selected' : '' }}>Do shipper</option>
            </select>
        </div>

        <div class="ghf-filter-group">
            <label>Trạng thái hàng hoàn</label>
            <select name="trang_thai_hang_hoan">
                <option value="">— Tất cả —</option>
                @foreach(['CHO_MANG_VE_KHO' => 'Chờ mang về kho',
                          'DA_NHAN_TAI_KHO' => 'Đã nhận tại kho',
                          'DANG_KIEM_TRA'   => 'Đang kiểm tra',
                          'DA_KIEM_TRA'     => 'Đã kiểm tra'] as $val => $lbl)
                    <option value="{{ $val }}" {{ request('trang_thai_hang_hoan') === $val ? 'selected' : '' }}>
                        {{ $lbl }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="ghf-filter-group">
            <label>Mã đơn hàng</label>
            <input type="number" name="ma_hoa_don" min="1" value="{{ request('ma_hoa_don') }}" placeholder="VD: 42">
        </div>

        <div class="ghf-filter-group">
            <label>Từ ngày</label>
            <input type="date" name="tu_ngay" value="{{ request('tu_ngay') }}">
        </div>

        <div class="ghf-filter-group">
            <label>Đến ngày</label>
            <input type="date" name="den_ngay" value="{{ request('den_ngay') }}">
        </div>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="ghf-btn ghf-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Tìm kiếm
            </button>
            @if(request()->anyFilled(['loai_loi', 'trang_thai_hang_hoan', 'ma_hoa_don', 'tu_ngay', 'den_ngay']))
                <a href="{{ route('admin.giao-hang.that-bai') }}" class="ghf-btn ghf-btn-ghost">Bỏ lọc</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="ghf-card">
        <div class="ghf-table-wrap">
            <table class="ghf-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Shipper</th>
                        <th>Loại lỗi</th>
                        <th>Lý do</th>
                        <th>Ngày báo</th>
                        <th>Hàng hoàn</th>
                        <th style="text-align:right;">Phạt shipper</th>
                        <th style="text-align:right;">Hệ thống chịu</th>
                        <th style="text-align:center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($danhSach as $g)
                        @php
                            $ttHH = $g->hangHoan?->trang_thai;
                            $ttHHText = [
                                'CHO_MANG_VE_KHO' => 'Chờ mang về kho',
                                'DA_NHAN_TAI_KHO' => 'Đã nhận tại kho',
                                'DANG_KIEM_TRA'   => 'Đang kiểm tra',
                                'DA_KIEM_TRA'     => 'Đã kiểm tra',
                            ][$ttHH] ?? ($ttHH ?: '—');
                            $ttHHBadge = [
                                'CHO_MANG_VE_KHO' => 'gray',
                                'DA_NHAN_TAI_KHO' => 'blue',
                                'DANG_KIEM_TRA'   => 'amber',
                                'DA_KIEM_TRA'     => 'green',
                            ][$ttHH] ?? 'gray';
                        @endphp
                        <tr>
                            <td class="ghf-code">#HD-{{ str_pad($g->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $g->hoaDon?->khachHang?->ten_khach_hang ?? '—' }}</td>
                            <td>{{ $g->shipper?->ten_nhan_vien ?? '—' }}</td>
                            <td>
                                @if($g->loai_loi === 'SHIPPER')
                                    <span class="ghf-badge red">Do shipper</span>
                                @elseif($g->loai_loi === 'CUSTOMER')
                                    <span class="ghf-badge amber">Do khách hàng</span>
                                @else
                                    <span class="ghf-badge gray">{{ $g->loai_loi }}</span>
                                @endif
                            </td>
                            <td style="max-width:220px; font-size:12.5px; color:#475467;">
                                {{ \Illuminate\Support\Str::limit($g->ly_do, 60) }}
                            </td>
                            <td style="white-space:nowrap; font-family:monospace; font-size:12.5px;">
                                {{ optional($g->thoi_diem_bao)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td>
                                <span class="ghf-badge {{ $ttHHBadge }}">{{ $ttHHText }}</span>
                            </td>
                            <td class="ghf-money red">
                                @if($g->da_quyet_toan)
                                    {{ number_format((float) $g->tong_shipper_chiu, 0, ',', '.') }} đ
                                @else
                                    <span style="color:#94a3b8; font-weight:400;">—</span>
                                @endif
                            </td>
                            <td class="ghf-money">
                                @if($g->da_quyet_toan)
                                    <span style="color:#B54708;">{{ number_format((float) $g->tong_he_thong_chiu, 0, ',', '.') }} đ</span>
                                @else
                                    <span style="color:#94a3b8; font-weight:400;">—</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <a href="{{ route('admin.giao-hang.that-bai.chi-tiet', $g->ma_giao_hang_that_bai) }}"
                                   class="ghf-btn-detail">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="ghf-empty">
                                    <div class="ghf-empty-icon">📭</div>
                                    <div style="font-size:15px; font-weight:600; color:#475467; margin-bottom:4px;">
                                        Không có đơn giao thất bại nào
                                    </div>
                                    <div style="font-size:13px;">
                                        Các đơn bị hủy do shipper hoặc khách hàng sẽ hiển thị tại đây.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($danhSach->hasPages())
            <div class="ghf-pagination">
                <div>
                    Hiển thị {{ $danhSach->firstItem() }}–{{ $danhSach->lastItem() }}
                    / {{ $danhSach->total() }} bản ghi
                </div>
                <div class="pages">
                    @if($danhSach->onFirstPage())
                        <span class="disabled">‹</span>
                    @else
                        <a href="{{ $danhSach->previousPageUrl() }}">‹</a>
                    @endif

                    @foreach($danhSach->getUrlRange(max(1, $danhSach->currentPage()-2), min($danhSach->lastPage(), $danhSach->currentPage()+2)) as $page => $url)
                        @if($page == $danhSach->currentPage())
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($danhSach->hasMorePages())
                        <a href="{{ $danhSach->nextPageUrl() }}">›</a>
                    @else
                        <span class="disabled">›</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

</div>
@endsection