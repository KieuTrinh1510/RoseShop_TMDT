@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/SanPham.css') }}">
@endpush

@section('content')

<div class="san-pham-container">
    <div class="container-fluid p-0">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @include('admin.SanPham._nav', ['activeTab' => 'bo-hoa'])

        @php
            $boController = \App\Http\Controllers\Admin\BoHoaController::class;
            $sizesBo  = $boController::SO_BONG;
            $kieusBo  = $boController::KIEU_DANG;
            $rolesBo  = $boController::VAI_TRO;
            $concepts = $boController::CONCEPT;
        @endphp

        <div id="khoi-bo-hoa">
            <div class="card">
                <div class="card-body">
                    <div id="bo-message" class="alert d-none" role="status" aria-live="polite"></div>

                    <details class="mb-4 border rounded p-3">
                        <summary>Cấu hình phụ phí size và kiểu dáng</summary>
                        <p class="text-muted mt-2 mb-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Chức năng này dùng để <strong>sửa phụ phí kiểu dáng và size của bó hoa</strong>.
                            Cấu hình trước khi tạo mẫu/bó; không ảnh hưởng giá của bó đã chuẩn bị.
                        </p>
                        <form id="bo-fees" class="row g-2" action="{{ route('admin.bo-hoa.phu-phi.store') }}">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label">Loại</label>
                                <select name="loai" class="form-select">
                                    <option value="SIZE">Size</option>
                                    <option value="KIEU_DANG">Kiểu dáng</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Giá trị</label>
                                <select name="gia_tri" class="form-select"></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Phụ phí (đ)</label>
                                <input class="form-control" name="phu_phi" type="number" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-3 align-self-end">
                                <button class="btn btn-outline-success">Lưu phụ phí</button>
                            </div>
                        </form>
                        <div class="mt-2">
                            @foreach($phuPhiBoHoa as $phi)
                                <span class="badge bg-light text-dark border m-1">
                                    {{ $phi->loai }} / {{ $phi->gia_tri }}: {{ number_format($phi->phu_phi) }} đ
                                </span>
                            @endforeach
                        </div>
                    </details>

                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bo-list" type="button" role="tab">
                                Danh sách bó hoa
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bo-mau" type="button" role="tab">
                                Tạo mẫu
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bo-san" type="button" role="tab">
                                Tạo bó hoa sẵn
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">

                        <div class="tab-pane fade show active" id="bo-list" role="tabpanel">
                            <form class="row g-2 mb-3" action="{{ route('san-pham.bo-hoa.index') }}">
                                <div class="col-md-6">
                                    <input aria-label="Tìm bó hoa" class="form-control" name="q_bo_hoa"
                                           value="{{ request('q_bo_hoa') }}" placeholder="Tên bó hoa...">
                                </div>
                                <div class="col-md-4">
                                    <select aria-label="Trạng thái bó hoa" class="form-select" name="trang_thai_bo_hoa">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="ACTIVE" @selected(request('trang_thai_bo_hoa') === 'ACTIVE')>Hiện</option>
                                        <option value="DRAFT" @selected(request('trang_thai_bo_hoa') === 'DRAFT')>Ẩn</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-primary w-100">Lọc</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Bó hoa</th>
                                            <th>Loại / Size / Kiểu</th>
                                            <th>Giá bán</th>
                                            <th>Trạng thái</th>
                                            <th>Khả dụng</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($danhSachBoHoa as $bo)
                                            @php($boModel = $bo->bo_dau_tien)
                                            <tr>
                                                <td>
                                                    <img src="{{ $boController::anh($boModel) }}" width="55" height="55"
                                                         style="object-fit:cover" alt="{{ $bo->ten_bo_hoa }}">
                                                    <strong>{{ $bo->ten_bo_hoa }}</strong>
                                                    <small class="d-block text-muted">#{{ $bo->ma_bo_hoa }} · {{ $concepts[$bo->concept] ?? $bo->concept }}</small>
                                                    @if(($bo->so_bien_the ?? 1) > 1)
                                                        <small class="text-muted d-block">({{ $bo->so_bien_the }} bó cùng mẫu)</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $bo->loai_bo_hoa }} / {{ $bo->size }} /
                                                    {{ $kieusBo[$bo->kieu_bo_hoa] ?? $bo->kieu_bo_hoa }}
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="fw-bold text-success">
                                                            {{ $bo->gia_ban !== null ? number_format($bo->gia_ban, 0, ',', '.') . ' đ' : 'Chưa có' }}
                                                        </span>
                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                                data-bo-chitiet-gia="{{ route('admin.bo-hoa.chi-tiet-gia', $bo->ma_bo_hoa) }}">
                                                            <i class="bi bi-info-circle me-1"></i>Chi tiết giá
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($bo->trang_thai === 'ACTIVE')
                                                        <span class="badge bg-success">Hiện</span>
                                                    @else
                                                        <span class="badge bg-secondary">Ẩn</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ $bo->so_luong }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        <button class="btn btn-danger btn-sm"
                                                                data-bo-baohong="{{ route('admin.bo-hoa.bao-hong', $bo->ma_bo_hoa) }}"
                                                                data-ten="{{ $bo->ten_bo_hoa }}"
                                                                data-sl="{{ $bo->so_luong }}"
                                                                {{ $bo->so_luong < 1 ? 'disabled' : '' }}>
                                                            <i class="bi bi-exclamation-triangle-fill"></i> Hỏng
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                Chưa có bó hoa phù hợp.
                                            </td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $danhSachBoHoa->links('pagination::bootstrap-5') }}
                        </div>

                        <div class="tab-pane fade" id="bo-mau" role="tabpanel">
                            <p class="alert alert-info">
                                Mẫu chỉ là công thức (không trừ kho). Mẫu được tạo ở trạng thái <strong>Ẩn</strong>;
                                nhấn "Cho sử dụng" để bật. Mọi trạng thái mẫu đều có thể dùng để tạo bó hoa sẵn.
                                <br>
                                <strong class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Lưu ý:</strong>
                                Mẫu sau khi tạo <strong>không thể sửa</strong>. Mẫu chỉ xóa được khi
                                <strong>không còn bó hoa nào</strong> được tạo sẵn từ mẫu đó.
                            </p>
                            <form class="bo-form" id="bo-form-mau"
                                  action="{{ route('admin.bo-hoa.mau.store') }}"
                                  enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Tên mẫu <span class="text-danger">*</span></label>
                                        <input class="form-control" name="ten_bo_hoa" maxlength="100" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kiểu dáng <span class="text-danger">*</span></label>
                                        <select class="form-select" name="kieu_bo_hoa" required>
                                            <option value="">Chọn kiểu</option>
                                            @foreach($phuPhiBoHoa->where('loai','KIEU_DANG') as $p)
                                                <option value="{{ $p->gia_tri }}">
                                                    {{ $kieusBo[$p->gia_tri] ?? $p->gia_tri }}
                                                    (+{{ number_format($p->phu_phi) }} đ)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Size <span class="text-danger">*</span></label>
                                        <select class="form-select" name="size" required>
                                            <option value="">Chọn size</option>
                                            @foreach($phuPhiBoHoa->where('loai','SIZE') as $p)
                                                <option value="{{ $p->gia_tri }}">
                                                    {{ $p->gia_tri }} · {{ $sizesBo[$p->gia_tri] ?? '?' }} bông
                                                    (+{{ number_format($p->phu_phi) }} đ)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Concept <span class="text-danger">*</span></label>
                                        <select class="form-select" name="concept" required>
                                            <option value="">Chọn concept</option>
                                            @foreach($concepts as $k => $v)
                                                <option value="{{ $k }}">{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ảnh mẫu bó hoa</label>
                                        <input type="file" class="form-control" name="hinh_anh"
                                               accept="image/jpeg,image/png,image/webp">
                                        <small class="text-muted">JPG/PNG/WEBP, tối đa 2MB.</small>
                                    </div>
                                </div>

                                <p class="mt-3 mb-1">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Thành phần cho <strong>một bó</strong>. Tổng <em>hoa chính + hoa trang trí</em> phải đúng
                                    số bông của size; hoa chính ≥ 1. Giấy gói / ruy băng / xốp cố định theo size.
                                    Phụ kiện có thể bỏ trống.
                                </p>
                                <div class="bo-rows"></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm bo-add-row mt-2">
                                    + Thêm nguyên liệu
                                </button>

                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-success" type="submit">Tạo mẫu</button>
                                    <button type="button" class="btn btn-light bo-reset">Nhập mới</button>
                                </div>
                            </form>

                            <h3 class="h6 mt-4">Mẫu hiện có</h3>
                            <ul class="list-group">
                                @foreach($mauBoHoas as $m)
                                    <li class="list-group-item d-flex gap-2 align-items-center">
                                        <img src="{{ $boController::anhMau($m) }}"
                                             width="45" height="45" style="object-fit:cover;border-radius:6px;">
                                        <span class="me-auto">
                                            {{ $m->ten_mau }} · {{ $m->size }} ·
                                            <span class="badge {{ $m->trang_thai === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $m->trang_thai === 'ACTIVE' ? 'Hiện' : 'Ẩn' }}
                                            </span>
                                            @if(($m->so_bo_hoa_chua_ban ?? 0) > 0)
                                                <span class="badge bg-info text-dark ms-1"
                                                      title="Số bó hoa chưa bán được tạo sẵn từ mẫu này">
                                                    <i class="bi bi-box-seam me-1"></i>{{ $m->so_bo_hoa_chua_ban }} bó
                                                </span>
                                            @endif
                                        </span>

                                        {{-- Nút bật/tắt trạng thái --}}
                                        <button class="btn btn-sm btn-outline-success"
                                                data-bo-action="{{ route('admin.bo-hoa.mau.trang-thai', $m->ma_mau_bo_hoa) }}"
                                                data-method="PATCH">
                                            {{ $m->trang_thai === 'ACTIVE' ? 'Ẩn' : 'Cho sử dụng' }}
                                        </button>

                                        {{-- Nút Sửa đã BỎ HẲN — mẫu không cho sửa sau khi tạo --}}

                                        {{-- Nút Xóa — disable nếu còn bó hoa chưa bán --}}
                                        <button class="btn btn-sm btn-outline-danger"
                                                data-bo-action="{{ route('admin.bo-hoa.mau.destroy', $m->ma_mau_bo_hoa) }}"
                                                data-method="DELETE"
                                                data-confirm="Xóa mẫu này?"
                                                {{ ($m->so_bo_hoa_chua_ban ?? 0) > 0 ? 'disabled' : '' }}
                                                title="{{ ($m->so_bo_hoa_chua_ban ?? 0) > 0
                                                    ? 'Không thể xóa: còn ' . $m->so_bo_hoa_chua_ban . ' bó hoa được tạo sẵn từ mẫu này'
                                                    : 'Xóa mẫu này' }}">
                                            Xóa
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="tab-pane fade" id="bo-san" role="tabpanel">
                            <p class="alert alert-warning">
                                Bó hoa mới tạo sẽ kế thừa trạng thái của mẫu.
                                <br>
                                Muốn ẩn/hiện bó hoa → vào tab "Tạo mẫu" và bật/tắt "Cho sử dụng" cho mẫu.
                            </p>
                            <form id="bo-form-san" action="{{ route('admin.bo-hoa.store') }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Chọn mẫu bó hoa <span class="text-danger">*</span></label>
                                        <select class="form-select" name="ma_mau_bo_hoa" id="bo-select-mau" required>
                                            <option value="">-- Chọn mẫu --</option>
                                            @foreach($mauBoHoas as $m)
                                                <option value="{{ $m->ma_mau_bo_hoa }}"
                                                        data-ten="{{ $m->ten_mau }}"
                                                        data-size="{{ $m->size }}"
                                                        data-kieu="{{ $kieusBo[$m->kieu_bo_hoa] ?? $m->kieu_bo_hoa }}"
                                                        data-concept="{{ $concepts[$m->concept] ?? $m->concept }}"
                                                        data-anh="{{ $boController::anhMau($m) }}"
                                                        data-trang-thai="{{ $m->trang_thai }}"
                                                        data-chitiet='@json($m->chiTietMauBoHoa->map(fn($c) => ["ten" => $c->sanPham?->ten_san_pham, "sl" => $c->so_luong, "vt" => $c->vai_tro]))'
                                                        @if($m->trang_thai !== 'ACTIVE') disabled @endif>
                                                    {{ $m->ten_mau }} · {{ $m->size }} ·
                                                    {{ $kieusBo[$m->kieu_bo_hoa] ?? '' }}
                                                    @if($m->trang_thai !== 'ACTIVE') (đang ẩn - không dùng được) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="so_luong"
                                               min="1" max="100" value="1" required>
                                    </div>
                                    <div class="col-md-3 align-self-end">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-check-lg me-1"></i>Tạo bó hoa
                                        </button>
                                    </div>
                                </div>

                                <div id="bo-thong-tin-mau" class="card mt-3 d-none">
                                    <div class="card-body">
                                        <h6 class="mb-2">
                                            <i class="bi bi-clipboard-check me-1"></i>Thông tin mẫu (chỉ xem)
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-2">
                                                <img id="bo-anh-xem" src="" alt="Ảnh mẫu"
                                                     class="img-fluid rounded border">
                                            </div>
                                            <div class="col-md-10">
                                                <table class="table table-sm mb-0">
                                                    <tr><th style="width:130px;">Tên mẫu:</th><td id="bo-ten-xem"></td></tr>
                                                    <tr><th>Size:</th><td id="bo-size-xem"></td></tr>
                                                    <tr><th>Kiểu dáng:</th><td id="bo-kieu-xem"></td></tr>
                                                    <tr><th>Concept:</th><td id="bo-concept-xem"></td></tr>
                                                    <tr><th>Thành phần:</th><td id="bo-chitiet-xem"></td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ===== MODAL: BÁO CÁO BÓ HOA HỎNG ===== --}}
