@extends('customer.layouts.customer')

@section('title', '🌸 Hồ Sơ Cá Nhân - RoseShop')

@section('head-styles')
    <link rel="stylesheet" href="{{ asset('css/Customer/Profile.css') }}?v={{ file_exists(public_path('css/Customer/Profile.css')) ? filemtime(public_path('css/Customer/Profile.css')) : time() }}">
@endsection

@section('content')
<div class="profile-page-wrapper">
    {{-- Breadcrumb --}}
    <div class="profile-breadcrumb">
        <a href="{{ route('customer.dashboard') }}">🏠 Trang chủ</a>
        <span class="sep">›</span>
        <span>Tài khoản</span>
        <span class="sep">›</span>
        <span class="current" id="profileBreadcrumbCurrent">
            {{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? 'Đổi mật khẩu' : 'Hồ sơ cá nhân' }}
        </span>
    </div>

    {{-- Main Centered Container --}}
    <div class="profile-container">
        {{-- Flash messages --}}
        @if(session('success_info'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success_info') }}</span>
            </div>
        @endif
        @if(session('success_password'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success_password') }}</span>
            </div>
        @endif

        {{-- Top Card Header --}}
        <div class="profile-card-header">
            <div class="profile-header-icon" id="headerIcon">
                <i class="{{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? 'fas fa-lock' : 'fas fa-envelope-open-text' }}"></i>
            </div>
            <div class="profile-header-text">
                <h1 class="profile-header-title" id="headerTitle">
                    {{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? 'Đổi mật khẩu' : 'Hồ sơ cá nhân' }}
                </h1>
                <p class="profile-header-sub" id="headerSub">
                    {{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? 'Cập nhật mật khẩu để bảo vệ tài khoản của bạn tốt hơn' : 'Quản lý thông tin tài khoản của bạn' }}
                </p>
            </div>
        </div>

        {{-- Switchable Tabs --}}
        <div class="profile-tabs">
            <button type="button"
                    class="profile-tab-btn {{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? '' : 'active' }}"
                    id="tab-info-btn"
                    onclick="switchProfileTab('info')">
                Thông tin cá nhân
            </button>
            <button type="button"
                    class="profile-tab-btn {{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? 'active' : '' }}"
                    id="tab-password-btn"
                    onclick="switchProfileTab('password')">
                <i class="fas fa-key"></i> Đổi mật khẩu
            </button>
        </div>

        {{-- ═════════════════════════════════════════════════════════════
             TAB 1: THÔNG TIN CÁ NHÂN
        ═════════════════════════════════════════════════════════════ --}}
        <div class="profile-pane {{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? '' : 'active' }}" id="pane-info">
            {{-- Validation Errors --}}
            @if($errors->has('ten_khach_hang') || $errors->has('so_dien_thoai') || $errors->has('email') || $errors->has('quan_huyen') || $errors->has('xa_phuong') || $errors->has('dia_chi_chi_tiet'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin:0;padding-left:1.2rem;">
                        @foreach(['ten_khach_hang','so_dien_thoai','email','quan_huyen','xa_phuong','dia_chi_chi_tiet'] as $field)
                            @if($errors->has($field))
                                <li>{{ $errors->first($field) }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('customer.profile.update') }}" id="formProfileInfo">
                @csrf
                @method('PATCH')

                @php
                    $diaChiHienTai = $khachHang->dia_chi ?? '';
                    $parts         = array_map('trim', explode(',', $diaChiHienTai));
                    if (count($parts) >= 1 && strtolower(end($parts)) === 'hà nội') array_pop($parts);
                    $savedQuan     = old('quan_huyen',      count($parts) >= 2 ? $parts[count($parts)-1] : '');
                    $savedPhuong   = old('xa_phuong',       count($parts) >= 3 ? $parts[count($parts)-2] : '');
                    $savedChiTiet  = old('dia_chi_chi_tiet', count($parts) >= 1 ? implode(', ', array_slice($parts, 0, count($parts)-2)) : $diaChiHienTai);
                @endphp

                {{-- SECTION 1: THÔNG TIN CƠ BẢN --}}
                <div class="profile-section-heading">
                    <i class="fas fa-user"></i> <span>Thông tin cơ bản</span>
                </div>

                <div class="profile-form-grid">
                    {{-- Tên đăng nhập --}}
                    <div class="profile-field">
                        <label>Tên đăng nhập</label>
                        <div class="profile-input-wrap">
                            <input type="text" value="{{ $user->ten_dang_nhap }}" class="readonly-field" readonly>
                        </div>
                    </div>

                    {{-- Họ tên --}}
                    <div class="profile-field">
                        <label>Họ tên *</label>
                        <div class="profile-input-wrap">
                            <input type="text" name="ten_khach_hang"
                                   value="{{ old('ten_khach_hang', $khachHang->ten_khach_hang) }}"
                                   placeholder="Nguyễn Thị Thanh"
                                   class="{{ $errors->has('ten_khach_hang') ? 'is-invalid' : '' }}"
                                   required>
                        </div>
                        @error('ten_khach_hang')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>

                    {{-- Số điện thoại --}}
                    <div class="profile-field">
                        <label>Số điện thoại *</label>
                        <div class="profile-input-wrap has-left-icon">
                            <i class="fas fa-phone-alt input-left-icon"></i>
                            <input type="text" name="so_dien_thoai"
                                   value="{{ old('so_dien_thoai', $khachHang->so_dien_thoai) }}"
                                   maxlength="10"
                                   oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                   placeholder="0918277896"
                                   class="{{ $errors->has('so_dien_thoai') ? 'is-invalid' : '' }}"
                                   required>
                        </div>
                        @error('so_dien_thoai')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="profile-field">
                        <label>Email *</label>
                        <div class="profile-input-wrap has-left-icon">
                            <i class="fas fa-envelope input-left-icon"></i>
                            <input type="email" name="email"
                                   value="{{ old('email', $khachHang->email) }}"
                                   placeholder="thanh@gmail.com"
                                   class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   required>
                        </div>
                        @error('email')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- SECTION 2: ĐỊA CHỈ GIAO HÀNG --}}
                <div class="profile-section-heading" style="margin-top: 24px;">
                    <i class="fas fa-map-marker-alt"></i> <span>Địa chỉ giao hàng</span>
                </div>

                <div class="profile-form-grid">
                    {{-- Tỉnh / Thành phố --}}
                    <div class="profile-field">
                        <label>Tỉnh / Thành phố *</label>
                        <div class="profile-input-wrap">
                            <select disabled class="profile-select readonly-select">
                                <option selected>Hà Nội</option>
                            </select>
                        </div>
                    </div>

                    {{-- Quận / Huyện --}}
                    <div class="profile-field">
                        <label>Quận / Huyện *</label>
                        <div class="profile-input-wrap">
                            <select id="sel_quan_huyen" name="quan_huyen"
                                    class="profile-select {{ $errors->has('quan_huyen') ? 'is-invalid' : '' }}"
                                    required>
                                <option value="">Chọn quận/huyện</option>
                            </select>
                        </div>
                        @error('quan_huyen')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>

                    {{-- Xã / Phường --}}
                    <div class="profile-field">
                        <label>Xã / Phường *</label>
                        <div class="profile-input-wrap">
                            <select id="sel_xa_phuong" name="xa_phuong"
                                    class="profile-select {{ $errors->has('xa_phuong') ? 'is-invalid' : '' }}"
                                    required>
                                <option value="">Chọn xã/phường</option>
                            </select>
                        </div>
                        @error('xa_phuong')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>

                    {{-- Địa chỉ chi tiết --}}
                    <div class="profile-field full-width">
                        <label>Địa chỉ chi tiết *</label>
                        <div class="profile-input-wrap has-left-icon">
                            <i class="fas fa-home input-left-icon"></i>
                            <input type="text" name="dia_chi_chi_tiet"
                                   value="{{ $savedChiTiet }}"
                                   placeholder="Số 18, ngõ 100"
                                   class="{{ $errors->has('dia_chi_chi_tiet') ? 'is-invalid' : '' }}"
                                   required>
                        </div>
                        @error('dia_chi_chi_tiet')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- SECTION 3: HIGHLIGHT CARDS --}}
                <div class="profile-summary-cards">
                    {{-- Points Card --}}
                    <div class="profile-summary-card points-card">
                        <div class="summary-card-tag">* Điểm tích lũy</div>
                        <div class="summary-card-body">
                            <div class="summary-card-icon points-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="summary-card-val">
                                <span class="val-num">{{ number_format($khachHang->diem_tich_luy) }}</span>
                                <span class="val-unit">điểm</span>
                            </div>
                        </div>
                    </div>

                    {{-- Account Type Card --}}
                    <div class="profile-summary-card role-card">
                        <div class="summary-card-tag">* Loại tài khoản</div>
                        <div class="summary-card-body">
                            <div class="summary-card-icon role-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="summary-card-info">
                                <div class="role-title">Khách hàng</div>
                                <div class="role-sub">Thành viên RoseShop</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="profile-actions">
                    <button type="submit" class="profile-btn-save">
                        <i class="fas fa-save"></i> Lưu thông tin
                    </button>
                    <button type="button" class="profile-btn-cancel" onclick="resetInfoForm()">
                        <i class="fas fa-undo"></i> Hủy bỏ
                    </button>
                </div>
            </form>
        </div>

        {{-- ═════════════════════════════════════════════════════════════
             TAB 2: ĐỔI MẬT KHẨU
        ═════════════════════════════════════════════════════════════ --}}
        <div class="profile-pane {{ ($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi') || session('tab') === 'password') ? 'active' : '' }}" id="pane-password">
            {{-- Validation Errors --}}
            @if($errors->has('mat_khau_cu') || $errors->has('mat_khau_moi'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin:0;padding-left:1.2rem;">
                        @foreach(['mat_khau_cu','mat_khau_moi'] as $field)
                            @if($errors->has($field))
                                <li>{{ $errors->first($field) }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Security Notice Banner --}}
            <div class="security-notice-card">
                <div class="security-notice-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="security-notice-text">
                    <div class="security-notice-title">Lưu ý bảo mật</div>
                    <div class="security-notice-desc">Vui lòng sử dụng mật khẩu mạnh để bảo vệ tài khoản của bạn.</div>
                </div>
            </div>

            <form method="POST" action="{{ route('customer.profile.password') }}" id="formProfilePassword">
                @csrf
                @method('PATCH')

                <div class="password-form-wrap">
                    {{-- Mật khẩu hiện tại --}}
                    <div class="profile-field">
                        <label>Mật khẩu hiện tại *</label>
                        <div class="profile-input-wrap has-left-icon has-right-btn">
                            <i class="fas fa-lock input-left-icon"></i>
                            <input type="password" name="mat_khau_cu" id="mat_khau_cu"
                                   autocomplete="current-password"
                                   placeholder="Nhập mật khẩu hiện tại"
                                   class="{{ $errors->has('mat_khau_cu') ? 'is-invalid' : '' }}"
                                   required>
                            <button type="button" class="pwd-eye-btn" onclick="togglePwd('mat_khau_cu', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('mat_khau_cu')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>

                    {{-- Mật khẩu mới --}}
                    <div class="profile-field">
                        <label>Mật khẩu mới * <span>(ít nhất 6 ký tự)</span></label>
                        <div class="profile-input-wrap has-left-icon has-right-btn">
                            <i class="fas fa-lock input-left-icon"></i>
                            <input type="password" name="mat_khau_moi" id="mat_khau_moi"
                                   autocomplete="new-password"
                                   placeholder="Tối thiểu 6 ký tự"
                                   minlength="6"
                                   oninput="checkPasswordCriteria(this.value)"
                                   class="{{ $errors->has('mat_khau_moi') ? 'is-invalid' : '' }}"
                                   required>
                            <button type="button" class="pwd-eye-btn" onclick="togglePwd('mat_khau_moi', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('mat_khau_moi')<div class="invalid-msg">{{ $message }}</div>@enderror
                    </div>

                    {{-- Checklist criteria --}}
                    <div class="password-checklist-box">
                        <div class="checklist-heading">* Mật khẩu nên bao gồm:</div>
                        <div class="checklist-item" id="chkLen">
                            <i class="fas fa-check-circle"></i> <span>Ít nhất 6 ký tự</span>
                        </div>
                        <div class="checklist-item" id="chkCase">
                            <i class="fas fa-check-circle"></i> <span>Kết hợp chữ hoa, chữ thường</span>
                        </div>
                        <div class="checklist-item" id="chkSpecial">
                            <i class="fas fa-check-circle"></i> <span>Số và ký tự đặc biệt (nếu có)</span>
                        </div>
                    </div>

                    {{-- Xác nhận mật khẩu mới --}}
                    <div class="profile-field">
                        <label>Xác nhận mật khẩu mới *</label>
                        <div class="profile-input-wrap has-left-icon has-right-btn">
                            <i class="fas fa-lock input-left-icon"></i>
                            <input type="password" name="mat_khau_moi_confirmation" id="mat_khau_moi_confirmation"
                                   autocomplete="new-password"
                                   placeholder="Nhập lại mật khẩu mới"
                                   minlength="6"
                                   oninput="checkMatchPassword()"
                                   required>
                            <button type="button" class="pwd-eye-btn" onclick="togglePwd('mat_khau_moi_confirmation', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="matchPasswordLabel" class="match-password-label"></div>
                    </div>

                    <div class="password-action-wrap">
                        <button type="submit" class="profile-btn-pwd">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ── Dữ liệu Quận/Huyện Hà Nội ────────────────────────────────────────────────
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

const selQuan   = document.getElementById('sel_quan_huyen');
const selPhuong = document.getElementById('sel_xa_phuong');

if (selQuan && selPhuong) {
    Object.keys(hanoiData).sort().forEach(q => {
        const opt = document.createElement('option');
        opt.value = q;
        opt.textContent = q;
        selQuan.appendChild(opt);
    });

    function updatePhuong(selectedPhuong) {
        selPhuong.innerHTML = '<option value="">Chọn xã/phường</option>';
        const wards = hanoiData[selQuan.value] || [];
        wards.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p;
            opt.textContent = p;
            if (p === selectedPhuong) opt.selected = true;
            selPhuong.appendChild(opt);
        });
    }

    selQuan.addEventListener('change', () => updatePhuong(''));

    const savedQuan   = "{{ $savedQuan }}";
    const savedPhuong = "{{ $savedPhuong }}";
    if (savedQuan) {
        selQuan.value = savedQuan;
        updatePhuong(savedPhuong);
    }
}

