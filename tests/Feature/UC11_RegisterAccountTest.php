<?php

namespace Tests\Feature;

use App\Mail\VerifyEmailMailable;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UC11_RegisterAccountTest extends TestCase
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
     * TC1: Email mới + dữ liệu hợp lệ
     * -> tạo account, trạng thái CHO_XAC_THUC, không auto login, gửi mail xác thực
     */
    public function test_tc1_register_new_valid_account_creates_unverified_user_and_sends_email()
    {
        $unique = time() . '_' . rand(100, 999);
        $username = 'user_tc1_' . $unique;
        $email = 'tc1_' . $unique . '@example.com';

        $response = $this->post(route('register'), [
            'ten_dang_nhap'           => $username,
            'ten_khach_hang'          => 'Khách Hàng TC1',
            'email'                   => $email,
            'so_dien_thoai'           => '0987654321',
            'quan_huyen'              => 'Cầu Giấy',
            'xa_phuong'               => 'Dịch Vọng',
            'dia_chi_chi_tiet'        => 'Số 10 Duy Tân',
            'mat_khau'                => 'password123',
            'mat_khau_confirmation'   => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertFalse(Auth::check(), 'Người dùng không được tự động đăng nhập');

        $user = NguoiDung::where('ten_dang_nhap', $username)->first();
        $this->assertNotNull($user, 'Tài khoản người dùng phải được tạo');
        $this->assertEquals(NguoiDung::TRANG_THAI_CHO_XAC_THUC, $user->trang_thai);
        $this->assertNull($user->email_verified_at);

        $khachHang = KhachHang::where('email', $email)->first();
        $this->assertNotNull($khachHang, 'Khách hàng phải được tạo');
        $this->assertEquals($user->ma_nguoi_dung, $khachHang->ma_khach_hang);

        Mail::assertSent(VerifyEmailMailable::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    /**
     * TC2: Email đã thuộc tài khoản HOAT_DONG
     * -> không tạo account, báo email đã sử dụng
     */
    public function test_tc2_email_belongs_to_active_account_cannot_register()
    {
        $unique = time() . '_' . rand(100, 999);
        $existingEmail = 'active_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'active_user_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
            'email_verified_at' => now(),
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Active Customer',
            'so_dien_thoai'  => '0987111222',
            'email'          => $existingEmail,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $response = $this->from(route('register'))->post(route('register'), [
            'ten_dang_nhap'           => 'new_user_' . $unique,
            'ten_khach_hang'          => 'New Customer',
            'email'                   => $existingEmail,
            'so_dien_thoai'           => '0987333444',
            'quan_huyen'              => 'Cầu Giấy',
            'xa_phuong'               => 'Dịch Vọng',
            'dia_chi_chi_tiet'        => 'Số 20 Cầu Giấy',
            'mat_khau'                => 'password123',
            'mat_khau_confirmation'   => 'password123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['email']);
        $this->assertNull(NguoiDung::where('ten_dang_nhap', 'new_user_' . $unique)->first());
    }

    /**
     * TC3: Email đã thuộc tài khoản CHO_XAC_THUC
     * -> không tạo thêm account, cho phép resend
     */
    public function test_tc3_email_belongs_to_unverified_account_blocks_duplicate_and_offers_resend()
    {
        $unique = time() . '_' . rand(100, 999);
        $pendingEmail = 'pending_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'pending_u_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_CHO_XAC_THUC,
            'email_verified_at' => null,
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Pending Customer',
            'so_dien_thoai'  => '0987555666',
            'email'          => $pendingEmail,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $countBefore = NguoiDung::count();

        $response = $this->from(route('register'))->post(route('register'), [
            'ten_dang_nhap'           => 'dup_user_' . $unique,
            'ten_khach_hang'          => 'Duplicate Attempt',
            'email'                   => $pendingEmail,
            'so_dien_thoai'           => '0987777888',
            'quan_huyen'              => 'Cầu Giấy',
            'xa_phuong'               => 'Dịch Vọng',
            'dia_chi_chi_tiet'        => 'Số 30 Cầu Giấy',
            'mat_khau'                => 'password123',
            'mat_khau_confirmation'   => 'password123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHas('status_warning');
        $response->assertSessionHas('resend_email', $pendingEmail);
        $this->assertEquals($countBefore, NguoiDung::count(), 'Không được tạo thêm bản ghi người dùng');
    }

    /**
     * TC4: Verification link hợp lệ
     * -> email verified, chuyển trạng thái HOAT_DONG
     */
    public function test_tc4_valid_verification_link_activates_account()
    {
        $unique = time() . '_' . rand(100, 999);
        $email = 'verify_tc4_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'user_tc4_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_CHO_XAC_THUC,
            'email_verified_at' => null,
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Customer TC4',
            'so_dien_thoai'  => '0987000111',
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->ma_nguoi_dung,
                'hash' => sha1($email),
            ]
        );

        $response = $this->get($url);
        $response->assertOk();
        $response->assertViewIs('auth.verify-status');
        $response->assertViewHas('state', 'success');

        $user->refresh();
        $this->assertEquals(NguoiDung::TRANG_THAI_HOAT_DONG, $user->trang_thai);
        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * TC5: Link invalid (sai hash hoặc sai signature)
     * -> không active
     */
    public function test_tc5_invalid_verification_link_does_not_activate_account()
    {
        $unique = time() . '_' . rand(100, 999);
        $email = 'verify_tc5_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'user_tc5_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_CHO_XAC_THUC,
            'email_verified_at' => null,
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Customer TC5',
            'so_dien_thoai'  => '0987000222',
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $invalidUrl = route('verification.verify', [
            'id'   => $user->ma_nguoi_dung,
            'hash' => 'invalid_hash_value',
        ]) . '?signature=fake_tampered_signature';

        $response = $this->get($invalidUrl);
        $response->assertOk();
        $response->assertViewIs('auth.verify-status');
        $response->assertViewHas('state', 'invalid_or_expired');

        $user->refresh();
        $this->assertEquals(NguoiDung::TRANG_THAI_CHO_XAC_THUC, $user->trang_thai);
        $this->assertNull($user->email_verified_at);
    }

    /**
     * TC6: Link expired
     * -> không active
     */
    public function test_tc6_expired_verification_link_does_not_activate_account()
    {
        $unique = time() . '_' . rand(100, 999);
        $email = 'verify_tc6_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'user_tc6_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_CHO_XAC_THUC,
            'email_verified_at' => null,
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Customer TC6',
            'so_dien_thoai'  => '0987000333',
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        // Tạo link đã hết hạn từ 10 phút trước
        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(10),
            [
                'id'   => $user->ma_nguoi_dung,
                'hash' => sha1($email),
            ]
        );

        $response = $this->get($expiredUrl);
        $response->assertOk();
        $response->assertViewIs('auth.verify-status');
        $response->assertViewHas('state', 'invalid_or_expired');

        $user->refresh();
        $this->assertEquals(NguoiDung::TRANG_THAI_CHO_XAC_THUC, $user->trang_thai);
        $this->assertNull($user->email_verified_at);
    }

    /**
     * TC7: Resend cho account CHO_XAC_THUC
     * -> gửi email mới thành công
     */
    public function test_tc7_resend_verification_email_for_unverified_account_succeeds()
    {
        $unique = time() . '_' . rand(100, 999);
        $email = 'resend_tc7_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'user_tc7_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_CHO_XAC_THUC,
            'email_verified_at' => null,
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Customer TC7',
            'so_dien_thoai'  => '0987000444',
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $response = $this->post(route('verification.resend'), [
            'email' => $email,
        ]);

        $response->assertSessionHas('status');
        Mail::assertSent(VerifyEmailMailable::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    /**
     * TC8: Resend cho account HOAT_DONG
     * -> từ chối gửi lại email
     */
    public function test_tc8_resend_verification_email_for_active_account_is_rejected()
    {
        $unique = time() . '_' . rand(100, 999);
        $email = 'active_tc8_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'user_tc8_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
            'email_verified_at' => now(),
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Customer TC8',
            'so_dien_thoai'  => '0987000555',
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $response = $this->post(route('verification.resend'), [
            'email' => $email,
        ]);

        $response->assertSessionHas('info');
        Mail::assertNotSent(VerifyEmailMailable::class);
    }

    /**
     * TC9: Username/password đúng nhưng CHO_XAC_THUC
     * -> không cho login, báo tài khoản chưa xác thực
     */
    public function test_tc9_login_with_valid_credentials_but_unverified_account_is_blocked()
    {
        $unique = time() . '_' . rand(100, 999);
        $username = 'login_tc9_' . $unique;
        $password = 'password123';
        $email = 'login_tc9_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => $username,
            'mat_khau'          => $password,
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_CHO_XAC_THUC,
            'email_verified_at' => null,
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Customer TC9',
            'so_dien_thoai'  => '0987000666',
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'ten_dang_nhap' => $username,
            'password'      => $password,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('unverified', true);
        $response->assertSessionHas('unverified_email', $email);
        $response->assertSessionHasErrors(['ten_dang_nhap']);
        $this->assertFalse(Auth::check(), 'Người dùng chưa xác thực không được đăng nhập');
    }

    /**
     * TC10: Username/password đúng + HOAT_DONG
     * -> login bình thường
     */
    public function test_tc10_login_with_valid_credentials_and_active_account_succeeds()
    {
        $unique = time() . '_' . rand(100, 999);
        $username = 'login_tc10_' . $unique;
        $password = 'password123';
        $email = 'login_tc10_' . $unique . '@example.com';

        $user = NguoiDung::create([
            'ten_dang_nhap'     => $username,
            'mat_khau'          => $password,
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
            'email_verified_at' => now(),
        ]);
        KhachHang::create([
            'ma_khach_hang'  => $user->ma_nguoi_dung,
            'ten_khach_hang' => 'Customer TC10',
            'so_dien_thoai'  => '0987000777',
            'email'          => $email,
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $response = $this->post(route('login'), [
            'ten_dang_nhap' => $username,
            'password'      => $password,
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertTrue(Auth::check(), 'Người dùng kích hoạt phải đăng nhập thành công');
        $this->assertEquals($user->ma_nguoi_dung, Auth::id());
    }

    /**
     * TC11: Mail service lỗi
     * -> account vẫn được tạo ở trạng thái CHO_XAC_THUC, không bị rollback/xóa
     */
    public function test_tc11_mail_service_failure_keeps_account_in_unverified_status()
    {
        $unique = time() . '_' . rand(100, 999);
        $username = 'user_tc11_' . $unique;
        $email = 'tc11_' . $unique . '@example.com';

        // Giả lập Mail gặp lỗi exception
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP Connection Error 500'));

        $response = $this->post(route('register'), [
            'ten_dang_nhap'           => $username,
            'ten_khach_hang'          => 'Customer TC11',
            'email'                   => $email,
            'so_dien_thoai'           => '0987000888',
            'quan_huyen'              => 'Cầu Giấy',
            'xa_phuong'               => 'Dịch Vọng',
            'dia_chi_chi_tiet'        => 'Số 50 Duy Tân',
            'mat_khau'                => 'password123',
            'mat_khau_confirmation'   => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('warning');

        $user = NguoiDung::where('ten_dang_nhap', $username)->first();
        $this->assertNotNull($user, 'Tài khoản vẫn phải tồn tại sau khi gửi mail thất bại');
        $this->assertEquals(NguoiDung::TRANG_THAI_CHO_XAC_THUC, $user->trang_thai);
        $this->assertNull($user->email_verified_at);
    }

    /**
     * TC12: Database lỗi khi tạo account
     * -> transaction rollback đúng, không lưu dữ liệu dở dang
     */
    public function test_tc12_database_error_rolls_back_transaction()
    {
        $unique = time() . '_' . rand(100, 999);
        $username = 'user_tc12_' . $unique;
        $email = 'tc12_' . $unique . '@example.com';

        // Mô phỏng lỗi DB bằng việc can thiệp sự kiện creating của KhachHang để ném Exception
        KhachHang::creating(function () {
            throw new \Exception('Database write disk full error!');
        });

        $countUsersBefore = NguoiDung::count();
        $countCustomersBefore = KhachHang::count();

        $response = $this->from(route('register'))->post(route('register'), [
            'ten_dang_nhap'           => $username,
            'ten_khach_hang'          => 'Customer TC12',
            'email'                   => $email,
            'so_dien_thoai'           => '0987000999',
            'quan_huyen'              => 'Cầu Giấy',
            'xa_phuong'               => 'Dịch Vọng',
            'dia_chi_chi_tiet'        => 'Số 60 Duy Tân',
            'mat_khau'                => 'password123',
            'mat_khau_confirmation'   => 'password123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['general']);

        $this->assertEquals($countUsersBefore, NguoiDung::count(), 'NguoiDung phải được rollback');
        $this->assertEquals($countCustomersBefore, KhachHang::count(), 'KhachHang phải được rollback');
    }

    /**
     * TC E2E: Toàn bộ luồng đăng ký -> gửi mail -> thử login bị chặn -> click link xác thực -> kích hoạt HOAT_DONG -> đăng nhập thành công
     */
    public function test_tc13_full_end_to_end_flow_register_mail_verify_login()
    {
        $unique = time() . '_' . rand(100, 999);
        $username = 'e2e_user_' . $unique;
        $password = 'Secret123!';
        $email = 'e2e_' . $unique . '@example.com';

        // 1. Đăng ký
        $registerRes = $this->post(route('register'), [
            'ten_dang_nhap'           => $username,
            'ten_khach_hang'          => 'E2E Full Flow Tester',
            'email'                   => $email,
            'so_dien_thoai'           => '0912345678',
            'quan_huyen'              => 'Cầu Giấy',
            'xa_phuong'               => 'Dịch Vọng',
            'dia_chi_chi_tiet'        => 'Số 99 Cầu Giấy',
            'mat_khau'                => $password,
            'mat_khau_confirmation'   => $password,
        ]);
        $registerRes->assertRedirect(route('verification.notice'));
        $this->assertFalse(Auth::check(), 'Chưa đăng nhập sau khi đăng ký');

        // 2. Thử đăng nhập ngay -> Bị chặn vì CHO_XAC_THUC
        $loginBlockedRes = $this->from(route('login'))->post(route('login'), [
            'ten_dang_nhap' => $username,
            'password'      => $password,
        ]);
        $loginBlockedRes->assertRedirect(route('login'));
        $loginBlockedRes->assertSessionHas('unverified', true);
        $this->assertFalse(Auth::check(), 'Bị chặn không cho đăng nhập khi chưa xác thực');

        // 3. Trích xuất link xác thực từ email đã gửi
        $verificationUrl = null;
        Mail::assertSent(VerifyEmailMailable::class, function ($mail) use ($email, &$verificationUrl) {
            if ($mail->hasTo($email)) {
                $verificationUrl = $mail->verificationUrl;
                return true;
            }
            return false;
        });
        $this->assertNotNull($verificationUrl, 'Phải có link xác thực được gửi trong mail');

        // 4. Khách hàng nhấp vào liên kết xác thực
        $verifyRes = $this->get($verificationUrl);
        $verifyRes->assertOk();
        $verifyRes->assertViewIs('auth.verify-status');
        $verifyRes->assertViewHas('state', 'success');

        // 5. Kiểm tra DB: tài khoản đã chuyển sang HOAT_DONG
        $user = NguoiDung::where('ten_dang_nhap', $username)->first();
        $this->assertEquals(NguoiDung::TRANG_THAI_HOAT_DONG, $user->trang_thai);
        $this->assertNotNull($user->email_verified_at);

        // 6. Đăng nhập lại với tài khoản vừa xác thực -> Đăng nhập thành công
        $loginSuccessRes = $this->post(route('login'), [
            'ten_dang_nhap' => $username,
            'password'      => $password,
        ]);
        $loginSuccessRes->assertRedirect(route('customer.dashboard'));
        $this->assertTrue(Auth::check(), 'Đăng nhập thành công sau khi xác thực');
        $this->assertEquals($user->ma_nguoi_dung, Auth::id());
    }
}
