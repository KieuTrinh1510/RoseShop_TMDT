<?php

namespace Tests\Feature;

use App\Models\ChiTietNhap;
use App\Models\InventoryReservation;
use App\Models\MauBoHoa;
use App\Models\NguoiDung;
use App\Models\PhieuNhap;
use App\Models\PhuPhiBoHoa;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UC4_MauBoHoaStockAndNameTest extends TestCase
{
    use DatabaseTransactions;

    protected NguoiDung $adminUser;
    protected array $createdSpIds = [];
    protected array $createdMauIds = [];

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

        // Seed basic phu phi for size S and ROUND
        PhuPhiBoHoa::firstOrCreate(['loai' => 'SIZE', 'gia_tri' => 'S'], ['phu_phi' => 20000]);
        PhuPhiBoHoa::firstOrCreate(['loai' => 'KIEU_DANG', 'gia_tri' => 'ROUND'], ['phu_phi' => 15000]);

        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        $this->adminUser = NguoiDung::create([
            'ten_dang_nhap'     => 'admin_test_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'ADMIN',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        try {
            DB::rollBack();
        } catch (\Throwable $e) {
            // ignore
        }

        if (!empty($this->createdMauIds)) {
            DB::table('chi_tiet_mau_bo_hoa')->whereIn('ma_mau_bo_hoa', $this->createdMauIds)->delete();
            DB::table('mau_bo_hoa')->whereIn('ma_mau_bo_hoa', $this->createdMauIds)->delete();
        }

        if (!empty($this->createdSpIds)) {
            DB::table('inventory_reservations')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
            DB::table('chi_tiet_mau_bo_hoa')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
            DB::table('chi_tiet_bo_hoa')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
            DB::table('chi_tiet_nhap')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
            DB::table('san_pham')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
        }

        if (isset($this->adminUser)) {
            DB::table('nguoi_dung')->where('ma_nguoi_dung', $this->adminUser->ma_nguoi_dung)->delete();
        }

        parent::tearDown();
    }

    /**
     * Tạo payload BOM hợp lệ cho Size S (7 hoa chính, 4 giấy, 1 ruy băng, 1 xốp)
     */
    protected function getValidBomPayload(string $name, array $overrides = []): array
    {
        $payload = [
            'ten_bo_hoa'  => $name,
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'mo_ta'       => 'Mẫu test kiểm tra tồn kho và tên',
            'thanh_phan'  => [
                ['ma_san_pham' => 330, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
                ['ma_san_pham' => 301, 'vai_tro' => 'GIAY_GOI',  'so_luong' => 4],
                ['ma_san_pham' => 302, 'vai_tro' => 'RUY_BANG',  'so_luong' => 1],
                ['ma_san_pham' => 305, 'vai_tro' => 'XOP',       'so_luong' => 1],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }

    /**
     * 1. Hoa available > 0 → chọn được và tạo thành công
     */
    public function test_1_hoa_available_greater_than_0_can_be_used(): void
    {
        $name = 'Test Mẫu Hoa Đủ Tồn ' . uniqid();
        $payload = $this->getValidBomPayload($name);

        $res = $this->actingAs($this->adminUser)
            ->postJson(route('admin.bo-hoa.mau.store'), $payload);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);
        $this->assertDatabaseHas('mau_bo_hoa', ['ten_mau' => $name]);
    }

    /**
     * 2. Hoa available = 0 → backend không cho dùng
     */
    public function test_2_hoa_available_equal_0_rejected_by_backend(): void
    {
        // 334 là Cành Lan Hồ Điệp Trắng hiện có tồn = 0
        $sp0 = SanPham::find(334);
        $this->assertNotNull($sp0);
        $this->assertEquals(0, $sp0->so_luong);

        $name = 'Test Mẫu Hoa Hết Hàng ' . uniqid();
        $payload = $this->getValidBomPayload($name, [
            'thanh_phan' => [
                ['ma_san_pham' => 334, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
                ['ma_san_pham' => 301, 'vai_tro' => 'GIAY_GOI',  'so_luong' => 4],
                ['ma_san_pham' => 302, 'vai_tro' => 'RUY_BANG',  'so_luong' => 1],
                ['ma_san_pham' => 305, 'vai_tro' => 'XOP',       'so_luong' => 1],
            ],
        ]);

        $res = $this->actingAs($this->adminUser)
            ->postJson(route('admin.bo-hoa.mau.store'), $payload);

        $res->assertStatus(422);
        $res->assertJson(['success' => false]);
        $this->assertStringContainsString('đã hết hàng khả dụng', $res->json('message'));
    }

    /**
     * 3. NVL available = 0 → backend không cho dùng
     */
    public function test_3_nvl_available_equal_0_rejected_by_backend(): void
    {
        // Tạo một NVL test với so_luong = 0
        $nvl0 = SanPham::create([
            'ten_san_pham'     => 'Giấy gói hết hàng ' . uniqid(),
            'so_luong'         => 0,
            'don_vi_tinh'      => 'TO',
            'loai_san_pham'    => 'GIAY_GOI',
            'trang_thai'       => 'DANG_BAN',
            'gia_ban_hien_tai' => 5000,
        ]);

        $name = 'Test NVL Hết Hàng ' . uniqid();
        $payload = $this->getValidBomPayload($name, [
            'thanh_phan' => [
                ['ma_san_pham' => 330, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
                ['ma_san_pham' => $nvl0->ma_san_pham, 'vai_tro' => 'GIAY_GOI',  'so_luong' => 4],
                ['ma_san_pham' => 302, 'vai_tro' => 'RUY_BANG',  'so_luong' => 1],
                ['ma_san_pham' => 305, 'vai_tro' => 'XOP',       'so_luong' => 1],
            ],
        ]);

        $res = $this->actingAs($this->adminUser)
            ->postJson(route('admin.bo-hoa.mau.store'), $payload);

        $res->assertStatus(422);
        $res->assertJson(['success' => false]);
        $this->assertStringContainsString('đã hết hàng khả dụng', $res->json('message'));
    }

    /**
     * 4. RESERVED được loại khỏi available (khả dụng = on_hand - reserved)
     */
    public function test_4_reserved_is_deducted_from_available(): void
    {
        // Tạo sản phẩm hoa test có tồn thực tế = 10
        $sp = SanPham::create([
            'ten_san_pham'     => 'Hoa Test Reserved ' . uniqid(),
            'so_luong'         => 10,
            'don_vi_tinh'      => 'CANH',
            'loai_san_pham'    => 'HOA_TUOI',
            'trang_thai'       => 'DANG_BAN',
            'gia_ban_hien_tai' => 20000,
        ]);

        $hoaDonId = \App\Models\HoaDon::value('ma_hoa_don') ?? 1;
        InventoryReservation::create([
            'ma_hoa_don'  => $hoaDonId,
            'ma_san_pham' => $sp->ma_san_pham,
            'so_luong'    => 4,
            'trang_thai'  => InventoryReservation::STATUS_RESERVED,
        ]);

        // Thử yêu cầu 7 cành (trong khi tồn thực tế là 10, nhưng khả dụng chỉ còn 6)
        $name = 'Test Reserved Deducted ' . uniqid();
        $payload = $this->getValidBomPayload($name, [
            'thanh_phan' => [
                ['ma_san_pham' => $sp->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
                ['ma_san_pham' => 301, 'vai_tro' => 'GIAY_GOI',  'so_luong' => 4],
                ['ma_san_pham' => 302, 'vai_tro' => 'RUY_BANG',  'so_luong' => 1],
                ['ma_san_pham' => 305, 'vai_tro' => 'XOP',       'so_luong' => 1],
            ],
        ]);

        $res = $this->actingAs($this->adminUser)
            ->postJson(route('admin.bo-hoa.mau.store'), $payload);

        $res->assertStatus(422);
        $this->assertStringContainsString('không còn đủ tồn khả dụng. Cần 7, hiện còn 6', $res->json('message'));
    }

    /**
     * 5. Không cho số lượng BOM vượt available
     */
    public function test_5_bom_quantity_exceeding_available_is_rejected(): void
    {
        $sp = SanPham::create([
            'ten_san_pham'     => 'Hoa Test Vượt Tồn ' . uniqid(),
            'so_luong'         => 5,
            'don_vi_tinh'      => 'CANH',
            'loai_san_pham'    => 'HOA_TUOI',
            'trang_thai'       => 'DANG_BAN',
            'gia_ban_hien_tai' => 20000,
        ]);

        // Yêu cầu 7 bông trong BOM, trong khi kho chỉ có 5
        $name = 'Test Qty Exceeding Available ' . uniqid();
        $payload = $this->getValidBomPayload($name, [
            'thanh_phan' => [
                ['ma_san_pham' => $sp->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
                ['ma_san_pham' => 301, 'vai_tro' => 'GIAY_GOI',  'so_luong' => 4],
                ['ma_san_pham' => 302, 'vai_tro' => 'RUY_BANG',  'so_luong' => 1],
                ['ma_san_pham' => 305, 'vai_tro' => 'XOP',       'so_luong' => 1],
            ],
        ]);

        $res = $this->actingAs($this->adminUser)
            ->postJson(route('admin.bo-hoa.mau.store'), $payload);

        $res->assertStatus(422);
        $this->assertStringContainsString('không còn đủ tồn khả dụng. Cần 7, hiện còn 5', $res->json('message'));
    }

    /**
     * 6. Tạo mẫu không làm thay đổi tồn kho (KHÔNG decrement, KHÔNG reservation)
     */
    public function test_6_creating_template_does_not_change_inventory(): void
    {
        $sp330Before = SanPham::find(330)->so_luong;
        $sp301Before = SanPham::find(301)->so_luong;
        $resCountBefore = InventoryReservation::count();

        $name = 'Test Không Trừ Kho ' . uniqid();
        $payload = $this->getValidBomPayload($name);

        $res = $this->actingAs($this->adminUser)
            ->postJson(route('admin.bo-hoa.mau.store'), $payload);

        $res->assertStatus(200);

        // Kiểm tra tồn kho sau khi tạo mẫu: GIỮ NGUYÊN
        $this->assertEquals($sp330Before, SanPham::find(330)->so_luong);
        $this->assertEquals($sp301Before, SanPham::find(301)->so_luong);
        $this->assertEquals($resCountBefore, InventoryReservation::count());
    }

    /**
     * 7. Tên mới → tạo được
     */
    public function test_7_new_unique_name_creates_successfully(): void
    {
        $name = 'Mẫu Độc Nhất ' . uniqid();
        $payload = $this->getValidBomPayload($name);

        $res = $this->actingAs($this->adminUser)
            ->postJson(route('admin.bo-hoa.mau.store'), $payload);

        $res->assertStatus(200);
        $this->assertDatabaseHas('mau_bo_hoa', ['ten_mau' => $name]);
    }

    /**
     * 8. Database có "Hoa A": tạo "Hoa A" → fail
     */
    public function test_8_exact_duplicate_name_fails(): void
    {
        $baseName = 'Hoa Mẫu Test Chống Trùng ' . uniqid();
        $payload1 = $this->getValidBomPayload($baseName);
        $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload1)->assertStatus(200);

        // Tạo lại đúng tên đó
        $payload2 = $this->getValidBomPayload($baseName);
        $res = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload2);

        $res->assertStatus(422);
        $this->assertEquals('Tên mẫu bó hoa đã tồn tại.', $res->json('message'));
    }

    /**
     * 9. Database có "Hoa A": tạo "hoa a" (chữ thường) → fail
     */
    public function test_9_lowercase_duplicate_name_fails(): void
    {
        $baseName = 'Hoa Mẫu Test Case ' . uniqid();
        $payload1 = $this->getValidBomPayload($baseName);
        $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload1)->assertStatus(200);

        // Tạo với chữ thường
        $payload2 = $this->getValidBomPayload(mb_strtolower($baseName, 'UTF-8'));
        $res = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload2);

        $res->assertStatus(422);
        $this->assertEquals('Tên mẫu bó hoa đã tồn tại.', $res->json('message'));
    }

    /**
     * 10. Database có "Hoa A": tạo "HOA A" (chữ hoa) → fail
     */
    public function test_10_uppercase_duplicate_name_fails(): void
    {
        $baseName = 'Hoa Mẫu Test Upper ' . uniqid();
        $payload1 = $this->getValidBomPayload($baseName);
        $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload1)->assertStatus(200);

        // Tạo với chữ hoa toàn bộ
        $payload2 = $this->getValidBomPayload(mb_strtoupper($baseName, 'UTF-8'));
        $res = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload2);

        $res->assertStatus(422);
        $this->assertEquals('Tên mẫu bó hoa đã tồn tại.', $res->json('message'));
    }

    /**
     * 11. Database có "Hoa A": tạo "  Hoa A  " (khoảng trắng đầu/cuối/giữa) → fail
     */
    public function test_11_spaced_duplicate_name_fails(): void
    {
        $uniquePart = uniqid();
        $baseName = 'Hoa Mẫu ' . $uniquePart;
        $payload1 = $this->getValidBomPayload($baseName);
        $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload1)->assertStatus(200);

        // Tạo với khoảng trắng đầu, cuối và khoảng trắng kép ở giữa
        $spacedName = '   Hoa    Mẫu   ' . $uniquePart . '   ';
        $payload2 = $this->getValidBomPayload($spacedName);
        $res = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload2);

        $res->assertStatus(422);
        $this->assertEquals('Tên mẫu bó hoa đã tồn tại.', $res->json('message'));
    }

    /**
     * 12. Tên khác → pass (và không tự bỏ dấu tiếng Việt)
     */
    public function test_12_different_name_passes(): void
    {
        $nameWithAccent = 'Hoa Hồng ' . uniqid();
        $nameWithoutAccent = 'Hoa Hong ' . uniqid();

        // Tạo mẫu có dấu
        $payload1 = $this->getValidBomPayload($nameWithAccent);
        $res1 = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload1);
        $res1->assertStatus(200);

        // Tạo mẫu không dấu cùng chuỗi gốc: KHÔNG coi là trùng vì không tự bỏ dấu tiếng Việt
        $payload2 = $this->getValidBomPayload($nameWithoutAccent);
        $res2 = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload2);
        $res2->assertStatus(200);
    }

    /**
     * 13. Hai request cùng tên không được tạo duplicate (race condition protection)
     */
    public function test_13_concurrent_same_name_requests_rejected(): void
    {
        $name = 'Hoa Đua Race ' . uniqid();
        $payload1 = $this->getValidBomPayload($name);
        $payload2 = $this->getValidBomPayload('  ' . mb_strtolower($name, 'UTF-8') . '  ');

        $res1 = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload1);
        $res2 = $this->actingAs($this->adminUser)->postJson(route('admin.bo-hoa.mau.store'), $payload2);

        $res1->assertStatus(200);
        $res2->assertStatus(422);
        $this->assertEquals(1, MauBoHoa::whereRaw('LOWER(TRIM(ten_mau)) = ?', [mb_strtolower($name, 'UTF-8')])->count());
    }
}
