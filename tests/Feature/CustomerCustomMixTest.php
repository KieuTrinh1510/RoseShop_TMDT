<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BoHoaController as AdminBoHoaController;
use App\Models\BoHoa;
use App\Models\ChiTietNhap;
use App\Models\CustomMixImage;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\PhieuNhap;
use App\Models\PhuPhiBoHoa;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerCustomMixTest extends TestCase
{
    use DatabaseTransactions;

    protected NguoiDung $user;
    protected KhachHang $customer;
    protected PhieuNhap $pn;
    protected InventoryReservationService $reservationService;

    protected SanPham $spHoa1;
    protected SanPham $spHoa2;
    protected SanPham $spGiay;
    protected SanPham $spRuy;
    protected SanPham $spXop;
    protected SanPham $spPhuKien;

    protected ChiTietNhap $lotHoa1;
    protected ChiTietNhap $lotHoa2;
    protected ChiTietNhap $lotGiay;
    protected ChiTietNhap $lotRuy;
    protected ChiTietNhap $lotXop;
    protected ChiTietNhap $lotPhuKien;

    protected array $createdSpIds = [];

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

        Storage::fake('public');

        $this->reservationService = app(InventoryReservationService::class);

        // Ensure surcharge configs
        PhuPhiBoHoa::firstOrCreate(['loai' => 'SIZE', 'gia_tri' => 'S'], ['phu_phi' => 20000]);
        PhuPhiBoHoa::firstOrCreate(['loai' => 'SIZE', 'gia_tri' => 'M'], ['phu_phi' => 30000]);
        PhuPhiBoHoa::firstOrCreate(['loai' => 'SIZE', 'gia_tri' => 'L'], ['phu_phi' => 45000]);
        PhuPhiBoHoa::firstOrCreate(['loai' => 'KIEU_DANG', 'gia_tri' => 'ROUND'], ['phu_phi' => 15000]);

        $unique = microtime(true) . '_' . mt_rand(1000, 9999);

        // Create Customer User
        $this->user = NguoiDung::create([
            'ten_dang_nhap'     => 'cust_mix_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);

        $this->customer = KhachHang::create([
            'ma_khach_hang'  => $this->user->ma_nguoi_dung,
            'ten_khach_hang' => 'Khách Hàng Test ' . $unique,
            'so_dien_thoai'  => '0988776655',
            'email'          => 'custom_mix_' . $unique . '@example.com',
            'dia_chi'        => '123 Đường Test, TP.HCM',
            'diem_tich_luy'  => 0,
        ]);

        $nv = NhanVien::first();
        $ncc = \App\Models\NhaCungCap::first();
        $this->pn = PhieuNhap::create([
            'ma_nha_cung_cap' => $ncc ? $ncc->ma_nha_cung_cap : 1,
            'ma_nhan_vien'    => $nv ? $nv->ma_nhan_vien : 6,
            'tong_tien'       => 10000000,
            'ngay_nhap'       => now()->subDays(1),
            'trang_thai'      => 'CONFIRMED',
        ]);

        // Create standard test products with known quantities
        $this->spHoa1 = $this->createProduct('Hoa Hồng Đỏ ' . $unique, 'HOA_TUOI', SanPham::DVT_CANH, 100, 20000);
        $this->lotHoa1 = $this->createLot($this->spHoa1, 100, 10000, 20000);

        $this->spHoa2 = $this->createProduct('Hoa Baby Trắng ' . $unique, 'HOA_TUOI', SanPham::DVT_CANH, 100, 15000);
        $this->lotHoa2 = $this->createLot($this->spHoa2, 100, 8000, 15000);

        $this->spGiay = $this->createProduct('Giấy Hàn Quốc ' . $unique, 'GIAY_GOI', SanPham::DVT_TO, 100, 5000);
        $this->lotGiay = $this->createLot($this->spGiay, 100, 2500, 5000);

        $this->spRuy = $this->createProduct('Ruy Băng Hồng ' . $unique, 'RUY_BANG', SanPham::DVT_MET, 100, 3000);
        $this->lotRuy = $this->createLot($this->spRuy, 100, 1500, 3000);

        $this->spXop = $this->createProduct('Xốp Cắm Hoa ' . $unique, 'MIENG_XOP', SanPham::DVT_MIENG, 100, 7000);
        $this->lotXop = $this->createLot($this->spXop, 100, 3500, 7000);

        $this->spPhuKien = $this->createProduct('Đèn LED Đom Đóm ' . $unique, 'PHU_KIEN_TRANG_TRI', 'Cái', 50, 10000);
        $this->lotPhuKien = $this->createLot($this->spPhuKien, 50, 5000, 10000);
    }

    protected function tearDown(): void
    {
        try {
            DB::rollBack();
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (!empty($this->createdSpIds)) {
                $lots = ChiTietNhap::whereIn('ma_san_pham', $this->createdSpIds)->pluck('ma_chi_tiet_nhap');
                \App\Models\BaoCaoHangHong::whereIn('ma_chi_tiet_nhap', $lots)->delete();
                \App\Models\DanhGiaLoHang::whereIn('ma_chi_tiet_nhap', $lots)->delete();
                ChiTietNhap::whereIn('ma_san_pham', $this->createdSpIds)->delete();
                SanPham::whereIn('ma_san_pham', $this->createdSpIds)->delete();
            }

            if (isset($this->customer)) {
                $boIds = BoHoa::where('ma_khach_hang', $this->customer->ma_khach_hang)->pluck('ma_bo_hoa');
                CustomMixImage::whereIn('ma_bo_hoa', $boIds)->delete();
                \App\Models\ChiTietBoHoa::whereIn('ma_bo_hoa', $boIds)->delete();
                BoHoa::whereIn('ma_bo_hoa', $boIds)->delete();

                $hdIds = HoaDon::where('ma_khach_hang', $this->customer->ma_khach_hang)->pluck('ma_hoa_don');
                InventoryReservation::whereIn('ma_hoa_don', $hdIds)->delete();
                \App\Models\ChiTietHoaDon::whereIn('ma_hoa_don', $hdIds)->delete();
                HoaDon::whereIn('ma_hoa_don', $hdIds)->delete();

                $this->customer->delete();
            }

            if (isset($this->user)) {
                $this->user->delete();
            }

            if (isset($this->pn)) {
                $hasLots = ChiTietNhap::where('ma_phieu_nhap', $this->pn->ma_phieu_nhap)->exists();
                if (!$hasLots) {
                    $this->pn->delete();
                }
            }
        } catch (\Throwable $e) {
            // ignore cleanup errors
        }

        parent::tearDown();
    }

    protected function createProduct(string $name, string $category, string $unit, int $qty, float $price): SanPham
    {
        $sp = SanPham::create([
            'ten_san_pham'     => $name,
            'loai_san_pham'    => $category,
            'don_vi_tinh'      => $unit,
            'so_luong'         => $qty,
            'gia_ban_hien_tai' => $price,
            'trang_thai'       => 'DANG_BAN',
        ]);
        $this->createdSpIds[] = $sp->ma_san_pham;
        return $sp;
    }

    protected function createLot(SanPham $sp, int $qty, float $cost, float $price, ?string $expiry = null): ChiTietNhap
    {
        return ChiTietNhap::create([
            'ma_phieu_nhap'    => $this->pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => $qty,
            'so_luong_con_lai' => $qty,
            'gia_nhap'         => $cost,
            'gia_ban'          => $price,
            'ngay_het_han'     => $expiry,
        ]);
    }

    /**
     * Helper to build a valid Size M payload
     */
    protected function buildValidSizeMPayload(array $overrides = []): array
    {
        $base = [
            'ten_bo_hoa'  => 'Bó Hoa Test Size M',
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'M',
            'concept'     => 'ROMANTIC',
            'so_luong'    => 1,
            'thanh_phan'  => [
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 3, 'vai_tro' => 'HOA_TRANG_TRI'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ],
        ];

        return array_merge($base, $overrides);
    }

    /**
     * Rule 69: Main and Decorative Flowers must be strictly mutually exclusive
     */
    public function test_main_and_decorative_flowers_are_mutually_exclusive()
    {
        $this->actingAs($this->user);

        // Attempt to submit Hoa1 as both HOA_CHINH and HOA_TRANG_TRI
        $payload = $this->buildValidSizeMPayload([
            'thanh_phan' => [
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 3, 'vai_tro' => 'HOA_TRANG_TRI'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ]
        ]);

        $response = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $payload);

        $response->assertSessionHasErrors();
    }

    /**
     * Rule 70: Cannot select more than available stock for single bouquet
     */
    public function test_cannot_select_more_than_available_stock_single_bouquet()
    {
        $this->actingAs($this->user);

        // Set Hoa1 available = 8
        $this->spHoa1->update(['so_luong' => 8]);
        $this->lotHoa1->update(['so_luong_con_lai' => 8]);

        // Case A: Size S (7 flowers total): 7 Main flowers <= 8 Available -> Passes
        $payloadValid = [
            'ten_bo_hoa'  => 'Bó Size S Hợp Lệ',
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'S',
            'concept'     => 'ROMANTIC',
            'so_luong'    => 1,
            'thanh_phan'  => [
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 4, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ]
        ];
        $resValid = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $payloadValid);
        $resValid->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // Case B: Size M (10 flowers total): 9 Main flowers > 8 Available -> Fails
        $payloadInvalid = $this->buildValidSizeMPayload([
            'thanh_phan' => [
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 9, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 1, 'vai_tro' => 'HOA_TRANG_TRI'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ]
        ]);
        $resInvalid = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $payloadInvalid);
        $resInvalid->assertSessionHasErrors();
    }

    /**
     * Rule 71: Check stock multiplied by number of bouquets (Available = 12, 5/bó -> 2 bó cần 10 PASS, 3 bó cần 15 FAIL)
     */
    public function test_stock_check_multiplies_by_number_of_bouquets()
    {
        $this->actingAs($this->user);

        // Set Hoa1 available = 12
        $this->spHoa1->update(['so_luong' => 12]);
        $this->lotHoa1->update(['so_luong_con_lai' => 12]);

        // Hoa1 = 5 cành / bó, Hoa2 = 5 cành / bó (Size M: 10 cành)
        $thanhPhan = [
            ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 5, 'vai_tro' => 'HOA_CHINH'],
            ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 5, 'vai_tro' => 'HOA_TRANG_TRI'],
            ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
            ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
            ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
        ];

        // 2 bouquets: 5 * 2 = 10 <= 12 -> PASS
        $res2 = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload([
            'so_luong' => 2,
            'thanh_phan' => $thanhPhan,
        ]));
        $res2->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // 3 bouquets: 5 * 3 = 15 > 12 -> FAIL
        $res3 = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload([
            'so_luong' => 3,
            'thanh_phan' => $thanhPhan,
        ]));
        $res3->assertSessionHasErrors();
    }

    /**
     * Rule 72: Size flower quota validation (Size M = 10: 7+3 PASS, 7+2 FAIL, 7+4 FAIL)
     */
    public function test_size_flower_quota_validation()
    {
        $this->actingAs($this->user);

        // 7 + 3 = 10 (PASS)
        $resPass = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload([
            'thanh_phan' => [
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 3, 'vai_tro' => 'HOA_TRANG_TRI'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ]
        ]));
        $resPass->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // 7 + 2 = 9 (FAIL)
        $resUnder = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload([
            'thanh_phan' => [
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 2, 'vai_tro' => 'HOA_TRANG_TRI'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ]
        ]));
        $resUnder->assertSessionHasErrors();

        // 7 + 4 = 11 (FAIL)
        $resOver = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload([
            'thanh_phan' => [
                ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 4, 'vai_tro' => 'HOA_TRANG_TRI'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ]
        ]));
        $resOver->assertSessionHasErrors();
    }

    /**
     * Rule 73: Fixed material quantity multiplied by number of bouquets (Size M Giấy = 6/bó, 3 bó = 18. Available 18 PASS, 17 FAIL)
     */
    public function test_fixed_materials_stock_multiplied_by_bouquets()
    {
        $this->actingAs($this->user);

        // Case A: Giấy available = 18 -> 3 bouquets need 18 -> PASS
        $this->spGiay->update(['so_luong' => 18]);
        $this->lotGiay->update(['so_luong_con_lai' => 18]);

        $res18 = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload([
            'so_luong' => 3,
        ]));
        $res18->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // Case B: Giấy available = 17 -> 3 bouquets need 18 -> FAIL
        $this->spGiay->update(['so_luong' => 17]);
        $this->lotGiay->update(['so_luong_con_lai' => 17]);

        $res17 = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload([
            'so_luong' => 3,
        ]));
        $res17->assertSessionHasErrors();
    }

    /**
     * Rule 74: Accessory quota per size (Size L max = 3: 3 PASS, 4 FAIL)
     */
    public function test_accessory_quota_per_size()
    {
        $this->actingAs($this->user);

        // Size L: 15 flowers, 8 paper, 1 ribbon, 1 foam, max accessory = 3
        $baseThanhPhan = [
            ['ma_san_pham' => $this->spHoa1->ma_san_pham, 'so_luong' => 10, 'vai_tro' => 'HOA_CHINH'],
            ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 5,  'vai_tro' => 'HOA_TRANG_TRI'],
            ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 8,  'vai_tro' => 'GIAY_GOI'],
            ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1,  'vai_tro' => 'RUY_BANG'],
            ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1,  'vai_tro' => 'XOP'],
        ];

        // 3 accessories -> PASS
        $thanhPhan3Acc = array_merge($baseThanhPhan, [
            ['ma_san_pham' => $this->spPhuKien->ma_san_pham, 'so_luong' => 3, 'vai_tro' => 'PHU_KIEN_TRANG_TRI'],
        ]);
        $res3 = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), [
            'ten_bo_hoa'  => 'Bó Size L 3 Phụ Kiện',
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'L',
            'concept'     => 'ROMANTIC',
            'so_luong'    => 1,
            'thanh_phan'  => $thanhPhan3Acc,
        ]);
        $res3->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // 4 accessories -> FAIL
        $thanhPhan4Acc = array_merge($baseThanhPhan, [
            ['ma_san_pham' => $this->spPhuKien->ma_san_pham, 'so_luong' => 4, 'vai_tro' => 'PHU_KIEN_TRANG_TRI'],
        ]);
        $res4 = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), [
            'ten_bo_hoa'  => 'Bó Size L 4 Phụ Kiện',
            'kieu_bo_hoa' => 'ROUND',
            'size'        => 'L',
            'concept'     => 'ROMANTIC',
            'so_luong'    => 1,
            'thanh_phan'  => $thanhPhan4Acc,
        ]);
        $res4->assertSessionHasErrors();
    }

    /**
     * Rule 75: Reference image upload validation
     */
    public function test_reference_image_validation()
    {
        $this->actingAs($this->user);

        // Case 1: No image -> PASS
        $resNoImg = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload());
        $resNoImg->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // Case 2: 1 JPG <= 5MB -> PASS
        $imgJpg = UploadedFile::fake()->image('mau_1.jpg', 600, 600)->size(2000);
        $resJpg = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), array_merge($this->buildValidSizeMPayload(), [
            'anh_mau' => [$imgJpg],
        ]));
        $resJpg->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // Case 3: 3 WEBP <= 5MB -> PASS
        $webp1 = UploadedFile::fake()->image('mau_1.webp')->size(1000);
        $webp2 = UploadedFile::fake()->image('mau_2.webp')->size(1000);
        $webp3 = UploadedFile::fake()->image('mau_3.webp')->size(1000);
        $res3Webp = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), array_merge($this->buildValidSizeMPayload(), [
            'anh_mau' => [$webp1, $webp2, $webp3],
        ]));
        $res3Webp->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // Case 4: 4 images -> FAIL (max 3)
        $webp4 = UploadedFile::fake()->image('mau_4.webp')->size(1000);
        $res4Img = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), array_merge($this->buildValidSizeMPayload(), [
            'anh_mau' => [$webp1, $webp2, $webp3, $webp4],
        ]));
        $res4Img->assertSessionHasErrors(['anh_mau']);

        // Case 5: Disguised file or invalid format -> FAIL
        $badFile = UploadedFile::fake()->create('malicious.php', 500, 'application/x-php');
        $resBad = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), array_merge($this->buildValidSizeMPayload(), [
            'anh_mau' => [$badFile],
        ]));
        $resBad->assertSessionHasErrors(['anh_mau.0']);

        // Case 6: File > 5MB -> FAIL
        $heavyFile = UploadedFile::fake()->image('heavy.jpg')->size(6000); // 6MB
        $resHeavy = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), array_merge($this->buildValidSizeMPayload(), [
            'anh_mau' => [$heavyFile],
        ]));
        $resHeavy->assertSessionHasErrors(['anh_mau.0']);
    }

    /**
     * Rule 76: Draft and Preview do NOT reserve stock
     */
    public function test_draft_and_preview_do_not_reserve_stock()
    {
        $this->actingAs($this->user);

        $initialOnHandHoa1 = $this->spHoa1->fresh()->so_luong;
        $initialReservedHoa1 = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);
        $initialAvailableHoa1 = InventoryReservationService::getAvailableForIngredient($this->spHoa1->ma_san_pham);

        // Submit to create draft / preview
        $res = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload());
        $res->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // View preview
        $resPreview = $this->get(route('customer.bo-hoa-tu-tao.chi-tiet'));
        $resPreview->assertOk();

        // Check stock state
        $afterOnHandHoa1 = $this->spHoa1->fresh()->so_luong;
        $afterReservedHoa1 = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);
        $afterAvailableHoa1 = InventoryReservationService::getAvailableForIngredient($this->spHoa1->ma_san_pham);

        $this->assertEquals($initialOnHandHoa1, $afterOnHandHoa1, 'On-hand must not change during draft/preview');
        $this->assertEquals($initialReservedHoa1, $afterReservedHoa1, 'Reserved must not change during draft/preview');
        $this->assertEquals($initialAvailableHoa1, $afterAvailableHoa1, 'Available must not change during draft/preview');
    }

    /**
     * Rule 77: Placing order reserves stock FIFO, Bouquet becomes ACTIVE, Order PENDING
     */
    public function test_order_creation_reserves_stock_fifo()
    {
        $this->actingAs($this->user);

        // 1. Create draft with sample image
        $sampleFile = UploadedFile::fake()->image('mau_tham_khao.jpg', 600, 600)->size(1500);
        $resDraft = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), array_merge($this->buildValidSizeMPayload(), [
            'anh_mau' => [$sampleFile],
        ]));
        $resDraft->assertRedirect(route('customer.bo-hoa-tu-tao.chi-tiet'));

        // 2. Click "Mua ngay" to proceed to checkout
        $resBuy = $this->post(route('customer.bo-hoa.tu-tao.mua'), [
            'loi_nhan' => 'Chúc mừng ngày kỷ niệm!',
        ]);
        $resBuy->assertRedirect(route('customer.thanh-toan'));

        $initialOnHand = $this->spHoa1->fresh()->so_luong;
        $initialReserved = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);
        $initialAvailable = InventoryReservationService::getAvailableForIngredient($this->spHoa1->ma_san_pham);

        // 3. Place order via direct checkout (POST customer.thanh-toan.store)
        $checkoutData = [
            'ten_nguoi_nhan'          => 'Nguyễn Văn Nhận',
            'so_dien_thoai'           => '0901234567',
            'quan_huyen'              => 'Ba Đình',
            'xa_phuong'               => 'Cống Vị',
            'dia_chi_chi_tiet'        => '123 Đội Cấn',
            'phuong_thuc_thanh_toan'  => 'COD',
        ];

        $resOrder = $this->post(route('customer.thanh-toan.store'), $checkoutData);
        $resOrder->assertRedirect();

        // Verify Order created with status PENDING
        $order = HoaDon::where('ma_khach_hang', $this->customer->ma_khach_hang)->latest('ma_hoa_don')->first();
        $this->assertNotNull($order);
        $this->assertEquals('PENDING', $order->trang_thai);

        // Verify Bouquet is ACTIVE and associated with Order
        $bouquet = BoHoa::where('ma_hoa_don', $order->ma_hoa_don)->first();
        $this->assertNotNull($bouquet);
        $this->assertEquals('ACTIVE', $bouquet->trang_thai);

        // Verify Sample Image persisted with Bouquet
        $this->assertGreaterThanOrEqual(1, $bouquet->customMixImages()->count());

        // Verify Stock: On Hand UNCHANGED, Reserved INCREASED by 7, Available DECREASED by 7
        $newOnHand = $this->spHoa1->fresh()->so_luong;
        $newReserved = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);
        $newAvailable = InventoryReservationService::getAvailableForIngredient($this->spHoa1->ma_san_pham);

        $this->assertEquals($initialOnHand, $newOnHand, 'On-hand must NOT decrease upon order placement (reservation phase)');
        $this->assertEquals($initialReserved + 7, $newReserved, 'Reserved must increase by bouquet ingredient quantity');
        $this->assertEquals($initialAvailable - 7, $newAvailable, 'Available must decrease by bouquet ingredient quantity');

        // Verify Reservations created with status RESERVED
        $reservations = InventoryReservation::where('ma_hoa_don', $order->ma_hoa_don)->get();
        $this->assertNotEmpty($reservations);
        foreach ($reservations as $r) {
            $this->assertEquals('RESERVED', $r->trang_thai);
        }
    }

    /**
     * Rule 78: Order confirmation consumes reserved stock
     */
    public function test_order_confirmation_consumes_reserved_stock()
    {
        $this->actingAs($this->user);

        // Create draft & place order
        $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload());
        $this->post(route('customer.bo-hoa.tu-tao.mua'));
        $this->post(route('customer.thanh-toan.store'), [
            'ten_nguoi_nhan'          => 'Nguyễn Văn Nhận',
            'so_dien_thoai'           => '0901234567',
            'quan_huyen'              => 'Ba Đình',
            'xa_phuong'               => 'Cống Vị',
            'dia_chi_chi_tiet'        => '123 Đội Cấn',
            'phuong_thuc_thanh_toan'  => 'COD',
        ]);

        $order = HoaDon::where('ma_khach_hang', $this->customer->ma_khach_hang)->latest('ma_hoa_don')->first();

        $preOnHand = $this->spHoa1->fresh()->so_luong;
        $preLotRemaining = $this->lotHoa1->fresh()->so_luong_con_lai;
        $preReserved = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);

        // Admin/Staff confirms the order -> consumes stock
        InventoryReservationService::confirmOrder($order);

        $postOnHand = $this->spHoa1->fresh()->so_luong;
        $postLotRemaining = $this->lotHoa1->fresh()->so_luong_con_lai;
        $postReserved = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);

        $this->assertEquals($preOnHand - 7, $postOnHand, 'On-hand must decrease by 7 upon consumption');
        $this->assertEquals($preLotRemaining - 7, $postLotRemaining, 'Lot remaining must decrease by 7 upon consumption');
        $this->assertEquals($preReserved - 7, $postReserved, 'Reserved must decrease by 7 upon consumption');

        // Check reservations status
        $reservations = InventoryReservation::where('ma_hoa_don', $order->ma_hoa_don)->get();
        foreach ($reservations as $r) {
            $this->assertEquals('CONSUMED', $r->trang_thai);
        }
    }

    /**
     * Rule 79: Order cancellation releases stock without incrementing on-hand
     */
    public function test_order_cancellation_releases_stock()
    {
        $this->actingAs($this->user);

        // Place order
        $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $this->buildValidSizeMPayload());
        $this->post(route('customer.bo-hoa.tu-tao.mua'));
        $this->post(route('customer.thanh-toan.store'), [
            'ten_nguoi_nhan'          => 'Nguyễn Văn Nhận',
            'so_dien_thoai'           => '0901234567',
            'quan_huyen'              => 'Ba Đình',
            'xa_phuong'               => 'Cống Vị',
            'dia_chi_chi_tiet'        => '123 Đội Cấn',
            'phuong_thuc_thanh_toan'  => 'COD',
        ]);

        $order = HoaDon::where('ma_khach_hang', $this->customer->ma_khach_hang)->latest('ma_hoa_don')->first();

        $preOnHand = $this->spHoa1->fresh()->so_luong;
        $preReserved = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);

        // Cancel order -> release reservation
        InventoryReservationService::releaseOrder($order);

        $postOnHand = $this->spHoa1->fresh()->so_luong;
        $postReserved = InventoryReservationService::getReservedForProduct($this->spHoa1->ma_san_pham);

        $this->assertEquals($preOnHand, $postOnHand, 'On-hand must not increase when releasing pre-consumption reservations');
        $this->assertEquals($preReserved - 7, $postReserved, 'Reserved must decrease by 7 upon release');

        // Check reservations status
        $reservations = InventoryReservation::where('ma_hoa_don', $order->ma_hoa_don)->get();
        foreach ($reservations as $r) {
            $this->assertEquals('RELEASED', $r->trang_thai);
        }
    }

    /**
     * Rule 80: Expired lots are excluded from available and reservation
     */
    public function test_expired_lots_are_excluded_from_available_and_reservation()
    {
        $unique = microtime(true) . '_' . mt_rand(1000, 9999);

        // Create product with only an expired lot
        $spExpired = $this->createProduct('Hoa Quá Hạn ' . $unique, 'HOA_TUOI', SanPham::DVT_CANH, 50, 15000);
        $this->createLot($spExpired, 50, 8000, 15000, now()->subDays(5)->toDateString());

        // Available must be 0
        $available = $this->reservationService->getAvailableForIngredient($spExpired->ma_san_pham);
        $this->assertEquals(0, $available, 'Product with only expired lot must have Available = 0');

        // Attempting to select this product in custom mix must fail
        $this->actingAs($this->user);
        $payload = $this->buildValidSizeMPayload([
            'thanh_phan' => [
                ['ma_san_pham' => $spExpired->ma_san_pham,    'so_luong' => 7, 'vai_tro' => 'HOA_CHINH'],
                ['ma_san_pham' => $this->spHoa2->ma_san_pham, 'so_luong' => 3, 'vai_tro' => 'HOA_TRANG_TRI'],
                ['ma_san_pham' => $this->spGiay->ma_san_pham, 'so_luong' => 6, 'vai_tro' => 'GIAY_GOI'],
                ['ma_san_pham' => $this->spRuy->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'RUY_BANG'],
                ['ma_san_pham' => $this->spXop->ma_san_pham,  'so_luong' => 1, 'vai_tro' => 'XOP'],
            ]
        ]);

        $res = $this->post(route('customer.bo-hoa.tu-tao.xac-nhan'), $payload);
        $res->assertSessionHasErrors();
    }
}
