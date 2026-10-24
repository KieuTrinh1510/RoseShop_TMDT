@extends('customer.layouts.customer')

@section('title', '💳 Thanh Toán – Hoa Tươi Shop')
@section('page-id', 'thanh-toan')

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/ThanhToan.css') }}">
@endsection

@section('content')
<div class="checkout-page">

    {{-- PROGRESS STEPS HEADER --}}
    <div class="checkout-stepper-wrap">
        <div class="stepper-item step-completed">
            <span class="step-circle">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </span>
            <span class="step-title">Giỏ hàng</span>
        </div>
        <div class="stepper-line completed"></div>
        <div class="stepper-item step-current">
            <span class="step-circle">2</span>
            <span class="step-title">Thanh toán</span>
        </div>
        <div class="stepper-line"></div>
        <div class="stepper-item step-pending">
            <span class="step-circle">3</span>
            <span class="step-title">Hoàn tất</span>
        </div>
    </div>

    {{-- NÚT QUAY LẠI GIỎ HÀNG (Ở GÓC TRÊN TRÁI THEO ĐÚNG MŨI TÊN BẠN VẼ) --}}
    <div class="top-back-wrap">
        <a href="{{ route('customer.gio-hang') }}" class="btn-top-back-cart">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>Quay lại giỏ hàng</span>
        </a>
    </div>

    @if(session('error'))
        <div class="alert-checkout error">❌ {{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('customer.thanh-toan.store') }}" id="checkoutForm">
        @csrf
        <input type="hidden" name="loai_checkout" value="{{ $loaiCheckout ?? (isset($dsBoHoa) && count($dsBoHoa) > 0 ? 'bo_hoa' : 'san_pham') }}">

        <div class="checkout-layout">

            {{-- CỘT TRÁI: THÔNG TIN NHẬN HÀNG & PHƯƠNG THỨC THANH TOÁN --}}
            <div class="checkout-left-col">

                {{-- BOX 1: THÔNG TIN NHẬN HÀNG (GIỮ NGUYÊN SỔ ĐỊA CHỈ NHƯ CŨ) --}}
                <div class="checkout-card address-box-card">
                    <div class="card-title-row">
                        <div class="card-title">
                            <span class="title-icon-pin">📍</span>
                            <span>Thông tin nhận hàng</span>
                        </div>
                    </div>

                    <div id="shopeeAddressPreview" class="shopee-address-preview-wrap">
                        @if(isset($diaChiHienTai) && $diaChiHienTai)
                            <div class="address-preview-details">
                                <div class="address-user-line">
                                    <strong id="dispTenNguoiNhan">{{ $diaChiHienTai->ten_nguoi_nhan }}</strong>
                                    <span class="phone-sep">|</span>
                                    <span id="dispSoDienThoai">{{ $diaChiHienTai->so_dien_thoai }}</span>
                                    <span class="badge-addr-default" id="dispBadgeDefault" style="{{ $diaChiHienTai->la_mac_dinh ? '' : 'display:none;' }}">Mặc định</span>
                                </div>
                                <div class="address-text-line" id="dispDiaChiChiTiet">
                                    {{ $diaChiHienTai->dia_chi_day_du }}
                                </div>
                            </div>
                            <div class="address-action-right">
                                <button type="button" class="btn-change-addr" onclick="openAddressListModal()">Thay Đổi</button>
                            </div>
                        @else
                            <div class="address-empty-alert">
                                <span>Bạn chưa có địa chỉ nhận hàng. Vui lòng thêm địa chỉ để tiếp tục đặt hàng.</span>
                            </div>
                            <div class="address-action-right">
                                <button type="button" class="btn-change-addr" onclick="openAddressCreateModal()">+ Thêm Địa Chỉ Mới</button>
                            </div>
                        @endif
                    </div>

                    {{-- Hidden inputs lưu thông tin gửi form --}}
                    <input type="hidden" name="ten_nguoi_nhan" id="inputTenNguoiNhan" value="{{ old('ten_nguoi_nhan', $diaChiHienTai->ten_nguoi_nhan ?? optional($user->khachHang)->ten_khach_hang) }}">
                    <input type="hidden" name="so_dien_thoai" id="inputSoDienThoai" value="{{ old('so_dien_thoai', $diaChiHienTai->so_dien_thoai ?? optional($user->khachHang)->so_dien_thoai) }}">
                    <input type="hidden" name="quan_huyen" id="inputQuanHuyen" value="{{ old('quan_huyen', $diaChiHienTai->quan_huyen ?? 'Cầu Giấy') }}">
                    <input type="hidden" name="xa_phuong" id="inputXaPhuong" value="{{ old('xa_phuong', $diaChiHienTai->xa_phuong ?? 'Dịch Vọng Hậu') }}">
                    <input type="hidden" name="dia_chi_chi_tiet" id="inputDiaChiChiTiet" value="{{ old('dia_chi_chi_tiet', $diaChiHienTai->dia_chi_chi_tiet ?? '') }}">
                    <input type="hidden" name="ma_dia_chi" id="inputMaDiaChi" value="{{ $diaChiHienTai->ma_dia_chi ?? '' }}">
                </div>

                {{-- BOX 2: PHƯƠNG THỨC THANH TOÁN (VNPAY & COD CHUẨN DESIGN) --}}
                <div class="checkout-card payment-box-card">
                    <div class="card-title-row">
                        <div class="card-title">
                            <span class="title-icon">💳</span>
                            <span>Phương thức thanh toán</span>
                        </div>
                    </div>
                    @error('phuong_thuc_thanh_toan')
                        <div class="error-msg-banner">{{ $message }}</div>
                    @enderror

                    <div class="payment-options-list">
                        {{-- OPTION 1: VNPAY --}}
                        <div class="payment-card-option {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'selected' : '' }}"
                             onclick="selectPayment('NGAN_HANG', this)">
                            <div class="option-radio-col">
                                <input type="radio" name="phuong_thuc_thanh_toan" value="NGAN_HANG" id="pay_vnpay"
                                       class="custom-radio"
                                       {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'checked' : '' }}>
                            </div>
                            <div class="option-icon-col">
                                <div class="icon-payment-box">💳</div>
                            </div>
                            <div class="option-body-col">
                                <div class="payment-title-row">
                                    <strong>Thanh toán qua VNPay</strong>
                                </div>
                                <div class="payment-desc-text">
                                    Thanh toán an toàn qua thẻ nội địa, thẻ quốc tế, ví điện tử...
                                </div>
                            </div>
                            <div class="option-brand-col">
                                <span class="vnpay-logo-badge">VNPAY</span>
                            </div>
                        </div>

                        {{-- HỘP THÔNG TIN BẢO MẬT VNPAY (HIỆN KHI CHỌN VNPAY) --}}
                        <div class="vnpay-info-detail {{ old('phuong_thuc_thanh_toan', 'NGAN_HANG') === 'NGAN_HANG' ? 'show' : '' }}" id="vnpayInfoBox">
                            <p>🔒 <strong>Cổng thanh toán:</strong> VNPay bảo mật SSL 256-bit (Hỗ trợ Vietcombank, Techcombank, MB, BIDV, Visa, QR Code...).</p>
                            <p>⏱️ Bạn có 15 phút để hoàn tất giao dịch trên trang VNPay sau khi bấm Đặt Hàng.</p>
                        </div>

                        {{-- OPTION 2: COD --}}
                        <div class="payment-card-option {{ old('phuong_thuc_thanh_toan') === 'COD' ? 'selected' : '' }}"
                             onclick="selectPayment('COD', this)">
                            <div class="option-radio-col">
                                <input type="radio" name="phuong_thuc_thanh_toan" value="COD" id="pay_cod"
                                       class="custom-radio"
                                       {{ old('phuong_thuc_thanh_toan') === 'COD' ? 'checked' : '' }}>
                            </div>
                            <div class="option-icon-col">
                                <div class="icon-payment-box">💵</div>
                            </div>
                            <div class="option-body-col">
                                <div class="payment-title-row">
                                    <strong>Thanh toán khi nhận hàng (COD)</strong>
                                </div>
                                <div class="payment-desc-text">
                                    Thanh toán bằng tiền mặt trực tiếp cho shipper khi nhận hàng
                                </div>
                            </div>
                            <div class="option-brand-col">
                                <span class="cash-icon-badge">💵</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- CỘT PHẢI: ĐƠN HÀNG CỦA BẠN & ĐIỂM TÍCH LŨY & TỔNG THANH TOÁN --}}
            <div class="checkout-right-col">
                <div class="checkout-card order-summary-card">

                    {{-- TIÊU ĐỀ + LINK CHỈNH SỬA GIỎ HÀNG --}}
                    <div class="order-card-header">
                        <div class="order-header-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                            <span>Đơn hàng của bạn</span>
                        </div>
                        <a href="{{ route('customer.gio-hang') }}" class="link-edit-cart">Chỉnh sửa giỏ hàng</a>
                    </div>

                    {{-- DANH SÁCH MÓN HÀNG --}}
                    <div class="order-items-scroll">

                        {{-- NHÁNH BÓ HOA --}}
                        @if(isset($dsBoHoa) && count($dsBoHoa) > 0)
                            @php
                                $tongTienGoc = 0;
                                foreach ($dsBoHoa as $bo) $tongTienGoc += (float) $bo['quote']['tong_tien'];
                            @endphp
                            @foreach($dsBoHoa as $idx => $bo)
                            <div class="order-item-row">
                                <img src="{{ !empty($bo['mau']) ? \App\Http\Controllers\Admin\BoHoaController::anhMau($bo['mau']) : asset('img/placeholder-flower.svg') }}"
                                     alt="{{ $bo['ten'] }}" class="order-item-thumb"
                                     onerror="this.src='{{ asset('img/placeholder-flower.svg') }}';">
                                <div class="order-item-info">
                                    <div class="order-item-title">{{ $bo['ten'] }}</div>
                                    <div class="order-item-sub">
                                        {{ $bo['so_luong'] }} bó · Size {{ $bo['mau']->size ?? 'Tiêu chuẩn' }}
                                        @if(!empty($bo['thiep']))
                                            <span class="order-thiep-tag">· Thiệp: {{ $bo['thiep']->ten_san_pham }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="order-item-qty">x{{ $bo['so_luong'] }}</div>
                                <div class="order-item-amount">{{ number_format($bo['quote']['tong_tien'], 0, ',', '.') }}₫</div>
                            </div>
                            @endforeach

                        {{-- NHÁNH SẢN PHẨM ĐƠN LẺ --}}
                        @elseif(isset($gioHang) && count($gioHang) > 0)
                            @php
                                $tongTienGoc = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
                            @endphp
                            @foreach($gioHang as $key => $item)
                            <div class="order-item-row">
                                @if(!empty($item['hinh_anh']))
                                    <img src="{{ asset($item['hinh_anh']) }}" alt="{{ $item['ten_san_pham'] }}" class="order-item-thumb"
                                         onerror="this.src='{{ asset('img/placeholder-flower.svg') }}';">
                                @else
                                    <img src="{{ asset('img/placeholder-flower.svg') }}" alt="{{ $item['ten_san_pham'] }}" class="order-item-thumb">
                                @endif
                                <div class="order-item-info">
                                    <div class="order-item-title">{{ $item['ten_san_pham'] }}</div>
                                    <div class="order-item-sub">Hoa tươi tuyển chọn</div>
                                </div>
                                <div class="order-item-qty">x{{ $item['so_luong'] }}</div>
                                <div class="order-item-amount">{{ number_format($item['gia_ban'] * $item['so_luong'], 0, ',', '.') }}₫</div>
                            </div>
                            @endforeach
                        @else
                            @php $tongTienGoc = 0; @endphp
                        @endif

                    </div>

                    {{-- KHỐI ĐIỂM TÍCH LŨY (NHƯ GHI CHÚ MÀU VÀNG CỦA BẠN: "NÀY ĐỂ LÀ ĐIỂM TÍCH LŨY") --}}
                    @auth
                    @php $diemHienCo = optional($user->khachHang)->diem_tich_luy ?? 0; @endphp
                    <div class="checkout-points-box">
                        <div class="points-header-line">
                            <span class="icon">🏷️</span>
                            <strong>Điểm tích lũy</strong>
                            <span class="points-avail-note">(Bạn có {{ number_format($diemHienCo) }} điểm)</span>
                        </div>
                        <div class="points-input-inline">
                            <input type="number" id="diemNhap" min="0" max="{{ $diemHienCo }}"
                                   value="{{ $diemSuDung > 0 ? $diemSuDung : '' }}"
                                   placeholder="Nhập số điểm muốn dùng"
                                   class="points-input-field">
                            <button type="button" class="btn-points-submit"
                                    onclick="{{ isset($dsBoHoa) && count($dsBoHoa) > 0 ? 'apDiemBoHoa()' : 'apDiem()' }}">
                                Áp dụng
                            </button>
                        </div>
                        <div id="pointsMsg" class="points-msg-alert"></div>
                    </div>
                    @endauth

                    {{-- CÁC DÒNG TỔNG KẾT TIỀN --}}
                    @php
                        $giamGia  = $diemSuDung * 1000;
                        $tongCuoi = max(0, $tongTienGoc - $giamGia);
                    @endphp
                    <div class="order-pricing-summary">
                        <div class="price-summary-line">
                            <span class="txt-lbl">Tạm tính</span>
                            <span class="txt-val">{{ number_format($tongTienGoc, 0, ',', '.') }}₫</span>
                        </div>
                        <div class="price-summary-line">
                            <span class="txt-lbl">Phí vận chuyển</span>
                            <span class="txt-val ship-tag-free">Miễn phí</span>
                        </div>
                        <div class="price-summary-line" id="rowGiamGia" style="{{ $diemSuDung > 0 ? '' : 'display:none;' }}">
                            <span class="txt-lbl" id="lblGiamGia">Giảm giá ({{ number_format($diemSuDung) }} điểm)</span>
                            <span class="txt-val discount-number" id="valGiamGia">−{{ number_format($giamGia, 0, ',', '.') }}₫</span>
                        </div>

                        <div class="summary-line-divider"></div>

                        <div class="price-summary-line total-grand-line">
                            <span class="total-grand-lbl">Tổng thanh toán</span>
                            <span class="total-grand-val" id="valTongCuoi">{{ number_format($tongCuoi, 0, ',', '.') }}₫</span>
                        </div>
                    </div>

                    {{-- NÚT ĐẶT HÀNG CHÍNH --}}
                    <button type="submit" class="btn-submit-order" id="btnOrder">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Đặt hàng</span>
                    </button>

                    <div class="order-terms-notice">
                        Bằng việc đặt hàng, bạn đồng ý với <a href="javascript:void(0)">Điều khoản mua hàng</a> của RoseShop
                    </div>

                </div>
            </div>

        </div>
    </form>

</div>

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
// ── Quận/Huyện & Xã/Phường Hà Nội ──────────────────────────────────────────
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
            <div class="address-preview-details">
                <div class="address-user-line">
                    <strong id="dispTenNguoiNhan">${escapeHtml(addr.ten_nguoi_nhan)}</strong>
                    <span class="phone-sep">|</span>
                    <span id="dispSoDienThoai">${escapeHtml(addr.so_dien_thoai)}</span>
                    <span class="badge-addr-default" id="dispBadgeDefault" style="${addr.la_mac_dinh ? '' : 'display:none;'}">Mặc định</span>
                </div>
                <div class="address-text-line" id="dispDiaChiChiTiet">
                    ${escapeHtml(addr.dia_chi_day_du)}
                </div>
            </div>
            <div class="address-action-right">
                <button type="button" class="btn-change-addr" onclick="openAddressListModal()">Thay Đổi</button>
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
    document.querySelectorAll('.payment-card-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;

    const vnpayBox = document.getElementById('vnpayInfoBox');
    if (vnpayBox) {
        if (value === 'NGAN_HANG') vnpayBox.classList.add('show');
        else vnpayBox.classList.remove('show');
    }
}

// ── Áp điểm tích lũy ──────────────────────────────────────────────────────────
const DIEM_QUY_DOI = 1000;
const tongTienGoc  = {{ $tongTienGoc ?? 0 }};
let   diemDangDung = {{ $diemSuDung ?? 0 }};

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
        msg.className   = 'points-msg-alert err';
        return;
    }

    fetch("{{ route('customer.gio-hang.apply-points') }}", {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}'
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
                ? `✓ Đã áp dụng ${diemDangDung.toLocaleString('vi')} điểm (−${formatVND(data.giam_gia)}₫)`
                : '✓ Đã bỏ áp dụng điểm.';
            msg.className   = 'points-msg-alert ok';
        } else {
            msg.textContent = data.message || 'Có lỗi xảy ra.';
            msg.className   = 'points-msg-alert err';
        }
    })
    .catch(() => {
        msg.textContent = 'Không thể kết nối. Thử lại sau.';
        msg.className   = 'points-msg-alert err';
    });
}

function apDiemBoHoa() {
    apDiem();
}

// ── Submit form bảo vệ chống double click ────────────────────────────────────
const checkoutForm = document.getElementById('checkoutForm');
if (checkoutForm) {
    checkoutForm.addEventListener('submit', function() {
        const btn = document.getElementById('btnOrder');
        if (btn) {
            btn.disabled    = true;
            btn.innerHTML = '<span>⏳ Đang xử lý...</span>';
        }
    });
}
</script>
@endsection