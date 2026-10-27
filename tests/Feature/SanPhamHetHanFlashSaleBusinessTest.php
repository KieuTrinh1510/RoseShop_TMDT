<?php

namespace Tests\Feature;

use App\Models\BaoCaoHangHong;
use App\Models\BoHoa;
use App\Models\ChiTietNhap;
use App\Models\FlashSale;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\KiemTraChatLuong;
use App\Models\MauBoHoa;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SanPhamHetHanFlashSaleBusinessTest extends TestCase
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
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            DB::rollBack();
        } catch (\Throwable $e) {
            // ignore
        }
        parent::tearDown();
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
                'chuc_vu'       => 'VAN_HANH',
                'email'         => $prefix . '_' . $unique . '@example.com',
                'so_dien_thoai' => '09' . rand(10000000, 99999999),
                'luong'         => 10000000,
            ]);
        } elseif ($role === 'KHACH_HANG') {
            \App\Models\KhachHang::create([
                'ma_khach_hang'  => $user->ma_nguoi_dung,
                'ten_khach_hang' => 'Khách hàng ' . $unique,
                'email'          => $prefix . '_' . $unique . '@example.com',
                'so_dien_thoai'  => '09' . rand(10000000, 99999999),
            ]);
        }

        return $user;
    }

    protected function createFlowerLot(int $daysToExpiry = 2, int $stock = 20, float $price = 100000): array
    {
        $ncc = NhaCungCap::first() ?? NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Test',
            'dia_chi' => 'Test',
            'so_dien_thoai' => '0900000000',
            'email' => 'ncc@test.com',
            'trang_thai' => 1,
        ]);

        $staff = $this->createUser('NHAN_VIEN');

        $sp = SanPham::create([
            'ten_san_pham' => 'Hoa Hồng Test ' . rand(1000, 9999),
            'gia_ban_hien_tai' => $price,
            'so_luong' => $stock,
            'trang_thai' => 'DANG_BAN',
            'loai_san_pham' => 'HOA_TUOI',
        ]);

        $pn = PhieuNhap::create([
            'ma_nha_cung_cap' => $ncc->ma_nha_cung_cap,
            'ma_nhan_vien' => $staff->ma_nguoi_dung,
            'ngay_nhap' => now(),
            'tong_tien' => $price * $stock,
            'trang_thai' => PhieuNhap::TRANG_THAI_CONFIRMED,
        ]);

        $ctn = ChiTietNhap::create([
            'ma_phieu_nhap' => $pn->ma_phieu_nhap,
            'ma_san_pham' => $sp->ma_san_pham,
            'so_luong' => $stock,
            'so_luong_con_lai' => $stock,
            'gia_nhap' => 50000,
            'gia_ban' => $price,
            'ngay_het_han' => Carbon::now()->addDays($daysToExpiry)->format('Y-m-d'),
        ]);

        return [$sp, $ctn, $staff];
    }

    /**
     * Test 1: Action A - Bán tiếp giá thường lưu log kiểm tra chất lượng
     */
    public function test_ban_tiep_gia_thuong_saves_quality_audit_log(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 1, stock: 20);

        $response = $this->actingAs($staff)->post(route('admin.san-pham-het-han.ban-tiep'), [
            'loai_doi_tuong' => 'LO_HANG',
            'id' => $ctn->ma_chi_tiet_nhap,
            'ghi_chu' => 'Hoa vẫn tươi, cánh không dập, tiếp tục bán.',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kiem_tra_chat_luong', [
            'doi_tuong_loai' => 'LO_HANG',
            'doi_tuong_id' => $ctn->ma_chi_tiet_nhap,
            'ma_san_pham' => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'ket_qua' => 'CON_BAN_DUOC',
            'ma_nhan_vien' => $staff->ma_nguoi_dung,
            'ghi_chu' => 'Hoa vẫn tươi, cánh không dập, tiếp tục bán.',
        ]);
    }

    /**
     * Test 2: Action B - Flash Sale với các mức 30%, 50%, 75% trên giá bán
     */
    public function test_flash_sale_discount_rates_and_stock_deduction(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 1, stock: 20, price: 100000);

        // Try invalid discount percentage 40% -> should fail validation
        $invalidResponse = $this->actingAs($staff)->post(route('admin.san-pham-het-han.flash-sale'), [
            'loai_doi_tuong' => 'LO_HANG',
            'id' => $ctn->ma_chi_tiet_nhap,
            'nguon' => 'CAN_HAN',
            'so_luong' => 5,
            'ti_le_giam' => 40,
            'bat_dau' => now()->format('Y-m-d H:i'),
            'ket_thuc' => now()->addHours(6)->format('Y-m-d H:i'),
        ]);
        $invalidResponse->assertSessionHasErrors(['ti_le_giam']);

        // Try valid 30% discount on 100,000đ -> price should be 70,000đ
        $validResponse = $this->actingAs($staff)->post(route('admin.san-pham-het-han.flash-sale'), [
            'loai_doi_tuong' => 'LO_HANG',
            'id' => $ctn->ma_chi_tiet_nhap,
            'nguon' => 'CAN_HAN',
            'so_luong' => 5,
            'ti_le_giam' => 30,
            'bat_dau' => now()->format('Y-m-d H:i'),
            'ket_thuc' => now()->addHours(6)->format('Y-m-d H:i'),
            'ghi_chu' => 'Đưa Flash Sale 30%',
        ]);

        $validResponse->assertSessionHas('success');

        // Check Flash Sale record
        $flash = FlashSale::where('ma_chi_tiet_nhap', $ctn->ma_chi_tiet_nhap)->first();
        $this->assertNotNull($flash);
        $this->assertEquals(100000, $flash->gia_goc);
        $this->assertEquals(70000, $flash->gia_flash_sale);
        $this->assertEquals(30, $flash->ti_le_giam);
        $this->assertEquals(5, $flash->so_luong);
        $this->assertEquals('CAN_HAN', $flash->nguon);

        // NEW BUSINESS: Creating Flash Sale does NOT decrement physical stock!
        $ctn->refresh();
        $this->assertEquals(20, $ctn->so_luong_con_lai, 'Physical lot stock must remain 20 after creating Flash Sale');
        $sp->refresh();
        $this->assertEquals(20, $sp->so_luong, 'Physical product stock must remain 20 after creating Flash Sale');
    }

    /**
     * Test 3: Action C - Báo hỏng từ sản phẩm cận hạn / hết hạn
     */
    public function test_bao_hong_deducts_stock_and_creates_centralized_report(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 0, stock: 10, price: 100000);

        $response = $this->actingAs($staff)->post(route('admin.san-pham-het-han.bao-hong'), [
            'loai_doi_tuong' => 'LO_HANG',
            'id' => $ctn->ma_chi_tiet_nhap,
            'so_luong_hong' => 4,
            'ly_do' => 'Hoa bị thối rễ, úa cánh',
            'ghi_chu' => 'Không thể bán tiếp',
        ]);

        $response->assertSessionHas('success');

        // Lot stock must be reduced by 4 (10 - 4 = 6)
        $ctn->refresh();
        $this->assertEquals(6, $ctn->so_luong_con_lai);
        $sp->refresh();
        $this->assertEquals(6, $sp->so_luong);

        // Centralized damage report record
        $this->assertDatabaseHas('bao_cao_hang_hong', [
            'ma_san_pham' => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'so_luong_hong' => 4,
            'nguon_hong' => 'CAN_HAN_HET_HAN',
            'ly_do' => 'Hoa bị thối rễ, úa cánh',
            'ma_nhan_vien' => $staff->ma_nguoi_dung,
        ]);

        // Quality check record
        $this->assertDatabaseHas('kiem_tra_chat_luong', [
            'doi_tuong_loai' => 'LO_HANG',
            'doi_tuong_id' => $ctn->ma_chi_tiet_nhap,
            'ket_qua' => 'HONG',
            'ma_nhan_vien' => $staff->ma_nguoi_dung,
        ]);
    }

    /**
     * Test 4: Flash Sale - Gia hạn có lưu log kiểm tra
     */
    public function test_flash_sale_extension_with_quality_audit(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 1, stock: 20, price: 100000);

        $flash = FlashSale::create([
            'ma_san_pham' => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'nguon' => 'CAN_HAN',
            'gia_goc' => 100000,
            'gia_flash_sale' => 50000,
            'so_tien_giam' => 50000,
            'ti_le_giam' => 50,
            'so_luong' => 5,
            'so_luong_da_ban' => 0,
            'bat_dau' => now()->subHours(2),
            'ket_thuc' => now()->addHour(),
            'trang_thai' => 'DANG_BAN',
            'nguoi_tao' => $staff->ma_nguoi_dung,
        ]);

        $newEnd = now()->addHours(12)->format('Y-m-d H:i');

        $response = $this->actingAs($staff)->post(route('admin.hoan-hang.flash-sale.extend', $flash->ma_flash_sale), [
            'ket_thuc_moi' => $newEnd,
            'ghi_chu' => 'Hoa vẫn giữ được độ tươi, gia hạn thêm 12h',
        ]);

        $response->assertSessionHas('success');

        $flash->refresh();
        $this->assertEquals(Carbon::parse($newEnd)->format('Y-m-d H:i'), Carbon::parse($flash->ket_thuc)->format('Y-m-d H:i'));

        $this->assertDatabaseHas('kiem_tra_chat_luong', [
            'doi_tuong_loai' => 'FLASH_SALE',
            'doi_tuong_id' => $flash->ma_flash_sale,
            'ma_flash_sale' => $flash->ma_flash_sale,
            'ket_qua' => 'CON_BAN_DUOC',
            'ma_nhan_vien' => $staff->ma_nguoi_dung,
        ]);
    }

    /**
     * Test 5: Flash Sale - Kết thúc/Bỏ Flash Sale không hoàn tồn vật lý vì tồn chưa từng bị trừ
     */
    public function test_flash_sale_end_does_not_modify_physical_stock(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 1, stock: 20, price: 100000);

        $flash = FlashSale::create([
            'ma_san_pham' => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'nguon' => 'HANG_HOAN',
            'gia_goc' => 100000,
            'gia_flash_sale' => 50000,
            'so_tien_giam' => 50000,
            'ti_le_giam' => 50,
            'so_luong' => 5,
            'so_luong_da_ban' => 2,
            'bat_dau' => now()->subDay(),
            'ket_thuc' => now()->subHour(),
            'trang_thai' => 'DANG_BAN',
            'nguoi_tao' => $staff->ma_nguoi_dung,
        ]);

        // Action kết thúc / return stock
        $response = $this->actingAs($staff)->post(route('admin.hoan-hang.flash-sale.return-stock', $flash->ma_flash_sale), [
            'ghi_chu' => 'Kết thúc chương trình sale',
        ]);

        $response->assertSessionHas('success');

        $flash->refresh();
        $this->assertEquals('KET_THUC', $flash->trang_thai);

        // Physical inventory must remain 20 (not incremented)
        $ctn->refresh();
        $this->assertEquals(20, $ctn->so_luong_con_lai);
        $sp->refresh();
        $this->assertEquals(20, $sp->so_luong);
    }

    /**
     * Test 6: Phân loại cảnh báo HSD (SAP_HET_HAN, HET_HAN_HOM_NAY, DA_QUA_HAN)
     */
    public function test_expiry_classification_in_san_pham_het_han_index(): void
    {
        [$sp1, $ctn1, $staff] = $this->createFlowerLot(daysToExpiry: 1); // SAP_HET_HAN
        [$sp2, $ctn2] = $this->createFlowerLot(daysToExpiry: 0); // HET_HAN_HOM_NAY
        [$sp3, $ctn3] = $this->createFlowerLot(daysToExpiry: -2); // DA_QUA_HAN
        [$sp4, $ctn4] = $this->createFlowerLot(daysToExpiry: 5); // > 2 days -> not in primary warning list

        $response = $this->actingAs($staff)->get(route('admin.san-pham-het-han'));
        $response->assertStatus(200);

        $loHangs = $response->viewData('loHangs');
        $lotIds = collect($loHangs)->pluck('ma_chi_tiet_nhap')->toArray();

        $this->assertContains($ctn1->ma_chi_tiet_nhap, $lotIds);
        $this->assertContains($ctn2->ma_chi_tiet_nhap, $lotIds);
        $this->assertContains($ctn3->ma_chi_tiet_nhap, $lotIds);
        $this->assertNotContains($ctn4->ma_chi_tiet_nhap, $lotIds);
    }

    /**
     * Test 7: Bó hoa thành phẩm xử lý Flash Sale & Báo hỏng (không trừ tồn khi tạo sale, chỉ trừ khi báo hỏng)
     */
    public function test_bo_hoa_thanh_pham_flash_sale_and_bao_hong(): void
    {
        $staff = $this->createUser('NHAN_VIEN');

        $bo = BoHoa::create([
            'ten_bo_hoa' => 'Bó Hoa Mừng Sinh Nhật ' . rand(1000, 9999),
            'so_luong' => 5,
            'gia_ban' => 200000,
            'gia_von' => 120000,
            'trang_thai' => 'ACTIVE',
            'ngay_het_han' => Carbon::now()->addDays(1)->format('Y-m-d'),
        ]);

        // Đưa 2 bó vào Flash Sale giảm 50%
        $responseFs = $this->actingAs($staff)->post(route('admin.san-pham-het-han.flash-sale'), [
            'loai_doi_tuong' => 'BO_HOA',
            'id' => $bo->ma_bo_hoa,
            'nguon' => 'CAN_HAN',
            'so_luong' => 2,
            'ti_le_giam' => 50,
            'bat_dau' => now()->format('Y-m-d H:i'),
            'ket_thuc' => now()->addHours(12)->format('Y-m-d H:i'),
        ]);
        $responseFs->assertSessionHas('success');

        // NEW BUSINESS: BoHoa stock remains 5!
        $bo->refresh();
        $this->assertEquals(5, $bo->so_luong, 'BoHoa so_luong must remain 5 after creating Flash Sale');
        $this->assertEquals('ACTIVE', $bo->trang_thai);

        $flash = FlashSale::where('ma_bo_hoa', $bo->ma_bo_hoa)->first();
        $this->assertNotNull($flash);
        $this->assertEquals(200000, $flash->gia_goc);
        $this->assertEquals(100000, $flash->gia_flash_sale);

        // Báo hỏng 1 bó từ nguồn cận hạn / hết hạn
        $responseBh = $this->actingAs($staff)->post(route('admin.san-pham-het-han.bao-hong'), [
            'loai_doi_tuong' => 'BO_HOA',
            'id' => $bo->ma_bo_hoa,
            'so_luong_hong' => 1,
            'ly_do' => 'Bó hoa bị gãy cành, dập cánh',
            'ghi_chu' => 'Không thể tiếp tục trưng bày',
        ]);
        $responseBh->assertSessionHas('success');

        // Now physical stock decreases from 5 to 4
        $bo->refresh();
        $this->assertEquals(4, $bo->so_luong);

        $this->assertDatabaseHas('bao_cao_hang_hong', [
            'ma_bo_hoa' => $bo->ma_bo_hoa,
            'so_luong_hong' => 1,
            'nguon_hong' => 'CAN_HAN_HET_HAN',
        ]);
    }

    /**
     * Test 8: Xem chi tiết báo cáo hàng hỏng
     */
    public function test_bao_cao_hang_hong_detail_view(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 0, stock: 10, price: 100000);

        $bc = BaoCaoHangHong::create([
            'ma_san_pham' => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'so_luong_hong' => 2,
            'nguon_hong' => 'CAN_HAN_HET_HAN',
            'ly_do' => 'Hoa héo',
            'ghi_chu' => 'Kiểm tra sáng nay',
            'ma_nhan_vien' => $staff->ma_nguoi_dung,
            'gia_tri_thiet_hai' => 100000,
        ]);

        $response = $this->actingAs($staff)->get(route('admin.bao-cao-hang-hong.show', $bc->ma_bao_cao));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'ma_bao_cao' => $bc->ma_bao_cao,
                'so_luong_hong' => 2,
                'nguon_hong' => 'CAN_HAN_HET_HAN',
            ]
        ]);
    }

    /**
     * Test 9: Trang Flash Sale /admin/hoan-hang/flash-sale render thành công HTTP 200
     */
    public function test_admin_flash_sale_index_page_renders_successfully(): void
    {
        $staff = $this->createUser('NHAN_VIEN');
        $response = $this->actingAs($staff)->get(route('admin.hoan-hang.flash-sale.index'));
        $response->assertStatus(200);
        $response->assertSee('FLASH SALE');
    }

    /**
     * Test 10: Quota và vòng đời Reservation Flash Sale:
     * - Giữ (RESERVED): on-hand không giảm, Available giảm, quota khả dụng giảm.
     * - Xác nhận (CONSUMED): on-hand giảm, lot giảm, so_luong_da_ban tăng.
     * - Hủy (RELEASED): on-hand không tăng thêm, quota khả dụng phục hồi.
     */
    public function test_flash_sale_reservation_lifecycle_and_quota_tracking(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 1, stock: 10, price: 100000);

        // Tạo Flash Sale quota = 5
        $flash = FlashSale::create([
            'ma_san_pham'      => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'nguon'            => 'CAN_HAN',
            'gia_goc'          => 100000,
            'gia_flash_sale'   => 50000,
            'so_tien_giam'     => 50000,
            'ti_le_giam'       => 50,
            'so_luong'         => 5,
            'so_luong_da_ban'  => 0,
            'bat_dau'          => now()->subHour(),
            'ket_thuc'         => now()->addHours(5),
            'trang_thai'       => 'DANG_BAN',
            'nguoi_tao'        => $staff->ma_nguoi_dung,
        ]);

        $this->assertEquals(5, $flash->available_quota);
        $this->assertEquals(10, $sp->fresh()->so_luong);

        // Tạo hóa đơn 1 đặt 2 sản phẩm sale
        $customer = $this->createUser('KHACH_HANG');
        $hd1 = \App\Models\HoaDon::create([
            'ma_khach_hang'   => $customer->ma_nguoi_dung,
            'tong_tien'       => 100000,
            'dia_chi_giao'    => 'Test Address',
            'so_dien_thoai'   => '0900000001',
            'trang_thai'      => 'PENDING',
        ]);

        $gioHang1 = [[
            'ma_san_pham'   => $sp->ma_san_pham,
            'ma_flash_sale' => $flash->ma_flash_sale,
            'so_luong'      => 2,
            'gia_ban'       => 50000,
            'gia_nhap'      => 50000,
        ]];

        \App\Services\InventoryReservationService::reserveSingleItems($hd1, $gioHang1);

        // Assert: on-hand CHƯA giảm, reserved = 2, available quota = 3
        $this->assertEquals(10, $sp->fresh()->so_luong);
        $this->assertEquals(10, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(2, $flash->fresh()->reserved_quantity);
        $this->assertEquals(3, $flash->fresh()->available_quota);
        $this->assertEquals(8, \App\Services\InventoryReservationService::getAvailableForIngredient($sp->ma_san_pham));

        // Xác nhận đơn 1 -> CONSUMED
        \App\Services\InventoryReservationService::confirmOrder($hd1);

        // Assert: on-hand giảm 2 (10 -> 8), lot giảm 2, so_luong_da_ban tăng 2, quota còn 3
        $this->assertEquals(8, $sp->fresh()->so_luong);
        $this->assertEquals(8, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(2, $flash->fresh()->so_luong_da_ban);
        $this->assertEquals(0, $flash->fresh()->reserved_quantity);
        $this->assertEquals(3, $flash->fresh()->available_quota);

        // Tạo hóa đơn 2 đặt 2 sản phẩm sale
        $hd2 = \App\Models\HoaDon::create([
            'ma_khach_hang'   => $customer->ma_nguoi_dung,
            'tong_tien'       => 100000,
            'dia_chi_giao'    => 'Test Address 2',
            'so_dien_thoai'   => '0900000002',
            'trang_thai'      => 'PENDING',
        ]);

        $gioHang2 = [[
            'ma_san_pham'   => $sp->ma_san_pham,
            'ma_flash_sale' => $flash->ma_flash_sale,
            'so_luong'      => 2,
            'gia_ban'       => 50000,
            'gia_nhap'      => 50000,
        ]];

        \App\Services\InventoryReservationService::reserveSingleItems($hd2, $gioHang2);

        // Đã bán = 2, Đang giữ = 2 -> Quota chỉ còn 1 suất sale!
        $this->assertEquals(2, $flash->fresh()->so_luong_da_ban);
        $this->assertEquals(2, $flash->fresh()->reserved_quantity);
        $this->assertEquals(1, $flash->fresh()->available_quota);

        // Hủy đơn 2 -> RELEASED
        \App\Services\InventoryReservationService::releaseOrder($hd2);

        // Assert: on-hand không tăng thêm (vẫn = 8), quota hồi phục (5 - 2 = 3)
        $this->assertEquals(8, $sp->fresh()->so_luong);
        $this->assertEquals(8, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(3, $flash->fresh()->available_quota);
    }

    /**
     * Test 11: Báo hỏng từ màn hình Flash Sale:
     * - Giảm tồn kho vật lý của sản phẩm và lô hàng
     * - Tạo bản ghi bao_cao_hang_hong với nguồn FLASH_SALE
     * - Giảm quota Flash Sale
     */
    public function test_flash_sale_report_damage_deducts_physical_stock(): void
    {
        [$sp, $ctn, $staff] = $this->createFlowerLot(daysToExpiry: 1, stock: 15, price: 100000);

        $flash = FlashSale::create([
            'ma_san_pham'      => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'nguon'            => 'CAN_HAN',
            'gia_goc'          => 100000,
            'gia_flash_sale'   => 50000,
            'so_tien_giam'     => 50000,
            'ti_le_giam'       => 50,
            'so_luong'         => 5,
            'so_luong_da_ban'  => 0,
            'bat_dau'          => now()->subHour(),
            'ket_thuc'         => now()->addHours(5),
            'trang_thai'       => 'DANG_BAN',
            'nguoi_tao'        => $staff->ma_nguoi_dung,
        ]);

        $response = $this->actingAs($staff)->post(route('admin.hoan-hang.flash-sale.report-damage', $flash->ma_flash_sale), [
            'so_luong_hong' => 2,
            'ly_do'         => 'Hoa héo úa trong thời gian sale',
            'ghi_chu'       => 'Kiểm tra lúc 15h',
        ]);

        $response->assertSessionHas('success');

        // Physical inventory must decrease by 2 (15 -> 13)
        $this->assertEquals(13, $sp->fresh()->so_luong);
        $this->assertEquals(13, $ctn->fresh()->so_luong_con_lai);

        // Flash sale quota decreases by 2 (5 -> 3)
        $this->assertEquals(3, $flash->fresh()->so_luong);

        // Centralized damage report
        $this->assertDatabaseHas('bao_cao_hang_hong', [
            'ma_san_pham'      => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $ctn->ma_chi_tiet_nhap,
            'so_luong_hong'    => 2,
            'nguon_hong'       => 'FLASH_SALE',
            'ly_do'            => 'Hoa héo úa trong thời gian sale',
        ]);
    }

    /**
     * Test 12: Mandatory Business Lifecycle for Bouquet Flash Sale (Cases 1 - 6):
     * Case 1: BoHoa stock = 1, Create Flash Sale quota = 1 -> bo_hoa.so_luong = 1
     * Case 2: Customer load bó hoa -> available = 1, active_flash_sale != null, effective_price = gia_flash_sale
     * Case 3: Homepage: Flash Sale đó có trong section Flash Sale
     * Case 4: Reserve: stock = 1, reserved = 1, available = 0, on-hand = 1
     * Case 5: Consume: stock = 0, so_luong_da_ban = 1
     * Case 6: Release: stock vẫn = 1, reservation = RELEASED, quota khả dụng = 1
     */
    public function test_bouquet_flash_sale_complete_business_lifecycle_and_customer_storefront(): void
    {
        $staff = $this->createUser('NHAN_VIEN');
        $customer = $this->createUser('KHACH_HANG');

        // Mẫu bó hoa và bó hoa thành phẩm có sẵn
        $mau = MauBoHoa::create([
            'ten_mau'     => 'Bó hoa Tình Yêu Vĩnh Cửu ' . rand(1000, 9999),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'M',
            'trang_thai'  => 'ACTIVE',
        ]);

        $bo = BoHoa::create([
            'ten_bo_hoa'    => $mau->ten_mau,
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'loai_bo_hoa'   => 'HE_THONG',
            'so_luong'      => 1,
            'gia_ban'       => 300000,
            'gia_von'       => 180000,
            'trang_thai'    => 'ACTIVE',
            'ngay_het_han'  => Carbon::now()->addDay()->format('Y-m-d'),
        ]);

        // ── CASE 1: BoHoa stock = 1, Create Flash Sale quota = 1 -> bo_hoa.so_luong = 1 ──
        $response = $this->actingAs($staff)->post(route('admin.san-pham-het-han.flash-sale'), [
            'loai_doi_tuong' => 'BO_HOA',
            'id'             => $bo->ma_bo_hoa,
            'nguon'          => 'CAN_HAN',
            'so_luong'       => 1,
            'ti_le_giam'     => 50,
            'bat_dau'        => now()->subMinute()->format('Y-m-d H:i'),
            'ket_thuc'       => now()->addHours(24)->format('Y-m-d H:i'),
            'ghi_chu'        => 'Bó hoa cận date chuyển sang Flash Sale 50%',
        ]);
        $response->assertSessionHas('success');

        $flash = FlashSale::where('ma_bo_hoa', $bo->ma_bo_hoa)->first();
        $this->assertNotNull($flash, 'Flash sale phải được tạo thành công');
        $this->assertEquals(1, $flash->so_luong, 'Flash sale quota = 1');
        $this->assertEquals(150000, (float) $flash->gia_flash_sale, 'Giá sale = 150.000đ (50% của 300.000đ)');
        $this->assertEquals(1, $bo->fresh()->so_luong, 'Case 1: bo_hoa.so_luong không được giảm khi tạo Flash Sale, vẫn phải = 1');
        $this->assertEquals('ACTIVE', $bo->fresh()->trang_thai, 'Case 1: bo_hoa.trang_thai phải là ACTIVE');

        // ── CASE 2: Customer load bó hoa -> available = 1, active_flash_sale != null, effective_price = gia_flash_sale ──
        $availablePhysical = InventoryReservationService::getAvailableForBouquet($bo->fresh());
        $this->assertEquals(1, $availablePhysical, 'Case 2: physical available = 1');

        $activeFs = $bo->fresh()->active_flash_sale;
        $this->assertNotNull($activeFs, 'Case 2: active_flash_sale != null');
        $this->assertEquals($flash->ma_flash_sale, $activeFs->ma_flash_sale);

        // Kiểm tra controller chi tiết sản phẩm khách hàng
        $chiTietView = app(\App\Http\Controllers\Customer\ChiTietSanPhamController::class)->showMauBoHoa($mau->ma_mau_bo_hoa);
        $viewData = $chiTietView->getData();
        $this->assertNotNull($viewData['readyBo']);
        $this->assertNotNull($viewData['activeFlashSale']);
        $this->assertEquals(150000, (float) $viewData['quote']['gia_moi_bo'], 'Case 2: effective_price = gia_flash_sale');

        // ── CASE 3: Homepage: Flash Sale đó phải có trong section Flash Sale ──
        $dashView = app(\App\Http\Controllers\Customer\CustomerController::class)->dashboard(new Request());
        $activeFsList = $dashView->getData()['activeFlashSales'];
        $this->assertTrue(
            $activeFsList->contains(fn ($item) => $item->ma_flash_sale === $flash->ma_flash_sale),
            'Case 3: Homepage Flash Sale section phải chứa Flash Sale vừa tạo'
        );

        // ── CASE 4: Reserve: stock = 1, reserved = 1, available = 0, on-hand = 1 ──
        $hd = HoaDon::create([
            'ma_khach_hang'   => $customer->ma_nguoi_dung,
            'tong_tien'       => 150000,
            'trang_thai'      => 'PENDING',
            'dia_chi_giao'    => 'Test Address',
            'so_dien_thoai'   => '0900000001',
        ]);

        DB::transaction(function () use ($hd, $bo, $flash) {
            InventoryReservationService::reserveReadyBouquet($hd, $bo->fresh(), $flash->ma_flash_sale);
        });

        $this->assertEquals(1, $bo->fresh()->so_luong, 'Case 4: on-hand stock vẫn = 1');
        $this->assertEquals(1, InventoryReservationService::getReservedForBouquet($bo->ma_bo_hoa), 'Case 4: reserved = 1');
        $this->assertEquals(0, InventoryReservationService::getAvailableForBouquet($bo->fresh()), 'Case 4: available = 0');
        $this->assertEquals(0, $flash->fresh()->available_quota, 'Case 4: flash sale available quota = 0');

        // ── CASE 5: Consume: stock = 0, so_luong_da_ban = 1 ──
        DB::transaction(function () use ($hd) {
            InventoryReservationService::confirmOrder($hd);
        });

        $this->assertEquals(0, $bo->fresh()->so_luong, 'Case 5: stock sau khi consume = 0');
        $this->assertEquals(1, $flash->fresh()->so_luong_da_ban, 'Case 5: flash sale so_luong_da_ban = 1');

        // ── CASE 6: Release: stock vẫn = 1, reservation = RELEASED, quota khả dụng trở lại = 1 ──
        // Tạo bó hoa 2 và flash sale 2 để test chu trình Release
        $bo2 = BoHoa::create([
            'ten_bo_hoa'    => $mau->ten_mau . ' - 2',
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'loai_bo_hoa'   => 'HE_THONG',
            'so_luong'      => 1,
            'gia_ban'       => 300000,
            'gia_von'       => 180000,
            'trang_thai'    => 'ACTIVE',
            'ngay_het_han'  => Carbon::now()->addDay()->format('Y-m-d'),
        ]);

        $flash2 = FlashSale::create([
            'ma_bo_hoa'       => $bo2->ma_bo_hoa,
            'nguon'           => 'CAN_HAN',
            'gia_goc'         => 300000,
            'gia_flash_sale'  => 150000,
            'so_tien_giam'    => 150000,
            'ti_le_giam'      => 50,
            'so_luong'        => 1,
            'so_luong_da_ban' => 0,
            'bat_dau'         => now()->subHour(),
            'ket_thuc'        => now()->addDay(),
            'trang_thai'      => 'DANG_BAN',
            'nguoi_tao'       => $staff->ma_nguoi_dung,
        ]);

        $hd2 = HoaDon::create([
            'ma_khach_hang'  => $customer->ma_nguoi_dung,
            'tong_tien'      => 150000,
            'trang_thai'     => 'PENDING',
            'dia_chi_giao'   => 'Test Address 2',
            'so_dien_thoai'  => '0900000002',
        ]);

        // Giữ hàng
        DB::transaction(function () use ($hd2, $bo2, $flash2) {
            InventoryReservationService::reserveReadyBouquet($hd2, $bo2->fresh(), $flash2->ma_flash_sale);
        });
        $this->assertEquals(0, InventoryReservationService::getAvailableForBouquet($bo2->fresh()));
        $this->assertEquals(0, $flash2->fresh()->available_quota);

        // Hủy đơn / Nhả reservation
        DB::transaction(function () use ($hd2) {
            InventoryReservationService::releaseOrder($hd2);
        });

        $this->assertEquals(1, $bo2->fresh()->so_luong, 'Case 6: stock sau khi release vẫn = 1');
        $reservationRecord = InventoryReservation::where('ma_hoa_don', $hd2->ma_hoa_don)->first();
        $this->assertEquals(InventoryReservation::STATUS_RELEASED, $reservationRecord->trang_thai, 'Case 6: reservation = RELEASED');
        $this->assertEquals(1, $flash2->fresh()->available_quota, 'Case 6: quota khả dụng trở lại = 1');
        $this->assertEquals(1, InventoryReservationService::getAvailableForBouquet($bo2->fresh()), 'Case 6: bouquet physical available = 1');
    }
}

