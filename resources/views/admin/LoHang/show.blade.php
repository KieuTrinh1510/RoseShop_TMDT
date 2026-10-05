@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
@endpush

@section('content')
<div class="ncc-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Chi tiết lô hàng #CTN-{{ $loHang->ma_chi_tiet_nhap }}</h1>
            <p class="rs-page-subtitle">Mặt hàng: {{ $loHang->sanPham->ten_san_pham ?? 'Sản phẩm #' . $loHang->ma_san_pham }} — Thuộc phiếu nhập #PN-{{ $loHang->ma_phieu_nhap }}</p>
        </div>
        <div class="ncc-header-actions">
            <a href="{{ route('admin.lo-hang') }}" class="ncc-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                <span>Danh sách lô</span>
            </a>
            @if($loHang->phieuNhap?->nhaCungCap)
                <a href="{{ route('admin.nha-cung-cap.show', $loHang->phieuNhap->nhaCungCap->ma_nha_cung_cap) }}" class="ncc-btn-secondary">
                    <i class="bi bi-building"></i>
                    <span>Xem Nhà cung cấp</span>
                </a>
            @endif
            @if($loHang->so_luong_con_lai > 0)
            <button type="button"
                    class="ncc-btn-primary"
                    onclick="openModalDanhGia({{ $loHang->ma_chi_tiet_nhap }}, '{{ addslashes($loHang->sanPham->ten_san_pham ?? '') }}', {{ $loHang->so_luong }}, '{{ $loHang->danhGiaMoiNhat?->ket_qua ?? '' }}', {{ $loHang->danhGiaMoiNhat?->so_luong_loi ?? 0 }}, '{{ addslashes($loHang->danhGiaMoiNhat?->ly_do ?? '') }}', '{{ addslashes($loHang->danhGiaMoiNhat?->ghi_chu ?? '') }}')">
                <i class="bi bi-shield-check me-1"></i>
                <span>{{ $loHang->danhGiaMoiNhat ? 'Cập nhật đánh giá' : 'Đánh giá chất lượng' }}</span>
            </button>
            <button type="button"
                    class="btn btn-outline-danger d-inline-flex align-items-center gap-1"
                    style="font-size: 13px; font-weight: 600; padding: 7px 14px; border-radius: 8px;"
                    onclick="openModalBaoHong()">
                <i class="bi bi-trash3 me-1"></i>
                <span>Báo hỏng lô</span>
            </button>
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

    {{-- Detail Grid --}}
    <div class="ncc-detail-grid">

        {{-- Left: Lot Info Summary --}}
        <div>
            <div class="ncc-info-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-light text-muted border">Mã lô #CTN-{{ $loHang->ma_chi_tiet_nhap }}</span>
                    @php $dgLatest = $loHang->danhGiaMoiNhat; @endphp
                    @if($dgLatest)
                        @if($dgLatest->ket_qua === 'DAT')
                            <span class="badge-quality-dat">Đạt chất lượng</span>
                        @elseif($dgLatest->ket_qua === 'CO_VAN_DE')
                            <span class="badge-quality-covande">Có vấn đề</span>
                        @elseif($dgLatest->ket_qua === 'KHONG_DAT')
                            <span class="badge-quality-khongdat">Không đạt</span>
                        @endif
                    @else
                        <span class="badge-quality-chuadanhgia">Chưa đánh giá</span>
                    @endif
                </div>

                <h2 class="ncc-info-title">{{ $loHang->sanPham->ten_san_pham ?? 'Sản phẩm #' . $loHang->ma_san_pham }}</h2>
                <div class="ncc-info-meta">
                    <i class="bi bi-box-seam me-1"></i>
                    Đơn vị: {{ $loHang->sanPham->don_vi_tinh ?? 'Cái' }} | Mã SP: #{{ $loHang->ma_san_pham }}
                </div>

                <div class="ncc-info-item">
                    <span class="label">Phiếu nhập nguồn</span>
                    <span class="value">
                        <a href="{{ route('phieu-nhap.index') }}">#PN-{{ $loHang->ma_phieu_nhap }}</a>
                        <small class="text-muted ms-1">({{ $loHang->created_at ? $loHang->created_at->format('d/m/Y H:i') : '—' }})</small>
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Nhà cung cấp</span>
                    <span class="value">
                        @if($loHang->phieuNhap?->nhaCungCap)
                            <a href="{{ route('admin.nha-cung-cap.show', $loHang->phieuNhap->nhaCungCap->ma_nha_cung_cap) }}" class="fw-semibold">
                                {{ $loHang->phieuNhap->nhaCungCap->ten_nha_cung_cap }}
                            </a>
                        @else
                            {{ $loHang->phieuNhap->ten_nha_cung_cap ?? '—' }}
                        @endif
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Trạng thái lô</span>
                    <span class="value">
                        @if($loHang->so_luong_con_lai > 0)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                <i class="bi bi-check-circle me-1"></i>CÒN HÀNG
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 fw-bold">
                                <i class="bi bi-dash-circle me-1"></i>HẾT HÀNG
                            </span>
                        @endif
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Phân bổ tồn kho lô</span>
                    <div class="mt-2" style="font-size:13px;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">1. Số lượng ban đầu:</span>
                            <strong class="text-dark">{{ $loHang->so_luong }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">2. Tồn thực tế (On Hand):</span>
                            @if($loHang->so_luong_con_lai > 0)
                                <span class="badge bg-primary-subtle text-primary border px-2 py-1 fw-bold">{{ $loHang->so_luong_con_lai }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fw-bold">0 (Hết hàng)</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">3. Đang giữ (Reserved):</span>
                            <span class="badge bg-warning-subtle text-warning border px-2 py-1 fw-bold">{{ $tongDangGiu }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">4. Khả dụng (Available):</span>
                            <span class="badge bg-success-subtle text-success border px-2 py-1 fw-bold">{{ $tonKhaDung }}</span>
                        </div>
                    </div>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Thống kê xuất & hỏng</span>
                    <div class="mt-2" style="font-size:13px;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Tổng đã xuất kho:</span>
                            <strong class="text-success">{{ $tongDaXuat }} sp</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Tổng hàng hỏng/hao hụt:</span>
                            <strong class="{{ $tongHangHong > 0 ? 'text-danger' : 'text-muted' }}">{{ $tongHangHong }} sp</strong>
                        </div>
                    </div>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Tài chính</span>
                    <div class="mt-1 d-flex justify-content-between" style="font-size:13px;">
                        <span class="text-muted">Giá nhập:</span>
                        <strong class="text-dark">{{ number_format($loHang->gia_nhap, 0, ',', '.') }} đ</strong>
                    </div>
                    <div class="mt-1 d-flex justify-content-between" style="font-size:13px;">
                        <span class="text-muted">Giá bán niêm yết:</span>
                        <strong class="text-success">{{ number_format($loHang->gia_ban, 0, ',', '.') }} đ</strong>
                    </div>
                    <div class="mt-1 d-flex justify-content-between" style="font-size:13px;">
                        <span class="text-muted">Tổng giá trị lô nhập:</span>
                        <strong style="color:#EC3D8F;">{{ number_format($loHang->so_luong * $loHang->gia_nhap, 0, ',', '.') }} đ</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Export History, Timeline, Reserved Orders, Quality & Damaged Reports --}}
        <div>

            {{-- 1. Lịch sử xuất kho theo lô (nguồn chuẩn: inventory_reservations CONSUMED) --}}
            <div class="ncc-card mb-4">
                <div class="ncc-card-header d-flex justify-content-between align-items-center">
                    <h2 class="ncc-card-title mb-0">
                        <i class="bi bi-cart-check text-success"></i>
                        Lịch sử xuất kho theo lô
                        <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $lichSuXuatKho->count() }} lượt xuất</span>
                    </h2>
                    <span class="text-muted" style="font-size: 13px;">Tổng xuất: <strong class="text-success">{{ $tongDaXuat }}</strong> sp</span>
                </div>

                <div class="table-responsive">
                    <table class="ncc-table">
                        <thead>
                            <tr>
                                <th>Thời gian xuất</th>
                                <th>Mã đơn hàng</th>
                                <th>Khách hàng</th>
                                <th class="text-center">Số lượng xuất</th>
                                <th>Phân loại / Mục đích</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lichSuXuatKho as $xuat)
                                <tr>
                                    <td>
                                        {{ $xuat->updated_at ? $xuat->updated_at->format('d/m/Y H:i') : ($xuat->created_at ? $xuat->created_at->format('d/m/Y H:i') : '—') }}
                                    </td>
                                    <td>
                                        <span class="fw-bold" style="color:#EC3D8F;">#DH-{{ $xuat->ma_hoa_don }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $xuat->hoaDon?->khachHang?->ho_ten ?? 'Khách vãng lai' }}</div>
                                        <small class="text-muted">{{ $xuat->hoaDon?->so_dien_thoai ?: '' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                            -{{ $xuat->so_luong }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($xuat->boHoa)
                                            <span class="badge bg-info-subtle text-info border">
                                                <i class="bi bi-flower1 me-1"></i>Nguyên liệu bó: {{ $xuat->boHoa->ten_bo_hoa }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-box-seam me-1"></i>Sản phẩm đơn lẻ
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ncc-empty-state py-4">
                                        <p class="mb-0 text-muted">Lô hàng này chưa phát sinh giao dịch xuất kho bán hàng nào.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Đơn hàng đang giữ tồn (RESERVED) trên lô nếu có --}}
            @if($danhSachDangGiu->isNotEmpty())
            <div class="ncc-card mb-4 border-warning">
                <div class="ncc-card-header bg-warning-subtle d-flex justify-content-between align-items-center">
                    <h2 class="ncc-card-title mb-0 text-warning-emphasis">
                        <i class="bi bi-lock-fill me-1"></i>
                        Đơn hàng đang giữ tồn (Reserved) trên lô
                        <span class="badge bg-warning text-dark ms-1">{{ $danhSachDangGiu->count() }} đơn</span>
                    </h2>
                    <span class="fw-bold text-dark">Tổng giữ: {{ $tongDangGiu }} sp</span>
                </div>
                <div class="table-responsive">
                    <table class="ncc-table">
                        <thead>
                            <tr>
                                <th>Thời gian giữ</th>
                                <th>Mã đơn hàng</th>
                                <th>Khách hàng</th>
                                <th class="text-center">Số lượng giữ</th>
                                <th>Phân loại</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($danhSachDangGiu as $giu)
                                <tr>
                                    <td>{{ $giu->created_at ? $giu->created_at->format('d/m/Y H:i') : '—' }}</td>
                                    <td>
                                        <span class="fw-bold text-warning-emphasis">#DH-{{ $giu->ma_hoa_don }}</span>
                                    </td>
                                    <td>{{ $giu->hoaDon?->khachHang?->ho_ten ?? 'Khách vãng lai' }}</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark fw-bold">{{ $giu->so_luong }}</span></td>
                                    <td>{{ $giu->boHoa ? "Bó hoa: {$giu->boHoa->ten_bo_hoa}" : 'Sản phẩm đơn lẻ' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- 3. Lịch sử biến động của lô (Timeline tổng hợp) --}}
            <div class="ncc-card mb-4">
                <div class="ncc-card-header d-flex justify-content-between align-items-center">
                    <h2 class="ncc-card-title mb-0">
                        <i class="bi bi-clock-history text-primary"></i>
                        Lịch sử biến động của lô
                        <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $timeline->count() }} mốc</span>
                    </h2>
                </div>
                <div class="p-3 bg-light border-bottom" style="font-size:12px; color:#667085;">
                    <i class="bi bi-info-circle me-1"></i>
                    Truy vết toàn diện từ Nhập kho ban đầu, Xuất kho theo đơn, đến Hàng hỏng/hao hụt. Hiện tại hệ thống chưa phát sinh nghiệp vụ Điều chỉnh tồn kho (Inventory Adjustment).
                </div>

                <div class="table-responsive">
                    <table class="ncc-table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Sự kiện</th>
                                <th class="text-center">Biến động</th>
                                <th>Chi tiết giao dịch</th>
                                <th>Người thực hiện</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($timeline as $m)
                                <tr>
                                    <td>
                                        {{ $m['thoi_gian'] ? \Carbon\Carbon::parse($m['thoi_gian'])->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $m['badge_class'] }}">
                                            <i class="bi {{ $m['icon'] }} me-1"></i>{{ $m['tieu_de'] }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold {{ $m['so_luong_thay_doi'] > 0 ? 'text-primary' : 'text-danger' }}">
                                        {{ $m['so_luong_thay_doi'] > 0 ? '+' . $m['so_luong_thay_doi'] : $m['so_luong_thay_doi'] }}
                                    </td>
                                    <td>
                                        {{ $m['chi_tiet'] }}
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $m['nguoi_thuc_hien'] }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ncc-empty-state py-4">
                                        <p class="mb-0 text-muted">Chưa có dữ liệu biến động.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Quality Evaluation History --}}
            <div class="ncc-card mb-4">
                <div class="ncc-card-header">
                    <h2 class="ncc-card-title">
                        <i class="bi bi-shield-check text-primary"></i>
                        Lịch sử đánh giá chất lượng lô
                        <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $loHang->danhGiaLoHangs->count() }}</span>
                    </h2>
                </div>

                <div class="table-responsive">
                    <table class="ncc-table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Người đánh giá</th>
                                <th class="text-center">Kết quả</th>
                                <th class="text-center">Số lượng lỗi</th>
                                <th>Lý do</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loHang->danhGiaLoHangs as $dg)
                                <tr>
                                    <td>
                                        {{ $dg->thoi_gian_danh_gia ? $dg->thoi_gian_danh_gia->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td>
                                        {{ $dg->nhanVien->ten_nhan_vien ?? 'Hệ thống' }}
                                    </td>
                                    <td class="text-center">
                                        @if($dg->ket_qua === 'DAT')
                                            <span class="badge-quality-dat">Đạt</span>
                                        @elseif($dg->ket_qua === 'CO_VAN_DE')
                                            <span class="badge-quality-covande">Có vấn đề</span>
                                        @elseif($dg->ket_qua === 'KHONG_DAT')
                                            <span class="badge-quality-khongdat">Không đạt</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold {{ $dg->so_luong_loi > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $dg->so_luong_loi }}
                                    </td>
                                    <td>
                                        {{ $dg->ly_do ?: '—' }}
                                    </td>
                                    <td>
                                        {{ $dg->ghi_chu ?: '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ncc-empty-state py-4">
                                        <p class="mb-0 text-muted">Lô hàng này chưa được đánh giá chất lượng.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 5. Damaged Inventory Reports --}}
            <div class="ncc-card">
                <div class="ncc-card-header d-flex justify-content-between align-items-center">
                    <h2 class="ncc-card-title mb-0">
                        <i class="bi bi-trash3 text-danger"></i>
                        Lịch sử hàng hỏng / hao hụt đã trừ tồn kho
                        <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $loHang->baoCaoHangHongs->count() }}</span>
                    </h2>
                    @if($loHang->so_luong_con_lai > 0)
                    <button type="button"
                            class="btn btn-sm btn-outline-danger fw-semibold d-inline-flex align-items-center gap-1"
                            onclick="openModalBaoHong()">
                        <i class="bi bi-plus-lg me-1"></i>Báo hỏng lô
                    </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="ncc-table">
                        <thead>
                            <tr>
                                <th>Thời gian báo cáo</th>
                                <th>Nhân viên</th>
                                <th class="text-center">Số lượng hỏng</th>
                                <th>Lý do</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loHang->baoCaoHangHongs as $hh)
                                <tr>
                                    <td>
                                        {{ $hh->thoi_gian_bao_cao ? \Carbon\Carbon::parse($hh->thoi_gian_bao_cao)->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td>
                                        {{ $hh->nhanVien->ten_nhan_vien ?? '—' }}
                                    </td>
                                    <td class="text-center text-danger fw-bold">
                                        {{ $hh->so_luong_hong }}
                                    </td>
                                    <td>
                                        {{ $hh->ly_do ?: '—' }}
                                    </td>
                                    <td>
                                        {{ $hh->ghi_chu ?: '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ncc-empty-state py-4">
                                        <p class="mb-0 text-muted">Lô hàng này chưa có báo cáo hàng hỏng/hao hụt phát sinh.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                        <input type="text" name="ly_do" id="modal_inp_ly_do" class="form-control" placeholder="VD: Hoa dập nát, sai quy cách...">
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

{{-- Modal Báo hỏng lô (Tái sử dụng nghiệp vụ UC4.4 san-pham.bao-hang-hong) --}}
<div class="modal fade" id="modalBaoHongLo" tabindex="-1" aria-labelledby="modalBaoHongLoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; border:none; box-shadow:0 10px 30px rgba(0,0,0,0.15); overflow:hidden;">
            <form action="{{ route('san-pham.bao-hang-hong') }}" method="POST" id="formBaoHongLo">
                @csrf
                <input type="hidden" name="ma_san_pham" value="{{ $loHang->ma_san_pham }}">
                <input type="hidden" name="ma_chi_tiet_nhap" value="{{ $loHang->ma_chi_tiet_nhap }}">
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                <div class="modal-header" style="background:#FFF5F5; border-bottom:1px solid #FEE2E2;">
                    <h5 class="modal-title fw-bold text-danger" id="modalBaoHongLoLabel" style="font-size:16px;">
                        <i class="bi bi-trash3 text-danger me-2"></i>Báo hỏng / Hao hụt kho cho lô #CTN-{{ $loHang->ma_chi_tiet_nhap }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 p-3 bg-light rounded-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                            <span class="text-muted">Mặt hàng:</span>
                            <strong class="text-dark">{{ $loHang->sanPham->ten_san_pham ?? 'Sản phẩm #' . $loHang->ma_san_pham }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                            <span class="text-muted">Mã lô nhập:</span>
                            <strong class="text-primary">#CTN-{{ $loHang->ma_chi_tiet_nhap }} (Phiếu #PN-{{ $loHang->ma_phieu_nhap }})</strong>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:13px;">
                            <span class="text-muted">Tồn khả dụng của lô:</span>
                            <strong class="text-success"><span id="bh_ton_con_lai">{{ $loHang->so_luong_con_lai }}</span> sp</strong>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:12px;">
                        <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0"></i>
                        <span>Thao tác này sẽ <strong>trừ trực tiếp</strong> số lượng vào tồn kho lô và tồn kho sản phẩm.</span>
                    </div>

                    @php
                        $qcLoi = (int) ($loHang->danhGiaMoiNhat?->so_luong_loi ?? 0);
                    @endphp
                    @if($qcLoi > 0 && $qcLoi <= $loHang->so_luong_con_lai)
                    <div class="mb-3 p-2 border rounded-3 bg-white d-flex justify-content-between align-items-center">
                        <span style="font-size:12px; color:#667085;">
                            <i class="bi bi-info-circle me-1"></i>QC gần nhất phát hiện: <strong class="text-danger">{{ $qcLoi }} lỗi</strong>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:11px;" onclick="document.getElementById('bh_so_luong_hong').value = {{ $qcLoi }};">
                            Điền số lượng này
                        </button>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Số lượng hỏng cần xuất hủy <span class="text-danger">*</span>
                            <small class="text-muted fw-normal">(Tối đa: {{ $loHang->so_luong_con_lai }} sp)</small>
                        </label>
                        <input type="number"
                               name="so_luong_hong"
                               id="bh_so_luong_hong"
                               class="form-control"
                               min="1"
                               max="{{ $loHang->so_luong_con_lai }}"
                               value="{{ $qcLoi > 0 && $qcLoi <= $loHang->so_luong_con_lai ? $qcLoi : 1 }}"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Lý do hỏng / hao hụt <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="ly_do"
                               id="bh_ly_do"
                               class="form-control"
                               value="{{ $loHang->danhGiaMoiNhat?->ly_do ? 'Theo kết quả QC: ' . $loHang->danhGiaMoiNhat->ly_do : '' }}"
                               placeholder="VD: Hoa héo dập khi vận chuyển, hỏng mốc..."
                               maxlength="255"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú chi tiết</label>
                        <textarea name="ghi_chu" id="bh_ghi_chu" class="form-control" rows="2" maxlength="1000" placeholder="Ghi chú thêm nếu có...">{{ $loHang->danhGiaMoiNhat?->ghi_chu ?? '' }}</textarea>
                    </div>

                    <div class="p-2 bg-light rounded-3 d-flex justify-content-between text-muted" style="font-size:12px;">
                        <span><i class="bi bi-person me-1"></i> Người báo cáo: <strong>{{ auth()->user()->nhanVien->ten_nhan_vien ?? auth()->user()->ten_dang_nhap }}</strong></span>
                        <span><i class="bi bi-clock me-1"></i> Ghi nhận tự động</span>
                    </div>
                </div>
                <div class="modal-footer" style="background:#FAFAFA; border-top:1px solid #EAECF0;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger fw-semibold">
                        <i class="bi bi-trash3 me-1"></i> Xác nhận trừ kho hàng hỏng
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

    function openModalBaoHong() {
        new bootstrap.Modal(document.getElementById('modalBaoHongLo')).show();
    }
</script>
@endpush
@endsection
