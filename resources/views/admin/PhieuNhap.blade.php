@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/Admin/PhieuNhap.css') }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
@endpush

@section('content')

<div class="rs-management-page">

    {{-- 1. HEADER TRANG: Tiêu đề + Nút Thêm phiếu nhập (Đồng bộ chuẩn Quản lý người dùng, nhân viên, shipper) --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Quản lý nhập hàng</h1>
                <p class="rs-header-subtitle">Lập và theo dõi các phiếu nhập hàng từ nhà vườn và nhà cung cấp</p>
            </div>
        </div>

        {{-- Nút Thêm phiếu nhập mới trên Header: Bấm vào mở Modal popup đè lên màn hình --}}
        <button type="button" class="rs-btn-create" onclick="openModalThemPhieu()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
            </svg>
            <span>Thêm phiếu nhập</span>
        </button>
    </div>

    {{-- THÔNG BÁO HỆ THỐNG --}}
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" style="border-radius:12px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- 2. CARD CHÍNH: CHỨA BỘ LỌC TINH GỌN VÀ BẢNG CÓ THANH CUỘN RIÊNG --}}
    <div class="rs-main-card">

        {{-- ===== BỘ LỌC / TÌM KIẾM FIT TRÊN 1 HÀNG HÀI HÒA ===== --}}
        <form method="GET" action="{{ route('phieu-nhap.index') }}" class="rs-filter-bar">
            
            {{-- Lọc trạng thái --}}
            <div class="rs-filter-item">
                <label class="rs-filter-label">Trạng thái:</label>
                <select name="trang_thai" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả --</option>
                    <option value="DRAFT" {{ request('trang_thai') == 'DRAFT' ? 'selected' : '' }}>
                        Đang tạo (Draft)
                    </option>
                    <option value="CONFIRMED" {{ request('trang_thai') == 'CONFIRMED' ? 'selected' : '' }}>
                        Đã xác nhận
                    </option>
                    <option value="CANCELLED" {{ request('trang_thai') == 'CANCELLED' ? 'selected' : '' }}>
                        Đã hủy
                    </option>
                </select>
            </div>

            {{-- Từ ngày --}}
            <div class="rs-filter-item">
                <label class="rs-filter-label">Từ ngày:</label>
                <input type="date" name="tu_ngay" class="rs-filter-date" value="{{ request('tu_ngay') }}">
            </div>

            {{-- Đến ngày --}}
            <div class="rs-filter-item">
                <label class="rs-filter-label">Đến ngày:</label>
                <input type="date" name="den_ngay" class="rs-filter-date" value="{{ request('den_ngay') }}">
            </div>

            {{-- Tìm nhà cung cấp (Ô tìm kiếm fit linh hoạt với icon kính lúp) --}}
            <div class="rs-filter-search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="tim_ncc" value="{{ request('tim_ncc') }}" placeholder="Nhập tên hoặc SĐT nhà cung cấp..." autocomplete="off">
            </div>

            {{-- Nút Lọc --}}
            <button type="submit" class="rs-btn-filter-submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <span>Lọc</span>
            </button>

            {{-- Nút Bỏ lọc --}}
            @if(request()->anyFilled(['trang_thai', 'tu_ngay', 'den_ngay', 'tim_ncc']))
                <a href="{{ route('phieu-nhap.index') }}" class="rs-btn-filter-reset" title="Đặt lại bộ lọc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    <span>Bỏ lọc</span>
                </a>
            @endif
        </form>

        {{-- ===== BẢNG DANH SÁCH CÓ THANH CUỘN RIÊNG & STICKY HEADER ===== --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th style="width: 90px; text-align: center;">Mã PN</th>
                        <th style="width: 140px; text-align: center;">Ngày nhập</th>
                        <th style="width: 170px; text-align: left;">Nhân viên</th>
                        <th style="width: 220px; text-align: left;">Nhà cung cấp</th>
                        <th style="width: 150px; text-align: center; white-space: nowrap;">SĐT NCC</th>
                        <th style="width: 140px; text-align: center;">Trạng thái</th>
                        <th style="width: 160px; text-align: center;">Hành động</th>
                    </tr>
                </thead>
                <tbody id="tbodyPhieuNhap">
                    @forelse($danhSachPhieuNhap as $pn)
                    <tr class="dong-phieu-nhap">
                        <td style="text-align:center;">
                            <a href="{{ route('phieu-nhap.show', $pn->ma_phieu_nhap) }}" class="fw-bold text-decoration-none" style="color:#EC3D8F;" title="Mở trang chi tiết">
                                #PN-{{ $pn->ma_phieu_nhap }}
                            </a>
                        </td>

                        <td style="text-align:center;">
                            {{ $pn->ngay_nhap ? \Carbon\Carbon::parse($pn->ngay_nhap)->format('d/m/Y H:i') : '—' }}
                        </td>

                        <td class="fw-medium">{{ $pn->nhanVien ? $pn->nhanVien->ten_nhan_vien : '—' }}</td>

                        <td class="fw-semibold text-dark">{{ $pn->nhaCungCap->ten_nha_cung_cap ?? $pn->ten_nha_cung_cap ?? '—' }}</td>

                        <td style="text-align:center;">{{ $pn->nhaCungCap->so_dien_thoai ?? $pn->so_dien_thoai_ncc ?? '—' }}</td>

                        {{-- Trạng thái --}}
                        <td style="text-align:center;">
                            @if($pn->trang_thai === 'DRAFT')
                                <span class="badge-draft">
                                    <i class="bi bi-pencil me-1"></i>Đang tạo
                                </span>
                            @elseif($pn->trang_thai === 'CONFIRMED')
                                <span class="badge-confirmed">
                                    <i class="bi bi-check-lg me-1"></i>Đã xác nhận
                                </span>
                            @elseif($pn->trang_thai === 'CANCELLED')
                                <span class="badge-cancelled">
                                    <i class="bi bi-x-circle me-1"></i>Đã hủy
                                </span>
                            @endif
                        </td>

                        {{-- Hành động --}}
                        <td style="text-align: center; white-space: nowrap;">
                            <div class="rs-action-group">

                                {{-- NÚT XEM CHI TIẾT (luôn hiện) --}}
                                <button type="button" class="rs-action-btn detail"
                                        onclick="xemChiTiet({{ $pn->ma_phieu_nhap }})"
                                        title="Xem chi tiết phiếu nhập">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>

                                @if($pn->trang_thai === 'DRAFT')

                                    {{-- NÚT SỬA (mở Modal Popup sửa đè lên) --}}
                                    <button type="button" class="rs-action-btn edit"
                                            onclick="chuyenCheDeSua({{ $pn->ma_phieu_nhap }})"
                                            title="Sửa phiếu nhập">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="m15 5 4 4"/>
                                        </svg>
                                    </button>

                                    {{-- NÚT XÁC NHẬN --}}
                                    <button type="button" class="rs-action-btn confirm"
                                            onclick="moModalXacNhan({{ $pn->ma_phieu_nhap }})"
                                            title="Xác nhận nhập kho">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </button>

                                    {{-- NÚT HỦY PHIẾU --}}
                                    <button type="button" class="rs-action-btn cancel"
                                            onclick="moModalHuyPhieu({{ $pn->ma_phieu_nhap }})"
                                            title="Hủy phiếu nhập">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="m15 9-6 6"/>
                                            <path d="m9 9 6 6"/>
                                        </svg>
                                    </button>

                                    {{-- NÚT XÓA --}}
                                    <button type="button" class="rs-action-btn delete"
                                            onclick="moModalXoa({{ $pn->ma_phieu_nhap }})"
                                            title="Xóa phiếu nhập">
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
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Không tìm thấy phiếu nhập nào phù hợp với bộ lọc.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG CLIENT-SIDE ĐỒNG BỘ --}}
        @if($danhSachPhieuNhap->count() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select id="rsPerPageSelect" class="rs-per-page-select" onchange="doiSoHangMoiTrang(this.value)">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div id="rsRecordsCount" class="rs-records-count">
                        1-{{ min(10, $danhSachPhieuNhap->count()) }} / {{ $danhSachPhieuNhap->count() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul id="rsPaginationList" class="rs-pagination">
                        {{-- Render bởi JS --}}
                    </ul>
                </div>
            </div>
        @endif

    </div>

</div>


{{-- ==========================================================================
     MODAL POPUP: THÊM / SỬA PHIẾU NHẬP (ĐÈ LÊN MÀN HÌNH, KHÔNG ĐẨY TRANG)
     ========================================================================== --}}
<div class="rs-modal" id="modalPhieuNhap" onclick="if(event.target === this) dongModalPhieuNhap();">
    <div class="rs-modal-dialog">
        <div class="rs-modal-header" id="cardFormHeader">
            <h3 class="rs-modal-title" id="tieuDeForm">
                <i class="bi bi-plus-circle me-1"></i>Thêm Phiếu Nhập Mới
            </h3>
            <button type="button" class="rs-modal-close" onclick="dongModalPhieuNhap()">&times;</button>
        </div>

        <div class="rs-modal-body">
            <form id="formChung" action="{{ route('phieu-nhap.store') }}" method="POST">
                @csrf
                <div id="methodField"></div>

                {{-- ===== THÔNG TIN PHIẾU NHẬP ===== --}}
                <p class="tieu-de-chi-tiet"><i class="bi bi-file-earmark-text me-1"></i>Thông tin phiếu nhập</p>
                <div class="row g-3 mb-3">

                    {{-- Ngày nhập --}}
                    <div class="col-md-3">
                        <label class="form-label">Ngày nhập <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="ngay_nhap" id="inp_ngay_nhap"
                               class="form-control" value="{{ old('ngay_nhap') }}" required>
                    </div>

                    {{-- Nhân viên vận hành --}}
                    <div class="col-md-3">
                        <label class="form-label">Nhân viên vận hành <span class="text-danger">*</span></label>
                        <select name="ma_nhan_vien" id="inp_nhan_vien" class="form-select" required>
                            <option value="">-- Chọn nhân viên --</option>
                            @foreach($danhSachNhanVien as $nv)
                                <option value="{{ $nv->ma_nhan_vien }}"
                                    {{ old('ma_nhan_vien') == $nv->ma_nhan_vien ? 'selected' : '' }}>
                                    {{ $nv->ten_nhan_vien }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tên nhà cung cấp (Dropdown) --}}
                    <div class="col-md-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Nhà cung cấp <span class="text-danger">*</span></span>
                            <a href="{{ route('admin.nha-cung-cap.create') }}" target="_blank" class="text-decoration-none" style="font-size:0.78rem; color:#EC3D8F;">
                                <i class="bi bi-plus-circle me-1"></i>Thêm mới
                            </a>
                        </label>
                        <select name="ma_nha_cung_cap" id="inp_ncc" class="form-select" required>
                            <option value="">-- Chọn nhà cung cấp --</option>
                            @foreach($danhSachNhaCungCap as $ncc)
                                <option value="{{ $ncc->ma_nha_cung_cap }}"
                                        data-sdt="{{ $ncc->so_dien_thoai }}"
                                        data-email="{{ $ncc->email }}"
                                        data-diachi="{{ $ncc->dia_chi }}"
                                        {{ old('ma_nha_cung_cap', $selectedNccId ?? null) == $ncc->ma_nha_cung_cap ? 'selected' : '' }}>
                                    {{ $ncc->ten_nha_cung_cap }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Số điện thoại NCC --}}
                    <div class="col-md-3">
                        <label class="form-label">Số điện thoại NCC</label>
                        <input type="text" id="inp_sdt_ncc"
                               class="form-control bg-light"
                               placeholder="Tự điền khi chọn NCC" readonly>
                    </div>

                    {{-- Email NCC --}}
                    <div class="col-md-3">
                        <label class="form-label">Email NCC</label>
                        <input type="email" id="inp_email_ncc"
                               class="form-control bg-light"
                               placeholder="Tự điền khi chọn NCC" readonly>
                    </div>

                    {{-- Địa chỉ NCC --}}
                    <div class="col-md-9">
                        <label class="form-label">Địa chỉ NCC</label>
                        <input type="text" id="inp_dia_chi_ncc"
                               class="form-control bg-light"
                               placeholder="Tự điền khi chọn NCC" readonly>
                    </div>

                </div>

                {{-- ===== DANH SÁCH SẢN PHẨM NHẬP ===== --}}
                <p class="tieu-de-chi-tiet mt-2">
                    <i class="bi bi-box-seam me-1"></i>Chi tiết nhập hàng
                    <small class="text-muted fw-normal ms-2" style="font-size:0.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Giá bán: để trống → hệ thống tự tính = Giá nhập + 50%
                    </small>
                </p>

                <div id="danhSachDongChiTiet">
                    {{-- Dòng sản phẩm đầu tiên --}}
                    <div class="dong-chi-tiet" id="dong_0">
                        <div class="row g-2 align-items-end">

                            {{-- Chọn sản phẩm --}}
                            <div class="col-md-4">
                                <label class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                                <select name="san_pham[]" class="form-select" required>
                                    <option value="">-- Chọn sản phẩm --</option>
                                    @foreach($danhSachSanPham as $sp)
                                        <option value="{{ $sp->ma_san_pham }}">
                                            {{ $sp->ten_san_pham }} (Tồn: {{ $sp->ton_kho_lo }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Số lượng --}}
                            <div class="col-md-2">
                                <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                                <input type="number" name="so_luong[]" class="form-control"
                                       min="1" placeholder="0" required>
                            </div>

                            {{-- Giá nhập --}}
                            <div class="col-md-2">
                                <label class="form-label">Giá nhập (₫) <span class="text-danger">*</span></label>
                                <input type="number" name="gia_nhap[]" class="form-control inp-gia-nhap"
                                       min="0" placeholder="0" required>
                            </div>

                            {{-- Giá bán --}}
                            <div class="col-md-3">
                                <label class="form-label">
                                    Giá bán (₫)
                                    <span class="text-muted" style="font-size:0.75rem; font-weight:normal;">— để trống = +50%</span>
                                </label>
                                <input type="number" name="gia_ban[]" class="form-control inp-gia-ban"
                                       min="0" placeholder="Tự tính nếu để trống">
                            </div>

                            <div class="col-md-1"></div>

                        </div>
                    </div>
                </div>

                {{-- Nút thêm dòng sản phẩm --}}
                <div class="mt-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="themDongSanPham()">
                        <i class="bi bi-plus-circle me-1"></i>Thêm sản phẩm
                    </button>
                </div>

            </form>
        </div>

        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="dongModalPhieuNhap()">
                Hủy bỏ
            </button>
            <button type="submit" id="btnSubmit" class="rs-btn-submit" form="formChung">
                <i id="iconSubmit" class="bi bi-plus-lg me-1"></i>
                <span id="textSubmit">Tạo phiếu nhập</span>
            </button>
        </div>
    </div>
</div>


{{-- =====================================================================
     MODAL XEM CHI TIẾT NHANH PHIẾU NHẬP
===================================================================== --}}
<div class="modal fade" id="modalChiTiet" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width: 1140px; width: 95%;">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #fecdd3; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);">

            <div class="modal-header" style="background: #fff1f2; border-bottom: 1px solid #fecdd3; padding: 16px 24px;">
                <h5 class="modal-title" id="tieuDeModal" style="color: #D82677; font-weight: 700; font-size: 17px; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-file-earmark-text me-2"></i>Chi tiết phiếu nhập
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(27%) sepia(85%) saturate(3015%) hue-rotate(334deg) brightness(91%) contrast(97%);"></button>
            </div>

            <div class="modal-body" id="noiDungModal" style="padding: 20px 24px;">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border text-danger me-2" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
                    <span>Đang tải thông tin chi tiết...</span>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-end" style="background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 12px 24px;">
                <a href="#" id="linkTrangChiTietModal" class="rs-btn-back" style="font-size: 13px; height: 38px;">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Mở trang chi tiết đầy đủ
                </a>
            </div>
        </div>
    </div>
</div>


{{-- =====================================================================
     MODAL XÁC NHẬN HỦY PHIẾU NHẬP (UC4.2)
===================================================================== --}}
<div class="modal fade" id="modalHuyPhieu" tabindex="-1" aria-labelledby="tieuDeModalHuy" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formHuyPhieu" method="POST" action="" onsubmit="return validateFormHuyPhieu()">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="tieuDeModalHuy">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Xác nhận hủy phiếu nhập
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 mb-3" style="font-size:0.9rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hành động này <strong>không thể hoàn tác</strong>. Phiếu nhập chuyển sang trạng thái <strong>Đã hủy</strong> và không làm phát sinh nhập kho.
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Mã phiếu: <span id="spanMaPhieuHuy" class="text-danger"></span></label>
                    </div>
                    <div class="mb-3">
                        <label for="inp_ly_do_huy" class="form-label fw-bold">
                            Lý do hủy <span class="text-danger">*</span>
                        </label>
                        <textarea name="ly_do_huy" id="inp_ly_do_huy" class="form-control" rows="3"
                                  placeholder="Nhập lý do hủy phiếu nhập này..." required></textarea>
                        <div id="errorLyDoHuy" class="text-danger mt-1" style="display:none; font-size:0.85rem;">
                            Vui lòng nhập lý do hủy!
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Xác nhận hủy phiếu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =====================================================================
     MODAL XÁC NHẬN NHẬP KHO (CONFIRM)
===================================================================== --}}
<div class="modal fade" id="modalXacNhanPhieu" tabindex="-1" aria-labelledby="tieuDeModalXacNhan" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #bbf7d0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
            <form id="formXacNhanModal" method="POST" action="">
                @csrf
                <div class="modal-header" style="background: #ecfdf5; border-bottom: 1px solid #a7f3d0; padding: 16px 22px;">
                    <h5 class="modal-title" id="tieuDeModalXacNhan" style="color: #059669; font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-check2-circle fs-5"></i>Xác nhận nhập kho phiếu nhập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 22px;">
                    <div class="alert alert-success py-2 mb-3" style="font-size: 0.9rem; background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hành động này sẽ <strong>cộng số lượng hàng vào tồn kho</strong> thực tế của hệ thống và chuyển trạng thái sang <strong>Đã xác nhận</strong>.
                    </div>
                    <p class="mb-1" style="font-size: 14.5px;">
                        Bạn có chắc chắn muốn xác nhận phiếu nhập <strong id="spanMaPhieuXacNhan" style="color: #059669; font-size: 16px;"></strong> không?
                    </p>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 12px 22px;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius: 8px; padding: 6px 14px;">Hủy bỏ</button>
                    <button type="submit" class="btn btn-success btn-sm" style="border-radius: 8px; padding: 6px 16px; font-weight: 600; background: #16a34a; border-color: #16a34a;">
                        <i class="bi bi-check-lg me-1"></i>Đồng ý xác nhận
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =====================================================================
     MODAL XÁC NHẬN XÓA PHIẾU NHẬP (DELETE)
