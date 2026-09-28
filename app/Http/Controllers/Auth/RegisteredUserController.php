<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMailable;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_dang_nhap'      => 'required|string|max:50|unique:nguoi_dung,ten_dang_nhap',
            'ten_khach_hang'     => 'required|string|max:100',
            'email'              => 'required|email|max:100',
            'so_dien_thoai'      => ['required', 'string', 'size:10', 'regex:/^\d{10}$/'],
            'quan_huyen'         => 'required|string|max:100',
            'xa_phuong'          => 'required|string|max:100',
            'dia_chi_chi_tiet'   => 'required|string|max:255',
            'mat_khau'           => 'required|string|min:6|confirmed',
        ], [
            'ten_dang_nhap.unique'      => 'Tên đăng nhập đã tồn tại.',
            'ten_dang_nhap.required'    => 'Vui lòng nhập tên đăng nhập.',
            'ten_khach_hang.required'   => 'Vui lòng nhập họ và tên.',
            'email.required'            => 'Vui lòng nhập email.',
            'email.email'               => 'Email không hợp lệ.',
            'so_dien_thoai.required'    => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.size'        => 'Số điện thoại phải đúng 10 chữ số.',
            'so_dien_thoai.regex'       => 'Số điện thoại chỉ được chứa chữ số.',
            'quan_huyen.required'       => 'Vui lòng chọn quận/huyện.',
            'xa_phuong.required'        => 'Vui lòng chọn xã/phường.',
            'dia_chi_chi_tiet.required' => 'Vui lòng nhập địa chỉ chi tiết.',
            'mat_khau.required'         => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min'              => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'mat_khau.confirmed'        => 'Xác nhận mật khẩu không khớp.',
        ]);

        // Kiểm tra tính duy nhất của email và trạng thái tài khoản liên quan
        $existingKhachHang = KhachHang::with('nguoiDung')->where('email', $request->email)->first();
        if ($existingKhachHang) {
            $existingUser = $existingKhachHang->nguoiDung;
            if ($existingUser && $existingUser->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) {
                return back()
                    ->withErrors(['email' => 'Email đã được sử dụng.'])
                    ->withInput();
            } elseif ($existingUser && $existingUser->trang_thai === NguoiDung::TRANG_THAI_CHO_XAC_THUC) {
                return back()
                    ->with('status_warning', 'Email này đã được đăng ký nhưng tài khoản chưa được xác thực.')
                    ->with('resend_email', $request->email)
                    ->withInput();
            }
        }

        $diaChiDayDu = $request->dia_chi_chi_tiet
            . ', ' . $request->xa_phuong
            . ', ' . $request->quan_huyen
            . ', Hà Nội';

        DB::beginTransaction();
        try {
            $user = NguoiDung::create([
                'ten_dang_nhap'     => $request->ten_dang_nhap,
                'mat_khau'          => $request->mat_khau,
                'vai_tro'           => 'KHACH_HANG',
                'trang_thai'        => NguoiDung::TRANG_THAI_CHO_XAC_THUC,
                'email_verified_at' => null,
            ]);

            KhachHang::create([
                'ma_khach_hang'  => $user->ma_nguoi_dung,
                'ten_khach_hang' => $request->ten_khach_hang,
                'so_dien_thoai'  => $request->so_dien_thoai,
                'email'          => $request->email,
                'dia_chi'        => $diaChiDayDu,
                'diem_tich_luy'  => 0,
            ]);

            DB::commit();

            // KHÔNG tự động đăng nhập!
            // Tạo link xác thực có chữ ký an toàn và gửi email
            $expireMinutes = (int) config('auth.verification.expire', 60);
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes($expireMinutes),
                [
                    'id'   => $user->ma_nguoi_dung,
                    'hash' => sha1($request->email),
                ]
            );

            try {
                Mail::to($request->email)->send(
                    new VerifyEmailMailable(
                        $user,
                        $verificationUrl,
                        $request->ten_khach_hang,
                        $expireMinutes
                    )
                );

                if (config('mail.default') === 'log' || app()->environment('local')) {
                    session()->flash('dev_verification_url', $verificationUrl);
                }

                return redirect()->route('verification.notice')
                    ->with('email', $request->email)
                    ->with('status', 'Tài khoản đã được tạo. Vui lòng kiểm tra email để xác thực tài khoản.');
            } catch (\Throwable $mailError) {
                Log::error('Lỗi gửi email xác thực tài khoản: ' . $mailError->getMessage());

                if (config('mail.default') === 'log' || app()->environment('local')) {
                    session()->flash('dev_verification_url', $verificationUrl);
                }

                return redirect()->route('verification.notice')
                    ->with('email', $request->email)
                    ->with('warning', 'Tài khoản đã được tạo (Chờ xác thực) nhưng hệ thống chưa thể gửi email lúc này. Quý khách có thể yêu cầu gửi lại sau.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['general' => 'Đã xảy ra lỗi khi tạo tài khoản: ' . $e->getMessage()])
                ->withInput();
        }
    }
}