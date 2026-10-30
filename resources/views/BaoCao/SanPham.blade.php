@extends('layouts.admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/Admin/BaoCaoSanPham.css') }}">
@endpush

@section('content')

<div class="bao-cao-san-pham-container">
{{-- ═══════════════════════ TOPBAR ═══════════════════════ --}}


<div class="page-container">

    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Báo cáo sản phẩm & nguyên liệu</h1>
            <p class="rs-page-subtitle">Phân tích số lượng tiêu thụ, tiêu hao sản xuất, hàng tồn kho và tỷ lệ hao hụt sản phẩm.</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('bao-cao.san-pham.export', array_filter([
                    'ngay_bat_dau' => $ngayBatDau->format('Y-m-d'),
                    'ngay_ket_thuc' => $ngayKetThuc->format('Y-m-d'),
                    'keyword' => $keyword
                ])) }}"
               class="btn btn-excel">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Xuất CSV
            </a>
        </div>
    </div>

    @if(!empty($dateWarning))
        <div class="alert alert-warning mb-3 py-2 px-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $dateWarning }}
        </div>
    @endif

    {{-- ═══════════════════════ BỘ LỌC ═══════════════════════ --}}
    <div class="filter-card mb-4">
        <div class="filter-card-header mb-3">
            <span class="filter-title">
                <i class="bi bi-funnel-fill me-1"></i> Bộ lọc
            </span>
        </div>

        <form method="GET" action="{{ route('bao-cao.san-pham') }}" id="formFilter">
            @if(request('per_page'))
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            @endif

            <div class="rs-filter-row">
                {{-- 1. Ngày bắt đầu --}}
                <div class="rs-filter-col rs-col-date">
                    <label class="filter-label">Ngày bắt đầu</label>
                    <div class="input-icon-wrap">
                        <input type="date"
                               name="ngay_bat_dau"
                               class="form-control filter-input"
                               value="{{ $ngayBatDau->format('Y-m-d') }}">
                        <i class="bi bi-calendar3 input-icon"></i>
                    </div>
                </div>

                {{-- 2. Ngày kết thúc --}}
                <div class="rs-filter-col rs-col-date">
                    <label class="filter-label">Ngày kết thúc</label>
                    <div class="input-icon-wrap">
                        <input type="date"
                               name="ngay_ket_thuc"
                               class="form-control filter-input"
                               value="{{ $ngayKetThuc->format('Y-m-d') }}">
                        <i class="bi bi-calendar3 input-icon"></i>
                    </div>
                </div>

                {{-- 3. Nút Lọc --}}
                <div class="rs-filter-col rs-col-btn">
                    <button type="submit" class="btn btn-filter-pink" title="Lọc theo khoảng ngày">
                        <i class="bi bi-funnel"></i>
                        <span>Lọc</span>
                    </button>
                </div>

                {{-- 4. Nút Đặt lại --}}
                <div class="rs-filter-col rs-col-btn">
                    <a href="{{ route('bao-cao.san-pham') }}" class="btn btn-reset" title="Đặt lại bộ lọc">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Đặt lại</span>
                    </a>
                </div>

                {{-- 5. Khung tìm kiếm sản phẩm (đưa ra phía sau, giãn đều vừa khung) --}}
                <div class="rs-filter-col rs-col-search">
                    <label class="filter-label">Tìm kiếm sản phẩm</label>
                    <div class="rs-search-box-wrap">
                        <i class="bi bi-search rs-search-box-icon"></i>
                        <input type="text"
                               name="keyword"
                               class="form-control rs-search-box-input"
                               style="padding-left: 46px !important;"
                               placeholder="Tìm theo tên sản phẩm..."
                               value="{{ $keyword }}">
                    </div>
                </div>

                {{-- 6. Nút Tìm --}}
                <div class="rs-filter-col rs-col-btn">
                    <button type="submit" class="btn btn-search-pink" title="Tìm theo từ khóa">
                        <i class="bi bi-search"></i>
                        <span>Tìm</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════ BẢNG BÁO CÁO SẢN PHẨM ═══════════════════════ --}}
    <div class="table-card mb-3">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="col-stt">STT</th>
                        <th class="col-sp">Sản phẩm</th>
                        <th class="col-loai">Loại sản phẩm</th>
                        <th class="col-num">Nhập trong kỳ<br><small>(Số lượng)</small></th>
                        <th class="col-num">Bán trong kỳ<br><small>(Số lượng)</small></th>
                        <th class="col-num">Tiêu hao SX<br><small>(Làm bó hoa)</small></th>
                        <th class="col-num">Hàng hỏng<br><small>(Số lượng)</small></th>
                        <th class="col-num">Tồn hiện tại<br><small>(On-Hand)</small></th>
                        <th class="col-num">Giá nhập TB<br><small>(VNĐ)</small></th>
                        <th class="col-num">Giá bán hiện tại<br><small>(VNĐ)</small></th>
                        <th class="col-num">Doanh thu<br><small>(VNĐ)</small></th>
                        <th class="col-num">Tỷ lệ hao hụt<br><small>(%)</small></th>
                        <th class="col-tt">Trạng thái</th>
                        <th class="col-action">Chi tiết</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($baoCaoSanPham as $i => $row)
                        <tr>
                            <td class="text-center text-muted" style="font-size: 12px; font-weight: 500;">
                                {{ ($baoCaoSanPham->currentPage() - 1) * $baoCaoSanPham->perPage() + $i + 1 }}
                            </td>

                            {{-- Sản phẩm --}}
                            <td class="text-start">
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 175px; font-size: 12.5px;" title="{{ $row['ten_san_pham'] }}">
                                    {{ $row['ten_san_pham'] }}
                                </div>
                            </td>

                            {{-- Loại --}}
                            <td>
                                <div style="font-size: 12px; white-space: nowrap;">
                                    {{ \App\Helpers\LabelHelper::loaiSanPham($row['loai_san_pham']) }}
                                </div>
                            </td>

                            {{-- Nhập thêm --}}
                            <td>
                                <div class="num-green" style="font-size: 12.5px; white-space: nowrap;">
                                    {{ number_format($row['nhap_them']) }}
                                </div>
                            </td>

                            {{-- Đã bán --}}
                            <td>
                                <div class="num-purple" style="font-size: 12.5px; white-space: nowrap;">
                                    {{ number_format($row['da_ban']) }}
                                </div>
                            </td>

                            {{-- Tiêu hao sản xuất bó hoa --}}
                            <td>
                                <div class="num-blue" style="font-size: 12.5px; white-space: nowrap;">
                                    {{ number_format($row['tieu_hao_san_xuat']) }}
                                </div>
                            </td>

                            {{-- Hàng hỏng --}}
                            <td>
                                <div class="num-red" style="font-size: 12.5px; white-space: nowrap;">
                                    {{ number_format($row['hang_hong']) }}
                                </div>

                                @if($row['hang_hong'] > 0)
                                    <div>
                                        <a href="#"
                                           class="link-chitiet"
                                           data-id="{{ $row['ma_san_pham'] }}"
                                           data-ten="{{ $row['ten_san_pham'] }}"
                                           data-batdau="{{ $ngayBatDau->format('Y-m-d') }}"
                                           data-ketthuc="{{ $ngayKetThuc->format('Y-m-d') }}"
                                           style="font-size: 10.5px; white-space: nowrap;">
                                            &lt;&lt; Chi tiết &gt;&gt;
                                        </a>
                                    </div>
                                @endif
                            </td>

                            {{-- Tồn kho hiện tại (On-Hand) kèm tooltip Khả dụng/Đang giữ --}}
                            <td>
                                <div class="num-orange" style="font-size: 12.5px; white-space: nowrap;">
                                    <span title="Tồn vật lý: {{ $row['ton_kho_hien_tai'] }} | Khả dụng: {{ $row['ton_kha_dung'] }} | Đang giữ: {{ $row['dang_giu'] }}" style="cursor:help;">
                                        {{ number_format($row['ton_kho_hien_tai']) }}
                                    </span>
                                </div>
                                @if($row['dang_giu'] > 0)
                                    <div class="text-muted" style="font-size: 10px; white-space: nowrap;">(Giữ: {{ $row['dang_giu'] }})</div>
                                @endif
                            </td>

                            {{-- Giá nhập TB --}}
                            <td>
                                <div class="text-end" style="font-size: 12px; white-space: nowrap;">
                                    {{ $row['gia_nhap_tb'] !== null ? number_format($row['gia_nhap_tb'], 0, ',', '.') : '-' }}
                                </div>
                            </td>

                            {{-- Giá bán hiện tại --}}
                            <td>
                                <div class="text-end" style="font-size: 12px; white-space: nowrap;">
                                    {{ $row['gia_ban_hien_tai'] > 0 ? number_format($row['gia_ban_hien_tai'], 0, ',', '.') : '-' }}
                                </div>
                            </td>

                            {{-- Doanh thu --}}
                            <td>
                                <div class="text-end fw-semibold text-dark" style="font-size: 12.5px; white-space: nowrap;">
                                    {{ number_format($row['doanh_thu'] ?? 0, 0, ',', '.') }}
                                </div>
                            </td>

                            {{-- Tỷ lệ hao hụt --}}
                            <td>
                                <div class="text-end {{ $row['ty_le_hong'] > 10 ? 'num-red fw-bold' : '' }}" style="font-size: 12px; white-space: nowrap;">
                                    {{ number_format($row['ty_le_hong'], 2, ',', '.') }}%
                                </div>
                            </td>

                            {{-- Trạng thái --}}
                            <td class="text-center">
                                <span class="badge-tt {{ $row['trang_thai'] === 'DANG_BAN' ? 'badge-dang-ban' : 'badge-ngung-ban' }}" style="font-size: 11px; white-space: nowrap;">
                                    {{ $row['trang_thai'] === 'DANG_BAN' ? 'Đang bán' : 'Ngừng bán' }}
                                </span>
                            </td>

                            {{-- Chi tiết sản phẩm tổng hợp --}}
                            <td class="text-center">
                                <button type="button"
                                        class="btn-chi-tiet-san-pham"
                                        data-id="{{ $row['ma_san_pham'] }}"
                                        data-batdau="{{ $ngayBatDau->format('Y-m-d') }}"
                                        data-ketthuc="{{ $ngayKetThuc->format('Y-m-d') }}"
                                        title="Xem chi tiết sản phẩm">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Không có dữ liệu sản phẩm trong khoảng thời gian này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- ── DÒNG TỔNG CỘNG ── --}}
                @if($baoCaoSanPham->total() > 0)
                    <tfoot>
                        <tr class="tong-cong-row">
                            <td colspan="3" class="fw-bold">TỔNG CỘNG</td>
                            <td class="num-green fw-bold">{{ number_format($tongNhapThem) }}</td>
                            <td class="num-purple fw-bold">{{ number_format($tongDaBan) }}</td>
                            <td class="num-blue fw-bold">{{ number_format($tongTieuHaoSanXuat) }}</td>
                            <td class="num-red fw-bold">{{ number_format($tongHangHong) }}</td>
                            <td class="num-orange fw-bold">{{ number_format($tongTonKhoHienTai) }}</td>
                            <td class="text-end fw-bold">
                                {{ $giaNhapTrungBinhChung !== null ? number_format($giaNhapTrungBinhChung, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end">—</td>
                            <td class="text-end fw-bold">{{ number_format($tongDoanhThuSanPham ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end num-red fw-bold">{{ number_format($tyLeHongChung, 2, ',', '.') }}%</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- ── THANH PHÂN TRANG CHUẨN ROSESHOP ── --}}
        @if($baoCaoSanPham->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50, 100] as $optPerPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $optPerPage, 'page' => 1]) }}" {{ request('per_page', 10) == $optPerPage ? 'selected' : '' }}>
                                    {{ $optPerPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rs-records-count">
                        Hiển thị {{ $baoCaoSanPham->firstItem() ?? 0 }} - {{ $baoCaoSanPham->lastItem() ?? 0 }} / {{ $baoCaoSanPham->total() }} sản phẩm
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($baoCaoSanPham->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $baoCaoSanPham->previousPageUrl() }}" rel="prev">&laquo;</a></li>
                        @endif

                        @php
                            $cur = $baoCaoSanPham->currentPage();
                            $last = $baoCaoSanPham->lastPage();
                            $start = max(1, $cur - 2);
                            $end = min($last, $cur + 2);
                        @endphp

                        @if($start > 1)
                            <li class="page-item"><a class="page-link" href="{{ $baoCaoSanPham->url(1) }}">1</a></li>
                            @if($start > 2)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif
                        @endif

                        @for ($page = $start; $page <= $end; $page++)
                            @if ($page == $cur)
                                <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $baoCaoSanPham->url($page) }}">{{ $page }}</a></li>
                            @endif
                        @endfor

                        @if($end < $last)
                            @if($end < $last - 1)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif
                            <li class="page-item"><a class="page-link" href="{{ $baoCaoSanPham->url($last) }}">{{ $last }}</a></li>
                        @endif

                        @if ($baoCaoSanPham->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $baoCaoSanPham->nextPageUrl() }}" rel="next">&raquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════ GHI CHÚ ═══════════════════════ --}}
    <div class="info-note mb-3">
        <i class="bi bi-info-circle-fill me-2 flex-shrink-0"></i>
        <div>
            <div>• <strong>Nhập trong kỳ:</strong> Tổng số lượng nhập từ các phiếu nhập đã xác nhận (CONFIRMED) trong khoảng thời gian.</div>
            <div>• <strong>Bán trong kỳ:</strong> Tổng số lượng bán lẻ từ các đơn hàng đã giao thành công (DELIVERED) trong khoảng thời gian.</div>
            <div>• <strong>Tiêu hao SX:</strong> Tổng số lượng nguyên vật liệu xuất kho thực tế để sản xuất bó hoa thành phẩm trong khoảng thời gian.</div>
            <div>• <strong>Hàng hỏng:</strong> Tổng số lượng hao hụt hư hại được ghi nhận trong khoảng thời gian.</div>
            <div>• <strong>Tồn hiện tại:</strong> Tồn kho vật lý thực tế hiện tại trong kho (On-Hand = Khả dụng + Đang giữ).</div>
            <div>• <strong>Doanh thu:</strong> Tổng giá trị bán theo giá snapshot lịch sử của các hóa đơn đã giao thành công (DELIVERED).</div>
            <div>• <strong>Tỷ lệ hao hụt:</strong> Tỷ lệ giữa hàng hỏng và tổng lượng hàng quản lý trong kỳ = [Hàng hỏng / (Tồn hiện tại + Đã bán + Tiêu hao SX + Hàng hỏng)] × 100%.</div>
        </div>
    </div>

    {{-- ═══════════════════════ ĐIỀU HƯỚNG PHIẾU NHẬP ═══════════════════════ --}}
    <div class="nav-phieu-nhap mb-4">
        <div class="nav-pn-left">
            <i class="bi bi-box-seam-fill me-2 flex-shrink-0" style="color:#28a745;font-size:1.3rem;"></i>
            <div>
                <div class="fw-semibold">Muốn xem chi tiết phiếu nhập, giá nhập và các thông tin nhập hàng?</div>
                <div class="text-muted small mt-1">Vào Quản lý phiếu nhập để xem đầy đủ thông tin.</div>
            </div>
        </div>

        <a href="{{ route('phieu-nhap.index') }}" class="btn btn-nav-pn" target="_blank">
            <i class="bi bi-box-arrow-up-right me-1"></i> Đến Quản lý phiếu nhập
        </a>
    </div>

    {{-- ═══════════════════════ BIỂU ĐỒ TOP 5 SẢN PHẨM BÁN CHẠY ═══════════════════════ --}}
    <div class="chart-card mb-4">
        <div class="chart-title">
            <i class="bi bi-trophy-fill me-2" style="color:#e91e8c;"></i>
            BIỂU ĐỒ TOP 5 SẢN PHẨM BÁN CHẠY
        </div>

        @if($topSanPhamBanChay->count() > 0)
            <div class="chart-label-y">Số lượng đã bán</div>
            <canvas id="chartTop5" style="max-height:320px;"></canvas>
            <div class="chart-legend mt-3">
                <span class="legend-dot"></span> Số lượng đã bán
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="bi bi-bar-chart-line fs-2 d-block mb-2"></i>
                Chưa có dữ liệu bán hàng trong khoảng thời gian này.
            </div>
        @endif
    </div>

