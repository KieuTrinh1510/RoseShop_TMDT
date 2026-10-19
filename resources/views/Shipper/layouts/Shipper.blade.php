<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoseShop - Thông báo dành cho Shipper</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-white to-rose-50 min-h-screen flex items-center justify-center py-10 px-4">

<div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 text-center">
    <div class="text-5xl mb-4">📱</div>

    <h1 class="text-xl font-bold text-rose-600 mb-2">
        Web giao hàng đã ngừng hỗ trợ
    </h1>

    <p class="text-sm text-gray-600 leading-relaxed mb-6">
        Hệ thống không còn hỗ trợ trang web giao hàng cho shipper.
        Chức năng giao hàng đã được chuyển sang <strong>ứng dụng di động</strong>.
        Vui lòng tải ứng dụng về điện thoại để tiếp tục nhận và giao đơn.
    </p>

    {{-- Thay các link '#' bên dưới bằng link tải thật (CH Play / App Store / file APK) --}}
    <div class="space-y-3 mb-6">
        <a href="#"
           class="block w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
            ⬇️ Tải ứng dụng cho Android
        </a>
        <a href="#"
           class="block w-full bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2.5 rounded-lg transition text-sm">
            ⬇️ Tải ứng dụng cho iOS
        </a>
    </div>

    <a href="{{ route('login') }}"
       class="inline-block text-sm text-gray-600 hover:text-rose-600 underline font-medium">
        ← Quay về trang đăng nhập
    </a>
</div>

</body>
</html>