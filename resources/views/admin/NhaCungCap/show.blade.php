@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
    <style>
        .ncc-kpi-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 12px !important;
            margin-bottom: 16px !important;
        }
        .ncc-kpi-card {
            padding: 12px 14px !important;
            border-radius: 10px !important;
            min-width: 0 !important;
        }
        .ncc-kpi-card.kpi-rate {
            grid-column: span 2 !important;
        }
        .ncc-kpi-card .kpi-label {
            font-size: 11px !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
            letter-spacing: 0.3px !important;
            color: #667085 !important;
            text-transform: uppercase !important;
            margin-bottom: 4px !important;
        }
        .ncc-kpi-card .kpi-val {
            font-size: 18px !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
        }
        @media (max-width: 768px) {
            .ncc-kpi-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .ncc-kpi-card.kpi-rate {
                grid-column: span 2 !important;
            }
        }
        .ncc-table {
            min-width: 960px !important;
        }
        .ncc-table thead th {
            background-color: #F8F9FA !important;
            color: #475467 !important;
            font-weight: 600 !important;
            font-size: 12.5px !important;
            white-space: nowrap !important;
        }
        .ncc-code-badge {
            display: inline-block;
            font-family: monospace;
            font-size: 11.5px;
            font-weight: 700;
            color: #EC3D8F !important;
            white-space: nowrap !important;
        }
        .ncc-action-btn {
            width: 32px !important;
            height: 32px !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 1px solid #D0D5DD !important;
            background: #ffffff !important;
            color: #475467 !important;
            transition: all 0.15s ease !important;
            text-decoration: none !important;
            font-size: 13.5px !important;
            cursor: pointer !important;
        }
        .ncc-action-btn:hover {
            background: #F9FAFB !important;
            color: #1D2939 !important;
            border-color: #98A2B3 !important;
        }
        .ncc-action-btn.view:hover {
            color: #EC3D8F !important;
            border-color: #EC3D8F !important;
            background-color: #FDEAF3 !important;
        }
        .ncc-action-btn.rate {
            background-color: #FDEAF3 !important;
            color: #EC3D8F !important;
            border-color: #FCCEE2 !important;
        }
        .ncc-action-btn.rate:hover {
            background-color: #EC3D8F !important;
            color: #ffffff !important;
            border-color: #EC3D8F !important;
            box-shadow: 0 2px 6px rgba(236, 61, 143, 0.25) !important;
        }
        .ncc-header-actions {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            flex-wrap: wrap !important;
        }
        .ncc-header-actions a,
        .ncc-header-actions button {
            height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            white-space: nowrap !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
        }
        .page-item.active .page-link {
            background-color: #EC3D8F !important;
            border-color: #EC3D8F !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
<div class="ncc-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">{{ $ncc->ten_nha_cung_cap }}</h1>
            <p class="rs-page-subtitle">Mã đối tác: #{{ $ncc->ma_nha_cung_cap }} — Chi tiết hồ sơ, chất lượng nguồn hàng và lịch sử nhập.</p>
        </div>
        <div class="ncc-header-actions">
            <a href="{{ route('admin.nha-cung-cap') }}" class="ncc-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                <span>Danh sách NCC</span>
            </a>

            @if($ncc->isDangHopTac())
                <a href="{{ route('phieu-nhap.index', ['ma_nha_cung_cap' => $ncc->ma_nha_cung_cap]) }}" class="ncc-btn-primary" style="background:#EC3D8F; border-color:#EC3D8F;">
                    <i class="bi bi-plus-circle me-1"></i>
                    <span>Tạo phiếu nhập từ NCC</span>
                </a>
            @endif

            <a href="{{ route('admin.nha-cung-cap.edit', $ncc->ma_nha_cung_cap) }}" class="ncc-btn-secondary">
                <i class="bi bi-pencil"></i>
                <span>Sửa thông tin</span>
            </a>

            @if($ncc->isDangHopTac())
                <form method="POST"
                      action="{{ route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap) }}"
                      class="d-inline"
                      onsubmit="return confirm('Bạn chắc chắn muốn ngừng hợp tác với nhà cung cấp này?\n\n- Thông tin tổng quan: {{ $thongKe['tong_so_lo'] }} lô đã nhập ({{ $thongKe['so_lo_khong_dat'] }} lô không đạt).\n- Nhà cung cấp này sẽ không được chọn khi tạo phiếu nhập mới.\n- Toàn bộ hồ sơ, phiếu nhập, lô hàng và lịch sử đánh giá vẫn được lưu giữ an toàn.')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="trang_thai" value="NGUNG_HOP_TAC">
                    <button type="submit" class="btn btn-outline-danger" style="font-weight:600; border-radius:8px; padding: 8px 16px;">
                        <i class="bi bi-pause-circle me-1"></i> Ngừng hợp tác
                    </button>
                </form>
            @else
                <form method="POST"
                      action="{{ route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap) }}"
                      class="d-inline"
                      onsubmit="return confirm('Kích hoạt lại nhà cung cấp này để có thể tiếp tục tạo phiếu nhập hàng?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="trang_thai" value="DANG_HOP_TAC">
                    <button type="submit" class="btn btn-success" style="font-weight:600; border-radius:8px; padding: 8px 16px;">
                        <i class="bi bi-play-circle me-1"></i> Kích hoạt lại
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Có lỗi xảy ra:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Main Content Grid --}}
    <div class="ncc-detail-grid">

        {{-- Left Column: Section A - NCC Profile --}}
        <div>
            <div class="ncc-info-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-light text-muted border">Mã #{{ $ncc->ma_nha_cung_cap }}</span>
                    @if($ncc->isDangHopTac())
                        <span class="ncc-badge active">
                            <span class="ncc-badge-dot"></span> Đang hợp tác
                        </span>
                    @else
                        <span class="ncc-badge inactive">
                            <span class="ncc-badge-dot"></span> Ngừng hợp tác
                        </span>
                    @endif
                </div>

                <h2 class="ncc-info-title">{{ $ncc->ten_nha_cung_cap }}</h2>
                <div class="ncc-info-meta">
                    <i class="bi bi-calendar3 me-1"></i>
                    Ngày tạo: {{ $ncc->created_at ? $ncc->created_at->format('d/m/Y H:i') : '—' }}
                </div>

                <div class="ncc-info-item">
                    <span class="label"><i class="bi bi-telephone me-1"></i> Số điện thoại</span>
                    <span class="value">
                        @if($ncc->so_dien_thoai)
                            <a href="tel:{{ $ncc->so_dien_thoai }}">{{ $ncc->so_dien_thoai }}</a>
                        @else
                            <span class="text-muted">Chưa cập nhật</span>
                        @endif
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label"><i class="bi bi-envelope me-1"></i> Email</span>
                    <span class="value">
                        @if($ncc->email)
                            <a href="mailto:{{ $ncc->email }}">{{ $ncc->email }}</a>
                        @else
                            <span class="text-muted">Chưa cập nhật</span>
                        @endif
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label"><i class="bi bi-geo-alt me-1"></i> Địa chỉ</span>
                    <span class="value">
                        {{ $ncc->dia_chi ?: 'Chưa cập nhật' }}
                    </span>
                </div>

                <div class="ncc-info-item border-bottom-0 pb-0">
                    <span class="label"><i class="bi bi-graph-up me-1"></i> Thống kê giao dịch</span>
                    <div class="mt-2 p-3 bg-light rounded-3">
                        <div class="d-flex justify-content-between mb-2" style="font-size:13px;">
                            <span class="text-muted">Tổng số phiếu nhập:</span>
                            <strong class="text-dark">{{ $ncc->phieu_nhaps_count ?? $phieuNhaps->total() }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2" style="font-size:13px;">
                            <span class="text-muted">Tổng số mặt hàng:</span>
                            <strong class="text-dark">{{ number_format($tongMatHang ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span class="text-muted">Tổng giá trị nhập:</span>
                            <strong style="color:#EC3D8F; font-size:15px;">{{ number_format($tongGiaTriNhap ?? 0, 0, ',', '.') }} đ</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Sections B, C, D --}}
        <div>

            {{-- Section B: TỔNG QUAN CHẤT LƯỢNG NGUỒN HÀNG --}}
            <div class="ncc-card mb-4">
                <div class="ncc-card-header">
                    <h2 class="ncc-card-title">
                        <i class="bi bi-shield-check text-success"></i>
                        Tổng quan chất lượng nguồn hàng
                    </h2>
                    @if($thongKe['so_lo_khong_dat'] > 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size:12px;">
                            <i class="bi bi-exclamation-octagon me-1"></i>Có {{ $thongKe['so_lo_khong_dat'] }} lô không đạt
                        </span>
                    @endif
                </div>
                <div class="p-3">
                    <div class="ncc-kpi-grid">
                        <div class="ncc-kpi-card">
                            <span class="kpi-label">Tổng số lô</span>
                            <span class="kpi-val">{{ $thongKe['tong_so_lo'] }}</span>
                        </div>
                        <div class="ncc-kpi-card">
                            <span class="kpi-label">Đã đánh giá</span>
                            <span class="kpi-val">{{ $thongKe['so_lo_da_danh_gia'] }}</span>
                        </div>
                        <div class="ncc-kpi-card kpi-success">
                            <span class="kpi-label">Lô đạt</span>
                            <span class="kpi-val">{{ $thongKe['so_lo_dat'] }}</span>
                        </div>
                        <div class="ncc-kpi-card kpi-warning">
                            <span class="kpi-label">Có vấn đề</span>
                            <span class="kpi-val">{{ $thongKe['so_lo_co_van_de'] }}</span>
                        </div>
                        <div class="ncc-kpi-card kpi-danger">
                            <span class="kpi-label">Không đạt</span>
                            <span class="kpi-val">{{ $thongKe['so_lo_khong_dat'] }}</span>
                        </div>
                        <div class="ncc-kpi-card kpi-rate">
                            <span class="kpi-label">Tỷ lệ không đạt</span>
                            <span class="kpi-val" style="font-size: {{ $thongKe['ty_le_khong_dat'] !== null ? '18px' : '13px' }};">
                                @if($thongKe['ty_le_khong_dat'] !== null)
                                    {{ $thongKe['ty_le_khong_dat'] }}%
                                @else
                                    <span class="text-muted fw-normal" style="font-size: 12.5px;">Chưa đủ dữ liệu đánh giá</span>
                                @endif
                            </span>
                        </div>
                        <div class="ncc-kpi-card">
                            <span class="kpi-label">Tổng hàng hỏng</span>
                            <span class="kpi-val text-secondary">{{ number_format($thongKe['tong_luong_hong']) }} <small style="font-size:12px; font-weight:normal;">sp</small></span>
                        </div>
                    </div>
                    <div class="text-muted" style="font-size:12px;">
                        <i class="bi bi-info-circle me-1"></i> Tỷ lệ lô không đạt = (Số lô không đạt / Số lô đã đánh giá) × 100%. Lô chưa đánh giá không được tính là lô đạt.
                    </div>
                </div>
            </div>

            {{-- Section D: DANH SÁCH LÔ ĐÃ NHẬP TỪ NHÀ CUNG CẤP --}}
            <div class="ncc-card mb-4">
                <div class="ncc-card-header">
                    <h2 class="ncc-card-title">
                        <i class="bi bi-boxes text-primary"></i>
                        Các lô hàng đã nhập từ Nhà cung cấp
                        <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $loHangs->total() }}</span>
                    </h2>
                </div>

                <div class="table-responsive">
                    <table class="ncc-table">
                        <thead>
                            <tr>
                                <th style="width: 85px; white-space: nowrap !important;">Mã lô</th>
                                <th style="min-width: 130px; white-space: nowrap !important;">Mặt hàng</th>
                                <th style="width: 105px; white-space: nowrap !important;">Phiếu nhập</th>
                                <th class="text-center" style="white-space: nowrap !important;">Số lượng ban đầu</th>
                                <th class="text-center" style="white-space: nowrap !important;">Còn lại</th>
                                <th class="text-center" style="white-space: nowrap !important;">Trạng thái</th>
                                <th class="text-end" style="white-space: nowrap !important;">Giá nhập</th>
                                <th class="text-center" style="min-width: 130px; white-space: nowrap !important;">Đánh giá chất lượng</th>
                                <th class="text-center" style="white-space: nowrap !important;">Hàng hỏng</th>
                                <th class="text-end" style="width: 90px; white-space: nowrap !important;">Thao tác</th>
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
                                        <span class="ncc-code-badge">#CTN-{{ $lo->ma_chi_tiet_nhap }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark" style="white-space: nowrap;">{{ $lo->sanPham->ten_san_pham ?? 'Sản phẩm #' . $lo->ma_san_pham }}</div>
                                        <small style="white-space: nowrap;">Mã SP: <span class="fw-bold" style="color:#EC3D8F; font-family: monospace; font-size: 11px;">#{{ $lo->ma_san_pham }}</span></small>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <a href="{{ route('phieu-nhap.index') }}" class="text-decoration-none ncc-code-badge">
                                            #PN-{{ $lo->ma_phieu_nhap }}
                                        </a>
                                        <div style="font-size:11px; color:#667085; white-space: nowrap;">
                                            {{ $lo->created_at ? $lo->created_at->format('d/m/Y') : '—' }}
                                        </div>
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
                                    <td class="text-center" style="white-space: nowrap;">
                                        @if($lo->so_luong_con_lai > 0)
                                            <span class="badge" style="background:#ECFDF3; color:#027A48; border:1px solid #ABEFC6; border-radius:12px; padding:4px 10px; font-size:11.5px; font-weight:600; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                                                <i class="bi bi-check-circle"></i> Còn hàng
                                            </span>
                                        @else
                                            <span class="badge" style="background:#FEF3F2; color:#B42318; border:1px solid #FECDCA; border-radius:12px; padding:4px 10px; font-size:11.5px; font-weight:600; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                                                <i class="bi bi-x-circle"></i> Hết hàng
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold" style="white-space: nowrap;">
                                        {{ number_format($lo->gia_nhap, 0, ',', '.') }} đ
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        @if($dg)
                                            @if($dg->ket_qua === 'DAT')
                                                <span class="badge-quality-dat" title="Đánh giá: Đạt"><i class="bi bi-check-circle me-1"></i>Đạt</span>
                                            @elseif($dg->ket_qua === 'CO_VAN_DE')
                                                <span class="badge-quality-covande" title="{{ $dg->ly_do }}"><i class="bi bi-exclamation-triangle me-1"></i>Có vấn đề</span>
                                            @elseif($dg->ket_qua === 'KHONG_DAT')
                                                <span class="badge-quality-khongdat" title="{{ $dg->ly_do }}"><i class="bi bi-x-circle me-1"></i>Không đạt</span>
                                            @endif
                                        @else
                                            <span class="badge-quality-chuadanhgia">Chưa đánh giá</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($tongHongLo > 0 || ($dg && $dg->so_luong_loi > 0))
                                            <span class="text-danger fw-bold" style="font-size:13px;">
                                                {{ max($tongHongLo, $dg?->so_luong_loi ?? 0) }}
                                            </span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end" style="white-space: nowrap;">
                                        <div class="d-inline-flex gap-1 align-items-center justify-content-end" style="white-space: nowrap;">
                                            <a href="{{ route('admin.lo-hang.show', $lo->ma_chi_tiet_nhap) }}"
                                               class="ncc-action-btn view"
                                               title="Xem chi tiết lô #CTN-{{ $lo->ma_chi_tiet_nhap }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($lo->so_luong_con_lai > 0)
                                            <button type="button"
                                                    class="ncc-action-btn rate"
                                                    title="{{ $dg ? 'Sửa đánh giá chất lượng (#CTN-' . $lo->ma_chi_tiet_nhap . ')' : 'Đánh giá chất lượng lô #CTN-' . $lo->ma_chi_tiet_nhap }}"
                                                    onclick="openModalDanhGia({{ $lo->ma_chi_tiet_nhap }}, '{{ addslashes($lo->sanPham->ten_san_pham ?? '') }}', {{ $lo->so_luong }}, '{{ $dg?->ket_qua ?? '' }}', {{ $dg?->so_luong_loi ?? 0 }}, '{{ addslashes($dg?->ly_do ?? '') }}', '{{ addslashes($dg?->ghi_chu ?? '') }}')">
                                                <i class="bi {{ $dg ? 'bi-pencil-square' : 'bi-clipboard-check' }}"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="ncc-empty-state">
                                        <div class="ncc-empty-icon">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <p class="mb-0 fw-semibold">Nhà cung cấp chưa có lô hàng nào.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($loHangs->hasPages())
                    <div class="p-3 border-top d-flex justify-content-end">
                        {{ $loHangs->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>

            {{-- Section C: LỊCH SỬ PHIẾU NHẬP --}}
            <div class="ncc-card">
                <div class="ncc-card-header">
                    <h2 class="ncc-card-title">
                        <i class="bi bi-clock-history"></i>
                        Lịch sử nhập hàng
                        <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $phieuNhaps->total() }}</span>
                    </h2>
                    @if($ncc->isDangHopTac())
                        <a href="{{ route('phieu-nhap.index', ['ma_nha_cung_cap' => $ncc->ma_nha_cung_cap]) }}" class="btn btn-sm btn-outline-dark" style="font-weight:600; font-size:12px;">
                            <i class="bi bi-plus-lg me-1"></i>Tạo phiếu nhập mới
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="ncc-table">
                        <thead>
                            <tr>
                                <th style="width: 85px; white-space: nowrap !important;">Mã phiếu</th>
                                <th style="white-space: nowrap !important;">Ngày nhập</th>
                                <th style="white-space: nowrap !important;">Nhân viên phụ trách</th>
                                <th class="text-center" style="white-space: nowrap !important;">Số mặt hàng</th>
                                <th class="text-end" style="white-space: nowrap !important;">Tổng giá trị</th>
                                <th style="width: 130px; white-space: nowrap !important;">Trạng thái</th>
                                <th class="text-end" style="width: 100px; white-space: nowrap !important;">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($phieuNhaps as $pn)
                                @php
                                    $tongTienPhieu = $pn->chiTietNhaps->sum(fn($ct) => ($ct->so_luong ?? 0) * ($ct->gia_nhap ?? 0));
                                    $soMatHang = $pn->chiTietNhaps->count();
                                @endphp
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <a href="{{ route('phieu-nhap.show', $pn->ma_phieu_nhap) }}" class="ncc-code-badge text-decoration-none" title="Xem chi tiết phiếu nhập">
                                            #PN-{{ $pn->ma_phieu_nhap }}
                                        </a>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        {{ $pn->ngay_nhap ? \Carbon\Carbon::parse($pn->ngay_nhap)->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td style="white-space: nowrap;">
                                        {{ $pn->nhanVien->ten_nhan_vien ?? '—' }}
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        <span class="badge bg-light text-dark border">
                                            {{ $soMatHang }} mặt hàng
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold" style="color:#1D2939; white-space: nowrap;">
                                        {{ number_format($tongTienPhieu, 0, ',', '.') }} đ
                                    </td>
                                    <td style="white-space: nowrap;">
                                        @if($pn->trang_thai === 'CONFIRMED')
                                            <span class="badge bg-dark" style="font-size:12px; font-weight:500;">
                                                <i class="bi bi-check-lg me-1"></i>Đã xác nhận
                                            </span>
                                        @else
                                            <span class="badge" style="background:#f2f4f7; color:#344054; border:1px solid #d0d5dd; font-size:12px; font-weight:500;">
                                                <i class="bi bi-pencil me-1"></i>Đang tạo (Draft)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end" style="white-space: nowrap;">
                                        <a href="{{ route('phieu-nhap.show', $pn->ma_phieu_nhap) }}"
                                           class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                           style="font-size:12px; font-weight:600; padding: 4px 10px; border-radius:6px;"
                                           title="Xem chi tiết phiếu nhập #PN-{{ $pn->ma_phieu_nhap }}">
                                            <i class="bi bi-eye"></i>
                                            <span>Xem chi tiết</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="ncc-empty-state">
                                        <div class="ncc-empty-icon">
                                            <i class="bi bi-journal-x"></i>
                                        </div>
                                        <p class="mb-0 fw-semibold">Nhà cung cấp chưa phát sinh giao dịch nhập hàng.</p>
                                        <small class="text-muted">Các phiếu nhập hàng liên kết với đối tác này sẽ được tự động hiển thị tại đây.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($phieuNhaps->hasPages())
                    <div class="p-3 border-top d-flex justify-content-end">
                        {{ $phieuNhaps->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>

        </div>

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
                            <span class="text-muted">Số lượng nhập ban đầu:</span>
                            <strong id="modal_so_luong_lo" class="text-primary">—</strong>
                        </div>
                    </div>

                    {{-- Kết quả đánh giá --}}
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

                    {{-- Số lượng lỗi / hỏng phát hiện --}}
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

                    {{-- Lý do (required khi CO_VAN_DE hoặc KHONG_DAT) --}}
                    <div class="mb-3" id="group_ly_do" style="display:none;">
                        <label class="form-label fw-semibold">
                            Lý do đánh giá <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="ly_do" id="modal_inp_ly_do" class="form-control" placeholder="VD: Hoa dập nát nhiều, héo úa, sai quy cách...">
                    </div>

                    {{-- Ghi chú --}}
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
