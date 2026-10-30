@extends('layouts.admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/Admin/BaoCaoGiaoHang.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="bao-cao-giao-hang-container">

    {{-- ═══════════════════════ 1. HEADER TRANG ═══════════════════════ --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">
                Thống kê giao hàng
            </h1>
            <p class="rs-page-subtitle">
                Theo dõi hiệu quả giao hàng, tỷ lệ thành công, hiệu suất Shipper và công nợ COD (UC10.4).
                <span class="badge bg-light text-secondary border ms-2"><i class="bi bi-shield-lock me-1"></i>Chế độ chỉ xem (Read-only)</span>
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.bao-cao.giao-hang.export', request()->query()) }}" class="btn-export-csv" id="btnExportCsv">
                <i class="bi bi-file-earmark-arrow-down"></i> Xuất CSV
            </a>
        </div>
    </div>

    {{-- Cảnh báo validate ngày nếu ngày bắt đầu > ngày kết thúc --}}
    @if(!empty($dateWarning))
        <div class="alert alert-warning py-2 px-3 mb-4 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div>{{ $dateWarning }}</div>
        </div>
    @endif

    {{-- ═══════════════════════ 2. BỘ LỌC DỮ LIỆU ═══════════════════════ --}}
    <div class="filter-card">
        <div class="filter-card-header">
            <span class="filter-title">
                <i class="bi bi-funnel-fill"></i> Bộ lọc thống kê giao hàng
            </span>
            {{-- Nút chọn nhanh chu kỳ --}}
            <div class="quick-filter-group mb-0">
                <span class="quick-filter-label"><i class="bi bi-clock-history me-1"></i>Chọn nhanh:</span>
                <button type="button" class="btn-quick-filter" onclick="setQuickDate('today')">Hôm nay</button>
                <button type="button" class="btn-quick-filter" onclick="setQuickDate('7days')">7 ngày qua</button>
                <button type="button" class="btn-quick-filter" onclick="setQuickDate('this_month')">Tháng này</button>
                <button type="button" class="btn-quick-filter" onclick="setQuickDate('last_month')">Tháng trước</button>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.bao-cao.giao-hang') }}" id="formFilterGiaoHang">
            @if(request('per_page'))
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            @endif
            @if(!empty($keyword))
                <input type="hidden" name="q" value="{{ $keyword }}">
            @endif

            <div class="rs-filter-row">
                {{-- 1. Từ ngày --}}
                <div class="rs-filter-col rs-col-date">
                    <label class="filter-label">Từ ngày</label>
                    <div class="input-icon-wrap">
                        <input type="date" name="ngay_bat_dau" id="filter_ngay_bat_dau" class="form-control filter-input"
                               value="{{ $ngayBatDau->format('Y-m-d') }}">
                        <i class="bi bi-calendar3 input-icon"></i>
                    </div>
                </div>

                {{-- 2. Đến ngày --}}
                <div class="rs-filter-col rs-col-date">
                    <label class="filter-label">Đến ngày</label>
                    <div class="input-icon-wrap">
                        <input type="date" name="ngay_ket_thuc" id="filter_ngay_ket_thuc" class="form-control filter-input"
                               value="{{ $ngayKetThuc->format('Y-m-d') }}">
                        <i class="bi bi-calendar3 input-icon"></i>
                    </div>
                </div>

                {{-- 3. Shipper --}}
                <div class="rs-filter-col rs-col-select">
                    <label class="filter-label">Nhân viên giao (Shipper)</label>
                    <select name="shipper_id" class="form-select filter-select">
                        <option value="">-- Tất cả Shipper --</option>
                        @foreach($dsShipper as $sp)
                            <option value="{{ $sp->ma_nhan_vien }}" {{ $shipperId == $sp->ma_nhan_vien ? 'selected' : '' }}>
                                {{ $sp->ten_nhan_vien }} {{ $sp->so_dien_thoai ? "({$sp->so_dien_thoai})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. Trạng thái giao hàng --}}
                <div class="rs-filter-col rs-col-select">
                    <label class="filter-label">Trạng thái giao hàng</label>
                    <select name="trang_thai" class="form-select filter-select">
                        <option value="all" {{ $trangThaiFilter === 'all' ? 'selected' : '' }}>-- Tất cả trạng thái --</option>
                        <option value="DELIVERED" {{ $trangThaiFilter === 'DELIVERED' ? 'selected' : '' }}>Giao thành công (DELIVERED)</option>
                        <option value="FAILED" {{ $trangThaiFilter === 'FAILED' ? 'selected' : '' }}>Giao thất bại (FAILED)</option>
                        <option value="SHIPPING" {{ $trangThaiFilter === 'SHIPPING' ? 'selected' : '' }}>Đang giao hàng (SHIPPING)</option>
                        <option value="CONFIRMED" {{ $trangThaiFilter === 'CONFIRMED' ? 'selected' : '' }}>Chờ lấy hàng (CONFIRMED)</option>
                    </select>
                </div>

                {{-- 5. Nút Lọc dữ liệu --}}
                <div class="rs-filter-col rs-col-btn">
                    <button type="submit" class="btn btn-filter-pink" title="Lọc dữ liệu">
                        <i class="bi bi-funnel"></i>
                        <span>Lọc</span>
                    </button>
                </div>

                {{-- 6. Nút Đặt lại --}}
                <div class="rs-filter-col rs-col-btn">
                    <a href="{{ route('admin.bao-cao.giao-hang') }}" class="btn btn-reset" title="Đặt lại bộ lọc">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Đặt lại</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════ 3. KPI CARDS GRID (BỐ CỤC COMPACT CHUẨN ĐỀ XUẤT) ═══════════════════════ --}}
    <div class="kpi-cards-grid">
        {{-- KPI 1: Tổng đơn giao --}}
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="TỔNG ĐƠN GIAO">TỔNG ĐƠN GIAO</div>
                <div class="kpi-value">{{ number_format($kpis['tong_don_giao']) }}</div>
                <div class="kpi-sub" title="Đã vào luồng giao">Đã vào luồng giao</div>
            </div>
        </div>

        {{-- KPI 2: Giao thành công --}}
        <div class="kpi-card kpi-emerald">
            <div class="kpi-icon"><i class="bi bi-check-circle"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="GIAO THÀNH CÔNG">GIAO THÀNH CÔNG</div>
                <div class="kpi-value">{{ number_format($kpis['giao_thanh_cong']) }}</div>
                <div class="kpi-sub" title="Trạng thái DELIVERED">Trạng thái DELIVERED</div>
            </div>
        </div>

        {{-- KPI 3: Giao thất bại --}}
        <div class="kpi-card kpi-rose">
            <div class="kpi-icon"><i class="bi bi-x-circle"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="GIAO THẤT BẠI">GIAO THẤT BẠI</div>
                <div class="kpi-value">{{ number_format($kpis['giao_that_bai']) }}</div>
                <div class="kpi-sub" title="Báo thất bại / hoàn">Báo thất bại / hoàn</div>
            </div>
        </div>

        {{-- KPI 4: Tỷ lệ thành công --}}
        <div class="kpi-card kpi-indigo">
            <div class="kpi-icon"><i class="bi bi-percent"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="TỶ LỆ THÀNH CÔNG">TỶ LỆ THÀNH CÔNG</div>
                <div class="kpi-value">{{ $kpis['ty_le_thanh_cong'] }}%</div>
                <div class="kpi-sub" title="Thành công / Tổng đơn">Thành công / Tổng đơn</div>
            </div>
        </div>

        {{-- KPI 5: Shipper từ chối --}}
        <div class="kpi-card kpi-gray" title="Chức năng Shipper từ chối nhận đơn chưa được persist trong cơ sở dữ liệu hiện tại">
            <div class="kpi-icon"><i class="bi bi-slash-circle"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="SHIPPER TỪ CHỐI">SHIPPER TỪ CHỐI</div>
                <div class="kpi-value">0</div>
                <div class="kpi-sub" title="Chưa có sự kiện DB">Chưa có sự kiện DB</div>
            </div>
        </div>

        {{-- KPI 6: Tổng COD đã thu --}}
        <div class="kpi-card kpi-amber">
            <div class="kpi-icon"><i class="bi bi-wallet2"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="TỔNG COD ĐÃ THU">TỔNG COD ĐÃ THU</div>
                <div class="kpi-value" style="font-size: 16px;">{{ number_format($kpis['tong_cod_da_thu'], 0, ',', '.') }}đ</div>
                <div class="kpi-sub" title="Đơn DELIVERED tiền mặt">Đơn DELIVERED tiền mặt</div>
            </div>
        </div>

        {{-- KPI 7: COD chưa nộp --}}
        <div class="kpi-card kpi-orange" title="Hệ thống hiện chưa có bảng/nghiệp vụ đối soát nộp tiền COD từ Shipper về cửa hàng">
            <div class="kpi-icon"><i class="bi bi-inbox"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="COD CHƯA NỘP">COD CHƯA NỘP</div>
                <div class="kpi-value">0đ</div>
                <div class="kpi-sub" title="Shipper đang giữ tiền">Shipper đang giữ tiền</div>
            </div>
        </div>

        {{-- KPI 8: Thời gian giao TB --}}
        <div class="kpi-card kpi-teal">
            <div class="kpi-icon"><i class="bi bi-stopwatch"></i></div>
            <div class="kpi-info">
                <div class="kpi-title" title="GIAO TB">GIAO TB</div>
                <div class="kpi-value" style="font-size: 16px;">{{ $kpis['thoi_gian_giao_tb'] }}</div>
                <div class="kpi-sub" title="Từ bắt đầu đến khi giao">Từ bắt đầu đến khi giao</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════ 4. BIỂU ĐỒ GIAO HÀNG ═══════════════════════ --}}
    <div class="charts-grid">
        {{-- Biểu đồ 1: Xu hướng giao hàng theo ngày --}}
        <div class="chart-card">
            <div class="chart-header">
                <span class="chart-title">
                    <i class="bi bi-graph-up text-primary"></i> Xu hướng giao hàng theo ngày
                </span>
                <div class="chart-legend-custom">
                    <span class="chart-legend-item">
                        <span class="chart-legend-badge" style="background: #10b981;"></span> Thành công
                    </span>
                    <span class="chart-legend-item">
                        <span class="chart-legend-badge" style="background: #ef4444;"></span> Thất bại
                    </span>
                </div>
            </div>
            <div class="chart-body">
                @if($kpis['tong_don_giao'] > 0)
                    <canvas id="chartXuHuongGiaoHang"></canvas>
                @else
                    <div class="empty-state">
                        <i class="bi bi-bar-chart-line d-block"></i>
                        <p>Không có dữ liệu giao hàng trong khoảng thời gian này.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Biểu đồ 2: Kết quả giao hàng (Doughnut Chart) --}}
        <div class="chart-card">
            <div class="chart-header">
                <span class="chart-title">
                    <i class="bi bi-pie-chart text-danger"></i> Kết quả giao hàng
                </span>
            </div>
            <div class="chart-body">
                @if($kpis['tong_don_giao'] > 0)
                    <canvas id="chartKetQuaGiaoHang"></canvas>
                @else
                    <div class="empty-state">
                        <i class="bi bi-pie-chart d-block"></i>
                        <p>Chưa có dữ liệu giao hàng.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════ 5. BẢNG HIỆU SUẤT SHIPPER (TOP SHIPPER) ═══════════════════════ --}}
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title">
                <i class="bi bi-person-badge text-primary"></i> Hiệu suất nhân viên giao hàng (Shipper)
            </h3>
            <span class="text-muted small">Thống kê theo số đơn phụ trách trong kỳ</span>
        </div>
        <div class="table-responsive">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">STT</th>
                        <th style="white-space: nowrap;">Shipper</th>
                        <th style="white-space: nowrap;">Số điện thoại</th>
                        <th class="text-center" style="white-space: nowrap;">Tổng giao</th>
                        <th class="text-center" style="white-space: nowrap;">Thành công</th>
                        <th class="text-center" style="white-space: nowrap;">Thất bại</th>
                        <th class="text-center" style="white-space: nowrap;">Đang giao</th>
                        <th class="text-center" style="white-space: nowrap;">Tỷ lệ thành công</th>
                        <th class="text-end" style="white-space: nowrap;">COD đã thu</th>
                        <th class="text-center" style="white-space: nowrap;">TG giao TB</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topShippers as $idx => $ts)
                        <tr>
                            <td class="text-center text-muted" style="white-space: nowrap; font-size: 12px;">{{ $idx + 1 }}</td>
                            <td style="white-space: nowrap;">
                                <strong style="font-size: 12.5px;">{{ $ts->ten_nhan_vien }}</strong>
                                <div class="text-muted small" style="font-size: 11px;">Mã NV: #{{ $ts->ma_nhan_vien }}</div>
                            </td>
                            <td style="white-space: nowrap; font-size: 12px;">{{ $ts->so_dien_thoai }}</td>
                            <td class="text-center fw-bold" style="white-space: nowrap; font-size: 12.5px;">{{ number_format($ts->tong_giao) }}</td>
                            <td class="text-center text-success fw-bold" style="white-space: nowrap; font-size: 12.5px;">{{ number_format($ts->thanh_cong) }}</td>
                            <td class="text-center text-danger fw-bold" style="white-space: nowrap; font-size: 12.5px;">{{ number_format($ts->that_bai) }}</td>
                            <td class="text-center text-primary fw-bold" style="white-space: nowrap; font-size: 12.5px;">{{ number_format($ts->dang_giao) }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <span class="badge {{ $ts->ty_le >= 80 ? 'bg-success' : ($ts->ty_le >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}" style="font-size: 11px;">
                                    {{ $ts->ty_le }}%
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark" style="white-space: nowrap; font-size: 12.5px;">{{ number_format($ts->cod_da_thu, 0, ',', '.') }}đ</td>
                            <td class="text-center" style="white-space: nowrap; font-size: 12px;">{{ $ts->tg_giao_tb }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                                Chưa có đơn hàng nào được phân công cho Shipper trong khoảng thời gian này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════ 6. BẢNG CHI TIẾT DANH SÁCH GIAO HÀNG ═══════════════════════ --}}
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title">
                <i class="bi bi-list-check text-success"></i> Danh sách đơn giao hàng
            </h3>
            <span class="text-muted small">Hiển thị {{ $danhSachDonHang->firstItem() ?? 0 }} - {{ $danhSachDonHang->lastItem() ?? 0 }} trên tổng số {{ $danhSachDonHang->total() }} đơn</span>
        </div>

        {{-- Toolbar tìm kiếm chuẩn RoseShop --}}
        <div class="table-toolbar">
            <form method="GET" action="{{ route('admin.bao-cao.giao-hang') }}" class="rs-table-search-form" id="formTableSearch">
                <input type="hidden" name="ngay_bat_dau" value="{{ $ngayBatDau->format('Y-m-d') }}">
                <input type="hidden" name="ngay_ket_thuc" value="{{ $ngayKetThuc->format('Y-m-d') }}">
                @if($shipperId)
                    <input type="hidden" name="shipper_id" value="{{ $shipperId }}">
                @endif
                @if($trangThaiFilter !== 'all')
                    <input type="hidden" name="trang_thai" value="{{ $trangThaiFilter }}">
                @endif
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif

                <div class="rs-search-box-wrap" style="position: relative;">
                    <i class="bi bi-search rs-search-box-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #98A2B3; font-size: 13.5px; z-index: 3; pointer-events: none;"></i>
                    <input type="text" name="q" class="form-control rs-search-box-input"
                           placeholder="Tìm theo mã đơn, khách, SĐT, shipper..."
                           value="{{ $keyword }}"
                           style="padding-left: 42px !important; height: 38px !important; font-size: 12.5px !important;">
                </div>
                <button type="submit" class="btn btn-search-pink" title="Tìm kiếm">
                    <i class="bi bi-search"></i>
                    <span>Tìm</span>
                </button>
                @if(!empty($keyword))
                    <a href="{{ route('admin.bao-cao.giao-hang', request()->except('q')) }}" class="btn btn-reset" title="Xóa tìm kiếm">
                        <i class="bi bi-x-circle"></i>
                        <span>Xóa tìm</span>
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">STT</th>
                        <th style="white-space: nowrap;">Mã đơn</th>
                        <th style="white-space: nowrap;">Khách hàng</th>
                        <th style="white-space: nowrap;">Shipper</th>
                        <th style="white-space: nowrap;">Bắt đầu giao</th>
                        <th style="white-space: nowrap;">Hoàn thành</th>
                        <th class="text-center" style="white-space: nowrap;">Thời gian giao</th>
                        <th class="text-center" style="white-space: nowrap;">Trạng thái</th>
                        <th style="white-space: nowrap;">Thanh toán</th>
                        <th class="text-end" style="min-width: 145px; white-space: nowrap;">Tiền COD</th>
                        <th style="width: 60px;" class="text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($danhSachDonHang as $idx => $hd)
                        <tr>
                            <td class="text-center text-muted" style="white-space: nowrap; font-size: 12px;">{{ $danhSachDonHang->firstItem() + $idx }}</td>
                            <td style="white-space: nowrap;">
                                <strong class="order-code-pink" style="font-size: 12.5px;">{{ $hd->ma_hien_thi }}</strong>
                                <div class="text-muted small" style="font-size: 11px;">{{ $hd->ngay_dat ? $hd->ngay_dat->format('d/m/Y') : '' }}</div>
                            </td>
                            <td style="white-space: nowrap;">
                                <div class="fw-bold" style="font-size: 12px;">{{ $hd->khachHang ? $hd->khachHang->ho_ten : 'Khách vãng lai' }}</div>
                                <div class="text-muted small" style="font-size: 11px;">
                                    {{ $hd->so_dien_thoai ?: ($hd->khachHang ? $hd->khachHang->so_dien_thoai : '') }}
                                </div>
                            </td>
                            <td style="white-space: nowrap; min-width: 135px;">
                                @if($hd->shipper)
                                    <span class="fw-semibold text-dark" style="font-size: 12px; white-space: nowrap;">{{ $hd->shipper->ten_nhan_vien }}</span>
                                @else
                                    <span class="text-muted fst-italic" style="font-size: 12px;">Chưa phân công</span>
                                @endif
                            </td>
                            <td style="white-space: nowrap; font-size: 12px;">
                                {{ $hd->ngay_bat_giao ? $hd->ngay_bat_giao->format('d/m H:i') : '—' }}
                            </td>
                            <td style="white-space: nowrap; font-size: 12px;">
                                @if($hd->trang_thai === 'DELIVERED')
                                    {{ $hd->ngay_giao ? $hd->ngay_giao->format('d/m H:i') : ($hd->ngay_den_noi ? $hd->ngay_den_noi->format('d/m H:i') : '—') }}
                                @elseif($hd->trang_thai === 'CANCELLED')
                                    <span class="text-danger small" style="font-size: 11px;">{{ $hd->thoi_diem_huy ? $hd->thoi_diem_huy->format('d/m H:i') : 'Đã hủy' }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center font-monospace" style="white-space: nowrap; font-size: 12px;">
                                {{ $hd->thoi_gian_giao_text }}
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <span class="badge-status {{ $hd->trang_thai_badge }}">
                                    {{ $hd->trang_thai_text }}
                                </span>
                                @if($hd->trang_thai_giao === 'FAILED' && $hd->loai_loi)
                                    <div class="text-danger small mt-1" style="font-size: 0.72rem; white-space: nowrap;">
                                        Do {{ $hd->loai_loi }}
                                    </div>
                                @endif
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 12px;">{{ $hd->phuong_thuc_thanh_toan }}</div>
                                <div class="text-muted small" style="font-size: 11px;">{{ number_format($hd->tong_tien, 0, ',', '.') }}đ</div>
                            </td>
                            <td class="text-end" style="white-space: nowrap;">
                                @if($hd->phuong_thuc_thanh_toan === 'COD')
                                    <span class="fw-bold text-dark" style="font-size: 12.5px;">{{ number_format($hd->tien_cod, 0, ',', '.') }}đ</span>
                                    @if($hd->trang_thai === 'DELIVERED' && ($hd->da_thu_tien_mat || $hd->trang_thai_thanh_toan === 'DA_THANH_TOAN'))
                                        <span class="badge-cod-collected">Đã thu</span>
                                    @else
                                        <span class="badge-cod-unpaid">Chưa thu</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <button type="button" class="btn-view-detail" title="Xem chi tiết đơn giao" onclick="openDeliveryDrawer({{ $hd->ma_hoa_don }})">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-search d-block"></i>
                                    <h5>Không có dữ liệu giao hàng</h5>
                                    <p>Không có đơn giao hàng phù hợp với điều kiện lọc hiện tại.</p>
                                    <a href="{{ route('admin.bao-cao.giao-hang') }}" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Đặt lại bộ lọc
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                {{-- Dòng tổng cộng Footer --}}
                @if($danhSachDonHang->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-uppercase fw-semibold" style="white-space: nowrap; font-size: 12px;">TỔNG CỘNG TRONG KỲ</td>
                            <td colspan="2" class="text-center" style="white-space: nowrap; font-size: 12px;">Thành công: <span class="text-success fw-bold">{{ $kpis['giao_thanh_cong'] }}</span> / Thất bại: <span class="text-danger fw-bold">{{ $kpis['giao_that_bai'] }}</span></td>
                            <td class="text-center" style="white-space: nowrap;">—</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <span class="badge-status badge-delivered">{{ $kpis['ty_le_thanh_cong'] }}%</span>
                            </td>
                            <td style="white-space: nowrap;">—</td>
                            <td class="text-end text-danger fw-bold" style="white-space: nowrap; font-size: 12.5px;">{{ number_format($kpis['tong_cod_da_thu'], 0, ',', '.') }}đ</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Thanh phân trang chuẩn RoseShop có dấu ba chấm --}}
        <div class="rs-table-footer">
            <div class="rs-footer-left">
                <form method="GET" action="{{ route('admin.bao-cao.giao-hang') }}" class="rs-per-page-form" id="perPageForm">
                    <input type="hidden" name="ngay_bat_dau" value="{{ $ngayBatDau->format('Y-m-d') }}">
                    <input type="hidden" name="ngay_ket_thuc" value="{{ $ngayKetThuc->format('Y-m-d') }}">
                    @if($shipperId)
                        <input type="hidden" name="shipper_id" value="{{ $shipperId }}">
                    @endif
                    @if($trangThaiFilter !== 'all')
                        <input type="hidden" name="trang_thai" value="{{ $trangThaiFilter }}">
                    @endif
                    @if(!empty($keyword))
                        <input type="hidden" name="q" value="{{ $keyword }}">
                    @endif

                    <label class="rs-per-page-inline">
                        <span>Hiển thị:</span>
                        <select name="per_page" class="rs-per-page-select" onchange="this.form.submit()">
                            @foreach([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" {{ request('per_page', 20) == $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                        <span>đơn/trang</span>
                    </label>
                </form>

                <span class="rs-records-count">
                    Hiển thị <strong>{{ $danhSachDonHang->firstItem() ?? 0 }} - {{ $danhSachDonHang->lastItem() ?? 0 }}</strong> trên tổng số <strong>{{ $danhSachDonHang->total() }}</strong> đơn
                </span>
            </div>

            <div class="rs-footer-right">
                @php
                    $cur = $danhSachDonHang->currentPage();
                    $last = $danhSachDonHang->lastPage();
                    $start = max(1, $cur - 2);
                    $end = min($last, $cur + 2);
                @endphp
                <ul class="pagination mb-0">
                    @if ($danhSachDonHang->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $danhSachDonHang->appends(request()->query())->previousPageUrl() }}">&laquo;</a></li>
                    @endif

                    @if($start > 1)
                        <li class="page-item"><a class="page-link" href="{{ $danhSachDonHang->appends(request()->query())->url(1) }}">1</a></li>
                        @if($start > 2)
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        @endif
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page == $cur)
                            <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $danhSachDonHang->appends(request()->query())->url($page) }}">{{ $page }}</a></li>
                        @endif
                    @endfor

                    @if($end < $last)
                        @if($end < $last - 1)
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        @endif
                        <li class="page-item"><a class="page-link" href="{{ $danhSachDonHang->appends(request()->query())->url($last) }}">{{ $last }}</a></li>
                    @endif

                    @if ($danhSachDonHang->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $danhSachDonHang->appends(request()->query())->nextPageUrl() }}">&raquo;</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════ 7. DRAWER CHI TIẾT GIAO HÀNG (READ-ONLY) ═══════════════════════ --}}
