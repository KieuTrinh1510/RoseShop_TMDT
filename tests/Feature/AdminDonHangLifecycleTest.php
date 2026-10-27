<?php

namespace Tests\Feature;

use App\Models\ChiTietHoaDon;
use App\Models\ChiTietHangHoan;
use App\Models\GiaoHangThatBai;
use App\Models\HangHoan;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\PhatShipper;
use App\Models\SanPham;
use App\Models\ViPhamKhachHang;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDonHangLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $nhanVien;
    protected $shipper;
    protected $khachHang;
    protected $sanPham;

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
        $this->nhanVien = NguoiDung::where('vai_tro', 'NHAN_VIEN')->first();
        if (!$this->nhanVien) {
            $this->nhanVien = NguoiDung::create([
                'ten_dang_nhap' => 'nv_test_' . uniqid(),
                'mat_khau' => bcrypt('password'),
                'ho_ten' => 'Nhân Viên Test',
                'email' => 'nv_test_' . uniqid() . '@example.com',
                'vai_tro' => 'NHAN_VIEN',
                'trang_thai' => 1,
            ]);
        }

        $this->shipper = NhanVien::where('chuc_vu', 'SHIPPER')
            ->where('trang_thai_hoat_dong', 'ONLINE')
            ->first();

        if (!$this->shipper) {
            $userShipper = NguoiDung::create([
                'ten_dang_nhap' => 'ship_test_' . uniqid(),
                'mat_khau' => bcrypt('password'),
                'ho_ten' => 'Shipper Test Online',
                'email' => 'ship_test_' . uniqid() . '@example.com',
                'vai_tro' => 'SHIPPER',
                'trang_thai' => 1,
            ]);
            $this->shipper = NhanVien::create([
                'ma_nhan_vien' => $userShipper->ma_nguoi_dung,
                'ten_nhan_vien' => 'Shipper Test Online',
                'chuc_vu' => 'SHIPPER',
                'trang_thai_hoat_dong' => 'ONLINE',
                'so_dien_thoai' => '0988776655',
                'email' => 'ship_test_' . uniqid() . '@example.com',
                'luong' => 5000000,
            ]);
        }

        $this->khachHang = KhachHang::first();
        $this->sanPham = SanPham::first();
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

    private function createOrder(array $attrs = []): HoaDon
    {
        return HoaDon::create(array_merge([
            'ma_khach_hang' => $this->khachHang->ma_khach_hang,
            'ngay_dat' => now(),
            'tong_tien' => 150000,
            'trang_thai' => 'PENDING',
            'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'dia_chi_giao' => '123 Test Street, HCM',
            'so_dien_thoai' => '0912345678',
            'co_hang_hoan' => 0,
        ], $attrs));
    }

    // ─── 1. PENDING → Chờ duyệt ───
    public function test_tab_pending_displays_pending_orders()
    {
        $hd = $this->createOrder(['trang_thai' => 'PENDING']);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $response->assertSee('Chờ duyệt');
    }

    // ─── 2. CONFIRMED → Đã xác nhận ───
    public function test_tab_confirmed_displays_confirmed_orders()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'CONFIRMED',
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'confirmed']));
        $response->assertStatus(200);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $response->assertSee('Đã xác nhận');
    }

    // ─── 3. SHIPPING → Đang giao ───
    public function test_tab_shipping_displays_shipping_orders()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'SHIPPING',
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_bat_giao' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'shipping']));
        $response->assertStatus(200);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $response->assertSee('Đang giao');
    }

    // ─── 4. DELIVERED → Đã giao ───
    public function test_tab_delivered_displays_delivered_orders()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'DELIVERED',
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_giao' => now(),
            'trang_thai_thanh_toan' => 'DA_THANH_TOAN',
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'delivered']));
        $response->assertStatus(200);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $response->assertSee('Đã giao');
    }

    // ─── 5. Failed delivery → Giao thất bại ───
    public function test_tab_failed_displays_failed_delivery_orders()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);

        $ghtb = GiaoHangThatBai::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'loai_loi' => 'CUSTOMER',
            'ly_do' => 'Khách thuê bao không liên lạc được',
            'thoi_diem_bao' => now(),
            'trang_thai' => 'DA_BAO',
        ]);

        HangHoan::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_giao_hang_that_bai' => $ghtb->ma_giao_hang_that_bai,
            'trang_thai' => 'CHO_MANG_VE_KHO',
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'failed']));
        $response->assertStatus(200);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $response->assertSee('Giao thất bại');
        $response->assertSee('Khách thuê bao không liên lạc được');
        $response->assertSee('Chờ mang về kho');
    }

    // ─── 6. Cancel thông thường → Đã hủy ───
    public function test_tab_cancelled_displays_normal_cancelled_orders()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 0,
            'ly_do_huy' => 'Khách đổi ý muốn hủy đơn',
            'thoi_diem_huy' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'cancelled']));
        $response->assertStatus(200);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $response->assertSee('Đã hủy');
        $response->assertSee('Khách đổi ý muốn hủy đơn');
    }

    // ─── 7. Failed không xuất hiện trong Đã hủy ───
    public function test_failed_order_does_not_appear_in_cancelled_tab()
    {
        $hdFailed = $this->createOrder([
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);
        GiaoHangThatBai::create([
            'ma_hoa_don' => $hdFailed->ma_hoa_don,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'loai_loi' => 'CUSTOMER',
            'ly_do' => 'Lỗi giao hàng',
            'thoi_diem_bao' => now(),
            'trang_thai' => 'DA_BAO',
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'cancelled']));
        $response->assertStatus(200);
        $response->assertDontSee('#HD-' . str_pad($hdFailed->ma_hoa_don, 4, '0', STR_PAD_LEFT));
    }

    // ─── 8. Cancel thông thường không xuất hiện Giao thất bại ───
    public function test_normal_cancelled_order_does_not_appear_in_failed_tab()
    {
        $hdNormalCancel = $this->createOrder([
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 0,
            'ly_do_huy' => 'Admin từ chối',
            'thoi_diem_huy' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'failed']));
        $response->assertStatus(200);
        $response->assertDontSee('#HD-' . str_pad($hdNormalCancel->ma_hoa_don, 4, '0', STR_PAD_LEFT));
    }

    // ─── 9. Search mã đơn ───
    public function test_search_by_order_code()
    {
        $hd1 = $this->createOrder(['tong_tien' => 123456]);
        $hd2 = $this->createOrder(['tong_tien' => 654321]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['q' => '#HD-' . str_pad($hd1->ma_hoa_don, 4, '0', STR_PAD_LEFT)]));
        $response->assertStatus(200);
        $response->assertSee('#HD-' . str_pad($hd1->ma_hoa_don, 4, '0', STR_PAD_LEFT));
        $response->assertDontSee('#HD-' . str_pad($hd2->ma_hoa_don, 4, '0', STR_PAD_LEFT));
    }

    // ─── 10. Search tên khách ───
    public function test_search_by_customer_name()
    {
        $uniqueName = 'KhachHangDacBiet_' . uniqid();
        $user = NguoiDung::create([
            'ten_dang_nhap' => 'user_' . uniqid(),
            'mat_khau' => bcrypt('password'),
            'ho_ten' => $uniqueName,
            'email' => 'user_' . uniqid() . '@example.com',
            'vai_tro' => 'KHACH_HANG',
            'trang_thai' => 1,
        ]);
        $kh = KhachHang::create([
            'ma_khach_hang' => $user->ma_nguoi_dung,
            'ten_khach_hang' => $uniqueName,
            'so_dien_thoai' => '0933999888',
            'email' => 'user_' . uniqid() . '@example.com',
            'dia_chi' => 'Test Address',
            'diem_tich_luy' => 0,
            'quyen_cod' => 'CHO_PHEP',
        ]);

        $hd = $this->createOrder(['ma_khach_hang' => $kh->ma_khach_hang]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['q' => $uniqueName]));
        $response->assertStatus(200);
        $response->assertSee($uniqueName);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
    }

    // ─── 11. Search SĐT ───
    public function test_search_by_phone()
    {
        $uniquePhone = '0987' . rand(100000, 999999);
        $hd = $this->createOrder(['so_dien_thoai' => $uniquePhone]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['q' => $uniquePhone]));
        $response->assertStatus(200);
        $response->assertSee($uniquePhone);
        $response->assertSee('#HD-' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT));
    }

    // ─── 12. Pagination giữ tab/query ───
    public function test_pagination_preserves_tab_and_query()
    {
        $response = $this->actingAs($this->admin)->get(route('don-hang.index', [
            'tab' => 'pending',
            'q' => '09',
            'per_page' => 10,
        ]));
        $response->assertStatus(200);
        $response->assertSee('tab=pending');
        $response->assertSee('q=09');
    }

    // ─── 13, 14, 15. Confirm đơn, gán shipper, reservation consume đúng ───
    public function test_confirm_order_assigns_shipper_and_consumes_reservation()
    {
        $hd = $this->createOrder(['trang_thai' => 'PENDING']);
        ChiTietHoaDon::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_san_pham' => $this->sanPham->ma_san_pham,
            'so_luong' => 2,
            'gia_ban_snapshot' => 50000,
            'gia_nhap_snapshot' => 30000,
        ]);

        $res = InventoryReservation::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_san_pham' => $this->sanPham->ma_san_pham,
            'so_luong' => 2,
            'trang_thai' => 'RESERVED',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('don-hang.confirm', $hd->ma_hoa_don), [
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $hd->refresh();
        $this->assertEquals('CONFIRMED', $hd->trang_thai);
        $this->assertEquals($this->shipper->ma_nhan_vien, $hd->ma_nhan_vien_giao);

        $res->refresh();
        $this->assertEquals('CONSUMED', $res->trang_thai);
    }

    // ─── 16. Cancel release đúng ───
    public function test_cancel_order_releases_reservation()
    {
        $hd = $this->createOrder(['trang_thai' => 'PENDING']);
        ChiTietHoaDon::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_san_pham' => $this->sanPham->ma_san_pham,
            'so_luong' => 1,
            'gia_ban_snapshot' => 50000,
            'gia_nhap_snapshot' => 30000,
        ]);

        $res = InventoryReservation::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_san_pham' => $this->sanPham->ma_san_pham,
            'so_luong' => 1,
            'trang_thai' => 'RESERVED',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('don-hang.cancel', $hd->ma_hoa_don), [
            'ly_do' => 'Hủy do hết hàng',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $hd->refresh();
        $this->assertEquals('CANCELLED', $hd->trang_thai);

        $res->refresh();
        $this->assertEquals('RELEASED', $res->trang_thai);
    }

    // ─── 17. Không double deduction stock khi confirm đơn ───
    public function test_confirm_order_does_not_double_deduct_stock()
    {
        $tonKhoGoc = $this->sanPham->so_luong;

        $hd = $this->createOrder(['trang_thai' => 'PENDING']);
        ChiTietHoaDon::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_san_pham' => $this->sanPham->ma_san_pham,
            'so_luong' => 1,
            'gia_ban_snapshot' => 50000,
            'gia_nhap_snapshot' => 30000,
        ]);

        InventoryReservation::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_san_pham' => $this->sanPham->ma_san_pham,
            'so_luong' => 1,
            'trang_thai' => 'RESERVED',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($this->admin)->postJson(route('don-hang.confirm', $hd->ma_hoa_don), [
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);

        $this->sanPham->refresh();
        // Tồn kho vật lý chỉ trừ đúng 1 lần khi checkout/reservation consumed
        $this->assertEquals($tonKhoGoc - 1, $this->sanPham->so_luong);

        // Thử confirm lại một lần nữa -> phải bị chặn, không trừ tiếp
        $secondAttempt = $this->actingAs($this->admin)->postJson(route('don-hang.confirm', $hd->ma_hoa_don), [
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);
        $this->sanPham->refresh();
        $this->assertEquals($tonKhoGoc - 1, $this->sanPham->so_luong);
    }

    // ─── 18, 19, 20, 21. Failed delivery info, HangHoan, nguồn lỗi, trạng thái hàng hoàn ───
    public function test_failed_delivery_displays_all_business_attributes()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);

        $ghtb = GiaoHangThatBai::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'loai_loi' => 'SHIPPER',
            'ly_do' => 'Shipper làm rơi vỡ bó hoa',
            'thoi_diem_bao' => now(),
            'trang_thai' => 'DA_BAO',
        ]);

        HangHoan::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_giao_hang_that_bai' => $ghtb->ma_giao_hang_that_bai,
            'trang_thai' => 'DA_NHAN_TAI_KHO',
            'thoi_diem_nhan_tai_kho' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'failed']));
        $response->assertStatus(200);
        $response->assertSee('Shipper làm rơi vỡ bó hoa');
        $response->assertSee('Đã nhận tại kho');
        $response->assertSee('Kiểm tra hàng');
    }

    // ─── 22. Link kiểm tra hàng hoạt động ───
    public function test_inspection_link_accessible_for_received_goods()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);

        $ghtb = GiaoHangThatBai::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'loai_loi' => 'CUSTOMER',
            'ly_do' => 'Khách hủy đột xuất',
            'thoi_diem_bao' => now(),
            'trang_thai' => 'DA_BAO',
        ]);

        HangHoan::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_giao_hang_that_bai' => $ghtb->ma_giao_hang_that_bai,
            'trang_thai' => 'DA_NHAN_TAI_KHO',
            'thoi_diem_nhan_tai_kho' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.hoa-don.kiem-tra-hang', $hd->ma_hoa_don));
        $response->assertStatus(200);
    }

    // ─── 23. Sau kiểm tra hiển thị kết quả ───
    public function test_completed_inspection_displays_results_and_status()
    {
        $hd = $this->createOrder([
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
        ]);

        $ghtb = GiaoHangThatBai::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'loai_loi' => 'SHIPPER',
            'ly_do' => 'Giao trễ làm hỏng hoa',
            'thoi_diem_bao' => now(),
            'trang_thai' => 'DA_BAO',
        ]);

        $hh = HangHoan::create([
            'ma_hoa_don' => $hd->ma_hoa_don,
            'ma_giao_hang_that_bai' => $ghtb->ma_giao_hang_that_bai,
            'trang_thai' => 'DA_KIEM_TRA',
            'nguoi_kiem_tra' => $this->admin->ma_nguoi_dung,
            'thoi_diem_nhan_tai_kho' => now()->subHours(2),
            'thoi_diem_kiem_tra' => now(),
        ]);

        ChiTietHangHoan::create([
            'ma_hang_hoan' => $hh->ma_hang_hoan,
            'ma_san_pham' => $this->sanPham->ma_san_pham,
            'so_luong_theo_don' => 3,
            'so_luong_con_ban_duoc' => 2,
            'so_luong_hong' => 1,
            'ket_qua' => 'DA_XU_LY',
        ]);

        $response = $this->actingAs($this->admin)->get(route('don-hang.index', ['tab' => 'failed']));
        $response->assertStatus(200);
        $response->assertSee('Đã kiểm tra');
        $response->assertSee('Xem kết quả');
    }

    // ─── 24. Backward compatibility redirect ───
    public function test_old_failed_delivery_route_redirects_to_failed_tab()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.giao-hang.that-bai'));
        $response->assertRedirect(route('don-hang.index', ['tab' => 'failed']));
    }

    // ─── 25. Security & Permission: Staff can access don-hang.index ───
    public function test_staff_can_access_order_page()
    {
        $response = $this->actingAs($this->nhanVien)->get(route('don-hang.index'));
        $response->assertStatus(200);
        $response->assertSee('Quản lý đơn hàng');
    }
}
