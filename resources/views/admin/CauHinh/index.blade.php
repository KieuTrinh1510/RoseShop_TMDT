@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/Admin/CauHinh.css') }}?v={{ time() }}">
@endpush

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/Admin/CauHinh.css') }}?v={{ time() }}">

<div class="rs-cauhinh-page">
    {{-- Alert thông báo --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rs-alert mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rs-alert mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show rs-alert mb-4" role="alert">
            <div class="fw-semibold mb-1"><i class="bi bi-x-circle-fill me-2"></i> Vui lòng kiểm tra lại thông tin:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Layout 2 cột: Sidebar cấu hình và Card nội dung --}}
    <div class="rs-cauhinh-layout">
        {{-- Menu tab dọc theo ảnh mẫu --}}
        <aside class="rs-cauhinh-tabs">
            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'shop']) }}" 
               class="rs-tab-item {{ $activeTab === 'shop' ? 'active' : '' }}">
                {{ __('admin.tab_shop') }}
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'interface']) }}" 
               class="rs-tab-item {{ $activeTab === 'interface' ? 'active' : '' }}">
                {{ __('admin.tab_interface') }}
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'sales']) }}" 
               class="rs-tab-item {{ $activeTab === 'sales' ? 'active' : '' }}">
                {{ __('admin.tab_sales') }}
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'inventory']) }}" 
               class="rs-tab-item {{ $activeTab === 'inventory' ? 'active' : '' }}">
                {{ __('admin.tab_inventory') }}
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'flash_sale']) }}" 
               class="rs-tab-item {{ $activeTab === 'flash_sale' ? 'active' : '' }}">
                {{ __('admin.tab_flash_sale') }}
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'loyalty']) }}" 
               class="rs-tab-item {{ $activeTab === 'loyalty' ? 'active' : '' }}">
                {{ __('admin.tab_loyalty') }}
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'violation']) }}" 
               class="rs-tab-item {{ $activeTab === 'violation' ? 'active' : '' }}">
                {{ __('admin.tab_violation') }}
            </a>
        </aside>

        {{-- Khối nội dung tương ứng với tab --}}
        <main class="rs-cauhinh-content">
            {{-- 1. TAB: THÔNG TIN CỬA HÀNG --}}
            @if($activeTab === 'shop')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <h2 class="rs-card-title">{{ __('admin.tab_shop') }}</h2>
                        <p class="rs-card-desc">{{ __('admin.system_settings_desc') }}</p>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="group" value="shop">

                        <div class="rs-form-grid-2">
                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.shop_name') }} *</label>
                                <input type="text" name="shop_name" class="rs-form-input" required
                                       value="{{ old('shop_name', $configs['shop_name'] ?? 'RoseShop') }}"
                                       placeholder="RoseShop">
                            </div>

                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.shop_phone') }}</label>
                                <input type="text" name="shop_phone" class="rs-form-input"
                                       value="{{ old('shop_phone', $configs['shop_phone'] ?? '') }}"
                                       placeholder="0901234567">
                            </div>

                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.shop_email') }}</label>
                                <input type="email" name="shop_email" class="rs-form-input"
                                       value="{{ old('shop_email', $configs['shop_email'] ?? '') }}"
                                       placeholder="roseshop@gmail.com">
                            </div>

                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.shop_address') }}</label>
                                <input type="text" name="shop_address" class="rs-form-input"
                                       value="{{ old('shop_address', $configs['shop_address'] ?? '') }}"
                                       placeholder="123 Đường Hoa Hồng...">
                            </div>

                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.opening_time') }}</label>
                                <input type="text" name="opening_time" class="rs-form-input"
                                       value="{{ old('opening_time', $configs['opening_time'] ?? '07:00 AM') }}"
                                       placeholder="07:00 AM">
                            </div>

                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.closing_time') }}</label>
                                <input type="text" name="closing_time" class="rs-form-input"
                                       value="{{ old('closing_time', $configs['closing_time'] ?? '10:00 PM') }}"
                                       placeholder="10:00 PM">
                            </div>

                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.facebook_url') }}</label>
                                <input type="url" name="facebook_url" class="rs-form-input"
                                       value="{{ old('facebook_url', $configs['facebook_url'] ?? '') }}"
                                       placeholder="https://facebook.com/...">
                            </div>

                            <div class="rs-form-field">
                                <label class="rs-form-label">{{ __('admin.zalo_url') }}</label>
                                <input type="text" name="zalo_url" class="rs-form-input"
                                       value="{{ old('zalo_url', $configs['zalo_url'] ?? '') }}"
                                       placeholder="https://zalo.me/...">
                            </div>
                        </div>

                        {{-- Logo cửa hàng --}}
                        <div class="rs-form-logo-section">
                            <label class="rs-form-label">{{ __('admin.shop_logo') }}</label>
                            <div class="rs-logo-card">
                                <div class="rs-logo-card-left">
                                    <div class="rs-logo-icon-wrap">
                                        <img id="logo-preview" 
                                             src="{{ \App\Services\SystemConfigService::getLogoUrl() }}" 
                                             alt="RoseShop Logo" 
                                             class="rs-logo-img"
                                             onerror="this.src='{{ asset('img/logo.png') }}'">
                                    </div>
                                    <div>
                                        <div class="rs-logo-title">{{ __('common.upload_image') }}</div>
                                        <div class="rs-logo-hint">{{ __('admin.shop_logo_help') }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label for="shop_logo" class="btn-choose-file">
                                        {{ __('common.change_image') }}
                                    </label>
                                    <input type="file" name="shop_logo" id="shop_logo" style="display: none !important;" accept="image/*" onchange="previewLogo(event)">
                                </div>
                            </div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn-save-pink">
                                {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 2. TAB: GIAO DIỆN & NGÔN NGỮ --}}
            @if($activeTab === 'interface')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <h2 class="rs-card-title">{{ __('admin.tab_interface') }}</h2>
                        <p class="rs-card-desc">{{ __('admin.system_settings_desc') }}</p>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="interface">

                        {{-- 1. Ngôn ngữ mặc định --}}
                        <div class="rs-section-group">
                            <label class="rs-section-title">{{ __('admin.default_locale') }}</label>
                            <div class="rs-radio-grid-2">
                                <label class="rs-radio-choice {{ ($configs['default_locale'] ?? 'vi') === 'vi' ? 'active' : '' }}">
                                    <input type="radio" name="default_locale" value="vi" {{ ($configs['default_locale'] ?? 'vi') === 'vi' ? 'checked' : '' }} onchange="this.form.querySelectorAll('input[name=default_locale]').forEach(el => el.closest('.rs-radio-choice').classList.toggle('active', el.checked))">
                                    <span class="rs-custom-radio"></span>
                                    <div class="rs-radio-body">
                                        <div class="rs-radio-head">
                                            <span class="rs-radio-name">{{ __('admin.locale_vi') }}</span>
                                            <span class="rs-badge-gray">VI</span>
                                        </div>
                                        <div class="rs-radio-sub">Ngôn ngữ tiếng Việt chuẩn RoseShop</div>
                                    </div>
                                </label>

                                <label class="rs-radio-choice {{ ($configs['default_locale'] ?? 'vi') === 'en' ? 'active' : '' }}">
                                    <input type="radio" name="default_locale" value="en" {{ ($configs['default_locale'] ?? 'vi') === 'en' ? 'checked' : '' }} onchange="this.form.querySelectorAll('input[name=default_locale]').forEach(el => el.closest('.rs-radio-choice').classList.toggle('active', el.checked))">
                                    <span class="rs-custom-radio"></span>
                                    <div class="rs-radio-body">
                                        <div class="rs-radio-head">
                                            <span class="rs-radio-name">{{ __('admin.locale_en') }}</span>
                                            <span class="rs-badge-gray">EN</span>
                                        </div>
                                        <div class="rs-radio-sub">English international interface</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- 2. Giao diện mặc định --}}
                        <div class="rs-section-group">
                            <label class="rs-section-title">{{ __('admin.default_theme') }}</label>
                            <div class="rs-radio-grid-2">
                                <label class="rs-radio-choice {{ ($configs['default_theme'] ?? 'light') === 'light' ? 'active' : '' }}">
                                    <input type="radio" name="default_theme" value="light" {{ ($configs['default_theme'] ?? 'light') === 'light' ? 'checked' : '' }} onchange="this.form.querySelectorAll('input[name=default_theme]').forEach(el => el.closest('.rs-radio-choice').classList.toggle('active', el.checked)); document.documentElement.setAttribute('data-theme', 'light');">
                                    <span class="rs-custom-radio"></span>
                                    <div class="rs-radio-body">
                                        <div class="rs-radio-head">
                                            <span class="rs-radio-name">{{ __('admin.theme_light') }}</span>
                                        </div>
                                        <div class="rs-radio-sub">{{ __('admin.theme_light_desc') }}</div>
                                    </div>
                                </label>

                                <label class="rs-radio-choice {{ ($configs['default_theme'] ?? 'light') === 'dark' ? 'active' : '' }}">
                                    <input type="radio" name="default_theme" value="dark" {{ ($configs['default_theme'] ?? 'light') === 'dark' ? 'checked' : '' }} onchange="this.form.querySelectorAll('input[name=default_theme]').forEach(el => el.closest('.rs-radio-choice').classList.toggle('active', el.checked)); document.documentElement.setAttribute('data-theme', 'dark');">
                                    <span class="rs-custom-radio"></span>
                                    <div class="rs-radio-body">
                                        <div class="rs-radio-head">
                                            <span class="rs-radio-name">{{ __('admin.theme_dark') }}</span>
                                        </div>
                                        <div class="rs-radio-sub">{{ __('admin.theme_dark_desc') }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn-save-pink">
                                {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 3. TAB: BÁN HÀNG & THANH TOÁN --}}
            @if($activeTab === 'sales')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <h2 class="rs-card-title">{{ __('admin.tab_sales') }}</h2>
                        <p class="rs-card-desc">{{ __('admin.system_settings_desc') }}</p>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="sales">

                        <div class="rs-section-group">
                            <label class="rs-section-title">{{ __('admin.tab_sales') }}</label>
                            <div class="rs-toggle-list">
                                {{-- Row 1: COD --}}
                                <div class="rs-toggle-card">
                                    <div class="rs-toggle-info">
                                        <div class="rs-toggle-title">{{ __('admin.payment_cod') }}</div>
                                        <div class="rs-toggle-desc">{{ __('admin.payment_cod_desc') }}</div>
                                    </div>
                                    <label class="rs-switch">
                                        <input type="checkbox" name="payment_cod_enabled" value="1" {{ !empty($configs['payment_cod_enabled']) ? 'checked' : '' }}>
                                        <span class="rs-slider"></span>
                                    </label>
                                </div>

                                {{-- Row 2: VNPay --}}
                                <div class="rs-toggle-card">
                                    <div class="rs-toggle-info">
                                        <div class="rs-toggle-title">{{ __('admin.payment_vnpay') }}</div>
                                        <div class="rs-toggle-desc">{{ __('admin.payment_vnpay_desc') }}</div>
                                    </div>
                                    <label class="rs-switch">
                                        <input type="checkbox" name="payment_vnpay_enabled" value="1" {{ !empty($configs['payment_vnpay_enabled']) ? 'checked' : '' }}>
                                        <span class="rs-slider"></span>
                                    </label>
                                </div>

                                {{-- Row 3: Timeout (cùng hàng sang bên phải) --}}
                                <div class="rs-toggle-card">
                                    <div class="rs-toggle-info pe-3">
                                        <div class="rs-toggle-title">{{ __('admin.vnpay_timeout') }}</div>
                                        <div class="rs-toggle-desc">{{ __('admin.vnpay_timeout_help') }}</div>
                                    </div>
                                    <div class="rs-input-with-unit m-0 flex-shrink-0">
                                        <input type="number" name="vnpay_timeout_minutes" class="rs-inventory-input" min="1" max="1440" required
                                               value="{{ old('vnpay_timeout_minutes', $configs['vnpay_timeout_minutes'] ?? 15) }}">
                                        <span class="rs-inventory-unit">{{ __('common.minutes') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Row 4: VNPay Status Card --}}
                        <div class="rs-status-box-green">
                            <div class="rs-status-box-title">{{ __('admin.vnpay_gateway_status') }}</div>
                            <div class="rs-status-box-desc">
                                @if($vnpayConfigured)
                                    {{ __('admin.vnpay_configured') }}
                                @else
                                    {{ __('admin.vnpay_not_configured') }}
                                @endif
                            </div>
                            <div class="rs-status-badge">Secrets Protected</div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn-save-pink">
                                {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 4. TAB: KHO & HẠN SỬ DỤNG --}}
            @if($activeTab === 'inventory')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <h2 class="rs-card-title">{{ __('admin.tab_inventory') }}</h2>
                        <p class="rs-card-desc">{{ __('admin.system_settings_desc') }}</p>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="inventory">

                        <div class="rs-inventory-grid">
                            <div class="rs-inventory-card">
                                <div>
                                    <div class="rs-inventory-title">{{ __('admin.fresh_flower_expiry') }}</div>
                                    <div class="rs-inventory-desc">{{ __('admin.fresh_flower_expiry_help') }}</div>
                                </div>
                                <div class="rs-inventory-input-wrap">
                                    <input type="number" name="fresh_flower_expiry_days" class="rs-inventory-input" min="1" max="365" required
                                           value="{{ old('fresh_flower_expiry_days', $configs['fresh_flower_expiry_days'] ?? 7) }}">
                                    <span class="rs-inventory-unit">{{ __('common.days') }}</span>
                                </div>
                            </div>

                            <div class="rs-inventory-card">
                                <div>
                                    <div class="rs-inventory-title">{{ __('admin.finished_bouquet_expiry') }}</div>
                                    <div class="rs-inventory-desc">{{ __('admin.finished_bouquet_expiry_help') }}</div>
                                </div>
                                <div class="rs-inventory-input-wrap">
                                    <input type="number" name="finished_bouquet_expiry_days" class="rs-inventory-input" min="1" max="365" required
                                           value="{{ old('finished_bouquet_expiry_days', $configs['finished_bouquet_expiry_days'] ?? 5) }}">
                                    <span class="rs-inventory-unit">{{ __('common.days') }}</span>
                                </div>
                            </div>

                            <div class="rs-inventory-card">
                                <div>
                                    <div class="rs-inventory-title">{{ __('admin.expiry_warning') }}</div>
                                    <div class="rs-inventory-desc">{{ __('admin.expiry_warning_help') }}</div>
                                </div>
                                <div class="rs-inventory-input-wrap">
                                    <input type="number" name="expiry_warning_days" class="rs-inventory-input" min="0" max="60" required
                                           value="{{ old('expiry_warning_days', $configs['expiry_warning_days'] ?? 2) }}">
                                    <span class="rs-inventory-unit">{{ __('common.days') }}</span>
                                </div>
                            </div>

                            <div class="rs-inventory-card">
                                <div>
                                    <div class="rs-inventory-title">{{ __('admin.low_stock') }}</div>
                                    <div class="rs-inventory-desc">{{ __('admin.low_stock_help') }}</div>
                                </div>
                                <div class="rs-inventory-input-wrap">
                                    <input type="number" name="low_stock_threshold" class="rs-inventory-input" min="0" max="10000" required
                                           value="{{ old('low_stock_threshold', $configs['low_stock_threshold'] ?? 10) }}">
                                    <span class="rs-inventory-unit">{{ __('common.items') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Ghi chú chính sách quản lý hạn sử dụng --}}
                        <div class="rs-policy-notice-blue">
                            <div class="rs-policy-notice-title">{{ __('admin.hsd_notice_title') }}</div>
                            <p class="rs-policy-notice-body">
                                {{ __('admin.hsd_notice_body') }}
                            </p>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn-save-pink">
                                {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 5. TAB: FLASH SALE (CHỈ ĐỌC - READ ONLY) --}}
            @if($activeTab === 'flash_sale')
                <div class="rs-card">
                    <div class="rs-card-header rs-card-header-flex">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_flash_sale') }}</h2>
                            <p class="rs-card-desc">{{ __('admin.flash_sale_notice_title') }}</p>
                        </div>
                        <span class="rs-badge-readonly">{{ __('common.read_only') }}</span>
                    </div>

                    <div class="rs-section-group">
                        <label class="rs-section-title">{{ __('admin.allowed_discounts') }}</label>
                        <div class="rs-flashsale-grid-3">
                            @foreach($flashSalePolicies as $p)
                                <div class="rs-flashsale-card">
                                    <div class="rs-flashsale-card-head">
                                        <span class="rs-flashsale-rate">{{ $p['rate'] }}</span>
                                        <span class="rs-badge-gray">{{ __('common.active') }}</span>
                                    </div>
                                    <div class="rs-flashsale-card-desc">{{ $p['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rs-policy-notice-blue">
                        <div class="rs-policy-notice-title">{{ __('admin.flash_sale_notice_title') }}</div>
                        <p class="rs-policy-notice-body">
                            {{ __('admin.flash_sale_notice_body') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- 6. TAB: KHÁCH HÀNG & ĐIỂM THƯỞNG --}}
            @if($activeTab === 'loyalty')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <h2 class="rs-card-title">{{ __('admin.tab_loyalty') }}</h2>
                        <p class="rs-card-desc">{{ __('admin.system_settings_desc') }}</p>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="loyalty">

                        <div class="rs-section-group">
                            <div class="rs-toggle-card">
                                <div class="rs-toggle-info pe-4">
                                    <div class="rs-toggle-title">{{ __('admin.compensation_points') }}</div>
                                    <div class="rs-toggle-desc">{{ __('admin.compensation_points_help') }}</div>
                                </div>
                                <div class="rs-input-with-unit m-0 flex-shrink-0">
                                    <input type="number" name="compensation_points" class="rs-inventory-input" min="0" max="10000" required
                                           value="{{ old('compensation_points', $configs['compensation_points'] ?? 20) }}">
                                    <span class="rs-inventory-unit">{{ __('common.points') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn-save-pink">
                                {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 7. TAB: CHÍNH SÁCH VI PHẠM & COD (CHỈ ĐỌC - READ ONLY) --}}
            @if($activeTab === 'violation')
                <div class="rs-card">
                    <div class="rs-card-header rs-card-header-flex">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_violation') }}</h2>
                            <p class="rs-card-desc">{{ __('admin.violation_policy_title') }}</p>
                        </div>
                        <span class="rs-badge-readonly">{{ __('common.read_only') }}</span>
                    </div>

                    {{-- Bảng so sánh 4 cột --}}
                    <div class="rs-violation-table-wrap">
                        <table class="rs-violation-table">
                            <thead>
                                <tr>
                                    <th>Mức xử lý</th>
                                    <th>Điều kiện</th>
                                    <th>Trạng thái COD</th>
                                    <th>Quyền mua hàng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-warning-bold">{{ __('admin.violation_rule_1') }}</td>
                                    <td>1 vi phạm chưa xử lý</td>
                                    <td>Cho phép</td>
                                    <td>Bình thường</td>
                                </tr>
                                <tr>
                                    <td class="text-pink-bold">{{ __('admin.violation_rule_2') }}</td>
                                    <td>2 vi phạm chưa xử lý</td>
                                    <td>Khóa</td>
                                    <td>Bình thường</td>
                                </tr>
                                <tr>
                                    <td class="text-danger-bold">{{ __('admin.violation_rule_3') }}</td>
                                    <td>Từ 3 vi phạm trở lên</td>
                                    <td>Khóa</td>
                                    <td>Tạm khóa</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Chi tiết xử lý --}}
                    <div class="rs-detail-box">
                        <div class="rs-detail-title">Chi tiết xử lý</div>
                        <div class="rs-detail-item"><strong>{{ __('admin.violation_rule_1') }}:</strong> {{ __('admin.violation_rule_1_desc') }}</div>
                        <div class="rs-detail-item"><strong>{{ __('admin.violation_rule_2') }}:</strong> {{ __('admin.violation_rule_2_desc') }}</div>
                        <div class="rs-detail-item"><strong>{{ __('admin.violation_rule_3') }}:</strong> {{ __('admin.violation_rule_3_desc') }}</div>
                    </div>

                    {{-- Chính sách hệ thống --}}
                    <div class="rs-policy-notice-blue">
                        <div class="rs-policy-notice-title">{{ __('common.system_policy') }}</div>
                        <p class="rs-policy-notice-body">
                            Chính sách vi phạm và hạn chế COD được bảo vệ ở tầng Core Backend nhằm đảm bảo tỷ lệ giao hàng thành công và quyền lợi chung của hệ thống RoseShop.
                        </p>
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>

<script>
function previewLogo(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logo-preview');
            const placeholder = document.getElementById('logo-placeholder');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