// ── Tab Switching ────────────────────────────────────────────────────────────
function switchProfileTab(tab) {
    document.querySelectorAll('.profile-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.profile-tab-btn').forEach(b => b.classList.remove('active'));

    const pane = document.getElementById('pane-' + tab);
    const btn = document.getElementById('tab-' + tab + '-btn');
    if (pane) pane.classList.add('active');
    if (btn) btn.classList.add('active');

    const headerIcon = document.getElementById('headerIcon');
    const headerTitle = document.getElementById('headerTitle');
    const headerSub = document.getElementById('headerSub');
    const breadcrumb = document.getElementById('profileBreadcrumbCurrent');

    if (tab === 'password') {
        if (headerIcon) headerIcon.innerHTML = '<i class="fas fa-lock"></i>';
        if (headerTitle) headerTitle.textContent = 'Đổi mật khẩu';
        if (headerSub) headerSub.textContent = 'Cập nhật mật khẩu để bảo vệ tài khoản của bạn tốt hơn';
        if (breadcrumb) breadcrumb.textContent = 'Đổi mật khẩu';
    } else {
        if (headerIcon) headerIcon.innerHTML = '<i class="fas fa-envelope-open-text"></i>';
        if (headerTitle) headerTitle.textContent = 'Hồ sơ cá nhân';
        if (headerSub) headerSub.textContent = 'Quản lý thông tin tài khoản của bạn';
        if (breadcrumb) breadcrumb.textContent = 'Hồ sơ cá nhân';
    }
}

