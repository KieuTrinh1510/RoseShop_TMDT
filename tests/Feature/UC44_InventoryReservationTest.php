<?php

namespace Tests\Feature;

use App\Http\Controllers\Customer\BoHoaController;
use App\Models\BoHoa;
use App\Models\ChiTietHoaDon;
use App\Models\ChiTietMauBoHoa;
use App\Models\ChiTietNhap;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\KhachHang;
use App\Models\MauBoHoa;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\PhuPhiBoHoa;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UC44_InventoryReservationTest extends TestCase
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

        // Seed basic phu phi for size S and ROUND
        PhuPhiBoHoa::firstOrCreate(['loai' => 'SIZE', 'gia_tri' => 'S'], ['phu_phi' => 20000]);
        PhuPhiBoHoa::firstOrCreate(['loai' => 'KIEU_DANG', 'gia_tri' => 'ROUND'], ['phu_phi' => 15000]);

        // VNPay dummy config for testing
        config([
            'vnpay.tmn_code'    => 'TEST_TMN',
            'vnpay.hash_secret' => 'TEST_SECRET_KEY_12345678901234567890',
            'vnpay.url'         => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'vnpay.return_url'  => 'http://localhost/vnpay/return',
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

    protected function createAdminUser(): NguoiDung
    {
        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        return NguoiDung::create([
            'ten_dang_nhap'     => 'admin_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'ADMIN',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);
    }

    protected function getOperationalEmployeeId(): int
    {
        $nv = NhanVien::where('chuc_vu', 'VAN_HANH')->first();
        if ($nv) {
            return $nv->ma_nhan_vien;
        }

        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'nv_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'NHAN_VIEN',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);

        $employee = NhanVien::create([
            'ma_nhan_vien'  => $user->ma_nguoi_dung,
            'ten_nhan_vien' => 'Nhân viên ' . $unique,
            'chuc_vu'       => 'VAN_HANH',
            'email'         => 'nv_' . $unique . '@example.com',
            'so_dien_thoai' => '09' . rand(10000000, 99999999),
            'luong'         => 10000000,
        ]);

        return $employee->ma_nhan_vien;
    }

    protected function createShipperUser(): NhanVien
    {
        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'shipper_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'SHIPPER',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);
        return NhanVien::create([
            'ma_nhan_vien'  => $user->ma_nguoi_dung,
            'ten_nhan_vien' => 'Shipper Test',
            'chuc_vu'       => 'SHIPPER',
            'email'         => 'shipper_' . $unique . '@example.com',
            'so_dien_thoai' => '09' . rand(10000000, 99999999),
            'luong'         => 8000000,
        ]);
    }

    protected function createCustomerUser(): array
    {
        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        $user = NguoiDung::create([
            'ten_dang_nhap'     => 'kh_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);
        $khach = KhachHang::create([
            'ma_khach_hang' => $user->ma_nguoi_dung,
            'ho_ten'        => 'Khách Test ' . $unique,
            'email'         => 'kh_' . $unique . '@example.com',
            'so_dien_thoai' => '0912345678',
            'dia_chi'       => '123 Cầu Giấy, Hà Nội',
            'diem_tich_luy' => 0,
        ]);
        return [$user, $khach];
    }

    protected function createProductWithLots(int $lotQty1 = 40, int $lotQty2 = 60, string $status = 'CONFIRMED', string $loaiSp = 'HOA_TUOI'): array
    {
        $unique = microtime(true) . '_' . mt_rand(1000, 9999);
        $sp = SanPham::create([
            'ten_san_pham'     => 'Hoa Hồng Test ' . $unique,
            'loai_san_pham'    => $loaiSp,
            'don_vi_tinh'      => 'CANH',
            'gia_ban_hien_tai' => 20000,
            'so_luong'         => $status === 'CONFIRMED' ? ($lotQty1 + $lotQty2) : 0,
            'trang_thai'       => 'DANG_BAN',
        ]);

        $ncc = NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Test ' . rand(1000, 9999),
            'so_dien_thoai'    => '0988776655',
            'email'            => 'ncc_' . rand(1000, 9999) . '@test.com',
            'dia_chi'          => 'Đà Lạt',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $employeeId = $this->getOperationalEmployeeId();

        $pn = PhieuNhap::create([
            'ngay_nhap'         => now()->subDays(2),
            'ma_nhan_vien'      => $employeeId,
            'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
            'email_ncc'         => $ncc->email,
            'dia_chi_ncc'       => $ncc->dia_chi,
            'tong_tien'         => 1000000,
            'trang_thai'        => $status,
        ]);

        $lot1 = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => max(1, $lotQty1),
            'gia_nhap'         => 10000,
            'gia_ban'          => 20000,
            'so_luong_con_lai' => $lotQty1,
        ]);

        $lot2 = null;
        if ($lotQty2 > 0) {
            $pn2 = PhieuNhap::create([
                'ngay_nhap'         => now()->subDay(),
                'ma_nhan_vien'      => $employeeId,
                'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
                'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
                'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
                'email_ncc'         => $ncc->email,
                'dia_chi_ncc'       => $ncc->dia_chi,
                'tong_tien'         => 1000000,
                'trang_thai'        => $status,
            ]);

            $lot2 = ChiTietNhap::create([
                'ma_phieu_nhap'    => $pn2->ma_phieu_nhap,
                'ma_san_pham'      => $sp->ma_san_pham,
                'so_luong'         => $lotQty2,
                'gia_nhap'         => 11000,
                'gia_ban'          => 20000,
                'so_luong_con_lai' => $lotQty2,
            ]);
        }

        return [$sp, $lot1, $lot2, $pn];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 1: Add to cart does NOT reserve inventory
    // ─────────────────────────────────────────────────────────────────────────
    public function test_add_to_cart_does_not_reserve_inventory(): void
    {
        [$user, $khach] = $this->createCustomerUser();
        [$sp, $lot1, $lot2] = $this->createProductWithLots(40, 60);

        $initialOnHand = $sp->so_luong;
        $initialAvailable = $sp->ton_kha_dung;

        // Khách thêm sản phẩm vào giỏ hàng qua session
        $this->actingAs($user)
            ->post(route('customer.gio-hang.add'), [
                'items' => [
                    [
                        'ma_chi_tiet_nhap' => $lot1->ma_chi_tiet_nhap,
                        'so_luong'         => 5,
                    ],
                ],
            ]);

        // Kiểm tra không có reservation nào được tạo
        $this->assertDatabaseMissing('inventory_reservations', [
            'ma_san_pham' => $sp->ma_san_pham,
        ]);

        $sp->refresh();
        $this->assertEquals($initialOnHand, $sp->ton_thuc_te, 'Tồn thực tế không đổi khi thêm vào giỏ');
        $this->assertEquals(0, $sp->ton_dang_giu, 'Tồn đang giữ vẫn là 0');
        $this->assertEquals($initialAvailable, $sp->ton_kha_dung, 'Tồn khả dụng không đổi khi thêm vào giỏ');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 2: Single item checkout with sufficient stock creates RESERVED
    // ─────────────────────────────────────────────────────────────────────────
    public function test_checkout_with_sufficient_stock_creates_reservation_and_leaves_on_hand_unchanged(): void
    {
        [$user, $khach] = $this->createCustomerUser();
        [$sp, $lot1, $lot2] = $this->createProductWithLots(40, 60);

        $initialOnHand = $sp->so_luong; // 100

        // Checkout 25 sản phẩm
        $response = $this->actingAs($user)
            ->withSession([
                'checkout_items' => [
                    $sp->ma_san_pham => [
                        'ma_san_pham'  => $sp->ma_san_pham,
                        'ten_san_pham' => $sp->ten_san_pham,
                        'gia_ban'      => $sp->gia_ban_hien_tai,
                        'so_luong'     => 25,
                    ],
                ],
            ])
            ->post(route('customer.thanh-toan.store'), [
                'ten_nguoi_nhan'         => 'Nguyễn Văn Test',
                'so_dien_thoai'          => '0912345678',
                'quan_huyen'             => 'Cầu Giấy',
                'xa_phuong'              => 'Dịch Vọng',
                'dia_chi_chi_tiet'       => 'Số 10 Dịch Vọng Hậu',
                'phuong_thuc_thanh_toan' => 'COD',
            ]);

        $response->assertRedirect(route('customer.dashboard'));

        $sp->refresh();
        $lot1->refresh();

        // 1. Tồn thực tế (on_hand) KHÔNG bị trừ
        $this->assertEquals($initialOnHand, $sp->ton_thuc_te, 'Tồn thực tế sản phẩm không được trừ khi checkout');
        $this->assertEquals(40, $lot1->so_luong_con_lai, 'Số lượng còn lại của lô không được trừ khi checkout');

        // 2. Tồn đang giữ tăng đúng 25
        $this->assertEquals(25, $sp->ton_dang_giu, 'Tồn đang giữ của sản phẩm phải là 25');
        $this->assertEquals(25, $lot1->ton_dang_giu, 'Tồn đang giữ của lô 1 phải là 25');

        // 3. Tồn khả dụng = 100 - 25 = 75
        $this->assertEquals(75, $sp->ton_kha_dung, 'Tồn khả dụng sản phẩm = on_hand - reserved');
        $this->assertEquals(15, $lot1->ton_kha_dung, 'Tồn khả dụng lô 1 = 40 - 25 = 15');

        // 4. Bảng inventory_reservations có bản ghi RESERVED gắn đúng mã đơn, mã sản phẩm và lô
        $this->assertDatabaseHas('inventory_reservations', [
            'ma_san_pham'      => $sp->ma_san_pham,
            'ma_chi_tiet_nhap' => $lot1->ma_chi_tiet_nhap,
            'so_luong'         => 25,
            'trang_thai'       => InventoryReservation::STATUS_RESERVED,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 3: No double counting between Product and Lot reservations
    // ─────────────────────────────────────────────────────────────────────────
    public function test_no_double_counting_between_product_and_lot(): void
    {
        [$sp, $lot1, $lot2] = $this->createProductWithLots(40, 60);

        $hd = HoaDon::create([
            'ma_khach_hang'          => 1,
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'tong_tien'              => 500000,
            'ngay_dat'               => now(),
        ]);

        DB::transaction(function () use ($hd, $sp) {
            InventoryReservationService::reserveSingleItems($hd, [
                [
                    'ma_san_pham' => $sp->ma_san_pham,
                    'so_luong'    => 20,
                    'gia_ban'     => 20000,
                ],
            ]);
        });

        // Chỉ có đúng 1 dòng reservation được tạo (gắn cả sp và lot1)
        $count = InventoryReservation::where('ma_hoa_don', $hd->ma_hoa_don)->count();
        $this->assertEquals(1, $count, 'Chỉ được tạo 1 dòng reservation kết hợp sản phẩm và lô, không tạo 2 dòng riêng');

        $res = InventoryReservation::where('ma_hoa_don', $hd->ma_hoa_don)->first();
        $this->assertEquals($sp->ma_san_pham, $res->ma_san_pham);
        $this->assertEquals($lot1->ma_chi_tiet_nhap, $res->ma_chi_tiet_nhap);
        $this->assertEquals(20, $res->so_luong);

        // Product reserved = 20, Lot 1 reserved = 20
        $this->assertEquals(20, $sp->fresh()->ton_dang_giu);
        $this->assertEquals(20, $lot1->fresh()->ton_dang_giu);
        $this->assertEquals(0, $lot2->fresh()->ton_dang_giu);
        $this->assertEquals(80, $sp->fresh()->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 4: Checkout with insufficient stock fails and rolls back
    // ─────────────────────────────────────────────────────────────────────────
    public function test_checkout_with_insufficient_stock_fails_and_does_not_reserve(): void
    {
        [$user, $khach] = $this->createCustomerUser();
        [$sp, $lot1] = $this->createProductWithLots(10, 0); // Tổng tồn = 10

        // Thử đặt 15 cái (vượt quá 10)
        $response = $this->actingAs($user)
            ->withSession([
                'checkout_items' => [
                    $sp->ma_san_pham => [
                        'ma_san_pham'  => $sp->ma_san_pham,
                        'ten_san_pham' => $sp->ten_san_pham,
                        'gia_ban'      => $sp->gia_ban_hien_tai,
                        'so_luong'     => 15,
                    ],
                ],
            ])
            ->post(route('customer.thanh-toan.store'), [
                'ten_nguoi_nhan'         => 'Nguyễn Văn Test',
                'so_dien_thoai'          => '0912345678',
                'quan_huyen'             => 'Cầu Giấy',
                'xa_phuong'              => 'Dịch Vọng',
                'dia_chi_chi_tiet'       => 'Số 10 Dịch Vọng Hậu',
                'phuong_thuc_thanh_toan' => 'COD',
            ]);

        // Bị redirect kèm lỗi
        $response->assertRedirect(route('customer.thanh-toan'));
        $response->assertSessionHas('error');

        // Không có reservation nào được tạo
        $this->assertDatabaseMissing('inventory_reservations', [
            'ma_san_pham' => $sp->ma_san_pham,
        ]);

        $sp->refresh();
        $this->assertEquals(10, $sp->ton_thuc_te);
        $this->assertEquals(0, $sp->ton_dang_giu);
        $this->assertEquals(10, $sp->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 5: Concurrency / Two orders cannot exceed available stock
    // ─────────────────────────────────────────────────────────────────────────
    public function test_two_orders_cannot_exceed_available_stock(): void
    {
        [$sp, $lot1] = $this->createProductWithLots(20, 0); // Tổng tồn = 20

        $hd1 = HoaDon::create([
            'ma_khach_hang' => 1, 'trang_thai' => 'PENDING', 'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD', 'tong_tien' => 300000, 'ngay_dat' => now(),
        ]);
        $hd2 = HoaDon::create([
            'ma_khach_hang' => 1, 'trang_thai' => 'PENDING', 'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD', 'tong_tien' => 300000, 'ngay_dat' => now(),
        ]);

        // Đơn 1 giữ 15 (thành công)
        DB::transaction(function () use ($hd1, $sp) {
            InventoryReservationService::reserveSingleItems($hd1, [
                ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 15, 'gia_ban' => 20000],
            ]);
        });

        $this->assertEquals(15, $sp->fresh()->ton_dang_giu);
        $this->assertEquals(5, $sp->fresh()->ton_kha_dung);

        // Đơn 2 cố giữ 10 (chỉ còn 5 khả dụng -> phải ném ngoại lệ)
        $this->expectException(ValidationException::class);
        DB::transaction(function () use ($hd2, $sp) {
            InventoryReservationService::reserveSingleItems($hd2, [
                ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 10, 'gia_ban' => 20000],
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 6: Confirm order: RESERVED -> CONSUMED, decrements on_hand
    // ─────────────────────────────────────────────────────────────────────────
    public function test_confirm_order_consumes_reservation_and_decrements_on_hand(): void
    {
        $admin = $this->createAdminUser();
        $shipper = $this->createShipperUser();
        [$user, $khach] = $this->createCustomerUser();
        [$sp, $lot1, $lot2] = $this->createProductWithLots(40, 60);

        // Tạo đơn hàng và giữ 30 cái
        $hd = HoaDon::create([
            'ma_khach_hang'          => $khach->ma_khach_hang,
            'dia_chi_giao'           => '123 Cầu Giấy, Hà Nội',
            'so_dien_thoai'          => '0912345678',
            'phuong_thuc_thanh_toan' => 'COD',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'tong_tien'              => 600000,
            'ngay_dat'               => now(),
        ]);

        ChiTietHoaDon::create([
            'ma_hoa_don'        => $hd->ma_hoa_don,
            'ma_san_pham'       => $sp->ma_san_pham,
            'ma_chi_tiet_nhap'  => $lot1->ma_chi_tiet_nhap,
            'so_luong'          => 30,
            'gia_nhap_snapshot' => 10000,
            'gia_ban_snapshot'  => 20000,
        ]);

        DB::transaction(function () use ($hd, $sp) {
            InventoryReservationService::reserveSingleItems($hd, [
                ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 30, 'gia_ban' => 20000],
            ]);
        });

        // Trước confirm
        $this->assertEquals(100, $sp->fresh()->ton_thuc_te);
        $this->assertEquals(30, $sp->fresh()->ton_dang_giu);
        $this->assertEquals(70, $sp->fresh()->ton_kha_dung);

        // Admin confirm đơn
        $response = $this->actingAs($admin)
            ->post(route('don-hang.confirm', $hd->ma_hoa_don), [
                'ma_nhan_vien_giao' => $shipper->ma_nhan_vien,
            ]);

        $response->assertJson(['success' => true]);

        // Sau confirm:
        $sp->refresh();
        $lot1->refresh();

        // 1. Reservation chuyển sang CONSUMED
        $this->assertDatabaseHas('inventory_reservations', [
            'ma_hoa_don' => $hd->ma_hoa_don,
            'trang_thai' => InventoryReservation::STATUS_CONSUMED,
        ]);
        $this->assertDatabaseMissing('inventory_reservations', [
            'ma_hoa_don' => $hd->ma_hoa_don,
            'trang_thai' => InventoryReservation::STATUS_RESERVED,
        ]);

        // 2. On-hand giảm 30 (100 -> 70), Lô 1 giảm 30 (40 -> 10)
        $this->assertEquals(70, $sp->ton_thuc_te);
        $this->assertEquals(10, $lot1->so_luong_con_lai);

        // 3. Đang giữ về 0, Khả dụng là 70
        $this->assertEquals(0, $sp->ton_dang_giu);
        $this->assertEquals(70, $sp->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 7: Cancel order: RESERVED -> RELEASED, on_hand UNCHANGED
    // ─────────────────────────────────────────────────────────────────────────
    public function test_cancel_order_releases_reservation_and_leaves_on_hand_untouched(): void
    {
        $admin = $this->createAdminUser();
        [$user, $khach] = $this->createCustomerUser();
        [$sp, $lot1, $lot2] = $this->createProductWithLots(40, 60);

        $hd = HoaDon::create([
            'ma_khach_hang'          => $khach->ma_khach_hang,
            'dia_chi_giao'           => '123 Cầu Giấy, Hà Nội',
            'so_dien_thoai'          => '0912345678',
            'phuong_thuc_thanh_toan' => 'COD',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'tong_tien'              => 600000,
            'ngay_dat'               => now(),
        ]);

        ChiTietHoaDon::create([
            'ma_hoa_don'        => $hd->ma_hoa_don,
            'ma_san_pham'       => $sp->ma_san_pham,
            'ma_chi_tiet_nhap'  => $lot1->ma_chi_tiet_nhap,
            'so_luong'          => 30,
            'gia_nhap_snapshot' => 10000,
            'gia_ban_snapshot'  => 20000,
        ]);

        DB::transaction(function () use ($hd, $sp) {
            InventoryReservationService::reserveSingleItems($hd, [
                ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 30, 'gia_ban' => 20000],
            ]);
        });

        $this->assertEquals(100, $sp->fresh()->ton_thuc_te);
        $this->assertEquals(30, $sp->fresh()->ton_dang_giu);
        $this->assertEquals(70, $sp->fresh()->ton_kha_dung);

        // Admin từ chối/hủy đơn
        $response = $this->actingAs($admin)
            ->post(route('don-hang.cancel', $hd->ma_hoa_don), [
                'ly_do' => 'Khách đổi ý',
            ]);

        $response->assertJson(['success' => true]);

        $sp->refresh();
        $lot1->refresh();

        // 1. Reservation chuyển sang RELEASED
        $this->assertDatabaseHas('inventory_reservations', [
            'ma_hoa_don' => $hd->ma_hoa_don,
            'trang_thai' => InventoryReservation::STATUS_RELEASED,
        ]);

        // 2. On-hand KHÔNG đổi (vẫn 100, lô vẫn 40)
        $this->assertEquals(100, $sp->ton_thuc_te, 'Tồn thực tế không được tăng ảo khi hủy đơn reservation');
        $this->assertEquals(40, $lot1->so_luong_con_lai, 'Lô hàng không bị tăng ảo khi hủy đơn');

        // 3. Đang giữ về 0, Khả dụng phục hồi đầy đủ về 100
        $this->assertEquals(0, $sp->ton_dang_giu);
        $this->assertEquals(100, $sp->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 8: Idempotency: cannot release twice
    // ─────────────────────────────────────────────────────────────────────────
    public function test_cannot_release_twice(): void
    {
        [$sp, $lot1] = $this->createProductWithLots(40, 0);

        $hd = HoaDon::create([
            'ma_khach_hang' => 1, 'trang_thai' => 'PENDING', 'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD', 'tong_tien' => 200000, 'ngay_dat' => now(),
        ]);

        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 10, 'gia_ban' => 20000],
        ]));

        // Lần 1: release thành công
        DB::transaction(fn () => InventoryReservationService::releaseOrder($hd));

        // Lần 2: phải ném LogicException vì đã release rồi
        $this->expectException(\LogicException::class);
        DB::transaction(fn () => InventoryReservationService::releaseOrder($hd));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 9: Idempotency: cannot consume twice
    // ─────────────────────────────────────────────────────────────────────────
    public function test_cannot_consume_twice(): void
    {
        [$sp, $lot1] = $this->createProductWithLots(40, 0);

        $hd = HoaDon::create([
            'ma_khach_hang' => 1, 'trang_thai' => 'PENDING', 'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD', 'tong_tien' => 200000, 'ngay_dat' => now(),
        ]);

        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 10, 'gia_ban' => 20000],
        ]));

        // Lần 1: confirm thành công
        DB::transaction(fn () => InventoryReservationService::confirmOrder($hd));

        // Lần 2: phải ném LogicException vì đã confirm rồi
        $this->expectException(\LogicException::class);
        DB::transaction(fn () => InventoryReservationService::confirmOrder($hd));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 10: DRAFT and CANCELLED phieu nhap do not supply stock
    // ─────────────────────────────────────────────────────────────────────────
    public function test_draft_and_cancelled_phieu_nhap_do_not_supply_stock(): void
    {
        // Tạo phiếu nhập DRAFT và CANCELLED
        [$spDraft, $lotDraft] = $this->createProductWithLots(50, 0, 'DRAFT');
        [$spCancel, $lotCancel] = $this->createProductWithLots(50, 0, 'CANCELLED');

        $hd = HoaDon::create([
            'ma_khach_hang' => 1, 'trang_thai' => 'PENDING', 'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD', 'tong_tien' => 200000, 'ngay_dat' => now(),
        ]);

        // Cố reserve từ spDraft -> phải lỗi vì không có lô CONFIRMED
        try {
            DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
                ['ma_san_pham' => $spDraft->ma_san_pham, 'so_luong' => 5, 'gia_ban' => 20000],
            ]));
            $this->fail('Phải ném ngoại lệ khi reserve từ phiếu nhập DRAFT');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Cố reserve từ spCancel -> phải lỗi vì không có lô CONFIRMED
        try {
            DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
                ['ma_san_pham' => $spCancel->ma_san_pham, 'so_luong' => 5, 'gia_ban' => 20000],
            ]));
            $this->fail('Phải ném ngoại lệ khi reserve từ phiếu nhập CANCELLED');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 11: Out of stock lot (so_luong_con_lai = 0) cannot be reserved
    // ─────────────────────────────────────────────────────────────────────────
    public function test_out_of_stock_lot_cannot_be_reserved(): void
    {
        [$sp, $lot1] = $this->createProductWithLots(10, 0);
        $lot1->update(['so_luong_con_lai' => 0]);
        $sp->update(['so_luong' => 0]);

        $hd = HoaDon::create([
            'ma_khach_hang' => 1, 'trang_thai' => 'PENDING', 'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD', 'tong_tien' => 200000, 'ngay_dat' => now(),
        ]);

        $this->expectException(ValidationException::class);
        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 1, 'gia_ban' => 20000],
        ]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 12: Pre-assembled bouquet (BÓ CÓ SẴN) reserves finished bouquet, NOT BOM
    // ─────────────────────────────────────────────────────────────────────────
    public function test_pre_assembled_bouquet_reserves_finished_bouquet_not_bom(): void
    {
        [$user, $khach] = $this->createCustomerUser();
        [$hoaChinh, $lotHoa] = $this->createProductWithLots(50, 0, 'CONFIRMED', 'HOA_TUOI');
        [$giayGoi, $lotGiay] = $this->createProductWithLots(20, 0, 'CONFIRMED', 'GIAY_GOI');
        [$ruyBang, $lotRuy] = $this->createProductWithLots(20, 0, 'CONFIRMED', 'RUY_BANG');
        [$xop, $lotXop] = $this->createProductWithLots(20, 0, 'CONFIRMED', 'MIENG_XOP');

        // Tạo mẫu bó hoa hợp lệ theo database enum
        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Bó Hồng Sẵn Test',
            'size'        => 'S',
            'kieu_bo_hoa' => 'ROUND',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);

        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $hoaChinh->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH']);
        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $giayGoi->ma_san_pham, 'so_luong' => 4, 'vai_tro' => 'GIAY_GOI']);
        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $ruyBang->ma_san_pham, 'so_luong' => 1, 'vai_tro' => 'RUY_BANG']);
        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $xop->ma_san_pham, 'so_luong' => 1, 'vai_tro' => 'XOP']);

        // Bó hoa đã được shop cắm sẵn trước (loai_bo_hoa = HE_THONG, ma_hoa_don = null)
        $boSan = BoHoa::create([
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'ten_bo_hoa'    => $mau->ten_mau,
            'size'          => 'S',
            'kieu_bo_hoa'   => 'ROUND',
            'concept'       => 'ROMANTIC',
            'loai_bo_hoa'   => 'HE_THONG',
            'so_luong'      => 1,
            'gia_ban'       => 150000,
            'trang_thai'    => 'ACTIVE',
        ]);

        $hoaChinhInitialOnHand = $hoaChinh->fresh()->so_luong;

        // Khách mua bó hoa mẫu này -> hệ thống phải nhận diện bó có sẵn
        $hoaDon = DB::transaction(fn () => BoHoaController::taoDonTuCheckout([
            'loai'          => 'MAU',
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ], $khach, [
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao'           => '123 Test',
            'so_dien_thoai'          => '0912345678',
        ]));

        // 1. Chỉ reserve chính bó hoa thành phẩm
        $this->assertDatabaseHas('inventory_reservations', [
            'ma_hoa_don'  => $hoaDon->ma_hoa_don,
            'ma_bo_hoa'   => $boSan->ma_bo_hoa,
            'ma_san_pham' => null, // Không gắn mã sản phẩm nguyên liệu
            'trang_thai'  => InventoryReservation::STATUS_RESERVED,
        ]);

        // 2. KHÔNG reserve hay trừ nguyên liệu hoa chính lần nữa (tránh double count!)
        $this->assertDatabaseMissing('inventory_reservations', [
            'ma_hoa_don'  => $hoaDon->ma_hoa_don,
            'ma_san_pham' => $hoaChinh->ma_san_pham,
        ]);
        $this->assertEquals($hoaChinhInitialOnHand, $hoaChinh->fresh()->so_luong, 'Nguyên vật liệu hoa chính không bị trừ');
        $this->assertEquals(0, $hoaChinh->fresh()->ton_dang_giu, 'Hoa chính không bị giữ tồn BOM');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 13: Made-to-order BOM bouquet reserves ingredients via FIFO
    // ─────────────────────────────────────────────────────────────────────────
    public function test_made_to_order_bom_reserves_ingredients_via_fifo(): void
    {
        [$user, $khach] = $this->createCustomerUser();
        [$hoaChinh, $lotHoa1, $lotHoa2] = $this->createProductWithLots(20, 30, 'CONFIRMED', 'HOA_TUOI');
        [$giayGoi, $lotGiay] = $this->createProductWithLots(10, 0, 'CONFIRMED', 'GIAY_GOI');
        [$ruyBang, $lotRuy] = $this->createProductWithLots(10, 0, 'CONFIRMED', 'RUY_BANG');
        [$xop, $lotXop] = $this->createProductWithLots(10, 0, 'CONFIRMED', 'MIENG_XOP');

        $mau = MauBoHoa::create([
            'ten_mau'     => 'Bó Cắm Theo Đơn ' . microtime(true),
            'size'        => 'S',
            'kieu_bo_hoa' => 'ROUND',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);

        // Cấu hình chuẩn theo size S: 7 bông hoa chính, 4 giấy gói, 1 ruy băng, 1 xốp
        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $hoaChinh->ma_san_pham, 'so_luong' => 7, 'vai_tro' => 'HOA_CHINH']);
        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $giayGoi->ma_san_pham, 'so_luong' => 4, 'vai_tro' => 'GIAY_GOI']);
        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $ruyBang->ma_san_pham, 'so_luong' => 1, 'vai_tro' => 'RUY_BANG']);
        ChiTietMauBoHoa::create(['ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa, 'ma_san_pham' => $xop->ma_san_pham, 'so_luong' => 1, 'vai_tro' => 'XOP']);

        // Không có bó sẵn -> làm theo đơn -> reserve BOM
        $hoaDon = DB::transaction(fn () => BoHoaController::taoDonTuCheckout([
            'loai'          => 'MAU',
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'so_luong'      => 1,
        ], $khach, [
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao'           => '123 Test',
            'so_dien_thoai'          => '0912345678',
        ]));

        // Đã reserve nguyên vật liệu hoa chính:
        $this->assertDatabaseHas('inventory_reservations', [
            'ma_hoa_don'  => $hoaDon->ma_hoa_don,
            'ma_san_pham' => $hoaChinh->ma_san_pham,
            'so_luong'    => 7,
            'trang_thai'  => InventoryReservation::STATUS_RESERVED,
        ]);

        $this->assertEquals(7, $hoaChinh->fresh()->ton_dang_giu);
        $this->assertEquals(43, $hoaChinh->fresh()->ton_kha_dung);
        $this->assertEquals(50, $hoaChinh->fresh()->ton_thuc_te, 'Tồn thực tế hoa chưa bị trừ');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 14: Custom Mix (TUY_CHON) reserves actual chosen ingredients
    // ─────────────────────────────────────────────────────────────────────────
    public function test_custom_mix_reserves_actual_chosen_ingredients(): void
    {
        [$user, $khach] = $this->createCustomerUser();
        [$hoaChinh, $lotHoa] = $this->createProductWithLots(30, 0, 'CONFIRMED', 'HOA_TUOI');
        [$giayGoi, $lotGiay] = $this->createProductWithLots(20, 0, 'CONFIRMED', 'GIAY_GOI');
        [$ruyBang, $lotRuy] = $this->createProductWithLots(20, 0, 'CONFIRMED', 'RUY_BANG');
        [$xop, $lotXop] = $this->createProductWithLots(20, 0, 'CONFIRMED', 'MIENG_XOP');

        $draft = BoHoa::create([
            'ma_khach_hang' => $khach->ma_khach_hang,
            'ten_bo_hoa'    => 'Bó Custom Test ' . microtime(true),
            'size'          => 'S',
            'kieu_bo_hoa'   => 'ROUND',
            'concept'       => 'ROMANTIC',
            'loai_bo_hoa'   => 'TUY_CHON',
            'so_luong'      => 1,
            'gia_ban'       => 200000,
            'trang_thai'    => 'DRAFT',
        ]);

        $thanhPhan = [
            ['ma_san_pham' => $hoaChinh->ma_san_pham, 'vai_tro' => 'HOA_CHINH', 'so_luong' => 7],
            ['ma_san_pham' => $giayGoi->ma_san_pham, 'vai_tro' => 'GIAY_GOI', 'so_luong' => 4],
            ['ma_san_pham' => $ruyBang->ma_san_pham, 'vai_tro' => 'RUY_BANG', 'so_luong' => 1],
            ['ma_san_pham' => $xop->ma_san_pham, 'vai_tro' => 'XOP', 'so_luong' => 1],
        ];

        foreach ($thanhPhan as $row) {
            $draft->chiTietBoHoa()->create($row);
        }

        $hoaDon = DB::transaction(fn () => BoHoaController::taoDonTuCheckout([
            'loai'       => 'TUY_CHON',
            'ma_bo_hoa'  => $draft->ma_bo_hoa,
            'so_luong'   => 1,
        ], $khach, [
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao'           => '123 Test',
            'so_dien_thoai'          => '0912345678',
        ]));

        $this->assertDatabaseHas('inventory_reservations', [
            'ma_hoa_don'  => $hoaDon->ma_hoa_don,
            'ma_san_pham' => $hoaChinh->ma_san_pham,
            'so_luong'    => 7,
            'trang_thai'  => InventoryReservation::STATUS_RESERVED,
        ]);

        $this->assertEquals(7, $hoaChinh->fresh()->ton_dang_giu);
        $this->assertEquals(23, $hoaChinh->fresh()->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 15: VNPay fail/abandon/cancel releases reservation and restores available
    // ─────────────────────────────────────────────────────────────────────────
    public function test_vnpay_fail_or_cancel_releases_reservation_and_restores_available(): void
    {
        [$user, $khach] = $this->createCustomerUser();
        [$sp, $lot1] = $this->createProductWithLots(50, 0);

        // Khách checkout chọn phương thức VNPay
        $response = $this->actingAs($user)
            ->withSession([
                'checkout_items' => [
                    $sp->ma_san_pham => [
                        'ma_san_pham'  => $sp->ma_san_pham,
                        'ten_san_pham' => $sp->ten_san_pham,
                        'gia_ban'      => $sp->gia_ban_hien_tai,
                        'so_luong'     => 15,
                    ],
                ],
            ])
            ->post(route('customer.thanh-toan.store'), [
                'ten_nguoi_nhan'         => 'Nguyễn Văn Test',
                'so_dien_thoai'          => '0912345678',
                'quan_huyen'             => 'Cầu Giấy',
                'xa_phuong'              => 'Dịch Vọng',
                'dia_chi_chi_tiet'       => 'Số 10 Dịch Vọng Hậu',
                'phuong_thuc_thanh_toan' => 'NGAN_HANG',
            ]);

        // Đơn hàng đã được tạo và giữ kho ngay lúc checkout
        $hd = HoaDon::where('ma_khach_hang', $khach->ma_khach_hang)->latest('ma_hoa_don')->first();
        $this->assertNotNull($hd);
        $this->assertEquals(15, $sp->fresh()->ton_dang_giu);
        $this->assertEquals(35, $sp->fresh()->ton_kha_dung);

        // Khách hủy giao dịch trên VNPay (ResponseCode = 24: Khách hàng hủy giao dịch)
        $vnpSecret = 'TEST_SECRET_KEY_12345678901234567890';
        $params = [
            'vnp_TxnRef'       => (string) $hd->ma_hoa_don,
            'vnp_ResponseCode' => '24',
            'vnp_Amount'       => (string) ((int) round($hd->tong_tien * 100)),
            'vnp_TmnCode'      => 'TEST_TMN',
        ];
        ksort($params, SORT_STRING);
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = $k . '=' . urlencode((string) $v);
        }
        $params['vnp_SecureHash'] = hash_hmac('sha512', implode('&', $parts), $vnpSecret);

        $returnResponse = $this->actingAs($user)
            ->get(route('vnpay.return', $params));

        $returnResponse->assertRedirect(route('customer.dashboard'));

        $sp->refresh();
        $lot1->refresh();
        $hd->refresh();

        // 1. Trạng thái đơn chuyển sang CANCELLED
        $this->assertEquals('CANCELLED', $hd->trang_thai);

        // 2. Reservation chuyển sang RELEASED
        $this->assertDatabaseHas('inventory_reservations', [
            'ma_hoa_don' => $hd->ma_hoa_don,
            'trang_thai' => InventoryReservation::STATUS_RELEASED,
        ]);

        // 3. Tồn thực tế KHÔNG đổi (vẫn 50)
        $this->assertEquals(50, $sp->ton_thuc_te);
        $this->assertEquals(50, $lot1->so_luong_con_lai);

        // 4. Tồn đang giữ về 0, tồn khả dụng phục hồi hoàn toàn về 50
        $this->assertEquals(0, $sp->ton_dang_giu);
        $this->assertEquals(50, $sp->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 16: Admin inventory page /admin/ton-kho and modal drill-down
    // ─────────────────────────────────────────────────────────────────────────
    public function test_admin_inventory_page_and_modal_drilldown(): void
    {
        $admin = $this->createAdminUser();
        [$sp, $lot1] = $this->createProductWithLots(40, 0);

        $hd = HoaDon::create([
            'ma_khach_hang'          => 1,
            'so_dien_thoai'          => '0988112233',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'tong_tien'              => 200000,
            'ngay_dat'               => now(),
        ]);

        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 12, 'gia_ban' => 20000],
        ]));

        // 1. Truy cập giao diện /admin/ton-kho có lọc theo keyword mã sản phẩm
        $viewRes = $this->actingAs($admin)->get(route('admin.ton-kho', ['keyword' => $sp->ma_san_pham]));
        $viewRes->assertOk();
        $viewRes->assertSee($sp->ten_san_pham);
        $viewRes->assertSee('Quản lý tồn kho');
        $viewRes->assertSee('Sắp hết hàng');
        $viewRes->assertSee('Tình trạng tồn');

        // Test lọc theo tình trạng tồn kho:
        // Sản phẩm này có khả dụng = 28 (> 0) và đang giữ = 12
        $filterDangGiu = $this->actingAs($admin)->get(route('admin.ton-kho', ['tinh_trang_ton' => 'dang_giu', 'keyword' => $sp->ma_san_pham]));
        $filterDangGiu->assertOk();
        $filterDangGiu->assertSee($sp->ten_san_pham);

        $filterConHang = $this->actingAs($admin)->get(route('admin.ton-kho', ['tinh_trang_ton' => 'con_hang', 'keyword' => $sp->ma_san_pham]));
        $filterConHang->assertOk();
        $filterConHang->assertSee($sp->ten_san_pham);

        $filterHetHang = $this->actingAs($admin)->get(route('admin.ton-kho', ['tinh_trang_ton' => 'het_hang', 'keyword' => $sp->ma_san_pham]));
        $filterHetHang->assertOk();
        $filterHetHang->assertDontSee($sp->ten_san_pham);

        // 2. Gọi API chi tiết đơn giữ
        $apiRes = $this->actingAs($admin)->getJson(route('admin.ton-kho.giu', $sp->ma_san_pham));
        $apiRes->assertOk();
        $apiRes->assertJsonPath('san_pham.ton_thuc_te', 40);
        $apiRes->assertJsonPath('san_pham.ton_dang_giu', 12);
        $apiRes->assertJsonPath('san_pham.ton_kha_dung', 28);
        $apiRes->assertJsonPath('reservations.0.ma_hoa_don', $hd->ma_hoa_don);
        $apiRes->assertJsonPath('reservations.0.so_luong', 12);
    }
}
