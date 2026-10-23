{{--
    resources/views/admin/HoaDon/KiemTraHang.blade.php
    - Chế độ nhập (chưa kiểm tra): form có +/- , checkbox hỏng, lý do + NV bắt buộc.
    - Chế độ xem log (đã kiểm tra): read-only, chỉ hiển thị kết quả.
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kiểm Tra Hàng Hoàn – HD-{{ str_pad($hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#f7f5f0; --surface:#fff; --surface-alt:#f9f7f3;
            --border:#e0dbd0; --border-soft:#ede9e0;
            --ink:#1a1714; --ink-soft:#6b6560; --ink-muted:#a09890;
            --accent:#8b6e4e; --accent-hover:#7a5e3e;
            --danger:#c0392b; --danger-bg:#fdf3f2;
            --warning:#c07a2b; --warning-bg:#fff8ec;
            --success:#2e7d52; --success-bg:#f0faf4;
            --font-sans:'IBM Plex Sans',sans-serif;
            --font-mono:'IBM Plex Mono',monospace;
            --font-display:'Playfair Display',serif;
            --radius:6px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{background:var(--bg);color:var(--ink);font-family:var(--font-sans);font-size:.875rem;line-height:1.6;}

        .page-header{background:var(--ink);color:#fff;padding:14px 32px;display:flex;align-items:center;gap:12px;}
        .page-header h1{font-family:var(--font-display);font-size:1.25rem;}
        .page-header .back-link{color:rgba(255,255,255,.6);text-decoration:none;font-family:var(--font-mono);font-size:.72rem;margin-left:auto;}
        .page-header .back-link:hover{color:#fff;}
        .page-body{max-width:1120px;margin:0 auto;padding:28px 24px 60px;}

        .section-label{font-family:var(--font-mono);font-size:.62rem;color:var(--ink-muted);letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px;margin-top:28px;}
        .section-label:first-child{margin-top:0;}

        .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;}
        .card-body{padding:20px 24px;}
        .card-body.success{background:var(--success-bg);border-color:#a8d5b8;color:var(--success);}

        .info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px 24px;}
        .info-item{display:flex;flex-direction:column;gap:2px;}
        .info-item .lbl{font-size:.67rem;font-family:var(--font-mono);color:var(--ink-muted);text-transform:uppercase;letter-spacing:.05em;}
        .info-item .val{font-size:.875rem;color:var(--ink);}
        .info-item .val.mono{font-family:var(--font-mono);}

        .badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.67rem;font-family:var(--font-mono);font-weight:500;}
        .badge-cancelled{background:#fde8e8;color:#7b1c1c;}
        .badge-customer{background:var(--warning-bg);color:var(--warning);}
        .badge-shipper{background:var(--danger-bg);color:var(--danger);}

        /* ─── Rows ─── */
        .product-row{border-bottom:1px solid var(--border-soft);}
        .product-row:last-child{border-bottom:none;}
        .product-row-header{
            display:grid;
            grid-template-columns:60px 1fr auto auto;
            gap:12px;align-items:center;padding:14px 24px;
            cursor:pointer;user-select:none;transition:background .15s;
        }
        .product-row-header:hover{background:var(--surface-alt);}
        .product-thumb{width:52px;height:52px;border-radius:4px;object-fit:cover;background:var(--bg);border:1px solid var(--border);}
        .product-thumb-placeholder{width:52px;height:52px;border-radius:4px;background:var(--border-soft);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--ink-muted);}
        .product-meta{min-width:0;}
        .product-meta .type-tag{display:inline-block;font-size:.62rem;font-family:var(--font-mono);background:var(--border);color:var(--ink-soft);padding:1px 6px;border-radius:3px;margin-bottom:3px;}
        .product-meta .type-tag.bo-hoa{background:#fce4ec;color:#880e4f;}
        .product-meta .name{font-weight:500;font-size:.875rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .product-meta .sub{font-size:.75rem;color:var(--ink-soft);font-family:var(--font-mono);}
        .product-qty{text-align:center;padding:0 8px;}
        .product-qty .number{font-family:var(--font-mono);font-size:1rem;font-weight:500;}
        .product-qty .caption{font-size:.65rem;color:var(--ink-muted);}
        .chevron{transition:transform .2s;font-size:.75rem;color:var(--ink-soft);}
        .product-row.open .chevron{transform:rotate(180deg);}

        .product-inputs{display:none;padding:0 24px 20px 96px;background:var(--surface-alt);border-top:1px solid var(--border-soft);}
        .product-row.open .product-inputs{display:block;}

        /* ─── Checkbox hàng hỏng ─── */
        .damage-toggle{
            display:flex;align-items:center;gap:8px;margin-top:16px;padding:10px 14px;
            background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
            cursor:pointer;user-select:none;font-weight:500;
        }
        .damage-toggle input{width:16px;height:16px;accent-color:var(--danger);cursor:pointer;}
        .damage-toggle.checked{background:var(--danger-bg);border-color:#e8b0a8;color:var(--danger);}

        /* ─── Ô số lượng +/- ─── */
        .qty-block{margin-top:14px;}
        .qty-block .qty-label{font-size:.67rem;font-family:var(--font-mono);color:var(--ink-soft);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;display:block;}
        .qty-stepper{display:inline-flex;align-items:center;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface);overflow:hidden;}
        .qty-stepper button{
            width:36px;height:36px;border:none;background:transparent;
            font-size:1.1rem;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:background .15s;
        }
        .qty-stepper button:hover:not(:disabled){background:var(--border-soft);color:var(--ink);}
        .qty-stepper button:disabled{opacity:.35;cursor:not-allowed;}
        .qty-stepper input{
            width:60px;height:36px;border:none;border-left:1px solid var(--border);border-right:1px solid var(--border);
            text-align:center;font-family:var(--font-mono);font-size:.95rem;font-weight:600;
            background:var(--surface);color:var(--ink);outline:none;
        }
        .qty-stepper input::-webkit-outer-spin-button,
        .qty-stepper input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0;}
        .qty-stepper input[type=number]{-moz-appearance:textfield;}

        .damage-fields{display:none;margin-top:12px;padding:14px 16px;background:var(--surface);border:1px solid #e8b0a8;border-radius:var(--radius);}
        .damage-fields.show{display:block;}
        .damage-fields .field{margin-bottom:12px;}
        .damage-fields .field:last-child{margin-bottom:0;}
        .damage-fields label{display:block;font-size:.67rem;font-family:var(--font-mono);color:var(--ink-soft);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;}
        .damage-fields label .req{color:var(--danger);}
        .damage-fields textarea,
        .damage-fields select{
            width:100%;border:1px solid var(--border);border-radius:var(--radius);
            padding:8px 10px;font-size:.82rem;font-family:var(--font-sans);
            background:var(--surface);color:var(--ink);outline:none;transition:border-color .15s;
        }
        .damage-fields textarea{resize:vertical;min-height:60px;}
        .damage-fields textarea:focus,
        .damage-fields select:focus{border-color:var(--accent);}

        .row-error{font-size:.72rem;color:var(--danger);margin-top:6px;display:none;}
        .row-error.show{display:block;}

        /* ─── Summary ─── */
        .summary-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;}
        .summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
        .summary-item{display:flex;flex-direction:column;gap:3px;}
        .summary-item .s-lbl{font-size:.67rem;font-family:var(--font-mono);color:var(--ink-muted);text-transform:uppercase;}
        .summary-item .s-val{font-family:var(--font-mono);font-size:1rem;font-weight:500;}
        .summary-item .s-val.danger{color:var(--danger);}
        .summary-item .s-val.success{color:var(--success);}
        .summary-item .s-val.warning{color:var(--warning);}
        .summary-divider{border:none;border-top:1px solid var(--border-soft);margin:16px 0;}

        .btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border:none;border-radius:var(--radius);font-size:.82rem;font-family:var(--font-mono);cursor:pointer;transition:background .18s;text-decoration:none;white-space:nowrap;}
        .btn-primary{background:var(--accent);color:#fff;}
        .btn-primary:hover{background:var(--accent-hover);}
        .btn-ghost{background:transparent;color:var(--ink-soft);border:1px solid var(--border);}
        .btn-ghost:hover{background:var(--border);color:var(--ink);}
        .btn:disabled{opacity:.45;cursor:not-allowed;}
        .form-actions{display:flex;gap:10px;align-items:center;margin-top:24px;}

        .toast{position:fixed;bottom:24px;right:24px;background:var(--ink);color:#fff;padding:10px 18px;border-radius:var(--radius);font-size:.8rem;font-family:var(--font-mono);opacity:0;pointer-events:none;transition:opacity .3s,transform .3s;transform:translateY(8px);z-index:999;}
        .toast.show{opacity:1;transform:translateY(0);}
        .toast.error{background:var(--danger);}

        @media(max-width:680px){
            .product-row-header{grid-template-columns:52px 1fr;}
            .product-qty{display:none;}
            .product-inputs{padding-left:24px;}
            .summary-grid{grid-template-columns:1fr 1fr;}
        }
    </style>
</head>
<body>

<header class="page-header">
    <h1>Kiểm Tra Hàng Hoàn</h1>
    <a href="{{ route('hoa-don.index') }}" class="back-link">← Quay lại danh sách</a>
</header>

<div class="page-body">

    {{-- THÔNG TIN ĐƠN --}}
    <p class="section-label">Thông tin đơn hàng</p>
    <div class="card">
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item"><span class="lbl">Mã hóa đơn</span><span class="val mono">HD-{{ str_pad($hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}</span></div>
                <div class="info-item"><span class="lbl">Trạng thái</span><span class="val"><span class="badge badge-cancelled">{{ $hoaDon->trang_thai }}</span></span></div>
                <div class="info-item"><span class="lbl">Nguồn lỗi</span><span class="val">
                    @if($hoaDon->nguon_huy === 'SHIPPER') <span class="badge badge-shipper">Do Shipper</span>
                    @elseif($hoaDon->nguon_huy === 'CUSTOMER') <span class="badge badge-customer">Do Khách hàng</span>
                    @else <span style="color:var(--ink-muted)">—</span> @endif
                </span></div>
                <div class="info-item"><span class="lbl">Lý do hủy</span><span class="val">{{ $hoaDon->ly_do_huy ?? '—' }}</span></div>
                <div class="info-item"><span class="lbl">Ngày hủy</span><span class="val mono">{{ $hoaDon->thoi_diem_huy ? \Carbon\Carbon::parse($hoaDon->thoi_diem_huy)->format('d/m/Y H:i') : '—' }}</span></div>
                <div class="info-item"><span class="lbl">Khách hàng</span><span class="val">{{ $khachHang?->ten_khach_hang ?? '—' }}</span></div>
                <div class="info-item"><span class="lbl">Shipper</span><span class="val">{{ $shipper?->ten_nhan_vien ?? '—' }}</span></div>
                <div class="info-item"><span class="lbl">Tổng tiền đơn</span><span class="val mono">{{ number_format($hoaDon->tong_tien, 0, ',', '.') }}đ</span></div>
            </div>
        </div>
    </div>

    @if($daKiemTra)
    <div class="card">
        <div class="card-body success">
            ✔ Kết quả đã được lưu cố định vào
            {{ optional($hangHoan->thoi_diem_kiem_tra)->format('d/m/Y H:i') ?? 'hệ thống' }}.
            Trang này chỉ dùng để xem lại log — không thể chỉnh sửa.
        </div>
    </div>
    @endif

    {{-- DANH SÁCH SẢN PHẨM --}}
    <p class="section-label">{{ $daKiemTra ? 'Kết quả kiểm tra (chỉ xem)' : 'Danh sách sản phẩm cần kiểm tra' }}</p>

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
                    $daXuLy = $ct->ket_qua === 'DA_XU_LY';
                    $readonly = $daKiemTra || $daXuLy;
                @endphp

                <div class="product-row {{ $daKiemTra ? 'open' : '' }}"
                     id="product-row-{{ $ct->ma_chi_tiet_hang_hoan }}"
                     data-max="{{ $ct->so_luong_theo_don }}"
                     data-gia="{{ $ct->gia_ban_snapshot }}"
                     data-la-bo="{{ $laBoHoa ? 1 : 0 }}">

                    {{-- Header --}}
                    <div class="product-row-header" onclick="toggleRow('{{ $ct->ma_chi_tiet_hang_hoan }}')">
                        @if($hinh)
                            <img src="{{ asset($hinh) }}" alt="{{ $ten }}" class="product-thumb">
                        @else
                            <div class="product-thumb-placeholder">🌸</div>
                        @endif

                        <div class="product-meta">
                            <span class="type-tag @if($laBoHoa) bo-hoa @endif">{{ $laBoHoa ? 'Bó hoa' : 'Sản phẩm lẻ' }}</span>
                            <div class="name">{{ $ten }}</div>
                            <div class="sub">
                                {{ $laBoHoa ? 'MBH' : 'MSP' }}-{{ str_pad($ma, 4, '0', STR_PAD_LEFT) }}
                                @if($daXuLy) &nbsp;·&nbsp; <span style="color:var(--success)">✔ Đã xử lý</span> @endif
                                @if($daKiemTra && $ct->so_luong_hong > 0)
                                    &nbsp;·&nbsp; <span style="color:var(--danger)">Hỏng: {{ $ct->so_luong_hong }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="product-qty">
                            <div class="number">{{ $ct->so_luong_theo_don }}</div>
                            <div class="caption">theo đơn</div>
                        </div>

                        <span class="chevron">▼</span>
                    </div>

                    {{-- Inputs --}}
                    <div class="product-inputs">

                        {{-- Bó hoa: checkbox xả bó --}}
                        @if($laBoHoa && !$daKiemTra && !$daXuLy)
                            <label class="damage-toggle" id="xabo-label-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                   onclick="onXaBo('{{ $ct->ma_chi_tiet_hang_hoan }}')">
                                <input type="checkbox" id="xabo-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                       {{ $ct->ket_qua === 'HONG' && $ct->so_luong_con_ban_duoc == 0 && $ct->so_luong_hong > 0 ? 'checked' : '' }}>
                                <span>Xả toàn bộ bó (toàn bộ xem là hỏng)</span>
                            </label>
                            <input type="hidden"
                                   name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][xa_bo]"
                                   id="xabo-hidden-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                   value="{{ $ct->ket_qua === 'HONG' && $ct->so_luong_con_ban_duoc == 0 && $ct->so_luong_hong > 0 ? '1' : '0' }}">
                        @elseif($laBoHoa)
                            <input type="hidden"
                                   name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][xa_bo]"
                                   value="{{ $ct->ket_qua === 'HONG' && $ct->so_luong_con_ban_duoc == 0 && $ct->so_luong_hong > 0 ? '1' : '0' }}">
                        @else
                            <input type="hidden" name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][xa_bo]" value="0">
                        @endif

                        {{-- Checkbox "có hỏng" (ẩn khi xả bó) --}}
                        <label class="damage-toggle {{ $ct->so_luong_hong > 0 ? 'checked' : '' }}"
                               id="hong-label-{{ $ct->ma_chi_tiet_hang_hoan }}"
                               style="{{ $laBoHoa && $ct->ket_qua === 'HONG' && $ct->so_luong_con_ban_duoc == 0 ? 'display:none;' : '' }}">
                            <input type="checkbox" id="hong-check-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                   {{ $ct->so_luong_hong > 0 ? 'checked' : '' }}
                                   {{ $readonly ? 'disabled' : '' }}
                                   onchange="onCheckHong('{{ $ct->ma_chi_tiet_hang_hoan }}', this.checked)">
                            <span>sản phẩm nguyên vẹn(bán được)</span>
                        </label>

                        {{-- Số lượng hỏng +/– --}}
                        <div class="qty-block" id="hong-block-{{ $ct->ma_chi_tiet_hang_hoan }}"
                             style="{{ $ct->so_luong_hong > 0 ? '' : 'display:none;' }}">
                            <span class="qty-label">Số lượng hỏng</span>
                            <div class="qty-stepper">
                                <button type="button"
                                        onclick="adjustQty('{{ $ct->ma_chi_tiet_hang_hoan }}','hong',-1)"
                                        {{ $readonly ? 'disabled' : '' }}>−</button>
                                <input type="number"
                                       id="hong-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                       name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][so_luong_hong]"
                                       value="{{ (int) $ct->so_luong_hong }}"
                                       min="0" max="{{ $ct->so_luong_theo_don }}" readonly>
                                <button type="button"
                                        onclick="adjustQty('{{ $ct->ma_chi_tiet_hang_hoan }}','hong',1)"
                                        {{ $readonly ? 'disabled' : '' }}>+</button>
                            </div>
                        </div>

                        {{-- Lý do + NV (chỉ hiện khi có hỏng) --}}
                        <div class="damage-fields {{ $ct->so_luong_hong > 0 ? 'show' : '' }}"
                             id="damage-fields-{{ $ct->ma_chi_tiet_hang_hoan }}">
                            <div class="field">
                                <label>Lý do hỏng <span class="req">*</span></label>
                                <textarea id="lydo-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                          name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][ly_do_hong]"
                                          placeholder="VD: Hoa dập, cánh gãy..."
                                          {{ $readonly ? 'disabled' : '' }}>{{ old('items.'.$ct->ma_chi_tiet_hang_hoan.'.ly_do_hong', $ct->so_luong_hong > 0 ? $ct->ghi_chu : '') }}</textarea>
                            </div>
                            <div class="field">
                                <label>Nhân viên báo cáo <span class="req">*</span></label>
                                <select id="nhanvien-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                        name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][ma_nhan_vien]"
                                        {{ $readonly ? 'disabled' : '' }}>
                                    <option value="">-- Chọn nhân viên --</option>
                                    @foreach($nhanViens as $nv)
                                        <option value="{{ $nv->ma_nhan_vien }}">{{ $nv->ten_nhan_vien }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Số lượng còn bán +/– --}}
                        <div class="qty-block" id="ban-block-{{ $ct->ma_chi_tiet_hang_hoan }}">
                            <span class="qty-label">Số lượng còn bán được</span>
                            <div class="qty-stepper">
                                <button type="button"
                                        onclick="adjustQty('{{ $ct->ma_chi_tiet_hang_hoan }}','ban',-1)"
                                        {{ $readonly ? 'disabled' : '' }}>−</button>
                                <input type="number"
                                       id="ban-{{ $ct->ma_chi_tiet_hang_hoan }}"
                                       name="items[{{ $ct->ma_chi_tiet_hang_hoan }}][so_luong_con_ban_duoc]"
                                       value="{{ (int) $ct->so_luong_con_ban_duoc }}"
                                       min="0" max="{{ $ct->so_luong_theo_don }}" readonly>
                                <button type="button"
                                        onclick="adjustQty('{{ $ct->ma_chi_tiet_hang_hoan }}','ban',1)"
                                        {{ $readonly ? 'disabled' : '' }}>+</button>
                            </div>
                        </div>

                        <div class="row-error" id="error-{{ $ct->ma_chi_tiet_hang_hoan }}"></div>
                    </div>
                </div>
            @empty
                <div style="padding:32px 24px;text-align:center;color:var(--ink-muted);">
                    Không có sản phẩm nào trong hàng hoàn này.
                </div>
            @endforelse
        </div>

        {{-- TỔNG KẾT --}}
        <p class="section-label">Tổng kết kiểm tra</p>
        <div class="summary-box">
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="s-lbl">Tổng số lượng hỏng</span>
                    <span class="s-val danger" id="sum-hong">0</span>
                </div>
                <div class="summary-item">
                    <span class="s-lbl">Tổng còn bán được</span>
                    <span class="s-val success" id="sum-ban">0</span>
                </div>
                <div class="summary-item">
                    <span class="s-lbl">Tổng thiệt hại hàng hỏng</span>
                    <span class="s-val warning" id="sum-thiet-hai">0đ</span>
                </div>
            </div>
            <hr class="summary-divider">
            <p style="font-size:.73rem;color:var(--ink-muted);font-family:var(--font-mono);">
                ⓘ Tiền hệ thống chịu, tiền shipper chịu, tiền hao hụt Flash Sale và phạt shipper sẽ được tính bởi backend.
            </p>
            @if($hoaDon->nguon_huy === 'CUSTOMER')
                <div style="margin-top:12px;padding:10px 14px;background:var(--success-bg);border:1px solid #a8d5b8;border-radius:var(--radius);font-size:.78rem;color:var(--success);">
                    ✔ Lỗi do khách hàng — shipper được miễn phạt.
                </div>
            @endif
        </div>

        {{-- NÚT --}}
        <div class="form-actions">
            @if($daKiemTra)
                <a href="{{ route('hoa-don.index') }}" class="btn btn-primary">← Quay lại danh sách hóa đơn</a>
                <span style="font-size:.78rem;color:var(--success);margin-left:12px;">
                    ✔ Kết quả đã lưu cố định, không thể chỉnh sửa.
                </span>
            @else
                <button type="submit" class="btn btn-primary" id="submitBtn">✔ Lưu kết quả kiểm tra</button>
                <a href="{{ route('hoa-don.index') }}" class="btn btn-ghost">Hủy</a>
            @endif
            <span id="form-global-error" style="font-size:.75rem;color:var(--danger);display:none;"></span>
        </div>
    </form>

</div>

<div class="toast" id="toast"></div>

<script>
const READONLY = @json($daKiemTra);

function fmt(n){ return Number(n).toLocaleString('vi-VN') + 'đ'; }
function showToast(msg, type='success'){
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast ' + type;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}
function toggleRow(id){
    document.getElementById('product-row-' + id).classList.toggle('open');
}

// Đồng bộ hai ô số lượng: hong + ban = max
function adjustQty(id, field, delta){
    if (READONLY) return;
    const row = document.getElementById('product-row-' + id);
    const max = Number(row.dataset.max);
    const hong = document.getElementById('hong-' + id);
    const ban  = document.getElementById('ban-' + id);

    let h = Number(hong.value), b = Number(ban.value);
    if (field === 'hong') {
        h = Math.max(0, Math.min(max, h + delta));
    } else {
        b = Math.max(0, Math.min(max, b + delta));
        h = max - b;
        // Nếu đang xả bó (hong = max) thì không cho giảm ban
        if (h === max && b > 0) { b = 0; h = max; }
    }
    if (field === 'hong') {
        b = max - h;
    }
    hong.value = h;
    ban.value  = b;

    // Nếu hong về 0 → bỏ tick
    const check = document.getElementById('hong-check-' + id);
    if (check) check.checked = h > 0;
    document.getElementById('hong-label-' + id)?.classList.toggle('checked', h > 0);

    // Hiển thị/ẩn block hỏng + damage fields
    document.getElementById('hong-block-' + id).style.display = h > 0 ? '' : 'none';
    document.getElementById('damage-fields-' + id).classList.toggle('show', h > 0);

    updateSummary();
    validateRow(id);
}

function onCheckHong(id, checked){
    if (READONLY) return;
    const row = document.getElementById('product-row-' + id);
    const max = Number(row.dataset.max);
    const hong = document.getElementById('hong-' + id);
    const ban  = document.getElementById('ban-' + id);

    document.getElementById('hong-label-' + id).classList.toggle('checked', checked);
    document.getElementById('hong-block-' + id).style.display = checked ? '' : 'none';
    document.getElementById('damage-fields-' + id).classList.toggle('show', checked);

    if (checked) {
        if (Number(hong.value) === 0) hong.value = 1;
        ban.value = max - Number(hong.value);
    } else {
        hong.value = 0;
        ban.value = max;
    }
    updateSummary();
    validateRow(id);
}

function onXaBo(id){
    if (READONLY) return;
    const check  = document.getElementById('xabo-' + id);
    const hidden = document.getElementById('xabo-hidden-' + id);
    // checkbox onclick ở label, ngăn default để tự đổi
    const next = !check.checked;
    check.checked = next;
    hidden.value = next ? '1' : '0';

    const row = document.getElementById('product-row-' + id);
    const max = Number(row.dataset.max);
    const hong = document.getElementById('hong-' + id);
    const ban  = document.getElementById('ban-' + id);
    const hongLabel = document.getElementById('hong-label-' + id);
    const hongBlock = document.getElementById('hong-block-' + id);
    const dmgFields = document.getElementById('damage-fields-' + id);

    if (next) {
        hong.value = max;
        ban.value = 0;
        hongLabel.style.display = 'none';
        hongBlock.style.display = '';
        dmgFields.classList.add('show');
    } else {
        hong.value = 0;
        ban.value = max;
        hongLabel.style.display = '';
        hongBlock.style.display = 'none';
        dmgFields.classList.remove('show');
    }
    updateSummary();
    validateRow(id);
}

function validateRow(id){
    const row = document.getElementById('product-row-' + id);
    const max = Number(row.dataset.max);
    const hong = Number(document.getElementById('hong-' + id)?.value || 0);
    const ban  = Number(document.getElementById('ban-' + id)?.value || 0);
    const lyDo = document.getElementById('lydo-' + id)?.value?.trim() || '';
    const nv   = document.getElementById('nhanvien-' + id)?.value || '';
    const err  = document.getElementById('error-' + id);

    const errors = [];
    if (hong < 0 || ban < 0) errors.push('Số lượng không được âm.');
    if (hong + ban > max) errors.push(`Tổng SL (${hong + ban}) vượt quá SL theo đơn (${max}).`);
    if (hong > 0 && lyDo === '') errors.push('Vui lòng nhập lý do hỏng.');
    if (hong > 0 && nv === '')   errors.push('Vui lòng chọn nhân viên báo cáo.');

    err.textContent = errors.join(' ');
    err.classList.toggle('show', errors.length > 0);
    return errors.length === 0;
}

function updateSummary(){
    let totalHong = 0, totalBan = 0, totalThietHai = 0;
    document.querySelectorAll('[id^="product-row-"]').forEach(row => {
        const id = row.id.replace('product-row-', '');
        const gia = Number(row.dataset.gia || 0);
        const hong = Number(document.getElementById('hong-' + id)?.value || 0);
        const ban  = Number(document.getElementById('ban-' + id)?.value || 0);
        totalHong += hong;
        totalBan  += ban;
        totalThietHai += hong * gia;
    });
    document.getElementById('sum-hong').textContent = totalHong;
    document.getElementById('sum-ban').textContent = totalBan;
    document.getElementById('sum-thiet-hai').textContent = fmt(totalThietHai);
}

document.getElementById('kiemTraForm').addEventListener('submit', function(e){
    if (READONLY) return;
    let ok = true;
    document.querySelectorAll('[id^="product-row-"]').forEach(row => {
        const id = row.id.replace('product-row-', '');
        if (!validateRow(id)) ok = false;
        if (!row.classList.contains('open')) row.classList.add('open');
    });
    if (!ok) {
        e.preventDefault();
        const ge = document.getElementById('form-global-error');
        ge.textContent = 'Vui lòng kiểm tra lại các dòng bị lỗi.';
        ge.style.display = 'inline';
        showToast('Có dòng bị lỗi — vui lòng kiểm tra lại.', 'error');
    }
});

document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[id^="product-row-"]').forEach(row => {
        const id = row.id.replace('product-row-', '');
        validateRow(id);
    });
    updateSummary();
});
</script>
</body>
</html>