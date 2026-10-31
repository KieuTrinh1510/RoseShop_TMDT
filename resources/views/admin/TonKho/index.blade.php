@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
    <style>
        .badge-ton-thuc-te { background: #e0f2fe; color: #0369a1; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
        .badge-ton-dang-giu { background: #fef3c7; color: #b45309; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .badge-ton-dang-giu:hover { background: #fde68a; }
        .badge-ton-kha-dung { background: #dcfce7; color: #15803d; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
        .stat-card-tk {
            background: #fff;
            border: 1px solid #eaecf0;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 1px 3px rgba(16,24,40,0.05);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            height: 100%;
        }
        .stat-card-tk:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,24,40,0.08);
        }
        .stat-card-tk .title { font-size: 13px; color: #667085; font-weight: 500; margin-bottom: 6px; }
        .stat-card-tk .value { font-size: 24px; font-weight: 700; color: #101828; }
        .ncc-filter-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1.5fr) minmax(0, 1.15fr) minmax(0, 1.25fr) minmax(0, 1.15fr) auto !important;
            gap: 12px 12px !important;
            align-items: flex-end !important;
        }
        .ncc-filter-grid > div { min-width: 0; }
        .ncc-filter-grid select,
        .ncc-filter-grid input { width: 100% !important; text-overflow: ellipsis; }
        .ncc-filter-grid select { font-size: 12.5px !important; padding-right: 26px !important; }
        @media (max-width: 1199.98px) {
            .ncc-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
        }
        @media (max-width: 767.98px) {
            .ncc-filter-grid { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
        }
        .ncc-filter-grid .form-label {
            font-size: 12.5px !important;
            font-weight: 600 !important;
            color: #475467 !important;
            margin-bottom: 5px !important;
            white-space: nowrap !important;
        }
        .ncc-filter-grid select,
        .ncc-filter-grid input {
            height: 38px !important;
            border: 1px solid #D0D5DD !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            color: #101828 !important;
            padding: 0 10px !important;
            background-color: #ffffff !important;
        }
        .ncc-filter-grid select:focus,
        .ncc-filter-grid input:focus {
            border-color: #EC3D8F !important;
            box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.15) !important;
            outline: none !important;
        }
        .ncc-filter-box {
            background: #ffffff !important;
            border: 1px solid #EAECF0 !important;
            border-radius: 12px !important;
            padding: 16px 20px !important;
            margin-bottom: 20px !important;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05) !important;
        }
        .ncc-table-card {
            background: #ffffff !important;
            border: 1px solid #EAECF0 !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05) !important;
            overflow: hidden !important;
        }
        .ncc-table {
            width: 100% !important;
            min-width: 1050px !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
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
            padding: 12px 10px !important;
            text-transform: none !important;
            letter-spacing: normal !important;
        }
        .ncc-table td {
            padding: 12px 10px !important;
            font-size: 13.5px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #F2F4F7 !important;
            color: #344054 !important;
        }
        .ncc-table tbody tr:hover {
            background-color: #FDF9FA !important;
        }
        .pagination .page-item .page-link {
            border-radius: 8px !important;
            margin: 0 2px !important;
            border: 1px solid #D0D5DD !important;
            color: #344054 !important;
            font-size: 13px !important;
            padding: 6px 12px !important;
        }
        .pagination .page-item.active .page-link {
            background-color: #EC3D8F !important;
            border-color: #EC3D8F !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }
        .pagination .page-item.disabled .page-link {
            color: #98A2B3 !important;
            background-color: #F9FAFB !important;
            border-color: #EAECF0 !important;
        }
    </style>
@endpush

@section('content')
<div class="ncc-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Quản lý tồn kho </h1>
            <p class="rs-page-subtitle">Kiểm soát tồn thực tế, số lượng đang giữ theo đơn và tính toán tồn khả dụng phục vụ bán hàng.</p>
        </div>
        <div class="ncc-header-actions">
            <a href="{{ route('phieu-nhap.index') }}" class="ncc-btn-primary">
                <i class="bi bi-box-seam"></i>
                <span>Xem phiếu nhập</span>
            </a>
            <a href="{{ route('admin.lo-hang') }}" class="ncc-btn-outline" style="margin-left: 8px; border: 1px solid #d0d5dd; padding: 8px 14px; border-radius: 8px; text-decoration: none; color: #344054; font-weight: 500;">
                <i class="bi bi-layers"></i>
                <span>Xem theo lô hàng</span>
            </a>
        </div>
    </div>

    {{-- KPI Cards (5 thẻ ngang hàng) --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-4">
        <div class="col">
            <a href="{{ route('admin.ton-kho', request()->except(['page', 'tinh_trang_ton'])) }}" class="text-decoration-none">
                <div class="stat-card-tk" title="Xem tất cả mặt hàng">
                    <div class="title"><i class="bi bi-grid me-1 text-primary"></i> Tổng mặt hàng</div>
                    <div class="value">{{ number_format($tongMatHang) }}</div>
                </div>
            </a>
        </div>
        <div class="col">
            <div class="stat-card-tk">
                <div class="title"><i class="bi bi-boxes me-1 text-info"></i> Tồn thực tế (On Hand)</div>
                <div class="value text-primary">{{ number_format($tongTonThucTe) }}</div>
            </div>
        </div>
        <div class="col">
            <a href="{{ route('admin.ton-kho', array_merge(request()->except('page'), ['tinh_trang_ton' => 'dang_giu'])) }}" class="text-decoration-none">
                <div class="stat-card-tk {{ request('tinh_trang_ton') === 'dang_giu' ? 'border-warning border-2' : '' }}" title="Lọc các mặt hàng đang có đơn giữ">
                    <div class="title"><i class="bi bi-clock-history me-1 text-warning"></i> Đang giữ (Reserved)</div>
                    <div class="value text-warning">{{ number_format($tongDangGiu) }}</div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.ton-kho', array_merge(request()->except('page'), ['tinh_trang_ton' => 'con_hang'])) }}" class="text-decoration-none">
                <div class="stat-card-tk {{ request('tinh_trang_ton') === 'con_hang' ? 'border-success border-2' : '' }}" title="Lọc các mặt hàng còn khả dụng">
                    <div class="title"><i class="bi bi-check2-circle me-1 text-success"></i> Khả dụng (Available)</div>
                    <div class="value text-success">{{ number_format($tongKhaDung) }}</div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.ton-kho', array_merge(request()->except('page'), ['tinh_trang_ton' => 'sap_het'])) }}" class="text-decoration-none">
                <div class="stat-card-tk {{ request('tinh_trang_ton') === 'sap_het' ? 'border-danger border-2' : '' }}" title="Lọc các mặt hàng sắp hết hàng (Khả dụng ≤ 10)">
                    <div class="title"><i class="bi bi-exclamation-triangle me-1 text-danger"></i> Sắp hết hàng</div>
                    <div class="value text-danger">{{ number_format($tongSapHetHang) }}</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Filter Box --}}
    <div class="ncc-filter-box">
        <form method="GET" action="{{ route('admin.ton-kho') }}">
            <div class="ncc-filter-grid">
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Loại tồn kho</label>
                    <select name="loai_ton_kho" class="form-select">
                        <option value="">-- Tất cả kho hàng --</option>
                        <option value="san_pham" {{ request('loai_ton_kho') === 'san_pham' ? 'selected' : '' }}>Sản phẩm</option>
                        <option value="nguyen_vat_lieu" {{ request('loai_ton_kho') === 'nguyen_vat_lieu' ? 'selected' : '' }}>Nguyên vật liệu</option>
                        <option value="bo_hoa" {{ request('loai_ton_kho') === 'bo_hoa' ? 'selected' : '' }}>Bó hoa thành phẩm</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Tìm kiếm mặt hàng</label>
                    <input type="text"
                           name="keyword"
                           class="form-control"
                           placeholder="Mã hoặc tên sản phẩm / mẫu bó..."
                           value="{{ request('keyword') }}">
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Loại sản phẩm</label>
                    <select name="loai_san_pham" class="form-select">
                        <option value="">-- Tất cả loại --</option>
                        <option value="HOA_TUOI" {{ request('loai_san_pham') === 'HOA_TUOI' ? 'selected' : '' }}>Hoa tươi</option>
                        <option value="SAN_PHAM_PREMIUM" {{ request('loai_san_pham') === 'SAN_PHAM_PREMIUM' ? 'selected' : '' }}>Hoa sen / Cao cấp</option>
                        <option value="HOA_SAP" {{ request('loai_san_pham') === 'HOA_SAP' ? 'selected' : '' }}>Hoa sáp</option>
                        <option value="HOA_GIA" {{ request('loai_san_pham') === 'HOA_GIA' ? 'selected' : '' }}>Hoa lụa/giả</option>
                        <option value="PHU_KIEN_TRANG_TRI" {{ request('loai_san_pham') === 'PHU_KIEN_TRANG_TRI' || request('loai_san_pham') === 'PHU_KIEN' ? 'selected' : '' }}>Phụ kiện trang trí</option>
                        <option value="GIAY_GOI" {{ request('loai_san_pham') === 'GIAY_GOI' ? 'selected' : '' }}>Giấy gói</option>
                        <option value="RUY_BANG" {{ request('loai_san_pham') === 'RUY_BANG' ? 'selected' : '' }}>Ruy băng</option>
                        <option value="MIENG_XOP" {{ request('loai_san_pham') === 'MIENG_XOP' ? 'selected' : '' }}>Miếng xốp</option>
                        <option value="THIEP" {{ request('loai_san_pham') === 'THIEP' ? 'selected' : '' }}>Thiệp</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Tình trạng tồn</label>
                    <select name="tinh_trang_ton" class="form-select">
                        <option value="">-- Tất cả tình trạng --</option>
                        <option value="con_hang" {{ request('tinh_trang_ton') === 'con_hang' ? 'selected' : '' }}>Còn hàng (Khả dụng > 0)</option>
                        <option value="sap_het" {{ request('tinh_trang_ton') === 'sap_het' ? 'selected' : '' }}>Sắp hết hàng (Khả dụng ≤ 10)</option>
                        <option value="het_hang" {{ request('tinh_trang_ton') === 'het_hang' ? 'selected' : '' }}>Hết hàng (Khả dụng = 0)</option>
                        <option value="dang_giu" {{ request('tinh_trang_ton') === 'dang_giu' ? 'selected' : '' }}>Đang có đơn giữ</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Trạng thái kinh doanh</label>
                    <select name="trang_thai" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="DANG_BAN" {{ request('trang_thai') === 'DANG_BAN' ? 'selected' : '' }}>Đang bán</option>
                        <option value="NGUNG_BAN" {{ request('trang_thai') === 'NGUNG_BAN' ? 'selected' : '' }}>Ngừng bán</option>
                    </select>
                </div>
                <div style="display:flex; align-items:flex-end; gap:8px; white-space:nowrap;">
                    <button type="submit" class="ncc-btn-primary" style="background-color:#EC3D8F; border-color:#EC3D8F; color:#fff; border-radius:8px; height:38px; padding:0 18px; font-weight:500; font-size:13px; display:inline-flex; align-items:center; justify-content:center; gap:6px; white-space:nowrap;">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    @if(request()->anyFilled(['loai_ton_kho', 'keyword', 'loai_san_pham', 'tinh_trang_ton', 'trang_thai']))
                        <a href="{{ route('admin.ton-kho') }}" class="btn" style="background:#F2F4F7; border:1px solid #D0D5DD; color:#344054; border-radius:8px; height:38px; width:38px; display:inline-flex; align-items:center; justify-content:center;" title="Xóa bộ lọc">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Main Table --}}
    <div class="ncc-table-card">
        <div class="rs-card-title-bar" style="padding: 16px 20px 14px 20px; background: #ffffff; border-bottom: 1px solid #EAECF0; display: flex; align-items: center; justify-content: space-between;">
            <div class="rs-card-title" style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: #101828;">
                <i class="bi bi-box-seam" style="font-size: 16px; color: #475467;"></i>
                <span>Danh sách tồn kho</span>
                <span class="rs-count-badge" style="display: inline-flex; align-items: center; justify-content: center; padding: 2px 8px; border-radius: 12px; background-color: #F2F4F7; color: #344054; font-size: 12.5px; font-weight: 600; margin-left: 4px;">{{ $items->total() }}</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table ncc-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px;">Mã</th>
                        <th>Mặt hàng</th>
                        <th>Loại / ĐVT</th>
                        <th class="text-center" style="width: 140px;">Tồn thực tế</th>
                        <th class="text-center" style="width: 140px;">Đang giữ</th>
                        <th class="text-center" style="width: 140px;">Khả dụng</th>
                        <th class="text-center" style="width: 130px;">Trạng thái</th>
                        <th class="text-end" style="width: 110px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $sp)
                        @php
                            $thucTe = (int) $sp->ton_thuc_te;
                            $dangGiu = (int) $sp->ton_dang_giu;
                            $khaDung = (int) $sp->ton_kha_dung;
                            $isBoHoa = !empty($sp->is_bo_hoa);
                        @endphp
                        <tr>
                            <td style="white-space: nowrap;">
                                <span class="fw-bold" style="color:#EC3D8F; font-size: 12.5px; white-space: nowrap;">{{ $sp->ma_dinh_danh }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($sp->hinh_anh)
                                        <img src="{{ asset($sp->hinh_anh) }}" alt="" style="width: 38px; height: 38px; object-fit: cover; border-radius: 8px; border: 1px solid #eaecf0;">
                                    @else
                                        <div style="width: 38px; height: 38px; background: #f2f4f7; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #98a2b3;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold text-dark d-flex align-items-center gap-1">
                                            {{ $sp->ten_san_pham }}
                                            @if($isBoHoa)
                                                <span class="badge" style="background:#f3e8ff; color:#7e22ce; border: 1px solid #e9d5ff; font-size:10px; font-weight:600; padding:2px 5px;">Bó sẵn</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ number_format($sp->gia_ban_hien_tai ?? 0) }} đ</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($isBoHoa)
                                    <div><small class="badge" style="background:#f3e8ff; color:#6b21a8; border: 1px solid #e9d5ff; font-weight:600;">Bó hoa thành phẩm</small></div>
                                    <small class="text-muted">{{ $sp->don_vi_tinh_text }}</small>
                                @elseif(!empty($sp->is_nguyen_vat_lieu))
                                    <div><small class="badge" style="background:#FEF0C7; color:#B54708; border: 1px solid #FEDF89; font-weight:600;">Nguyên vật liệu</small></div>
                                    <small class="text-muted">{{ $sp->loai_badge_text }} · {{ $sp->don_vi_tinh_text }}</small>
                                @else
                                    <div><small class="badge" style="background:#E0F2FE; color:#026AA2; border: 1px solid #B9E6FE; font-weight:600;">Sản phẩm</small></div>
                                    <small class="text-muted">{{ $sp->loai_badge_text }} · {{ $sp->don_vi_tinh_text }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-ton-thuc-te">{{ number_format($thucTe) }}</span>
                            </td>
                            <td class="text-center">
                                @if($dangGiu > 0)
                                    @if($isBoHoa)
                                        <span class="badge-ton-dang-giu" onclick="xemDonGiuBoHoa({{ $sp->id }})" title="Bấm để xem danh sách đơn đang giữ bó hoa này">
                                            <i class="bi bi-lock-fill me-1"></i>{{ number_format($dangGiu) }}
                                        </span>
                                    @else
                                        <span class="badge-ton-dang-giu" onclick="xemDonGiu({{ $sp->id }})" title="Bấm để xem danh sách đơn đang giữ">
                                            <i class="bi bi-lock-fill me-1"></i>{{ number_format($dangGiu) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-ton-kha-dung">{{ number_format($khaDung) }}</span>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                @if($khaDung <= 0)
                                    <span class="badge" style="background:#FEF3F2; color:#B42318; border:1px solid #FECDCA; border-radius:12px; padding:3px 10px; font-size:11.5px; font-weight:600; white-space:nowrap;">Hết hàng</span>
                                @elseif($khaDung <= 10)
                                    <span class="badge" style="background:#FFFAEB; color:#B54708; border:1px solid #FEDF89; border-radius:12px; padding:3px 10px; font-size:11.5px; font-weight:600; white-space:nowrap;">Sắp hết</span>
                                @else
                                    <span class="badge" style="background:#ECFDF3; color:#027A48; border:1px solid #ABEFC6; border-radius:12px; padding:3px 10px; font-size:11.5px; font-weight:600; white-space:nowrap;">Còn hàng</span>
                                @endif
                            </td>
                            <td class="text-end" style="white-space: nowrap;">
                                @if($isBoHoa)
                                    @if($dangGiu > 0)
                                        <button type="button" class="btn btn-sm" style="background:#FEF3C7; border:1px solid #FDE68A; color:#B45309; border-radius:6px; font-size:12px; font-weight:600; padding:4px 10px;" onclick="xemDonGiuBoHoa({{ $sp->id }})" title="Xem đơn giữ">
                                            <i class="bi bi-eye me-1"></i> Đơn giữ
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm" style="background:#FDEAF3; border:1px solid #FCCEE2; color:#EC3D8F; border-radius:6px; font-size:12px; font-weight:600; padding:4px 10px;" onclick="xemChiTietBoHoa({{ $sp->id }})" title="Xem chi tiết tồn bó">
                                            <i class="bi bi-info-circle me-1"></i> Chi tiết
                                        </button>
                                    @endif
                                @else
                                    @if($dangGiu > 0)
                                        <button type="button" class="btn btn-sm" style="background:#FEF3C7; border:1px solid #FDE68A; color:#B45309; border-radius:6px; font-size:12px; font-weight:600; padding:4px 10px;" onclick="xemDonGiu({{ $sp->id }})" title="Xem đơn giữ">
                                            <i class="bi bi-eye me-1"></i> Đơn giữ
                                        </button>
                                    @else
                                        <span class="text-muted" style="font-size: 13px;">—</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Không tìm thấy mặt hàng nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN --}}
        @if($items->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage, 'page' => 1]) }}" {{ (request('per_page', $items->perPage()) == $perPage) ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <span class="rs-records-count">
                        {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} / {{ $items->total() }} bản ghi
                    </span>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($items->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $items->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $items->currentPage();
                            $lastPage = $items->lastPage();
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
                                <li class="page-item"><a class="page-link" href="{{ $items->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($items->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $items->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    </div>

</div>

{{-- Modal Xem Danh Sách Đơn Đang Giữ --}}
<div class="modal fade" id="modalDonGiu" tabindex="-1" aria-labelledby="modalDonGiuLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="modalDonGiuLabel">Chi tiết đơn hàng đang giữ tồn</h5>
                    <div class="text-muted" id="modalSubTitle" style="font-size: 13px;"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modalLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
                <div id="modalContent" style="display: none;">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            Tồn thực tế: <strong id="mThucTe" class="text-primary">0</strong> |
                            Đang giữ: <strong id="mDangGiu" class="text-warning">0</strong> |
                            Khả dụng: <strong id="mKhaDung" class="text-success">0</strong>
                        </div>
                        <small class="text-muted">Đơn PENDING / Chưa xuất kho</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Số lượng giữ</th>
                                    <th>Lô phân bổ</th>
                                    <th>Bó hoa liên quan</th>
                                    <th>Thời điểm giữ</th>
                                </tr>
                            </thead>
                            <tbody id="dsDonGiuBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function xemDonGiu(sanPhamId) {
    const modalEl = document.getElementById('modalDonGiu');
    const modal = new bootstrap.Modal(modalEl);
    const loading = document.getElementById('modalLoading');
    const content = document.getElementById('modalContent');
    const subTitle = document.getElementById('modalSubTitle');
    const tbody = document.getElementById('dsDonGiuBody');

    loading.style.display = 'block';
    content.style.display = 'none';
    subTitle.innerText = 'Đang tải thông tin...';
    tbody.innerHTML = '';

    modal.show();

    fetch(`/admin/ton-kho/${sanPhamId}/giu`)
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            content.style.display = 'block';

            subTitle.innerText = `${data.san_pham.ten_san_pham} (#${data.san_pham.ma_san_pham})`;
            document.getElementById('mThucTe').innerText = data.san_pham.ton_thuc_te + ' ' + data.san_pham.don_vi_tinh;
            document.getElementById('mDangGiu').innerText = data.san_pham.ton_dang_giu + ' ' + data.san_pham.don_vi_tinh;
            document.getElementById('mKhaDung').innerText = data.san_pham.ton_kha_dung + ' ' + data.san_pham.don_vi_tinh;

            if (data.reservations.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">Hiện không có đơn hàng nào giữ sản phẩm này.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.reservations.map(r => `
                <tr>
                    <td><strong>#${r.ma_hoa_don}</strong></td>
                    <td>
                        <div class="fw-semibold">${r.khach_hang}</div>
                        <small class="text-muted">${r.so_dien_thoai || ''}</small>
                    </td>
                    <td><span class="badge bg-warning text-dark fw-bold">${r.so_luong}</span></td>
                    <td><span class="badge bg-info-subtle text-info border">${r.so_lo}</span></td>
                    <td>${r.ten_bo_hoa ? `<small class="text-secondary">${r.ten_bo_hoa}</small>` : '<span class="text-muted">—</span>'}</td>
                    <td><small class="text-muted">${r.thoi_gian}</small></td>
                </tr>
            `).join('');
        })
        .catch(err => {
            loading.style.display = 'none';
            subTitle.innerText = 'Lỗi tải dữ liệu';
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">Có lỗi xảy ra khi tải danh sách đơn giữ.</td></tr>`;
        });
}

