<?php

namespace Tests\Feature;

use App\Models\ChiTietNhap;
use App\Models\DiaChiKhachHang;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerThanhToanDiaChiTest extends TestCase
{
    protected NguoiDung $user;
    protected KhachHang $customer;
    protected SanPham $product;
    protected ChiTietNhap $lot;

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

        $unique = microtime(true) . '_' . mt_rand(1000, 9999);

        $this->user = NguoiDung::create([
            'ten_dang_nhap'     => 'cust_addr_' . $unique,
            'mat_khau'          => bcrypt('password123'),
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);

        $this->customer = KhachHang::create([
            'ma_khach_hang'  => $this->user->ma_nguoi_dung,
            'ten_khach_hang' => 'Nguyễn Văn Test',
            'so_dien_thoai'  => '0912345678',
            'email'          => 'cust_addr_' . $unique . '@example.com',
            'dia_chi'        => 'Số 10 Tràng Thi, Hàng Trống, Hoàn Kiếm, Hà Nội',
            'diem_tich_luy'  => 0,
        ]);

        $nv = NhanVien::first();
        $ncc = \App\Models\NhaCungCap::first();
        $pn = PhieuNhap::create([
            'ma_nha_cung_cap' => $ncc ? $ncc->ma_nha_cung_cap : 1,
            'ma_nhan_vien'    => $nv ? $nv->ma_nhan_vien : 6,
            'tong_tien'       => 1000000,
            'ngay_nhap'       => now()->subDays(1),
            'trang_thai'      => 'CONFIRMED',
        ]);

        $this->product = SanPham::create([
            'ten_san_pham'     => 'Hoa Tulip Test ' . $unique,
            'loai_san_pham'    => 'HOA_TUOI',
            'don_vi_tinh'      => 'Cành',
            'so_luong'         => 50,
            'gia_ban_hien_tai' => 50000,
            'trang_thai'       => 'DANG_BAN',
        ]);

        $this->lot = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $this->product->ma_san_pham,
            'so_luong'         => 50,
            'so_luong_con_lai' => 50,
            'gia_nhap'         => 30000,
            'gia_ban'          => 50000,
            'ngay_het_han'     => now()->addDays(5)->toDateString(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_get_address_list_returns_saved_addresses(): void
    {
        $this->actingAs($this->user);

        // Pre-create an address
        DiaChiKhachHang::create([
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Trần Thị B',
            'so_dien_thoai'    => '0987654321',
            'quan_huyen'       => 'Cầu Giấy',
            'xa_phuong'        => 'Dịch Vọng Hậu',
            'dia_chi_chi_tiet' => '123 Xuân Thủy',
            'dia_chi_day_du'   => '123 Xuân Thủy, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
            'la_mac_dinh'      => true,
        ]);

        $response = $this->getJson(route('customer.dia-chi.index'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'danh_sach')
            ->assertJsonFragment([
                'ten_nguoi_nhan' => 'Trần Thị B',
                'so_dien_thoai'  => '0987654321',
            ]);
    }

    public function test_can_add_new_address_and_set_as_default(): void
    {
        $this->actingAs($this->user);

        $payload = [
            'ten_nguoi_nhan'   => 'Lê Văn C',
            'so_dien_thoai'    => '0901234567',
            'quan_huyen'       => 'Đống Đa',
            'xa_phuong'        => 'Láng Hạ',
            'dia_chi_chi_tiet' => '88 Thái Hà',
            'la_mac_dinh'      => true,
        ];

        $response = $this->postJson(route('customer.dia-chi.store'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Thêm địa chỉ nhận hàng thành công!',
            ])
            ->assertJsonFragment([
                'ten_nguoi_nhan' => 'Lê Văn C',
                'dia_chi_day_du' => '88 Thái Hà, Láng Hạ, Đống Đa, Hà Nội',
                'la_mac_dinh'    => true,
            ]);

        $this->assertDatabaseHas('dia_chi_khach_hang', [
            'ma_khach_hang'  => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan' => 'Lê Văn C',
            'la_mac_dinh'    => 1,
        ]);

        // Customer's main dia_chi field should be synchronized
        $this->customer->refresh();
        $this->assertEquals('88 Thái Hà, Láng Hạ, Đống Đa, Hà Nội', $this->customer->dia_chi);
    }

    public function test_can_update_existing_address(): void
    {
        $this->actingAs($this->user);

        $addr = DiaChiKhachHang::create([
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Lê Văn C',
            'so_dien_thoai'    => '0901234567',
            'quan_huyen'       => 'Đống Đa',
            'xa_phuong'        => 'Láng Hạ',
            'dia_chi_chi_tiet' => '88 Thái Hà',
            'dia_chi_day_du'   => '88 Thái Hà, Láng Hạ, Đống Đa, Hà Nội',
            'la_mac_dinh'      => true,
        ]);

        $updatePayload = [
            'ten_nguoi_nhan'   => 'Lê Văn C - Updated',
            'so_dien_thoai'    => '0909998877',
            'quan_huyen'       => 'Đống Đa',
            'xa_phuong'        => 'Láng Hạ',
            'dia_chi_chi_tiet' => '90 Thái Hà Tầng 5',
            'la_mac_dinh'      => true,
        ];

        $response = $this->putJson(route('customer.dia-chi.update', $addr->ma_dia_chi), $updatePayload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'ten_nguoi_nhan' => 'Lê Văn C - Updated',
                'so_dien_thoai'  => '0909998877',
                'dia_chi_day_du' => '90 Thái Hà Tầng 5, Láng Hạ, Đống Đa, Hà Nội',
            ]);
    }

    public function test_can_switch_default_address(): void
    {
        $this->actingAs($this->user);

        $addr1 = DiaChiKhachHang::create([
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Địa chỉ 1',
            'so_dien_thoai'    => '0911111111',
            'quan_huyen'       => 'Ba Đình',
            'xa_phuong'        => 'Kim Mã',
            'dia_chi_chi_tiet' => '10 Kim Mã',
            'dia_chi_day_du'   => '10 Kim Mã, Kim Mã, Ba Đình, Hà Nội',
            'la_mac_dinh'      => true,
        ]);

        $addr2 = DiaChiKhachHang::create([
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Địa chỉ 2',
            'so_dien_thoai'    => '0922222222',
            'quan_huyen'       => 'Cầu Giấy',
            'xa_phuong'        => 'Dịch Vọng',
            'dia_chi_chi_tiet' => '20 Cầu Giấy',
            'dia_chi_day_du'   => '20 Cầu Giấy, Dịch Vọng, Cầu Giấy, Hà Nội',
            'la_mac_dinh'      => false,
        ]);

        $response = $this->patchJson(route('customer.dia-chi.mac-dinh', $addr2->ma_dia_chi));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $addr1->refresh();
        $addr2->refresh();

        $this->assertFalse((bool)$addr1->la_mac_dinh);
        $this->assertTrue((bool)$addr2->la_mac_dinh);

        $this->customer->refresh();
        $this->assertEquals($addr2->dia_chi_day_du, $this->customer->dia_chi);
    }

    public function test_checkout_page_auto_displays_default_saved_address(): void
    {
        $this->actingAs($this->user);

        // Save an address
        $addr = DiaChiKhachHang::create([
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Hoàng Mai Phương',
            'so_dien_thoai'    => '0933333333',
            'quan_huyen'       => 'Tây Hồ',
            'xa_phuong'        => 'Quảng An',
            'dia_chi_chi_tiet' => '50 Đặng Thai Mai',
            'dia_chi_day_du'   => '50 Đặng Thai Mai, Quảng An, Tây Hồ, Hà Nội',
            'la_mac_dinh'      => true,
        ]);

        // Place item in session for checkout
        session([
            'mua_ngay' => [
                $this->product->ma_san_pham => [
                    'ma_san_pham'  => $this->product->ma_san_pham,
                    'ten_san_pham' => $this->product->ten_san_pham,
                    'gia_ban'      => 50000,
                    'so_luong'     => 1,
                    'hinh_anh'     => null,
                ]
            ]
        ]);

        $response = $this->get(route('customer.thanh-toan'));

        $response->assertOk();
        $response->assertSee('Hoàng Mai Phương');
        $response->assertSee('0933333333');
        $response->assertSee('50 Đặng Thai Mai, Quảng An, Tây Hồ, Hà Nội');
        $response->assertSee('Địa Chỉ Nhận Hàng');
        $response->assertSee('Thay Đổi');
    }

    public function test_checkout_submission_persists_new_address_and_reuses_it_subsequently(): void
    {
        $this->actingAs($this->user);

        session([
            'mua_ngay' => [
                $this->product->ma_san_pham => [
                    'ma_san_pham'  => $this->product->ma_san_pham,
                    'ten_san_pham' => $this->product->ten_san_pham,
                    'gia_ban'      => 50000,
                    'so_luong'     => 1,
                    'hinh_anh'     => null,
                ]
            ]
        ]);

        $checkoutPayload = [
            'ten_nguoi_nhan'         => 'Nguyễn Thanh Tùng',
            'so_dien_thoai'          => '0944444444',
            'quan_huyen'             => 'Hai Bà Trưng',
            'xa_phuong'              => 'Bách Khoa',
            'dia_chi_chi_tiet'       => 'Số 1 Đại Cồ Việt',
            'phuong_thuc_thanh_toan' => 'COD',
        ];

        $response = $this->post(route('customer.thanh-toan.store'), $checkoutPayload);

        $response->assertRedirect(route('customer.dashboard'));

        // Address should have been automatically persisted to dia_chi_khach_hang
        $this->assertDatabaseHas('dia_chi_khach_hang', [
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Nguyễn Thanh Tùng',
            'so_dien_thoai'    => '0944444444',
            'quan_huyen'       => 'Hai Bà Trưng',
            'xa_phuong'        => 'Bách Khoa',
            'dia_chi_chi_tiet' => 'Số 1 Đại Cồ Việt',
        ]);

        // When purchasing again, the saved address is automatically loaded
        session([
            'mua_ngay' => [
                $this->product->ma_san_pham => [
                    'ma_san_pham'  => $this->product->ma_san_pham,
                    'ten_san_pham' => $this->product->ten_san_pham,
                    'gia_ban'      => 50000,
                    'so_luong'     => 1,
                    'hinh_anh'     => null,
                ]
            ]
        ]);

        $secondCheckout = $this->get(route('customer.thanh-toan'));
        $secondCheckout->assertOk();
        $secondCheckout->assertSee('Nguyễn Thanh Tùng');
        $secondCheckout->assertSee('0944444444');
        $secondCheckout->assertSee('Số 1 Đại Cồ Việt, Bách Khoa, Hai Bà Trưng, Hà Nội');
    }

    public function test_can_delete_address_and_fallback_to_another(): void
    {
        $this->actingAs($this->user);

        $addr1 = DiaChiKhachHang::create([
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Địa chỉ 1 (Default)',
            'so_dien_thoai'    => '0911111111',
            'quan_huyen'       => 'Ba Đình',
            'xa_phuong'        => 'Kim Mã',
            'dia_chi_chi_tiet' => '10 Kim Mã',
            'dia_chi_day_du'   => '10 Kim Mã, Kim Mã, Ba Đình, Hà Nội',
            'la_mac_dinh'      => true,
        ]);

        $addr2 = DiaChiKhachHang::create([
            'ma_khach_hang'    => $this->customer->ma_khach_hang,
            'ten_nguoi_nhan'   => 'Địa chỉ 2 (Secondary)',
            'so_dien_thoai'    => '0922222222',
            'quan_huyen'       => 'Cầu Giấy',
            'xa_phuong'        => 'Dịch Vọng',
            'dia_chi_chi_tiet' => '20 Cầu Giấy',
            'dia_chi_day_du'   => '20 Cầu Giấy, Dịch Vọng, Cầu Giấy, Hà Nội',
            'la_mac_dinh'      => false,
        ]);

        $response = $this->deleteJson(route('customer.dia-chi.destroy', $addr1->ma_dia_chi));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('dia_chi_khach_hang', [
            'ma_dia_chi' => $addr1->ma_dia_chi,
        ]);

        $addr2->refresh();
        $this->assertTrue((bool)$addr2->la_mac_dinh);
    }
}
