<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-900 mb-2">🌸 Quên mật khẩu?</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Đừng lo lắng! Hãy nhập địa chỉ email đã đăng ký tài khoản tại RoseShop. Hệ thống sẽ gửi mã xác thực (6 số) và liên kết đặt lại mật khẩu tới hộp thư của bạn.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Địa chỉ email đã đăng ký')" />
            <x-text-input id="email" class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                placeholder="vidu@gmail.com"
                required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-rose-600 underline">
                ← Quay lại đăng nhập
            </a>

            <x-primary-button class="bg-rose-600 hover:bg-rose-700">
                Gửi mã xác thực
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-100 text-center">
        <span class="text-xs text-gray-500">Đã nhận được mã xác thực trong email?</span>
        <a href="{{ route('password.reset') }}" class="text-xs font-semibold text-rose-600 hover:underline ml-1">
            👉 Nhập mã đặt lại mật khẩu tại đây
        </a>
    </div>
</x-guest-layout>
