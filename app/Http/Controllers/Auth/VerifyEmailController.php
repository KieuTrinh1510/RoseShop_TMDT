<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMailable;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Màn hình thông báo chờ xác thực email sau khi đăng ký hoặc khi được điều hướng tới.
     */
    public function notice(Request $request)
    {
        $email = session('email', $request->query('email', ''));
        return view('auth.verify-notice', compact('email'));
    }

    /**
     * Xử lý liên kết xác thực email khi khách hàng nhấp vào link trong email.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = NguoiDung::with('khachHang')->find($id);

        // 1. Kiểm tra chữ ký và hạn sử dụng của link (Signature & Expiration)
        $hasValidSig = $request->hasValidSignature();
        if (! $hasValidSig) {
            $hosts = ['http://127.0.0.1:8000', 'http://localhost:8000', 'http://localhost', 'http://127.0.0.1'];
            foreach ($hosts as $host) {
                $testUrl = $host . '/' . ltrim($request->path(), '/') . '?' . $request->server->get('QUERY_STRING');
                if (\Illuminate\Http\Request::create($testUrl)->hasValidSignature()) {
                    $hasValidSig = true;
                    break;
                }
            }
        }

        if (! $hasValidSig) {
            $userEmail = $user?->getEmailForVerification();

            return view('auth.verify-status', [
                'state'   => 'invalid_or_expired',
                'title'   => 'Liên kết không hợp lệ hoặc đã hết hạn',
                'message' => 'Liên kết xác thực tài khoản này không đúng, đã bị chỉnh sửa hoặc đã quá hạn sử dụng. Vui lòng yêu cầu gửi lại email xác thực mới.',
                'email'   => $userEmail,
            ]);
        }

        // 2. Kiểm tra tồn tại người dùng và mã băm email
        if (! $user) {
            return view('auth.verify-status', [
                'state'   => 'invalid',
                'title'   => 'Tài khoản không tồn tại',
                'message' => 'Không tìm thấy tài khoản tương ứng với liên kết xác thực này.',
                'email'   => null,
            ]);
        }

        $expectedHash = sha1($user->getEmailForVerification());
        if (! hash_equals((string) $hash, (string) $expectedHash)) {
            return view('auth.verify-status', [
                'state'   => 'invalid',
                'title'   => 'Liên kết không hợp lệ',
                'message' => 'Mã xác thực không khớp với thông tin tài khoản.',
                'email'   => $user->getEmailForVerification(),
            ]);
        }

        // 3. Nếu tài khoản đã được kích hoạt trước đó
        if ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) {
            return view('auth.verify-status', [
                'state'   => 'already_active',
                'title'   => 'Tài khoản đã được kích hoạt',
                'message' => 'Tài khoản của bạn đã được xác thực trước đó. Bạn có thể đăng nhập ngay.',
                'email'   => $user->getEmailForVerification(),
            ]);
        }

        // 4. Kích hoạt tài khoản: Chuyển CHO_XAC_THUC -> HOAT_DONG
        $user->markEmailAsVerified();

        return view('auth.verify-status', [
            'state'   => 'success',
            'title'   => 'Xác thực tài khoản thành công!',
            'message' => 'Chúc mừng bạn! Tài khoản của bạn đã được kích hoạt thành công. Hãy đăng nhập để trải nghiệm mua sắm tại RoseShop.',
            'email'   => $user->getEmailForVerification(),
        ]);
    }

    /**
     * Gửi lại email xác thực cho tài khoản đang ở trạng thái CHO_XAC_THUC.
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100',
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email'    => 'Địa chỉ email không đúng định dạng.',
        ]);

        $khachHang = KhachHang::with('nguoiDung')->where('email', $request->email)->first();

        if (! $khachHang || ! $khachHang->nguoiDung) {
            return back()
                ->withErrors(['resend_email' => 'Không tìm thấy tài khoản khách hàng tương ứng với email này.'])
                ->withInput();
        }

        $user = $khachHang->nguoiDung;

        // Nếu tài khoản đã hoạt động: không gửi lại
        if ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) {
            return back()
                ->with('info', 'Tài khoản của email này đã được xác thực và đang hoạt động bình thường. Bạn có thể đăng nhập ngay.')
                ->withInput();
        }

        // Nếu tài khoản không ở trạng thái CHO_XAC_THUC
        if ($user->trang_thai !== NguoiDung::TRANG_THAI_CHO_XAC_THUC) {
            return back()
                ->withErrors(['resend_email' => 'Tài khoản không ở trạng thái chờ xác thực.'])
                ->withInput();
        }

        $expireMinutes = (int) config('auth.verification.expire', 60);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($expireMinutes),
            [
                'id'   => $user->ma_nguoi_dung,
                'hash' => sha1($khachHang->email),
            ]
        );

        try {
            Mail::to($khachHang->email)->send(
                new VerifyEmailMailable(
                    $user,
                    $verificationUrl,
                    $khachHang->ten_khach_hang,
                    $expireMinutes
                )
            );

            if (config('mail.default') === 'log' || app()->environment('local')) {
                session()->flash('dev_verification_url', $verificationUrl);
            }

            return back()->with('status', 'Đã gửi lại email xác thực thành công! Vui lòng kiểm tra hộp thư đến (hoặc thư rác).');
        } catch (\Throwable $e) {
            Log::error('Lỗi khi gửi lại email xác thực: ' . $e->getMessage());

            return back()
                ->withErrors(['resend_email' => 'Hệ thống chưa thể gửi email lúc này. Vui lòng thử lại sau ít phút.'])
                ->withInput();
        }
    }
}
