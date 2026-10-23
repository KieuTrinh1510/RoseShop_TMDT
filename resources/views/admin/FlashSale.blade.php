<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Flash Sale hàng hoàn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Flash Sale hàng hoàn</h1>
            <p class="text-muted mb-0">Hàng đã tạo Flash Sale luôn được giữ lại để theo dõi lịch sử.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Quay lại Dashboard
            </a>
            <a href="{{ route('hoa-don.index') }}" class="btn btn-outline-secondary">Hóa đơn</a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    <section class="card shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Hàng hoàn có thể mở Flash Sale</strong></div>
        <div class="card-body">
            @forelse($coTheTao as $item)
                <form class="row g-2 align-items-end border-bottom pb-3 mb-3" method="POST" action="{{ route('admin.hoan-hang.flash-sale.store', $item['ma_chi_tiet_hang_hoan']) }}">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Sản phẩm</label>
                        <div class="fw-semibold">{{ $item['ten'] }}</div>
                        <small class="text-muted">Còn bán được: {{ $item['so_luong_con_lai_flash_sale'] }}</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Số lượng</label>
                        <input class="form-control" name="so_luong" type="number" min="1" max="{{ $item['so_luong_con_lai_flash_sale'] }}" value="{{ $item['so_luong_con_lai_flash_sale'] }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Giá Flash Sale</label>
                        <input class="form-control" name="gia_flash_sale" type="number" min="0" step="any" value="{{ $item['gia_flash_sale_de_xuat'] }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bắt đầu</label>
                        <input class="form-control" name="bat_dau" type="datetime-local" value="{{ now()->format('Y-m-d\\TH:i') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kết thúc</label>
                        <input class="form-control" name="ket_thuc" type="datetime-local" value="{{ now()->addDays(1)->format('Y-m-d\\TH:i') }}" required>
                    </div>
                    <input type="hidden" name="trang_thai" value="DANG_BAN">
                    <div class="col-md-1">
                        <button class="btn btn-warning w-100">Mở bán</button>
                    </div>
                </form>
            @empty
                <p class="text-muted mb-0">Không có hàng hoàn chờ mở Flash Sale.</p>
            @endforelse
        </div>
    </section>

    <section class="card shadow-sm">
        <div class="card-header bg-white"><strong>Danh sách Flash Sale đang bán</strong></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Đã bán / SL</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Xử lý</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($flashSales as $fs)
                    @php
                        $ten = $fs->ma_bo_hoa
                            ? ($fs->boHoa?->ten_bo_hoa ?? 'Bó hoa')
                            : ($fs->sanPham?->ten_san_pham ?? 'Sản phẩm');
                        $con = max(0, $fs->so_luong - $fs->so_luong_da_ban);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $ten }}</strong><br>
                            <small class="text-muted">#FS-{{ $fs->ma_flash_sale }}</small>
                        </td>
                        <td>{{ number_format($fs->gia_flash_sale, 0, ',', '.') }}đ</td>
                        <td>{{ $fs->so_luong_da_ban }} / {{ $fs->so_luong }} <small class="text-muted">(còn {{ $con }})</small></td>
                        <td>
                            <span class="badge text-bg-{{ $fs->trang_thai === 'DANG_BAN' ? 'success' : 'secondary' }}">
                                {{ $fs->trang_thai }}
                            </span>
                        </td>
                        <td>
                            <small>{{ optional($fs->bat_dau)->format('d/m H:i') }} — {{ optional($fs->ket_thuc)->format('d/m H:i') }}</small>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                @if($con > 0 && $fs->trang_thai === 'DANG_BAN')
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#damage-{{ $fs->ma_flash_sale }}">
                                        Báo hỏng
                                    </button>
                                    <div class="collapse mt-2" id="damage-{{ $fs->ma_flash_sale }}">
                                        <form method="POST"
                                              action="{{ route('admin.hoan-hang.flash-sale.report-damage', $fs->ma_flash_sale) }}"
                                              class="d-flex gap-1 flex-wrap">
                                            @csrf
                                            <input class="form-control form-control-sm" name="so_luong_hong" type="number" min="1" max="{{ $con }}" placeholder="SL" required style="width:70px;">
                                            <input class="form-control form-control-sm" name="ly_do" placeholder="Lý do hỏng" required style="flex:1;min-width:120px;">
                                            <select class="form-select form-select-sm" name="ma_nhan_vien" required style="width:auto;">
                                                <option value="">NV</option>
                                                @foreach($nhanViens as $nv)
                                                    <option value="{{ $nv->ma_nhan_vien }}">{{ $nv->ten_nhan_vien }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-danger">Lưu</button>
                                        </form>
                                    </div>
                                @endif

                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editFsModal"
                                        onclick="openEditFs(
                                            {{ $fs->ma_flash_sale }},
                                            '{{ $fs->bat_dau?->format('Y-m-d\TH:i') }}',
                                            '{{ $fs->ket_thuc?->format('Y-m-d\TH:i') }}',
                                            {{ $fs->so_luong }},
                                            {{ $fs->gia_flash_sale }},
                                            '{{ in_array($fs->trang_thai, ['KET_THUC','HET_HANG','AN']) ? 'DANG_BAN' : $fs->trang_thai }}'
                                        )">
                                    Sửa / Gia hạn
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Chưa có Flash Sale đang bán.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $flashSales->links() }}</div>
    </section>

    {{-- MODAL SỬA / GIA HẠN FLASH SALE --}}
    <div class="modal fade" id="editFsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="formEditFs" action="">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Sửa / Gia hạn Flash Sale</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Nếu kéo dài <strong>Kết thúc</strong> tới tương lai, FS đã KET_THUC sẽ <strong>tự mở lại DANG_BAN</strong>.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá Flash Sale (đ)</label>
                            <input type="number" name="gia_flash_sale" id="editGia" class="form-control" min="0" step="any" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số lượng</label>
                            <input type="number" name="so_luong" id="editSl" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bắt đầu</label>
                            <input type="datetime-local" name="bat_dau" id="editBatDau" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kết thúc</label>
                            <input type="datetime-local" name="ket_thuc" id="editKetThuc" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="trang_thai" id="editTt" class="form-select">
                                <option value="DANG_BAN">Đang bán</option>
                                <option value="AN">Ẩn</option>
                                <option value="KET_THUC">Kết thúc</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditFs(id, batDau, ketThuc, sl, gia, tt) {
            document.getElementById('formEditFs').action = '/admin/hoan-hang/flash-sale/' + id;
            document.getElementById('editBatDau').value  = batDau;
            document.getElementById('editKetThuc').value = ketThuc;
            document.getElementById('editSl').value      = sl;
            document.getElementById('editGia').value     = gia;
            document.getElementById('editTt').value      = tt;
        }
    </script>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>