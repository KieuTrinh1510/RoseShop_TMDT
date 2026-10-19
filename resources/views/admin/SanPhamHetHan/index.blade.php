@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .exp-container { padding: 24px; max-width: 1440px; margin: 0 auto; }

        /* Stats Cards */
        .exp-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 1200px) { .exp-stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .exp-stats-grid { grid-template-columns: 1fr; } }

        .exp-stat-card { background: #ffffff; border: 1px solid #EAECF0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04); display: flex; align-items: center; gap: 16px; transition: all 0.2s ease; }
        .exp-stat-card:hover { box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08); transform: translateY(-1px); }
        .exp-stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .exp-stat-icon.red { background: #FEE4E2; color: #D92D20; }
        .exp-stat-icon.orange { background: #FEF0C7; color: #DC6803; }
        .exp-stat-icon.purple { background: #F4EBFF; color: #7F56D9; }
        .exp-stat-icon.green { background: #ECFDF3; color: #039855; }
        .exp-stat-info h3 { margin: 0; font-size: 22px; font-weight: 700; color: #101828; line-height: 1.2; }
        .exp-stat-info p { margin: 0; font-size: 13px; color: #667085; font-weight: 500; }
        .exp-stat-info .sub-text { font-size: 11.5px; color: #98A2B3; margin-top: 3px; }

        /* Filter Box */
        .exp-filter-card { background: #ffffff; border: 1px solid #EAECF0; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04); }
        .exp-filter-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .exp-filter-tab { padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; color: #475467; background: #F2F4F7; transition: all 0.15s ease; border: 1px solid transparent; }
        .exp-filter-tab:hover { color: #101828; background: #EAECF0; }
        .exp-filter-tab.active { color: #ffffff; background: #E11D48; border-color: #E11D48; }

        /* Section Container */
        .exp-section-card { background: #ffffff; border: 1px solid #EAECF0; border-radius: 12px; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04); margin-bottom: 30px; overflow: hidden; }
        .exp-section-header { padding: 18px 24px; border-bottom: 1px solid #EAECF0; display: flex; align-items: center; justify-content: space-between; background: #FAFAFA; flex-wrap: wrap; gap: 12px; }
        .exp-section-title { display: flex; align-items: center; gap: 10px; }
        .exp-section-title h2 { margin: 0; font-size: 17px; font-weight: 700; color: #101828; }
        .exp-section-title .badge-count { background: #FEE4E2; color: #B42318; font-size: 12px; font-weight: 700; padding: 3px 9px; border-radius: 12px; }

        .btn-view-inventory { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 13px; font-weight: 600; color: #027A48; background: #ECFDF3; border: 1px solid #A6F4C5; border-radius: 8px; text-decoration: none; transition: all 0.15s ease; }
        .btn-view-inventory:hover { background: #D1FADF; color: #027A48; border-color: #6CE9A6; text-decoration: none; transform: translateY(-1px); }

        /* Tables */
        .exp-table { width: 100%; margin-bottom: 0; border-collapse: collapse; }
        .exp-table th { background: #F9FAFB; color: #475467; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; padding: 12px 18px; border-bottom: 1px solid #EAECF0; white-space: nowrap; }
        .exp-table td { padding: 14px 18px; vertical-align: middle; border-bottom: 1px solid #F2F4F7; font-size: 13.5px; color: #344054; }
        .exp-table tbody tr:hover { background-color: #FDFBFB; }

        .exp-item-thumb { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid #EAECF0; background: #F2F4F7; flex-shrink: 0; }
        .exp-item-placeholder { width: 44px; height: 44px; border-radius: 8px; background: #FDE8ED; color: #E11D48; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }

        /* Buttons */
        .btn-thanh-ly-ncc { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; font-size: 12.5px; font-weight: 600; color: #B42318; background: #FEF3F2; border: 1px solid #FECDCA; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; white-space: nowrap; }
        .btn-thanh-ly-ncc:hover { background: #FEE4E2; border-color: #FDA29B; color: #912018; transform: translateY(-1px); }

        .btn-xu-ly-bo { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; font-size: 12.5px; font-weight: 600; color: #B54708; background: #FFFAEB; border: 1px solid #FEDF89; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; white-space: nowrap; }
        .btn-xu-ly-bo:hover { background: #FEF0C7; border-color: #FEC84B; color: #7A2E0E; transform: translateY(-1px); }

        /* Modal Styles */
        .modal-info-box { background: #F9FAFB; border: 1px solid #EAECF0; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; }
        .modal-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px 16px; font-size: 13px; }
        .modal-info-grid .label { color: #667085; font-weight: 500; }
        .modal-info-grid .val { color: #101828; font-weight: 600; }
    </style>
@endpush

@section('content')
<div class="exp-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Cảnh báo & Xử lý sản phẩm hết hạn</h1>
            <p class="rs-page-subtitle">Kiểm soát các lô hoa tươi và bó hoa mẫu sắp hoặc đã quá hạn, hỗ trợ thanh lý lại cho nhà cung cấp hoặc xả bó tái sử dụng.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-3 px-3 py-2">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="{{ $isAdmin ? route('admin.ton-kho') : route('admin.lo-hang') }}" class="btn btn-dark btn-sm fw-semibold rounded-3 px-3 py-2">
                <i class="bi bi-boxes me-1"></i> Quản lý kho hàng
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="exp-stats-grid">
        <div class="exp-stat-card">
            <div class="exp-stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="exp-stat-info">
                <h3>{{ number_format($thongKe['tongCanXuLy']) }}</h3>
                <p>Tổng sản phẩm cần xử lý</p>
                <div class="sub-text">Gồm lô hoa tươi & bó hoa mẫu</div>
            </div>
        </div>

        <div class="exp-stat-card">
            <div class="exp-stat-icon orange"><i class="bi bi-flower1"></i></div>
            <div class="exp-stat-info">
                <h3>{{ number_format($thongKe['tongLo']) }}</h3>
                <p>Lô hoa tươi hết / sắp hết hạn</p>
                <div class="sub-text">
                    <span class="text-danger fw-semibold">{{ $thongKe['loQuaHan'] }} quá hạn</span> •
                    <span class="text-danger fw-semibold">{{ $thongKe['loHomNay'] }} hôm nay</span> •
                    <span class="text-warning fw-semibold">{{ $thongKe['loSapHet'] }} sắp hết</span>
                </div>
            </div>
        </div>

        <div class="exp-stat-card">
            <div class="exp-stat-icon purple"><i class="bi bi-box2-heart"></i></div>
            <div class="exp-stat-info">
                <h3>{{ number_format($thongKe['tongBo']) }}</h3>
                <p>Bó hoa mẫu hết / sắp hết hạn</p>
                <div class="sub-text">
                    <span class="text-danger fw-semibold">{{ $thongKe['boQuaHan'] }} quá hạn</span> •
                    <span class="text-danger fw-semibold">{{ $thongKe['boHomNay'] }} hôm nay</span>
                </div>
            </div>
        </div>

        <div class="exp-stat-card">
            <div class="exp-stat-icon green"><i class="bi bi-cash-stack"></i></div>
            <div class="exp-stat-info">
                <h3>{{ number_format($thongKe['tongGiaTri']) }}đ</h3>
                <p>Ước tính giá trị tồn</p>
                <div class="sub-text">Cần giải phóng vốn / thanh lý</div>
            </div>
        </div>
    </div>

    {{-- Filter & Search Card --}}
    <div class="exp-filter-card">
        <form method="GET" action="{{ route('admin.san-pham-het-han') }}" class="row g-3 align-items-center">
            <div class="col-lg-6 col-md-12">
                <div class="exp-filter-tabs">
                    <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'all'])) }}"
                       class="exp-filter-tab {{ $filter === 'all' ? 'active' : '' }}">
                        Tất cả cần xử lý ({{ $thongKe['tongCanXuLy'] }})
                    </a>
                    <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'qua_han'])) }}"
                       class="exp-filter-tab {{ $filter === 'qua_han' ? 'active' : '' }}">
                        Đã quá hạn ({{ $thongKe['loQuaHan'] + $thongKe['boQuaHan'] }})
                    </a>
                    <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'hom_nay'])) }}"
                       class="exp-filter-tab {{ $filter === 'hom_nay' ? 'active' : '' }}">
                        Hết hạn hôm nay ({{ $thongKe['loHomNay'] + $thongKe['boHomNay'] }})
                    </a>
                    <a href="{{ route('admin.san-pham-het-han', array_merge(request()->query(), ['filter' => 'sap_het'])) }}"
                       class="exp-filter-tab {{ $filter === 'sap_het' ? 'active' : '' }}">
                        Sắp hết hạn 1-3 ngày ({{ $thongKe['loSapHet'] + $thongKe['boSapHet'] }})
                    </a>
                </div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="keyword" class="form-control border-start-0"
                           placeholder="Tìm tên hoa, mã lô, tên NCC, tên bó..."
                           value="{{ $keyword }}">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <button class="btn btn-outline-secondary px-3" type="submit">Tìm</button>
                    @if($keyword !== '' || $filter !== 'all')
                        <a href="{{ route('admin.san-pham-het-han') }}" class="btn btn-light border px-3" title="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- PHẦN 1: CÁC LOẠI HOA SẮP HẾT HẠN (THEO LÔ HÀNG) --}}
    {{-- ========================================================================= --}}
    <div class="exp-section-card">
        <div class="exp-section-header">
            <div class="exp-section-title">
                <i class="bi bi-flower2 text-danger fs-5"></i>
                <h2>Các loại hoa sắp hết hạn</h2>
                <span class="badge-count">{{ count($loHangs) }} lô</span>
            </div>
            <div>
                <a href="{{ $isAdmin ? route('admin.ton-kho', ['loai_ton_kho' => 'san_pham']) : route('admin.lo-hang') }}"
                   class="btn-view-inventory" title="Xem tồn kho chi tiết các loại hoa">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Bấm xem tồn kho</span>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="exp-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">STT</th>
                        <th>Hoa</th>
                        <th class="text-center">Lô</th>
                        <th class="text-center">Tồn kho</th>
                        <th class="text-center">Ngày nhập</th>
                        <th class="text-center">Ngày hết hạn</th>
                        <th>Nhà cung cấp</th>
                        <th class="text-center" style="width: 210px;">Cần xử lý</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loHangs as $idx => $lo)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($lo->hinh_anh)
                                        <img src="{{ asset('storage/' . $lo->hinh_anh) }}"
                                             alt="{{ $lo->ten_san_pham }}"
                                             class="exp-item-thumb"
                                             onerror="this.onerror=null; this.src='{{ asset($lo->hinh_anh) }}';">
                                    @else
                                        <div class="exp-item-placeholder"><i class="bi bi-flower1"></i></div>
                                    @endif
                                    <div>
                                        <div class="fw-bold text-dark">{{ $lo->ten_san_pham }}</div>
                                        <div class="text-muted" style="font-size: 11.5px;">
                                            Mã SP: #{{ $lo->ma_san_pham }} • ĐVT: {{ $lo->don_vi_tinh ?? 'Cành' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.lo-hang.show', $lo->ma_chi_tiet_nhap) }}"
                                   class="text-decoration-none fw-semibold text-primary"
                                   title="Xem chi tiết lô">
                                    Lô #{{ $lo->ma_chi_tiet_nhap }}
                                </a>
                                <div class="text-muted" style="font-size: 11px;">PN #{{ $lo->ma_phieu_nhap }}</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark fs-6">{{ number_format($lo->so_luong_con_lai) }}</span>
                                <span class="text-muted" style="font-size: 12px;">/ {{ number_format($lo->so_luong) }}</span>
                            </td>
                            <td class="text-center text-nowrap">{{ $lo->ngayNhapFmt }}</td>
                            <td class="text-center text-nowrap">
                                <div class="fw-semibold text-dark">{{ $lo->hsdFmt }}</div>
                                @if($lo->diffDays < 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size: 11px; padding: 2px 7px;">
                                        Quá hạn {{ abs($lo->diffDays) }} ngày
                                    </span>
                                @elseif($lo->diffDays == 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size: 11px; padding: 2px 7px;">
                                        Hôm nay
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold" style="font-size: 11px; padding: 2px 7px;">
                                        Còn {{ $lo->diffDays }} ngày
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $lo->ten_nha_cung_cap ?? '—' }}</div>
                                @if($lo->sdt_ncc)
                                    <a href="tel:{{ $lo->sdt_ncc }}" class="text-muted text-decoration-none" style="font-size: 12px;">
                                        <i class="bi bi-telephone me-1"></i>{{ $lo->sdt_ncc }}
                                    </a>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn-thanh-ly-ncc"
                                        data-action="thanh-ly-ncc"
                                        data-malo="{{ $lo->ma_chi_tiet_nhap }}"
                                        data-mapn="{{ $lo->ma_phieu_nhap }}"
                                        data-tenhoa="{{ $lo->ten_san_pham }}"
                                        data-soluongton="{{ (int) $lo->so_luong_con_lai }}"
                                        data-donvitinh="{{ $lo->don_vi_tinh ?? 'Cành' }}"
                                        data-gianhap="{{ (float) $lo->gia_nhap }}"
                                        data-giatrion="{{ (float) $lo->giaTriTon }}"
                                        data-ngaynhap="{{ $lo->ngayNhapFmt }}"
                                        data-hsd="{{ $lo->hsdFmt }}"
                                        data-diffdays="{{ (int) $lo->diffDays }}"
                                        data-tenncc="{{ $lo->ten_nha_cung_cap ?? 'Chưa rõ' }}"
                                        data-sdtncc="{{ $lo->sdt_ncc ?? '' }}"
                                        data-emailncc="{{ $lo->email_ncc ?? '' }}"
                                        data-diachincc="{{ $lo->dia_chi_ncc ?? '' }}"
                                        title="Bấm vào để báo cáo liên hệ hoàn trả hoặc chiết khấu">
                                    <i class="bi bi-arrow-return-left"></i>
                                    <span>Thanh lý lại cho NCC</span>
                                </button>
                                <div class="text-muted" style="font-size: 10.5px; margin-top: 3px;">
                                    (báo cáo & liên hệ)
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                Tuyệt vời! Hiện không có loại hoa nào sắp hết hạn hoặc quá hạn.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- PHẦN 2: CÁC BÓ HOA SẮP HẾT HẠN (BÓ HOA MẪU / THÀNH PHẨM) --}}
    {{-- ========================================================================= --}}
    <div class="exp-section-card">
        <div class="exp-section-header">
            <div class="exp-section-title">
                <i class="bi bi-gift text-warning fs-5"></i>
                <h2>Các bó hoa sắp hết hạn</h2>
                <span class="badge-count" style="background:#FEF0C7; color:#B54708;">{{ count($boHoas) }} bó</span>
            </div>
            <div>
                <a href="{{ $isAdmin ? route('admin.ton-kho', ['loai_ton_kho' => 'bo_hoa']) : route('admin.mau-bo-hoa') }}"
                   class="btn-view-inventory" title="Xem tồn kho bó hoa">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Bấm xem tồn kho</span>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="exp-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">STT</th>
                        <th>Bó hoa</th>
                        <th class="text-center">Tồn kho</th>
                        <th class="text-center">Ngày tạo bó hoa</th>
                        <th class="text-center">Ngày hết hạn</th>
                        <th class="text-end">Giá bán</th>
                        <th class="text-center" style="width: 210px;">Cần xử lý</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($boHoas as $idx => $bh)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($bh->hinh_anh)
                                        <img src="{{ asset('storage/' . $bh->hinh_anh) }}"
                                             alt="{{ $bh->ten_bo_hoa }}"
                                             class="exp-item-thumb"
                                             onerror="this.onerror=null; this.src='{{ asset($bh->hinh_anh) }}';">
                                    @else
                                        <div class="exp-item-placeholder" style="background:#FEF0C7; color:#DC6803;">
                                            <i class="bi bi-flower3"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold text-dark">{{ $bh->ten_bo_hoa }}</div>
                                        <div class="text-muted" style="font-size: 11.5px;">
                                            Mã bó: #{{ $bh->ma_bo_hoa }} @if($bh->ten_mau) • Mẫu: {{ $bh->ten_mau }} @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark fs-6">{{ number_format($bh->so_luong) }}</span>
                                <span class="text-muted" style="font-size: 12px;">bó</span>
                            </td>
                            <td class="text-center text-nowrap">{{ $bh->ngayTaoFmt }}</td>
                            <td class="text-center text-nowrap">
                                <div class="fw-semibold text-dark">{{ $bh->hsdFmt }}</div>
                                @if($bh->diffDays < 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size: 11px; padding: 2px 7px;">
                                        Quá hạn {{ abs($bh->diffDays) }} ngày
                                    </span>
                                @elseif($bh->diffDays == 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold" style="font-size: 11px; padding: 2px 7px;">
                                        Hôm nay
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold" style="font-size: 11px; padding: 2px 7px;">
                                        Còn {{ $bh->diffDays }} ngày
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-dark">{{ number_format($bh->gia_ban) }}đ</td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn-xu-ly-bo"
                                        data-action="xu-ly-bo"
                                        data-mabohoa="{{ $bh->ma_bo_hoa }}"
                                        data-tenbohoa="{{ $bh->ten_bo_hoa }}"
                                        data-tenmau="{{ $bh->ten_mau ?? '' }}"
                                        data-soluong="{{ (int) $bh->so_luong }}"
                                        data-giaban="{{ (float) $bh->gia_ban }}"
                                        data-ngaytao="{{ $bh->ngayTaoFmt }}"
                                        data-hsd="{{ $bh->hsdFmt }}"
                                        data-diffdays="{{ (int) $bh->diffDays }}"
                                        title="Bấm vào để xử lý thanh lý hoặc xả bó">
                                    <i class="bi bi-tag"></i>
                                    <span>Thanh lý / Xả bó</span>
                                </button>
                                <div class="text-muted" style="font-size: 10.5px; margin-top: 3px;">
                                    (giảm giá hoặc xả bó)
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                Tuyệt vời! Hiện không có bó hoa mẫu nào sắp hết hạn hoặc quá hạn.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ========================================================================= --}}
{{-- MODAL 1: BÁO CÁO & LIÊN HỆ NCC THANH LÝ LÔ HÀNG --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="modalThanhLyNcc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #FEE4E2; color: #D92D20; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-dark">Thanh lý / Hoàn trả lại cho Nhà cung cấp</h5>
                        <div class="text-muted" style="font-size: 12px;">Báo cáo chi tiết và liên hệ trực tiếp với NCC để xử lý lô hàng</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="modal-info-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold text-dark fs-6" id="mLoTenHoa">—</div>
                        <span id="mLoBadgeHsd" class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">Quá hạn</span>
                    </div>
                    <div class="modal-info-grid">
                        <div><span class="label">Mã lô hàng:</span> <span class="val text-primary" id="mLoMaLo">—</span></div>
                        <div><span class="label">Phiếu nhập:</span> <span class="val" id="mLoMaPn">—</span></div>
                        <div><span class="label">Số lượng tồn cần xử lý:</span> <span class="val text-danger" id="mLoSoLuongTon">—</span></div>
                        <div><span class="label">Đơn giá nhập:</span> <span class="val" id="mLoGiaNhap">—</span></div>
                        <div><span class="label">Ngày nhập kho:</span> <span class="val" id="mLoNgayNhap">—</span></div>
                        <div><span class="label">Hạn sử dụng:</span> <span class="val" id="mLoHsd">—</span></div>
                        <div style="grid-column: span 2;">
                            <span class="label">Tổng giá trị tồn ước tính:</span>
                            <span class="val text-danger fs-6" id="mLoGiaTriTon">—</span>
                        </div>
                    </div>
                </div>

                <div class="border rounded-3 p-3 mb-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-bold text-dark">
                            <i class="bi bi-building me-1 text-primary"></i> Thông tin liên hệ Nhà cung cấp
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Đối tác cung ứng</span>
                    </div>
                    <div class="row g-2" style="font-size: 13px;">
                        <div class="col-md-6">
                            <div class="text-muted">Tên nhà cung cấp:</div>
                            <div class="fw-bold text-dark" id="mNccTen">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Số điện thoại:</div>
                            <div class="fw-bold" id="mNccSdtWrap">
                                <a href="#" id="mNccSdtLink" class="text-success text-decoration-none fw-bold">
                                    <i class="bi bi-telephone-fill me-1"></i><span id="mNccSdt">—</span>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Email:</div>
                            <div class="fw-semibold" id="mNccEmailWrap">
                                <a href="#" id="mNccEmailLink" class="text-primary text-decoration-none">
                                    <i class="bi bi-envelope-fill me-1"></i><span id="mNccEmail">—</span>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted">Địa chỉ:</div>
                            <div class="text-dark" id="mNccDiaChi">—</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1" style="font-size: 13px;">
                        <span><i class="bi bi-file-text me-1"></i> Mẫu báo cáo đề xuất hoàn trả / thanh lý chiết khấu:</span>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-primary" id="btnCopyReport">
                            <i class="bi bi-clipboard me-1"></i> Sao chép nội dung
                        </button>
                    </label>
                    <textarea id="mBaoCaoText" class="form-control" rows="4" style="font-size: 12.5px; background: #F8F9FA;" readonly></textarea>
                </div>
            </div>

            <div class="modal-footer border-top py-3 px-4 bg-light d-flex justify-content-between" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" data-bs-dismiss="modal">Đóng</button>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="btnCallNcc" class="btn btn-success btn-sm px-3 fw-semibold">
                        <i class="bi bi-telephone-outbound me-1"></i> Gọi điện NCC
                    </a>
                    <a href="#" id="btnEmailNcc" class="btn btn-primary btn-sm px-3 fw-semibold">
                        <i class="bi bi-envelope me-1"></i> Gửi Email báo cáo
                    </a>
                    <a href="{{ route('admin.lo-hang') }}" class="btn btn-outline-dark btn-sm px-3 fw-semibold">
                        <i class="bi bi-box-seam me-1"></i> Đến trang Lô hàng
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 2: XỬ LÝ BÓ HOA HẾT HẠN (GIẢM GIÁ THANH LÝ / XẢ BÓ) --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="modalXuLyBoHoa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #FEF0C7; color: #DC6803; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="bi bi-tags"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-dark">Phương án xử lý bó hoa mẫu hết hạn</h5>
                        <div class="text-muted" style="font-size: 12px;">Đề xuất phương án thanh lý giảm giá hoặc tháo xả bó hoa thu hồi phụ kiện</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="modal-info-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold text-dark fs-6" id="mBoTen">—</div>
                        <span id="mBoBadgeHsd" class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">Quá hạn</span>
                    </div>
                    <div class="modal-info-grid">
                        <div><span class="label">Mã bó hoa:</span> <span class="val text-primary" id="mBoMa">—</span></div>
                        <div><span class="label">Mẫu thiết kế:</span> <span class="val" id="mBoMau">—</span></div>
                        <div><span class="label">Số lượng tồn kho:</span> <span class="val text-danger" id="mBoSoLuong">—</span></div>
                        <div><span class="label">Giá niêm yết:</span> <span class="val text-dark" id="mBoGiaGoc">—</span></div>
                        <div><span class="label">Ngày tạo bó hoa:</span> <span class="val" id="mBoNgayTao">—</span></div>
                        <div><span class="label">Ngày hết hạn:</span> <span class="val text-danger" id="mBoHsd">—</span></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 bg-white" style="border-color: #FEDF89 !important;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning text-dark fw-bold">Phương án 1</span>
                                <h6 class="fw-bold text-dark mb-0">Thanh lý giảm giá Flash Sale</h6>
                            </div>
                            <p class="text-muted" style="font-size: 12.5px; line-height: 1.4;">
                                Bán xả nhanh tại quầy hoặc trên website với mức chiết khấu 30% - 50% trong ngày để thu hồi vốn bó hoa.
                            </p>
                            <div class="p-2 rounded bg-light border text-center my-2">
                                <span class="text-muted" style="font-size: 11.5px;">Giá thanh lý đề xuất (-40%):</span>
                                <div class="fs-5 fw-bold text-danger" id="mBoGiaGiamDeXuat">—</div>
                            </div>
                            <div class="text-center mt-3">
                                <a href="{{ route('admin.mau-bo-hoa') }}" class="btn btn-warning btn-sm w-100 fw-semibold text-dark">
                                    <i class="bi bi-tag-fill me-1"></i> Cập nhật giá bán thanh lý
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 bg-white" style="border-color: #D0D5DD !important;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-secondary text-white fw-bold">Phương án 2</span>
                                <h6 class="fw-bold text-dark mb-0">Xả bó & Tách phụ kiện</h6>
                            </div>
                            <p class="text-muted" style="font-size: 12.5px; line-height: 1.4;">
                                Tháo bó hoa để thu hồi các phụ kiện còn mới (giấy gói, ruy băng, bướm trang trí, xốp cắm), phần hoa tươi héo ghi nhận báo hỏng / hao hụt.
                            </p>
                            <div class="p-2 rounded bg-light border" style="font-size: 12px;">
                                <div class="text-success"><i class="bi bi-check-circle me-1"></i> Giấy gói, ruy băng: Thu hồi tồn kho</div>
                                <div class="text-danger"><i class="bi bi-x-circle me-1"></i> Hoa hỏng: Ghi nhận hao hụt</div>
                            </div>
                            <div class="text-center mt-3">
                                <a href="{{ route('admin.mau-bo-hoa') }}" class="btn btn-outline-danger btn-sm w-100 fw-semibold">
                                    <i class="bi bi-scissors me-1"></i> Xả bó & Ghi nhận hủy
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-3 px-4 bg-light" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    const fmt = v => new Intl.NumberFormat('vi-VN').format(Number(v) || 0) + 'đ';

    // ── Helper: khởi tạo Bootstrap Modal an toàn ──
    function showModal(id) {
        const el = document.getElementById(id);
        if (!el) { console.error('Không tìm thấy modal #' + id); return; }
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.error('Bootstrap chưa load! Kiểm tra layout admin có nhúng bootstrap.bundle.min.js chưa.');
            alert('Không thể mở hộp thoại: Bootstrap chưa được tải. Vui lòng tải lại trang.');
            return;
        }
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    // ═════════════════════════════════════════════════════════════
    // MODAL 1: THANH LÝ LẠI CHO NCC
    // ═════════════════════════════════════════════════════════════
    function openThanhLyModal(data) {
        document.getElementById('mLoTenHoa').textContent = data.tenHoa || '—';
        document.getElementById('mLoMaLo').textContent = '#' + (data.maLo || '—');
        document.getElementById('mLoMaPn').textContent = '#PN-' + (data.maPn || '—');
        document.getElementById('mLoSoLuongTon').textContent = (data.soLuongTon || 0) + ' ' + (data.donViTinh || '');
        document.getElementById('mLoGiaNhap').textContent = fmt(data.giaNhap);
        document.getElementById('mLoGiaTriTon').textContent = fmt(data.giaTriTon);
        document.getElementById('mLoNgayNhap').textContent = data.ngayNhap || '—';
        document.getElementById('mLoHsd').textContent = data.hsd || '—';

        const badgeHsd = document.getElementById('mLoBadgeHsd');
        const dd = parseInt(data.diffDays) || 0;
        if (dd < 0) {
            badgeHsd.className = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold';
            badgeHsd.textContent = 'Đã quá hạn ' + Math.abs(dd) + ' ngày';
        } else if (dd === 0) {
            badgeHsd.className = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold';
            badgeHsd.textContent = 'Hết hạn hôm nay';
        } else {
            badgeHsd.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold';
            badgeHsd.textContent = 'Còn ' + dd + ' ngày';
        }

        document.getElementById('mNccTen').textContent = data.tenNcc || '—';
        document.getElementById('mNccSdt').textContent = data.sdtNcc || 'Chưa cập nhật';
        document.getElementById('mNccEmail').textContent = data.emailNcc || 'Chưa cập nhật';
        document.getElementById('mNccDiaChi').textContent = data.diaChiNcc || 'Chưa cập nhật';

        const callBtn = document.getElementById('btnCallNcc');
        const sdtLink = document.getElementById('mNccSdtLink');
        if (data.sdtNcc) {
            callBtn.href = 'tel:' + data.sdtNcc;
            callBtn.classList.remove('disabled');
            sdtLink.href = 'tel:' + data.sdtNcc;
        } else {
            callBtn.href = '#';
            callBtn.classList.add('disabled');
            sdtLink.href = '#';
        }

        // Report body
        const reportBody =
            `Kính gửi đối tác ${data.tenNcc || 'Quý đối tác'},\n\n` +
            `Cửa hàng RoseShop xin gửi thông báo về lô hàng đã nhập từ quý đối tác như sau:\n` +
            `- Sản phẩm: ${data.tenHoa}\n` +
            `- Mã lô hàng: #${data.maLo} (Phiếu nhập: #PN-${data.maPn})\n` +
            `- Ngày nhập kho: ${data.ngayNhap}\n` +
            `- Ngày hết hạn sử dụng: ${data.hsd} (${dd <= 0 ? 'Hiện đã đến/quá hạn' : 'Sắp đến hạn'})\n` +
            `- Số lượng tồn kho cần hoàn trả/thanh lý: ${data.soLuongTon} ${data.donViTinh}\n` +
            `- Đơn giá nhập: ${fmt(data.giaNhap)}\n` +
            `- Tổng giá trị lô hàng tồn: ${fmt(data.giaTriTon)}\n\n` +
            `RoseShop kính đề xuất quý đối tác phương án hỗ trợ thu hồi thanh lý hoặc chiết khấu hoàn trả theo chính sách hợp tác nhà cung cấp.\n` +
            `Rất mong nhận được phản hồi sớm từ quý đối tác!\n\n` +
            `Trân trọng,\nBộ phận Quản lý Kho - RoseShop\nĐiện thoại: 0987.654.321`;

        document.getElementById('mBaoCaoText').value = reportBody;

        const emailBtn = document.getElementById('btnEmailNcc');
        const emailLink = document.getElementById('mNccEmailLink');
        if (data.emailNcc) {
            const subject = '[RoseShop] Đề xuất thanh lý / hoàn trả lô hoa #' + data.maLo + ' - ' + data.tenHoa;
            const mailto = 'mailto:' + data.emailNcc
                + '?subject=' + encodeURIComponent(subject)
                + '&body=' + encodeURIComponent(reportBody);
            emailBtn.href = mailto;
            emailBtn.classList.remove('disabled');
            emailLink.href = mailto;
        } else {
            emailBtn.href = '#';
            emailBtn.classList.add('disabled');
            emailLink.href = '#';
        }

        showModal('modalThanhLyNcc');
    }

    // ═════════════════════════════════════════════════════════════
    // MODAL 2: XỬ LÝ BÓ HOA HẾT HẠN
    // ═════════════════════════════════════════════════════════════
    function openXuLyBoHoaModal(data) {
        document.getElementById('mBoTen').textContent = data.tenBoHoa || '—';
        document.getElementById('mBoMa').textContent = '#' + (data.maBoHoa || '—');
        document.getElementById('mBoMau').textContent = data.tenMau || '—';
        document.getElementById('mBoSoLuong').textContent = (data.soLuong || 0) + ' bó';
        document.getElementById('mBoGiaGoc').textContent = fmt(data.giaBan);
        document.getElementById('mBoNgayTao').textContent = data.ngayTao || '—';
        document.getElementById('mBoHsd').textContent = data.hsd || '—';

        document.getElementById('mBoGiaGiamDeXuat').textContent = fmt((Number(data.giaBan) || 0) * 0.6);

        const badgeHsd = document.getElementById('mBoBadgeHsd');
        const dd = parseInt(data.diffDays) || 0;
        if (dd < 0) {
            badgeHsd.className = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold';
            badgeHsd.textContent = 'Đã quá hạn ' + Math.abs(dd) + ' ngày';
        } else if (dd === 0) {
            badgeHsd.className = 'badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold';
            badgeHsd.textContent = 'Hết hạn hôm nay';
        } else {
            badgeHsd.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold';
            badgeHsd.textContent = 'Còn ' + dd + ' ngày';
        }

        showModal('modalXuLyBoHoa');
    }

    // ═════════════════════════════════════════════════════════════
    // EVENT DELEGATION — chống lỗi khi tên có ký tự đặc biệt
    // ═════════════════════════════════════════════════════════════
    document.addEventListener('click', function (e) {
        // Nút Thanh lý NCC
        const btnNcc = e.target.closest('[data-action="thanh-ly-ncc"]');
        if (btnNcc) {
            e.preventDefault();
            openThanhLyModal({
                maLo:       btnNcc.dataset.malo,
                maPn:       btnNcc.dataset.mapn,
                tenHoa:     btnNcc.dataset.tenhoa,
                soLuongTon: parseInt(btnNcc.dataset.soluongton) || 0,
                donViTinh:  btnNcc.dataset.donvitinh,
                giaNhap:    parseFloat(btnNcc.dataset.gianhap) || 0,
                giaTriTon:  parseFloat(btnNcc.dataset.giatrion) || 0,
                ngayNhap:   btnNcc.dataset.ngaynhap,
                hsd:        btnNcc.dataset.hsd,
                diffDays:   parseInt(btnNcc.dataset.diffdays) || 0,
                tenNcc:     btnNcc.dataset.tenncc,
                sdtNcc:     btnNcc.dataset.sdtncc,
                emailNcc:   btnNcc.dataset.emailncc,
                diaChiNcc:  btnNcc.dataset.diachincc,
            });
            return;
        }

        // Nút Xử lý bó hoa
        const btnBo = e.target.closest('[data-action="xu-ly-bo"]');
        if (btnBo) {
            e.preventDefault();
            openXuLyBoHoaModal({
                maBoHoa:  btnBo.dataset.mabohoa,
                tenBoHoa: btnBo.dataset.tenbohoa,
                tenMau:   btnBo.dataset.tenmau,
                soLuong:  parseInt(btnBo.dataset.soluong) || 0,
                giaBan:   parseFloat(btnBo.dataset.giaban) || 0,
                ngayTao:  btnBo.dataset.ngaytao,
                hsd:      btnBo.dataset.hsd,
                diffDays: parseInt(btnBo.dataset.diffdays) || 0,
            });
            return;
        }

        // Nút copy báo cáo
        if (e.target.closest('#btnCopyReport')) {
            e.preventDefault();
            const ta = document.getElementById('mBaoCaoText');
            if (!ta) return;
            ta.select();
            ta.setSelectionRange(0, 99999);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(ta.value)
                    .then(() => alert('Đã sao chép nội dung báo cáo! Bạn có thể dán để gửi qua Zalo/Email cho Nhà cung cấp.'))
                    .catch(() => alert('Sao chép thất bại, vui lòng bôi đen và nhấn Ctrl+C.'));
            } else {
                document.execCommand('copy');
                alert('Đã sao chép nội dung báo cáo!');
            }
        }
    });
})();
</script>
@endpush