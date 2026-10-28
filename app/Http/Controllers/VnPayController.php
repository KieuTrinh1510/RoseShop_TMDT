<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\ThanhToanController;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Services\VnPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
    KIẾN TRÚC:

    store() [ThanhToanController]
      ├─ COD    → tạo HoaDon PENDING + reserve + điểm NGAY (không đổi)
      └─ VNPAY  → KHÔNG tạo HoaDon. Cache "vnpay_pending_TXN-{uuid}" 25h.
                  Redirect /vnpay/pending/{ref}.

    paymentPending() → đọc cache → redirect cổng VNPay.

    IPN / Return (TXN-):
      - Verify signature + amount.
      - Thành công → finalizePendingHoaDon():
          * Cache lock + DB transaction.
          * Tạo HoaDon DA_THANH_TOAN.
          * Reserve kho — LỖI → THROW [STOCK] → ROLLBACK toàn bộ.
          * CHỈ khi reserve OK mới trừ/cộng điểm.
          * Lỗi [STOCK] → ghi marker refund + báo khách, không trừ điểm.
      - Thất bại → xóa cache, KHÔNG tạo đơn, KHÔNG trừ điểm, giỏ còn nguyên.
*/

class VnPayController extends Controller
{
    public function __construct(private VnPayService $vnPayService)
    {
    }