// ── Reset Info Form ──────────────────────────────────────────────────────────
const originalInfo = {
    ten_khach_hang:   "{{ addslashes($khachHang->ten_khach_hang) }}",
    so_dien_thoai:    "{{ addslashes($khachHang->so_dien_thoai) }}",
    email:            "{{ addslashes($khachHang->email) }}",
    quan_huyen:       "{{ addslashes($savedQuan) }}",
    xa_phuong:        "{{ addslashes($savedPhuong) }}",
    dia_chi_chi_tiet: "{{ addslashes($savedChiTiet) }}",
};

function resetInfoForm() {
    const form = document.getElementById('formProfileInfo');
    if (!form) return;
    if (form.ten_khach_hang) form.ten_khach_hang.value = originalInfo.ten_khach_hang;
    if (form.so_dien_thoai) form.so_dien_thoai.value = originalInfo.so_dien_thoai;
    if (form.email) form.email.value = originalInfo.email;
    if (form.dia_chi_chi_tiet) form.dia_chi_chi_tiet.value = originalInfo.dia_chi_chi_tiet;
    if (selQuan) {
        selQuan.value = originalInfo.quan_huyen;
        updatePhuong(originalInfo.xa_phuong);
    }
}

// ── Password helpers ─────────────────────────────────────────────────────────
function togglePwd(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const icon = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    if (icon) {
        icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }
}

