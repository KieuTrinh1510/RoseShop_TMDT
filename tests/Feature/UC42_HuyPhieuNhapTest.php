<?php

namespace Tests\Feature;

use App\Models\ChiTietNhap;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UC42_HuyPhieuNhapTest extends TestCase
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
    }

    protected function createUser(string $role, string $prefix = 'user'): NguoiDung
    {
        $unique = microtime(true) . '_' . mt_rand(100000, 999999);
        $user = NguoiDung::create([
            'ten_dang_nhap'     => substr($prefix . '_' . $unique, 0, 50),
            'mat_khau'          => 'password123',
            'vai_tro'           => $role,
            'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
            'email_verified_at' => now(),
        ]);

        if (in_array($role, ['NHAN_VIEN', 'SHIPPER'])) {
            NhanVien::create([
                'ma_nhan_vien'  => $user->ma_nguoi_dung,
                'ten_nhan_vien' => 'Nhân viên ' . $unique,
                'chuc_vu'       => $role === 'SHIPPER' ? 'SHIPPER' : 'VAN_HANH',
                'email'         => $prefix . '_' . $unique . '@example.com',
                'so_dien_thoai' => '09' . rand(10000000, 99999999),
                'luong'         => 10000000,
            ]);
        }

        return $user;
    }

    protected function getOperationalEmployeeId(): int
    {
        $nv = NhanVien::where('chuc_vu', 'VAN_HANH')->first();
        if ($nv) {
            return $nv->ma_nhan_vien;
        }

        $user = $this->createUser('NHAN_VIEN', 'staff_op');
        return $user->ma_nguoi_dung;
    }

    protected function createProduct(string $name, int $initialStock = 10): SanPham
    {
        return SanPham::create([
            'ten_san_pham'  => $name . '_' . time() . '_' . rand(100, 999),
            'so_luong'      => $initialStock,
            'don_vi_tinh'   => SanPham::DVT_CANH,
            'loai_san_pham' => SanPham::LOAI_HOA_TUOI,
            'mo_ta'         => 'Test product for UC4.2',
            'trang_thai'    => 'DANG_BAN',
        ]);
    }

    protected function createDraftPhieuNhap(): array
    {
        $employeeId = $this->getOperationalEmployeeId();
        $ncc = NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Test ' . rand(100, 999),
            'so_dien_thoai'    => '0988776655',
            'email'            => 'ncc_' . rand(100, 999) . '@test.com',
            'dia_chi'          => 'Lâm Đồng',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $sp = $this->createProduct('Hoa Hồng Red Naomi', 20);

        $pn = PhieuNhap::create([
            'ngay_nhap'         => now(),
            'ma_nhan_vien'      => $employeeId,
            'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
            'email_ncc'         => $ncc->email,
            'dia_chi_ncc'       => $ncc->dia_chi,
            'trang_thai'        => PhieuNhap::TRANG_THAI_DRAFT,
        ]);

        $ctn = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => 50,
            'gia_nhap'         => 12000,
            'gia_ban'          => 18000,
            'so_luong_con_lai' => 50,
        ]);

        return [$pn, $sp, $ctn, $ncc];
    }

    /**
     * Test 1: DRAFT có thể cancel.
     */
    public function test_1_draft_can_transition_to_cancelled()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn] = $this->createDraftPhieuNhap();

        $response = $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Nhà cung cấp hết hàng',
        ]);

        $response->assertRedirect(route('phieu-nhap.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(PhieuNhap::TRANG_THAI_CANCELLED, $pn->fresh()->trang_thai);
    }

    /**
     * Test 2: Cancel bắt buộc có lý do (không được rỗng hoặc chỉ khoảng trắng).
     */
    public function test_2_cancel_requires_reason()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn] = $this->createDraftPhieuNhap();

        // 1. Gửi request không có lý do
        $response1 = $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => '',
        ]);
        $response1->assertSessionHasErrors(['ly_do_huy']);
        $this->assertEquals(PhieuNhap::TRANG_THAI_DRAFT, $pn->fresh()->trang_thai);

        // 2. Gửi request chỉ có khoảng trắng
        $response2 = $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => '    ',
        ]);
        $response2->assertSessionHasErrors(['ly_do_huy']);
        $this->assertEquals(PhieuNhap::TRANG_THAI_DRAFT, $pn->fresh()->trang_thai);
    }

    /**
     * Test 3: Sau cancel: trạng thái = CANCELLED.
     */
    public function test_3_status_becomes_cancelled_after_cancel()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn] = $this->createDraftPhieuNhap();

        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Nhập nhầm phiếu',
        ]);

        $fresh = $pn->fresh();
        $this->assertSame(PhieuNhap::TRANG_THAI_CANCELLED, $fresh->trang_thai);
        $this->assertTrue($fresh->isCancelled());
        $this->assertFalse($fresh->isDraft());
        $this->assertFalse($fresh->isConfirmed());
    }

    /**
     * Test 4: Sau cancel: lưu đúng lý do.
     */
    public function test_4_saves_cancellation_reason_properly()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn] = $this->createDraftPhieuNhap();

        $reason = 'Sai đơn giá nhập so với hợp đồng NCC';
        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => '  ' . $reason . '  ',
        ]);

        $fresh = $pn->fresh();
        $this->assertSame($reason, $fresh->ly_do_huy);
    }

    /**
     * Test 5: Sau cancel: lưu người hủy.
     */
    public function test_5_saves_cancelling_user()
    {
        $admin = $this->createUser('ADMIN', 'admin_canceller');
        [$pn] = $this->createDraftPhieuNhap();

        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Hủy do kiểm kê lại kho',
        ]);

        $fresh = $pn->fresh();
        $this->assertSame($admin->ma_nguoi_dung, (int) $fresh->nguoi_huy_id);
        $this->assertNotNull($fresh->nguoiHuy);
    }

    /**
     * Test 6: Sau cancel: lưu thời gian hủy.
     */
    public function test_6_saves_cancellation_timestamp()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn] = $this->createDraftPhieuNhap();

        $beforeTime = now()->subSeconds(2);
        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Thời gian hủy được ghi nhận tự động',
        ]);
        $afterTime = now()->addSeconds(2);

        $fresh = $pn->fresh();
        $this->assertNotNull($fresh->huy_luc);
        $this->assertTrue($fresh->huy_luc->greaterThanOrEqualTo($beforeTime));
        $this->assertTrue($fresh->huy_luc->lessThanOrEqualTo($afterTime));
    }

    /**
     * Test 7: Cancel không cộng tồn kho.
     */
    public function test_7_cancelling_draft_does_not_increase_inventory()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp] = $this->createDraftPhieuNhap();

        $initialStock = $sp->so_luong; // 20

        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Không cộng tồn kho khi hủy',
        ]);

        $this->assertSame($initialStock, $sp->fresh()->so_luong);
    }

    /**
     * Test 8: Cancel không tạo lô (tổng record chi tiết nhập không tăng).
     */
    public function test_8_cancelling_draft_does_not_create_new_lots()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn] = $this->createDraftPhieuNhap();

        $totalLotsBefore = ChiTietNhap::count();

        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Không tạo lô',
        ]);

        $totalLotsAfter = ChiTietNhap::count();

        $this->assertSame($totalLotsBefore, $totalLotsAfter);
    }

    /**
     * Test 9: CANCELLED không confirm được.
     */
    public function test_9_cancelled_receipt_cannot_be_confirmed()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp] = $this->createDraftPhieuNhap();

        $initialStock = $sp->so_luong;

        // Hủy phiếu
        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Đã hủy',
        ]);

        // Cố gắng xác nhận phiếu đã hủy
        $response = $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        $response->assertSessionHas('error');
        $this->assertEquals(PhieuNhap::TRANG_THAI_CANCELLED, $pn->fresh()->trang_thai);
        $this->assertSame($initialStock, $sp->fresh()->so_luong);
    }

    /**
     * Test 10: CANCELLED không sửa được.
     */
    public function test_10_cancelled_receipt_cannot_be_edited()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn, $ncc] = $this->createDraftPhieuNhap();

        // Hủy phiếu
        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Hủy phiếu để kiểm tra không cho sửa',
        ]);

        // Cố gắng update phiếu đã hủy
        $response = $this->actingAs($admin)->put(route('phieu-nhap.update', $pn->ma_phieu_nhap), [
            'ngay_nhap'       => now()->format('Y-m-d H:i:s'),
            'ma_nhan_vien'    => $pn->ma_nhan_vien,
            'ma_nha_cung_cap' => $ncc->ma_nha_cung_cap,
            'san_pham'        => [$sp->ma_san_pham],
            'so_luong'        => [999],
            'gia_nhap'        => [50000],
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(PhieuNhap::TRANG_THAI_CANCELLED, $pn->fresh()->trang_thai);
        $this->assertEquals(50, $ctn->fresh()->so_luong);
    }

    /**
     * Test 11: CONFIRMED không cancel được.
     */
    public function test_11_confirmed_receipt_cannot_be_cancelled()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn] = $this->createDraftPhieuNhap();

        // Xác nhận phiếu nhập
        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));
        $this->assertEquals(PhieuNhap::TRANG_THAI_CONFIRMED, $pn->fresh()->trang_thai);

        // Cố gắng hủy phiếu CONFIRMED
        $response = $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Cố tình hủy phiếu confirmed',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(PhieuNhap::TRANG_THAI_CONFIRMED, $pn->fresh()->trang_thai);
    }

    /**
     * Test 12: User không đủ quyền không cancel được.
     */
    public function test_12_unauthorized_user_cannot_cancel()
    {
        [$pn] = $this->createDraftPhieuNhap();

        // 1. Guest chưa đăng nhập -> chuyển hướng login
        $guestResponse = $this->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Guest cố hủy',
        ]);
        $guestResponse->assertRedirect(route('login'));
        $this->assertEquals(PhieuNhap::TRANG_THAI_DRAFT, $pn->fresh()->trang_thai);

        // 2. Khách hàng -> 403 Forbidden
        $customer = $this->createUser('KHACH_HANG', 'cust_test');
        $custResponse = $this->actingAs($customer)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Khách hàng cố hủy',
        ]);
        $custResponse->assertStatus(403);
        $this->assertEquals(PhieuNhap::TRANG_THAI_DRAFT, $pn->fresh()->trang_thai);

        // 3. Shipper -> 403 Forbidden
        $shipper = $this->createUser('SHIPPER', 'ship_test');
        $shipResponse = $this->actingAs($shipper)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Shipper cố hủy',
        ]);
        $shipResponse->assertStatus(403);
        $this->assertEquals(PhieuNhap::TRANG_THAI_DRAFT, $pn->fresh()->trang_thai);

        // 4. Nhân viên VAN_HANH (role NHAN_VIEN) -> có quyền hủy
        $staff = $this->createUser('NHAN_VIEN', 'staff_test');
        $staffResponse = $this->actingAs($staff)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Nhân viên vận hành hủy hợp lệ',
        ]);
        $staffResponse->assertRedirect(route('phieu-nhap.index'));
        $this->assertEquals(PhieuNhap::TRANG_THAI_CANCELLED, $pn->fresh()->trang_thai);
    }
}
