@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .exp-container { padding: 24px 32px 24px 32px; max-width: 100%; margin: 0; background: #f8f9fa; min-height: 100vh; }

        /* Stats Cards */
        .exp-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 1200px) { .exp-stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .exp-stats-grid { grid-template-columns: 1fr; } }

        .exp-stat-card { background: #ffffff; border: 1px solid #EAECF0; border-radius: 12px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04); display: flex; align-items: center; gap: 10px; height: 82px; min-height: 82px; transition: all 0.2s ease; }
        .exp-stat-card:hover { box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08); transform: translateY(-1px); }
        .exp-stat-icon { width: 38px; height: 38px; min-width: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .exp-stat-icon.red { background: #FEE4E2; color: #D92D20; }
        .exp-stat-icon.orange { background: #FEF0C7; color: #DC6803; }
        .exp-stat-icon.purple { background: #F4EBFF; color: #7F56D9; }
        .exp-stat-icon.green { background: #ECFDF3; color: #039855; }
        .exp-stat-info h3 { margin: 0; font-size: 18px; font-weight: 700; color: #101828; line-height: 1.15; }
        .exp-stat-info p { margin: 0; font-size: 11.5px; color: #344054; font-weight: 600; line-height: 1.2; white-space: nowrap; }
        .exp-stat-info .sub-text { font-size: 10px; color: #667085; margin-top: 1px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Filter Tab */
        .exp-filter-tab { padding: 5px 9px; border-radius: 6px; font-size: 11.5px; font-weight: 600; text-decoration: none; color: #475467; background: #F2F4F7; transition: all 0.15s ease; border: 1px solid transparent; white-space: nowrap; }
        .exp-filter-tab:hover { color: #101828; background: #EAECF0; }
        .exp-filter-tab.active { color: #ffffff; background: #EC3D8F; border-color: #EC3D8F; }

        /* Section Container */
        .exp-section-card { background: #ffffff; border: 1px solid #EAECF0; border-radius: 12px; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04); margin-bottom: 24px; overflow: hidden; }
        .exp-section-header { padding: 12px 18px; border-bottom: 1px solid #EAECF0; display: flex; align-items: center; justify-content: space-between; background: #FAFAFA; flex-wrap: wrap; gap: 10px; }
        .exp-section-title { display: flex; align-items: center; gap: 8px; }
        .exp-section-title h2 { margin: 0; font-size: 15px; font-weight: 700; color: #101828; }
        .exp-section-title .badge-count { background: #FEE4E2; color: #B42318; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 10px; }

        /* Tables - Compact và hài hòa */
        .exp-table { width: 100%; margin-bottom: 0; border-collapse: collapse; }
        .exp-table th { background: #F8F9FA !important; color: #475467 !important; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; padding: 8px 12px; border-bottom: 1px solid #EAECF0; white-space: nowrap; }
        .exp-table td { padding: 8px 12px; vertical-align: middle; border-bottom: 1px solid #EAECF0; font-size: 12px; color: #344054; }
        .exp-table tbody tr:hover { background-color: #FDFBFB; }

        /* Thumbnails đồng bộ giống SanPham */
        .rs-product-thumb {
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            max-width: 36px;
            max-height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            overflow: hidden;
            background: #f1f5f9;
            flex-shrink: 0;
        }
        .rs-product-thumb-img {
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: block;
        }
        .rs-product-thumb-placeholder {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 16px;
            background: #f1f5f9;
        }

        /* Button Action: nhỏ gọn, vừa vặn */
        .btn-action-process { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 11.5px; font-weight: 600; color: #ffffff; background: #EC3D8F; border: 1px solid #EC3D8F; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; white-space: nowrap; }
        .btn-action-process:hover { background: #D92578; border-color: #D92578; color: #ffffff; }

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
        .btn-action-process:hover { background: #D92578; border-color: #D92578; color: #ffffff; transform: translateY(-1px); }

        /* Decision Navigation Tabs in Modal */
        .decision-nav { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #EAECF0; padding-bottom: 8px; }
        .decision-tab-btn { flex: 1; padding: 10px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; border: 1px solid #EAECF0; background: #F9FAFB; color: #475467; cursor: pointer; text-align: center; transition: all 0.2s ease; }
        .decision-tab-btn.active { background: #FDEAF3; border-color: #EC3D8F; color: #EC3D8F; }

        /* Discount Preset Buttons */
        .btn-discount-preset { flex: 1; padding: 10px 8px; border-radius: 8px; font-weight: 700; font-size: 13.5px; border: 2px solid #EAECF0; background: #fff; color: #344054; cursor: pointer; transition: all 0.15s ease; text-align: center; }
        .btn-discount-preset.active { border-color: #EC3D8F; background: #FDEAF3; color: #EC3D8F; }

        /* Quick duration buttons */
        .btn-duration-quick { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid #D0D5DD; background: #fff; color: #344054; cursor: pointer; transition: all 0.15s ease; }
        .btn-duration-quick:hover { background: #F2F4F7; border-color: #98A2B3; }
    </style>
@endpush

@section('content')
<div class="exp-container">

    {{-- Alert Link to Flash Sale --}}
    @if(session('flash_sale_link'))
        <div class="alert alert-success d-flex align-items-center justify-content-between rounded-3 border-0 shadow-sm mb-4" style="background: #ECFDF3; border-left: 4px solid #12B76A !important;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <span class="text-dark fw-semibold">{{ session('success') }}</span>
            </div>
            <a href="{{ session('flash_sale_link') }}" class="btn btn-sm btn-success fw-bold px-3 py-1 rounded-2">
                <i class="bi bi-lightning-fill me-1"></i> Xem trong Flash Sale
            </a>
        </div>
    @elseif(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4" role="alert" style="background: #ECFDF3; color: #027A48;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="rs-page-header d-flex justify-content-between align-items-center mb-3 gap-3 flex-nowrap">
        <div style="flex: 1; min-width: 0;">
            <h1 class="rs-page-title fs-5 fw-bold text-dark mb-1">Sản phẩm cận hạn / hết hạn</h1>
            <p class="rs-page-subtitle text-muted mb-0 text-truncate" style="font-size: 12px;">
                Theo dõi HSD, cảnh báo chất lượng thực tế. HSD chỉ là cảnh báo, quyết định xử lý do người vận hành.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.hoan-hang.flash-sale.index') }}" class="btn btn-outline-danger btn-sm fw-semibold rounded-3 px-3 py-1 text-nowrap" style="font-size: 12px; height: 32px; display: inline-flex; align-items: center;">
                <i class="bi bi-lightning me-1"></i> Quản lý Flash Sale
            </a>
            <a href="{{ route('admin.bao-cao-hang-hong.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-3 px-3 py-1 text-nowrap" style="font-size: 12px; height: 32px; display: inline-flex; align-items: center;">
                <i class="bi bi-trash3 me-1"></i> Báo cáo hàng hỏng
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="exp-stats-grid">
        <div class="exp-stat-card">
            <div class="exp-stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="exp-stat-info" style="min-width: 0; flex: 1;">
                <h3>{{ number_format($thongKe['tongCanXuLy']) }}</h3>
                <p>Tổng SP cần kiểm tra</p>
                <div class="sub-text">Lô hoa & Bó thành phẩm</div>
            </div>
        </div>

        <div class="exp-stat-card">
            <div class="exp-stat-icon orange"><i class="bi bi-flower1"></i></div>
            <div class="exp-stat-info" style="min-width: 0; flex: 1;">
                <h3>{{ number_format($thongKe['tongLo']) }}</h3>
                <p>Lô hoa tươi cận/hết hạn</p>
                <div class="sub-text text-nowrap" style="font-size: 10px;">
                    <span class="text-danger fw-bold">{{ $thongKe['loQuaHan'] }}</span> quá hạn •
                    <span class="text-danger fw-bold">{{ $thongKe['loHomNay'] }}</span> hôm nay •
                    <span class="text-warning fw-bold">{{ $thongKe['loSapHet'] }}</span> sắp hết
                </div>
            </div>
        </div>

        <div class="exp-stat-card">
            <div class="exp-stat-icon purple"><i class="bi bi-box2-heart"></i></div>
            <div class="exp-stat-info" style="min-width: 0; flex: 1;">
                <h3>{{ number_format($thongKe['tongBo']) }}</h3>
                <p>Bó hoa cận/hết hạn</p>
                <div class="sub-text text-nowrap" style="font-size: 10px;">
                    <span class="text-danger fw-bold">{{ $thongKe['boQuaHan'] }}</span> quá hạn •
                    <span class="text-danger fw-bold">{{ $thongKe['boHomNay'] }}</span> hôm nay •
                    <span class="text-warning fw-bold">{{ $thongKe['boSapHet'] }}</span> sắp hết
                </div>
            </div>
        </div>

        <div class="exp-stat-card">
            <div class="exp-stat-icon green"><i class="bi bi-cash-stack"></i></div>
            <div class="exp-stat-info" style="min-width: 0; flex: 1;">
                <h3>{{ number_format($thongKe['tongGiaTri']) }}đ</h3>
                <p>Ước tính giá trị tồn kho</p>
                <div class="sub-text">Cần kiểm tra & xử lý</div>
            </div>
        </div>
    </div>

    {{-- Filter & Search Card --}}
    <div class="card border-0 shadow-sm rounded-3 p-2 mb-4 bg-white">
        <form method="GET" action="{{ route('admin.san-pham-het-han') }}" class="d-flex align-items-center justify-content-between gap-2 flex-nowrap" style="overflow-x: auto;">
            <div class="d-flex align-items-center gap-1 flex-nowrap flex-shrink-0">
                <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'all'])) }}"
                   class="exp-filter-tab {{ $filter === 'all' ? 'active' : '' }}">
                    Tất cả ({{ $thongKe['tongCanXuLy'] }})
                </a>
                <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'sap_het'])) }}"
                   class="exp-filter-tab {{ $filter === 'sap_het' ? 'active' : '' }}">
                    Sắp hết hạn ({{ $thongKe['loSapHet'] + $thongKe['boSapHet'] }})
                </a>
                <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'hom_nay'])) }}"
                   class="exp-filter-tab {{ $filter === 'hom_nay' ? 'active' : '' }}">
                    Hết hạn hôm nay ({{ $thongKe['loHomNay'] + $thongKe['boHomNay'] }})
                </a>
                <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'qua_han'])) }}"
                   class="exp-filter-tab {{ $filter === 'qua_han' ? 'active' : '' }}">
                    Đã quá hạn ({{ $thongKe['loQuaHan'] + $thongKe['boQuaHan'] }})
                </a>
            </div>

            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <div class="position-relative d-flex align-items-center" style="width: 250px;">
                    <i class="bi bi-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 13px; z-index: 5;"></i>
                    <input type="text" name="keyword" class="form-control"
                           style="padding: 0 12px 0 38px !important; border-radius: 8px; border: 1px solid #D0D5DD; font-size: 12px; height: 34px;"
                           placeholder="Tìm tên hoa, mã lô, tên bó hoa..."
                           value="{{ $keyword }}">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    @if(request('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                </div>
                <button class="btn text-white fw-bold d-inline-flex align-items-center justify-content-center text-nowrap" style="background:#EC3D8F; border-color:#EC3D8F; font-size: 12px; height: 34px; padding: 0 14px; border-radius: 8px;" type="submit">
                    Tìm
                </button>
                @if($keyword !== '' || $filter !== 'all')
                    <a href="{{ route('admin.san-pham-het-han') }}" class="btn btn-light border d-inline-flex align-items-center justify-content-center" style="height: 34px; width: 34px; padding: 0; border-radius: 8px;" title="Đặt lại bộ lọc">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- PHẦN 1: CÁC LÔ HOA TƯƠI CẬN / HẾT HẠN --}}
    {{-- ========================================================================= --}}
    <div class="exp-section-card">
        <div class="exp-section-header">
            <div class="exp-section-title">
                <i class="bi bi-flower2 text-danger fs-5"></i>
                <h2>Lô hoa tươi cần kiểm tra chất lượng</h2>
                <span class="badge-count">{{ $loHangs->total() }} lô</span>
            </div>
            <div>
                <a href="{{ route('admin.lo-hang') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-2 px-3">
                    <i class="bi bi-box-seam me-1"></i> Xem danh sách lô hàng
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="exp-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">STT</th>
                        <th>Hoa / Lô</th>
                        <th class="text-center">Tồn kho</th>
                        <th class="text-end">Giá bán</th>
                        <th class="text-center">HSD & Cảnh báo</th>
                        <th class="text-center">Kiểm tra chất lượng</th>
                        <th class="text-center" style="width: 170px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loHangs as $idx => $lo)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $loHangs->firstItem() ? ($loHangs->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rs-product-thumb">
                                        @if($lo->hinh_anh)
                                            <img src="{{ asset('storage/' . $lo->hinh_anh) }}"
                                                 alt="{{ $lo->ten_san_pham }}"
                                                 class="rs-product-thumb-img"
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'rs-product-thumb-placeholder\'><i class=\'bi bi-flower1\'></i></div>';">
                                        @else
                                            <div class="rs-product-thumb-placeholder"><i class="bi bi-flower1"></i></div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 12.5px;">{{ $lo->ten_san_pham }}</div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            Lô: <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 11px;">#{{ $lo->ma_chi_tiet_nhap }}</span>
                                            (PN <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 11px;">#{{ $lo->ma_phieu_nhap }}</span>) • ĐVT: {{ $lo->don_vi_tinh ?? 'Cành' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark" style="font-size: 12.5px;">{{ number_format($lo->so_luong_con_lai) }}</span>
                                <span class="text-muted" style="font-size: 11px;">/ {{ number_format($lo->so_luong) }}</span>
                            </td>
                            <td class="text-end fw-bold text-dark" style="font-size: 12.5px;">
                                {{ number_format($lo->giaBanHienTai) }}đ
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="fw-semibold text-dark" style="font-size: 11.5px;">{{ $lo->hsdFmt }}</div>
                                @if($lo->canhBaoCode === 'DA_QUA_HAN')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size: 10.5px; padding: 2px 6px;">
                                        {{ $lo->canhBaoText }}
                                    </span>
                                @elseif($lo->canhBaoCode === 'HET_HAN_HOM_NAY')
                                    <span class="badge bg-danger text-white fw-bold" style="font-size: 10.5px; padding: 2px 6px;">
                                        {{ $lo->canhBaoText }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold" style="font-size: 10.5px; padding: 2px 6px;">
                                        {{ $lo->canhBaoText }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center" style="font-size: 11px;">
                                @if($lo->kiemTraGanNhat)
                                    <div>
                                        @if($lo->kiemTraGanNhat->ket_qua === 'CON_BAN_DUOC')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">Đạt - Bán thường</span>
                                        @elseif($lo->kiemTraGanNhat->ket_qua === 'FLASH_SALE')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">Đã mở Flash Sale</span>
                                        @else
                                            <span class="badge bg-secondary text-white fw-semibold">Báo hỏng</span>
                                        @endif
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 11px;">
                                        Bởi: {{ $lo->kiemTraGanNhat->nhanVien?->ten_nhan_vien ?? $lo->kiemTraGanNhat->nguoiDung?->ten_dang_nhap ?? 'Staff' }}
                                        ({{ optional($lo->kiemTraGanNhat->thoi_gian_kiem_tra)->format('d/m H:i') }})
                                    </div>
                                @else
                                    <span class="text-muted fst-italic">Chưa kiểm tra</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn-action-process btn-open-modal-decision"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDecision"
                                        data-type="LO_HANG"
                                        data-id="{{ $lo->ma_chi_tiet_nhap }}"
                                        data-name="{{ $lo->ten_san_pham }}"
                                        data-sub="Lô #{{ $lo->ma_chi_tiet_nhap }} • PN #{{ $lo->ma_phieu_nhap }}"
                                        data-stock="{{ $lo->so_luong_con_lai }}"
                                        data-price="{{ $lo->giaBanHienTai }}"
                                        data-hsd="{{ $lo->hsdFmt }}"
                                        data-diff="{{ $lo->diffDays }}"
                                        data-status="{{ $lo->canhBaoText }}">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Kiểm tra & xử lý</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                Tuyệt vời! Hiện không có lô hoa tươi nào trong danh sách cảnh báo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang Lô hoa tươi chuẩn RoseShop --}}
        <div class="rs-table-footer">
            <div class="rs-footer-left">
                <div class="rs-per-page-inline">
                    <span>Hiển thị</span>
                    <select class="rs-per-page-select" onchange="window.location.href = this.value;">
                        @foreach([10, 15, 20, 50, 100] as $s)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $s, 'page_lo' => 1]) }}" {{ (int)request('per_page', $perPage ?? 10) === $s ? 'selected' : '' }}>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>
                    <span>/ trang</span>
                </div>
                <div class="rs-records-count">
                    Hiển thị <strong>{{ $loHangs->firstItem() ?? 0 }}-{{ $loHangs->lastItem() ?? 0 }}</strong> trong tổng số <strong>{{ $loHangs->total() }}</strong> lô hoa
                </div>
            </div>

            <div class="rs-footer-pagination">
                @if($loHangs->hasPages())
                    <ul class="rs-pagination">
                        {{-- Nút trang trước --}}
                        @if($loHangs->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->previousPageUrl() }}">&laquo;</a></li>
                        @endif

                        {{-- Các số trang (rút gọn nếu quá nhiều trang) --}}
                        @php
                            $curPage = $loHangs->currentPage();
                            $lastPage = $loHangs->lastPage();
                            $startPage = max(1, $curPage - 2);
                            $endPage = min($lastPage, $curPage + 2);
                        @endphp

                        @if($startPage > 1)
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->url(1) }}">1</a></li>
                            @if($startPage > 2)
                                <li class="page-item disabled"><span class="page-link ellipsis">...</span></li>
                            @endif
                        @endif

                        @for($i = $startPage; $i <= $endPage; $i++)
                            <li class="page-item {{ $i === $curPage ? 'active' : '' }}">
                                @if($i === $curPage)
                                    <span class="page-link">{{ $i }}</span>
                                @else
                                    <a class="page-link" href="{{ $loHangs->url($i) }}">{{ $i }}</a>
                                @endif
                            </li>
                        @endfor

                        @if($endPage < $lastPage)
                            @if($endPage < $lastPage - 1)
                                <li class="page-item disabled"><span class="page-link ellipsis">...</span></li>
                            @endif
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->url($lastPage) }}">{{ $lastPage }}</a></li>
                        @endif

                        {{-- Nút trang kế --}}
                        @if($loHangs->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->nextPageUrl() }}">&raquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- PHẦN 2: CÁC BÓ HOA THÀNH PHẨM CẬN / HẾT HẠN --}}
    {{-- ========================================================================= --}}
    <div class="exp-section-card">
        <div class="exp-section-header">
            <div class="exp-section-title">
                <i class="bi bi-box2-heart text-warning fs-5"></i>
                <h2>Bó hoa thành phẩm cần kiểm tra chất lượng</h2>
                <span class="badge-count" style="background:#FEF0C7; color:#B54708;">{{ $boHoas->total() }} bó</span>
            </div>
            <div>
                <a href="{{ route('admin.mau-bo-hoa') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-2 px-3">
                    <i class="bi bi-flower3 me-1"></i> Mẫu bó hoa & Kho bó
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="exp-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">STT</th>
                        <th>Bó hoa thành phẩm</th>
                        <th class="text-center">Tồn kho</th>
                        <th class="text-end">Giá niêm yết</th>
                        <th class="text-center">HSD & Cảnh báo</th>
                        <th class="text-center">Kiểm tra chất lượng</th>
                        <th class="text-center" style="width: 170px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($boHoas as $idx => $bh)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $boHoas->firstItem() ? ($boHoas->firstItem() + $idx) : ($idx + 1) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rs-product-thumb">
                                        @if($bh->hinh_anh)
                                            <img src="{{ asset('storage/' . $bh->hinh_anh) }}"
                                                 alt="{{ $bh->ten_bo_hoa }}"
                                                 class="rs-product-thumb-img"
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'rs-product-thumb-placeholder\' style=\'background:#FEF0C7; color:#DC6803;\'><i class=\'bi bi-flower3\'></i></div>';">
                                        @else
                                            <div class="rs-product-thumb-placeholder" style="background:#FEF0C7; color:#DC6803;"><i class="bi bi-flower3"></i></div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 12.5px;">{{ $bh->ten_bo_hoa }}</div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            Mã bó: <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 11px;">#{{ $bh->ma_bo_hoa }}</span>
                                            @if($bh->ten_mau) • Mẫu: {{ $bh->ten_mau }} @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark" style="font-size: 12.5px;">{{ number_format($bh->so_luong) }}</span>
                                <span class="text-muted" style="font-size: 11px;">bó</span>
                            </td>
                            <td class="text-end fw-bold text-dark" style="font-size: 12.5px;">
                                {{ number_format($bh->gia_ban) }}đ
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="fw-semibold text-dark" style="font-size: 11.5px;">{{ $bh->hsdFmt }}</div>
                                @if($bh->canhBaoCode === 'DA_QUA_HAN')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size: 10.5px; padding: 2px 6px;">
                                        {{ $bh->canhBaoText }}
                                    </span>
                                @elseif($bh->canhBaoCode === 'HET_HAN_HOM_NAY')
                                    <span class="badge bg-danger text-white fw-bold" style="font-size: 10.5px; padding: 2px 6px;">
                                        {{ $bh->canhBaoText }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold" style="font-size: 10.5px; padding: 2px 6px;">
                                        {{ $bh->canhBaoText }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center" style="font-size: 11px;">
                                @if($bh->kiemTraGanNhat)
                                    <div>
                                        @if($bh->kiemTraGanNhat->ket_qua === 'CON_BAN_DUOC')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">Đạt - Bán thường</span>
                                        @elseif($bh->kiemTraGanNhat->ket_qua === 'FLASH_SALE')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">Đã mở Flash Sale</span>
                                        @else
                                            <span class="badge bg-secondary text-white fw-semibold">Báo hỏng</span>
                                        @endif
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 11px;">
                                        Bởi: {{ $bh->kiemTraGanNhat->nhanVien?->ten_nhan_vien ?? $bh->kiemTraGanNhat->nguoiDung?->ten_dang_nhap ?? 'Staff' }}
                                        ({{ optional($bh->kiemTraGanNhat->thoi_gian_kiem_tra)->format('d/m H:i') }})
                                    </div>
                                @else
                                    <span class="text-muted fst-italic">Chưa kiểm tra</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn-action-process btn-open-modal-decision"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDecision"
                                        data-type="BO_HOA"
                                        data-id="{{ $bh->ma_bo_hoa }}"
                                        data-name="{{ $bh->ten_bo_hoa }}"
                                        data-sub="Bó hoa thành phẩm #{{ $bh->ma_bo_hoa }} @if($bh->ten_mau)({{ $bh->ten_mau }})@endif"
                                        data-stock="{{ $bh->so_luong }}"
                                        data-price="{{ $bh->gia_ban }}"
                                        data-hsd="{{ $bh->hsdFmt }}"
                                        data-diff="{{ $bh->diffDays }}"
                                        data-status="{{ $bh->canhBaoText }}">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Kiểm tra & xử lý</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                Tuyệt vời! Hiện không có bó hoa thành phẩm nào trong danh sách cảnh báo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang Bó hoa thành phẩm chuẩn RoseShop --}}
        <div class="rs-table-footer">
            <div class="rs-footer-left">
                <div class="rs-per-page-inline">
                    <span>Hiển thị</span>
                    <select class="rs-per-page-select" onchange="window.location.href = this.value;">
                        @foreach([10, 15, 20, 50, 100] as $s)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $s, 'page_bo' => 1]) }}" {{ (int)request('per_page', $perPage ?? 10) === $s ? 'selected' : '' }}>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>
                    <span>/ trang</span>
                </div>
                <div class="rs-records-count">
                    Hiển thị <strong>{{ $boHoas->firstItem() ?? 0 }}-{{ $boHoas->lastItem() ?? 0 }}</strong> trong tổng số <strong>{{ $boHoas->total() }}</strong> bó hoa
                </div>
            </div>

            <div class="rs-footer-pagination">
                @if($boHoas->hasPages())
                    <ul class="rs-pagination">
                        {{-- Nút trang trước --}}
                        @if($boHoas->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $boHoas->previousPageUrl() }}">&laquo;</a></li>
                        @endif

                        {{-- Các số trang (rút gọn nếu quá nhiều trang) --}}
                        @php
                            $curPageBo = $boHoas->currentPage();
                            $lastPageBo = $boHoas->lastPage();
                            $startPageBo = max(1, $curPageBo - 2);
                            $endPageBo = min($lastPageBo, $curPageBo + 2);
                        @endphp

                        @if($startPageBo > 1)
                            <li class="page-item"><a class="page-link" href="{{ $boHoas->url(1) }}">1</a></li>
                            @if($startPageBo > 2)
                                <li class="page-item disabled"><span class="page-link ellipsis">...</span></li>
                            @endif
                        @endif

                        @for($i = $startPageBo; $i <= $endPageBo; $i++)
                            <li class="page-item {{ $i === $curPageBo ? 'active' : '' }}">
                                @if($i === $curPageBo)
                                    <span class="page-link">{{ $i }}</span>
                                @else
                                    <a class="page-link" href="{{ $boHoas->url($i) }}">{{ $i }}</a>
                                @endif
                            </li>
                        @endfor

                        @if($endPageBo < $lastPageBo)
                            @if($endPageBo < $lastPageBo - 1)
                                <li class="page-item disabled"><span class="page-link ellipsis">...</span></li>
                            @endif
                            <li class="page-item"><a class="page-link" href="{{ $boHoas->url($lastPageBo) }}">{{ $lastPageBo }}</a></li>
                        @endif

                        {{-- Nút trang kế --}}
                        @if($boHoas->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $boHoas->nextPageUrl() }}">&raquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ========================================================================= --}}
{{-- MODAL CHUNG: KIỂM TRA CHẤT LƯỢNG & RA QUYẾT ĐỊNH XỬ LÝ --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="modalDecision" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #FDEAF3; color: #EC3D8F; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 fs-6">Kiểm tra thực tế & Ra quyết định xử lý</h5>
                        <div class="text-muted" style="font-size: 12px;">Hạn sử dụng chỉ là cảnh báo — Người vận hành quyết định phương án</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                {{-- Thông tin sản phẩm --}}
                <div class="p-3 rounded-3 bg-light border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold text-dark fs-6" id="mItemName">—</div>
                        <span id="mItemBadgeStatus" class="badge bg-danger text-white fw-bold">Cảnh báo HSD</span>
                    </div>
                    <div class="row g-2" style="font-size: 13px;">
                        <div class="col-md-6"><span class="text-muted">Phân loại & Mã:</span> <span class="fw-semibold text-dark" id="mItemSub">—</span></div>
                        <div class="col-md-6"><span class="text-muted">Tồn kho hiện tại:</span> <span class="fw-bold text-danger fs-6" id="mItemStock">—</span></div>
                        <div class="col-md-6"><span class="text-muted">Giá bán hiện tại:</span> <span class="fw-bold text-dark" id="mItemPrice">—</span></div>
                        <div class="col-md-6"><span class="text-muted">Hạn sử dụng:</span> <span class="fw-semibold text-dark" id="mItemHsd">—</span></div>
                    </div>
                </div>

                {{-- Tabs 3 Lựa chọn --}}
                <div class="decision-nav">
                    <button type="button" class="decision-tab-btn active" data-target="#paneBanTiep">
                        <i class="bi bi-check-circle me-1 text-success"></i> 1. Bán tiếp giá thường
                    </button>
                    <button type="button" class="decision-tab-btn" data-target="#paneFlashSale">
                        <i class="bi bi-lightning-charge me-1 text-danger"></i> 2. Đưa vào Flash Sale
                    </button>
                    <button type="button" class="decision-tab-btn" data-target="#paneBaoHong">
                        <i class="bi bi-trash3 me-1 text-secondary"></i> 3. Báo hỏng
                    </button>
                </div>

                {{-- PANEL 1: BÁN TIẾP GIÁ THƯỜNG --}}
                <div class="decision-pane" id="paneBanTiep">
                    <form action="{{ route('admin.san-pham-het-han.ban-tiep') }}" method="POST">
                        @csrf
                        <input type="hidden" name="loai_doi_tuong" class="input-form-type" value="">
                        <input type="hidden" name="id" class="input-form-id" value="">

                        <div class="alert alert-info border-0 rounded-3 mb-3" style="background:#EBF5FF; color:#1E429F; font-size:12.5px;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            Nếu sản phẩm cận hạn hoặc quá hạn nhưng Staff/Admin đã kiểm tra thực tế và xác nhận chất lượng hoa vẫn đạt chuẩn (cánh không dập, thân cứng, màu sắc tươi tốt), hệ thống cho phép tiếp tục bán giá thường và ghi nhận lịch sử kiểm tra.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark" style="font-size:13px;">
                                Ghi chú / Nhận xét kiểm tra chất lượng <span class="text-danger">*</span>:
                            </label>
                            <textarea name="ghi_chu" class="form-control" rows="3" required
                                      placeholder="Ví dụ: Hoa vẫn tươi, cánh không dập, thân cứng, đủ điều kiện tiếp tục bán giá thường."></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted" style="font-size:12.5px;">Nhân viên kiểm tra:</label>
                                <select name="ma_nhan_vien" class="form-select form-select-sm">
                                    @foreach($nhanViens as $nv)
                                        <option value="{{ $nv->ma_nhan_vien }}" {{ Auth::user()?->nhanVien?->ma_nhan_vien == $nv->ma_nhan_vien ? 'selected' : '' }}>
                                            {{ $nv->ten_nhan_vien }} (#{{ $nv->ma_nhan_vien }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">
                                <i class="bi bi-check2-circle me-1"></i> Xác nhận Bán tiếp giá thường
                            </button>
                        </div>
                    </form>
                </div>

                {{-- PANEL 2: ĐƯA VÀO FLASH SALE --}}
                <div class="decision-pane d-none" id="paneFlashSale">
                    <form action="{{ route('admin.san-pham-het-han.flash-sale') }}" method="POST" id="formFlashSale">
                        @csrf
                        <input type="hidden" name="loai_doi_tuong" class="input-form-type" value="">
                        <input type="hidden" name="id" class="input-form-id" value="">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size:13px;">
                                    Nguồn Flash Sale <span class="text-danger">*</span>:
                                </label>
                                <select name="nguon" id="fsNguon" class="form-select form-select-sm" required>
                                    <option value="CAN_HAN">Cận hạn (còn 1-2 ngày HSD)</option>
                                    <option value="HET_HAN">Hết hạn (kiểm tra đạt tiêu chuẩn sale)</option>
                                    <option value="HANG_CO_VAN_DE">Hàng có vấn đề thẩm mỹ nhẹ</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size:13px;">
                                    Số lượng đưa Flash Sale <span class="text-danger">*</span>:
                                </label>
                                <input type="number" name="so_luong" id="fsSoLuong" class="form-control form-control-sm"
                                       min="1" value="1" required>
                                <div class="text-muted mt-1" style="font-size:11.5px;">Tồn khả dụng tối đa: <span id="fsMaxStock" class="fw-bold text-dark">0</span></div>
                            </div>
                        </div>

                        {{-- Chọn 3 mức giảm giá mặc định 30%, 50%, 75% --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark d-block" style="font-size:13px;">
                                Mức giảm giá Flash Sale <span class="text-danger">*</span> (Tính trên Giá bán hiện tại):
                            </label>
                            <input type="hidden" name="ti_le_giam" id="fsTiLeGiam" value="50">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn-discount-preset" data-rate="30">
                                    <i class="bi bi-tag-fill me-1"></i> Giảm 30%
                                </button>
                                <button type="button" class="btn-discount-preset active" data-rate="50">
                                    <i class="bi bi-fire me-1"></i> Giảm 50%
                                </button>
                                <button type="button" class="btn-discount-preset" data-rate="75">
                                    <i class="bi bi-lightning-fill me-1"></i> Giảm 75%
                                </button>
                            </div>
                        </div>

                        {{-- Giá Flash Sale realtime --}}
                        <div class="p-3 rounded-3 bg-light border mb-3 d-flex justify-content-around align-items-center text-center">
                            <div>
                                <div class="text-muted" style="font-size:12px;">Giá gốc bán thường:</div>
                                <div class="fw-bold text-dark fs-6" id="fsGiaGocText">0đ</div>
                            </div>
                            <div class="text-muted fs-4">→</div>
                            <div>
                                <div class="text-danger fw-semibold" style="font-size:12px;">Giá Flash Sale (-<span id="fsRateText">50</span>%):</div>
                                <div class="fw-bold text-danger fs-5" id="fsGiaSaleText">0đ</div>
                            </div>
                        </div>

                        {{-- Thời gian --}}
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark" style="font-size:12.5px;">Thời gian bắt đầu:</label>
                                <input type="datetime-local" name="bat_dau" id="fsBatDau" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark d-flex justify-content-between align-items-center" style="font-size:12.5px;">
                                    <span>Thời gian kết thúc:</span>
                                    <span class="d-flex gap-1">
                                        <button type="button" class="btn-duration-quick" data-hours="6">+6h</button>
                                        <button type="button" class="btn-duration-quick" data-hours="12">+12h</button>
                                        <button type="button" class="btn-duration-quick" data-hours="24">+24h</button>
                                    </span>
                                </label>
                                <input type="datetime-local" name="ket_thuc" id="fsKetThuc" class="form-control form-control-sm" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark" style="font-size:12.5px;">Ghi chú kiểm tra chất lượng / Mô tả vấn đề:</label>
                            <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Ghi nhận tình trạng sản phẩm khi mở Flash Sale..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold" style="background:#EC3D8F; border-color:#EC3D8F;">
                                <i class="bi bi-lightning-charge me-1"></i> Xác nhận Đưa vào Flash Sale
                            </button>
                        </div>
                    </form>
                </div>

                {{-- PANEL 3: BÁO HỎNG --}}
                <div class="decision-pane d-none" id="paneBaoHong">
                    <form action="{{ route('admin.san-pham-het-han.bao-hong') }}" method="POST">
                        @csrf
                        <input type="hidden" name="loai_doi_tuong" class="input-form-type" value="">
                        <input type="hidden" name="id" class="input-form-id" value="">

                        <div class="alert alert-danger border-0 rounded-3 mb-3" style="background:#FEF3F2; color:#B42318; font-size:12.5px;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Sản phẩm không đạt chất lượng (hoa héo úa, cánh dập nát, form hỏng) sẽ được ghi nhận vào Báo cáo hàng hỏng và trừ thẳng vào số lượng tồn kho vật lý.
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size:13px;">
                                    Số lượng báo hỏng <span class="text-danger">*</span>:
                                </label>
                                <input type="number" name="so_luong_hong" id="bhSoLuong" class="form-control form-control-sm"
                                       min="1" value="1" required>
                                <div class="text-muted mt-1" style="font-size:11.5px;">Tồn kho tối đa: <span id="bhMaxStock" class="fw-bold text-dark">0</span></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark" style="font-size:13px;">
                                    Lý do hỏng <span class="text-danger">*</span>:
                                </label>
                                <select name="ly_do" class="form-select form-select-sm" required>
                                    <option value="Hoa héo úa quá hạn sử dụng">Hoa héo úa quá hạn sử dụng</option>
                                    <option value="Cánh hoa thâm đen, dập nát">Cánh hoa thâm đen, dập nát</option>
                                    <option value="Thân hoa thối gãy, úng nước">Thân hoa thối gãy, úng nước</option>
                                    <option value="Mất form bó, hoa không hồi phục">Mất form bó, hoa không hồi phục</option>
                                    <option value="Khác">Lý do khác...</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark" style="font-size:12.5px;">Ghi chú chi tiết kiểm tra:</label>
                            <textarea name="ghi_chu" class="form-control" rows="2" placeholder="Chi tiết tình trạng hỏng..."></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted" style="font-size:12.5px;">Nhân viên báo hỏng:</label>
                                <select name="ma_nhan_vien" class="form-select form-select-sm">
                                    @foreach($nhanViens as $nv)
                                        <option value="{{ $nv->ma_nhan_vien }}" {{ Auth::user()?->nhanVien?->ma_nhan_vien == $nv->ma_nhan_vien ? 'selected' : '' }}>
                                            {{ $nv->ten_nhan_vien }} (#{{ $nv->ma_nhan_vien }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">
                                <i class="bi bi-trash3-fill me-1"></i> Xác nhận Báo hỏng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalDecision');
    if (!modalEl) return;

    let currentPrice = 0;
    let currentStock = 0;

    // Format helper
    const formatVND = (num) => new Intl.NumberFormat('vi-VN').format(Math.round(num)) + 'đ';

    function toLocalISOString(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return date.getFullYear() + '-' +
            pad(date.getMonth() + 1) + '-' +
            pad(date.getDate()) + 'T' +
            pad(date.getHours()) + ':' +
            pad(date.getMinutes());
    }

    // Tabs switching
    document.querySelectorAll('.decision-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.decision-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.decision-pane').forEach(p => p.classList.add('d-none'));

            this.classList.add('active');
            const target = document.querySelector(this.dataset.target);
            if (target) target.classList.remove('d-none');
        });
    });

    // Discount rate preset buttons (30%, 50%, 75%)
    document.querySelectorAll('.btn-discount-preset').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-discount-preset').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const rate = parseInt(this.dataset.rate, 10);
            document.getElementById('fsTiLeGiam').value = rate;
            document.getElementById('fsRateText').textContent = rate;

            updateFlashSalePrice();
        });
    });

    function updateFlashSalePrice() {
        const rate = parseInt(document.getElementById('fsTiLeGiam').value, 10) || 50;
        const salePrice = currentPrice * (1 - rate / 100);
        document.getElementById('fsGiaSaleText').textContent = formatVND(salePrice);
    }

    // Quick duration (+6h, +12h, +24h)
    document.querySelectorAll('.btn-duration-quick').forEach(btn => {
        btn.addEventListener('click', function () {
            const hours = parseInt(this.dataset.hours, 10);
            const startVal = document.getElementById('fsBatDau').value;
            const startDate = startVal ? new Date(startVal) : new Date();

            const endDate = new Date(startDate.getTime() + hours * 60 * 60 * 1000);
            document.getElementById('fsKetThuc').value = toLocalISOString(endDate);
        });
    });

    // Populate data into modal
    function populateDecisionModal(btn) {
        if (!btn) return;
        const type = btn.dataset.type;
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const sub = btn.dataset.sub;
        currentStock = parseInt(btn.dataset.stock, 10) || 1;
        currentPrice = parseFloat(btn.dataset.price) || 0;
        const hsd = btn.dataset.hsd;
        const diff = parseInt(btn.dataset.diff, 10);
        const status = btn.dataset.status;

        // Fill header info
        const elName = document.getElementById('mItemName');
        const elSub = document.getElementById('mItemSub');
        const elStock = document.getElementById('mItemStock');
        const elPrice = document.getElementById('mItemPrice');
        const elHsd = document.getElementById('mItemHsd');
        const elStatus = document.getElementById('mItemBadgeStatus');

        if (elName) elName.textContent = name || '—';
        if (elSub) elSub.textContent = sub || '—';
        if (elStock) elStock.textContent = currentStock;
        if (elPrice) elPrice.textContent = formatVND(currentPrice);
        if (elHsd) elHsd.textContent = hsd || '—';
        if (elStatus) elStatus.textContent = status || 'Cảnh báo';

        // Set hidden inputs in all 3 forms
        document.querySelectorAll('.input-form-type').forEach(inp => inp.value = type);
        document.querySelectorAll('.input-form-id').forEach(inp => inp.value = id);

        // Setup Flash Sale defaults
        const fsMaxStock = document.getElementById('fsMaxStock');
        const fsSoLuong = document.getElementById('fsSoLuong');
        const fsGiaGocText = document.getElementById('fsGiaGocText');
        if (fsMaxStock) fsMaxStock.textContent = currentStock;
        if (fsSoLuong) {
            fsSoLuong.max = currentStock;
            fsSoLuong.value = Math.min(1, currentStock);
        }
        if (fsGiaGocText) fsGiaGocText.textContent = formatVND(currentPrice);

        // Auto set source based on HSD
        const fsNguon = document.getElementById('fsNguon');
        if (fsNguon) {
            fsNguon.value = (diff <= 0) ? 'HET_HAN' : 'CAN_HAN';
        }

        // Datetimes
        const now = new Date();
        const elBatDau = document.getElementById('fsBatDau');
        const elKetThuc = document.getElementById('fsKetThuc');
        if (elBatDau) elBatDau.value = toLocalISOString(now);
        if (elKetThuc) {
            const defaultEnd = new Date(now.getTime() + 24 * 60 * 60 * 1000);
            elKetThuc.value = toLocalISOString(defaultEnd);
        }

        updateFlashSalePrice();

        // Setup Báo hỏng defaults
        const bhMaxStock = document.getElementById('bhMaxStock');
        const bhSoLuong = document.getElementById('bhSoLuong');
        if (bhMaxStock) bhMaxStock.textContent = currentStock;
        if (bhSoLuong) {
            bhSoLuong.max = currentStock;
            bhSoLuong.value = 1;
        }

        // Reset tabs to Tab 1
        document.querySelectorAll('.decision-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.decision-pane').forEach(p => p.classList.add('d-none'));
        const tab1 = document.querySelector('.decision-tab-btn[data-target="#paneBanTiep"]');
        if (tab1) tab1.classList.add('active');
        const pane1 = document.getElementById('paneBanTiep');
        if (pane1) pane1.classList.remove('d-none');
    }

    // Event delegation on click
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-open-modal-decision');
        if (!btn) return;
        populateDecisionModal(btn);
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }
    });

    // Also hook Bootstrap's show.bs.modal event
    modalEl.addEventListener('show.bs.modal', function (event) {
        if (event.relatedTarget) {
            populateDecisionModal(event.relatedTarget);
        }
    });
});
</script>
@endpush