    // ═══════════════════════════════════════════════════════════════════
    // PAYMENT (HoaDon đã tồn tại) — legacy
    // ═══════════════════════════════════════════════════════════════════
    public function payment(HoaDon $hoaDon, Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $khachHang = Auth::user()->khachHang;
        if (!$khachHang || (int) $hoaDon->ma_khach_hang !== (int) $khachHang->ma_khach_hang) {
            abort(403, 'Bạn không có quyền thanh toán đơn hàng này.');
        }

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
                'error'      => $e->getMessage(),
            ]);
            return redirect()->route('customer.dashboard')
                ->with('error', 'Không thể khởi tạo thanh toán VNPay lúc này. Vui lòng thử lại sau.');
        }

        return redirect()->away($url);
    }

    // ═══════════════════════════════════════════════════════════════════
    // PAYMENT PENDING — VNPAY chưa có HoaDon
    // ═══════════════════════════════════════════════════════════════════
    public function paymentPending(Request $request, string $ref)
    {
        $pending = Cache::get("vnpay_pending_{$ref}");
        if (!$pending) {
            Log::warning('VNPay paymentPending: cache miss', ['ref' => $ref]);
            return redirect()->route('customer.thanh-toan')
                ->with('error', 'Phiên thanh toán đã hết hạn. Vui lòng đặt lại đơn hàng.');
        }

        $khachHang = Auth::user()?->khachHang;
        if (!$khachHang || (int) $pending['ma_khach_hang'] !== (int) $khachHang->ma_khach_hang) {
            abort(403, 'Bạn không có quyền thanh toán phiên này.');
        }

        try {
            $url = $this->vnPayService->createPaymentUrlFromData(
                $ref,
                (float) $pending['tong_tien'],
                $request
            );
        } catch (\Throwable $e) {
            Log::error('VNPay createPaymentUrlFromData thất bại', [
                'ref'   => $ref,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('customer.thanh-toan')
                ->with('error', 'Không thể khởi tạo thanh toán VNPay. Vui lòng thử lại.');
        }

        return redirect()->away($url);
    }

    // ═══════════════════════════════════════════════════════════════════
    // RETURN
    // ═══════════════════════════════════════════════════════════════════
    public function return(Request $request)
    {
        $vnpData      = $request->query();
        $txnRef       = $vnpData['vnp_TxnRef'] ?? null;
        $responseCode = $vnpData['vnp_ResponseCode'] ?? null;

        if ($txnRef && str_starts_with($txnRef, 'VP-')) {
            Log::info('VNPay Return: chuyển sang VnPayViPhamController', ['txn_ref' => $txnRef]);
            return app(\App\Http\Controllers\VnPayViPhamController::class)->return($request);
        }

        if ($txnRef && str_starts_with($txnRef, 'TXN-')) {
            return $this->xuLyReturnPending($txnRef, $vnpData, $responseCode);
        }

        return $this->xuLyReturnLegacy($vnpData, $txnRef, $responseCode);
    }

    // ═══════════════════════════════════════════════════════════════════
    // IPN
    // ═══════════════════════════════════════════════════════════════════
    public function ipn(Request $request)
    {
        $vnpData      = $request->query();
        $txnRef       = $vnpData['vnp_TxnRef'] ?? null;
        $responseCode = $vnpData['vnp_ResponseCode'] ?? null;

        Log::info('VNPay IPN received', [
            'txn_ref'       => $txnRef,
            'response_code' => $responseCode,
        ]);

        $verify = $this->vnPayService->verifyResponse($vnpData);
        if (!$verify['valid']) {
            Log::warning('VNPay IPN: chữ ký không hợp lệ', [
                'txn_ref' => $txnRef,
                'reason'  => $verify['reason'],
            ]);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        if ($txnRef && str_starts_with($txnRef, 'VP-')) {
            Log::info('VNPay IPN: chuyển sang VnPayViPhamController', ['txn_ref' => $txnRef]);
            return app(\App\Http\Controllers\VnPayViPhamController::class)->ipn($request);
        }

        if ($txnRef && str_starts_with($txnRef, 'TXN-')) {
            return $this->xuLyIpnPending($txnRef, $vnpData, $responseCode);
        }

        return $this->xuLyIpnLegacy($vnpData, $txnRef, $responseCode);
    }

    // ═══════════════════════════════════════════════════════════════════
    // RETURN — NHÁNH TXN- (đã tích hợp Case 4)
    // ═══════════════════════════════════════════════════════════════════
    private function xuLyReturnPending(string $txnRef, array $vnpData, ?string $responseCode)
    {
        $verify = $this->vnPayService->verifyResponse($vnpData);
        if (!$verify['valid']) {
            return redirect()->route('customer.dashboard')
                ->with('error', 'Không xác thực được kết quả thanh toán từ VNPay.');
        }

        // IPN đã tạo HoaDon?
        $hoaDon = HoaDon::where('vnpay_cart_snapshot->txn_ref', $txnRef)->first();
        if ($hoaDon) {
            Log::info('VNPay Return: HoaDon đã có (IPN tới trước)', [
                'txn_ref' => $txnRef, 'ma_hoa_don' => $hoaDon->ma_hoa_don,
            ]);
            $this->xoaGioHangSauKhiDat($hoaDon, (array) ($hoaDon->vnpay_cart_snapshot ?? []));
            return redirect()->route('customer.dashboard')
                ->with('success', "🎉 Thanh toán VNPay thành công cho đơn hàng #{$hoaDon->ma_hoa_don}!");
        }

        // Đã từng fail reserve → chờ hoàn tiền
        if (Cache::has("vnpay_refund_needed_{$txnRef}")) {
            Log::info('VNPay Return: giao dịch đã ghi nhận cần hoàn tiền', ['txn_ref' => $txnRef]);
            return redirect()->route('customer.dashboard')
                ->with('error', 'Giao dịch đã được thanh toán nhưng không thể tạo đơn do hết hàng. Cửa hàng sẽ liên hệ hoàn tiền cho bạn.');
        }

        $cacheKey = "vnpay_pending_{$txnRef}";
        $pending  = Cache::get($cacheKey);

        // Thất bại / hủy
        if ($responseCode !== null && $responseCode !== '00') {
            if ($pending) {
                Cache::forget($cacheKey);
            }
            Log::info('VNPay Return: thanh toán thất bại/hủy', [
                'txn_ref' => $txnRef, 'response_code' => $responseCode,
            ]);
            return redirect()->route('customer.thanh-toan')
                ->with('error', 'Thanh toán VNPay không thành công hoặc đã bị hủy. Giỏ hàng của bạn vẫn được giữ nguyên, bạn có thể thử lại.');
        }

        // Thành công + IPN chưa tới → FALLBACK
        if ($responseCode === '00') {
            if (!$pending) {
                Log::warning('VNPay Return: thành công nhưng cache miss', ['txn_ref' => $txnRef]);
                return redirect()->route('customer.dashboard')
                    ->with('info', 'Đã nhận thanh toán VNPay. Vui lòng liên hệ cửa hàng nếu đơn chưa xuất hiện sau 5 phút.');
            }

            $expected = (int) round((float) $pending['tong_tien'] * 100);
            $actual   = (int) ($vnpData['vnp_Amount'] ?? 0);
            if ($expected !== $actual) {
                Log::warning('VNPay Return: sai số tiền', [
                    'txn_ref' => $txnRef, 'expected' => $expected, 'actual' => $actual,
                ]);
                return redirect()->route('customer.dashboard')
                    ->with('error', 'Số tiền thanh toán không khớp. Vui lòng liên hệ cửa hàng.');
            }

            try {
                $hoaDon = $this->finalizePendingHoaDon($txnRef, $pending, $vnpData);
                Cache::forget($cacheKey);
                $this->xoaGioHangSauKhiDat($hoaDon, $pending);
                Log::info('VNPay Return: fallback finalize thành công', [
                    'txn_ref' => $txnRef, 'ma_hoa_don' => $hoaDon->ma_hoa_don,
                ]);
                return redirect()->route('customer.dashboard')
                    ->with('success', "🎉 Thanh toán VNPay thành công cho đơn hàng #{$hoaDon->ma_hoa_don}!");
            } catch (\Throwable $e) {
                Log::error('VNPay Return: fallback finalize thất bại', [
                    'txn_ref' => $txnRef, 'error' => $e->getMessage(),
                ]);
                if (str_contains($e->getMessage(), '[STOCK]')) {
                    return redirect()->route('customer.dashboard')
                        ->with('error', 'Giao dịch đã được thanh toán nhưng không thể tạo đơn do hết hàng. Cửa hàng sẽ liên hệ hoàn tiền.');
                }
                return redirect()->route('customer.dashboard')
                    ->with('info', 'Đã nhận thanh toán. Đơn hàng đang được xử lý và sẽ hiển thị trong ít phút.');
            }
        }

        return redirect()->route('customer.dashboard')
            ->with('info', 'Đang xử lý kết quả thanh toán...');
    }

    // ═══════════════════════════════════════════════════════════════════
    // IPN — NHÁNH TXN- (đã tích hợp Case 4)
    // ═══════════════════════════════════════════════════════════════════
    private function xuLyIpnPending(string $txnRef, array $vnpData, ?string $responseCode): \Illuminate\Http\JsonResponse
    {
        // Idempotent
        $existing = HoaDon::where('vnpay_cart_snapshot->txn_ref', $txnRef)->first();
        if ($existing) {
            Log::info('VNPay IPN: HoaDon đã tồn tại, idempotent', [
                'txn_ref' => $txnRef, 'ma_hoa_don' => $existing->ma_hoa_don,
            ]);
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        $cacheKey = "vnpay_pending_{$txnRef}";
        $pending  = Cache::get($cacheKey);

        // Không có pending + có refund marker → đã fail reserve trước đó
        if (!$pending && Cache::has("vnpay_refund_needed_{$txnRef}")) {
            Log::warning('VNPay IPN: giao dịch đã ghi nhận cần hoàn tiền', ['txn_ref' => $txnRef]);
            return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
        }

        if (!$pending) {
            Log::warning('VNPay IPN: cache miss + chưa có HoaDon', ['txn_ref' => $txnRef]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        // Verify amount
        $expected = (int) round((float) $pending['tong_tien'] * 100);
        $actual   = (int) ($vnpData['vnp_Amount'] ?? 0);
        if ($expected !== $actual) {
            Log::warning('VNPay IPN: sai số tiền', [
                'txn_ref' => $txnRef, 'expected' => $expected, 'actual' => $actual,
            ]);
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        // Thất bại → xóa cache, không tạo đơn
        if ($responseCode !== '00') {
            Cache::forget($cacheKey);
            Log::info('VNPay IPN: thanh toán thất bại/hủy', [
                'txn_ref' => $txnRef, 'response_code' => $responseCode,
            ]);
            return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
        }

        // Thành công → tạo HoaDon
        try {
            $hoaDon = $this->finalizePendingHoaDon($txnRef, $pending, $vnpData);
            Cache::forget($cacheKey);
            $this->xoaGioHangSauKhiDat($hoaDon, $pending);
            Log::info('VNPay IPN: tạo HoaDon thành công', [
                'txn_ref' => $txnRef, 'ma_hoa_don' => $hoaDon->ma_hoa_don,
            ]);
            return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
        } catch (\Throwable $e) {
            $laLoiTonKho = str_contains($e->getMessage(), '[STOCK]');
            Log::error('VNPay IPN: finalize thất bại', [
                'txn_ref'   => $txnRef,
                'error'     => $e->getMessage(),
                'permanent' => $laLoiTonKho,
            ]);
            if ($laLoiTonKho) {
                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            }
            return response()->json(['RspCode' => '99', 'Message' => 'Internal error']);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // FINALIZE PENDING — Cache lock + DB transaction + refund marker
    // ═══════════════════════════════════════════════════════════════════
    private function finalizePendingHoaDon(string $ref, array $pending, array $vnpData): HoaDon
    {
        $lock = Cache::lock("vnpay_pending_lock_{$ref}", 30);
        if (!$lock->get()) {
            throw new \RuntimeException('Không lấy được lock xử lý thanh toán.');
        }

        try {
            $existing = HoaDon::where('vnpay_cart_snapshot->txn_ref', $ref)->first();
            if ($existing) {
                return $existing;
            }

            try {
                return DB::transaction(function () use ($pending, $ref, $vnpData) {
                    return $this->taoHoaDonVnpay($pending, $ref, $vnpData);
                }, 3);
            } catch (\Throwable $e) {
                // Rollback xong → ghi refund marker nếu là lỗi vĩnh viễn
                if (str_contains($e->getMessage(), '[STOCK]')) {
                    $this->ghiNhanCanHoanTien($ref, $pending, $vnpData, $e);
                    Cache::forget("vnpay_pending_{$ref}");
                }
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Ghi marker cần hoàn tiền + gửi thông báo khách + log CRITICAL.
     * Dùng Cache (không tạo bảng), TTL 30 ngày.
     */
    private function ghiNhanCanHoanTien(string $ref, array $pending, array $vnpData, \Throwable $e): void
    {
        $data = [
            'txn_ref'        => $ref,
            'ma_khach_hang'  => $pending['ma_khach_hang'] ?? null,
            'tong_tien'      => $pending['tong_tien'] ?? 0,
            'vnpay_amount'   => $vnpData['vnp_Amount'] ?? null,
            'vnpay_txn_no'   => $vnpData['vnp_TransactionNo'] ?? null,
            'vnpay_bank'     => $vnpData['vnp_BankCode'] ?? null,
            'vnpay_pay_date' => $vnpData['vnp_PayDate'] ?? null,
            'error'          => $e->getMessage(),
            'created_at'     => now()->toIso8601String(),
        ];

        Cache::put("vnpay_refund_needed_{$ref}", $data, now()->addDays(30));

        Log::critical('[VNPay] ĐÃ THU TIỀN NHƯNG KHÔNG THỂ TẠO ĐƠN — CẦN HOÀN TIỀN THỦ CÔNG', $data);

        if (!empty($pending['ma_khach_hang'])) {
            try {
                \App\Models\ThongBao::gui(
                    (int) $pending['ma_khach_hang'],
                    'refund_needed',
                    '⚠️ Giao dịch VNPay của bạn đã được ghi nhận thanh toán nhưng đơn hàng không thể hoàn tất do hết hàng. '
                    . 'Cửa hàng sẽ liên hệ hoàn tiền trong thời gian sớm nhất. Mã giao dịch: '
                    . ($vnpData['vnp_TransactionNo'] ?? $ref),
                    null
                );
            } catch (\Throwable $te) {
                Log::error('[VNPay] Không gửi được thông báo refund cho khách', ['error' => $te->getMessage()]);
            }
        }
    }

    /**
     * Tạo HoaDon DA_THANH_TOAN + reserve kho + trừ/cộng điểm.
     * Reserve lỗi → throw RuntimeException [STOCK] → rollback toàn transaction.
     */
    private function taoHoaDonVnpay(array $pending, string $ref, array $vnpData): HoaDon
    {
        $khachHang = KhachHang::find($pending['ma_khach_hang']);
        if (!$khachHang) {
            throw new \RuntimeException("Khách hàng #{$pending['ma_khach_hang']} không tồn tại.");
        }

        $hoaDon = HoaDon::create([
            'ma_khach_hang'          => $khachHang->ma_khach_hang,
            'trang_thai'             => 'PENDING',
            'trang_thai_thanh_toan'  => 'DA_THANH_TOAN',
            'phuong_thuc_thanh_toan' => 'NGAN_HANG',
            'dia_chi_giao'           => $pending['dia_chi_giao'],
            'so_dien_thoai'          => $pending['so_dien_thoai'],
            'tong_tien'              => $pending['tong_tien'],
            'ngay_dat'               => now(),
            'vnpay_transaction_no'   => $vnpData['vnp_TransactionNo'] ?? null,
            'vnpay_bank_code'        => $vnpData['vnp_BankCode'] ?? null,
            'vnpay_pay_date'         => $vnpData['vnp_PayDate'] ?? null,
            'vnpay_response_code'    => $vnpData['vnp_ResponseCode'] ?? null,
            'vnpay_cart_snapshot'    => [
                'txn_ref'        => $ref,
                'loai_don'       => 'gop',
                'ds_checkout'    => $pending['ds_bo_hoa'] ?? [],
                'gio_hang'       => $pending['gio_hang'] ?? [],
                'diem_su_dung'   => $pending['diem_su_dung'] ?? 0,
                'ds_bo_hoa_keys' => $pending['ds_bo_hoa_keys'] ?? [],
                'gio_hang_keys'  => $pending['gio_hang_keys'] ?? [],
            ],
        ]);

        // ═══ RESERVE BÓ HOA — KHÔNG NUỐT LỖI ═══
        if (!empty($pending['ds_bo_hoa'])) {
            foreach ($pending['ds_bo_hoa'] as $idx => $checkout) {
                try {
                    \App\Http\Controllers\Customer\BoHoaController::hoanTatBoHoa(
                        $hoaDon, $checkout, $khachHang, 0, false, false
                    );
                } catch (\Throwable $e) {
                    throw new \RuntimeException(
                        "[STOCK] Không thể giữ kho bó hoa #{$idx}: {$e->getMessage()}",
                        0, $e
                    );
                }
            }
        }

        // ═══ RESERVE SẢN PHẨM LẺ — KHÔNG NUỐT LỖI ═══
        if (!empty($pending['gio_hang'])) {
            try {
                $allocatedRows = \App\Services\InventoryReservationService::reserveSingleItems(
                    $hoaDon, $pending['gio_hang']
                );
                $grouped = collect($allocatedRows)->groupBy(function ($r) {
                    if (!empty($r['ma_bo_hoa'])) return 'bo_' . $r['ma_bo_hoa'];
                    return ($r['ma_san_pham'] ?? 0) . '-' . ($r['ma_chi_tiet_nhap'] ?? 0);
                });
                foreach ($grouped as $items) {
                    $first = $items->first();
                    \App\Models\ChiTietHoaDon::create([
                        'ma_hoa_don'        => $hoaDon->getKey(),
                        'ma_san_pham'       => $first['ma_san_pham'] ?? null,
                        'ma_bo_hoa'         => $first['ma_bo_hoa']   ?? null,
                        'ma_chi_tiet_nhap'  => $first['ma_chi_tiet_nhap'] ?? null,
                        'so_luong'          => $items->sum('so_luong'),
                        'gia_nhap_snapshot' => $first['gia_nhap_snapshot'] ?? 0,
                        'gia_ban_snapshot'  => $first['gia_ban_snapshot']  ?? 0,
                    ]);
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    "[STOCK] Không thể giữ kho sản phẩm lẻ: {$e->getMessage()}",
                    0, $e
                );
            }
        }

        // ═══ CHỈ KHI RESERVE THÀNH CÔNG MỚI XỬ LÝ ĐIỂM ═══
        $diemSuDung = (int) ($pending['diem_su_dung'] ?? 0);
        if ($diemSuDung > 0) {
            $actual = min($diemSuDung, (int) $khachHang->diem_tich_luy);
            if ($actual > 0) {
                $khachHang->decrement('diem_tich_luy', $actual);
            }
        }
        $diemMoi = (int) floor((float) $pending['tong_tien'] / 100000);
        if ($diemMoi > 0) {
            $khachHang->increment('diem_tich_luy', $diemMoi);
        }

        return $hoaDon;
    }

    /**
     * Xóa cart: session (nếu có) + DB theo key (idempotent).
     */
    private function xoaGioHangSauKhiDat(HoaDon $hoaDon, array $context): void
    {
        $this->xoaCartSessionNeuCo($hoaDon);

        $gioHangKeys = $context['gio_hang_keys'] ?? [];
        $boHoaKeys   = $context['ds_bo_hoa_keys'] ?? [];
        if (empty($gioHangKeys) && empty($boHoaKeys)) {
            return;
        }
        $this->xoaGioHangDBTheoKey((int) $hoaDon->ma_khach_hang, $gioHangKeys, $boHoaKeys);
    }

    private function xoaGioHangDBTheoKey(int $maKhachHang, array $gioHangKeys, array $boHoaKeys): void
    {
        $khach = KhachHang::find($maKhachHang);
        if (!$khach) return;

        $data = $khach->du_lieu_gio_hang;
        if (!is_array($data)) return;

        $changed = false;
        if (!empty($gioHangKeys) && !empty($data['gio_hang'])) {
            foreach ($gioHangKeys as $key) {
                if (isset($data['gio_hang'][$key])) {
                    unset($data['gio_hang'][$key]);
                    $changed = true;
                }
            }
        }
        if (!empty($boHoaKeys) && !empty($data['gio_bo_hoa'])) {
            foreach ($boHoaKeys as $key) {
                if (isset($data['gio_bo_hoa'][$key])) {
                    unset($data['gio_bo_hoa'][$key]);
                    $changed = true;
                }
            }
        }
        if ($changed) {
            $khach->du_lieu_gio_hang = $data;
            $khach->save();
        }
    }

    /**
     * Xóa session cart nếu user đang đăng nhập đúng chủ HoaDon.
     */
    private function xoaCartSessionNeuCo(HoaDon $hoaDon): void
    {
        if (!Auth::check()) return;

        $khachHang = Auth::user()->khachHang;
        if (!$khachHang || (int) $khachHang->ma_khach_hang !== (int) $hoaDon->ma_khach_hang) {
            return;
        }

        session()->forget([
            'checkout_items', 'checkout_bo_hoa', 'mua_ngay',
            'diem_su_dung', 'bo_hoa_checkout',
        ]);
        \App\Services\CartService::syncToDatabase();
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEGACY RETURN — giữ nguyên từ code cũ của bạn
    // ═══════════════════════════════════════════════════════════════════
    private function xuLyReturnLegacy(array $vnpData, ?string $txnRef, ?string $responseCode)
    {
        Log::info('VNPay Return (legacy): nhận request', [
            'txn_ref'       => $txnRef,
            'response_code' => $responseCode,
        ]);

        $verify = $this->vnPayService->verifyResponse($vnpData);
        $hoaDon = $txnRef ? HoaDon::find((int) $txnRef) : null;

        if (!$verify['valid'] || !$hoaDon) {
            Log::warning('VNPay Return (legacy): verify fail hoặc không tìm thấy đơn', [
                'reason' => $verify['reason'] ?? 'no_order',
            ]);
            return redirect()->route('customer.dashboard')
                ->with('error', 'Không xác thực được kết quả thanh toán từ VNPay.');
        }

        if ($hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN') {
            Log::info('VNPay Return (legacy): đơn đã DA_THANH_TOAN, bỏ qua', [
                'ma_hoa_don' => $hoaDon->ma_hoa_don,
            ]);
            return redirect()->route('customer.dashboard')
                ->with('success', "🎉 Thanh toán VNPay thành công cho đơn hàng #{$hoaDon->ma_hoa_don}!");
        }

        if ($this->vnPayService->isSuccessResponseCode($responseCode)) {
            if (!$this->vnPayService->isAmountMatch($hoaDon, $vnpData['vnp_Amount'] ?? 0)) {
                Log::warning('VNPay Return (legacy): sai số tiền', ['ma_hoa_don' => $hoaDon->ma_hoa_don]);
                return redirect()->route('customer.dashboard')
                    ->with('error', 'Số tiền thanh toán không khớp với đơn hàng. Vui lòng liên hệ cửa hàng.');
            }

            Log::info('VNPay Return (legacy): bắt đầu finalize fallback', [
                'ma_hoa_don' => $hoaDon->ma_hoa_don,
            ]);

            try {
                DB::transaction(function () use ($hoaDon, $vnpData, $responseCode) {
                    $locked = HoaDon::where('ma_hoa_don', $hoaDon->ma_hoa_don)
                        ->lockForUpdate()
                        ->first();

                    if (!$locked
                        || $locked->trang_thai_thanh_toan === 'DA_THANH_TOAN'
                        || $locked->trang_thai === 'CANCELLED') {
                        Log::info('VNPay Return (legacy): đơn đã xử lý trong lock, bỏ qua', [
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

                Log::info('VNPay Return (legacy): finalize fallback thành công', [
                    'ma_hoa_don' => $hoaDon->ma_hoa_don,
                ]);
            } catch (\Throwable $e) {
                Log::error('VNPay Return (legacy): finalize fallback thất bại', [
                    'ma_hoa_don' => $hoaDon->ma_hoa_don,
                    'error'      => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);
            }

            return redirect()->route('customer.dashboard')
                ->with('success', "🎉 Thanh toán VNPay thành công cho đơn hàng #{$hoaDon->ma_hoa_don}!");
        }

        if ($responseCode !== null && !$this->vnPayService->isSuccessResponseCode($responseCode)) {
            Log::info('VNPay Return (legacy): thanh toán thất bại/hủy, giải phóng reservation', [
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
                Log::error('VNPay Return (legacy) release reservation error: ' . $e->getMessage());
            }

            return redirect()->route('customer.dashboard')
                ->with('error', "Thanh toán cho đơn hàng #{$hoaDon->ma_hoa_don} không thành công hoặc đã bị hủy. Hàng đang giữ đã được giải phóng.");
        }

        return redirect()->route('customer.dashboard')
            ->with('info', "Đơn hàng #{$hoaDon->ma_hoa_don} đang được xác nhận thanh toán, vui lòng đợi trong giây lát và kiểm tra lại.");
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEGACY IPN — giữ nguyên từ code cũ của bạn
    // ═══════════════════════════════════════════════════════════════════
    private function xuLyIpnLegacy(array $vnpData, ?string $txnRef, ?string $responseCode): \Illuminate\Http\JsonResponse
    {
        if (!$txnRef || !ctype_digit((string) $txnRef)) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $hoaDonSoBo = HoaDon::find((int) $txnRef);
        if (!$hoaDonSoBo) {
            Log::warning('VNPay IPN (legacy): không tìm thấy HoaDon', ['ma_hoa_don' => $txnRef]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        Log::info('VNPay IPN (legacy): tìm thấy HoaDon', ['ma_hoa_don' => $hoaDonSoBo->ma_hoa_don]);

        if (!$this->vnPayService->isAmountMatch($hoaDonSoBo, $vnpData['vnp_Amount'] ?? 0)) {
            Log::warning('VNPay IPN (legacy): sai số tiền', ['ma_hoa_don' => $hoaDonSoBo->ma_hoa_don]);
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        Log::info('VNPay IPN (legacy): amount hợp lệ', ['ma_hoa_don' => $hoaDonSoBo->ma_hoa_don]);

        $result = ['RspCode' => '99', 'Message' => 'Unknow error'];

        try {
            DB::transaction(function () use ($hoaDonSoBo, $vnpData, $responseCode, &$result) {
                $hoaDon = HoaDon::where('ma_hoa_don', $hoaDonSoBo->ma_hoa_don)
                    ->lockForUpdate()
                    ->first();

                if (!$hoaDon) {
                    $result = ['RspCode' => '01', 'Message' => 'Order not found'];
                    return;
                }

                if ($hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN' || $hoaDon->trang_thai === 'CANCELLED') {
                    Log::info('VNPay IPN (legacy): đơn đã xử lý trước đó (idempotent)', [
                        'ma_hoa_don' => $hoaDon->ma_hoa_don,
                    ]);
                    $result = ['RspCode' => '02', 'Message' => 'Order already confirmed'];
                    return;
                }

                if ($this->vnPayService->isSuccessResponseCode($responseCode)) {
                    Log::info('VNPay IPN (legacy): bắt đầu finalize', ['ma_hoa_don' => $hoaDon->ma_hoa_don]);

                    app(ThanhToanController::class)->finalizeVnpayThanhCong($hoaDon, [
                        'vnpay_transaction_no' => $vnpData['vnp_TransactionNo'] ?? null,
                        'vnpay_bank_code'      => $vnpData['vnp_BankCode'] ?? null,
                        'vnpay_pay_date'       => $vnpData['vnp_PayDate'] ?? null,
                        'vnpay_response_code'  => $responseCode,
                    ]);

                    Log::info('VNPay IPN (legacy): finalize thành công', ['ma_hoa_don' => $hoaDon->ma_hoa_don]);
                } else {
                    if ($hoaDon->reservations()->where('trang_thai', \App\Models\InventoryReservation::STATUS_RESERVED)->exists()) {
                        \App\Services\InventoryReservationService::releaseOrder($hoaDon);
                    }
                    $hoaDon->trang_thai           = 'CANCELLED';
                    $hoaDon->vnpay_response_code  = $responseCode;
                    $hoaDon->vnpay_transaction_no = $vnpData['vnp_TransactionNo'] ?? null;
                    $hoaDon->vnpay_bank_code      = $vnpData['vnp_BankCode'] ?? null;
                    $hoaDon->save();

                    Log::info('VNPay IPN (legacy): thanh toán thất bại/huỷ, reservation đã release', [
                        'ma_hoa_don'    => $hoaDon->ma_hoa_don,
                        'response_code' => $responseCode,
                    ]);
                }

                $result = ['RspCode' => '00', 'Message' => 'Confirm Success'];
            });
        } catch (\Throwable $e) {
            Log::error('VNPay IPN (legacy): finalize thất bại / xử lý lỗi', [
                'ma_hoa_don' => $hoaDonSoBo->ma_hoa_don,
                'error'      => $e->getMessage(),
            ]);
            $result = ['RspCode' => '99', 'Message' => 'Unknow error'];
        }

        return response()->json($result);
    }
}