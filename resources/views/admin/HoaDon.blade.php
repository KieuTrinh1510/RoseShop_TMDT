@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Admin/HoaDon.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=IM+Fell+English:ital@0;1&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
@endpush

@section('content')

<div class="hoa-don-container">

    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">Quản lý hóa đơn</h1>
            <p class="rs-page-subtitle">Danh sách hóa đơn bán hàng, lịch sử thanh toán và trạng thái xử lý.</p>
        </div>
    </div>

<div class="page-body" id="page-body">

    {{-- ── Ô 1: BỘ LỌC ── --}}
    <form method="GET" action="{{ route('hoa-don.index') }}" id="filter-form">
        <div class="filter-box">
            <div class="filter-grid">

                {{-- Khách hàng --}}
                <div class="filter-group">
                    <label>Khách Hàng</label>
                    <select name="ma_khach_hang">
                        <option value="">— Tất cả —</option>
                        @foreach ($khachHangs as $kh)
                            <option value="{{ $kh->ma_khach_hang }}"
                                {{ request('ma_khach_hang') == $kh->ma_khach_hang ? 'selected' : '' }}>
                                {{ $kh->ten_khach_hang }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Trạng thái đơn --}}
                <div class="filter-group">
                    <label>Trạng Thái Đơn</label>
                    <select name="trang_thai">
                        <option value="">— Tất cả —</option>
                        @foreach (['PENDING'=>'Chờ xử lý','CONFIRMED'=>'Đã xác nhận','SHIPPING'=>'Đang giao','DELIVERED'=>'Đã giao','CANCELLED'=>'Đã hủy'] as $val => $label)
                            <option value="{{ $val }}" {{ request('trang_thai') == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Thanh toán --}}
                <div class="filter-group">
                    <label>Thanh Toán</label>
                    <select name="trang_thai_thanh_toan">
                        <option value="">— Tất cả —</option>
                        <option value="CHUA_THANH_TOAN" {{ request('trang_thai_thanh_toan') == 'CHUA_THANH_TOAN' ? 'selected' : '' }}>Chưa thanh toán</option>
                        <option value="DA_THANH_TOAN"   {{ request('trang_thai_thanh_toan') == 'DA_THANH_TOAN'   ? 'selected' : '' }}>Đã thanh toán</option>
                    </select>
                </div>

                {{-- Từ ngày --}}
                <div class="filter-group">
                    <label>Từ Ngày</label>
                    <input type="date" name="tu_ngay" value="{{ request('tu_ngay') }}">
                </div>

                {{-- Đến ngày --}}
                <div class="filter-group">
                    <label>Đến Ngày</label>
                    <input type="date" name="den_ngay" value="{{ request('den_ngay') }}">
                </div>

                {{-- Nút --}}
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary">Tìm Kiếm</button>
                    <a href="{{ route('hoa-don.index') }}" class="btn btn-reset">Reset</a>
                </div>

            </div>
        </div>
    </form>

    {{-- ── Ô 2: BẢNG HÓA ĐƠN ── --}}
    <div class="table-box">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã HD</th>
                        <th>Khách Hàng</th>
                        <th>Ngày Đặt</th>
                        <th>Tổng Tiền</th>
                        <th>Trạng Thái</th>
                        <th>Hủy / Hàng hoàn</th>
                        <th>Thanh Toán</th>
                        <th>PT Thanh Toán</th>
                        <th>Địa Chỉ Giao</th>
                        <th>SĐT</th>
                        <th>Ngày Giao</th>
                        <th>Nhân Viên Giao</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody id="invoice-table-body">
                @forelse ($hoaDons as $index => $hd)
                    <tr id="row-{{ $hd->ma_hoa_don }}">
                        <td class="id-col">{{ $hoaDons->firstItem() + $index }}</td>
                        <td class="id-col">#HD-{{ str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $hd->khachHang->ten_khach_hang ?? '—' }}</td>
                        <td>{{ optional($hd->ngay_dat)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="money">{{ number_format($hd->tong_tien, 0, ',', '.') }}₫</td>
                        <td>
                            @php
                                $statusMap = ['PENDING'=>'badge-pending','CONFIRMED'=>'badge-confirmed','SHIPPING'=>'badge-shipping','DELIVERED'=>'badge-delivered','CANCELLED'=>'badge-cancelled'];
                                $statusLabel = ['PENDING'=>'Chờ xử lý','CONFIRMED'=>'Xác nhận','SHIPPING'=>'Đang giao','DELIVERED'=>'Đã giao','CANCELLED'=>'Đã hủy'];
                            @endphp
                            <span class="badge {{ $statusMap[$hd->trang_thai] ?? '' }}">
                                {{ $statusLabel[$hd->trang_thai] ?? $hd->trang_thai }}
                            </span>
                        </td>
                        <td>
                            @if($hd->trang_thai === 'CANCELLED' && $hd->nguon_huy_text)
                                <span class="badge {{ strtoupper($hd->nguon_huy) === 'SHIPPER' ? 'badge-shipping' : 'badge-cancelled' }}">
                                    Đã hủy - {{ $hd->nguon_huy_text }}
                                </span>
                                @if($hd->ly_do_huy)
                                    <div class="wrap" style="font-size:11px;color:#667085;margin-top:4px;max-width:170px;">
                                        {{ $hd->ly_do_huy }}
                                    </div>
                                @endif
                                @if($hd->trang_thai_hang_hoan_text)
                                    <div style="font-size:11px;color:#475467;margin-top:4px;">
                                        Hàng hoàn: {{ $hd->trang_thai_hang_hoan_text }}
                                    </div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ in_array($hd->trang_thai_thanh_toan, ['DA_THANH_TOAN','DA_NOP']) ? 'badge-paid' : 'badge-unpaid' }}">
                                        {{ in_array($hd->trang_thai_thanh_toan, ['DA_THANH_TOAN','DA_NOP']) ? 'Đã TT' : 'Chưa TT' }}
                        </span>
                        </td>
                        <td>{{ $hd->phuong_thuc_thanh_toan ?? '—' }}</td>
                        <td class="wrap">{{ $hd->dia_chi_giao ?? '—' }}</td>
                        <td>{{ $hd->so_dien_thoai ?? '—' }}</td>
                        <td>{{ optional($hd->ngay_giao)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $hd->shipper->ten_nhan_vien ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:6px;align-items:center;">
                                {{-- Nút Chi Tiết --}}
                                <button class="btn btn-detail"
                                        data-id="{{ $hd->ma_hoa_don }}"
                                        onclick="openDetail({{ $hd->ma_hoa_don }}, this)">
                                    Chi Tiết
                                </button>

                                @if($hd->co_the_kiem_tra_hang && !$hd->da_kiem_tra_hang)
                             <a class="btn btn-detail"
                                               href="{{ route('admin.hoa-don.kiem-tra-hang', $hd->ma_hoa_don) }}"
                                                  title="Kiểm tra hàng hoàn">
                                               Kiểm tra hàng
                                                        </a>
                                    @elseif($hd->co_the_kiem_tra_hang && $hd->da_kiem_tra_hang)
                                <button type="button"
                                  class="btn btn-detail"
                                  onclick="openKiemTraLog({{ $hd->ma_hoa_don }}, this)"
                          title="Xem log kiểm tra hàng hoàn">
                              Xem log kiểm tra
                            </button>
@endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="14">Không tìm thấy hóa đơn nào.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($hoaDons->hasPages())
        <div class="pagination-wrap">
            <span>Hiển thị {{ $hoaDons->firstItem() }}–{{ $hoaDons->lastItem() }} / {{ $hoaDons->total() }} hóa đơn</span>
            <div class="pages">
                {{-- Prev --}}
                @if ($hoaDons->onFirstPage())
                    <span class="disabled">‹ Trước</span>
                @else
                    <a href="{{ $hoaDons->previousPageUrl() }}">‹ Trước</a>
                @endif

                {{-- Page numbers --}}
                @foreach ($hoaDons->getUrlRange(max(1, $hoaDons->currentPage()-2), min($hoaDons->lastPage(), $hoaDons->currentPage()+2)) as $page => $url)
                    @if ($page == $hoaDons->currentPage())
                        <span class="active-page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($hoaDons->hasMorePages())
                    <a href="{{ $hoaDons->nextPageUrl() }}">Sau ›</a>
                @else
                    <span class="disabled">Sau ›</span>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>{{-- /page-body --}}

{{-- ── DETAIL PANEL ── --}}
<div id="detail-panel">
    <div class="panel-header">
        <h2 id="panel-title">Chi Tiết Hóa Đơn</h2>
        <button class="panel-close" onclick="closeDetail()">✕ Đóng</button>
    </div>
    <div class="panel-body" id="panel-body">
    </div>
</div>

{{-- ── TOAST ── --}}
<div id="toast"></div>
</div>

@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let activeDetailId = null;
    let activeBtn      = null;

    function showToast(msg, isError = false) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className   = 'show' + (isError ? ' error' : '');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = ''; }, 3000);
    }

    function openDetail(id, btn) {
        const panel = document.getElementById('detail-panel');
        const body  = document.getElementById('panel-body');

        if (activeDetailId === id && panel.classList.contains('open')) {
            closeDetail();
            return;
        }

        document.querySelectorAll('tr.highlighted').forEach(r => r.classList.remove('highlighted'));
        document.querySelectorAll('.btn-detail.active').forEach(b => b.classList.remove('active'));
        const row = document.getElementById('row-' + id);
        if (row) row.classList.add('highlighted');
        btn.classList.add('active');

        activeDetailId = id;
        activeBtn      = btn;

        document.getElementById('panel-title').textContent = '#HD-' + String(id).padStart(4, '0');
        body.innerHTML = '<div class="panel-loading">Đang tải</div>';
        panel.classList.add('open');
        document.getElementById('page-body').classList.add('panel-open');

        fetch(`/hoa-don/${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { body.innerHTML = '<p style="padding:20px;color:#888">Không tải được dữ liệu.</p>'; return; }
            renderPanel(res.data);
        })
        .catch(() => { body.innerHTML = '<p style="padding:20px;color:#888">Lỗi kết nối.</p>'; });
    }

    function closeDetail() {
        document.getElementById('detail-panel').classList.remove('open');
        document.getElementById('page-body').classList.remove('panel-open');
        document.querySelectorAll('tr.highlighted').forEach(r => r.classList.remove('highlighted'));
        document.querySelectorAll('.btn-detail.active').forEach(b => b.classList.remove('active'));
        activeDetailId = null;
        activeBtn      = null;
    }
    // ── Panel xem log kiểm tra hàng hoàn ─────────────────────────────
function openKiemTraLog(id, btn) {
    const panel = document.getElementById('detail-panel');
    const body  = document.getElementById('panel-body');

    // Reset panel chi tiết nếu đang mở
    if (activeDetailId && activeDetailId !== id) closeDetail();
    activeDetailId = null;
    activeBtn = btn;

    document.querySelectorAll('tr.highlighted').forEach(r => r.classList.remove('highlighted'));
    const row = document.getElementById('row-' + id);
    if (row) row.classList.add('highlighted');

    document.getElementById('panel-title').textContent =
        'Log kiểm tra hàng hoàn — #HD-' + String(id).padStart(4, '0');

    body.innerHTML = '<div class="panel-loading">Đang tải...</div>';
    panel.classList.add('open');
    document.getElementById('page-body').classList.add('panel-open');

    fetch(`/admin/hoa-don/${id}/kiem-tra-hang`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            body.innerHTML = '<p style="padding:20px;color:#888">Không tải được dữ liệu.</p>';
            return;
        }
        renderKiemTraLog(res.data);
    })
    .catch(() => {
        body.innerHTML = '<p style="padding:20px;color:#888">Lỗi kết nối.</p>';
    });
}

function renderKiemTraLog(d) {
    const esc = v => String(v ?? '—').replace(/[&<>"']/g, c =>
        ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

    const hangHoan = d.hangHoan || {};
    const thoiDiem = hangHoan.thoi_diem_kiem_tra || null;

    const rows = (d.dongKiemTra || []).map(x => `
        <tr>
            <td>${esc(x.la_bo_hoa ? 'Bó hoa' : 'Sản phẩm')}: ${esc(x.ten)}</td>
            <td style="text-align:center">${Number(x.so_luong_theo_don) || 0}</td>
            <td style="text-align:center;color:#c0392b">${Number(x.so_luong_hong) || 0}</td>
            <td style="text-align:center;color:#2e7d52">${Number(x.so_luong_con_ban_duoc) || 0}</td>
            <td>${esc(x.ket_qua)}</td>
            <td>${esc(x.ghi_chu)}</td>
        </tr>
    `).join('') || '<tr><td colspan="6" style="text-align:center;color:#888">Chưa có dữ liệu</td></tr>';

    document.getElementById('panel-body').innerHTML = `
        <div class="panel-section">
            <div class="panel-section-title">Kết quả kiểm tra hàng hoàn</div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align:center">SL đơn</th>
                        <th style="text-align:center">Hỏng</th>
                        <th style="text-align:center">Còn bán</th>
                        <th>Kết quả</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
        <div class="panel-section" style="padding-top:0;">
            <small style="color:#64748b;font-size:12px;">
                ${thoiDiem
                    ? 'Log đã lưu cố định vào ' + esc(thoiDiem) + ' — không thể chỉnh sửa.'
                    : 'Log kiểm tra — chỉ đọc.'}
            </small>
        </div>
    `;
}

    function renderPanel(d) {
        const statusLabel = {PENDING:'Chờ xử lý',CONFIRMED:'Đã xác nhận',SHIPPING:'Đang giao',DELIVERED:'Đã giao',CANCELLED:'Đã hủy'};
        const fmt = v => v ? Number(v).toLocaleString('vi-VN') + '₫' : '—';
        const escapeHtml = v => String(v ?? '—').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        let productsHtml = '';
        let grandTotal = 0;
        if (d.chi_tiet_hoa_don && d.chi_tiet_hoa_don.length) {
            // ===== THAY ĐỔI =====
            // Backend giờ lưu 1 dòng ChiTietHoaDon cho MỖI batch đã lấy hàng.
            // Nếu 1 sản phẩm được lấy từ nhiều batch (VD FIFO lấy Lô1:2 + Lô2:2),
            // d.chi_tiet_hoa_don sẽ có nhiều dòng cùng ma_san_pham nhưng khác batch.
            // Group lại theo ma_san_pham trước khi render để hiển thị đúng
            // "Hoa A x4" thay vì lặp "Hoa A x2" hai lần.
            // Lưu ý: đây chỉ là gộp lúc HIỂN THỊ trên panel này — dữ liệu batch
            // (ma_chi_tiet_nhap) vẫn còn nguyên vẹn trong d.chi_tiet_hoa_don gốc
            // và trong database, không bị mất đi.
            const grouped = {};
            const order   = [];

            d.chi_tiet_hoa_don.forEach(ct => {
                        const key = ct.ma_bo_hoa
                            ? 'bo_' + ct.ma_bo_hoa
                            : (ct.ma_san_pham !== undefined && ct.ma_san_pham !== null)
                                ? 'sp_' + ct.ma_san_pham
                                : 'ten_' + (ct.ten_hien_thi ?? ct.san_pham?.ten_san_pham ?? 'unknown');

                        if (!grouped[key]) {
                            // Ưu tiên ten_hien_thi từ backend (đã resolve sẵn boHoa/fallback)
                            grouped[key] = {
                                ten_san_pham:     ct.ten_hien_thi
                                                ?? ct.san_pham?.ten_san_pham
                                                ?? '—',
                                size:             ct.size ?? null,
                                so_luong:         0,
                                gia_ban_snapshot: ct.gia_ban_snapshot,
                                thanh_tien:       0,
                            };
                            order.push(key);
                        }

                        grouped[key].so_luong   += Number(ct.so_luong);
                        grouped[key].thanh_tien += Number(ct.thanh_tien);
                    });

            order.forEach(key => {
                    const g = grouped[key];
                    grandTotal += g.thanh_tien;
                    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                    const tenDayDu = g.size ? `${esc(g.ten_san_pham)} · ${esc(g.size)}` : esc(g.ten_san_pham);
                    productsHtml += `
                    <tr>
                        <td>${tenDayDu}</td>
                        <td style="text-align:center">${g.so_luong}</td>
                        <td style="text-align:right">${fmt(g.gia_ban_snapshot)}</td>
                        <td style="text-align:right;font-weight:700">${fmt(g.thanh_tien)}</td>
                    </tr>`;
                });

            productsHtml += `
                <tr class="total-row">
                    <td colspan="3">Tổng cộng</td>
                    <td style="text-align:right">${fmt(grandTotal)}</td>
                </tr>`;
        } else {
            productsHtml = '<tr><td colspan="4" style="text-align:center;color:#888;padding:12px">Không có sản phẩm</td></tr>';
        }

        let sampleImagesHtml = '';
        if (d.anh_mau_custom && d.anh_mau_custom.length > 0) {
            sampleImagesHtml = `
            <div class="panel-section">
                <div class="panel-section-title">Ảnh Mẫu Khách Gửi (Custom Mix)</div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
                    ${d.anh_mau_custom.map(img => `
                        <a href="${img.url}" target="_blank" title="${img.original_name || 'Xem ảnh gốc'}" style="display:block; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; width:80px; height:80px; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                            <img src="${img.url}" alt="${img.original_name || 'Ảnh mẫu'}" style="width:100%; height:100%; object-fit:cover;" />
                        </a>
                    `).join('')}
                </div>
                <small style="color:#64748b; display:block; margin-top:6px; font-size:12px;">Ảnh mẫu dùng để thợ cắm hoa tham khảo phong cách phối bó tự chọn.</small>
            </div>`;
        }

        document.getElementById('panel-body').innerHTML = `
        <div class="panel-section">
            <div class="panel-section-title">Thông Tin Hóa Đơn</div>
            <div class="info-row"><span class="info-label">Mã hóa đơn</span><span class="info-value">#HD-${String(d.ma_hoa_don).padStart(4,'0')}</span></div>
            <div class="info-row"><span class="info-label">Ngày đặt</span><span class="info-value">${d.ngay_dat ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">Ngày giao</span><span class="info-value">${d.ngay_giao ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">Trạng thái</span><span class="info-value">${statusLabel[d.trang_thai] ?? d.trang_thai}</span></div>
            ${d.trang_thai === 'CANCELLED' && d.nguon_huy_text ? `
            <div class="info-row"><span class="info-label">Nguồn hủy</span><span class="info-value">Đã hủy - ${d.nguon_huy_text}</span></div>
            <div class="info-row"><span class="info-label">Lý do hủy</span><span class="info-value" style="max-width:240px;text-align:right;white-space:normal">${d.ly_do_huy ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">Hàng hoàn</span><span class="info-value">${d.trang_thai_hang_hoan_text ?? 'Chưa tạo'}</span></div>` : ''}
            <div class="info-row"><span class="info-label">Thanh toán</span><span class="info-value">${['DA_THANH_TOAN','DA_NOP'].includes(d.trang_thai_thanh_toan) ? 'Đã thanh toán' : 'Chưa thanh toán'}</span></div>
            <div class="info-row"><span class="info-label">Phương thức</span><span class="info-value">${d.phuong_thuc_thanh_toan ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">Tổng tiền</span><span class="info-value">${fmt(d.tong_tien)}</span></div>
        </div>

        <div class="panel-section">
            <div class="panel-section-title">Thông Tin Khách Hàng</div>
            <div class="info-row"><span class="info-label">Tên KH</span><span class="info-value">${d.khach_hang?.ten_khach_hang ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">SĐT KH</span><span class="info-value">${d.khach_hang?.so_dien_thoai ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">Email</span><span class="info-value">${d.khach_hang?.email ?? '—'}</span></div>
        </div>

        <div class="panel-section">
            <div class="panel-section-title">Thông Tin Giao Hàng</div>
            <div class="info-row"><span class="info-label">Địa chỉ giao</span><span class="info-value" style="max-width:240px;text-align:right;white-space:normal">${d.dia_chi_giao ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">SĐT nhận</span><span class="info-value">${d.so_dien_thoai ?? '—'}</span></div>
            <div class="info-row"><span class="info-label">NV giao</span><span class="info-value">${d.nhan_vien_giao?.ten_nhan_vien ?? '—'}</span></div>
        </div>

        ${sampleImagesHtml}

        ${d.kiem_tra_hang_url ? `
        <div class="panel-section">
            <div class="panel-section-title">Kiểm Tra Hàng Hoàn</div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                <button type="button" class="btn btn-detail" onclick="document.getElementById('return-log-${d.ma_hoa_don}').classList.toggle('d-none')">${d.da_kiem_tra_hang ? 'Xem log kiểm tra' : 'Kiểm tra hàng'}</button>
                ${d.da_kiem_tra_hang ? '<span style="font-size:12px;color:#16803c">Đã lưu, chỉ xem</span>' : ''}
            </div>
            <div id="return-log-${d.ma_hoa_don}" class="d-none" style="margin-top:10px;overflow:auto;">
                ${d.da_kiem_tra_hang ? `<div style="font-size:12px;color:#667085;margin-bottom:6px">Lưu lúc: ${escapeHtml(d.thoi_diem_kiem_tra)}</div><table class="product-table"><thead><tr><th>Sản phẩm</th><th>Hỏng</th><th>Còn bán</th><th>Ghi chú</th></tr></thead><tbody>${(d.kiem_tra_log || []).map(x => `<tr><td>${escapeHtml(x.loai)}: ${escapeHtml(x.ten)}</td><td style="text-align:center">${Number(x.hong) || 0}</td><td style="text-align:center">${Number(x.con_ban) || 0}</td><td>${escapeHtml(x.ghi_chu)}</td></tr>`).join('') || '<tr><td colspan="4">Chưa có dữ liệu</td></tr>'}</tbody></table>` : `<a href="${d.kiem_tra_hang_url}" class="btn btn-detail" style="display:inline-flex">Mở màn hình kiểm tra</a>`}
            </div>
        </div>` : ''}

        <div class="panel-section">
            <div class="panel-section-title">Chi Tiết Sản Phẩm</div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Sản Phẩm</th>
                        <th style="text-align:center">SL</th>
                        <th style="text-align:right">Đơn Giá</th>
                        <th style="text-align:right">Thành Tiền</th>
                    </tr>
                </thead>
                <tbody>${productsHtml}</tbody>
            </table>
        </div>
        `;
    }

    function deleteInvoice(id, btn) {
        if (!confirm(`Bạn chắc chắn muốn xóa hóa đơn #HD-${String(id).padStart(4,'0')}?\nChi tiết hóa đơn cũng sẽ bị xóa theo.`)) return;

        btn.disabled = true;
        btn.textContent = '...';

        fetch(`/hoa-don/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN':    CSRF,
                'X-Requested-With':'XMLHttpRequest',
                'Accept':          'application/json',
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (activeDetailId === id) closeDetail();

                const row = document.getElementById('row-' + id);
                if (row) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity    = '0';
                    setTimeout(() => row.remove(), 300);
                }
                showToast(res.message);
            } else {
                btn.disabled    = false;
                btn.textContent = 'Xóa';
                showToast(res.message, true);
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'Xóa';
            showToast('Lỗi kết nối máy chủ.', true);
        });
    }
</script>
@endpush
