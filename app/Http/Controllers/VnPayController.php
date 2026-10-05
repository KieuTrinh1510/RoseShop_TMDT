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

    finalizeVnpayThanhCong() ở ThanhToanController giờ đã đầy đủ FIFO/ChiTietHoaDon/
    điểm, dùng chung logic xuLyFifoVaHoanTat() với COD.
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
    $txnRef  = $vnpData['vnp_TxnRef'] ?? null;

    Log::info('VNPay Return: nhận request', [
        'txn_ref'       => $txnRef,
        'response_code' => $vnpData['vnp_ResponseCode'] ?? null,
    ]);

    $verify  = $this->vnPayService->verifyResponse($vnpData);
    $hoaDon  = $txnRef ? HoaDon::find((int) $txnRef) : null;

    if (!$verify['valid'] || !$hoaDon) {
        Log::warning('VNPay Return: verify fail hoặc không tìm thấy đơn', [
            'reason' => $verify['reason'] ?? 'no_order',
        ]);
        return redirect()->route('customer.dashboard')
            ->with('error', 'Không xác thực được kết quả thanh toán từ VNPay.');
    }

    // Đã thanh toán (do IPN tới trước) → chỉ hiển thị, không làm gì.
    if ($hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN') {
        Log::info('VNPay Return: đơn đã DA_THANH_TOAN, bỏ qua', [
            'ma_hoa_don' => $hoaDon->ma_hoa_don,
        ]);
        return redirect()->route('customer.dashboard')
            ->with('success', "🎉 Thanh toán VNPay thành công cho đơn hàng #{$hoaDon->ma_hoa_don}!");
    }

    $responseCode = $vnpData['vnp_ResponseCode'] ?? null;

    // ═══ FALLBACK: xử lý finalize nếu IPN chưa/không tới ═══
    // Idempotent vì có lockForUpdate + check trang_thai_thanh_toan.
    // VNPay khuyến nghị merchant xử lý CẢ IPN và Return URL.
    if ($this->vnPayService->isSuccessResponseCode($responseCode)) {

        // Verify amount — bắt buộc, không tin query string dù đã verify chữ ký.
        if (!$this->vnPayService->isAmountMatch($hoaDon, $vnpData['vnp_Amount'] ?? 0)) {
            Log::warning('VNPay Return: sai số tiền', ['ma_hoa_don' => $hoaDon->ma_hoa_don]);
            return redirect()->route('customer.dashboard')
                ->with('error', 'Số tiền thanh toán không khớp với đơn hàng. Vui lòng liên hệ cửa hàng.');
        }

        Log::info('VNPay Return: bắt đầu finalize fallback', [
            'ma_hoa_don' => $hoaDon->ma_hoa_don,
        ]);

        try {
            DB::transaction(function () use ($hoaDon, $vnpData, $responseCode) {
                // Lock lại + re-check idempotent — chống race với IPN.
                $locked = HoaDon::where('ma_hoa_don', $hoaDon->ma_hoa_don)
                    ->lockForUpdate()
                    ->first();

                if (!$locked
                    || $locked->trang_thai_thanh_toan === 'DA_THANH_TOAN'
                    || $locked->trang_thai === 'CANCELLED') {
                    Log::info('VNPay Return: đơn đã xử lý trong lock, bỏ qua', [
                        'ma_hoa_don' => $hoaDon->ma_hoa_don,
                    ]);
                    return;
                }

                app(ThanhToanController::class)->finalizeVnpayThanhCong($locked, [
                    'vnpay_transaction_no' => $vnpData['vnp_TransactionNo'] ?? null,
                    'vnpay_bank_code'      => $vnpData['vnp_BankCode'] ?? null,
                    'vnpay_pay_date'       => $vnpData['vnp_PayDate'] ?? null,
                    'vnpay_response_code'  => $responseCode,
                ]);
            });

            Log::info('VNPay Return: finalize fallback thành công', [
                'ma_hoa_don' => $hoaDon->ma_hoa_don,
            ]);
        } catch (\Throwable $e) {
            // Không chặn user — IPN vẫn có thể tới sau và xử lý.
            // Nhưng log rõ để debug tại sao finalize fail.
            Log::error('VNPay Return: finalize fallback thất bại', [
                'ma_hoa_don' => $hoaDon->ma_hoa_don,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }

        return redirect()->route('customer.dashboard')
            ->with('success', "🎉 Thanh toán VNPay thành công cho đơn hàng #{$hoaDon->ma_hoa_don}!");
    }

    // Thanh toán thất bại/hủy — release reservation để phục hồi tồn khả dụng
    if ($responseCode !== null && !$this->vnPayService->isSuccessResponseCode($responseCode)) {
        Log::info('VNPay Return: thanh toán thất bại/hủy, giải phóng reservation', [
            'ma_hoa_don'    => $hoaDon->ma_hoa_don,
            'response_code' => $responseCode,
        ]);

        try {
            DB::transaction(function () use ($hoaDon, $responseCode, $vnpData) {
                $locked = HoaDon::where('ma_hoa_don', $hoaDon->ma_hoa_don)->lockForUpdate()->first();
                if ($locked && $locked->trang_thai_thanh_toan !== 'DA_THANH_TOAN' && $locked->trang_thai !== 'CANCELLED') {
                    if ($locked->reservations()->where('trang_thai', \App\Models\InventoryReservation::STATUS_RESERVED)->exists()) {
                        \App\Services\InventoryReservationService::releaseOrder($locked);
                    }
                    $locked->trang_thai           = 'CANCELLED';
                    $locked->vnpay_response_code  = $responseCode;
                    $locked->vnpay_transaction_no = $vnpData['vnp_TransactionNo'] ?? null;
                    $locked->vnpay_bank_code      = $vnpData['vnp_BankCode'] ?? null;
                    $locked->save();
                }
            });
        } catch (\Throwable $e) {
            Log::error('VNPay Return release reservation error: ' . $e->getMessage());
        }

        return redirect()->route('customer.dashboard')
            ->with('error', "Thanh toán cho đơn hàng #{$hoaDon->ma_hoa_don} không thành công hoặc đã bị hủy. Hàng đang giữ đã được giải phóng.");
    }

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
        $txnRefLog = $vnpData['vnp_TxnRef'] ?? null;

        Log::info('VNPay IPN received', [
            'ma_hoa_don'    => $txnRefLog,
            'response_code' => $vnpData['vnp_ResponseCode'] ?? null,
        ]);

        $verify = $this->vnPayService->verifyResponse($vnpData);
        if (!$verify['valid']) {
            Log::warning('VNPay IPN: chữ ký không hợp lệ', [
                'ma_hoa_don' => $txnRefLog,
                'reason'     => $verify['reason'],
            ]);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        Log::info('VNPay IPN: verify signature thành công', ['ma_hoa_don' => $txnRefLog]);

        $txnRef = $vnpData['vnp_TxnRef'] ?? null;
        if (!$txnRef || !ctype_digit((string) $txnRef)) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $hoaDonSoBo = HoaDon::find((int) $txnRef);
        if (!$hoaDonSoBo) {
            Log::warning('VNPay IPN: không tìm thấy HoaDon', ['ma_hoa_don' => $txnRef]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        Log::info('VNPay IPN: tìm thấy HoaDon', ['ma_hoa_don' => $hoaDonSoBo->ma_hoa_don]);

        if (!$this->vnPayService->isAmountMatch($hoaDonSoBo, $vnpData['vnp_Amount'] ?? 0)) {
            Log::warning('VNPay IPN: sai số tiền', ['ma_hoa_don' => $hoaDonSoBo->ma_hoa_don]);
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        Log::info('VNPay IPN: amount hợp lệ', ['ma_hoa_don' => $hoaDonSoBo->ma_hoa_don]);

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
                    Log::info('VNPay IPN: đơn đã xử lý trước đó, bỏ qua (idempotent)', [
                        'ma_hoa_don' => $hoaDon->ma_hoa_don,
                    ]);
                    $result = ['RspCode' => '02', 'Message' => 'Order already confirmed'];
                    return;
                }

                if ($this->vnPayService->isSuccessResponseCode($responseCode)) {
                    Log::info('VNPay IPN: bắt đầu finalize', ['ma_hoa_don' => $hoaDon->ma_hoa_don]);

                    // Gọi logic hoàn tất đơn hàng DÙNG CHUNG với COD.
                    // finalizeVnpayThanhCong() chịu trách nhiệm: FIFO trừ kho theo lô,
                    // trừ điểm dùng, cộng điểm tích luỹ, và set trang_thai_thanh_toan = DA_THANH_TOAN.
                    app(ThanhToanController::class)->finalizeVnpayThanhCong($hoaDon, [
                        'vnpay_transaction_no' => $vnpData['vnp_TransactionNo'] ?? null,
                        'vnpay_bank_code'      => $vnpData['vnp_BankCode'] ?? null,
                        'vnpay_pay_date'       => $vnpData['vnp_PayDate'] ?? null,
                        'vnpay_response_code'  => $responseCode,
                    ]);

                    Log::info('VNPay IPN: finalize thành công', ['ma_hoa_don' => $hoaDon->ma_hoa_don]);
                } else {
                    // Thất bại / khách huỷ trên trang VNPay: giải phóng giữ kho (RESERVED -> RELEASED)
                    if ($hoaDon->reservations()->where('trang_thai', \App\Models\InventoryReservation::STATUS_RESERVED)->exists()) {
                        \App\Services\InventoryReservationService::releaseOrder($hoaDon);
                    }
                    $hoaDon->trang_thai           = 'CANCELLED';
                    $hoaDon->vnpay_response_code  = $responseCode;
                    $hoaDon->vnpay_transaction_no = $vnpData['vnp_TransactionNo'] ?? null;
                    $hoaDon->vnpay_bank_code      = $vnpData['vnp_BankCode'] ?? null;
                    $hoaDon->save();

                    Log::info('VNPay IPN: thanh toán thất bại/huỷ, reservation đã release', [
                        'ma_hoa_don'    => $hoaDon->ma_hoa_don,
                        'response_code' => $responseCode,
                    ]);
                }

                $result = ['RspCode' => '00', 'Message' => 'Confirm Success'];
            });
        } catch (\Throwable $e) {
            Log::error('VNPay IPN: finalize thất bại / xử lý lỗi', [
                'ma_hoa_don' => $hoaDonSoBo->ma_hoa_don,
                'error'      => $e->getMessage(),
            ]);
            $result = ['RspCode' => '99', 'Message' => 'Unknow error'];
        }

        return response()->json($result);
    }
}