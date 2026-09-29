<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-900 mb-2">🔑 Đặt lại mật khẩu mới</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Vui lòng nhập thông tin xác thực (Mã 6 số HOẶC Liên kết) cùng mật khẩu mới của bạn để hoàn tất việc khôi phục tài khoản.
        </p>
    </div>

    <!-- Thông báo lỗi chung của xác thực -->
    @if ($errors->has('xac_thuc'))
        <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
            ⚠️ {{ $errors->first('xac_thuc') }}
        </div>
    @endif

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.store') }}" id="resetPasswordForm">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Địa chỉ email')" />
            <x-text-input id="email" class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email', $email ?? '')"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        @php
            $hasToken = !empty($token);
        @endphp

        <!-- Lựa chọn phương thức xác thực -->
        <div class="mt-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
            <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                Phương thức xác thực
            </div>

            <!-- Tab chọn cách xác thực -->
            <div class="grid grid-cols-2 gap-2 mb-3">
                <button type="button" id="tabCodeBtn" onclick="switchMethod('code')"
                    class="py-2 px-3 text-xs font-semibold rounded-lg border transition {{ !$hasToken ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                    🔢 Nhập mã 6 số
                </button>
                <button type="button" id="tabTokenBtn" onclick="switchMethod('token')"
                    class="py-2 px-3 text-xs font-semibold rounded-lg border transition {{ $hasToken ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                    🔗 Dùng Token / Link
                </button>
            </div>

            <!-- Khung nhập mã 6 số -->
            <div id="methodCodeWrapper" style="{{ $hasToken ? 'display: none;' : 'display: block;' }}">
                <x-input-label for="ma_xac_thuc" :value="__('Mã xác thực 6 chữ số (từ email)')" />
                <x-text-input id="ma_xac_thuc" class="block mt-1 w-full text-center tracking-widest font-mono text-lg font-bold"
                    type="text"
                    name="ma_xac_thuc"
                    maxlength="6"
                    placeholder="123456"
                    :value="old('ma_xac_thuc')"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                <p class="text-[11px] text-gray-500 mt-1">Mã xác thực gồm 6 chữ số được gửi trong email của bạn.</p>
            </div>

            <!-- Khung nhập Token liên kết -->
            <div id="methodTokenWrapper" style="{{ $hasToken ? 'display: block;' : 'display: none;' }}">
                <x-input-label for="token" :value="__('Mã bảo mật Token liên kết')" />
                <x-text-input id="token" class="block mt-1 w-full font-mono text-xs text-gray-600 bg-gray-50"
                    type="text"
                    name="token"
                    :value="old('token', $token ?? '')"
                    placeholder="Dán mã token bảo mật từ liên kết email" />
                @if ($hasToken)
                    <p class="text-[11px] text-emerald-600 font-medium mt-1">✓ Đã tự động nhận token từ liên kết email của bạn.</p>
                @else
                    <p class="text-[11px] text-gray-500 mt-1">Token ngẫu nhiên đính kèm trên thanh địa chỉ của liên kết email.</p>
                @endif
            </div>
        </div>

        <!-- Mật khẩu mới -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mật khẩu mới (tối thiểu 6 ký tự)')" />
            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Xác nhận mật khẩu mới -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Xác nhận mật khẩu mới')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-rose-600 underline">
                ← Đăng nhập
            </a>

            <x-primary-button class="bg-rose-600 hover:bg-rose-700">
                Lưu mật khẩu mới
            </x-primary-button>
        </div>
    </form>

    <script>
        function switchMethod(method) {
            const codeWrap = document.getElementById('methodCodeWrapper');
            const tokenWrap = document.getElementById('methodTokenWrapper');
            const tabCodeBtn = document.getElementById('tabCodeBtn');
            const tabTokenBtn = document.getElementById('tabTokenBtn');
            const codeInput = document.getElementById('ma_xac_thuc');
            const tokenInput = document.getElementById('token');

            if (method === 'code') {
                codeWrap.style.display = 'block';
                tokenWrap.style.display = 'none';

                tabCodeBtn.className = 'py-2 px-3 text-xs font-semibold rounded-lg border transition bg-rose-600 text-white border-rose-600 shadow-xs';
                tabTokenBtn.className = 'py-2 px-3 text-xs font-semibold rounded-lg border transition bg-white text-gray-700 border-gray-300 hover:bg-gray-100';

                tokenInput.value = '';
                codeInput.focus();
            } else {
                codeWrap.style.display = 'none';
                tokenWrap.style.display = 'block';

                tabTokenBtn.className = 'py-2 px-3 text-xs font-semibold rounded-lg border transition bg-rose-600 text-white border-rose-600 shadow-xs';
                tabCodeBtn.className = 'py-2 px-3 text-xs font-semibold rounded-lg border transition bg-white text-gray-700 border-gray-300 hover:bg-gray-100';

                codeInput.value = '';
                tokenInput.focus();
            }
        }
    </script>
</x-guest-layout>