<div class="modal fade modal-hong" id="modalHangHongBo" tabindex="-1" aria-labelledby="labelModalHangHongBo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelModalHangHongBo">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Báo Cáo Bó Hoa Hỏng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="formHangHongBo">
                @csrf
                <input type="hidden" id="bo_hh_url">

                <div class="modal-body">
                    <div class="info-sp-hong mb-3">
                        <div class="text-muted mb-1" style="font-size:12px;">Bó hoa được báo cáo</div>
                        <strong id="bo_hh_ten">—</strong>
                        <div class="mt-1">
                            Số lượng hiện có:
                            <span class="badge bg-secondary" id="bo_hh_sl">0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Số lượng hỏng <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="so_luong_hong" id="bo_hh_so_luong"
                               class="form-control" min="1" required
                               placeholder="Nhập số lượng hỏng">
                        <div class="form-text text-danger" id="bo_hh_sl_error" style="display:none;">
                            Số lượng hỏng không được vượt quá số lượng hiện có!
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Lý do hỏng <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="ly_do" id="bo_hh_ly_do" class="form-control"
                               maxlength="255" required
                               placeholder="VD: Hoa héo, gãy cành, dập nát...">
                        <div class="form-text text-danger" id="bo_hh_ly_do_error" style="display:none;">
                            Vui lòng nhập lý do hỏng.
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Ghi chú thêm</label>
                        <textarea name="ghi_chu" id="bo_hh_ghi_chu" class="form-control" rows="2"
                                  maxlength="1000"
                                  placeholder="Ghi chú thêm nếu cần..."></textarea>
                    </div>

                    <div class="text-muted" style="font-size:12px;">
                        <i class="bi bi-person-fill me-1"></i>
                        Nhân viên báo cáo:
                        <strong>{{ Auth::user()?->nhanVien?->ten_nhan_vien ?? Auth::user()?->name ?? 'Nhân viên' }}</strong>
                    </div>

                    <div class="alert alert-info mt-2 mb-0" style="font-size:12.5px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hệ thống sẽ tự động ghi log hàng hỏng cho <strong>từng nguyên vật liệu</strong>
                        cấu thành nên bó hoa này (cùng lý do, cùng thời gian).
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Hủy
                    </button>
                    <button type="submit" class="btn btn-danger" id="bo_hh_submit" disabled>
                        <i class="bi bi-send-fill me-1"></i>Xác nhận báo cáo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL: CHI TIẾT GIÁ BÓ HOA ===== --}}
