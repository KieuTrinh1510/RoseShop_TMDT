<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMailable;
use App\Models\KhachHang;
use App\Models\YeuCauDatLaiMatKhau;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email'    => 'Địa chỉ email không đúng định dạng.',
            'email.max'      => 'Địa chỉ email không được vượt quá 100 ký tự.',
        ]);

        $email = trim($request->email);

        // Tìm khách hàng theo email đã đăng ký
        $khachHang = KhachHang::where('email', $email)->first();

        // Chỉ tạo yêu cầu nếu email thuộc tài khoản khách hàng tồn tại
        if (! $khachHang || ! $khachHang->nguoiDung) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Địa chỉ email này chưa được đăng ký trong hệ thống RoseShop.']);
        }

        $nguoiDung = $khachHang->nguoiDung;

        // Vô hiệu hóa các yêu cầu đặt lại mật khẩu cũ còn hiệu lực của tài khoản này
        YeuCauDatLaiMatKhau::where('ma_nguoi_dung', $nguoiDung->ma_nguoi_dung)
            ->where('trang_thai', YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC)
            ->update(['trang_thai' => YeuCauDatLaiMatKhau::TRANG_THAI_VO_HIEU_HOA]);

        // Tạo mã xác thực 6 chữ số và token ngẫu nhiên 64 ký tự
        $maXacThuc = sprintf('%06d', random_int(100000, 999999));
        $token = Str::random(64);
        $expireMinutes = 60;

        // Tạo bản ghi yêu cầu đặt lại mật khẩu
        $yeuCau = YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $nguoiDung->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => $maXacThuc,
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes($expireMinutes),
        ]);

        // Tạo liên kết đặt lại mật khẩu
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $khachHang->email,
        ]);

        // Gửi email thật tới khách hàng với xử lý lỗi an toàn
        try {
            Mail::to($khachHang->email)->send(
                new ResetPasswordMailable($khachHang, $maXacThuc, $resetUrl, $expireMinutes)
            );
        } catch (\Throwable $e) {
            // Nếu dịch vụ email lỗi: xóa yêu cầu, không đổi mật khẩu, thông báo khách thử lại sau
            $yeuCau->delete();
            Log::error('Lỗi gửi email đặt lại mật khẩu: ' . $e->getMessage(), [
                'email' => $khachHang->email,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Hệ thống gửi thư tạm thời gặp sự cố. Mật khẩu của bạn không bị thay đổi, vui lòng thử lại sau.']);
        }

        return redirect()->route('password.reset', ['email' => $khachHang->email])
            ->with('status', 'Mã xác thực và liên kết đặt lại mật khẩu đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.');
    }
}
