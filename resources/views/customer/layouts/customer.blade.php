<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet"
        href="{{ asset('css/Customer/Dashboard.css') }}?v={{ filemtime(public_path('css/Customer/Dashboard.css')) }}">
    <title>@yield('title', '🌸 Cửa Hàng Hoa Tươi')</title>
    <!-- Google Fonts: Be Vietnam Pro (Đồng bộ font chữ chung giống Admin) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap"
        rel="stylesheet">

    <style>
        /* ===== Đồng bộ Font chữ Be Vietnam Pro toàn hệ thống giống Admin ===== */
        :root {
            --font-main: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --rs-font: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        html,
        body,
        button,
        input,
        select,
        textarea {
            font-family: var(--font-main) !important;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        label {
            font-family: var(--font-main);
        }

        /* Bảo vệ bộ icon Font Awesome không bị ghi đè font chữ */
        .fa,
        .fas,
        .far,
        .fal,
        .fad,
        .fab,
        .fa-solid,
        .fa-regular,
        .fa-brands,
        [class*="fa-"],
        [class^="fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", "FontAwesome" !important;
        }

        .fa::before,
        .fas::before,
        .far::before,
        .fal::before,
        .fad::before,
        .fab::before,
        .fa-solid::before,
        .fa-regular::before,
        .fa-brands::before,
        [class*="fa-"]::before,
        [class^="fa-"]::before {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", "FontAwesome" !important;
        }

        /* ===== Sticky Header Wrapper (Cố định toàn bộ Header khi lướt web) ===== */
        .site-header-wrapper {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(142, 53, 85, .08);
            transition: box-shadow .2s ease;
        }

        .site-header-wrapper.is-scrolled {
            box-shadow: 0 8px 24px rgba(142, 53, 85, .16);
        }

        /* ===== Navigation Bar & Dropdown "MUA BÓ HOA" ===== */
        #catNav .nav-dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        #catNav .nav-dropdown .dropdown-toggle {
            background: none;
            border: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            line-height: inherit;
            color: var(--dark, #33282e);
            padding: 15px 0;
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            position: relative;
            box-shadow: none;
            outline: none;
            transition: color .2s ease;
            text-decoration: none;
        }

        #catNav .nav-dropdown .dropdown-toggle .nav-chevron {
            font-size: 11px;
            transition: transform .25s ease;
            pointer-events: none;
        }

        #catNav .nav-dropdown:hover .dropdown-toggle,
        #catNav .nav-dropdown.show .dropdown-toggle,
        #catNav .nav-dropdown.is-open .dropdown-toggle,
        #catNav .nav-dropdown .dropdown-toggle.active {
            color: var(--primary, #e8436a);
        }

        #catNav .nav-dropdown:hover .dropdown-toggle .nav-chevron,
        #catNav .nav-dropdown.show .dropdown-toggle .nav-chevron,
        #catNav .nav-dropdown.is-open .dropdown-toggle .nav-chevron {
            transform: rotate(180deg);
        }

        /* Đồng bộ đường kẻ hồng với "HOA TƯƠI" và các menu khác */
        #catNav .nav-dropdown .dropdown-toggle::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 3px;
            background: var(--primary, #e8436a);
            transform: scaleX(0);
            transition: transform .2s ease;
        }

        #catNav .nav-dropdown:hover .dropdown-toggle::after,
        #catNav .nav-dropdown.show .dropdown-toggle::after,
        #catNav .nav-dropdown.is-open .dropdown-toggle::after,
        #catNav .nav-dropdown .dropdown-toggle.active::after {
            transform: scaleX(1);
        }

        #catNav .nav-dropdown .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            border: 1px solid #fce7f3;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(232, 67, 106, .18);
            min-width: 220px;
            padding: 8px;
            z-index: 1000;
            margin-top: 2px;
        }

        /* Cầu vô hình (bridge) nối liền nút và menu dropdown để di chuyển chuột không bị rớt hover */
        #catNav .nav-dropdown .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: transparent;
        }

        #catNav .nav-dropdown.show .dropdown-menu,
        #catNav .nav-dropdown.is-open .dropdown-menu,
        #catNav .nav-dropdown:hover .dropdown-menu {
            display: block;
            animation: catNavDropdownFadeIn .18s ease-out;
        }

        @keyframes catNavDropdownFadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -6px);
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        #catNav .nav-dropdown .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            color: #33282e;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: all .15s ease;
        }

        #catNav .nav-dropdown .dropdown-menu a:hover {
            background: #fff1f6;
            color: var(--primary, #e8436a);
            padding-left: 18px;
        }

        /* Chấm đỏ thông báo chưa đọc được định nghĩa đồng bộ trong Dashboard.css */
    </style>
    @yield('head-styles')
