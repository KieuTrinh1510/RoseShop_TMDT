@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .fs-container { padding: 24px 32px; background: #f8f9fa; min-height: 100vh; }

        /* Stats Grid */
        .fs-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 1200px) { .fs-stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .fs-stats-grid { grid-template-columns: 1fr; } }

        .fs-stat-card { background: #fff; border: 1px solid #EAECF0; border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; height: 84px; transition: all 0.2s ease; }
        .fs-stat-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,24,40,0.06); }
        .fs-stat-icon { width: 44px; height: 44px; min-width: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .fs-stat-icon.pink { background: #FDEAF3; color: #EC3D8F; }
        .fs-stat-icon.green { background: #ECFDF3; color: #039855; }
        .fs-stat-icon.orange { background: #FEF0C7; color: #DC6803; }
        .fs-stat-icon.gray { background: #F2F4F7; color: #475467; }

        /* Card Section */
        .fs-card { background: #fff; border: 1px solid #EAECF0; border-radius: 14px; box-shadow: 0 1px 3px rgba(16,24,40,0.04); margin-bottom: 24px; }
        .fs-card-header { padding: 18px 24px; border-bottom: 1px solid #EAECF0; display: flex; align-items: center; justify-content: space-between; background: #FAFAFA; flex-wrap: wrap; gap: 12px; }

        /* Table */
        .fs-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .fs-table th { background: #F9FAFB; color: #475467; font-size: 11.5px; font-weight: 600; text-transform: uppercase; padding: 10px 10px; border-bottom: 1px solid #EAECF0; white-space: nowrap; }
        .fs-table td { padding: 10px 10px; vertical-align: middle; border-bottom: 1px solid #F2F4F7; font-size: 12.5px; color: #344054; }
        .fs-table tbody tr:hover { background-color: #FDFBFB; }

        /* Badges */
        .badge-source { padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
        .badge-src-can-han { background: #FEF0C7; color: #B54708; border: 1px solid #FEDF89; }
        .badge-src-het-han { background: #FEE4E2; color: #B42318; border: 1px solid #FECDCA; }
        .badge-src-hoan-hang { background: #E0F2FE; color: #026AA2; border: 1px solid #B9E6FE; }
        .badge-src-co-van-de { background: #F4EBFF; color: #7F56D9; border: 1px solid #D6BBFB; }

        .badge-rate { padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; white-space: nowrap; }
        .badge-rate-30 { background: #EFF8FF; color: #175CD3; border: 1px solid #B2DDFF; }
        .badge-rate-50 { background: #FEF3F2; color: #B42318; border: 1px solid #FECDCA; }
        .badge-rate-75 { background: #FDF2FA; color: #C11574; border: 1px solid #FCCEEE; }

        /* Filter Tab */
        .fs-filter-tab { display: inline-block; padding: 5px 9px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #667085; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
        .fs-filter-tab:hover { background: #F2F4F7; color: #344054; }
        .fs-filter-tab.active { background: #EC3D8F; color: #fff; }

        .btn-quick-hr { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid #D0D5DD; background: #fff; color: #344054; cursor: pointer; }
        .btn-quick-hr:hover { background: #F2F4F7; }

        /* Action Buttons: nhỏ gọn, 1 hàng */
        .btn-act { font-size: 11px; padding: 4px 7px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all 0.15s ease; white-space: nowrap; line-height: 1.2; }
        .btn-act-extend { background: #EFF8FF; color: #175CD3; border-color: #B2DDFF; }
        .btn-act-extend:hover { background: #D1E9FF; color: #155EEF; }
        .btn-act-return { background: #ECFDF3; color: #027A48; border-color: #A6F4C5; }
        .btn-act-return:hover { background: #D1FADF; color: #05603A; }
        .btn-act-damage { background: #FEF3F2; color: #B42318; border-color: #FECDCA; }
        .btn-act-damage:hover { background: #FEE4E2; color: #912018; }
        .btn-act-close { background: #F2F4F7; color: #344054; border-color: #D0D5DD; }
        .btn-act-close:hover { background: #E4E7EC; color: #1D2939; }

        /* RoseShop Standard Table Footer & Pagination */
        .rs-table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: #ffffff;
            border-top: 1px solid #EAECF0;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
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
            font-size: 13px;
            color: #475467;
        }
        .rs-per-page-select {
            width: 68px !important;
            height: 34px !important;
            padding: 0 20px 0 10px !important;
            border: 1px solid #D0D5DD !important;
            border-radius: 8px !important;
            font-size: 13px !important;
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
            font-size: 13px;
            color: #667085;
            white-space: nowrap;
        }
        .rs-footer-pagination {
            display: flex;
            align-items: center;
        }
        .rs-pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 4px;
            align-items: center;
        }
        .rs-pagination .page-item .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 8px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #475467;
            background: #ffffff;
            border: 1px solid #D0D5DD;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .rs-pagination .page-item:not(.disabled) .page-link:hover {
            background: #F9FAFB;
            border-color: #EC3D8F;
            color: #EC3D8F;
        }
        .rs-pagination .page-item.active .page-link {
            background: #EC3D8F;
            border-color: #EC3D8F;
            color: #ffffff;
            font-weight: 700;
        }
        .rs-pagination .page-item.disabled .page-link {
            background: #F9FAFB;
            border-color: #EAECF0;
            color: #D0D5DD;
            cursor: not-allowed;
        }
        .rs-pagination .page-link.ellipsis {
            border-color: transparent !important;
            background: transparent !important;
            color: #98A2B3 !important;
            cursor: default !important;
        }
    </style>
@endpush

@section('content')
<div class="fs-container">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4" style="background:#ECFDF3; color:#027A48;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="rs-page-header d-flex justify-content-between align-items-center mb-3 gap-3 flex-nowrap">
        <div style="flex: 1; min-width: 0;">
            <h1 class="rs-page-title fs-5 fw-bold text-dark mb-1">FLASH SALE</h1>
            <p class="rs-page-subtitle text-muted mb-0 text-truncate" style="font-size: 12px;">
                Quản lý tập trung các chương trình Flash Sale từ nhiều nguồn: Cận hạn, Hết hạn, Hàng hoàn và Hàng có vấn đề.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.san-pham-het-han') }}" class="btn btn-outline-danger btn-sm fw-semibold rounded-3 px-3 py-1 text-nowrap" style="font-size: 12px; height: 32px; display: inline-flex; align-items: center;">
                <i class="bi bi-clock-history me-1"></i> Sản phẩm cận hạn
            </a>
            <a href="{{ route('admin.bao-cao-hang-hong.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-3 px-3 py-1 text-nowrap" style="font-size: 12px; height: 32px; display: inline-flex; align-items: center;">
                <i class="bi bi-trash3 me-1"></i> Báo cáo hàng hỏng
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="fs-stats-grid">
        <div class="fs-stat-card">
            <div class="fs-stat-icon pink"><i class="bi bi-lightning-fill"></i></div>
            <div class="d-flex flex-column justify-content-center">
                <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1.2;">{{ number_format($thongKe['tat_ca']) }}</h3>
                <div class="text-muted text-nowrap mt-1" style="font-size: 12px; line-height: 1.2;">Tổng CT Flash Sale</div>
            </div>
        </div>
        <div class="fs-stat-card">
            <div class="fs-stat-icon green"><i class="bi bi-play-circle-fill"></i></div>
            <div class="d-flex flex-column justify-content-center">
                <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1.2;">{{ number_format($thongKe['dang_ban']) }}</h3>
                <div class="text-muted text-nowrap mt-1" style="font-size: 12px; line-height: 1.2;">Đang mở bán Flash Sale</div>
            </div>
        </div>
        <div class="fs-stat-card">
            <div class="fs-stat-icon orange"><i class="bi bi-clock-history"></i></div>
            <div class="d-flex flex-column justify-content-center">
                <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1.2;">{{ number_format($thongKe['het_thoi_gian']) }}</h3>
                <div class="text-muted text-nowrap mt-1" style="font-size: 12px; line-height: 1.2;">Đã hết thời gian</div>
            </div>
        </div>
        <div class="fs-stat-card">
            <div class="fs-stat-icon gray"><i class="bi bi-bag-check-fill"></i></div>
            <div class="d-flex flex-column justify-content-center">
                <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1.2;">{{ number_format($thongKe['het_hang']) }}</h3>
                <div class="text-muted text-nowrap mt-1" style="font-size: 12px; line-height: 1.2;">Đã bán hết hàng</div>
            </div>
        </div>
    </div>

    {{-- Filter bar: Cùng 1 hàng, không bị rớt dòng --}}
    <div class="card border-0 shadow-sm rounded-3 p-2 mb-4 bg-white">
        <form method="GET" action="{{ route('admin.hoan-hang.flash-sale.index') }}" class="d-flex align-items-center justify-content-between gap-2 flex-nowrap" style="overflow-x: auto;">
            <div class="d-flex align-items-center gap-1 flex-nowrap">
                <span class="text-muted fw-semibold me-1 text-nowrap" style="font-size: 11px; text-transform: uppercase;">Bộ lọc:</span>
                <a href="{{ route('admin.hoan-hang.flash-sale.index', ['filter' => 'all', 'keyword' => $keyword ?? '']) }}"
                   class="fs-filter-tab {{ ($filter ?? 'all') === 'all' ? 'active' : '' }}">Tất cả ({{ $thongKe['tat_ca'] }})</a>
                <a href="{{ route('admin.hoan-hang.flash-sale.index', ['filter' => 'CAN_HAN', 'keyword' => $keyword ?? '']) }}"
                   class="fs-filter-tab {{ ($filter ?? '') === 'CAN_HAN' ? 'active' : '' }}">Cận hạn</a>
                <a href="{{ route('admin.hoan-hang.flash-sale.index', ['filter' => 'HET_HAN', 'keyword' => $keyword ?? '']) }}"
                   class="fs-filter-tab {{ ($filter ?? '') === 'HET_HAN' ? 'active' : '' }}">Hết hạn</a>
                <a href="{{ route('admin.hoan-hang.flash-sale.index', ['filter' => 'HANG_HOAN', 'keyword' => $keyword ?? '']) }}"
                   class="fs-filter-tab {{ ($filter ?? '') === 'HANG_HOAN' ? 'active' : '' }}">Hàng hoàn</a>
                <a href="{{ route('admin.hoan-hang.flash-sale.index', ['filter' => 'HANG_CO_VAN_DE', 'keyword' => $keyword ?? '']) }}"
                   class="fs-filter-tab {{ ($filter ?? '') === 'HANG_CO_VAN_DE' ? 'active' : '' }}">Có vấn đề</a>
                <a href="{{ route('admin.hoan-hang.flash-sale.index', ['filter' => 'dang_ban', 'keyword' => $keyword ?? '']) }}"
                   class="fs-filter-tab {{ ($filter ?? '') === 'dang_ban' ? 'active' : '' }}">Đang bán ({{ $thongKe['dang_ban'] }})</a>
                <a href="{{ route('admin.hoan-hang.flash-sale.index', ['filter' => 'het_thoi_gian', 'keyword' => $keyword ?? '']) }}"
                   class="fs-filter-tab {{ ($filter ?? '') === 'het_thoi_gian' ? 'active' : '' }}">Hết giờ ({{ $thongKe['het_thoi_gian'] }})</a>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <div class="position-relative" style="width: 250px;">
                    <i class="bi bi-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 13px; z-index: 5;"></i>
                    <input type="text" name="keyword" class="form-control" style="padding: 0 12px 0 38px !important; height: 34px; border-radius: 8px; font-size: 12px;" placeholder="Tìm tên hoa, mã Flash Sale..." value="{{ $keyword ?? '' }}">
                    <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">
                </div>
                <button type="submit" class="btn text-white fw-bold d-inline-flex align-items-center justify-content-center text-nowrap" style="background:#EC3D8F; border-color:#EC3D8F; font-size: 12px; height: 34px; padding: 0 14px; border-radius: 8px;">
                    Tìm
                </button>
                @if(!empty($keyword) || ($filter ?? 'all') !== 'all')
                    <a href="{{ route('admin.hoan-hang.flash-sale.index') }}" class="btn btn-light border d-inline-flex align-items-center justify-content-center" style="height: 34px; width: 34px; padding: 0; border-radius: 8px;" title="Đặt lại">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Bảng danh sách Flash Sale --}}
    <div class="fs-card">
        <div class="fs-card-header bg-white">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-tags text-danger fs-5"></i>
                <h2 class="fs-6 fw-bold text-dark mb-0">Danh sách chương trình Flash Sale</h2>
            </div>
        </div>
        <div class="table-responsive">
            <table class="fs-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 75px;">Mã</th>
                        <th style="min-width: 180px; max-width: 240px;">Sản phẩm / Bó hoa</th>
                        <th class="text-center" style="width: 110px;">Nguồn</th>
                        <th class="text-center" style="width: 160px;">Quota / Đã bán / Giữ / Còn</th>
                        <th class="text-end" style="width: 95px;">Giá gốc</th>
                        <th class="text-center" style="width: 80px;">Mức giảm</th>
                        <th class="text-end" style="width: 110px;">Giá Flash Sale</th>
                        <th class="text-center" style="width: 140px;">Thời gian kết thúc</th>
                        <th class="text-center" style="width: 100px;">Trạng thái</th>
                        <th class="text-center" style="width: 170px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flashSales as $fs)
                        @php
                            $ten = $fs->ma_bo_hoa
                                ? ($fs->boHoa?->ten_bo_hoa ?? 'Bó hoa thành phẩm #' . $fs->ma_bo_hoa)
                                : ($fs->sanPham?->ten_san_pham ?? 'Sản phẩm #' . $fs->ma_san_pham);
                            $conQuota = $fs->available_quota;
                            $dangGiu = $fs->reserved_quantity;
                            $daHetGio = $fs->ket_thuc && \Carbon\Carbon::parse($fs->ket_thuc)->isPast();
                        @endphp
                        <tr>
                            <td class="text-center" style="white-space: nowrap;">
                                <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 12px;">#FS-{{ $fs->ma_flash_sale }}</span>
                            </td>
                            <td>
                                <div style="max-width: 250px; min-width: 0;">
                                    <div class="fw-bold text-dark text-truncate" title="{{ $ten }}" style="font-size: 13px;">{{ $ten }}</div>
                                    <div class="text-muted" style="font-size: 11.5px; white-space: nowrap;">
                                        @if($fs->ma_chi_tiet_nhap)
                                            Lô: <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 11px;">#{{ $fs->ma_chi_tiet_nhap }}</span>
                                        @elseif($fs->ma_chi_tiet_hang_hoan)
                                            Hoàn: <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 11px;">#{{ $fs->ma_chi_tiet_hang_hoan }}</span>
                                        @elseif($fs->ma_bo_hoa)
                                            Bó hoa: <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 11px;">#{{ $fs->ma_bo_hoa }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div>
                                    @switch($fs->nguon)
                                        @case('CAN_HAN')
                                            <span class="badge-source badge-src-can-han"><i class="bi bi-clock"></i> Cận hạn</span>
                                            @break
                                        @case('HET_HAN')
                                            <span class="badge-source badge-src-het-han"><i class="bi bi-exclamation-circle"></i> Hết hạn</span>
                                            @break
                                        @case('HANG_HOAN')
                                            <span class="badge-source badge-src-hoan-hang"><i class="bi bi-arrow-return-left"></i> Hàng hoàn</span>
                                            @break
                                        @case('HANG_CO_VAN_DE')
                                            <span class="badge-source badge-src-co-van-de"><i class="bi bi-patch-question"></i> Có vấn đề</span>
                                            @break
                                        @default
                                            <span class="badge-source badge-src-hoan-hang">{{ $fs->nguon ?? 'Chưa rõ' }}</span>
                                    @endswitch
                                </div>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div class="d-inline-flex align-items-center gap-1" style="font-size: 12.5px;">
                                    <span class="fw-bold text-dark" title="Hạn mức Flash Sale (Quota)">{{ number_format($fs->so_luong) }}</span>
                                    <span class="text-muted">/</span>
                                    <span class="fw-bold text-success" title="Đã bán thành công (Consumed)">{{ number_format($fs->so_luong_da_ban) }}</span>
                                    <span class="text-muted">/</span>
                                    <span class="fw-bold text-warning" title="Đang có khách giữ (Reserved)">{{ number_format($dangGiu) }}</span>
                                    <span class="text-muted">/</span>
                                    <span class="fw-bold {{ $conQuota > 0 ? 'text-primary' : 'text-muted' }}" title="Suất sale còn khả dụng">{{ number_format($conQuota) }}</span>
                                </div>
                            </td>
                            <td class="text-end text-muted text-decoration-line-through" style="white-space: nowrap;">
                                <div style="font-size: 12.5px;">{{ number_format($fs->gia_goc) }}đ</div>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div>
                                    @php
                                        $rate = $fs->ti_le_giam ?? ($fs->gia_goc > 0 ? round(($fs->so_tien_giam / $fs->gia_goc) * 100) : 0);
                                    @endphp
                                    @if($rate >= 70)
                                        <span class="badge-rate badge-rate-75">-{{ $rate }}%</span>
                                    @elseif($rate >= 45)
                                        <span class="badge-rate badge-rate-50">-{{ $rate }}%</span>
                                    @else
                                        <span class="badge-rate badge-rate-30">-{{ $rate }}%</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-bold text-danger" style="white-space: nowrap;">
                                <div style="font-size: 13.5px;">{{ number_format($fs->gia_flash_sale) }}đ</div>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div class="fw-semibold {{ $daHetGio ? 'text-danger' : 'text-dark' }}" style="font-size: 12.5px;">
                                    {{ optional($fs->ket_thuc)->format('d/m/Y H:i') }}
                                </div>
                                @if($daHetGio)
                                    <div class="mt-1">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size: 10.5px;">
                                            Đã hết giờ
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div>
                                    @if($fs->trang_thai === 'DANG_BAN')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold" style="font-size: 11.5px;">Đang bán</span>
                                    @elseif($fs->trang_thai === 'KET_THUC')
                                        <span class="badge bg-secondary-subtle text-secondary border fw-semibold" style="font-size: 11.5px;">Đã kết thúc</span>
                                    @elseif($fs->trang_thai === 'HET_HANG')
                                        <span class="badge bg-dark text-white fw-semibold" style="font-size: 11.5px;">Hết quota</span>
                                    @else
                                        <span class="badge bg-light text-dark border" style="font-size: 11.5px;">{{ $fs->trang_thai }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-nowrap">
                                    {{-- 1. Gia hạn Flash Sale --}}
                                    <button type="button"
                                            class="btn-act btn-act-extend btn-open-modal-extend text-nowrap"
                                            data-id="{{ $fs->ma_flash_sale }}"
                                            data-ten="{{ $ten }}"
                                            data-ketthuc="{{ optional($fs->ket_thuc)->format('Y-m-d\\TH:i') }}"
                                            title="Gia hạn Flash Sale">
                                        <i class="bi bi-clock-history"></i> Gia hạn
                                    </button>

                                    {{-- 2. Báo hỏng hàng Flash Sale (trừ tồn vật lý và báo hỏng) --}}
                                    @if($conQuota > 0)
                                        <button type="button"
                                                class="btn-act btn-act-damage btn-open-modal-damage text-nowrap"
                                                data-id="{{ $fs->ma_flash_sale }}"
                                                data-ten="{{ $ten }}"
                                                data-conlai="{{ $conQuota }}"
                                                title="Báo hỏng">
                                            <i class="bi bi-trash3"></i> Báo hỏng
                                        </button>
                                    @endif

                                    {{-- 3. Kết thúc --}}
                                    @if($fs->trang_thai !== 'KET_THUC')
                                        <form action="{{ route('admin.hoan-hang.flash-sale.close', $fs->ma_flash_sale) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Xác nhận kết thúc chương trình Flash Sale này? Hệ thống sẽ ngừng áp dụng giá sale, sản phẩm vẫn được giữ nguyên trong kho bán thường.');">
                                            @csrf
                                            <button type="submit" class="btn-act btn-act-close text-nowrap" title="Kết thúc chương trình">
                                                <i class="bi bi-stop-circle"></i> Kết thúc
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-lightning fs-3 text-secondary d-block mb-2"></i>
                                Không tìm thấy chương trình Flash Sale nào trong bộ lọc này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Phân trang chuẩn RoseShop cho Flash Sale --}}
        @if($flashSales->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span>Số hàng mỗi trang:</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $sz)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $sz, 'page' => 1]) }}"
                                        {{ ($flashSales->perPage() == $sz) ? 'selected' : '' }}>
                                    {{ $sz }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rs-records-count">
                        {{ $flashSales->firstItem() ?? 0 }}-{{ $flashSales->lastItem() ?? 0 }} / {{ $flashSales->total() }} bản ghi
                    </div>
                </div>
                @if($flashSales->hasPages())
                    <div class="rs-footer-pagination">
                        <ul class="rs-pagination">
                            {{-- Trang trước --}}
                            @if ($flashSales->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $flashSales->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                            @endif

                            {{-- Rút gọn trang với dấu ... --}}
                            @php
                                $curP = $flashSales->currentPage();
                                $lastP = $flashSales->lastPage();
                                $pagesFs = [];
                                if ($lastP <= 3) {
                                    for ($i = 1; $i <= $lastP; $i++) { $pagesFs[] = $i; }
                                } else {
                                    if ($curP == 1) {
                                        $pagesFs = [1, '...', $lastP];
                                    } elseif ($curP == $lastP) {
                                        $pagesFs = [1, '...', $lastP];
                                    } else {
                                        $pagesFs = [1];
                                        if ($curP > 2) { $pagesFs[] = '...'; }
                                        $pagesFs[] = $curP;
                                        if ($curP < $lastP - 1) { $pagesFs[] = '...'; }
                                        $pagesFs[] = $lastP;
                                    }
                                }
                            @endphp

                            @foreach ($pagesFs as $p)
                                @if ($p === '...')
                                    <li class="page-item disabled"><span class="page-link ellipsis">&hellip;</span></li>
                                @elseif ($p == $curP)
                                    <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $flashSales->url($p) }}">{{ $p }}</a></li>
                                @endif
                            @endforeach

                            {{-- Trang sau --}}
                            @if ($flashSales->hasMorePages())
                                <li class="page-item"><a class="page-link" href="{{ $flashSales->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Phần Hàng hoàn chờ đưa Flash Sale (nếu có) --}}
    @if(isset($hangHoanChoFlashSale) && count($hangHoanChoFlashSale) > 0)
        <div class="fs-card mt-4">
            <div class="fs-card-header bg-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-return-left text-primary fs-5"></i>
                    <h2 class="fs-6 fw-bold text-dark mb-0">Hàng hoàn đủ điều kiện đưa vào Flash Sale</h2>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">{{ count($hangHoanChoFlashSale) }} món</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="fs-table">
                    <thead>
                        <tr>
                            <th>Mặt hàng</th>
                            <th class="text-center">Số lượng còn tốt</th>
                            <th class="text-end">Giá bán đơn cũ (Snapshot)</th>
                            <th>Ghi chú kiểm tra</th>
                            <th class="text-center" style="width: 180px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hangHoanChoFlashSale as $hh)
                            @php
                                $tenHh = $hh->chiTietHoaDon?->boHoa?->ten_bo_hoa 
                                    ?? $hh->chiTietHoaDon?->sanPham?->ten_san_pham 
                                    ?? 'Mặt hàng #' . $hh->ma_chi_tiet_hang_hoan;
                                $giaSnapshot = $hh->chiTietHoaDon?->gia_ban ?? 0;
                            @endphp
                            <tr>
                                <td class="fw-bold text-dark">{{ $tenHh }}</td>
                                <td class="text-center fw-bold text-primary">{{ $hh->so_luong_tot }}</td>
                                <td class="text-end fw-semibold text-dark">{{ number_format($giaSnapshot) }}đ</td>
                                <td class="text-muted" style="font-size: 13px;">{{ $hh->ghi_chu ?? '—' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-1 btn-open-modal-hang-hoan"
                                            data-id="{{ $hh->ma_chi_tiet_hang_hoan }}"
                                            data-ten="{{ $tenHh }}"
                                            data-giagoc="{{ $giaSnapshot }}"
                                            data-sl="{{ $hh->so_luong_tot }}">
                                        <i class="bi bi-lightning-fill me-1"></i> Mở Flash Sale
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

{{-- MODAL 1: GIA HẠN FLASH SALE (MỤC XIV.A & XV) --}}
<div class="modal fade" id="modalGiaHan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <form action="" method="POST" id="formGiaHan">
                @csrf
                <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="bi bi-clock-history text-primary me-2"></i> Gia hạn thời gian Flash Sale
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="text-muted" style="font-size: 12px;">Sản phẩm Flash Sale:</div>
                        <div class="fw-bold text-dark fs-6" id="ghTenSanPham">—</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" style="font-size:13px;">
                            Thời gian kết thúc mới <span class="text-danger">*</span>:
                        </label>
                        <input type="datetime-local" name="ket_thuc_moi" id="ghKetThucMoi" class="form-control" required>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="text-muted" style="font-size: 12px;">Gia hạn nhanh:</span>
                            <button type="button" class="btn-quick-hr" data-add="6">+6 giờ</button>
                            <button type="button" class="btn-quick-hr" data-add="12">+12 giờ</button>
                            <button type="button" class="btn-quick-hr" data-add="24">+24 giờ</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" style="font-size:13px;">
                            Ghi chú kiểm tra chất lượng <span class="text-danger">*</span>:
                        </label>
                        <textarea name="ghi_chu" class="form-control" rows="3" required
                                  placeholder="Bắt buộc xác nhận chất lượng (ví dụ: Đã kiểm tra thực tế, hoa vẫn giữ độ tươi tốt, cho phép tiếp tục Flash Sale)."></textarea>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-0 rounded-3" style="font-size: 12px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hệ thống sẽ ghi nhận lịch sử kiểm tra chất lượng với kết quả <strong>CON_BAN_DUOC</strong> cùng thời gian gia hạn mới.
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4 bg-light" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Xác nhận gia hạn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 2: BÁO HỎNG FLASH SALE (MỤC XIV.B & XVI) --}}
<div class="modal fade" id="modalDamage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <form action="" method="POST" id="formDamage">
                @csrf
                <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="bi bi-trash3 text-danger me-2"></i> Báo hỏng hàng Flash Sale
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="text-muted" style="font-size: 12px;">Mặt hàng:</div>
                        <div class="fw-bold text-dark fs-6" id="dmTenSanPham">—</div>
                        <div class="text-muted mt-1" style="font-size: 12px;">
                            Số lượng tồn Flash Sale còn lại: <strong class="text-danger" id="dmConLai">0</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" style="font-size:13px;">
                            Số lượng báo hỏng <span class="text-danger">*</span>:
                        </label>
                        <input type="number" name="so_luong_hong" id="dmSoLuong" class="form-control" min="1" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" style="font-size:13px;">
                            Lý do hỏng <span class="text-danger">*</span>:
                        </label>
                        <input type="text" name="ly_do" class="form-control" required
                               placeholder="Ví dụ: Hoa bị héo sau thời gian trưng bày Flash Sale">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark" style="font-size:13px;">Ghi chú chi tiết:</label>
                        <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Chi tiết tình trạng..."></textarea>
                    </div>

                    <div class="alert alert-warning py-2 px-3 mb-0 rounded-3" style="font-size: 12px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Hệ thống sẽ trừ tồn Flash Sale và ghi nhận tập trung vào <strong>Báo cáo hàng hỏng</strong> (nguồn FLASH_SALE).
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4 bg-light" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold">
                        <i class="bi bi-trash3 me-1"></i> Xác nhận Báo hỏng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 3: ĐƯA HÀNG HOÀN VÀO FLASH SALE --}}
<div class="modal fade" id="modalHangHoan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <form action="" method="POST" id="formHangHoan">
                @csrf
                <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        <i class="bi bi-lightning-charge text-danger me-2"></i> Mở Flash Sale từ Hàng hoàn
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="text-muted" style="font-size: 12px;">Mặt hàng hoàn:</div>
                        <div class="fw-bold text-dark fs-6" id="hhTenMatHang">—</div>
                        <div class="text-muted mt-1" style="font-size: 12px;">
                            Giá bán đơn cũ (Snapshot): <strong class="text-dark" id="hhGiaGocText">0đ</strong>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark" style="font-size:13px;">Số lượng Flash Sale <span class="text-danger">*</span>:</label>
                            <input type="number" name="so_luong" id="hhSoLuong" class="form-control form-control-sm" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark" style="font-size:13px;">Mức giảm giá <span class="text-danger">*</span>:</label>
                            <select name="ti_le_giam" id="hhTiLeGiam" class="form-select form-select-sm" required>
                                <option value="30">Giảm 30%</option>
                                <option value="50" selected>Giảm 50%</option>
                                <option value="75">Giảm 75%</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 bg-light border mb-3 text-center">
                        <div class="text-muted" style="font-size:12px;">Giá Flash Sale tính trên đơn cũ:</div>
                        <div class="fw-bold text-danger fs-5" id="hhGiaSaleText">0đ</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark" style="font-size:12.5px;">Bắt đầu:</label>
                            <input type="datetime-local" name="bat_dau" id="hhBatDau" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark" style="font-size:12.5px;">Kết thúc:</label>
                            <input type="datetime-local" name="ket_thuc" id="hhKetThuc" class="form-control form-control-sm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4 bg-light" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold" style="background:#EC3D8F; border-color:#EC3D8F;">
                        Xác nhận Mở Flash Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pad = (n) => String(n).padStart(2, '0');
    const toISO = (date) => date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    const formatVND = (num) => new Intl.NumberFormat('vi-VN').format(Math.round(num)) + 'đ';

    // 1. MODAL GIA HẠN
    const modalGiaHanEl = document.getElementById('modalGiaHan');
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-open-modal-extend');
        if (!btn) return;
        e.preventDefault();
        const id = btn.dataset.id;
        const ten = btn.dataset.ten;

        const elTen = document.getElementById('ghTenSanPham');
        const formGiaHan = document.getElementById('formGiaHan');
        const elKetThuc = document.getElementById('ghKetThucMoi');

        if (elTen) elTen.textContent = ten;
        if (formGiaHan) formGiaHan.action = '/admin/hoan-hang/flash-sale/' + id + '/gia-han';

        const defaultEnd = new Date(Date.now() + 24 * 60 * 60 * 1000);
        if (elKetThuc) elKetThuc.value = toISO(defaultEnd);

        if (modalGiaHanEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalGiaHanEl);
            modal.show();
        }
    });

    document.querySelectorAll('.btn-quick-hr').forEach(btn => {
        btn.addEventListener('click', function () {
            const hrs = parseInt(this.dataset.add, 10);
            const newDate = new Date(Date.now() + hrs * 60 * 60 * 1000);
            const el = document.getElementById('ghKetThucMoi');
            if (el) el.value = toISO(newDate);
        });
    });

    // 2. MODAL BÁO HỎNG
    const modalDamageEl = document.getElementById('modalDamage');
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-open-modal-damage');
        if (!btn) return;
        e.preventDefault();
        const id = btn.dataset.id;
        const ten = btn.dataset.ten;
        const conLai = parseInt(btn.dataset.conlai, 10) || 0;

        const elTen = document.getElementById('dmTenSanPham');
        const elConLai = document.getElementById('dmConLai');
        const elSoLuong = document.getElementById('dmSoLuong');
        const formDamage = document.getElementById('formDamage');

        if (elTen) elTen.textContent = ten;
        if (elConLai) elConLai.textContent = conLai;
        if (elSoLuong) {
            elSoLuong.max = conLai;
            elSoLuong.value = Math.min(1, conLai);
        }
        if (formDamage) formDamage.action = '/admin/hoan-hang/flash-sale/' + id + '/bao-hong';

        if (modalDamageEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalDamageEl);
            modal.show();
        }
    });

    // 3. MODAL HÀNG HOÀN FLASH SALE
    const modalHHEl = document.getElementById('modalHangHoan');
    let hhGiaGoc = 0;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-open-modal-hang-hoan');
        if (!btn) return;
        const id = btn.dataset.id;
        const ten = btn.dataset.ten;
        const sl = parseInt(btn.dataset.sl, 10) || 1;
        hhGiaGoc = parseFloat(btn.dataset.giagoc) || 0;

        const elTen = document.getElementById('hhTenMatHang');
        const elGiaGoc = document.getElementById('hhGiaGocText');
        const elSoLuong = document.getElementById('hhSoLuong');
        const formHH = document.getElementById('formHangHoan');
        const elBatDau = document.getElementById('hhBatDau');
        const elKetThuc = document.getElementById('hhKetThuc');

        if (elTen) elTen.textContent = ten;
        if (elGiaGoc) elGiaGoc.textContent = formatVND(hhGiaGoc);
        if (elSoLuong) {
            elSoLuong.max = sl;
            elSoLuong.value = sl;
        }
        if (formHH) formHH.action = '/admin/hoan-hang/flash-sale/' + id;

        const now = new Date();
        if (elBatDau) elBatDau.value = toISO(now);
        if (elKetThuc) elKetThuc.value = toISO(new Date(now.getTime() + 24 * 60 * 60 * 1000));

        updateHHPrice();

        if (modalHHEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalHHEl);
            modal.show();
        }
    });

    document.getElementById('hhTiLeGiam')?.addEventListener('change', updateHHPrice);

    function updateHHPrice() {
        const rate = parseInt(document.getElementById('hhTiLeGiam')?.value, 10) || 50;
        const sale = hhGiaGoc * (1 - rate / 100);
        const el = document.getElementById('hhGiaSaleText');
        if (el) el.textContent = formatVND(sale);
    }
});
</script>
@endpush