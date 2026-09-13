<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\ThanhToanController;
use App\Models\HoaDon;
use App\Services\VnPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
    Trách nhiệm của controller này (đúng kiến trúc mục 3 trong yêu cầu gốc):
    - redirect khách hàng sang VNPay (payment)
    - xử lý Return URL — CHỈ hiển thị kết quả cho khách xem, KHÔNG xử lý đơn hàng
    - xử lý IPN — nguồn xử lý đơn hàng DUY NHẤT (trừ kho, cộng/trừ điểm, đổi trạng thái)
    - chống xử lý 1 giao dịch nhiều lần bằng DB::transaction + lockForUpdate()

    LƯU Ý QUAN TRỌNG (forward reference — sẽ hoàn thiện ở PHASE 5):
    ipn() gọi tới ThanhToanController::finalizeVnpayThanhCong($hoaDon, $vnpayMeta).
    Hàm này CHƯA tồn tại ở ThanhToanController tại thời điểm Phase 3 — sẽ được thêm
    ở Phase 5 bằng cách tách logic FIFO/trừ kho/điểm hiện có ra thành 1 hàm dùng
    chung cho cả COD và VNPay (đúng yêu cầu "không tạo phiên bản FIFO khác").
    Trước khi Phase 5 xong, gọi thật IPN sẽ lỗi "method not found" — đây là chủ đích,
    không phải bug, để tránh viết 2 lần logic FIFO khác nhau.
*/

class VnPayController extends Controller
{
    public function __construct(private VnPayService $vnPayService)
    {
    }

    /**
     * GET /vnpay/payment/{hoaDon}
     * Redirect khách hàng sang cổng thanh toán VNPay.
     */
    public function payment(HoaDon $hoaDon, Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $khachHang = Auth::user()->khachHang;

        // Chỉ chủ đơn hàng mới được redirect sang VNPay cho đơn của mình.
        if (!$khachHang || (int) $hoaDon->ma_khach_hang !== (int) $khachHang->ma_khach_hang) {
            abort(403, 'Bạn không có quyền thanh toán đơn hàng này.');
        }

        // Chỉ cho phép thanh toán VNPay với đơn đúng trạng thái "đang chờ thanh toán online"
        // (xem giải thích tổ hợp trạng thái ở đầu Phase 3).
        $dangChoThanhToanVnpay = $hoaDon->phuong_thuc_thanh_toan === 'NGAN_HANG'
            && $hoaDon->trang_thai_thanh_toan === 'CHUA_THANH_TOAN'
            && $hoaDon->trang_thai === 'PENDING';

        if (!$dangChoThanhToanVnpay) {
            return redirect()->route('customer.dashboard')
                ->with('error', 'Đơn hàng không hợp lệ hoặc đã được xử lý, không thể thanh toán VNPay.');
        }

        try {
            $url = $this->vnPayService->createPaymentUrl($hoaDon, $request);
        } catch (\Throwable $e) {
            Log::error('VNPay createPaymentUrl thất bại', [
                'ma_hoa_don' => $hoaDon->ma_hoa_don,
                'error'      => $e->getMessage(), // KHÔNG log secret vì VnPayService không trả secret ra exception message
            ]);

            return redirect()->route('customer.dashboard')
                ->with('error', 'Không thể khởi tạo thanh toán VNPay lúc này. Vui lòng thử lại sau.');
        }

        return redirect()->away($url);
    }

    /**
     * GET /vnpay/return
     * Khách hàng được VNPay redirect trình duyệt về đây.
     * CHỈ hiển thị trạng thái — KHÔNG trừ kho, KHÔNG cộng/trừ điểm, KHÔNG đổi trạng thái đơn.
     * Nguồn xử lý thật sự luôn luôn là ipn().
     */
    public function return(Request $request)
    {
    $vnpData = $request->query();
    $verify  = $this->vnPayService->verifyResponse($vnpData);

    $txnRef = $vnpData['vnp_TxnRef'] ?? null;
    $hoaDon = $txnRef ? HoaDon::find((int) $txnRef) : null;

    if (!$verify['valid'] || !$hoaDon) {
        return redirect()->route('customer.dashboard')
            ->with('error', 'Không xác thực được kết quả thanh toán từ VNPay.');
    }

    // Chỉ đọc trạng thái THẬT đã được ipn() ghi vào DB để báo thành công.
    if ($hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN') {
        return redirect()->route('customer.dashboard')
            ->with('success', "🎉 Thanh toán VNPay thành công cho đơn hàng #{$hoaDon->ma_hoa_don}!");
    }

    // Đọc vnp_ResponseCode CHỈ để hiển thị thông báo phù hợp cho khách — KHÔNG dùng để
    // ghi DB (nguồn ghi DB duy nhất vẫn là ipn()). Đơn luôn được giữ nguyên Chua_TT để
    // khách bấm "Thanh toán" thử lại trên chính đơn này.
    $responseCode = $vnpData['vnp_ResponseCode'] ?? null;
    if ($responseCode !== null && !$this->vnPayService->isSuccessResponseCode($responseCode)) {
        return redirect()->route('customer.dashboard')
            ->with('error', "Thanh toán cho đơn hàng #{$hoaDon->ma_hoa_don} không thành công. Bạn có thể bấm \"Thanh toán\" ở đơn hàng này để thử lại.");
    }

    // Khách quay lại trước khi IPN kịp xử lý xong (độ trễ mạng bình thường).
    return redirect()->route('customer.dashboard')
        ->with('info', "Đơn hàng #{$hoaDon->ma_hoa_don} đang được xác nhận thanh toán, vui lòng đợi trong giây lát và kiểm tra lại.");
    }

