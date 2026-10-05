<?php

namespace Tests\Feature;

use App\Models\BaoCaoHangHong;
use App\Models\ChiTietNhap;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UC42_LoHangBusinessRulesTest extends TestCase
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
            'mo_ta'         => 'Test product for Lot rules',
            'trang_thai'    => 'DANG_BAN',
        ]);
    }

    protected function createPhieuNhap(string $trangThai = PhieuNhap::TRANG_THAI_DRAFT, int $qty = 40): array
    {
        $employeeId = $this->getOperationalEmployeeId();
        $ncc = NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Test ' . rand(100, 999),
            'so_dien_thoai'    => '0988776655',
            'email'            => 'ncc_' . rand(100, 999) . '@test.com',
            'dia_chi'          => 'Đà Lạt',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $sp = $this->createProduct('Hoa Cúc Mẫu Đơn', 10);

        $pn = PhieuNhap::create([
            'ngay_nhap'         => now(),
            'ma_nhan_vien'      => $employeeId,
            'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
            'email_ncc'         => $ncc->email,
            'dia_chi_ncc'       => $ncc->dia_chi,
            'trang_thai'        => $trangThai,
        ]);

        $ctn = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => $qty,
            'gia_nhap'         => 15000,
            'gia_ban'          => 25000,
            'so_luong_con_lai' => $qty,
        ]);

        return [$pn, $sp, $ctn, $ncc];
    }

    /**
     * Test 1: DRAFT không tạo lô (Không xuất hiện trong danh sách và chi tiết lô)
     */
    public function test_1_draft_phieu_nhap_does_not_appear_as_active_lot()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT);

        // Danh sách lô không được chứa chi tiết nhập của phiếu DRAFT
        $indexResponse = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertDontSee('#CTN-' . $ctn->ma_chi_tiet_nhap);

        // Trang chi tiết lô trả về 404 cho phiếu DRAFT
        $showResponse = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $showResponse->assertStatus(404);
    }

    /**
     * Test 2: CANCELLED không tạo lô (Không xuất hiện trong danh sách và chi tiết lô)
     */
    public function test_2_cancelled_phieu_nhap_does_not_appear_as_active_lot()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT);

        // Cancel phiếu
        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Hủy phiếu để kiểm tra lô',
        ]);
        $this->assertEquals(PhieuNhap::TRANG_THAI_CANCELLED, $pn->fresh()->trang_thai);

        // Danh sách lô không chứa chi tiết nhập của phiếu CANCELLED
        $indexResponse = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertDontSee('#CTN-' . $ctn->ma_chi_tiet_nhap);

        // Trang chi tiết lô trả về 404 cho phiếu CANCELLED
        $showResponse = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $showResponse->assertStatus(404);
    }

    /**
     * Test 3: CONFIRMED tạo lô đúng số lượng
     */
    public function test_3_confirmed_phieu_nhap_creates_and_displays_lot()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 40);

        // Confirm phiếu nhập
        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));
        $this->assertEquals(PhieuNhap::TRANG_THAI_CONFIRMED, $pn->fresh()->trang_thai);

        // Danh sách lô phải xuất hiện mã lô này
        $indexResponse = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);

        // Chi tiết lô hiển thị đầy đủ thông tin
        $showResponse = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
    }

    /**
     * Test 4: Lô mới: so_luong_ban_dau = số lượng nhập
     */
    public function test_4_new_lot_initial_quantity_equals_imported_quantity()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 45);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        $freshCtn = $ctn->fresh();
        $this->assertEquals(45, $freshCtn->so_luong);
    }

    /**
     * Test 5: Lô mới: so_luong_con_lai = số lượng nhập
     */
    public function test_5_new_lot_remaining_quantity_equals_imported_quantity()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 60);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        $freshCtn = $ctn->fresh();
        $this->assertEquals(60, $freshCtn->so_luong_con_lai);
    }

    /**
     * Test 6: QC không làm giảm số lượng lô
     */
    public function test_6_quality_evaluation_does_not_decrease_quantity()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 50);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        $initialConLai = $ctn->fresh()->so_luong_con_lai; // 50
        $initialStock = $sp->fresh()->so_luong;           // 10 + 50 = 60

        // Thực hiện đánh giá QC: KHONG_DAT với 20 bông lỗi
        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $ctn->ma_chi_tiet_nhap), [
            'ket_qua'      => 'KHONG_DAT',
            'so_luong_loi' => 20,
            'ly_do'        => 'Hoa bị dập nát do vận chuyển',
            'ghi_chu'      => 'Chờ thủ kho xử lý báo hỏng',
        ]);

        $response->assertSessionHas('success');

        // Số lượng còn lại của lô và tồn kho sản phẩm tuyệt đối không giảm chỉ vì QC
        $this->assertSame($initialConLai, $ctn->fresh()->so_luong_con_lai);
        $this->assertSame($initialStock, $sp->fresh()->so_luong);
    }

    /**
     * Test 7: Ghi nhận hàng hỏng làm giảm đúng số lượng còn lại của lô và tồn sản phẩm
     */
    public function test_7_recording_damaged_stock_decreases_quantity_correctly()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 40);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        $sanPhamBefore = $sp->fresh()->so_luong; // 10 + 40 = 50

        // Báo hỏng 5 bông
        $response = $this->actingAs($admin)->post(route('san-pham.bao-hang-hong'), [
            'ma_san_pham'      => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'so_luong_hong'    => 5,
            'ly_do'            => 'Hoa gãy cành',
            'ghi_chu'          => 'Ghi nhận hàng hỏng đợt 1',
        ]);

        $this->assertEquals(35, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals($sanPhamBefore - 5, $sp->fresh()->so_luong);

        $this->assertDatabaseHas('bao_cao_hang_hong', [
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong_hong'    => 5,
        ]);
    }

    /**
     * Test 8: Không cho hàng hỏng > số lượng còn lại
     */
    public function test_8_cannot_report_damage_greater_than_remaining_quantity()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 20);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        // Cố tình báo hỏng 25 khi lô chỉ còn 20
        $response = $this->actingAs($admin)->post(route('san-pham.bao-hang-hong'), [
            'ma_san_pham'      => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'so_luong_hong'    => 25,
            'ly_do'            => 'Báo hỏng vượt số lượng',
        ]);

        $response->assertSessionHasErrors(['so_luong_hong']);
        $this->assertEquals(20, $ctn->fresh()->so_luong_con_lai);
    }

    /**
     * Test 9: Khi số lượng còn lại = 0 thì trạng thái là HẾT HÀNG
     */
    public function test_9_lot_with_zero_remaining_displays_out_of_stock()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 10);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        // Báo hỏng toàn bộ 10
        $this->actingAs($admin)->post(route('san-pham.bao-hang-hong'), [
            'ma_san_pham'      => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'so_luong_hong'    => 10,
            'ly_do'            => 'Hỏng toàn bộ lô',
        ]);

        $this->assertEquals(0, $ctn->fresh()->so_luong_con_lai);

        // Kiểm tra danh sách hiển thị badge HẾT HÀNG
        $indexResponse = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('HẾT HÀNG');

        // Kiểm tra chi tiết hiển thị HẾT HÀNG
        $showResponse = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('HẾT HÀNG');
    }

    /**
     * Test 10: Lô HẾT HÀNG vẫn xem được chi tiết để truy vết lịch sử
     */
    public function test_10_out_of_stock_lot_is_still_viewable()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 5);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        // Đưa tồn về 0
        $ctn->update(['so_luong_con_lai' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $response->assertStatus(200);
        $response->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
        $response->assertSee('HẾT HÀNG');
    }

    /**
     * Test 11: Không có route cancel lô trong hệ thống
     */
    public function test_11_no_lot_cancellation_route_exists()
    {
        $this->assertFalse(Route::has('admin.lo-hang.cancel'));
        $this->assertFalse(Route::has('lo-hang.cancel'));
    }

    /**
     * Test 12: Không có logic tự động đưa số lượng về 0 vì HSD
     */
    public function test_12_no_automated_hsd_expiry_logic()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 30);

        $this->actingAs($admin)->post(route('phieu-nhap.confirm', $pn->ma_phieu_nhap));

        // Schema không chứa cột HSD nào làm giảm tồn, số lượng giữ nguyên
        $this->assertEquals(30, $ctn->fresh()->so_luong_con_lai);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han'));
    }

    /**
     * Test 13: Phiếu nhập CANCELLED không hiển thị lô tại bảng "Các lô hàng đã nhập từ Nhà cung cấp"
     */
    public function test_13_cancelled_phieu_nhap_does_not_display_lot_under_supplier_show_page()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        [$pn, $sp, $ctn, $ncc] = $this->createPhieuNhap(PhieuNhap::TRANG_THAI_DRAFT, 25);

        // Hủy phiếu nhập
        $this->actingAs($admin)->post(route('phieu-nhap.cancel', $pn->ma_phieu_nhap), [
            'ly_do_huy' => 'Hủy phiếu đối soát với NCC',
        ]);
        $this->assertEquals(PhieuNhap::TRANG_THAI_CANCELLED, $pn->fresh()->trang_thai);

        // Vào trang chi tiết NCC
        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $response->assertStatus(200);

        // Phiếu nhập đã hủy vẫn hiển thị ở mục "Lịch sử phiếu nhập" với trạng thái Đã hủy để truy vết
        $response->assertSee('#PN-' . $pn->ma_phieu_nhap);
        $response->assertSee('Đã hủy');

        // Nhưng phần "Các lô hàng đã nhập từ Nhà cung cấp" KHÔNG ĐƯỢC hiển thị mã lô này
        $response->assertDontSee('#CTN-' . $ctn->ma_chi_tiet_nhap);

        // Thống kê chất lượng của NCC không tính lô này
        $thongKe = $ncc->fresh()->thong_ke_chat_luong;
        $this->assertEquals(0, $thongKe['tong_so_lo']);
    }
}
