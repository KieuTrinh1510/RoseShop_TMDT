<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\ChiTietHoaDon;
use App\Models\ChiTietNhap;
use App\Models\SanPham;
use App\Models\KhachHang;
use App\Models\DiaChiKhachHang;
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
        $user      = Auth::user()->load('khachHang');
        $khachHang = $user->khachHang;
        $khachId   = $khachHang->getKey();

        $danhSachDiaChi = $this->layDanhSachDiaChi($khachHang);
        $diaChiHienTai  = $danhSachDiaChi->firstWhere('la_mac_dinh', true) ?? $danhSachDiaChi->first();
        $type           = request('type');

        // ─── Nhánh: Mua ngay 1 bó hoa đơn lẻ (tự tạo Custom Mix hoặc mẫu mua ngay) ───
        if (session()->has('bo_hoa_checkout')) {
            $boHoaCheckoutData = session('bo_hoa_checkout');
            try {
                $info = \App\Http\Controllers\Customer\BoHoaController::duLieuCheckout(
                    $boHoaCheckoutData, $khachId
                );
                $info['key'] = 'bo_hoa_checkout';
                $dsBoHoa = [$info];
            } catch (\Throwable $e) {
                session()->forget('bo_hoa_checkout');
                $redirectRoute = ($boHoaCheckoutData['loai'] ?? '') === 'TUY_CHON'
                    ? route('customer.bo-hoa.tu-tao')
                    : route('customer.bo-hoa.he-thong');
                return redirect($redirectRoute)->with('error', $e->getMessage());
            }
            $diemSuDung = session('diem_su_dung', 0);
            return view('customer.ThanhToan', [
                'user'           => $user,
                'dsBoHoa'        => $dsBoHoa,
                'gioHang'        => [],
                'diemSuDung'     => $diemSuDung,
                'danhSachDiaChi' => $danhSachDiaChi,
                'diaChiHienTai'  => $diaChiHienTai,
                'loaiCheckout'   => 'mua_ngay_bo_hoa',
            ]);
        }

        // ─── TẬP HỢP TẤT CẢ SẢN PHẨM & BÓ HOA TỪ GIỎ ĐÃ CHỌN ─────────────
        $gioHang   = session('checkout_items', session('mua_ngay', []));
        $rawBoHoa  = session('checkout_bo_hoa', ($type === 'bo_hoa' ? session('gio_bo_hoa', []) : []));

        // Nếu cả 2 đều trống và không phải mua ngay: nạp từ giỏ hàng hiện tại
        if (empty($gioHang) && empty($rawBoHoa) && !session()->has('mua_ngay')) {
            $gioHang  = session('gio_hang', []);
            $rawBoHoa = session('gio_bo_hoa', []);
        }

        $dsBoHoa = [];
        if (!empty($rawBoHoa)) {
            foreach ($rawBoHoa as $key => $item) {
                try {
                    $info = \App\Http\Controllers\Customer\BoHoaController::duLieuCheckout([
                        'loai'          => 'MAU',
                        'ma_mau_bo_hoa' => $item['ma_mau_bo_hoa'],
                        'so_luong'      => $item['so_luong'],
                        'ma_thiep'      => $item['ma_thiep'] ?? null,
                        'loi_nhan'      => $item['loi_nhan'] ?? null,
                    ], $khachId);
                    $info['key'] = $key;
                    $dsBoHoa[] = $info;
                } catch (\Throwable $e) {}
            }
        }

        if (empty($gioHang) && empty($dsBoHoa)) {
            session()->forget(['mua_ngay', 'checkout_items', 'checkout_bo_hoa', 'diem_su_dung']);
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Giỏ hàng của bạn đang trống!');
        }

        $diemSuDung = session('diem_su_dung', 0);

        return view('customer.ThanhToan', [
            'user'           => $user,
            'dsBoHoa'        => $dsBoHoa,
            'gioHang'        => $gioHang,
            'diemSuDung'     => $diemSuDung,
            'danhSachDiaChi' => $danhSachDiaChi,
            'diaChiHienTai'  => $diaChiHienTai,
            'loaiCheckout'   => 'gop',
        ]);
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

        // ─── Chặn theo mức vi phạm (chỉ tính vi phạm CHƯA GỠ) ───
        $viPhamChuaGo = \App\Models\ViPhamKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
            ->where('da_go', false);

        $soViPhamChuaGo = (clone $viPhamChuaGo)->count();

        // >= 3 vi phạm: Tạm khóa mua hàng (không được tạo đơn mới, 3 -> 2 cho mua lại)
        if ($soViPhamChuaGo >= 3) {
            return redirect()->route('customer.vi-pham.index')
                ->with('error', 'Tài khoản đang bị tạm khóa mua hàng do có từ 3 vi phạm chưa xử lý. Vui lòng thanh toán các khoản vi phạm để được mở lại quyền mua hàng.');
        }

        // 2 vi phạm hoặc COD bị giới hạn: Cấm COD (chỉ thanh toán online, 2 -> 1 mở lại COD)
        if ($request->phuong_thuc_thanh_toan === 'COD'
            && ($khachHang->quyen_cod === 'GIOI_HAN' || $soViPhamChuaGo >= 2)) {
            return redirect()->route('customer.thanh-toan')
                ->with('error', 'Tài khoản của bạn đang bị hạn chế COD do vi phạm. Vui lòng thanh toán trước (VNPay) hoặc thanh toán khoản vi phạm tại mục "Xử lý vi phạm".');
        }

        $diemSuDung = (int) session('diem_su_dung', 0);
        $diaChiGiao = $request->dia_chi_chi_tiet . ', ' . $request->xa_phuong
            . ', ' . $request->quan_huyen . ', Hà Nội';

        // Tự động lưu địa chỉ vào sổ địa chỉ nếu chưa có hoặc có thay đổi
        $this->luuDiaChiVaoSo($khachHang, $request, $diaChiGiao);

        $donData = [
            'phuong_thuc_thanh_toan' => $request->phuong_thuc_thanh_toan,
            'dia_chi_giao'           => $diaChiGiao,
            'so_dien_thoai'          => $request->so_dien_thoai,
        ];
        $laVnpay = $request->phuong_thuc_thanh_toan === 'NGAN_HANG';
        $loaiCheckout = $request->input('loai_checkout');

        // ─── Nhánh: Mua ngay 1 bó hoa ──────────────────────────────────
        if (($loaiCheckout === 'mua_ngay_bo_hoa' || (!$loaiCheckout && session()->has('bo_hoa_checkout'))) && session()->has('bo_hoa_checkout')) {
            try {
                $hoaDon = DB::transaction(fn () => \App\Http\Controllers\Customer\BoHoaController::taoDonTuCheckout(
                    session('bo_hoa_checkout'), $khachHang, $donData, $diemSuDung
                ), 3);
            } catch (\Throwable $e) {
                return redirect()->route('customer.thanh-toan')->with('error', $e->getMessage());
            }
            session()->forget(['bo_hoa_checkout', 'diem_su_dung']);
            \App\Services\CartService::syncToDatabase();
            if ($laVnpay) return redirect()->route('vnpay.payment', $hoaDon);
            return redirect()->route('customer.thong-bao', ['tab' => 'don-hien-tai'])
                ->with('success', 'Đơn bó hoa đã được tạo và đang chờ nhân viên xác nhận.');
        }

        // ─── Nhánh: Thanh toán giỏ hàng (Bao gồm Bó hoa + Sản phẩm lẻ) ───
        $rawBoHoa  = session('checkout_bo_hoa', ($loaiCheckout === 'bo_hoa' ? session('gio_bo_hoa', []) : []));
        $gioHang   = session('checkout_items', session('mua_ngay', ($loaiCheckout === 'san_pham' ? session('gio_hang', []) : [])));

        if (empty($rawBoHoa) && empty($gioHang) && !session()->has('mua_ngay')) {
            $rawBoHoa = session('gio_bo_hoa', []);
            $gioHang  = session('gio_hang', []);
        }

        if (empty($rawBoHoa) && empty($gioHang)) {
            return redirect()->route('customer.gio-hang')
                ->with('error', 'Vui lòng chọn sản phẩm để thanh toán.');
        }

        $dsCheckoutBoHoa = [];
        $tongGocBoHoa    = 0;
        if (!empty($rawBoHoa)) {
            foreach ($rawBoHoa as $bKey => $bItem) {
                $itemCheckout = [
                    'loai'          => 'MAU',
                    'ma_mau_bo_hoa' => $bItem['ma_mau_bo_hoa'],
                    'so_luong'      => $bItem['so_luong'],
                    'ma_thiep'      => $bItem['ma_thiep'] ?? null,
                    'loi_nhan'      => $bItem['loi_nhan'] ?? null,
                ];
                try {
                    $info = \App\Http\Controllers\Customer\BoHoaController::duLieuCheckout($itemCheckout, $khachHang->getKey());
                    $tongGocBoHoa += (float) $info['quote']['tong_tien'];
                    $dsCheckoutBoHoa[$bKey] = $itemCheckout;
                } catch (\Throwable $e) {
                    return redirect()->route('customer.thanh-toan')->with('error', 'Mẫu bó hoa không khả dụng: ' . $e->getMessage());
                }
            }
        }

        $tongGocSP   = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
        $tongTienGoc = $tongGocBoHoa + $tongGocSP;
        $giamGia     = min($diemSuDung * self::DIEM_QUY_DOI, $tongTienGoc);
        $tongTien    = max(0, $tongTienGoc - $giamGia);

        try {
            $hoaDon = DB::transaction(function () use (
                $request, $gioHang, $dsCheckoutBoHoa, $khachHang, $tongTien,
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

                // 1. Xử lý các bó hoa (nếu có)
                if (!empty($dsCheckoutBoHoa)) {
                    foreach ($dsCheckoutBoHoa as $checkout) {
                        \App\Http\Controllers\Customer\BoHoaController::hoanTatBoHoa($hoaDon, $checkout, $khachHang, 0, false, false);
                    }
                }

                // 2. Xử lý sản phẩm lẻ (nếu có)
                if (!empty($gioHang)) {
                    $allocatedRows = \App\Services\InventoryReservationService::reserveSingleItems($hoaDon, $gioHang);
                    $grouped = collect($allocatedRows)->groupBy(function ($r) {
                        if (!empty($r['ma_bo_hoa'])) {
                            return 'bo_' . $r['ma_bo_hoa'];
                        }
                        return ($r['ma_san_pham'] ?? 0) . '-' . ($r['ma_chi_tiet_nhap'] ?? 0);
                    });

                    foreach ($grouped as $items) {
                        $first = $items->first();
                        $item = null;
                        if (!empty($first['ma_bo_hoa'])) {
                            $item = collect($gioHang)->first(fn ($row) =>
                                !empty($row['ma_bo_hoa']) && (int) $row['ma_bo_hoa'] === (int) $first['ma_bo_hoa']
                            );
                        } else {
                            $item = collect($gioHang)->first(fn ($row) =>
                                !empty($row['ma_san_pham']) && (int) $row['ma_san_pham'] === (int) $first['ma_san_pham']
                            );
                        }

                        ChiTietHoaDon::create([
                            'ma_hoa_don'        => $hoaDon->getKey(),
                            'ma_san_pham'       => $first['ma_san_pham'] ?? null,
                            'ma_bo_hoa'         => $first['ma_bo_hoa']   ?? null,
                            'ma_chi_tiet_nhap'  => $first['ma_chi_tiet_nhap'] ?? null,
                            'so_luong'          => $items->sum('so_luong'),
                            'gia_nhap_snapshot' => $first['gia_nhap_snapshot'] ?? 0,
                            'gia_ban_snapshot'  => $first['gia_ban_snapshot']  ?? ($item['gia_ban'] ?? 0),
                        ]);
                    }
                }

                // 3. Trừ điểm sử dụng & Cộng điểm tích lũy
                if ($diemSuDung > 0 && $khachHang) {
                    $khachHang->decrement('diem_tich_luy', $diemSuDung);
                }
                $diemMoi = (int) floor($tongTien / self::TICH_DIEM);
                if ($diemMoi > 0 && $khachHang) {
                    $khachHang->increment('diem_tich_luy', $diemMoi);
                }

                // 4. Lưu snapshot VNPay nếu chọn VNPAY
                if ($laVnpay) {
                    $hoaDon->vnpay_cart_snapshot = [
                        'loai_don'        => 'gop',
                        'ds_checkout'     => $dsCheckoutBoHoa,
                        'gio_hang'        => $gioHang,
                        'diem_su_dung'    => $diemSuDung,
                    ];
                    $hoaDon->save();
                }

                return $hoaDon;
            });
        } catch (\Exception $e) {
            return redirect()->route('customer.thanh-toan')->with('error', $e->getMessage());
        }

        // ─── DỌN DẸP SESSION GIỎ HÀNG ───
        if (!empty($gioHang)) {
            $mainCart = session('gio_hang', []);
            foreach (array_keys($gioHang) as $k) {
                if (isset($mainCart[$k])) unset($mainCart[$k]);
            }
            session(['gio_hang' => $mainCart]);
        }

        if (!empty($rawBoHoa)) {
            $mainCartBH = session('gio_bo_hoa', []);
            foreach (array_keys($rawBoHoa) as $bKey) {
                if (isset($mainCartBH[$bKey])) unset($mainCartBH[$bKey]);
            }
            session(['gio_bo_hoa' => $mainCartBH]);
        }

        session()->forget(['checkout_items', 'checkout_bo_hoa', 'mua_ngay', 'diem_su_dung']);
        \App\Services\CartService::syncToDatabase();

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

        // Nếu đơn đã được tạo RESERVED lúc checkout (kiến trúc mới):
        // Chỉ lưu thông tin giao dịch VNPay và đánh dấu DA_THANH_TOAN.
        // Hàng giữ vẫn RESERVED chờ admin xác nhận (CONFIRM) mới trừ kho on_hand.
        if ($hoaDon->reservations()->exists()) {
            $hoaDon->vnpay_transaction_no = $vnpayMeta['vnpay_transaction_no'] ?? null;
            $hoaDon->vnpay_bank_code      = $vnpayMeta['vnpay_bank_code'] ?? null;
            $hoaDon->vnpay_pay_date       = $vnpayMeta['vnpay_pay_date'] ?? null;
            $hoaDon->vnpay_response_code  = $vnpayMeta['vnpay_response_code'] ?? null;
            $hoaDon->trang_thai_thanh_toan = 'DA_THANH_TOAN';
            $hoaDon->save();
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

    /**
     * Lấy danh sách địa chỉ đã lưu hoặc tự khởi tạo từ dia_chi hồ sơ
     */
    private function layDanhSachDiaChi(KhachHang $khachHang)
    {
        $danhSach = $khachHang->danhSachDiaChi()->get();

        if ($danhSach->isEmpty() && !empty($khachHang->dia_chi)) {
            $raw = trim($khachHang->dia_chi);
            $parts = array_map('trim', explode(',', $raw));
            if (count($parts) >= 1 && strtolower(end($parts)) === 'hà nội') {
                array_pop($parts);
            }
            $quan = count($parts) >= 2 ? $parts[count($parts) - 1] : 'Cầu Giấy';
            $phuong = count($parts) >= 3 ? $parts[count($parts) - 2] : 'Dịch Vọng Hậu';
            $chiTiet = count($parts) >= 1 ? implode(', ', array_slice($parts, 0, max(1, count($parts) - 2))) : $raw;

            $diaChiDayDu = $chiTiet . ', ' . $phuong . ', ' . $quan . ', Hà Nội';

            $diaChiMoi = DiaChiKhachHang::create([
                'ma_khach_hang'    => $khachHang->ma_khach_hang,
                'ten_nguoi_nhan'   => $khachHang->ten_khach_hang ?: 'Khách Hàng',
                'so_dien_thoai'    => $khachHang->so_dien_thoai ?: '0988888888',
                'quan_huyen'       => $quan ?: 'Cầu Giấy',
                'xa_phuong'        => $phuong ?: 'Dịch Vọng Hậu',
                'dia_chi_chi_tiet' => $chiTiet ?: 'Địa chỉ chi tiết',
                'dia_chi_day_du'   => $diaChiDayDu,
                'la_mac_dinh'      => true,
            ]);

            $danhSach = collect([$diaChiMoi]);
        }

        return $danhSach;
    }

    /**
     * Tự động lưu địa chỉ vào sổ địa chỉ khi khách đặt hàng
     */
    private function luuDiaChiVaoSo(KhachHang $khachHang, Request $request, string $diaChiGiao): void
    {
        try {
            $exist = DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
                ->where('dia_chi_day_du', $diaChiGiao)
                ->where('so_dien_thoai', $request->so_dien_thoai)
                ->first();

            if (!$exist) {
                $hasAny = DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)->exists();
                $laMacDinh = !$hasAny;

                DiaChiKhachHang::create([
                    'ma_khach_hang'    => $khachHang->ma_khach_hang,
                    'ten_nguoi_nhan'   => $request->ten_nguoi_nhan,
                    'so_dien_thoai'    => $request->so_dien_thoai,
                    'quan_huyen'       => $request->quan_huyen,
                    'xa_phuong'        => $request->xa_phuong,
                    'dia_chi_chi_tiet' => $request->dia_chi_chi_tiet,
                    'dia_chi_day_du'   => $diaChiGiao,
                    'la_mac_dinh'      => $laMacDinh,
                ]);

                if ($laMacDinh) {
                    $khachHang->update(['dia_chi' => $diaChiGiao]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Không thể lưu địa chỉ vào sổ địa chỉ: ' . $e->getMessage());
        }
    }
}