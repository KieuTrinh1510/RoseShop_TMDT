@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/PhieuNhap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
@endpush

@section('content')
<div class="rs-management-page">

    {{-- 1. HEADER TRANG: Tiêu đề + Nút Tạo phiếu nhập mới (Đồng bộ Quản lý người dùng, Phiếu nhập) --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m7.5 4.27 9 5.15"/>
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                    <path d="m3.3 7 8.7 5 8.7-5"/>
                    <path d="M12 22V12"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Quản lý lô hàng</h1>
                <p class="rs-header-subtitle">Theo dõi danh sách các lô hàng, kiểm soát tồn theo lô và đánh giá chất lượng nguồn hàng</p>
            </div>
        </div>
        <a href="{{ route('phieu-nhap.index') }}" class="rs-btn-create">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
            </svg>
            <span>Tạo phiếu nhập mới</span>
        </a>
    </div>

    {{-- THÔNG BÁO HỆ THỐNG --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- 2. CARD CHÍNH: CHỨA BỘ LỌC TINH GỌN VÀ BẢNG DỮ LIỆU CHUẨN UX/UI ROSESHOP --}}
    <div class="rs-main-card">

        {{-- BỘ LỌC TINH GỌN FIT TRÊN 1 HÀNG HÀI HÒA --}}
        <form method="GET" action="{{ route('admin.lo-hang') }}" class="rs-filter-bar">
            
            <div class="rs-filter-item">
                <label class="rs-filter-label">Nhà cung cấp:</label>
                <select name="ma_nha_cung_cap" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả NCC --</option>
                    @foreach($nhaCungCaps as $ncc)
                        <option value="{{ $ncc->ma_nha_cung_cap }}" {{ (string) request('ma_nha_cung_cap') === (string) $ncc->ma_nha_cung_cap ? 'selected' : '' }}>
                            {{ $ncc->ten_nha_cung_cap }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="rs-filter-item">
                <label class="rs-filter-label">Đánh giá chất lượng:</label>
                <select name="ket_qua" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả kết quả --</option>
                    <option value="DAT" {{ request('ket_qua') === 'DAT' ? 'selected' : '' }}>Đạt</option>
                    <option value="CO_VAN_DE" {{ request('ket_qua') === 'CO_VAN_DE' ? 'selected' : '' }}>Có vấn đề</option>
                    <option value="KHONG_DAT" {{ request('ket_qua') === 'KHONG_DAT' ? 'selected' : '' }}>Không đạt</option>
                    <option value="CHUA_DANH_GIA" {{ request('ket_qua') === 'CHUA_DANH_GIA' ? 'selected' : '' }}>Chưa đánh giá</option>
                </select>
            </div>

            <div class="rs-filter-item">
                <label class="rs-filter-label">Tình trạng tồn kho:</label>
                <select name="ton_kho" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả --</option>
                    <option value="con_hang" {{ request('ton_kho') === 'con_hang' ? 'selected' : '' }}>Còn hàng</option>
                    <option value="het_hang" {{ request('ton_kho') === 'het_hang' ? 'selected' : '' }}>Hết hàng</option>
                </select>
            </div>

            <div class="rs-filter-search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Mã lô, mã SP, mã PN, tên SP..." autocomplete="off">
            </div>

            <button type="submit" class="rs-btn-filter-submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <span>Lọc</span>
            </button>

            @if(request()->anyFilled(['keyword', 'ma_nha_cung_cap', 'ket_qua', 'ton_kho']))
                <a href="{{ route('admin.lo-hang') }}" class="rs-btn-filter-reset" title="Bỏ lọc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    <span>Bỏ lọc</span>
                </a>
            @endif
        </form>

        {{-- BẢNG DANH SÁCH VỚI STICKY HEADER VÀ MÀU NỀN HỒNG #FDEAF3 --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th style="width: 90px; text-align: center;">Mã lô</th>
                        <th style="text-align: left;">Mặt hàng</th>
                        <th style="width: 130px; text-align: center;">Phiếu nhập</th>
                        <th style="text-align: left;">Nhà cung cấp</th>
                        <th style="width: 90px; text-align: center;">Số lượng</th>
                        <th style="width: 90px; text-align: center;">Còn lại</th>
                        <th style="width: 120px; text-align: right;">Giá nhập</th>
                        <th style="width: 110px; text-align: center;">Trạng thái</th>
                        <th style="width: 130px; text-align: center;">Chất lượng</th>
                        <th style="width: 130px; text-align: center;">Hao hụt & Lỗi QC</th>
                        <th style="width: 140px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loHangs as $lo)
                        @php
                            $dg = $lo->danhGiaMoiNhat;
                            $tongHongLo = $lo->baoCaoHangHongs->sum('so_luong_hong');
                        @endphp
                        <tr>
                            <td style="text-align:center;">
                                <a href="{{ route('admin.lo-hang.show', $lo->ma_chi_tiet_nhap) }}" class="fw-bold text-decoration-none" style="color:#EC3D8F;" title="Mở chi tiết lô">
                                    #CTN-{{ $lo->ma_chi_tiet_nhap }}
                                </a>
                            </td>

                            <td>
                                <div class="fw-semibold text-dark">{{ $lo->sanPham->ten_san_pham ?? 'Sản phẩm #' . $lo->ma_san_pham }}</div>
                                <small class="text-muted" style="font-size:11.5px;">Mã SP: #{{ $lo->ma_san_pham }}</small>
                            </td>

                            <td style="text-align:center;">
                                <a href="{{ route('phieu-nhap.show', $lo->ma_phieu_nhap) }}" class="fw-medium text-decoration-none" style="color:#475467;" title="Xem phiếu nhập">
                                    #PN-{{ $lo->ma_phieu_nhap }}
                                </a>
                                <div style="font-size:11px; color:#667085;">
                                    {{ $lo->created_at ? $lo->created_at->format('d/m/Y') : '—' }}
                                </div>
                            </td>

                            <td>
                                @if($lo->phieuNhap?->nhaCungCap)
                                    <a href="{{ route('admin.nha-cung-cap.show', $lo->phieuNhap->nhaCungCap->ma_nha_cung_cap) }}" class="text-decoration-none fw-semibold" style="color:#EC3D8F;">
                                        {{ $lo->phieuNhap->nhaCungCap->ten_nha_cung_cap }}
                                    </a>
                                @else
                                    <span class="text-muted">{{ $lo->phieuNhap->ten_nha_cung_cap ?? '—' }}</span>
                                @endif
                            </td>

                            <td style="text-align:center;" class="fw-semibold">
                                {{ $lo->so_luong }}
                            </td>

                            <td style="text-align:center;">
                                @if($lo->so_luong_con_lai > 0)
                                    <span class="badge-confirmed">
                                        {{ $lo->so_luong_con_lai }}
                                    </span>
                                @else
                                    <span class="badge-cancelled">
                                        0 (Hết)
                                    </span>
                                @endif
                            </td>

                            <td style="text-align:right;" class="fw-semibold">
                                {{ number_format($lo->gia_nhap, 0, ',', '.') }} ₫
                            </td>

                            <td style="text-align:center;">
                                @if($lo->so_luong_con_lai > 0)
                                    <span class="badge-confirmed"><i class="bi bi-check-lg me-1"></i>CÒN HÀNG</span>
                                @else
                                    <span class="badge-cancelled"><i class="bi bi-x-circle me-1"></i>HẾT HÀNG</span>
                                @endif
                            </td>

                            <td style="text-align:center;">
                                @if($dg)
                                    @if($dg->ket_qua === 'DAT')
                                        <span class="badge-confirmed">
                                            <i class="bi bi-check-circle me-1"></i>Đạt
                                        </span>
                                    @elseif($dg->ket_qua === 'CO_VAN_DE')
                                        <span class="badge-draft" title="{{ $dg->ly_do }}">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Có vấn đề
                                        </span>
                                    @elseif($dg->ket_qua === 'KHONG_DAT')
                                        <span class="badge-cancelled" title="{{ $dg->ly_do }}">
                                            <i class="bi bi-x-circle me-1"></i>Không đạt
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted" style="font-size:12.5px;">Chưa đánh giá</span>
                                @endif
                            </td>

                            <td style="font-size: 12px; line-height: 1.4;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted" title="Lỗi QC (không trừ tồn kho)">Lỗi QC:</span>
                                    @if($dg && $dg->so_luong_loi > 0)
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold px-1 py-0">{{ $dg->so_luong_loi }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted" title="Hàng hỏng đã trừ kho">Trừ hỏng:</span>
                                    @if($tongHongLo > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-1 py-0">{{ $tongHongLo }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </div>
                            </td>

                            {{-- CỘT THAO TÁC ĐỒNG BỘ: NÚT XEM, ĐÁNH GIÁ VÀ BÁO HỎNG LÔ HÀNG --}}
                            <td style="text-align: center; white-space: nowrap;">
                                <div class="rs-action-group">
                                    {{-- 1. Nút Xem chi tiết lô --}}
                                    <a href="{{ route('admin.lo-hang.show', $lo->ma_chi_tiet_nhap) }}"
                                       class="rs-action-btn detail"
                                       title="Xem chi tiết lô hàng">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    @if($lo->so_luong_con_lai > 0)
                                        {{-- 2. Nút Đánh giá chất lượng --}}
                                        <button type="button"
                                                class="rs-action-btn edit"
                                                title="{{ $dg ? 'Sửa đánh giá chất lượng lô' : 'Đánh giá chất lượng lô hàng' }}"
                                                onclick="openModalDanhGia({{ $lo->ma_chi_tiet_nhap }}, '{{ addslashes($lo->sanPham->ten_san_pham ?? '') }}', {{ $lo->so_luong }}, '{{ $dg?->ket_qua ?? '' }}', {{ $dg?->so_luong_loi ?? 0 }}, '{{ addslashes($dg?->ly_do ?? '') }}', '{{ addslashes($dg?->ghi_chu ?? '') }}')">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                                                <path d="m9 12 2 2 4-4"/>
                                            </svg>
                                        </button>

                                        {{-- 3. Nút Báo hỏng lô hàng --}}
                                        <button type="button"
                                                class="rs-action-btn delete"
                                                title="Báo hỏng / hao hụt kho cho lô hàng này"
                                                onclick="openModalBaoHongIndex({{ $lo->ma_chi_tiet_nhap }}, {{ $lo->ma_san_pham }}, '{{ addslashes($lo->sanPham->ten_san_pham ?? '') }}', {{ $lo->ma_phieu_nhap }}, {{ $lo->so_luong_con_lai }})">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Không tìm thấy lô hàng nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN PHIẾU NHẬP & QUẢN LÝ NGƯỜI DÙNG --}}
        @if($loHangs->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 20, 50, 100] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage]) }}" {{ request('per_page', 10) == $perPage ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rs-records-count">
                        {{ $loHangs->firstItem() ?? 0 }}-{{ $loHangs->lastItem() ?? 0 }} / {{ $loHangs->total() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($loHangs->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $loHangs->currentPage();
                            $lastPage = $loHangs->lastPage();
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
                                <li class="page-item"><a class="page-link" href="{{ $loHangs->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($loHangs->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $loHangs->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

    </div>

</div>

{{-- MODAL POPUP ĐÁNH GIÁ CHẤT LƯỢNG LÔ HÀNG --}}
<div class="rs-modal" id="modalDanhGiaLo" onclick="if(event.target === this) dongModalDanhGiaLo();">
    <div class="rs-modal-dialog" style="max-width: 620px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title">
                <i class="bi bi-shield-check me-1"></i>Đánh giá chất lượng lô hàng
            </h3>
            <button type="button" class="rs-modal-close" onclick="dongModalDanhGiaLo()">&times;</button>
        </div>
        <div class="rs-modal-body">
            <form id="formDanhGiaLo" method="POST" action="">
                @csrf
                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">Mã lô:</span>
                        <strong id="modal_ma_lo" style="color:#EC3D8F;">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">Mặt hàng:</span>
                        <strong id="modal_ten_sp">—</strong>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:13px;">
                        <span class="text-muted">Số lượng ban đầu:</span>
                        <strong id="modal_so_luong_lo" style="color:#EC3D8F;">—</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">Kết quả đánh giá <span class="text-danger">*</span></label>
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
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Số lượng phát hiện lỗi/hỏng (nếu có)
                        <small class="text-muted fw-normal">(Tối đa: <span id="modal_max_loi">0</span> sp)</small>
                    </label>
                    <input type="number" name="so_luong_loi" id="modal_inp_so_luong_loi" class="form-control" min="0" value="0" style="border-radius:8px; padding:8px 12px;">
                    <small class="text-muted" style="font-size:11px;">
                        * Lưu ý: Đánh giá chất lượng phục vụ kiểm định nguồn hàng, KHÔNG tự trừ tồn kho vận hành.
                    </small>
                </div>

                <div class="mb-3" id="group_ly_do" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Lý do đánh giá <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="ly_do" id="modal_inp_ly_do" class="form-control" placeholder="VD: Hoa héo dập, sai quy cách..." style="border-radius:8px; padding:8px 12px;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">Ghi chú thêm</label>
                    <textarea name="ghi_chu" id="modal_inp_ghi_chu" class="form-control" rows="2" placeholder="Ghi chú chi tiết nếu có..." style="border-radius:8px; padding:8px 12px;"></textarea>
                </div>

                <div class="p-2 bg-light rounded-3 d-flex justify-content-between text-muted" style="font-size:12px;">
                    <span><i class="bi bi-person me-1"></i> Người đánh giá: <strong>{{ auth()->user()->nhanVien->ten_nhan_vien ?? auth()->user()->ten_dang_nhap }}</strong></span>
                    <span><i class="bi bi-clock me-1"></i> Tự động ghi nhận</span>
                </div>
            </form>
        </div>
        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="dongModalDanhGiaLo()">Hủy bỏ</button>
            <button type="submit" form="formDanhGiaLo" class="rs-btn-submit">
                <i class="bi bi-check-lg me-1"></i> Lưu kết quả đánh giá
            </button>
        </div>
    </div>
</div>

{{-- MODAL POPUP BÁO HỎNG LÔ HÀNG (UC4.4 CHUẨN NGHIỆP VỤ SAN-PHAM.BAO-HANG-HONG) --}}
<div class="rs-modal" id="modalBaoHongLo" onclick="if(event.target === this) dongModalBaoHong();">
    <div class="rs-modal-dialog" style="max-width: 620px;">
        <div class="rs-modal-header" style="background: #FEF3F2 !important; border-bottom: 1px solid #FECDCA !important;">
            <h3 class="rs-modal-title" style="color: #B42318 !important;">
                <i class="bi bi-trash3 me-1"></i>Báo hỏng / Hao hụt kho cho lô hàng
            </h3>
            <button type="button" class="rs-modal-close" style="color: #B42318 !important; border-color: #FECDCA !important;" onclick="dongModalBaoHong()">&times;</button>
        </div>
        <div class="rs-modal-body">
            <form action="{{ route('san-pham.bao-hang-hong') }}" method="POST" id="formBaoHongIndex">
                @csrf
                <input type="hidden" name="ma_san_pham" id="bh_ma_san_pham" value="">
                <input type="hidden" name="ma_chi_tiet_nhap" id="bh_ma_chi_tiet_nhap" value="">
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">Mặt hàng:</span>
                        <strong id="bh_ten_sp" class="text-dark">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">Mã lô nhập:</span>
                        <strong id="bh_ma_lo" style="color:#EC3D8F;">—</strong>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:13px;">
                        <span class="text-muted">Tồn khả dụng hiện tại:</span>
                        <strong id="bh_ton_kha_dung" class="text-danger">—</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Số lượng hỏng / hao hụt <span class="text-danger">*</span>
                        <small class="text-muted fw-normal">(Tối đa: <span id="bh_max_sl">0</span> sp)</small>
                    </label>
                    <input type="number" name="so_luong_hong" id="bh_inp_so_luong_hong" class="form-control" min="1" required style="border-radius:8px; padding:8px 12px;" placeholder="Nhập số lượng hàng hỏng...">
                    <small class="text-danger mt-1 d-block" style="font-size:11px;">
                        * Lưu ý: Số lượng này sẽ được <strong>TRỪ TRỰC TIẾP</strong> khỏi tồn kho thực tế của lô hàng.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">
                        Lý do báo hỏng <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="ly_do" id="bh_inp_ly_do" class="form-control" placeholder="Ví dụ: Hoa héo dập, mốc hỏng, bể vỡ..." required style="border-radius:8px; padding:8px 12px;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px; color:#344054;">Ghi chú thêm</label>
                    <textarea name="ghi_chu" id="bh_inp_ghi_chu" class="form-control" rows="2" placeholder="Ghi chú chi tiết nguyên nhân hỏng..." style="border-radius:8px; padding:8px 12px;"></textarea>
                </div>
            </form>
        </div>
        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="dongModalBaoHong()">Hủy bỏ</button>
            <button type="submit" form="formBaoHongIndex" class="rs-btn-submit" style="background:#B42318 !important; border-color:#B42318 !important;">
                <i class="bi bi-trash3 me-1"></i> Xác nhận báo hỏng
            </button>
        </div>
    </div>
</div>

@push('scripts')
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

        document.getElementById('modalDanhGiaLo').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function dongModalDanhGiaLo() {
        document.getElementById('modalDanhGiaLo').classList.remove('show');
        document.body.style.overflow = '';
    }

    function openModalBaoHongIndex(maChiTietNhap, maSanPham, tenSp, maPhieuNhap, tonKhaDung) {
        document.getElementById('bh_ma_san_pham').value = maSanPham;
        document.getElementById('bh_ma_chi_tiet_nhap').value = maChiTietNhap;
        document.getElementById('bh_ten_sp').textContent = tenSp;
        document.getElementById('bh_ma_lo').textContent = `#CTN-${maChiTietNhap} (Phiếu #PN-${maPhieuNhap})`;
        document.getElementById('bh_ton_kha_dung').textContent = `${tonKhaDung} sản phẩm`;
        document.getElementById('bh_max_sl').textContent = tonKhaDung;
        
        const inpSl = document.getElementById('bh_inp_so_luong_hong');
        inpSl.max = tonKhaDung;
        inpSl.value = '';
        
        document.getElementById('bh_inp_ly_do').value = '';
        document.getElementById('bh_inp_ghi_chu').value = '';

        document.getElementById('modalBaoHongLo').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function dongModalBaoHong() {
        document.getElementById('modalBaoHongLo').classList.remove('show');
        document.body.style.overflow = '';
    }
</script>
@endpush
@endsection
