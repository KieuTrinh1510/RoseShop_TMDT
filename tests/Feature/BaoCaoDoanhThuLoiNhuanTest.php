<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BaoCaoController;
use App\Models\BoHoa;
use App\Models\NguoiDung;
use App\Models\SanPham;
use Illuminate\Http\Request;
use Tests\TestCase;

class BaoCaoDoanhThuLoiNhuanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'shop_hoa',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');
    }
    protected function getBaoCaoData(array $params = []): array
    {
        $ctrl = new BaoCaoController();
        $reflection = new \ReflectionClass($ctrl);
        $method = $reflection->getMethod('dataDoanhThu');
        $method->setAccessible(true);

        $defaultParams = [
            'tu_ngay' => now()->startOfMonth()->format('Y-m-d'),
            'den_ngay' => now()->format('Y-m-d'),
            'kieu_hien_thi' => 'thang',
        ];

        return $method->invoke($ctrl, new Request(array_merge($defaultParams, $params)));
    }

    /**
     * 1. Sản phẩm bán lẻ có lot cost: Revenue > 0, Cost > 0, Profit = Revenue - Cost.
     */
    public function test_san_pham_ban_le_co_lot_cost_va_profit_chinh_xac(): void
    {
        $data = $this->getBaoCaoData(['nhom_kinh_doanh' => 'hoa_le']);

        $this->assertGreaterThan(0, $data['tongDoanhThu']);
        $this->assertGreaterThan(0, $data['tongVon']);
        $this->assertEquals($data['tongDoanhThu'] - $data['tongVon'], $data['tongLoiNhuan']);

        $hoaHuongDuong = $data['topSanPham']->firstWhere('ten_san_pham', 'Hoa Hướng Dương Đà Lạt');
        $this->assertNotNull($hoaHuongDuong);
        $this->assertEquals(50000, $hoaHuongDuong->doanh_thu);
        $this->assertEquals(20000, $hoaHuongDuong->gia_von);
        $this->assertEquals(30000, $hoaHuongDuong->loi_nhuan);
    }

    /**
     * 2. Bó hoa có ChiTietBoHoa: Cost lấy đúng component lots.
     */
    public function test_bo_hoa_lay_dung_cogs_tu_component_lots(): void
    {
        $data = $this->getBaoCaoData(['nhom_kinh_doanh' => 'bo_hoa_co_san']);

        $this->assertGreaterThan(0, $data['tongDoanhThu']);
        $this->assertGreaterThan(0, $data['tongVon']);
        $this->assertEquals($data['tongDoanhThu'] - $data['tongVon'], $data['tongLoiNhuan']);

        $boHoa = $data['topSanPham']->firstWhere('ten_san_pham', 'Bó hoa tặng người thân');
        $this->assertNotNull($boHoa);
        $this->assertEquals(297000, $boHoa->doanh_thu);
        // Cost gốc của bó #104 truy từ 7 chi tiết lô nhập
        $this->assertEquals(133000, $boHoa->gia_von);
        $this->assertEquals(164000, $boHoa->loi_nhuan);
    }

    /**
     * 3. Bó hoa không làm decrement stock khi report.
     */
    public function test_bao_cao_khong_lam_thay_doi_ton_kho(): void
    {
        $sp = SanPham::find(331);
        $stockSpTruoc = $sp->so_luong;

        $bo = BoHoa::find(104);
        $stockBoTruoc = $bo->so_luong;

        // Chạy báo cáo nhiều lần
        $this->getBaoCaoData();
        $this->getBaoCaoData(['kieu_hien_thi' => 'ngay']);
        $this->getBaoCaoData(['kieu_hien_thi' => 'quy']);

        $sp->refresh();
        $bo->refresh();

        $this->assertEquals($stockSpTruoc, $sp->so_luong, 'Tồn kho sản phẩm không được thay đổi khi xem báo cáo.');
        $this->assertEquals($stockBoTruoc, $bo->so_luong, 'Tồn kho bó hoa không được thay đổi khi xem báo cáo.');
    }

    /**
     * 4. Top revenue sort đúng theo doanh thu giảm dần.
     */
    public function test_top_revenue_sort_giam_dan(): void
    {
        $data = $this->getBaoCaoData();
        $top = $data['topSanPham'];

        $this->assertNotEmpty($top);
        $previousRevenue = PHP_INT_MAX;
        foreach ($top as $item) {
            $this->assertLessThanOrEqual($previousRevenue, $item->doanh_thu);
            $previousRevenue = $item->doanh_thu;
        }

        // Bó hoa 297k phải đứng trước Hoa Hướng Dương 50k
        $this->assertEquals('Bó hoa tặng người thân', $top->first()->ten_san_pham);
    }

    /**
     * 5. Top profit sort đúng theo lợi nhuận giảm dần.
     */
    public function test_top_profit_sort_giam_dan(): void
    {
        $data = $this->getBaoCaoData();
        $topProfit = $data['topLoiNhuan'];

        $this->assertNotEmpty($topProfit);
        $previousProfit = PHP_INT_MAX;
        foreach ($topProfit as $item) {
            $this->assertLessThanOrEqual($previousProfit, $item->loi_nhuan);
            $previousProfit = $item->loi_nhuan;
        }

        // Bó hoa lời 164k phải đứng trước Hoa Hướng Dương lời 30k
        $this->assertEquals('Bó hoa tặng người thân', $topProfit->first()->ten_san_pham);
    }

    /**
     * 6. Group của SanPham hiển thị đúng ("Hoa lẻ").
     */
    public function test_group_cua_san_pham_hien_thi_dung(): void
    {
        $data = $this->getBaoCaoData();
        $hoaHuongDuong = $data['topSanPham']->firstWhere('ten_san_pham', 'Hoa Hướng Dương Đà Lạt');

        $this->assertNotNull($hoaHuongDuong);
        $this->assertEquals('hoa_le', $hoaHuongDuong->nhom_hang);
        $this->assertEquals('Hoa lẻ', $hoaHuongDuong->ten_nhom);
    }

    /**
     * 7. Group của BoHoa hiển thị đúng ("Bó hoa").
     */
    public function test_group_cua_bo_hoa_hien_thi_dung(): void
    {
        $data = $this->getBaoCaoData();
        $boHoa = $data['topSanPham']->firstWhere('ten_san_pham', 'Bó hoa tặng người thân');

        $this->assertNotNull($boHoa);
        $this->assertEquals('bo_hoa_co_san', $boHoa->nhom_hang);
        $this->assertEquals('Bó hoa', $boHoa->ten_nhom);
    }

    /**
     * 8. Kiểm tra render view hoàn chỉnh có đủ dữ liệu bảng và KPI.
     */
    public function test_render_view_day_du_kpi_va_hai_bang_top(): void
    {
        $admin = NguoiDung::where('vai_tro', 'ADMIN')->first();
        $this->actingAs($admin);

        $response = $this->get('/bao-cao/doanh-thu');
        $response->assertStatus(200);

        // Kiểm tra số liệu hiển thị trên view
        $response->assertSee('347.000');
        $response->assertSee('153.000');
        $response->assertSee('194.000');
        $response->assertSee('Hoa lẻ');
        $response->assertSee('Bó hoa');
        $response->assertSee('Bó hoa tặng người thân');
        $response->assertSee('Hoa Hướng Dương Đà Lạt');
        $response->assertSee('Top 5 mặt hàng theo doanh thu');
        $response->assertSee('Top 5 mặt hàng theo lợi nhuận');
    }
}
