<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RoseShop Admin</title>

    <!-- Google Fonts: Be Vietnam Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @stack('styles')

    @vite([
        'resources/css/admin-dashboard.css'
    ])

    <style>
        /* ==========================================================================
           Sidebar Navigation Styles (Single Active Menu & Accordion)
           ========================================================================== */
        .sidebar .menu-item {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 12px !important;
            padding: 10px 14px !important;
            margin-bottom: 4px !important;
            border-radius: 8px !important;
            color: #344054 !important;
            background-color: transparent !important;
            text-decoration: none !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            transition: all 0.2s ease-in-out !important;
            white-space: nowrap !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-item span {
            font-size: 15px !important;
            font-weight: 700 !important;
            color: inherit !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
        }

        .sidebar .menu-item .menu-icon {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
            flex-shrink: 0 !important;
            color: #344054 !important;
            stroke: currentColor !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: color 0.2s ease-in-out !important;
        }

        /* Active Main Menu Item (e.g. Tổng quan active) */
        .sidebar .menu-item.active {
            background-color: #FDEAF3 !important;
            color: #EC3D8F !important;
            border-radius: 8px !important;
        }

        .sidebar .menu-item.active .menu-icon {
            color: #EC3D8F !important;
            stroke: #EC3D8F !important;
        }

        .sidebar .menu-item.active span {
            color: #EC3D8F !important;
        }

        /* Hover Main Menu Item */
        .sidebar .menu-item:hover {
            background-color: #FDEAF3 !important;
            color: #EC3D8F !important;
            border-radius: 8px !important;
        }

        .sidebar .menu-item:hover .menu-icon {
            color: #EC3D8F !important;
            stroke: #EC3D8F !important;
        }

        .sidebar .menu-item:hover span {
            color: #EC3D8F !important;
        }

        /* Main Menu Parent Button */
        .sidebar .menu-parent {
            width: 100% !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 10px 14px !important;
            margin-bottom: 4px !important;
            border-radius: 8px !important;
            color: #344054 !important;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            cursor: pointer !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            font-family: inherit !important;
            text-align: left !important;
            transition: all 0.2s ease-in-out !important;
            white-space: nowrap !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-parent:focus,
        .sidebar .menu-parent:focus-visible,
        .sidebar .menu-item:focus,
        .sidebar .menu-item:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        .sidebar .menu-parent-left {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 12px !important;
            flex: 1 !important;
            min-width: 0 !important;
        }

        .sidebar .menu-parent-left span {
            font-size: 15px !important;
            font-weight: 700 !important;
            color: inherit !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
        }

        .sidebar .menu-parent-left .menu-icon {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
            flex-shrink: 0 !important;
            color: #344054 !important;
            stroke: currentColor !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: color 0.2s ease-in-out !important;
        }

        .sidebar .menu-chevron {
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            flex-shrink: 0 !important;
            color: #98A2B3 !important;
            stroke: currentColor !important;
            margin-left: auto !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transform: rotate(0deg) !important;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s ease-in-out !important;
        }

        /* Active / Open Main Menu Parent Button */
        .sidebar .menu-parent.active,
        .sidebar .menu-group.open > .menu-parent {
            background-color: #FDEAF3 !important;
            color: #EC3D8F !important;
            border-radius: 8px !important;
        }

        .sidebar .menu-parent.active .menu-parent-left span,
        .sidebar .menu-group.open > .menu-parent .menu-parent-left span {
            color: #EC3D8F !important;
        }

        .sidebar .menu-parent.active .menu-icon,
        .sidebar .menu-parent.active .menu-chevron,
        .sidebar .menu-group.open > .menu-parent .menu-icon,
        .sidebar .menu-group.open > .menu-parent .menu-chevron {
            color: #EC3D8F !important;
            stroke: #EC3D8F !important;
        }

        /* Open group rotates chevron downwards */
        .sidebar .menu-group.open .menu-chevron {
            transform: rotate(90deg) !important;
            color: #EC3D8F !important;
            stroke: #EC3D8F !important;
        }

        /* Hover Main Menu Parent Button */
        .sidebar .menu-parent:hover {
            background-color: #FDEAF3 !important;
            color: #EC3D8F !important;
            border-radius: 8px !important;
        }

        .sidebar .menu-parent:hover .menu-parent-left span {
            color: #EC3D8F !important;
        }

        .sidebar .menu-parent:hover .menu-icon,
        .sidebar .menu-parent:hover .menu-chevron {
            color: #EC3D8F !important;
            stroke: #EC3D8F !important;
        }

        /* Submenu Lists and Items */
        .sidebar .submenu-list {
            margin-top: 2px !important;
            margin-bottom: 6px !important;
        }

        .sidebar .submenu-item {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            margin-left: 32px !important;
            margin-right: 6px !important;
            margin-bottom: 2px !important;
            padding: 8px 12px !important;
            border-radius: 6px !important;
            color: #475467 !important;
            text-decoration: none !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            transition: all 0.15s ease-in-out !important;
            white-space: nowrap !important;
        }

        .sidebar .submenu-item:hover {
            background-color: #FDEAF3 !important;
            color: #EC3D8F !important;
            font-weight: 600 !important;
        }

        .sidebar .submenu-item.active {
            background-color: #FDEAF3 !important;
            color: #EC3D8F !important;
            font-weight: 600 !important;
        }
    </style>
</head>

<body>
<div class="admin-layout">

    <aside class="sidebar">
        <div>
            <div class="brand">
                <img src="{{ asset('img/logo.png') }}" alt="Logo" class="brand-logo">
                <div>
                    <h2>RoseShop</h2>
                    <p class="brand-sub">FLOWER OF YOU</p>
                </div>
            </div>

            @php
                $isAdmin = Auth::user()->vai_tro === 'ADMIN';

                // Bán hàng: don-hang, hoa-don, admin.thanh-toan
                $isBanHangActive = request()->routeIs('don-hang.*') 
                                || request()->routeIs('hoa-don.*') 
                                || request()->routeIs('admin.thanh-toan*');

                // Sản phẩm & Kho: san-pham, phieu-nhap, and placeholders
                $isSanPhamKhoActive = request()->routeIs('san-pham.*') 
                                   || request()->routeIs('phieu-nhap.*') 
                                   || request()->routeIs('admin.mau-bo-hoa*') 
                                   || request()->routeIs('admin.nguyen-vat-lieu*') 
                                   || request()->routeIs('admin.nha-cung-cap*') 
                                   || request()->routeIs('admin.lo-hang*') 
                                   || request()->routeIs('admin.ton-kho*');

                // Người dùng: nguoi-dung, khach-hang, nhan-vien, admin.shipper
                $isNguoiDungActive = request()->routeIs('nguoi-dung.*') 
                                  || request()->routeIs('khach-hang.*') 
                                  || request()->routeIs('nhan-vien.*') 
                                  || request()->routeIs('admin.shipper*');

                // Giao hàng: admin.giao-hang.*
                $isGiaoHangActive = request()->routeIs('admin.giao-hang.*');

                // Báo cáo & Thống kê: bao-cao.*, admin.bao-cao.*
                $isBaoCaoActive = request()->routeIs('bao-cao.*') 
                               || request()->routeIs('admin.bao-cao.*');

                // Hệ thống: admin.he-thong.*
                $isHeThongActive = request()->routeIs('admin.he-thong.*');

                // Kiểm tra xem có bất kỳ menu con/nhóm nào đang active không
                $hasAnyGroupActive = $isBanHangActive || $isSanPhamKhoActive || $isNguoiDungActive 
                                  || $isGiaoHangActive || $isBaoCaoActive || $isHeThongActive;

                // "Tổng quan" CHỈ active khi ở route admin.dashboard VÀ không có menu cha nào khác active
                $isTongQuanActive = request()->routeIs('admin.dashboard') && !$hasAnyGroupActive;
            @endphp

            <nav class="menu">
                {{-- Tổng quan --}}
                <a href="{{ route('admin.dashboard') }}" 
                   class="menu-item {{ $isTongQuanActive ? 'active' : '' }}" 
                   id="menu-tong-quan">
                    <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Tổng quan</span>
                </a>

                {{-- Bán hàng --}}
                <div class="menu-group {{ $isBanHangActive ? 'open' : '' }}" id="group-ban-hang">
                    <button type="button" class="menu-parent {{ $isBanHangActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                        <div class="menu-parent-left">
                            <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="8" cy="21" r="1"/>
                                <circle cx="19" cy="21" r="1"/>
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                            </svg>
                            <span>Bán hàng</span>
                        </div>
                        <svg class="menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </button>
                    <div class="submenu-list" style="{{ $isBanHangActive ? 'display: block;' : 'display: none;' }}">
                        <a href="{{ route('don-hang.index') }}" class="submenu-item {{ request()->routeIs('don-hang.*') ? 'active' : '' }}">
                            Quản lý đơn hàng
                        </a>
                        <a href="{{ route('hoa-don.index') }}" class="submenu-item {{ request()->routeIs('hoa-don.*') ? 'active' : '' }}">
                            Quản lý hóa đơn
                        </a>
                        @if($isAdmin)
                            <a href="{{ route('admin.thanh-toan') }}" class="submenu-item {{ request()->routeIs('admin.thanh-toan*') ? 'active' : '' }}">
                                Thanh toán
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Sản phẩm & Kho --}}
                <div class="menu-group {{ $isSanPhamKhoActive ? 'open' : '' }}" id="group-san-pham-kho">
                    <button type="button" class="menu-parent {{ $isSanPhamKhoActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                        <div class="menu-parent-left">
                            <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m7.5 4.27 9 5.15"/>
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                <path d="M12 22V12"/>
                            </svg>
                            <span>Sản phẩm & Kho</span>
                        </div>
                        <svg class="menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </button>
                    <div class="submenu-list" style="{{ $isSanPhamKhoActive ? 'display: block;' : 'display: none;' }}">
                        <a href="{{ route('san-pham.index') }}" class="submenu-item {{ request()->routeIs('san-pham.*') ? 'active' : '' }}">
                            Sản phẩm
                        </a>
                        @if($isAdmin)
                            <a href="{{ route('admin.mau-bo-hoa') }}" class="submenu-item {{ request()->routeIs('admin.mau-bo-hoa*') ? 'active' : '' }}">
                                Mẫu bó hoa
                            </a>
                            <a href="{{ route('admin.nguyen-vat-lieu') }}" class="submenu-item {{ request()->routeIs('admin.nguyen-vat-lieu*') ? 'active' : '' }}">
                                Nguyên vật liệu
                            </a>
                        @endif
                        <a href="{{ route('phieu-nhap.index') }}" class="submenu-item {{ request()->routeIs('phieu-nhap.*') ? 'active' : '' }}">
                            Phiếu nhập hàng
                        </a>
                        <a href="{{ route('admin.nha-cung-cap') }}" class="submenu-item {{ request()->routeIs('admin.nha-cung-cap*') ? 'active' : '' }}">
                            Nhà cung cấp
                        </a>
                        <a href="{{ route('admin.lo-hang') }}" class="submenu-item {{ request()->routeIs('admin.lo-hang*') ? 'active' : '' }}">
                            Lô hàng
                        </a>
                        @if($isAdmin)
                            <a href="{{ route('admin.ton-kho') }}" class="submenu-item {{ request()->routeIs('admin.ton-kho*') ? 'active' : '' }}">
                                Tồn kho
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Người dùng (ADMIN only) --}}
                @if($isAdmin)
                    <div class="menu-group {{ $isNguoiDungActive ? 'open' : '' }}" id="group-nguoi-dung">
                        <button type="button" class="menu-parent {{ $isNguoiDungActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                            <div class="menu-parent-left">
                                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                                <span>Người dùng</span>
                            </div>
                            <svg class="menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                        <div class="submenu-list" style="{{ $isNguoiDungActive ? 'display: block;' : 'display: none;' }}">
                            <a href="{{ route('nguoi-dung.index') }}" class="submenu-item {{ request()->routeIs('nguoi-dung.*') ? 'active' : '' }}">
                                Tài khoản hệ thống
                            </a>
                            <a href="{{ route('khach-hang.index') }}" class="submenu-item {{ request()->routeIs('khach-hang.*') ? 'active' : '' }}">
                                Khách hàng
                            </a>
                            <a href="{{ route('nhan-vien.index') }}" class="submenu-item {{ request()->routeIs('nhan-vien.*') ? 'active' : '' }}">
                                Nhân viên
                            </a>
                            <a href="{{ route('admin.shipper.index') }}" class="submenu-item {{ request()->routeIs('admin.shipper*') ? 'active' : '' }}">
                                Shipper
                            </a>
                        </div>
                    </div>

                    {{-- Giao hàng (ADMIN only) --}}
                    <div class="menu-group {{ $isGiaoHangActive ? 'open' : '' }}" id="group-giao-hang">
                        <button type="button" class="menu-parent {{ $isGiaoHangActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                            <div class="menu-parent-left">
                                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                                    <path d="M15 18H9"/>
                                    <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                                    <circle cx="17" cy="18" r="2"/>
                                    <circle cx="7" cy="18" r="2"/>
                                </svg>
                                <span>Giao hàng</span>
                            </div>
                            <svg class="menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                        <div class="submenu-list" style="{{ $isGiaoHangActive ? 'display: block;' : 'display: none;' }}">
                            <a href="{{ route('admin.giao-hang.phan-cong') }}" class="submenu-item {{ request()->routeIs('admin.giao-hang.phan-cong*') ? 'active' : '' }}">
                                Phân công giao hàng
                            </a>
                            <a href="{{ route('admin.giao-hang.theo-doi') }}" class="submenu-item {{ request()->routeIs('admin.giao-hang.theo-doi*') ? 'active' : '' }}">
                                Theo dõi giao hàng
                            </a>
                            <a href="{{ route('admin.giao-hang.hang-hoan') }}" class="submenu-item {{ request()->routeIs('admin.giao-hang.hang-hoan*') ? 'active' : '' }}">
                                Hàng hoàn
                            </a>
                            <a href="{{ route('admin.giao-hang.doi-soat-cod') }}" class="submenu-item {{ request()->routeIs('admin.giao-hang.doi-soat-cod*') ? 'active' : '' }}">
                                Đối soát COD
                            </a>
                        </div>
                    </div>

                    {{-- Báo cáo & Thống kê (ADMIN only) --}}
                    <div class="menu-group {{ $isBaoCaoActive ? 'open' : '' }}" id="group-bao-cao">
                        <button type="button" class="menu-parent {{ $isBaoCaoActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                            <div class="menu-parent-left">
                                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18"/>
                                    <path d="m19 9-5 5-4-4-3 3"/>
                                </svg>
                                <span>Báo cáo & Thống kê</span>
                            </div>
                            <svg class="menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                        <div class="submenu-list" style="{{ $isBaoCaoActive ? 'display: block;' : 'display: none;' }}">
                            <a href="{{ route('bao-cao.doanh-thu') }}" class="submenu-item {{ request()->routeIs('bao-cao.doanh-thu*') ? 'active' : '' }}">
                                Doanh thu & lợi nhuận
                            </a>
                            <a href="{{ route('bao-cao.san-pham') }}" class="submenu-item {{ request()->routeIs('bao-cao.san-pham*') ? 'active' : '' }}">
                                Sản phẩm
                            </a>
                            <a href="{{ route('admin.bao-cao.kho') }}" class="submenu-item {{ request()->routeIs('admin.bao-cao.kho*') ? 'active' : '' }}">
                                Kho
                            </a>
                            <a href="{{ route('admin.bao-cao.giao-hang') }}" class="submenu-item {{ request()->routeIs('admin.bao-cao.giao-hang*') ? 'active' : '' }}">
                                Hoạt động giao hàng
                            </a>
                        </div>
                    </div>

                    {{-- Hệ thống (ADMIN only) --}}
                    <div class="menu-group {{ $isHeThongActive ? 'open' : '' }}" id="group-he-thong">
                        <button type="button" class="menu-parent {{ $isHeThongActive ? 'active' : '' }}" onclick="toggleSubmenu(this)">
                            <div class="menu-parent-left">
                                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <span>Hệ thống</span>
                            </div>
                            <svg class="menu-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                        <div class="submenu-list" style="{{ $isHeThongActive ? 'display: block;' : 'display: none;' }}">
                            <a href="{{ route('admin.he-thong.thong-bao') }}" class="submenu-item {{ request()->routeIs('admin.he-thong.thong-bao*') ? 'active' : '' }}">
                                Thông báo
                            </a>
                            <a href="{{ route('admin.he-thong.cau-hinh') }}" class="submenu-item {{ request()->routeIs('admin.he-thong.cau-hinh*') ? 'active' : '' }}">
                                Cấu hình hệ thống
                            </a>
                        </div>
                    </div>
                @endif
            </nav>
        </div>

        <div class="admin-footer">
            <div>
                <p class="admin-name">{{ Auth::user()->ten_dang_nhap }}</p>
                <p class="admin-role">{{ Auth::user()->vai_tro }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Đăng xuất</button>
            </form>
        </div>
    </aside>

    <main class="content admin-page-content">
        @yield('content')
    </main>

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allGroups = document.querySelectorAll('.sidebar .menu-group');
            const tongQuanItem = document.getElementById('menu-tong-quan');
            const isDashboard = {{ request()->routeIs('admin.dashboard') ? 'true' : 'false' }};

            // Accordion toggle & active state handling
            window.toggleSubmenu = function(button) {
                const currentGroup = button.closest('.menu-group');
                if (!currentGroup) return;

                const isCurrentlyOpen = currentGroup.classList.contains('open');

                // 1. Accordion: Đóng TẤT CẢ các nhóm menu khác và bỏ trạng thái active của chúng
                allGroups.forEach(group => {
                    if (group !== currentGroup) {
                        group.classList.remove('open');
                        const submenu = group.querySelector('.submenu-list');
                        if (submenu) submenu.style.display = 'none';
                        const parentBtn = group.querySelector('.menu-parent');
                        if (parentBtn) parentBtn.classList.remove('active');
                    }
                });

                // 2. Xử lý nhóm vừa click
                if (isCurrentlyOpen) {
                    // Nếu nhóm này đang mở -> đóng lại
                    currentGroup.classList.remove('open');
                    const submenu = currentGroup.querySelector('.submenu-list');
                    if (submenu) submenu.style.display = 'none';
                    button.classList.remove('active');

                    // Nếu đóng hết tất cả nhóm và đang ở trang Dashboard -> khôi phục active cho Tổng quan
                    const anyGroupOpen = document.querySelector('.sidebar .menu-group.open');
                    if (!anyGroupOpen && isDashboard && tongQuanItem) {
                        tongQuanItem.classList.add('active');
                    }
                } else {
                    // Nếu nhóm này đang đóng -> mở ra và gán trạng thái active màu hồng
                    currentGroup.classList.add('open');
                    const submenu = currentGroup.querySelector('.submenu-list');
                    if (submenu) submenu.style.display = 'block';
                    button.classList.add('active');

                    // Tắt active ở Tổng quan
                    if (tongQuanItem) {
                        tongQuanItem.classList.remove('active');
                    }
                }
            };

            // Khi click Tổng quan: đóng tất cả các submenu đang mở
            if (tongQuanItem) {
                tongQuanItem.addEventListener('click', function() {
                    allGroups.forEach(group => {
                        group.classList.remove('open');
                        const submenu = group.querySelector('.submenu-list');
                        if (submenu) submenu.style.display = 'none';
                        const parentBtn = group.querySelector('.menu-parent');
                        if (parentBtn) parentBtn.classList.remove('active');
                    });
                    tongQuanItem.classList.add('active');
                });
            }

            // Tự động kết thúc / đóng mọi thông báo alert sau 3 giây (3s)
            function setupAutoDismiss(alertEl) {
                if (!alertEl || alertEl.dataset.autoDismissed) return;
                alertEl.dataset.autoDismissed = 'true';
                setTimeout(function() {
                    alertEl.style.transition = 'opacity 0.5s ease, transform 0.5s ease, max-height 0.5s ease, margin 0.5s ease, padding 0.5s ease';
                    alertEl.style.opacity = '0';
                    alertEl.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                            try {
                                const bsAlert = bootstrap.Alert.getInstance(alertEl) || new bootstrap.Alert(alertEl);
                                bsAlert.close();
                            } catch (e) {
                                alertEl.remove();
                            }
                        } else {
                            alertEl.remove();
                        }
                    }, 500);
                }, 3000);
            }

            // Kích hoạt cho các alert có sẵn khi tải trang
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(setupAutoDismiss);

            // Theo dõi và tự động áp dụng cho các alert được thêm động vào DOM
            const alertObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            if (node.classList && node.classList.contains('alert') && !node.classList.contains('alert-permanent')) {
                                setupAutoDismiss(node);
                            } else if (node.querySelectorAll) {
                                node.querySelectorAll('.alert:not(.alert-permanent)').forEach(setupAutoDismiss);
                            }
                        }
                    });
                });
            });

            alertObserver.observe(document.body, { childList: true, subtree: true });
        });
    </script>
</div>
</body>
</html>