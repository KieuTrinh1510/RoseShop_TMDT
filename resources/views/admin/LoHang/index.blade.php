@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
    <style>
        .ncc-table {
            min-width: 1200px;
            width: 100%;
        }
        .ncc-table thead tr {
            background-color: #F8F9FA !important;
        }
        .ncc-table th {
            background-color: #F8F9FA !important;
            color: #475467 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            border-bottom: 1px solid #EAECF0 !important;
            white-space: nowrap !important;
            padding: 10px 8px !important;
        }
        .ncc-table td {
            padding: 10px 8px !important;
            font-size: 13px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #EAECF0 !important;
        }
        .ncc-table tbody tr:hover {
            background-color: #F9FAFB !important;
        }
        .ncc-filter-grid {
            grid-template-columns: 1.5fr 1.3fr 1.2fr 1fr auto;
        }
        .badge-quality-dat,
        .badge-quality-covande,
        .badge-quality-khongdat,
        .badge-quality-chuadanhgia {
            white-space: nowrap !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
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
            color: #475467;
            cursor: pointer;
            transition: all 0.15s ease;
            padding: 0;
            text-decoration: none;
            font-size: 14px;
        }
        .rs-action-btn:hover {
            background-color: #F9FAFB !important;
            color: #EC3D8F !important;
            border-color: #EC3D8F !important;
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
            font-size: 13px;
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
            font-size: 13px;
            color: #667085;
            white-space: nowrap;
        }
        .rs-pagination, .rs-pagination .pagination {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0;
            padding: 0;
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
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }
        .rs-pagination .page-item.active .page-link,
        .page-item.active .page-link,
        .active > .page-link {
            background-color: #EC3D8F !important;
            background: #EC3D8F !important;
            border-color: #EC3D8F !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            box-shadow: 0 1px 3px rgba(236, 61, 143, 0.25) !important;
        }
    </style>
@endpush

