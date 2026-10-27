@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hh-container { padding: 24px; max-width: 1440px; margin: 0 auto; }
        .hh-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 768px) { .hh-stats-grid { grid-template-columns: 1fr; } }
        .hh-stat-card { background: #fff; border: 1px solid #EAECF0; border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; }
        .hh-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .hh-stat-icon.red { background: #FEE4E2; color: #D92D20; }
        .hh-stat-icon.orange { background: #FEF0C7; color: #DC6803; }
        .hh-stat-icon.purple { background: #F4EBFF; color: #7F56D9; }
        .hh-card { background: #fff; border: 1px solid #EAECF0; border-radius: 14px; box-shadow: 0 1px 3px rgba(16,24,40,0.04); overflow: hidden; }
        .hh-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .hh-table th { background: #F9FAFB; color: #475467; font-size: 12px; font-weight: 600; text-transform: uppercase; padding: 12px 18px; border-bottom: 1px solid #EAECF0; }
        .hh-table td { padding: 14px 18px; vertical-align: middle; border-bottom: 1px solid #F2F4F7; font-size: 13.5px; color: #344054; }
        .badge-source { padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .badge-can-han { background: #FEF0C7; color: #B54708; border: 1px solid #FEDF89; }
        .badge-hoan-hang { background: #FEE4E2; color: #B42318; border: 1px solid #FECDCA; }
        .badge-ton-kho { background: #EAECF0; color: #344054; border: 1px solid #D0D5DD; }
        .badge-flash-sale { background: #FCE7F6; color: #C11574; border: 1px solid #FCCEEE; }
        .badge-shipper { background: #E0F2FE; color: #026AA2; border: 1px solid #B9E6FE; }
        .badge-khac { background: #F2F4F7; color: #475467; border: 1px solid #EAECF0; }
    </style>
@endpush

@section('content')
<div class="hh-container">

    {{-- Page Header --}}
    <div class="rs-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="rs-page-title fs-4 fw-bold text-dark mb-1">Báo cáo hàng hỏng & Tiêu hủy</h1>
            <p class="rs-page-subtitle text-muted mb-0" style="font-size: 13px;">
                Quản lý tập trung toàn bộ dữ liệu hàng hỏng từ Cận hạn/Hết hạn, Hàng hoàn, Tồn kho, Flash Sale và Shipper.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.san-pham-het-han') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-3 px-3 py-2">
                <i class="bi bi-clock-history me-1"></i> Sản phẩm cận hạn
            </a>
            <a href="{{ route('admin.hoan-hang.flash-sale.index') }}" class="btn btn-outline-danger btn-sm fw-semibold rounded-3 px-3 py-2">
                <i class="bi bi-lightning me-1"></i> Flash Sale
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="hh-stats-grid">
        <div class="hh-stat-card">
            <div class="hh-stat-icon red"><i class="bi bi-trash3"></i></div>
            <div>
                <h3 class="fs-4 fw-bold text-dark mb-0">{{ number_format($thongKe['tong_so_luong']) }}</h3>
                <div class="text-muted" style="font-size: 13px;">Tổng số lượng đã báo hỏng</div>
            </div>
        </div>
        <div class="hh-stat-card">
            <div class="hh-stat-icon orange"><i class="bi bi-file-earmark-text"></i></div>
            <div>
                <h3 class="fs-4 fw-bold text-dark mb-0">{{ number_format($thongKe['tong_lan']) }}</h3>
                <div class="text-muted" style="font-size: 13px;">Tổng số lần ghi nhận hỏng</div>
            </div>
        </div>
        <div class="hh-stat-card">
            <div class="hh-stat-icon purple"><i class="bi bi-cash-stack"></i></div>
            <div>
                <h3 class="fs-4 fw-bold text-dark mb-0">{{ number_format($thongKe['tong_thiet_hai']) }}đ</h3>
                <div class="text-muted" style="font-size: 13px;">Tổng giá trị thiệt hại ước tính</div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="hh-card p-3 mb-4">
        <form method="GET" action="{{ route('admin.bao-cao-hang-hong.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="nguon_hong" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Tất cả nguồn hỏng --</option>
                    <option value="CAN_HAN_HET_HAN" {{ request('nguon_hong') === 'CAN_HAN_HET_HAN' ? 'selected' : '' }}>Cận hạn / Hết hạn</option>
                    <option value="HANG_HOAN" {{ request('nguon_hong') === 'HANG_HOAN' ? 'selected' : '' }}>Hàng hoàn</option>
                    <option value="TON_KHO" {{ request('nguon_hong') === 'TON_KHO' ? 'selected' : '' }}>Tồn kho</option>
                    <option value="FLASH_SALE" {{ request('nguon_hong') === 'FLASH_SALE' ? 'selected' : '' }}>Flash Sale</option>
                    <option value="SHIPPER" {{ request('nguon_hong') === 'SHIPPER' ? 'selected' : '' }}>Shipper</option>
                    <option value="KHAC" {{ request('nguon_hong') === 'KHAC' ? 'selected' : '' }}>Khác</option>
                </select>
            </div>
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <input type="text" name="keyword" class="form-control" placeholder="Tìm tên mặt hàng, mã lô, mã bó, lý do..." value="{{ request('keyword') }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Tìm</button>
                    @if(request()->anyFilled(['nguon_hong', 'keyword', 'tu_ngay', 'den_ngay']))
                        <a href="{{ route('admin.bao-cao-hang-hong.index') }}" class="btn btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
                    @endif
                </div>
            </div>
            <div class="col-md-2">
                <input type="date" name="tu_ngay" class="form-control form-control-sm" value="{{ request('tu_ngay') }}" placeholder="Từ ngày">
            </div>
            <div class="col-md-2">
                <input type="date" name="den_ngay" class="form-control form-control-sm" value="{{ request('den_ngay') }}" placeholder="Đến ngày">
            </div>
        </form>
    </div>

    {{-- Main Table --}}
    <div class="hh-card">
        <div class="table-responsive">
            <table class="hh-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">STT</th>
                        <th>Mặt hàng</th>
                        <th class="text-center">Loại</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-center">Nguồn hỏng</th>
                        <th>Người báo</th>
                        <th class="text-center">Thời gian</th>
                        <th>Lý do</th>
                        <th class="text-center" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($baoCaoHangHongs as $idx => $item)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $baoCaoHangHongs->firstItem() + $idx }}</td>
                            <td>
                                <div class="fw-bold text-dark">
                                    {{ $item->boHoa?->ten_bo_hoa ?? $item->sanPham?->ten_san_pham ?? 'Sản phẩm #' . $item->ma_san_pham }}
                                </div>
                                <div class="text-muted" style="font-size: 11.5px;">
                                    @if($item->ma_bo_hoa)
                                        Mã bó: #{{ $item->ma_bo_hoa }}
                                    @elseif($item->ma_chi_tiet_nhap)
                                        Lô: #{{ $item->ma_chi_tiet_nhap }} • SP: #{{ $item->ma_san_pham }}
                                    @else
                                        SP: #{{ $item->ma_san_pham }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($item->ma_bo_hoa)
                                    <span class="badge bg-purple-subtle text-purple border" style="background:#F4EBFF; color:#7F56D9;">Bó hoa thành phẩm</span>
                                @else
                                    <span class="badge bg-light text-dark border">Lô hoa / SP</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-danger fs-6">{{ number_format($item->so_luong_hong) }}</span>
                            </td>
                            <td class="text-center">
                                @switch($item->nguon_hong)
                                    @case('CAN_HAN_HET_HAN')
                                        <span class="badge-source badge-can-han"><i class="bi bi-clock-history"></i> Cận hạn/Hết hạn</span>
                                        @break
                                    @case('HANG_HOAN')
                                        <span class="badge-source badge-hoan-hang"><i class="bi bi-arrow-return-left"></i> Hàng hoàn</span>
                                        @break
                                    @case('TON_KHO')
                                        <span class="badge-source badge-ton-kho"><i class="bi bi-boxes"></i> Tồn kho</span>
                                        @break
                                    @case('FLASH_SALE')
                                        <span class="badge-source badge-flash-sale"><i class="bi bi-lightning-fill"></i> Flash Sale</span>
                                        @break
                                    @case('SHIPPER')
                                        <span class="badge-source badge-shipper"><i class="bi bi-bicycle"></i> Shipper</span>
                                        @break
                                    @default
                                        <span class="badge-source badge-khac"><i class="bi bi-question-circle"></i> {{ $item->nguon_hong }}</span>
                                @endswitch
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->nhanVien?->ten_nhan_vien ?? 'Hệ thống' }}</div>
                            </td>
                            <td class="text-center text-nowrap">
                                {{ optional($item->thoi_gian_bao_cao)->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 180px;" title="{{ $item->ly_do }}">
                                    {{ $item->ly_do ?? '—' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-dark fw-semibold rounded-2 px-2 py-1 btn-xem-chi-tiet"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalChiTietBaoHong"
                                        data-id="{{ $item->ma_bao_cao }}"
                                        data-ten="{{ $item->boHoa?->ten_bo_hoa ?? $item->sanPham?->ten_san_pham ?? 'Chưa rõ' }}"
                                        data-loai="{{ $item->ma_bo_hoa ? 'Bó hoa thành phẩm' : 'Lô hoa / Sản phẩm' }}"
                                        data-masp="{{ $item->ma_san_pham ?? '—' }}"
                                        data-malo="{{ $item->ma_chi_tiet_nhap ?? '—' }}"
                                        data-mabo="{{ $item->ma_bo_hoa ?? '—' }}"
                                        data-mahd="{{ $item->ma_hoa_don ? '#HD-'.$item->ma_hoa_don : '—' }}"
                                        data-mafs="{{ $item->ma_flash_sale ? '#FS-'.$item->ma_flash_sale : '—' }}"
                                        data-sl="{{ $item->so_luong_hong }}"
                                        data-thiet-hai="{{ $item->gia_tri_thiet_hai ? number_format($item->gia_tri_thiet_hai).'đ' : '—' }}"
                                        data-nguon="{{ $item->nguon_hong }}"
                                        data-nguoibao="{{ $item->nhanVien?->ten_nhan_vien ?? 'Hệ thống' }}"
                                        data-thoigian="{{ optional($item->thoi_gian_bao_cao)->format('d/m/Y H:i:s') }}"
                                        data-lydo="{{ $item->ly_do ?? '—' }}"
                                        data-comment="{{ $item->ghi_chu ?? '—' }}">
                                    <i class="bi bi-eye"></i> Chi tiết
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                Không có ghi nhận hàng hỏng nào trong bộ lọc này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($baoCaoHangHongs->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $baoCaoHangHongs->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL XEM CHI TIẾT BÁO HỎNG --}}
<div class="modal fade" id="modalChiTietBaoHong" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header border-bottom py-3 px-4 bg-light" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title fw-bold text-dark fs-6">
                    <i class="bi bi-info-circle text-danger me-2"></i> Chi tiết báo cáo hàng hỏng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 rounded-3 bg-light border mb-3">
                    <div class="fw-bold text-dark fs-6" id="dtTenMatHang">—</div>
                    <div class="text-muted" style="font-size: 12px;" id="dtLoaiMatHang">—</div>
                </div>
                <div class="row g-2 mb-3" style="font-size: 13px;">
                    <div class="col-6"><span class="text-muted">Mã SP:</span> <span class="fw-semibold text-dark" id="dtMaSp">—</span></div>
                    <div class="col-6"><span class="text-muted">Mã Lô:</span> <span class="fw-semibold text-dark" id="dtMaLo">—</span></div>
                    <div class="col-6"><span class="text-muted">Mã Bó hoa:</span> <span class="fw-semibold text-dark" id="dtMaBo">—</span></div>
                    <div class="col-6"><span class="text-muted">Đơn hàng liên quan:</span> <span class="fw-semibold text-primary" id="dtMaHd">—</span></div>
                    <div class="col-6"><span class="text-muted">Flash Sale:</span> <span class="fw-semibold text-danger" id="dtMaFs">—</span></div>
                    <div class="col-6"><span class="text-muted">Nguồn hỏng:</span> <span class="fw-bold text-dark" id="dtNguon">—</span></div>
                    <div class="col-6"><span class="text-muted">Số lượng hỏng:</span> <span class="fw-bold text-danger fs-6" id="dtSoLuong">—</span></div>
                    <div class="col-6"><span class="text-muted">Giá trị thiệt hại:</span> <span class="fw-bold text-dark" id="dtThietHai">—</span></div>
                    <div class="col-6"><span class="text-muted">Người báo:</span> <span class="fw-semibold text-dark" id="dtNguoiBao">—</span></div>
                    <div class="col-6"><span class="text-muted">Thời gian:</span> <span class="text-dark" id="dtThoiGian">—</span></div>
                </div>
                <div class="mb-2">
                    <div class="text-muted fw-semibold mb-1" style="font-size: 12px;">Lý do hỏng:</div>
                    <div class="p-2 rounded bg-light border text-dark" style="font-size: 13px;" id="dtLyDo">—</div>
                </div>
                <div>
                    <div class="text-muted fw-semibold mb-1" style="font-size: 12px;">Ghi chú / Comment chi tiết:</div>
                    <div class="p-2 rounded bg-light border text-dark" style="font-size: 13px;" id="dtComment">—</div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 bg-light" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalChiTietBaoHong');
    if (!modalEl) return;

    function populateChiTietModal(btn) {
        if (!btn) return;
        document.getElementById('dtTenMatHang').textContent = btn.dataset.ten || '—';
        document.getElementById('dtLoaiMatHang').textContent = btn.dataset.loai || '—';
        document.getElementById('dtMaSp').textContent = btn.dataset.masp || '—';
        document.getElementById('dtMaLo').textContent = btn.dataset.malo || '—';
        document.getElementById('dtMaBo').textContent = btn.dataset.mabo || '—';
        document.getElementById('dtMaHd').textContent = btn.dataset.mahd || '—';
        document.getElementById('dtMaFs').textContent = btn.dataset.mafs || '—';
        document.getElementById('dtSoLuong').textContent = btn.dataset.sl || '—';
        document.getElementById('dtThietHai').textContent = btn.dataset.thiethai || '—';
        document.getElementById('dtNguon').textContent = btn.dataset.nguon || '—';
        document.getElementById('dtNguoiBao').textContent = btn.dataset.nguoibao || '—';
        document.getElementById('dtThoiGian').textContent = btn.dataset.thoigian || '—';
        document.getElementById('dtLyDo').textContent = btn.dataset.lydo || '—';
        document.getElementById('dtComment').textContent = btn.dataset.comment || '—';
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-xem-chi-tiet');
        if (!btn) return;
        populateChiTietModal(btn);
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    modalEl.addEventListener('show.bs.modal', function (event) {
        if (event.relatedTarget) {
            populateChiTietModal(event.relatedTarget);
        }
    });
});
</script>
@endpush