</head>

<body data-page="@yield('page-id', 'other')">

    <div id="page-transition">🌸</div>

    {{-- STICKY SITE HEADER (Cố định toàn bộ Topbar, Header và Nav) --}}
    <div class="site-header-wrapper" id="siteHeaderWrapper">
        {{-- TOP BAR --}}
        <div class="topbar">
            <div class="topbar-left">
                <span>📞 Hotline: 0964023510</span>
                <span>💬 Zalo: <a href="https://zalo.me/0357634696" target="_blank">0357 634 696</a></span>
            </div>

            <div class="topbar-right">
                <a href="https://www.facebook.com/duong.manh.19423" target="_blank">📘 Facebook</a>
                <span>🕐 Phục vụ 24/7</span>
            </div>
        </div>

        {{-- HEADER --}}
        <header class="customer-header">
            <a href="{{ route('customer.dashboard') }}" class="logo" id="logoLink">
                <img src="{{ asset('img/logo.png') }}" alt="Logo" class="logo-img">

                <div class="logo-text">
                    <span>RoseShop</span>
                    <span>Chuyên Các Loại Hoa Tươi</span>
                </div>
            </a>

            <div class="search-wrap">
                <input type="text" id="searchInput" placeholder="Tìm sản phẩm... ">
                <button type="button" class="search-btn" onclick="handleSearch()">Tìm kiếm</button>
            </div>

            @php
                // ✅ Khôi phục giỏ hàng từ DB nếu người dùng đã đăng nhập và session đang trống
                if (auth()->check() && empty(session('gio_hang')) && empty(session('gio_bo_hoa'))) {
                    \App\Services\CartService::restoreFromDatabase(auth()->user());
                }
                $soItemGioHang = count(session('gio_hang', []));
                $soItemGioBoHoa = count(session('gio_bo_hoa', []));
                $tongItemGio = $soItemGioHang + $soItemGioBoHoa;
            @endphp

            <div class="header-right">
                @auth
                    <div class="header-actions">

                        <!-- 1. Chuông thông báo -->
                        <div class="header-action-item">
                            <a href="{{ route('customer.thong-bao') }}" class="header-action-btn notification-item"
                                title="Thông báo">
                                <i class="fa-regular fa-bell"></i>
                                <span class="header-badge notif-badge notif-dot" id="notifDot"></span>
                            </a>
                        </div>

                        <div class="header-action-divider"></div>

                        <!-- 2. Giỏ hàng -->
                        <div class="header-action-item">
                            <a href="{{ route('customer.gio-hang') }}" class="header-action-btn cart-icon-btn"
                                id="cartDropdownBtn" title="Giỏ hàng">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <span class="header-badge cart-badge" id="cartBadge">{{ $tongItemGio }}</span>
                            </a>
                        </div>
                        <div class="header-action-divider"></div>

                        <!-- <div class="header-action-item">
                        <div class="cart-dropdown-wrapper">
                            <button type="button" class="header-action-btn cart-icon-btn" id="cartDropdownBtn" title="Giỏ hàng">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <span class="header-badge cart-badge" id="cartBadge">{{ $tongItemGio }}</span>
                            </button>

                            <div class="cart-dropdown-menu" id="cartDropdownMenu">
                                <a href="{{ route('customer.gio-hang') }}">
                                    <i class="fa-solid fa-bag-shopping" style="color: #be185d;"></i>
                                    <span>Xem giỏ hàng</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="header-action-divider"></div> -->

                        <!-- 3. Tài khoản người dùng -->
                        <div class="header-action-item">
                            <a href="{{ route('customer.profile.edit') }}" class="header-user-btn user-profile-link"
                                title="Thông tin tài khoản">
                                <div class="header-user-avatar">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="header-user-info user-info">
                                    <span class="header-user-greeting">Xin chào,</span>
                                    <strong
                                        class="header-user-name">{{ $user->ten_dang_nhap ?? auth()->user()->ten_dang_nhap ?? '' }}</strong>
                                </div>
                            </a>
                        </div>

                        <div class="header-action-divider"></div>

                        <!-- 4. Nút Đăng xuất -->
                        <div class="header-action-item">
                            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                                @csrf
                                <button type="submit" class="header-logout-btn logout-btn" title="Đăng xuất">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                    <span>Đăng xuất</span>
                                </button>
                            </form>
                        </div>

                    </div>
                @else
                    <div class="guest-actions">
                        <a href="{{ route('login') }}" class="login-btn">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="register-btn">Đăng ký</a>
                    </div>
                @endauth
            </div>
        </header>

        {{-- MAIN NAV --}}
        <nav class="cat-nav" id="catNav">
            <a href="{{ route('customer.dashboard') }}"
                class="cat-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
                TRANG CHỦ
            </a>

            <div class="nav-dropdown" id="navDropdownBoHoa">
                <a href="{{ route('customer.bo-hoa.he-thong') }}" id="btnMuaBoHoa"
                    class="cat-link dropdown-toggle {{ request()->routeIs('customer.bo-hoa.*', 'customer.mau-bo-hoa.*', 'customer.bo-hoa-tu-tao.*', 'customer.bo-hoa.gio.*') ? 'active' : '' }}">
                    <span>MUA BÓ HOA</span>
                    <i class="fa-solid fa-chevron-down nav-chevron"></i>
                </a>
                <div class="dropdown-menu" id="dropdownMenuBoHoa">
                    <a href="{{ route('customer.bo-hoa.he-thong') }}" class="dropdown-cat">💐 Bó hoa đề xuất</a>
                    <a href="{{ route('customer.bo-hoa.tu-tao') }}" class="dropdown-cat">✿ Tự tạo bó hoa</a>
                </div>
            </div>

            <a href="{{ route('customer.hoa-tuoi') }}"
                class="cat-link {{ request()->routeIs('customer.hoa-tuoi') ? 'active' : '' }}">
                HOA TƯƠI
            </a>

            <a href="{{ route('customer.phu-kien') }}"
                class="cat-link {{ request()->routeIs('customer.phu-kien') ? 'active' : '' }}">
                PHỤ KIỆN
            </a>

            <a href="{{ route('customer.qua-tang') }}"
                class="cat-link {{ request()->routeIs('customer.qua-tang') ? 'active' : '' }}">
                QUÀ TẶNG
            </a>

            <a href="{{ route('customer.gioi-thieu') }}"
                class="cat-link {{ request()->routeIs('customer.gioi-thieu') ? 'active' : '' }}">
                GIỚI THIỆU
            </a>

            <a href="{{ route('customer.tin-tuc') }}"
                class="cat-link {{ request()->routeIs('customer.tin-tuc') || request()->routeIs('customer.tin-tuc.chi-tiet') ? 'active' : '' }}">
                TIN TỨC
            </a>

            <a href="{{ route('customer.lien-he') }}"
                class="cat-link {{ request()->routeIs('customer.lien-he') ? 'active' : '' }}">
                LIÊN HỆ
            </a>
        </nav>
    </div>



    @yield('content')

    {{-- FOOTER --}}
    <footer class="rose-footer">
        <div class="rose-footer-main">
            <div class="rose-footer-col footer-brand">
                <div class="footer-logo" id="footerLogo">
                    <img src="{{ asset('img/logo.png') }}" alt="RoseShop">
                    <h3>RoseShop</h3>
                </div>
                <p>Chuyên cung cấp hoa tươi chất lượng cao, giao hàng nhanh chóng và tận tâm.</p>

                <div class="footer-social">
                    <a href="#">f</a>
                    <a href="#">▶</a>
                    <a href="#">◎</a>
                </div>
            </div>

            <div class="rose-footer-col">
                <h4>THÔNG TIN LIÊN HỆ</h4>
                <p>📍 123 Nguyễn Trãi, Thanh Xuân, Hà Nội</p>
                <p>📞 0357 634 696</p>
                <p>✉️ roseshop@gmail.com</p>
                <p>🕘 7:00 - 22:00 (Tất cả các ngày)</p>
            </div>

            <div class="rose-footer-col">
                <h4>DANH MỤC</h4>
                <a href="{{ route('customer.dashboard') }}">Trang chủ</a>
                <a href="{{ route('customer.bo-hoa.he-thong') }}">Mua bó hoa</a>
                <a href="{{ route('customer.hoa-tuoi') }}">Hoa tươi</a>
                <a href="{{ route('customer.phu-kien') }}">Phụ kiện</a>
                <a href="{{ route('customer.tin-tuc') }}">Tin tức</a>
                <a href="{{ route('customer.gioi-thieu') }}">Giới thiệu</a>
                <a href="{{ route('customer.lien-he') }}">Liên hệ</a>
            </div>

            <div class="rose-footer-col">
                <h4>CHÍNH SÁCH</h4>
                <a href="#">Chính sách giao hàng</a>
                <a href="#">Chính sách đổi trả</a>
                <a href="#">Chính sách bảo mật</a>
                <a href="#">Điều khoản sử dụng</a>
            </div>

            <div class="rose-footer-col footer-newsletter">
                <h4>ĐĂNG KÝ NHẬN TIN</h4>
                <p>Nhận ưu đãi và thông tin mới nhất từ RoseShop.</p>

                <form>
                    <input type="email" placeholder="Nhập email của bạn">
                    <button type="button">Đăng ký</button>
                </form>
            </div>
        </div>

        <div class="rose-footer-bottom">
            <p>© {{ date('Y') }} RoseShop. All Rights Reserved.</p>

            <div class="payment-icons">
                <span>VISA</span>
                <span>🔴🟠</span>
                <span>mastercard</span>
                <span>momo</span>
                <span>ZaloPay</span>
            </div>
        </div>
    </footer>

    <script>
        const PAGE = document.body.dataset.page;
        const DASH_URL = "{{ route('customer.dashboard') }}";
        const overlay = document.getElementById('page-transition');

        function navigateTo(url) {
            overlay.classList.add('active');
            setTimeout(() => { window.location.href = url; }, 220);
        }

        document.getElementById('logoLink').addEventListener('click', e => {
            if (PAGE === 'dashboard') {
                e.preventDefault();
                if (typeof filterCat === 'function') filterCat('', null);
                if (typeof setSearchVal === 'function') setSearchVal('');
                document.getElementById('searchInput').value = '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                e.preventDefault();
                navigateTo(DASH_URL);
            }
        });

        document.getElementById('footerLogo').addEventListener('click', e => {
            if (PAGE === 'dashboard') {
                e.preventDefault();
                if (typeof filterCat === 'function') filterCat('', null);
                if (typeof setSearchVal === 'function') setSearchVal('');
                document.getElementById('searchInput').value = '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                e.preventDefault();
                navigateTo(DASH_URL);
            }
        });

        document.querySelectorAll('#catNav [data-cat]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();

                const cat = btn.dataset.cat;

                if (PAGE === 'dashboard') {
                    document.querySelectorAll('#catNav [data-cat]')
                        .forEach(b => b.classList.remove('active'));

                    btn.classList.add('active');

                    if (typeof filterCat === 'function') {
                        filterCat(cat, null);
                    }

                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    navigateTo(DASH_URL + '?cat=' + encodeURIComponent(cat));
                }
            });
        });

        function handleSearch() {
            const input = document.getElementById('searchInput');
            const val = input ? input.value.trim() : '';

            window.location.href = val
                ? DASH_URL + '?q=' + encodeURIComponent(val)
                : DASH_URL;
        }

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleSearch();
                }
            });
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            const msgEl = document.getElementById('toastMsg');
            if (msgEl) msgEl.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        const cartBtn = document.getElementById('cartDropdownBtn');
        const cartMenu = document.getElementById('cartDropdownMenu');
        if (cartBtn && cartMenu) {
            cartBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                cartMenu.classList.toggle('show');
            });
            document.addEventListener('click', (e) => {
                if (!cartBtn.contains(e.target) && !cartMenu.contains(e.target)) {
                    cartMenu.classList.remove('show');
                }
            });
        }

        window.updateCartBadge = function (count) {
            const badge = document.getElementById('cartBadge');
            if (badge) {
                badge.textContent = count || 0;
            }
        };

        function showSuccessToast(message) {
            const toast = document.getElementById('success-toast');
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // ✅ Kiểm tra thông báo chưa đọc → hiện số lượng/chấm đỏ trên chuông
        @auth
            (function checkNotifDot() {
                fetch('/customer/thong-bao/so-chua-doc', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(r => r.json())
                    .then(data => {
                        const dot = document.getElementById('notifDot');
                        if (!dot) return;
                        const count = data.count || data.so_chua_doc || 0;
                        if (count > 0) {
                            dot.textContent = count > 99 ? '99+' : count;
                            dot.classList.add('has-notif');
                        } else {
                            dot.textContent = '';
                            dot.classList.remove('has-notif');
                        }
                    })
                    .catch(() => { });
            })();
        @endauth
        // ✅ Menu dropdown "MUA BÓ HOA" - Rê chuột hiển thị tùy chọn, bấm trực tiếp trỏ đến trang Bó hoa đề xuất
        (function initNavDropdownBoHoa() {
            const navDropdown = document.getElementById('navDropdownBoHoa');
            if (!navDropdown) return;

            let hoverTimer = null;

            // Rê chuột vào: hiển thị ngay và hủy hẹn giờ đóng
            navDropdown.addEventListener('mouseenter', function () {
                if (hoverTimer) {
                    clearTimeout(hoverTimer);
                    hoverTimer = null;
                }
                navDropdown.classList.add('is-open');
            });

            // Rê chuột ra: chờ 200ms mới đóng (tránh nhạy/chớp giật)
            navDropdown.addEventListener('mouseleave', function () {
                hoverTimer = setTimeout(function () {
                    navDropdown.classList.remove('is-open');
                }, 200);
            });

            // Khi bấm vào 1 trong các mục dropdown thì đóng menu
            navDropdown.querySelectorAll('.dropdown-menu a').forEach(function (link) {
                link.addEventListener('click', function () {
                    navDropdown.classList.remove('is-open');
                });
            });
        })();
        // ✅ Tự động tăng độ bóng đổ cho Header cố định khi cuộn trang
        window.addEventListener('scroll', function () {
            const headerWrapper = document.getElementById('siteHeaderWrapper');
            if (headerWrapper) {
                if (window.scrollY > 8) {
                    headerWrapper.classList.add('is-scrolled');
                } else {
                    headerWrapper.classList.remove('is-scrolled');
                }
            }
        }, { passive: true });
    </script>

    @yield('scripts')

    <div id="success-toast" class="success-toast">
        ✅ Đã thêm sản phẩm vào giỏ hàng thành công
    </div>

    {{-- RoseBot AI Customer Support Widget --}}
    @include('components.chatbot-widget')

</body>

</html>