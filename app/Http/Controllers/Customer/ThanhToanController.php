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
use Illuminate\Support\Facades\Log;

class ThanhToanController extends Controller
{
    const DIEM_QUY_DOI = 1000;
    const TICH_DIEM    = 100000;

    public const HANOI_DISTRICTS = [
        'Ba Đình','Hoàn Kiếm','Đống Đa','Hai Bà Trưng','Cầu Giấy','Thanh Xuân',
        'Hoàng Mai','Long Biên','Nam Từ Liêm','Bắc Từ Liêm','Tây Hồ','Hà Đông',
        'Gia Lâm','Đông Anh','Sóc Sơn','Thạch Thất','Quốc Oai','Mê Linh','Chương Mỹ',
    ];

    public function index()
    {
        $user    = Auth::user()->load('khachHang');
        $khachId = $user->khachHang->getKey();

        // ─── Nhánh 1: Giỏ bó hoa mẫu (nhiều bó) ────────────────────────
        if (session()->has('gio_bo_hoa')) {
            $dsCheckout = [];
            foreach (session('gio_bo_hoa', []) as $key => $item) {
                try {
                    $info = \App\Http\Controllers\Customer\BoHoaController::duLieuCheckout([
                        'loai'          => 'MAU',
                        'ma_mau_bo_hoa' => $item['ma_mau_bo_hoa'],
                        'so_luong'      => $item['so_luong'],
                        'ma_thiep'      => $item['ma_thiep'] ?? null,
                        'loi_nhan'      => $item['loi_nhan'] ?? null,
                    ], $khachId);
                    $info['key'] = $key;
                    $dsCheckout[] = $info;
                } catch (\Throwable $e) {
                    return redirect()->route('customer.bo-hoa.gio.index')
                        ->with('error', $e->getMessage());
                }
            }
            if (empty($dsCheckout)) {
                session()->forget('gio_bo_hoa');
                return redirect()->route('customer.bo-hoa.he-thong')
                    ->with('error', 'Giỏ bó hoa trống.');
            }
            $diemSuDung = session('diem_su_dung', 0);
            return view('customer.ThanhToan', [
                'user'       => $user,
                'dsBoHoa'    => $dsCheckout,
                'gioHang'    => [],
                'diemSuDung' => $diemSuDung,
            ]);
        }

        // ─── Nhánh 2: Mua ngay 1 bó (giữ nguyên hành vi cũ) ────────────
        if (session()->has('bo_hoa_checkout')) {
            try {
                $info = \App\Http\Controllers\Customer\BoHoaController::duLieuCheckout(
                    session('bo_hoa_checkout'), $khachId
                );
                $info['key'] = 'bo_hoa_checkout';
                $dsBoHoa = [$info];
            } catch (\Throwable $e) {
                session()->forget('bo_hoa_checkout');
                return redirect()->route('customer.bo-hoa.he-thong')->with('error', $e->getMessage());
            }
            $diemSuDung = session('diem_su_dung', 0);
            return view('customer.ThanhToan', [
                'user'       => $user,
                'dsBoHoa'    => $dsBoHoa,
                'gioHang'    => [],
                'diemSuDung' => $diemSuDung,
            ]);
        }

        // ─── Nhánh 3: Giỏ sản phẩm đơn lẻ (giữ nguyên) ─────────────────
               if (session()->has('mua_ngay')) {
            $gioHang = session('mua_ngay', []);
        } elseif (session()->has('checkout_items')) {
            $gioHang = session('checkout_items', []);
        } else {
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Giỏ hàng của bạn đang trống!');
        }

        // ✅ Guard: nếu session có nhưng rỗng → cũng phải chặn
        if (empty($gioHang)) {
            session()->forget(['mua_ngay', 'checkout_items', 'diem_su_dung']);
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
            'quan_huyen'             => ['required','string','max:100', \Illuminate\Validation\Rule::in(self::HANOI_DISTRICTS)],
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

        $user      = Auth::user()->load('khachHang');
        $khachHang = $user->khachHang;
        $diemSuDung = (int) session('diem_su_dung', 0);
        $diaChiGiao = $request->dia_chi_chi_tiet . ', ' . $request->xa_phuong
            . ', ' . $request->quan_huyen . ', Hà Nội';

        $donData = [
            'phuong_thuc_thanh_toan' => $request->phuong_thuc_thanh_toan,
            'dia_chi_giao'           => $diaChiGiao,
            'so_dien_thoai'          => $request->so_dien_thoai,
        ];
        $laVnpay = $request->phuong_thuc_thanh_toan === 'NGAN_HANG';

        // ─── Nhánh 1: Giỏ bó hoa mẫu ──────────────────────────────────
        if (session()->has('gio_bo_hoa')) {
            $dsCheckout = [];
            foreach (session('gio_bo_hoa', []) as $key => $item) {
                $dsCheckout[] = [
                    'loai'          => 'MAU',
                    'ma_mau_bo_hoa' => $item['ma_mau_bo_hoa'],
                    'so_luong'      => $item['so_luong'],
                    'ma_thiep'      => $item['ma_thiep'] ?? null,
                    'loi_nhan'      => $item['loi_nhan'] ?? null,
                ];
            }

            try {
                $hoaDon = DB::transaction(fn () => \App\Http\Controllers\Customer\BoHoaController::taoDonTuGioBoHoa(
                    $dsCheckout, $khachHang, $donData, $diemSuDung
                ), 3);
            } catch (\Throwable $e) {
                return redirect()->route('customer.thanh-toan')->with('error', $e->getMessage());
            }

            session()->forget(['gio_bo_hoa', 'diem_su_dung']);

            if ($laVnpay) return redirect()->route('vnpay.payment', $hoaDon);
            return redirect()->route('customer.thong-bao', ['tab' => 'don-hien-tai'])
                ->with('success', 'Đơn bó hoa đã được tạo và đang chờ nhân viên xác nhận.');
        }

        // ─── Nhánh 2: Mua ngay 1 bó ────────────────────────────────────
        if (session()->has('bo_hoa_checkout')) {
            try {
                $hoaDon = DB::transaction(fn () => \App\Http\Controllers\Customer\BoHoaController::taoDonTuCheckout(
                    session('bo_hoa_checkout'), $khachHang, $donData, $diemSuDung
                ), 3);
            } catch (\Throwable $e) {
                return redirect()->route('customer.thanh-toan')->with('error', $e->getMessage());
            }
            session()->forget(['bo_hoa_checkout', 'diem_su_dung']);
            if ($laVnpay) return redirect()->route('vnpay.payment', $hoaDon);
            return redirect()->route('customer.thong-bao', ['tab' => 'don-hien-tai'])
                ->with('success', 'Đơn bó hoa đã được tạo và đang chờ nhân viên xác nhận.');
        }

        // ─── Nhánh 3: Giỏ sản phẩm đơn lẻ (giữ nguyên) ─────────────────
        $isMuaNgay  = session()->has('mua_ngay');
        $isCheckout = session()->has('checkout_items');

        if ($isMuaNgay) {
            $gioHang = session('mua_ngay', []);
        } elseif ($isCheckout) {
            $gioHang = session('checkout_items', []);
        } else {
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Vui lòng chọn sản phẩm để thanh toán.');
        }

        if (empty($gioHang)) {
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Giỏ hàng trống!');
        }

        $tongTienGoc = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
        $giamGia     = min($diemSuDung * self::DIEM_QUY_DOI, $tongTienGoc);
        $tongTien    = max(0, $tongTienGoc - $giamGia);

        try {
            $hoaDon = DB::transaction(function () use (
                $request, $gioHang, $khachHang, $tongTien,
                $diemSuDung, $diaChiGiao, $laVnpay
            ) {
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

                if ($laVnpay) {
                    $hoaDon->vnpay_cart_snapshot = [
                        'loai_don'     => 'gio_hang',
                        'gio_hang'     => $gioHang,
                        'diem_su_dung' => $diemSuDung,
                        'tong_tien'    => $tongTien,
                    ];
                    $hoaDon->save();
                } else {
                    $this->xuLyFifoVaHoanTat($hoaDon, $gioHang, $diemSuDung, $khachHang, $tongTien);
                }

                return $hoaDon;
            });
        } catch (\Exception $e) {
            return redirect()->route('customer.thanh-toan')->with('error', $e->getMessage());
        }

        if ($isMuaNgay) {
            session()->forget(['mua_ngay', 'diem_su_dung']);
        } elseif ($isCheckout) {
            $mainCart = session('gio_hang', []);
            $paidKeys = array_keys(session('checkout_items', []));
            foreach ($paidKeys as $k) {
                if (isset($mainCart[$k])) unset($mainCart[$k]);
            }
            session(['gio_hang' => $mainCart]);
            session()->forget(['checkout_items', 'diem_su_dung']);
        }

        if ($laVnpay) {
            return redirect()->route('vnpay.payment', $hoaDon)
                ->with('info', "Đơn hàng #{$hoaDon->ma_hoa_don} đã được tạo. Vui lòng hoàn tất thanh toán qua VNPay.");
        }

        return redirect()->route('customer.dashboard')
            ->with('success', '🎉 Đặt hàng thành công! Phương thức: COD. Chúng tôi sẽ liên hệ sớm nhất.');
    }

    protected function xuLyFifoVaHoanTat(HoaDon $hoaDon, array $gioHang, int $diemSuDung, $khachHang, float $tongTien): void
    {
        $phanBo = \App\Http\Controllers\Admin\BoHoaController::phanBo($gioHang);

        $grouped = collect($phanBo)->groupBy(
            fn ($r) => ($r['ma_san_pham'] ?? 0).'-'.($r['ma_chi_tiet_nhap'] ?? 0)
        );

        foreach ($grouped as $items) {
            $first = $items->first();
            $item  = collect($gioHang)->firstWhere('ma_san_pham', $first['ma_san_pham']);
            unset($first['vai_tro']);
            ChiTietHoaDon::create([
                'ma_hoa_don'        => $hoaDon->getKey(),
                'ma_san_pham'       => $first['ma_san_pham'],
                'ma_chi_tiet_nhap'  => $first['ma_chi_tiet_nhap'] ?? null,
                'so_luong'          => $items->sum('so_luong'),
                'gia_nhap_snapshot' => $first['gia_nhap_snapshot'] ?? 0,
                'gia_ban_snapshot'  => $item['gia_ban'] ?? ($first['gia_ban_snapshot'] ?? 0),
            ]);
        }

        if ($diemSuDung > 0 && $khachHang) {
            $khachHang->decrement('diem_tich_luy', $diemSuDung);
        }

        $diemMoi = (int) floor($tongTien / self::TICH_DIEM);
        if ($diemMoi > 0 && $khachHang) {
            $khachHang->increment('diem_tich_luy', $diemMoi);
        }
    }

    public function finalizeVnpayThanhCong(HoaDon $hoaDon, array $vnpayMeta): void
    {
        if ($hoaDon->phuong_thuc_thanh_toan !== 'NGAN_HANG') {
            throw new \RuntimeException("HoaDon #{$hoaDon->ma_hoa_don} không phải đơn thanh toán VNPay.");
        }

        if ($hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN' || $hoaDon->trang_thai === 'CANCELLED') {
            return;
        }

        $snapshot = $hoaDon->vnpay_cart_snapshot;

        if (is_array($snapshot) && ($snapshot['loai_don'] ?? null) === 'bo_hoa') {
            $khachHang = $hoaDon->khachHang;
            if (!$khachHang) {
                throw new \RuntimeException("HoaDon #{$hoaDon->ma_hoa_don}: không tìm thấy khách hàng.");
            }
            \App\Http\Controllers\Customer\BoHoaController::hoanTatBoHoa(
                $hoaDon,
                $snapshot['bo_hoa_checkout'] ?? [],
                $khachHang,
                (int) ($snapshot['diem_su_dung'] ?? 0),
                false
            );
        } elseif (is_array($snapshot) && ($snapshot['loai_don'] ?? null) === 'gio_bo_hoa') {
            $khachHang = $hoaDon->khachHang;
            if (!$khachHang) {
                throw new \RuntimeException("HoaDon #{$hoaDon->ma_hoa_don}: không tìm thấy khách hàng.");
            }
            \App\Http\Controllers\Customer\BoHoaController::hoanTatGioBoHoa(
                $hoaDon,
                $snapshot['ds_checkout'] ?? [],
                $khachHang,
                (int) ($snapshot['diem_su_dung'] ?? 0)
            );
        } elseif (is_array($snapshot) && !empty($snapshot['gio_hang'])) {
            $gioHang    = $snapshot['gio_hang'];
            $diemSuDung = (int) ($snapshot['diem_su_dung'] ?? 0);
            $tongTien   = (float) ($snapshot['tong_tien'] ?? $hoaDon->tong_tien);
            $khachHang  = $hoaDon->khachHang;

            if (!$khachHang) {
                throw new \RuntimeException("HoaDon #{$hoaDon->ma_hoa_don}: không tìm thấy khách hàng.");
            }
            $this->xuLyFifoVaHoanTat($hoaDon, $gioHang, $diemSuDung, $khachHang, $tongTien);
        } else {
            throw new \RuntimeException(
                "HoaDon #{$hoaDon->ma_hoa_don}: thiếu hoặc sai định dạng vnpay_cart_snapshot."
            );
        }

        $hoaDon->vnpay_transaction_no = $vnpayMeta['vnpay_transaction_no'] ?? null;
        $hoaDon->vnpay_bank_code      = $vnpayMeta['vnpay_bank_code'] ?? null;
        $hoaDon->vnpay_pay_date       = $vnpayMeta['vnpay_pay_date'] ?? null;
        $hoaDon->vnpay_response_code  = $vnpayMeta['vnpay_response_code'] ?? null;
        $hoaDon->trang_thai_thanh_toan = 'DA_THANH_TOAN';
        $hoaDon->save();
    }
}