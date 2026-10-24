<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\BotKnowledge;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\SanPham;
use App\Models\MauBoHoa;
use App\Models\BoHoa;
use App\Models\PhuPhiBoHoa;
use App\Services\InventoryReservationService;
use App\Http\Controllers\Admin\BoHoaController as AdminBo;
use App\Http\Controllers\Customer\BoHoaController as CustomerBo;

class ChatbotService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ai_bot.base_url', 'https://api-ai.vnhat.dev/v1'), '/');
        $this->apiKey  = config('services.ai_bot.api_key', '');
        $this->model   = config('services.ai_bot.model', 'v9');
    }

    /**
     * Xử lý tin nhắn của khách hàng, tích hợp Tool Checking + RAG + LLM Call
     */
    public function reply(string $userMessage, array $conversationHistory = []): array
    {
        $suggestedItems = [];
        $customMixProposal = null;
        $appliedCustomMix = false;
        $builderUrl = null;

        // 1. Tool Call: Kiểm tra tra cứu đơn hàng (chính chủ)
        $orderContext = $this->checkOrderLookup($userMessage);

        // 2. Tool Call: Kiểm tra Custom Mix (Tạo bó hoa tự chọn theo tiêu chí hoặc áp dụng cấu hình)
        $customMixResult = $this->checkCustomMixInquiry($userMessage, $conversationHistory);
        $customMixContext = $customMixResult['context'] ?? '';
        if (!empty($customMixResult['custom_mix_proposal'])) {
            $customMixProposal = $customMixResult['custom_mix_proposal'];
        }
        if (!empty($customMixResult['applied_custom_mix'])) {
            $appliedCustomMix = true;
            $builderUrl = $customMixResult['builder_url'] ?? route('customer.bo-hoa.tu-tao');
        }

        // 3. Tool Call: Tra cứu trực tiếp sản phẩm cụ thể (Tồn kho thực tế, tồn đang giữ, tồn khả dụng, giá)
        $productResult = $this->checkProductInquiry($userMessage);
        $productContext = $productResult['context'] ?? '';
        if (!empty($productResult['suggested_items'])) {
            $suggestedItems = array_merge($suggestedItems, $productResult['suggested_items']);
        }

        // 4. Tool Call: Tư vấn mẫu bó hoa theo ngân sách, dịp tặng, đối tượng người nhận
        $bouquetResult = $this->checkBouquetConsultation($userMessage);
        $bouquetContext = $bouquetResult['context'] ?? '';
        if (!empty($bouquetResult['suggested_items'])) {
            $suggestedItems = array_merge($suggestedItems, $bouquetResult['suggested_items']);
        }

        // 5. RAG Retrieval: Tìm kiếm tri thức liên quan từ bảng bot_knowledge (chính sách, chăm sóc hoa, FAQ)
        $ragResults = $this->retrieveKnowledge($userMessage);
        $ragContext = $ragResults['context'];

        // Nếu chưa có suggested_items từ các Tool dữ liệu thật thì mới bổ sung từ RAG
        if (empty($suggestedItems) && !empty($ragResults['suggested_items'])) {
            $suggestedItems = $ragResults['suggested_items'];
        }

        // Giới hạn tối đa 4 card sản phẩm và loại bỏ trùng ID
        $uniqueSuggested = [];
        $seenKeys = [];
        foreach ($suggestedItems as $item) {
            $k = ($item['type'] ?? '') . '_' . ($item['id'] ?? $item['title']);
            if (!isset($seenKeys[$k])) {
                $seenKeys[$k] = true;
                $uniqueSuggested[] = $item;
            }
            if (count($uniqueSuggested) >= 4) break;
        }

        // 6. Xây dựng System Prompt với tất cả bối cảnh dữ liệu thời gian thực
        $systemPrompt = $this->buildSystemPrompt(
            $ragContext,
            $orderContext,
            $productContext,
            $bouquetContext,
            $customMixContext
        );

        // 7. Chuẩn bị danh sách Messages cho OpenAI Compatible endpoint
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        // Giữ lại 6 lượt trao đổi gần nhất để bot nhớ ngữ cảnh
        $recentHistory = array_slice($conversationHistory, -6);
        foreach ($recentHistory as $msg) {
            if (isset($msg['role'], $msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => (string) $msg['content']
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // 8. Gửi request đến AI Server
        $aiReply = $this->callLlm($messages);

        return [
            'reply' => $aiReply,
            'suggested_items' => $uniqueSuggested,
            'has_order_info' => !empty($orderContext) && !str_contains($orderContext, 'LƯU Ý HỆ THỐNG'),
            'custom_mix_proposal' => $customMixProposal,
            'applied_custom_mix' => $appliedCustomMix,
            'builder_url' => $builderUrl,
        ];
    }

    /**
     * Tra cứu thông tin đơn hàng thời gian thực (Tool Calling logic)
     */
    protected function checkOrderLookup(string $message): string
    {
        $orderContext = '';
        $matchedId = null;

        // Tìm kiếm mẫu mã đơn: #123, HD123, đơn 123, mã đơn 123...
        if (preg_match('/(?:#|HD|đơn|mã đơn|đơn hàng)[\s#:]*([0-9]+)/iu', $message, $matches)) {
            $matchedId = intval($matches[1]);
        }

        if ($matchedId) {
            // Kiểm tra bảo mật: Yêu cầu đăng nhập và chỉ cho phép tra cứu đơn của chính mình
            if (!Auth::check()) {
                return "LƯU Ý HỆ THỐNG: Khách hàng chưa đăng nhập. Để bảo mật thông tin đơn hàng, vui lòng nhắc khách đăng nhập tài khoản trước khi tra cứu mã đơn #{$matchedId}. TUYỆT ĐỐI không hiển thị thông tin nếu chưa xác thực.";
            }

            $user = Auth::user();
            $khachId = $user->ma_nguoi_dung ?? $user->id;
            $khachHang = KhachHang::where('ma_khach_hang', $khachId)->first();
            if (!$khachHang) {
                return "LƯU Ý HỆ THỐNG: Tài khoản này chưa có hồ sơ khách hàng liên kết.";
            }

            $order = HoaDon::with(['chiTietHoaDon.sanPham'])
                ->where('ma_hoa_don', $matchedId)
                ->where('ma_khach_hang', $khachHang->ma_khach_hang)
                ->first();

            if ($order) {
                return $this->formatOrderInfo($order);
            } else {
                return "LƯU Ý HỆ THỐNG BẢO MẬT: Đơn hàng mã #{$matchedId} KHÔNG thuộc quyền sở hữu của khách hàng đang đăng nhập (hoặc mã đơn không tồn tại). Vì lý do bảo mật, hãy thông báo rằng không tìm thấy đơn hàng này trong tài khoản của họ và nhắc khách kiểm tra lại mã đơn. Tuyệt đối không tiết lộ thông tin đơn hàng của người khác.";
            }
        }

        // Nếu khách hỏi "đơn hàng của tôi" và đã đăng nhập
        if (Auth::check() && preg_match('/(?:đơn hàng của tôi|đơn của tôi|tôi có đơn nào|tra cứu đơn)/iu', $message)) {
            $user = Auth::user();
            $khachId = $user->ma_nguoi_dung ?? $user->id;
            $khachHang = KhachHang::where('ma_khach_hang', $khachId)->first();
            if ($khachHang) {
                $recentOrders = HoaDon::where('ma_khach_hang', $khachHang->ma_khach_hang)
                    ->orderByDesc('ma_hoa_don')
                    ->take(3)
                    ->get();

                if ($recentOrders->isNotEmpty()) {
                    $uName = $user->ho_ten ?? 'Khách';
                    $orderContext = "DANH SÁCH ĐƠN HÀNG GẦN ĐÂY CỦA KHÁCH HÀNG ({$uName}):\n";
                    foreach ($recentOrders as $ord) {
                        $tongTienOrd = number_format($ord->tong_tien, 0, ',', '.');
                        $ttOrd = $this->translateOrderStatus($ord->trang_thai);
                        $orderContext .= "- Đơn #{$ord->ma_hoa_don}: Ngày đặt {$ord->ngay_dat}, Tổng tiền {$tongTienOrd} VNĐ, Trạng thái: {$ttOrd}, Thanh toán: {$ord->trang_thai_thanh_toan}\n";
                    }
                    $orderContext .= "\nHãy tóm tắt các đơn hàng này cho khách một cách an tâm và chu đáo.";
                    return $orderContext;
                }
            }
        }

        return '';
    }

    /**
     * Tra cứu trực tiếp thông tin sản phẩm hoa lẻ / phụ kiện từ Database
     * Đảm bảo tính toán tồn khả dụng = max(0, tồn thực tế - đang giữ - trừ lô hết hạn)
     */
    protected function checkProductInquiry(string $message): array
    {
        $allProducts = SanPham::where('trang_thai', 'DANG_BAN')->get();
        $matched = [];
        $suggestedItems = [];

        foreach ($allProducts as $p) {
            $nameLower = mb_strtolower($p->ten_san_pham, 'UTF-8');
            // Cắt bỏ tiền tố "hoa ", "cành " để tìm linh hoạt
            $cleanName = preg_replace('/^(hoa|cành|bình|chậu|miếng|cuộn|tờ)\s+/iu', '', $nameLower);
            $spIdStr = (string) $p->ma_san_pham;

            $isMatch = false;
            // 1. Khớp mã ID cụ thể
            if (preg_match('/(?:#|SP|mã|sản phẩm)\s*' . $spIdStr . '\b/iu', $message) || preg_match('/\b' . $spIdStr . '\b/', $message)) {
                $isMatch = true;
            }
            // 2. Khớp tên sản phẩm
            elseif (mb_stripos($message, $nameLower) !== false || mb_stripos($message, $cleanName) !== false) {
                $isMatch = true;
            }
            // 3. Khớp tên ngắn đặc trưng (Hồng Vàng Ánh Kim -> "hồng vàng", Cúc Tana Nhỏ -> "cúc tana", "tana")
            elseif (mb_stripos($cleanName, 'tana') !== false && mb_stripos($message, 'tana') !== false) {
                $isMatch = true;
            }

            if ($isMatch) {
                // Trích xuất số lượng khách yêu cầu đối với sản phẩm này (nếu có)
                $requestedQty = null;
                $patternBefore = '/(\d+)\s*(?:cành|bông|hoa|cái|gói|tờ|mét|miếng)?\s*(?:[^\d,;]{0,15})' . preg_quote($cleanName, '/') . '/iu';
                $patternAfter = '/' . preg_quote($cleanName, '/') . '\s*(?:[^\d,;]{0,15})?\s*(?:số lượng|sl)?\s*(\d+)/iu';

                if (preg_match($patternBefore, $message, $mQty)) {
                    $requestedQty = (int) $mQty[1];
                } elseif (preg_match($patternAfter, $message, $mQty)) {
                    $requestedQty = (int) $mQty[1];
                }

                $matched[] = [
                    'product' => $p,
                    'requested_qty' => $requestedQty,
                ];
            }
        }

        if (empty($matched)) {
            return ['context' => '', 'suggested_items' => []];
        }

        $lines = ["THÔNG TIN SẢN PHẨM & TỒN KHO THỜI GIAN THỰC TỪ DATABASE (ĐỘ CHÍNH XÁC CAO NHẤT):"];

        foreach ($matched as $item) {
            /** @var SanPham $sp */
            $sp = $item['product'];
            $reqQty = $item['requested_qty'];

            $onHand = (int) $sp->so_luong;
            $reserved = InventoryReservationService::getReservedForProduct($sp->ma_san_pham);
            $available = InventoryReservationService::getAvailableForIngredient($sp->ma_san_pham);
            $price = (float) ($sp->gia_ban_hien_tai ?? 0);
            $priceFmt = number_format($price, 0, ',', '.') . ' đ';
            $dvt = $sp->don_vi_tinh_text ?: ($sp->don_vi_tinh ?: 'cành');
            $url = route('customer.san-pham.chi-tiet', $sp->ma_san_pham);

            $statusText = "Sản phẩm ĐANG BÁN LẺ chính thức trên website.";
            $availText = "Tồn thực tế trong kho: {$onHand} {$dvt} | Đang giữ cho đơn khác: {$reserved} {$dvt} | TỒN KHẢ DỤNG: {$available} {$dvt}.";

            $reqNote = "";
            if ($reqQty !== null) {
                if ($available >= $reqQty) {
                    $totalMoney = number_format($price * $reqQty, 0, ',', '.') . ' đ';
                    $reqNote = "\n  -> Nhu cầu khách hỏi: {$reqQty} {$dvt}. KẾT LUẬN: CÒN ĐỦ HÀNG (Kho có {$available} khả dụng). Tổng giá dự kiến: {$totalMoney}.";
                } else {
                    $reqNote = "\n  -> Nhu cầu khách hỏi: {$reqQty} {$dvt}. KẾT LUẬN: KHÔNG ĐỦ HÀNG (Kho chỉ còn {$available} khả dụng, thiếu " . ($reqQty - $available) . " {$dvt}). Cần thông báo rõ cho khách và tư vấn hoa thay thế.";
                }
            } else {
                if ($available <= 0) {
                    $reqNote = "\n  -> Tình trạng: TẠM HẾT HÀNG KHẢ DỤNG. Tuyệt đối không khẳng định còn hàng.";
                }
            }

            $lines[] = "- Tên sản phẩm: {$sp->ten_san_pham} (Mã #{$sp->ma_san_pham})\n"
                     . "  Giá bán niêm yết: {$priceFmt} / {$dvt}\n"
                     . "  Tình trạng kinh doanh: {$statusText}\n"
                     . "  {$availText}\n"
                     . "  Đường dẫn trang chi tiết sản phẩm: {$url}{$reqNote}";

            $suggestedItems[] = [
                'id' => $sp->ma_san_pham,
                'title' => $sp->ten_san_pham,
                'type' => 'SAN_PHAM',
                'url' => $url,
                'price' => (float) $sp->gia,
                'stock_status' => ($available > 0 ? 'Còn hàng' : 'Hết hàng'),
                'concept' => $priceFmt . ' / ' . $dvt,
                'size' => ($available > 0 ? "Còn {$available} {$dvt}" : "Tạm hết hàng"),
            ];
        }

        $lines[] = "\nQUY ĐỊNH BẮT BUỘC:\n"
                 . "1. Sử dụng đúng dữ liệu giá, tồn kho khả dụng và URL ở trên.\n"
                 . "2. Khẳng định rõ cả hai sản phẩm đều CÓ DỮ LIỆU BÁN LẺ và cung cấp link xem chi tiết.\n"
                 . "3. Nếu khách hỏi mua số lượng cụ thể, báo cáo chính xác khả năng đáp ứng dựa trên tồn khả dụng.";

        return [
            'context' => implode("\n", $lines),
            'suggested_items' => $suggestedItems,
        ];
    }

    /**
     * Tư vấn mẫu bó hoa theo ngân sách, đối tượng và dịp tặng
     */
    protected function checkBouquetConsultation(string $message): array
    {
        // Nhận diện ý định tìm kiếm bó hoa / tư vấn quà tặng
        $isConsultIntent = preg_match('/(?:tư vấn|gợi ý|mua hoa|bó hoa|tặng|sinh nhật|ngân sách|giá|tầm|khoảng|mẹ|bạn gái|vợ|ông bà|thầy cô|giáo viên|đồng nghiệp|khai trương|tốt nghiệp)/iu', $message);
        // Nếu là Custom Mix builder thì không chạy bộ lọc này
        $isCustomMix = preg_match('/(?:tự mix|custom|tự tạo|thiết kế riêng|tự chọn bông)/iu', $message);

        if (!$isConsultIntent || $isCustomMix) {
            return ['context' => '', 'suggested_items' => []];
        }

        // Trích xuất ngân sách
        $budget = null;
        if (preg_match('/(?:ngân sách|tầm|khoảng|có|dưới|tối đa|giá)\s*(\d+(?:[\.,]\d{3})*|\d+)\s*(k|nghìn|ngàn|tr|triệu|đ|vnd|vnđ)?/iu', $message, $bm)) {
            $rawNum = str_replace(['.', ','], '', $bm[1]);
            $unit = mb_strtolower($bm[2] ?? '', 'UTF-8');
            $val = (float) $rawNum;
            if (in_array($unit, ['k', 'nghìn', 'ngàn']) || ($val < 1000 && $val >= 50)) {
                $val *= 1000;
            } elseif (in_array($unit, ['tr', 'triệu'])) {
                $val *= 1000000;
            }
            $budget = $val;
        }

        // Lấy danh sách mẫu bó hoa ACTIVE
        $activeMaus = MauBoHoa::where('trang_thai', 'ACTIVE')
            ->with(['chiTietMauBoHoa.sanPham'])
            ->get();

        $readyBouquets = BoHoa::where('loai_bo_hoa', 'HE_THONG')
            ->where('trang_thai', 'ACTIVE')
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->get()
            ->groupBy('ma_mau_bo_hoa');

        $scoredBouquets = [];

        foreach ($activeMaus as $m) {
            // Tính giá hiển thị thực tế
            $ready = $readyBouquets->get($m->ma_mau_bo_hoa)?->first();
            $price = $ready ? (float) $ready->gia_ban : CustomerBo::tinhGiaUocTinhMau($m);
            if ($price === null || $price <= 0) {
                $cost = $m->chiTietMauBoHoa->sum(fn($ct) => ($ct->sanPham?->gia_nhap ?? 0) * $ct->so_luong);
                $price = round($cost * 1.5, 2);
            }

            // Kiểm tra khả dụng
            $kd = CustomerBo::kiemTraKhaDungMau($m);
            $isAvailable = $kd['kha_dung'];

            // Tính điểm phù hợp
            $score = 10;
            $mNameLower = mb_strtolower($m->ten_mau, 'UTF-8');
            $conceptLower = mb_strtolower($m->concept ?? '', 'UTF-8');

            // Khớp đối tượng tặng
            if (preg_match('/(?:mẹ|người thân|gia đình)/iu', $message)) {
                if (str_contains($mNameLower, 'người thân') || str_contains($mNameLower, 'ông bà')) $score += 50;
                if ($conceptLower === 'luxury' || $conceptLower === 'natural' || $conceptLower === 'elegant') $score += 20;
            }
            if (preg_match('/(?:bạn gái|người yêu)/iu', $message)) {
                if (str_contains($mNameLower, 'người yêu')) $score += 50;
                if ($conceptLower === 'romantic') $score += 20;
            }
            if (preg_match('/(?:vợ)/iu', $message)) {
                if (str_contains($mNameLower, 'vợ') || str_contains($mNameLower, 'người yêu')) $score += 50;
            }
            if (preg_match('/(?:thầy|cô|giáo viên)/iu', $message)) {
                if (str_contains($mNameLower, 'giáo viên')) $score += 50;
            }
            if (preg_match('/(?:đồng nghiệp|bạn bè)/iu', $message)) {
                if (str_contains($mNameLower, 'đồng nghiệp') || str_contains($mNameLower, 'bạn bè')) $score += 40;
            }

            // Khớp ngân sách
            if ($budget !== null) {
                if ($price <= $budget) {
                    $score += 30; // Nằm trong ngân sách
                } elseif ($price <= $budget * 1.15) {
                    $score += 5;  // Vượt nhẹ ngân sách
                } else {
                    $score -= 40; // Quá ngân sách
                }
            }

            // Ưu tiên mẫu đang có sẵn thành phẩm hoặc đủ nguyên liệu
            if ($isAvailable) {
                $score += 20;
            } else {
                $score -= 30;
            }

            $scoredBouquets[] = [
                'mau' => $m,
                'price' => $price,
                'is_available' => $isAvailable,
                'score' => $score,
            ];
        }

        // Sắp xếp theo điểm phù hợp
        usort($scoredBouquets, fn($a, $b) => $b['score'] <=> $a['score']);

        $topBouquets = array_slice($scoredBouquets, 0, 3);
        $suggestedItems = [];
        $lines = ["DANH SÁCH MẪU BÓ HOA THỰC TẾ PHÙ HỢP TIÊU CHÍ (TỪ DATABASE):"];

        foreach ($topBouquets as $item) {
            /** @var MauBoHoa $m */
            $m = $item['mau'];
            $priceFmt = number_format($item['price'], 0, ',', '.') . ' đ';
            $availStr = $item['is_available'] ? 'CÒN HÀNG (Có sẵn hoặc nghệ nhân cắm hoa trong 60 phút)' : 'TẠM THIẾU NGUYÊN LIỆU';
            $url = route('customer.mau-bo-hoa.chi-tiet', $m->ma_mau_bo_hoa);

            $thanhPhanList = [];
            foreach ($m->chiTietMauBoHoa as $ct) {
                $thanhPhanList[] = ($ct->sanPham?->ten_san_pham ?? 'Hoa') . ' (' . $ct->so_luong . ')';
            }
            $thanhPhanStr = implode(', ', $thanhPhanList);

            $lines[] = "- Mẫu: {$m->ten_mau} (Mã #{$m->ma_mau_bo_hoa})\n"
                     . "  Kích thước: Size {$m->size} | Kiểu dáng: {$m->kieu_bo_hoa} | Concept: {$m->concept}\n"
                     . "  Giá bán thực tế: {$priceFmt}\n"
                     . "  Trạng thái khả dụng: {$availStr}\n"
                     . "  Thành phần hoa: {$thanhPhanStr}\n"
                     . "  Link chi tiết: {$url}";

            if ($item['is_available']) {
                $suggestedItems[] = [
                    'id' => $m->ma_mau_bo_hoa,
                    'title' => $m->ten_mau,
                    'type' => 'MAU_BO_HOA',
                    'url' => $url,
                    'price' => (float) $item['price'],
                    'stock_status' => 'Còn hàng',
                    'concept' => 'Giá: ' . $priceFmt . ' • ' . $m->concept,
                    'size' => 'Size ' . $m->size,
                ];
            }
        }

        if ($budget !== null) {
            $lines[] = "\nLƯU Ý NGÂN SÁCH: Khách hàng có ngân sách " . number_format($budget, 0, ',', '.') . " đ. Hãy giới thiệu mẫu hoa phù hợp nhất nằm trong ngân sách và khẳng định giá chính xác.";
        }

        return [
            'context' => implode("\n", $lines),
            'suggested_items' => $suggestedItems,
        ];
    }

    /**
     * Xử lý tạo cấu hình Custom Mix Builder theo tiêu chí người dùng
     * Hoặc áp dụng cấu hình vừa gợi ý sang Builder
     */
    protected function checkCustomMixInquiry(string $message, array $history = []): array
    {
        // 1. Kiểm tra yêu cầu ÁP DỤNG cấu hình vừa gợi ý vào Custom Mix Builder
        if (preg_match('/(?:đồng ý|áp dụng|chuyển sang|mở|lấy cấu hình|chỉnh sửa cấu hình|dùng mẫu vừa gợi ý).*?(?:custom|mix|builder|tự tạo)/iu', $message)
            || preg_match('/(?:áp dụng vào custom mix|áp dụng cấu hình đó)/iu', $message)) {

            $pending = session('chatbot_pending_custom_mix');
            if ($pending && is_array($pending) && !empty($pending['thanh_phan'])) {
                // Lưu trực tiếp vào session builder
                session(['bo_hoa_tu_tao_nhap' => $pending]);

                $builderUrl = route('customer.bo-hoa.tu-tao');
                $context = "THÔNG BÁO QUAN TRỌNG TỪ HỆ THỐNG: Cấu hình bó hoa Custom Mix của bạn ĐÃ ĐƯỢC ÁP DỤNG THÀNH CÔNG vào Custom Mix Builder.\n"
                         . "- Link trang thiết kế: {$builderUrl}\n"
                         . "Hãy thông báo chúc mừng cho khách hàng và mời khách bấm vào nút 'Áp dụng vào Custom Mix Builder' hoặc truy cập link trên để kiểm tra, thêm bớt nguyên liệu và đặt hàng.";

                return [
                    'context' => $context,
                    'applied_custom_mix' => true,
                    'builder_url' => $builderUrl,
                    'custom_mix_proposal' => $pending,
                ];
            }
        }

        // 2. Kiểm tra ý định tạo bó hoa tùy biến (Custom Mix)
        $isCreateMixIntent = preg_match('/(?:tạo|thiết kế|mix|tự mix|phối|tự tạo)\s*(?:một|1)?\s*bó hoa/iu', $message)
                          || preg_match('/(?:custom mix|tự thiết kế bó hoa|bó hoa custom)/iu', $message);

        if (!$isCreateMixIntent) {
            return ['context' => ''];
        }

        // Trích xuất tiêu chí
        $budget = 500000; // Mặc định 500k nếu không nói
        if (preg_match('/(?:ngân sách|tầm|khoảng|có|dưới|tối đa|giá)\s*(\d+(?:[\.,]\d{3})*|\d+)\s*(k|nghìn|ngàn|tr|triệu|đ|vnd|vnđ)?/iu', $message, $bm)) {
            $rawNum = str_replace(['.', ','], '', $bm[1]);
            $unit = mb_strtolower($bm[2] ?? '', 'UTF-8');
            $val = (float) $rawNum;
            if (in_array($unit, ['k', 'nghìn', 'ngàn']) || ($val < 1000 && $val >= 50)) {
                $val *= 1000;
            } elseif (in_array($unit, ['tr', 'triệu'])) {
                $val *= 1000000;
            }
            $budget = $val;
        }

        // Kích thước (Size)
        $size = 'M';
        if (preg_match('/\bsize\s*(s|m|l|xl|xxl|special)\b/iu', $message, $sm)) {
            $size = strtoupper($sm[1]);
        }

        // Kiểu dáng
        $kieuBo = 'ROUND';
        if (preg_match('/(?:dáng tròn|bó tròn|round)/iu', $message)) $kieuBo = 'ROUND';
        elseif (preg_match('/(?:tự nhiên|natural)/iu', $message)) $kieuBo = 'NATURAL';
        elseif (preg_match('/(?:thác đổ|cascading)/iu', $message)) $kieuBo = 'CASCADING';
        elseif (preg_match('/(?:hình nón|dáng dài|cone)/iu', $message)) $kieuBo = 'CONE';
        elseif (preg_match('/(?:bất đối xứng|asymmetric)/iu', $message)) $kieuBo = 'ASYMMETRIC';
        elseif (preg_match('/(?:gọn|compact)/iu', $message)) $kieuBo = 'COMPACT';

        // Concept
        $concept = 'ELEGANT';
        if (preg_match('/(?:lãng mạn|người yêu|bạn gái|romantic)/iu', $message)) $concept = 'ROMANTIC';
        elseif (preg_match('/(?:tự nhiên|nature)/iu', $message)) $concept = 'NATURAL';
        elseif (preg_match('/(?:sang trọng|luxury)/iu', $message)) $concept = 'LUXURY';
        elseif (preg_match('/(?:dễ thương|cute)/iu', $message)) $concept = 'CUTE';
        elseif (preg_match('/(?:tối giản|minimalist)/iu', $message)) $concept = 'MINIMALIST';

        // Tên bó hoa theo dịp / người nhận
        $tenBo = 'Bó hoa Custom Mix Yêu Thương';
        if (preg_match('/(?:mẹ)/iu', $message)) $tenBo = 'Bó hoa Tri Ân Mẹ Yêu';
        elseif (preg_match('/(?:bạn gái|người yêu)/iu', $message)) $tenBo = 'Bó hoa Tình Yêu Lãng Mạn';
        elseif (preg_match('/(?:vợ)/iu', $message)) $tenBo = 'Bó hoa Ngọt Ngào Dành Tặng Vợ';
        elseif (preg_match('/(?:thầy|cô|giáo viên)/iu', $message)) $tenBo = 'Bó hoa Kính Tặng Thầy Cô';

        // Lấy định mức
        $canBong = AdminBo::SO_BONG[$size] ?? 10;
        $canGiay = AdminBo::GIAY_GOI_QTY[$size] ?? 6;
        $canRuy  = AdminBo::RUY_BANG_QTY[$size] ?? 1;
        $canXop  = AdminBo::XOP_QTY[$size] ?? 1;

        // Tìm hoa chính và hoa phụ còn tồn khả dụng
        $activeFlowers = SanPham::whereIn('loai_san_pham', ['HOA_TUOI', 'HOA_GIA', 'HOA_SAP', 'HOA_GIAY_NHUN'])
            ->where('trang_thai', 'DANG_BAN')
            ->get();

        $flowerIds = $activeFlowers->pluck('ma_san_pham')->all();
        $availMap = InventoryReservationService::getBatchAvailableForIngredients($flowerIds);

        // Lọc hoa còn tồn kho
        $validFlowers = $activeFlowers->filter(fn($f) => ($availMap[$f->ma_san_pham] ?? 0) >= 3)->values();

        // Ưu tiên chọn hoa theo tone màu
        $isTonePink = preg_match('/(?:hồng|pink|nhẹ nhàng)/iu', $message);
        $isToneRed = preg_match('/(?:đỏ|red|nhiệt huyết)/iu', $message);
        $isToneYellow = preg_match('/(?:vàng|yellow|nắng)/iu', $message);

        $mainFlower = null;
        $decorFlower = null;

        foreach ($validFlowers as $f) {
            $name = mb_strtolower($f->ten_san_pham, 'UTF-8');
            if ($isTonePink && (str_contains($name, 'cẩm chướng') || str_contains($name, 'hồng phấn') || str_contains($name, 'hồng'))) {
                if (!$mainFlower && ($availMap[$f->ma_san_pham] ?? 0) >= ($canBong - 3)) $mainFlower = $f;
            } elseif ($isToneRed && (str_contains($name, 'đỏ') || str_contains($name, 'đồng tiền'))) {
                if (!$mainFlower && ($availMap[$f->ma_san_pham] ?? 0) >= ($canBong - 3)) $mainFlower = $f;
            } elseif ($isToneYellow && (str_contains($name, 'hướng dương') || str_contains($name, 'vàng'))) {
                if (!$mainFlower && ($availMap[$f->ma_san_pham] ?? 0) >= ($canBong - 3)) $mainFlower = $f;
            }
        }

        // Fallback hoa chính nếu chưa chọn được
        if (!$mainFlower) {
            $mainFlower = $validFlowers->first(fn($f) => ($availMap[$f->ma_san_pham] ?? 0) >= ($canBong - 3)) ?? $validFlowers->first();
        }

        // Chọn hoa trang trí (khác hoa chính)
        foreach ($validFlowers as $f) {
            if ($mainFlower && $f->ma_san_pham === $mainFlower->ma_san_pham) continue;
            $name = mb_strtolower($f->ten_san_pham, 'UTF-8');
            if (str_contains($name, 'tana') || str_contains($name, 'baby') || str_contains($name, 'nhỏ')) {
                $decorFlower = $f;
                break;
            }
        }
        if (!$decorFlower) {
            $decorFlower = $validFlowers->first(fn($f) => $mainFlower && $f->ma_san_pham !== $mainFlower->ma_san_pham);
        }

        // Số lượng chia hoa chính và hoa phụ
        $qtyDecor = min(4, max(2, (int) round($canBong * 0.3)));
        $qtyMain  = $canBong - $qtyDecor;

        // Lấy phụ liệu gói
        $giayGoi = SanPham::where('loai_san_pham', 'GIAY_GOI')->where('trang_thai', 'DANG_BAN')->first();
        $ruyBang = SanPham::where('loai_san_pham', 'RUY_BANG')->where('trang_thai', 'DANG_BAN')->first();
        $xop     = SanPham::where('loai_san_pham', 'MIENG_XOP')->where('trang_thai', 'DANG_BAN')->first();

        if (!$mainFlower || !$decorFlower || !$giayGoi || !$ruyBang || !$xop) {
            return ['context' => 'LƯU Ý: Hiện tại kho nguyên liệu không đủ để tạo cấu hình Custom Mix. Hãy gợi ý khách chọn các mẫu bó hoa có sẵn.'];
        }

        // Xây dựng công thức thành phần hợp lệ
        $thanhPhan = [
            [
                'ma_san_pham'  => $mainFlower->ma_san_pham,
                'ten_san_pham' => $mainFlower->ten_san_pham,
                'so_luong'     => $qtyMain,
                'vai_tro'      => 'HOA_CHINH',
                'gia_ban'      => (float) $mainFlower->gia_ban_hien_tai,
            ],
            [
                'ma_san_pham'  => $decorFlower->ma_san_pham,
                'ten_san_pham' => $decorFlower->ten_san_pham,
                'so_luong'     => $qtyDecor,
                'vai_tro'      => 'HOA_TRANG_TRI',
                'gia_ban'      => (float) $decorFlower->gia_ban_hien_tai,
            ],
            [
                'ma_san_pham'  => $giayGoi->ma_san_pham,
                'ten_san_pham' => $giayGoi->ten_san_pham,
                'so_luong'     => $canGiay,
                'vai_tro'      => 'GIAY_GOI',
                'gia_ban'      => (float) $giayGoi->gia_ban_hien_tai,
            ],
            [
                'ma_san_pham'  => $ruyBang->ma_san_pham,
                'ten_san_pham' => $ruyBang->ten_san_pham,
                'so_luong'     => $canRuy,
                'vai_tro'      => 'RUY_BANG',
                'gia_ban'      => (float) $ruyBang->gia_ban_hien_tai,
            ],
            [
                'ma_san_pham'  => $xop->ma_san_pham,
                'ten_san_pham' => $xop->ten_san_pham,
                'so_luong'     => $canXop,
                'vai_tro'      => 'XOP',
                'gia_ban'      => (float) $xop->gia_ban_hien_tai,
            ],
        ];

        $proposalData = [
            'ten_bo_hoa'  => $tenBo,
            'size'        => $size,
            'kieu_bo_hoa' => $kieuBo,
            'concept'     => $concept,
            'so_luong'    => 1,
            'thanh_phan'  => $thanhPhan,
        ];

        // Tính giá và phụ phí bằng logic thực tế của Laravel
        $phuPhi = AdminBo::layPhuPhi($size, $kieuBo);
        $tienNguyenLieu = 0;
        foreach ($thanhPhan as $row) {
            $tienNguyenLieu += $row['gia_ban'] * $row['so_luong'];
        }
        $tongTien = $tienNguyenLieu + ($phuPhi['tong'] ?? 0);

        // Lưu cấu hình vào proposal để trả về frontend và lưu session
        $fullProposal = [
            'ten_bo_hoa'        => $tenBo,
            'size'              => $size,
            'kieu_bo_hoa'       => $kieuBo,
            'kieu_bo_hoa_text'  => AdminBo::KIEU_DANG[$kieuBo] ?? $kieuBo,
            'concept'           => $concept,
            'concept_text'      => AdminBo::CONCEPT[$concept] ?? $concept,
            'so_luong'          => 1,
            'so_bong'           => $canBong,
            'thanh_phan'        => $thanhPhan,
            'tien_nguyen_lieu'  => $tienNguyenLieu,
            'phu_phi'           => $phuPhi,
            'tong_tien'         => $tongTien,
            'gia_uoc_tinh'      => $tongTien,
            'builder_url'       => route('customer.bo-hoa.tu-tao'),
        ];

        // Lưu vào session pending để khi khách bấm Áp dụng là có sẵn
        session(['chatbot_pending_custom_mix' => $fullProposal]);

        $phiSizeStr = number_format($phuPhi['size'], 0, ',', '.') . ' đ';
        $phiKieuStr = number_format($phuPhi['kieu_dang'], 0, ',', '.') . ' đ';
        $tongPhiStr = number_format($phuPhi['tong'], 0, ',', '.') . ' đ';
        $tongTienStr = number_format($tongTien, 0, ',', '.') . ' đ';
        $budgetStr = number_format($budget, 0, ',', '.') . ' đ';

        $lines = [
            "HỆ THỐNG ĐÃ TẠO THÀNH CÔNG CẤU HÌNH BÓ HOA CUSTOM MIX HỢP LỆ THEO TIÊU CHÍ:",
            "- Tên bó hoa: {$tenBo}",
            "- Kích thước: Size {$size} (Định mức chuẩn: {$canBong} bông hoa)",
            "- Kiểu dáng: " . (AdminBo::KIEU_DANG[$kieuBo] ?? $kieuBo) . " (Phụ phí: {$phiKieuStr})",
            "- Chủ đề / Concept: " . (AdminBo::CONCEPT[$concept] ?? $concept),
            "- Chi tiết nguyên liệu & thành phần:",
            "  + Hoa chính: {$qtyMain} cành {$mainFlower->ten_san_pham} (Đơn giá: " . number_format($mainFlower->gia_ban_hien_tai, 0, ',', '.') . " đ)",
            "  + Hoa trang trí: {$qtyDecor} cành {$decorFlower->ten_san_pham} (Đơn giá: " . number_format($decorFlower->gia_ban_hien_tai, 0, ',', '.') . " đ)",
            "  + Giấy gói: {$canGiay} tờ {$giayGoi->ten_san_pham}",
            "  + Ruy băng: {$canRuy} mét {$ruyBang->ten_san_pham}",
            "  + Xốp cắm hoa: {$canXop} miếng {$xop->ten_san_pham}",
            "- TỒN KHO KHẢ DỤNG: Toàn bộ nguyên liệu đều được kiểm tra có sẵn trong kho.",
            "- GIÁ DỰ KIẾN: {$tongTienStr} (Tiền hoa & phụ liệu: " . number_format($tienNguyenLieu, 0, ',', '.') . " đ + Phụ phí gia công nghệ nhân: {$tongPhiStr})",
            "- SO VỚI NGÂN SÁCH ({$budgetStr}): " . ($tongTien <= $budget ? "NẰM HOÀN TOÀN TRONG NGÂN SÁCH (Tiết kiệm " . number_format($budget - $tongTien, 0, ',', '.') . " đ)" : "Vượt nhẹ ngân sách nhưng thiết kế rất đẹp và trang nhã"),
            "- NÚT TƯƠNG TÁC: Giao diện chat đã hiển thị nút 'Áp dụng vào Custom Mix Builder'.",
            "QUY TẮC PHẢN HỒI: Hãy trình bày cấu hình trên rõ ràng, lịch sự, nêu bật ý nghĩa tình cảm của bó hoa và mời khách nhấn nút 'Áp dụng vào Custom Mix Builder' ngay bên dưới để mở trang tự chọn và chỉnh sửa nếu muốn.",
        ];

        return [
            'context' => implode("\n", $lines),
            'custom_mix_proposal' => $fullProposal,
        ];
    }

    /**
     * Định dạng chi tiết 1 đơn hàng
     */
    protected function formatOrderInfo(HoaDon $order): string
    {
        $statusMap = [
            'PENDING'   => 'Đang chờ xác nhận / Đang chuẩn bị hoa',
            'CONFIRMED' => 'Đã xác nhận đơn - Nghệ nhân đang cắm hoa',
            'SHIPPING'  => 'Đang giao hàng (Shipper đang trên đường giao)',
            'DELIVERED' => 'Giao hàng thành công',
            'CANCELLED' => 'Đã hủy đơn',
        ];

        $paymentStatusMap = [
            'CHUA_THANH_TOAN' => 'Chưa thanh toán (Thanh toán khi nhận hoa COD)',
            'DA_THANH_TOAN'   => 'Đã thanh toán thành công (VNPAY)',
            'DA_NOP'          => 'Đã hoàn tất thanh toán',
        ];

        $statusStr = $statusMap[$order->trang_thai] ?? $order->trang_thai;
        $payStr    = $paymentStatusMap[$order->trang_thai_thanh_toan] ?? $order->trang_thai_thanh_toan;
        $tongTien  = number_format($order->tong_tien ?? 0, 0, ',', '.') . ' VNĐ';

        return "THÔNG TIN ĐƠN HÀNG THỰC TẾ TỪ CƠ SỞ DỮ LIỆU:\n"
             . "- Mã đơn hàng: #{$order->ma_hoa_don}\n"
             . "- Ngày đặt: {$order->ngay_dat}\n"
             . "- Tổng giá trị: {$tongTien}\n"
             . "- Trạng thái tiến độ: {$statusStr}\n"
             . "- Tình trạng thanh toán: {$payStr} (Phương thức: {$order->phuong_thuc_thanh_toan})\n"
             . "- Địa chỉ nhận: " . ($order->dia_chi_giao ?? 'Tại cửa hàng RoseShop') . "\n"
             . "- Số điện thoại nhận: {$order->so_dien_thoai}\n"
             . "Hãy báo cáo tình trạng này một cách chính xác, an tâm và chu đáo cho khách hàng.";
    }

    protected function translateOrderStatus(?string $status): string
    {
        return match($status) {
            'PENDING' => 'Chờ xác nhận',
            'CONFIRMED' => 'Đã xác nhận',
            'SHIPPING' => 'Đang giao',
            'DELIVERED' => 'Đã giao',
            'CANCELLED' => 'Đã hủy',
            default => $status ?? 'Đang xử lý',
        };
    }

    /**
     * RAG Retrieval Engine: Tính điểm phù hợp và lấy Top Chunks
     */
    protected function retrieveKnowledge(string $query): array
    {
        $allKnowledge = BotKnowledge::all();
        $queryLower = mb_strtolower(trim($query), 'UTF-8');

        $words = preg_split('/[\s,?.!]+/', $queryLower, -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = ['là', 'gì', 'có', 'không', 'cho', 'mình', 'em', 'anh', 'shop', 'với', 'và', 'của', 'như', 'thế', 'nào', 'ạ', 'ơi', 'nhé', 'muốn', 'hỏi', 'xem'];
        $keywords = array_filter($words, fn($w) => !in_array($w, $stopWords) && mb_strlen($w, 'UTF-8') >= 2);

        $scoredItems = [];

        foreach ($allKnowledge as $item) {
            $score = 0;
            $titleLower = mb_strtolower($item->tieu_de, 'UTF-8');
            $contentLower = mb_strtolower($item->noi_dung, 'UTF-8');
            $tagLower = mb_strtolower($item->tu_khoa ?? '', 'UTF-8');

            if (mb_strpos($contentLower, $queryLower) !== false) $score += 15;
            if (mb_strpos($titleLower, $queryLower) !== false) $score += 25;

            foreach ($keywords as $kw) {
                if (mb_strpos($titleLower, $kw) !== false) $score += 8;
                if (mb_strpos($tagLower, $kw) !== false) $score += 6;
                if (mb_strpos($contentLower, $kw) !== false) $score += 3;
            }

            // Ưu tiên theo intent
            if (preg_match('/(?:ship|giao|vận chuyển|nhận hàng|hỏa tốc)/iu', $query) && $item->loai === 'CHINH_SACH' && str_contains($item->ma_dinh_danh, 'giao_hang')) {
                $score += 35;
            }
            if (preg_match('/(?:đổi|trả|hỏng|héo|dập|bảo hành|hoàn tiền)/iu', $query) && $item->loai === 'CHINH_SACH' && str_contains($item->ma_dinh_danh, 'doi_tra')) {
                $score += 35;
            }
            if (preg_match('/(?:chăm sóc|giữ tươi|tưới nước|cắm hoa lâu tàn)/iu', $query) && str_contains($item->ma_dinh_danh, 'bao_quan')) {
                $score += 35;
            }

            if ($score > 0) {
                $scoredItems[] = [
                    'item' => $item,
                    'score' => $score
                ];
            }
        }

        usort($scoredItems, fn($a, $b) => $b['score'] <=> $a['score']);
        $topChunks = array_slice($scoredItems, 0, 3);

        $contextParts = [];
        $suggestedItems = [];

        foreach ($topChunks as $entry) {
            $item = $entry['item'];
            $contextParts[] = "--- TÀI LIỆU [{$item->loai}]: {$item->tieu_de} ---\n{$item->noi_dung}";

            if (in_array($item->loai, ['MAU_BO_HOA', 'SAN_PHAM']) && !empty($item->metadata)) {
                $meta = $item->metadata;
                $suggestedItems[] = [
                    'id' => $meta['id'] ?? null,
                    'title' => $meta['ten_mau'] ?? $meta['ten_san_pham'] ?? $item->tieu_de,
                    'type' => $item->loai,
                    'url' => $meta['url'] ?? null,
                    'concept' => $meta['concept'] ?? null,
                    'size' => $meta['size'] ?? null,
                ];
            }
        }

        return [
            'context' => implode("\n\n", $contextParts),
            'suggested_items' => array_slice($suggestedItems, 0, 2),
        ];
    }

    /**
     * Xây dựng System Prompt chuẩn chỉnh cho RoseBot AI
     */
    protected function buildSystemPrompt(
        string $ragContext,
        string $orderContext = '',
        string $productContext = '',
        string $bouquetContext = '',
        string $customMixContext = ''
    ): string {
        $customerName = Auth::check() ? (Auth::user()->ho_ten ?? Auth::user()->name ?? 'Khách hàng') : 'quý khách';

        $prompt = "Bạn là RoseBot, trợ lý ảo tư vấn hoa tươi và chăm sóc khách hàng thông minh của Cửa hàng hoa tươi RoseShop.\n"
                . "Người đang trò chuyện với bạn là: {$customerName}.\n\n"
                . "PHONG CÁCH GIAO TIẾP:\n"
                . "- Giọng điệu thân thiện, ấm áp, lịch thiệp, yêu thích cái đẹp và hoa tươi.\n"
                . "- Trả lời bằng tiếng Việt chuẩn mực, rõ ràng, gãy gọn.\n"
                . "- Sử dụng các emoji hoa lá nhẹ nhàng (🌸, 🌹, 🌿, ✨, 🚚) để tạo cảm giác tươi vui và chuyên nghiệp.\n"
                . "- Dùng định dạng Markdown chuẩn (danh sách *, in đậm **tên**) để câu trả lời dễ đọc. KHÔNG dùng các ký tự thoát vô nghĩa như \\###, \\---, 1\\..\n\n"
                . "QUY TẮC NGHIỆP VỤ & SỰ THẬT TỐI CAO:\n"
                . "1. Ưu tiên số 1 là các phần 'DỮ LIỆU THỜI GIAN THỰC' được cung cấp bên dưới (Sản phẩm, Đơn hàng, Custom Mix, Bó hoa).\n"
                . "2. TUYỆT ĐỐI không bịa đặt giá, không bịa đặt tên sản phẩm hay tồn kho.\n"
                . "3. Nếu phần DỮ LIỆU SẢN PHẨM xác nhận sản phẩm đang bán lẻ và có giá, BẮT BUỘC khẳng định sản phẩm có bán lẻ, nêu đúng giá và link xem chi tiết. Không được tuyên bố sản phẩm chỉ là nguyên liệu không bán lẻ nếu dữ liệu khẳng định đang bán.\n"
                . "4. Khi khách hỏi mua số lượng cụ thể, so sánh với TỒN KHẢ DỤNG: Nếu đủ thì xác nhận đủ hàng; nếu thiếu thì báo rõ số lượng khả dụng còn lại và tư vấn hoa thay thế.\n"
                . "5. Khi tư vấn theo ngân sách, CHỈ gợi ý các mẫu hoa có giá nằm trong hoặc gần nhất với ngân sách, nêu rõ giá tiền thực tế.\n"
                . "6. Với cấu hình Custom Mix do hệ thống tính toán, hãy trình bày đầy đủ các thành phần, phụ phí và tổng tiền đã tính toán chính xác, nhắc khách có thể bấm nút 'Áp dụng vào Custom Mix Builder' ngay bên dưới.\n";

        if (!empty($productContext)) {
            $prompt .= "\n========================================\n"
                     . "{$productContext}\n"
                     . "========================================\n";
        }

        if (!empty($customMixContext)) {
            $prompt .= "\n========================================\n"
                     . "{$customMixContext}\n"
                     . "========================================\n";
        }

        if (!empty($bouquetContext)) {
            $prompt .= "\n========================================\n"
                     . "{$bouquetContext}\n"
                     . "========================================\n";
        }

        if (!empty($orderContext)) {
            $prompt .= "\n========================================\n"
                     . "{$orderContext}\n"
                     . "========================================\n";
        }

        if (!empty($ragContext)) {
            $prompt .= "\n========================================\n"
                     . "TRI THỨC BỔ SUNG (CHÍNH SÁCH & CẨM NANG):\n"
                     . "{$ragContext}\n"
                     . "========================================\n";
        }

        return $prompt;
    }

    /**
     * Gọi LLM qua OpenAI Compatible endpoint
     */
    protected function callLlm(array $messages): string
    {
        if (empty($this->apiKey)) {
            Log::warning('ChatbotService: AI_BOT_API_KEY chưa được cấu hình.');
            return "Dạ RoseShop xin chào! Hiện tại hệ thống trợ lý ảo AI chưa được thiết lập API key. Bạn có thể liên hệ trực tiếp số điện thoại hotline của RoseShop để được hỗ trợ chu đáo nhất nhé! 🌸";
        }

        try {
            $url = $this->baseUrl . '/chat/completions';

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post($url, [
                    'model'    => $this->model,
                    'messages' => $messages,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                if (!empty($content)) {
                    return $this->sanitizeAiReply(trim($content));
                }
            }

            Log::error('ChatbotService LLM Call Failed: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('ChatbotService Exception: ' . $e->getMessage());
        }

        // Fallback response nếu có sự cố mạng
        return "Dạ RoseShop xin chào! Hiện tại hệ thống tư vấn tự động đang bận một chút xíu. Bạn có thể để lại số điện thoại hoặc nhắn tin qua hotline của shop để được nghệ nhân tư vấn ngay nhé! 🌸";
    }

    /**
     * Làm sạch phản hồi của AI khỏi các ký tự escape markdown thừa hoặc entity lỗi
     */
    protected function sanitizeAiReply(string $text): string
    {
        // Loại bỏ các backslash escape markdown thừa như \###, \---, 1\., \***
        $cleaned = preg_replace('/\\\\([#\-\*\_`~\[\]\(\)\.\!])/', '$1', $text);

        // Chuyển đổi các entity hex thông dụng như &#x20; -> khoảng trắng
        $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($cleaned);
    }
}