@section('content')
<div class="ncc-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Quản lý lô hàng</h1>
            <p class="rs-page-subtitle">Theo dõi danh sách các lô hàng, kiểm soát tồn kho theo lô và đánh giá chất lượng nguồn hàng.</p>
        </div>
        <div class="ncc-header-actions">
            <a href="{{ route('phieu-nhap.index') }}" class="ncc-btn-primary">
                <i class="bi bi-plus-circle"></i>
                <span>Tạo phiếu nhập mới</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Box --}}
    <div class="ncc-filter-box">
        <form method="GET" action="{{ route('admin.lo-hang') }}">
            <div class="ncc-filter-grid">
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Tìm kiếm</label>
                    <input type="text"
                           name="keyword"
                           class="form-control"
                           placeholder="Mã lô, mã SP, mã PN, tên SP..."
                           value="{{ request('keyword') }}">
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Nhà cung cấp</label>
                    <select name="ma_nha_cung_cap" class="form-select">
                        <option value="">-- Tất cả NCC --</option>
                        @foreach($nhaCungCaps as $ncc)
                            <option value="{{ $ncc->ma_nha_cung_cap }}" {{ (string) request('ma_nha_cung_cap') === (string) $ncc->ma_nha_cung_cap ? 'selected' : '' }}>
                                {{ $ncc->ten_nha_cung_cap }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Đánh giá chất lượng</label>
                    <select name="ket_qua" class="form-select">
                        <option value="">-- Tất cả kết quả --</option>
                        <option value="DAT" {{ request('ket_qua') === 'DAT' ? 'selected' : '' }}>Đạt</option>
                        <option value="CO_VAN_DE" {{ request('ket_qua') === 'CO_VAN_DE' ? 'selected' : '' }}>Có vấn đề</option>
                        <option value="KHONG_DAT" {{ request('ket_qua') === 'KHONG_DAT' ? 'selected' : '' }}>Không đạt</option>
                        <option value="CHUA_DANH_GIA" {{ request('ket_qua') === 'CHUA_DANH_GIA' ? 'selected' : '' }}>Chưa đánh giá</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Tình trạng tồn kho</label>
                    <select name="ton_kho" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="con_hang" {{ request('ton_kho') === 'con_hang' ? 'selected' : '' }}>Còn hàng</option>
                        <option value="het_hang" {{ request('ton_kho') === 'het_hang' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn" style="height:38px; font-weight:600; font-size:13px; padding: 0 16px; background-color: #EC3D8F; border-color: #EC3D8F; color: #ffffff; border-radius: 8px;">
                        <i class="bi bi-funnel me-1"></i> Lọc
                    </button>
                    @if(request()->anyFilled(['keyword', 'ma_nha_cung_cap', 'ket_qua', 'ton_kho']))
                        <a href="{{ route('admin.lo-hang') }}" class="btn btn-outline-secondary" style="height:38px; display:inline-flex; align-items:center; border-radius: 8px;" title="Xóa bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Main Table --}}
    <div class="ncc-card">
        <div class="ncc-card-header">
            <h2 class="ncc-card-title">
                <i class="bi bi-boxes"></i>
                Danh sách lô hàng
                <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $loHangs->total() }}</span>
            </h2>
        </div>

        <div class="table-responsive">
            <table class="ncc-table">
                <thead>
                    <tr>
                        <th style="width: 100px; white-space: nowrap;">Mã lô</th>
                        <th style="min-width: 155px;">Mặt hàng</th>
                        <th style="min-width: 145px; white-space: nowrap;">Phiếu nhập</th>
                        <th style="min-width: 130px; white-space: nowrap;">Nhà cung cấp</th>
                        <th class="text-center" style="white-space: nowrap;">Số lượng</th>
                        <th class="text-center" style="white-space: nowrap;">Còn lại</th>
                        <th class="text-end" style="min-width: 105px; white-space: nowrap;">Giá nhập</th>
                        <th class="text-center" style="white-space: nowrap;">Trạng thái</th>
                        <th class="text-center" style="min-width: 125px; white-space: nowrap;">Chất lượng</th>
                        <th class="text-center" style="min-width: 135px; white-space: nowrap;">Hao hụt & Lỗi QC</th>
                        <th class="text-end" style="min-width: 125px; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loHangs as $lo)
                        @php
                            $dg = $lo->danhGiaMoiNhat;
                            $tongHongLo = $lo->baoCaoHangHongs->sum('so_luong_hong');
                        @endphp
                        <tr>
                            <td style="white-space: nowrap;">
                                <span class="fw-bold" style="font-family: monospace; font-size: 11.5px; color: #EC3D8F; white-space: nowrap;">#CTN-{{ $lo->ma_chi_tiet_nhap }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark" style="font-size: 13px; line-height: 1.35;">{{ $lo->sanPham->ten_san_pham ?? 'Sản phẩm #' . $lo->ma_san_pham }}</div>
                                <small style="white-space: nowrap; font-size: 11px;">Mã SP: <span class="fw-bold" style="color:#EC3D8F; font-family: monospace;">#{{ $lo->ma_san_pham }}</span></small>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark" style="font-size: 12.5px; white-space: nowrap;">#PN-{{ $lo->ma_phieu_nhap }}</span>
                                <div class="mt-1 d-flex flex-column gap-1" style="font-size:11.5px; line-height:1.35;">
                                    <div class="text-nowrap" title="Ngày nhập lô hàng">
                                        <span class="text-muted"><i class="bi bi-calendar-event me-1"></i>Nhập:</span>
                                        <span class="fw-medium text-dark">{{ $lo->phieuNhap?->ngay_nhap ? \Carbon\Carbon::parse($lo->phieuNhap->ngay_nhap)->format('d/m/Y') : ($lo->created_at ? $lo->created_at->format('d/m/Y') : '—') }}</span>
                                    </div>
                                    @php
                                        $isLoaiHoa = in_array($lo->sanPham?->loai_san_pham, [\App\Models\SanPham::LOAI_HOA_TUOI, \App\Models\SanPham::LOAI_CHAU_HOA_TUOI]);
                                        $hsd = $lo->ngay_het_han ? \Carbon\Carbon::parse($lo->ngay_het_han)->startOfDay() : ($isLoaiHoa && $lo->created_at ? $lo->created_at->copy()->addDays(7)->startOfDay() : null);
                                    @endphp
                                    @if($isLoaiHoa || $lo->ngay_het_han)
                                        <div class="text-nowrap">
                                            <span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Hết hạn:</span>
                                            @if($hsd)
                                                @php
                                                    $today = \Carbon\Carbon::today();
                                                    $diffDays = $today->diffInDays($hsd, false);
                                                @endphp
                                                @if($diffDays < 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size:10.5px; padding:2px 5px;" title="Lô hoa đã quá hạn {{ abs($diffDays) }} ngày">
                                                        {{ $hsd->format('d/m/Y') }} (Quá hạn)
                                                    </span>
                                                @elseif($diffDays <= 2)
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold" style="font-size:10.5px; padding:2px 5px;" title="Sắp hết hạn trong {{ $diffDays }} ngày">
                                                        {{ $hsd->format('d/m/Y') }} (Còn {{ $diffDays }}n)
                                                    </span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold" style="font-size:10.5px; padding:2px 5px;" title="Còn {{ $diffDays }} ngày sử dụng">
                                                        {{ $hsd->format('d/m/Y') }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted fst-italic">—</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                @if($lo->phieuNhap?->nhaCungCap)
                                    <a href="{{ route('admin.nha-cung-cap.show', $lo->phieuNhap->nhaCungCap->ma_nha_cung_cap) }}" class="text-decoration-none fw-semibold" style="color:#EC3D8F; font-size: 12.5px;">
                                        {{ $lo->phieuNhap->nhaCungCap->ten_nha_cung_cap }}
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size: 12.5px;">{{ $lo->phieuNhap->ten_nha_cung_cap ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="text-center fw-semibold" style="white-space: nowrap;">
                                {{ $lo->so_luong }}
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                @if($lo->so_luong_con_lai > 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        {{ $lo->so_luong_con_lai }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        0 (Hết)
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-dark" style="white-space: nowrap; font-size: 13px;">
                                {{ number_format($lo->gia_nhap, 0, ',', '.') }} đ
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                @if($lo->so_luong_con_lai > 0)
                                    <span class="badge" style="font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 12px; background: #ECFDF3; color: #027A48; border: 1px solid #ABEFC6; white-space: nowrap;">Còn hàng</span>
                                @else
                                    <span class="badge" style="font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 12px; background: #F2F4F7; color: #344054; border: 1px solid #D0D5DD; white-space: nowrap;">Hết hàng</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                @if($dg)
                                    @if($dg->ket_qua === 'DAT')
                                        <span class="badge-quality-dat"><i class="bi bi-check-circle me-1"></i>Đạt</span>
                                    @elseif($dg->ket_qua === 'CO_VAN_DE')
                                        <span class="badge-quality-covande" title="{{ $dg->ly_do }}"><i class="bi bi-exclamation-triangle me-1"></i>Có vấn đề</span>
                                    @elseif($dg->ket_qua === 'KHONG_DAT')
                                        <span class="badge-quality-khongdat" title="{{ $dg->ly_do }}"><i class="bi bi-x-circle me-1"></i>Không đạt</span>
                                    @endif
                                @else
                                    <span class="badge-quality-chuadanhgia">Chưa đánh giá</span>
                                @endif
                            </td>
                            <td style="font-size: 12px; line-height: 1.4;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted" title="Lỗi ghi nhận từ QC (không trừ tồn kho)">Lỗi QC:</span>
                                    @if($dg && $dg->so_luong_loi > 0)
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold px-1 py-0" title="Phát hiện qua kiểm định QC (chưa trừ kho)">{{ $dg->so_luong_loi }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted" title="Hàng hỏng / hao hụt đã thực tế trừ kho">Đã trừ hỏng:</span>
                                    @if($tongHongLo > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-1 py-0" title="Đã trừ vào tồn kho thực tế">{{ $tongHongLo }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end" style="white-space: nowrap;">
                                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                                    <a href="{{ route('admin.lo-hang.show', $lo->ma_chi_tiet_nhap) }}"
                                       class="rs-action-btn"
                                       title="Xem chi tiết lô">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($lo->so_luong_con_lai > 0)
                                        <button type="button"
                                                class="rs-action-btn"
                                                title="{{ $dg ? 'Chỉnh sửa đánh giá lô hàng' : 'Đánh giá chất lượng lô hàng' }}"
                                                onclick="openModalDanhGia({{ $lo->ma_chi_tiet_nhap }}, '{{ addslashes($lo->sanPham->ten_san_pham ?? '') }}', {{ $lo->so_luong }}, '{{ $dg?->ket_qua ?? '' }}', {{ $dg?->so_luong_loi ?? 0 }}, '{{ addslashes($dg?->ly_do ?? '') }}', '{{ addslashes($dg?->ghi_chu ?? '') }}')">
                                            <i class="bi {{ $dg ? 'bi-pencil-square' : 'bi-clipboard-check' }}"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="ncc-empty-state">
                                <div class="ncc-empty-icon">
                                    <i class="bi bi-boxes"></i>
                                </div>
                                <p class="mb-0 fw-semibold">Không tìm thấy lô hàng phù hợp.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN ROSESHOP --}}
        @if($loHangs->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage, 'page' => 1]) }}" {{ (request('per_page', $loHangs->perPage()) == $perPage) ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <span class="rs-records-count">
                        {{ $loHangs->firstItem() ?? 0 }}-{{ $loHangs->lastItem() ?? 0 }} / {{ $loHangs->total() }} bản ghi
                    </span>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($loHangs->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $loHangs->currentPage();
                            $lastPage = $loHangs->lastPage();
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
                                <li class="page-item"><a class="page-link" href="{{ $loHangs->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($loHangs->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    </div>

</div>

{{-- MODAL ĐÁNH GIÁ CHẤT LƯỢNG LÔ HÀNG --}}
<div class="modal fade" id="modalDanhGiaLo" tabindex="-1" aria-labelledby="modalDanhGiaLoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; overflow:hidden;">
            <form id="formDanhGiaLo" method="POST" action="">
                @csrf
                <div class="modal-header" style="background:#FAFAFA; border-bottom:1px solid #EAECF0;">
                    <h5 class="modal-title fw-bold" id="modalDanhGiaLoLabel" style="font-size:16px;">
                        <i class="bi bi-shield-check text-primary me-2"></i>Đánh giá chất lượng lô hàng
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 p-3 bg-light rounded-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                            <span class="text-muted">Mã lô:</span>
                            <strong id="modal_ma_lo">—</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                            <span class="text-muted">Mặt hàng:</span>
                            <strong id="modal_ten_sp">—</strong>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span class="text-muted">Số lượng ban đầu:</span>
                            <strong id="modal_so_luong_lo" class="text-primary">—</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kết quả đánh giá <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <div class="form-check flex-fill p-2 border rounded-3 text-center" style="cursor:pointer;" onclick="document.getElementById('kq_dat').click()">
                                <input class="form-check-input ms-0 me-1" type="radio" name="ket_qua" id="kq_dat" value="DAT" checked onchange="onKetQuaChange()">
                                <label class="form-check-label text-success fw-bold" for="kq_dat" style="cursor:pointer;">
                                    Đạt
                                </label>
                            </div>
                            <div class="form-check flex-fill p-2 border rounded-3 text-center" style="cursor:pointer;" onclick="document.getElementById('kq_covande').click()">
                                <input class="form-check-input ms-0 me-1" type="radio" name="ket_qua" id="kq_covande" value="CO_VAN_DE" onchange="onKetQuaChange()">
                                <label class="form-check-label text-warning fw-bold" for="kq_covande" style="cursor:pointer;">
                                    Có vấn đề
                                </label>
                            </div>
                            <div class="form-check flex-fill p-2 border rounded-3 text-center" style="cursor:pointer;" onclick="document.getElementById('kq_khongdat').click()">
                                <input class="form-check-input ms-0 me-1" type="radio" name="ket_qua" id="kq_khongdat" value="KHONG_DAT" onchange="onKetQuaChange()">
                                <label class="form-check-label text-danger fw-bold" for="kq_khongdat" style="cursor:pointer;">
                                    Không đạt
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Số lượng phát hiện lỗi/hỏng (nếu có)
                            <small class="text-muted fw-normal">(Tối đa: <span id="modal_max_loi">0</span> sp)</small>
                        </label>
                        <input type="number" name="so_luong_loi" id="modal_inp_so_luong_loi" class="form-control" min="0" value="0">
                        <small class="text-muted" style="font-size:11px;">
                            * Lưu ý: Đánh giá chất lượng phục vụ kiểm định nguồn hàng, KHÔNG tự trừ tồn kho vận hành.
                        </small>
                    </div>

                    <div class="mb-3" id="group_ly_do" style="display:none;">
                        <label class="form-label fw-semibold">
                            Lý do đánh giá <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="ly_do" id="modal_inp_ly_do" class="form-control" placeholder="VD: Hoa héo dập, sai quy cách...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú thêm</label>
                        <textarea name="ghi_chu" id="modal_inp_ghi_chu" class="form-control" rows="2" placeholder="Ghi chú chi tiết nếu có..."></textarea>
                    </div>

                    <div class="p-2 bg-light rounded-3 d-flex justify-content-between text-muted" style="font-size:12px;">
                        <span><i class="bi bi-person me-1"></i> Người đánh giá: <strong>{{ auth()->user()->nhanVien->ten_nhan_vien ?? auth()->user()->ten_dang_nhap }}</strong></span>
                        <span><i class="bi bi-clock me-1"></i> Tự động ghi nhận</span>
                    </div>
                </div>
                <div class="modal-footer" style="background:#FAFAFA; border-top:1px solid #EAECF0;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" style="background:#EC3D8F; border-color:#EC3D8F; font-weight:600;">
                        <i class="bi bi-check-lg me-1"></i> Lưu kết quả đánh giá
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function onKetQuaChange() {
        const kq = document.querySelector('input[name="ket_qua"]:checked')?.value;
        const grpLyDo = document.getElementById('group_ly_do');
        const inpLyDo = document.getElementById('modal_inp_ly_do');

        if (kq === 'CO_VAN_DE' || kq === 'KHONG_DAT') {
            grpLyDo.style.display = 'block';
            inpLyDo.setAttribute('required', 'required');
        } else {
            grpLyDo.style.display = 'none';
            inpLyDo.removeAttribute('required');
        }
    }

    function openModalDanhGia(maChiTietNhap, tenSp, soLuong, ketQuaHienTai, soLuongLoiHienTai, lyDoHienTai, ghiChuHienTai) {
        document.getElementById('formDanhGiaLo').action = '/admin/lo-hang/' + maChiTietNhap + '/danh-gia';
        document.getElementById('modal_ma_lo').textContent = '#CTN-' + maChiTietNhap;
        document.getElementById('modal_ten_sp').textContent = tenSp;
        document.getElementById('modal_so_luong_lo').textContent = soLuong + ' sản phẩm';
        document.getElementById('modal_max_loi').textContent = soLuong;

        const inpSoLuongLoi = document.getElementById('modal_inp_so_luong_loi');
        inpSoLuongLoi.max = soLuong;
        inpSoLuongLoi.value = soLuongLoiHienTai || 0;

        document.getElementById('modal_inp_ly_do').value = lyDoHienTai || '';
        document.getElementById('modal_inp_ghi_chu').value = ghiChuHienTai || '';

        if (ketQuaHienTai === 'CO_VAN_DE') {
            document.getElementById('kq_covande').checked = true;
        } else if (ketQuaHienTai === 'KHONG_DAT') {
            document.getElementById('kq_khongdat').checked = true;
        } else {
            document.getElementById('kq_dat').checked = true;
        }

        onKetQuaChange();

        new bootstrap.Modal(document.getElementById('modalDanhGiaLo')).show();
    }
</script>
@endpush
@endsection
