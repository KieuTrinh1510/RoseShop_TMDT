<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BoHoaController;
use App\Models\BoHoa;
use App\Models\ChiTietNhap;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\MauBoHoa;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\PhieuNhap;
use App\Models\PhuPhiBoHoa;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UC_TonKhoBoHoaBusinessRulesTest extends TestCase
{
    use DatabaseTransactions;

    protected NguoiDung $adminUser;
    protected SanPham $spHoa;
    protected SanPham $spGiay;
    protected SanPham $spRuy;
    protected SanPham $spXop;
    protected ChiTietNhap $lotHoa;
    protected ChiTietNhap $lotGiay;
    protected ChiTietNhap $lotRuy;
    protected ChiTietNhap $lotXop;
    protected MauBoHoa $mau;

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

        $nv = NhanVien::first();
        $ncc = \App\Models\NhaCungCap::first();
        $pn = PhieuNhap::create([
            'ma_nha_cung_cap' => $ncc ? $ncc->ma_nha_cung_cap : 1,
            'ma_nhan_vien'    => $nv ? $nv->ma_nhan_vien : 6,
            'tong_tien'       => 5000000,
            'ngay_nhap'       => now()->subDays(2),
            'trang_thai'      => 'CONFIRMED',
        ]);

        // Tạo 4 nguyên liệu chuẩn cho Size S: 7 bông hoa, 4 giấy, 1 ruy băng, 1 xốp
        $this->spHoa = SanPham::create([
            'ten_san_pham'      => 'Hoa Hồng Đỏ Test ' . $unique,
            'loai_san_pham'     => 'HOA_TUOI',
            'don_vi_tinh'       => SanPham::DVT_CANH,
            'so_luong'          => 200,
            'gia_ban_hien_tai'  => 15000,
            'trang_thai'        => 'DANG_BAN',
        ]);
        $this->createdSpIds[] = $this->spHoa->ma_san_pham;

        $this->lotHoa = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $this->spHoa->ma_san_pham,
            'so_luong'         => 200,
            'so_luong_con_lai' => 200,
            'gia_nhap'         => 8000,
            'gia_ban'          => 15000,
        ]);

        $this->spGiay = SanPham::create([
            'ten_san_pham'      => 'Giấy Gói Trắng Test ' . $unique,
            'loai_san_pham'     => 'GIAY_GOI',
            'don_vi_tinh'       => SanPham::DVT_TO,
            'so_luong'          => 100,
            'gia_ban_hien_tai'  => 5000,
            'trang_thai'        => 'DANG_BAN',
        ]);
        $this->createdSpIds[] = $this->spGiay->ma_san_pham;

        $this->lotGiay = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $this->spGiay->ma_san_pham,
            'so_luong'         => 100,
            'so_luong_con_lai' => 100,
            'gia_nhap'         => 3000,
            'gia_ban'          => 5000,
        ]);

        $this->spRuy = SanPham::create([
            'ten_san_pham'      => 'Ruy Băng Lụa Test ' . $unique,
            'loai_san_pham'     => 'RUY_BANG',
            'don_vi_tinh'       => SanPham::DVT_MET,
            'so_luong'          => 50,
            'gia_ban_hien_tai'  => 4000,
            'trang_thai'        => 'DANG_BAN',
        ]);
        $this->createdSpIds[] = $this->spRuy->ma_san_pham;

        $this->lotRuy = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $this->spRuy->ma_san_pham,
            'so_luong'         => 50,
            'so_luong_con_lai' => 50,
            'gia_nhap'         => 2000,
            'gia_ban'          => 4000,
        ]);

        $this->spXop = SanPham::create([
            'ten_san_pham'      => 'Mút Xốp Test ' . $unique,
            'loai_san_pham'     => 'MIENG_XOP',
            'don_vi_tinh'       => SanPham::DVT_MIENG,
            'so_luong'          => 50,
            'gia_ban_hien_tai'  => 8000,
            'trang_thai'        => 'DANG_BAN',
        ]);
        $this->createdSpIds[] = $this->spXop->ma_san_pham;

        $this->lotXop = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $this->spXop->ma_san_pham,
            'so_luong'         => 50,
            'so_luong_con_lai' => 50,
            'gia_nhap'         => 4000,
            'gia_ban'          => 8000,
        ]);

        $this->mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Bó Test ' . $unique,
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'mo_ta'       => 'Mẫu test kiểm tra quy tắc tồn kho',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $this->mau->ma_mau_bo_hoa;

        $this->mau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $this->spHoa->ma_san_pham,  'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $this->spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI',  'so_luong' => 4],
            ['ma_san_pham' => $this->spRuy->ma_san_pham,  'vai_tro' => 'RUY_BANG',  'so_luong' => 1],
            ['ma_san_pham' => $this->spXop->ma_san_pham,  'vai_tro' => 'XOP',       'so_luong' => 1],
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
            $boIds = DB::table('bo_hoa')->whereIn('ma_mau_bo_hoa', $this->createdMauIds)->pluck('ma_bo_hoa')->toArray();
            if (!empty($boIds)) {
                DB::table('inventory_reservations')->whereIn('ma_bo_hoa', $boIds)->delete();
                DB::table('chiTietBoHoa')->whereIn('ma_bo_hoa', $boIds)->delete();
                DB::table('bo_hoa')->whereIn('ma_bo_hoa', $boIds)->delete();
            }
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
     * CASE 1: Tạo mẫu bó hoa -> không thay đổi tồn kho.
     */
    public function test_case_1_tao_mau_khong_thay_doi_ton_kho(): void
    {
        $spHoaBefore = $this->spHoa->fresh()->so_luong;
        $lotHoaBefore = $this->lotHoa->fresh()->so_luong_con_lai;

        $newMau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Case 1 ' . microtime(true),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $newMau->ma_mau_bo_hoa;
        $newMau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $this->spHoa->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $this->spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
        ]);

        $this->assertEquals($spHoaBefore, $this->spHoa->fresh()->so_luong);
        $this->assertEquals($lotHoaBefore, $this->lotHoa->fresh()->so_luong_con_lai);
        $this->assertEquals(0, InventoryReservation::where('ma_san_pham', $this->spHoa->ma_san_pham)->count());
    }

    /**
     * CASE 2 & 3: Sản xuất 10 bó:
     * - Trừ đúng BOM × 10
     * - Tạo đúng 10 BoHoa theo kiến trúc hiện tại
     */
    public function test_case_2_va_3_san_xuat_10_bo(): void
    {
        Auth::login($this->adminUser);

        $spHoaBefore = $this->spHoa->fresh()->so_luong;
        $spGiayBefore = $this->spGiay->fresh()->so_luong;

        $controller = app(BoHoaController::class);
        $req = Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $this->mau->ma_mau_bo_hoa,
            'so_luong'      => 10,
        ]);
        $res = $controller->store($req);
        $data = $res->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(10, $data['data']['ids']);

        // CASE 2: Trừ đúng BOM × 10
        // Hoa: 7 * 10 = 70 bông
        $this->assertEquals($spHoaBefore - 70, $this->spHoa->fresh()->so_luong);
        $this->assertEquals(200 - 70, $this->lotHoa->fresh()->so_luong_con_lai);
        // Giấy: 4 * 10 = 40 tờ
        $this->assertEquals($spGiayBefore - 40, $this->spGiay->fresh()->so_luong);
        $this->assertEquals(100 - 40, $this->lotGiay->fresh()->so_luong_con_lai);

        // CASE 3: Tạo đúng 10 BoHoa record với so_luong = 1
        $boList = BoHoa::where('ma_mau_bo_hoa', $this->mau->ma_mau_bo_hoa)->get();
        $this->assertCount(10, $boList);
        foreach ($boList as $bo) {
            $this->assertEquals(1, $bo->so_luong);
            $this->assertEquals('ACTIVE', $bo->trang_thai);
            $this->assertEquals('HE_THONG', $bo->loai_bo_hoa);
        }
    }

    /**
     * Helper sản xuất 10 bó
     */
    protected function produceTenBouquets(): array
    {
        Auth::login($this->adminUser);
        $controller = app(BoHoaController::class);
        $req = Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $this->mau->ma_mau_bo_hoa,
            'so_luong'      => 10,
        ]);
        $res = $controller->store($req);
        return $res->getData(true)['data']['ids'];
    }

    /**
     * CASE 4: Tồn bó ban đầu: On Hand = 10, Reserved = 0, Available = 10.
     */
    public function test_case_4_ton_bo_ban_dau(): void
    {
        $this->produceTenBouquets();

        $stock = InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa);
        $this->assertEquals(10, $stock['on_hand']);
        $this->assertEquals(0, $stock['reserved']);
        $this->assertEquals(10, $stock['available']);

        // Check qua Model accessor
        $this->assertEquals(10, $this->mau->ton_thuc_te);
        $this->assertEquals(0, $this->mau->ton_dang_giu);
        $this->assertEquals(10, $this->mau->ton_kha_dung);
    }

    /**
     * CASE 5: Reserve 2 bó -> On Hand = 10, Reserved = 2, Available = 8.
     */
    public function test_case_5_reserve_2_bo(): void
    {
        $ids = $this->produceTenBouquets();

        $hd = HoaDon::create([
            'ma_khach_hang'   => 1,
            'tong_tien'       => 200000,
            'trang_thai'      => 'PENDING',
        ]);

        $bo1 = BoHoa::find($ids[0]);
        $bo2 = BoHoa::find($ids[1]);

        InventoryReservationService::reserveReadyBouquet($hd, $bo1);
        InventoryReservationService::reserveReadyBouquet($hd, $bo2);

        $stock = InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa);
        $this->assertEquals(10, $stock['on_hand']);
        $this->assertEquals(2, $stock['reserved']);
        $this->assertEquals(8, $stock['available']);
    }

    /**
     * CASE 6: Confirm 2 bó -> On Hand = 8, Reserved = 0, Available = 8.
     */
    public function test_case_6_confirm_2_bo(): void
    {
        $ids = $this->produceTenBouquets();

        $hd = HoaDon::create([
            'ma_khach_hang'   => 1,
            'tong_tien'       => 200000,
            'trang_thai'      => 'PENDING',
        ]);

        $bo1 = BoHoa::find($ids[0]);
        $bo2 = BoHoa::find($ids[1]);

        InventoryReservationService::reserveReadyBouquet($hd, $bo1);
        InventoryReservationService::reserveReadyBouquet($hd, $bo2);

        InventoryReservationService::confirmOrder($hd);

        $stock = InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa);
        $this->assertEquals(8, $stock['on_hand']);
        $this->assertEquals(0, $stock['reserved']);
        $this->assertEquals(8, $stock['available']);

        // Hai bó đã bán có so_luong = 0 và gắn ma_hoa_don
        $this->assertEquals(0, $bo1->fresh()->so_luong);
        $this->assertEquals($hd->ma_hoa_don, $bo1->fresh()->ma_hoa_don);
        $this->assertEquals(0, $bo2->fresh()->so_luong);
        $this->assertEquals($hd->ma_hoa_don, $bo2->fresh()->ma_hoa_don);
    }

    /**
     * CASE 7: Cancel reservation -> On Hand không đổi.
     */
    public function test_case_7_cancel_reservation_on_hand_khong_doi(): void
    {
        $ids = $this->produceTenBouquets();

        $hd = HoaDon::create([
            'ma_khach_hang'   => 1,
            'tong_tien'       => 100000,
            'trang_thai'      => 'PENDING',
        ]);

        $bo = BoHoa::find($ids[0]);
        InventoryReservationService::reserveReadyBouquet($hd, $bo);

        $this->assertEquals(1, InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa)['reserved']);

        InventoryReservationService::releaseOrder($hd);

        $stock = InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa);
        $this->assertEquals(10, $stock['on_hand']);
        $this->assertEquals(0, $stock['reserved']);
        $this->assertEquals(10, $stock['available']);
        $this->assertEquals('ACTIVE', $bo->fresh()->trang_thai);
    }

    /**
     * CASE 8 & 9: Cancel bó thành phẩm trong đơn:
     * - SanPham không tăng
     * - ChiTietNhap không tăng
     */
    public function test_case_8_va_9_cancel_bo_thanh_pham_khong_hoan_bom(): void
    {
        $ids = $this->produceTenBouquets();

        $spHoaStock = $this->spHoa->fresh()->so_luong;
        $lotHoaStock = $this->lotHoa->fresh()->so_luong_con_lai;

        $hd = HoaDon::create([
            'ma_khach_hang'   => 1,
            'tong_tien'       => 100000,
            'trang_thai'      => 'PENDING',
        ]);

        $bo = BoHoa::find($ids[0]);
        $bo->ma_hoa_don = $hd->ma_hoa_don;
        $bo->save();

        InventoryReservationService::reserveReadyBouquet($hd, $bo);

        // Gọi hàm hủy bó trong đơn
        BoHoaController::huyBoTrongDon($hd);

        // CASE 8: SanPham không tăng
        $this->assertEquals($spHoaStock, $this->spHoa->fresh()->so_luong);

        // CASE 9: ChiTietNhap không tăng
        $this->assertEquals($lotHoaStock, $this->lotHoa->fresh()->so_luong_con_lai);

        // Bó hoa quay lại ACTIVE
        $this->assertEquals('ACTIVE', $bo->fresh()->trang_thai);
        $this->assertNull($bo->fresh()->ma_hoa_don);
    }

    /**
     * CASE 10: Bán bó thành phẩm: không decrement BOM components lần 2.
     */
    public function test_case_10_ban_bo_khong_decrement_bom_lan_2(): void
    {
        $ids = $this->produceTenBouquets();

        $spHoaStock = $this->spHoa->fresh()->so_luong;
        $lotHoaStock = $this->lotHoa->fresh()->so_luong_con_lai;

        $hd = HoaDon::create([
            'ma_khach_hang'   => 1,
            'tong_tien'       => 100000,
            'trang_thai'      => 'PENDING',
        ]);

        $bo = BoHoa::find($ids[0]);
        InventoryReservationService::reserveReadyBouquet($hd, $bo);
        InventoryReservationService::confirmOrder($hd);

        // Tồn kho nguyên liệu không bị trừ thêm lần nào nữa
        $this->assertEquals($spHoaStock, $this->spHoa->fresh()->so_luong);
        $this->assertEquals($lotHoaStock, $this->lotHoa->fresh()->so_luong_con_lai);
    }

    /**
     * CASE 11, 12, 13: Báo hỏng 2 bó:
     * - CASE 11: On Hand giảm 2
     * - CASE 12: SanPham không giảm lần 2
     * - CASE 13: ChiTietNhap không giảm lần 2
     */
    public function test_case_11_12_13_bao_hong_bo_thanh_pham(): void
    {
        $ids = $this->produceTenBouquets();

        $spHoaStock = $this->spHoa->fresh()->so_luong;
        $lotHoaStock = $this->lotHoa->fresh()->so_luong_con_lai;

        Auth::login($this->adminUser);
        $controller = app(BoHoaController::class);

        $req = Request::create("/admin/bo-hoa/{$ids[0]}/bao-hong", 'POST', [
            'so_luong_hong' => 2,
            'ly_do'         => 'Héo trong quá trình bảo quản',
        ]);

        $res = $controller->baoHangHong($req, $ids[0]);
        $this->assertTrue($res->getData(true)['success']);

        // CASE 11: On Hand giảm 2 (từ 10 xuống 8)
        $stock = InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa);
        $this->assertEquals(8, $stock['on_hand']);

        // CASE 12: SanPham không giảm
        $this->assertEquals($spHoaStock, $this->spHoa->fresh()->so_luong);

        // CASE 13: ChiTietNhap không giảm
        $this->assertEquals($lotHoaStock, $this->lotHoa->fresh()->so_luong_con_lai);
    }

    /**
     * CASE 14: TonKhoController filter Bó hoa thành phẩm trả đúng dữ liệu.
     */
    public function test_case_14_ton_kho_controller_filter_bo_hoa(): void
    {
        $this->produceTenBouquets();

        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/ton-kho?loai_ton_kho=bo_hoa');
        $res->assertStatus(200);

        $items = $res->viewData('items');
        $this->assertTrue($items->isNotEmpty());

        $boItem = collect($items->items())->first(fn ($it) => $it->is_bo_hoa && $it->id === $this->mau->ma_mau_bo_hoa);
        $this->assertNotNull($boItem);
        $this->assertEquals(10, $boItem->ton_thuc_te);
        $this->assertEquals(0, $boItem->ton_dang_giu);
        $this->assertEquals(10, $boItem->ton_kha_dung);
    }

    /**
     * CASE 15: Available được tính bằng On Hand - Reserved, không để âm.
     */
    public function test_case_15_available_tinh_bang_on_hand_tru_reserved(): void
    {
        $ids = $this->produceTenBouquets();

        $stock = InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa);
        $this->assertEquals($stock['on_hand'] - $stock['reserved'], $stock['available']);
        $this->assertGreaterThanOrEqual(0, $stock['available']);

        // Giữ 3 bó
        $hd = HoaDon::create(['ma_khach_hang' => 1, 'tong_tien' => 300000, 'trang_thai' => 'PENDING']);
        InventoryReservationService::reserveReadyBouquet($hd, BoHoa::find($ids[0]));
        InventoryReservationService::reserveReadyBouquet($hd, BoHoa::find($ids[1]));
        InventoryReservationService::reserveReadyBouquet($hd, BoHoa::find($ids[2]));

        $stockAfter = InventoryReservationService::getStockForTemplate($this->mau->ma_mau_bo_hoa);
        $this->assertEquals(10, $stockAfter['on_hand']);
        $this->assertEquals(3, $stockAfter['reserved']);
        $this->assertEquals(7, $stockAfter['available']);
        $this->assertEquals($stockAfter['on_hand'] - $stockAfter['reserved'], $stockAfter['available']);
    }

    /**
     * CASE 16: Hai request reservation đồng thời: không reserve vượt Available.
     */
    public function test_case_16_concurrency_khong_reserve_vuot_available(): void
    {
        $ids = $this->produceTenBouquets();

        $bo1 = BoHoa::find($ids[0]);

        $hd1 = HoaDon::create(['ma_khach_hang' => 1, 'tong_tien' => 100000, 'trang_thai' => 'PENDING']);
        $hd2 = HoaDon::create(['ma_khach_hang' => 2, 'tong_tien' => 100000, 'trang_thai' => 'PENDING']);

        // Request 1 reserve bó thành công
        $res1 = InventoryReservationService::reserveReadyBouquet($hd1, $bo1);
        $this->assertNotNull($res1);

        // Request 2 cố gắng reserve cùng bó hoa đó phải bị ném ngoại lệ
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        InventoryReservationService::reserveReadyBouquet($hd2, $bo1);
    }
}
