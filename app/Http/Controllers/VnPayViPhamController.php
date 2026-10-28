<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ThanhToanViPhamKhachHang;
use App\Services\VnPayViPhamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VnPayViPhamController extends Controller
{
    public function __construct(private VnPayViPhamService $vnPayService)
    {
    }

    /**
     * GET /vnpay/vi-pham/payment/{maThanhToan}
     * Redirect khách hàng sang cổng VNPay.
     */
    public function payment(ThanhToanViPhamKhachHang $maThanhToan, Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $khach = Auth::user()->khachHang;
        if (!$khach || (int) $maThanhToan->ma_khach_hang !== (int) $khach->ma_khach_hang) {
            abort(403, 'Bạn không có quyền thanh toán giao dịch này.');
        }

        if ($maThanhToan->trang_thai !== ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN) {
            return redirect()->route('customer.vi-pham.index')
                ->with('error', 'Giao dịch không còn ở trạng thái chờ thanh toán.');
        }

        try {
            $url = $this->vnPayService->createViPhamPaymentUrl($maThanhToan, $request);
        } catch (\Throwable $e) {
            Log::error('[VNPay ViPham] createPaymentUrl failed', [
                'ma_thanh_toan' => $maThanhToan->ma_thanh_toan,
                'error'         => $e->getMessage(),
            ]);
            return redirect()->route('customer.vi-pham.index')
                ->with('error', 'Không thể khởi tạo thanh toán VNPay. Vui lòng thử lại.');
        }

        return redirect()->away($url);
    }

    /**
     * GET /vnpay/vi-pham/return
     * Return URL — chỉ hiển thị, KHÔNG xử lý. Nguồn xử lý duy nhất là IPN.
     */
    public function return(Request $request)
    {
        $vnpData = $request->query();
        $txnRef  = $vnpData['vnp_TxnRef'] ?? null;

        $verify = $this->vnPayService->verifyResponse($vnpData);
        if (!$verify['valid']) {
            return redirect()->route('customer.vi-pham.index')
                ->with('error', 'Không xác thực được kết quả thanh toán từ VNPay.');
        }

        $maThanhToan = $this->vnPayService->parseTxnRef($txnRef);
        $tt = $maThanhToan ? ThanhToanViPhamKhachHang::find($maThanhToan) : null;

        if (!$tt) {
            return redirect()->route('customer.vi-pham.index')
                ->with('error', 'Không tìm thấy giao dịch vi phạm.');
        }

        if ($tt->trang_thai === ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN) {
            return redirect()->route('customer.vi-pham.index')
                ->with('success', '🎉 Thanh toán khoản vi phạm thành công!');
        }

        $responseCode = $vnpData['vnp_ResponseCode'] ?? null;

        // Fallback xử lý nếu IPN chưa tới
        if ($this->vnPayService->isSuccessResponseCode($responseCode)
            && $this->vnPayService->isViPhamAmountMatch($tt, $vnpData['vnp_Amount'] ?? 0)) {

            try {
                DB::transaction(function () use ($tt, $vnpData, $responseCode) {
                    $locked = ThanhToanViPhamKhachHang::whereKey($tt->ma_thanh_toan)->lockForUpdate()->first();
                    if ($locked && $locked->trang_thai === ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN) {
                        $this->finalizeThanhCong($locked, $vnpData, $responseCode);
                    }
                });
            } catch (\Throwable $e) {
                Log::error('[VNPay ViPham Return] finalize fallback failed', [
                    'ma_thanh_toan' => $tt->ma_thanh_toan,
                    'error'         => $e->getMessage(),
                ]);
            }

            return redirect()->route('customer.vi-pham.index')
                ->with('success', '🎉 Thanh toán khoản vi phạm thành công!');
        }

        return redirect()->route('customer.vi-pham.index')
            ->with('error', 'Thanh toán không thành công hoặc đã bị hủy.');
    }

    /**
     * GET /vnpay/vi-pham/ipn
     * VNPay server gọi trực tiếp — nguồn xử lý DUY NHẤT.
     */
    public function ipn(Request $request)
    {
        $vnpData = $request->query();

        $verify = $this->vnPayService->verifyResponse($vnpData);
        if (!$verify['valid']) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $txnRef = $vnpData['vnp_TxnRef'] ?? null;
        $maThanhToan = $this->vnPayService->parseTxnRef($txnRef);
        if (!$maThanhToan) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $tt = ThanhToanViPhamKhachHang::find($maThanhToan);
        if (!$tt) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        if (!$this->vnPayService->isViPhamAmountMatch($tt, $vnpData['vnp_Amount'] ?? 0)) {
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        $responseCode = $vnpData['vnp_ResponseCode'] ?? null;
        $result = ['RspCode' => '99', 'Message' => 'Unknow error'];

        try {
            DB::transaction(function () use ($tt, $vnpData, $responseCode, &$result) {
                $locked = ThanhToanViPhamKhachHang::whereKey($tt->ma_thanh_toan)->lockForUpdate()->first();
                if (!$locked) {
                    $result = ['RspCode' => '01', 'Message' => 'Order not found'];
                    return;
                }

                // Idempotent
                if (in_array($locked->trang_thai, [
                    ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN,
                    ThanhToanViPhamKhachHang::TT_DA_HUY,
                ], true)) {
                    $result = ['RspCode' => '02', 'Message' => 'Order already confirmed'];
                    return;
                }

                if ($this->vnPayService->isSuccessResponseCode($responseCode)) {
                    $this->finalizeThanhCong($locked, $vnpData, $responseCode);
                    $result = ['RspCode' => '00', 'Message' => 'Confirm Success'];
                } else {
                    $locked->update([
                        'trang_thai' => ThanhToanViPhamKhachHang::TT_THAT_BAI,
                        'ma_giao_dich' => $vnpData['vnp_TransactionNo'] ?? null,
                        'ghi_chu' => 'Thanh toán VNPay thất bại/hủy. ResponseCode=' . $responseCode,
                    ]);
                    $result = ['RspCode' => '00', 'Message' => 'Confirm Success'];
                }
            });
        } catch (\Throwable $e) {
            Log::error('[VNPay ViPham IPN] error', [
                'ma_thanh_toan' => $tt->ma_thanh_toan,
                'error'         => $e->getMessage(),
            ]);
            $result = ['RspCode' => '99', 'Message' => 'Unknow error'];
        }

        return response()->json($result);
    }

    /**
     * [PRIVATE] Đánh dấu giao dịch DA_THANH_TOAN + soft-delete vi phạm + mở lại COD nếu cần.
     */
    private function finalizeThanhCong(ThanhToanViPhamKhachHang $tt, array $vnpData, ?string $responseCode): void
    {
        $tt->update([
            'trang_thai'            => ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN,
            'ma_giao_dich'          => $vnpData['vnp_TransactionNo'] ?? null,
            'thoi_diem_thanh_toan'  => now(),
            'ghi_chu'               => 'Thanh toán VNPay thành công. ResponseCode=' . $responseCode,
        ]);

        // Soft-delete vi phạm
        $viPham = $tt->viPham;
        if ($viPham && !$viPham->da_go) {
            $viPham->update([
                'da_go'        => true,
                'ly_do_go'     => 'Thanh toán qua VNPay',
                'nguoi_go'     => 'Hệ thống (VNPay)',
                'thoi_diem_go' => now(),
            ]);
        }

        // Cập nhật lại quyền COD / quyền mua hàng + gửi thông báo
        if ($viPham) {
            $conLai = \App\Models\ViPhamKhachHang::where('ma_khach_hang', $viPham->ma_khach_hang)
                ->where('da_go', false)
                ->count();

            $maHD = '#HD-' . str_pad((string) $viPham->ma_hoa_don, 4, '0', STR_PAD_LEFT);

            // Quy tắc cập nhật quyền:
            // conLai = 0 → COD CHO_PHEP
            // conLai = 1 → COD CHO_PHEP (CANH_BAO - 2 -> 1 mở lại COD)
            // conLai = 2 → COD GIOI_HAN (CAM_COD - 3 -> 2 cho mua hàng lại, COD vẫn khóa)
            // conLai >= 3 → TAM_KHOA_MUA_HANG (vẫn GIOI_HAN)
            if ($conLai <= 1) {
                \App\Models\KhachHang::whereKey($viPham->ma_khach_hang)
                    ->update(['quyen_cod' => 'CHO_PHEP']);
            } else {
                \App\Models\KhachHang::whereKey($viPham->ma_khach_hang)
                    ->update(['quyen_cod' => 'GIOI_HAN']);
            }

            // Gửi thông báo tương ứng
            if ($conLai === 0) {
                \App\Models\ThongBao::gui(
                    (int) $viPham->ma_khach_hang,
                    'info',
                    "🎉 Bạn đã thanh toán thành công khoản vi phạm của đơn {$maHD}. Tất cả vi phạm đã được giải quyết, quyền đặt hàng COD đã được khôi phục toàn diện. Cảm ơn bạn đã đồng hành cùng RoseShop! 💐",
                    null
                );
            } elseif ($conLai === 1) {
                \App\Models\ThongBao::gui(
                    (int) $viPham->ma_khach_hang,
                    'info',
                    "✅ Bạn đã thanh toán khoản vi phạm của đơn {$maHD} và được gỡ bỏ 1 vi phạm. Bạn còn 1 vi phạm chưa xử lý. Quyền COD đã được khôi phục.",
                    $viPham->ma_hoa_don ? (int) $viPham->ma_hoa_don : null
                );
            } elseif ($conLai === 2) {
                \App\Models\ThongBao::gui(
                    (int) $viPham->ma_khach_hang,
                    'info',
                    "✅ Bạn đã thanh toán khoản vi phạm của đơn {$maHD}. Quyền mua hàng của bạn đã được mở lại. Bạn còn 2 vi phạm chưa xử lý (hình thức thanh toán COD vẫn tạm khóa).",
                    $viPham->ma_hoa_don ? (int) $viPham->ma_hoa_don : null
                );
            } else {
                \App\Models\ThongBao::gui(
                    (int) $viPham->ma_khach_hang,
                    'info',
                    "✅ Bạn đã thanh toán khoản vi phạm của đơn {$maHD}. Bạn còn {$conLai} vi phạm chưa xử lý. Vui lòng thanh toán thêm để mở lại quyền mua hàng.",
                    $viPham->ma_hoa_don ? (int) $viPham->ma_hoa_don : null
                );
            }
        }
    }
}