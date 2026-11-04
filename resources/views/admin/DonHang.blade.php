@extends('layouts.admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/Admin/DonHang.css') }}">

@endpush

@section('content')

<div class="rs-management-page">

    {{-- Page Header --}}
    <div class="rs-header-block">
        <div>
            <h1 class="rs-header-title">Quản lý đơn hàng</h1>
            <p class="rs-header-subtitle">Theo dõi và xử lý toàn bộ vòng đời đơn hàng</p>
        </div>
    </div>

    {{-- ════════ TAB BAR (Vòng đời đơn hàng) ════════ --}}
    <div class="rs-tab-bar">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'all' ? 'active' : '' }}">
            <i class="bi bi-collection"></i>
            Tất cả
            <span class="rs-tab-count">{{ $tabCounts['all'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'pending', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'pending' ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i>
            Chờ duyệt
            <span class="rs-tab-count">{{ $tabCounts['pending'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'confirmed', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'confirmed' ? 'active' : '' }}">
            <i class="bi bi-check2-circle"></i>
            Đã xác nhận
            <span class="rs-tab-count">{{ $tabCounts['confirmed'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'shipping', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'shipping' ? 'active' : '' }}">
            <i class="bi bi-truck"></i>
            Đang giao
            <span class="rs-tab-count">{{ $tabCounts['shipping'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'delivered', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'delivered' ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i>
            Đã giao
            <span class="rs-tab-count">{{ $tabCounts['delivered'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'failed', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'failed' ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle"></i>
            Giao thất bại
            <span class="rs-tab-count rs-tab-count-danger">{{ $tabCounts['failed'] }}</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'cancelled', 'page' => 1]) }}"
           class="rs-tab-item {{ $tab === 'cancelled' ? 'active' : '' }}">
            <i class="bi bi-x-circle"></i>
            Đã hủy
            <span class="rs-tab-count">{{ $tabCounts['cancelled'] }}</span>
        </a>
    </div>

    {{-- Main Unified Card --}}
    <div class="rs-main-card">

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('don-hang.index') }}" class="rs-filter-bar">
            {{-- Giữ tab hiện tại --}}
            <input type="hidden" name="tab" value="{{ $tab }}">

            {{-- Tìm kiếm chung --}}
            <div class="rs-filter-group rs-filter-group-lg">
                <label>Tìm kiếm</label>
                <input type="text" name="q"
                       placeholder="Mã đơn (#HD-...), tên khách, SĐT..."
                       value="{{ request('q') }}">
            </div>

            {{-- Trạng thái thanh toán --}}
            <div class="rs-filter-group">
                <label>Thanh Toán</label>
                <select name="trang_thai_thanh_toan">
                    <option value="">— Tất cả —</option>
                    <option value="CHUA_THANH_TOAN" {{ request('trang_thai_thanh_toan') === 'CHUA_THANH_TOAN' ? 'selected' : '' }}>
                        Chưa thanh toán
                    </option>
                    <option value="DA_THANH_TOAN" {{ request('trang_thai_thanh_toan') === 'DA_THANH_TOAN' ? 'selected' : '' }}>
                        Đã thanh toán
                    </option>
                </select>
            </div>

            {{-- Phương thức thanh toán --}}
            <div class="rs-filter-group">
                <label>Hình thức</label>
                <select name="phuong_thuc_thanh_toan">
                    <option value="">— Tất cả —</option>
                    <option value="COD" {{ request('phuong_thuc_thanh_toan') === 'COD' ? 'selected' : '' }}>COD (Tiền mặt)</option>
                    <option value="VNPAY" {{ request('phuong_thuc_thanh_toan') === 'VNPAY' ? 'selected' : '' }}>VNPay</option>
                </select>
            </div>

            {{-- Từ ngày --}}
            <div class="rs-filter-group">
                <label>Từ ngày</label>
                <input type="date" name="tu_ngay" value="{{ request('tu_ngay') }}">
            </div>

            {{-- Đến ngày --}}
            <div class="rs-filter-group">
                <label>Đến ngày</label>
                <input type="date" name="den_ngay" value="{{ request('den_ngay') }}">
            </div>

            {{-- Nút lọc & Bỏ lọc --}}
            <div class="rs-filter-btn-group">
                <button type="submit" class="rs-btn-filter-submit">
                    <i class="bi bi-search"></i>
                    <span>Lọc</span>
                </button>

                @if(request()->anyFilled(['q', 'trang_thai_thanh_toan', 'phuong_thuc_thanh_toan', 'tu_tien', 'den_tien', 'tu_ngay', 'den_ngay']))
                    <a href="{{ route('don-hang.index', ['tab' => $tab]) }}" class="rs-btn-filter-reset" title="Bỏ lọc">
                        <i class="bi bi-x-lg"></i>
                        <span>Bỏ lọc</span>
                    </a>
                @endif
            </div>
        </form>

        {{-- Cảnh báo shipper khi ở tab Chờ duyệt --}}
        @if($tab === 'pending' && $shippers->isEmpty())
            <div style="padding:12px 20px;background:#FFFAEB;border:1px solid #FEF08A;border-radius:8px;margin:16px 20px 0 20px;color:#B54708;font-size:13.5px;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Không có shipper nào đang ONLINE hoặc tất cả đã đủ 10/10 đơn. Vui lòng chờ shipper bật trạng thái hoặc giao bớt đơn.</span>
            </div>
        @endif

        {{-- Card Title Bar --}}
        <div class="rs-card-title-bar">
            <div class="rs-card-title">
                <i class="bi bi-list-ul"></i>
                <span>
                    @switch($tab)
                        @case('pending') Danh sách đơn hàng chờ duyệt @break
                        @case('confirmed') Danh sách đơn hàng đã xác nhận @break
                        @case('shipping') Danh sách đơn hàng đang giao @break
                        @case('delivered') Danh sách đơn hàng đã giao thành công @break
                        @case('failed') Danh sách đơn hàng giao thất bại & hoàn hàng @break
                        @case('cancelled') Danh sách đơn hàng đã hủy @break
                        @default Danh sách tất cả đơn hàng
                    @endswitch
                </span>
                <span class="rs-tab-count">{{ $donHangs->total() }}</span>
            </div>
            <div class="text-muted" style="font-size: 13px;">
                Trang {{ $donHangs->currentPage() }} / {{ $donHangs->lastPage() }}
            </div>
        </div>

        {{-- Table Container --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Ngày Đặt</th>
                        <th>Khách Hàng</th>
                        <th>Tổng Tiền</th>
                        <th>Thanh Toán</th>
                        <th>Shipper</th>

                        @if($tab === 'shipping')
                            <th>Bắt Đầu Giao</th>
                        @elseif($tab === 'failed')
                            <th>Nguồn Lỗi</th>
                            <th>Lý Do Thất Bại</th>
                            <th>Hàng Hoàn</th>
                        @elseif($tab === 'cancelled')
                            <th>Thời Điểm Hủy</th>
                            <th>Lý Do Hủy</th>
                        @endif

                        <th>Trạng Thái</th>
                        <th style="text-align: center;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($donHangs as $dh)
                    @php
                        $isFailedOrder = ($dh->co_hang_hoan == 1 || $dh->giaoHangThatBai->isNotEmpty());
                        $failRecord    = $dh->giaoHangThatBai->first();
                        $hangHoan      = $dh->hangHoan;
                    @endphp
                    <tr id="row-{{ $dh->ma_hoa_don }}">
                        {{-- Mã ĐH & Tóm tắt hàng --}}
                        <td style="white-space: nowrap;">
                            <span class="rs-code-order">#HD-{{ str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</span>
                            @foreach($boTheoDon->get($dh->ma_hoa_don, collect()) as $bo)
                                <div style="font-size: 11px; color: #667085; white-space: nowrap;">{{ $bo->ten_bo_hoa }} × {{ $bo->so_luong }} · {{ $bo->size }}</div>
                            @endforeach
                            @foreach($dh->chiTietHoaDon->take(2) as $ct)
                                @if($ct->sanPham)
                                    <div style="font-size: 11px; color: #667085; white-space: nowrap;">{{ $ct->sanPham->ten_san_pham }} × {{ $ct->so_luong }}</div>
                                @endif
                            @endforeach
                        </td>

                        {{-- Ngày đặt --}}
                        <td style="white-space: nowrap; font-size: 12.5px; color: #667085;">
                            {{ $dh->ngay_dat ? $dh->ngay_dat->format('d/m/Y H:i') : '—' }}
                        </td>

                        {{-- Khách hàng --}}
                        <td style="white-space: nowrap;">
                            <div style="font-weight: 500; color: #101828;">{{ $dh->khachHang?->ten_khach_hang ?? 'Khách vãng lai' }}</div>
                            <div style="font-size: 12px; color: #667085; font-family: monospace;">{{ $dh->so_dien_thoai ?? $dh->khachHang?->so_dien_thoai ?? '—' }}</div>
                        </td>

                        {{-- Tổng tiền --}}
                        <td style="white-space: nowrap; font-weight: 700; color: #101828;">
                            {{ number_format($dh->tong_tien, 0, ',', '.') }}₫
                        </td>

                        {{-- Thanh toán --}}
                        <td style="white-space: nowrap;">
                            <div style="font-size: 12px; font-weight: 500; color: #475467; margin-bottom: 2px;">
                                {{ $dh->phuong_thuc_thanh_toan ?? 'COD' }}
                            </div>
                            <span class="{{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'rs-badge-paid' : 'rs-badge-unpaid' }}">
                                <i class="bi {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'bi-check-circle' : 'bi-clock' }}"></i>
                                {{ $dh->trang_thai_thanh_toan === 'DA_THANH_TOAN' ? 'Đã TT' : 'Chưa TT' }}
                            </span>
                        </td>

                        {{-- Shipper / Chọn shipper --}}
                        <td style="white-space: nowrap;">
                            @if($dh->trang_thai === 'PENDING')
                                <select class="shipper-select" id="shipper-{{ $dh->ma_hoa_don }}" data-id="{{ $dh->ma_hoa_don }}">
                                    <option value="">— Chọn shipper —</option>
                                    @foreach($shippers as $sp)
                                        <option value="{{ $sp->ma_nhan_vien }}" data-ten="{{ $sp->ten_nhan_vien }}">
                                            {{ $sp->ten_nhan_vien }} ({{ $sp->so_don_live }}/10)
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div style="font-weight: 500; color: #344054;">
                                    {{ $dh->shipper?->ten_nhan_vien ?? ($failRecord?->shipper?->ten_nhan_vien ?? 'Chưa phân công') }}
                                </div>
                                @if($dh->shipper?->so_dien_thoai)
                                    <div style="font-size: 11.5px; color: #667085; font-family: monospace;">{{ $dh->shipper->so_dien_thoai }}</div>
                                @endif
                            @endif
                        </td>

                        {{-- Cột bổ sung theo Tab --}}
                        @if($tab === 'shipping')
                            <td style="white-space: nowrap; font-size: 12.5px; color: #667085;">
                                {{ $dh->ngay_bat_giao ? $dh->ngay_bat_giao->format('d/m/Y H:i') : 'Đang xử lý lấy hàng' }}
                            </td>
                        @elseif($tab === 'failed')
                            {{-- Nguồn lỗi --}}
                            <td style="white-space: nowrap;">
                                @if($failRecord && $failRecord->loai_loi === 'CUSTOMER')
                                    <span class="rs-badge rs-badge-failed">
                                        <i class="bi bi-person-x"></i> Khách hàng
                                    </span>
                                @elseif($failRecord && $failRecord->loai_loi === 'SHIPPER')
                                    <span class="rs-badge rs-badge-failed">
                                        <i class="bi bi-truck"></i> Shipper
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Lý do thất bại --}}
                            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $failRecord?->ly_do }}">
                                {{ $failRecord?->ly_do ?? '—' }}
                            </td>

                            {{-- Trạng thái Hàng hoàn --}}
                            <td style="white-space: nowrap;">
                                @if($hangHoan)
                                    @switch($hangHoan->trang_thai)
                                        @case('CHO_MANG_VE_KHO')
                                            <span class="rs-badge rs-badge-return-waiting">Chờ mang về kho</span>
                                            @break
                                        @case('DA_NHAN_TAI_KHO')
                                            <span class="rs-badge rs-badge-return-received">Đã nhận tại kho</span>
                                            @break
                                        @case('DANG_KIEM_TRA')
                                            <span class="rs-badge rs-badge-return-checking">Đang kiểm tra</span>
                                            @break
                                        @case('DA_KIEM_TRA')
                                            <span class="rs-badge rs-badge-return-done">Đã kiểm tra</span>
                                            @break
                                        @default
                                            <span class="rs-badge rs-badge-cancelled">{{ $hangHoan->trang_thai }}</span>
                                    @endswitch
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @elseif($tab === 'cancelled')
                            <td style="white-space: nowrap; font-size: 12.5px; color: #667085;">
                                {{ $dh->thoi_diem_huy ? $dh->thoi_diem_huy->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $dh->ly_do_huy }}">
                                {{ $dh->ly_do_huy ?? '—' }}
                            </td>
                        @endif

                        {{-- Trạng thái --}}
                        <td style="white-space: nowrap;">
                            @if($isFailedOrder)
                                <span class="rs-badge rs-badge-failed">
                                    <i class="bi bi-x-octagon"></i> Giao thất bại
                                </span>
                            @else
                                @switch($dh->trang_thai)
                                    @case('PENDING')
                                        <span class="rs-badge rs-badge-pending">
                                            <i class="bi bi-clock"></i> Chờ duyệt
                                        </span>
                                        @break
                                    @case('CONFIRMED')
                                        <span class="rs-badge rs-badge-confirmed">
                                            <i class="bi bi-check2"></i> Đã xác nhận
                                        </span>
                                        @break
                                    @case('SHIPPING')
                                        <span class="rs-badge rs-badge-shipping">
                                            <i class="bi bi-truck"></i> Đang giao
                                        </span>
                                        @break
                                    @case('DELIVERED')
                                        <span class="rs-badge rs-badge-delivered">
                                            <i class="bi bi-check-all"></i> Đã giao
                                        </span>
                                        @break
                                    @case('CANCELLED')
                                        <span class="rs-badge rs-badge-cancelled">
                                            <i class="bi bi-slash-circle"></i> Đã hủy
                                        </span>
                                        @break
                                    @default
                                        <span class="rs-badge rs-badge-cancelled">{{ $dh->trang_thai }}</span>
                                @endswitch
                            @endif
                        </td>

                        {{-- Thao tác --}}
                        <td style="white-space: nowrap; text-align: center;">
                            @if($dh->trang_thai === 'PENDING')
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                    <button class="rs-order-btn btn-success"
                                            onclick="confirmOrder({{ $dh->ma_hoa_don }})"
                                            title="Xác nhận đơn hàng (Yêu cầu chọn shipper)">
                                        <i class="bi bi-check-lg" style="font-size: 16px; line-height: 1;"></i>
                                    </button>
                                    <button class="rs-order-btn btn-danger"
                                            onclick="openCancelDialog({{ $dh->ma_hoa_don }}, 'HD-{{ str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}')"
                                            title="Từ chối đơn hàng">
                                        <i class="bi bi-x-lg" style="font-size: 13px; line-height: 1;"></i>
                                    </button>
                                </div>
                            @elseif($isFailedOrder)
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    @if($hangHoan && in_array($hangHoan->trang_thai, ['CHO_MANG_VE_KHO', 'DA_NHAN_TAI_KHO'], true))
                                        <a href="{{ route('admin.hoa-don.kiem-tra-hang', $dh->ma_hoa_don) }}"
                                           onclick="return openKiemTraModal({{ $dh->ma_hoa_don }}, event)"
                                           class="rs-btn-action rs-btn-action-primary"
                                           title="Kiểm tra chất lượng hàng hoàn">
                                            <i class="bi bi-clipboard-check"></i> Kiểm tra hàng
                                        </a>
                                    @elseif($hangHoan && $hangHoan->trang_thai === 'DA_KIEM_TRA')
                                        <button type="button"
                                                onclick="openFailedDetail({{ $dh->ma_hoa_don }})"
                                                class="rs-btn-action rs-btn-action-info"
                                                title="Xem kết quả kiểm tra chất lượng">
                                            <i class="bi bi-eye"></i> Xem kết quả
                                        </button>
                                    @endif

                                    <button type="button"
                                            onclick="openFailedDetail({{ $dh->ma_hoa_don }})"
                                            class="rs-btn-action rs-btn-action-ghost"
                                            title="Chi tiết xử lý giao thất bại">
                                        <i class="bi bi-info-circle"></i> Chi tiết
                                    </button>
                                </div>
                            @else
                                <a href="{{ route('hoa-don.show', $dh->ma_hoa_don) }}"
                                   onclick="return openOrderDetail({{ $dh->ma_hoa_don }}, event)"
                                   class="rs-btn-action rs-btn-action-ghost"
                                   title="Xem chi tiết hóa đơn">
                                    <i class="bi bi-eye"></i> Chi tiết
                                </a>
                            @endif

                            {{-- Embed JSON data for failed delivery modal --}}
                            @if($isFailedOrder)
                                <script type="application/json" id="failed-data-{{ $dh->ma_hoa_don }}">
                                {!! json_encode([
                                    'ma_hoa_don'     => $dh->ma_hoa_don,
                                    'ma_hd_code'     => '#HD-'.str_pad($dh->ma_hoa_don, 4, '0', STR_PAD_LEFT),
                                    'tong_tien'      => number_format($dh->tong_tien, 0, ',', '.').'₫',
                                    'shipper_ten'    => $failRecord?->shipper?->ten_nhan_vien ?? ($dh->shipper?->ten_nhan_vien ?? '—'),
                                    'shipper_sdt'    => $failRecord?->shipper?->so_dien_thoai ?? ($dh->shipper?->so_dien_thoai ?? '—'),
                                    'thoi_diem_bao'  => $failRecord?->thoi_diem_bao ? $failRecord->thoi_diem_bao->format('d/m/Y H:i') : '—',
                                    'loai_loi'       => $failRecord?->loai_loi ?? '—',
                                    'ly_do'          => $failRecord?->ly_do ?? '—',
                                    'ghi_chu'        => $failRecord?->ghi_chu ?? '',
                                    'khach_hang_ten' => $dh->khachHang?->ten_khach_hang ?? '—',
                                    'khach_hang_sdt' => $dh->so_dien_thoai ?? ($dh->khachHang?->so_dien_thoai ?? '—'),
                                    'quyen_cod'      => $dh->khachHang?->quyen_cod === 'GIOI_HAN' ? 'Đang khóa / Giới hạn' : 'Bình thường',
                                    'vi_pham_count'  => $dh->khachHang ? $dh->khachHang->viPhamGiaoHang->count() : 0,
                                    'is_admin'       => (Auth::check() && Auth::user()->vai_tro === 'ADMIN'),
                                    'phat_shipper'   => $dh->phatShipper->map(fn($p) => [
                                        'so_tien'                 => number_format($p->so_tien, 0, ',', '.').'₫',
                                        'tien_hang_hong'          => number_format($p->tien_hang_hong, 0, ',', '.').'₫',
                                        'tien_hao_hut_flash_sale' => number_format($p->tien_hao_hut_flash_sale, 0, ',', '.').'₫',
                                        'ly_do'                   => $p->ly_do,
                                        'trang_thai'              => $p->trang_thai,
                                    ]),
                                    'hang_hoan_trang_thai' => $hangHoan?->trang_thai ?? 'CHUA_TAO',
                                    'ngay_nhan_kho'        => $hangHoan?->thoi_diem_nhan_tai_kho ? $hangHoan->thoi_diem_nhan_tai_kho->format('d/m/Y H:i') : null,
                                    'ngay_kiem_tra'        => $hangHoan?->thoi_diem_kiem_tra ? $hangHoan->thoi_diem_kiem_tra->format('d/m/Y H:i') : null,
                                    'nguoi_kiem_tra'       => $hangHoan?->nguoiKiemTra?->ho_ten ?? ($hangHoan?->nguoiKiemTra?->ten_dang_nhap ?? '—'),
                                    'kiem_tra_url'         => route('admin.hoa-don.kiem-tra-hang', $dh->ma_hoa_don),
                                    'chi_tiet_hang_hoan'   => $hangHoan ? $hangHoan->chiTiet->map(fn($ct) => [
                                        'ten'                   => $ct->boHoa?->ten_bo_hoa ?? ($ct->sanPham?->ten_san_pham ?? 'Sản phẩm'),
                                        'so_luong_theo_don'     => $ct->so_luong_theo_don,
                                        'so_luong_con_ban_duoc' => $ct->so_luong_con_ban_duoc,
                                        'so_luong_hong'         => $ct->so_luong_hong,
                                        'ket_qua'               => $ct->ket_qua,
                                        'ghi_chu'               => $ct->ghi_chu,
                                    ]) : [],
                                ]) !!}
                                </script>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ in_array($tab, ['shipping', 'cancelled']) ? 9 : ($tab === 'failed' ? 10 : 8) }}">
                            <div class="empty-state" style="text-align: center; padding: 48px 24px; color: #667085;">
                                <div style="font-size: 32px; margin-bottom: 8px;">◻</div>
                                <p class="mb-0 fw-semibold">Không tìm thấy đơn hàng nào phù hợp với bộ lọc hiện tại.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang chuẩn RoseShop --}}
        @if($donHangs->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span>Số hàng mỗi trang:</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage, 'page' => 1]) }}"
                                        {{ (request('per_page', $donHangs->perPage()) == $perPage) ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rs-records-count">
                        {{ $donHangs->firstItem() ?? 0 }}-{{ $donHangs->lastItem() ?? 0 }} / {{ $donHangs->total() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($donHangs->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $donHangs->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $donHangs->currentPage();
                            $lastPage = $donHangs->lastPage();
                            $pages = [];

                            if ($lastPage <= 5) {
                                for ($i = 1; $i <= $lastPage; $i++) { $pages[] = $i; }
                            } else {
                                $pages[] = 1;
                                if ($currentPage > 3) { $pages[] = '...'; }
                                $start = max(2, $currentPage - 1);
                                $end   = min($lastPage - 1, $currentPage + 1);
                                for ($i = $start; $i <= $end; $i++) { $pages[] = $i; }
                                if ($currentPage < $lastPage - 2) { $pages[] = '...'; }
                                $pages[] = $lastPage;
                            }
                        @endphp

                        @foreach ($pages as $p)
                            @if ($p === '...')
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            @elseif ($p == $currentPage)
                                <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $donHangs->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($donHangs->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $donHangs->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

    </div>

    {{-- ════════ DIALOG XÁC NHẬN TỪ CHỐI ════════ --}}
    <div class="overlay" id="cancelOverlay">
        <div class="dialog">
            <h3>Xác nhận từ chối đơn hàng</h3>
            <p id="cancelMsg">Bạn có chắc muốn từ chối đơn hàng này?<br>
                Trạng thái sẽ chuyển sang <strong>CANCELLED</strong> và giải phóng hàng tồn đang giữ.<br>
                Hành động này không thể hoàn tác.
            </p>
            <div class="dialog-actions">
                <label style="font-size: 13px; font-weight: 500; color: #344054; margin-bottom: 6px;">
                    Lý do từ chối (không bắt buộc)
                </label>
                <textarea id="cancelReason" maxlength="1000" placeholder="Nhập lý do từ chối đơn..."></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                    <button class="btn btn-ghost" onclick="closeCancelDialog()" style="background:#F2F4F7; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:500; color:#344054; cursor:pointer;">Hủy bỏ</button>
                    <button class="btn btn-danger" id="cancelOkBtn" style="background:#B42318; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:600; color:#ffffff; cursor:pointer;">✕ Xác nhận từ chối</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ MODAL CHI TIẾT XỬ LÝ GIAO THẤT BẠI ════════ --}}
    <div class="overlay" id="failedModalOverlay">
        <div class="rs-drawer" id="failedModalDrawer">
            <div class="rs-drawer-header">
                <div class="rs-drawer-title">
                    <span id="fmOrderCode" style="color: #EC3D8F; font-family: monospace;">#HD-0000</span>
                    <span>— XỬ LÝ GIAO THẤT BẠI</span>
                </div>
                <button type="button" onclick="closeFailedDetail()" style="background: none; border: none; font-size: 20px; color: #667085; cursor: pointer;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- 1. TIMELINE --}}
            <div class="rs-section-title">
                <i class="bi bi-diagram-3"></i> VÒNG ĐỜI XỬ LÝ HÀNG HOÀN
            </div>
            <div class="rs-timeline" id="fmTimeline">
                {{-- Dynamic via JS --}}
            </div>

            {{-- 2. GIAO HÀNG & NGUỒN LỖI --}}
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div style="background: #F8F9FC; border: 1px solid #EAECF0; border-radius: 10px; padding: 14px;">
                        <div class="rs-section-title" style="margin-bottom: 8px;">
                            <i class="bi bi-truck"></i> GIAO HÀNG
                        </div>
                        <div style="font-size: 13px; line-height: 1.6;">
                            <div><strong>Shipper:</strong> <span id="fmShipper"></span></div>
                            <div><strong>Số điện thoại:</strong> <span id="fmShipperPhone"></span></div>
                            <div><strong>Thời gian thất bại:</strong> <span id="fmFailTime"></span></div>
                            <div><strong>Nguồn lỗi:</strong> <span id="fmSourceBadge"></span></div>
                            <div><strong>Lý do:</strong> <span id="fmReason" style="color: #B42318;"></span></div>
                            <div id="fmNoteRow" style="display: none;"><strong>Ghi chú:</strong> <span id="fmNote"></span></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div style="background: #F8F9FC; border: 1px solid #EAECF0; border-radius: 10px; padding: 14px;">
                        <div class="rs-section-title" style="margin-bottom: 8px;">
                            <i class="bi bi-shield-check"></i> TRÁCH NHIỆM & CHẾ TÀI
                        </div>
                        <div id="fmResponsibilityContent" style="font-size: 13px; line-height: 1.6;">
                            {{-- Dynamic --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. HÀNG HOÀN & KẾT QUẢ KIỂM TRA --}}
            <div style="border-top: 1px solid #EAECF0; padding-top: 16px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="rs-section-title mb-0">
                        <i class="bi bi-clipboard2-check"></i> KIỂM TRA CHẤT LƯỢNG HÀNG HOÀN
                    </div>
                    <div id="fmInspectActionSlot"></div>
                </div>
                <div id="fmInspectionInfo" style="font-size: 13px; margin-bottom: 12px; color: #475467;"></div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size: 12.5px; border-color: #EAECF0;">
                        <thead style="background-color: #F8F9FC;">
                            <tr>
                                <th>Mặt hàng</th>
                                <th style="text-align: center;">SL Đơn</th>
                                <th style="text-align: center; color: #027A48;">SL Còn tốt</th>
                                <th style="text-align: center; color: #B42318;">SL Hỏng</th>
                                <th>Kết quả / Hướng xử lý</th>
                            </tr>
                        </thead>
                        <tbody id="fmItemsTableBody">
                            {{-- Dynamic --}}
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeFailedDetail()" class="rs-btn-action rs-btn-action-ghost" style="padding: 8px 18px; font-size: 13px;">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    {{-- ════════ MODAL CHI TIẾT ĐƠN HÀNG ════════ --}}
    <div class="overlay" id="orderDetailOverlay" aria-hidden="true">
        <div class="rs-drawer" id="orderDetailDrawer" role="dialog" aria-modal="true" aria-labelledby="odTitle">
            <div class="rs-drawer-header">
                <div class="rs-drawer-title">
                    <i class="bi bi-receipt"></i>
                    <span id="odTitle">Chi tiết đơn hàng</span>
                </div>
                <button type="button" class="kth-close" onclick="closeOrderDetail()" aria-label="Đóng">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div id="orderDetailBody" class="order-detail-body">
                <div class="kth-loading">Đang tải chi tiết đơn hàng...</div>
            </div>
        </div>
    </div>

    {{-- ════════ MODAL KIỂM TRA HÀNG HOÀN ════════ --}}
    <div class="kth-overlay" id="kthOverlay">
        <div class="kth-modal" role="dialog" aria-modal="true" aria-labelledby="kthTitle">
            <div class="kth-header">
                <h3 class="kth-title" id="kthTitle"><i class="bi bi-clipboard-check"></i> Kiểm tra hàng hoàn</h3>
                <button type="button" class="kth-close" onclick="closeKiemTraModal()" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="kth-body" id="kthBody">
                <div class="kth-loading">Đang tải dữ liệu hàng hoàn...</div>
            </div>
            <div class="kth-footer">
                <button type="button" class="kth-btn" onclick="closeKiemTraModal()">Hủy</button>
                <button type="button" class="kth-btn primary" id="kthSaveBtn" onclick="saveKiemTra()" disabled>Lưu kết quả kiểm tra</button>
            </div>
        </div>
    </div>

    {{-- ════════ TOAST NOTIFICATION ════════ --}}
    <div class="toast" id="toast"></div>

</div>

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let currentCancelId = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className   = 'toast ' + type + ' show';
        clearTimeout(t._t);
        t._t = setTimeout(() => t.classList.remove('show'), 3500);
    }

    function removeRow(id) {
        const row = document.getElementById('row-' + id);
        if (!row) return;
        row.style.transition = 'opacity .35s, transform .35s';
        row.style.opacity    = '0';
        row.style.transform  = 'translateX(16px)';
        setTimeout(() => row.remove(), 370);
    }

    function updateShipperCount(maNv, soDon) {
        document.querySelectorAll('.shipper-select').forEach(sel => {
            const opt = sel.querySelector(`option[value="${maNv}"]`);
            if (!opt) return;
            if (soDon >= 10) {
                if (sel.value == maNv) sel.value = '';
                opt.remove();
            } else {
                opt.textContent = `${opt.dataset.ten} (${soDon}/10)`;
            }
        });
    }

    function confirmOrder(id) {
        const sel = document.getElementById('shipper-' + id);

        if (!sel || !sel.value) {
            sel && sel.classList.add('error');
            showToast('Vui lòng chọn shipper trước khi xác nhận!', 'error');
            sel && sel.focus();
            return;
        }
        sel.classList.remove('error');

        const btn = document.querySelector('#row-' + id + ' .btn-success');
        if (btn) { btn.disabled = true; btn.textContent = '…'; }

        fetch(`/don-hang/${id}/confirm`, {
            method:  'POST',
            headers: {
                'Content-Type':    'application/json',
                'X-CSRF-TOKEN':    CSRF,
                'Accept':          'application/json',
                'X-Requested-With':'XMLHttpRequest',
            },
            body: JSON.stringify({ ma_nhan_vien_giao: sel.value }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (res.shipper) updateShipperCount(res.shipper.ma_nhan_vien, res.shipper.so_don);
                removeRow(id);
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Có lỗi xảy ra.', 'error');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i>'; }
            }
        })
        .catch(() => {
            showToast('Lỗi kết nối, vui lòng thử lại.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i>'; }
        });
    }

    function openCancelDialog(id, label) {
        document.getElementById('cancelReason').value = '';
        currentCancelId = id;
        document.getElementById('cancelMsg').innerHTML =
            `Bạn có chắc muốn từ chối <strong>${label}</strong>?<br>
             Trạng thái sẽ chuyển sang <strong>CANCELLED</strong> và giải phóng hàng tồn đang giữ.<br>
             Hành động này không thể hoàn tác.`;
        document.getElementById('cancelOverlay').classList.add('show');
    }

    function closeCancelDialog() {
        document.getElementById('cancelOverlay').classList.remove('show');
        currentCancelId = null;
    }

    document.getElementById('cancelOkBtn').addEventListener('click', function () {
        if (!currentCancelId) return;
        const id  = currentCancelId;
        this.disabled    = true;
        this.textContent = '…';

        fetch(`/don-hang/${id}/cancel`, {
            method:  'POST',
            body: new URLSearchParams({ly_do: document.getElementById('cancelReason').value}),
            headers: {
                'X-CSRF-TOKEN':    CSRF,
                'Accept':          'application/json',
                'X-Requested-With':'XMLHttpRequest',
            },
        })
        .then(r => r.json())
        .then(res => {
            closeCancelDialog();
            this.disabled    = false;
            this.textContent = '✕ Xác nhận từ chối';

            if (res.success) {
                removeRow(id);
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Có lỗi xảy ra.', 'error');
            }
        })
        .catch(() => {
            closeCancelDialog();
            this.disabled    = false;
            this.textContent = '✕ Xác nhận từ chối';
            showToast('Lỗi kết nối, vui lòng thử lại.', 'error');
        });
    });

    document.getElementById('cancelOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeCancelDialog();
    });

    document.querySelectorAll('.shipper-select').forEach(sel => {
        sel.addEventListener('change', () => sel.classList.remove('error'));
    });

    // ─── ORDER DETAIL MODAL ───
    function openOrderDetail(orderId, event) {
        event?.preventDefault();
        const overlay = document.getElementById('orderDetailOverlay');
        const body = document.getElementById('orderDetailBody');
        if (!overlay || !body) return false;

        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        body.innerHTML = '<div class="kth-loading">Đang tải chi tiết đơn hàng...</div>';

        fetch(`/hoa-don/${orderId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(({ ok, j }) => {
                if (!ok || !j.success) throw new Error(j.message || 'Không tải được chi tiết đơn hàng.');
                renderOrderDetail(j.data || {});
            })
            .catch(err => {
                body.innerHTML = `<div class="order-detail-error"><i class="bi bi-exclamation-circle"></i>${kthEsc(err.message || 'Không tải được chi tiết đơn hàng.')}</div>`;
            });

        return false;
    }

    function closeOrderDetail() {
        const overlay = document.getElementById('orderDetailOverlay');
        if (overlay) {
            overlay.classList.remove('show');
            overlay.setAttribute('aria-hidden', 'true');
        }
        document.body.style.overflow = '';
    }

    function renderOrderDetail(d) {
        const esc = v => kthEsc(v || '—');
        const money = v => Number(v || 0).toLocaleString('vi-VN') + '₫';
        const customer = d.khach_hang || {};
        const shipper = d.nhan_vien_giao || {};
        const rows = (d.chi_tiet_hoa_don || []).map(x => `
            <tr>
                <td><strong>${esc(x.ten_hien_thi || x.san_pham?.ten_san_pham)}</strong><small>${esc(x.loai === 'bo_hoa' ? 'Bó hoa' : 'Sản phẩm')}</small></td>
                <td class="text-center">${Number(x.so_luong || 0)}</td>
                <td class="text-end">${money(x.gia_ban_snapshot)}</td>
                <td class="text-end">${money(x.thanh_tien)}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="text-center text-muted">Không có sản phẩm.</td></tr>';

        document.getElementById('orderDetailBody').innerHTML = `
            <div class="order-detail-grid">
                <div><label>Mã đơn hàng</label><strong class="order-detail-code">${esc('#HD-' + String(d.ma_hoa_don || '').padStart(4, '0'))}</strong></div>
                <div><label>Trạng thái</label><span class="rs-badge rs-badge-${d.trang_thai === 'DELIVERED' ? 'delivered' : (d.trang_thai === 'CANCELLED' ? 'cancelled' : 'confirmed')}">${esc(d.trang_thai_text || d.trang_thai)}</span></div>
                <div><label>Ngày đặt</label><span>${esc(d.ngay_dat)}</span></div>
                <div><label>Khách hàng</label><span>${esc(customer.ten_khach_hang)}</span></div>
                <div><label>Số điện thoại</label><span>${esc(customer.so_dien_thoai || d.so_dien_thoai)}</span></div>
                <div><label>Shipper</label><span>${esc(shipper.ten_nhan_vien)}</span></div>
                <div><label>Thanh toán</label><span>${esc(d.phuong_thuc_thanh_toan)} · ${esc(d.trang_thai_thanh_toan)}</span></div>
                <div><label>Tổng tiền</label><strong>${money(d.tong_tien)}</strong></div>
            </div>
            <div class="order-detail-section">
                <h4><i class="bi bi-box-seam"></i> Sản phẩm trong đơn</h4>
                <div class="order-detail-table-wrap"><table class="order-detail-table"><thead><tr><th>Sản phẩm</th><th class="text-center">SL</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead><tbody>${rows}</tbody></table></div>
            </div>
            <div class="order-detail-section"><h4><i class="bi bi-geo-alt"></i> Địa chỉ giao hàng</h4><p class="order-detail-address">${esc(d.dia_chi_giao)}</p></div>
            ${d.hang_hoan ? `<div class="order-detail-return"><strong>Hàng hoàn:</strong> ${esc(d.hang_hoan.trang_thai_text || d.hang_hoan.trang_thai)}</div>` : ''}
        `;
    }

    document.getElementById('orderDetailOverlay')?.addEventListener('click', function (e) {
        if (e.target === this) closeOrderDetail();
    });

    // ─── FAILED DETAIL MODAL LOGIC ───
    function openFailedDetail(orderId) {
        const raw = document.getElementById('failed-data-' + orderId);
        if (!raw) return;

        let data = {};
        try {
            data = JSON.parse(raw.textContent);
        } catch (e) {
            console.error('Invalid JSON failed data', e);
            return;
        }

        document.getElementById('fmOrderCode').textContent = data.ma_hd_code;
        document.getElementById('fmShipper').textContent   = data.shipper_ten;
        document.getElementById('fmShipperPhone').textContent = data.shipper_sdt;
        document.getElementById('fmFailTime').textContent  = data.thoi_diem_bao;
        document.getElementById('fmReason').textContent    = data.ly_do;

        if (data.ghi_chu) {
            document.getElementById('fmNote').textContent = data.ghi_chu;
            document.getElementById('fmNoteRow').style.display = 'block';
        } else {
            document.getElementById('fmNoteRow').style.display = 'none';
        }

        // Source badge
        const srcBadge = document.getElementById('fmSourceBadge');
        if (data.loai_loi === 'CUSTOMER') {
            srcBadge.className = 'rs-badge rs-badge-failed';
            srcBadge.innerHTML = '<i class="bi bi-person-x"></i> Khách hàng';
        } else if (data.loai_loi === 'SHIPPER') {
            srcBadge.className = 'rs-badge rs-badge-failed';
            srcBadge.innerHTML = '<i class="bi bi-truck"></i> Shipper';
        } else {
            srcBadge.className = 'text-muted';
            srcBadge.textContent = '—';
        }

        // Responsibility & sanction
        const respEl = document.getElementById('fmResponsibilityContent');
        if (data.loai_loi === 'CUSTOMER') {
            respEl.innerHTML = `
                <div><strong>Trách nhiệm:</strong> <span style="color:#B42318; font-weight:600;">Khách hàng</span></div>
                <div><strong>Khách hàng:</strong> ${data.khach_hang_ten} (${data.khach_hang_sdt})</div>
                <div><strong>Vi phạm chưa xử lý:</strong> <span class="badge ${data.vi_pham_count > 0 ? 'bg-danger' : 'bg-secondary'}">${data.vi_pham_count}</span></div>
                <div><strong>Trạng thái COD:</strong> <span style="font-weight:600;">${data.quyen_cod}</span></div>
            `;
        } else if (data.loai_loi === 'SHIPPER') {
            let fineHtml = '';
            if (data.is_admin) {
                if (data.phat_shipper && data.phat_shipper.length > 0) {
                    fineHtml = data.phat_shipper.map(p => `
                        <div style="background:#FFF; border:1px solid #FECDCA; border-radius:6px; padding:6px 10px; margin-top:6px;">
                            <div><strong>Tiền phạt:</strong> <span style="color:#B42318; font-weight:700;">${p.so_tien}</span> (${p.trang_thai})</div>
                            <div style="font-size:11.5px; color:#667085;">Hàng hỏng: ${p.tien_hang_hong} · Hao hụt Flash Sale: ${p.tien_hao_hut_flash_sale}</div>
                            <div style="font-size:11.5px; color:#475467;">Lý do: ${p.ly_do}</div>
                        </div>
                    `).join('');
                } else {
                    fineHtml = '<div class="text-muted mt-1">Chưa tạo khoản phạt cho shipper.</div>';
                }
            } else {
                fineHtml = '<div class="text-muted mt-1" style="font-style:italic;">Thông tin phạt và chế tài do Quản trị viên quản lý.</div>';
            }

            respEl.innerHTML = `
                <div><strong>Trách nhiệm:</strong> <span style="color:#B42318; font-weight:600;">Shipper</span></div>
                <div><strong>Shipper:</strong> ${data.shipper_ten} (${data.shipper_sdt})</div>
                ${fineHtml}
            `;
        } else {
            respEl.innerHTML = '<div class="text-muted">Chưa xác định nguồn lỗi.</div>';
        }

        // Timeline
        const status = data.hang_hoan_trang_thai;
        const isChoMangVe  = status === 'CHO_MANG_VE_KHO';
        const isDaNhan     = status === 'DA_NHAN_TAI_KHO';
        const canInspect   = isChoMangVe || isDaNhan;
        const isDangKiemTra= status === 'DANG_KIEM_TRA';
        const isDaKiemTra  = status === 'DA_KIEM_TRA';

        const step1Class = 'completed';
        const step2Class = (isChoMangVe ? 'current' : (isDaNhan || isDangKiemTra || isDaKiemTra ? 'completed' : ''));
        const step3Class = (isDaNhan ? 'current' : (isDangKiemTra || isDaKiemTra ? 'completed' : ''));
        const step4Class = (isDangKiemTra ? 'current' : (isDaKiemTra ? 'completed' : ''));
        const step5Class = (isDaKiemTra ? 'completed' : '');

        document.getElementById('fmTimeline').innerHTML = `
            <div class="rs-timeline-step ${step1Class}">
                <div class="rs-timeline-dot">✓</div>
                <div class="rs-timeline-label">Giao thất bại</div>
                <div class="rs-timeline-time">${data.thoi_diem_bao}</div>
            </div>
            <div class="rs-timeline-step ${step2Class}">
                <div class="rs-timeline-dot">${isChoMangVe ? '●' : (step2Class === 'completed' ? '✓' : '2')}</div>
                <div class="rs-timeline-label">Chờ mang về kho</div>
            </div>
            <div class="rs-timeline-step ${step3Class}">
                <div class="rs-timeline-dot">${isDaNhan ? '●' : (step3Class === 'completed' ? '✓' : '3')}</div>
                <div class="rs-timeline-label">Kho nhận hàng</div>
                <div class="rs-timeline-time">${data.ngay_nhan_kho || '—'}</div>
            </div>
            <div class="rs-timeline-step ${step4Class}">
                <div class="rs-timeline-dot">${isDangKiemTra ? '●' : (step4Class === 'completed' ? '✓' : '4')}</div>
                <div class="rs-timeline-label">Kiểm tra chất lượng</div>
                <div class="rs-timeline-time">${data.ngay_kiem_tra || '—'}</div>
            </div>
            <div class="rs-timeline-step ${step5Class}">
                <div class="rs-timeline-dot">${isDaKiemTra ? '✓' : '5'}</div>
                <div class="rs-timeline-label">Hoàn tất</div>
            </div>
        `;

        // Inspect button or message
        const actionSlot = document.getElementById('fmInspectActionSlot');
        if (canInspect) {
            actionSlot.innerHTML = `
                <a href="${data.kiem_tra_url}" onclick="closeFailedDetail(); return openKiemTraModal(${data.ma_hoa_don}, event)" class="rs-btn-action rs-btn-action-primary">
                    <i class="bi bi-clipboard-check"></i> Thực hiện kiểm tra hàng
                </a>
            `;
        } else {
            actionSlot.innerHTML = '';
        }

        // Inspection info
        let infoText = '';
        if (isDaKiemTra) {
            infoText = `<strong>Người kiểm tra:</strong> ${data.nguoi_kiem_tra} · <strong>Ngày kiểm tra:</strong> ${data.ngay_kiem_tra || '—'}`;
        } else if (isDaNhan) {
            infoText = `Hàng đã được tiếp nhận tại kho lúc <strong>${data.ngay_nhan_kho || '—'}</strong>. Vui lòng bấm kiểm tra hàng để phân loại hồi kho, xả Flash Sale hoặc báo hỏng.`;
        } else {
            infoText = `Hàng hoàn đang trong quá trình shipper vận chuyển quay về kho shop.`;
        }
        document.getElementById('fmInspectionInfo').innerHTML = infoText;

        // Items table
        const tbody = document.getElementById('fmItemsTableBody');
        if (data.chi_tiet_hang_hoan && data.chi_tiet_hang_hoan.length > 0) {
            tbody.innerHTML = data.chi_tiet_hang_hoan.map(it => `
                <tr>
                    <td><strong>${it.ten}</strong></td>
                    <td style="text-align: center;">${it.so_luong_theo_don}</td>
                    <td style="text-align: center; font-weight:600; color:#027A48;">${it.so_luong_con_ban_duoc}</td>
                    <td style="text-align: center; font-weight:600; color:#B42318;">${it.so_luong_hong}</td>
                    <td>
                        <span class="badge ${it.ket_qua === 'HOAN_HOAN_THANH' ? 'bg-success' : 'bg-secondary'}">${it.ket_qua || 'Chưa kiểm tra'}</span>
                        ${it.ghi_chu ? `<span class="text-muted ms-1">(${it.ghi_chu})</span>` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Chưa có dữ liệu chi tiết hàng hoàn.</td></tr>`;
        }

        document.getElementById('failedModalOverlay').classList.add('show');
    }

    function closeFailedDetail() {
        document.getElementById('failedModalOverlay').classList.remove('show');
    }

    document.getElementById('failedModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeFailedDetail();
    });
    // ─── MODAL KIỂM TRA HÀNG HOÀN ───
    const KTH_URL_TPL   = @json(route('admin.hoa-don.kiem-tra-hang', ['maHoaDon' => '__ID__']));
    const KTH_STORE_TPL = @json(route('admin.hoa-don.kiem-tra-hang.store', ['maHoaDon' => '__ID__']));
    const kth = { id: null, items: [], saving: false, reloadOnClose: false };

    const kthEsc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const kthMoney = n => Number(n || 0).toLocaleString('vi-VN') + 'đ';
    const kthEl = id => document.getElementById(id);

    function kthHeaders(json = true) {
        const h = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    // Trả về false để chặn điều hướng <a href> (fallback sang trang cũ nếu JS lỗi)
    function openKiemTraModal(orderId, ev) {
        if (ev) ev.preventDefault();
        kth.id = orderId; kth.items = []; kth.reloadOnClose = false;
        kthEl('kthBody').innerHTML = '<div class="kth-loading">Đang tải dữ liệu hàng hoàn...</div>';
        kthEl('kthSaveBtn').disabled = true;
        kthEl('kthOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';

        fetch(KTH_URL_TPL.replace('__ID__', orderId) + '?mo_kiem_tra=1', { headers: kthHeaders(false), credentials: 'same-origin' })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(({ ok, j }) => {
                if (!ok || !j.success) {
                    closeKiemTraModal();
                    showToast(j.message || 'Không thể mở màn hình kiểm tra hàng hoàn.', 'error');
                    return;
                }
                kth.reloadOnClose = !!j.data.vuaNhanKho;
                renderKiemTra(j.data);
            })
            .catch(() => { closeKiemTraModal(); showToast('Lỗi kết nối, vui lòng thử lại.', 'error'); });
        return false;
    }

    function closeKiemTraModal() {
        if (kth.saving) return;
        kthEl('kthOverlay').classList.remove('show');
        document.body.style.overflow = '';
        // Trạng thái vừa chuyển sang "Đã nhận tại kho" → tải lại để bảng đơn hàng hiển thị đúng
        if (kth.reloadOnClose) { kth.reloadOnClose = false; location.reload(); }
    }

    function renderKiemTra(d) {
        const t = d.thongTinDon || {};
        kth.items = d.dongKiemTra || [];
        const nvOptions = (d.nhanViens || []).map(n => `<option value="${n.ma_nhan_vien}">${kthEsc(n.ten_nhan_vien)}</option>`).join('');
        const nguon = t.loai_loi === 'SHIPPER' ? 'Do shipper' : (t.loai_loi === 'CUSTOMER' ? 'Do khách hàng' : '—');
        const ro = (label, val, cls = '') => `<div class="kth-field ${cls}"><label>${label}</label><div class="kth-ro">${kthEsc(val || '—')}</div></div>`;

        const rows = kth.items.map(it => {
            const id = it.ma_chi_tiet_hang_hoan;
            return `
            <div class="kth-item" id="kth-item-${id}" data-max="${it.so_luong_theo_don}" data-gia="${it.gia_ban}" data-bo="${it.la_bo_hoa ? 1 : 0}">
                <div class="kth-item-head">
                    <img class="kth-thumb" src="${kthEsc(it.anh)}" alt="" onerror="this.style.visibility='hidden'">
                    <div class="kth-item-meta">
                        <div class="kth-item-name"><span class="kth-tag ${it.la_bo_hoa ? 'bo' : ''}">${it.la_bo_hoa ? 'Bó hoa' : 'Sản phẩm'}</span>${kthEsc(it.ten)}</div>
                        <div style="font-size:12.5px;color:#667085;">Giá bán: ${kthMoney(it.gia_ban)}</div>
                    </div>
                    <div class="kth-order-qty"><b>${it.so_luong_theo_don}</b><span>theo đơn</span></div>
                    <div class="kth-result" id="kth-result-${id}"></div>
                </div>
                ${it.la_bo_hoa ? `<label class="kth-xabo"><input type="checkbox" id="kth-xabo-${id}" onchange="kthXaBo(${id}, this.checked)"> Xả toàn bộ bó (tất cả xem là hàng hỏng)</label>` : ''}
                <div class="kth-qty-row">
                    <div class="kth-qty-block hong">
                        <label>Số lượng hỏng</label>
                        <div class="kth-stepper">
                            <button type="button" id="kth-hong-minus-${id}" onclick="kthStep(${id},'hong',-1)">−</button>
                            <input type="number" id="kth-hong-${id}" min="0" max="${it.so_luong_theo_don}" value="0" oninput="kthInput(${id},'hong')">
                            <button type="button" id="kth-hong-plus-${id}" onclick="kthStep(${id},'hong',1)">+</button>
                        </div>
                    </div>
                    <div class="kth-qty-block ban">
                        <label>Số lượng còn bán được</label>
                        <div class="kth-stepper">
                            <button type="button" id="kth-ban-minus-${id}" onclick="kthStep(${id},'ban',-1)">−</button>
                            <input type="number" id="kth-ban-${id}" min="0" max="${it.so_luong_theo_don}" value="${it.so_luong_theo_don}" oninput="kthInput(${id},'ban')">
                            <button type="button" id="kth-ban-plus-${id}" onclick="kthStep(${id},'ban',1)">+</button>
                        </div>
                    </div>
                </div>
                <div class="kth-damage" id="kth-damage-${id}">
                    <div><label>Lý do hỏng <span class="req">*</span></label>
                        <textarea id="kth-lydo-${id}" maxlength="255" placeholder="VD: Hoa dập, cánh gãy..." oninput="kthValidateRow(${id}, false)"></textarea></div>
                    <div><label>Nhân viên báo cáo <span class="req">*</span></label>
                        <select id="kth-nv-${id}" onchange="kthValidateRow(${id}, false)"><option value="">-- Chọn nhân viên --</option>${nvOptions}</select></div>
                </div>
                <div class="kth-row-error" id="kth-error-${id}"></div>
            </div>`;
        }).join('');

        kthEl('kthBody').innerHTML = `
            <div class="kth-section-title"><i class="bi bi-receipt"></i> Thông tin đơn hàng</div>
            <div class="kth-info-grid">
                ${ro('Mã đơn hàng', t.ma_hd_code)}
                ${ro('Khách hàng', t.khach_hang)}
                ${ro('Số điện thoại', t.so_dien_thoai)}
                ${ro('Tổng tiền đơn', kthMoney(t.tong_tien))}
                ${ro('Nguồn lỗi', nguon)}
                ${ro('Shipper', t.shipper)}
                ${ro('Thời điểm giao thất bại', t.thoi_diem_bao)}
                ${ro('Nhận tại kho', t.thoi_diem_nhan_tai_kho)}
                ${ro('Lý do thất bại', t.ly_do, 'kth-span2')}
            </div>
            <div class="kth-section-title"><i class="bi bi-box-seam"></i> Chi tiết hàng hoàn
                <small>Số hỏng + số còn bán được phải bằng số lượng theo đơn</small></div>
            <div class="kth-list">${rows || '<div class="kth-loading">Không có sản phẩm nào trong hàng hoàn này.</div>'}</div>
            <div class="kth-summary">
                <div><span>Tổng số lượng hỏng</span><b style="color:#B42318" id="kth-sum-hong">0</b></div>
                <div><span>Tổng còn bán được (chuyển Flash Sale)</span><b style="color:#027A48" id="kth-sum-ban">0</b></div>
                <div><span>Thiệt hại hàng hỏng (ước tính)</span><b style="color:#B54708" id="kth-sum-tien">0đ</b></div>
            </div>
            <div class="kth-note">ⓘ Tiền hệ thống/shipper chịu, hao hụt Flash Sale và phạt shipper do hệ thống tính khi lưu. Hàng còn bán được sẽ chờ tạo chương trình ở trang Flash Sale.</div>
            <div class="kth-global-error" id="kthGlobalError"></div>`;

        kth.items.forEach(it => kthRefresh(it.ma_chi_tiet_hang_hoan));
        kthEl('kthSaveBtn').disabled = kth.items.length === 0;
    }

    const kthMax = id => Number(kthEl('kth-item-' + id).dataset.max);
    const kthClamp = (v, max) => Math.max(0, Math.min(max, Number.isFinite(v) ? Math.trunc(v) : 0));

    function kthSet(id, hong) {
        const max = kthMax(id);
        hong = kthClamp(hong, max);
        kthEl('kth-hong-' + id).value = hong;
        kthEl('kth-ban-' + id).value = max - hong;
        kthRefresh(id);
    }
    function kthStep(id, field, delta) {
        const h = Number(kthEl('kth-hong-' + id).value) || 0;
        const b = Number(kthEl('kth-ban-' + id).value) || 0;
        kthSet(id, field === 'hong' ? h + delta : kthMax(id) - (b + delta));
    }
    function kthInput(id, field) {
        const max = kthMax(id);
        const v = kthClamp(Number(kthEl(`kth-${field}-${id}`).value), max);
        kthSet(id, field === 'hong' ? v : max - v);
    }
    function kthXaBo(id, checked) {
        kthSet(id, checked ? kthMax(id) : 0);
    }

    // Cập nhật giao diện 1 dòng + tổng kết sau mỗi thay đổi
    function kthRefresh(id) {
        const max = kthMax(id);
        const hong = Number(kthEl('kth-hong-' + id).value) || 0;
        const ban = max - hong;
        kthEl('kth-damage-' + id).classList.toggle('show', hong > 0);

        const xabo = kthEl('kth-xabo-' + id);
        if (xabo) xabo.checked = (hong === max && max > 0);

        kthEl('kth-hong-minus-' + id).disabled = hong <= 0;
        kthEl('kth-hong-plus-' + id).disabled  = hong >= max;
        kthEl('kth-ban-minus-' + id).disabled  = ban <= 0;
        kthEl('kth-ban-plus-' + id).disabled   = ban >= max;

        const res = kthEl('kth-result-' + id);
        if (ban > 0 && hong > 0) res.innerHTML = '<span class="kth-badge mix">FLASH_SALE (' + ban + ') + hỏng (' + hong + ')</span>';
        else if (ban > 0)        res.innerHTML = '<span class="kth-badge fs">FLASH_SALE</span>';
        else                     res.innerHTML = '<span class="kth-badge hong">HONG</span>';

        kthValidateRow(id, false);
        kthUpdateSummary();
    }

    function kthUpdateSummary() {
        let h = 0, b = 0, tien = 0;
        kth.items.forEach(it => {
            const id = it.ma_chi_tiet_hang_hoan, max = kthMax(id);
            const hong = Number(kthEl('kth-hong-' + id).value) || 0;
            h += hong; b += max - hong; tien += hong * Number(kthEl('kth-item-' + id).dataset.gia || 0);
        });
        kthEl('kth-sum-hong').textContent = h;
        kthEl('kth-sum-ban').textContent = b;
        kthEl('kth-sum-tien').textContent = kthMoney(tien);
    }

    // showErr=false: chỉ kiểm tra im lặng (khi đang nhập); true: hiện lỗi khi bấm Lưu
    function kthValidateRow(id, showErr) {
        const max = kthMax(id);
        const hong = Number(kthEl('kth-hong-' + id).value) || 0;
        const ban = Number(kthEl('kth-ban-' + id).value) || 0;
        const errs = [];
        if (hong < 0 || ban < 0) errs.push('Số lượng không được âm.');
        if (hong + ban !== max) errs.push(`Hỏng + còn bán được phải bằng ${max}.`);
        if (hong > 0 && !kthEl('kth-lydo-' + id).value.trim()) errs.push('Vui lòng nhập lý do hỏng.');
        if (hong > 0 && !kthEl('kth-nv-' + id).value) errs.push('Vui lòng chọn nhân viên báo cáo.');

        const box = kthEl('kth-error-' + id);
        const show = errs.length > 0 && (showErr || box.dataset.shown === '1');
        if (showErr) box.dataset.shown = '1';
        box.textContent = errs.join(' ');
        box.classList.toggle('show', show);
        kthEl('kth-item-' + id).classList.toggle('has-error', show);
        return errs.length === 0;
    }

    function kthGlobalError(msg) {
        const el = kthEl('kthGlobalError');
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('show', !!msg);
    }

    function saveKiemTra() {
        if (kth.saving) return;
        kthGlobalError('');
        let ok = true;
        kth.items.forEach(it => { if (!kthValidateRow(it.ma_chi_tiet_hang_hoan, true)) ok = false; });
        if (!ok) { kthGlobalError('Vui lòng kiểm tra lại các dòng bị lỗi.'); return; }

        const items = {};
        kth.items.forEach(it => {
            const id = it.ma_chi_tiet_hang_hoan;
            const hong = Number(kthEl('kth-hong-' + id).value) || 0;
            const ban = Number(kthEl('kth-ban-' + id).value) || 0;
            items[id] = {
                so_luong_hong: hong,
                so_luong_con_ban_duoc: ban,
                xa_bo: !!(it.la_bo_hoa && hong === kthMax(id)),
            };
            if (hong > 0) {
                items[id].ly_do_hong = kthEl('kth-lydo-' + id).value.trim();
                items[id].ma_nhan_vien = Number(kthEl('kth-nv-' + id).value);
            }
        });

        kth.saving = true;
        const btn = kthEl('kthSaveBtn');
        btn.disabled = true; btn.textContent = 'Đang lưu...';

        fetch(KTH_STORE_TPL.replace('__ID__', kth.id), {
            method: 'POST', headers: kthHeaders(true), credentials: 'same-origin',
            body: JSON.stringify({ items }),
        })
            .then(r => r.json().then(j => ({ status: r.status, j })))
            .then(({ status, j }) => {
                if (status >= 200 && status < 300 && j.success) {
                    kth.saving = false; kth.reloadOnClose = false;
                    kthEl('kthOverlay').classList.remove('show');
                    document.body.style.overflow = '';
                    showToast(j.message || 'Đã lưu kết quả kiểm tra.', 'success');
                    setTimeout(() => location.reload(), 900);
                    return;
                }
                // Lỗi validation: gắn vào đúng dòng nếu có, còn lại hiện ở cuối modal
                const general = [];
                if (j.errors) {
                    Object.entries(j.errors).forEach(([key, msgs]) => {
                        const m = key.match(/^items\.(\d+)/);
                        const box = m ? kthEl('kth-error-' + m[1]) : null;
                        if (box) {
                            box.textContent = msgs.join(' '); box.classList.add('show'); box.dataset.shown = '1';
                            kthEl('kth-item-' + m[1]).classList.add('has-error');
                        } else general.push(...msgs);
                    });
                }
                if (!general.length && !j.errors) general.push(j.message || 'Không thể lưu kết quả kiểm tra.');
                kthGlobalError(general.join(' ') || 'Vui lòng kiểm tra lại các dòng bị lỗi.');
                kth.saving = false; btn.disabled = false; btn.textContent = 'Lưu kết quả kiểm tra';
            })
            .catch(() => {
                kthGlobalError('Lỗi kết nối, vui lòng thử lại.');
                kth.saving = false; btn.disabled = false; btn.textContent = 'Lưu kết quả kiểm tra';
            });
    }

    kthEl('kthOverlay').addEventListener('click', function (e) { if (e.target === this) closeKiemTraModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && kthEl('kthOverlay').classList.contains('show')) closeKiemTraModal();
    });
</script>
@endpush
