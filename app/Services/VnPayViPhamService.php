<?php

namespace App\Services;

use App\Models\ThanhToanViPhamKhachHang;
use Illuminate\Http\Request;

class VnPayViPhamService extends VnPayService
{
    /**
     * Prefix để phân biệt giao dịch vi phạm với giao dịch đơn hàng.
     */
    private const TXN_PREFIX = 'VP-';

    /**
     * Tạo URL thanh toán VNPay cho 1 giao dịch vi phạm.
     */
    public function createViPhamPaymentUrl(ThanhToanViPhamKhachHang $tt, Request $request): string
    {
        // VnPayService::createPaymentUrl yêu cầu HoaDon. Nhưng logic URL build giống hệt,
        // chỉ khác: TxnRef = VP-{id} và amount = so_tien của giao dịch vi phạm.

        $txnRef = self::TXN_PREFIX . $tt->ma_thanh_toan;
        $amount = (int) round((float) $tt->so_tien * 100);

        $ip = $request->ip();
        if (!$ip || $ip === '::1') {
            $ip = '127.0.0.1';
        }

        $params = [
            'vnp_Version'    => $this->version,
            'vnp_Command'    => $this->command,
            'vnp_TmnCode'    => $this->tmnCode,
            'vnp_Amount'     => $amount,
            'vnp_CurrCode'   => $this->currCode,
            'vnp_TxnRef'     => $txnRef,
            'vnp_OrderInfo'  => 'Thanh toan vi pham don hang ' . ($tt->ma_hoa_don ?? ''),
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => $this->locale,
            // Dùng chung /vnpay/return — VnPayController::return() sẽ forward theo prefix 'VP-'.
            'vnp_ReturnUrl'  => route('vnpay.return'),
            'vnp_IpAddr'     => $ip,
            'vnp_CreateDate' => now(self::VNPAY_TIMEZONE)->format('YmdHis'),
            'vnp_ExpireDate' => now(self::VNPAY_TIMEZONE)->addMinutes(15)->format('YmdHis'),
        ];

        return $this->buildSignedUrl($params);
    }

    /**
     * Parse `vnp_TxnRef` của giao dịch vi phạm → trả về ma_thanh_toan (int) hoặc null.
     */
    public function parseTxnRef(?string $txnRef): ?int
    {
        if (!$txnRef || !str_starts_with($txnRef, self::TXN_PREFIX)) {
            return null;
        }
        $id = substr($txnRef, strlen(self::TXN_PREFIX));
        return ctype_digit($id) ? (int) $id : null;
    }

    /**
     * Đối chiếu số tiền VNPay trả về với so_tien của giao dịch.
     */
    public function isViPhamAmountMatch(ThanhToanViPhamKhachHang $tt, $vnpAmount): bool
    {
        $expected = (int) round((float) $tt->so_tien * 100);
        $actual   = (int) $vnpAmount;
        return $actual === $expected;
    }
}