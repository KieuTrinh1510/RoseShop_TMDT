<?php

namespace Tests\Feature;

use App\Models\ChiTietMauBoHoa;
use App\Models\ChiTietNhap;
use App\Models\KhachHang;
use App\Models\MauBoHoa;
use App\Models\NguoiDung;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use App\Services\CartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GioHangPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    private NguoiDung $customerUser;
    private KhachHang $khachHang;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'shop_hoa',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');
        \Illuminate\Support\Facades\DB::beginTransaction();

        $this->customerUser = NguoiDung::create([
            'ten_dang_nhap'     => 'cust_' . uniqid(),
            'mat_khau'          => bcrypt('Password123!'),
            'vai_tro'           => 'KHACH_HANG',
            'trang_thai'        => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);

        $this->khachHang = KhachHang::create([
            'ma_khach_hang'  => $this->customerUser->ma_nguoi_dung,
            'ten_khach_hang' => 'Khách Test Giỏ Hàng',
            'so_dien_thoai'  => '090' . rand(1000000, 9999999),
            'email'          => 'cust_' . uniqid() . '@example.com',
            'dia_chi'        => 'Hà Nội',
            'diem_tich_luy'  => 0,
            'quyen_cod'      => 'CHO_PHEP',
        ]);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::rollBack();
        parent::tearDown();
    }

    public function test_customer_adds_bouquet_and_product_and_views_unified_cart(): void
    {
        // 1. Tạo mẫu bó hoa hợp lệ có sẵn nguyên liệu
        $flower = SanPham::create([
            'ten_san_pham'     => 'Hoa Test ' . uniqid(),
            'so_luong'         => 100,
            'don_vi_tinh'      => 'CANH',
            'loai_san_pham'    => SanPham::LOAI_HOA_TUOI,
            'trang_thai'       => 'DANG_BAN',
            'gia_ban_hien_tai' => 20000,
        ]);

        $pn = PhieuNhap::create([
            'ma_nguoi_dung' => 1,
            'ngay_nhap'     => now(),
            'tong_tien'     => 1000000,
            'trang_thai'    => 'CONFIRMED',
        ]);

        $lot = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $flower->ma_san_pham,
            'so_luong'         => 100,
            'so_luong_con_lai' => 100,
            'gia_nhap'         => 10000,
            'gia_ban'          => 20000,
        ]);

        $mau = MauBoHoa::create([
            'ten_mau'     => 'Mẫu Test Giỏ ' . uniqid(),
            'size'        => 'S',
            'kieu_bo_hoa' => 'ROUND',
            'concept'     => 'ROMANTIC',
            'trang_thai'  => 'ACTIVE',
        ]);

        ChiTietMauBoHoa::create([
            'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
            'ma_san_pham'   => $flower->ma_san_pham,
            'vai_tro'       => 'HOA_CHINH',
            'so_luong'      => 7,
        ]);

        // 2. Khách thêm bó hoa vào giỏ hàng
        $resBoHoa = $this->actingAs($this->customerUser)
            ->post(route('customer.bo-hoa.gio.add'), [
                'ma_mau_bo_hoa' => $mau->ma_mau_bo_hoa,
                'so_luong'      => 1,
                'loi_nhan'      => 'Chúc mừng sinh nhật!',
            ]);

        $resBoHoa->assertRedirect(route('customer.gio-hang'));

        // 3. Khách thêm sản phẩm hoa lẻ vào giỏ hàng
        $resSP = $this->actingAs($this->customerUser)
            ->postJson(route('customer.gio-hang.add'), [
                'items' => [
                    [
                        'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
                        'so_luong'         => 3,
                    ],
                ],
            ]);

        $resSP->assertJson(['success' => true]);

        // 4. Truy cập trang giỏ hàng thống nhất
        $resGioHang = $this->actingAs($this->customerUser)
            ->get(route('customer.gio-hang'));

        $resGioHang->assertStatus(200);
        $resGioHang->assertSee($mau->ten_mau);
        $resGioHang->assertSee($flower->ten_san_pham);
        $resGioHang->assertSee('Chúc mừng sinh nhật!');
        $resGioHang->assertDontSee('Giỏ hàng của bạn đang trống');

        // 5. Kiểm tra giỏ hàng đã được lưu tự động trong database
        $this->khachHang->refresh();
        $this->assertNotNull($this->khachHang->du_lieu_gio_hang);
        $this->assertNotEmpty($this->khachHang->du_lieu_gio_hang['gio_hang']);
        $this->assertNotEmpty($this->khachHang->du_lieu_gio_hang['gio_bo_hoa']);
    }

    public function test_cart_restores_from_database_after_session_cleared(): void
    {
        // Giả lập dữ liệu giỏ hàng đã lưu trong database của khách
        $savedCart = [
            'gio_hang' => [
                'lo_999' => [
                    'ma_chi_tiet_nhap' => 999,
                    'ma_san_pham'      => 330,
                    'ten_san_pham'     => 'Hoa Hồng Đỏ',
                    'gia_ban'          => 25000,
                    'so_luong'         => 2,
                    'so_luong_con_lai' => 50,
                ],
            ],
            'gio_bo_hoa' => [
                'bo_test_123' => [
                    'ma_mau_bo_hoa' => 392,
                    'so_luong'      => 1,
                    'loi_nhan'      => 'Tặng người thương',
                ],
            ],
        ];

        $this->khachHang->du_lieu_gio_hang = $savedCart;
        $this->khachHang->save();

        // Giả lập session mới tinh (ví dụ khách vừa mở lại trình duyệt hoặc hết hạn session)
        session()->forget(['gio_hang', 'gio_bo_hoa']);
        $this->assertEmpty(session('gio_hang', []));
        $this->assertEmpty(session('gio_bo_hoa', []));

        // Khách truy cập trang giỏ hàng
        $res = $this->actingAs($this->customerUser)
            ->get(route('customer.gio-hang'));

        $res->assertStatus(200);

        // Session đã được tự động khôi phục từ database
        $this->assertNotEmpty(session('gio_hang'));
        $this->assertNotEmpty(session('gio_bo_hoa'));
        $this->assertEquals(2, session('gio_hang')['lo_999']['so_luong']);
        $this->assertEquals('Tặng người thương', session('gio_bo_hoa')['bo_test_123']['loi_nhan']);
    }
}
