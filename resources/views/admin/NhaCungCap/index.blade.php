@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/PhieuNhap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
@endpush

@section('content')
<div class="rs-management-page">

    {{-- 1. HEADER TRANG: Tiêu đề + Nút Thêm nhà cung cấp (Mở Modal Popup) --}}
    <div class="rs-header-block">
        <div class="rs-header-left">
            <div class="rs-header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <h1 class="rs-header-title">Quản lý nhà cung cấp</h1>
                <p class="rs-header-subtitle">Quản lý danh sách đối tác cung ứng hoa tươi và phụ kiện của RoseShop</p>
            </div>
        </div>

        <button type="button" class="rs-btn-create" onclick="openModalThemNcc()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
            </svg>
            <span>Thêm nhà cung cấp</span>
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

    {{-- STATS CARDS --}}
    <div class="ncc-stats-grid">
        <div class="ncc-stat-card">
            <div class="ncc-stat-icon pink">
                <i class="bi bi-building"></i>
            </div>
            <div class="ncc-stat-info">
                <p>Tổng nhà cung cấp</p>
                <h3>{{ number_format($stats['tong_ncc'] ?? 0) }}</h3>
            </div>
        </div>

        <div class="ncc-stat-card">
            <div class="ncc-stat-icon green">
                <i class="bi bi-patch-check"></i>
            </div>
            <div class="ncc-stat-info">
                <p>Đang hợp tác</p>
                <h3>{{ number_format($stats['dang_hop_tac'] ?? 0) }}</h3>
            </div>
        </div>

        <div class="ncc-stat-card">
            <div class="ncc-stat-icon gray">
                <i class="bi bi-pause-circle"></i>
            </div>
            <div class="ncc-stat-info">
                <p>Ngừng hợp tác</p>
                <h3>{{ number_format($stats['ngung_hop_tac'] ?? 0) }}</h3>
            </div>
        </div>
    </div>

    {{-- 2. CARD CHÍNH: BỘ LỌC + BẢNG DANH SÁCH + PHÂN TRANG --}}
    <div class="rs-main-card">

        {{-- BỘ LỌC TINH GỌN FIT TRÊN 1 HÀNG HÀI HÒA (ĐỒNG BỘ PHIẾU NHẬP) --}}
        <form method="GET" action="{{ route('admin.nha-cung-cap') }}" class="rs-filter-bar">
            
            <div class="rs-filter-item">
                <label class="rs-filter-label">Trạng thái:</label>
                <select name="trang_thai" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="DANG_HOP_TAC" {{ request('trang_thai') === 'DANG_HOP_TAC' ? 'selected' : '' }}>
                        Đang hợp tác
                    </option>
                    <option value="NGUNG_HOP_TAC" {{ request('trang_thai') === 'NGUNG_HOP_TAC' ? 'selected' : '' }}>
                        Ngừng hợp tác
                    </option>
                </select>
            </div>

            <div class="rs-filter-item">
                <label class="rs-filter-label">Sắp xếp:</label>
                <select name="sort" class="rs-filter-select" onchange="this.form.submit()">
                    <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Tên A → Z</option>
                    <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Tên Z → A</option>
                </select>
            </div>

            <div class="rs-filter-search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Nhập tên, SĐT, email hoặc mã NCC..." autocomplete="off">
            </div>

            <button type="submit" class="rs-btn-filter-submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <span>Lọc</span>
            </button>

            @if(request()->hasAny(['keyword', 'trang_thai', 'sort']))
                <a href="{{ route('admin.nha-cung-cap') }}" class="rs-btn-filter-reset" title="Đặt lại bộ lọc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    <span>Bỏ lọc</span>
                </a>
            @endif
        </form>

        {{-- BẢNG DANH SÁCH VỚI STICKY HEADER VA MÀU NỀN #FDEAF3 --}}
        <div class="rs-table-container">
            <table class="rs-table">
                <thead>
                    <tr>
                        <th style="width: 90px; text-align: center;">Mã NCC</th>
                        <th style="text-align: left;">Nhà cung cấp</th>
                        <th style="width: 150px; text-align: center; white-space: nowrap;">Số điện thoại</th>
                        <th style="text-align: left;">Email</th>
                        <th style="text-align: left;">Địa chỉ</th>
                        <th style="width: 110px; text-align: center;">Lần nhập</th>
                        <th style="width: 140px; text-align: center;">Trạng thái</th>
                        <th style="width: 140px; text-align: center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nhaCungCaps as $ncc)
                        <tr>
                            <td style="text-align:center;">
                                <a href="{{ route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap) }}" class="fw-bold text-decoration-none" style="color:#EC3D8F;">
                                    #NCC-{{ $ncc->ma_nha_cung_cap }}
                                </a>
                            </td>

                            <td>
                                <a href="{{ route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap) }}"
                                   class="fw-semibold text-dark text-decoration-none">
                                    {{ $ncc->ten_nha_cung_cap }}
                                </a>
                            </td>

                            <td style="text-align:center; white-space:nowrap;">
                                @if($ncc->so_dien_thoai)
                                    <a href="tel:{{ $ncc->so_dien_thoai }}" class="text-decoration-none fw-medium" style="color:#475467;" title="Bấm để gọi {{ $ncc->so_dien_thoai }}">
                                        <i class="bi bi-telephone me-1" style="color:#EC3D8F;"></i>{{ $ncc->so_dien_thoai }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($ncc->email)
                                    <a href="mailto:{{ $ncc->email }}" class="text-muted text-decoration-none">
                                        <i class="bi bi-envelope me-1 text-secondary"></i>{{ $ncc->email }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td style="max-width: 220px;" class="text-truncate" title="{{ $ncc->dia_chi }}">
                                {{ $ncc->dia_chi ?? '—' }}
                            </td>

                            <td style="text-align:center;">
                                <span class="badge rounded-pill bg-light text-dark border">
                                    {{ $ncc->phieu_nhaps_count ?? 0 }} phiếu
                                </span>
                            </td>

                            <td style="text-align:center;">
                                @if($ncc->trang_thai === \App\Models\NhaCungCap::TRANG_THAI_DANG_HOP_TAC)
                                    <span class="badge-confirmed">
                                        <i class="bi bi-check-lg me-1"></i>Đang hợp tác
                                    </span>
                                @else
                                    <span class="badge-cancelled">
                                        <i class="bi bi-x-circle me-1"></i>Ngừng hợp tác
                                    </span>
                                @endif
                            </td>

                            <td style="text-align: center; white-space: nowrap;">
                                <div class="rs-action-group">
                                    {{-- Xem chi tiết --}}
                                    <a href="{{ route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap) }}"
                                       class="rs-action-btn detail"
                                       title="Xem chi tiết & lịch sử nhập">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    {{-- Sửa (Mở Modal Popup trực tiếp) --}}
                                    <button type="button" class="rs-action-btn edit"
                                            onclick="openModalSuaNcc({{ $ncc->ma_nha_cung_cap }}, '{{ addslashes($ncc->ten_nha_cung_cap) }}', '{{ addslashes($ncc->so_dien_thoai ?? '') }}', '{{ addslashes($ncc->email ?? '') }}', '{{ addslashes($ncc->dia_chi ?? '') }}')"
                                            title="Sửa thông tin nhà cung cấp">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="m15 5 4 4"/>
                                        </svg>
                                    </button>

                                    {{-- Ngừng hợp tác / Kích hoạt lại --}}
                                    @if($ncc->trang_thai === \App\Models\NhaCungCap::TRANG_THAI_DANG_HOP_TAC)
                                        <form method="POST"
                                              action="{{ route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Bạn chắc chắn muốn ngừng hợp tác với nhà cung cấp \'{{ addslashes($ncc->ten_nha_cung_cap) }}\'?\n(Nhà cung cấp này sẽ không hiển thị khi tạo phiếu nhập mới, nhưng lịch sử nhập hàng vẫn được lưu đầy đủ).')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="NGUNG_HOP_TAC">
                                            <button type="submit" class="rs-action-btn cancel" title="Ngừng hợp tác">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="10" y1="15" x2="10" y2="9"/>
                                                    <line x1="14" y1="15" x2="14" y2="9"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Kích hoạt lại nhà cung cấp \'{{ addslashes($ncc->ten_nha_cung_cap) }}\' để có thể tiếp tục tạo phiếu nhập hàng?')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="DANG_HOP_TAC">
                                            <button type="submit" class="rs-action-btn confirm" title="Kích hoạt lại">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Không tìm thấy nhà cung cấp nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN PHIẾU NHẬP --}}
        @if($nhaCungCaps->total() > 0)
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
                        {{ $nhaCungCaps->firstItem() ?? 0 }}-{{ $nhaCungCaps->lastItem() ?? 0 }} / {{ $nhaCungCaps->total() }} bản ghi
                    </div>
                </div>
                <div class="rs-footer-pagination">
                    <ul class="rs-pagination">
                        @if ($nhaCungCaps->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $nhaCungCaps->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
                        @endif

                        @php
                            $currentPage = $nhaCungCaps->currentPage();
                            $lastPage = $nhaCungCaps->lastPage();
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
                                <li class="page-item"><a class="page-link" href="{{ $nhaCungCaps->url($p) }}">{{ $p }}</a></li>
                            @endif
                        @endforeach

                        @if ($nhaCungCaps->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $nhaCungCaps->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

    </div>

</div>

{{-- ==========================================================================
     MODAL POPUP: THÊM / SỬA NHÀ CUNG CẤP (ĐÈ LÊN MÀN HÌNH BẢNG DANH SÁCH)
     ========================================================================== --}}
<div class="rs-modal" id="modalNhaCungCap" onclick="if(event.target === this) dongModalNhaCungCap();">
    <div class="rs-modal-dialog" style="max-width: 680px;">
        <div class="rs-modal-header">
            <h3 class="rs-modal-title" id="tieuDeModalNcc">
                <i class="bi bi-building-add me-1"></i>Thêm Nhà Cung Cấp Mới
            </h3>
            <button type="button" class="rs-modal-close" onclick="dongModalNhaCungCap()">&times;</button>
        </div>

        <div class="rs-modal-body">
            <form id="formModalNcc" method="POST" action="{{ route('admin.nha-cung-cap.store') }}">
                @csrf
                <div id="methodFieldNcc"></div>

                {{-- Tên nhà cung cấp --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="modal_ten_nha_cung_cap">
                        Tên nhà cung cấp <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="ten_nha_cung_cap" id="modal_ten_nha_cung_cap"
                           class="form-control" placeholder="Ví dụ: Công ty TNHH Hoa Tươi Đà Lạt"
                           maxlength="100" required>
                </div>

                {{-- SĐT & Email --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="modal_so_dien_thoai">Số điện thoại liên hệ</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="so_dien_thoai" id="modal_so_dien_thoai"
                                   class="form-control" placeholder="10 chữ số (VD: 0987527192)"
                                   maxlength="10">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="modal_email">Địa chỉ Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="modal_email"
                                   class="form-control" placeholder="ncc@example.com"
                                   maxlength="100">
                        </div>
                    </div>
                </div>

                {{-- Địa chỉ --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="modal_dia_chi">Địa chỉ văn phòng / nhà vườn</label>
                    <textarea name="dia_chi" id="modal_dia_chi" rows="3"
                              class="form-control" placeholder="Ví dụ: Lô B2, Làng hoa Vạn Thành, TP. Đà Lạt"
                              maxlength="500"></textarea>
                </div>
            </form>
        </div>

        <div class="rs-modal-footer">
            <button type="button" class="rs-btn-cancel" onclick="dongModalNhaCungCap()">Hủy bỏ</button>
            <button type="submit" id="btnSubmitNcc" class="rs-btn-submit" form="formModalNcc">
                <i id="iconSubmitNcc" class="bi bi-check2-circle me-1"></i>
                <span id="textSubmitNcc">Lưu nhà cung cấp</span>
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModalThemNcc() {
        document.getElementById('formModalNcc').reset();
        document.getElementById('formModalNcc').action = '{{ route('admin.nha-cung-cap.store') }}';
        document.getElementById('methodFieldNcc').innerHTML = '';
        document.getElementById('tieuDeModalNcc').innerHTML = '<i class="bi bi-building-add me-1"></i>Thêm Nhà Cung Cấp Mới';
        document.getElementById('textSubmitNcc').textContent = 'Lưu nhà cung cấp';
        document.getElementById('modalNhaCungCap').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function openModalSuaNcc(id, ten, sdt, email, diaChi) {
        document.getElementById('formModalNcc').action = `/admin/nha-cung-cap/${id}`;
        document.getElementById('methodFieldNcc').innerHTML = '@method("PUT")';
        document.getElementById('modal_ten_nha_cung_cap').value = ten || '';
        document.getElementById('modal_so_dien_thoai').value = sdt || '';
        document.getElementById('modal_email').value = email || '';
        document.getElementById('modal_dia_chi').value = diaChi || '';
        document.getElementById('tieuDeModalNcc').innerHTML = `<i class="bi bi-pencil-square me-1"></i>Cập Nhật Nhà Cung Cấp (#${id})`;
        document.getElementById('textSubmitNcc').textContent = 'Lưu thay đổi';
        document.getElementById('modalNhaCungCap').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function dongModalNhaCungCap() {
        document.getElementById('modalNhaCungCap').classList.remove('show');
        document.body.style.overflow = '';
    }
</script>
@endpush
