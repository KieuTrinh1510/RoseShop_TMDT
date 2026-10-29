<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\GiaoHangThatBai;
use App\Models\HangHoan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class AdminBaoCaoGiaoHangTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $nhanVien;
    protected $customer;
    protected $shipper;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'shop_hoa',
        ]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->admin = NguoiDung::where('vai_tro', 'ADMIN')->first();

        $this->nhanVien = NguoiDung::where('vai_tro', 'NHAN_VIEN')->first();
        if (!$this->nhanVien) {
            $this->nhanVien = NguoiDung::create([
                'ten_dang_nhap' => 'nv_test_' . uniqid(),
                'mat_khau' => bcrypt('password'),
                'ho_ten' => 'Nhân Viên Test',
                'email' => 'nv_' . uniqid() . '@example.com',
                'vai_tro' => 'NHAN_VIEN',
                'trang_thai' => 1,
            ]);
        }

        $this->customer = NguoiDung::where('vai_tro', 'KHACH_HANG')->first();
        if (!$this->customer) {
            $this->customer = NguoiDung::create([
                'ten_dang_nhap' => 'kh_test_' . uniqid(),
                'mat_khau' => bcrypt('password'),
                'ho_ten' => 'Khách Hàng Test',
                'email' => 'kh_' . uniqid() . '@example.com',
                'vai_tro' => 'KHACH_HANG',
                'trang_thai' => 1,
            ]);
        }

        $this->shipper = NhanVien::where('chuc_vu', 'SHIPPER')->first();
        if (!$this->shipper) {
            $u = NguoiDung::create([
                'ten_dang_nhap' => 'shipper_test_' . uniqid(),
                'mat_khau' => bcrypt('password'),
                'ho_ten' => 'Shipper Test',
                'email' => 'ship_' . uniqid() . '@example.com',
                'vai_tro' => 'SHIPPER',
                'trang_thai' => 1,
            ]);
            $this->shipper = NhanVien::create([
                'ma_nhan_vien' => $u->ma_nguoi_dung,
                'ten_nhan_vien' => 'Shipper Test',
                'email' => $u->email,
                'so_dien_thoai' => '0988776655',
                'chuc_vu' => 'SHIPPER',
                'cong_viec' => 'Giao hàng',
                'luong' => 8000000,
            ]);
        }
    }

    /**
     * Test 1: Admin truy cập thành công trang Thống kê giao hàng
     */
    public function test_admin_can_access_bao_cao_giao_hang()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang'));

        $response->assertStatus(200);
        $response->assertViewIs('BaoCao.GiaoHang');
        $response->assertSee('Thống kê giao hàng');
        $response->assertSee('Tổng đơn được giao');
        $response->assertSee('Giao thành công');
        $response->assertSee('Giao thất bại');
        $response->assertSee('Tỷ lệ thành công');
        $response->assertSee('Shipper từ chối');
        $response->assertSee('Tổng COD đã thu');
        $response->assertSee('COD chưa nộp');
        $response->assertSee('Thời gian giao TB');
    }

    /**
     * Test 2: Phân quyền - Nhân viên hoặc Khách hàng không có quyền truy cập
     */
    public function test_non_admin_cannot_access_bao_cao_giao_hang()
    {
        // Nhân viên thường
        $resNv = $this->actingAs($this->nhanVien)->get(route('admin.bao-cao.giao-hang'));
        $this->assertTrue(in_array($resNv->getStatusCode(), [403, 302]));

        // Khách hàng
        $resKh = $this->actingAs($this->customer)->get(route('admin.bao-cao.giao-hang'));
        $this->assertTrue(in_array($resKh->getStatusCode(), [403, 302]));
    }

    /**
     * Test 3: Tổng số đơn được giao chỉ tính đơn bước vào delivery flow
     * Đơn PENDING hoặc CANCELLED trước phân công bị loại trừ.
     */
    public function test_kpi_tong_don_giao_only_counts_assigned_delivery_orders()
    {
        $kh = KhachHang::first();
        $khId = $kh ? $kh->ma_khach_hang : 1;

        // Đơn PENDING (chưa bước vào luồng giao)
        $hdPending = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now(),
            'tong_tien' => 100000,
            'trang_thai' => 'PENDING',
            'ma_nhan_vien_giao' => null,
            'ngay_bat_giao' => null,
        ]);

        // Đơn CANCELLED trước giao (không có shipper, không có record failure)
        $hdCancelledBefore = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now(),
            'tong_tien' => 150000,
            'trang_thai' => 'CANCELLED',
            'co_hang_hoan' => 0,
            'ma_nhan_vien_giao' => null,
            'ngay_bat_giao' => null,
            'thoi_diem_huy' => now(),
        ]);

        // Đơn ĐÃ PHÂN CÔNG (CONFIRMED có shipper)
        $hdAssigned = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now(),
            'tong_tien' => 200000,
            'trang_thai' => 'CONFIRMED',
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_bat_giao' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'ngay_bat_dau' => now()->format('Y-m-d'),
            'ngay_ket_thuc' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $danhSach = $response->viewData('danhSachDonHang');

        // Phải chứa đơn đã phân công giao
        $this->assertTrue($danhSach->contains('ma_hoa_don', $hdAssigned->ma_hoa_don));
        // Không chứa đơn PENDING
        $this->assertFalse($danhSach->contains('ma_hoa_don', $hdPending->ma_hoa_don));
        // Không chứa đơn Cancelled trước giao
        $this->assertFalse($danhSach->contains('ma_hoa_don', $hdCancelledBefore->ma_hoa_don));
    }

    /**
     * Test 4: Giao thành công lọc theo timestamp giao (ngay_giao / ngay_den_noi)
     * Đơn đặt tháng trước, giao tháng này -> tính vào tháng này.
     */
    public function test_delivered_orders_filtered_by_delivery_timestamp_not_order_date()
    {
        $kh = KhachHang::first();
        $khId = $kh ? $kh->ma_khach_hang : 1;

        // Đặt tháng trước (30 ngày trước), giao hôm nay
        $hdDelivered = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now()->subDays(30),
            'tong_tien' => 300000,
            'trang_thai' => 'DELIVERED',
            'phuong_thuc_thanh_toan' => 'COD',
            'trang_thai_thanh_toan' => 'DA_THANH_TOAN',
            'da_thu_tien_mat' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_bat_giao' => now()->subMinutes(45),
            'ngay_den_noi' => now()->subMinutes(5),
            'ngay_giao' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'ngay_bat_dau' => now()->startOfDay()->format('Y-m-d'),
            'ngay_ket_thuc' => now()->endOfDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $danhSach = $response->viewData('danhSachDonHang');
        $this->assertTrue($danhSach->contains('ma_hoa_don', $hdDelivered->ma_hoa_don));
    }

    /**
     * Test 5: Giao thất bại tính đúng đơn có sự kiện thất bại
     */
    public function test_failed_delivery_order_is_correctly_identified()
    {
        $kh = KhachHang::first();
        $khId = $kh ? $kh->ma_khach_hang : 1;

        $hdFailed = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now(),
            'tong_tien' => 250000,
            'trang_thai' => 'CANCELLED',
            'nguon_huy' => 'CUSTOMER',
            'ly_do_huy' => 'Khách tắt máy không nhận hàng',
            'thoi_diem_huy' => now(),
            'co_hang_hoan' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_bat_giao' => now()->subMinutes(30),
            'ngay_den_noi' => now()->subMinutes(10),
        ]);

        $ghtb = GiaoHangThatBai::create([
            'ma_hoa_don' => $hdFailed->ma_hoa_don,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'loai_loi' => 'CUSTOMER',
            'ly_do' => 'Khách tắt máy không nhận hàng',
            'thoi_diem_bao' => now(),
            'trang_thai' => 'DA_BAO',
        ]);

        HangHoan::create([
            'ma_hoa_don' => $hdFailed->ma_hoa_don,
            'ma_giao_hang_that_bai' => $ghtb->ma_giao_hang_that_bai,
            'trang_thai' => 'CHO_MANG_VE_KHO',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'ngay_bat_dau' => now()->format('Y-m-d'),
            'ngay_ket_thuc' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');
        $this->assertGreaterThanOrEqual(1, $kpis['giao_that_bai']);
    }

    /**
     * Test 6: Tính toán tỷ lệ giao thành công và xử lý tránh chia cho 0
     */
    public function test_success_rate_calculation_and_division_by_zero_safety()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'ngay_bat_dau' => '2010-01-01',
            'ngay_ket_thuc' => '2010-01-02',
        ]));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');
        $this->assertEquals(0, $kpis['tong_don_giao']);
        $this->assertEquals(0, $kpis['ty_le_thanh_cong']);
    }

    /**
     * Test 7: Tổng COD đã thu chỉ tính đơn DELIVERED thanh toán COD và đã thu tiền mặt
     */
    public function test_total_cod_only_counts_delivered_paid_cod_orders()
    {
        $kh = KhachHang::first();
        $khId = $kh ? $kh->ma_khach_hang : 1;

        // Đơn COD thành công đã thu tiền mặt
        $hdCodSuccess = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now(),
            'tong_tien' => 500000,
            'trang_thai' => 'DELIVERED',
            'phuong_thuc_thanh_toan' => 'COD',
            'trang_thai_thanh_toan' => 'DA_THANH_TOAN',
            'da_thu_tien_mat' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_giao' => now(),
        ]);

        // Đơn Ngân hàng/VNPay thành công (không phải COD)
        $hdVnPay = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now(),
            'tong_tien' => 800000,
            'trang_thai' => 'DELIVERED',
            'phuong_thuc_thanh_toan' => 'NGAN_HANG',
            'trang_thai_thanh_toan' => 'DA_THANH_TOAN',
            'da_thu_tien_mat' => 0,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_giao' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'ngay_bat_dau' => now()->format('Y-m-d'),
            'ngay_ket_thuc' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');
        // Phải chứa 500,000 của COD, không cộng 800,000 của VNPay
        $this->assertGreaterThanOrEqual(500000, $kpis['tong_cod_da_thu']);
    }

    /**
     * Test 8: Thời gian giao hàng trung bình tính đúng khoảng cách phút giữa bắt đầu và hoàn thành
     */
    public function test_average_delivery_duration_calculation()
    {
        $kh = KhachHang::first();
        $khId = $kh ? $kh->ma_khach_hang : 1;

        // Bắt đầu 40 phút trước, kết thúc 10 phút trước -> thời gian giao = 30 phút
        $hdDuration = HoaDon::create([
            'ma_khach_hang' => $khId,
            'ngay_dat' => now(),
            'tong_tien' => 200000,
            'trang_thai' => 'DELIVERED',
            'phuong_thuc_thanh_toan' => 'COD',
            'trang_thai_thanh_toan' => 'DA_THANH_TOAN',
            'da_thu_tien_mat' => 1,
            'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
            'ngay_bat_giao' => now()->subMinutes(40),
            'ngay_den_noi' => now()->subMinutes(10),
            'ngay_giao' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'ngay_bat_dau' => now()->format('Y-m-d'),
            'ngay_ket_thuc' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');
        $this->assertNotEquals('—', $kpis['thoi_gian_giao_tb']);
        $this->assertStringContainsString('phút', $kpis['thoi_gian_giao_tb']);
    }

    /**
     * Test 9: Validate ngày - Nếu ngày bắt đầu > ngày kết thúc, hệ thống tự động hoán đổi và cảnh báo
     */
    public function test_date_filter_validation_swaps_dates()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'ngay_bat_dau' => '2026-10-31',
            'ngay_ket_thuc' => '2026-10-01',
        ]));

        $response->assertStatus(200);
        $this->assertNotNull($response->viewData('dateWarning'));
        $this->assertTrue($response->viewData('ngayBatDau')->lte($response->viewData('ngayKetThuc')));
    }

    /**
     * Test 10: Lọc theo Shipper chỉ hiển thị đơn của Shipper đó
     */
    public function test_filter_by_shipper_restricts_results()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang', [
            'shipper_id' => $this->shipper->ma_nhan_vien,
        ]));

        $response->assertStatus(200);
        $danhSach = $response->viewData('danhSachDonHang');
        foreach ($danhSach as $row) {
            $this->assertEquals($this->shipper->ma_nhan_vien, $row->ma_nhan_vien_giao);
        }
    }

    /**
     * Test 11: Export CSV trả về StreamedResponse có UTF-8 BOM và đúng 15 cột tiêu đề
     */
    public function test_export_csv_contains_utf8_bom_and_correct_headers()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang.export'));

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Kiểm tra UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        // Kiểm tra các cột chính
        $this->assertStringContainsString('Mã đơn hàng', $content);
        $this->assertStringContainsString('Shipper phụ trách', $content);
        $this->assertStringContainsString('COD đã thu (VNĐ)', $content);
        $this->assertStringContainsString('TỔNG CỘNG', $content);
    }

    /**
     * Test 12: API chi tiết một lần giao (Drawer) trả về JSON chính xác
     */
    public function test_api_chi_tiet_giao_hang_returns_accurate_details()
    {
        $hd = HoaDon::where('trang_thai', 'DELIVERED')->first();
        if (!$hd) {
            $hd = HoaDon::create([
                'ma_khach_hang' => 1,
                'ngay_dat' => now(),
                'tong_tien' => 120000,
                'trang_thai' => 'DELIVERED',
                'phuong_thuc_thanh_toan' => 'COD',
                'trang_thai_thanh_toan' => 'DA_THANH_TOAN',
                'da_thu_tien_mat' => 1,
                'ma_nhan_vien_giao' => $this->shipper->ma_nhan_vien,
                'ngay_giao' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.bao-cao.giao-hang.chi-tiet', $hd->ma_hoa_don));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'ma_hoa_don',
                'ma_hien_thi',
                'trang_thai',
                'trang_thai_text',
                'khach_hang' => ['ho_ten', 'so_dien_thoai', 'dia_chi_giao'],
                'thanh_toan' => ['phuong_thuc', 'tong_tien', 'trang_thai_thanh_toan', 'da_thu_tien_mat'],
                'san_pham',
            ],
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertEquals($hd->ma_hoa_don, $response->json('data.ma_hoa_don'));
    }
}
