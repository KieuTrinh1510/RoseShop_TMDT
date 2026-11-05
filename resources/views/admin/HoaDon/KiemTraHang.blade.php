{{--
    resources/views/admin/HoaDon/KiemTraHang.blade.php
    Module: Kiểm tra hàng hoàn & Quyết toán (QC: Còn tốt / Flash Sale / Báo hỏng)
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kiểm Tra Hàng Hoàn – HD-{{ str_pad($hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg:#f7f5f0; --surface:#fff; --surface-alt:#f9f7f3;
            --border:#e0dbd0; --border-soft:#ede9e0;
            --ink:#1a1714; --ink-soft:#6b6560; --ink-muted:#a09890;
            --accent:#8b6e4e; --accent-hover:#7a5e3e;
            --danger:#c0392b; --danger-bg:#fdf3f2;
            --warning:#d97706; --warning-bg:#fffbeb;
            --success:#059669; --success-bg:#ecfdf5;
            --info:#2563eb; --info-bg:#eff6ff;
            --font-sans:'IBM Plex Sans',sans-serif;
            --font-mono:'IBM Plex Mono',monospace;
            --font-display:'Playfair Display',serif;
            --radius:8px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);font-size:.875rem;line-height:1.6;}

        .page-header{background:var(--ink);color:#fff;padding:16px 32px;display:flex;align-items:center;gap:12px;}
        .page-header h1{font-family:var(--font-display);font-size:1.35rem;}
        .page-header .back-link{color:rgba(255,255,255,.75);text-decoration:none;font-family:var(--font-mono);font-size:.78rem;margin-left:auto;display:inline-flex;align-items:center;gap:6px;}
        .page-header .back-link:hover{color:#fff;}
        .page-body{max-width:1120px;margin:0 auto;padding:28px 24px 60px;}

        .section-label{font-family:var(--font-mono);font-size:.68rem;color:var(--ink-muted);letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px;margin-top:28px;display:flex;align-items:center;gap:6px;}
        .section-label:first-child{margin-top:0;}

        .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.03);}
        .card-body{padding:20px 24px;}
        .card-body.success-banner{background:var(--success-bg);border-left:4px solid var(--success);color:var(--success);font-weight:500;}

        .info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px 24px;}
        .info-item{display:flex;flex-direction:column;gap:2px;}
        .info-item .lbl{font-size:.67rem;font-family:var(--font-mono);color:var(--ink-muted);text-transform:uppercase;letter-spacing:.05em;}
        .info-item .val{font-size:.875rem;color:var(--ink);font-weight:500;}
        .info-item .val.mono{font-family:var(--font-mono);}

        .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-family:var(--font-mono);font-weight:600;}
        .badge-cancelled{background:#fee2e2;color:#991b1b;}
        .badge-customer{background:var(--warning-bg);color:var(--warning);border:1px solid #fde68a;}
        .badge-shipper{background:var(--danger-bg);color:var(--danger);border:1px solid #fecaca;}

        /* ─── Product Rows ─── */
        .product-row{border-bottom:1px solid var(--border-soft);transition:background .15s;}
        .product-row:last-child{border-bottom:none;}
        .product-row-header{
            display:grid;grid-template-columns:56px 1fr auto;
            gap:16px;align-items:center;padding:16px 24px;
            background:var(--surface);
        }
        .product-thumb{width:56px;height:56px;border-radius:6px;object-fit:cover;background:var(--bg);border:1px solid var(--border);}
        .product-thumb-placeholder{width:56px;height:56px;border-radius:6px;background:var(--border-soft);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:var(--ink-muted);}
        .product-meta{min-width:0;}
        .product-meta .type-tag{display:inline-block;font-size:.62rem;font-family:var(--font-mono);background:var(--border-soft);color:var(--ink-soft);padding:1px 7px;border-radius:4px;margin-bottom:3px;font-weight:600;}
        .product-meta .type-tag.bo-hoa{background:#fce4ec;color:#880e4f;}
        .product-meta .name{font-weight:600;font-size:.95rem;color:var(--ink);}
        .product-meta .sub{font-size:.78rem;color:var(--ink-soft);font-family:var(--font-mono);margin-top:2px;}
        .product-qty-badge{text-align:right;}
        .product-qty-badge .num{font-family:var(--font-mono);font-size:1.1rem;font-weight:700;color:var(--ink);}
        .product-qty-badge .cap{font-size:.68rem;color:var(--ink-muted);text-transform:uppercase;}

        .product-body{padding:0 24px 20px 24px;background:var(--surface-alt);border-top:1px dashed var(--border-soft);}

        /* ─── Branch Selector (3 branches) ─── */
        .branch-group-title{font-size:.75rem;font-family:var(--font-mono);font-weight:600;color:var(--ink-soft);text-transform:uppercase;margin:16px 0 10px;display:flex;align-items:center;gap:6px;}
        .rs-branch-selector{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:14px;}
        @media(max-width:800px){.rs-branch-selector{grid-template-columns:1fr;}}

        .rs-branch-card{
            position:relative;display:flex;gap:12px;padding:14px;
            background:var(--surface);border:2px solid var(--border);border-radius:var(--radius);
            cursor:pointer;transition:all .18s;user-select:none;
        }
        .rs-branch-card:hover{border-color:var(--ink-muted);}
        .rs-branch-card input[type="radio"]{position:absolute;opacity:0;pointer-events:none;}
        .rs-branch-card .b-icon{font-size:1.35rem;line-height:1;}
        .rs-branch-card .b-content{flex:1;}
        .rs-branch-card .b-title{font-weight:600;font-size:.875rem;margin-bottom:2px;}
        .rs-branch-card .b-desc{font-size:.72rem;color:var(--ink-soft);line-height:1.4;}

        /* Selected styles */
        .rs-branch-card.active.branch-good{border-color:var(--success);background:#f0fdf4;box-shadow:0 0 0 1px var(--success);}
        .rs-branch-card.active.branch-good .b-title{color:var(--success);}
        .rs-branch-card.active.branch-flash{border-color:var(--warning);background:#fffbeb;box-shadow:0 0 0 1px var(--warning);}
        .rs-branch-card.active.branch-flash .b-title{color:var(--warning);}
        .rs-branch-card.active.branch-damage{border-color:var(--danger);background:#fef2f2;box-shadow:0 0 0 1px var(--danger);}
        .rs-branch-card.active.branch-damage .b-title{color:var(--danger);}

        /* ─── Flash Sale Discount Selection (30% / 50% / 75%) ─── */
        .fs-sub-panel{background:var(--surface);border:1px solid #fed7aa;border-radius:var(--radius);padding:14px 18px;margin-top:10px;}
        .discount-pills{display:flex;gap:12px;margin-top:8px;}
        @media(max-width:600px){.discount-pills{flex-direction:column;}}
        .discount-pill{
            flex:1;display:flex;align-items:center;justify-content:space-between;
            padding:10px 14px;background:#fff7ed;border:1.5px solid #fdba74;border-radius:6px;
            cursor:pointer;user-select:none;transition:all .15s;
        }
        .discount-pill:hover{border-color:#f97316;background:#ffedd5;}
        .discount-pill input[type="radio"]{accent-color:#ea580c;width:16px;height:16px;margin-right:8px;}
        .discount-pill .pill-pct{font-weight:700;font-size:.9rem;color:#c2410c;}
        .discount-pill .pill-price{font-family:var(--font-mono);font-size:.85rem;font-weight:600;color:var(--ink);}

        /* ─── Damage Report Sub-panel ─── */
        .damage-sub-panel{background:var(--surface);border:1px solid #fecaca;border-radius:var(--radius);padding:14px 18px;margin-top:10px;}
        .field-group{margin-bottom:12px;}
        .field-group:last-child{margin-bottom:0;}
        .field-group label{display:block;font-size:.72rem;font-family:var(--font-mono);font-weight:600;color:var(--ink-soft);text-transform:uppercase;margin-bottom:4px;}
        .field-group label .req{color:var(--danger);}
        .field-group textarea, .field-group select{
            width:100%;border:1px solid var(--border);border-radius:6px;
            padding:8px 12px;font-size:.84rem;font-family:var(--font-sans);
            background:#fff;color:var(--ink);outline:none;transition:border-color .15s;
        }
        .field-group textarea{resize:vertical;min-height:56px;}
        .field-group textarea:focus, .field-group select:focus{border-color:var(--accent);}

        /* ─── Read-only Result Box ─── */
        .result-box{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:6px;margin-top:12px;}
        .result-box.good{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;}
        .result-box.flash{background:#fffbeb;border:1px solid #fed7aa;color:#9a3412;}
        .result-box.damage{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;}

        /* ─── Summary ─── */
        .summary-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px 26px;}
        .summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
        @media(max-width:768px){.summary-grid{grid-template-columns:1fr 1fr;}}
        .summary-item{display:flex;flex-direction:column;gap:3px;}
        .summary-item .s-lbl{font-size:.67rem;font-family:var(--font-mono);color:var(--ink-muted);text-transform:uppercase;}
        .summary-item .s-val{font-family:var(--font-mono);font-size:1.15rem;font-weight:700;}
        .summary-item .s-val.good{color:var(--success);}
        .summary-item .s-val.flash{color:var(--warning);}
        .summary-item .s-val.damage{color:var(--danger);}
        .summary-divider{border:none;border-top:1px solid var(--border-soft);margin:18px 0;}

        /* ─── Form Actions ─── */
        .form-actions{display:flex;gap:12px;align-items:center;margin-top:24px;}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border:none;border-radius:6px;font-size:.85rem;font-family:var(--font-mono);font-weight:600;cursor:pointer;transition:all .18s;text-decoration:none;white-space:nowrap;}
        .btn-primary{background:var(--accent);color:#fff;}
        .btn-primary:hover{background:var(--accent-hover);}
        .btn-ghost{background:transparent;color:var(--ink-soft);border:1px solid var(--border);}
        .btn-ghost:hover{background:var(--border);color:var(--ink);}

        .toast{position:fixed;bottom:24px;right:24px;background:var(--ink);color:#fff;padding:12px 20px;border-radius:var(--radius);font-size:.82rem;font-family:var(--font-mono);opacity:0;pointer-events:none;transition:opacity .3s,transform .3s;transform:translateY(8px);z-index:999;}
        .toast.show{opacity:1;transform:translateY(0);}
        .toast.error{background:var(--danger);}
    </style>
</head>
<body>

<header class="page-header">
    <div style="display:flex; align-items:center; gap:10px;">
        <span style="font-size:1.4rem;">🌸</span>
        <h1>Kiểm Tra Hàng Hoàn & Quyết Toán</h1>
    </div>
    <a href="{{ route('don-hang.index', ['tab' => 'failed']) }}" class="back-link">
        <i class="bi bi-arrow-left"></i> Quay lại Đơn hàng giao thất bại
    </a>
</header>

<div class="page-body">

    {{-- THÔNG TIN ĐƠN --}}
    <div class="section-label"><i class="bi bi-info-circle"></i> Thông tin đơn hàng hoàn</div>
    <div class="card">
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item"><span class="lbl">Mã hóa đơn</span><span class="val mono">#HD-{{ str_pad($hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</span></div>
                <div class="info-item"><span class="lbl">Trạng thái</span><span class="val"><span class="badge badge-cancelled">{{ $hoaDon->trang_thai }}</span></span></div>
                <div class="info-item"><span class="lbl">Nguồn lỗi</span><span class="val">
                    @if($loaiLoi === 'SHIPPER') <span class="badge badge-shipper"><i class="bi bi-truck"></i> Do Shipper</span>
                    @elseif($loaiLoi === 'CUSTOMER') <span class="badge badge-customer"><i class="bi bi-person-x"></i> Do Khách hàng</span>
                    @else <span style="color:var(--ink-muted)">—</span> @endif
                </span></div>
                <div class="info-item"><span class="lbl">Shipper giao</span><span class="val">{{ $shipper?->ten_nhan_vien ?? '—' }} ({{ $shipper?->so_dien_thoai ?? '—' }})</span></div>
                <div class="info-item"><span class="lbl">Khách hàng</span><span class="val">{{ $khachHang?->ten_khach_hang ?? '—' }}</span></div>
                <div class="info-item"><span class="lbl">Lý do thất bại</span><span class="val" style="color:var(--danger)">{{ $giaoHangThatBai?->ly_do ?? $hoaDon->ly_do_huy ?? '—' }}</span></div>
                <div class="info-item"><span class="lbl">Tổng tiền đơn</span><span class="val mono">{{ number_format($hoaDon->tong_tien, 0, ',', '.') }}₫</span></div>
                <div class="info-item"><span class="lbl">Thời điểm nhận kho</span><span class="val mono">{{ $hangHoan->thoi_diem_nhan_tai_kho ? \Carbon\Carbon::parse($hangHoan->thoi_diem_nhan_tai_kho)->format('d/m/Y H:i') : 'Đã nhận tại kho' }}</span></div>
            </div>
        </div>
    </div>

    @if($daKiemTra)
    <div class="card">
        <div class="card-body success-banner">
            <i class="bi bi-check-circle-fill"></i> Kết quả kiểm tra chất lượng đã được hoàn tất và lưu cố định vào hệ thống lúc {{ optional($hangHoan->thoi_diem_kiem_tra)->format('d/m/Y H:i') ?? '—' }}. Không thể thay đổi kết quả này.
        </div>
    </div>
    @endif

    {{-- DANH SÁCH SẢN PHẨM CẦN QC --}}
    <div class="section-label">
        <i class="bi bi-box-seam"></i> {{ $daKiemTra ? 'Kết quả phân loại sản phẩm (Đã hoàn tất)' : 'Phân loại xử lý từng sản phẩm (3 Hướng: Còn tốt / Flash Sale / Báo hỏng)' }}
    </div>

    <form method="POST"
          action="{{ route('admin.hoa-don.kiem-tra-hang.store', $hoaDon->ma_hoa_don) }}"
          id="kiemTraForm" novalidate>
        @csrf

        <div class="card">
            @forelse($chiTietHangHoan as $ct)
                @php
                    $laBoHoa = !is_null($ct->ma_bo_hoa);
                    $ten = $laBoHoa
                        ? ($ct->boHoa?->ten_bo_hoa ?? 'Bó hoa #'.$ct->ma_bo_hoa)
                        : ($ct->sanPham?->ten_san_pham ?? 'Sản phẩm #'.$ct->ma_san_pham);
                    $ma = $laBoHoa ? $ct->ma_bo_hoa : $ct->ma_san_pham;
                    $hinh = $laBoHoa ? ($ct->boHoa?->hinh_anh ?? null) : ($ct->sanPham?->hinh_anh ?? null);
                    $giaGoc = (float) $ct->gia_ban_snapshot;
                    $p30 = round($giaGoc * 0.70, 2);
                    $p50 = round($giaGoc * 0.50, 2);
                    $p75 = round($giaGoc * 0.25, 2);
                    $fsRecord = $ct->flashSales()->first();
                @endphp

                <div class="product-row"
                     id="product-row-{{ $ct->ma_chi_tiet_hang_hoan }}"
                     data-id="{{ $ct->ma_chi_tiet_hang_hoan }}"
                     data-qty="{{ $ct->so_luong_theo_don }}"
                     data-gia="{{ $giaGoc }}">

                    {{-- Product Header --}}
                    <div class="product-row-header">
                        @if($hinh)
                            <img src="{{ asset($hinh) }}" alt="{{ $ten }}" class="product-thumb">
                        @else
                            <div class="product-thumb-placeholder">🌸</div>
                        @endif

                        <div class="product-meta">
                            <span class="type-tag @if($laBoHoa) bo-hoa @endif">{{ $laBoHoa ? 'Bó hoa' : 'Sản phẩm lẻ' }}</span>
                            <div class="name">{{ $ten }}</div>
                            <div class="sub">
                                Mã: {{ $laBoHoa ? 'MBH' : 'MSP' }}-{{ str_pad($ma, 4, '0', STR_PAD_LEFT) }} &nbsp;·&nbsp;
                                Giá gốc: <strong style="color:var(--ink)">{{ number_format($giaGoc, 0, ',', '.') }}₫</strong>
                            </div>
                        </div>

                        <div class="product-qty-badge">
                            <div class="num">{{ $ct->so_luong_theo_don }}</div>
                            <div class="cap">Số lượng hoàn</div>
                        </div>
                    </div>

                    {{-- Product Body --}}
                    <div class="product-body">
                        @if($daKiemTra)
                            {{-- Read-only mode --}}
                            @if($ct->ket_qua === 'DA_XU_LY' || ($ct->so_luong_con_ban_duoc > 0 && $ct->ket_qua !== 'FLASH_SALE'))
                                <div class="result-box good">
                                    <i class="bi bi-check-circle" style="font-size:1.2rem;"></i>
                                    <div>
                                        <strong>1. Còn tốt — Đã Restock về tồn kho thường</strong>
                                        <div style="font-size:.78rem; margin-top:2px;">Số lượng: {{ $ct->so_luong_con_ban_duoc }} · Sản phẩm tiếp tục mở bán bình thường với giá niêm yết {{ number_format($giaGoc, 0, ',', '.') }}₫. Không phạt shipper.</div>
                                    </div>
                                </div>
                            @elseif($ct->ket_qua === 'FLASH_SALE')
                                <div class="result-box flash">
                                    <i class="bi bi-lightning-charge-fill" style="font-size:1.2rem;"></i>
                                    <div>
                                        <strong>2. Giảm chất lượng — Đã tạo Flash Sale (Giảm {{ $fsRecord?->ti_le_giam ?? 50 }}%)</strong>
                                        <div style="font-size:.78rem; margin-top:2px;">
                                            Số lượng: {{ $ct->so_luong_con_ban_duoc }} · Giá Flash Sale: <strong>{{ number_format($ct->gia_flash_sale_snapshot, 0, ',', '.') }}₫</strong>
                                            · Hao hụt Flash Sale: {{ number_format($ct->tien_hao_hut_flash_sale, 0, ',', '.') }}₫
                                            @if($loaiLoi === 'SHIPPER') (Shipper chịu: {{ number_format($ct->tien_shipper_chiu, 0, ',', '.') }}₫) @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="result-box damage">
                                    <i class="bi bi-x-octagon-fill" style="font-size:1.2rem;"></i>
                                    <div>
                                        <strong>3. Hỏng hoàn toàn — Đã ghi nhận thiệt hại & báo hỏng</strong>
                                        <div style="font-size:.78rem; margin-top:2px;">
                                            Số lượng hỏng: {{ $ct->so_luong_hong }} · Thiệt hại: <strong>{{ number_format($ct->tien_thiet_hai_hang_hong, 0, ',', '.') }}₫</strong>
                                            · Lý do: {{ $ct->ghi_chu ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            {{-- Edit mode (Admin QC): Select 1 of 3 branches --}}
                            <div class="branch-group-title">
                                <i class="bi bi-signpost-split"></i> Chọn hướng xử lý:
                            </div>

                            <div class="rs-branch-selector">
                                {{-- Nhánh 1: Còn tốt --}}
                                <label class="rs-branch-card branch-good active" id="card-good-{{ $ct->ma_chi_tiet_hang_hoan }}" onclick="selectBranch({{ $ct->ma_chi_tiet_hang_hoan }}, 'CON_TOT')">
                                    <input type="radio" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][huong_xu_ly]" value="CON_TOT" checked>
                                    <div class="b-icon">🟢</div>
                                    <div class="b-content">
                                        <div class="b-title">CÒN TỐT</div>
                                        <div class="b-desc">
                                            Sản phẩm vẫn đạt chất lượng và có thể bán bình thường.<br>
                                            Giá bán hiện tại: <strong style="color:var(--ink);">{{ number_format($giaGoc, 0, ',', '.') }} đ</strong>
                                        </div>
                                    </div>
                                </label>

                                {{-- Nhánh 2: Giảm chất lượng (Flash Sale) --}}
                                <label class="rs-branch-card branch-flash" id="card-flash-{{ $ct->ma_chi_tiet_hang_hoan }}" onclick="selectBranch({{ $ct->ma_chi_tiet_hang_hoan }}, 'FLASH_SALE')">
                                    <input type="radio" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][huong_xu_ly]" value="FLASH_SALE">
                                    <div class="b-icon">🟠</div>
                                    <div class="b-content">
                                        <div class="b-title">GIẢM CHẤT LƯỢNG / FLASH SALE</div>
                                        <div class="b-desc">Sản phẩm vẫn bán được nhưng chất lượng đã giảm. Chọn mức giảm 30% / 50% / 75%.</div>
                                    </div>
                                </label>

                                {{-- Nhánh 3: Hỏng hoàn toàn (Báo hỏng) --}}
                                <label class="rs-branch-card branch-damage" id="card-damage-{{ $ct->ma_chi_tiet_hang_hoan }}" onclick="selectBranch({{ $ct->ma_chi_tiet_hang_hoan }}, 'HONG')">
                                    <input type="radio" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][huong_xu_ly]" value="HONG">
                                    <div class="b-icon">🔴</div>
                                    <div class="b-content">
                                        <div class="b-title">HỎNG</div>
                                        <div class="b-desc">Sản phẩm không còn khả năng bán. Báo hỏng và ghi nhận thiệt hại.</div>
                                    </div>
                                </label>
                            </div>

                            {{-- Option container cho Flash Sale (Giảm 30% / 50% / 75%) --}}
                            <div class="fs-sub-panel" id="fs-panel-{{ $ct->ma_chi_tiet_hang_hoan }}" style="display:none;">
                                <div style="font-weight:600; font-size:.82rem; color:#9a3412; margin-bottom:4px;">
                                    <i class="bi bi-tag-fill"></i> Chọn một mức giảm giá:
                                </div>
                                <div class="discount-pills">
                                    <label class="discount-pill" id="pill-30-{{ $ct->ma_chi_tiet_hang_hoan }}">
                                        <div style="display:flex; align-items:center;">
                                            <input type="radio" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][ti_le_giam]" value="30" onchange="updateSummary()">
                                            <span class="pill-pct">30%</span>
                                        </div>
                                        <span class="pill-price">{{ number_format($p30, 0, ',', '.') }} đ</span>
                                    </label>
                                    <label class="discount-pill" id="pill-50-{{ $ct->ma_chi_tiet_hang_hoan }}">
                                        <div style="display:flex; align-items:center;">
                                            <input type="radio" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][ti_le_giam]" value="50" checked onchange="updateSummary()">
                                            <span class="pill-pct">50%</span>
                                        </div>
                                        <span class="pill-price">{{ number_format($p50, 0, ',', '.') }} đ</span>
                                    </label>
                                    <label class="discount-pill" id="pill-75-{{ $ct->ma_chi_tiet_hang_hoan }}">
                                        <div style="display:flex; align-items:center;">
                                            <input type="radio" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][ti_le_giam]" value="75" onchange="updateSummary()">
                                            <span class="pill-pct">75%</span>
                                        </div>
                                        <span class="pill-price">{{ number_format($p75, 0, ',', '.') }} đ</span>
                                    </label>
                                </div>

                                <div class="fs-summary-preview" id="fs-preview-{{ $ct->ma_chi_tiet_hang_hoan }}" style="margin-top:10px; padding:10px 14px; background:#fff; border-radius:6px; border:1px dashed #fdba74; font-size:.82rem; font-family:var(--font-mono);">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                                        <span style="color:var(--ink-soft);">Giá hiện tại:</span>
                                        <strong>{{ number_format($giaGoc, 0, ',', '.') }} đ</strong>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                                        <span style="color:var(--ink-soft);">Mức giảm:</span>
                                        <strong id="fs-pct-lbl-{{ $ct->ma_chi_tiet_hang_hoan }}" style="color:#c2410c;">50%</strong>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; padding-top:4px; border-top:1px solid #fee2e2;">
                                        <span style="color:var(--ink); font-weight:600;">Giá Flash Sale:</span>
                                        <strong id="fs-price-lbl-{{ $ct->ma_chi_tiet_hang_hoan }}" style="color:#ea580c; font-size:.95rem;">{{ number_format($p50, 0, ',', '.') }} đ</strong>
                                    </div>
                                </div>
                            </div>

                            {{-- Option container cho Báo Hỏng --}}
                            <div class="damage-sub-panel" id="damage-panel-{{ $ct->ma_chi_tiet_hang_hoan }}" style="display:none;">
                                @if($laBoHoa)
                                <div class="field-group" style="margin-bottom:8px;">
                                    <label style="cursor:pointer; display:inline-flex; align-items:center; gap:6px; font-weight:normal; text-transform:none;">
                                        <input type="checkbox" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][xa_bo]" value="1" id="xabo-{{ $ct->ma_chi_tiet_hang_hoan }}">
                                        <span><strong>Xả toàn bộ bó hoa</strong> (chuyển trạng thái bó hoa sang DRAFT, xem như hỏng hoàn toàn)</span>
                                    </label>
                                </div>
                                @endif
                                <div class="field-group">
                                    <label>Lý do hỏng: <span class="req">*</span></label>
                                    <textarea name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][ly_do_hong]"
                                              id="lydo-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                              placeholder="Nhập lý do hỏng (VD: Cánh hoa dập nát, gãy thân...)..."></textarea>
                                </div>
                                <div class="field-group">
                                    <label>Nhân viên kiểm tra: <span class="req">*</span></label>
                                    <select name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][ma_nhan_vien]"
                                            id="nhanvien-{{ $ct->ma_chi_tiet_hang_hoan }}">
                                        <option value="">-- Chọn nhân viên kiểm tra --</option>
                                        @foreach($nhanViens as $nv)
                                            <option value="{{ $nv->ma_nhan_vien }}">{{ $nv->ten_nhan_vien }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="padding:48px 24px;text-align:center;color:var(--ink-muted);">
                    Không có sản phẩm nào trong phiếu hàng hoàn này.
                </div>
            @endforelse
        </div>

        {{-- TỔNG KẾT KIỂM TRA --}}
        <div class="section-label"><i class="bi bi-calculator"></i> Tổng kết quyết toán</div>
        <div class="summary-box">
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="s-lbl">1. Còn tốt (Restock)</span>
                    <span class="s-val good" id="sum-good">0</span>
                </div>
                <div class="summary-item">
                    <span class="s-lbl">2. Giảm chất lượng (Flash Sale)</span>
                    <span class="s-val flash" id="sum-flash">0</span>
                </div>
                <div class="summary-item">
                    <span class="s-lbl">3. Hỏng hoàn toàn</span>
                    <span class="s-val damage" id="sum-damage">0</span>
                </div>
                <div class="summary-item">
                    <span class="s-lbl">Ước tính thiệt hại hỏng</span>
                    <span class="s-val damage" id="sum-thiet-hai">0₫</span>
                </div>
            </div>
            <hr class="summary-divider">
            <div style="font-size:.78rem; color:var(--ink-soft); display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                <span>
                    <i class="bi bi-info-circle"></i>
                    @if($loaiLoi === 'SHIPPER')
                        <strong>Nguồn lỗi Shipper:</strong> Chịu 100% thiệt hại hàng hỏng + 50% hao hụt Flash Sale + phạt 20.000₫ bồi thường khách.
                    @elseif($loaiLoi === 'CUSTOMER')
                        <strong>Nguồn lỗi Khách hàng:</strong> Hệ thống chịu toàn bộ chi phí hao hụt và hỏng hóc, miễn phạt cho shipper.
                    @else
                        Hệ thống sẽ tự động đối soát trách nhiệm shipper và thiệt hại khi lưu.
                    @endif
                </span>
                <span id="sum-hao-hut-container">
                    Hao hụt Flash Sale dự kiến: <strong id="sum-hao-hut" style="color:var(--warning)">0₫</strong>
                </span>
            </div>
        </div>

        {{-- NÚT THAO TÁC --}}
        <div class="form-actions">
            @if($daKiemTra)
                <a href="{{ route('don-hang.index', ['tab' => 'failed']) }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Quay lại Đơn hàng giao thất bại
                </a>
                <span style="font-size:.82rem;color:var(--success);font-weight:600;">
                    ✔ Kết quả đã được lưu cố định vào hệ thống.
                </span>
            @else
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-check2-circle"></i> XÁC NHẬN KẾT QUẢ KIỂM TRA
                </button>
                <a href="{{ route('don-hang.index', ['tab' => 'failed']) }}" class="btn btn-ghost">Hủy</a>
            @endif
        </div>
    </form>

</div>

<div class="toast" id="toast"></div>

<script>
const READONLY = @json($daKiemTra);

function fmt(n){ return Number(n).toLocaleString('vi-VN') + '₫'; }

function showToast(msg, type='success'){
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast ' + type;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}

// Xử lý chuyển đổi giữa 3 nhánh cho từng dòng
function selectBranch(id, branch) {
    if (READONLY) return;

    // Active state của 3 card
    const cardGood   = document.getElementById('card-good-' + id);
    const cardFlash  = document.getElementById('card-flash-' + id);
    const cardDamage = document.getElementById('card-damage-' + id);

    cardGood.classList.toggle('active', branch === 'CON_TOT');
    cardFlash.classList.toggle('active', branch === 'FLASH_SALE');
    cardDamage.classList.toggle('active', branch === 'HONG');

    // Check radio tương ứng
    const radios = document.querySelectorAll(`input[name="items[${id}][huong_xu_ly]"]`);
    radios.forEach(r => { if (r.value === branch) r.checked = true; });

    // Hiển thị panel con tương ứng
    const fsPanel     = document.getElementById('fs-panel-' + id);
    const damagePanel = document.getElementById('damage-panel-' + id);

    if (fsPanel) fsPanel.style.display = (branch === 'FLASH_SALE') ? 'block' : 'none';
    if (damagePanel) damagePanel.style.display = (branch === 'HONG') ? 'block' : 'none';

    updateSummary();
}

function updateSummary() {
    let sumGood = 0;
    let sumFlash = 0;
    let sumDamage = 0;
    let totalThietHai = 0;
    let totalHaoHut = 0;

    document.querySelectorAll('.product-row').forEach(row => {
        const id = row.dataset.id;
        const qty = Number(row.dataset.qty || 1);
        const gia = Number(row.dataset.gia || 0);

        if (READONLY) return;

        const checkedRadio = document.querySelector(`input[name="items[${id}][huong_xu_ly]"]:checked`);
        const branch = checkedRadio ? checkedRadio.value : 'CON_TOT';

        if (branch === 'CON_TOT') {
            sumGood += qty;
        } else if (branch === 'FLASH_SALE') {
            sumFlash += qty;
            const fsRadio = document.querySelector(`input[name="items[${id}][ti_le_giam]"]:checked`);
            const pct = fsRadio ? Number(fsRadio.value) : 50;
            const giaFlash = Math.round(gia * (1 - pct / 100));
            const haoHut = (gia - giaFlash) * qty;
            totalHaoHut += haoHut;

            const pctLbl = document.getElementById('fs-pct-lbl-' + id);
            const priceLbl = document.getElementById('fs-price-lbl-' + id);
            if (pctLbl) pctLbl.textContent = pct + '%';
            if (priceLbl) priceLbl.textContent = fmt(giaFlash);
        } else if (branch === 'HONG') {
            sumDamage += qty;
            totalThietHai += (gia * qty);
        }
    });

    if (!READONLY) {
        document.getElementById('sum-good').textContent = sumGood;
        document.getElementById('sum-flash').textContent = sumFlash;
        document.getElementById('sum-damage').textContent = sumDamage;
        document.getElementById('sum-thiet-hai').textContent = fmt(totalThietHai);
        document.getElementById('sum-hao-hut').textContent = fmt(totalHaoHut);
    }
}

// Validate form trước khi submit
document.getElementById('kiemTraForm')?.addEventListener('submit', function(e) {
    if (READONLY) return;

    let hasError = false;
    let firstErrorMsg = '';

    document.querySelectorAll('.product-row').forEach(row => {
        const id = row.dataset.id;
        const checkedRadio = document.querySelector(`input[name="items[${id}][huong_xu_ly]"]:checked`);
        const branch = checkedRadio ? checkedRadio.value : 'CON_TOT';

        if (branch === 'HONG') {
            const lydo = document.getElementById('lydo-' + id)?.value?.trim();
            const nv = document.getElementById('nhanvien-' + id)?.value;

            if (!lydo) {
                hasError = true;
                firstErrorMsg = 'Vui lòng nhập lý do hỏng cho các sản phẩm báo hỏng.';
                document.getElementById('lydo-' + id)?.focus();
            } else if (!nv) {
                hasError = true;
                firstErrorMsg = 'Vui lòng chọn nhân viên báo cáo cho các sản phẩm báo hỏng.';
                document.getElementById('nhanvien-' + id)?.focus();
            }
        }
    });

    if (hasError) {
        e.preventDefault();
        showToast(firstErrorMsg, 'error');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    updateSummary();
});
</script>
</body>
</html>
