<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckNhanVien
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            !Auth::check() ||
            !in_array(Auth::user()->vai_tro, ['ADMIN', 'NHAN_VIEN'])
        ) {
            abort(403, 'Chỉ Nhân Viên hoặc Admin mới có quyền truy cập.');
        }

        if (Auth::user()->trang_thai === \App\Models\NguoiDung::TRANG_THAI_BI_KHOA) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'ten_dang_nhap' => 'Tài khoản của bạn đã bị ngừng hoạt động. Vui lòng liên hệ quản trị viên.'
            ]);
        }

        return $next($request);
    }
}