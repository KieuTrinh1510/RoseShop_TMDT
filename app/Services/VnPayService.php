<?php

namespace App\Services;

use App\Models\HoaDon;
use Illuminate\Http\Request;

/*
    LUỒNG (không đổi so với thiết kế ban đầu):

    ThanhToanController
            │
            │ tạo đơn hàng (trang_thai_thanh_toan = CHO_THANH_TOAN)
            ▼
       VnPayService::createPaymentUrl()
            │
            │ tạo URL thanh toán + vnp_SecureHash (HMAC SHA512)
            ▼
          VNPay
            │
            ├──── Return URL  ──► VnPayService::verifyResponse()  (chỉ để HIỂN THỊ)
            │
            └──── IPN         ──► VnPayService::verifyResponse()  (nguồn XỬ LÝ duy nhất)
                                   │
                                   ▼
                            VnPayController
                                   │
                                   ▼
                              cập nhật đơn

    VnPayService KHÔNG tự ý đọc/ghi DB đơn hàng (ngoài đọc field của HoaDon được
    truyền vào để tính amount) và KHÔNG quyết định trạng thái đơn hàng — việc đó
    thuộc về VnPayController + logic dùng chung trong ThanhToanController.
    Class này chỉ có 1 trách nhiệm: làm việc với "giao thức" VNPay (build URL,
    ký, verify chữ ký, đối chiếu số tiền).
*/

class VnPayService
{
    /*
        FIX (phát hiện sau khi test sandbox thực tế — lỗi VNPay code 15
        "Giao dịch đã quá thời gian chờ thanh toán" xảy ra ngay lập tức):

        VNPay LUÔN hiểu vnp_CreateDate / vnp_ExpireDate là giờ Việt Nam
        (Asia/Ho_Chi_Minh, UTC+7), bất kể server của merchant đặt timezone gì.
        Dự án này có config('app.timezone') = 'UTC' (mặc định Laravel), nên
        now()->format('YmdHis') trả về giờ UTC — lệch 7 tiếng về quá khứ so
        với đồng hồ VNPay, khiến họ thấy vnp_ExpireDate đã qua và từ chối
        giao dịch ngay khi vừa redirect sang, dù người dùng chưa kịp thao tác.

        Cố tình KHÔNG đổi 'timezone' trong config/app.php (đổi timezone toàn
        app sẽ ảnh hưởng ngay_dat, ngay_giao và các nơi khác dùng now(),
        ngoài phạm vi VNPay) — thay vào đó ép rõ ràng Asia/Ho_Chi_Minh chỉ tại
        đúng 2 chỗ VNPay yêu cầu, độc lập với cấu hình timezone của app.
    */
    protected const VNPAY_TIMEZONE = 'Asia/Ho_Chi_Minh';

    protected string $tmnCode;
    protected string $hashSecret;
    protected string $vnpUrl;
    protected string $returnUrl;
    protected string $version;
    protected string $command;
    protected string $currCode;
    protected string $locale;

    public function __construct()
    {
        // Đọc credential từ config/vnpay.php (config này chỉ đọc từ .env qua env()).
        // KHÔNG bao giờ hard-code tmn_code / hash_secret trong class này.
        $this->tmnCode    = (string) config('vnpay.tmn_code');
        $this->hashSecret = (string) config('vnpay.hash_secret');
        $this->vnpUrl     = (string) config('vnpay.url');
        $this->returnUrl  = (string) config('vnpay.return_url');
        $this->version    = (string) config('vnpay.version');
        $this->command    = (string) config('vnpay.command');
        $this->currCode   = (string) config('vnpay.curr_code');
        $this->locale     = (string) config('vnpay.locale');

        if ($this->tmnCode === '' || $this->hashSecret === '') {
            // Fail sớm & rõ ràng nếu thiếu cấu hình, thay vì tạo URL sai lặng lẽ.
            throw new \RuntimeException(
                'Thiếu cấu hình VNPay (VNPAY_TMN_CODE / VNPAY_HASH_SECRET). Kiểm tra lại .env và config/vnpay.php.'
            );
        }
    }

