@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/NhaCungCap.css') }}">
    <style>
        .ncc-table th {
            white-space: nowrap !important;
            padding: 12px 12px !important;
        }
        .ncc-table td {
            padding: 12px 12px !important;
        }
        .rs-filter-search-box {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .rs-filter-search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #667085;
            pointer-events: none;
            z-index: 2;
            font-size: 14px;
        }
        .rs-filter-search-box input {
            width: 100%;
            height: 38px;
            padding-left: 36px !important;
            padding-right: 12px;
            border-radius: 8px;
            border: 1px solid #D0D5DD !important;
            font-size: 13.5px;
            color: #101828;
            background-color: #ffffff;
            outline: none;
            box-shadow: none !important;
        }
        .rs-filter-search-box input:focus {
            border-color: #EC3D8F !important;
            box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.15) !important;
        }
    </style>
@endpush

@section('content')
<div class="ncc-container">

    {{-- Page Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Quản lý nhà cung cấp</h1>
            <p class="rs-page-subtitle">Quản lý danh sách đối tác cung ứng hoa tươi và phụ kiện của RoseShop.</p>
        </div>
        <div class="ncc-header-actions">
            <a href="{{ route('admin.nha-cung-cap.create') }}" class="ncc-btn-primary">
                <i class="bi bi-plus-lg"></i>
                <span>Thêm nhà cung cấp</span>
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

    {{-- Stats Cards --}}
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

    {{-- Filter & Search Box --}}
    <div class="ncc-filter-box">
        <form method="GET" action="{{ route('admin.nha-cung-cap') }}">
            <div class="ncc-filter-grid">
                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:#475467;">Tìm kiếm</label>
                    <div class="rs-filter-search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" name="keyword"
                               placeholder="Tìm theo tên, SĐT, email, mã..."
                               value="{{ request('keyword') }}" autocomplete="off">
                    </div>
                </div>

                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:#475467;">Trạng thái hợp tác</label>
                    <select name="trang_thai" class="form-select">
                        <option value="">— Tất cả trạng thái —</option>
                        <option value="DANG_HOP_TAC" {{ request('trang_thai') === 'DANG_HOP_TAC' ? 'selected' : '' }}>
                            Đang hợp tác
                        </option>
                        <option value="NGUNG_HOP_TAC" {{ request('trang_thai') === 'NGUNG_HOP_TAC' ? 'selected' : '' }}>
                            Ngừng hợp tác
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:#475467;">Sắp xếp</label>
                    <select name="sort" class="form-select">
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Tên A → Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Tên Z → A</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="ncc-btn-primary" style="padding: 8px 14px;">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    @if(request()->hasAny(['keyword', 'trang_thai', 'sort']))
                        <a href="{{ route('admin.nha-cung-cap') }}" class="ncc-btn-secondary" style="padding: 8px 14px;" title="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Suppliers Table Card --}}
    <div class="ncc-card">
        <div class="ncc-card-header">
            <h2 class="ncc-card-title">
                <i class="bi bi-list-ul"></i>
                Danh sách nhà cung cấp
                <span class="badge bg-light text-muted border ms-1" style="font-size:12px;">{{ $nhaCungCaps->total() }}</span>
            </h2>
        </div>

        <div class="table-responsive">
            <table class="ncc-table">
                <thead>
                    <tr>
                        <th style="width: 70px; white-space: nowrap;">Mã</th>
                        <th style="white-space: nowrap;">Nhà cung cấp</th>
                        <th style="width: 140px; white-space: nowrap;">Số điện thoại</th>
                        <th style="width: 220px; white-space: nowrap;">Email</th>
                        <th style="white-space: nowrap;">Địa chỉ</th>
                        <th class="text-center" style="width: 110px; white-space: nowrap;">Lần nhập</th>
                        <th style="width: 150px; white-space: nowrap;">Trạng thái</th>
                        <th class="text-end" style="width: 130px; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nhaCungCaps as $ncc)
                        <tr>
                            <td class="text-muted fw-semibold" style="white-space: nowrap;">
                                #{{ $ncc->ma_nha_cung_cap }}
                            </td>

                            <td>
                                <a href="{{ route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap) }}"
                                   class="fw-bold text-dark text-decoration-none"
                                   style="font-size: 14px;">
                                    {{ $ncc->ten_nha_cung_cap }}
                                </a>
                            </td>

                            <td style="white-space: nowrap;">
                                @if($ncc->so_dien_thoai)
                                    <a href="tel:{{ $ncc->so_dien_thoai }}" class="text-muted text-decoration-none" style="font-size: 13px; white-space: nowrap;">
                                        <i class="bi bi-telephone me-1 text-secondary"></i>{{ $ncc->so_dien_thoai }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td style="white-space: nowrap;">
                                @if($ncc->email)
                                    <a href="mailto:{{ $ncc->email }}" class="text-muted text-decoration-none" style="font-size: 13px; white-space: nowrap;">
                                        <i class="bi bi-envelope me-1 text-secondary"></i>{{ $ncc->email }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td style="max-width: 200px;" class="text-truncate" title="{{ $ncc->dia_chi }}">
                                {{ $ncc->dia_chi ?? '—' }}
                            </td>

                            <td class="text-center">
                                <span class="badge rounded-pill bg-light text-dark border">
                                    {{ $ncc->phieu_nhaps_count ?? 0 }} phiếu
                                </span>
                            </td>

                            <td>
                                @if($ncc->trang_thai === \App\Models\NhaCungCap::TRANG_THAI_DANG_HOP_TAC)
                                    <span class="ncc-badge active">
                                        <span class="ncc-badge-dot"></span> Đang hợp tác
                                    </span>
                                @else
                                    <span class="ncc-badge inactive">
                                        <span class="ncc-badge-dot"></span> Ngừng hợp tác
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    {{-- Xem chi tiết --}}
                                    <a href="{{ route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap) }}"
                                       class="ncc-action-btn view"
                                       title="Xem chi tiết & lịch sử nhập">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    {{-- Sửa --}}
                                    <a href="{{ route('admin.nha-cung-cap.edit', $ncc->ma_nha_cung_cap) }}"
                                       class="ncc-action-btn edit"
                                       title="Sửa thông tin">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    {{-- Ngừng hợp tác / Kích hoạt lại --}}
                                    @if($ncc->trang_thai === \App\Models\NhaCungCap::TRANG_THAI_DANG_HOP_TAC)
                                        <form method="POST"
                                              action="{{ route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Bạn chắc chắn muốn ngừng hợp tác với nhà cung cấp \'{{ addslashes($ncc->ten_nha_cung_cap) }}\'?\n(Nhà cung cấp này sẽ không hiển thị khi tạo phiếu nhập mới, nhưng lịch sử nhập hàng vẫn được lưu đầy đủ).')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="NGUNG_HOP_TAC">
                                            <button type="submit" class="ncc-action-btn status-deactivate" title="Ngừng hợp tác">
                                                <i class="bi bi-pause-circle"></i>
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
                                            <button type="submit" class="ncc-action-btn status-activate" title="Kích hoạt lại">
                                                <i class="bi bi-play-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="ncc-empty-state">
                                <div class="ncc-empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <p class="mb-0 fw-semibold">Không tìm thấy nhà cung cấp phù hợp.</p>
                                <small class="text-muted">Hãy thử thay đổi từ khóa tìm kiếm hoặc bỏ chọn bộ lọc trạng thái.</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN --}}
        @if($nhaCungCaps->total() > 0)
            <div class="rs-table-footer">
                <div class="rs-footer-left">
                    <div class="rs-per-page-inline">
                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                        <select class="rs-per-page-select" onchange="location = this.value;">
                            @foreach([10, 15, 20, 50] as $perPage)
                                <option value="{{ request()->fullUrlWithQuery(['per_page' => $perPage, 'page' => 1]) }}" {{ (request('per_page', $nhaCungCaps->perPage()) == $perPage) ? 'selected' : '' }}>
                                    {{ $perPage }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <span class="rs-records-count">
                        {{ $nhaCungCaps->firstItem() ?? 0 }}-{{ $nhaCungCaps->lastItem() ?? 0 }} / {{ $nhaCungCaps->total() }} bản ghi
                    </span>
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
@endsection
