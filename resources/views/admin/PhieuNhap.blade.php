@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
<link rel="stylesheet" href="{{ asset('css/Admin/PhieuNhap.css') }}">
@endpush

@section('content')

<div class="ncc-container">

    {{-- 1. HEADER TRANG: Đồng bộ hoàn toàn với Quản lý nhà cung cấp --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Quản lý phiếu nhập</h1>
            <p class="rs-page-subtitle">Lập và theo dõi các phiếu nhập hàng từ nhà vườn và nhà cung cấp.</p>
        </div>
        <div class="ncc-header-actions">
            <button type="button" class="ncc-btn-primary" onclick="openModalThemPhieu()">
                <i class="bi bi-plus-lg"></i>
                <span>Thêm phiếu nhập</span>
            </button>
        </div>
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

    {{-- 2. KHU VỰC BỘ LỌC & TÌM KIẾM: Đồng bộ chuẩn ncc-filter-box, gọn gàng, ít màu --}}
    <div class="ncc-filter-box">
        <form method="GET" action="{{ route('phieu-nhap.index') }}">
            <div class="pn-filter-grid">
                
                {{-- Lọc trạng thái --}}
                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:#475467;">Trạng thái</label>
                    <select name="trang_thai" class="form-select" onchange="this.form.submit()">
                        <option value="">— Tất cả trạng thái —</option>
                        <option value="DRAFT" {{ request('trang_thai') == 'DRAFT' ? 'selected' : '' }}>
                            Đang tạo
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
                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:#475467;">Từ ngày</label>
                    <input type="date" name="tu_ngay" class="form-control" value="{{ request('tu_ngay') }}">
                </div>

                {{-- Đến ngày --}}
                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:#475467;">Đến ngày</label>
                    <input type="date" name="den_ngay" class="form-control" value="{{ request('den_ngay') }}">
                </div>

                {{-- Tìm nhà cung cấp --}}
                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:#475467;">Tìm kiếm nhà cung cấp</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="tim_ncc" value="{{ request('tim_ncc') }}" class="form-control"
                               placeholder="Tìm theo tên hoặc SĐT NCC..." autocomplete="off">
                    </div>
                </div>

                {{-- Nút Thao tác lọc --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="ncc-btn-primary" style="padding: 8px 16px;">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    @if(request()->anyFilled(['trang_thai', 'tu_ngay', 'den_ngay', 'tim_ncc']))
                        <a href="{{ route('phieu-nhap.index') }}" class="ncc-btn-secondary" style="padding: 8px 14px;" title="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- 3. CARD BẢNG DANH SÁCH: Đồng bộ cấu trúc ncc-card & ncc-table --}}
    <div class="ncc-card">
        <div class="ncc-card-header">
            <h2 class="ncc-card-title">
                <i class="bi bi-list-ul"></i>
                Danh sách phiếu nhập
                <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $danhSachPhieuNhap->count() }}</span>
            </h2>
        </div>

        <div class="table-responsive">
            <table class="ncc-table">
                <thead>
                    <tr>
                        <th style="width: 85px; text-align: center;">Mã PN</th>
                        <th style="width: 140px; text-align: center;">Ngày nhập</th>
                        <th>Nhân viên</th>
                        <th>Nhà cung cấp</th>
                        <th style="width: 130px; text-align: center;">SĐT NCC</th>
                        <th style="width: 140px; text-align: center;">Trạng thái</th>
                        <th class="text-end" style="width: 180px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="tbodyPhieuNhap">
                    @forelse($danhSachPhieuNhap as $pn)
                    <tr class="dong-phieu-nhap">
                        {{-- Mã PN --}}
                        <td style="text-align: center;">
                            <a href="{{ route('phieu-nhap.show', $pn->ma_phieu_nhap) }}" class="pn-code-link" title="Mở trang chi tiết">
                                #PN-{{ $pn->ma_phieu_nhap }}
                            </a>
                        </td>

                        {{-- Ngày nhập --}}
                        <td style="text-align: center;" class="text-muted">
                            {{ $pn->ngay_nhap ? \Carbon\Carbon::parse($pn->ngay_nhap)->format('d/m/Y H:i') : '—' }}
                        </td>

                        {{-- Nhân viên --}}
                        <td class="fw-medium text-dark">
                            {{ $pn->nhanVien ? $pn->nhanVien->ten_nhan_vien : '—' }}
                        </td>

                        {{-- Nhà cung cấp --}}
                        <td>
                            @if($pn->nhaCungCap)
                                <a href="{{ route('admin.nha-cung-cap.show', $pn->nhaCungCap->ma_nha_cung_cap) }}"
                                   class="fw-semibold text-dark text-decoration-none">
                                    {{ $pn->nhaCungCap->ten_nha_cung_cap }}
                                </a>
                            @else
                                <span class="fw-semibold text-dark">{{ $pn->ten_nha_cung_cap ?? '—' }}</span>
                            @endif
                        </td>

                        {{-- SĐT NCC --}}
                        <td style="text-align: center;">
                            @php
                                $sdt = $pn->nhaCungCap->so_dien_thoai ?? $pn->so_dien_thoai_ncc;
                            @endphp
                            @if($sdt)
                                <a href="tel:{{ $sdt }}" class="text-muted text-decoration-none">
                                    <i class="bi bi-telephone me-1 text-secondary"></i>{{ $sdt }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Trạng thái: Badge pill chuẩn hệ thống --}}
                        <td style="text-align: center;">
                            @if($pn->trang_thai === 'DRAFT')
                                <span class="ncc-badge draft">
                                    <span class="ncc-badge-dot"></span> Đang tạo
                                </span>
                            @elseif($pn->trang_thai === 'CONFIRMED')
                                <span class="ncc-badge active">
                                    <span class="ncc-badge-dot"></span> Đã xác nhận
                                </span>
                            @elseif($pn->trang_thai === 'CANCELLED')
                                <span class="ncc-badge inactive">
                                    <span class="ncc-badge-dot"></span> Đã hủy
                                </span>
                            @endif
                        </td>

                        {{-- Cột hành động: Icon buttons nhỏ gọn 32px, 1 hàng duy nhất --}}
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 justify-content-end">

                                {{-- NÚT XEM CHI TIẾT (luôn hiện) --}}
                                <button type="button" class="ncc-action-btn view"
                                        onclick="xemChiTiet({{ $pn->ma_phieu_nhap }})"
                                        title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </button>

                                @if($pn->trang_thai === 'DRAFT')
                                    {{-- NÚT SỬA --}}
                                    <button type="button" class="ncc-action-btn edit"
                                            onclick="chuyenCheDeSua({{ $pn->ma_phieu_nhap }})"
                                            title="Sửa phiếu nhập">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    {{-- NÚT XÁC NHẬN --}}
                                    <button type="button" class="ncc-action-btn confirm"
                                            onclick="moModalXacNhan({{ $pn->ma_phieu_nhap }})"
                                            title="Xác nhận nhập kho">
                                        <i class="bi bi-check2-circle"></i>
                                    </button>

                                    {{-- NÚT HỦY PHIẾU --}}
                                    <button type="button" class="ncc-action-btn cancel"
                                            onclick="moModalHuyPhieu({{ $pn->ma_phieu_nhap }})"
                                            title="Hủy phiếu nhập">
                                        <i class="bi bi-x-circle"></i>
                                    </button>

                                    {{-- NÚT XÓA --}}
                                    <button type="button" class="ncc-action-btn delete"
                                            onclick="moModalXoa({{ $pn->ma_phieu_nhap }})"
                                            title="Xóa phiếu nhập">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="ncc-empty-state">
                            <div class="ncc-empty-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <p class="mb-0 fw-semibold">Không tìm thấy phiếu nhập nào phù hợp.</p>
                            <small class="text-muted">Hãy thử thay đổi từ khóa tìm kiếm hoặc điều chỉnh khoảng ngày lọc.</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 4. FOOTER PHÂN TRANG CLIENT-SIDE ĐỒNG BỘ --}}
        @if($danhSachPhieuNhap->count() > 0)
            <div class="ncc-table-footer">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="font-size: 13.5px; font-weight: 500;">Số hàng mỗi trang:</span>
                        <select id="rsPerPageSelect" class="form-select form-select-sm" style="width: 72px;" onchange="doiSoHangMoiTrang(this.value)">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div id="rsRecordsCount" class="text-muted fw-semibold" style="font-size: 13.5px;">
                        1-{{ min(10, $danhSachPhieuNhap->count()) }} / {{ $danhSachPhieuNhap->count() }} bản ghi
                    </div>
                </div>
                <div>
                    <ul id="rsPaginationList" class="pagination pagination-sm mb-0">
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
                            <a href="{{ route('admin.nha-cung-cap.create') }}" target="_blank" class="text-decoration-none" style="font-size:0.78rem; color:#f43f5e;">
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
            <button type="button" class="ncc-btn-secondary" onclick="dongModalPhieuNhap()">
                Hủy bỏ
            </button>
            <button type="submit" id="btnSubmit" class="ncc-btn-primary" form="formChung">
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
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #EAECF0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);">

            <div class="modal-header" style="background: #FAFAFA; border-bottom: 1px solid #EAECF0; padding: 16px 24px;">
                <h5 class="modal-title" id="tieuDeModal" style="color: #101828; font-weight: 700; font-size: 17px; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-file-earmark-text me-2" style="color: #EC3D8F;"></i>Chi tiết phiếu nhập
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="noiDungModal" style="padding: 20px 24px;">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border text-danger me-2" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
                    <span>Đang tải thông tin chi tiết...</span>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-end" style="background: #FAFAFA; border-top: 1px solid #EAECF0; padding: 12px 24px;">
                <a href="#" id="linkTrangChiTietModal" class="ncc-btn-secondary" style="font-size: 13px;">
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #EAECF0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
            <form id="formHuyPhieu" method="POST" action="" onsubmit="return validateFormHuyPhieu()">
                @csrf
                <div class="modal-header" style="background: #FEF3F2; border-bottom: 1px solid #FECDCA; padding: 16px 22px;">
                    <h5 class="modal-title" id="tieuDeModalHuy" style="color: #B42318; font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-exclamation-triangle me-2"></i>Xác nhận hủy phiếu nhập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 22px;">
                    <div class="alert alert-warning py-2 mb-3" style="font-size: 0.9rem; background: #FFFAEB; border-color: #FEDF89; color: #B54708;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hành động này <strong>không thể hoàn tác</strong>. Phiếu nhập chuyển sang trạng thái <strong>Đã hủy</strong> và không làm phát sinh nhập kho.
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size: 13.5px; font-weight: 600; color: #344054;">Mã phiếu: <span id="spanMaPhieuHuy" style="color: #B42318; font-weight: 700;"></span></label>
                    </div>
                    <div class="mb-3">
                        <label for="inp_ly_do_huy" class="form-label" style="font-size: 13px; font-weight: 600; color: #344054;">
                            Lý do hủy <span class="text-danger">*</span>
                        </label>
                        <textarea name="ly_do_huy" id="inp_ly_do_huy" class="form-control" rows="3"
                                  placeholder="Nhập lý do hủy phiếu nhập này..." required></textarea>
                        <div id="errorLyDoHuy" class="text-danger mt-1" style="display:none; font-size:0.85rem;">
                            Vui lòng nhập lý do hủy!
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #FAFAFA; border-top: 1px solid #EAECF0; padding: 12px 22px;">
                    <button type="button" class="ncc-btn-secondary" data-bs-dismiss="modal" style="padding: 6px 14px; font-size: 13px;">Đóng</button>
                    <button type="submit" class="btn btn-danger btn-sm" style="border-radius: 8px; padding: 7px 16px; font-weight: 600; background: #D92D20; border-color: #D92D20;">
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
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #EAECF0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
            <form id="formXacNhanModal" method="POST" action="">
                @csrf
                <div class="modal-header" style="background: #ECFDF3; border-bottom: 1px solid #A6F4C5; padding: 16px 22px;">
                    <h5 class="modal-title" id="tieuDeModalXacNhan" style="color: #027A48; font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-check2-circle fs-5"></i>Xác nhận nhập kho phiếu nhập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 22px;">
                    <div class="alert alert-success py-2 mb-3" style="font-size: 0.9rem; background: #ECFDF3; border-color: #A6F4C5; color: #027A48;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hành động này sẽ <strong>cộng số lượng hàng vào tồn kho</strong> thực tế của hệ thống và chuyển trạng thái sang <strong>Đã xác nhận</strong>.
                    </div>
                    <p class="mb-1" style="font-size: 14.5px; color: #344054;">
                        Bạn có chắc chắn muốn xác nhận phiếu nhập <strong id="spanMaPhieuXacNhan" style="color: #027A48; font-size: 15px;"></strong> không?
                    </p>
                </div>
                <div class="modal-footer" style="background: #FAFAFA; border-top: 1px solid #EAECF0; padding: 12px 22px;">
                    <button type="button" class="ncc-btn-secondary" data-bs-dismiss="modal" style="padding: 6px 14px; font-size: 13px;">Hủy bỏ</button>
                    <button type="submit" class="ncc-btn-primary" style="padding: 7px 16px; font-size: 13px; background: #039855; border-color: #039855;">
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
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #EAECF0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
            <form id="formXoaModal" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header" style="background: #FEF3F2; border-bottom: 1px solid #FECDCA; padding: 16px 22px;">
                    <h5 class="modal-title" id="tieuDeModalXoa" style="color: #B42318; font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-trash3 fs-5"></i>Xác nhận xóa phiếu nhập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 22px;">
                    <div class="alert alert-danger py-2 mb-3" style="font-size: 0.9rem; background: #FEF3F2; border-color: #FECDCA; color: #B42318;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Hành động này <strong>không thể hoàn tác</strong>. Dữ liệu phiếu nhập nháp sẽ bị xóa vĩnh viễn khỏi hệ thống.
                    </div>
                    <p class="mb-1" style="font-size: 14.5px; color: #344054;">
                        Bạn có chắc chắn muốn xóa phiếu nhập <strong id="spanMaPhieuXoa" style="color: #B42318; font-size: 15px;"></strong> không?
                    </p>
                </div>
                <div class="modal-footer" style="background: #FAFAFA; border-top: 1px solid #EAECF0; padding: 12px 22px;">
                    <button type="button" class="ncc-btn-secondary" data-bs-dismiss="modal" style="padding: 6px 14px; font-size: 13px;">Hủy bỏ</button>
                    <button type="submit" class="btn btn-danger btn-sm" style="border-radius: 8px; padding: 7px 16px; font-weight: 600; background: #D92D20; border-color: #D92D20;">
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
            '<i class="bi bi-plus-circle me-1" style="color: #EC3D8F;"></i>Thêm Phiếu Nhập Mới';

        document.getElementById('btnSubmit').className = 'ncc-btn-primary';
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
                    `<i class="bi bi-pencil-fill me-1" style="color: #EC3D8F;"></i>Sửa Phiếu Nhập #PN-${id}`;

                document.getElementById('btnSubmit').className = 'ncc-btn-primary';
                document.getElementById('iconSubmit').className = 'bi bi-check2 me-1';
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
                                    <span style="color:#e11d48; font-weight:800; font-size:15px;">#PN-${id}</span>
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
                                        <td class="text-end fw-bold" style="color:#e11d48;">${thanhTien.toLocaleString('vi-VN')} ₫</td>
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
                                            <td colspan="4" class="text-end text-uppercase" style="color:#e11d48; font-weight:700;">Tổng cộng:</td>
                                            <td class="text-end" style="color:#e11d48; font-weight:700; font-size:15px;">${tongCong.toLocaleString('vi-VN')} ₫</td>
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
        const prevDisabled = trangHienTai === 1 ? 'disabled' : '';
        html += `<li class="page-item ${prevDisabled}">
                    <a onclick="denTrang(${trangHienTai - 1})" class="page-link" style="${trangHienTai === 1 ? 'pointer-events:none;' : 'cursor:pointer;'}">&lsaquo;</a>
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
                            <a onclick="denTrang(${p})" class="page-link" style="cursor:pointer;">${p}</a>
                         </li>`;
            }
        });

        const nextDisabled = trangHienTai === tongTrang ? 'disabled' : '';
        html += `<li class="page-item ${nextDisabled}">
                    <a onclick="denTrang(${trangHienTai + 1})" class="page-link" style="${trangHienTai === tongTrang ? 'pointer-events:none;' : 'cursor:pointer;'}">&rsaquo;</a>
                 </li>`;

        pagList.innerHTML = html;
    }
</script>
@endpush
