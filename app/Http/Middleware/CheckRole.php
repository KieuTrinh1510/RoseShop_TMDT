<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // Chặn nhân viên ngừng hoạt động tiếp tục sử dụng chức năng ngay lập tức
        if (Auth::user()->trang_thai === \App\Models\NguoiDung::TRANG_THAI_BI_KHOA) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'ten_dang_nhap' => 'Tài khoản của bạn đã bị ngừng hoạt động. Vui lòng liên hệ quản trị viên.'
            ]);
        }

        $vai_tro = Auth::user()->vai_tro; // ADMIN, NHAN_VIEN, SHIPPER, KHACH_HANG

        if (! in_array($vai_tro, $roles)) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}