function xemDonGiuBoHoa(mauId) {
    const modalEl = document.getElementById('modalDonGiu');
    const modal = new bootstrap.Modal(modalEl);
    const loading = document.getElementById('modalLoading');
    const content = document.getElementById('modalContent');
    const subTitle = document.getElementById('modalSubTitle');
    const tbody = document.getElementById('dsDonGiuBody');

    loading.style.display = 'block';
    content.style.display = 'none';
    subTitle.innerText = 'Đang tải thông tin bó hoa...';
    tbody.innerHTML = '';

    modal.show();

    fetch(`/admin/ton-kho/bo-hoa/${mauId}/giu`)
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            content.style.display = 'block';

            subTitle.innerText = `${data.san_pham.ten_san_pham} (#BH${data.san_pham.ma_san_pham}) · Size ${data.san_pham.size}`;
            document.getElementById('mThucTe').innerText = data.san_pham.ton_thuc_te + ' ' + data.san_pham.don_vi_tinh;
            document.getElementById('mDangGiu').innerText = data.san_pham.ton_dang_giu + ' ' + data.san_pham.don_vi_tinh;
            document.getElementById('mKhaDung').innerText = data.san_pham.ton_kha_dung + ' ' + data.san_pham.don_vi_tinh;

            if (data.reservations.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">Hiện không có đơn hàng nào giữ bó hoa thuộc mẫu này.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.reservations.map(r => `
                <tr>
                    <td><strong>#${r.ma_hoa_don}</strong></td>
                    <td>
                        <div class="fw-semibold">${r.khach_hang}</div>
                        <small class="text-muted">${r.so_dien_thoai || ''}</small>
                    </td>
                    <td><span class="badge bg-warning text-dark fw-bold">${r.so_luong} bó</span></td>
                    <td><span class="badge bg-purple-subtle text-purple border">${r.so_lo}</span></td>
                    <td><small class="text-secondary">${r.ten_bo_hoa}</small></td>
                    <td><small class="text-muted">${r.thoi_gian}</small></td>
                </tr>
            `).join('');
        })
        .catch(err => {
            loading.style.display = 'none';
            subTitle.innerText = 'Lỗi tải dữ liệu';
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">Có lỗi xảy ra khi tải danh sách đơn giữ.</td></tr>`;
        });
}

function xemChiTietBoHoa(mauId) {
    xemDonGiuBoHoa(mauId);
}

document.addEventListener('DOMContentLoaded', function () {
    const loaiTonKhoSelect = document.querySelector('select[name="loai_ton_kho"]');
    const loaiSanPhamSelect = document.querySelector('select[name="loai_san_pham"]');

    function syncLoaiSanPhamState() {
        if (!loaiTonKhoSelect || !loaiSanPhamSelect) return;
        if (loaiTonKhoSelect.value === 'bo_hoa') {
            loaiSanPhamSelect.value = '';
            loaiSanPhamSelect.disabled = true;
            loaiSanPhamSelect.setAttribute('title', 'Kho bó hoa thành phẩm không áp dụng phân loại nguyên vật liệu');
        } else {
            loaiSanPhamSelect.disabled = false;
            loaiSanPhamSelect.removeAttribute('title');
        }
    }

    if (loaiTonKhoSelect) {
        loaiTonKhoSelect.addEventListener('change', syncLoaiSanPhamState);
        syncLoaiSanPhamState();
    }
});
</script>
@endpush
