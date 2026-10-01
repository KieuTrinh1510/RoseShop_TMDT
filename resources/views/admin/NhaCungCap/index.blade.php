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
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="keyword" class="form-control"
                               placeholder="Tìm theo tên, SĐT, email, mã..."
                               value="{{ request('keyword') }}">
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
                        <th style="width: 70px;">Mã</th>
                        <th>Nhà cung cấp</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Địa chỉ</th>
                        <th class="text-center" style="width: 120px;">Lần nhập</th>
                        <th style="width: 150px;">Trạng thái</th>
                        <th class="text-end" style="width: 150px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nhaCungCaps as $ncc)
                        <tr>
                            <td class="text-muted fw-semibold">
                                #{{ $ncc->ma_nha_cung_cap }}
                            </td>

                            <td>
                                <a href="{{ route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap) }}"
                                   class="fw-bold text-dark text-decoration-none"
                                   style="font-size: 14px;">
                                    {{ $ncc->ten_nha_cung_cap }}
                                </a>
                            </td>

                            <td>
                                @if($ncc->so_dien_thoai)
                                    <a href="tel:{{ $ncc->so_dien_thoai }}" class="text-muted text-decoration-none">
                                        <i class="bi bi-telephone me-1 text-secondary"></i>{{ $ncc->so_dien_thoai }}
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

        @if($nhaCungCaps->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $nhaCungCaps->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
