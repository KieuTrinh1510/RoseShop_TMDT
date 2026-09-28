<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoseShop - {{ $title ?? 'Xác thực tài khoản' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-white to-rose-50 min-h-screen flex items-center justify-center py-10 px-4">

<div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-8 border border-rose-100 text-center">

    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm font-medium">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('resend_email'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">
            {{ $errors->first('resend_email') }}
        </div>
    @endif

    @if ($state === 'success')
        <!-- Icon thành công -->
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $title }}</h1>
        <p class="text-gray-600 text-sm mb-6 leading-relaxed">{{ $message }}</p>

        <a href="{{ route('login') }}"
           class="inline-block w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 rounded-xl transition text-sm shadow-md shadow-rose-200">
            👉 Chuyển sang Đăng nhập ngay
        </a>

    @elseif ($state === 'already_active')
        <!-- Icon đã kích hoạt -->
        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $title }}</h1>
        <p class="text-gray-600 text-sm mb-6 leading-relaxed">{{ $message }}</p>

        <a href="{{ route('login') }}"
           class="inline-block w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 rounded-xl transition text-sm shadow-md shadow-rose-200">
            👉 Đăng nhập vào tài khoản
        </a>

    @else
        <!-- Icon thất bại / hết hạn -->
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $title }}</h1>
        <p class="text-gray-600 text-sm mb-6 leading-relaxed">{{ $message }}</p>

        <!-- Form gửi lại email xác thực -->
        <div class="p-5 bg-gray-50 border border-gray-200 rounded-xl mb-4 text-left">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Gửi lại email xác thực</h3>
            <form method="POST" action="{{ route('verification.resend') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Địa chỉ email của bạn</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required
                           placeholder="example@gmail.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                </div>

                <button type="submit"
                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                    🔄 Gửi lại liên kết xác thực mới
                </button>
            </form>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-500 pt-2">
            <a href="{{ route('login') }}" class="text-rose-600 hover:underline font-medium">← Quay lại Đăng nhập</a>
            <a href="{{ route('register') }}" class="text-gray-500 hover:underline">Đăng ký mới</a>
        </div>
    @endif

</div>

</body>
</html>
