<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\NguoiDung;
use App\Models\NhanVien;
use App\Models\HoaDon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AdminShipperTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $shipperUser;
    protected $shipper;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'shop_hoa',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        // Lấy admin mẫu
        $this->admin = NguoiDung::where('vai_tro', 'ADMIN')->first();

        // Lấy shipper mẫu
        $this->shipper = NhanVien::where('chuc_vu', 'SHIPPER')->with('nguoiDung')->first();
        if ($this->shipper) {
            $this->shipperUser = $this->shipper->nguoiDung;
        }
    }

    /**
     * Test UC2.4 - a: Admin có thể xem danh sách Shipper
     */
    public function test_admin_can_view_shipper_list_controller()
    {
        $controller = new \App\Http\Controllers\Admin\ShipperController();
        $request = new \Illuminate\Http\Request();
        $response = $controller->index($request);

        $this->assertEquals('admin.Shipper', $response->name());
        $this->assertArrayHasKey('shippers', $response->getData());
        $this->assertArrayHasKey('tongShipper', $response->getData());
        $this->assertArrayHasKey('dangHoatDong', $response->getData());
        $this->assertArrayHasKey('dangOnline', $response->getData());
    }

    /**
     * Test UC2.4 - b, c: Xem chi tiết Shipper và trạng thái hoạt động
     */
    public function test_admin_can_view_shipper_detail()
    {
        if (!$this->shipper) {
            $this->markTestSkipped('Không có dữ liệu shipper để test.');
        }

        $controller = new \App\Http\Controllers\Admin\ShipperController();
        $response = $controller->detail($this->shipper->ma_nhan_vien);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals($this->shipper->ma_nhan_vien, $data['data']['ma_nhan_vien']);
        $this->assertArrayHasKey('thong_ke', $data['data']);
        $this->assertArrayHasKey('trang_thai_hoat_dong', $data['data']);
        $this->assertArrayHasKey('trang_thai_tai_khoan', $data['data']);
    }

    /**
     * Test UC2.4 - d: Xem lịch sử giao hàng của Shipper
     */
    public function test_admin_can_view_delivery_history()
    {
        if (!$this->shipper) {
            $this->markTestSkipped('Không có dữ liệu shipper để test.');
        }

        $controller = new \App\Http\Controllers\Admin\ShipperController();
        $response = $controller->lichSuGiaoHang($this->shipper->ma_nhan_vien);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('don_hang', $data);
        $this->assertArrayHasKey('total', $data);
    }

    /**
     * Test UC2.4 - e, f: Tạm khóa và Mở khóa Shipper kèm quy tắc nghiệp vụ
     */
    public function test_admin_can_lock_and_unlock_shipper()
    {
        if (!$this->shipper || !$this->shipperUser) {
            $this->markTestSkipped('Không có dữ liệu shipper để test.');
        }

        $controller = new \App\Http\Controllers\Admin\ShipperController();

        // 1. Tạm khóa Shipper
        $this->shipperUser->trang_thai = NguoiDung::TRANG_THAI_HOAT_DONG;
        $this->shipperUser->save();

        $lockRequest = new \Illuminate\Http\Request(['target_status' => 'BI_KHOA']);
        $res = $controller->toggleStatus($this->shipper->ma_nhan_vien, $lockRequest);

        $this->shipperUser->refresh();
        $this->assertEquals(NguoiDung::TRANG_THAI_BI_KHOA, $this->shipperUser->trang_thai);

        // Quy tắc: Không khóa lại khi đang khóa
        $res2 = $controller->toggleStatus($this->shipper->ma_nhan_vien, $lockRequest);
        $this->assertEquals(NguoiDung::TRANG_THAI_BI_KHOA, $this->shipperUser->trang_thai);

        // 2. Mở khóa Shipper
        $unlockRequest = new \Illuminate\Http\Request(['target_status' => 'HOAT_DONG']);
        $res3 = $controller->toggleStatus($this->shipper->ma_nhan_vien, $unlockRequest);

        $this->shipperUser->refresh();
        $this->assertEquals(NguoiDung::TRANG_THAI_HOAT_DONG, $this->shipperUser->trang_thai);

        // Quy tắc: Không mở khóa khi đang hoạt động
        $res4 = $controller->toggleStatus($this->shipper->ma_nhan_vien, $unlockRequest);
        $this->assertEquals(NguoiDung::TRANG_THAI_HOAT_DONG, $this->shipperUser->trang_thai);
    }

    /**
     * Test Quy tắc nghiệp vụ UC2.4: Không xóa dữ liệu Shipper đã phát sinh lịch sử giao hàng
     */
    public function test_cannot_delete_shipper_with_delivery_history()
    {
        $shipperWithOrders = HoaDon::whereNotNull('ma_nhan_vien_giao')->first();
        if (!$shipperWithOrders) {
            $this->markTestSkipped('Không có đơn hàng nào được gán cho shipper để test.');
        }

        $controller = new \App\Http\Controllers\Admin\ShipperController();
        $response = $controller->destroy($shipperWithOrders->ma_nhan_vien_giao);

        // Phải chặn và báo lỗi trong session (bảo toàn hồ sơ hoặc đang có đơn chưa hoàn tất)
        $this->assertTrue(session()->has('error'));
        $this->assertTrue(
            str_contains(session('error'), 'Bảo toàn hồ sơ') || 
            str_contains(session('error'), 'chưa hoàn tất')
        );

        // Bản ghi vẫn tồn tại
        $this->assertNotNull(NhanVien::find($shipperWithOrders->ma_nhan_vien_giao));
    }
}
