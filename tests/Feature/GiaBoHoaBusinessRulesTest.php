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

class GiaBoHoaBusinessRulesTest extends TestCase
{
    use DatabaseTransactions;

    protected NguoiDung $adminUser;
    protected PhieuNhap $phieuNhap;
    protected array $createdSpIds = [];
    protected array $createdMauIds = [];
    protected array $createdBoIds = [];
    protected array $createdHdIds = [];

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
            'ten_dang_nhap'     => 'admin_gia_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'ADMIN',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);

        $nv = NhanVien::first();
        $ncc = \App\Models\NhaCungCap::first();
        $this->phieuNhap = PhieuNhap::create([
            'ma_nha_cung_cap' => $ncc ? $ncc->ma_nha_cung_cap : 1,
            'ma_nhan_vien'    => $nv ? $nv->ma_nhan_vien : 6,
            'tong_tien'       => 10000000,
            'ngay_nhap'       => now()->subDays(5),
            'trang_thai'      => 'CONFIRMED',
        ]);
    }

    protected function tearDown(): void
    {
        try {
            DB::rollBack();
        } catch (\Throwable $e) {
            // ignore
        }

        if (!empty($this->createdHdIds)) {
            DB::table('inventory_reservations')->whereIn('ma_hoa_don', $this->createdHdIds)->delete();
            DB::table('hoa_don')->whereIn('ma_hoa_don', $this->createdHdIds)->delete();
        }

        if (!empty($this->createdBoIds)) {
            DB::table('inventory_reservations')->whereIn('ma_bo_hoa', $this->createdBoIds)->delete();
            DB::table('chi_tiet_bo_hoa')->whereIn('ma_bo_hoa', $this->createdBoIds)->delete();
            DB::table('bo_hoa')->whereIn('ma_bo_hoa', $this->createdBoIds)->delete();
        }

        if (!empty($this->createdMauIds)) {
            DB::table('chi_tiet_mau_bo_hoa')->whereIn('ma_mau_bo_hoa', $this->createdMauIds)->delete();
            DB::table('mau_bo_hoa')->whereIn('ma_mau_bo_hoa', $this->createdMauIds)->delete();
        }

        if (!empty($this->createdSpIds)) {
            DB::table('inventory_reservations')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
            DB::table('chi_tiet_nhap')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
            DB::table('san_pham')->whereIn('ma_san_pham', $this->createdSpIds)->delete();
        }

        if (isset($this->adminUser)) {
            DB::table('nguoi_dung')->where('ma_nguoi_dung', $this->adminUser->ma_nguoi_dung)->delete();
        }

        parent::tearDown();
    }

    protected function createProduct(string $name, string $type, string $dvt, int $stock, float $price): SanPham
    {
        $unique = microtime(true) . '_' . mt_rand(100, 999);
        $sp = SanPham::create([
            'ten_san_pham'     => $name . ' ' . $unique,
            'loai_san_pham'    => $type,
            'don_vi_tinh'      => $dvt,
            'so_luong'         => $stock,
            'gia_ban_hien_tai' => $price,
            'trang_thai'       => 'DANG_BAN',
        ]);
        $this->createdSpIds[] = $sp->ma_san_pham;
        return $sp;
    }

    protected function createLot(SanPham $sp, int $qty, float $giaNhap, float $giaBan, ?\DateTimeInterface $ngayNhap = null): ChiTietNhap
    {
        $pn = $this->phieuNhap;
        if ($ngayNhap) {
            $pn = PhieuNhap::create([
                'ma_nha_cung_cap' => $this->phieuNhap->ma_nha_cung_cap,
                'ma_nhan_vien'    => $this->phieuNhap->ma_nhan_vien,
                'tong_tien'       => $qty * $giaNhap,
                'ngay_nhap'       => $ngayNhap,
                'trang_thai'      => 'CONFIRMED',
            ]);
        }

        return ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => $qty,
            'so_luong_con_lai' => $qty,
            'gia_nhap'         => $giaNhap,
            'gia_ban'          => $giaBan,
        ]);
    }

    /**
     * TEST CASE 1:
     * Một bó lấy nguyên liệu từ 1 lô mỗi loại.
     * -> giá vốn đúng.
     * -> giá bán = giá vốn × 1.5.
     */
    public function test_case_1_mot_bo_lay_nguyen_lieu_tu_1_lo_moi_loai(): void
    {
        Auth::login($this->adminUser);

        // Chuẩn bị 4 loại NVL cho Size S (7 hoa, 4 giấy, 1 ruy băng, 1 xốp)
        $spHoa  = $this->createProduct('Hoa Hồng TC1', 'HOA_TUOI', SanPham::DVT_CANH, 100, 20000);
        $spGiay = $this->createProduct('Giấy Gói TC1', 'GIAY_GOI', SanPham::DVT_TO, 50, 10000);
        $spRuy  = $this->createProduct('Ruy Băng TC1', 'RUY_BANG', SanPham::DVT_MET, 20, 10000);
        $spXop  = $this->createProduct('Mút Xốp TC1', 'MIENG_XOP', SanPham::DVT_CAI, 20, 15000);

        // 1 lô mỗi loại với giá nhập thực tế xác định:
        // Hoa: 15.000 / cành
        // Giấy: 5.000 / tờ
        // Ruy băng: 6.000 / mét
        // Xốp: 10.000 / miếng
        $this->createLot($spHoa, 50, 15000, 20000);
        $this->createLot($spGiay, 30, 5000, 10000);
        $this->createLot($spRuy, 10, 6000, 10000);
        $this->createLot($spXop, 10, 10000, 15000);

        // Tạo mẫu bó hoa size S
        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Case 1 ' . microtime(true),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $mau->ma_mau_bo_hoa;

        $mau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $spHoa->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
            ['ma_san_pham' => $spRuy->ma_san_pham, 'vai_tro' => 'RUY_BANG', 'so_luong' => 1],
            ['ma_san_pham' => $spXop->ma_san_pham, 'vai_tro' => 'XOP', 'so_luong' => 1],
        ]);

        // Sản xuất 1 bó hoa
        $controller = app(BoHoaController::class);
        $req = Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ]);
        $res = $controller->store($req);
        $data = $res->getData(true);

        $this->assertTrue($data['success']);
        $boId = $data['data']['ids'][0];
        $this->createdBoIds[] = $boId;

        $bo = BoHoa::findOrFail($boId);

        // Kỳ vọng:
        // 7 Hoa × 15.000 = 105.000
        // 4 Giấy × 5.000 = 20.000
        // 1 Ruy băng × 6.000 = 6.000
        // 1 Mút xốp × 10.000 = 10.000
        // Giá vốn = 141.000đ
        // Giá bán = 141.000 × 1.5 = 211.500đ
        $expectedCost = 7 * 15000 + 4 * 5000 + 1 * 6000 + 1 * 10000; // 141000
        $expectedPrice = round($expectedCost * 1.5, 2);               // 211500

        $this->assertEquals($expectedCost, (float) $bo->gia_von, 'Giá vốn bó hoa phải tính đúng từ giá nhập của lô.');
        $this->assertEquals($expectedPrice, (float) $bo->gia_ban, 'Giá bán bó hoa phải bằng đúng giá vốn × 1.5.');

        // Kiểm tra qua modal chi tiết giá
        $detailRes = $controller->chiTietGiaBoHoa($boId);
        $detailData = $detailRes->getData(true)['data'];
        $this->assertEquals($expectedCost, (float) $detailData['gia_von']);
        $this->assertEquals($expectedPrice, (float) $detailData['gia_ban']);
    }

    /**
     * TEST CASE 2:
     * Một loại hoa lấy từ 2 lô khác nhau.
     * -> tính đúng từng gia_nhap của từng lô.
     */
    public function test_case_2_mot_loai_hoa_lay_tu_2_lo_khac_nhau(): void
    {
        Auth::login($this->adminUser);

        $spHoa  = $this->createProduct('Hoa Hồng TC2', 'HOA_TUOI', SanPham::DVT_CANH, 100, 25000);
        $spGiay = $this->createProduct('Giấy Gói TC2', 'GIAY_GOI', SanPham::DVT_TO, 50, 10000);
        $spRuy  = $this->createProduct('Ruy Băng TC2', 'RUY_BANG', SanPham::DVT_MET, 20, 10000);
        $spXop  = $this->createProduct('Mút Xốp TC2', 'MIENG_XOP', SanPham::DVT_CAI, 20, 15000);

        // Hoa Hồng được chia làm 2 lô:
        // Lô A (nhập trước, SUB 4 DAYS): còn 5 cành, giá nhập 12.000
        // Lô B (nhập sau, SUB 2 DAYS): còn 20 cành, giá nhập 14.000
        $lotA = $this->createLot($spHoa, 5, 12000, 20000, now()->subDays(4));
        $lotB = $this->createLot($spHoa, 20, 14000, 22000, now()->subDays(2));

        $this->createLot($spGiay, 30, 5000, 10000);
        $this->createLot($spRuy, 10, 6000, 10000);
        $this->createLot($spXop, 10, 10000, 15000);

        // Mẫu size S cần 7 cành hoa
        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Case 2 ' . microtime(true),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $mau->ma_mau_bo_hoa;

        $mau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $spHoa->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
            ['ma_san_pham' => $spRuy->ma_san_pham, 'vai_tro' => 'RUY_BANG', 'so_luong' => 1],
            ['ma_san_pham' => $spXop->ma_san_pham, 'vai_tro' => 'XOP', 'so_luong' => 1],
        ]);

        // Sản xuất 1 bó hoa -> theo FIFO sẽ lấy:
        // 5 cành từ Lô A @ 12.000 = 60.000
        // 2 cành từ Lô B @ 14.000 = 28.000
        // Tổng tiền hoa = 88.000
        $controller = app(BoHoaController::class);
        $req = Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ]);
        $res = $controller->store($req);
        $boId = $res->getData(true)['data']['ids'][0];
        $this->createdBoIds[] = $boId;

        $bo = BoHoa::with('chiTietBoHoa.chiTietNhap')->findOrFail($boId);

        // Kiểm tra chi tiết hoa lấy từ 2 lô riêng biệt
        $flowerDetails = $bo->chiTietBoHoa->where('ma_san_pham', $spHoa->ma_san_pham);
        $this->assertCount(2, $flowerDetails, 'Phải có 2 bản ghi chi tiết cho hoa lấy từ 2 lô khác nhau.');

        $fromLotA = $flowerDetails->firstWhere('ma_chi_tiet_nhap', $lotA->ma_chi_tiet_nhap);
        $fromLotB = $flowerDetails->firstWhere('ma_chi_tiet_nhap', $lotB->ma_chi_tiet_nhap);

        $this->assertNotNull($fromLotA);
        $this->assertEquals(5, $fromLotA->so_luong);
        $this->assertEquals(12000, (float) $fromLotA->chiTietNhap->gia_nhap);

        $this->assertNotNull($fromLotB);
        $this->assertEquals(2, $fromLotB->so_luong);
        $this->assertEquals(14000, (float) $fromLotB->chiTietNhap->gia_nhap);

        // Giá vốn tổng:
        // Hoa: 5 * 12.000 + 2 * 14.000 = 88.000
        // Giấy: 4 * 5.000 = 20.000
        // Ruy băng: 1 * 6.000 = 6.000
        // Xốp: 1 * 10.000 = 10.000
        // Tổng giá vốn = 124.000
        // Giá bán = 124.000 * 1.5 = 186.000
        $expectedCost = (5 * 12000 + 2 * 14000) + 4 * 5000 + 1 * 6000 + 1 * 10000; // 124000
        $expectedPrice = round($expectedCost * 1.5, 2);                              // 186000

        $this->assertEquals($expectedCost, (float) $bo->gia_von);
        $this->assertEquals($expectedPrice, (float) $bo->gia_ban);
    }

    /**
     * TEST CASE 3:
     * Sau khi sản xuất, thay đổi giá nhập của lô/sản phẩm khác.
     * -> giá bó cũ không thay đổi (Snapshot giá).
     */
    public function test_case_3_thay_doi_gia_nhap_gia_bo_cu_khong_doi(): void
    {
        Auth::login($this->adminUser);

        $spHoa  = $this->createProduct('Hoa Hồng TC3', 'HOA_TUOI', SanPham::DVT_CANH, 100, 20000);
        $spGiay = $this->createProduct('Giấy Gói TC3', 'GIAY_GOI', SanPham::DVT_TO, 50, 10000);
        $spRuy  = $this->createProduct('Ruy Băng TC3', 'RUY_BANG', SanPham::DVT_MET, 20, 10000);
        $spXop  = $this->createProduct('Mút Xốp TC3', 'MIENG_XOP', SanPham::DVT_CAI, 20, 15000);

        $lotHoa = $this->createLot($spHoa, 50, 10000, 20000);
        $this->createLot($spGiay, 30, 5000, 10000);
        $this->createLot($spRuy, 10, 6000, 10000);
        $this->createLot($spXop, 10, 10000, 15000);

        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Case 3 ' . microtime(true),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $mau->ma_mau_bo_hoa;

        $mau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $spHoa->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
            ['ma_san_pham' => $spRuy->ma_san_pham, 'vai_tro' => 'RUY_BANG', 'so_luong' => 1],
            ['ma_san_pham' => $spXop->ma_san_pham, 'vai_tro' => 'XOP', 'so_luong' => 1],
        ]);

        $controller = app(BoHoaController::class);
        $res = $controller->store(Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ]));
        $boId = $res->getData(true)['data']['ids'][0];
        $this->createdBoIds[] = $boId;

        $boCu = BoHoa::findOrFail($boId);
        $giaVonGoc = (float) $boCu->gia_von;
        $giaBanGoc = (float) $boCu->gia_ban;

        // Ngày hôm sau: giá nhập hoa trên thị trường tăng vọt!
        // Tạo lô hoa mới với giá nhập 30.000 / bông và cập nhật giá bán SP lên 50.000
        $this->createLot($spHoa, 50, 30000, 50000, now());
        $spHoa->update(['gia_ban_hien_tai' => 50000]);

        // Bó cũ đã sản xuất phải GIỮ NGUYÊN snapshot giá vốn và giá bán
        $boCuFresh = $boCu->fresh();
        $this->assertEquals($giaVonGoc, (float) $boCuFresh->gia_von, 'Giá vốn bó cũ không được thay đổi.');
        $this->assertEquals($giaBanGoc, (float) $boCuFresh->gia_ban, 'Giá bán bó cũ không được thay đổi.');

        // Kiểm tra admin API getDuLieuBoHoa cũng trả về đúng giá snapshot cũ
        $listData = BoHoaController::getDuLieuBoHoa(new Request(['q_bo_hoa' => $mau->ten_mau]));
        $found = collect($listData['danhSachBoHoa']->items())->firstWhere('ma_mau_bo_hoa', $mau->ma_mau_bo_hoa);
        $this->assertNotNull($found);
        $this->assertEquals($giaVonGoc, (float) $found->gia_von);
        $this->assertEquals($giaBanGoc, (float) $found->gia_ban);
    }

    /**
     * TEST CASE 4:
     * Bán bó hoa thành phẩm.
     * -> chỉ giảm tồn bó hoa.
     * -> không giảm nguyên liệu lần 2.
     */
    public function test_case_4_ban_bo_hoa_thanh_pham_khong_giam_nguyen_lieu_lan_2(): void
    {
        Auth::login($this->adminUser);

        $spHoa  = $this->createProduct('Hoa Hồng TC4', 'HOA_TUOI', SanPham::DVT_CANH, 100, 20000);
        $spGiay = $this->createProduct('Giấy Gói TC4', 'GIAY_GOI', SanPham::DVT_TO, 50, 10000);
        $spRuy  = $this->createProduct('Ruy Băng TC4', 'RUY_BANG', SanPham::DVT_MET, 20, 10000);
        $spXop  = $this->createProduct('Mút Xốp TC4', 'MIENG_XOP', SanPham::DVT_CAI, 20, 15000);

        $lotHoa = $this->createLot($spHoa, 50, 10000, 20000);
        $lotGiay = $this->createLot($spGiay, 30, 5000, 10000);
        $lotRuy = $this->createLot($spRuy, 10, 6000, 10000);
        $lotXop = $this->createLot($spXop, 10, 10000, 15000);

        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Case 4 ' . microtime(true),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $mau->ma_mau_bo_hoa;

        $mau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $spHoa->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
            ['ma_san_pham' => $spRuy->ma_san_pham, 'vai_tro' => 'RUY_BANG', 'so_luong' => 1],
            ['ma_san_pham' => $spXop->ma_san_pham, 'vai_tro' => 'XOP', 'so_luong' => 1],
        ]);

        // Sản xuất 1 bó hoa
        $controller = app(BoHoaController::class);
        $res = $controller->store(Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ]));
        $boId = $res->getData(true)['data']['ids'][0];
        $this->createdBoIds[] = $boId;

        // Lưu trữ số lượng tồn kho nguyên liệu ngay SAU KHI SẢN XUẤT
        $spHoaStockAfterProd = $spHoa->fresh()->so_luong;
        $lotHoaStockAfterProd = $lotHoa->fresh()->so_luong_con_lai;
        $spGiayStockAfterProd = $spGiay->fresh()->so_luong;
        $lotGiayStockAfterProd = $lotGiay->fresh()->so_luong_con_lai;

        // Tạo đơn hàng bán bó hoa này
        $khach = \App\Models\KhachHang::first();
        $hd = HoaDon::create([
            'ma_khach_hang'         => $khach ? $khach->ma_khach_hang : 1,
            'trang_thai'            => 'PENDING',
            'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'ngay_dat'              => now(),
            'tong_tien'             => 200000,
        ]);
        $this->createdHdIds[] = $hd->ma_hoa_don;

        $bo = BoHoa::findOrFail($boId);
        InventoryReservationService::reserveReadyBouquet($hd, $bo);

        // Xác nhận đơn hàng (CONFIRM / DELIVERED)
        InventoryReservationService::confirmOrder($hd);

        // Kết quả:
        // 1. Tồn bó hoa giảm đi 1 (từ 1 về 0)
        $this->assertEquals(0, $bo->fresh()->so_luong, 'Tồn bó hoa thành phẩm phải giảm đi 1.');

        // 2. Tồn kho nguyên liệu (SanPham và ChiTietNhap) KHÔNG ĐƯỢC GIẢM THÊM LẦN NỮA!
        $this->assertEquals($spHoaStockAfterProd, $spHoa->fresh()->so_luong, 'Hoa không được giảm lần 2.');
        $this->assertEquals($lotHoaStockAfterProd, $lotHoa->fresh()->so_luong_con_lai, 'Lô hoa không được giảm lần 2.');
        $this->assertEquals($spGiayStockAfterProd, $spGiay->fresh()->so_luong, 'Giấy không được giảm lần 2.');
        $this->assertEquals($lotGiayStockAfterProd, $lotGiay->fresh()->so_luong_con_lai, 'Lô giấy không được giảm lần 2.');
    }

    /**
     * TEST CASE 5:
     * Hủy đơn khi bó hoa đang reserved.
     * -> release bó hoa (về ACTIVE).
     * -> không restore nguyên liệu.
     */
    public function test_case_5_huy_don_bo_hoa_reserved_khong_restore_nguyen_lieu(): void
    {
        Auth::login($this->adminUser);

        $spHoa  = $this->createProduct('Hoa Hồng TC5', 'HOA_TUOI', SanPham::DVT_CANH, 100, 20000);
        $spGiay = $this->createProduct('Giấy Gói TC5', 'GIAY_GOI', SanPham::DVT_TO, 50, 10000);
        $spRuy  = $this->createProduct('Ruy Băng TC5', 'RUY_BANG', SanPham::DVT_MET, 20, 10000);
        $spXop  = $this->createProduct('Mút Xốp TC5', 'MIENG_XOP', SanPham::DVT_CAI, 20, 15000);

        $lotHoa = $this->createLot($spHoa, 50, 10000, 20000);
        $this->createLot($spGiay, 30, 5000, 10000);
        $this->createLot($spRuy, 10, 6000, 10000);
        $this->createLot($spXop, 10, 10000, 15000);

        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Case 5 ' . microtime(true),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $mau->ma_mau_bo_hoa;

        $mau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $spHoa->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
            ['ma_san_pham' => $spRuy->ma_san_pham, 'vai_tro' => 'RUY_BANG', 'so_luong' => 1],
            ['ma_san_pham' => $spXop->ma_san_pham, 'vai_tro' => 'XOP', 'so_luong' => 1],
        ]);

        $controller = app(BoHoaController::class);
        $res = $controller->store(Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ]));
        $boId = $res->getData(true)['data']['ids'][0];
        $this->createdBoIds[] = $boId;

        $spHoaStockAfterProd = $spHoa->fresh()->so_luong;
        $lotHoaStockAfterProd = $lotHoa->fresh()->so_luong_con_lai;

        $khach = \App\Models\KhachHang::first();
        $hd = HoaDon::create([
            'ma_khach_hang'         => $khach ? $khach->ma_khach_hang : 1,
            'trang_thai'            => 'PENDING',
            'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'ngay_dat'              => now(),
            'tong_tien'             => 200000,
        ]);
        $this->createdHdIds[] = $hd->ma_hoa_don;

        $bo = BoHoa::findOrFail($boId);
        InventoryReservationService::reserveReadyBouquet($hd, $bo);

        // Hủy đơn hàng đang reserved
        InventoryReservationService::releaseOrder($hd);

        // Bó hoa phải quay về ACTIVE, ma_hoa_don = null, so_luong = 1
        $boFresh = $bo->fresh();
        $this->assertEquals('ACTIVE', $boFresh->trang_thai);
        $this->assertNull($boFresh->ma_hoa_don);
        $this->assertEquals(1, $boFresh->so_luong);

        // Tồn kho nguyên liệu KHÔNG ĐƯỢC TĂNG LẠI (không restore BOM)
        $this->assertEquals($spHoaStockAfterProd, $spHoa->fresh()->so_luong, 'Không được hoàn lại hoa.');
        $this->assertEquals($lotHoaStockAfterProd, $lotHoa->fresh()->so_luong_con_lai, 'Không được hoàn lại lô hoa.');
    }

    /**
     * TEST CASE 6:
     * Bó hoa bị hỏng.
     * -> giảm bó hoa thành phẩm.
     * -> không tác động tồn raw material lần 2.
     */
    public function test_case_6_bo_hoa_bi_hong_khong_tac_dong_ton_raw_material(): void
    {
        Auth::login($this->adminUser);

        $spHoa  = $this->createProduct('Hoa Hồng TC6', 'HOA_TUOI', SanPham::DVT_CANH, 100, 20000);
        $spGiay = $this->createProduct('Giấy Gói TC6', 'GIAY_GOI', SanPham::DVT_TO, 50, 10000);
        $spRuy  = $this->createProduct('Ruy Băng TC6', 'RUY_BANG', SanPham::DVT_MET, 20, 10000);
        $spXop  = $this->createProduct('Mút Xốp TC6', 'MIENG_XOP', SanPham::DVT_CAI, 20, 15000);

        $lotHoa = $this->createLot($spHoa, 50, 10000, 20000);
        $this->createLot($spGiay, 30, 5000, 10000);
        $this->createLot($spRuy, 10, 6000, 10000);
        $this->createLot($spXop, 10, 10000, 15000);

        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Case 6 ' . microtime(true),
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);
        $this->createdMauIds[] = $mau->ma_mau_bo_hoa;

        $mau->chiTietMauBoHoa()->createMany([
            ['ma_san_pham' => $spHoa->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $spGiay->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
            ['ma_san_pham' => $spRuy->ma_san_pham, 'vai_tro' => 'RUY_BANG', 'so_luong' => 1],
            ['ma_san_pham' => $spXop->ma_san_pham, 'vai_tro' => 'XOP', 'so_luong' => 1],
        ]);

        $controller = app(BoHoaController::class);
        $res = $controller->store(Request::create('/admin/bo-hoa', 'POST', [
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ]));
        $boId = $res->getData(true)['data']['ids'][0];
        $this->createdBoIds[] = $boId;

        $spHoaStockAfterProd = $spHoa->fresh()->so_luong;
        $lotHoaStockAfterProd = $lotHoa->fresh()->so_luong_con_lai;

        // Báo hỏng 1 bó hoa thành phẩm
        $hongRes = $controller->baoHangHong(new Request([
            'so_luong_hong' => 1,
            'ly_do'         => 'Héo do vận chuyển',
            'ghi_chu'       => 'Test hỏng',
        ]), $boId);

        $this->assertTrue($hongRes->getData(true)['success']);

        // Bó hoa giảm về so_luong = 0
        $bo = BoHoa::findOrFail($boId);
        $this->assertEquals(0, $bo->so_luong);

        // Nguyên liệu KHÔNG bị tác động (không trừ thêm lần 2, không hoàn lại)
        $this->assertEquals($spHoaStockAfterProd, $spHoa->fresh()->so_luong);
        $this->assertEquals($lotHoaStockAfterProd, $lotHoa->fresh()->so_luong_con_lai);
    }
}
