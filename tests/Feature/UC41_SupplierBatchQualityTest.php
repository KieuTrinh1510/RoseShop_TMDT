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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UC41_SupplierBatchQualityTest extends TestCase
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
            'ten_nha_cung_cap' => 'NCC Quality Test ' . $unique,
            'so_dien_thoai'    => '09' . rand(10000000, 99999999),
            'email'            => 'ncc_q_' . $unique . '@example.com',
            'dia_chi'          => 'Lâm Đồng, Việt Nam ' . $unique,
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ], $attributes));
    }

    protected function getOrCreateProduct(): SanPham
    {
        $sp = SanPham::first();
        if ($sp) {
            return $sp;
        }

        return SanPham::create([
            'ten_san_pham'  => 'Hoa Hồng Đỏ Test ' . time(),
            'so_luong'      => 100,
            'don_vi_tinh'   => 'CANH',
            'loai_san_pham' => 'HOA_TUOI',
            'trang_thai'    => 'DANG_BAN',
        ]);
    }

    protected function createLotForSupplier(NhaCungCap $ncc, int $quantity = 50, float $price = 15000): ChiTietNhap
    {
        $nvId = $this->getOperationalEmployeeId();
        $sp = $this->getOrCreateProduct();

        $pn = PhieuNhap::create([
            'ngay_nhap'         => now(),
            'ma_nhan_vien'      => $nvId,
            'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
            'email_ncc'         => $ncc->email,
            'dia_chi_ncc'       => $ncc->dia_chi,
            'trang_thai'        => 'CONFIRMED',
        ]);

        return ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => $quantity,
            'gia_nhap'         => $price,
            'gia_ban'          => $price * 1.5,
            'so_luong_con_lai' => $quantity,
        ]);
    }

    // =========================================================================
    // 20 TEST CASES THEO YÊU CẦU ĐẶC TẢ MỤC 23
    // =========================================================================

    // TEST 1: Từ NCC ACTIVE có thể mở Tạo Phiếu nhập và NCC được chọn sẵn
    public function test_1_active_supplier_can_preload_in_create_phieu_nhap(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();

        $response = $this->actingAs($admin)->get(route('phieu-nhap.index', ['ma_nha_cung_cap' => $ncc->ma_nha_cung_cap]));

        $response->assertStatus(200);
        $response->assertViewHas('selectedNccId', $ncc->ma_nha_cung_cap);
        $response->assertSee($ncc->ten_nha_cung_cap);
    }

    // TEST 2: NCC INACTIVE không được tạo Phiếu nhập mới (không preload)
    public function test_2_inactive_supplier_cannot_preload_create_phieu_nhap(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $inactiveNcc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC]);

        $response = $this->actingAs($admin)->get(route('phieu-nhap.index', ['ma_nha_cung_cap' => $inactiveNcc->ma_nha_cung_cap]));

        $response->assertStatus(200);
        // Không preload supplier đã ngừng hợp tác
        $response->assertViewHas('selectedNccId', null);
    }

    // TEST 3: Backend reject create Phiếu nhập cho NCC INACTIVE
    public function test_3_backend_rejects_create_phieu_nhap_for_inactive_ncc(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $inactiveNcc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC]);
        $sp = $this->getOrCreateProduct();
        $nvId = $this->getOperationalEmployeeId();

        $response = $this->actingAs($admin)->post(route('phieu-nhap.store'), [
            'ngay_nhap'       => now()->toDateTimeString(),
            'ma_nhan_vien'    => $nvId,
            'ma_nha_cung_cap' => $inactiveNcc->ma_nha_cung_cap,
            'san_pham'        => [$sp->ma_san_pham],
            'so_luong'        => [20],
            'gia_nhap'        => [10000],
        ]);

        $response->assertSessionHasErrors(['ma_nha_cung_cap']);
        $this->assertDatabaseMissing('phieu_nhap', [
            'ma_nha_cung_cap' => $inactiveNcc->ma_nha_cung_cap,
        ]);
    }

    // TEST 4: Phiếu nhập xác nhận sinh/liên kết đúng lô theo nghiệp vụ hiện tại
    public function test_4_phieu_nhap_confirmation_links_correct_lots(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 80, 20000);

        $this->assertNotNull($lot);
        $this->assertEquals(80, $lot->so_luong);
        $this->assertEquals('CONFIRMED', $lot->phieuNhap->trang_thai);
        $this->assertEquals($ncc->ma_nha_cung_cap, $lot->phieuNhap->ma_nha_cung_cap);
    }

    // TEST 5: Từ một Lô truy được: Lô → Phiếu nhập → NCC
    public function test_5_trace_from_lot_to_phieu_nhap_to_supplier(): void
    {
        $ncc = $this->createSupplier(['ten_nha_cung_cap' => 'NCC Trace Test ' . rand(100, 999)]);
        $lot = $this->createLotForSupplier($ncc, 60, 25000);

        $this->assertNotNull($lot->phieuNhap);
        $this->assertNotNull($lot->phieuNhap->nhaCungCap);
        $this->assertEquals($ncc->ma_nha_cung_cap, $lot->phieuNhap->nhaCungCap->ma_nha_cung_cap);
        $this->assertEquals($ncc->ten_nha_cung_cap, $lot->phieuNhap->nhaCungCap->ten_nha_cung_cap);
    }

    // TEST 6: Trang chi tiết NCC chỉ hiển thị các Lô thuộc NCC đó
    public function test_6_supplier_detail_only_shows_its_own_lots(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $nccA = $this->createSupplier(['ten_nha_cung_cap' => 'NCC A Lots Only ' . rand(100, 999)]);
        $nccB = $this->createSupplier(['ten_nha_cung_cap' => 'NCC B Other Lots ' . rand(100, 999)]);

        $lotA = $this->createLotForSupplier($nccA, 35, 12000);
        $lotB = $this->createLotForSupplier($nccB, 45, 18000);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $nccA->ma_nha_cung_cap));

        $response->assertStatus(200);
        $response->assertSee('#CTN-' . $lotA->ma_chi_tiet_nhap);
        $response->assertDontSee('#CTN-' . $lotB->ma_chi_tiet_nhap);
    }

    // TEST 7: Đánh giá lô DAT thành công
    public function test_7_evaluate_lot_as_dat_succeeds(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 100);

        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_DAT,
            'so_luong_loi' => 0,
            'ghi_chu'      => 'Chất lượng hoa rất tươi và đều.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('danh_gia_lo_hang', [
            'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_DAT,
            'so_luong_loi'     => 0,
        ]);
    }

    // TEST 8: Đánh giá lô CO_VAN_DE thành công
    public function test_8_evaluate_lot_as_co_van_de_succeeds(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 100);

        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_CO_VAN_DE,
            'so_luong_loi' => 5,
            'ly_do'        => 'Có một số cành hoa bị dập mép lá',
            'ghi_chu'      => 'Đã nhắc nhở bên vận chuyển của NCC',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('danh_gia_lo_hang', [
            'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_CO_VAN_DE,
            'so_luong_loi'     => 5,
            'ly_do'            => 'Có một số cành hoa bị dập mép lá',
        ]);
    }

    // TEST 9: Đánh giá lô KHONG_DAT thành công
    public function test_9_evaluate_lot_as_khong_dat_succeeds(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 100);

        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'so_luong_loi' => 40,
            'ly_do'        => 'Hoa bị héo úa và nấm mốc nghiêm trọng',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('danh_gia_lo_hang', [
            'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'so_luong_loi'     => 40,
        ]);
    }

    // TEST 10: CO_VAN_DE/KHONG_DAT thiếu lý do → validation fail
    public function test_10_missing_reason_for_issues_or_fail_validation_fails(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 50);

        // CO_VAN_DE thiếu lý do
        $res1 = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_CO_VAN_DE,
            'so_luong_loi' => 2,
            'ly_do'        => '',
        ]);
        $res1->assertSessionHasErrors(['ly_do']);

        // KHONG_DAT thiếu lý do
        $res2 = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'so_luong_loi' => 20,
            'ly_do'        => '',
        ]);
        $res2->assertSessionHasErrors(['ly_do']);
    }

    // TEST 11: Số lượng hỏng vượt số lượng lô → reject
    public function test_11_damaged_quantity_exceeding_lot_is_rejected(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 50); // Ban đầu 50

        // Đánh giá 60 lỗi (> 50)
        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'so_luong_loi' => 60,
            'ly_do'        => 'Vượt quá số lượng nhập',
        ]);

        $response->assertSessionHasErrors(['so_luong_loi']);
    }

    // TEST 12: Đánh giá không tự trừ tồn hai lần
    public function test_12_evaluation_does_not_deduct_inventory_stock(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 100);

        $this->assertEquals(100, $lot->so_luong_con_lai);

        // Đánh giá KHONG_DAT với 30 sản phẩm lỗi
        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'so_luong_loi' => 30,
            'ly_do'        => 'Hoa thối thân',
        ]);

        $response->assertSessionHas('success');

        // Tồn kho lô vẫn phải giữ nguyên 100, không bị trừ ngầm gây double deduction
        $lot->refresh();
        $this->assertEquals(100, $lot->so_luong_con_lai);
    }

    // TEST 13: NCC tổng hợp đúng số Lô
    public function test_13_supplier_summarizes_total_lots_correctly(): void
    {
        $ncc = $this->createSupplier();
        $lot1 = $this->createLotForSupplier($ncc, 20);
        $lot2 = $this->createLotForSupplier($ncc, 30);
        $lot3 = $this->createLotForSupplier($ncc, 40);

        $thongKe = $ncc->thong_ke_chat_luong;
        $this->assertEquals(3, $thongKe['tong_so_lo']);
    }

    // TEST 14: NCC tổng hợp đúng số Lô đã đánh giá
    public function test_14_supplier_summarizes_evaluated_lots_count_correctly(): void
    {
        $ncc = $this->createSupplier();
        $lot1 = $this->createLotForSupplier($ncc, 20);
        $lot2 = $this->createLotForSupplier($ncc, 30);
        $lot3 = $this->createLotForSupplier($ncc, 40);

        // Đánh giá 2 trong số 3 lô
        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lot1->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_DAT,
        ]);
        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lot2->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'ly_do'            => 'Lỗi quy cách',
        ]);

        $thongKe = $ncc->thong_ke_chat_luong;
        $this->assertEquals(3, $thongKe['tong_so_lo']);
        $this->assertEquals(2, $thongKe['so_lo_da_danh_gia']);
    }

    // TEST 15: Tính đúng: DAT / CO_VAN_DE / KHONG_DAT
    public function test_15_supplier_accurately_counts_all_quality_categories(): void
    {
        $ncc = $this->createSupplier();
        $lotDat = $this->createLotForSupplier($ncc, 10);
        $lotCvd = $this->createLotForSupplier($ncc, 20);
        $lotKd  = $this->createLotForSupplier($ncc, 30);
        $lotChua = $this->createLotForSupplier($ncc, 40);

        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lotDat->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_DAT,
        ]);
        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lotCvd->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_CO_VAN_DE,
            'ly_do'            => 'Có vấn đề nhẹ',
        ]);
        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lotKd->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'ly_do'            => 'Không đạt chuẩn',
        ]);

        $thongKe = $ncc->thong_ke_chat_luong;
        $this->assertEquals(4, $thongKe['tong_so_lo']);
        $this->assertEquals(3, $thongKe['so_lo_da_danh_gia']);
        $this->assertEquals(1, $thongKe['so_lo_dat']);
        $this->assertEquals(1, $thongKe['so_lo_co_van_de']);
        $this->assertEquals(1, $thongKe['so_lo_khong_dat']);
    }

    // TEST 16: Tỷ lệ không đạt dùng: KHONG_DAT / số lô đã đánh giá
    public function test_16_failure_rate_uses_khong_dat_divided_by_evaluated_lots(): void
    {
        $ncc = $this->createSupplier();
        $lot1 = $this->createLotForSupplier($ncc, 10);
        $lot2 = $this->createLotForSupplier($ncc, 20);
        $lot3 = $this->createLotForSupplier($ncc, 30);
        $lot4 = $this->createLotForSupplier($ncc, 40); // Chưa đánh giá

        // Đánh giá 3 lô: 1 KHONG_DAT, 2 DAT
        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lot1->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'ly_do'            => 'Hoa hỏng',
        ]);
        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lot2->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_DAT,
        ]);
        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lot3->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_DAT,
        ]);

        $thongKe = $ncc->thong_ke_chat_luong;
        // 1 / 3 = 33.33% (KHÔNG lấy 1 / 4 = 25%)
        $this->assertEquals(33.33, $thongKe['ty_le_khong_dat']);
    }

    // TEST 17: Lô chưa đánh giá không bị coi là Lô đạt
    public function test_17_unevaluated_lots_are_not_counted_as_passed(): void
    {
        $ncc = $this->createSupplier();
        $lot1 = $this->createLotForSupplier($ncc, 10);
        $lot2 = $this->createLotForSupplier($ncc, 20);

        $thongKe = $ncc->thong_ke_chat_luong;
        $this->assertEquals(2, $thongKe['tong_so_lo']);
        $this->assertEquals(0, $thongKe['so_lo_da_danh_gia']);
        $this->assertEquals(0, $thongKe['so_lo_dat']); // Tuyệt đối không tính là Đạt
        $this->assertNull($thongKe['ty_le_khong_dat']); // Chưa đủ dữ liệu, không ghi 0%
    }

    // TEST 18: NCC ngừng hợp tác vẫn xem được Lô và đánh giá cũ
    public function test_18_inactive_supplier_can_still_view_lots_and_evaluations(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC]);
        $lot = $this->createLotForSupplier($ncc, 50);

        DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'ly_do'            => 'Lý do cũ khi còn hợp tác',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));

        $response->assertStatus(200);
        $response->assertSee('Ngừng hợp tác');
        $response->assertSee('#CTN-' . $lot->ma_chi_tiet_nhap);
        $response->assertSee('Không đạt');
    }

    // TEST 19: Ngừng hợp tác không xóa Phiếu nhập/Lô/đánh giá
    public function test_19_deactivating_supplier_preserves_all_lots_and_evaluations(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_test');
        $ncc = $this->createSupplier(['trang_thai' => NhaCungCap::TRANG_THAI_DANG_HOP_TAC]);
        $lot = $this->createLotForSupplier($ncc, 75);

        $dg = DanhGiaLoHang::create([
            'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
            'ket_qua'          => DanhGiaLoHang::KET_QUA_DAT,
        ]);

        // Thực hiện ngừng hợp tác từ trang chi tiết
        $response = $this->actingAs($admin)
            ->from(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap))
            ->patch(route('admin.nha-cung-cap.status', $ncc->ma_nha_cung_cap), [
                'trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC,
            ]);

        $response->assertRedirect(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));

        // Kiểm tra record vẫn còn nguyên vẹn trong Database
        $this->assertDatabaseHas('nha_cung_cap', [
            'ma_nha_cung_cap' => $ncc->ma_nha_cung_cap,
            'trang_thai'      => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC,
        ]);
        $this->assertDatabaseHas('phieu_nhap', [
            'ma_phieu_nhap' => $lot->ma_phieu_nhap,
        ]);
        $this->assertDatabaseHas('chi_tiet_nhap', [
            'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
        ]);
        $this->assertDatabaseHas('danh_gia_lo_hang', [
            'ma_danh_gia' => $dg->ma_danh_gia,
        ]);
    }

    // TEST 20: User không có quyền không được đánh giá Lô
    public function test_20_unauthorized_user_cannot_evaluate_lots(): void
    {
        $ncc = $this->createSupplier();
        $lot = $this->createLotForSupplier($ncc, 50);

        // Khách hàng
        $customer = $this->createUser('KHACH_HANG', 'customer_test');
        $resCust = $this->actingAs($customer)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua' => 'DAT',
        ]);
        $resCust->assertStatus(403);

        // Shipper
        $shipper = $this->createUser('SHIPPER', 'shipper_test');
        $resShip = $this->actingAs($shipper)->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua' => 'DAT',
        ]);
        $resShip->assertStatus(403);

        // Guest -> redirect login
        \Illuminate\Support\Facades\Auth::logout();
        $resGuest = $this->post(route('admin.lo-hang.danh-gia', $lot->ma_chi_tiet_nhap), [
            'ket_qua' => 'DAT',
        ]);
        $resGuest->assertRedirect(route('login'));
    }
}
