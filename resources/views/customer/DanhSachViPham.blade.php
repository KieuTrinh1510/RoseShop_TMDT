<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet"
        href="{{ asset('css/Customer/ThongBao.css') }}?v={{ file_exists(public_path('css/Customer/ThongBao.css')) ? filemtime(public_path('css/Customer/ThongBao.css')) : time() }}">
    <title>Xử lý vi phạm - RoseShop</title>
    <style>
        :root {
            --rs-font: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        html,
        body,
        button,
        input,
        select,
        textarea {
            font-family: var(--rs-font) !important;
        }

        h1, h2, h3, h4, h5, h6, p, label {
            font-family: var(--rs-font);
        }

        .fa, .fas, .far, .fal, .fad, .fab, .fa-solid, .fa-regular, .fa-brands, [class*="fa-"], [class^="fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", "FontAwesome" !important;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: .86rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-box.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .alert-box.danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-box.info {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
    </style>
</head>

<body>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- TOPBAR ĐỒNG BỘ HOÀN TOÀN VỚI THÔNG BÁO & BÓ HOA ĐÃ LƯU --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <header class="topbar">
        <a href="{{ route('customer.dashboard') }}" style="text-decoration: none;">
            <span class="topbar-brand">🌸 RoseShop</span>
        </a>
        <a href="{{ route('customer.dashboard') }}" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại cửa hàng
        </a>
    </header>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- CONTAINER CHÍNH CĂN GIỮA ĐỒNG BỘ: .page-wrap           --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="page-wrap">

        {{-- TIÊU ĐỀ TRANG CHUNG CỦA MODULE ACCOUNT --}}
        <div class="page-title">
            <i class="fas fa-bell" style="color:var(--brand)"></i>
            Thông báo & Đơn hàng
        </div>

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- ACCOUNT TAB BAR: 5 TAB ĐỒNG BỘ KÍCH THƯỚC & VỊ TRÍ     --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        <div class="tab-bar">
            <a href="{{ route('customer.thong-bao', ['tab' => 'thong-bao']) }}" class="tab-btn" id="tab-thong-bao-btn">
                <i class="fas fa-bell"></i> Thông báo
                @if(($soThongBao ?? 0) > 0)
                    <span class="tab-badge">{{ $soThongBao }}</span>
                @endif
            </a>
            <a href="{{ route('customer.thong-bao', ['tab' => 'don-hien-tai']) }}" class="tab-btn" id="tab-don-hien-tai-btn">
                <i class="fas fa-truck"></i> Đơn hiện tại
                @if(($soDonHienTai ?? 0) > 0)
                    <span class="tab-badge">{{ $soDonHienTai }}</span>
                @endif
            </a>
            <a href="{{ route('customer.thong-bao', ['tab' => 'lich-su']) }}" class="tab-btn" id="tab-lich-su-btn">
                <i class="fas fa-history"></i> Lịch sử
            </a>
            <a href="{{ route('customer.thong-bao', ['tab' => 'bo-hoa-da-luu']) }}" class="tab-btn" id="tab-bo-hoa-da-luu-btn">
                <i class="fas fa-heart"></i> Bó hoa đã lưu
            </a>
            <a href="{{ route('customer.vi-pham.index') }}" class="tab-btn active" id="tab-vi-pham-btn">
                <i class="fas fa-shield-alt"></i> Xử lý vi phạm
                @if(($soViPhamChuaGo ?? 0) > 0)
                    <span class="tab-badge">{{ $soViPhamChuaGo }}</span>
                @endif
            </a>
        </div>

        {{-- SECTION TITLE & DESCRIPTION --}}
        <div class="detail-section-title" style="margin-top: 24px; font-size: 1.05rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-alt" style="color: var(--brand);"></i> Xử lý vi phạm
        </div>
        <p style="font-size: .84rem; color: var(--muted); margin: 6px 0 20px; line-height: 1.5;">
            Kiểm tra các đơn giao thất bại và hoàn tất thanh toán để duy trì quyền mua hàng và thanh toán COD.
        </p>

        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="alert-box success">
                <i class="fas fa-check-circle" style="font-size: 1.1rem;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert-box danger">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.1rem;"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if(session('info'))
            <div class="alert-box info">
                <i class="fas fa-info-circle" style="font-size: 1.1rem;"></i>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- KPI CARDS: 3 CỘT ĐỀU NHAU, NẰM TRONG CONTAINER CHÍNH    --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        <div class="vp-kpi-grid">
            {{-- KPI 1: Vi phạm chưa xử lý --}}
            <div class="vp-kpi-card">
                <div class="vp-kpi-icon {{ $soViPhamChuaGo > 0 ? 'red' : 'green' }}">
                    <i class="fas {{ $soViPhamChuaGo > 0 ? 'fa-exclamation-triangle' : 'fa-check' }}"></i>
                </div>
                <div class="vp-kpi-content">
                    <div class="vp-kpi-label">Vi phạm chưa xử lý</div>
                    <div class="vp-kpi-value" style="color: {{ $soViPhamChuaGo > 0 ? 'var(--red)' : 'var(--green)' }};">
                        {{ $soViPhamChuaGo }}
                    </div>
                </div>
            </div>

            {{-- KPI 2: Vi phạm đã xử lý --}}
            <div class="vp-kpi-card">
                <div class="vp-kpi-icon green">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="vp-kpi-content">
                    <div class="vp-kpi-label">Vi phạm đã xử lý</div>
                    <div class="vp-kpi-value" style="color: var(--green);">
                        {{ $soViPhamDaGo }}
                    </div>
                </div>
            </div>

            {{-- KPI 3: Trạng thái COD / Quyền mua hàng --}}
            <div class="vp-kpi-card">
                @if($soViPhamChuaGo >= 3)
                    <div class="vp-kpi-icon red">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <div class="vp-kpi-content">
                        <div class="vp-kpi-label">Quyền mua hàng</div>
                        <span class="vp-status-badge danger">
                            <i class="fas fa-lock"></i> Tạm khóa
                        </span>
                    </div>
                @elseif($trangThaiCod === 'TAM_KHOA')
                    <div class="vp-kpi-icon amber">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="vp-kpi-content">
                        <div class="vp-kpi-label">Trạng thái COD</div>
                        <span class="vp-status-badge warning">
                            <i class="fas fa-exclamation-circle"></i> Tạm khóa
                        </span>
                    </div>
                @else
                    <div class="vp-kpi-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="vp-kpi-content">
                        <div class="vp-kpi-label">Trạng thái COD</div>
                        <span class="vp-status-badge active">
                            <i class="fas fa-check"></i> Đang hoạt động
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- TAB "CHƯA XỬ LÝ / ĐÃ XỬ LÝ": RỘNG 100% CONTENT AREA    --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        <div class="vp-subtabs">
            <a href="?tab=chua-xu-ly"
               class="vp-subtab-btn {{ ($subTab ?? 'chua-xu-ly') === 'chua-xu-ly' ? 'active' : '' }}">
                <i class="fas fa-clock"></i> Chưa xử lý
                <span class="vp-subtab-badge red">{{ $soViPhamChuaGo }}</span>
            </a>
            <a href="?tab=da-xu-ly"
               class="vp-subtab-btn {{ ($subTab ?? '') === 'da-xu-ly' ? 'active' : '' }}">
                <i class="fas fa-check-double"></i> Đã xử lý
                <span class="vp-subtab-badge green">{{ $soViPhamDaGo }}</span>
            </a>
        </div>

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- NỘI DUNG TAB 1: CHƯA XỬ LÝ                             --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        @if(($subTab ?? 'chua-xu-ly') === 'chua-xu-ly')
            @if($viPhamsChuaXuLy->isEmpty())
                <div class="empty-state" style="background:#fff; border:1.5px dashed var(--border); border-radius:14px; padding:36px 20px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.02);">
                    <div style="font-size:2.4rem; margin-bottom:10px;">🛡️</div>
                    <p style="font-size:1rem; font-weight:700; color:var(--text-main); margin:0 0 6px;">Bạn không có vi phạm cần xử lý</p>
                    <small style="font-size:.84rem; color:var(--muted); display:block; max-width:400px; margin:0 auto 16px; line-height:1.5;">
                        Hiện tại tài khoản của bạn không có đơn giao thất bại cần thanh toán. Hãy tiếp tục tận hưởng trải nghiệm mua sắm tại RoseShop!
                    </small>
                    <div>
                        <a href="{{ route('customer.dashboard') }}" class="btn-empty-action">
                            <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            @else
                @foreach($viPhamsChuaXuLy as $vp)
                    @php
                        $hd = $vp->hoaDon;
                        $items = $hd ? $hd->chi_tiet_hien_thi : collect();
                        $fallbackBo = $hd?->boHoas?->first();
                        $soTienCanThanhToan = (float) ($hd?->tong_tien ?? 0);

                        // Kiểm tra giao dịch đang chờ trong vòng 15 phút
                        $dangCho = $vp->thanhToanViPham
                            ->where('trang_thai', \App\Models\ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN)
                            ->filter(fn ($t) => $t->created_at && $t->created_at->gt(now()->subMinutes(15)))
                            ->first();
                    @endphp

                    <div class="order-card" id="vi-pham-{{ $vp->ma_vi_pham }}" style="margin-bottom: 16px;">
                        {{-- Header Card --}}
                        <div class="order-card-header" style="cursor: default;">
                            <div>
                                <div class="order-id">
                                    <i class="fas fa-receipt" style="font-size:.8rem"></i>
                                    #HD-{{ str_pad($vp->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}
                                    <span class="pill pill-cancelled">
                                        <i class="fas fa-exclamation-circle"></i> CHƯA XỬ LÝ
                                    </span>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar-alt"></i> Ngày đặt: {{ optional($hd?->ngay_dat)->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </div>
                        </div>

                        {{-- Toàn bộ sản phẩm của đơn --}}
                        <div style="padding: 14px 16px; border-bottom: 1px solid var(--border);">
                            <div class="detail-section-title" style="margin-bottom: 8px;">
                                <i class="fas fa-box-open"></i> Sản phẩm trong đơn hàng
                            </div>

                            @if($items->isNotEmpty())
                                @foreach($items as $ct)
                                    @php
                                        $imgUrl = null;
                                        if ($ct->ma_bo_hoa) {
                                            $bo = $ct->boHoa;
                                            $imgRaw = $bo?->hinh_anh ?: $bo?->mauBoHoa?->hinh_anh;
                                            $imgUrl = $imgRaw ? asset($imgRaw) : null;
                                        } elseif ($ct->sanPham) {
                                            $imgRaw = $ct->sanPham->hinh_anh;
                                            $imgUrl = $imgRaw ? asset($imgRaw) : null;
                                        }
                                        $giaSnapshot = (float) $ct->gia_ban_snapshot;
                                        $subtotal = $ct->so_luong * $giaSnapshot;
                                    @endphp
                                    <div class="vp-item-row">
                                        @if($imgUrl)
                                            <img src="{{ $imgUrl }}" alt="{{ $ct->ten_hien_thi }}" class="vp-item-thumb"
                                                 onerror="this.outerHTML='<div class=\'vp-item-thumb-placeholder\'>🌸</div>'">
                                        @else
                                            <div class="vp-item-thumb-placeholder">🌸</div>
                                        @endif

                                        <div class="vp-item-info">
                                            <div class="vp-item-name">{{ $ct->ten_hien_thi }}</div>
                                            <div class="vp-item-meta">
                                                <span>Số lượng: <strong>{{ $ct->so_luong }}</strong></span>
                                                <span>Giá lúc mua: <strong class="vp-item-price-snapshot">{{ number_format($giaSnapshot, 0, ',', '.') }}đ</strong></span>
                                            </div>
                                        </div>

                                        <div class="vp-item-subtotal">
                                            {{ number_format($subtotal, 0, ',', '.') }}đ
                                        </div>
                                    </div>
                                @endforeach
                            @elseif($fallbackBo)
                                @php
                                    $imgRaw = $fallbackBo->hinh_anh ?: $fallbackBo->mauBoHoa?->hinh_anh;
                                    $imgUrl = $imgRaw ? asset($imgRaw) : null;
                                    $giaBo = (float) $fallbackBo->gia_ban;
                                    $subtotal = $fallbackBo->so_luong * $giaBo;
                                @endphp
                                <div class="vp-item-row">
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="{{ $fallbackBo->ten_bo_hoa }}" class="vp-item-thumb"
                                             onerror="this.outerHTML='<div class=\'vp-item-thumb-placeholder\'>💐</div>'">
                                    @else
                                        <div class="vp-item-thumb-placeholder">💐</div>
                                    @endif

                                    <div class="vp-item-info">
                                        <div class="vp-item-name">{{ $fallbackBo->ten_bo_hoa }}</div>
                                        <div class="vp-item-meta">
                                            <span>Số lượng: <strong>{{ $fallbackBo->so_luong }}</strong></span>
                                            <span>Giá lúc mua: <strong class="vp-item-price-snapshot">{{ number_format($giaBo, 0, ',', '.') }}đ</strong></span>
                                        </div>
                                    </div>

                                    <div class="vp-item-subtotal">
                                        {{ number_format($subtotal, 0, ',', '.') }}đ
                                    </div>
                                </div>
                            @else
                                <div style="font-size:.82rem; color:var(--muted); font-style:italic;">
                                    Đơn hàng không có chi tiết sản phẩm hiển thị.
                                </div>
                            @endif
                        </div>

                        {{-- Thông tin giao thất bại & Nút thanh toán --}}
                        <div style="padding: 14px 16px; background: #fafbfc;">
                            <div class="vp-failure-box">
                                <div class="vp-failure-title">
                                    <i class="fas fa-exclamation-triangle"></i> Lý do giao thất bại
                                </div>
                                <div class="vp-failure-reason">
                                    {{ $vp->giaoHangThatBai?->ly_do ?: ($vp->ly_do ?: 'Khách từ chối nhận hàng / không liên lạc được.') }}
                                </div>
                                <div class="vp-failure-time">
                                    <i class="fas fa-clock"></i> Thời gian:
                                    {{ optional($vp->giaoHangThatBai?->thoi_diem_bao ?: $vp->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>

                            <div class="vp-payment-summary">
                                <div>
                                    <div class="vp-price-row">
                                        <span>Tổng giá trị đơn:</span>
                                        <span>{{ number_format($hd?->tong_tien ?? 0, 0, ',', '.') }}đ</span>
                                    </div>
                                    <div class="vp-price-row highlight">
                                        <span>SỐ TIỀN CẦN THANH TOÁN:</span>
                                        <span>{{ number_format($soTienCanThanhToan, 0, ',', '.') }}đ</span>
                                    </div>
                                </div>

                                <form action="{{ route('customer.vi-pham.vnpay', $vp->ma_vi_pham) }}" method="POST" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn-pay-vnpay">
                                        <i class="fas fa-credit-card"></i>
                                        @if($dangCho)
                                            Tiếp tục thanh toán qua VNPay
                                        @else
                                            Thanh toán qua VNPay
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- NỘI DUNG TAB 2: ĐÃ XỬ LÝ (da_go = true)                --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        @else
            @if($viPhamsDaXuLy->isEmpty())
                <div class="empty-state" style="background:#fff; border:1.5px dashed var(--border); border-radius:14px; padding:36px 20px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.02);">
                    <div style="font-size:2.4rem; margin-bottom:10px;">📁</div>
                    <p style="font-size:1rem; font-weight:700; color:var(--text-main); margin:0 0 6px;">Chưa có lịch sử vi phạm đã xử lý</p>
                    <small style="font-size:.84rem; color:var(--muted); display:block; max-width:400px; margin:0 auto 16px; line-height:1.5;">
                        Khi bạn hoàn tất thanh toán khoản vi phạm, thông tin giao dịch và biên lai sẽ hiển thị tại đây.
                    </small>
                    <div>
                        <a href="?tab=chua-xu-ly" class="btn-empty-action">
                            <i class="fas fa-arrow-left"></i> Xem vi phạm chưa xử lý
                        </a>
                    </div>
                </div>
            @else
                @foreach($viPhamsDaXuLy as $vp)
                    @php
                        $hd = $vp->hoaDon;
                        $items = $hd ? $hd->chi_tiet_hien_thi : collect();
                        $fallbackBo = $hd?->boHoas?->first();
                        $giaoDichThanhCong = $vp->thanhToanViPham
                            ->where('trang_thai', \App\Models\ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN)
                            ->first();
                        $soTienDaTra = (float) ($giaoDichThanhCong?->so_tien ?? ($hd?->tong_tien ?? 0));
                    @endphp

                    <div class="order-card" id="vi-pham-da-xu-ly-{{ $vp->ma_vi_pham }}" style="margin-bottom: 16px;">
                        {{-- Header Card --}}
                        <div class="order-card-header" style="cursor: default;">
                            <div>
                                <div class="order-id">
                                    <i class="fas fa-receipt" style="font-size:.8rem"></i>
                                    #HD-{{ str_pad($vp->ma_hoa_don, 4, '0', STR_PAD_LEFT) }}
                                    <span class="pill pill-delivered">
                                        <i class="fas fa-check-circle"></i> ĐÃ XỬ LÝ
                                    </span>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar-alt"></i> Ngày đặt: {{ optional($hd?->ngay_dat)->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </div>
                        </div>

                        {{-- Toàn bộ sản phẩm của đơn --}}
                        <div style="padding: 14px 16px; border-bottom: 1px solid var(--border);">
                            <div class="detail-section-title" style="margin-bottom: 8px;">
                                <i class="fas fa-box-open"></i> Sản phẩm trong đơn hàng
                            </div>

                            @if($items->isNotEmpty())
                                @foreach($items as $ct)
                                    @php
                                        $imgUrl = null;
                                        if ($ct->ma_bo_hoa) {
                                            $bo = $ct->boHoa;
                                            $imgRaw = $bo?->hinh_anh ?: $bo?->mauBoHoa?->hinh_anh;
                                            $imgUrl = $imgRaw ? asset($imgRaw) : null;
                                        } elseif ($ct->sanPham) {
                                            $imgRaw = $ct->sanPham->hinh_anh;
                                            $imgUrl = $imgRaw ? asset($imgRaw) : null;
                                        }
                                        $giaSnapshot = (float) $ct->gia_ban_snapshot;
                                        $subtotal = $ct->so_luong * $giaSnapshot;
                                    @endphp
                                    <div class="vp-item-row">
                                        @if($imgUrl)
                                            <img src="{{ $imgUrl }}" alt="{{ $ct->ten_hien_thi }}" class="vp-item-thumb"
                                                 onerror="this.outerHTML='<div class=\'vp-item-thumb-placeholder\'>🌸</div>'">
                                        @else
                                            <div class="vp-item-thumb-placeholder">🌸</div>
                                        @endif

                                        <div class="vp-item-info">
                                            <div class="vp-item-name">{{ $ct->ten_hien_thi }}</div>
                                            <div class="vp-item-meta">
                                                <span>Số lượng: <strong>{{ $ct->so_luong }}</strong></span>
                                                <span>Giá lúc mua: <strong class="vp-item-price-snapshot">{{ number_format($giaSnapshot, 0, ',', '.') }}đ</strong></span>
                                            </div>
                                        </div>

                                        <div class="vp-item-subtotal">
                                            {{ number_format($subtotal, 0, ',', '.') }}đ
                                        </div>
                                    </div>
                                @endforeach
                            @elseif($fallbackBo)
                                @php
                                    $imgRaw = $fallbackBo->hinh_anh ?: $fallbackBo->mauBoHoa?->hinh_anh;
                                    $imgUrl = $imgRaw ? asset($imgRaw) : null;
                                    $giaBo = (float) $fallbackBo->gia_ban;
                                    $subtotal = $fallbackBo->so_luong * $giaBo;
                                @endphp
                                <div class="vp-item-row">
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="{{ $fallbackBo->ten_bo_hoa }}" class="vp-item-thumb"
                                             onerror="this.outerHTML='<div class=\'vp-item-thumb-placeholder\'>💐</div>'">
                                    @else
                                        <div class="vp-item-thumb-placeholder">💐</div>
                                    @endif

                                    <div class="vp-item-info">
                                        <div class="vp-item-name">{{ $fallbackBo->ten_bo_hoa }}</div>
                                        <div class="vp-item-meta">
                                            <span>Số lượng: <strong>{{ $fallbackBo->so_luong }}</strong></span>
                                            <span>Giá lúc mua: <strong class="vp-item-price-snapshot">{{ number_format($giaBo, 0, ',', '.') }}đ</strong></span>
                                        </div>
                                    </div>

                                    <div class="vp-item-subtotal">
                                        {{ number_format($subtotal, 0, ',', '.') }}đ
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Thông tin giao thất bại & Lịch sử thanh toán đã xử lý --}}
                        <div style="padding: 14px 16px; background: #fafbfc;">
                            <div class="vp-failure-box" style="border-left-color:var(--gray); border-color:var(--border);">
                                <div class="vp-failure-title" style="color:var(--gray);">
                                    <i class="fas fa-info-circle"></i> Lý do giao thất bại trước đó
                                </div>
                                <div class="vp-failure-reason">
                                    {{ $vp->giaoHangThatBai?->ly_do ?: ($vp->ly_do ?: 'Khách từ chối nhận hàng.') }}
                                </div>
                                <div class="vp-failure-time">
                                    <i class="fas fa-clock"></i> Thời gian:
                                    {{ optional($vp->giaoHangThatBai?->thoi_diem_bao ?: $vp->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>

                            <div class="vp-resolved-box" style="margin-top: 10px;">
                                <div class="vp-resolved-title">
                                    <i class="fas fa-check-circle"></i> Thông tin thanh toán gỡ vi phạm
                                </div>
                                <div style="font-size:.82rem; color:var(--text-main); line-height:1.6;">
                                    <div>Đã thanh toán: <strong style="color:var(--green);">{{ number_format($soTienDaTra, 0, ',', '.') }}đ</strong></div>
                                    <div>Phương thức: <strong>{{ $giaoDichThanhCong?->phuong_thuc ?? 'VNPay' }}</strong></div>
                                    <div>Ngày thanh toán: <strong>{{ optional($giaoDichThanhCong?->thoi_diem_thanh_toan ?: $vp->thoi_diem_go)->format('d/m/Y H:i') ?? '—' }}</strong></div>
                                    @if($giaoDichThanhCong?->ma_giao_dich)
                                        <div>Mã giao dịch: <code style="font-size:.78rem; background:#f0fdf4; padding:2px 6px; border-radius:4px;">{{ $giaoDichThanhCong->ma_giao_dich }}</code></div>
                                    @endif
                                </div>
                                <div style="margin-top:8px; padding-top:6px; border-top:1px dashed #bbf7d0; font-size:.78rem; font-weight:700; color:var(--green);">
                                    <i class="fas fa-check"></i> Vi phạm đã được xử lý ({{ $vp->ly_do_go ?: 'Khách đã thanh toán qua VNPay' }})
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        @endif

    </div>

    <div id="toast"></div>

</body>
</html>