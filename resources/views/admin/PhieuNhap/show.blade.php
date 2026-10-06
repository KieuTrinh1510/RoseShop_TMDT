@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/PhieuNhap.css') }}">
@endpush

@section('content')
<div class="pn-detail-container">
<div class="container py-4">

    {{-- 1. HEADER TRANG: Tiêu đề chuẩn hóa RoseShop + 1 Nút Quay lại danh sách duy nhất --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Chi tiết phiếu nhập #PN-{{ $phieuNhap->ma_phieu_nhap }}</h1>
                <p class="rs-header-subtitle">
                    Ngày nhập: {{ $phieuNhap->ngay_nhap ? $phieuNhap->ngay_nhap->format('d/m/Y H:i') : '—' }} 
                    — Trạng thái: 
                    @if($phieuNhap->trang_thai === 'CONFIRMED')
                        <span class="badge badge-confirmed"><i class="bi bi-check-lg me-1"></i>Đã xác nhận</span>
                    @elseif($phieuNhap->trang_thai === 'CANCELLED')
                        <span class="badge badge-cancelled"><i class="bi bi-x-circle me-1"></i>Đã hủy</span>
                    @else
                        <span class="badge badge-draft"><i class="bi bi-pencil me-1"></i>Đang tạo (Draft)</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Nút Quay lại danh sách phiếu nhập duy nhất, rõ ràng, không gây rối luồng --}}
        <div>
            <a href="{{ route('phieu-nhap.index') }}" class="rs-btn-back">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                <span>Quay lại danh sách phiếu nhập</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $tongSoLo = $phieuNhap->chiTietNhaps->count();
        $tongSoLuong = $phieuNhap->chiTietNhaps->sum('so_luong');
        $tongGiaTri = $phieuNhap->chiTietNhaps->sum(fn($ct) => (float)$ct->so_luong * (float)$ct->gia_nhap);
    @endphp

    {{-- 2. Top Summary KPIs: Thống kê số lượng & giá trị --}}
    <div class="pn-kpi-grid">
        <div class="pn-kpi-card">
            <span class="pn-kpi-label">Tổng mặt hàng</span>
            <span class="pn-kpi-val">{{ $tongSoLo }}</span>
            <small class="text-muted" style="font-size:12px; margin-top:4px;">Số loại sản phẩm</small>
        </div>
        <div class="pn-kpi-card">
            <span class="pn-kpi-label">Tổng số lượng</span>
            <span class="pn-kpi-val">{{ number_format($tongSoLuong) }}</span>
            <small class="text-muted" style="font-size:12px; margin-top:4px;">Sản phẩm nhập</small>
        </div>
        <div class="pn-kpi-card highlight">
            <span class="pn-kpi-label">Tổng giá trị phiếu nhập</span>
            <span class="pn-kpi-val">{{ number_format($tongGiaTri, 0, ',', '.') }} đ</span>
            <small class="text-muted" style="font-size:12px; margin-top:4px;">Tổng kinh phí nhập hàng</small>
        </div>
    </div>

    {{-- Khối thông tin hủy nếu phiếu bị cancel --}}
    @if($phieuNhap->trang_thai === 'CANCELLED')
        <div class="card mb-4 border-danger" style="border-width: 1px; background: #fff1f2; border-radius: 14px;">
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

    {{-- 3. 2-Column Detail Grid: Thông tin Phiếu & Thông tin NCC --}}
    <div class="pn-detail-grid">
        {{-- Left: Thông tin Phiếu nhập --}}
        <div>
            <div class="pn-info-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-light text-muted border">Mã phiếu: #PN-{{ $phieuNhap->ma_phieu_nhap }}</span>
                    @if($phieuNhap->trang_thai === 'CONFIRMED')
                        <span class="badge badge-confirmed">
                            <i class="bi bi-check-lg me-1"></i>Đã xác nhận
                        </span>
                    @elseif($phieuNhap->trang_thai === 'CANCELLED')
                        <span class="badge badge-cancelled">
                            <i class="bi bi-x-circle me-1"></i>Đã hủy
                        </span>
                    @else
                        <span class="badge badge-draft">
                            <i class="bi bi-pencil me-1"></i>Đang tạo (Draft)
                        </span>
                    @endif
                </div>

                <h2 class="pn-info-title">
                    <i class="bi bi-file-earmark-text text-danger me-1"></i>Thông tin phiếu nhập
                </h2>
                <div class="pn-info-meta">
                    Phiếu nhập hàng hóa kho nội bộ RoseShop
                </div>

                <div class="pn-info-item">
                    <span class="label">Mã phiếu</span>
                    <span class="value fw-bold text-danger">#PN-{{ $phieuNhap->ma_phieu_nhap }}</span>
                </div>

                <div class="pn-info-item">
                    <span class="label">Ngày nhập</span>
                    <span class="value">
                        {{ $phieuNhap->ngay_nhap ? $phieuNhap->ngay_nhap->format('d/m/Y H:i') : '—' }}
                    </span>
                </div>

                <div class="pn-info-item">
                    <span class="label">Nhân viên phụ trách</span>
                    <span class="value fw-semibold">
                        {{ $phieuNhap->nhanVien->ten_nhan_vien ?? '—' }}
                    </span>
                </div>

                <div class="pn-info-item">
                    <span class="label">Thời gian tạo</span>
                    <span class="value text-muted" style="font-size:13px;">
                        {{ $phieuNhap->created_at ? $phieuNhap->created_at->format('d/m/Y H:i') : '—' }}
                    </span>
                </div>

                <div class="pn-info-item">
                    <span class="label">Cập nhật lần cuối</span>
                    <span class="value text-muted" style="font-size:13px;">
                        {{ $phieuNhap->updated_at ? $phieuNhap->updated_at->format('d/m/Y H:i') : '—' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Right: Thông tin Nhà cung cấp --}}
        <div>
            <div class="pn-info-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge bg-light text-muted border">Đối tác cung ứng</span>
                    <span class="text-muted" style="font-size:12px;">Dữ liệu giao dịch</span>
                </div>

                <h2 class="pn-info-title">
                    <i class="bi bi-building text-danger me-1"></i>Thông tin nhà cung cấp
                </h2>
                <div class="pn-info-meta">
                    Thông tin nhà cung cấp tại thời điểm lập phiếu nhập
                </div>

                <div class="pn-info-item">
                    <span class="label">Tên nhà cung cấp</span>
                    <span class="value fw-bold text-dark">
                        {{ $phieuNhap->ten_nha_cung_cap ?: ($phieuNhap->nhaCungCap?->ten_nha_cung_cap ?? '—') }}
                    </span>
                </div>

                <div class="pn-info-item">
                    <span class="label">Số điện thoại</span>
                    <span class="value">
                        {{ $phieuNhap->so_dien_thoai_ncc ?: ($phieuNhap->nhaCungCap?->so_dien_thoai ?? '—') }}
                    </span>
                </div>

                <div class="pn-info-item">
                    <span class="label">Email</span>
                    <span class="value">
                        {{ $phieuNhap->email_ncc ?: ($phieuNhap->nhaCungCap?->email ?? '—') }}
                    </span>
                </div>

                <div class="pn-info-item">
                    <span class="label">Địa chỉ</span>
                    <span class="value">
                        {{ $phieuNhap->dia_chi_ncc ?: ($phieuNhap->nhaCungCap?->dia_chi ?? '—') }}
                    </span>
                </div>

                @if($phieuNhap->ma_nha_cung_cap)
                    <div class="pn-info-item">
                        <span class="label">Hồ sơ đối tác</span>
                        <span class="value">
                            <a href="{{ route('admin.nha-cung-cap.show', $phieuNhap->ma_nha_cung_cap) }}" class="text-decoration-none fw-semibold" style="color:#f43f5e;" title="Xem thông tin chi tiết nhà cung cấp">
                                <i class="bi bi-link-45deg me-1"></i>#NCC-{{ $phieuNhap->ma_nha_cung_cap }} ({{ $phieuNhap->nhaCungCap->ten_nha_cung_cap ?? 'Xem chi tiết' }})
                            </a>
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 4. Bảng Mặt hàng nhập & Thông tin Lô --}}
    <div class="card pn-card mb-4" style="border: 1px solid #fecdd3; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(244,63,94,0.06);">
        <div class="card-header pn-card-header" style="background:#fff1f2; border-bottom:1px solid #fecdd3; padding:14px 20px; display:flex; justify-content:space-between; align-items:center;">
            <span class="fw-bold" style="color:#e11d48; font-size:15px; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-boxes"></i>Danh sách mặt hàng nhập
            </span>
            <span class="badge" style="background:#ffe4e6; color:#e11d48; border:1px solid #fecdd3; font-size:12px; font-weight:600; padding:5px 12px; border-radius:20px;">
                {{ $tongSoLo }} mặt hàng
            </span>
        </div>

        {{-- Thanh cuộn dọc & ngang riêng biệt cho bảng danh sách mặt hàng --}}
        <div class="rs-table-container" style="max-height: 480px; overflow-y: auto; overflow-x: auto; border: none; border-radius: 0;">
            <table class="table rs-table mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 180px;">Mặt hàng</th>
                        <th class="text-center" style="min-width: 80px;">Mã SP</th>
                        <th class="text-center" style="min-width: 80px;">ĐVT</th>
                        <th class="text-center" style="min-width: 100px;">SL nhập</th>
                        <th class="text-end" style="min-width: 120px;">Giá nhập</th>
                        <th class="text-end" style="min-width: 130px;">Thành tiền</th>
                        <th class="text-center" style="min-width: 100px;">Mã lô</th>
                        <th class="text-center" style="min-width: 90px;">Còn lại</th>
                        <th class="text-center" style="min-width: 120px;">Chất lượng lô</th>
                        <th class="text-center" style="min-width: 100px;">Hàng hỏng</th>
                        <th class="text-center" style="min-width: 110px;">Thao tác</th>
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
                            <td class="text-center">
                                <span class="badge bg-light text-muted border">#{{ $ct->ma_san_pham }}</span>
                            </td>
                            <td class="text-center">
                                <span class="text-muted">{{ $ct->sanPham->don_vi_tinh ?? '—' }}</span>
                            </td>
                            <td class="text-center fw-bold">
                                {{ $ct->so_luong }}
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($ct->gia_nhap, 0, ',', '.') }} đ
                            </td>
                            <td class="text-end fw-bold" style="color:#0f172a;">
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
                            <td class="text-center">
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
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Phiếu nhập chưa có mặt hàng.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($tongSoLo > 0)
                    <tfoot>
                        <tr style="background:#fff1f2; font-weight:700;">
                            <td colspan="3" class="text-end text-uppercase" style="font-size:13.5px; color:#e11d48; font-weight:700;">
                                TỔNG CỘNG:
                            </td>
                            <td class="text-center" style="font-size:14px; color:#e11d48; font-weight:700;">
                                {{ number_format($tongSoLuong) }}
                            </td>
                            <td></td>
                            <td class="text-end" style="color:#e11d48; font-size:15px; font-weight:700;">
                                {{ number_format($tongGiaTri, 0, ',', '.') }} đ
                            </td>
                            <td colspan="5"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- 5. Tổng kết Phiếu nhập (Summary Block) Đồng bộ màu sắc RoseShop --}}
    <div class="card pn-card" style="max-width: 480px; border: 1px solid #fecdd3; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(244,63,94,0.06);">
        <div class="card-header pn-card-header" style="background: #fff1f2; border-bottom: 1px solid #fecdd3; padding: 14px 20px;">
            <span class="fw-bold" style="color: #e11d48; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-receipt-cutoff"></i>Tổng kết phiếu nhập
            </span>
        </div>
        <div class="p-3" style="background: #ffffff;">
            <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: #ffe4e6 !important; font-size: 13.5px;">
                <span class="text-muted">Tổng số loại mặt hàng:</span>
                <strong class="text-dark">{{ $tongSoLo }} loại</strong>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: #ffe4e6 !important; font-size: 13.5px;">
                <span class="text-muted">Tổng số lượng nhập:</span>
                <strong class="text-dark">{{ number_format($tongSoLuong) }}</strong>
            </div>
            <div class="d-flex justify-content-between py-2 align-items-center" style="font-size: 13.5px;">
                <span class="text-muted fw-semibold">Tổng giá trị phiếu nhập:</span>
                <strong style="color: #e11d48; font-weight: 800; font-size: 18px;">{{ number_format($tongGiaTri, 0, ',', '.') }} đ</strong>
            </div>
        </div>
    </div>

</div>
</div>
@endsection
