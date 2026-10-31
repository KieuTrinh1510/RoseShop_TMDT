<?php

namespace Tests\Feature;

use App\Models\BoHoa;
use App\Models\ChiTietNhap;
use App\Models\MauBoHoa;
use App\Models\NguoiDung;
use App\Models\PhieuNhap;
use App\Models\PhuPhiBoHoa;
use App\Models\SanPham;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TonKhoFilterLoaiTest extends TestCase
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

        PhuPhiBoHoa::firstOrCreate(['loai' => 'SIZE', 'gia_tri' => 'S'], ['phu_phi' => 20000]);
        PhuPhiBoHoa::firstOrCreate(['loai' => 'KIEU_DANG', 'gia_tri' => 'ROUND'], ['phu_phi' => 15000]);

        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        $this->adminUser = NguoiDung::create([
            'ten_dang_nhap'     => 'admin_tk_' . $unique,
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
        parent::tearDown();
    }

    /**
     * 1. Test Tất cả kho hàng trả về đủ 3 nhóm: Sản phẩm, Nguyên vật liệu, Bó hoa thành phẩm.
     * Kiểm tra dropdown UI có đúng 4 options, không còn option cũ.
     */
    public function test_tat_ca_kho_hang_tra_ve_du_3_nhom_va_dropdown_chuan(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/ton-kho');
        $res->assertStatus(200);

        // Kiểm tra dropdown options
        $res->assertSee('-- Tất cả kho hàng --');
        $res->assertSee('<option value="san_pham"', false);
        $res->assertSee('<option value="nguyen_vat_lieu"', false);
        $res->assertSee('<option value="bo_hoa"', false);
        $res->assertDontSee('Sản phẩm / Nguyên vật liệu');

        // Kiểm tra dữ liệu danh sách có đủ các nhóm
        $items = $res->viewData('items');
        $this->assertTrue($items->isNotEmpty());

        $hasSp = collect($items->items())->contains(fn ($it) => !$it->is_bo_hoa && !$it->is_nguyen_vat_lieu);
        $hasNvl = collect($items->items())->contains(fn ($it) => !$it->is_bo_hoa && $it->is_nguyen_vat_lieu);

        $this->assertTrue($hasSp, 'Danh sách tất cả phải có sản phẩm hoa bán lẻ');
        $this->assertTrue($hasNvl, 'Danh sách tất cả phải có nguyên vật liệu');
    }

    /**
     * 2. Test Chọn "Sản phẩm":
     * Chỉ trả về các mặt hàng hoa bán lẻ, không có nguyên vật liệu, không có bó hoa.
     */
    public function test_filter_san_pham_chi_tra_ve_hoa_ban_le(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/ton-kho?loai_ton_kho=san_pham');
        $res->assertStatus(200);

        $items = $res->viewData('items');
        $this->assertTrue($items->isNotEmpty());

        foreach ($items->items() as $item) {
            $this->assertFalse($item->is_bo_hoa, "Item #{$item->id} không được là bó hoa");
            $this->assertFalse($item->is_nguyen_vat_lieu, "Item #{$item->id} không được là nguyên vật liệu");
            $this->assertEquals('san_pham', $item->loai_ton_kho);
            $this->assertFalse(
                in_array($item->loai_san_pham, SanPham::DANH_SACH_LOAI_NGUYEN_LIEU, true),
                "Loại {$item->loai_san_pham} không được nằm trong nhóm nguyên vật liệu"
            );
        }

        // Kiểm tra badge trong HTML view
        $res->assertSee('Sản phẩm</small>', false);
        $res->assertDontSee('Sản phẩm / Nguyên vật liệu');
    }

    /**
     * 3. Test Chọn "Nguyên vật liệu":
     * Chỉ trả về các vật tư / nguyên vật liệu, không có hoa bán lẻ, không có bó hoa.
     */
    public function test_filter_nguyen_vat_lieu_chi_tra_ve_nguyen_vat_lieu(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/ton-kho?loai_ton_kho=nguyen_vat_lieu');
        $res->assertStatus(200);

        $items = $res->viewData('items');
        $this->assertTrue($items->isNotEmpty());

        foreach ($items->items() as $item) {
            $this->assertFalse($item->is_bo_hoa, "Item #{$item->id} không được là bó hoa");
            $this->assertTrue($item->is_nguyen_vat_lieu, "Item #{$item->id} phải là nguyên vật liệu");
            $this->assertEquals('nguyen_vat_lieu', $item->loai_ton_kho);
            $this->assertTrue(
                in_array($item->loai_san_pham, SanPham::DANH_SACH_LOAI_NGUYEN_LIEU, true),
                "Loại {$item->loai_san_pham} phải thuộc danh sách nguyên vật liệu"
            );
        }

        // Kiểm tra badge trong HTML view
        $res->assertSee('Nguyên vật liệu</small>', false);
        $res->assertDontSee('Sản phẩm / Nguyên vật liệu');
    }

    /**
     * 4. Test Chọn "Bó hoa thành phẩm":
     * Chỉ trả về bó hoa thành phẩm, không có hoa lẻ, không có nguyên vật liệu.
     */
    public function test_filter_bo_hoa_thanh_pham_chi_tra_ve_bo_hoa(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/ton-kho?loai_ton_kho=bo_hoa');
        $res->assertStatus(200);

        $items = $res->viewData('items');
        $this->assertTrue($items->isNotEmpty());

        foreach ($items->items() as $item) {
            $this->assertTrue($item->is_bo_hoa, "Item #{$item->id} phải là bó hoa");
            $this->assertFalse($item->is_nguyen_vat_lieu, "Item #{$item->id} không được là nguyên vật liệu");
            $this->assertEquals('bo_hoa', $item->loai_ton_kho);
        }

        $res->assertSee('Bó hoa thành phẩm</small>', false);
        $res->assertDontSee('Sản phẩm / Nguyên vật liệu');
    }

    /**
     * 5. Test Mẫu bó hoa chưa sản xuất (0 bó) không có tồn thực tế vật lý.
     */
    public function test_mau_bo_hoa_chua_san_xuat_co_ton_thuc_te_bang_khong(): void
    {
        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Bó Test Chưa SX ' . $unique,
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'mo_ta'       => 'Mẫu test kiểm tra quy tắc tồn kho',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $mau->ma_mau_bo_hoa;

        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/ton-kho?loai_ton_kho=bo_hoa&keyword=' . urlencode($mau->ten_mau));
        $res->assertStatus(200);

        $items = $res->viewData('items');
        $found = collect($items->items())->first(fn ($it) => $it->id === $mau->ma_mau_bo_hoa);

        $this->assertNotNull($found);
        $this->assertEquals(0, $found->ton_thuc_te, 'Mẫu bó hoa chưa sản xuất thì tồn thực tế phải bằng 0');
    }

    /**
     * 6. Test Filter kết hợp:
     * - Loại tồn kho = Sản phẩm + Tình trạng = Sắp hết
     * - Loại tồn kho = Nguyên vật liệu + Keyword
     */
    public function test_filter_ket_hop_tinh_trang_ton_va_keyword(): void
    {
        $this->actingAs($this->adminUser);

        // Sản phẩm + Sắp hết (Khả dụng <= 10)
        $res = $this->get('/admin/ton-kho?loai_ton_kho=san_pham&tinh_trang_ton=sap_het');
        $res->assertStatus(200);

        $items = $res->viewData('items');
        foreach ($items->items() as $item) {
            $this->assertFalse($item->is_bo_hoa);
            $this->assertFalse($item->is_nguyen_vat_lieu);
            $this->assertLessThanOrEqual(10, $item->ton_kha_dung);
            $this->assertGreaterThan(0, $item->ton_kha_dung);
        }

        // Nguyên vật liệu + Keyword "Giấy"
        $res2 = $this->get('/admin/ton-kho?loai_ton_kho=nguyen_vat_lieu&keyword=' . urlencode('Giấy'));
        $res2->assertStatus(200);

        $items2 = $res2->viewData('items');
        foreach ($items2->items() as $item) {
            $this->assertTrue($item->is_nguyen_vat_lieu);
            $this->assertStringContainsStringIgnoringCase('giấy', $item->ten_san_pham);
        }
    }

    /**
     * 7. Test Pagination giữ query parameters (withQueryString convention).
     */
    public function test_pagination_giu_nguyen_query_filter(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/ton-kho?loai_ton_kho=san_pham&per_page=10&page=1');
        $res->assertStatus(200);

        $items = $res->viewData('items');
        $this->assertEquals('san_pham', $items->getOptions()['query']['loai_ton_kho'] ?? null);

        // Khi gọi url(2), query loai_ton_kho=san_pham phải hiện diện
        $urlPage2 = $items->url(2);
        $this->assertStringContainsString('loai_ton_kho=san_pham', $urlPage2);
    }
}
