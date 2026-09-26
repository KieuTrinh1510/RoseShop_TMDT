@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/Admin/SanPham.css') }}">
@endpush

@section('content')

@php
    $dsNguyenVatLieu = $nguyenVatLieu;
    $dsNguyenVatLieu->each(function ($vl) {
        $vl->ton_kho_hien_thi = $vl->ton_kho_thuc_te
            ?? ($vl->relationLoaded('chiTietNhaps') ? $vl->chiTietNhaps->sum('so_luong_con_lai') : $vl->so_luong);
        $vl->tong_gia_von = $vl->relationLoaded('chiTietNhaps')
            ? $vl->chiTietNhaps->sum(fn ($ct) => ($ct->gia_nhap ?? 0) * $ct->so_luong_con_lai)
            : 0;
    });
    $soNguyenLieuSapHet  = $dsNguyenVatLieu->where('ton_kho_hien_thi', '>', 0)->where('ton_kho_hien_thi', '<', 5)->count();
    $soNguyenLieuHetHang = $dsNguyenVatLieu->where('ton_kho_hien_thi', '<=', 0)->count();
@endphp

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

        @include('admin.SanPham._nav', ['activeTab' => 'nguyen-vat-lieu'])

        {{-- ===== FORM THÊM / SỬA NGUYÊN VẬT LIỆU ===== --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-boxes me-2"></i>Thêm / Sửa Nguyên Vật Liệu
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Nguyên vật liệu (giấy gói, ruy băng, phụ kiện trang trí, thiệp, mút xốp)
                    chỉ dùng để <strong>nhập kho và cấu thành bó hoa</strong>, không hiển thị và không bán
                    trực tiếp cho khách. Số lượng tồn kho chỉ thay đổi khi
                    <a href="{{ route('phieu-nhap.index') }}">nhập hàng</a> hoặc khi được dùng để tạo bó hoa.
                </p>
                <form id="formNguyenLieu" action="{{ route('san-pham.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div id="vl_methodField"></div>

                    {{-- Hàng 1: Tên (4) + Loại (3) + ĐVT (2) + Giá bán (3) --}}
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tên nguyên vật liệu <span class="text-danger">*</span></label>
                            <input type="text" name="ten_san_pham" id="vl_inp_ten" class="form-control"
                                   placeholder="Ví dụ: Giấy gói Hàn Quốc hoa văn" required>
                        </div>

                        <div class="col-md-3" id="vl_khuVucLoai">
                            <label class="form-label fw-semibold">Loại nguyên vật liệu <span class="text-danger" id="vl_loaiRequired">*</span></label>
                            <select name="loai_san_pham" id="vl_inp_loai" class="form-select" required>
                                <option value="">-- Chọn loại --</option>
                                @foreach($danhSachLoaiNguyenLieu as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div id="vl_loaiHienThi" style="display:none;">
                                <input type="text" id="vl_inp_loai_text" class="form-control bg-light" readonly>
                                <small class="text-muted"><i class="bi bi-lock-fill me-1"></i>Không thể thay đổi loại</small>
                            </div>
                        </div>

                        <div class="col-md-2" id="vl_khuVucDonVi">
                            <label class="form-label fw-semibold">Đơn vị tính</label>
                            <input type="text" class="form-control" id="vl_donViTinhHienThi" readonly
                                   placeholder="Chọn loại NVL...">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Giá bán hiện tại</label>
                            <input type="number" name="gia_ban_hien_tai" id="vl_inp_gia_ban"
                                   class="form-control" min="0" step="1000"
                                   placeholder="Ví dụ: 5000">
                            <small class="text-muted">Giá tham khảo của NVL</small>
                        </div>
                    </div>

                    {{-- Hàng 2: Tồn kho (khi sửa) + Ảnh + Mô tả --}}
                    <div class="row g-3 mt-1">
                        <div class="col-md-2" id="vl_khuVucSoLuong" style="display:none;">
                            <label class="form-label fw-semibold">Tồn kho</label>
                            <input type="text" id="vl_inp_so_luong" class="form-control bg-light" readonly>
                            <small class="text-muted"><i class="bi bi-lock-fill me-1"></i>Chỉ đổi khi nhập hàng</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Hình ảnh</label>
                            <input type="file" name="hinh_anh" id="vl_inp_anh" class="form-control"
                                   accept="image/jpg,image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">JPG, PNG, GIF, WEBP — tối đa 2MB.</small>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Mô tả</label>
                            <textarea name="mo_ta" id="vl_inp_mo_ta" class="form-control" rows="2"
                                      placeholder="Ghi chú thêm về nguyên vật liệu (màu sắc, kích thước, nhà cung cấp...)"></textarea>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" id="vl_btnSubmit" class="btn btn-secondary">
                            <i class="bi bi-plus-lg me-1" id="vl_iconSubmit"></i>
                            <span id="vl_textSubmit">Thêm nguyên vật liệu</span>
                        </button>
                        <button type="button" id="vl_btnHuy" class="btn btn-light" onclick="vlChuyenVeCheDoDaThem()" style="display:none;">
                            <i class="bi bi-x-lg me-1"></i>Hủy sửa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== BẢNG TỒN KHO NVL ===== --}}
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-boxes me-2"></i>Tồn Kho Nguyên Vật Liệu</span>
                <span class="badge bg-white text-primary">{{ $dsNguyenVatLieu->count() }} mặt hàng</span>
            </div>
            <div class="card-body">
                @if($soNguyenLieuHetHang > 0 || $soNguyenLieuSapHet > 0)
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        @if($soNguyenLieuHetHang > 0)
                            <strong>{{ $soNguyenLieuHetHang }}</strong> nguyên vật liệu đã <strong>hết hàng</strong>.
                        @endif
                        @if($soNguyenLieuSapHet > 0)
                            <strong>{{ $soNguyenLieuSapHet }}</strong> nguyên vật liệu <strong>sắp hết</strong> (dưới 5 đơn vị).
                        @endif
                    </div>
                @endif

                <p class="text-muted mb-3" style="font-size:13px;">
                    <i class="bi bi-info-circle me-1"></i>
                    Trạng thái <strong>Kinh doanh</strong>:
                    <span class="badge bg-success">Đang bán</span> → nguyên vật liệu <strong>được phép</strong> chọn khi tạo bó hoa.
                    <span class="badge bg-secondary">Ngừng bán</span> → <strong>bị ẩn</strong> khỏi mọi form tạo bó hoa (cả admin lẫn khách).
                    Nguyên vật liệu <strong>không bao giờ</strong> hiển thị trên trang chủ khách hàng.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ảnh</th>
                                <th>Tên nguyên vật liệu</th>
                                <th>Loại</th>
                                <th>Đơn vị tính</th>
                                <th>Giá bán (từ phiếu nhập)</th>
                                <th>Giá hiện tại</th>
                                <th>Tồn kho</th>
                                <th>Tổng giá vốn</th>
                                <th>Trạng thái kho</th>
                                <th>Kinh doanh</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dsNguyenVatLieu as $vl)
                            <tr class="{{ $vl->ton_kho_hien_thi <= 0 ? 'table-danger' : ($vl->ton_kho_hien_thi < 5 ? 'table-warning' : '') }}">
                                <td>
                                    <img src="{{ $vl->hinh_anh ? asset($vl->hinh_anh) : asset('img/default.jpg') }}"
                                         alt="{{ $vl->ten_san_pham }}" width="50" height="50" style="object-fit:cover">
                                </td>
                                <td class="fw-semibold">{{ $vl->ten_san_pham }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $danhSachLoaiNguyenLieu[$vl->loai_san_pham] ?? $vl->loai_san_pham }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $vl->don_vi_tinh_text ?: '—' }}
                                    </span>
                                </td>

                                {{-- Giá bán (từ phiếu nhập) — list các lô --}}
                                <td>
                                    @php $loCoHang = $vl->chiTietNhaps; @endphp
                                    @if($loCoHang->isNotEmpty())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($loCoHang as $ct)
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-success" style="min-width:90px; text-align:right;">
                                                        {{ number_format($ct->gia_ban, 0, ',', '.') }} đ
                                                    </span>
                                                    <span class="badge bg-secondary" title="Số lượng còn lại của lô này">
                                                        <i class="bi bi-box-seam me-1"></i>{{ $ct->so_luong_con_lai }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">Chưa nhập hàng</span>
                                    @endif
                                </td>

                                {{-- Giá hiện tại --}}
                                <td class="fw-bold text-success">
                                    {{ $vl->gia_ban_hien_tai
                                        ? number_format($vl->gia_ban_hien_tai, 0, ',', '.') . 'đ'
                                        : '-' }}
                                </td>

                                <td class="fw-bold">{{ $vl->ton_kho_hien_thi }}</td>

                                <td class="text-end">
                                    <span class="fw-semibold text-primary">
                                        {{ number_format($vl->tong_gia_von, 0, ',', '.') }} đ
                                    </span>
                                </td>

                                <td>
                                    @if($vl->ton_kho_hien_thi <= 0)
                                        <span class="badge bg-danger"><i class="bi bi-x-octagon-fill me-1"></i>Hết hàng</span>
                                    @elseif($vl->ton_kho_hien_thi < 5)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Sắp hết</span>
                                    @else
                                        <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Đủ hàng</span>
                                    @endif
                                </td>

                                <td>
                                    @if($vl->trang_thai === 'DANG_BAN')
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Đang bán
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-eye-slash-fill me-1"></i>Ngừng bán
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button class="btn btn-warning btn-sm"
                                                onclick="vlChuyenCheDoSua({{ $vl->ma_san_pham }})" title="Sửa">
                                            <i class="bi bi-pencil-fill"></i> Sửa
                                        </button>

                                        <form action="{{ route('san-pham.toggle', $vl->ma_san_pham) }}"
                                              method="POST"
                                              onsubmit="return confirm('{{ $vl->trang_thai === 'DANG_BAN'
                                                    ? 'Ẩn nguyên vật liệu này? Nó sẽ không được chọn khi tạo bó hoa.'
                                                    : 'Hiện lại nguyên vật liệu này để chọn được khi tạo bó hoa?' }}')">
                                            @csrf
                                            @method('PATCH')
                                            @if($vl->trang_thai === 'DANG_BAN')
                                                <button type="submit" class="btn btn-secondary btn-sm"
                                                        title="Ẩn khỏi danh sách nguyên liệu">
                                                    <i class="bi bi-eye-slash-fill"></i> Ẩn
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-success btn-sm"
                                                        title="Hiện lại">
                                                    <i class="bi bi-eye-fill"></i> Hiện
                                                </button>
                                            @endif
                                        </form>

                                        <a href="{{ route('phieu-nhap.index') }}"
                                           class="btn btn-outline-success btn-sm" title="Nhập thêm hàng">
                                            <i class="bi bi-box-arrow-in-down"></i> Nhập hàng
                                        </a>

                                        <button class="btn btn-danger btn-sm"
                                                onclick="moModalHangHong({{ $vl->ma_san_pham }}, '{{ addslashes($vl->ten_san_pham) }}', {{ $vl->ton_kho_hien_thi }})"
                                                title="Báo cáo hỏng / rách / hết hạn"
                                                {{ $vl->ton_kho_hien_thi <= 0 ? 'disabled' : '' }}>
                                            <i class="bi bi-exclamation-triangle-fill"></i> Hỏng
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Chưa có nguyên vật liệu nào.
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

