@extends('layouts.admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/Admin/BaoCao.css') }}">
@endpush

@section('content')

<div class="bao-cao-doanh-thu-container">
<div class="page-container">

    {{-- ═══════════════════════ 1. HEADER TRANG ═══════════════════════ --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Báo cáo doanh thu & lợi nhuận</h1>
            <p class="rs-page-subtitle">Thống kê biến động doanh thu, giá vốn và tỷ suất lợi nhuận theo kỳ.</p>
        </div>
        <div class="header-actions">
            <div class="dropdown">
                <button class="btn btn-export dropdown-toggle" type="button" id="exportMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-1"></i> Xuất báo cáo
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="exportMenuDropdown">
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('bao-cao.doanh-thu.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">
                            <i class="bi bi-file-earmark-excel-fill text-success me-2"></i> Excel (.xlsx)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('bao-cao.doanh-thu.export', request()->query()) }}">
                            <i class="bi bi-file-earmark-text-fill text-primary me-2"></i> CSV (.csv)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════ 2. KHU VỰC BỘ LỌC ═══════════════════════ --}}
    <div class="filter-card">
        <div class="filter-card-header">
            <span class="filter-title">
                <i class="bi bi-funnel-fill me-1"></i> LỌC THEO THỜI GIAN VÀ TIÊU CHÍ
            </span>
        </div>

        {{-- A. Quick filter buttons --}}
        <div class="quick-filter-group">
            <span class="quick-filter-label"><i class="bi bi-clock-history me-1"></i>Lọc nhanh:</span>
            <button type="button" class="btn-quick-filter" data-range="today">Hôm nay</button>
            <button type="button" class="btn-quick-filter" data-range="7days">7 ngày</button>
            <button type="button" class="btn-quick-filter" data-range="this_month">Tháng này</button>
            <button type="button" class="btn-quick-filter" data-range="last_month">Tháng trước</button>
            <button type="button" class="btn-quick-filter" data-range="this_year">Năm nay</button>
        </div>

        {{-- B. Form filter các field --}}
        <form method="GET" action="{{ route('bao-cao.doanh-thu') }}" id="formFilterDoanhThu">
            {{-- Hàng 1 --}}
            <div class="filter-row filter-row-1">
                <div class="filter-col">
                    <label class="filter-label">Từ ngày</label>
                    <input type="date" name="tu_ngay" id="filter_tu_ngay" class="form-control"
                           value="{{ request('tu_ngay', isset($tuNgay) ? \Carbon\Carbon::parse($tuNgay)->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d')) }}">
                </div>

                <div class="filter-col">
                    <label class="filter-label">Đến ngày</label>
                    <input type="date" name="den_ngay" id="filter_den_ngay" class="form-control"
                           value="{{ request('den_ngay', isset($denNgay) ? \Carbon\Carbon::parse($denNgay)->format('Y-m-d') : now()->format('Y-m-d')) }}">
                </div>

                <div class="filter-col">
                    <label class="filter-label">Nhóm dữ liệu theo</label>
                    <select name="kieu_hien_thi" class="form-select">
                        <option value="" disabled {{ empty($kieuHienThi) ? 'selected' : '' }}>-- Chọn chu kỳ --</option>
                        <option value="ngay" {{ ($kieuHienThi ?? '') === 'ngay' ? 'selected' : '' }}>Theo ngày</option>
                        <option value="thang" {{ ($kieuHienThi ?? 'thang') === 'thang' ? 'selected' : '' }}>Theo tháng</option>
                        <option value="quy" {{ ($kieuHienThi ?? '') === 'quy' ? 'selected' : '' }}>Theo quý</option>
                        <option value="nam" {{ ($kieuHienThi ?? '') === 'nam' ? 'selected' : '' }}>Theo năm</option>
                    </select>
                </div>

                <div class="filter-col">
                    <label class="filter-label">Nhóm kinh doanh</label>
                    <select name="nhom_kinh_doanh" class="form-select">
                        <option value="">-- Tất cả nhóm --</option>
                        <option value="bo_hoa_co_san" {{ request('nhom_kinh_doanh') === 'bo_hoa_co_san' ? 'selected' : '' }}>Bó hoa có sẵn</option>
                        <option value="custom_mix" {{ request('nhom_kinh_doanh') === 'custom_mix' ? 'selected' : '' }}>Custom Mix</option>
                        <option value="hoa_le" {{ request('nhom_kinh_doanh') === 'hoa_le' ? 'selected' : '' }}>Hoa lẻ</option>
                        <option value="flash_sale" {{ request('nhom_kinh_doanh') === 'flash_sale' ? 'selected' : '' }}>Flash Sale</option>
                    </select>
                </div>
            </div>

            {{-- Hàng 2 --}}
            <div class="filter-row filter-row-2">
                <div class="filter-col">
                    <label class="filter-label">Nguồn bán</label>
                    <select name="nguon_ban" class="form-select">
                        <option value="">-- Tất cả nguồn --</option>
                        <option value="online" {{ request('nguon_ban') === 'online' ? 'selected' : '' }}>Online</option>
                        <option value="tai_cua_hang" {{ request('nguon_ban') === 'tai_cua_hang' ? 'selected' : '' }}>Tại cửa hàng</option>
                    </select>
                </div>

                <div class="filter-col">
                    <label class="filter-label">Phương thức thanh toán</label>
                    <select name="phuong_thuc_thanh_toan" class="form-select">
                        <option value="">-- Tất cả phương thức --</option>
                        <option value="COD" {{ request('phuong_thuc_thanh_toan') === 'COD' ? 'selected' : '' }}>COD (Thanh toán khi nhận hàng)</option>
                        <option value="NGAN_HANG" {{ request('phuong_thuc_thanh_toan') === 'NGAN_HANG' ? 'selected' : '' }}>Chuyển khoản (VNPay / Ngân hàng)</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <a href="{{ route('bao-cao.doanh-thu') }}" class="btn-reset">
                        <i class="bi bi-arrow-counterclockwise"></i> Xóa lọc
                    </a>
                    <button type="submit" class="btn-filter">
                        <i class="bi bi-funnel-fill"></i> Lọc dữ liệu
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════ 3. KPI CARDS (7 CARDS) ═══════════════════════ --}}
    @php
        $tyLeLoiNhuanChung = ($tongDoanhThu ?? 0) > 0 ? (($tongLoiNhuan ?? 0) / $tongDoanhThu) * 100 : 0;
    @endphp
    <div class="kpi-grid">
        {{-- Card 1: Tổng đơn hàng --}}
        <div class="kpi-card kpi-blue">
            <div class="kpi-header">
                <span class="kpi-label">Tổng đơn hàng</span>
                <div class="kpi-icon-wrap"><i class="bi bi-bag-check"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($tongDonHang ?? 0) }}</div>
        </div>

        {{-- Card 2: Tổng sản phẩm bán --}}
        <div class="kpi-card kpi-purple">
            <div class="kpi-header">
                <span class="kpi-label">Tổng sản phẩm bán</span>
                <div class="kpi-icon-wrap"><i class="bi bi-box-seam"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($tongSanPhamBan ?? 0) }}</div>
        </div>

        {{-- Card 3: Tổng doanh thu --}}
        <div class="kpi-card kpi-pink">
            <div class="kpi-header">
                <span class="kpi-label">Tổng doanh thu</span>
                <div class="kpi-icon-wrap"><i class="bi bi-cash-stack"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($tongDoanhThu ?? 0, 0, ',', '.') }}đ</div>
        </div>

        {{-- Card 4: Tổng giá vốn --}}
        <div class="kpi-card kpi-amber">
            <div class="kpi-header">
                <span class="kpi-label">Tổng giá vốn</span>
                <div class="kpi-icon-wrap"><i class="bi bi-receipt"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($tongVon ?? 0, 0, ',', '.') }}đ</div>
        </div>

        {{-- Card 5: Tổng lợi nhuận --}}
        <div class="kpi-card kpi-emerald">
            <div class="kpi-header">
                <span class="kpi-label">Tổng lợi nhuận</span>
                <div class="kpi-icon-wrap"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($tongLoiNhuan ?? 0, 0, ',', '.') }}đ</div>
        </div>

        {{-- Card 6: Tỷ lệ lợi nhuận --}}
        <div class="kpi-card kpi-indigo">
            <div class="kpi-header">
                <span class="kpi-label">Tỷ lệ lợi nhuận</span>
                <div class="kpi-icon-wrap"><i class="bi bi-percent"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($tyLeLoiNhuanChung, 2, ',', '.') }}%</div>
        </div>

        {{-- Card 7: Giá trị đơn TB --}}
        <div class="kpi-card kpi-teal">
            <div class="kpi-header">
                <span class="kpi-label">Giá trị đơn TB</span>
                <div class="kpi-icon-wrap"><i class="bi bi-cart-check"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($giaTriDonTrungBinh ?? 0, 0, ',', '.') }}đ</div>
        </div>
    </div>

    {{-- ═══════════════════════ 4. BỐ CỤC KHU BIỂU ĐỒ ═══════════════════════ --}}
    <div class="charts-section">
        {{-- Biểu đồ lớn bên trái: Biến động doanh thu - giá vốn - lợi nhuận --}}
        <div class="chart-card">
            <div class="chart-header">
                <span class="chart-title">
                    <i class="bi bi-graph-up me-1"></i> Biến động doanh thu - giá vốn - lợi nhuận
                </span>
                <div class="chart-legend-custom">
                    <span class="chart-legend-item">
                        <span class="chart-legend-badge" style="background: #ec4899;"></span> Doanh thu
                    </span>
                    <span class="chart-legend-item">
                        <span class="chart-legend-badge" style="background: #f97316;"></span> Giá vốn
                    </span>
                    <span class="chart-legend-item">
                        <span class="chart-legend-badge" style="background: #10b981;"></span> Lợi nhuận
                    </span>
                </div>
            </div>

            @if(($chiTietDoanhThu ?? collect())->count() > 0)
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="chartBienDongDoanhThu"></canvas>
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-bar-chart-line d-block"></i>
                    <p>Chưa có dữ liệu biến động doanh thu trong khoảng thời gian này.</p>
                </div>
            @endif
        </div>

        {{-- Hai biểu đồ nhỏ bên phải --}}
        <div class="charts-sidebar">
            {{-- Card: Doanh thu theo nhóm sản phẩm --}}
            <div class="donut-card">
                <div class="chart-header pb-2 mb-2">
                    <span class="chart-title" style="font-size: 0.88rem;">
                        <i class="bi bi-pie-chart me-1"></i> Doanh thu theo nhóm sản phẩm
                    </span>
                    <!-- <span class="badge bg-light text-muted fw-normal" style="font-size: 0.7rem;">UC10.1</span> -->
                </div>
                <div class="donut-body">
                    <div class="donut-canvas-wrap">
                        <canvas id="chartNhomSanPham"></canvas>
                    </div>
                    <div class="donut-legend-wrap">
                        <div class="donut-legend-item">
                            <div class="donut-legend-left">
                                <span class="donut-legend-dot" style="background: #ec4899;"></span>
                                <span>Bó hoa có sẵn</span>
                            </div>
                            <span class="donut-legend-pct">0%</span>
                        </div>
                        <div class="donut-legend-item">
                            <div class="donut-legend-left">
                                <span class="donut-legend-dot" style="background: #8b5cf6;"></span>
                                <span>Custom Mix</span>
                            </div>
                            <span class="donut-legend-pct">0%</span>
                        </div>
                        <div class="donut-legend-item">
                            <div class="donut-legend-left">
                                <span class="donut-legend-dot" style="background: #3b82f6;"></span>
                                <span>Hoa lẻ</span>
                            </div>
                            <span class="donut-legend-pct">0%</span>
                        </div>
                        <div class="donut-legend-item">
                            <div class="donut-legend-left">
                                <span class="donut-legend-dot" style="background: #f59e0b;"></span>
                                <span>Flash Sale</span>
                            </div>
                            <span class="donut-legend-pct">0%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Doanh thu theo phương thức thanh toán --}}
            <div class="donut-card">
                <div class="chart-header pb-2 mb-2">
                    <span class="chart-title" style="font-size: 0.88rem;">
                        <i class="bi bi-credit-card me-1"></i> Doanh thu theo PT thanh toán
                    </span>
                    <!-- <span class="badge bg-light text-muted fw-normal" style="font-size: 0.7rem;">Hệ thống</span> -->
                </div>
                <div class="donut-body">
                    <div class="donut-canvas-wrap">
                        <canvas id="chartPhuongThucThanhToan"></canvas>
                    </div>
                    <div class="donut-legend-wrap">
                        <div class="donut-legend-item">
                            <div class="donut-legend-left">
                                <span class="donut-legend-dot" style="background: #10b981;"></span>
                                <span>COD</span>
                            </div>
                            <span class="donut-legend-pct">0%</span>
                        </div>
                        <div class="donut-legend-item">
                            <div class="donut-legend-left">
                                <span class="donut-legend-dot" style="background: #3b82f6;"></span>
                                <span>VNPay / Ngân hàng</span>
                            </div>
                            <span class="donut-legend-pct">0%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════ 5. BẢNG CHI TIẾT DOANH THU ═══════════════════════ --}}
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">
                <i class="bi bi-table me-1"></i> Chi tiết doanh thu
            </span>
        </div>

        <div class="table-wrap">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">STT</th>
                        <th>Thời gian</th>
                        <th class="text-end">Số đơn hàng</th>
                        <th class="text-end">SL bán</th>
                        <th class="text-end">Doanh thu</th>
                        <th class="text-end">Giá vốn</th>
                        <th class="text-end">Lợi nhuận</th>
                        <th class="text-end">Tỷ lệ lợi nhuận</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($chiTietDoanhThu as $index => $item)
                        @php
                            $tyLeItem = $item->doanh_thu > 0
                                ? ($item->loi_nhuan / $item->doanh_thu) * 100
                                : 0;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $item->thoi_gian }}</td>
                            <td class="text-end">{{ number_format($item->so_don_hang) }}</td>
                            <td class="text-end">{{ number_format($item->tong_san_pham_ban) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->doanh_thu, 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($item->gia_von, 0, ',', '.') }}đ</td>
                            <td class="text-end profit-highlight">{{ number_format($item->loi_nhuan, 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($tyLeItem, 2, ',', '.') }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <div class="empty-state">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p>Chưa có dữ liệu doanh thu trong khoảng thời gian này.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if(($chiTietDoanhThu ?? collect())->count() > 0)
                    <tr class="total-row">
                        <td colspan="2" class="fw-bold">Tổng cộng</td>
                        <td class="text-end fw-bold">{{ number_format($tongDonHang ?? 0) }}</td>
                        <td class="text-end fw-bold">{{ number_format($tongSanPhamBan ?? 0) }}</td>
                        <td class="text-end fw-bold">{{ number_format($tongDoanhThu ?? 0, 0, ',', '.') }}đ</td>
                        <td class="text-end fw-bold">{{ number_format($tongVon ?? 0, 0, ',', '.') }}đ</td>
                        <td class="text-end fw-bold profit-highlight">{{ number_format($tongLoiNhuan ?? 0, 0, ',', '.') }}đ</td>
                        <td class="text-end fw-bold">{{ number_format($tyLeLoiNhuanChung, 2, ',', '.') }}%</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════ 6. TOP MẶT HÀNG (2 BẢNG 50/50) ═══════════════════════ --}}
    <div class="top-grid">
        {{-- Card 1: Top 5 mặt hàng theo doanh thu --}}
        <div class="table-card mb-0">
            <div class="table-card-header">
                <span class="table-card-title">
                    <i class="bi bi-trophy-fill text-warning me-1"></i> Top 5 mặt hàng theo doanh thu
                </span>
            </div>
            <div class="table-wrap">
                <table class="rs-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">STT</th>
                            <th>Mặt hàng</th>
                            <th>Nhóm</th>
                            <th class="text-end">SL bán</th>
                            <th class="text-end">Doanh thu</th>
                            <th class="text-end">Giá vốn</th>
                            <th class="text-end">Lợi nhuận</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSanPham as $index => $sp)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if(!empty($sp->hinh_anh))
                                            <img src="{{ asset('storage/' . $sp->hinh_anh) }}" alt="{{ $sp->ten_san_pham }}"
                                                 style="width: 28px; height: 28px; object-fit: cover; border-radius: 4px;"
                                                 onerror="this.style.display='none'">
                                        @endif
                                        <span class="fw-semibold text-truncate" style="max-width: 140px;" title="{{ $sp->ten_san_pham }}">
                                            {{ $sp->ten_san_pham }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-category">{{ $sp->ten_nhom ?? ($sp->nhom_hang === 'hoa_le' ? 'Hoa lẻ' : 'Bó hoa') }}</span>
                                </td>
                                <td class="text-end">{{ number_format($sp->tong_san_pham_ban ?? 0) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($sp->doanh_thu ?? 0, 0, ',', '.') }}đ</td>
                                <td class="text-end">{{ $sp->gia_von !== null ? number_format($sp->gia_von, 0, ',', '.') . 'đ' : '-' }}</td>
                                <td class="text-end profit-highlight">{{ $sp->loi_nhuan !== null ? number_format($sp->loi_nhuan, 0, ',', '.') . 'đ' : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox d-block"></i>
                                        <p>Chưa có dữ liệu mặt hàng trong khoảng thời gian này.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card 2: Top 5 mặt hàng theo lợi nhuận --}}
        <div class="table-card mb-0">
            <div class="table-card-header">
                <span class="table-card-title">
                    <i class="bi bi-graph-up-arrow text-success me-1"></i> Top 5 mặt hàng theo lợi nhuận
                </span>
            </div>
            <div class="table-wrap">
                <table class="rs-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">STT</th>
                            <th>Mặt hàng</th>
                            <th>Nhóm</th>
                            <th class="text-end">SL bán</th>
                            <th class="text-end">Doanh thu</th>
                            <th class="text-end">Giá vốn</th>
                            <th class="text-end">Lợi nhuận</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topLoiNhuan ?? [] as $index => $sp)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if(!empty($sp->hinh_anh))
                                            <img src="{{ asset('storage/' . $sp->hinh_anh) }}" alt="{{ $sp->ten_san_pham }}"
                                                 style="width: 28px; height: 28px; object-fit: cover; border-radius: 4px;"
                                                 onerror="this.style.display='none'">
                                        @endif
                                        <span class="fw-semibold text-truncate" style="max-width: 140px;" title="{{ $sp->ten_san_pham }}">
                                            {{ $sp->ten_san_pham }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-category">{{ $sp->ten_nhom ?? ($sp->nhom_hang === 'hoa_le' ? 'Hoa lẻ' : 'Bó hoa') }}</span>
                                </td>
                                <td class="text-end">{{ number_format($sp->tong_san_pham_ban ?? 0) }}</td>
                                <td class="text-end">{{ number_format($sp->doanh_thu ?? 0, 0, ',', '.') }}đ</td>
                                <td class="text-end">{{ $sp->gia_von !== null ? number_format($sp->gia_von, 0, ',', '.') . 'đ' : '-' }}</td>
                                <td class="text-end profit-highlight fw-semibold">{{ $sp->loi_nhuan !== null ? number_format($sp->loi_nhuan, 0, ',', '.') . 'đ' : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox d-block"></i>
                                        <p>Chưa có dữ liệu lợi nhuận mặt hàng trong khoảng thời gian này.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>{{-- end .page-container --}}
</div>{{-- end .bao-cao-doanh-thu-container --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ═══════════════════════ 1. QUICK FILTER HANDLER ═══════════════════════
    const quickButtons = document.querySelectorAll('.btn-quick-filter');
    const tuNgayInput = document.getElementById('filter_tu_ngay');
    const denNgayInput = document.getElementById('filter_den_ngay');

    const formatDate = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    quickButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            quickButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const range = this.dataset.range;
            const now = new Date();

            if (range === 'today') {
                tuNgayInput.value = formatDate(now);
                denNgayInput.value = formatDate(now);
            } else if (range === '7days') {
                const start = new Date(now);
                start.setDate(now.getDate() - 6);
                tuNgayInput.value = formatDate(start);
                denNgayInput.value = formatDate(now);
            } else if (range === 'this_month') {
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                tuNgayInput.value = formatDate(start);
                denNgayInput.value = formatDate(now);
            } else if (range === 'last_month') {
                const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const end = new Date(now.getFullYear(), now.getMonth(), 0);
                tuNgayInput.value = formatDate(start);
                denNgayInput.value = formatDate(end);
            } else if (range === 'this_year') {
                const start = new Date(now.getFullYear(), 0, 1);
                tuNgayInput.value = formatDate(start);
                denNgayInput.value = formatDate(now);
            }
        });
    });

    // ═══════════════════════ 2. CHART BIẾN ĐỘNG DOANH THU ═══════════════════════
    const ctxBienDong = document.getElementById('chartBienDongDoanhThu');
    if (ctxBienDong) {
        const labels = @json(($chiTietDoanhThu ?? collect())->pluck('thoi_gian'));
        const dataDoanhThu = @json(($chiTietDoanhThu ?? collect())->pluck('doanh_thu'));
        const dataGiaVon = @json(($chiTietDoanhThu ?? collect())->pluck('gia_von'));
        const dataLoiNhuan = @json(($chiTietDoanhThu ?? collect())->pluck('loi_nhuan'));

        new Chart(ctxBienDong, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Doanh thu',
                        data: dataDoanhThu,
                        borderColor: '#ec4899',
                        backgroundColor: 'rgba(236, 72, 153, 0.08)',
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Giá vốn',
                        data: dataGiaVon,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.05)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: false,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Lợi nhuận',
                        data: dataLoiNhuan,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: false,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' đ';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            font: { size: 12 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#64748b',
                            font: { size: 12 },
                            callback: function (val) {
                                if (val >= 1000000) return (val / 1000000) + ' tr';
                                if (val >= 1000) return (val / 1000) + ' k';
                                return val;
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    }
                }
            }
        });
    }

    // ═══════════════════════ 3. CHART DOANH THU THEO NHÓM SP ═══════════════════════
    const ctxNhomSP = document.getElementById('chartNhomSanPham');
    if (ctxNhomSP) {
        new Chart(ctxNhomSP, {
            type: 'doughnut',
            data: {
                labels: ['Bó hoa có sẵn', 'Custom Mix', 'Hoa lẻ', 'Flash Sale'],
                datasets: [{
                    data: [1], // placeholder ring khi chưa có data
                    backgroundColor: ['#f1f5f9'],
                    hoverBackgroundColor: ['#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    }

    // ═══════════════════════ 4. CHART DOANH THU THEO PT THANH TOÁN ═══════════════════════
    const ctxPTTT = document.getElementById('chartPhuongThucThanhToan');
    if (ctxPTTT) {
        new Chart(ctxPTTT, {
            type: 'doughnut',
            data: {
                labels: ['COD', 'VNPay / Ngân hàng'],
                datasets: [{
                    data: [1], // placeholder ring khi chưa có data
                    backgroundColor: ['#f1f5f9'],
                    hoverBackgroundColor: ['#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    }
});
</script>
@endpush

