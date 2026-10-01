<?php

namespace Tests\Feature;

use App\Models\ChiTietNhap;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UC41_SupplierManagementTest extends TestCase
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

        $user = $this->createUser('NHAN_VIEN', 'operator');
        return $user->ma_nguoi_dung;
    }

    protected function createSupplier(array $attributes = []): NhaCungCap
    {
        $unique = time() . '_' . rand(1000, 9999);
        return NhaCungCap::create(array_merge([
            'ten_nha_cung_cap' => 'NCC Test ' . $unique,
            'so_dien_thoai'    => '09' . rand(10000000, 99999999),
            'email'            => 'ncc_' . $unique . '@example.com',
            'dia_chi'          => 'Địa chỉ NCC ' . $unique,
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ], $attributes));
    }

    // 1. Admin mở danh sách NCC thành công
    public function test_1_admin_opens_supplier_list_successfully(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.NhaCungCap.index');
        $response->assertViewHas('nhaCungCaps');
        $response->assertSee('Quản lý nhà cung cấp');
    }

    // 2. Nhân viên có quyền mở được
    public function test_2_employee_opens_supplier_list_successfully(): void
    {
        $nhanVien = $this->createUser('NHAN_VIEN', 'staff_test');

        $response = $this->actingAs($nhanVien)->get(route('admin.nha-cung-cap'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.NhaCungCap.index');
        $response->assertViewHas('nhaCungCaps');
    }

    // 3. User không có quyền → 403 hoặc behavior hiện tại
    public function test_3_unauthorized_user_is_forbidden(): void
    {
        $customer = $this->createUser('KHACH_HANG', 'customer_test');
        $response = $this->actingAs($customer)->get(route('admin.nha-cung-cap'));
        $response->assertStatus(403);

        $shipper = $this->createUser('SHIPPER', 'shipper_test');
        $response = $this->actingAs($shipper)->get(route('admin.nha-cung-cap'));
        $response->assertStatus(403);

        // Guest truy cập -> chuyển hướng sang login
        \Illuminate\Support\Facades\Auth::logout();
        $guestResponse = $this->get(route('admin.nha-cung-cap'));
        $guestResponse->assertRedirect(route('login'));
    }

    // 4. Thêm NCC hợp lệ → thành công
    public function test_4_store_valid_supplier_succeeds(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $uniqueName = 'Công Ty Hoa Lan Rừng ' . time() . '_' . rand(100, 999);
        $data = [
            'ten_nha_cung_cap' => $uniqueName,
            'so_dien_thoai'    => '0987654321',
            'email'            => 'hoalan@example.com',
            'dia_chi'          => 'Bảo Lộc, Lâm Đồng',
        ];

        $response = $this->actingAs($admin)->post(route('admin.nha-cung-cap.store'), $data);

        $ncc = NhaCungCap::where('ten_nha_cung_cap', $uniqueName)->first();
        $this->assertNotNull($ncc);
        $this->assertEquals(NhaCungCap::TRANG_THAI_DANG_HOP_TAC, $ncc->trang_thai);
        $response->assertRedirect(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $response->assertSessionHas('success');
    }

    // 5. Thêm NCC invalid → không lưu
    public function test_5_store_invalid_supplier_fails(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');

        // Thiếu tên
        $response = $this->actingAs($admin)->post(route('admin.nha-cung-cap.store'), [
            'ten_nha_cung_cap' => '',
            'so_dien_thoai'    => '0987654321',
        ]);
        $response->assertSessionHasErrors(['ten_nha_cung_cap']);

        // SĐT sai format (không đủ 10 số hoặc không bắt đầu bằng số 0)
        $response2 = $this->actingAs($admin)->post(route('admin.nha-cung-cap.store'), [
            'ten_nha_cung_cap' => 'NCC Sai SĐT',
            'so_dien_thoai'    => '12345',
        ]);
        $response2->assertSessionHasErrors(['so_dien_thoai']);

        // Email sai format
        $response3 = $this->actingAs($admin)->post(route('admin.nha-cung-cap.store'), [
            'ten_nha_cung_cap' => 'NCC Sai Email',
            'email'            => 'invalid-email-format',
        ]);
        $response3->assertSessionHasErrors(['email']);
    }

    // 6. Search theo tên → đúng
    public function test_6_search_supplier_by_name(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $nccA = $this->createSupplier(['ten_nha_cung_cap' => 'NhaCungCap_DocNhat_SearchName']);
        $nccB = $this->createSupplier(['ten_nha_cung_cap' => 'NhaCungCap_KhacBiet_KhongKhop']);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap', ['keyword' => 'DocNhat_SearchName']));

        $response->assertStatus(200);
        $response->assertSee('NhaCungCap_DocNhat_SearchName');
        $response->assertDontSee('NhaCungCap_KhacBiet_KhongKhop');
    }

    // 7. Search theo SĐT/email → đúng
    public function test_7_search_supplier_by_phone_and_email(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $nccA = $this->createSupplier([
            'ten_nha_cung_cap' => 'NCC A Search',
            'so_dien_thoai'    => '0919888999',
            'email'            => 'unique_email_search@domain.com',
        ]);

        // Search theo SĐT
        $resPhone = $this->actingAs($admin)->get(route('admin.nha-cung-cap', ['keyword' => '0919888999']));
        $resPhone->assertStatus(200);
        $resPhone->assertSee('NCC A Search');

        // Search theo Email
        $resEmail = $this->actingAs($admin)->get(route('admin.nha-cung-cap', ['keyword' => 'unique_email_search@domain.com']));
        $resEmail->assertStatus(200);
        $resEmail->assertSee('NCC A Search');
    }

    // 8. Xem chi tiết NCC → đúng dữ liệu
    public function test_8_show_supplier_detail(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier([
            'ten_nha_cung_cap' => 'NCC Chi Tiết Cụ Thể',
            'so_dien_thoai'    => '0901234567',
            'email'            => 'chitiet@ncc.com',
            'dia_chi'          => '123 Đường Hoa Hồng, TP.HCM',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));

        $response->assertStatus(200);
        $response->assertViewIs('admin.NhaCungCap.show');
        $response->assertSee('NCC Chi Tiết Cụ Thể');
        $response->assertSee('0901234567');
        $response->assertSee('chitiet@ncc.com');
        $response->assertSee('123 Đường Hoa Hồng, TP.HCM');
        $response->assertSee('Đang hợp tác');
    }

    // 9. Sửa NCC → cập nhật đúng
    public function test_9_update_supplier_succeeds(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier(['ten_nha_cung_cap' => 'Tên Ban Đầu']);

        $response = $this->actingAs($admin)->put(route('admin.nha-cung-cap.update', $ncc->ma_nha_cung_cap), [
            'ten_nha_cung_cap' => 'Tên Đã Được Sửa Mới',
            'so_dien_thoai'    => '0911223344',
            'email'            => 'newemail@domain.com',
            'dia_chi'          => 'Địa chỉ mới cập nhật',
        ]);

        $response->assertRedirect(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $ncc->refresh();
        $this->assertEquals('Tên Đã Được Sửa Mới', $ncc->ten_nha_cung_cap);
        $this->assertEquals('0911223344', $ncc->so_dien_thoai);
        $this->assertEquals('newemail@domain.com', $ncc->email);
        $this->assertEquals('Địa chỉ mới cập nhật', $ncc->dia_chi);
    }

    // 10. Ngừng hợp tác → status đổi nhưng record còn tồn tại
    public function test_10_deactivate_supplier_changes_status_without_deleting_record(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_DANG_HOP_TAC]);

        $response = $this->actingAs($admin)->patch(route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap), [
            'trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC,
        ]);

        $ncc->refresh();
        $this->assertEquals(NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC, $ncc->trang_thai);
        $this->assertDatabaseHas('nha_cung_cap', ['ma_nha_cung_cap' => $ncc->ma_nha_cung_cap]);
    }

    // 11. NCC inactive vẫn xem được lịch sử Phiếu nhập
    public function test_11_inactive_supplier_still_shows_import_history(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC]);

        // Tạo 1 phiếu nhập gắn với NCC này
        $pn = PhieuNhap::create([
            'ngay_nhap'         => now(),
            'ma_nhan_vien'      => $this->getOperationalEmployeeId(),
            'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
            'trang_thai'        => 'CONFIRMED',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));

        $response->assertStatus(200);
        $response->assertSee('#' . $pn->ma_phieu_nhap);
        $response->assertSee('Ngừng hợp tác');
    }

    // 12. Kích hoạt lại → ACTIVE
    public function test_12_reactivate_supplier_changes_status_to_active(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC]);

        $response = $this->actingAs($admin)->patch(route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap), [
            'trang_thai' => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $ncc->refresh();
        $this->assertEquals(NhaCungCap::TRANG_THAI_DANG_HOP_TAC, $ncc->trang_thai);
    }

    // 13. NCC inactive không xuất hiện khi tạo Phiếu nhập mới
    public function test_13_inactive_supplier_does_not_appear_in_phieu_nhap_create_dropdown(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $inactiveNcc = $this->createSupplier([
            'ten_nha_cung_cap' => 'NCC_Da_Ngung_Hop_Tac_Khong_Hien',
            'trang_thai'       => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC,
        ]);

        $response = $this->actingAs($admin)->get(route('phieu-nhap.index'));

        $response->assertStatus(200);
        $viewSuppliers = $response->viewData('danhSachNhaCungCap');
        $this->assertFalse($viewSuppliers->contains('ma_nha_cung_cap', $inactiveNcc->ma_nha_cung_cap));
        $response->assertDontSee('NCC_Da_Ngung_Hop_Tac_Khong_Hien');
    }

    // 14. Gửi request thủ công với NCC inactive → backend không cho tạo Phiếu nhập
    public function test_14_creating_phieu_nhap_with_inactive_supplier_is_rejected_by_backend(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $inactiveNcc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC]);
        $sp = SanPham::first() ?? SanPham::create([
            'ten_san_pham'  => 'Sản phẩm test',
            'loai_san_pham' => 'HOA_TUOI',
            'don_vi_tinh'   => 'CANH',
            'so_luong'      => 0,
            'trang_thai'    => 'NGUNG_BAN',
        ]);

        $postData = [
            'ngay_nhap'       => now()->format('Y-m-d'),
            'ma_nhan_vien'    => $this->getOperationalEmployeeId(),
            'ma_nha_cung_cap' => $inactiveNcc->ma_nha_cung_cap,
            'san_pham'        => [$sp->ma_san_pham],
            'so_luong'        => [10],
            'gia_nhap'        => [50000],
        ];

        $response = $this->actingAs($admin)->post(route('phieu-nhap.store'), $postData);

        $response->assertSessionHasErrors(['ma_nha_cung_cap']);
        $this->assertDatabaseMissing('phieu_nhap', [
            'ma_nha_cung_cap' => $inactiveNcc->ma_nha_cung_cap,
        ]);
    }

    // 15. NCC active xuất hiện khi tạo Phiếu nhập
    public function test_15_active_supplier_appears_in_phieu_nhap_create_dropdown(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $activeNcc = $this->createSupplier([
            'ten_nha_cung_cap' => 'NCC_Dang_Hop_Tac_Duoc_Chon',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $response = $this->actingAs($admin)->get(route('phieu-nhap.index'));

        $response->assertStatus(200);
        $viewSuppliers = $response->viewData('danhSachNhaCungCap');
        $this->assertTrue($viewSuppliers->contains('ma_nha_cung_cap', $activeNcc->ma_nha_cung_cap));
        $response->assertSee('NCC_Dang_Hop_Tac_Duoc_Chon');
    }

    // 16. Phiếu nhập mới liên kết đúng NCC và lưu snapshot
    public function test_16_new_phieu_nhap_links_to_supplier_and_stores_snapshot(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $activeNcc = $this->createSupplier([
            'ten_nha_cung_cap' => 'NCC Mới Tạo Phiếu',
            'so_dien_thoai'    => '0933112233',
            'email'            => 'ncc_phieu@example.com',
            'dia_chi'          => 'Đà Lạt',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $sp = SanPham::first() ?? SanPham::create([
            'ten_san_pham'  => 'Hoa Cúc Mẫu Đơn Test',
            'loai_san_pham' => 'HOA_TUOI',
            'don_vi_tinh'   => 'CANH',
            'so_luong'      => 0,
            'trang_thai'    => 'NGUNG_BAN',
        ]);

        $postData = [
            'ngay_nhap'       => now()->format('Y-m-d'),
            'ma_nhan_vien'    => $this->getOperationalEmployeeId(),
            'ma_nha_cung_cap' => $activeNcc->ma_nha_cung_cap,
            'san_pham'        => [$sp->ma_san_pham],
            'so_luong'        => [20],
            'gia_nhap'        => [35000],
        ];

        $response = $this->actingAs($admin)->post(route('phieu-nhap.store'), $postData);
        $response->assertRedirect(route('phieu-nhap.index'));

        $pn = PhieuNhap::where('ma_nha_cung_cap', $activeNcc->ma_nha_cung_cap)
            ->latest('ma_phieu_nhap')
            ->first();

        $this->assertNotNull($pn);
        $this->assertEquals($activeNcc->ma_nha_cung_cap, $pn->ma_nha_cung_cap);
        $this->assertEquals('NCC Mới Tạo Phiếu', $pn->ten_nha_cung_cap);
        $this->assertEquals('0933112233', $pn->so_dien_thoai_ncc);
        $this->assertEquals('ncc_phieu@example.com', $pn->email_ncc);
        $this->assertEquals('Đà Lạt', $pn->dia_chi_ncc);
    }

    // 17. Lịch sử NCC chỉ chứa Phiếu nhập của NCC đó
    public function test_17_supplier_history_only_contains_its_own_phieu_nhap(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc1 = $this->createSupplier(['ten_nha_cung_cap' => 'NCC 1 History']);
        $ncc2 = $this->createSupplier(['ten_nha_cung_cap' => 'NCC 2 History']);

        $empId = $this->getOperationalEmployeeId();
        $pn1 = PhieuNhap::create([
            'ngay_nhap'       => now(),
            'ma_nhan_vien'    => $empId,
            'ma_nha_cung_cap' => $ncc1->ma_nha_cung_cap,
            'trang_thai'      => 'CONFIRMED',
        ]);

        $pn2 = PhieuNhap::create([
            'ngay_nhap'       => now(),
            'ma_nhan_vien'    => $empId,
            'ma_nha_cung_cap' => $ncc2->ma_nha_cung_cap,
            'trang_thai'      => 'CONFIRMED',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc1->ma_nha_cung_cap));

        $historyPns = $response->viewData('phieuNhaps');
        $this->assertTrue($historyPns->contains('ma_phieu_nhap', $pn1->ma_phieu_nhap));
        $this->assertFalse($historyPns->contains('ma_phieu_nhap', $pn2->ma_phieu_nhap));
    }

    // 18. Phiếu nhập cũ vẫn hiển thị NCC sau migration/refactor
    public function test_18_old_phieu_nhap_displays_supplier_after_migration(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');

        // Lấy phiếu nhập cũ #2 đã tồn tại sẵn trong database
        $oldPn = PhieuNhap::with('nhaCungCap')->find(2);
        $this->assertNotNull($oldPn);
        $this->assertNotNull($oldPn->ma_nha_cung_cap);
        $this->assertEquals('NCC Hoa Trung Quoc', $oldPn->ten_nha_cung_cap);

        $response = $this->actingAs($admin)->get(route('phieu-nhap.index'));
        $response->assertStatus(200);
        $response->assertSee('NCC Hoa Trung Quoc');
    }

    // 19. Bộ lọc “Tìm NCC” hiện tại vẫn hoạt động
    public function test_19_phieu_nhap_search_by_supplier_still_works(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');

        // Tìm kiếm theo "Lâm Đồng"
        $response = $this->actingAs($admin)->get(route('phieu-nhap.index', ['tim_ncc' => 'Lâm Đồng']));
        $response->assertStatus(200);
        $response->assertSee('NCC hoa Lâm Đồng');
    }

    // 20. Thay đổi thông tin NCC không làm mất lịch sử Phiếu nhập
    public function test_20_updating_supplier_does_not_corrupt_or_overwrite_past_phieu_nhap_snapshots(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier([
            'ten_nha_cung_cap' => 'NCC Snapshot Test',
            'so_dien_thoai'    => '0901112222',
            'email'            => 'snapshot@ncc.com',
            'dia_chi'          => 'Địa chỉ cũ',
        ]);

        $pn = PhieuNhap::create([
            'ngay_nhap'         => now(),
            'ma_nhan_vien'      => $this->getOperationalEmployeeId(),
            'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap'  => 'NCC Snapshot Test',
            'so_dien_thoai_ncc' => '0901112222',
            'email_ncc'         => 'snapshot@ncc.com',
            'dia_chi_ncc'       => 'Địa chỉ cũ',
            'trang_thai'        => 'CONFIRMED',
        ]);

        // Cập nhật thông tin NCC sang thông tin mới
        $this->actingAs($admin)->put(route('admin.nha-cung-cap.update', $ncc->ma_nha_cung_cap), [
            'ten_nha_cung_cap' => 'NCC Đã Đổi Tên Thành Công',
            'so_dien_thoai'    => '0999888777',
            'email'            => 'new_snapshot@ncc.com',
            'dia_chi'          => 'Địa chỉ mới 2026',
        ]);

        // Kiểm tra phiếu nhập cũ: snapshot vẫn giữ nguyên!
        $pn->refresh();
        $this->assertEquals($ncc->ma_nha_cung_cap, $pn->ma_nha_cung_cap);
        $this->assertEquals('NCC Snapshot Test', $pn->ten_nha_cung_cap);
        $this->assertEquals('0901112222', $pn->so_dien_thoai_ncc);
        $this->assertEquals('snapshot@ncc.com', $pn->email_ncc);
        $this->assertEquals('Địa chỉ cũ', $pn->dia_chi_ncc);

        // Nhưng quan hệ nhaCungCap phản ánh đúng thông tin hiện tại
        $this->assertEquals('NCC Đã Đổi Tên Thành Công', $pn->nhaCungCap->ten_nha_cung_cap);
    }
}