    /**
     * Tạo URL redirect sang cổng thanh toán VNPay cho 1 HoaDon.
     *
     * vnp_TxnRef dùng trực tiếp ma_hoa_don (int, PK, duy nhất) — xem ghi chú
     * "VỀ vnp_TxnRef" ở cuối file để hiểu rõ đánh đổi của lựa chọn này.
     *
     * @param HoaDon  $hoaDon  Đơn hàng đã được tạo với trang_thai_thanh_toan = CHO_THANH_TOAN
     * @param Request $request Request hiện tại (dùng để lấy IP khách hàng)
     */
    public function createPaymentUrl(HoaDon $hoaDon, Request $request): string
    {
        $txnRef = (string) $hoaDon->ma_hoa_don;
        $amount = $this->toVnpAmount((float) $hoaDon->tong_tien);

        $ip = $request->ip();
        if (!$ip || $ip === '::1') {
            // VNPay sandbox không chấp nhận IPv6 loopback, fallback về IPv4 loopback.
            $ip = '127.0.0.1';
        }

        $params = [
            'vnp_Version'    => $this->version,
            'vnp_Command'    => $this->command,
            'vnp_TmnCode'    => $this->tmnCode,
            'vnp_Amount'     => $amount,
            'vnp_CurrCode'   => $this->currCode,
            'vnp_TxnRef'     => $txnRef,
            'vnp_OrderInfo'  => 'Thanh toan don hang ' . $txnRef,
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => $this->locale,
            'vnp_ReturnUrl'  => $this->returnUrl,
            'vnp_IpAddr'     => $ip,
            // Ép rõ Asia/Ho_Chi_Minh (xem giải thích hằng số VNPAY_TIMEZONE ở đầu file) —
            // KHÔNG dùng now() trần vì nó phụ thuộc config('app.timezone') của app,
            // có thể khác múi giờ VNPay yêu cầu.
            'vnp_CreateDate' => now(self::VNPAY_TIMEZONE)->format('YmdHis'),
            // Hết hạn sau 15 phút nếu khách không thanh toán — tránh đơn PENDING treo mãi.
            'vnp_ExpireDate' => now(self::VNPAY_TIMEZONE)->addMinutes(15)->format('YmdHis'),
        ];

        return $this->buildSignedUrl($params);
    }

    /**
     * Verify dữ liệu VNPay trả về (dùng chung cho cả Return URL và IPN).
     * KHÔNG bao giờ tin dữ liệu này nếu verify thất bại.
     *
     * @param array $vnpData Toàn bộ query params VNPay gửi về (request()->query() dạng mảng)
     * @return array{valid: bool, reason: ?string, data: array}
     *   reason khi valid=false: MISSING_SECURE_HASH | INVALID_SIGNATURE | INVALID_TMN_CODE
     */
    public function verifyResponse(array $vnpData): array
    {
        if (empty($vnpData['vnp_SecureHash'])) {
            return ['valid' => false, 'reason' => 'MISSING_SECURE_HASH', 'data' => $vnpData];
        }

        $receivedHash = (string) $vnpData['vnp_SecureHash'];

        $dataToVerify = $vnpData;
        unset($dataToVerify['vnp_SecureHash'], $dataToVerify['vnp_SecureHashType']);

        $calculatedHash = $this->hashParams($dataToVerify);

        // hash_equals() bắt buộc để chống timing attack khi so sánh chữ ký.
        if (!hash_equals($calculatedHash, $receivedHash)) {
            return ['valid' => false, 'reason' => 'INVALID_SIGNATURE', 'data' => $vnpData];
        }

        if (!isset($vnpData['vnp_TmnCode']) || (string) $vnpData['vnp_TmnCode'] !== $this->tmnCode) {
            return ['valid' => false, 'reason' => 'INVALID_TMN_CODE', 'data' => $vnpData];
        }

        return ['valid' => true, 'reason' => null, 'data' => $vnpData];
    }

    /**
     * So sánh vnp_Amount (đơn vị x100) VNPay trả về với tong_tien thực tế của HoaDon.
     * $vnpAmount có thể là string (từ query params) nên so sánh bằng số nguyên đã ép kiểu.
     */
    public function isAmountMatch(HoaDon $hoaDon, $vnpAmount): bool
    {
        $expected = $this->toVnpAmount((float) $hoaDon->tong_tien);
        $actual   = (int) $vnpAmount;

        return $actual === $expected;
    }

    /**
     * vnp_ResponseCode == '00' nghĩa là giao dịch thành công.
     * Tách thành hàm riêng để nơi gọi không tự so sánh chuỗi rải rác nhiều chỗ.
     */
    public function isSuccessResponseCode(?string $responseCode): bool
    {
        return $responseCode === '00';
    }

    /**
     * Amount thực tế (VND) -> vnp_Amount (VND * 100, không phần thập phân).
     * Ví dụ: 100000 VND -> 10000000
     */
    protected function toVnpAmount(float $amountVnd): int
    {
        return (int) round($amountVnd * 100);
    }

    /**
     * Build URL đầy đủ kèm vnp_SecureHash từ mảng tham số (dùng cho createPaymentUrl).
     */
    protected function buildSignedUrl(array $params): string
    {
        ksort($params, SORT_STRING);

        $query = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            // Dùng urlencode (không phải rawurlencode) để khớp đúng cách VNPay
            // server tự build hash ở phía họ (chuẩn application/x-www-form-urlencoded).
            $query[] = $key . '=' . urlencode((string) $value);
        }

        $secureHash = $this->hashParams($params);

        $queryStr = implode('&', $query) . '&vnp_SecureHash=' . $secureHash;

        return rtrim($this->vnpUrl, '?') . '?' . $queryStr;
    }

    /**
     * Tính HMAC SHA512 cho một mảng tham số theo đúng chuẩn VNPay:
     * sort theo key, nối "key=urlencode(value)" bằng "&", bỏ qua giá trị rỗng,
     * rồi hash_hmac('sha512', ..., hash_secret).
     */
    protected function hashParams(array $params): string
    {
        ksort($params, SORT_STRING);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = $key . '=' . urlencode((string) $value);
        }

        $hashData = implode('&', $parts);

        return hash_hmac('sha512', $hashData, $this->hashSecret);
    }
}

