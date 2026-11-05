<?php

namespace Tests\Feature;

use App\Models\BaoCaoHangHong;
use App\Models\BoHoa;
use App\Models\ChiTietHangHoan;
use App\Models\ChiTietHoaDon;
use App\Models\ChiTietNhap;
use App\Models\FlashSale;
use App\Models\GiaoHangThatBai;
use App\Models\HangHoan;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HangHoanFlashSaleQCTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $shipper;
    protected $nhanVien;
    protected $khachHang;

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

        $this->admin = NguoiDung::where('vai_tro', 'ADMIN')->first();
        if (!$this->admin) {
            $this->admin = NguoiDung::create([
                'ten_dang_nhap' => 'admin_test_' . uniqid(),
                'mat_khau'      => bcrypt('password'),
                'ho_ten'        => 'Admin Test',
                'email'         => 'admin_test_' . uniqid() . '@example.com',
                'vai_tro'       => 'ADMIN',
                'trang_thai'    => 1,
            ]);
        }

        $userShipper = NguoiDung::where('vai_tro', 'SHIPPER')->first();
        if (!$userShipper) {
            $userShipper = NguoiDung::create([
                'ten_dang_nhap' => 'shipper_test_' . uniqid(),
                'mat_khau'      => bcrypt('password'),
                'ho_ten'        => 'Shipper Test',
                'email'         => 'ship_' . uniqid() . '@example.com',
                'vai_tro'       => 'SHIPPER',
                'trang_thai'    => 1,
            ]);
        }

        $this->shipper = NhanVien::where('chuc_vu', 'SHIPPER')->first();
        if (!$this->shipper) {
            $this->shipper = NhanVien::create([
                'ma_nhan_vien'         => $userShipper->ma_nguoi_dung,
                'ten_nhan_vien'        => 'Shipper Test',
                'chuc_vu'              => 'SHIPPER',
                'trang_thai_hoat_dong' => 'ONLINE',
                'so_dien_thoai'        => '0911223344',
                'luong'                => 5000000,
            ]);
        }

        $userNv = NguoiDung::where('vai_tro', 'NHAN_VIEN')->first();
        if (!$userNv) {
            $userNv = NguoiDung::create([
                'ten_dang_nhap' => 'nv_test_' . uniqid(),
                'mat_khau'      => bcrypt('password'),
                'ho_ten'        => 'Nhân Viên Kho Test',
                'email'         => 'nv_' . uniqid() . '@example.com',
                'vai_tro'       => 'NHAN_VIEN',
                'trang_thai'    => 1,
            ]);
        }

        $this->nhanVien = NhanVien::where('chuc_vu', '!=', 'SHIPPER')->first();
        if (!$this->nhanVien) {
            $this->nhanVien = NhanVien::create([
                'ma_nhan_vien'         => $userNv->ma_nguoi_dung,
                'ten_nhan_vien'        => 'Nhân Viên Kho Test',
                'chuc_vu'              => 'THU_KHO',
                'trang_thai_hoat_dong' => 'ONLINE',
                'so_dien_thoai'        => '0933445566',
                'luong'                => 6000000,
            ]);
        }

        $this->khachHang = KhachHang::first();
        if (!$this->khachHang) {
            $userKh = NguoiDung::create([
                'ten_dang_nhap' => 'kh_' . uniqid(),
                'mat_khau'      => bcrypt('password'),
                'ho_ten'        => 'Khách Test',
                'email'         => 'kh_' . uniqid() . '@test.com',
                'vai_tro'       => 'KHACH_HANG',
                'trang_thai'    => 1,
            ]);
            $this->khachHang = KhachHang::create([
                'ma_khach_hang'  => $userKh->ma_nguoi_dung,
                'ten_khach_hang' => 'Khách Test',
                'so_dien_thoai'  => '0901234567',
                'email'          => 'kh_' . uniqid() . '@test.com',
                'quyen_cod'      => 'BINH_THUONG',
            ]);
        }
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
     * Helper tạo sản phẩm + phiếu nhập + chi tiết nhập có sẵn trong DB
     */
    protected function createProductAndLot(int $stock = 20, float $price = 100000): array
    {
        $ncc = NhaCungCap::first() ?? NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Test ' . uniqid(),
            'dia_chi'          => 'HCM',
            'so_dien_thoai'    => '0900000000',
            'email'            => 'ncc_' . uniqid() . '@test.com',
            'trang_thai'       => 1,
        ]);

        $sp = SanPham::create([
            'ten_san_pham'     => 'Hoa Hồng Đỏ Test ' . rand(1000, 9999),
            'gia_ban_hien_tai' => $price,
            'so_luong'         => $stock,
            'trang_thai'       => 'DANG_BAN',
            'loai_san_pham'    => 'HOA_TUOI',
            'don_vi_tinh'      => 'CANH',
        ]);

        $pn = PhieuNhap::create([
            'ma_nha_cung_cap' => $ncc->ma_nha_cung_cap,
            'ma_nhan_vien'    => $this->nhanVien->ma_nhan_vien,
            'ngay_nhap'       => now(),
            'tong_tien'       => $stock * 50000,
            'trang_thai'      => 'CONFIRMED',
        ]);

        $ctn = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => $stock,
            'so_luong_nhap'    => $stock,
            'so_luong_con_lai' => $stock,
            'gia_nhap'         => 50000,
            'gia_ban'          => $price,
            'ngay_het_han'     => now()->addDays(5)->toDateString(),
        ]);

        return [$sp, $ctn];
    }

    /**
     * Helper tạo đơn hàng giao thất bại và hàng hoàn đã nhận tại kho (chờ QC)
     */
    protected function createReturnedOrder(array $itemsConfig, string $loaiLoi = 'SHIPPER'): array
    {
        $tongTien = 0;
        foreach ($itemsConfig as $cfg) {
            $tongTien += $cfg['so_luong'] * $cfg['san_pham']->gia_ban_hien_tai;
        }

        $hd = HoaDon::create([
            'ma_khach_hang'          => $this->khachHang->ma_khach_hang,
            'ma_nhan_vien_giao'      => $this->shipper->ma_nhan_vien,
            'ngay_dat'               => now()->subHours(2),
            'tong_tien'              => $tongTien,
            'trang_thai'             => 'CANCELLED',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao'           => '123 Đào Duy Từ, Q10',
            'so_dien_thoai'          => '0912345678',
            'co_hang_hoan'           => 1,
            'nguon_huy'              => $loaiLoi,
            'ly_do_huy'              => 'Giao thất bại - không liên lạc được',
            'thoi_diem_huy'          => now()->subHour(),
        ]);

        $ghtb = GiaoHangThatBai::create([
            'ma_hoa_don'        => $hd->ma_hoa_don,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'loai_loi'          => $loaiLoi,
            'ly_do'             => 'Không liên lạc được khách hàng',
            'thoi_diem_bao'     => now()->subHour(),
            'trang_thai'        => 'DA_BAO',
        ]);

        $hh = HangHoan::create([
            'ma_hoa_don'             => $hd->ma_hoa_don,
            'ma_giao_hang_that_bai'  => $ghtb->ma_giao_hang_that_bai,
            'trang_thai'             => 'DA_NHAN_TAI_KHO',
            'thoi_diem_nhan_tai_kho' => now(),
        ]);

        $createdCtList = [];
        foreach ($itemsConfig as $cfg) {
            $sp = $cfg['san_pham'];
            $ctn = $cfg['chi_tiet_nhap'];
            $qty = $cfg['so_luong'];

            $cthd = ChiTietHoaDon::create([
                'ma_hoa_don'          => $hd->ma_hoa_don,
                'ma_san_pham'         => $sp->ma_san_pham,
                'ma_chi_tiet_nhap'    => $ctn->ma_chi_tiet_nhap,
                'so_luong'            => $qty,
                'gia_nhap_snapshot'   => $ctn->gia_nhap,
                'gia_ban_snapshot'    => $sp->gia_ban_hien_tai,
            ]);

            $cthh = ChiTietHangHoan::create([
                'ma_hang_hoan'                => $hh->ma_hang_hoan,
                'ma_san_pham'                 => $sp->ma_san_pham,
                'ma_bo_hoa'                   => null,
                'ma_chi_tiet_hoa_don'         => $cthd->ma_chi_tiet,
                'so_luong_theo_don'           => $qty,
                'so_luong_hong'               => 0,
                'so_luong_con_ban_duoc'       => 0,
                'so_luong_con_lai_flash_sale' => 0,
                'gia_von_snapshot'            => $ctn->gia_nhap,
                'gia_ban_snapshot'            => $sp->gia_ban_hien_tai,
                'ket_qua'                     => 'CHO_KIEM_TRA',
            ]);

            $createdCtList[] = $cthh;
        }

        return [$hd, $ghtb, $hh, $createdCtList];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC01: Hoàn hàng về kho nhưng CHƯA QC → Tồn không tăng, không có Flash Sale
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc01_returned_to_warehouse_not_qced_stock_not_increased_no_flash_sale()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 100000);
        $initSpStock = (int) $sp->fresh()->so_luong;
        $initLotStock = (int) $ctn->fresh()->so_luong_con_lai;

        // Giả lập đơn giao thất bại, đã nhận tại kho nhưng CHƯA QC
        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 2]
        ]);

        $this->assertEquals('DA_NHAN_TAI_KHO', $hh->fresh()->trang_thai);
        $this->assertEquals($initSpStock, (int) $sp->fresh()->so_luong);
        $this->assertEquals($initLotStock, (int) $ctn->fresh()->so_luong_con_lai);

        $flashSaleCount = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->count();
        $this->assertEquals(0, $flashSaleCount, 'Chưa QC tuyệt đối không được tự động tạo Flash Sale');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC02: QC chọn "Còn tốt" → Tồn kho tăng đúng, KHÔNG tạo Flash Sale
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc02_qc_good_restocks_inventory_no_flash_sale()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 100000);
        $initSpStock = (int) $sp->fresh()->so_luong;
        $initLotStock = (int) $ctn->fresh()->so_luong_con_lai;

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 3]
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'CON_TOT',
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('don-hang.index', ['tab' => 'failed']));

        // Kiểm tra tồn tăng đúng bằng 3
        $this->assertEquals($initSpStock + 3, (int) $sp->fresh()->so_luong);
        $this->assertEquals($initLotStock + 3, (int) $ctn->fresh()->so_luong_con_lai);

        // Kiểm tra KHÔNG tạo Flash Sale
        $fsCount = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->count();
        $this->assertEquals(0, $fsCount);

        // Kiểm tra trạng thái hàng hoàn đã kiểm tra
        $this->assertEquals('DA_KIEM_TRA', $hh->fresh()->trang_thai);
        $this->assertEquals('CON_TOT', $cts[0]->fresh()->ket_qua);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC03: QC chọn "Giảm chất lượng" 30% → Tạo Flash Sale giảm 30%, số lượng và giá đúng
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc03_qc_degraded_30_percent_creates_flash_sale_correct_price_and_stock()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 100000);
        $initSpStock = (int) $sp->fresh()->so_luong;

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 2]
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 30,
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('don-hang.index', ['tab' => 'failed']));

        // Flash Sale được tạo với đúng 30%
        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fs);
        $this->assertEquals(30, $fs->ti_le_giam);
        $this->assertEquals(2, $fs->so_luong);
        $this->assertEquals(70000.0, (float) $fs->gia_flash_sale);
        $this->assertEquals('HANG_HOAN', $fs->nguon);
        $this->assertEquals('DANG_BAN', $fs->trang_thai);

        // Tồn kho vật lý tăng đúng số lượng hàng về kho để có thể giao khi bán FS
        $this->assertEquals($initSpStock + 2, (int) $sp->fresh()->so_luong);
        $this->assertEquals('DA_KIEM_TRA', $hh->fresh()->trang_thai);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC04: QC chọn "Giảm chất lượng" 50% → Tạo Flash Sale giảm 50%
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc04_qc_degraded_50_percent_creates_flash_sale_correct_price_and_stock()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 200000);

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 4]
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 50,
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('don-hang.index', ['tab' => 'failed']));

        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fs);
        $this->assertEquals(50, $fs->ti_le_giam);
        $this->assertEquals(4, $fs->so_luong);
        $this->assertEquals(100000.0, (float) $fs->gia_flash_sale);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC05: QC chọn "Giảm chất lượng" 75% → Tạo Flash Sale giảm 75%
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc05_qc_degraded_75_percent_creates_flash_sale_correct_price_and_stock()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 100000);

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 1]
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 75,
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('don-hang.index', ['tab' => 'failed']));

        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fs);
        $this->assertEquals(75, $fs->ti_le_giam);
        $this->assertEquals(1, $fs->so_luong);
        $this->assertEquals(25000.0, (float) $fs->gia_flash_sale);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC06: QC chọn "Hỏng hoàn toàn" → Tồn KHÔNG tăng, KHÔNG tạo Flash Sale, ghi nhận hỏng
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc06_qc_damaged_no_restock_no_flash_sale_records_loss()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 120000);
        $initSpStock = (int) $sp->fresh()->so_luong;
        $initLotStock = (int) $ctn->fresh()->so_luong_con_lai;

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 2]
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly'  => 'HONG',
                        'ly_do_hong'   => 'Hoa bị dập nát hoàn toàn do shipper làm rơi',
                        'ma_nhan_vien' => $this->nhanVien->ma_nhan_vien,
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('don-hang.index', ['tab' => 'failed']));

        // Tồn kho KHÔNG tăng
        $this->assertEquals($initSpStock, (int) $sp->fresh()->so_luong);
        $this->assertEquals($initLotStock, (int) $ctn->fresh()->so_luong_con_lai);

        // KHÔNG tạo Flash Sale
        $this->assertEquals(0, FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->count());

        // Ghi nhận báo cáo hỏng đúng
        $bc = BaoCaoHangHong::where('ma_hoa_don', $hd->ma_hoa_don)->first();
        $this->assertNotNull($bc);
        $this->assertEquals(2, $bc->so_luong_hong);
        $this->assertEquals(240000.0, (float) $bc->gia_tri_thiet_hai);
        $this->assertEquals('HONG', $cts[0]->fresh()->ket_qua);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC07: Đơn nhiều sản phẩm, mỗi sản phẩm chọn 1 nhánh khác nhau (Còn tốt, FS 50%, Hỏng)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc07_multi_item_order_each_item_chooses_different_branch()
    {
        [$spA, $ctnA] = $this->createProductAndLot(stock: 10, price: 100000);
        [$spB, $ctnB] = $this->createProductAndLot(stock: 10, price: 150000);
        [$spC, $ctnC] = $this->createProductAndLot(stock: 10, price: 200000);

        $initStockA = (int) $spA->fresh()->so_luong;
        $initStockB = (int) $spB->fresh()->so_luong;
        $initStockC = (int) $spC->fresh()->so_luong;

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $spA, 'chi_tiet_nhap' => $ctnA, 'so_luong' => 2],
            ['san_pham' => $spB, 'chi_tiet_nhap' => $ctnB, 'so_luong' => 3],
            ['san_pham' => $spC, 'chi_tiet_nhap' => $ctnC, 'so_luong' => 1],
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    // SP A: Còn tốt
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'CON_TOT',
                    ],
                    // SP B: Giảm chất lượng (Flash Sale 50%)
                    $cts[1]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 50,
                    ],
                    // SP C: Hỏng hoàn toàn
                    $cts[2]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly'  => 'HONG',
                        'ly_do_hong'   => 'Bị gãy cành',
                        'ma_nhan_vien' => $this->nhanVien->ma_nhan_vien,
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('don-hang.index', ['tab' => 'failed']));

        // SP A: Restock kho, không Flash Sale
        $this->assertEquals($initStockA + 2, (int) $spA->fresh()->so_luong);
        $this->assertEquals(0, FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->count());
        $this->assertEquals('CON_TOT', $cts[0]->fresh()->ket_qua);

        // SP B: Tạo Flash Sale 50%, số lượng 3, giá 75k
        $fsB = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[1]->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fsB);
        $this->assertEquals(50, $fsB->ti_le_giam);
        $this->assertEquals(3, $fsB->so_luong);
        $this->assertEquals(75000.0, (float) $fsB->gia_flash_sale);
        $this->assertEquals('FLASH_SALE', $cts[1]->fresh()->ket_qua);

        // SP C: Hỏng, tồn không tăng, ghi nhận báo cáo hỏng
        $this->assertEquals($initStockC, (int) $spC->fresh()->so_luong);
        $this->assertEquals(0, FlashSale::where('ma_chi_tiet_hang_hoan', $cts[2]->ma_chi_tiet_hang_hoan)->count());
        $this->assertDatabaseHas('bao_cao_hang_hong', [
            'ma_hoa_don'  => $hd->ma_hoa_don,
            'ma_san_pham' => $spC->ma_san_pham,
            'so_luong_hong' => 1,
        ]);
        $this->assertEquals('HONG', $cts[2]->fresh()->ket_qua);

        $this->assertEquals('DA_KIEM_TRA', $hh->fresh()->trang_thai);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC08: Thử QC lần 2 trên đơn đã hoàn tất QC → Hệ thống chặn, không cho phép
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc08_repeat_qc_on_already_completed_qc_is_blocked()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 100000);

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 1]
        ]);

        // QC lần 1 thành công
        $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'CON_TOT',
                    ],
                ],
            ]
        );
        $this->assertEquals('DA_KIEM_TRA', $hh->fresh()->trang_thai);

        // Thử QC lần 2 trên đơn đã kiểm tra
        $response2 = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 50,
                    ],
                ],
            ]
        );

        // Bị chặn do validation exception
        $response2->assertSessionHasErrors('hoa_don');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC09: Kiểm tra Flash Sale vừa tạo hiển thị đúng ở trang danh sách Flash Sale
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc09_created_flash_sale_displays_in_flash_sale_list_hang_hoan_filter()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 160000);

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 2]
        ]);

        $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 50,
                    ],
                ],
            ]
        );

        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fs);

        // Xem trang Flash Sale lọc nguồn HANG_HOAN
        $response = $this->actingAs($this->admin)->get(route('admin.hoan-hang.flash-sale.index', ['filter' => 'HANG_HOAN']));
        $response->assertStatus(200);
        $response->assertSee($sp->ten_san_pham);
        $response->assertSee('50%');
        $response->assertSee(number_format($fs->gia_flash_sale)); // 80,000đ
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC10: Bán thử sản phẩm Flash Sale vừa tạo → Trừ đúng quota Flash Sale, tồn kho nhất quán
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc10_purchasing_flash_sale_item_consumes_quota_and_maintains_stock()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 5, price: 100000);

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 3]
        ]);

        $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 30,
                    ],
                ],
            ]
        );

        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fs);
        $this->assertEquals(3, $fs->so_luong);
        $this->assertEquals(0, $fs->so_luong_da_ban);

        // Tạo đơn khách hàng mua 2 sản phẩm Flash Sale
        $newOrder = HoaDon::create([
            'ma_khach_hang'          => $this->khachHang->ma_khach_hang,
            'ngay_dat'               => now(),
            'tong_tien'              => 2 * $fs->gia_flash_sale,
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao'           => 'Test address',
            'so_dien_thoai'          => '0901234567',
        ]);

        // Giữ tồn (RESERVED) qua InventoryReservationService
        InventoryReservationService::reserveSingleItems($newOrder, [
            [
                'ma_san_pham'   => $sp->ma_san_pham,
                'ma_flash_sale' => $fs->ma_flash_sale,
                'so_luong'      => 2,
            ]
        ]);

        // Xác nhận đơn để consume reservation
        InventoryReservationService::confirmOrder($newOrder);

        // Kiểm tra quota đã bán tăng lên 2
        $this->assertEquals(2, (int) $fs->fresh()->so_luong_da_ban);
        $this->assertEquals(1, (int) $fs->fresh()->available_quota);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC11: Phân bổ số lượng theo từng nhánh khi dòng hoàn có số lượng > 1 (Split Quantity)
    // Ví dụ: Đơn hoàn 5 hoa → 2 Còn tốt, 2 Flash Sale 50%, 1 Báo hỏng
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc11_split_quantity_across_multiple_branches()
    {
        [$sp, $ctn] = $this->createProductAndLot(stock: 10, price: 100000);
        $spInitialStock = (int) $sp->fresh()->so_luong;

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 5]
        ]);

        $ct = $cts[0];
        $this->assertEquals(5, (int) $ct->so_luong_theo_don);

        $response = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $ct->ma_chi_tiet_hang_hoan => [
                        'so_luong_tot'        => 2,
                        'so_luong_flash_sale' => 2,
                        'ti_le_giam'          => 50,
                        'so_luong_hong'       => 1,
                        'ly_do_hong'          => 'Gãy dập 1 cành trong quá trình chuyển hoàn',
                        'ma_nhan_vien'        => $this->nhanVien->ma_nhan_vien,
                    ],
                ],
            ]
        );

        $response->assertSessionHasNoErrors();

        // Kiểm tra ChiTietHangHoan
        $ctFresh = $ct->fresh();
        $this->assertEquals(4, (int) $ctFresh->so_luong_con_ban_duoc); // 2 tốt + 2 flash
        $this->assertEquals(1, (int) $ctFresh->so_luong_hong);
        $this->assertEquals(50000, (float) $ctFresh->gia_flash_sale_snapshot);

        // Kiểm tra Flash Sale được tạo với đúng quota 2
        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $ct->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fs);
        $this->assertEquals(2, $fs->so_luong);
        $this->assertEquals(50, $fs->ti_le_giam);
        $this->assertEquals(50000, $fs->gia_flash_sale);

        // Kiểm tra Báo cáo hàng hỏng được tạo với đúng 1 số lượng
        $bc = BaoCaoHangHong::where('ma_hoa_don', $hd->ma_hoa_don)->where('ma_san_pham', $sp->ma_san_pham)->first();
        $this->assertNotNull($bc);
        $this->assertEquals(1, $bc->so_luong_hong);

        // Kiểm tra Tồn kho vật lý: tăng 4 (2 tốt + 2 flash physical)
        $this->assertEquals($spInitialStock + 4, (int) $sp->fresh()->so_luong);

        // Tồn khả dụng cho khách mua giá thường: chỉ tăng 2! (không bị double availability từ 2 flash sale)
        $availNormal = InventoryReservationService::getAvailableForProduct($sp->fresh());
        $this->assertEquals($spInitialStock + 2, $availNormal);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TC12: Tuân thủ Hạn sử dụng (HSD) của lô hoa tươi (Không hardcode 3 ngày, chặn khi hết hạn)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_tc12_flash_sale_strictly_respects_lot_expiration_date()
    {
        // 1. Trường hợp lô hoa chỉ còn hạn 2 ngày nữa (< thời gian mặc định cấu hình)
        $ncc = NhaCungCap::first() ?? NhaCungCap::create(['ten_nha_cung_cap' => 'NCC HSD Test']);
        $pn = PhieuNhap::create([
            'ma_nha_cung_cap' => $ncc->ma_nha_cung_cap,
            'ma_nhan_vien'    => $this->nhanVien->ma_nhan_vien,
            'ngay_nhap'       => now(),
            'tong_tien'       => 100000,
            'trang_thai'      => 'CONFIRMED',
        ]);

        $sp = SanPham::create([
            'ten_san_pham'      => 'Hoa Cẩm Chướng HSD ' . uniqid(),
            'gia_ban_hien_tai'  => 80000,
            'so_luong'          => 10,
            'trang_thai'        => 'DANG_BAN',
            'don_vi_tinh'       => 'Cành',
        ]);

        $expiringInTwoDays = now()->addDays(2)->endOfDay();
        $ctn = ChiTietNhap::create([
            'ma_phieu_nhap'     => $pn->ma_phieu_nhap,
            'ma_san_pham'       => $sp->ma_san_pham,
            'so_luong'          => 10,
            'so_luong_con_lai'  => 10,
            'gia_nhap'          => 40000,
            'gia_ban'           => 80000,
            'ngay_het_han'      => $expiringInTwoDays,
        ]);

        [$hd, $ghtb, $hh, $cts] = $this->createReturnedOrder([
            ['san_pham' => $sp, 'chi_tiet_nhap' => $ctn, 'so_luong' => 2]
        ]);

        $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd->ma_hoa_don),
            [
                'items' => [
                    $cts[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 50,
                    ],
                ],
            ]
        );

        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $cts[0]->ma_chi_tiet_hang_hoan)->first();
        $this->assertNotNull($fs);
        // Thời gian kết thúc Flash Sale không được vượt quá HSD của lô (2 ngày)
        $this->assertNotNull($fs->ket_thuc);
        $this->assertLessThanOrEqual(
            $expiringInTwoDays->timestamp,
            \Carbon\Carbon::parse($fs->ket_thuc)->timestamp
        );

        // 2. Trường hợp lô hoa ĐÃ QUÁ HẠN (expired) → Hệ thống chặn không cho tạo Flash Sale
        $spExpired = SanPham::create([
            'ten_san_pham'      => 'Hoa Hồng Hết Hạn ' . uniqid(),
            'gia_ban_hien_tai'  => 50000,
            'so_luong'          => 5,
            'trang_thai'        => 'DANG_BAN',
            'don_vi_tinh'       => 'Cành',
        ]);

        $alreadyExpired = now()->subDay()->endOfDay();
        $ctnExpired = ChiTietNhap::create([
            'ma_phieu_nhap'     => $pn->ma_phieu_nhap,
            'ma_san_pham'       => $spExpired->ma_san_pham,
            'so_luong'          => 5,
            'so_luong_con_lai'  => 5,
            'gia_nhap'          => 20000,
            'gia_ban'           => 50000,
            'ngay_het_han'      => $alreadyExpired,
        ]);

        [$hd2, $ghtb2, $hh2, $cts2] = $this->createReturnedOrder([
            ['san_pham' => $spExpired, 'chi_tiet_nhap' => $ctnExpired, 'so_luong' => 2]
        ]);

        // Cố tình chọn FLASH_SALE trên hoa đã quá hạn
        $responseExpired = $this->actingAs($this->admin)->post(
            route('admin.hoa-don.kiem-tra-hang.store', $hd2->ma_hoa_don),
            [
                'items' => [
                    $cts2[0]->ma_chi_tiet_hang_hoan => [
                        'huong_xu_ly' => 'FLASH_SALE',
                        'ti_le_giam'  => 50,
                    ],
                ],
            ]
        );

        // Bị chặn do validation error thông báo hoa đã quá hạn
        $responseExpired->assertSessionHasErrors();
        $this->assertNull(FlashSale::where('ma_chi_tiet_hang_hoan', $cts2[0]->ma_chi_tiet_hang_hoan)->first());
    }
}