<div class="modal fade" id="modalChiTietGiaBo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>Chi tiết giá: <span id="ctg_ten">—</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="badge bg-secondary">#<span id="ctg_ma">—</span></span>
                    <strong>Size:</strong> <span id="ctg_size">—</span> ·
                    <strong>Kiểu dáng:</strong> <span id="ctg_kieu">—</span>
                </div>

                <h6 class="mt-3"><i class="bi bi-flower1 me-1"></i>Nguyên liệu cấu thành</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-2 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nguyên liệu</th>
                                <th>Vai trò</th>
                                <th class="text-end">SL</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="ctg_nguyen_lieu"></tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-bold">Tổng giá gốc nguyên liệu:</td>
                                <td class="text-end fw-bold text-primary" id="ctg_tong_nl">0 đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h6 class="mt-3"><i class="bi bi-cash-stack me-1"></i>Phụ phí</h6>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Phụ phí size (<span id="ctg_size_label">—</span>)</span>
                        <span id="ctg_pp_size" class="fw-semibold">0 đ</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Phụ phí kiểu dáng (<span id="ctg_kieu_label">—</span>)</span>
                        <span id="ctg_pp_kieu" class="fw-semibold">0 đ</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Tổng phụ phí</strong>
                        <strong id="ctg_pp_tong" class="text-primary">0 đ</strong>
                    </li>
                </ul>

                <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                    <strong>Giá bán mỗi bó</strong>
                    <strong class="fs-5" id="ctg_gia_ban">0 đ</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const root = document.getElementById('khoi-bo-hoa');
    if (!root) return;

    const products  = @json($nguyenLieuBoHoa);
    const templates = @json($mauBoHoas);
    const roles     = @json($rolesBo);
    const sizes     = @json($sizesBo);
    const styles    = @json($kieusBo);
    const csrf      = root.querySelector('[name=_token]').value;
    const msg       = root.querySelector('#bo-message');
    const money     = n => Number(n).toLocaleString('vi-VN') + ' đ';

    const FIXED = {
        GIAY_GOI:           @json(\App\Http\Controllers\Admin\BoHoaController::GIAY_GOI_QTY),
        RUY_BANG:           @json(\App\Http\Controllers\Admin\BoHoaController::RUY_BANG_QTY),
        XOP:                @json(\App\Http\Controllers\Admin\BoHoaController::XOP_QTY),
        PHU_KIEN_TRANG_TRI: @json(\App\Http\Controllers\Admin\BoHoaController::PHU_KIEN_MAX),
    };

    function tell(text, ok = false) {
        msg.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
        msg.textContent = text;
        msg.scrollIntoView({block:'nearest'});
    }

    async function api(url, method = 'GET', data = null) {
        const headers = {'Accept':'application/json', 'X-CSRF-TOKEN':csrf};
        const options = {method, headers};
        if (data) options.body = data;
        const response = await fetch(url, options);
        let result;
        try { result = await response.json(); }
        catch { throw new Error('Phản hồi không hợp lệ từ máy chủ.'); }
        if (!response.ok || !result.success) {
            const err = new Error(result.message || 'Không thực hiện được thao tác.');
            err.errors = result.errors || {};
            throw err;
        }
        return result;
    }

    function addRow(form, value = {}) {
        const wrap = form.querySelector('.bo-rows');
        const index = Number(form.dataset.next || 0);
        form.dataset.next = index + 1;

        const row = document.createElement('div');
        row.className = 'row g-2 mt-1';
        row.innerHTML = `
            <div class="col-md-3"><select aria-label="Vai trò" class="form-select role"></select></div>
            <div class="col-md-5"><select aria-label="Nguyên liệu" class="form-select product" required></select></div>
            <div class="col-md-3"><input aria-label="Số lượng" class="form-control qty" type="number" min="1" max="1000" value="1" required></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger">×</button></div>`;

        const role    = row.querySelector('.role');
        const product = row.querySelector('.product');
        const qty     = row.querySelector('.qty');

        role.name    = `thanh_phan[${index}][vai_tro]`;
        product.name = `thanh_phan[${index}][ma_san_pham]`;
        qty.name     = `thanh_phan[${index}][so_luong]`;

        Object.entries(roles)
            .filter(([k]) => k !== 'THIEP')
            .forEach(([k,v]) => role.add(new Option(v,k)));
        role.value = value.vai_tro || 'HOA_CHINH';

        function filterProducts() {
            product.replaceChildren(new Option('Chọn nguyên liệu',''));
            const types = ['HOA_CHINH','HOA_TRANG_TRI'].includes(role.value)
                ? ['HOA_TUOI','HOA_GIA','HOA_SAP','HOA_GIAY_NHUN']
                : role.value === 'PHU_KIEN_TRANG_TRI'
                    ? ['PHU_KIEN_TRANG_TRI']
                    : role.value === 'XOP'
                        ? ['MIENG_XOP']
                        : [role.value];
            products.filter(p => types.includes(p.loai_san_pham))
                .forEach(p => product.add(new Option(
                    `${p.ten_san_pham} · ${p.don_vi_tinh} · kho ${p.so_luong}`,
                    p.ma_san_pham
                )));
        }

        function autoQty() {
            const size    = form.elements.size?.value;
            const roleVal = role.value;
            if (!size) return;
            let fixed = null;
            if (roleVal === 'GIAY_GOI')       fixed = FIXED.GIAY_GOI[size];
            else if (roleVal === 'RUY_BANG')  fixed = FIXED.RUY_BANG[size];
            else if (roleVal === 'XOP')       fixed = FIXED.XOP[size];

            if (fixed !== undefined && fixed !== null) {
                qty.value     = fixed;
                qty.readOnly  = true;
                qty.classList.add('bg-light');
            } else {
                qty.readOnly = false;
                qty.classList.remove('bg-light');
                if (roleVal === 'PHU_KIEN_TRANG_TRI') {
                    qty.max = FIXED.PHU_KIEN_TRANG_TRI[size];
                } else {
                    qty.max = 1000;
                }
            }
        }

        role.addEventListener('change', () => { filterProducts(); autoQty(); });
        form.elements.size?.addEventListener('change', autoQty);
        filterProducts();
        autoQty();

        if (value.ma_san_pham) product.value = value.ma_san_pham;
        if (value.so_luong && !qty.readOnly) qty.value = value.so_luong;

        row.querySelector('button').onclick = () => row.remove();
        wrap.append(row);
    }

    function fill(form, d, details) {
        ['ten_bo_hoa','size','kieu_bo_hoa','concept','so_luong'].forEach(k => {
            if (form.elements[k]) form.elements[k].value = d[k] ?? (k === 'so_luong' ? 1 : '');
        });
        form.querySelector('.bo-rows').replaceChildren();
        (details || []).forEach(r => addRow(form, r));
    }

    const formMau = root.querySelector('#bo-form-mau');
    if (formMau) {
        formMau.dataset.original = formMau.action;
        addRow(formMau);

        formMau.querySelector('.bo-add-row').onclick = () => addRow(formMau);
        formMau.querySelector('.bo-reset').onclick = () => {
            formMau.reset();
            formMau.action = formMau.dataset.original;
            delete formMau.dataset.edit;
            formMau.querySelector('.bo-rows').replaceChildren();
            addRow(formMau);
        };

        function clearInvalid(scope) {
            scope.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            scope.querySelectorAll('.invalid-feedback.client-error').forEach(el => el.remove());
        }

        function markInvalid(el, message) {
            if (!el) return;
            el.classList.add('is-invalid');
            const parent = el.parentElement;
            let fb = parent.querySelector('.invalid-feedback.client-error');
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'invalid-feedback client-error';
                parent.appendChild(fb);
            }
            fb.textContent = message;
        }

        function validateMauForm() {
            clearInvalid(formMau);
            const errs = [];
            const f = formMau.elements;

            if (!f.ten_bo_hoa.value.trim()) {
                markInvalid(f.ten_bo_hoa, 'Vui lòng nhập tên mẫu.');
                errs.push('Tên mẫu không được để trống');
            } else if (f.ten_bo_hoa.value.trim().length > 100) {
                markInvalid(f.ten_bo_hoa, 'Tên mẫu không vượt quá 100 ký tự.');
                errs.push('Tên mẫu quá dài');
            }

            if (!f.size.value) {
                markInvalid(f.size, 'Vui lòng chọn size.');
                errs.push('Chưa chọn size');
            }
            if (!f.kieu_bo_hoa.value) {
                markInvalid(f.kieu_bo_hoa, 'Vui lòng chọn kiểu dáng.');
                errs.push('Chưa chọn kiểu dáng');
            }
            if (!f.concept.value) {
                markInvalid(f.concept, 'Vui lòng chọn concept.');
                errs.push('Chưa chọn concept');
            }

            const rows = formMau.querySelectorAll('.bo-rows > .row');
            if (rows.length === 0) {
                errs.push('Chưa có nguyên liệu nào trong công thức');
            }
            rows.forEach((row, i) => {
                const prod = row.querySelector('.product');
                const qty  = row.querySelector('.qty');
                if (!prod.value) {
                    markInvalid(prod, `Dòng ${i + 1}: chưa chọn nguyên liệu.`);
                    errs.push(`Dòng ${i + 1}: chưa chọn nguyên liệu`);
                }
                const n = parseInt(qty.value) || 0;
                if (n < 1) {
                    markInvalid(qty, `Dòng ${i + 1}: số lượng phải từ 1 trở lên.`);
                    errs.push(`Dòng ${i + 1}: số lượng không hợp lệ`);
                }
            });

            return errs;
        }

        formMau.onsubmit = async e => {
            e.preventDefault();
            const errs = validateMauForm();
            if (errs.length) {
                tell('Không thể lưu vì: ' + errs.join(' · '));
                return;
            }
            const button = formMau.querySelector('button[type=submit]');
            button.disabled = true;
            try {
                const data = new FormData(formMau);
                if (formMau.dataset.edit) data.set('_method','PUT');
                const r = await api(formMau.action, 'POST', data);
                tell(r.message, true);
                location.reload();
            } catch (e) {
                let m = e.message;
                if (e.errors) {
                    const list = Object.values(e.errors).flat();
                    if (list.length) m = list.join(' · ');
                }
                tell(m);
            } finally {
                button.disabled = false;
            }
        };
    }

    const formSan   = root.querySelector('#bo-form-san');
    const selectMau = root.querySelector('#bo-select-mau');
    if (formSan && selectMau) {
        selectMau.onchange = () => {
            const opt  = selectMau.options[selectMau.selectedIndex];
            const wrap = document.getElementById('bo-thong-tin-mau');
            if (!opt.value) { wrap.classList.add('d-none'); return; }
            wrap.classList.remove('d-none');
            document.getElementById('bo-ten-xem').textContent     = opt.dataset.ten || '';
            document.getElementById('bo-size-xem').textContent    = opt.dataset.size || '';
            document.getElementById('bo-kieu-xem').textContent    = opt.dataset.kieu || '';
            document.getElementById('bo-concept-xem').textContent = opt.dataset.concept || '';
            document.getElementById('bo-anh-xem').src             = opt.dataset.anh || '';
            try {
                const ds = JSON.parse(opt.dataset.chitiet || '[]');
                document.getElementById('bo-chitiet-xem').innerHTML = ds.length
                    ? ds.map(x => `• ${x.ten} × ${x.sl}`).join('<br>')
                    : '<em>Chưa có thành phần</em>';
            } catch { document.getElementById('bo-chitiet-xem').textContent = ''; }
        };

        formSan.onsubmit = async e => {
            e.preventDefault();
            const selectEl = formSan.querySelector('[name=ma_mau_bo_hoa]');
            const qtyEl    = formSan.querySelector('[name=so_luong]');

            formSan.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            let invalid = false;
            let msgs = [];
            if (!selectEl.value) {
                selectEl.classList.add('is-invalid');
                msgs.push('Chưa chọn mẫu bó hoa');
                invalid = true;
            }
            const n = parseInt(qtyEl.value) || 0;
            if (n < 1 || n > 100) {
                qtyEl.classList.add('is-invalid');
                msgs.push('Số lượng phải từ 1 đến 100');
                invalid = true;
            }
            if (invalid) {
                tell('Không thể tạo bó. Vui lòng kiểm tra: ' + msgs.join(' · '));
                return;
            }

            const button = formSan.querySelector('button[type=submit]');
            button.disabled = true;
            try {
                const r = await api(formSan.action, 'POST', new FormData(formSan));
                tell(r.message, true);
                location.reload();
            } catch (e) {
                let m = e.message;
                if (e.errors) {
                    const list = Object.values(e.errors).flat();
                    if (list.length) m = list.join(' · ');
                }
                tell(m);
            } finally {
                button.disabled = false;
            }
        };
    }

    // ── Nút Sửa mẫu (data-template-edit) — KHÔNG còn nút nào trong DOM,
    //    nên đoạn này không match và không gây lỗi. Giữ lại để tương thích ngược.
    root.querySelectorAll('[data-template-edit]').forEach(button => {
        button.onclick = async () => {
            try {
                const r = await api('/admin/bo-hoa/mau/' + button.dataset.templateEdit + '/edit-data');
                const m = r.data;
                fill(formMau, {...m, ten_bo_hoa: m.ten_mau}, m.chi_tiet_mau_bo_hoa);
                formMau.action = button.dataset.saveUrl;
                formMau.dataset.edit = '1';
                new bootstrap.Tab(root.querySelector('[data-bs-target="#bo-mau"]')).show();
                formMau.scrollIntoView({block:'center'});
            } catch (e) { tell(e.message); }
        };
    });

    root.querySelectorAll('[data-bo-action]').forEach(button => {
        button.onclick = async () => {
            if (button.disabled) return;   // chặn nút bị disable bấm
            if (button.dataset.confirm && !confirm(button.dataset.confirm)) return;
            button.disabled = true;
            try {
                const d = new FormData();
                d.set('_method', button.dataset.method);
                await api(button.dataset.boAction, 'POST', d);
                location.reload();
            } catch (e) { tell(e.message); }
            finally { button.disabled = false; }
        };
    });

    root.querySelectorAll('[data-bo-baohong]').forEach(button => {
        button.onclick = () => {
            const url = button.dataset.boBaohong;
            const ten = button.dataset.ten;
            const sl  = parseInt(button.dataset.sl) || 0;
            if (sl < 1) { tell('Bó hoa không còn số lượng để báo hỏng.'); return; }

            document.getElementById('bo_hh_url').value        = url;
            document.getElementById('bo_hh_ten').textContent  = ten;
            document.getElementById('bo_hh_sl').textContent   = sl;
            document.getElementById('bo_hh_so_luong').value   = '';
            document.getElementById('bo_hh_so_luong').max     = sl;
            document.getElementById('bo_hh_ly_do').value      = '';
            document.getElementById('bo_hh_ghi_chu').value    = '';
            document.getElementById('bo_hh_sl_error').style.display    = 'none';
            document.getElementById('bo_hh_ly_do_error').style.display = 'none';
            document.getElementById('bo_hh_submit').disabled  = true;

            new bootstrap.Modal(document.getElementById('modalHangHongBo')).show();
        };
    });

    (() => {
        const modal = document.getElementById('modalHangHongBo');
        if (!modal) return;
        const sl    = document.getElementById('bo_hh_so_luong');
        const ly    = document.getElementById('bo_hh_ly_do');
        const btn   = document.getElementById('bo_hh_submit');
        const slErr = document.getElementById('bo_hh_sl_error');
        const lyErr = document.getElementById('bo_hh_ly_do_error');

        function validate() {
            const n   = parseInt(sl.value) || 0;
            const max = parseInt(sl.max) || 0;
            const l   = ly.value.trim();
            let ok = true;

            if (n < 1 || n > max) { slErr.style.display = 'block'; ok = false; }
            else { slErr.style.display = 'none'; }

            if (!l) { lyErr.style.display = 'block'; ok = false; }
            else { lyErr.style.display = 'none'; }

            btn.disabled = !ok;
        }

        sl.addEventListener('input', validate);
        ly.addEventListener('input', validate);

        document.getElementById('formHangHongBo').onsubmit = async (e) => {
            e.preventDefault();
            btn.disabled = true;
            try {
                const fd = new FormData();
                fd.set('so_luong_hong', sl.value);
                fd.set('ly_do', ly.value);
                fd.set('ghi_chu', document.getElementById('bo_hh_ghi_chu').value);

                await api(document.getElementById('bo_hh_url').value, 'POST', fd);
                bootstrap.Modal.getInstance(modal).hide();
                location.reload();
            } catch (err) {
                let m = err.message;
                if (err.errors) {
                    const list = Object.values(err.errors).flat();
                    if (list.length) m = list.join(' · ');
                }
                tell(m);
                btn.disabled = false;
            }
        };
    })();

    // ── Chi tiết giá bó hoa ───────────────────────────────────
    root.querySelectorAll('[data-bo-chitiet-gia]').forEach(button => {
        button.onclick = async () => {
            const url = button.dataset.boChitietGia;
            try {
                const r = await api(url);

                document.getElementById('ctg_ten').textContent  = r.data.ten_bo_hoa;
                document.getElementById('ctg_ma').textContent   = r.data.ma_bo_hoa;
                document.getElementById('ctg_size').textContent = r.data.size;
                document.getElementById('ctg_kieu').textContent = r.data.kieu_bo_hoa_text;

                document.getElementById('ctg_size_label').textContent = r.data.size;
                document.getElementById('ctg_kieu_label').textContent = r.data.kieu_bo_hoa_text;

                const tbody = document.getElementById('ctg_nguyen_lieu');
                tbody.innerHTML = '';
                r.data.chi_tiet.forEach(ct => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${ct.ten_san_pham}</td>
                        <td><span class="badge bg-secondary">${ct.vai_tro_text}</span></td>
                        <td class="text-end">${ct.so_luong} ${ct.don_vi_tinh || ''}</td>
                        <td class="text-end">${money(ct.gia_ban)}</td>
                        <td class="text-end fw-semibold">${money(ct.thanh_tien)}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('ctg_tong_nl').textContent  = money(r.data.tien_nguyen_lieu);
                document.getElementById('ctg_pp_size').textContent  = money(r.data.phu_phi.size);
                document.getElementById('ctg_pp_kieu').textContent  = money(r.data.phu_phi.kieu_dang);
                document.getElementById('ctg_pp_tong').textContent  = money(r.data.phu_phi.tong);
                document.getElementById('ctg_gia_ban').textContent  = money(r.data.gia_ban);

                new bootstrap.Modal(document.getElementById('modalChiTietGiaBo')).show();
            } catch (e) {
                tell(e.message);
            }
        };
    });

    const fee = root.querySelector('#bo-fees');
    if (fee) {
        function feeOptions() {
            const select = fee.elements.gia_tri;
            select.replaceChildren();
            Object.keys(fee.elements.loai.value === 'SIZE' ? sizes : styles)
                .forEach(k => select.add(new Option(k, k)));
        }
        fee.elements.loai.onchange = feeOptions;
        feeOptions();
        fee.onsubmit = async e => {
            e.preventDefault();
            try {
                await api(fee.action, 'POST', new FormData(fee));
                location.reload();
            } catch (e) { tell(e.message); }
        };
    }
})();
</script>
@endpush