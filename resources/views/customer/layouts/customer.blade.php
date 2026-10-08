<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/Customer/Dashboard.css') }}">
    <title>@yield('title', '🌸 Cửa Hàng Hoa Tươi')</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        #catNav .nav-dropdown { position: relative; display: inline-block; }
        #catNav .nav-dropdown .dropdown-toggle {
            background: none; border: 0; cursor: pointer; font: inherit;
            color: inherit; padding: 0;
        }
        #catNav .nav-dropdown .dropdown-menu {
            display: none; position: absolute; top: 100%; left: 0;
            background: #fff; border: 1px solid #eadbe1; border-radius: 10px;
            box-shadow: 0 8px 24px rgba(142,53,85,.14);
            min-width: 220px; padding: 8px; z-index: 1000; margin-top: 6px;
        }
        #catNav .nav-dropdown:hover .dropdown-menu { display: block; }
        #catNav .nav-dropdown .dropdown-menu a {
            display: block; padding: 10px 14px; color: #33282e;
            text-decoration: none; border-radius: 6px; font-size: 14px;
            white-space: nowrap;
        }
        #catNav .nav-dropdown .dropdown-menu a:hover {
            background: #f8eef2; color: #8e3555;
        }

        /* ✅ Chấm đỏ thông báo chưa đọc */
        .notification-item { position: relative; display: inline-block; }
        .notif-dot {
            position: absolute; top: -2px; right: -4px;
            width: 8px; height: 8px; background: #D82677;
            border-radius: 50%; box-shadow: 0 0 0 2px #fff;
            display: none;
        }
        .notif-dot.has-notif { display: block; }
    </style>
    @yield('head-styles')
</head>

<body data-page="@yield('page-id', 'other')">

<div id="page-transition">🌸</div>

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
        // ✅ Đếm số lượng item trong cả 2 giỏ
        $soItemGioHang  = count(session('gio_hang', []));
        $soItemGioBoHoa = count(session('gio_bo_hoa', []));
        $tongItemGio    = $soItemGioHang + $soItemGioBoHoa;
    @endphp

    <div class="header-right">
        @auth
            <div class="header-actions">

                <div class="header-action-item">
                    <a href="{{ route('customer.thong-bao') }}" class="notification-item">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notif-dot" id="notifDot"></span>
                    </a>
                </div>

                <div class="header-action-item">
                    <div class="cart-dropdown-wrapper">
                        <button class="cart-icon-btn" id="cartDropdownBtn">
                            <span class="icon">🛒</span>
                            <span class="cart-badge" id="cartBadge">{{ $tongItemGio }}</span>
                        </button>

                        <div class="cart-dropdown-menu" id="cartDropdownMenu">
    <a href="{{ route('customer.gio-hang') }}">🛒 Xem giỏ hàng</a>

    @if($soItemGioBoHoa > 0)
        <a href="{{ route('customer.bo-hoa.gio.index') }}">
            💐 Giỏ bó hoa ({{ $soItemGioBoHoa }})
        </a>
    @endif
</div>
                    </div>
                </div>

                <div class="header-action-item">
                    <a href="{{ route('customer.profile.edit') }}" class="user-profile-link">
                        <span class="user-avatar">👤</span>
                        <div class="user-info">
                            <span>Xin chào,</span>
                            <strong>{{ $user->ten_dang_nhap ?? auth()->user()->ten_dang_nhap ?? '' }}</strong>
                        </div>
                    </a>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Đăng xuất</button>
                </form>

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

    <a href="{{ route('customer.hoa-tuoi') }}"
       class="cat-link {{ request()->routeIs('customer.hoa-tuoi') ? 'active' : '' }}">
        HOA TƯƠI
    </a>

    <div class="nav-dropdown">
        <button type="button"
                class="cat-link dropdown-toggle {{ request()->routeIs('customer.bo-hoa.*','customer.mau-bo-hoa.*','customer.bo-hoa-tu-tao.*','customer.bo-hoa.gio.*') ? 'active' : '' }}">
            Mua bó hoa ▾
        </button>
        <div class="dropdown-menu">
            <a href="{{ route('customer.bo-hoa.he-thong') }}" class="dropdown-cat">💐 Bó hoa đề xuất</a>
            <a href="{{ route('customer.bo-hoa.tu-tao') }}" class="dropdown-cat">✿ Tự tạo bó hoa</a>
            @auth
                @if($soItemGioBoHoa > 0)
                    <a href="{{ route('customer.bo-hoa.gio.index') }}" class="dropdown-cat">
                        🛒 Giỏ bó hoa ({{ $soItemGioBoHoa }})
                    </a>
                @endif
            @endauth
        </div>
    </div>

    <a href="{{ route('customer.phu-kien') }}"
       class="cat-link {{ request()->routeIs('customer.phu-kien') ? 'active' : '' }}">
        PHỤ KIỆN
    </a>

    <a href="{{ route('customer.qua-tang') }}"
       class="cat-link {{ request()->routeIs('customer.qua-tang') ? 'active' : '' }}">
        Quà tặng
    </a>

    <a href="{{ route('customer.gioi-thieu') }}"
       class="cat-link {{ request()->routeIs('customer.gioi-thieu') ? 'active' : '' }}">
        Giới thiệu
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
            <a href="{{ route('customer.hoa-tuoi') }}">Hoa tươi</a>
            <a href="{{ route('customer.phu-kien') }}">Phụ kiện</a>
            <a href="{{ route('customer.tin-tuc') }}">Tin tức</a>
            <a href="#">Giới thiệu</a>
            <a href="#">Liên hệ</a>
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
    const PAGE     = document.body.dataset.page;
    const DASH_URL = "{{ route('customer.dashboard') }}";
    const overlay  = document.getElementById('page-transition');

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
        const val   = input ? input.value.trim() : '';

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

    const cartBtn  = document.getElementById('cartDropdownBtn');
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

    // ✅ Kiểm tra thông báo chưa đọc → hiện chấm đỏ trên chuông
    @auth
    (function checkNotifDot() {
        fetch('/customer/thong-bao/so-chua-doc', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const dot = document.getElementById('notifDot');
            if (dot && data.count > 0) dot.classList.add('has-notif');
        })
        .catch(() => {});
    })();
    @endauth
</script>

@yield('scripts')

<div id="success-toast" class="success-toast">
    ✅ Đã thêm sản phẩm vào giỏ hàng thành công
</div>

</body>
</html>