function checkPasswordCriteria(val) {
    const chkLen = document.getElementById('chkLen');
    const chkCase = document.getElementById('chkCase');
    const chkSpecial = document.getElementById('chkSpecial');

    // Length >= 6
    if (val.length >= 6) {
        chkLen.classList.add('valid');
    } else {
        chkLen.classList.remove('valid');
    }

    // Both upper and lowercase
    if (/[a-z]/.test(val) && /[A-Z]/.test(val)) {
        chkCase.classList.add('valid');
    } else {
        chkCase.classList.remove('valid');
    }

    // Number or special character
    if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) {
        chkSpecial.classList.add('valid');
    } else {
        chkSpecial.classList.remove('valid');
    }

    checkMatchPassword();
}

function checkMatchPassword() {
    const moi = document.getElementById('mat_khau_moi').value;
    const conf = document.getElementById('mat_khau_moi_confirmation').value;
    const label = document.getElementById('matchPasswordLabel');
    if (!label) return;

    if (!conf) {
        label.textContent = '';
        label.className = 'match-password-label';
        return;
    }
    if (moi === conf) {
        label.textContent = '✓ Mật khẩu khớp';
        label.className = 'match-password-label valid';
    } else {
        label.textContent = '✗ Mật khẩu chưa khớp';
        label.className = 'match-password-label invalid';
    }
}
</script>
@endsection