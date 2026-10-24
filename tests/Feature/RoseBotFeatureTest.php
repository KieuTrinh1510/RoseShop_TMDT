<?php

namespace Tests\Feature;

use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\SanPham;
use App\Services\Chatbot\ChatbotService;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoseBotFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected ChatbotService $chatbotService;
    protected InventoryReservationService $reservationService;

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

        $this->chatbotService = app(ChatbotService::class);
        $this->reservationService = app(InventoryReservationService::class);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    /**
     * TC01: Chào hỏi - Chatbot trả lời đúng, định dạng sạch.
     */
    public function test_tc01_greeting_clean_format()
    {
        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Xin chào RoseBot'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'reply', 'suggested_items']);
        $this->assertTrue($response->json('success'));

        $reply = $response->json('reply');
        $this->assertNotEmpty($reply);
        // Không chứa escaped entities hoặc raw backslashes
        $this->assertStringNotContainsString('\###', $reply);
        $this->assertStringNotContainsString('\---', $reply);
        $this->assertStringNotContainsString('&#x20;', $reply);
        $this->assertStringNotContainsString('&#x48;', $reply);
    }

    /**
     * TC02: Tư vấn hoa sinh nhật cho mẹ với ngân sách 500.000đ
     * Sản phẩm có thật, giá đúng và không vượt ngân sách nếu khẳng định phù hợp.
     */
    public function test_tc02_consultation_budget_500k_mom()
    {
        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Tôi có 500.000đ, muốn mua hoa sinh nhật tặng mẹ.'
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);

        $reply = $data['reply'];
        $suggestedItems = $data['suggested_items'];

        $this->assertNotEmpty($suggestedItems);
        foreach ($suggestedItems as $item) {
            $this->assertNotEmpty($item['title']);
            $this->assertNotEmpty($item['url']);
            if (isset($item['price'])) {
                $this->assertLessThanOrEqual(500000, $item['price']);
            }
        }

        $this->assertStringContainsString('500.000', $reply);
    }

    /**
     * TC05: Tư vấn sản phẩm hết hàng - Không khẳng định sản phẩm hết hàng là còn bán được.
     */
    public function test_tc05_out_of_stock_product_inquiry()
    {
        // Tạo sản phẩm hết hàng (so_luong = 0)
        $spOut = SanPham::create([
            'ten_san_pham' => 'Hoa Tulip Trắng Test Tồn Kho',
            'loai' => 'HOA_CAT_CANH',
            'gia' => 35000,
            'so_luong' => 0,
            'trang_thai' => 'DANG_BAN',
        ]);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => "Sản phẩm #{$spOut->ma_san_pham} còn hàng không?"
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');
        $suggestedItems = $response->json('suggested_items');

        $this->assertStringContainsStringIgnoringCase('hết hàng', $reply);
        $this->assertStringNotContainsString('đủ hàng đáp ứng', $reply);

        if (!empty($suggestedItems)) {
            $this->assertEquals('Hết hàng', $suggestedItems[0]['stock_status']);
        }
    }

    /**
     * TC07: Tra cứu đơn của mình - Trả đúng đơn của người đăng nhập.
     */
    public function test_tc07_lookup_own_order()
    {
        $hoaDon = HoaDon::first();
        if (!$hoaDon) {
            $user = NguoiDung::first();
            $hoaDon = HoaDon::create([
                'ma_khach_hang' => $user->ma_nguoi_dung,
                'tong_tien' => 350000,
                'trang_thai' => 'PENDING',
                'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
                'ngay_dat' => now(),
            ]);
        } else {
            $user = NguoiDung::find($hoaDon->ma_khach_hang);
        }

        $response = $this->actingAs($user)->postJson(route('chatbot.message'), [
            'message' => "Kiểm tra đơn hàng #{$hoaDon->ma_hoa_don}"
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('has_order_info'));
        $this->assertStringContainsString((string)$hoaDon->ma_hoa_don, $response->json('reply'));
    }

    /**
     * TC08: Tra cứu đơn người khác - Không tiết lộ thông tin.
     */
    public function test_tc08_lookup_other_user_order_forbidden()
    {
        $hoaDon = HoaDon::first();
        if (!$hoaDon) {
            $this->markTestSkipped('Không có hóa đơn nào trong CSDL để test.');
        }

        // Lấy hoặc tạo một user khác không sở hữu đơn hàng này
        $otherUser = NguoiDung::where('ma_nguoi_dung', '!=', $hoaDon->ma_khach_hang)->first();
        if (!$otherUser) {
            $otherUser = NguoiDung::create([
                'ten_dang_nhap' => 'other_' . uniqid(),
                'mat_khau' => bcrypt('123456'),
                'vai_tro' => 'KHACH_HANG',
                'trang_thai' => NguoiDung::TRANG_THAI_HOAT_DONG,
            ]);
            KhachHang::create([
                'ma_khach_hang' => $otherUser->ma_nguoi_dung,
                'ten_khach_hang' => 'Người Dùng Khác',
                'so_dien_thoai' => '0911223344',
                'email' => 'other_' . uniqid() . '@gmail.com',
            ]);
        }

        $response = $this->actingAs($otherUser)->postJson(route('chatbot.message'), [
            'message' => "Kiểm tra đơn hàng #{$hoaDon->ma_hoa_don}"
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('has_order_info'));
        $reply = $response->json('reply');
        $this->assertStringContainsString('bảo mật', $reply);
    }

    /**
     * TC10: AI tạo Custom Mix - Cấu hình cụ thể, hợp lệ, tính giá và kiểm tra tồn khả dụng.
     */
    public function test_tc10_ai_generate_custom_mix()
    {
        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Tạo một bó hoa tặng mẹ, dáng tròn, Size M, tone hồng nhẹ nhàng, ngân sách 500.000đ.'
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotNull($data['custom_mix_proposal']);

        $proposal = $data['custom_mix_proposal'];
        $this->assertEquals('M', $proposal['size']);
        $this->assertContains($proposal['kieu_bo_hoa'], ['ROUND', 'TRON']);
        $this->assertEquals(10, $proposal['so_bong']);
        $this->assertGreaterThan(0, $proposal['gia_uoc_tinh']);
        $this->assertLessThanOrEqual(500000, $proposal['gia_uoc_tinh']);

        // Kiểm tra thành phần hợp lệ
        $this->assertNotEmpty($proposal['thanh_phan']);
        $hasFlower = false;
        $hasWrapper = false;
        $hasRibbon = false;
        foreach ($proposal['thanh_phan'] as $tp) {
            if ($tp['vai_tro'] === 'HOA_CHINH' || $tp['vai_tro'] === 'HOA_PHU' || $tp['vai_tro'] === 'HOA_TRANG_TRI') $hasFlower = true;
            if ($tp['vai_tro'] === 'GIAY_GOI') $hasWrapper = true;
            if ($tp['vai_tro'] === 'RUY_BANG') $hasRibbon = true;
        }
        $this->assertTrue($hasFlower, 'Cấu hình phải có hoa');
        $this->assertTrue($hasWrapper, 'Cấu hình phải có giấy gói');
        $this->assertTrue($hasRibbon, 'Cấu hình phải có ruy băng');
    }

    /**
     * TC11: Mua 20 cành Hồng Vàng Ánh Kim và 20 cành Cúc Tana Nhỏ
     * Trả đúng giá, tồn khả dụng, khả năng đáp ứng và URL theo database thực tế.
     */
    public function test_tc11_inquiry_sp337_sp339()
    {
        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Tôi muốn mua 20 cành Hoa Hồng Vàng Ánh Kim (#337) và 20 cành Hoa Cúc Tana Nhỏ (#339).'
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);

        $reply = $data['reply'];
        $suggestedItems = $data['suggested_items'];

        // Cả 2 sản phẩm đều có trong kết quả
        $this->assertStringContainsString('337', $reply);
        $this->assertStringContainsString('339', $reply);
        $this->assertStringContainsString('28.000', $reply);
        $this->assertStringContainsString('25.000', $reply);
        $this->assertStringContainsStringIgnoringCase('khả dụng', $reply);

        $this->assertCount(2, $suggestedItems);
        $ids = array_column($suggestedItems, 'id');
        $this->assertContains(337, $ids);
        $this->assertContains(339, $ids);

        // URL đúng định dạng
        foreach ($suggestedItems as $item) {
            $this->assertStringContainsString('/customer/san-pham/' . $item['id'], $item['url']);
        }
    }

    /**
     * Kiểm tra Reservation riêng biệt: Tồn thực tế > 0 nhưng tồn khả dụng không đủ do Reservation.
     */
    public function test_reservation_available_stock_vs_on_hand()
    {
        // Tạo sản phẩm có on-hand = 10
        $sp = SanPham::create([
            'ten_san_pham' => 'Hoa Ly Test Reservation',
            'loai' => 'HOA_CAT_CANH',
            'gia' => 40000,
            'so_luong' => 10,
            'trang_thai' => 'DANG_BAN',
        ]);

        // Tạo hóa đơn tạm để liên kết reservation
        $hoaDon = HoaDon::first();
        if (!$hoaDon) {
            $khach = KhachHang::first();
            $hoaDon = HoaDon::create([
                'ma_khach_hang' => $khach->ma_khach_hang,
                'tong_tien' => 400000,
                'trang_thai' => 'PENDING',
                'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
                'ngay_dat' => now(),
            ]);
        }

        // Tạo reservation hợp lệ giữ 8 cành
        InventoryReservation::create([
            'ma_hoa_don' => $hoaDon->ma_hoa_don,
            'ma_san_pham' => $sp->ma_san_pham,
            'so_luong' => 8,
            'trang_thai' => InventoryReservation::STATUS_RESERVED,
        ]);

        // Hỏi mua 5 cành (vượt tồn khả dụng 2 dù tồn thực tế là 10)
        $response = $this->postJson(route('chatbot.message'), [
            'message' => "Tôi muốn mua 5 cành sản phẩm #{$sp->ma_san_pham}"
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        // Bot phải phân biệt tồn thực tế và tồn khả dụng
        $this->assertStringContainsStringIgnoringCase('khả dụng', $reply);
        $this->assertStringContainsStringIgnoringCase('giữ', $reply);
        $this->assertTrue(
            str_contains(mb_strtolower($reply), 'chưa') || 
            str_contains(mb_strtolower($reply), 'tối đa') ||
            str_contains(mb_strtolower($reply), 'không đủ')
        );
    }

    /**
     * TC12: Áp dụng cấu hình AI vào Custom Mix Builder
     * Builder mở ra với dữ liệu điền sẵn và có thể chỉnh sửa.
     */
    public function test_tc12_apply_custom_mix_to_builder()
    {
        // 1. Tạo Custom Mix thông qua chatbot
        $chatRes = $this->postJson(route('chatbot.message'), [
            'message' => 'Tạo một bó hoa tặng mẹ, dáng tròn, Size M, tone hồng nhẹ nhàng, ngân sách 500.000đ.'
        ]);
        $proposal = $chatRes->json('custom_mix_proposal');
        $this->assertNotNull($proposal);

        // 2. Gọi API apply custom mix
        $applyRes = $this->postJson(route('chatbot.apply-custom-mix'), [
            'custom_mix' => $proposal
        ]);
        $applyRes->assertStatus(200);
        $applyRes->assertJsonStructure(['success', 'redirect_url', 'draft']);
        $this->assertTrue($applyRes->json('success'));

        // Kiểm tra session đã được nạp
        $sessionDraft = session('bo_hoa_tu_tao_nhap');
        $this->assertNotNull($sessionDraft);
        $this->assertEquals('M', $sessionDraft['size']);
        $this->assertContains($sessionDraft['kieu_bo_hoa'], ['ROUND', 'TRON']);
        $this->assertNotEmpty($sessionDraft['thanh_phan']);

        // 3. Truy cập route Custom Mix Builder
        $builderRes = $this->get(route('customer.bo-hoa.tu-tao'));
        $builderRes->assertStatus(200);
        // Kiểm tra blade nạp draft vào DU_LIEU_CU
        $builderRes->assertSee('DU_LIEU_CU');
    }

    /**
     * TC13: Hội thoại nhiều lượt và Reset
     * Nhớ đúng ngữ cảnh, xóa đúng phiên.
     */
    public function test_tc13_multi_turn_and_reset()
    {
        // Lượt 1
        $res1 = $this->postJson(route('chatbot.message'), [
            'message' => 'Chào bạn'
        ]);
        $res1->assertStatus(200);
        $this->assertNotEmpty(session('chatbot_history'));
        $this->assertCount(2, session('chatbot_history')); // user + bot

        // Lượt 2
        $res2 = $this->postJson(route('chatbot.message'), [
            'message' => 'Shop có hoa gì màu đỏ không?'
        ]);
        $res2->assertStatus(200);
        $this->assertCount(4, session('chatbot_history'));

        // Reset
        $resetRes = $this->postJson(route('chatbot.reset'));
        $resetRes->assertStatus(200);
        $this->assertNull(session('chatbot_history'));
        $this->assertNull(session('chatbot_pending_custom_mix'));
    }

    /**
     * TC14: API AI lỗi - Thông báo phù hợp, không lộ dữ liệu nhạy cảm.
     */
    public function test_tc14_ai_error_graceful_handling()
    {
        // Fake Google Gemini API lỗi 500
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'Hãy giải thích ý nghĩa phong thủy của các loài hoa theo kinh dịch'
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');
        $this->assertNotEmpty($reply);
        // Không tiết lộ API key hay exception trace
        $this->assertStringNotContainsString('AIzaSy', $reply);
        $this->assertStringNotContainsString('Stack trace', $reply);
        $this->assertStringNotContainsString('Exception', $reply);
    }
}
