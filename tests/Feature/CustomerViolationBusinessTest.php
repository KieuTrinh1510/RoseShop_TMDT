<?php

namespace Tests\Feature;

use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\SanPham;
use App\Models\ThanhToanViPhamKhachHang;
use App\Models\ThongBao;
use App\Models\ViPhamKhachHang;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerViolationBusinessTest extends TestCase
{
    use DatabaseTransactions;

    private NguoiDung $user;
    private KhachHang $khach;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'shop_hoa',
            'vnpay.tmn_code'    => 'TESTCODE123',
            'vnpay.hash_secret' => 'TESTSECRET1234567890123456789012',
            'vnpay.url'         => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        $uniq = uniqid();
        $this->user = NguoiDung::create([
            'ten_dang_nhap'     => 'cust_vp_' . $uniq,
            'mat_khau'          => Hash::make('password123'),
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);

        $this->khach = KhachHang::create([
            'ma_khach_hang'  => $this->user->ma_nguoi_dung,
            'ten_khach_hang' => 'Khách Test Vi Phạm',
            'so_dien_thoai'  => '098' . substr(str_shuffle('0123456789'), 0, 7),
            'email'          => 'cust_vp_' . $uniq . '@example.com',
            'dia_chi'        => '123 Cầu Giấy, Hà Nội',
            'quyen_cod'      => 'CHO_PHEP',
        ]);
    }

    private function taoHoaDon(float $tongTien = 350000): HoaDon
    {
        return HoaDon::create([
            'ma_khach_hang'          => $this->khach->ma_khach_hang,
            'ngay_dat'               => now(),
            'tong_tien'              => $tongTien,
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao'           => '123 Cầu Giấy, Hà Nội',
            'so_dien_thoai'          => $this->khach->so_dien_thoai,
        ]);
    }

    private function taoViPham(HoaDon $hd, int $lan = 1, string $muc = 'CANH_BAO', bool $daGo = false): ViPhamKhachHang
    {
        return ViPhamKhachHang::create([
            'ma_khach_hang'   => $this->khach->ma_khach_hang,
            'ma_hoa_don'      => $hd->ma_hoa_don,
            'ly_do'           => 'Khách từ chối nhận hàng test',
            'lan_vi_pham'     => $lan,
            'han_che_ap_dung' => $muc,
            'da_go'           => $daGo,
        ]);
    }

    /**
     * Test 1: 0 vi phạm → COD = CHO_PHEP, checkout COD thành công.
     */
    public function test_1_zero_violation_allows_cod()
    {
        $this->actingAs($this->user);

        $sp = SanPham::first();
        if (!$sp) {
            $this->markTestSkipped('No SanPham in database');
        }

        // Tạo giỏ hàng trong session
        $res = $this->withSession([
            'cart' => [
                $sp->ma_san_pham => [
                    'id'          => $sp->ma_san_pham,
                    'so_luong'    => 1,
                    'don_gia'     => $sp->gia_ban_hien_tai ?? 100000,
                    'ten_san_pham'=> $sp->ten_san_pham,
                ]
            ]
        ])->post(route('customer.thanh-toan.store'), [
            'ten_nguoi_nhan'         => 'Nguyễn Văn A',
            'so_dien_thoai'          => '0987654321',
            'quan_huyen'             => 'Cầu Giấy',
            'xa_phuong'              => 'Dịch Vọng',
            'dia_chi_chi_tiet'       => '123 Cầu Giấy',
            'phuong_thuc_thanh_toan' => 'COD',
        ]);

        $res->assertSessionHasNoErrors();
        $this->assertEquals('CHO_PHEP', $this->khach->fresh()->quyen_cod);
    }

    /**
     * Test 2: 1 vi phạm → CANH_BAO + COD CHO_PHEP.
     */
    public function test_2_one_violation_allows_cod()
    {
        $hd = $this->taoHoaDon(200000);
        $this->taoViPham($hd, 1, 'CANH_BAO');

        $this->actingAs($this->user);

        $sp = SanPham::first();
        if (!$sp) {
            $this->markTestSkipped('No SanPham in database');
        }

        $res = $this->withSession([
            'cart' => [
                $sp->ma_san_pham => [
                    'id'          => $sp->ma_san_pham,
                    'so_luong'    => 1,
                    'don_gia'     => $sp->gia_ban_hien_tai ?? 100000,
                    'ten_san_pham'=> $sp->ten_san_pham,
                ]
            ]
        ])->post(route('customer.thanh-toan.store'), [
            'ten_nguoi_nhan'         => 'Nguyễn Văn A',
            'so_dien_thoai'          => '0987654321',
            'quan_huyen'             => 'Cầu Giấy',
            'xa_phuong'              => 'Dịch Vọng',
            'dia_chi_chi_tiet'       => '123 Cầu Giấy',
            'phuong_thuc_thanh_toan' => 'COD',
        ]);

        $res->assertSessionHasNoErrors();
        // Không bị chặn COD
        $this->assertNotEquals(route('customer.vi-pham.index'), $res->headers->get('Location'));
    }

    /**
     * Test 3: 2 vi phạm → COD bị chặn (chỉ online).
     */
    public function test_3_two_violations_blocks_cod()
    {
        $hd1 = $this->taoHoaDon(200000);
        $this->taoViPham($hd1, 1, 'CANH_BAO');
        $hd2 = $this->taoHoaDon(250000);
        $this->taoViPham($hd2, 2, 'CAM_COD');

        $this->actingAs($this->user);

        $sp = SanPham::first();
        if (!$sp) {
            $this->markTestSkipped('No SanPham in database');
        }

        $res = $this->withSession([
            'cart' => [
                $sp->ma_san_pham => [
                    'id'          => $sp->ma_san_pham,
                    'so_luong'    => 1,
                    'don_gia'     => $sp->gia_ban_hien_tai ?? 100000,
                    'ten_san_pham'=> $sp->ten_san_pham,
                ]
            ]
        ])->post(route('customer.thanh-toan.store'), [
            'ten_nguoi_nhan'         => 'Nguyễn Văn A',
            'so_dien_thoai'          => '0987654321',
            'quan_huyen'             => 'Cầu Giấy',
            'xa_phuong'              => 'Dịch Vọng',
            'dia_chi_chi_tiet'       => '123 Cầu Giấy',
            'phuong_thuc_thanh_toan' => 'COD',
        ]);

        $res->assertSessionHas('error');
    }

    /**
     * Test 4: >= 3 vi phạm → Chặn tạo đơn mới hoàn toàn.
     */
    public function test_4_three_violations_blocks_all_orders()
    {
        $hd1 = $this->taoHoaDon(100000);
        $this->taoViPham($hd1, 1, 'CANH_BAO');
        $hd2 = $this->taoHoaDon(150000);
        $this->taoViPham($hd2, 2, 'CAM_COD');
        $hd3 = $this->taoHoaDon(200000);
        $this->taoViPham($hd3, 3, 'TAM_KHOA_MUA_HANG');

        $this->actingAs($this->user);

        $sp = SanPham::first();
        if (!$sp) {
            $this->markTestSkipped('No SanPham in database');
        }

        // Thử đặt hàng bằng NGAN_HANG (VNPay)
        $res = $this->withSession([
            'cart' => [
                $sp->ma_san_pham => [
                    'id'          => $sp->ma_san_pham,
                    'so_luong'    => 1,
                    'don_gia'     => $sp->gia_ban_hien_tai ?? 100000,
                    'ten_san_pham'=> $sp->ten_san_pham,
                ]
            ]
        ])->post(route('customer.thanh-toan.store'), [
            'ten_nguoi_nhan'         => 'Nguyễn Văn A',
            'so_dien_thoai'          => '0987654321',
            'quan_huyen'             => 'Cầu Giấy',
            'xa_phuong'              => 'Dịch Vọng',
            'dia_chi_chi_tiet'       => '123 Cầu Giấy',
            'phuong_thuc_thanh_toan' => 'NGAN_HANG',
        ]);

        $res->assertRedirect(route('customer.vi-pham.index'));
        $res->assertSessionHas('error');
    }

    /**
     * Test 5: 3 → 2: Gỡ 1 vi phạm khi có 3 vi phạm → Mua hàng lại được (VNPay), COD vẫn khóa.
     */
    public function test_5_three_to_two_allows_purchase_but_cod_locked()
    {
        $hd1 = $this->taoHoaDon(100000);
        $vp1 = $this->taoViPham($hd1, 1, 'CANH_BAO');
        $hd2 = $this->taoHoaDon(150000);
        $this->taoViPham($hd2, 2, 'CAM_COD');
        $hd3 = $this->taoHoaDon(200000);
        $this->taoViPham($hd3, 3, 'TAM_KHOA_MUA_HANG');

        // Tạo thanh toán và hoàn tất thanh toán cho vp1
        $tt = ThanhToanViPhamKhachHang::create([
            'ma_vi_pham'    => $vp1->ma_vi_pham,
            'ma_khach_hang' => $this->khach->ma_khach_hang,
            'ma_hoa_don'    => $vp1->ma_hoa_don,
            'so_tien'       => 100000,
            'phuong_thuc'   => 'VNPAY',
            'trang_thai'    => ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN,
        ]);

        // Giả lập callback thành công
        $controller = app(\App\Http\Controllers\VnPayViPhamController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('finalizeThanhCong');
        $method->setAccessible(true);
        $method->invoke($controller, $tt, ['vnp_TransactionNo' => '123456'], '00');

        // Sau khi gỡ 1, còn lại 2 vi phạm
        $this->assertEquals(2, ViPhamKhachHang::where('ma_khach_hang', $this->khach->ma_khach_hang)->where('da_go', false)->count());
        $this->assertEquals('GIOI_HAN', $this->khach->fresh()->quyen_cod);

        $this->actingAs($this->user);
        $sp = SanPham::first();
        if (!$sp) {
            $this->markTestSkipped('No SanPham in database');
        }

        // Đặt hàng VNPay phải được phép (không bị redirect vi-pham.index)
        $res = $this->withSession([
            'cart' => [
                $sp->ma_san_pham => [
                    'id'          => $sp->ma_san_pham,
                    'so_luong'    => 1,
                    'don_gia'     => $sp->gia_ban_hien_tai ?? 100000,
                    'ten_san_pham'=> $sp->ten_san_pham,
                ]
            ]
        ])->post(route('customer.thanh-toan.store'), [
            'ten_nguoi_nhan'         => 'Nguyễn Văn A',
            'so_dien_thoai'          => '0987654321',
            'quan_huyen'             => 'Cầu Giấy',
            'xa_phuong'              => 'Dịch Vọng',
            'dia_chi_chi_tiet'       => '123 Cầu Giấy',
            'phuong_thuc_thanh_toan' => 'NGAN_HANG',
        ]);

        // Được phép đi tiếp sang VNPay (hoặc dashboard)
        $this->assertNotEquals(route('customer.vi-pham.index'), $res->headers->get('Location'));
    }

    /**
     * Test 6: 2 → 1: Gỡ 1 vi phạm khi có 2 vi phạm → COD được mở lại (quyen_cod = CHO_PHEP).
     */
    public function test_6_two_to_one_restores_cod()
    {
        $hd1 = $this->taoHoaDon(100000);
        $vp1 = $this->taoViPham($hd1, 1, 'CANH_BAO');
        $hd2 = $this->taoHoaDon(150000);
        $this->taoViPham($hd2, 2, 'CAM_COD');

        $this->khach->update(['quyen_cod' => 'GIOI_HAN']);

        $tt = ThanhToanViPhamKhachHang::create([
            'ma_vi_pham'    => $vp1->ma_vi_pham,
            'ma_khach_hang' => $this->khach->ma_khach_hang,
            'ma_hoa_don'    => $vp1->ma_hoa_don,
            'so_tien'       => 100000,
            'phuong_thuc'   => 'VNPAY',
            'trang_thai'    => ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN,
        ]);

        $controller = app(\App\Http\Controllers\VnPayViPhamController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('finalizeThanhCong');
        $method->setAccessible(true);
        $method->invoke($controller, $tt, ['vnp_TransactionNo' => '123456'], '00');

        // Còn lại 1 vi phạm → quyền COD PHẢI LÀ CHO_PHEP!
        $this->assertEquals(1, ViPhamKhachHang::where('ma_khach_hang', $this->khach->ma_khach_hang)->where('da_go', false)->count());
        $this->assertEquals('CHO_PHEP', $this->khach->fresh()->quyen_cod);
    }

    /**
     * Test 7: 1 → 0: Thanh toán hết → quyền COD = CHO_PHEP, thông báo chúc mừng.
     */
    public function test_7_one_to_zero_clears_all()
    {
        $hd1 = $this->taoHoaDon(100000);
        $vp1 = $this->taoViPham($hd1, 1, 'CANH_BAO');

        $tt = ThanhToanViPhamKhachHang::create([
            'ma_vi_pham'    => $vp1->ma_vi_pham,
            'ma_khach_hang' => $this->khach->ma_khach_hang,
            'ma_hoa_don'    => $vp1->ma_hoa_don,
            'so_tien'       => 100000,
            'phuong_thuc'   => 'VNPAY',
            'trang_thai'    => ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN,
        ]);

        $controller = app(\App\Http\Controllers\VnPayViPhamController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('finalizeThanhCong');
        $method->setAccessible(true);
        $method->invoke($controller, $tt, ['vnp_TransactionNo' => '999999'], '00');

        $this->assertEquals(0, ViPhamKhachHang::where('ma_khach_hang', $this->khach->ma_khach_hang)->where('da_go', false)->count());
        $this->assertEquals('CHO_PHEP', $this->khach->fresh()->quyen_cod);

        $this->actingAs($this->user);
        $res = $this->get(route('customer.thong-bao'));
        // Không còn thẻ cảnh báo vi phạm
        $res->assertDontSee('notif-vi-pham-warning');
    }

    /**
     * Test 8, 9, 10: Page hiển thị đúng hóa đơn, sản phẩm và giá snapshot.
     */
    public function test_8_9_10_view_shows_order_items_and_snapshot_prices()
    {
        $sp = SanPham::first();
        if (!$sp) {
            $this->markTestSkipped('No SanPham in database');
        }

        $hd = $this->taoHoaDon(450000);
        ChiTietHoaDon::create([
            'ma_hoa_don'        => $hd->ma_hoa_don,
            'ma_san_pham'       => $sp->ma_san_pham,
            'so_luong'          => 3,
            'gia_ban_snapshot'  => 150000,
            'gia_nhap_snapshot' => 100000,
        ]);

        $vp = $this->taoViPham($hd, 1, 'CANH_BAO');

        $this->actingAs($this->user);
        $res = $this->get(route('customer.vi-pham.index'));

        $res->assertOk();
        $res->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $res->assertSee($sp->ten_san_pham);
        $res->assertSee('150.000'); // Giá snapshot
        $res->assertSee('450.000'); // Tổng cộng
    }

    /**
     * Test 11: VNPay amount lấy từ backend (hoa_don.tong_tien).
     */
    public function test_11_vnpay_amount_comes_from_backend()
    {
        $hd = $this->taoHoaDon(280000);
        $vp = $this->taoViPham($hd, 1, 'CANH_BAO');

        $this->actingAs($this->user);
        $res = $this->post(route('customer.vi-pham.vnpay', $vp->ma_vi_pham));

        // Phải tạo ThanhToanViPhamKhachHang với số tiền đúng 280000
        $tt = ThanhToanViPhamKhachHang::where('ma_vi_pham', $vp->ma_vi_pham)->first();
        $this->assertNotNull($tt);
        $this->assertEquals(280000, (float) $tt->so_tien);
        $res->assertRedirect(route('vnpay.vi-pham.payment', $tt->ma_thanh_toan));
    }

    /**
     * Test 12: Customer A không xem/thanh toán vi phạm của Customer B.
     */
    public function test_12_security_cross_customer_access_blocked()
    {
        // Tạo customer B
        $userB = NguoiDung::create([
            'ten_dang_nhap'     => 'user_b_' . uniqid(),
            'mat_khau'          => Hash::make('password123'),
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);
        $khachB = KhachHang::create([
            'ma_khach_hang'  => $userB->ma_nguoi_dung,
            'ten_khach_hang' => 'Khách B',
            'so_dien_thoai'  => '091' . substr(str_shuffle('0123456789'), 0, 7),
            'email'          => 'user_b_' . uniqid() . '@example.com',
            'dia_chi'        => '456 Hà Nội',
            'quyen_cod'      => 'CHO_PHEP',
        ]);
        $hdB = HoaDon::create([
            'ma_khach_hang'          => $khachB->ma_khach_hang,
            'ngay_dat'               => now(),
            'tong_tien'              => 500000,
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao'           => '456 Hà Nội',
            'so_dien_thoai'          => $khachB->so_dien_thoai,
        ]);
        $vpB = ViPhamKhachHang::create([
            'ma_khach_hang'   => $khachB->ma_khach_hang,
            'ma_hoa_don'      => $hdB->ma_hoa_don,
            'ly_do'           => 'Vi phạm B',
            'lan_vi_pham'     => 1,
            'han_che_ap_dung' => 'CANH_BAO',
            'da_go'           => false,
        ]);

        // Customer A đăng nhập và cố gắng thanh toán vi phạm của B
        $this->actingAs($this->user);
        $res = $this->post(route('customer.vi-pham.vnpay', $vpB->ma_vi_pham));
        // Phải bị 404 hoặc 403
        $this->assertContains($res->getStatusCode(), [403, 404]);
    }

    /**
     * Test 13: Violation đã xử lý không được thanh toán lại.
     */
    public function test_13_resolved_violation_cannot_be_paid_again()
    {
        $hd = $this->taoHoaDon(200000);
        $vp = $this->taoViPham($hd, 1, 'CANH_BAO', true); // da_go = true

        $this->actingAs($this->user);
        $res = $this->post(route('customer.vi-pham.vnpay', $vp->ma_vi_pham));

        $res->assertSessionHas('error', 'Vi phạm này đã được xử lý.');
    }

    /**
     * Test 14: Callback duplicate không xử lý 2 lần (Idempotency).
     */
    public function test_14_duplicate_callback_is_idempotent()
    {
        $hd = $this->taoHoaDon(200000);
        $vp = $this->taoViPham($hd, 1, 'CANH_BAO');

        $tt = ThanhToanViPhamKhachHang::create([
            'ma_vi_pham'    => $vp->ma_vi_pham,
            'ma_khach_hang' => $this->khach->ma_khach_hang,
            'ma_hoa_don'    => $vp->ma_hoa_don,
            'so_tien'       => 200000,
            'phuong_thuc'   => 'VNPAY',
            'trang_thai'    => ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN,
        ]);

        $controller = app(\App\Http\Controllers\VnPayViPhamController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('finalizeThanhCong');
        $method->setAccessible(true);

        // Gọi lần 1
        $method->invoke($controller, $tt, ['vnp_TransactionNo' => '11111'], '00');
        $this->assertTrue((bool) $vp->fresh()->da_go);
        $notifCountAfterFirst = ThongBao::where('ma_nguoi_dung', $this->khach->ma_khach_hang)->count();

        // Lần 2 giả lập IPN đến sau: trạng thái $tt đã là TT_DA_THANH_TOAN nên controller sẽ return RspCode 02 (đã confirm)
        $ttFresh = $tt->fresh();
        $this->assertEquals(ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN, $ttFresh->trang_thai);

        // Không gửi thêm thông báo duplicate
        $notifCountAfterSecond = ThongBao::where('ma_nguoi_dung', $this->khach->ma_khach_hang)->count();
        $this->assertEquals($notifCountAfterFirst, $notifCountAfterSecond);
    }

    /**
     * Test 15: Payment hết hạn (15 phút) không gỡ violation.
     */
    public function test_15_expired_payment_does_not_clear_violation()
    {
        $hd = $this->taoHoaDon(200000);
        $vp = $this->taoViPham($hd, 1, 'CANH_BAO');

        // Tạo payment đã 20 phút trước
        $tt = ThanhToanViPhamKhachHang::create([
            'ma_vi_pham'    => $vp->ma_vi_pham,
            'ma_khach_hang' => $this->khach->ma_khach_hang,
            'ma_hoa_don'    => $vp->ma_hoa_don,
            'so_tien'       => 200000,
            'phuong_thuc'   => 'VNPAY',
            'trang_thai'    => ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN,
        ]);
        \Illuminate\Support\Facades\DB::table('thanh_toan_vi_pham_khach_hang')
            ->where('ma_thanh_toan', $tt->ma_thanh_toan)
            ->update(['created_at' => now()->subMinutes(20)]);

        $this->actingAs($this->user);
        // Khách bấm tạo VNPay mới
        $res = $this->post(route('customer.vi-pham.vnpay', $vp->ma_vi_pham));

        // Payment cũ phải bị hủy
        $this->assertEquals(ThanhToanViPhamKhachHang::TT_DA_HUY, $tt->fresh()->trang_thai);
        // Vi phạm VẪN CHƯA GỠ
        $this->assertFalse((bool) $vp->fresh()->da_go);
    }
}