    /**
     * GET /vnpay/ipn
     * VNPay server gọi trực tiếp — KHÔNG được phụ thuộc session/đăng nhập của khách.
     * Đây là nguồn XỬ LÝ DUY NHẤT: verify chữ ký, verify số tiền, rồi mới xử lý đơn hàng.
     * Phải luôn trả JSON đúng định dạng VNPay yêu cầu (RspCode, Message).
     */
    public function ipn(Request $request)
    {
        $vnpData = $request->query();

        $verify = $this->vnPayService->verifyResponse($vnpData);
        if (!$verify['valid']) {
            Log::warning('VNPay IPN: chữ ký không hợp lệ', ['reason' => $verify['reason']]);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $txnRef = $vnpData['vnp_TxnRef'] ?? null;
        if (!$txnRef || !ctype_digit((string) $txnRef)) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $hoaDonSoBo = HoaDon::find((int) $txnRef);
        if (!$hoaDonSoBo) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        if (!$this->vnPayService->isAmountMatch($hoaDonSoBo, $vnpData['vnp_Amount'] ?? 0)) {
            Log::warning('VNPay IPN: sai số tiền', ['ma_hoa_don' => $hoaDonSoBo->ma_hoa_don]);
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        $responseCode = $vnpData['vnp_ResponseCode'] ?? null;
        $result = ['RspCode' => '99', 'Message' => 'Unknow error'];

        try {
            DB::transaction(function () use ($hoaDonSoBo, $vnpData, $responseCode, &$result) {
                // lockForUpdate() để chống 2 IPN (hoặc IPN + retry) xử lý cùng lúc trên
                // cùng 1 đơn hàng — đây là chốt chặn idempotent quan trọng nhất.
                $hoaDon = HoaDon::where('ma_hoa_don', $hoaDonSoBo->ma_hoa_don)
                    ->lockForUpdate()
                    ->first();

                if (!$hoaDon) {
                    $result = ['RspCode' => '01', 'Message' => 'Order not found'];
                    return;
                }

                // Idempotent: đơn đã được xử lý (thành công hoặc đã huỷ) rồi thì KHÔNG
                // xử lý lại lần nữa, kể cả khi VNPay gọi IPN nhiều lần hay khách refresh
                // Return URL nhiều lần (Return URL không gọi vào đây, nhưng phòng thủ vẫn giữ).
                if ($hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN' || $hoaDon->trang_thai === 'CANCELLED') {
                    $result = ['RspCode' => '02', 'Message' => 'Order already confirmed'];
                    return;
                }

                if ($this->vnPayService->isSuccessResponseCode($responseCode)) {
                    // Gọi logic hoàn tất đơn hàng DÙNG CHUNG với COD (Phase 5).
                    // finalizeVnpayThanhCong() chịu trách nhiệm: FIFO trừ kho theo lô,
                    // trừ điểm dùng, cộng điểm tích luỹ, và set trang_thai_thanh_toan = DA_THANH_TOAN.
                    app(ThanhToanController::class)->finalizeVnpayThanhCong($hoaDon, [
                        'vnpay_transaction_no' => $vnpData['vnp_TransactionNo'] ?? null,
                        'vnpay_bank_code'      => $vnpData['vnp_BankCode'] ?? null,
                        'vnpay_pay_date'       => $vnpData['vnp_PayDate'] ?? null,
                        'vnpay_response_code'  => $responseCode,
                    ]);
                } else {
    // Thất bại / khách huỷ trên trang VNPay: KHÔNG trừ kho, KHÔNG trừ/cộng điểm.
    // QUAN TRỌNG: KHÔNG đổi trang_thai (giữ nguyên PENDING) và KHÔNG đổi
    // trang_thai_thanh_toan (giữ nguyên CHUA_THANH_TOAN) — đơn phải sống nguyên
    // để khách bấm "Thanh toán" lại trên CHÍNH đơn này (không tạo đơn mới).
    // Chỉ ghi lại kết quả lần thử gần nhất để tra cứu/hỗ trợ khi cần.
                             $hoaDon->vnpay_response_code   = $responseCode;
                             $hoaDon->vnpay_transaction_no  = $vnpData['vnp_TransactionNo'] ?? null;
                             $hoaDon->vnpay_bank_code       = $vnpData['vnp_BankCode'] ?? null;
                              $hoaDon->save();

                             Log::info('VNPay IPN: thanh toán thất bại/huỷ, đơn được giữ nguyên để retry', [
                             'ma_hoa_don'    => $hoaDon->ma_hoa_don,
                                'response_code' => $responseCode,
                             ]);
                        }

                $result = ['RspCode' => '00', 'Message' => 'Confirm Success'];
            });
        } catch (\Throwable $e) {
            Log::error('VNPay IPN xử lý lỗi', [
                'ma_hoa_don' => $hoaDonSoBo->ma_hoa_don,
                'error'      => $e->getMessage(),
            ]);
            $result = ['RspCode' => '99', 'Message' => 'Unknow error'];
        }

        return response()->json($result);
    }
}