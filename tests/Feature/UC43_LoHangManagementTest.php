<?php

namespace Tests\Feature;

use App\Models\BaoCaoHangHong;
use App\Models\ChiTietHoaDon;
use App\Models\ChiTietNhap;
use App\Models\DanhGiaLoHang;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UC43_LoHangManagementTest extends TestCase
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
        $unique = microtime(true) . '_' . mt_rand(100000, 999999);
        $user = NguoiDung::create([
            'ten_dang_nhap'     => substr($prefix . '_' . $unique, 0, 50),
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

    protected function createProduct(string $name = 'Hoa Cúc Tana', int $initialStock = 10): SanPham
    {
        return SanPham::create([
            'ten_san_pham'     => $name . '_' . time() . '_' . rand(100, 999),
            'so_luong'         => $initialStock,
            'don_vi_tinh'      => SanPham::DVT_CANH,
            'loai_san_pham'    => SanPham::LOAI_HOA_TUOI,
            'mo_ta'            => 'Sản phẩm kiểm thử UC4.3',
            'gia_ban_hien_tai' => 25000,
            'trang_thai'       => 'DANG_BAN',
        ]);
    }

    protected function createPhieuNhapWithLot(string $trangThai = PhieuNhap::TRANG_THAI_CONFIRMED, int $qty = 50, ?NhaCungCap $customNcc = null): array
    {
        $employeeId = $this->getOperationalEmployeeId();
        $ncc = $customNcc ?? NhaCungCap::create([
            'ten_nha_cung_cap' => 'NCC Đà Lạt Farm ' . rand(100, 999),
            'so_dien_thoai'    => '0988776655',
            'email'            => 'dalat_' . rand(100, 999) . '@test.com',
            'dia_chi'          => 'Đà Lạt, Lâm Đồng',
            'trang_thai'       => NhaCungCap::TRANG_THAI_DANG_HOP_TAC,
        ]);

        $sp = $this->createProduct('Hoa Baby Trắng', 20);

        $pn = PhieuNhap::create([
            'ngay_nhap'         => now(),
            'ma_nhan_vien'      => $employeeId,
            'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
            'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
            'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
            'email_ncc'         => $ncc->email,
            'dia_chi_ncc'       => $ncc->dia_chi,
            'trang_thai'        => $trangThai,
            'tong_tien'         => $qty * 15000,
        ]);

        $ctn = ChiTietNhap::create([
            'ma_phieu_nhap'    => $pn->ma_phieu_nhap,
            'ma_san_pham'      => $sp->ma_san_pham,
            'so_luong'         => $qty,
            'gia_nhap'         => 15000,
            'gia_ban'          => 25000,
            'so_luong_con_lai' => $qty,
        ]);

        return [$pn, $ctn, $sp, $ncc];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 1: Phiếu DRAFT không xuất hiện trong danh sách lô
    // ─────────────────────────────────────────────────────────────────────────
    public function test_1_phieu_draft_khong_xuat_hien_trong_danh_sach_lo(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_1');
        [$pn, $ctn] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_DRAFT, 30);

        $response = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $response->assertOk();
        $response->assertDontSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 2: Phiếu CANCELLED không xuất hiện trong danh sách lô
    // ─────────────────────────────────────────────────────────────────────────
    public function test_2_phieu_cancelled_khong_xuat_hien_trong_danh_sach_lo(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_2');
        [$pn, $ctn] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CANCELLED, 40);

        $response = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $response->assertOk();
        $response->assertDontSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 3: Phiếu CONFIRMED tạo/hiển thị lô
    // ─────────────────────────────────────────────────────────────────────────
    public function test_3_phieu_confirmed_tao_va_hien_thi_lo(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_3');
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 60);

        $response = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $response->assertOk();
        $response->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
        $response->assertSee($sp->ten_san_pham);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 4: Lô từ CONFIRMED xuất hiện trong trang NCC
    // ─────────────────────────────────────────────────────────────────────────
    public function test_4_lo_tu_confirmed_xuat_hien_trong_trang_ncc(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_4');
        [$pn, $ctn, $sp, $ncc] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 50);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $response->assertOk();
        $response->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 5: Lô hết hàng vẫn xuất hiện trong trang NCC
    // ─────────────────────────────────────────────────────────────────────────
    public function test_5_lo_het_hang_van_xuat_hien_trong_trang_ncc(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_5');
        [$pn, $ctn, $sp, $ncc] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 20);

        // Giả lập lô đã hết hàng
        $ctn->update(['so_luong_con_lai' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $response->assertOk();
        $response->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 6: Lô hết hàng vẫn xem được chi tiết
    // ─────────────────────────────────────────────────────────────────────────
    public function test_6_lo_het_hang_van_xem_duoc_chi_tiet(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_6');
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 35);
        $ctn->update(['so_luong_con_lai' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $response->assertOk();
        $response->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
        $response->assertSee('HẾT HÀNG');
        $response->assertSee('0 (Hết hàng)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 7: so_luong_con_lai > 0: trạng thái = CÒN HÀNG
    // ─────────────────────────────────────────────────────────────────────────
    public function test_7_so_luong_con_lai_lon_hon_0_trang_thai_con_hang(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_7');
        [$pn, $ctn] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 15);

        $response = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $response->assertOk();
        $response->assertSee('CÒN HÀNG');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 8: so_luong_con_lai = 0: trạng thái = HẾT HÀNG
    // ─────────────────────────────────────────────────────────────────────────
    public function test_8_so_luong_con_lai_bang_0_trang_thai_het_hang(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_8');
        [$pn, $ctn] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 20);
        $ctn->update(['so_luong_con_lai' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $response->assertOk();
        $response->assertSee('HẾT HÀNG');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 9: Không có trạng thái CANCELLED cho lô
    // ─────────────────────────────────────────────────────────────────────────
    public function test_9_khong_co_trang_thai_cancelled_cho_lo(): void
    {
        // Bảng chi_tiet_nhap không có cột trang_thai = CANCELLED
        $columns = Schema::getColumnListing('chi_tiet_nhap');
        $this->assertNotContains('trang_thai', $columns);

        // Không có route hủy lô
        $this->assertFalse(Route::has('admin.lo-hang.cancel'));
        $this->assertFalse(Route::has('admin.lo-hang.destroy'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 10: Không còn logic HSD trong luồng nghiệp vụ lô
    // ─────────────────────────────────────────────────────────────────────────
    public function test_10_khong_con_logic_hsd_trong_luong_nghiep_vu_lo(): void
    {
        $this->assertFalse(Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han'));
        $this->assertFalse(Schema::hasColumn('chi_tiet_nhap', 'han_su_dung'));

        $admin = $this->createUser('ADMIN', 'admin_10');
        [$pn, $ctn] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 25);

        $resIndex = $this->actingAs($admin)->get(route('admin.lo-hang'));
        $resIndex->assertDontSee('HSD');
        $resIndex->assertDontSee('Hạn sử dụng');

        $resShow = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $resShow->assertDontSee('HSD');
        $resShow->assertDontSee('Hạn sử dụng');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 11: QC ĐẠT không thay đổi tồn
    // ─────────────────────────────────────────────────────────────────────────
    public function test_11_qc_dat_khong_thay_doi_ton(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_11');
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 40);

        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $ctn->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_DAT,
            'so_luong_loi' => 0,
        ]);
        $response->assertSessionHas('success');

        $this->assertEquals(40, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(20, $sp->fresh()->so_luong);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 12: QC CÓ VẤN ĐỀ không thay đổi tồn
    // ─────────────────────────────────────────────────────────────────────────
    public function test_12_qc_co_van_de_khong_thay_doi_ton(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_12');
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 40);

        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $ctn->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_CO_VAN_DE,
            'so_luong_loi' => 5,
            'ly_do'        => 'Dập nát 5 cành khi vận chuyển',
        ]);
        $response->assertSessionHas('success');

        // Tồn kho không bị trừ tự động khi QC
        $this->assertEquals(40, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(20, $sp->fresh()->so_luong);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 13: QC KHÔNG ĐẠT không tự động giảm tồn
    // ─────────────────────────────────────────────────────────────────────────
    public function test_13_qc_khong_dat_khong_tu_dong_giam_ton(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_13');
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 40);

        $response = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $ctn->ma_chi_tiet_nhap), [
            'ket_qua'      => DanhGiaLoHang::KET_QUA_KHONG_DAT,
            'so_luong_loi' => 40,
            'ly_do'        => 'Sai quy cách toàn bộ lô hoa',
        ]);
        $response->assertSessionHas('success');

        $this->assertEquals(40, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(20, $sp->fresh()->so_luong);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 14: Không được QC dòng thuộc Phiếu DRAFT/CANCELLED
    // ─────────────────────────────────────────────────────────────────────────
    public function test_14_khong_duoc_qc_dong_thuoc_phieu_draft_va_cancelled(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_14');
        [$pnDraft, $ctnDraft] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_DRAFT, 30);
        [$pnCancel, $ctnCancel] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CANCELLED, 30);

        // Thử đánh giá QC cho chi tiết phiếu DRAFT
        $resDraft = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $ctnDraft->ma_chi_tiet_nhap), [
            'ket_qua' => DanhGiaLoHang::KET_QUA_DAT,
        ]);
        $resDraft->assertNotFound();

        // Thử đánh giá QC cho chi tiết phiếu CANCELLED
        $resCancel = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $ctnCancel->ma_chi_tiet_nhap), [
            'ket_qua' => DanhGiaLoHang::KET_QUA_DAT,
        ]);
        $resCancel->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 15: Không tạo QC mới cho lô HẾT HÀNG (Backend guard ở cả 2 entry point)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_15_khong_tao_qc_moi_cho_lo_het_hang(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_15');
        [$pn, $ctn, $sp, $ncc] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 20);
        $ctn->update(['so_luong_con_lai' => 0]);

        // Entry point 1: LoHangController::danhGia
        $res1 = $this->actingAs($admin)->post(route('admin.lo-hang.danh-gia', $ctn->ma_chi_tiet_nhap), [
            'ket_qua' => DanhGiaLoHang::KET_QUA_DAT,
        ]);
        $res1->assertSessionHasErrors('lo_hang');

        // Entry point 2: NhaCungCapController::danhGiaLo
        $res2 = $this->actingAs($admin)->post(route('admin.nha-cung-cap.danh-gia-lo', $ctn->ma_chi_tiet_nhap), [
            'ket_qua' => DanhGiaLoHang::KET_QUA_DAT,
        ]);
        $res2->assertSessionHasErrors('lo_hang');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 16: Hàng hỏng UC4.4 giảm đúng so_luong_con_lai một lần
    // ─────────────────────────────────────────────────────────────────────────
    public function test_16_hang_hong_uc44_giam_dung_so_luong_con_lai_mot_lan(): void
    {
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 50);

        DB::transaction(function () use ($ctn, $sp) {
            $ctn->decrement('so_luong_con_lai', 10);
            $sp->decrement('so_luong', 10);
            BaoCaoHangHong::create([
                'ma_san_pham'       => $sp->ma_san_pham,
                'ma_chi_tiet_nhap'  => $ctn->ma_chi_tiet_nhap,
                'ma_nhan_vien'      => $this->getOperationalEmployeeId(),
                'so_luong_hong'     => 10,
                'ly_do'             => 'Hoa gãy cành',
                'thoi_gian_bao_cao' => now(),
            ]);
        });

        $this->assertEquals(40, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(10, $sp->fresh()->so_luong);

        // Xem trang chi tiết lô phải thấy báo cáo hỏng 10 sp
        $admin = $this->createUser('ADMIN', 'admin_16');
        $resShow = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $resShow->assertOk();
        $resShow->assertSee('Hoa gãy cành');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 17: Reservation RESERVED không giảm so_luong_con_lai
    // ─────────────────────────────────────────────────────────────────────────
    public function test_17_reservation_reserved_khong_giam_so_luong_con_lai(): void
    {
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 50);

        $hd = HoaDon::create([
            'ma_khach_hang'          => 1,
            'so_dien_thoai'          => '0912345678',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'tong_tien'              => 50000,
            'ngay_dat'               => now(),
        ]);

        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 8, 'gia_ban' => 25000],
        ]));

        // Lô vật lý vẫn là 50
        $this->assertEquals(50, $ctn->fresh()->so_luong_con_lai);
        // Tồn đang giữ trên lô là 8, khả dụng còn 42
        $this->assertEquals(8, $ctn->fresh()->ton_dang_giu);
        $this->assertEquals(42, $ctn->fresh()->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 18: Reservation CONSUMED giảm đúng lô
    // ─────────────────────────────────────────────────────────────────────────
    public function test_18_reservation_consumed_giam_dung_lo(): void
    {
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 50);

        $hd = HoaDon::create([
            'ma_khach_hang'          => 1,
            'so_dien_thoai'          => '0912345678',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'tong_tien'              => 50000,
            'ngay_dat'               => now(),
        ]);

        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 8, 'gia_ban' => 25000],
        ]));

        // Xác nhận đơn: CONSUMED
        DB::transaction(fn () => InventoryReservationService::confirmOrder($hd));

        // Lô vật lý giảm còn 42
        $this->assertEquals(42, $ctn->fresh()->so_luong_con_lai);
        // Không còn giữ
        $this->assertEquals(0, $ctn->fresh()->ton_dang_giu);
        $this->assertEquals(42, $ctn->fresh()->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 19: Reservation RELEASED không cộng tăng ảo lô
    // ─────────────────────────────────────────────────────────────────────────
    public function test_19_reservation_released_khong_cong_tang_ao_lo(): void
    {
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 50);

        $hd = HoaDon::create([
            'ma_khach_hang'          => 1,
            'so_dien_thoai'          => '0912345678',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'tong_tien'              => 50000,
            'ngay_dat'               => now(),
        ]);

        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 8, 'gia_ban' => 25000],
        ]));

        // Hủy đơn: RELEASED
        DB::transaction(fn () => InventoryReservationService::releaseOrder($hd));

        // Tồn kho vật lý vẫn là 50, không bị cộng tăng ảo thành 58
        $this->assertEquals(50, $ctn->fresh()->so_luong_con_lai);
        $this->assertEquals(0, $ctn->fresh()->ton_dang_giu);
        $this->assertEquals(50, $ctn->fresh()->ton_kha_dung);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 20: Không cho lô có so_luong_con_lai < 0
    // ─────────────────────────────────────────────────────────────────────────
    public function test_20_khong_cho_lo_co_so_luong_con_lai_am(): void
    {
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 10);

        $hd = HoaDon::create([
            'ma_khach_hang'          => 1,
            'so_dien_thoai'          => '0912345678',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'tong_tien'              => 500000,
            'ngay_dat'               => now(),
        ]);

        // Cố tình reserve vượt quá số lượng còn lại sẽ bị ValidationException từ chối
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 999, 'gia_ban' => 25000],
        ]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 21: Lịch sử xuất kho truy được theo ma_chi_tiet_nhap
    // ─────────────────────────────────────────────────────────────────────────
    public function test_21_lich_su_xuat_kho_truy_duoc_theo_ma_chi_tiet_nhap(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_21');
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 50);

        $hd = HoaDon::create([
            'ma_khach_hang'          => 1,
            'so_dien_thoai'          => '0912345678',
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'COD',
            'tong_tien'              => 50000,
            'ngay_dat'               => now(),
        ]);

        DB::transaction(fn () => InventoryReservationService::reserveSingleItems($hd, [
            ['ma_san_pham' => $sp->ma_san_pham, 'so_luong' => 7, 'gia_ban' => 25000],
        ]));
        DB::transaction(fn () => InventoryReservationService::confirmOrder($hd));

        $resShow = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $resShow->assertOk();
        $resShow->assertSee('Lịch sử xuất kho theo lô');
        $resShow->assertSee('#DH-' . $hd->ma_hoa_don);
        $resShow->assertSee('-7');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 22: Lịch sử hỏng/hao hụt truy được theo lô nếu dữ liệu đã tồn tại
    // ─────────────────────────────────────────────────────────────────────────
    public function test_22_lich_su_hong_hao_hut_truy_duoc_theo_lo_neu_du_lieu_da_ton_tai(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_22');
        [$pn, $ctn, $sp] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 40);

        BaoCaoHangHong::create([
            'ma_san_pham'       => $sp->ma_san_pham,
            'ma_chi_tiet_nhap'  => $ctn->ma_chi_tiet_nhap,
            'ma_nhan_vien'      => $this->getOperationalEmployeeId(),
            'so_luong_hong'     => 3,
            'ly_do'             => 'Bị sâu ăn lá',
            'thoi_gian_bao_cao' => now(),
        ]);

        $resShow = $this->actingAs($admin)->get(route('admin.lo-hang.show', $ctn->ma_chi_tiet_nhap));
        $resShow->assertOk();
        $resShow->assertSee('Bị sâu ăn lá');
        $resShow->assertSee('3');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEST 23: Nhà cung cấp ngừng hợp tác vẫn xem được các lô cũ
    // ─────────────────────────────────────────────────────────────────────────
    public function test_23_nha_cung_cap_ngung_hop_tac_van_xem_duoc_cac_lo_cu(): void
    {
        $admin = $this->createUser('ADMIN', 'admin_23');
        [$pn, $ctn, $sp, $ncc] = $this->createPhieuNhapWithLot(PhieuNhap::TRANG_THAI_CONFIRMED, 50);

        // Ngừng hợp tác NCC
        $ncc->update(['trang_thai' => NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC]);

        // Truy cập chi tiết NCC
        $resShow = $this->actingAs($admin)->get(route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap));
        $resShow->assertOk();
        // Vẫn thấy lô hàng cũ
        $resShow->assertSee('#CTN-' . $ctn->ma_chi_tiet_nhap);
    }
}
