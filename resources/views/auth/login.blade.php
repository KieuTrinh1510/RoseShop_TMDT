<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Tên đăng nhập -->
        <div>
            <x-input-label for="ten_dang_nhap" :value="__('Tên đăng nhập')" />
            <x-text-input id="ten_dang_nhap" class="block mt-1 w-full"
                type="text"
                name="ten_dang_nhap"
                :value="old('ten_dang_nhap')"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('ten_dang_nhap')" class="mt-2" />
        </div>

        <!-- Mật khẩu -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mật khẩu')" />
            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Ghi nhớ -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-300"
                    name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Ghi nhớ đăng nhập') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">
            <!-- Nút đăng ký -->
            <a href="{{ route('register') }}"
                class="text-sm text-rose-600 hover:text-rose-800 underline">
                {{ __('Chưa có tài khoản? Đăng ký') }}
            </a>

            <x-primary-button>
                {{ __('Đăng nhập') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Danh sách tài khoản mẫu (Bấm để tự động điền) -->
    <div class="mt-6 pt-4 border-t border-gray-100">
        <div class="flex items-center justify-between mb-2.5">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tài khoản dùng thử</span>
            <span class="text-[11px] text-rose-500 font-medium italic">⚡ Bấm để tự điền</span>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <!-- Admin -->
            <button type="button" onclick="fillAccount('admin', '123456')"
                class="p-2.5 rounded-lg border border-rose-200 bg-rose-50/50 hover:bg-rose-100/70 hover:border-rose-300 transition text-left cursor-pointer group shadow-xs">
                <div class="flex items-center gap-1.5 font-bold text-rose-700 text-xs mb-1">
                    <span>👑</span>
                    <span>Quản trị viên</span>
                </div>
                <div class="text-gray-700 font-mono text-[11px]">TK: <span class="font-bold text-gray-900">admin</span></div>
                <div class="text-gray-500 font-mono text-[11px]">MK: <span class="font-medium text-gray-700">123456</span></div>
            </button>

            <!-- Nhân viên -->
            <button type="button" onclick="fillAccount('nhanvien1', '123456')"
                class="p-2.5 rounded-lg border border-blue-200 bg-blue-50/50 hover:bg-blue-100/70 hover:border-blue-300 transition text-left cursor-pointer group shadow-xs">
                <div class="flex items-center gap-1.5 font-bold text-blue-700 text-xs mb-1">
                    <span>💼</span>
                    <span>Nhân viên</span>
                </div>
                <div class="text-gray-700 font-mono text-[11px]">TK: <span class="font-bold text-gray-900">nhanvien1</span></div>
                <div class="text-gray-500 font-mono text-[11px]">MK: <span class="font-medium text-gray-700">123456</span></div>
            </button>

            <!-- Shipper -->
            <button type="button" onclick="fillAccount('shipper1', '123456')"
                class="p-2.5 rounded-lg border border-amber-200 bg-amber-50/50 hover:bg-amber-100/70 hover:border-amber-300 transition text-left cursor-pointer group shadow-xs">
                <div class="flex items-center gap-1.5 font-bold text-amber-700 text-xs mb-1">
                    <span>🚚</span>
                    <span>Shipper</span>
                </div>
                <div class="text-gray-700 font-mono text-[11px]">TK: <span class="font-bold text-gray-900">shipper1</span></div>
                <div class="text-gray-500 font-mono text-[11px]">MK: <span class="font-medium text-gray-700">123456</span></div>
            </button>

            <!-- Khách hàng -->
            <button type="button" onclick="fillAccount('kh1', '123456')"
                class="p-2.5 rounded-lg border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-100/70 hover:border-emerald-300 transition text-left cursor-pointer group shadow-xs">
                <div class="flex items-center gap-1.5 font-bold text-emerald-700 text-xs mb-1">
                    <span>🛍️</span>
                    <span>Khách hàng</span>
                </div>
                <div class="text-gray-700 font-mono text-[11px]">TK: <span class="font-bold text-gray-900">kh1</span></div>
                <div class="text-gray-500 font-mono text-[11px]">MK: <span class="font-medium text-gray-700">123456</span></div>
            </button>
        </div>
    </div>

    <script>
        function fillAccount(username, password) {
            const uInput = document.getElementById('ten_dang_nhap');
            const pInput = document.getElementById('password');
            if (uInput && pInput) {
                uInput.value = username;
                pInput.value = password;
                pInput.focus();
            }
        }
    </script>
</x-guest-layout>