{{-- ===== MODAL: BÁO CÁO HÀNG HỎNG (NVL) ===== --}}
<div class="modal fade modal-hong" id="modalHangHong" tabindex="-1" aria-labelledby="labelModalHangHong" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelModalHangHong">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Báo Cáo Nguyên Vật Liệu Hỏng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('san-pham.bao-hang-hong') }}" method="POST">
                @csrf
                <input type="hidden" name="ma_san_pham" id="modal_ma_sp">

                <div class="modal-body">
                    <div class="info-sp-hong mb-3">
                        <div class="text-muted mb-1" style="font-size:12px;">Nguyên vật liệu được báo cáo</div>
                        <strong id="modal_ten_sp">—</strong>
                        <div class="mt-1">
                            Tồn kho lô đang chọn:
                            <span class="badge bg-secondary" id="modal_so_luong_badge">0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Chọn lô nhập <span class="text-danger">*</span>
                        </label>
                        <select name="ma_chi_tiet_nhap" id="modal_lo_nhap" class="form-select" required>
                            <option value="">-- Đang tải lô... --</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Chọn đúng lô bị hỏng. Mỗi lô hiển thị: Phiếu nhập — Ngày nhập — Số lượng còn lại.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Số lượng hỏng <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="so_luong_hong" id="modal_so_luong_hong"
                               class="form-control" min="1" required
                               placeholder="Nhập số lượng hỏng">
                        <div class="form-text text-danger" id="modal_sl_error" style="display:none;">
                            Số lượng hỏng không được vượt tồn kho của lô này!
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Lý do hỏng <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="ly_do" id="modal_ly_do" class="form-control"
                               maxlength="255" required
                               placeholder="VD: Rách, ẩm mốc, hết hạn...">
                        <div class="form-text text-danger" id="modal_ly_do_error" style="display:none;">
                            Vui lòng nhập lý do hỏng.
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Ghi chú thêm</label>
                        <textarea name="ghi_chu" class="form-control" rows="2" maxlength="1000"
                                  placeholder="Ghi chú thêm nếu cần..."></textarea>
                    </div>

                    <div class="text-muted" style="font-size:12px;">
                        <i class="bi bi-person-fill me-1"></i>
                        Nhân viên báo cáo:
                        <strong>{{ Auth::user()?->nhanVien?->ten_nhan_vien ?? Auth::user()?->name ?? 'Nhân viên' }}</strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Hủy
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnGuiBaoCao" disabled>
                        <i class="bi bi-send-fill me-1"></i>Xác nhận báo cáo
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
    const danhSachLoaiVL = @json($danhSachLoaiNguyenLieu);

    // ── Map đơn vị tính NVL (đặt LÊN ĐẦU để dùng trong vlChuyenCheDoSua) ──
    const DON_VI_THEO_LOAI = {
        GIAY_GOI: 'Tờ', RUY_BANG: 'Mét',
        PHU_KIEN_TRANG_TRI: 'Cái', THIEP: 'Cái', MIENG_XOP: 'Miếng',
    };

    function vlChuyenCheDoSua(id) {
        fetch('/san-pham/' + id + '/edit-data')
            .then(res => res.json())
            .then(sp => {
                document.getElementById('vl_inp_ten').value   = sp.ten_san_pham;
                document.getElementById('vl_inp_mo_ta').value = sp.mo_ta ?? '';
                document.getElementById('vl_inp_gia_ban').value = sp.gia_ban_hien_tai ?? '';

                // ✅ FIX: set đúng ô đơn vị tính hiển thị
                document.getElementById('vl_donViTinhHienThi').value =
                    DON_VI_THEO_LOAI[sp.loai_san_pham] ?? sp.don_vi_tinh ?? '';

                document.getElementById('vl_khuVucSoLuong').style.display = 'block';
                document.getElementById('vl_inp_so_luong').value = sp.ton_kho_thuc_te ?? sp.so_luong;

                document.getElementById('vl_inp_loai').style.display = 'none';
                document.getElementById('vl_inp_loai').removeAttribute('required');
                document.getElementById('vl_loaiHienThi').style.display = 'block';
                document.getElementById('vl_inp_loai_text').value =
                    danhSachLoaiVL[sp.loai_san_pham] ?? sp.loai_san_pham;
                document.getElementById('vl_loaiRequired').style.display = 'none';

                document.getElementById('vl_inp_anh').value = '';

                document.getElementById('formNguyenLieu').action = '/san-pham/' + id;
                document.getElementById('vl_methodField').innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';

                document.getElementById('vl_btnHuy').style.display = 'inline-block';
                document.getElementById('vl_iconSubmit').className = 'bi bi-save me-1';
                document.getElementById('vl_textSubmit').textContent = 'Lưu thay đổi';

                document.getElementById('formNguyenLieu').scrollIntoView({ behavior: 'smooth' });
            })
            .catch(() => alert('Không thể tải dữ liệu nguyên vật liệu! Vui lòng thử lại.'));
    }

    function vlChuyenVeCheDoDaThem() {
        const form = document.getElementById('formNguyenLieu');
        form.reset();
        form.action = '{{ route('san-pham.store') }}';
        document.getElementById('vl_methodField').innerHTML = '';

        document.getElementById('vl_inp_loai').style.display = 'block';
        document.getElementById('vl_inp_loai').setAttribute('required', 'required');
        document.getElementById('vl_loaiHienThi').style.display = 'none';
        document.getElementById('vl_loaiRequired').style.display = 'inline';
        document.getElementById('vl_khuVucSoLuong').style.display = 'none';
        document.getElementById('vl_donViTinhHienThi').value = '';
        document.getElementById('vl_inp_gia_ban').value = '';

        document.getElementById('vl_iconSubmit').className = 'bi bi-plus-lg me-1';
        document.getElementById('vl_textSubmit').textContent = 'Thêm nguyên vật liệu';
        document.getElementById('vl_btnHuy').style.display = 'none';
    }

    document.getElementById('vl_inp_loai')?.addEventListener('change', function () {
        const target = document.getElementById('vl_donViTinhHienThi');
        if (target) target.value = DON_VI_THEO_LOAI[this.value] ?? '';
    });

    // ── Modal báo cáo hàng hỏng (dùng chung với SP) ──────────
    let _soLuongKho = 0;

    function moModalHangHong(maSP, tenSP, soLuong) {
        _soLuongKho = soLuong;

        document.getElementById('modal_ma_sp').value         = maSP;
        document.getElementById('modal_ten_sp').textContent  = tenSP;
        document.getElementById('modal_so_luong_badge').textContent = soLuong;
        document.getElementById('modal_so_luong_hong').value = '';
        document.getElementById('modal_so_luong_hong').max   = soLuong;
        document.getElementById('modal_sl_error').style.display = 'none';
        document.getElementById('modal_ly_do').value = '';
        document.getElementById('modal_ly_do_error').style.display = 'none';
        document.getElementById('btnGuiBaoCao').disabled = true;

        const selectLo = document.getElementById('modal_lo_nhap');
        selectLo.innerHTML = '<option value="">-- Đang tải lô nhập... --</option>';

        fetch('/san-pham/' + maSP + '/lo-nhap')
            .then(res => res.json())
            .then(data => {
                selectLo.innerHTML = '<option value="">-- Chọn lô nhập --</option>';

                if (data.lo_nhaps.length === 0) {
                    selectLo.innerHTML = '<option value="">Không có lô nào còn hàng</option>';
                    return;
                }

                data.lo_nhaps.forEach(lo => {
                    const opt = document.createElement('option');
                    opt.value = lo.ma_chi_tiet_nhap;
                    opt.textContent = lo.label;
                    opt.dataset.soLuongConLai = lo.so_luong_con_lai;
                    selectLo.appendChild(opt);
                });
            })
            .catch(() => {
                selectLo.innerHTML = '<option value="">Lỗi tải lô, vui lòng thử lại</option>';
            });

        selectLo.onchange = function () {
            const opt = this.options[this.selectedIndex];
            const slConLai = parseInt(opt.dataset.soLuongConLai) || 0;
            _soLuongKho = slConLai;
            document.getElementById('modal_so_luong_hong').max = slConLai;
            document.getElementById('modal_so_luong_badge').textContent = slConLai;
            document.getElementById('modal_so_luong_hong').value = '';
            document.getElementById('modal_sl_error').style.display = 'none';
            validateSanPhamHH();
        };

        new bootstrap.Modal(document.getElementById('modalHangHong')).show();
    }

    function validateSanPhamHH() {
        const val    = parseInt(document.getElementById('modal_so_luong_hong').value) || 0;
        const lyDo   = document.getElementById('modal_ly_do').value.trim();
        const errEl  = document.getElementById('modal_sl_error');
        const lyErr  = document.getElementById('modal_ly_do_error');
        const btnGui = document.getElementById('btnGuiBaoCao');
        const loVal  = document.getElementById('modal_lo_nhap').value;

        let ok = true;

        if (!loVal) ok = false;
        if (val < 1 || val > _soLuongKho) {
            errEl.style.display = 'block';
            ok = false;
        } else {
            errEl.style.display = 'none';
        }
        if (!lyDo) {
            lyErr.style.display = 'block';
            ok = false;
        } else {
            lyErr.style.display = 'none';
        }

        btnGui.disabled = !ok;
    }

    document.getElementById('modal_so_luong_hong').addEventListener('input', validateSanPhamHH);
    document.getElementById('modal_ly_do').addEventListener('input', validateSanPhamHH);
</script>
@endpush