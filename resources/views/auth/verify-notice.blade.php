<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoseShop - Chờ xác thực email</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-white to-rose-50 min-h-screen flex items-center justify-center py-10 px-4">

<div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-8 border border-rose-100 text-center">
    
    <!-- Icon hoa hồng thư -->
    <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-2">Vui lòng kiểm tra email</h1>
    
    <!-- Thông báo trạng thái -->
    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm font-medium">
            {{ session('status') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl mb-5 text-sm">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl mb-5 text-sm">
            {{ session('info') }}
        </div>
    @endif

    @if ($errors->has('resend_email'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">
            {{ $errors->first('resend_email') }}
        </div>
    @endif

    <p class="text-gray-600 text-sm mb-4 leading-relaxed">
        Hệ thống đã tạo tài khoản của bạn ở trạng thái <strong>Chờ xác thực</strong>.<br>
        Một liên kết kích hoạt đã được gửi tới địa chỉ:
    </p>

    <div class="bg-rose-50 border border-rose-200 text-rose-700 font-semibold px-4 py-2.5 rounded-lg mb-6 text-sm inline-block max-w-full truncate">
        {{ $email ?: (session('email') ?: 'email của bạn') }}
    </div>

    <p class="text-xs text-gray-500 mb-6">
        Vui lòng mở hộp thư đến (hoặc kiểm tra mục Thư rác / Spam) và bấm vào liên kết để kích hoạt tài khoản.
    </p>

    {{-- Hỗ trợ khi test trên Local --}}
    @if (session('dev_verification_url'))
    <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-300 text-left text-xs text-amber-900 shadow-sm">
        <div class="font-bold flex items-center gap-1.5 mb-1 text-amber-800">
            <span>⚡ Tiện ích kiểm thử nhanh (Môi trường Local):</span>
        </div>
        <p class="mb-2.5 text-amber-700 leading-relaxed">
            Hệ thống đã gửi email kích hoạt. Nếu chưa kịp mở hòm thư, bạn cũng có thể bấm trực tiếp nút bên dưới để kích hoạt ngay tài khoản:
        </p>
        <a href="{{ session('dev_verification_url') }}"
           class="inline-block w-full text-center bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-3 rounded-lg text-xs transition shadow-sm">
            👉 Bấm vào đây để Xác thực tài khoản ngay
        </a>
    </div>
    @endif

    <!-- Form gửi lại email xác thực -->
    <div class="pt-6 border-t border-gray-100">
        <p class="text-xs text-gray-500 mb-3">Chưa nhận được email hoặc liên kết đã hết hạn?</p>
        
        <form method="POST" action="{{ route('verification.resend') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="email" value="{{ $email ?: session('email') }}">

            @if(empty($email) && !session('email'))
            <div>
                <input type="email" name="email" required placeholder="Nhập địa chỉ email của bạn"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 mb-2">
            </div>
            @endif

            <button type="submit" 
                class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-semibold py-2.5 rounded-lg transition text-sm">
                🔄 Gửi lại email xác thực
            </button>
        </form>

        <div class="mt-4 flex items-center justify-between text-xs text-gray-500 pt-3">
            <a href="{{ route('login') }}" class="text-rose-600 hover:underline font-medium">← Quay lại Đăng nhập</a>
            <a href="{{ route('register') }}" class="text-gray-500 hover:underline">Đăng ký tài khoản khác</a>
        </div>
    </div>

</div>

</body>
</html>
