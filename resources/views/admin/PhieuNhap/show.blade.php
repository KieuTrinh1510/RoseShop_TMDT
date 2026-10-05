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
            <h1 class="rs-page-title">Chi tiết phiếu nhập #PN-{{ $phieuNhap->ma_phieu_nhap }}</h1>
            <p class="rs-page-subtitle">
                Ngày nhập: {{ $phieuNhap->ngay_nhap ? $phieuNhap->ngay_nhap->format('d/m/Y H:i') : '—' }} 
                — Trạng thái: 
                @if($phieuNhap->trang_thai === 'CONFIRMED')
                    Đã xác nhận
                @elseif($phieuNhap->trang_thai === 'CANCELLED')
                    Đã hủy
                @else
                    Đang tạo (Draft)
                @endif
            </p>
        </div>
        <div class="ncc-header-actions">
            @if($phieuNhap->ma_nha_cung_cap)
                <a href="{{ route('admin.nha-cung-cap.show', $phieuNhap->ma_nha_cung_cap) }}" class="ncc-btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span>Quay lại Nhà cung cấp</span>
                </a>
                <a href="{{ route('admin.nha-cung-cap.show', $phieuNhap->ma_nha_cung_cap) }}" class="ncc-btn-secondary">
                    <i class="bi bi-building"></i>
                    <span>Xem hồ sơ NCC</span>
                </a>
            @else
                <a href="{{ route('phieu-nhap.index') }}" class="ncc-btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span>Quay lại danh sách</span>
                </a>
            @endif
            <a href="{{ route('phieu-nhap.index') }}" class="ncc-btn-primary">
                <i class="bi bi-journal-text"></i>
                <span>Danh sách phiếu nhập</span>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $tongSoLo = $phieuNhap->chiTietNhaps->count();
        $tongSoLuong = $phieuNhap->chiTietNhaps->sum('so_luong');
        $tongGiaTri = $phieuNhap->chiTietNhaps->sum(fn($ct) => (float)$ct->so_luong * (float)$ct->gia_nhap);
    @endphp

    {{-- Top Summary KPIs --}}
    <div class="ncc-kpi-grid mb-4">
        <div class="ncc-kpi-card">
            <span class="kpi-label">Tổng mặt hàng</span>
            <span class="kpi-val">{{ $tongSoLo }}</span>
            <small class="text-muted" style="font-size:12px;">Số loại sản phẩm</small>
        </div>
        <div class="ncc-kpi-card">
            <span class="kpi-label">Tổng số lượng</span>
            <span class="kpi-val">{{ number_format($tongSoLuong) }}</span>
            <small class="text-muted" style="font-size:12px;">Sản phẩm nhập</small>
        </div>
        <div class="ncc-kpi-card kpi-rate">
            <span class="kpi-label">Tổng giá trị phiếu nhập</span>
            <span class="kpi-val">{{ number_format($tongGiaTri, 0, ',', '.') }} đ</span>
            <small class="text-muted" style="font-size:12px;">Tổng kinh phí nhập</small>
        </div>
    </div>

    {{-- Khối thông tin hủy nếu phiếu bị cancel (Section F) --}}
    @if($phieuNhap->trang_thai === 'CANCELLED')
        <div class="card mb-4 border-danger" style="border-width: 2px; background: #fff5f5; border-radius: 8px;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-danger me-2" style="font-size: 13px;">
                        <i class="bi bi-x-circle me-1"></i>THÔNG TIN HỦY PHIẾU
                    </span>
                    <span class="text-muted" style="font-size: 13px;">
                        Phiếu này đã bị hủy, không làm phát sinh nhập kho hay tạo lô tồn thực tế.
                    </span>
                </div>
                <div class="row g-2 mt-1" style="font-size: 14px;">
                    <div class="col-md-6">
                        <strong class="text-secondary">Lý do hủy:</strong>
                        <div class="text-danger fw-semibold mt-1 p-2 bg-white rounded border border-danger-subtle">
                            {{ $phieuNhap->ly_do_huy ?: '—' }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <strong class="text-secondary">Người hủy:</strong>
                        <div class="mt-1 text-dark fw-medium">
                            {{ $phieuNhap->ten_nguoi_huy }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <strong class="text-secondary">Thời gian hủy:</strong>
                        <div class="mt-1 text-dark fw-medium">
                            {{ $phieuNhap->huy_luc ? $phieuNhap->huy_luc->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 2-Column Detail Grid: Thông tin Phiếu & Thông tin NCC --}}
    <div class="ncc-detail-grid mb-4">
        {{-- Left: Thông tin Phiếu nhập --}}
        <div>
            <div class="ncc-info-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-light text-muted border">Mã phiếu: #PN-{{ $phieuNhap->ma_phieu_nhap }}</span>
                    @if($phieuNhap->trang_thai === 'CONFIRMED')
                        <span class="badge bg-dark" style="font-size:12px; font-weight:500;">
                            <i class="bi bi-check-lg me-1"></i>Đã xác nhận
                        </span>
                    @elseif($phieuNhap->trang_thai === 'CANCELLED')
                        <span class="badge bg-secondary" style="font-size:12px; font-weight:500;">
                            <i class="bi bi-x-circle me-1"></i>Đã hủy
                        </span>
                    @else
                        <span class="badge" style="background:#f2f4f7; color:#344054; border:1px solid #d0d5dd; font-size:12px; font-weight:500;">
                            <i class="bi bi-pencil me-1"></i>Đang tạo (Draft)
                        </span>
                    @endif
                </div>

                <h2 class="ncc-info-title">
                    <i class="bi bi-file-earmark-text text-primary me-1"></i>Thông tin phiếu nhập
                </h2>
                <div class="ncc-info-meta">
                    Phiếu nhập hàng hóa kho nội bộ
                </div>

                <div class="ncc-info-item">
                    <span class="label">Mã phiếu</span>
                    <span class="value fw-bold">#PN-{{ $phieuNhap->ma_phieu_nhap }}</span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Ngày nhập</span>
                    <span class="value">
                        {{ $phieuNhap->ngay_nhap ? $phieuNhap->ngay_nhap->format('d/m/Y H:i') : '—' }}
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Nhân viên phụ trách</span>
                    <span class="value">
                        {{ $phieuNhap->nhanVien->ten_nhan_vien ?? '—' }}
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Thời gian tạo</span>
                    <span class="value text-muted" style="font-size:13px;">
                        {{ $phieuNhap->created_at ? $phieuNhap->created_at->format('d/m/Y H:i') : '—' }}
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Cập nhật lần cuối</span>
                    <span class="value text-muted" style="font-size:13px;">
                        {{ $phieuNhap->updated_at ? $phieuNhap->updated_at->format('d/m/Y H:i') : '—' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Right: Thông tin Nhà cung cấp (Bảo toàn snapshot) --}}
        <div>
            <div class="ncc-info-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-light text-muted border">Đối tác cung ứng</span>
                    @if($phieuNhap->ma_nha_cung_cap)
                        <a href="{{ route('admin.nha-cung-cap.show', $phieuNhap->ma_nha_cung_cap) }}" class="fw-semibold text-decoration-none" style="font-size:12px; color:#EC3D8F;">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Xem hồ sơ Nhà cung cấp
                        </a>
                    @else
                        <span class="text-muted" style="font-size:12px;">Snapshot dữ liệu</span>
                    @endif
                </div>

                <h2 class="ncc-info-title">
                    <i class="bi bi-building text-primary me-1"></i>Thông tin nhà cung cấp
                </h2>
                <div class="ncc-info-meta">
                    Dữ liệu lưu cùng phiếu nhập tại thời điểm giao dịch
                </div>

                <div class="ncc-info-item">
                    <span class="label">Tên nhà cung cấp</span>
                    <span class="value fw-bold text-dark">
                        {{ $phieuNhap->ten_nha_cung_cap ?: ($phieuNhap->nhaCungCap?->ten_nha_cung_cap ?? '—') }}
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Số điện thoại</span>
                    <span class="value">
                        {{ $phieuNhap->so_dien_thoai_ncc ?: ($phieuNhap->nhaCungCap?->so_dien_thoai ?? '—') }}
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Email</span>
                    <span class="value">
                        {{ $phieuNhap->email_ncc ?: ($phieuNhap->nhaCungCap?->email ?? '—') }}
                    </span>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Địa chỉ</span>
                    <span class="value">
                        {{ $phieuNhap->dia_chi_ncc ?: ($phieuNhap->nhaCungCap?->dia_chi ?? '—') }}
                    </span>
                </div>

                @if($phieuNhap->ma_nha_cung_cap)
                    <div class="ncc-info-item">
                        <span class="label">Hồ sơ danh mục</span>
                        <span class="value">
                            <a href="{{ route('admin.nha-cung-cap.show', $phieuNhap->ma_nha_cung_cap) }}" class="text-decoration-none fw-semibold" style="color:#EC3D8F;">
                                <i class="bi bi-link-45deg me-1"></i>#NCC-{{ $phieuNhap->ma_nha_cung_cap }} ({{ $phieuNhap->nhaCungCap->ten_nha_cung_cap ?? 'Chi tiết đối tác' }})
                            </a>
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Main Table: Danh sách mặt hàng nhập & Thông tin Lô --}}
    <div class="ncc-card">
        <div class="ncc-card-header">
            <h2 class="ncc-card-title">
                <i class="bi bi-boxes"></i>
                Danh sách mặt hàng nhập
                <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $tongSoLo }}</span>
            </h2>
        </div>

        <div class="table-responsive">
            <table class="ncc-table">
                <thead>
                    <tr>
                        <th>Mặt hàng</th>
                        <th>Mã SP</th>
                        <th>ĐVT</th>
                        <th class="text-center">SL nhập</th>
                        <th class="text-end">Giá nhập</th>
                        <th class="text-end">Thành tiền</th>
                        <th class="text-center">Mã lô</th>
                        <th class="text-center">Còn lại</th>
                        <th class="text-center">Chất lượng lô</th>
                        <th class="text-center">Hàng hỏng</th>
                        <th class="text-end" style="width: 100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($phieuNhap->chiTietNhaps as $ct)
                        @php
                            $thanhTien = (float)$ct->so_luong * (float)$ct->gia_nhap;
                            $dg = $ct->danhGiaMoiNhat;
                            $tongHong = $ct->baoCaoHangHongs->sum('so_luong_hong');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ $ct->sanPham->ten_san_pham ?? ('Sản phẩm #' . $ct->ma_san_pham) }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-muted border">#{{ $ct->ma_san_pham }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $ct->sanPham->don_vi_tinh ?? '—' }}</span>
                            </td>
                            <td class="text-center fw-bold">
                                {{ $ct->so_luong }}
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($ct->gia_nhap, 0, ',', '.') }} đ
                            </td>
                            <td class="text-end fw-bold" style="color:#1D2939;">
                                {{ number_format($thanhTien, 0, ',', '.') }} đ
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark">#CTN-{{ $ct->ma_chi_tiet_nhap }}</span>
                            </td>
                            <td class="text-center">
                                @if($ct->so_luong_con_lai > 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        {{ $ct->so_luong_con_lai }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        0 (Hết)
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($dg)
                                    @if($dg->ket_qua === 'DAT')
                                        <span class="badge-quality-dat">
                                            <i class="bi bi-check-circle me-1"></i>Đạt
                                        </span>
                                    @elseif($dg->ket_qua === 'CO_VAN_DE')
                                        <span class="badge-quality-covande" title="{{ $dg->ly_do }}">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Có vấn đề
                                        </span>
                                    @elseif($dg->ket_qua === 'KHONG_DAT')
                                        <span class="badge-quality-khongdat" title="{{ $dg->ly_do }}">
                                            <i class="bi bi-x-circle me-1"></i>Không đạt
                                        </span>
                                    @endif
                                @else
                                    <span class="badge-quality-chuadanhgia">
                                        Chưa đánh giá
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tongHong > 0 || ($dg && $dg->so_luong_loi > 0))
                                    <span class="text-danger fw-bold">
                                        {{ max($tongHong, $dg?->so_luong_loi ?? 0) }}
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($phieuNhap->trang_thai === 'CONFIRMED')
                                    <a href="{{ route('admin.lo-hang.show', $ct->ma_chi_tiet_nhap) }}"
                                       class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                                       style="font-size:12px; font-weight:600; padding:4px 8px; border-radius:6px;"
                                       title="Xem chi tiết lô">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                        <span>Xem lô</span>
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size:12px;">Chưa tạo lô</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="ncc-empty-state">
                                <div class="ncc-empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <p class="mb-0 fw-semibold">Phiếu nhập chưa có mặt hàng.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($tongSoLo > 0)
                    <tfoot>
                        <tr style="background:#FAFAFA; font-weight:700;">
                            <td colspan="3" class="text-end text-uppercase" style="font-size:13px; color:#475467;">
                                Tổng cộng:
                            </td>
                            <td class="text-center text-dark" style="font-size:14px;">
                                {{ number_format($tongSoLuong) }}
                            </td>
                            <td></td>
                            <td class="text-end" style="color:#EC3D8F; font-size:15px;">
                                {{ number_format($tongGiaTri, 0, ',', '.') }} đ
                            </td>
                            <td colspan="5"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Tổng kết Phiếu nhập (Summary Block) --}}
    <div class="ncc-card mt-4">
        <div class="ncc-card-header">
            <h2 class="ncc-card-title">
                <i class="bi bi-receipt"></i>
                Tổng kết phiếu nhập
            </h2>
        </div>
        <div class="p-3" style="max-width: 500px;">
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">Tổng số loại mặt hàng:</span>
                <strong class="text-dark">{{ $tongSoLo }} loại</strong>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">Tổng số lượng nhập:</span>
                <strong class="text-dark">{{ number_format($tongSoLuong) }}</strong>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span class="text-muted">Tổng giá trị phiếu nhập:</span>
                <strong class="fs-5" style="color:#EC3D8F;">{{ number_format($tongGiaTri, 0, ',', '.') }} đ</strong>
            </div>
        </div>
    </div>

</div>
@endsection
