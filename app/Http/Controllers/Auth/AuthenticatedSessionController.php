<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
{
    $request->validate([
        'ten_dang_nhap' => ['required', 'string'],
        'password'      => ['required', 'string'],
    ]);

    // Lưu lại giỏ hàng trước khi đăng nhập
    $gioHang   = session('gio_hang', []);
    $gioBoHoa  = session('gio_bo_hoa', []);

    // Hỗ trợ trường hợp tài khoản có mật khẩu dạng plain-text chưa mã hóa từ database cũ
    $user = \App\Models\NguoiDung::where('ten_dang_nhap', $request->ten_dang_nhap)->first();
    if ($user && !str_starts_with($user->mat_khau, '$2y$') && !str_starts_with($user->mat_khau, '$2a$')) {
        if ($user->mat_khau === $request->password) {
            $user->mat_khau = $request->password;
            $user->save();
        } else {
            return back()->withErrors([
                'ten_dang_nhap' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
            ])->onlyInput('ten_dang_nhap');
        }
    }

    if (! Auth::attempt([
        'ten_dang_nhap' => $request->ten_dang_nhap,
        'password'      => $request->password,
    ], $request->boolean('remember'))) {
        return back()->withErrors([
            'ten_dang_nhap' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
        ])->onlyInput('ten_dang_nhap');
    }

    $authenticatedUser = Auth::user();

    // Chặn đăng nhập nếu tài khoản chưa được xác thực
    if ($authenticatedUser->trang_thai === \App\Models\NguoiDung::TRANG_THAI_CHO_XAC_THUC) {
        $email = $authenticatedUser->getEmailForVerification();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('unverified', true)
            ->with('unverified_email', $email)
            ->withErrors([
                'ten_dang_nhap' => 'Tài khoản chưa được xác thực. Vui lòng kiểm tra email để kích hoạt tài khoản.',
            ])->onlyInput('ten_dang_nhap');
    }

    // Chặn đăng nhập nếu tài khoản bị khóa
    if ($authenticatedUser->trang_thai === \App\Models\NguoiDung::TRANG_THAI_BI_KHOA) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors([
                'ten_dang_nhap' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ])->onlyInput('ten_dang_nhap');
    }

    // Shipper không còn đăng nhập trên web → đăng xuất và chuyển sang trang hướng dẫn tải app
    if ($authenticatedUser->vai_tro === 'SHIPPER') {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shipper.mobile-only');
    }

    $request->session()->regenerate();

    // Khôi phục lại giỏ hàng sau khi regenerate session
    session(['gio_hang' => $gioHang, 'gio_bo_hoa' => $gioBoHoa]);
    \App\Services\CartService::restoreFromDatabase($authenticatedUser);

    $vai_tro = Auth::user()->vai_tro;

    if (in_array($vai_tro, ['ADMIN', 'NHAN_VIEN'])) {
        session()->flash('vua_dang_nhap', true);
    }

    return match($vai_tro) {
        'ADMIN', 'NHAN_VIEN' => redirect()->route('admin.dashboard'),
        'SHIPPER'            => redirect()->route('shipper.mobile-only'),
        default              => redirect()->route('customer.dashboard'),
    };
}

    public function destroy(Request $request)
    {
        if (Auth::check()) {
            \App\Services\CartService::syncToDatabase(Auth::user());
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.dashboard');
    }
}