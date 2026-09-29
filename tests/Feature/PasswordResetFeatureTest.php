<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMailable;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\YeuCauDatLaiMatKhau;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'shop_hoa',
        ]);
        DB::purge('mysql');
        DB::reconnect('mysql');
        Mail::fake();
    }

    /**
     * Tạo tài khoản khách hàng mẫu cho test.
     */
    protected function createCustomer(string $prefix, string $plainPassword = 'password123'): array
    {
        $unique = time() . '_' . rand(1000, 9999);
        $username = $prefix . '_' . $unique;
        $email = $prefix . '_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => $username,
            'mat_khau'          => $plainPassword,
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
            'email_verified_at' => now(),
        ]);

        $khachHang = KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Khách Hàng Test ' . $unique,
            'so_dien_thoai'  => '09' . rand(10000000, 99999999),
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        return [$user, $khachHang];
    }

    /**
     * Tạo tài khoản nội bộ (Admin / Nhân viên / Shipper) mẫu cho test.
     */
    protected function createInternalUser(string $role = 'NHAN_VIEN', string $plainPassword = 'password123'): NguoiDung
    {
        $unique = time() . '_' . rand(1000, 9999);
        $user = NguoiDung::create([
            'ten_dang_nhap'     => strtolower($role) . '_' . $unique,
            'mat_khau'          => $plainPassword,
            'vai_tro'           => $role,
            'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
            'email_verified_at' => now(),
        ]);

        if (in_array($role, ['NHAN_VIEN', 'SHIPPER'])) {
            NhanVien::create([
                'ma_nhan_vien'  => $user->ma_nguoi_dung,
                'ten_nhan_vien' => 'Nhân Viên ' . $unique,
                'email'         => 'nv_' . $unique . '@example.com',
                'so_dien_thoai' => '09' . rand(10000000, 99999999),
                'chuc_vu'       => 'CSKH',
                'luong'         => 10000000,
            ]);
        }

        return $user;
    }

    /**
     * TC1: Email khách hàng tồn tại -> tạo yêu cầu reset -> gửi mã + link
     */
    public function test_tc1_existing_email_creates_reset_request_and_sends_email_with_code_and_link()
    {
        [$user, $khachHang] = $this->createCustomer('tc1');

        $response = $this->post(route('password.email'), [
            'email' => $khachHang->email,
        ]);

        $response->assertRedirect(route('password.reset', ['email' => $khachHang->email]));
        $response->assertSessionHas('status');

        // Bản ghi yêu cầu đặt lại mật khẩu phải tồn tại trong DB
        $resetRequest = YeuCauDatLaiMatKhau::where('ma_nguoi_dung', $user->ma_nguoi_dung)
            ->where('email', $khachHang->email)
            ->first();

        $this->assertNotNull($resetRequest);
        $this->assertEquals(YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC, $resetRequest->trang_thai);
        $this->assertEquals(6, strlen($resetRequest->ma_xac_thuc));
        $this->assertEquals(64, strlen($resetRequest->token));
        $this->assertTrue(now()->lessThanOrEqualTo($resetRequest->expires_at));

        // Kiểm tra email được gửi
        Mail::assertSent(ResetPasswordMailable::class, function ($mail) use ($khachHang, $resetRequest) {
            return $mail->hasTo($khachHang->email)
                && $mail->maXacThuc === $resetRequest->ma_xac_thuc
                && str_contains($mail->resetUrl, $resetRequest->token);
        });
    }

    /**
     * TC2: Email không tồn tại -> không tạo yêu cầu reset
     */
    public function test_tc2_non_existing_email_does_not_create_reset_request()
    {
        $nonExistentEmail = 'nonexistent_' . time() . '@example.com';

        $response = $this->post(route('password.email'), [
            'email' => $nonExistentEmail,
        ]);

        $response->assertSessionHasErrors('email');

        $exists = YeuCauDatLaiMatKhau::where('email', $nonExistentEmail)->exists();
        $this->assertFalse($exists);
        Mail::assertNothingSent();
    }

    /**
     * TC3: Mã hợp lệ, còn hạn -> cho nhập mật khẩu mới
     */
    public function test_tc3_valid_code_allows_resetting_password()
    {
        [$user, $khachHang] = $this->createCustomer('tc3', 'oldpassword123');

        $code = '849201';
        $token = Str::random(64);

        $resetRequest = YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => $code,
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes(60),
        ]);

        $response = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => $code,
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $user->mat_khau));
    }

    /**
     * TC4: Link hợp lệ, còn hạn -> cho nhập mật khẩu mới
     */
    public function test_tc4_valid_token_link_allows_resetting_password()
    {
        [$user, $khachHang] = $this->createCustomer('tc4', 'oldpassword123');

        $token = Str::random(64);

        YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => '123456',
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes(60),
        ]);

        // Kiểm tra mở màn hình reset bằng token link
        $getScreen = $this->get(route('password.reset', ['token' => $token, 'email' => $khachHang->email]));
        $getScreen->assertStatus(200);

        // Gửi form đặt lại mật khẩu mới
        $response = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'token'                 => $token,
            'password'              => 'newpassword789',
            'password_confirmation' => 'newpassword789',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword789', $user->mat_khau));
    }

    /**
     * TC5: Mã sai -> không cho reset
     */
    public function test_tc5_invalid_code_rejects_reset()
    {
        [$user, $khachHang] = $this->createCustomer('tc5', 'oldpassword123');

        YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => '111222',
            'token'         => Str::random(64),
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes(60),
        ]);

        $response = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => '999999', // Mã sai
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors('xac_thuc');

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->mat_khau), 'Mật khẩu cũ không được thay đổi');
    }

    /**
     * TC6: Link sai -> không cho reset
     */
    public function test_tc6_invalid_token_rejects_reset()
    {
        [$user, $khachHang] = $this->createCustomer('tc6', 'oldpassword123');

        YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => '111222',
            'token'         => Str::random(64),
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes(60),
        ]);

        $response = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'token'                 => 'invalid_token_hash_value',
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors('xac_thuc');

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->mat_khau));
    }

    /**
     * TC7: Mã/link quá 60 phút -> từ chối
     */
    public function test_tc7_expired_code_or_token_is_rejected()
    {
        [$user, $khachHang] = $this->createCustomer('tc7', 'oldpassword123');

        $code = '654321';
        $token = Str::random(64);

        // Tạo yêu cầu đã hết hạn (> 60 phút trước)
        YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => $code,
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->subMinute(), // Hết hạn
        ]);

        // Thử reset với mã hết hạn
        $responseCode = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => $code,
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);
        $responseCode->assertSessionHasErrors('xac_thuc');

        // Thử reset với token hết hạn
        $responseToken = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'token'                 => $token,
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);
        $responseToken->assertSessionHasErrors('xac_thuc');

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->mat_khau));
    }

    /**
     * TC8: Mã/link đã dùng -> không được dùng lại
     */
    public function test_tc8_used_code_or_token_cannot_be_reused()
    {
        [$user, $khachHang] = $this->createCustomer('tc8', 'oldpassword123');

        $code = '123456';
        $token = Str::random(64);

        YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => $code,
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_DA_SU_DUNG,
            'expires_at'    => now()->addMinutes(60),
        ]);

        $response = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => $code,
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors('xac_thuc');
        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->mat_khau));
    }

    /**
     * TC9: Mật khẩu mới không đúng policy -> không cập nhật -> mã/link vẫn chưa bị sử dụng
     */
    public function test_tc9_invalid_password_policy_does_not_update_and_keeps_token_valid()
    {
        [$user, $khachHang] = $this->createCustomer('tc9', 'oldpassword123');

        $code = '789123';
        $token = Str::random(64);

        $resetRequest = YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => $code,
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes(60),
        ]);

        // Mật khẩu dưới 6 ký tự
        $response = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => $code,
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');

        // Mật khẩu cũ không đổi
        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->mat_khau));

        // Yêu cầu vẫn giữ nguyên trạng thái CO_HIEU_LUC
        $resetRequest->refresh();
        $this->assertEquals(YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC, $resetRequest->trang_thai);
    }

    /**
     * TC10: Reset thành công -> mật khẩu mới login được -> mật khẩu cũ không login được
     */
    public function test_tc10_successful_reset_allows_login_with_new_password_and_rejects_old_password()
    {
        [$user, $khachHang] = $this->createCustomer('tc10', 'originalPassword123');

        $code = '555666';
        $token = Str::random(64);

        YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => $code,
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes(60),
        ]);

        // Đổi mật khẩu
        $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => $code,
            'password'              => 'freshNewPassword999',
            'password_confirmation' => 'freshNewPassword999',
        ]);

        // Đăng nhập bằng mật khẩu cũ -> Thất bại
        $failLogin = $this->post(route('login'), [
            'ten_dang_nhap' => $user->ten_dang_nhap,
            'password'      => 'originalPassword123',
        ]);
        $this->assertFalse(Auth::check());

        // Đăng nhập bằng mật khẩu mới -> Thành công
        $successLogin = $this->post(route('login'), [
            'ten_dang_nhap' => $user->ten_dang_nhap,
            'password'      => 'freshNewPassword999',
        ]);
        $successLogin->assertRedirect();
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->ma_nguoi_dung, Auth::id());
    }

    /**
     * TC11: Sau reset thành công -> mã cũ không dùng lại được -> link cũ không dùng lại được
     */
    public function test_tc11_after_reset_both_code_and_token_become_invalid()
    {
        [$user, $khachHang] = $this->createCustomer('tc11', 'passwordOld123');

        $code = '333444';
        $token = Str::random(64);

        $resetRequest = YeuCauDatLaiMatKhau::create([
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'email'         => $khachHang->email,
            'ma_xac_thuc'   => $code,
            'token'         => $token,
            'trang_thai'    => YeuCauDatLaiMatKhau::TRANG_THAI_CO_HIEU_LUC,
            'expires_at'    => now()->addMinutes(60),
        ]);

        // Thực hiện reset lần 1 bằng code
        $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => $code,
            'password'              => 'newPasswordRound1',
            'password_confirmation' => 'newPasswordRound1',
        ]);

        $resetRequest->refresh();
        $this->assertEquals(YeuCauDatLaiMatKhau::TRANG_THAI_DA_SU_DUNG, $resetRequest->trang_thai);

        // Thử dùng lại mã cũ
        $reuseCode = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'ma_xac_thuc'           => $code,
            'password'              => 'anotherPassword',
            'password_confirmation' => 'anotherPassword',
        ]);
        $reuseCode->assertSessionHasErrors('xac_thuc');

        // Thử dùng lại link/token cũ
        $reuseToken = $this->post(route('password.store'), [
            'email'                 => $khachHang->email,
            'token'                 => $token,
            'password'              => 'anotherPassword',
            'password_confirmation' => 'anotherPassword',
        ]);
        $reuseToken->assertSessionHasErrors('xac_thuc');
    }

    /**
     * TC12: Email service lỗi -> mật khẩu hiện tại không thay đổi
     */
    public function test_tc12_mail_service_failure_keeps_current_password_intact()
    {
        [$user, $khachHang] = $this->createCustomer('tc12', 'myCurrentPassword123');

        // Giả lập Mail service ném Exception
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP Connection Timed Out'));

        $response = $this->post(route('password.email'), [
            'email' => $khachHang->email,
        ]);

        $response->assertSessionHasErrors('email');

        // Không tạo bản ghi rác
        $exists = YeuCauDatLaiMatKhau::where('ma_nguoi_dung', $user->ma_nguoi_dung)->exists();
        $this->assertFalse($exists);

        // Mật khẩu hiện tại không thay đổi
        $user->refresh();
        $this->assertTrue(Hash::check('myCurrentPassword123', $user->mat_khau));
    }

    /**
     * TC13: Admin reset tài khoản nội bộ -> mật khẩu mới được hash và cập nhật
     */
    public function test_tc13_admin_resets_internal_user_password_hashes_and_updates()
    {
        $admin = $this->createInternalUser('ADMIN', 'adminPass123');
        $staff = $this->createInternalUser('NHAN_VIEN', 'staffOldPass123');

        $this->actingAs($admin);

        $response = $this->post(route('nguoi-dung.reset-password', $staff->ma_nguoi_dung), [
            'mat_khau'              => 'staffNewPass456',
            'mat_khau_confirmation' => 'staffNewPass456',
        ]);

        $response->assertRedirect(route('nguoi-dung.index'));

        $staff->refresh();
        $this->assertTrue(Hash::check('staffNewPass456', $staff->mat_khau));
        $this->assertFalse(Hash::check('staffOldPass123', $staff->mat_khau));
    }

    /**
     * TC14: Admin không có quyền -> không được reset
     */
    public function test_tc14_non_admin_cannot_reset_internal_password()
    {
        $staff = $this->createInternalUser('NHAN_VIEN', 'staffPass123');
        $targetUser = $this->createInternalUser('SHIPPER', 'shipperPass123');

        // Nhân viên thường không có quyền admin
        $this->actingAs($staff);

        $response = $this->post(route('nguoi-dung.reset-password', $targetUser->ma_nguoi_dung), [
            'mat_khau'              => 'hackedPass123',
            'mat_khau_confirmation' => 'hackedPass123',
        ]);

        // Bị chặn 403 Forbidden bởi middleware role:ADMIN
        $this->assertEquals(403, $response->getStatusCode());

        $targetUser->refresh();
        $this->assertTrue(Hash::check('shipperPass123', $targetUser->mat_khau));
    }

    /**
     * TC15: Admin không được xem mật khẩu cũ
     */
    public function test_tc15_admin_cannot_view_or_retrieve_old_password()
    {
        $admin = $this->createInternalUser('ADMIN', 'adminPass123');
        $staff = $this->createInternalUser('NHAN_VIEN', 'secretStaffPass999');

        $this->actingAs($admin);

        // Truy cập endpoint lấy dữ liệu sửa người dùng
        $response = $this->get(route('nguoi-dung.edit-data', $staff->ma_nguoi_dung));
        $response->assertStatus(200);

        $data = $response->json();

        // Mật khẩu nằm trong hidden attribute hoặc không được trả về dạng plaintext
        $this->assertNotEquals('secretStaffPass999', $data['mat_khau'] ?? null);

        // API reset password cũng không yêu cầu hay hiển thị mật khẩu cũ
        $resetFormScreen = $this->get(route('nguoi-dung.index'));
        $resetFormScreen->assertDontSee('secretStaffPass999');
    }
}
