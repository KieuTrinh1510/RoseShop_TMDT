<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\ChiTietHoaDon;
use App\Models\ChiTietNhap;
use App\Models\SanPham;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ThanhToanController extends Controller
{
    const DIEM_QUY_DOI = 1000;
    const TICH_DIEM    = 100000;

    public function index()
    {
        $user = Auth::user()->load('khachHang');

        // Priority: mua_ngay -> checkout_items -> (none allowed)
        if (session()->has('mua_ngay')) {
            $gioHang = session('mua_ngay', []);
        } elseif (session()->has('checkout_items')) {
            $gioHang = session('checkout_items', []);
        } else {
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Giỏ hàng của bạn đang trống!');
        }

        $diemSuDung = session('diem_su_dung', 0);

        return view('customer.ThanhToan', compact('user', 'gioHang', 'diemSuDung'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_nguoi_nhan'         => 'required|string|max:100',
            'so_dien_thoai'          => 'required|string|size:10|regex:/^\d{10}$/',
            'quan_huyen'             => 'required|string|max:100',
            'xa_phuong'              => 'required|string|max:100',
            'dia_chi_chi_tiet'       => 'required|string|max:255',
            
            'phuong_thuc_thanh_toan' => 'required|in:NGAN_HANG,COD',
        ], [
            'ten_nguoi_nhan.required'         => 'Vui lòng nhập tên người nhận.',
            'so_dien_thoai.required'          => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.size'              => 'Số điện thoại phải đúng 10 chữ số.',
            'so_dien_thoai.regex'             => 'Số điện thoại chỉ được chứa chữ số.',
            'quan_huyen.required'             => 'Vui lòng chọn quận/huyện.',
            'xa_phuong.required'              => 'Vui lòng chọn xã/phường.',
            'dia_chi_chi_tiet.required'       => 'Vui lòng nhập địa chỉ chi tiết.',
            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
        ]);

        // Determine which checkout session is present
        $isMuaNgay   = session()->has('mua_ngay');
        $isCheckout  = session()->has('checkout_items');

        if ($isMuaNgay) {
            $gioHang = session('mua_ngay', []);
        } elseif ($isCheckout) {
            $gioHang = session('checkout_items', []);
        } else {
            // Do not allow falling back to full session('gio_hang') per new rules
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Vui lòng chọn sản phẩm để thanh toán.');
        }

        if (empty($gioHang)) {
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Giỏ hàng trống!');
        }

        $user       = Auth::user()->load('khachHang');
        $khachHang  = $user->khachHang;
        $diemSuDung = session('diem_su_dung', 0);

        $tongTienGoc = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
        $giamGia     = min($diemSuDung * self::DIEM_QUY_DOI, $tongTienGoc);
        $tongTien    = max(0, $tongTienGoc - $giamGia);

        $diaChiGiao = $request->dia_chi_chi_tiet
            . ', ' . $request->xa_phuong
            . ', ' . $request->quan_huyen
            . ', Hà Nội';

        
        $laVnpay = $request->phuong_thuc_thanh_toan === 'NGAN_HANG';

        try {
            $hoaDon = DB::transaction(function () use (
                $request,
                $gioHang,
                $khachHang,
                $tongTien,
                $diemSuDung,
                $diaChiGiao,
                $laVnpay
            ) {
                // trang_thai_thanh_toan LUÔN LUÔN là CHUA_THANH_TOAN lúc tạo đơn, kể cả VNPay
                // (đơn VNPay chỉ chuyển DA_THANH_TOAN khi IPN xác nhận thành công — xem Phase 3).
                // Trạng thái thanh toán KHÔNG liên quan đến việc trừ kho — trừ kho xảy ra
                // ngay tại đây, đơn thanh toán online hay COD đều bị trừ kho như nhau.
                $hoaDon = HoaDon::create([
                    'ma_khach_hang'          => $khachHang->ma_khach_hang,
                    'trang_thai'             => 'PENDING',
                    'trang_thai_thanh_toan'  => 'CHUA_THANH_TOAN',
                    'phuong_thuc_thanh_toan' => $request->phuong_thuc_thanh_toan,
                    'dia_chi_giao'           => $diaChiGiao,
                    'so_dien_thoai'          => $request->so_dien_thoai,
                    'tong_tien'              => $tongTien,
                    'ngay_dat'               => now(),
                ]);

                // THAY ĐỔI: trừ tồn kho (FIFO theo lô) + trừ/cộng điểm NGAY KHI TẠO ĐƠN,
                // áp dụng như nhau cho cả COD và VNPay — không còn phân biệt 2 nhánh.
                // Lý do: nếu đơn bị admin từ chối (DonHangController::cancel()), tồn kho
                // sẽ được hoàn lại CHÍNH XÁC theo từng lô đã lấy (dựa vào ma_chi_tiet_nhap
                // đã ghi trong ChiTietHoaDon ở dưới), nên việc trừ sớm không gây sai lệch.
                // Đơn VNPay chưa thanh toán vẫn giữ hàng cho tới khi thanh toán thành công
                // hoặc bị admin từ chối — không dùng vnpay_cart_snapshot để trừ kho lần 2 nữa
                // (xem finalizeVnpayThanhCong() bên dưới, giờ chỉ còn cập nhật trạng thái).
                $this->xuLyFifoVaHoanTat($hoaDon, $gioHang, $diemSuDung, $khachHang, $tongTien);

                return $hoaDon;
            });
        } catch (\Exception $e) {
            return redirect()->route('customer.thanh-toan')
                ->with('error', $e->getMessage());
        }

        // Clear only relevant sessions after successful order (GIỮ NGUYÊN — áp dụng cho
        // cả COD và VNPay, đơn đã được ghi nhận vào DB dù thanh toán VNPay có thành công
        // hay không; nếu thất bại, IPN sẽ tự set trang_thai = CANCELLED, không phục hồi giỏ hàng).
        if ($isMuaNgay) {
            session()->forget(['mua_ngay', 'diem_su_dung']);
        } elseif ($isCheckout) {
            // remove only paid items from the main cart
            $mainCart = session('gio_hang', []);
            $paidKeys = array_keys(session('checkout_items', []));
            foreach ($paidKeys as $k) {
                if (isset($mainCart[$k])) {
                    unset($mainCart[$k]);
                }
            }
            session(['gio_hang' => $mainCart]);
            session()->forget(['checkout_items', 'diem_su_dung']);
        }

        if ($laVnpay) {
            // Chuyển sang VnPayController::payment() để redirect khách sang cổng VNPay.
            return redirect()->route('vnpay.payment', $hoaDon)
                ->with('info', "Đơn hàng #{$hoaDon->ma_hoa_don} đã được tạo. Vui lòng hoàn tất thanh toán qua VNPay.");
        }

        return redirect()->route('customer.dashboard')
            ->with('success', '🎉 Đặt hàng thành công! Phương thức: Thanh toán khi nhận hàng (COD). Chúng tôi sẽ liên hệ sớm nhất.');
    }

    /**
     * PHASE 6 — Logic FIFO + trừ tồn kho + trừ điểm + cộng điểm.
     * Từ nay chỉ có DUY NHẤT 1 nơi gọi: store() — ngay khi tạo đơn, cho CẢ COD và VNPay.
     * finalizeVnpayThanhCong() (khi IPN báo thanh toán VNPay thành công) KHÔNG gọi lại
     * hàm này nữa — nó chỉ cập nhật trang_thai_thanh_toan, tránh trừ kho/điểm 2 lần.
     *
     * Nếu đơn bị admin từ chối sau đó (DonHangController::cancel()), tồn kho được hoàn
     * lại đúng theo từng lô nhờ ma_chi_tiet_nhap đã ghi vào ChiTietHoaDon ở dưới.
     *
     * PHẢI được gọi bên trong 1 DB::transaction() đã mở sẵn ở nơi gọi (không tự mở
     * transaction ở đây) vì cần lockForUpdate() nằm chung transaction với thao tác
     * tạo HoaDon.
     *
     * @param array $gioHang mảng các item dạng ['ma_san_pham','so_luong','gia_ban','ten_san_pham']
     */
    protected function xuLyFifoVaHoanTat(HoaDon $hoaDon, array $gioHang, int $diemSuDung, $khachHang, float $tongTien): void
    {
        $yeuCauTheoSanPham = collect($gioHang)
            ->groupBy('ma_san_pham')
            ->map(fn($items) => $items->sum('so_luong'));

        // ===== THAY ĐỔI QUAN TRỌNG (giữ nguyên từ code gốc) =====
        // Gộp logic FIFO và tạo ChiTietHoaDon thành MỘT vòng lặp duy nhất.
        // Ngay khi trừ số lượng từ 1 batch cụ thể, tạo luôn dòng ChiTietHoaDon
        // ghi lại chính xác ma_chi_tiet_nhap (batch) và số lượng đã lấy từ batch đó.
        foreach ($yeuCauTheoSanPham as $maSanPham => $soLuongYeuCau) {
            $mauItem = collect($gioHang)->firstWhere('ma_san_pham', $maSanPham);
            $giaBanSnapshot = $mauItem['gia_ban'];

            $soLuongCanTru = $soLuongYeuCau;

            // lockForUpdate(): khóa các dòng ChiTietNhap liên quan trong phạm vi
            // transaction này, tránh 2 giao dịch đồng thời cùng đọc một giá trị
            // so_luong_con_lai rồi cùng trừ đè lên nhau (race condition).
            $loNhapTheoFIFO = ChiTietNhap::with('phieuNhap')
                ->where('ma_san_pham', $maSanPham)
                ->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
                ->lockForUpdate()
                ->get()
                ->sortBy(function ($lot) {
                    return [
                        $lot->phieuNhap->ngay_nhap ?? now(),
                        $lot->ma_chi_tiet_nhap,
                    ];
                });

            foreach ($loNhapTheoFIFO as $lot) {
                if ($soLuongCanTru <= 0) {
                    break;
                }

                $tru = min($lot->so_luong_con_lai, $soLuongCanTru);
                $lot->decrement('so_luong_con_lai', $tru);
                $soLuongCanTru -= $tru;

                // Ghi lại CHÍNH XÁC batch đã lấy + số lượng đã lấy từ batch đó.
                ChiTietHoaDon::create([
                    'ma_hoa_don'        => $hoaDon->ma_hoa_don,
                    'ma_san_pham'       => $maSanPham,
                    'ma_chi_tiet_nhap'  => $lot->ma_chi_tiet_nhap,
                    'so_luong'          => $tru,
                    'gia_ban_snapshot'  => $giaBanSnapshot,
                    'gia_nhap_snapshot' => $lot->gia_nhap,
                ]);
            }

            // An toàn: nếu sau khi khóa dòng thực tế mà vẫn không đủ hàng (do đơn khác
            // vừa giành mất hàng giữa lúc pre-check và lúc khóa), chặn lại toàn bộ giao dịch.
            if ($soLuongCanTru > 0) {
                $tenSanPham = $mauItem['ten_san_pham'] ?? 'Sản phẩm';
                throw new \Exception("Sản phẩm \"{$tenSanPham}\" vừa hết hàng do có đơn khác mua trước. Vui lòng thử lại.");
            }
        }

        foreach ($gioHang as $item) {
            SanPham::where('ma_san_pham', $item['ma_san_pham'])
                ->decrement('so_luong', $item['so_luong']);
        }

        if ($diemSuDung > 0 && $khachHang) {
            $khachHang->decrement('diem_tich_luy', $diemSuDung);
        }

        $diemMoi = (int) floor($tongTien / self::TICH_DIEM);

        if ($diemMoi > 0 && $khachHang) {
            $khachHang->increment('diem_tich_luy', $diemMoi);
        }
    }

    /**
     * PHASE 6 — Được VnPayController::ipn() gọi (bên trong DB::transaction +
     * lockForUpdate() đã mở sẵn ở đó) NGAY SAU KHI verify chữ ký + verify amount
     * + xác nhận vnp_ResponseCode == '00' thành công.
     *
     * THAY ĐỔI: tồn kho + điểm giờ đã được trừ/cộng NGAY LÚC TẠO ĐƠN (trong store(),
     * xem xuLyFifoVaHoanTat() gọi ở trên) — áp dụng như nhau cho COD và VNPay. Vì vậy
     * hàm này KHÔNG được gọi lại xuLyFifoVaHoanTat() nữa (tránh trừ kho/điểm 2 lần).
     * Trách nhiệm duy nhất còn lại của hàm này là XÁC NHẬN đơn đã thanh toán: cập nhật
     * trang_thai_thanh_toan = DA_THANH_TOAN và lưu lại metadata giao dịch VNPay.
     *
     * Idempotency (chống xử lý 2 lần) đã được VnPayController::ipn() đảm bảo TRƯỚC
     * khi gọi hàm này (check trang_thai_thanh_toan/trang_thai trước khi gọi), nên
     * hàm này không tự check lại — nó giả định luôn "đây là lần xử lý hợp lệ đầu tiên".
     */
    public function finalizeVnpayThanhCong(HoaDon $hoaDon, array $vnpayMeta): void
    {
        if ($hoaDon->phuong_thuc_thanh_toan !== 'NGAN_HANG') {
            throw new \RuntimeException("HoaDon #{$hoaDon->ma_hoa_don} không phải đơn thanh toán VNPay.");
        }

        $hoaDon->trang_thai_thanh_toan = 'DA_THANH_TOAN';
        $hoaDon->vnpay_transaction_no  = $vnpayMeta['vnpay_transaction_no'] ?? null;
        $hoaDon->vnpay_bank_code       = $vnpayMeta['vnpay_bank_code'] ?? null;
        $hoaDon->vnpay_pay_date        = $vnpayMeta['vnpay_pay_date'] ?? null;
        $hoaDon->vnpay_response_code   = $vnpayMeta['vnpay_response_code'] ?? null;
        $hoaDon->save();
    }
}