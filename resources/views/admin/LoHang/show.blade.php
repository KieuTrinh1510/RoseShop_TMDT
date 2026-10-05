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
                    <span class="label">Tồn kho lô</span>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <div>
                            <span class="text-muted" style="font-size:13px;">Ban đầu:</span>
                            <strong>{{ $loHang->so_luong }}</strong>
                        </div>
                        <div>
                            <span class="text-muted" style="font-size:13px;">Còn lại:</span>
                            @if($loHang->so_luong_con_lai > 0)
                                <span class="badge bg-success" style="font-size:13px;">{{ $loHang->so_luong_con_lai }}</span>
                            @else
                                <span class="badge bg-secondary" style="font-size:13px;">0 (Hết hàng)</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ncc-info-item">
                    <span class="label">Tổng hàng hỏng / hao hụt</span>
                    <span class="value fw-bold {{ $loHang->baoCaoHangHongs->sum('so_luong_hong') > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ $loHang->baoCaoHangHongs->sum('so_luong_hong') }} {{ $loHang->sanPham->don_vi_tinh ?? 'sản phẩm' }}
                    </span>
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

        {{-- Right: Evaluation History & Damage Reports --}}
        <div>

            {{-- Quality Evaluation History --}}
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

            {{-- Damaged Inventory Reports --}}
            <div class="ncc-card">
                <div class="ncc-card-header">
                    <h2 class="ncc-card-title">
                        <i class="bi bi-trash3 text-danger"></i>
                        Lịch sử hàng hỏng / hao hụt đã trừ tồn kho
                        <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $loHang->baoCaoHangHongs->count() }}</span>
                    </h2>
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