</div>{{-- end page-container --}}

{{-- ═══════════════════════ MODAL CHI TIẾT HÀNG HỎNG ═══════════════════════ --}}
<div class="modal fade" id="modalHangHong" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    CHI TIẾT HÀNG HỎNG
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="modalHangHongInfo" class="mb-3" style="display:none;">
                    <div class="modal-sp-info">
                        <span>Sản phẩm: <strong id="modalTenSP"></strong></span>
                        <span class="ms-3">
                            Tổng số lượng hỏng:
                            <strong class="text-danger" id="modalTongSoLuong"></strong>
                        </span>
                    </div>
                </div>

                <div id="modalHangHongBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-danger" role="status"></div>
                        <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Đóng
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════ MODAL CHI TIẾT SẢN PHẨM TỔNG HỢP ═══════════════════════ --}}
<!-- <div class="modal fade" id="modalChiTietSanPham" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">
                    <i class="bi bi-box-seam-fill me-2"></i>
                    CHI TIẾT SẢN PHẨM
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="chiTietSanPhamBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-danger" role="status"></div>
                        <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div> -->


<div id="drawerChiTietSanPham" class="drawer-chi-tiet">

    <div class="drawer-overlay"></div>

    <div class="drawer-content">

        <div class="drawer-header">

            <h5 class="mb-0">
                <i class="bi bi-box-seam-fill me-2"></i>
                Chi tiết sản phẩm
            </h5>

            <button type="button" class="drawer-close">
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <div class="drawer-body" id="chiTietSanPhamBody">

            <div class="text-center py-5">
                <div class="spinner-border text-danger"></div>
                <div class="mt-2 text-muted">
                    Đang tải dữ liệu...
                </div>
            </div>

        </div>

    </div>

