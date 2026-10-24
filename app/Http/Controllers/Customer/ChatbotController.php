<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Gửi tin nhắn đến Chatbot và nhận phản hồi
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = trim($request->input('message'));
        $history = $request->session()->get('chatbot_history', []);

        try {
            $result = $this->chatbotService->reply($userMessage, $history);

            // Cập nhật lịch sử vào session
            $history[] = ['role' => 'user', 'content' => $userMessage];
            $history[] = ['role' => 'assistant', 'content' => $result['reply']];

            // Giới hạn lưu 12 tin gần nhất trong session
            if (count($history) > 12) {
                $history = array_slice($history, -12);
            }
            $request->session()->put('chatbot_history', $history);

            if (!empty($result['custom_mix_proposal'])) {
                $request->session()->put('chatbot_pending_custom_mix', $result['custom_mix_proposal']);
            }

            return response()->json([
                'success' => true,
                'reply' => $result['reply'],
                'suggested_items' => $result['suggested_items'] ?? [],
                'has_order_info' => $result['has_order_info'] ?? false,
                'custom_mix_proposal' => $result['custom_mix_proposal'] ?? null,
                'applied_custom_mix' => $result['applied_custom_mix'] ?? false,
                'builder_url' => $result['builder_url'] ?? route('customer.bo-hoa.tu-tao'),
            ]);
        } catch (\Throwable $e) {
            Log::error('ChatbotController Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kết nối tới trợ lý ảo. Xin vui lòng thử lại sau giây lát!',
            ], 500);
        }
    }

    /**
     * Áp dụng cấu hình Custom Mix từ chatbot vào Custom Mix Builder
     */
    public function applyCustomMix(Request $request)
    {
        $proposal = $request->input('custom_mix');
        if (!$proposal) {
            $proposal = $request->session()->get('chatbot_pending_custom_mix');
        }

        if (!$proposal || empty($proposal['thanh_phan'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cấu hình bó hoa hợp lệ để áp dụng.',
            ], 422);
        }

        $draft = [
            'ten_bo_hoa' => $proposal['ten_bo_hoa'] ?? 'Bó hoa AI thiết kế',
            'size' => $proposal['size'] ?? 'M',
            'kieu_bo_hoa' => $proposal['kieu_bo_hoa'] ?? 'TRON',
            'concept' => $proposal['concept'] ?? 'TONE_HONG',
            'so_luong' => 1,
            'thanh_phan' => array_map(function ($tp) {
                return [
                    'ma_san_pham' => (int) $tp['ma_san_pham'],
                    'so_luong' => (int) $tp['so_luong'],
                    'vai_tro' => $tp['vai_tro'] ?? 'HOA_CHINH',
                ];
            }, $proposal['thanh_phan']),
        ];

        // Lưu vào session để Custom Mix Builder tự động nạp
        $request->session()->put('bo_hoa_tu_tao_nhap', $draft);

        return response()->json([
            'success' => true,
            'message' => 'Đã áp dụng cấu hình vào Custom Mix Builder thành công!',
            'redirect_url' => route('customer.bo-hoa.tu-tao'),
            'draft' => $draft,
        ]);
    }

    /**
     * Lấy lịch sử hội thoại hiện tại
     */
    public function getHistory(Request $request)
    {
        $history = $request->session()->get('chatbot_history', []);
        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Đặt lại phiên trò chuyện
     */
    public function resetHistory(Request $request)
    {
        $request->session()->forget(['chatbot_history', 'chatbot_pending_custom_mix']);
        return response()->json([
            'success' => true,
            'message' => 'Đã làm mới cuộc hội thoại.',
        ]);
    }
}