===================================================================== --}}
<div class="modal fade" id="modalXoaPhieu" tabindex="-1" aria-labelledby="tieuDeModalXoa" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #fecdd3; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
            <form id="formXoaModal" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header" style="background: #fff1f2; border-bottom: 1px solid #fecdd3; padding: 16px 22px;">
                    <h5 class="modal-title" id="tieuDeModalXoa" style="color: #D82677; font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-trash3 fs-5"></i>Xác nhận xóa phiếu nhập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 22px;">
                    <div class="alert alert-danger py-2 mb-3" style="font-size: 0.9rem; background: #fff1f2; border-color: #fecdd3; color: #9f1239;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Hành động này <strong>không thể hoàn tác</strong>. Dữ liệu phiếu nhập nháp sẽ bị xóa vĩnh viễn khỏi hệ thống.
                    </div>
                    <p class="mb-1" style="font-size: 14.5px;">
                        Bạn có chắc chắn muốn xóa phiếu nhập <strong id="spanMaPhieuXoa" style="color: #D82677; font-size: 16px;"></strong> không?
                    </p>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 12px 22px;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius: 8px; padding: 6px 14px;">Hủy bỏ</button>
                    <button type="submit" class="btn btn-danger btn-sm" style="border-radius: 8px; padding: 6px 16px; font-weight: 600; background: #D82677; border-color: #D82677;">
                        <i class="bi bi-trash me-1"></i>Đồng ý xóa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function moModalXacNhan(id) {
        document.getElementById('spanMaPhieuXacNhan').innerText = `#PN-${id}`;
        document.getElementById('formXacNhanModal').action = `/phieu-nhap/${id}/confirm`;
        const modal = new bootstrap.Modal(document.getElementById('modalXacNhanPhieu'));
        modal.show();
    }

    function moModalXoa(id) {
        document.getElementById('spanMaPhieuXoa').innerText = `#PN-${id}`;
        document.getElementById('formXoaModal').action = `/phieu-nhap/${id}`;
        const modal = new bootstrap.Modal(document.getElementById('modalXoaPhieu'));
        modal.show();
    }

    function moModalHuyPhieu(maPhieuNhap) {
        document.getElementById('spanMaPhieuHuy').innerText = `#PN-${maPhieuNhap}`;
        document.getElementById('inp_ly_do_huy').value = '';
        document.getElementById('errorLyDoHuy').style.display = 'none';
        document.getElementById('formHuyPhieu').action = `/phieu-nhap/${maPhieuNhap}/cancel`;
        const modal = new bootstrap.Modal(document.getElementById('modalHuyPhieu'));
        modal.show();
    }

    function validateFormHuyPhieu() {
        const val = document.getElementById('inp_ly_do_huy').value.trim();
        if (!val) {
            document.getElementById('errorLyDoHuy').style.display = 'block';
            return false;
        }
        return true;
    }

    const danhSachSanPham = @json($danhSachSanPham);
    let demDong = 1;

    function themDongSanPham() {
        const id = 'dong_' + demDong++;
        const container = document.getElementById('danhSachDongChiTiet');

        let optionsSanPham = '<option value="">-- Chọn sản phẩm --</option>';
        danhSachSanPham.forEach(sp => {
            optionsSanPham += `<option value="${sp.ma_san_pham}">${sp.ten_san_pham} (Tồn: ${sp.ton_kho_lo})</option>`;
        });

        const dongHtml = `
            <div class="dong-chi-tiet" id="${id}">
                <button type="button" class="btn-xoa-dong" onclick="xoaDong('${id}')" title="Xóa dòng này">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                        <select name="san_pham[]" class="form-select" required>
                            ${optionsSanPham}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                        <input type="number" name="so_luong[]" class="form-control" min="1" placeholder="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Giá nhập (₫) <span class="text-danger">*</span></label>
                        <input type="number" name="gia_nhap[]" class="form-control inp-gia-nhap" min="0" placeholder="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            Giá bán (₫)
                            <span class="text-muted" style="font-size:0.75rem; font-weight:normal;">— để trống = +50%</span>
                        </label>
                        <input type="number" name="gia_ban[]" class="form-control inp-gia-ban" min="0" placeholder="Tự tính nếu để trống">
                    </div>
                    <div class="col-md-1"></div>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', dongHtml);
    }

    function xoaDong(id) {
        const dong = document.getElementById(id);
        if (dong) dong.remove();
    }

    // Modal popup Thêm phiếu nhập
    function openModalThemPhieu() {
        resetFormVeTaoMoi();
        document.getElementById('modalPhieuNhap').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function dongModalPhieuNhap() {
        document.getElementById('modalPhieuNhap').classList.remove('show');
        document.body.style.overflow = '';
    }

    function resetFormVeTaoMoi() {
        const container = document.getElementById('danhSachDongChiTiet');
        demDong = 0;

        let optionsSanPham = '<option value="">-- Chọn sản phẩm --</option>';
        danhSachSanPham.forEach(sp => {
            optionsSanPham += `<option value="${sp.ma_san_pham}">${sp.ten_san_pham} (Tồn: ${sp.ton_kho_lo})</option>`;
        });

        container.innerHTML = `
            <div class="dong-chi-tiet" id="dong_0">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                        <select name="san_pham[]" class="form-select" required>${optionsSanPham}</select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                        <input type="number" name="so_luong[]" class="form-control" min="1" placeholder="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Giá nhập (₫) <span class="text-danger">*</span></label>
                        <input type="number" name="gia_nhap[]" class="form-control inp-gia-nhap" min="0" placeholder="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            Giá bán (₫)
                            <span class="text-muted" style="font-size:0.75rem; font-weight:normal;">— để trống = +50%</span>
                        </label>
                        <input type="number" name="gia_ban[]" class="form-control inp-gia-ban" min="0" placeholder="Tự tính nếu để trống">
                    </div>
                    <div class="col-md-1"></div>
                </div>
            </div>`;
        demDong = 1;

        document.getElementById('formChung').reset();
        document.getElementById('inp_sdt_ncc').value = '';
        document.getElementById('inp_email_ncc').value = '';
        document.getElementById('inp_dia_chi_ncc').value = '';
        document.getElementById('formChung').action = '{{ route('phieu-nhap.store') }}';
        document.getElementById('methodField').innerHTML = '';

        document.getElementById('tieuDeForm').innerHTML =
            '<i class="bi bi-plus-circle me-1"></i>Thêm Phiếu Nhập Mới';

        document.getElementById('btnSubmit').className = 'rs-btn-submit';
        document.getElementById('iconSubmit').className = 'bi bi-plus-lg me-1';
        document.getElementById('textSubmit').textContent = 'Tạo phiếu nhập';
    }

    function chuyenCheDeSua(id) {
        fetch(`/phieu-nhap/${id}/edit-data`)
            .then(res => res.json())
            .then(pn => {
                document.getElementById('inp_ngay_nhap').value = pn.ngay_nhap
                    ? pn.ngay_nhap.replace(' ', 'T').substring(0, 16) : '';
                document.getElementById('inp_nhan_vien').value = pn.ma_nhan_vien ?? '';
                const nccSelect = document.getElementById('inp_ncc');
                const nccId = pn.ma_nha_cung_cap ?? '';
                if (nccId) {
                    let optExists = Array.from(nccSelect.options).some(o => o.value == nccId);
                    if (!optExists && pn.ten_nha_cung_cap) {
                        const opt = new Option(`${pn.ten_nha_cung_cap} (Đang ngừng hợp tác)`, nccId, true, true);
                        opt.setAttribute('data-sdt', pn.so_dien_thoai_ncc ?? '');
                        opt.setAttribute('data-email', pn.email_ncc ?? '');
                        opt.setAttribute('data-diachi', pn.dia_chi_ncc ?? '');
                        nccSelect.add(opt);
                    }
                    nccSelect.value = nccId;
                } else {
                    nccSelect.value = '';
                }

                document.getElementById('inp_sdt_ncc').value     = pn.so_dien_thoai_ncc ?? (pn.nha_cung_cap ? pn.nha_cung_cap.so_dien_thoai : '') ?? '';
                document.getElementById('inp_email_ncc').value   = pn.email_ncc ?? (pn.nha_cung_cap ? pn.nha_cung_cap.email : '') ?? '';
                document.getElementById('inp_dia_chi_ncc').value = pn.dia_chi_ncc ?? (pn.nha_cung_cap ? pn.nha_cung_cap.dia_chi : '') ?? '';

                const container = document.getElementById('danhSachDongChiTiet');
                container.innerHTML = '';
                demDong = 0;

                if (pn.chi_tiet_nhaps && pn.chi_tiet_nhaps.length > 0) {
                    pn.chi_tiet_nhaps.forEach((ct, index) => {
                        const dongId = 'dong_' + demDong++;

                        let opts = '<option value="">-- Chọn sản phẩm --</option>';
                        danhSachSanPham.forEach(sp => {
                            const sel = sp.ma_san_pham == ct.ma_san_pham ? 'selected' : '';
                            opts += `<option value="${sp.ma_san_pham}" ${sel}>${sp.ten_san_pham} (Tồn: ${sp.ton_kho_lo})</option>`;
                        });

                        const nutXoa = index > 0
                            ? `<button type="button" class="btn-xoa-dong" onclick="xoaDong('${dongId}')"><i class="bi bi-x-circle-fill"></i></button>`
                            : '';

                        const dongHtml = `
                            <div class="dong-chi-tiet" id="${dongId}">
                                ${nutXoa}
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                                        <select name="san_pham[]" class="form-select" required>${opts}</select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                                        <input type="number" name="so_luong[]" class="form-control"
                                               min="1" value="${ct.so_luong}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Giá nhập (₫) <span class="text-danger">*</span></label>
                                        <input type="number" name="gia_nhap[]" class="form-control inp-gia-nhap"
                                               min="0" value="${ct.gia_nhap}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Giá bán (₫)
                                            <span class="text-muted" style="font-size:0.75rem; font-weight:normal;">— để trống = +50%</span>
                                        </label>
                                        <input type="number" name="gia_ban[]" class="form-control inp-gia-ban"
                                               min="0" value="${ct.gia_ban ?? ''}" placeholder="Tự tính nếu để trống">
                                    </div>
                                    <div class="col-md-1"></div>
                                </div>
                            </div>`;
                        container.insertAdjacentHTML('beforeend', dongHtml);
                    });
                }

                document.getElementById('formChung').action = `/phieu-nhap/${id}`;
                document.getElementById('methodField').innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';

                document.getElementById('tieuDeForm').innerHTML =
                    `<i class="bi bi-pencil-fill me-1"></i>Sửa Phiếu Nhập #PN-${id}`;

                document.getElementById('btnSubmit').className = 'rs-btn-submit';
                document.getElementById('iconSubmit').className = 'bi bi-save me-1';
                document.getElementById('textSubmit').textContent = 'Lưu thay đổi';

                // Mở Modal Popup đè lên màn hình
                document.getElementById('modalPhieuNhap').classList.add('show');
                document.body.style.overflow = 'hidden';
            })
            .catch(() => alert('Không thể tải dữ liệu phiếu nhập!'));
    }

    function xacNhanPhieu(id) {
        moModalXacNhan(id);
    }

    function xoaPhieu(id) {
        moModalXoa(id);
    }

    function xemChiTiet(id) {
        document.getElementById('tieuDeModal').innerHTML =
            `<i class="bi bi-file-earmark-text me-2"></i>Chi tiết phiếu nhập #PN-${id}`;
        document.getElementById('noiDungModal').innerHTML =
            `<div class="text-center py-4 text-muted">
                <div class="spinner-border text-danger me-2" role="status" style="width:1.5rem; height:1.5rem;"></div>
                <span>Đang tải thông tin chi tiết phiếu nhập...</span>
             </div>`;
        
        const linkFull = document.getElementById('linkTrangChiTietModal');
        if (linkFull) {
            linkFull.href = `/phieu-nhap/${id}`;
        }

        const modal = new bootstrap.Modal(document.getElementById('modalChiTiet'));
        modal.show();

        fetch(`/phieu-nhap/${id}`)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const bangChiTiet = doc.querySelector('table');
                const subTitleElem = doc.querySelector('.rs-header-subtitle');
                const subTitleHtml = subTitleElem ? subTitleElem.innerHTML : `Mã phiếu: #PN-${id}`;

                if (bangChiTiet) {
                    document.getElementById('noiDungModal').innerHTML = `
                        <div class="mb-3 p-3" style="background: linear-gradient(135deg, #fff1f2 0%, #ffffff 100%); border:1px solid #fecdd3; border-radius:12px; font-size:13.5px; box-shadow: 0 1px 3px rgba(244,63,94,0.06);">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted fw-semibold">Mã phiếu:</span>
                                    <span style="color:#D82677; font-weight:800; font-size:15px;">#PN-${id}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    ${subTitleHtml}
                                </div>
                            </div>
                        </div>
                        <div class="rs-modal-table-container">
                            ${bangChiTiet.outerHTML}
                        </div>`;
                    return;
                }

                fetch(`/phieu-nhap/${id}/edit-data`)
                    .then(res => res.json())
                    .then(pn => {
                        let rows = '';
                        let tongCong = 0;
                        if (pn.chi_tiet_nhaps && pn.chi_tiet_nhaps.length > 0) {
                            pn.chi_tiet_nhaps.forEach((ct, i) => {
                                const sp = danhSachSanPham.find(s => s.ma_san_pham == ct.ma_san_pham);
                                const tenSp = sp ? sp.ten_san_pham : `SP #${ct.ma_san_pham}`;
                                const thanhTien = ct.so_luong * ct.gia_nhap;
                                tongCong += thanhTien;
                                rows += `
                                    <tr>
                                        <td class="text-center">${i + 1}</td>
                                        <td><strong>${tenSp}</strong></td>
                                        <td class="text-center fw-bold">${ct.so_luong}</td>
                                        <td class="text-end">${Number(ct.gia_nhap).toLocaleString('vi-VN')} ₫</td>
                                        <td class="text-end fw-bold" style="color:#D82677;">${thanhTien.toLocaleString('vi-VN')} ₫</td>
                                    </tr>`;
                            });
                        }

                        document.getElementById('noiDungModal').innerHTML = `
                            <div class="row g-2 mb-3 p-3 bg-light rounded" style="font-size:13.5px; border:1px solid #e2e8f0;">
                                <div class="col-md-3"><strong>Mã phiếu:</strong> <span class="text-danger">#PN-${pn.ma_phieu_nhap}</span></div>
                                <div class="col-md-3"><strong>Ngày nhập:</strong> ${pn.ngay_nhap ?? '—'}</div>
                                <div class="col-md-3"><strong>Nhà cung cấp:</strong> ${pn.ten_nha_cung_cap ?? '—'}</div>
                                <div class="col-md-3"><strong>Trạng thái:</strong> <span class="badge ${pn.trang_thai === 'CONFIRMED' ? 'badge-confirmed' : 'badge-draft'}">${pn.trang_thai}</span></div>
                            </div>
                            <div class="rs-modal-table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:60px;">#</th>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">Số lượng</th>
                                            <th class="text-end">Giá nhập</th>
                                            <th class="text-end">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rows}</tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end text-uppercase" style="color:#D82677; font-weight:700;">Tổng cộng:</td>
                                            <td class="text-end" style="color:#D82677; font-weight:700; font-size:15px;">${tongCong.toLocaleString('vi-VN')} ₫</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>`;
                    });
            })
            .catch(() => {
                document.getElementById('noiDungModal').innerHTML =
                    '<div class="text-danger text-center py-4"><i class="bi bi-exclamation-triangle me-2"></i>Không thể tải dữ liệu chi tiết phiếu nhập!</div>';
            });
    }

    // Tự động điền SĐT, Email, Địa chỉ khi chọn Nhà cung cấp
    document.getElementById('inp_ncc')?.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('inp_sdt_ncc').value     = opt.getAttribute('data-sdt') || '';
            document.getElementById('inp_email_ncc').value   = opt.getAttribute('data-email') || '';
            document.getElementById('inp_dia_chi_ncc').value = opt.getAttribute('data-diachi') || '';
        } else {
            document.getElementById('inp_sdt_ncc').value     = '';
            document.getElementById('inp_email_ncc').value   = '';
            document.getElementById('inp_dia_chi_ncc').value = '';
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const inpNcc = document.getElementById('inp_ncc');
        if (inpNcc && inpNcc.value) {
            inpNcc.dispatchEvent(new Event('change'));
        }
        @if($errors->any() || !empty($selectedNccId))
            openModalThemPhieu();
        @endif

        // Khởi tạo phân trang Client-side
        capNhatPhanTrang();
    });

    // =========================================================================
    // Client-side Pagination: Phân trang đồng bộ không cần sửa Backend
    // =========================================================================
    let trangHienTai = 1;
    let soHangMoiTrang = 10;

    function doiSoHangMoiTrang(val) {
        soHangMoiTrang = parseInt(val, 10);
        trangHienTai = 1;
        capNhatPhanTrang();
    }

    function denTrang(page) {
        const danhSachDong = Array.from(document.querySelectorAll('#tbodyPhieuNhap tr.dong-phieu-nhap'));
        const tongTrang = Math.ceil(danhSachDong.length / soHangMoiTrang);
        if (page < 1 || page > tongTrang) return;
        trangHienTai = page;
        capNhatPhanTrang();
    }

    function capNhatPhanTrang() {
        const danhSachDong = Array.from(document.querySelectorAll('#tbodyPhieuNhap tr.dong-phieu-nhap'));
        const tongSoDong = danhSachDong.length;
        if (tongSoDong === 0) return;

        const tongTrang = Math.ceil(tongSoDong / soHangMoiTrang);
        const batDau = (trangHienTai - 1) * soHangMoiTrang;
        const ketThuc = batDau + soHangMoiTrang;

        danhSachDong.forEach((dong, idx) => {
            if (idx >= batDau && idx < ketThuc) {
                dong.style.display = '';
            } else {
                dong.style.display = 'none';
            }
        });

        const hienThiDen = Math.min(ketThuc, tongSoDong);
        const countElem = document.getElementById('rsRecordsCount');
        if (countElem) countElem.innerText = `${batDau + 1}-${hienThiDen} / ${tongSoDong} bản ghi`;

        const pagList = document.getElementById('rsPaginationList');
        if (!pagList) return;

        let html = '';
        const prevDisabled = trangHienTai === 1 ? 'pointer-events:none; opacity:0.5;' : 'cursor:pointer;';
        html += `<li style="display:inline-block;">
                    <a onclick="denTrang(${trangHienTai - 1})" class="page-link" style="${prevDisabled}">&lsaquo;</a>
                 </li>`;

        let pages = [];
        if (tongTrang <= 3) {
            for (let i = 1; i <= tongTrang; i++) pages.push(i);
        } else {
            if (trangHienTai === 1) {
                pages = [1, '...', tongTrang];
            } else if (trangHienTai === tongTrang) {
                pages = [1, '...', tongTrang];
            } else {
                pages = [1];
                if (trangHienTai > 2) pages.push('...');
                pages.push(trangHienTai);
                if (trangHienTai < tongTrang - 1) pages.push('...');
                pages.push(tongTrang);
            }
        }

        pages.forEach(p => {
            if (p === '...') {
                html += `<li class="page-item disabled"><span class="page-link ellipsis">...</span></li>`;
            } else {
                const activeClass = p === trangHienTai ? 'active' : '';
                html += `<li class="page-item ${activeClass}">
                            <a onclick="denTrang(${p})" class="page-link">${p}</a>
                         </li>`;
            }
        });

        const nextDisabled = trangHienTai === tongTrang ? 'pointer-events:none; opacity:0.5;' : 'cursor:pointer;';
        html += `<li style="display:inline-block;">
                    <a onclick="denTrang(${trangHienTai + 1})" class="page-link" style="${nextDisabled}">&rsaquo;</a>
                 </li>`;

        pagList.innerHTML = html;
    }
</script>
@endpush
