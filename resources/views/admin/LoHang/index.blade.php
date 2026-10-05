@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
    <style>
        .ncc-filter-grid {
            grid-template-columns: 1.5fr 1.3fr 1.2fr 1fr auto;
        }
    </style>
@endpush

@section('content')
<div class="ncc-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Quản lý lô hàng</h1>
            <p class="rs-page-subtitle">Theo dõi danh sách các lô hàng, kiểm soát tồn kho theo lô và đánh giá chất lượng nguồn hàng.</p>
        </div>
        <div class="ncc-header-actions">
            <a href="{{ route('phieu-nhap.index') }}" class="ncc-btn-primary">
                <i class="bi bi-plus-circle"></i>
                <span>Tạo phiếu nhập mới</span>
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

    {{-- Filter Box --}}
    <div class="ncc-filter-box">
        <form method="GET" action="{{ route('admin.lo-hang') }}">
            <div class="ncc-filter-grid">
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Tìm kiếm</label>
                    <input type="text"
                           name="keyword"
                           class="form-control"
                           placeholder="Mã lô, mã SP, mã PN, tên SP..."
                           value="{{ request('keyword') }}">
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Nhà cung cấp</label>
                    <select name="ma_nha_cung_cap" class="form-select">
                        <option value="">-- Tất cả NCC --</option>
                        @foreach($nhaCungCaps as $ncc)
                            <option value="{{ $ncc->ma_nha_cung_cap }}" {{ (string) request('ma_nha_cung_cap') === (string) $ncc->ma_nha_cung_cap ? 'selected' : '' }}>
                                {{ $ncc->ten_nha_cung_cap }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Đánh giá chất lượng</label>
                    <select name="ket_qua" class="form-select">
                        <option value="">-- Tất cả kết quả --</option>
                        <option value="DAT" {{ request('ket_qua') === 'DAT' ? 'selected' : '' }}>Đạt</option>
                        <option value="CO_VAN_DE" {{ request('ket_qua') === 'CO_VAN_DE' ? 'selected' : '' }}>Có vấn đề</option>
                        <option value="KHONG_DAT" {{ request('ket_qua') === 'KHONG_DAT' ? 'selected' : '' }}>Không đạt</option>
                        <option value="CHUA_DANH_GIA" {{ request('ket_qua') === 'CHUA_DANH_GIA' ? 'selected' : '' }}>Chưa đánh giá</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px; font-weight:600; color:#475467;">Tình trạng tồn kho</label>
                    <select name="ton_kho" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="con_hang" {{ request('ton_kho') === 'con_hang' ? 'selected' : '' }}>Còn hàng</option>
                        <option value="het_hang" {{ request('ton_kho') === 'het_hang' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark" style="height:38px; font-weight:600; font-size:13px; padding: 0 16px;">
                        <i class="bi bi-funnel me-1"></i> Lọc
                    </button>
                    @if(request()->anyFilled(['keyword', 'ma_nha_cung_cap', 'ket_qua', 'ton_kho']))
                        <a href="{{ route('admin.lo-hang') }}" class="btn btn-outline-secondary" style="height:38px; display:inline-flex; align-items:center;" title="Xóa bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Main Table --}}
    <div class="ncc-card">
        <div class="ncc-card-header">
            <h2 class="ncc-card-title">
                <i class="bi bi-boxes"></i>
                Danh sách lô hàng
                <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $loHangs->total() }}</span>
            </h2>
        </div>

        <div class="table-responsive">
            <table class="ncc-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Mã lô</th>
                        <th>Mặt hàng</th>
                        <th>Phiếu nhập</th>
                        <th>Nhà cung cấp</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-center">Còn lại</th>
                        <th class="text-end">Giá nhập</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Chất lượng</th>
                        <th class="text-center">Hàng hỏng</th>
                        <th class="text-end" style="width: 140px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loHangs as $lo)
                        @php
                            $dg = $lo->danhGiaMoiNhat;
                            $tongHongLo = $lo->baoCaoHangHongs->sum('so_luong_hong');
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-bold">#CTN-{{ $lo->ma_chi_tiet_nhap }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $lo->sanPham->ten_san_pham ?? 'Sản phẩm #' . $lo->ma_san_pham }}</div>
                                <small class="text-muted">Mã SP: #{{ $lo->ma_san_pham }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold">#PN-{{ $lo->ma_phieu_nhap }}</span>
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
                            <td class="text-center fw-semibold">
                                {{ $lo->so_luong }}
                            </td>
                            <td class="text-center">
                                @if($lo->so_luong_con_lai > 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        {{ $lo->so_luong_con_lai }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        0 (Hết)
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($lo->gia_nhap, 0, ',', '.') }} đ
                            </td>
                            <td class="text-center">
                                @if($lo->so_luong_con_lai > 0)
                                    <span class="badge bg-success" style="font-size:11px; font-weight:600;">CÒN HÀNG</span>
                                @else
                                    <span class="badge bg-secondary" style="font-size:11px; font-weight:600;">HẾT HÀNG</span>
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
                                @if($tongHongLo > 0 || ($dg && $dg->so_luong_loi > 0))
                                    <span class="text-danger fw-bold">
                                        {{ max($tongHongLo, $dg?->so_luong_loi ?? 0) }}
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.lo-hang.show', $lo->ma_chi_tiet_nhap) }}"
                                       class="ncc-action-btn view"
                                       title="Xem chi tiết lô">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($lo->so_luong_con_lai > 0)
                                        <button type="button"
                                                class="btn btn-sm {{ $dg ? 'btn-outline-primary' : 'btn-primary' }}"
                                                style="font-size:12px; font-weight:600; padding: 4px 10px; border-radius:6px;"
                                                onclick="openModalDanhGia({{ $lo->ma_chi_tiet_nhap }}, '{{ addslashes($lo->sanPham->ten_san_pham ?? '') }}', {{ $lo->so_luong }}, '{{ $dg?->ket_qua ?? '' }}', {{ $dg?->so_luong_loi ?? 0 }}, '{{ addslashes($dg?->ly_do ?? '') }}', '{{ addslashes($dg?->ghi_chu ?? '') }}')">
                                            {{ $dg ? 'Sửa ĐG' : 'Đánh giá' }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="ncc-empty-state">
                                <div class="ncc-empty-icon">
                                    <i class="bi bi-boxes"></i>
                                </div>
                                <p class="mb-0 fw-semibold">Không tìm thấy lô hàng phù hợp.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loHangs->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $loHangs->links('pagination::bootstrap-5') }}
            </div>
        @endif
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
                        <input type="text" name="ly_do" id="modal_inp_ly_do" class="form-control" placeholder="VD: Hoa héo dập, sai quy cách...">
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
</script>
@endpush
@endsection