</div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── 1. Biểu đồ Top 5 sản phẩm bán chạy ───────────────────────────────────
@if($topSanPhamBanChay->count() > 0)
(function () {
    const labels = @json($topSanPhamBanChay->pluck('ten_san_pham'));
    const data   = @json($topSanPhamBanChay->pluck('tong_da_ban'));

    new Chart(document.getElementById('chartTop5'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Số lượng đã bán',
                data,
                backgroundColor: '#e91e8c',
                borderRadius: 6,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y.toLocaleString('vi-VN') + ' sản phẩm'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { callback: v => v.toLocaleString('vi-VN') }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
})();
@endif

// ── 2. Helpers ───────────────────────────────────────────────────────────
function formatMoney(value) {
    value = Number(value || 0);
    return value.toLocaleString('vi-VN') + ' đ';
}

function formatNumber(value) {
    value = Number(value || 0);
    return value.toLocaleString('vi-VN');
}

function formatDateVN(value) {
    if (!value) return '-';

    const d = new Date(value);

    if (isNaN(d.getTime())) {
        return value;
    }

    return d.toLocaleDateString('vi-VN');
}

// ── 3. Modal hàng hỏng ───────────────────────────────────────────────────
function moModalHangHong(id, ten, batDau, ketThuc) {
    document.getElementById('modalTenSP').textContent = ten;
    document.getElementById('modalTongSoLuong').textContent = '...';
    document.getElementById('modalHangHongInfo').style.display = 'none';
    document.getElementById('modalHangHongBody').innerHTML =
        '<div class="text-center py-4">' +
        '<div class="spinner-border text-danger" role="status"></div>' +
        '<div class="mt-2 text-muted">Đang tải dữ liệu...</div></div>';

    const modal = new bootstrap.Modal(document.getElementById('modalHangHong'));
    modal.show();

    const url = `/bao-cao/san-pham/${id}/hang-hong?ngay_bat_dau=${batDau}&ngay_ket_thuc=${ketThuc}`;

    fetch(url, {
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Không tải được dữ liệu hàng hỏng');
            }
            return response.json();
        })
        .then(function (res) {
            document.getElementById('modalTenSP').textContent = res.ten_san_pham;
            document.getElementById('modalTongSoLuong').textContent = formatNumber(res.tong_so_luong_hong);
            document.getElementById('modalHangHongInfo').style.display = 'block';

            if (!res.data || res.data.length === 0) {
                document.getElementById('modalHangHongBody').innerHTML =
                    '<p class="text-muted text-center py-3"><i class="bi bi-inbox me-1"></i>Không có dữ liệu hàng hỏng trong kỳ từ ' + formatDateVN(batDau) + ' đến ' + formatDateVN(ketThuc) + '.</p>';
                return;
            }

            let rows = '';
            res.data.forEach(function (item) {
                rows += `
                    <tr>
                        <td>${item.ngay_ghi_nhan}</td>
                        <td class="text-end text-danger fw-semibold">${formatNumber(item.so_luong_hong)}</td>
                        <td>${item.ly_do_hong}</td>
                        <td>${item.nhan_vien_ghi_nhan}</td>
                        <td>${item.ma_phieu_nhap ? '#' + item.ma_phieu_nhap + ' (' + item.ngay_nhap_lo + ')' : '-'}</td>
                    </tr>
                `;
            });

            document.getElementById('modalHangHongBody').innerHTML = `
                <div class="mb-2 text-muted small">
                    <i class="bi bi-calendar3 me-1"></i> Khoảng thời gian: <strong>${formatDateVN(batDau)}</strong> đến <strong>${formatDateVN(ketThuc)}</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ngày ghi nhận</th>
                                <th class="text-end">Số lượng hỏng</th>
                                <th>Lý do hỏng</th>
                                <th>Nhân viên ghi nhận</th>
                                <th>Lô nhập liên quan</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `;
        })
        .catch(function () {
            document.getElementById('modalHangHongBody').innerHTML =
                '<p class="text-danger text-center py-3"><i class="bi bi-exclamation-circle me-1"></i>Lỗi khi tải dữ liệu. Vui lòng thử lại.</p>';
        });
}

// ── 4. Drawer chi tiết sản phẩm tổng hợp ──────────────────────────────────
function moModalChiTietSanPham(id, batDau, ketThuc) {
    const drawer = document.getElementById('drawerChiTietSanPham');
    const bodyEl = document.getElementById('chiTietSanPhamBody');

    drawer.classList.add('active');

    bodyEl.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-danger"></div>
            <div class="mt-2 text-muted">Đang tải dữ liệu chi tiết...</div>
        </div>
    `;

    const url = `/bao-cao/san-pham/${id}/chi-tiet?ngay_bat_dau=${batDau}&ngay_ket_thuc=${ketThuc}`;

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(async function (response) {
        if (!response.ok) {
            throw new Error('API lỗi ' + response.status);
        }
        return response.json();
    })
    .then(function (res) {
        renderChiTietSanPham(res, batDau, ketThuc);
    })
    .catch(function (error) {
        console.error('Lỗi chi tiết sản phẩm:', error);
        bodyEl.innerHTML = `
            <div class="alert alert-danger my-4">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Không thể tải dữ liệu chi tiết sản phẩm. Vui lòng thử lại.
            </div>
        `;
    });
}

function renderChiTietSanPham(res, batDau, ketThuc) {
    const bodyEl = document.getElementById('chiTietSanPhamBody');

    const sp = res.san_pham || {};
    const tq = res.tong_quan || {};
    const dsBan = res.lich_su_gia_ban || [];
    const dsNhap = res.lich_su_nhap || [];
    const dsSX = res.lich_su_san_xuat || [];

    let rowsGiaBan = '';
    if (dsBan.length > 0) {
        dsBan.forEach(function (item) {
            rowsGiaBan += `
                <tr>
                    <td class="fw-semibold">#${item.ma_hoa_don || '-'}</td>
                    <td>${formatDateVN(item.ngay_giao)}</td>
                    <td class="text-end fw-semibold text-purple">${formatNumber(item.so_luong)}</td>
                    <td class="text-end">${formatMoney(item.gia_ban_snapshot)}</td>
                    <td class="text-end fw-bold">${formatMoney(item.thanh_tien || (item.so_luong * item.gia_ban_snapshot))}</td>
                </tr>
            `;
        });
    } else {
        rowsGiaBan = `<tr><td colspan="5" class="text-center text-muted py-3">Chưa có dữ liệu bán hàng DELIVERED trong khoảng này.</td></tr>`;
    }

    let rowsNhap = '';
    if (dsNhap.length > 0) {
        dsNhap.forEach(function (item) {
            rowsNhap += `
                <tr>
                    <td class="fw-semibold">#PN-${item.ma_phieu_nhap || '-'}</td>
                    <td>${formatDateVN(item.ngay_nhap)}</td>
                    <td class="text-end fw-semibold text-success">${formatNumber(item.so_luong)}</td>
                    <td class="text-end">${formatMoney(item.gia_nhap)}</td>
                    <td class="text-end">${formatMoney(item.gia_ban)}</td>
                </tr>
            `;
        });
    } else {
        rowsNhap = `<tr><td colspan="5" class="text-center text-muted py-3">Chưa có dữ liệu nhập hàng trong khoảng này.</td></tr>`;
    }

    let rowsSX = '';
    if (dsSX.length > 0) {
        dsSX.forEach(function (item) {
            rowsSX += `
                <tr>
                    <td class="fw-semibold">#BH-${item.ma_bo_hoa || '-'}</td>
                    <td>${item.ten_bo_hoa || '-'}</td>
                    <td>${item.loai_bo_hoa === 'HE_THONG' ? '<span class="badge bg-primary">Bó mẫu sẵn</span>' : '<span class="badge bg-info text-dark">Bó theo đơn</span>'}</td>
                    <td>${formatDateVN(item.created_at)}</td>
                    <td>${item.vai_tro || '-'}</td>
                    <td class="text-end fw-semibold text-primary">${formatNumber(item.so_luong)}</td>
                </tr>
            `;
        });
    } else {
        rowsSX = `<tr><td colspan="6" class="text-center text-muted py-3">Chưa có tiêu hao sản xuất bó hoa trong khoảng này.</td></tr>`;
    }

    bodyEl.innerHTML = `
        <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1" style="color:#e91e8c;">${sp.ten_san_pham || '-'}</h4>
                <div class="text-muted small">
                    Mã sản phẩm: <strong>#${sp.ma_san_pham}</strong> &bull;
                    Loại: <strong>${sp.loai_san_pham || '-'}</strong>
                </div>
            </div>
            <div>
                <span class="badge ${sp.trang_thai === 'DANG_BAN' ? 'badge-dang-ban' : 'badge-ngung-ban'} px-3 py-2">
                    ${sp.trang_thai === 'DANG_BAN' ? 'Đang bán' : 'Ngừng bán'}
                </span>
            </div>
        </div>

        <div class="mb-3 text-muted small">
            <i class="bi bi-clock-history me-1"></i> Dữ liệu thống kê từ <strong>${formatDateVN(batDau)}</strong> đến <strong>${formatDateVN(ketThuc)}</strong>
        </div>

        {{-- 6 KPI cards --}}
        <div class="row g-2 mb-4">
            <div class="col-6 col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="text-muted small">Tồn kho vật lý (On-Hand)</div>
                    <div class="fs-4 fw-bold text-warning">${formatNumber(tq.ton_kho_hien_tai)}</div>
                    <div class="text-muted" style="font-size:0.75rem;">Khả dụng: <strong>${formatNumber(tq.ton_kha_dung)}</strong> | Giữ: <strong>${formatNumber(tq.dang_giu)}</strong></div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="text-muted small">Nhập trong kỳ</div>
                    <div class="fs-4 fw-bold text-success">${formatNumber(tq.nhap_trong_ky)}</div>
                    <div class="text-muted" style="font-size:0.75rem;">Phiếu nhập CONFIRMED</div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="text-muted small">Đã bán lẻ trong kỳ</div>
                    <div class="fs-4 fw-bold text-purple">${formatNumber(tq.tong_da_ban)}</div>
                    <div class="text-muted" style="font-size:0.75rem;">Đơn DELIVERED</div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="text-muted small">Tiêu hao sản xuất</div>
                    <div class="fs-4 fw-bold" style="color:#0284c7;">${formatNumber(tq.tieu_hao_san_xuat)}</div>
                    <div class="text-muted" style="font-size:0.75rem;">Cắm bó hoa thành phẩm</div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="text-muted small">Hàng hỏng trong kỳ</div>
                    <div class="fs-4 fw-bold text-danger">${formatNumber(tq.tong_hang_hong)}</div>
                    <div class="text-muted" style="font-size:0.75rem;">Hao hụt ghi nhận</div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="text-muted small">Doanh thu bán lẻ</div>
                    <div class="fs-4 fw-bold text-dark">${formatMoney(tq.tong_doanh_thu)}</div>
                    <div class="text-muted" style="font-size:0.75rem;">Giá snapshot đơn giao</div>
                </div>
            </div>
        </div>

        {{-- Nav tabs --}}
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGiaBan" type="button">
                    <i class="bi bi-cart-check me-1"></i> Lịch sử bán hàng (${dsBan.length})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNhapHang" type="button">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Lịch sử nhập (${dsNhap.length})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSanXuat" type="button">
                    <i class="bi bi-flower1 me-1"></i> Tiêu hao bó hoa (${dsSX.length})
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tabGiaBan">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày giao</th>
                                <th class="text-end">Số lượng</th>
                                <th class="text-end">Giá snapshot</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>${rowsGiaBan}</tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tabNhapHang">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Phiếu nhập</th>
                                <th>Ngày nhập</th>
                                <th class="text-end">Số lượng</th>
                                <th class="text-end">Giá nhập</th>
                                <th class="text-end">Giá bán đề xuất</th>
                            </tr>
                        </thead>
                        <tbody>${rowsNhap}</tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tabSanXuat">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã bó</th>
                                <th>Tên bó hoa</th>
                                <th>Loại bó</th>
                                <th>Ngày sản xuất</th>
                                <th>Vai trò</th>
                                <th class="text-end">Số lượng tiêu hao</th>
                            </tr>
                        </thead>
                        <tbody>${rowsSX}</tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

// ── 5. Gắn sự kiện ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.link-chitiet').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            moModalHangHong(
                this.dataset.id,
                this.dataset.ten,
                this.dataset.batdau,
                this.dataset.ketthuc
            );
        });
    });

    document.querySelectorAll('.btn-chi-tiet-san-pham').forEach(function (btn) {
        btn.addEventListener('click', function () {
            moModalChiTietSanPham(
                this.dataset.id,
                this.dataset.batdau,
                this.dataset.ketthuc
            );
        });
    });
});
document.querySelector('.drawer-close')
    .addEventListener('click', closeDrawer);

document.querySelector('.drawer-overlay')
    .addEventListener('click', closeDrawer);

function closeDrawer() {
    document
        .getElementById('drawerChiTietSanPham')
        .classList.remove('active');
}
</script>
@endpush
