@extends('customer.layouts.customer')

@section('title', '💳 Thanh Toán – Hoa Tươi Shop')
@section('page-id', 'thanh-toan')

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/ThanhToan.css') }}?v={{ time() }}">
@endsection

@section('content')
@if(isset($dsBoHoa) && count($dsBoHoa) > 0)
@php
    $diaChiKH = optional($user->khachHang)->dia_chi ?? '';
    $parts    = array_map('trim', explode(',', $diaChiKH));
    if (count($parts) >= 1 && strtolower(end($parts)) === 'hà nội') array_pop($parts);
    $ttQuan    = old('quan_huyen',        count($parts) >= 2 ? $parts[count($parts)-1] : '');
    $ttPhuong  = old('xa_phuong',         count($parts) >= 3 ? $parts[count($parts)-2] : '');
    $ttChiTiet = old('dia_chi_chi_tiet',  count($parts) >= 1 ? implode(', ', array_slice($parts, 0, count($parts)-2)) : $diaChiKH);
@endphp
<div class="checkout-page">
    <div class="checkout-breadcrumb">
        <a href="{{ route('customer.dashboard') }}">🏠 Trang chủ</a>
        <span class="sep">›</span>
        <a href="{{ route('customer.gio-hang') }}">Giỏ hàng</a>
        <span class="sep">›</span>
        <span class="current">Thanh toán</span>
    </div>

    {{-- Top Header & 3-Step Progress Bar --}}
    <div class="checkout-header-bar">
        <div class="checkout-header-left">
            <div class="checkout-header-icon">
                <i class="far fa-credit-card"></i>
            </div>
            <div>
                <h1 class="checkout-header-title">Thanh Toán</h1>
                <p class="checkout-header-sub">Hoàn tất đơn hàng của bạn chỉ trong vài bước đơn giản</p>
            </div>
        </div>
        <div class="checkout-stepper">
            <div class="stepper-item active">
                <span class="stepper-num">1</span>
                <span class="stepper-txt">Thông tin nhận hàng</span>
            </div>
            <div class="stepper-line active"></div>
            <div class="stepper-item active">
                <span class="stepper-num">2</span>
                <span class="stepper-txt">Phương thức thanh toán</span>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item">
                <span class="stepper-num">3</span>
                <span class="stepper-txt">Hoàn tất đơn hàng</span>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert-checkout error">❌ {{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('customer.thanh-toan.store') }}" id="checkoutForm">
        @csrf
        <input type="hidden" name="loai_checkout" value="bo_hoa">
        <div class="checkout-layout">

            {{-- LEFT: Thông tin & Địa chỉ Shopee --}}
            <div class="checkout-panel">
                {{-- SHOPEE SHIPPING ADDRESS CARD --}}
                <div class="shopee-address-card">
                    <div class="shopee-address-header">
                        <div class="shopee-address-title">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Địa Chỉ Nhận Hàng</span>
                        </div>
                        @if(isset($diaChiHienTai) && $diaChiHienTai)
                            <button type="button" class="btn-shopee-change" onclick="openAddressListModal()">Thay Đổi</button>
                        @else
                            <button type="button" class="btn-shopee-change" onclick="openAddressCreateModal()">+ Thêm Mới</button>
                        @endif
                    </div>

                    <div id="shopeeAddressPreview" class="shopee-address-content">
                        @if(isset($diaChiHienTai) && $diaChiHienTai)
                            <div class="shopee-address-body">
                                <div class="shopee-address-pin-badge">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="shopee-address-details">
                                    <div class="shopee-user-info">
                                        <span id="dispTenNguoiNhan">{{ $diaChiHienTai->ten_nguoi_nhan }}</span>
                                        <span class="shopee-user-phone" id="dispSoDienThoai"><i class="fas fa-phone-alt" style="font-size:12px; color:#EC3D8F;"></i> {{ $diaChiHienTai->so_dien_thoai }}</span>
                                        <span class="badge-shopee-default" id="dispBadgeDefault" style="{{ $diaChiHienTai->la_mac_dinh ? '' : 'display:none;' }}">Mặc định</span>
                                    </div>
                                    <div class="shopee-address-text" id="dispDiaChiChiTiet">
                                        <i class="fas fa-home" style="font-size:12px; color:#98A2B3; margin-right:4px;"></i> {{ $diaChiHienTai->dia_chi_day_du }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="shopee-address-empty">
                                <span>Bạn chưa có địa chỉ nhận hàng. Vui lòng thêm địa chỉ nhận hàng để tiếp tục đặt hàng.</span>
                            </div>
                        @endif
                    </div>

                    {{-- Hidden inputs submitted with form --}}
                    <input type="hidden" name="ten_nguoi_nhan" id="inputTenNguoiNhan" value="{{ old('ten_nguoi_nhan', $diaChiHienTai->ten_nguoi_nhan ?? optional($user->khachHang)->ten_khach_hang) }}">
                    <input type="hidden" name="so_dien_thoai" id="inputSoDienThoai" value="{{ old('so_dien_thoai', $diaChiHienTai->so_dien_thoai ?? optional($user->khachHang)->so_dien_thoai) }}">
                    <input type="hidden" name="quan_huyen" id="inputQuanHuyen" value="{{ old('quan_huyen', $diaChiHienTai->quan_huyen ?? 'Cầu Giấy') }}">
                    <input type="hidden" name="xa_phuong" id="inputXaPhuong" value="{{ old('xa_phuong', $diaChiHienTai->xa_phuong ?? 'Dịch Vọng Hậu') }}">
                    <input type="hidden" name="dia_chi_chi_tiet" id="inputDiaChiChiTiet" value="{{ old('dia_chi_chi_tiet', $diaChiHienTai->dia_chi_chi_tiet ?? '') }}">
                    <input type="hidden" name="ma_dia_chi" id="inputMaDiaChi" value="{{ $diaChiHienTai->ma_dia_chi ?? '' }}">
                </div>

                {{-- Phương thức thanh toán --}}
                <div class="checkout-payment-card">
                    <div class="checkout-payment-header">
                        <i class="far fa-credit-card"></i>
                        <span>Phương Thức Thanh Toán</span>
                    </div>
                    @error('phuong_thuc_thanh_toan')<div class="error-msg" style="margin-bottom:10px;">{{ $message }}</div>@enderror

                    {{-- VNPay --}}
                    <div class="payment-option {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'selected' : '' }}"
                         onclick="selectPayment('NGAN_HANG', this)">
                        <div class="payment-option-top">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="NGAN_HANG"
                                   id="pay_vnpay" {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'checked' : '' }}>
                            <div class="vnpay-pill-logo">
                                <span style="color:#005BAA; font-weight:900; font-size:13px;">VN</span><span style="color:#ED1C24; font-weight:900; font-size:13px;">PAY</span>
                            </div>
                            <div class="payment-option-info">
                                <div class="payment-option-title">Thanh toán qua VNPAY</div>
                                <div class="payment-option-desc">
                                    Bạn sẽ được chuyển tới cổng thanh toán VNPAY để hoàn tất giao dịch. Đơn hàng chỉ được xác nhận sau khi VNPAY xác nhận thanh toán thành công.
                                </div>
                            </div>
                        </div>
                        <div class="payment-perks-row">
                            <span class="payment-perk-item"><i class="fas fa-shield-alt"></i> Thanh toán an toàn bảo mật tuyệt đối</span>
                            <span class="payment-perk-item"><i class="fas fa-bolt"></i> Hỗ trợ nhiều phương thức (ATM, QR, Thẻ, Ví điện tử)</span>
                            <span class="payment-perk-item"><i class="fas fa-check-circle"></i> Xác nhận tự động ngay lập tức</span>
                        </div>
                    </div>

                    <div class="bank-info {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'show' : '' }}"
                         id="vnpayInfoBox">
                        <p>💳 <strong>Cổng thanh toán:</strong> VNPay (hỗ trợ thẻ ATM nội địa, Internet Banking, quét mã QR, Visa/Master/JCB).</p>
                        <p>🔒 <strong>Bảo mật:</strong> Bạn nhập thông tin thanh toán trực tiếp trên trang của VNPay, shop không lưu thông tin thẻ/tài khoản của bạn.</p>
                        <p>⏱️ <strong>Lưu ý:</strong> Sau khi bấm "Đặt hàng", bạn có 15 phút để hoàn tất thanh toán trước khi phiên giao dịch hết hạn.</p>
                    </div>

                    {{-- COD --}}
                    <div class="payment-option {{ old('phuong_thuc_thanh_toan') === 'COD' ? 'selected' : '' }}"
                         onclick="selectPayment('COD', this)">
                        <div class="payment-option-top">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="COD"
                                   id="pay_cod" {{ old('phuong_thuc_thanh_toan') === 'COD' ? 'checked' : '' }}>
                            <div class="payment-icon-box">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="payment-option-info">
                                <div class="payment-option-title">Thanh toán khi nhận hàng (COD)</div>
                                <div class="payment-option-desc">
                                    Thanh toán trực tiếp cho shipper khi nhận được hoa.
                                </div>
                            </div>
                        </div>
                        <div class="payment-perks-row">
                            <span class="payment-perk-item"><i class="fas fa-box-open"></i> Kiểm tra sản phẩm trước khi thanh toán</span>
                            <span class="payment-perk-item"><i class="fas fa-truck"></i> Thanh toán linh hoạt dễ dàng</span>
                            <span class="payment-perk-item"><i class="fas fa-city"></i> Phù hợp cho khách hàng nội thành</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Bó hoa --}}
                        <div class="order-summary-panel">
                <h3>💐 Bó Hoa Đã Chọn</h3>

                @php
                    $tongGocBoHoa = 0;
                    foreach ($dsBoHoa as $bo) $tongGocBoHoa += (float) $bo['quote']['tong_tien'];
                @endphp

                <div class="order-items">
                    @foreach($dsBoHoa as $idx => $bo)
                        <div class="order-item-card">
                            <div class="order-item-thumb">
                                <div class="order-item-placeholder">💐</div>
                            </div>
                            <div class="order-item-meta">
                                <div class="order-item-name">{{ $bo['ten'] }}</div>
                                <div class="order-item-unit-price">{{ number_format($bo['quote']['gia_moi_bo'], 0, ',', '.') }}₫/bó</div>
                                @if(!empty($bo['thiep']))
                                    <div style="font-size:11px;color:#8e3555;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        💌 {{ $bo['thiep']->ten_san_pham }}
                                    </div>
                                @endif
                            </div>
                            <div class="checkout-item-stepper">
                                <button type="button" class="checkout-step-btn" disabled>−</button>
                                <span class="checkout-step-val">{{ $bo['so_luong'] }}</span>
                                <button type="button" class="checkout-step-btn" disabled>+</button>
                            </div>
                            <div class="order-item-line-price">
                                {{ number_format($bo['quote']['tong_tien'], 0, ',', '.') }}₫
                            </div>
                            @if(!empty($bo['key']))
                                <a href="javascript:void(0)" onclick="xoaBoHoaTrongThanhToan('{{ $bo['key'] }}')" class="order-item-delete-btn" title="Xóa">
                                    <i class="far fa-trash-alt"></i>
                                </a>
                            @else
                                <div></div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="order-totals">
                    <div class="total-row">
                        <span>Tạm tính</span>
                        <span class="val">{{ number_format($tongGocBoHoa, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="total-row">
                        <span>Vận chuyển</span>
                        <span class="ship-free">Miễn phí</span>
                    </div>

                    @auth
                    @php $diemHienCo = optional($user->khachHang)->diem_tich_luy ?? 0; @endphp
                    @if($diemHienCo > 0)
                    <div class="points-box" id="pointsBox">
                        <div class="points-box-header">
                            <span>⭐ Điểm tích lũy của bạn: <strong>{{ number_format($diemHienCo) }} điểm</strong></span>
                            <span class="points-equiv">(≡ {{ number_format($diemHienCo * 1000, 0, ',', '.') }}₫)</span>
                        </div>
                        <div class="points-input-row">
                            <input type="number" id="diemNhap" min="0" max="{{ $diemHienCo }}"
                                   value="{{ $diemSuDung > 0 ? $diemSuDung : '' }}"
                                   placeholder="Nhập số điểm muốn dùng">
                            <button type="button" onclick="apDiemBoHoa()">Áp dụng</button>
                        </div>
                        <div id="pointsMsg" class="points-msg"></div>
                    </div>
                    @endif
                    @endauth

                    <div class="total-row" id="rowGiamGia" style="{{ $diemSuDung > 0 ? '' : 'display:none;' }}">
                        <span id="lblGiamGia">Giảm giá ({{ number_format($diemSuDung) }} điểm)</span>
                        <span class="discount-val" id="valGiamGia">−{{ number_format($diemSuDung * 1000, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="total-row grand">
                        <span><strong>Tổng</strong></span>
                        <span class="val" id="valTongCuoi">
                            {{ number_format(max(0, $tongGocBoHoa - $diemSuDung * 1000), 0, ',', '.') }}₫
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-place-order" id="btnOrder">
                    <i class="far fa-credit-card"></i> <span>{{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'Đặt Hàng & Thanh Toán VNPAY' : 'Đặt Hàng (COD)' }}</span> <i class="fas fa-arrow-right"></i>
                </button>

                <div class="checkout-trust-badge">
                    <i class="fas fa-lock" style="color:#027A48;"></i> Thông tin thanh toán của bạn được bảo mật tuyệt đối
                </div>

                @if(session()->has('gio_bo_hoa'))
                    <a href="{{ route('customer.bo-hoa.gio.index') }}" class="btn-back-cart">
                        ← Quay lại giỏ bó hoa
                    </a>
                @else
                    <a href="{{ route('customer.bo-hoa.he-thong') }}" class="btn-back-cart">
                        ← Quay lại chọn bó hoa
                    </a>
                @endif
            </div>

        </div>
    </form>
</div>
@else
<div class="checkout-page">
    <div class="checkout-breadcrumb">
        <a href="{{ route('customer.dashboard') }}">🏠 Trang chủ</a>
        <span class="sep">›</span>
        <a href="{{ route('customer.gio-hang') }}">Giỏ hàng</a>
        <span class="sep">›</span>
        <span class="current">Thanh toán</span>
    </div>

    {{-- Top Header & 3-Step Progress Bar --}}
    <div class="checkout-header-bar">
        <div class="checkout-header-left">
            <div class="checkout-header-icon">
                <i class="far fa-credit-card"></i>
            </div>
            <div>
                <h1 class="checkout-header-title">Thanh Toán</h1>
                <p class="checkout-header-sub">Hoàn tất đơn hàng của bạn chỉ trong vài bước đơn giản</p>
            </div>
        </div>
        <div class="checkout-stepper">
            <div class="stepper-item active">
                <span class="stepper-num">1</span>
                <span class="stepper-txt">Thông tin nhận hàng</span>
            </div>
            <div class="stepper-line active"></div>
            <div class="stepper-item active">
                <span class="stepper-num">2</span>
                <span class="stepper-txt">Phương thức thanh toán</span>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item">
                <span class="stepper-num">3</span>
                <span class="stepper-txt">Hoàn tất đơn hàng</span>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert-checkout error">❌ {{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('customer.thanh-toan.store') }}" id="checkoutForm">
        @csrf
        <input type="hidden" name="loai_checkout" value="san_pham">
        <div class="checkout-layout">

            {{-- LEFT: Thông tin & Địa chỉ Shopee --}}
            <div class="checkout-panel">
                {{-- SHOPEE SHIPPING ADDRESS CARD --}}
                <div class="shopee-address-card">
                    <div class="shopee-address-header">
                        <div class="shopee-address-title">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Địa Chỉ Nhận Hàng</span>
                        </div>
                        @if(isset($diaChiHienTai) && $diaChiHienTai)
                            <button type="button" class="btn-shopee-change" onclick="openAddressListModal()">Thay Đổi</button>
                        @else
                            <button type="button" class="btn-shopee-change" onclick="openAddressCreateModal()">+ Thêm Mới</button>
                        @endif
                    </div>

                    <div id="shopeeAddressPreview" class="shopee-address-content">
                        @if(isset($diaChiHienTai) && $diaChiHienTai)
                            <div class="shopee-address-body">
                                <div class="shopee-address-pin-badge">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="shopee-address-details">
                                    <div class="shopee-user-info">
                                        <span id="dispTenNguoiNhan">{{ $diaChiHienTai->ten_nguoi_nhan }}</span>
                                        <span class="shopee-user-phone" id="dispSoDienThoai"><i class="fas fa-phone-alt" style="font-size:12px; color:#EC3D8F;"></i> {{ $diaChiHienTai->so_dien_thoai }}</span>
                                        <span class="badge-shopee-default" id="dispBadgeDefault" style="{{ $diaChiHienTai->la_mac_dinh ? '' : 'display:none;' }}">Mặc định</span>
                                    </div>
                                    <div class="shopee-address-text" id="dispDiaChiChiTiet">
                                        <i class="fas fa-home" style="font-size:12px; color:#98A2B3; margin-right:4px;"></i> {{ $diaChiHienTai->dia_chi_day_du }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="shopee-address-empty">
                                <span>Bạn chưa có địa chỉ nhận hàng. Vui lòng thêm địa chỉ nhận hàng để tiếp tục đặt hàng.</span>
                            </div>
                        @endif
                    </div>

                    {{-- Hidden inputs submitted with form --}}
                    <input type="hidden" name="ten_nguoi_nhan" id="inputTenNguoiNhan" value="{{ old('ten_nguoi_nhan', $diaChiHienTai->ten_nguoi_nhan ?? optional($user->khachHang)->ten_khach_hang) }}">
                    <input type="hidden" name="so_dien_thoai" id="inputSoDienThoai" value="{{ old('so_dien_thoai', $diaChiHienTai->so_dien_thoai ?? optional($user->khachHang)->so_dien_thoai) }}">
                    <input type="hidden" name="quan_huyen" id="inputQuanHuyen" value="{{ old('quan_huyen', $diaChiHienTai->quan_huyen ?? 'Cầu Giấy') }}">
                    <input type="hidden" name="xa_phuong" id="inputXaPhuong" value="{{ old('xa_phuong', $diaChiHienTai->xa_phuong ?? 'Dịch Vọng Hậu') }}">
                    <input type="hidden" name="dia_chi_chi_tiet" id="inputDiaChiChiTiet" value="{{ old('dia_chi_chi_tiet', $diaChiHienTai->dia_chi_chi_tiet ?? '') }}">
                    <input type="hidden" name="ma_dia_chi" id="inputMaDiaChi" value="{{ $diaChiHienTai->ma_dia_chi ?? '' }}">
                </div>

                {{-- Phương thức thanh toán --}}
                <div class="checkout-payment-card">
                    <div class="checkout-payment-header">
                        <i class="far fa-credit-card"></i>
                        <span>Phương Thức Thanh Toán</span>
                    </div>
                    @error('phuong_thuc_thanh_toan')<div class="error-msg" style="margin-bottom:10px;">{{ $message }}</div>@enderror

                    {{-- VNPay --}}
                    <div class="payment-option {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'selected' : '' }}"
                         onclick="selectPayment('NGAN_HANG', this)">
                        <div class="payment-option-top">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="NGAN_HANG"
                                   id="pay_vnpay" {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'checked' : '' }}>
                            <div class="vnpay-pill-logo">
                                <span style="color:#005BAA; font-weight:900; font-size:13px;">VN</span><span style="color:#ED1C24; font-weight:900; font-size:13px;">PAY</span>
                            </div>
                            <div class="payment-option-info">
                                <div class="payment-option-title">Thanh toán qua VNPAY</div>
                                <div class="payment-option-desc">
                                    Bạn sẽ được chuyển tới cổng thanh toán VNPAY để hoàn tất giao dịch. Đơn hàng chỉ được xác nhận sau khi VNPAY xác nhận thanh toán thành công.
                                </div>
                            </div>
                        </div>
                        <div class="payment-perks-row">
                            <span class="payment-perk-item"><i class="fas fa-shield-alt"></i> Thanh toán an toàn bảo mật tuyệt đối</span>
                            <span class="payment-perk-item"><i class="fas fa-bolt"></i> Hỗ trợ nhiều phương thức (ATM, QR, Thẻ, Ví điện tử)</span>
                            <span class="payment-perk-item"><i class="fas fa-check-circle"></i> Xác nhận tự động ngay lập tức</span>
                        </div>
                    </div>

                    <div class="bank-info {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'show' : '' }}"
                         id="vnpayInfoBox">
                        <p>💳 <strong>Cổng thanh toán:</strong> VNPay (hỗ trợ thẻ ATM nội địa, Internet Banking, quét mã QR, Visa/Master/JCB).</p>
                        <p>🔒 <strong>Bảo mật:</strong> Bạn nhập thông tin thanh toán trực tiếp trên trang của VNPay, shop không lưu thông tin thẻ/tài khoản của bạn.</p>
                        <p>⏱️ <strong>Lưu ý:</strong> Sau khi bấm "Đặt hàng", bạn có 15 phút để hoàn tất thanh toán trước khi phiên giao dịch hết hạn.</p>
                    </div>

                    {{-- COD --}}
                    <div class="payment-option {{ old('phuong_thuc_thanh_toan') === 'COD' ? 'selected' : '' }}"
                         onclick="selectPayment('COD', this)">
                        <div class="payment-option-top">
                            <input type="radio" name="phuong_thuc_thanh_toan" value="COD"
                                   id="pay_cod" {{ old('phuong_thuc_thanh_toan') === 'COD' ? 'checked' : '' }}>
                            <div class="payment-icon-box">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="payment-option-info">
                                <div class="payment-option-title">Thanh toán khi nhận hàng (COD)</div>
                                <div class="payment-option-desc">
                                    Thanh toán trực tiếp cho shipper khi nhận được hoa.
                                </div>
                            </div>
                        </div>
                        <div class="payment-perks-row">
                            <span class="payment-perk-item"><i class="fas fa-box-open"></i> Kiểm tra sản phẩm trước khi thanh toán</span>
                            <span class="payment-perk-item"><i class="fas fa-truck"></i> Thanh toán linh hoạt dễ dàng</span>
                            <span class="payment-perk-item"><i class="fas fa-city"></i> Phù hợp cho khách hàng nội thành</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Đơn hàng --}}
            <div class="order-summary-panel">
                <div class="order-summary-header-row">
                    <h3>🛍️ Đơn Hàng Của Bạn ({{ count($gioHang) }} sản phẩm)</h3>
                    <a href="{{ route('customer.gio-hang') }}" class="btn-summary-back">
                        ← Quay lại giỏ hàng
                    </a>
                </div>

                <div class="order-items">
                    @foreach($gioHang as $key => $item)
                    <div class="order-item-card">
                        <div class="order-item-thumb">
                            @if($item['hinh_anh'])
                                <img src="{{ asset($item['hinh_anh']) }}" alt="{{ $item['ten_san_pham'] }}"
                                     class="order-item-img"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <div class="order-item-placeholder" style="display:none;">🌸</div>
                            @else
                                <div class="order-item-placeholder">🌸</div>
                            @endif
                        </div>
                        <div class="order-item-meta">
                            <div class="order-item-name">{{ $item['ten_san_pham'] }}</div>
                            <div class="order-item-unit-price">{{ number_format($item['gia_ban'], 0, ',', '.') }}₫</div>
                        </div>
                        <div class="checkout-item-stepper">
                            <button type="button" class="checkout-step-btn" disabled>−</button>
                            <span class="checkout-step-val">{{ $item['so_luong'] }}</span>
                            <button type="button" class="checkout-step-btn" disabled>+</button>
                        </div>
                        <div class="order-item-line-price">
                            {{ number_format($item['gia_ban'] * $item['so_luong'], 0, ',', '.') }}₫
                        </div>
                        <a href="{{ route('customer.gio-hang') }}" class="order-item-delete-btn" title="Chỉnh sửa trong giỏ hàng">
                            <i class="far fa-trash-alt"></i>
                        </a>
                    </div>
                    @endforeach
                </div>

                @php
                    $tongTienGoc = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
                    $giamGia     = $diemSuDung * 1000;
                    $tongCuoi    = max(0, $tongTienGoc - $giamGia);
                @endphp

                {{-- Áp điểm tích lũy --}}
                @auth
                @php $diemHienCo = optional($user->khachHang)->diem_tich_luy ?? 0; @endphp
                @if($diemHienCo > 0)
                <div class="points-box" id="pointsBox">
                    <div class="points-box-header">
                        <span>⭐ Điểm tích lũy của bạn: <strong>{{ number_format($diemHienCo) }} điểm</strong></span>
                        <span class="points-equiv">(= {{ number_format($diemHienCo * 1000, 0, ',', '.') }}₫)</span>
                    </div>
                    <div class="points-input-row">
                        <input type="number" id="diemNhap" min="0" max="{{ $diemHienCo }}"
                               value="{{ $diemSuDung > 0 ? $diemSuDung : '' }}"
                               placeholder="Nhập số điểm muốn dùng">
                        <button type="button" onclick="apDiem()">Áp dụng</button>
                    </div>
                    <div id="pointsMsg" class="points-msg"></div>
                </div>
                @endif
                @endauth

                <div class="order-totals">
                    <div class="total-row">
                        <span>Tạm tính</span>
                        <span class="val">{{ number_format($tongTienGoc, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="total-row" id="rowGiamGia" style="{{ $diemSuDung > 0 ? '' : 'display:none;' }}">
                        <span id="lblGiamGia">Giảm giá ({{ number_format($diemSuDung) }} điểm)</span>
                        <span class="discount-val" id="valGiamGia">−{{ number_format($diemSuDung * 1000, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="total-row">
                        <span>Giảm giá (nếu có)</span>
                        <span class="val">{{ $diemSuDung > 0 ? number_format($diemSuDung * 1000, 0, ',', '.') . '₫' : '0₫' }}</span>
                    </div>
                    <div class="total-row">
                        <span>Phí vận chuyển</span>
                        <span class="ship-free">Miễn phí</span>
                    </div>
                    <div class="total-row grand">
                        <span><strong>Tổng thanh toán</strong></span>
                        <span class="val" id="valTongCuoi">{{ number_format($tongCuoi, 0, ',', '.') }}₫</span>
                    </div>
                </div>

                <button type="submit" class="btn-place-order" id="btnOrder">
                    <i class="far fa-credit-card"></i> <span>{{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'Đặt Hàng & Thanh Toán VNPAY' : 'Đặt Hàng (COD)' }}</span> <i class="fas fa-arrow-right"></i>
                </button>

                <div class="checkout-trust-badge">
                    <i class="fas fa-lock" style="color:#027A48;"></i> Thông tin thanh toán của bạn được bảo mật tuyệt đối
                </div>
            </div>

        </div>
    </form>

</div>

@endif

{{-- MODAL 1: SỔ ĐỊA CHỈ (SHOPEE STYLE) --}}
<div class="shopee-modal-overlay" id="modalAddressList" style="display:none;" onclick="if(event.target===this) closeAddressListModal()">
    <div class="shopee-modal-box">
        <div class="shopee-modal-header">
            <h3>Địa Chỉ Của Tôi</h3>
            <button type="button" class="btn-modal-close" onclick="closeAddressListModal()">&times;</button>
        </div>
        <div class="shopee-modal-body">
            <div id="addressItemsContainer" class="address-items-list"></div>
            <button type="button" class="btn-shopee-add-address-dashed" onclick="openAddressCreateModal()">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                    <path d="M7 0V14M0 7H14" stroke="currentColor" stroke-width="2"/>
                </svg>
                Thêm Địa Chỉ Mới
            </button>
        </div>
        <div class="shopee-modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeAddressListModal()">Hủy</button>
            <button type="button" class="btn-modal-confirm" onclick="applySelectedAddress()">Xác Nhận</button>
        </div>
    </div>
</div>

{{-- MODAL 2: THÊM / CẬP NHẬT ĐỊA CHỈ (SHOPEE STYLE) --}}
<div class="shopee-modal-overlay" id="modalAddressForm" style="display:none;" onclick="if(event.target===this) closeAddressFormModal()">
    <div class="shopee-modal-box modal-box-lg">
        <div class="shopee-modal-header">
            <h3 id="modalAddressFormTitle">Địa Chỉ Mới</h3>
            <button type="button" class="btn-modal-close" onclick="closeAddressFormModal()">&times;</button>
        </div>
        <form id="formAddressModal" onsubmit="submitAddressForm(event)">
            <input type="hidden" id="form_ma_dia_chi" value="">
            <div class="shopee-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Họ và tên <span>*</span></label>
                        <input type="text" id="modal_ten_nguoi_nhan" required placeholder="Họ và tên người nhận" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại <span>*</span></label>
                        <input type="tel" id="modal_so_dien_thoai" required placeholder="Số điện thoại (10 chữ số)" pattern="[0-9]{10}" maxlength="10">
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Tỉnh/Thành phố</label>
                        <input type="text" value="Hà Nội" readonly style="background:#f9fafb;color:#6b7280;cursor:not-allowed;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Quận/Huyện <span>*</span></label>
                        <select id="modal_quan_huyen" required onchange="onModalQuanChange()">
                            <option value="">Chọn quận/huyện</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Xã/Phường <span>*</span></label>
                        <select id="modal_xa_phuong" required>
                            <option value="">Chọn xã/phường</option>
                        </select>
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Địa chỉ cụ thể <span>*</span></label>
                        <input type="text" id="modal_dia_chi_chi_tiet" required placeholder="Số nhà, tên ngõ, tên đường..." maxlength="255">
                    </div>
                </div>

                <div class="form-check-default">
                    <label>
                        <input type="checkbox" id="modal_la_mac_dinh">
                        <span>Đặt làm địa chỉ mặc định</span>
                    </label>
                </div>
                <div id="modalAddressError" class="modal-error-msg" style="display:none;"></div>
            </div>
            <div class="shopee-modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeAddressFormModal()">Trở Lại</button>
                <button type="submit" class="btn-modal-confirm" id="btnSaveAddress">Hoàn Thành</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Quận/Huyện & Xã/Phường (dùng chung cho cả 2 nhánh) ──────────────────────
const hanoiData = {
    "Ba Đình": ["Phúc Xá","Trúc Bạch","Vĩnh Phúc","Cống Vị","Liễu Giai","Nguyễn Trung Trực","Quán Thánh","Ngọc Hà","Điện Biên","Đội Cấn","Ngọc Khánh","Kim Mã","Giảng Võ","Thành Công"],
    "Hoàn Kiếm": ["Phúc Tân","Đồng Xuân","Hàng Mã","Hàng Buồm","Hàng Đào","Hàng Bồ","Cửa Đông","Lý Thái Tổ","Hàng Bạc","Hàng Gai","Chương Dương","Hàng Trống","Cửa Nam","Hàng Bông","Tràng Tiền","Trần Hưng Đạo","Phan Chu Trinh","Hàng Bài"],
    "Đống Đa": ["Cát Linh","Văn Miếu","Quốc Tử Giám","Láng Thượng","Ô Chợ Dừa","Văn Chương","Hàng Bột","Nam Đồng","Trung Phụng","Trung Liệt","Khâm Thiên","Thịnh Quang","Láng Hạ","Ngã Tư Sở","Phương Liên","Phương Mai","Kim Liên","Trung Tự","Khương Thượng"],
    "Hai Bà Trưng": ["Nguyễn Du","Bùi Thị Xuân","Ngô Thì Nhậm","Lê Đại Hành","Đồng Nhân","Phố Huế","Đống Mác","Thanh Lương","Thanh Nhàn","Cầu Dền","Bách Khoa","Quỳnh Lôi","Bạch Mai","Quỳnh Mai","Vĩnh Tuy","Minh Khai","Trương Định"],
    "Cầu Giấy": ["Nghĩa Đô","Nghĩa Tân","Mai Dịch","Dịch Vọng","Dịch Vọng Hậu","Quan Hoa","Yên Hòa","Trung Hòa"],
    "Thanh Xuân": ["Nhân Chính","Thượng Đình","Khương Trung","Khương Mai","Thanh Xuân Trung","Phương Liệt","Hạ Đình","Thanh Xuân Bắc","Thanh Xuân Nam","Khương Đình","Kim Giang"],
    "Hoàng Mai": ["Thanh Trì","Vĩnh Hưng","Định Công","Mai Động","Tương Mai","Đại Kim","Tân Mai","Hoàng Văn Thụ","Giáp Bát","Lĩnh Nam","Thịnh Liệt","Trần Phú","Hoàng Liệt","Yên Sở"],
    "Long Biên": ["Thượng Thanh","Ngọc Thụy","Giang Biên","Đức Giang","Việt Hưng","Gia Thụy","Ngọc Lâm","Phúc Lợi","Bồ Đề","Sài Đồng","Long Biên","Thạch Bàn","Phúc Đồng","Cự Khối"],
    "Nam Từ Liêm": ["Cầu Diễn","Xuân Phương","Phương Canh","Mỹ Đình 1","Mỹ Đình 2","Tây Mỗ","Mễ Trì","Phú Đô","Đại Mỗ","Trung Văn"],
    "Bắc Từ Liêm": ["Thượng Cát","Liên Mạc","Đông Ngạc","Đức Thắng","Thụy Phương","Tây Tựu","Xuân Đỉnh","Xuân Tảo","Minh Khai","Cổ Nhuế 1","Cổ Nhuế 2","Phú Diễn","Phúc Diễn"],
    "Tây Hồ": ["Phú Thượng","Nhật Tân","Tứ Liên","Quảng An","Xuân La","Yên Phụ","Bưởi","Thụy Khuê"],
    "Hà Đông": ["Nguyễn Trãi","Mỗ Lao","Văn Quán","Vạn Phúc","Yết Kiêu","Quang Trung","La Khê","Phú La","Phúc La","Hà Cầu","Yên Nghĩa","Kiến Hưng","Phú Lương","Dương Nội","Đồng Mai","Biên Giang"],
    "Gia Lâm": ["Yên Viên","Cổ Bi","Đặng Xá","Trâu Quỳ","Dương Xá","Kiêu Kỵ","Ninh Hiệp","Phù Đổng","Lệ Chi","Đông Dư","Bát Tràng","Kim Lan","Văn Đức","Dương Quang","Gia Lâm","Kim Sơn","Phú Thị","Trung Màu","Yên Thường"],
    "Đông Anh": ["Vân Nội","Liên Hà","Việt Hùng","Uy Nỗ","Xuân Nộn","Thụy Lâm","Bắc Hồng","Nguyên Khê","Nam Hồng","Tiên Dương","Vân Hà","Cổ Loa","Hải Bối","Xuân Canh","Võng La","Tầm Xá","Mai Lâm","Đông Hội","Kim Nỗ","Kim Chung","Dục Tú"],
    "Sóc Sơn": ["Sóc Sơn","Bắc Phú","Bắc Sơn","Đông Xuân","Đức Hòa","Hiền Ninh","Hồng Kỳ","Kim Lũ","Minh Phú","Minh Trí","Nam Sơn","Phù Lỗ","Phú Cường","Phú Minh","Quang Tiến","Tân Dân","Tân Hưng","Tiên Dược","Trung Giã","Việt Long","Xuân Giang","Xuân Thu"],
    "Thạch Thất": ["Thạch Thất","Bình Phú","Bình Yên","Cẩm Yên","Canh Nậu","Chàng Sơn","Dị Nậu","Đại Đồng","Hương Ngải","Hữu Bằng","Kim Quan","Lại Thượng","Liên Quan","Phú Kim","Phùng Xá","Tân Xã","Thạch Hòa","Tiến Xuân","Yên Bình","Yên Trung"],
    "Quốc Oai": ["Quốc Oai","Cấn Hữu","Cộng Hòa","Đông Cứu","Đông Yên","Đồng Quang","Hòa Thạch","Liệp Tuyết","Ngọc Liệp","Ngọc Mỹ","Phú Cát","Phú Mãn","Sài Sơn","Tân Hòa","Tân Phú","Thạch Thán","Tuyết Nghĩa","Yên Sơn"],
    "Mê Linh": ["Chi Đông","Chu Phan","Đại Thịnh","Hoàng Kim","Kim Hoa","Liên Mạc","Mê Linh","Tam Đồng","Thạch Đà","Tráng Việt","Tiền Phong","Tự Lập","Văn Khê","Vạn Yên"],
    "Chương Mỹ": ["Chúc Sơn","Đại Yên","Đồng Phú","Đồng Lạc","Hòa Chính","Hoàng Diệu","Hoàng Văn Thụ","Hữu Văn","Lam Điền","Long Sơn","Mỹ Lương","Nam Phương Tiến","Ngọc Hòa","Phú Nam An","Phụng Châu","Quảng Bị","Tốt Động","Thủy Xuân Tiên","Thanh Bình","Trung Hòa","Trường Yên","Xuân Mai"],
};

// ── SỔ ĐỊA CHỈ & THAY ĐỔI / THÊM ĐỊA CHỈ KIỂU SHOPEE ────────────────────────
let addresses = @json($danhSachDiaChi ?? []);
let selectedAddressId = {{ $diaChiHienTai->ma_dia_chi ?? 'null' }};

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openAddressListModal() {
    renderAddressList();
    const modal = document.getElementById('modalAddressList');
    if (modal) modal.style.display = 'flex';
}

function closeAddressListModal() {
    const modal = document.getElementById('modalAddressList');
    if (modal) modal.style.display = 'none';
}

function renderAddressList() {
    const container = document.getElementById('addressItemsContainer');
    if (!container) return;

    if (!addresses || addresses.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:24px; color:#6b7280;">Chưa có địa chỉ nhận hàng nào được lưu.</div>';
        return;
    }

    container.innerHTML = addresses.map(addr => `
        <div class="shopee-address-item ${addr.ma_dia_chi === selectedAddressId ? 'item-selected' : ''}" onclick="selectAddressRadio(${addr.ma_dia_chi})">
            <div class="address-item-radio">
                <input type="radio" name="selected_addr_radio" value="${addr.ma_dia_chi}" ${addr.ma_dia_chi === selectedAddressId ? 'checked' : ''}>
            </div>
            <div class="address-item-info">
                <div class="address-item-top">
                    <strong class="item-name">${escapeHtml(addr.ten_nguoi_nhan)}</strong>
                    <span class="item-sep">|</span>
                    <span class="item-phone">${escapeHtml(addr.so_dien_thoai)}</span>
                    ${addr.la_mac_dinh ? '<span class="badge-shopee-default">Mặc định</span>' : ''}
                </div>
                <div class="address-item-detail">${escapeHtml(addr.dia_chi_day_du)}</div>
            </div>
            <div class="address-item-actions">
                <button type="button" class="btn-item-edit" onclick="event.stopPropagation(); editAddress(${addr.ma_dia_chi})">Cập nhật</button>
            </div>
        </div>
    `).join('');
}

function selectAddressRadio(id) {
    selectedAddressId = id;
    renderAddressList();
}

function applySelectedAddress() {
    const chosen = addresses.find(a => a.ma_dia_chi === selectedAddressId);
    if (!chosen) {
        alert('Vui lòng chọn một địa chỉ.');
        return;
    }
    updateCheckoutAddressUI(chosen);
    closeAddressListModal();
}

function updateCheckoutAddressUI(addr) {
    if (!addr) return;
    const preview = document.getElementById('shopeeAddressPreview');
    if (preview) {
        preview.innerHTML = `
            <div class="shopee-address-details">
                <div class="shopee-user-info">
                    <span id="dispTenNguoiNhan">${escapeHtml(addr.ten_nguoi_nhan)}</span>
                    <span class="shopee-user-phone" id="dispSoDienThoai"><i class="fas fa-phone-alt" style="font-size:12px; color:#EC3D8F;"></i> ${escapeHtml(addr.so_dien_thoai)}</span>
                    <span class="badge-shopee-default" id="dispBadgeDefault" style="${addr.la_mac_dinh ? '' : 'display:none;'}">Mặc định</span>
                </div>
                <div class="shopee-address-text" id="dispDiaChiChiTiet">
                    <i class="fas fa-home" style="font-size:12px; color:#98A2B3; margin-right:4px;"></i> ${escapeHtml(addr.dia_chi_day_du)}
                </div>
            </div>
            <div class="shopee-address-actions">
                <button type="button" class="btn-shopee-change" onclick="openAddressListModal()">Thay Đổi</button>
            </div>
        `;
    }

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val || '';
    };

    setVal('inputTenNguoiNhan', addr.ten_nguoi_nhan);
    setVal('inputSoDienThoai', addr.so_dien_thoai);
    setVal('inputQuanHuyen', addr.quan_huyen);
    setVal('inputXaPhuong', addr.xa_phuong);
    setVal('inputDiaChiChiTiet', addr.dia_chi_chi_tiet);
    setVal('inputMaDiaChi', addr.ma_dia_chi);
}

function initModalHanoiDistricts() {
    const sel = document.getElementById('modal_quan_huyen');
    if (!sel || sel.options.length > 1) return;
    Object.keys(hanoiData).sort().forEach(q => {
        const opt = document.createElement('option');
        opt.value = q;
        opt.textContent = q;
        sel.appendChild(opt);
    });
}

function onModalQuanChange(selectedPhuong = '') {
    const selQuan = document.getElementById('modal_quan_huyen');
    const selPhuong = document.getElementById('modal_xa_phuong');
    if (!selQuan || !selPhuong) return;

    selPhuong.innerHTML = '<option value="">Chọn xã/phường</option>';
    (hanoiData[selQuan.value] || []).forEach(p => {
        const opt = document.createElement('option');
        opt.value = p;
        opt.textContent = p;
        if (p === selectedPhuong) opt.selected = true;
        selPhuong.appendChild(opt);
    });
}

function openAddressCreateModal(editData = null) {
    initModalHanoiDistricts();
    const modal = document.getElementById('modalAddressForm');
    const title = document.getElementById('modalAddressFormTitle');
    const form = document.getElementById('formAddressModal');
    const errDiv = document.getElementById('modalAddressError');
    if (errDiv) errDiv.style.display = 'none';

    if (editData) {
        title.textContent = 'Cập Nhật Địa Chỉ';
        document.getElementById('form_ma_dia_chi').value = editData.ma_dia_chi;
        document.getElementById('modal_ten_nguoi_nhan').value = editData.ten_nguoi_nhan || '';
        document.getElementById('modal_so_dien_thoai').value = editData.so_dien_thoai || '';
        document.getElementById('modal_quan_huyen').value = editData.quan_huyen || '';
        onModalQuanChange(editData.xa_phuong || '');
        document.getElementById('modal_dia_chi_chi_tiet').value = editData.dia_chi_chi_tiet || '';
        document.getElementById('modal_la_mac_dinh').checked = !!editData.la_mac_dinh;
    } else {
        title.textContent = 'Địa Chỉ Mới';
        form.reset();
        document.getElementById('form_ma_dia_chi').value = '';
        document.getElementById('modal_ten_nguoi_nhan').value = "{{ optional($user->khachHang)->ten_khach_hang ?? '' }}";
        document.getElementById('modal_so_dien_thoai').value = "{{ optional($user->khachHang)->so_dien_thoai ?? '' }}";
        document.getElementById('modal_la_mac_dinh').checked = (addresses.length === 0);
        onModalQuanChange('');
    }

    if (modal) modal.style.display = 'flex';
}

function closeAddressFormModal() {
    const modal = document.getElementById('modalAddressForm');
    if (modal) modal.style.display = 'none';
}

function editAddress(id) {
    const item = addresses.find(a => a.ma_dia_chi === id);
    if (!item) return;
    openAddressCreateModal(item);
}

async function submitAddressForm(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveAddress');
    btn.disabled = true;
    btn.textContent = 'Đang lưu...';

    const errDiv = document.getElementById('modalAddressError');
    errDiv.style.display = 'none';

    const id = document.getElementById('form_ma_dia_chi').value;
    const isEdit = !!id;
    const url = isEdit ? `/customer/dia-chi/${id}` : '/customer/dia-chi';
    const method = isEdit ? 'PUT' : 'POST';

    const payload = {
        ten_nguoi_nhan: document.getElementById('modal_ten_nguoi_nhan').value.trim(),
        so_dien_thoai: document.getElementById('modal_so_dien_thoai').value.trim(),
        quan_huyen: document.getElementById('modal_quan_huyen').value,
        xa_phuong: document.getElementById('modal_xa_phuong').value,
        dia_chi_chi_tiet: document.getElementById('modal_dia_chi_chi_tiet').value.trim(),
        la_mac_dinh: document.getElementById('modal_la_mac_dinh').checked,
    };

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) {
            let msg = data.message || 'Đã có lỗi xảy ra.';
            if (data.errors) {
                msg = Object.values(data.errors).flat().join('<br>');
            }
            errDiv.innerHTML = msg;
            errDiv.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Hoàn Thành';
            return;
        }

        addresses = data.danh_sach || [];
        selectedAddressId = data.data.ma_dia_chi;
        updateCheckoutAddressUI(data.data);
        renderAddressList();

        closeAddressFormModal();
        closeAddressListModal();
    } catch (err) {
        errDiv.textContent = 'Lỗi kết nối mạng hoặc hệ thống.';
        errDiv.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Hoàn Thành';
    }
}

// ── Phương thức thanh toán ───────────────────────────────────────────────────
function selectPayment(value, el) {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;

    const vnpayBox = document.getElementById('vnpayInfoBox');
    if (vnpayBox) {
        if (value === 'NGAN_HANG') vnpayBox.classList.add('show');
        else vnpayBox.classList.remove('show');
    }

    updateOrderButtonLabel(value);
}

function updateOrderButtonLabel(value) {
    const btn = document.getElementById('btnOrder');
    if (!btn || btn.disabled) return;
    if (value === 'NGAN_HANG') {
        btn.innerHTML = '<i class="far fa-credit-card"></i> <span>Đặt Hàng & Thanh Toán VNPAY</span> <i class="fas fa-arrow-right"></i>';
    } else {
        btn.innerHTML = '<i class="fas fa-shopping-bag"></i> <span>Đặt Hàng (COD)</span> <i class="fas fa-arrow-right"></i>';
    }
}

@if(!isset($dsBoHoa) || count($dsBoHoa) === 0)
// ── Áp điểm tích lũy (chỉ cho đơn sản phẩm thường) ──────────────────────────
const DIEM_QUY_DOI = 1000;
const tongTienGoc  = {{ collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']) }};
let   diemDangDung = {{ $diemSuDung }};

function formatVND(n) {
    return n.toLocaleString('vi-VN');
}

function renderTotals(diem) {
    const giamGia  = diem * DIEM_QUY_DOI;
    const tongCuoi = Math.max(0, tongTienGoc - giamGia);

    const rowGG = document.getElementById('rowGiamGia');
    if (rowGG) rowGG.style.display = diem > 0 ? 'flex' : 'none';
    const valGG = document.getElementById('valGiamGia');
    if (valGG) valGG.textContent = '−' + formatVND(giamGia) + '₫';
    const lblGG = document.getElementById('lblGiamGia');
    if (lblGG) lblGG.textContent = 'Giảm giá (' + diem.toLocaleString('vi') + ' điểm)';
    const valTC = document.getElementById('valTongCuoi');
    if (valTC) valTC.textContent = formatVND(tongCuoi) + '₫';
}

function apDiem() {
    const input    = document.getElementById('diemNhap');
    const msg      = document.getElementById('pointsMsg');
    const diemMax  = parseInt(input.max) || 0;
    let   diem     = parseInt(input.value) || 0;

    if (diem < 0) diem = 0;
    if (diem > diemMax) {
        msg.textContent = `Bạn chỉ có ${diemMax.toLocaleString('vi')} điểm.`;
        msg.className   = 'points-msg err';
        return;
    }

    fetch("{{ route('customer.gio-hang.apply-points') }}", {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
                         || '{{ csrf_token() }}'
        },
        body: JSON.stringify({ diem })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            diemDangDung = data.diem_su_dung;
            renderTotals(diemDangDung);
            input.value     = diemDangDung > 0 ? diemDangDung : '';
            msg.textContent = diemDangDung > 0
                ? `✓ Đã áp dụng ${diemDangDung.toLocaleString('vi')} điểm – giảm ${formatVND(data.giam_gia)}₫`
                : '✓ Đã bỏ áp dụng điểm.';
            msg.className   = 'points-msg ok';
        } else {
            msg.textContent = data.message || 'Có lỗi xảy ra.';
            msg.className   = 'points-msg err';
        }
    })
    .catch(() => {
        msg.textContent = 'Không thể kết nối. Thử lại sau.';
        msg.className   = 'points-msg err';
    });
}

renderTotals(diemDangDung);
@endif

// ── Prevent double submit ────────────────────────────────────────────────────
const checkoutForm = document.getElementById('checkoutForm');
if (checkoutForm) {
    checkoutForm.addEventListener('submit', function() {
        const btn = document.getElementById('btnOrder');
        if (btn) {
            btn.disabled    = true;
            btn.textContent = '⏳ Đang xử lý...';
        }
    });
}
// ── Áp điểm cho nhánh bó hoa (nhiều bó) ─────────────────────────────────
@if(isset($dsBoHoa) && count($dsBoHoa) > 0)
const DIEM_QUY_DOI_BH = 1000;
const tongGocBH       = {{ collect($dsBoHoa)->sum(fn($b) => (float) $b['quote']['tong_tien']) }};
let   diemDangDungBH  = {{ $diemSuDung }};

function renderTotalsBH(diem) {
    const giamGia  = diem * DIEM_QUY_DOI_BH;
    const tongCuoi = Math.max(0, tongGocBH - giamGia);

    const rowGG = document.getElementById('rowGiamGia');
    if (rowGG) rowGG.style.display = diem > 0 ? 'flex' : 'none';
    const valGG = document.getElementById('valGiamGia');
    if (valGG) valGG.textContent = '−' + giamGia.toLocaleString('vi-VN') + '₫';
    const lblGG = document.getElementById('lblGiamGia');
    if (lblGG) lblGG.textContent = 'Giảm giá (' + diem.toLocaleString('vi-VN') + ' điểm)';
    const valTC = document.getElementById('valTongCuoi');
    if (valTC) valTC.textContent = tongCuoi.toLocaleString('vi-VN') + '₫';
}

function apDiemBoHoa() {
    const input   = document.getElementById('diemNhap');
    const msg     = document.getElementById('pointsMsg');
    const diemMax = parseInt(input.max) || 0;
    let   diem    = parseInt(input.value) || 0;

    if (diem < 0) diem = 0;
    if (diem > diemMax) {
        msg.textContent = `Bạn chỉ có ${diemMax.toLocaleString('vi-VN')} điểm.`;
        msg.className   = 'points-msg err';
        return;
    }

    fetch("{{ route('customer.gio-hang.apply-points') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
        },
        body: JSON.stringify({ diem })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            diemDangDungBH = data.diem_su_dung;
            renderTotalsBH(diemDangDungBH);
            input.value     = diemDangDungBH > 0 ? diemDangDungBH : '';
            msg.textContent = diemDangDungBH > 0
                ? `✓ Đã áp dụng ${diemDangDungBH.toLocaleString('vi-VN')} điểm – giảm ${data.giam_gia.toLocaleString('vi-VN')}₫`
                : '✓ Đã bỏ áp dụng điểm.';
            msg.className   = 'points-msg ok';
        } else {
            msg.textContent = data.message || 'Có lỗi xảy ra.';
            msg.className   = 'points-msg err';
        }
    })
    .catch(() => {
        msg.textContent = 'Không thể kết nối. Thử lại sau.';
        msg.className   = 'points-msg err';
    });
}

renderTotalsBH(diemDangDungBH);

function xoaBoHoaTrongThanhToan(key) {
    if (!confirm('Xóa bó hoa này khỏi giỏ?')) return;
    fetch('{{ route("customer.bo-hoa.gio.remove") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ key })
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); })
    .catch(() => alert('Có lỗi xảy ra.'));
}
@endif
</script>
@endsection