<div class="drawer-backdrop" id="drawerBackdrop" onclick="closeDeliveryDrawer()"></div>
<div class="drawer-panel" id="deliveryDrawer">
    <div class="drawer-header">
        <div class="drawer-title-wrap">
            <h4 class="drawer-title" id="drawerOrderCode">#HD-0000</h4>
            <span class="badge-status" id="drawerStatusBadge">ĐANG TẢI...</span>
        </div>
        <button type="button" class="drawer-close" onclick="closeDeliveryDrawer()" aria-label="Đóng">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body" id="drawerBody">
        <div class="text-center py-5">
            <div class="spinner-border text-danger" role="status"></div>
            <p class="mt-2 text-muted">Đang tải thông tin chi tiết giao hàng...</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // ═══════════════════════ QUICK DATE FILTER ═══════════════════════
    function setQuickDate(type) {
        const today = new Date();
        let start = new Date();
        let end = new Date();

        if (type === 'today') {
            start = today;
            end = today;
        } else if (type === '7days') {
            start.setDate(today.getDate() - 6);
        } else if (type === 'this_month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = today;
        } else if (type === 'last_month') {
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
        }

        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        document.getElementById('filter_ngay_bat_dau').value = formatDate(start);
        document.getElementById('filter_ngay_ket_thuc').value = formatDate(end);
        document.getElementById('formFilterGiaoHang').submit();
    }

    // ═══════════════════════ CHARTS ═══════════════════════
    document.addEventListener('DOMContentLoaded', function () {
        // Biểu đồ 1: Xu hướng giao hàng
        const ctxXuHuong = document.getElementById('chartXuHuongGiaoHang');
        if (ctxXuHuong) {
            new Chart(ctxXuHuong, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartXuHuong['labels']) !!},
                    datasets: [
                        {
                            label: 'Thành công',
                            data: {!! json_encode($chartXuHuong['thanhCong']) !!},
                            backgroundColor: '#10b981',
                            borderRadius: 4,
                            barPercentage: 0.6,
                        },
                        {
                            label: 'Thất bại',
                            data: {!! json_encode($chartXuHuong['thatBai']) !!},
                            backgroundColor: '#ef4444',
                            borderRadius: 4,
                            barPercentage: 0.6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Biểu đồ 2: Kết quả giao hàng (Doughnut)
        const ctxKetQua = document.getElementById('chartKetQuaGiaoHang');
        if (ctxKetQua) {
            new Chart(ctxKetQua, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($chartKetQua['labels']) !!},
                    datasets: [{
                        data: {!! json_encode($chartKetQua['data']) !!},
                        backgroundColor: {!! json_encode($chartKetQua['colors']) !!},
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 14,
                                font: { size: 12 }
                            }
                        }
                    },
                    cutout: '68%'
                }
            });
        }
    });

    // ═══════════════════════ DRAWER CHI TIẾT ═══════════════════════
    function openDeliveryDrawer(orderId) {
        const backdrop = document.getElementById('drawerBackdrop');
        const drawer = document.getElementById('deliveryDrawer');
        const body = document.getElementById('drawerBody');
        const codeElem = document.getElementById('drawerOrderCode');
        const badgeElem = document.getElementById('drawerStatusBadge');

        backdrop.classList.add('show');
        drawer.classList.add('show');
        document.body.style.overflow = 'hidden';

        codeElem.textContent = `#HD-${String(orderId).padStart(4, '0')}`;
        badgeElem.className = 'badge-status';
        badgeElem.textContent = 'Đang tải...';

        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-danger" role="status"></div>
                <p class="mt-2 text-muted">Đang tải thông tin chi tiết giao hàng...</p>
            </div>
        `;

        fetch(`{{ url('/admin/bao-cao/giao-hang') }}/${orderId}/chi-tiet`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    body.innerHTML = `<div class="alert alert-danger">${res.message || 'Không thể tải chi tiết.'}</div>`;
                    return;
                }
                const d = res.data;
                codeElem.textContent = d.ma_hien_thi;
                badgeElem.className = `badge-status ${d.trang_thai_badge}`;
                badgeElem.textContent = d.trang_thai_text;

                let failureHtml = '';
                if (d.that_bai) {
                    failureHtml = `
                        <div class="failure-alert-box">
                            <div class="failure-alert-title">
                                <i class="bi bi-exclamation-octagon-fill"></i> THÔNG TIN GIAO HÀNG THẤT BẠI
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-item-label">Nguồn lỗi</span>
                                    <span class="detail-item-value text-danger">${d.that_bai.loai_loi_text}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-item-label">Thời điểm báo thất bại</span>
                                    <span class="detail-item-value">${d.that_bai.thoi_diem}</span>
                                </div>
                                <div class="detail-item full-width">
                                    <span class="detail-item-label">Lý do thất bại</span>
                                    <span class="detail-item-value">${d.that_bai.ly_do}</span>
                                </div>
                                ${d.that_bai.hang_hoan ? `
                                    <div class="detail-item full-width mt-1">
                                        <span class="detail-item-label">Trạng thái hàng hoàn</span>
                                        <span class="detail-item-value text-warning fw-bold">
                                            <i class="bi bi-arrow-return-left me-1"></i>${d.that_bai.hang_hoan.trang_thai_text} (#HH-${d.that_bai.hang_hoan.ma_hang_hoan})
                                        </span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }

                let productsHtml = '';
                if (d.san_pham && d.san_pham.length > 0) {
                    productsHtml = d.san_pham.map(sp => `
                        <tr>
                            <td>${sp.ten}</td>
                            <td class="text-center">${sp.so_luong}</td>
                            <td class="text-end">${Number(sp.don_gia).toLocaleString('vi-VN')}đ</td>
                            <td class="text-end fw-bold">${Number(sp.thanh_tien).toLocaleString('vi-VN')}đ</td>
                        </tr>
                    `).join('');
                } else {
                    productsHtml = `<tr><td colspan="4" class="text-center text-muted">Không có thông tin sản phẩm.</td></tr>`;
                }

                body.innerHTML = `
                    ${failureHtml}

                    {{-- Khách hàng --}}
                    <div class="detail-section">
                        <h5 class="detail-section-title"><i class="bi bi-person"></i> THÔNG TIN KHÁCH HÀNG</h5>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-item-label">Họ và tên</span>
                                <span class="detail-item-value">${d.khach_hang.ho_ten}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-item-label">Số điện thoại</span>
                                <span class="detail-item-value">${d.khach_hang.so_dien_thoai}</span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-item-label">Địa chỉ nhận hàng</span>
                                <span class="detail-item-value">${d.khach_hang.dia_chi_giao}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Shipper --}}
                    <div class="detail-section">
                        <h5 class="detail-section-title"><i class="bi bi-bicycle"></i> NHÂN VIÊN GIAO HÀNG (SHIPPER)</h5>
                        ${d.shipper ? `
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-item-label">Họ tên Shipper</span>
                                    <span class="detail-item-value">${d.shipper.ten_nhan_vien}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-item-label">Số điện thoại</span>
                                    <span class="detail-item-value">${d.shipper.so_dien_thoai}</span>
                                </div>
                            </div>
                        ` : `
                            <div class="text-muted small fst-italic">Đơn hàng chưa có nhân viên phụ trách giao.</div>
                        `}
                    </div>

                    {{-- Mốc thời gian --}}
                    <div class="detail-section">
                        <h5 class="detail-section-title"><i class="bi bi-clock"></i> MỐC THỜI GIAN GIAO HÀNG</h5>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-item-label">Thời điểm đặt hàng</span>
                                <span class="detail-item-value">${d.ngay_dat}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-item-label">Bắt đầu giao</span>
                                <span class="detail-item-value">${d.ngay_bat_giao}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-item-label">Đến điểm giao</span>
                                <span class="detail-item-value">${d.ngay_den_noi}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-item-label">${d.trang_thai === 'DELIVERED' ? 'Hoàn thành giao' : 'Thời điểm kết thúc'}</span>
                                <span class="detail-item-value ${d.trang_thai === 'DELIVERED' ? 'text-success' : ''}">
                                    ${d.ngay_giao !== '—' ? d.ngay_giao : (d.thoi_diem_huy || '—')}
                                </span>
                            </div>
                            <div class="detail-item full-width mt-1">
                                <span class="detail-item-label">Thời gian giao thực tế (Duration)</span>
                                <span class="detail-item-value text-primary fs-6 fw-bold">
                                    <i class="bi bi-stopwatch me-1"></i>${d.thoi_gian_giao}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Thanh toán & COD --}}
                    <div class="detail-section">
                        <h5 class="detail-section-title"><i class="bi bi-credit-card"></i> THANH TOÁN & TIỀN COD</h5>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-item-label">Phương thức thanh toán</span>
                                <span class="detail-item-value fw-bold">${d.thanh_toan.phuong_thuc}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-item-label">Tổng tiền đơn</span>
                                <span class="detail-item-value fw-bold text-danger">${Number(d.thanh_toan.tong_tien).toLocaleString('vi-VN')}đ</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-item-label">Trạng thái thanh toán</span>
                                <span class="detail-item-value">${d.thanh_toan.trang_thai_thanh_toan}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-item-label">Đã thu tiền mặt</span>
                                <span class="detail-item-value ${d.thanh_toan.da_thu_tien_mat ? 'text-success fw-bold' : 'text-muted'}">
                                    ${d.thanh_toan.da_thu_tien_mat ? '<i class="bi bi-check-circle-fill me-1"></i>ĐÃ THU' : 'CHƯA THU'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Sản phẩm trong đơn --}}
                    <div class="detail-section">
                        <h5 class="detail-section-title"><i class="bi bi-basket"></i> MẶT HÀNG TRONG ĐƠN</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sản phẩm / Bó hoa</th>
                                        <th class="text-center" style="width: 60px;">SL</th>
                                        <th class="text-end" style="width: 100px;">Đơn giá</th>
                                        <th class="text-end" style="width: 110px;">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${productsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            })
            .catch(err => {
                body.innerHTML = `<div class="alert alert-danger">Đã xảy ra lỗi khi tải dữ liệu: ${err.message}</div>`;
            });
    }

    function closeDeliveryDrawer() {
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.getElementById('deliveryDrawer').classList.remove('show');
        document.body.style.overflow = '';
    }

    // Đóng drawer bằng phím Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeliveryDrawer();
        }
    });
</script>
@endpush
