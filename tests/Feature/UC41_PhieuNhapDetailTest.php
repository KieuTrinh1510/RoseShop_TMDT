<?php

namespace Tests\Feature;

use App\Models\BaoCaoHangHong;
use App\Models\ChiTietNhap;
use App\Models\DanhGiaLoHang;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UC41_PhieuNhapDetailTest extends TestCase
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
    }

    protected function createUser(string $role, string $prefix = 'user'): NguoiDung
    {
        $unique = time() . '_' . rand(1000, 9999);
        $user = NguoiDung::create([
            'ten_dang_nhap'     => $prefix . '_' . $unique,
            'mat_khau'          => 'password123',
            'vai_tro'           => $role,
            'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
            'email_verified_at' => now(),
        ]);

        if (in_array($role, ['NHAN_VIEN', 'SHIPPER'])) {
            NhanVien::create([
                'ma_nhan_vien'  => $user->ma_nguoi_dung,
                'ten_nhan_vien' => 'Nhân viên ' . $unique,
                'chuc_vu'       => $role === 'SHIPPER' ? 'SHIPPER' : 'VAN_HANH',
                'email'         => $prefix . '_' . $unique . '@example.com',
                'so_dien_thoai' => '09' . rand(10000000, 99999999),
                'luong'         => 10000000,
            ]);
        }

        return $user;
    }

    protected function getOperationalEmployeeId(): int
    {
        $nv = NhanVien::where('chuc_vu', 'VAN_HANH')->first();
        if ($nv) {
            return $nv->ma_nhan_vien;
        }

        $user = $this->createUser('NHAN_VIEN', 'staff_op');
        return $user->ma_nguoi_dung;
    }

    protected function createProduct(string $name, float $price = 50000): SanPham
    {
        return SanPham::create([
            'ten_san_pham'  => $name . '_' . time() . '_' . rand(100, 999),
            'so_luong'      => 0,
            'don_vi_tinh'   => SanPham::DVT_CANH,
            'loai_san_pham' => SanPham::LOAI_HOA_TUOI,
            'mo_ta'         => 'Test product',
            'trang_thai'    => 'DANG_BAN',
        ]);
    }

    /**
     * Test 1: Từ trang NCC click Phiếu nhập đúng route.
     */
    public function test_1_supplier_detail_history_links_to_phieu_nhap_show()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();

        $ncc = NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Dalat Hasfarm ' . rand(100, 999),
            'so_dien_thoai'    => '0912345678',
            'email'            => 'dalat_' . rand(100, 999) . '@example.com',
            'dia_chi'          => 'Đà Lạt, Lâm Đồng',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ma_nha_cung_cap'  => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap' => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc'=> $ncc->so_dien_thoai,
            'email_ncc'        => $ncc->email,
            'dia_chi_ncc'      => $ncc->dia_chi,
            'trang_thai'       => 'CONFIRMED',
        ]);

        $res = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $res->assertStatus(200);
        $res->assertSee(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertSee('Xem chi tiết');
    }

    /**
     * Test 2: Trang chi tiết hiển thị đúng Phiếu nhập.
     */
    public function test_2_phieu_nhap_detail_page_loads_correct_phieu_nhap()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'Vườn Hoa Minh Tâm Snapshot',
            'so_dien_thoai_ncc'=> '0987654321',
            'email_ncc'        => 'minhtam@example.com',
            'dia_chi_ncc'      => 'Vạn Thành, Đà Lạt',
            'trang_thai'       => 'CONFIRMED',
        ]);

        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee('Chi tiết phiếu nhập #PN-' . $pn->ma_phieu_nhap);
        $res->assertSee('Đã xác nhận');
    }

    /**
     * Test 3: Hiển thị đúng snapshot NCC.
     */
    public function test_3_phieu_nhap_detail_displays_correct_ncc_snapshot()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC Snapshot Tên Độc Bản ' . rand(100, 999),
            'so_dien_thoai_ncc'=> '0933999888',
            'email_ncc'        => 'snapshot_unique@example.com',
            'dia_chi_ncc'      => '123 Đường Snapshot Lịch Sử',
            'trang_thai'       => 'DRAFT',
        ]);

        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee($pn->ten_nha_cung_cap);
        $res->assertSee('0933999888');
        $res->assertSee('snapshot_unique@example.com');
        $res->assertSee('123 Đường Snapshot Lịch Sử');
        $res->assertSee('Đang tạo (Draft)');
    }

    /**
     * Test 4 & 5: Hiển thị đúng các Chi tiết nhập của Phiếu đó, không hiển thị của Phiếu khác.
     */
    public function test_4_and_5_displays_only_its_own_items_and_not_other_phieu_items()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();

        $sp1 = $this->createProduct('Hoa Hồng Đỏ PN1');
        $sp2 = $this->createProduct('Hoa Cúc Trắng PN2');

        $pn1 = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC 1',
            'trang_thai'       => 'CONFIRMED',
        ]);

        $ct1 = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn1->ma_phieu_nhap,
            'ma_san_pham'      => $sp1->ma_san_pham,
            'so_luong'         => 80,
            'gia_nhap'         => 25000,
            'gia_ban'          => 37500,
            'so_luong_con_lai' => 80,
        ]);

        $pn2 = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC 2',
            'trang_thai'       => 'CONFIRMED',
        ]);

        $ct2 = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn2->ma_phieu_nhap,
            'ma_san_pham'      => $sp2->ma_san_pham,
            'so_luong'         => 150,
            'gia_nhap'         => 12000,
            'gia_ban'          => 18000,
            'so_luong_con_lai' => 150,
        ]);

        $res1 = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn1->ma_phieu_nhap));
        $res1->assertStatus(200);
        $res1->assertSee($sp1->ten_san_pham);
        $res1->assertSee('#CTN-' . $ct1->ma_chi_tiet_nhap);
        $res1->assertDontSee($sp2->ten_san_pham);
        $res1->assertDontSee('#CTN-' . $ct2->ma_chi_tiet_nhap);
    }

    /**
     * Test 6 & 7: Tổng số lượng và Tổng giá trị tính đúng từ chi tiết nhập thực tế.
     */
    public function test_6_and_7_totals_calculated_correctly()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();

        $spA = $this->createProduct('Hoa Lan Hồ Điệp');
        $spB = $this->createProduct('Hoa Cẩm Tú Cầu');

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC Tổng Kết Test',
            'trang_thai'       => 'CONFIRMED',
        ]);

        // Item 1: 50 * 30,000 = 1,500,000
        ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $spA->ma_san_pham,
            'so_luong'         => 50,
            'gia_nhap'         => 30000,
            'gia_ban'          => 45000,
            'so_luong_con_lai' => 45,
        ]);

        // Item 2: 70 * 20,000 = 1,400,000
        ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $spB->ma_san_pham,
            'so_luong'         => 70,
            'gia_nhap'         => 20000,
            'gia_ban'          => 30000,
            'so_luong_con_lai' => 70,
        ]);

        // Total qty = 120, Total value = 2,900,000 đ
        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee('120'); // Total quantity
        $res->assertSee('2.900.000 đ'); // Total amount
    }

    /**
     * Test 8: Hiển thị đúng lô tương ứng và số lượng còn lại.
     */
    public function test_8_displays_correct_lot_and_remaining_quantity()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();
        $sp = $this->createProduct('Hoa Ly Vàng');

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC Test Lot',
            'trang_thai'       => 'CONFIRMED',
        ]);

        $ct = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => 100,
            'gia_nhap'         => 40000,
            'gia_ban'          => 60000,
            'so_luong_con_lai' => 37,
        ]);

        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee('#CTN-' . $ct->ma_chi_tiet_nhap);
        $res->assertSee('37');
    }

    /**
     * Test 9: Lô chưa đánh giá hiển thị "Chưa đánh giá", lô có đánh giá hiển thị kết quả.
     */
    public function test_9_lot_quality_evaluation_status_displays_accurately()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();
        $sp1 = $this->createProduct('Hoa Tulip Trắng');
        $sp2 = $this->createProduct('Hoa Baby Hồng');

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC QC Test',
            'trang_thai'       => 'CONFIRMED',
        ]);

        // Lot 1: Chưa đánh giá
        $ct1 = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp1->ma_san_pham,
            'so_luong'         => 50,
            'gia_nhap'         => 35000,
            'gia_ban'          => 50000,
            'so_luong_con_lai' => 50,
        ]);

        // Lot 2: Đã đánh giá ĐẠT
        $ct2 = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp2->ma_san_pham,
            'so_luong'         => 60,
            'gia_nhap'         => 15000,
            'gia_ban'          => 25000,
            'so_luong_con_lai' => 60,
        ]);

        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap'   => $ct2->ma_chi_tiet_nhap,
            'ma_nhan_vien'       => $employeeId,
            'ket_qua'            => 'DAT',
            'so_luong_loi'       => 0,
            'thoi_gian_danh_gia' => now(),
        ]);

        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee('Chưa đánh giá');
        $res->assertSee('Đạt');
    }

    /**
     * Test 10: Link "Xem lô" đi đúng lô tới route admin.lo-hang.show.
     */
    public function test_10_view_lot_button_links_to_lo_hang_show()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();
        $sp = $this->createProduct('Hoa Cúc Calimero');

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC Link Lot Test',
            'trang_thai'       => 'CONFIRMED',
        ]);

        $ct = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => 200,
            'gia_nhap'         => 8000,
            'gia_ban'          => 12000,
            'so_luong_con_lai' => 200,
        ]);

        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee(route('admin.lo-hang.show', $ct->ma_chi_tiet_nhap));
        $res->assertSee('Xem lô');
    }

    /**
     * Test 11: User không đủ quyền không được truy cập.
     */
    public function test_11_unauthorized_user_forbidden()
    {
        $employeeId = $this->getOperationalEmployeeId();
        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC Auth Test',
            'trang_thai'       => 'CONFIRMED',
        ]);

        // Guest -> redirect to login
        $guestRes = $this->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $guestRes->assertRedirect(route('login'));

        // Customer (KHACH_HANG) -> 403 Forbidden
        $customer = $this->createUser('KHACH_HANG', 'customer_user');
        $custRes = $this->actingAs($customer)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $custRes->assertStatus(403);

        // Shipper -> 403 Forbidden
        $shipper = $this->createUser('SHIPPER', 'shipper_user');
        $shipRes = $this->actingAs($shipper)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $shipRes->assertStatus(403);

        // Nhan vien -> 200 OK
        $employee = $this->createUser('NHAN_VIEN', 'employee_user');
        $empRes = $this->actingAs($employee)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $empRes->assertStatus(200);
    }

    /**
     * Test 12: Phiếu nhập không tồn tại trả về 404.
     */
    public function test_12_non_existent_phieu_nhap_returns_404()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', 99999999));
        $res->assertStatus(404);
    }

    /**
     * Test 13: Phiếu nhập chưa có mặt hàng hiển thị empty state an toàn.
     */
    public function test_13_empty_items_displays_empty_state()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ten_nha_cung_cap' => 'NCC Rỗng Hàng',
            'trang_thai'       => 'DRAFT',
        ]);

        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee('Phiếu nhập chưa có mặt hàng.');
    }

    /**
     * Test 14: Phiếu nhập có liên kết NCC có link xem hồ sơ NCC và quay lại NCC.
     */
    public function test_14_linked_ncc_displays_profile_link()
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $employeeId = $this->getOperationalEmployeeId();

        $ncc = NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Liên Kết ' . rand(100, 999),
            'so_dien_thoai'    => '0988776655',
            'email'            => 'lienket_' . rand(100, 999) . '@example.com',
            'dia_chi'          => 'Đà Lạt',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $pn = PhieuNhap::create([
            'ngay_nhap'        => now(),
            'ma_nhan_vien'     => $employeeId,
            'ma_nha_cung_cap'  => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap' => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc'=> $ncc->so_dien_thoai,
            'email_ncc'        => $ncc->email,
            'dia_chi_ncc'      => $ncc->dia_chi,
            'trang_thai'       => 'CONFIRMED',
        ]);

        $res = $this->actingAs($admin)->get(route('phieu-nhap.show', $pn->ma_phieu_nhap));
        $res->assertStatus(200);
        $res->assertSee(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $res->assertSee('Quay lại Nhà cung cấp');
        $res->assertSee('Xem hồ sơ NCC');
    }
}
