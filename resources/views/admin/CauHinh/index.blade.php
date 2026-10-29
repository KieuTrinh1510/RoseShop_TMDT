@extends('layouts.admin')

@section('content')
<div class="rs-cauhinh-page">
    {{-- Header --}}
    <div class="rs-page-header">
        <div class="rs-page-header-text">
            <h1 class="rs-page-title">
                <i class="bi bi-gear-wide-connected me-2 text-pink"></i>{{ __('admin.system_settings') }}
            </h1>
            <p class="rs-page-subtitle">{{ __('admin.system_settings_desc') }}</p>
        </div>
    </div>

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

    {{-- Layout 2 cột: Tabs dọc bên trái, Nội dung bên phải --}}
    <div class="rs-cauhinh-layout">
        {{-- Menu tab dọc --}}
        <aside class="rs-cauhinh-tabs">
            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'shop']) }}" 
               class="rs-tab-item {{ $activeTab === 'shop' ? 'active' : '' }}">
                <span class="rs-tab-icon"><i class="bi bi-shop"></i></span>
                <span class="rs-tab-text">{{ __('admin.tab_shop') }}</span>
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'interface']) }}" 
               class="rs-tab-item {{ $activeTab === 'interface' ? 'active' : '' }}">
                <span class="rs-tab-icon"><i class="bi bi-palette"></i></span>
                <span class="rs-tab-text">{{ __('admin.tab_interface') }}</span>
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'sales']) }}" 
               class="rs-tab-item {{ $activeTab === 'sales' ? 'active' : '' }}">
                <span class="rs-tab-icon"><i class="bi bi-credit-card"></i></span>
                <span class="rs-tab-text">{{ __('admin.tab_sales') }}</span>
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'inventory']) }}" 
               class="rs-tab-item {{ $activeTab === 'inventory' ? 'active' : '' }}">
                <span class="rs-tab-icon"><i class="bi bi-boxes"></i></span>
                <span class="rs-tab-text">{{ __('admin.tab_inventory') }}</span>
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'flash_sale']) }}" 
               class="rs-tab-item {{ $activeTab === 'flash_sale' ? 'active' : '' }}">
                <span class="rs-tab-icon"><i class="bi bi-lightning-charge"></i></span>
                <span class="rs-tab-text">{{ __('admin.tab_flash_sale') }}</span>
                <span class="badge bg-light text-muted ms-auto rs-badge-ro">{{ __('common.read_only') }}</span>
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'loyalty']) }}" 
               class="rs-tab-item {{ $activeTab === 'loyalty' ? 'active' : '' }}">
                <span class="rs-tab-icon"><i class="bi bi-award"></i></span>
                <span class="rs-tab-text">{{ __('admin.tab_loyalty') }}</span>
            </a>

            <a href="{{ route('admin.he-thong.cau-hinh', ['tab' => 'violation']) }}" 
               class="rs-tab-item {{ $activeTab === 'violation' ? 'active' : '' }}">
                <span class="rs-tab-icon"><i class="bi bi-shield-slash"></i></span>
                <span class="rs-tab-text">{{ __('admin.tab_violation') }}</span>
                <span class="badge bg-light text-muted ms-auto rs-badge-ro">{{ __('common.read_only') }}</span>
            </a>
        </aside>

        {{-- Khối nội dung tương ứng với tab --}}
        <main class="rs-cauhinh-content">
            {{-- 1. TAB: THÔNG TIN CỬA HÀNG --}}
            @if($activeTab === 'shop')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_shop') }}</h2>
                            <p class="rs-card-desc">Thiết lập các thông tin nhận diện thương hiệu và kênh liên hệ của RoseShop.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="group" value="shop">

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label fw-semibold">{{ __('admin.shop_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="shop_name" class="form-control" required
                                       value="{{ old('shop_name', $configs['shop_name'] ?? 'RoseShop') }}"
                                       placeholder="RoseShop">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">{{ __('admin.shop_phone') }}</label>
                                <input type="text" name="shop_phone" class="form-control"
                                       value="{{ old('shop_phone', $configs['shop_phone'] ?? '') }}"
                                       placeholder="0901234567">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">{{ __('admin.shop_email') }}</label>
                                <input type="email" name="shop_email" class="form-control"
                                       value="{{ old('shop_email', $configs['shop_email'] ?? '') }}"
                                       placeholder="contact@roseshop.vn">
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold">{{ __('admin.opening_time') }}</label>
                                <input type="time" name="opening_time" class="form-control"
                                       value="{{ old('opening_time', $configs['opening_time'] ?? '07:00') }}">
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold">{{ __('admin.closing_time') }}</label>
                                <input type="time" name="closing_time" class="form-control"
                                       value="{{ old('closing_time', $configs['closing_time'] ?? '22:00') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('admin.shop_address') }}</label>
                                <textarea name="shop_address" class="form-control" rows="2"
                                          placeholder="123 Đường Hoa Hồng, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh">{{ old('shop_address', $configs['shop_address'] ?? '') }}</textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">{{ __('admin.facebook_url') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-facebook"></i></span>
                                    <input type="url" name="facebook_url" class="form-control"
                                           value="{{ old('facebook_url', $configs['facebook_url'] ?? '') }}"
                                           placeholder="https://facebook.com/roseshop">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">{{ __('admin.zalo_url') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-chat-dots-fill"></i></span>
                                    <input type="text" name="zalo_url" class="form-control"
                                           value="{{ old('zalo_url', $configs['zalo_url'] ?? '') }}"
                                           placeholder="https://zalo.me/0901234567">
                                </div>
                            </div>

                            {{-- Logo Upload & Preview --}}
                            <div class="col-12 pt-2">
                                <label class="form-label fw-semibold">{{ __('admin.shop_logo') }}</label>
                                <div class="rs-logo-box">
                                    <div class="rs-logo-preview-wrap">
                                        <img id="logo-preview" 
                                             src="{{ \App\Services\SystemConfigService::getLogoUrl() }}" 
                                             alt="Shop Logo" 
                                             class="rs-logo-preview"
                                             onerror="this.src='{{ asset('img/logo.png') }}'">
                                    </div>
                                    <div class="rs-logo-actions">
                                        <label for="shop_logo" class="btn btn-outline-secondary btn-sm rs-upload-btn">
                                            <i class="bi bi-upload me-1"></i> {{ __('common.upload_image') }}
                                        </label>
                                        <input type="file" name="shop_logo" id="shop_logo" class="d-none" accept="image/*" onchange="previewLogo(event)">
                                        <p class="text-muted small mb-0 mt-2">{{ __('admin.shop_logo_help') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn btn-pink px-4">
                                <i class="bi bi-save me-1"></i> {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 2. TAB: GIAO DIỆN & NGÔN NGỮ --}}
            @if($activeTab === 'interface')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_interface') }}</h2>
                            <p class="rs-card-desc">Cấu hình ngôn ngữ mặc định và chế độ giao diện Sáng / Tối cho toàn hệ thống.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="interface">

                        {{-- Chọn Ngôn ngữ --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-2">{{ __('admin.default_locale') }}</label>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="rs-radio-card {{ ($configs['default_locale'] ?? 'vi') === 'vi' ? 'checked' : '' }}">
                                        <input type="radio" name="default_locale" value="vi" 
                                               {{ ($configs['default_locale'] ?? 'vi') === 'vi' ? 'checked' : '' }}>
                                        <div class="rs-radio-card-content">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="fw-bold">🇻🇳 {{ __('admin.locale_vi') }}</span>
                                                <span class="badge bg-pink text-white small">Mặc định</span>
                                            </div>
                                            <p class="text-muted small mb-0">Hệ thống hiển thị tiếng Việt chuẩn theo quy trình RoseShop.</p>
                                        </div>
                                    </label>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="rs-radio-card {{ ($configs['default_locale'] ?? 'vi') === 'en' ? 'checked' : '' }}">
                                        <input type="radio" name="default_locale" value="en" 
                                               {{ ($configs['default_locale'] ?? 'vi') === 'en' ? 'checked' : '' }}>
                                        <div class="rs-radio-card-content">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="fw-bold">🇬🇧 {{ __('admin.locale_en') }}</span>
                                                <span class="badge bg-secondary small">International</span>
                                            </div>
                                            <p class="text-muted small mb-0">English interface for admin, navigation, and system labels.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="rs-divider my-4">

                        {{-- Chọn Chế độ Giao diện (Theme) --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-2">{{ __('admin.default_theme') }}</label>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="rs-radio-card {{ ($configs['default_theme'] ?? 'light') === 'light' ? 'checked' : '' }}">
                                        <input type="radio" name="default_theme" value="light" 
                                               {{ ($configs['default_theme'] ?? 'light') === 'light' ? 'checked' : '' }}>
                                        <div class="rs-radio-card-content">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="fw-bold"><i class="bi bi-sun text-warning me-1"></i> {{ __('admin.theme_light') }}</span>
                                                <span class="badge bg-light text-dark border small">Standard</span>
                                            </div>
                                            <p class="text-muted small mb-0">{{ __('admin.theme_light_desc') }}</p>
                                        </div>
                                    </label>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="rs-radio-card {{ ($configs['default_theme'] ?? 'light') === 'dark' ? 'checked' : '' }}">
                                        <input type="radio" name="default_theme" value="dark" 
                                               {{ ($configs['default_theme'] ?? 'light') === 'dark' ? 'checked' : '' }}>
                                        <div class="rs-radio-card-content">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="fw-bold"><i class="bi bi-moon-stars text-pink me-1"></i> {{ __('admin.theme_dark') }}</span>
                                                <span class="badge bg-dark text-white small">Dark</span>
                                            </div>
                                            <p class="text-muted small mb-0">{{ __('admin.theme_dark_desc') }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn btn-pink px-4">
                                <i class="bi bi-save me-1"></i> {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 3. TAB: BÁN HÀNG & THANH TOÁN --}}
            @if($activeTab === 'sales')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_sales') }}</h2>
                            <p class="rs-card-desc">Quản lý phương thức thanh toán tiền mặt (COD), cổng thanh toán VNPay và thời gian hiệu lực.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="sales">

                        {{-- Toggle COD --}}
                        <div class="rs-toggle-row">
                            <div>
                                <h3 class="rs-toggle-title">{{ __('admin.payment_cod') }}</h3>
                                <p class="rs-toggle-desc">{{ __('admin.payment_cod_desc') }}</p>
                            </div>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" name="payment_cod_enabled" value="1"
                                       {{ !empty($configs['payment_cod_enabled']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <hr class="rs-divider">

                        {{-- Toggle VNPay --}}
                        <div class="rs-toggle-row">
                            <div>
                                <h3 class="rs-toggle-title">{{ __('admin.payment_vnpay') }}</h3>
                                <p class="rs-toggle-desc">{{ __('admin.payment_vnpay_desc') }}</p>
                            </div>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" name="payment_vnpay_enabled" value="1"
                                       {{ !empty($configs['payment_vnpay_enabled']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <hr class="rs-divider">

                        {{-- VNPay Timeout --}}
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-md-7">
                                <label class="form-label fw-semibold mb-1">{{ __('admin.vnpay_timeout') }}</label>
                                <p class="text-muted small mb-0">{{ __('admin.vnpay_timeout_help') }}</p>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="input-group">
                                    <input type="number" name="vnpay_timeout_minutes" class="form-control text-end" min="1" max="1440" required
                                           value="{{ old('vnpay_timeout_minutes', $configs['vnpay_timeout_minutes'] ?? 15) }}">
                                    <span class="input-group-text">{{ __('common.minutes') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Bảo mật VNPay: Tuyệt đối không để lộ secret --}}
                        <div class="rs-security-card p-3 rounded-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rs-status-icon text-success fs-5"><i class="bi bi-shield-check"></i></span>
                                    <div>
                                        <div class="fw-semibold">{{ __('admin.vnpay_gateway_status') }}</div>
                                        <div class="small text-muted">
                                            @if($vnpayConfigured)
                                                <span class="text-success fw-medium"><i class="bi bi-check2-circle"></i> {{ __('admin.vnpay_configured') }}</span>
                                            @else
                                                <span class="text-warning fw-medium"><i class="bi bi-exclamation-circle"></i> {{ __('admin.vnpay_not_configured') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="badge bg-light text-muted border px-2 py-1 small">
                                    <i class="bi bi-lock-fill me-1"></i> Secrets Protected
                                </span>
                            </div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn btn-pink px-4">
                                <i class="bi bi-save me-1"></i> {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 4. TAB: KHO & HẠN SỬ DỤNG --}}
            @if($activeTab === 'inventory')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_inventory') }}</h2>
                            <p class="rs-card-desc">Thiết lập các mốc thời gian hạn sử dụng mặc định và ngưỡng cảnh báo vận hành kho.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="inventory">

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold mb-1">{{ __('admin.fresh_flower_expiry') }}</label>
                                <p class="text-muted small mb-2">{{ __('admin.fresh_flower_expiry_help') }}</p>
                                <div class="input-group">
                                    <input type="number" name="fresh_flower_expiry_days" class="form-control text-end" min="1" max="365" required
                                           value="{{ old('fresh_flower_expiry_days', $configs['fresh_flower_expiry_days'] ?? 7) }}">
                                    <span class="input-group-text">{{ __('common.days') }}</span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold mb-1">{{ __('admin.finished_bouquet_expiry') }}</label>
                                <p class="text-muted small mb-2">{{ __('admin.finished_bouquet_expiry_help') }}</p>
                                <div class="input-group">
                                    <input type="number" name="finished_bouquet_expiry_days" class="form-control text-end" min="1" max="365" required
                                           value="{{ old('finished_bouquet_expiry_days', $configs['finished_bouquet_expiry_days'] ?? 5) }}">
                                    <span class="input-group-text">{{ __('common.days') }}</span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold mb-1">{{ __('admin.expiry_warning') }}</label>
                                <p class="text-muted small mb-2">{{ __('admin.expiry_warning_help') }}</p>
                                <div class="input-group">
                                    <input type="number" name="expiry_warning_days" class="form-control text-end" min="0" max="60" required
                                           value="{{ old('expiry_warning_days', $configs['expiry_warning_days'] ?? 2) }}">
                                    <span class="input-group-text">{{ __('common.days') }}</span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold mb-1">{{ __('admin.low_stock') }}</label>
                                <p class="text-muted small mb-2">{{ __('admin.low_stock_help') }}</p>
                                <div class="input-group">
                                    <input type="number" name="low_stock_threshold" class="form-control text-end" min="0" max="10000" required
                                           value="{{ old('low_stock_threshold', $configs['low_stock_threshold'] ?? 10) }}">
                                    <span class="input-group-text">{{ __('common.items') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Ghi chú chính sách vận hành: Giữ nguyên Business core --}}
                        <div class="rs-policy-notice p-3 rounded-3 mb-3">
                            <div class="fw-semibold text-primary mb-1">
                                <i class="bi bi-info-circle-fill me-1"></i> {{ __('admin.hsd_notice_title') }}
                            </div>
                            <p class="text-muted small mb-0">{{ __('admin.hsd_notice_body') }}</p>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn btn-pink px-4">
                                <i class="bi bi-save me-1"></i> {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 5. TAB: FLASH SALE (CHỈ ĐỌC - READ ONLY) --}}
            @if($activeTab === 'flash_sale')
                <div class="rs-card">
                    <div class="rs-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_flash_sale') }}</h2>
                            <p class="rs-card-desc">{{ __('admin.flash_sale_notice_title') }}</p>
                        </div>
                        <span class="badge bg-secondary px-3 py-2 rs-badge-ro">
                            <i class="bi bi-lock-fill me-1"></i> {{ __('common.read_only') }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">{{ __('admin.allowed_discounts') }}</label>
                        <div class="row g-3">
                            @foreach($flashSalePolicies as $p)
                                <div class="col-12 col-md-4">
                                    <div class="rs-policy-card p-3 rounded-3 border">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="fs-4 fw-bold text-pink">{{ $p['rate'] }}</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-lg"></i> {{ __('common.active') }}
                                            </span>
                                        </div>
                                        <p class="text-muted small mb-0">{{ $p['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rs-policy-notice p-3 rounded-3">
                        <div class="fw-semibold text-primary mb-1">
                            <i class="bi bi-shield-check me-1"></i> {{ __('common.system_policy') }}
                        </div>
                        <p class="text-muted small mb-0">{{ __('admin.flash_sale_notice_body') }}</p>
                    </div>
                </div>
            @endif

            {{-- 6. TAB: KHÁCH HÀNG & ĐIỂM THƯỞNG --}}
            @if($activeTab === 'loyalty')
                <div class="rs-card">
                    <div class="rs-card-header">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_loyalty') }}</h2>
                            <p class="rs-card-desc">Cấu hình điểm tích lũy và đền bù cho khách hàng trong các tình huống vận hành giao hàng.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.he-thong.cau-hinh.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="loyalty">

                        <div class="row mb-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold mb-1">{{ __('admin.compensation_points') }}</label>
                                <p class="text-muted small mb-2">{{ __('admin.compensation_points_help') }}</p>
                                <div class="input-group">
                                    <input type="number" name="compensation_points" class="form-control text-end" min="0" max="10000" required
                                           value="{{ old('compensation_points', $configs['compensation_points'] ?? 20) }}">
                                    <span class="input-group-text">{{ __('common.points') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rs-form-footer">
                            <button type="submit" class="btn btn-pink px-4">
                                <i class="bi bi-save me-1"></i> {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 7. TAB: CHÍNH SÁCH VI PHẠM & COD (CHỈ ĐỌC - READ ONLY) --}}
            @if($activeTab === 'violation')
                <div class="rs-card">
                    <div class="rs-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h2 class="rs-card-title">{{ __('admin.tab_violation') }}</h2>
                            <p class="rs-card-desc">{{ __('admin.violation_policy_title') }}</p>
                        </div>
                        <span class="badge bg-secondary px-3 py-2 rs-badge-ro">
                            <i class="bi bi-lock-fill me-1"></i> {{ __('common.read_only') }}
                        </span>
                    </div>

                    <div class="d-flex flex-column gap-3 mb-4">
                        @foreach($violationPolicies as $v)
                            <div class="rs-violation-card p-3 rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-{{ $v['status'] }} px-2 py-1">{{ $v['badge'] }}</span>
                                        <span class="fw-bold">{{ $v['title'] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 small">
                                        <span class="badge {{ $v['cod_allowed'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                            COD: {{ $v['cod_allowed'] ? 'Cho phép' : 'Khóa' }}
                                        </span>
                                        <span class="badge {{ $v['buy_allowed'] ? 'bg-success-subtle text-success' : 'bg-dark text-white' }}">
                                            Mua hàng: {{ $v['buy_allowed'] ? 'Bình thường' : 'Tạm khóa' }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-muted small mb-0">{{ $v['description'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="rs-policy-notice p-3 rounded-3">
                        <div class="fw-semibold text-primary mb-1">
                            <i class="bi bi-info-circle-fill me-1"></i> {{ __('common.system_policy') }}
                        </div>
                        <p class="text-muted small mb-0">Chính sách vi phạm và hạn chế COD được bảo vệ nghiêm ngặt ở tầng Core Backend nhằm đảm bảo tỉ lệ giao hàng thành công và quyền lợi chung của toàn hệ thống RoseShop.</p>
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>

<style>
/* ==========================================================================
   RoseShop System Settings Page Custom Styling
   ========================================================================== */
.rs-cauhinh-page {
    max-width: 1200px;
    margin: 0 auto;
}

.rs-cauhinh-layout {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

/* Sidebar Tabs Dọc */
.rs-cauhinh-tabs {
    width: 280px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: var(--rs-white, #ffffff);
    border: 1px solid var(--rs-border, #eaecf0);
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
}

.rs-tab-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    color: var(--rs-secondary-text, #475467);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}

.rs-tab-item:hover {
    background: var(--rs-brand-light-bg, #fdeaf3);
    color: var(--rs-brand-primary, #ec3d8f);
}

.rs-tab-item.active {
    background: var(--rs-brand-light-bg, #fdeaf3);
    color: var(--rs-brand-primary, #ec3d8f);
    font-weight: 600;
}

.rs-tab-icon {
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.rs-badge-ro {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Content Area */
.rs-cauhinh-content {
    flex: 1;
    min-width: 0;
}

.rs-card {
    background: var(--rs-white, #ffffff);
    border: 1px solid var(--rs-border, #eaecf0);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
}

.rs-card-header {
    border-bottom: 1px solid var(--rs-border, #eaecf0);
    padding-bottom: 16px;
    margin-bottom: 24px;
}

.rs-card-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: var(--rs-primary-text, #25324b);
}

.rs-card-desc {
    font-size: 13px;
    color: var(--rs-muted-text, #667085);
    margin: 0;
}

.rs-divider {
    border-color: var(--rs-border, #eaecf0);
    opacity: 0.8;
}

.rs-form-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid var(--rs-border, #eaecf0);
}

/* Radio Cards (Language & Theme selection) */
.rs-radio-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border: 1.5px solid var(--rs-border, #eaecf0);
    border-radius: 10px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    height: 100%;
}

.rs-radio-card:hover {
    border-color: #ec3d8f;
    background: rgba(236, 61, 143, 0.02);
}

.rs-radio-card.checked {
    border-color: #ec3d8f;
    background: var(--rs-brand-light-bg, #fdeaf3);
}

.rs-radio-card input[type="radio"] {
    margin-top: 3px;
    accent-color: #ec3d8f;
}

.rs-radio-card-content {
    flex: 1;
}

/* Toggles */
.rs-toggle-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.rs-toggle-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--rs-primary-text, #25324b);
    margin: 0 0 4px 0;
}

.rs-toggle-desc {
    font-size: 13px;
    color: var(--rs-muted-text, #667085);
    margin: 0;
}

/* Logo Box */
.rs-logo-box {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 16px;
    background: var(--rs-page-bg, #f8f9fc);
    border: 1px dashed var(--rs-border, #eaecf0);
    border-radius: 10px;
}

.rs-logo-preview-wrap {
    width: 72px;
    height: 72px;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #eaecf0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rs-logo-preview {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Policy & Security Notice Box */
.rs-policy-notice {
    background: #eff8ff;
    border: 1px solid #b2ddff;
}

[data-theme="dark"] .rs-policy-notice {
    background: #17283c;
    border-color: #1e3a8a;
}

.rs-security-card {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

[data-theme="dark"] .rs-security-card {
    background: #132d20;
    border-color: #166534;
}

.rs-violation-card,
.rs-policy-card {
    background: var(--rs-page-bg, #f8f9fc);
    border-color: var(--rs-border, #eaecf0) !important;
}

.btn-pink {
    background-color: #ec3d8f;
    color: #ffffff;
    border: none;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.btn-pink:hover {
    background-color: #d92f7d;
    color: #ffffff;
}

.text-pink {
    color: #ec3d8f !important;
}

/* Responsive */
@media (max-width: 860px) {
    .rs-cauhinh-layout {
        flex-direction: column;
    }
    .rs-cauhinh-tabs {
        width: 100%;
        flex-direction: row;
        overflow-x: auto;
        padding: 8px;
        white-space: nowrap;
    }
    .rs-tab-item {
        padding: 8px 12px;
        flex-shrink: 0;
    }
}
</style>

<script>
function previewLogo(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logo-preview');
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
