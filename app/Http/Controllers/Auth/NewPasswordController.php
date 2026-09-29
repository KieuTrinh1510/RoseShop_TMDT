<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KhachHang;
use App\Models\YeuCauDatLaiMatKhau;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request, ?string $token = null): View
    {
        return view('auth.reset-password', [
            'request' => $request,
            'token'   => $token ?? $request->query('token'),
            'email'   => $request->query('email', old('email')),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'       => ['required', 'string', 'email', 'max:100'],
            'password'    => ['required', 'string', 'min:6', 'confirmed'],
            'token'       => ['nullable', 'string'],
            'ma_xac_thuc' => ['nullable', 'string'],
        ], [
            'email.required'     => 'Vui lòng nhập địa chỉ email.',
            'email.email'        => 'Địa chỉ email không đúng định dạng.',
            'password.required'  => 'Vui lòng nhập mật khẩu mới.',
            'password.min'       => 'Mật khẩu mới phải có tối thiểu 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        $email = trim($request->email);
        $token = trim((string) $request->token);
        $maXacThuc = trim((string) $request->ma_xac_thuc);

        // Khách hàng phải cung cấp hoặc mã 6 số HOẶC token liên kết
        if (empty($token) && empty($maXacThuc)) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => 'Vui lòng cung cấp mã xác thực 6 số hoặc liên kết đặt lại mật khẩu.']);
        }

        // Tìm khách hàng theo email
        $khachHang = KhachHang::where('email', $email)->first();
        if (! $khachHang || ! $khachHang->nguoiDung) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['email' => 'Không tìm thấy tài khoản khách hàng tương ứng với email này.']);
        }

        // Tìm yêu cầu đặt lại mật khẩu tương ứng
        $query = YeuCauDatLaiMatKhau::where('email', $email);

        if (! empty($token)) {
            $query->where('token', $token);
        } else {
            $query->where('ma_xac_thuc', $maXacThuc);
        }

        $yeuCau = $query->latest('id')->first();

        // Kiểm tra tồn tại
        if (! $yeuCau) {
            $errMsg = ! empty($token)
                ? 'Liên kết đặt lại mật khẩu không hợp lệ hoặc không thuộc tài khoản này.'
                : 'Mã xác thực không chính xác hoặc không thuộc tài khoản này.';

            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => $errMsg]);
        }

        // Kiểm tra tài khoản liên kết
        if ($yeuCau->ma_nguoi_dung !== $khachHang->ma_khach_hang) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => 'Yêu cầu đặt lại mật khẩu không khớp với tài khoản người dùng.']);
        }

        // Kiểm tra mã/link đã được dùng chưa
        if ($yeuCau->trang_thai === YeuCauDatLaiMatKhau::TRANG_THAI_DA_SU_DUNG) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => 'Mã xác thực hoặc liên kết này đã được sử dụng trước đó. Vui lòng gửi yêu cầu mới nếu cần đổi lại mật khẩu.']);
        }

        // Kiểm tra yêu cầu có bị vô hiệu hóa không
        if ($yeuCau->trang_thai === YeuCauDatLaiMatKhau::TRANG_THAI_VO_HIEU_HOA) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => 'Yêu cầu đặt lại mật khẩu này đã bị vô hiệu hóa do đã có yêu cầu mới hơn.']);
        }

        // Kiểm tra trạng thái có hiệu lực
        if ($yeuCau->trang_thai !== YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => 'Yêu cầu đặt lại mật khẩu hiện không còn hiệu lực.']);
        }

        // Kiểm tra thời hạn 60 phút
        if ($yeuCau->isExpired()) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => 'Mã xác thực hoặc liên kết đặt lại mật khẩu đã hết hạn (chỉ có hiệu lực trong vòng 60 phút).']);
        }

        // Cập nhật mật khẩu mới và vô hiệu hóa mã/link trong DB Transaction nhất quán
        try {
            DB::transaction(function () use ($yeuCau, $request) {
                $nguoiDung = $yeuCau->nguoiDung;

                // NguoiDung model sử dụng mutator setMatKhauAttribute() để mã hóa bcrypt
                $nguoiDung->mat_khau = $request->password;
                $nguoiDung->save();

                // Vô hiệu hóa yêu cầu đã sử dụng thành công
                $yeuCau->update([
                    'trang_thai' => YeuCauDatLaiMatKhau::TRANG_THAI_DA_SU_DUNG,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput($request->only('email', 'token', 'ma_xac_thuc'))
                ->withErrors(['xac_thuc' => 'Có lỗi xảy ra trong quá trình cập nhật mật khẩu. Vui lòng thử lại sau.']);
        }

        return redirect()->route('login')
            ->with('status', 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập bằng mật khẩu mới.');
    }
}
