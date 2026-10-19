<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ThanhToanViPhamKhachHang;
use App\Models\ViPhamKhachHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ThanhToanViPhamController extends Controller
{
    /** Link VNPay (vnp_ExpireDate) sống 15 phút — khớp VnPayViPhamService. */
    private const PHUT_HET_HAN_VNPAY = 15;

    /**
     * Danh sách vi phạm CHƯA THANH TOÁN của khách đang đăng nhập.
     */
    public function index()
    {
        $khach = Auth::user()->khachHang;
        abort_unless($khach, 403, 'Tài khoản chưa có hồ sơ khách hàng.');

        $viPhams = ViPhamKhachHang::where('ma_khach_hang', $khach->ma_khach_hang)
            ->where('da_go', false)
            ->with(['hoaDon', 'thanhToanViPham'])
            ->orderByDesc('ma_vi_pham')
            ->get();

        return view('customer.DanhSachViPham', compact('viPhams'));
    }

    /**
     * Trang thanh toán 1 vi phạm cụ thể.
     */
    public function show($maViPham)
    {
        $khach = Auth::user()->khachHang;
        abort_unless($khach, 403);

        $viPham = ViPhamKhachHang::where('ma_vi_pham', $maViPham)
            ->where('ma_khach_hang', $khach->ma_khach_hang)
            ->with(['hoaDon', 'thanhToanViPham'])
            ->firstOrFail();

        if ($viPham->da_go) {
            return redirect()->route('customer.vi-pham.index')
                ->with('error', 'Vi phạm này đã được xử lý, không cần thanh toán nữa.');
        }

        if ($viPham->hoaDon?->trang_thai_thanh_toan === 'DA_THANH_TOAN') {
            return redirect()->route('customer.vi-pham.index')
                ->with('info', 'Đơn này đã được thanh toán trước đó nên không cần thanh toán thêm.');
        }

        return view('customer.ThanhToanViPham', compact('viPham'));
    }

    /**
     * Khách bấm "Thanh toán qua VNPay" → tạo giao dịch mới + redirect VNPay.
     */
    public function taoVnPay(Request $request, $maViPham)
    {
        $khach = Auth::user()->khachHang;
        abort_unless($khach, 403);

        $viPham = ViPhamKhachHang::where('ma_vi_pham', $maViPham)
            ->where('ma_khach_hang', $khach->ma_khach_hang)
            ->with('hoaDon')
            ->firstOrFail();

        if ($viPham->da_go) {
            return back()->with('error', 'Vi phạm này đã được xử lý.');
        }

        // Đơn đã thanh toán trước (VNPay) rồi bị hủy do khách → không cần hoàn trả thêm
        if ($viPham->hoaDon?->trang_thai_thanh_toan === 'DA_THANH_TOAN') {
            return back()->with('error', 'Đơn này đã được thanh toán trước đó nên không cần thanh toán thêm.');
        }

        // Kiểm tra đã có giao dịch DA_THANH_TOAN chưa
        $daTra = ThanhToanViPhamKhachHang::where('ma_vi_pham', $maViPham)
            ->where('trang_thai', ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN)
            ->exists();
        if ($daTra) {
            return back()->with('error', 'Vi phạm này đã được thanh toán.');
        }

        // Giao dịch đang chờ: link VNPay chỉ sống 15 phút.
        //  - Còn hạn  → dùng lại giao dịch cũ (tránh tạo trùng, tránh mất tiền nếu IPN tới muộn)
        //  - Hết hạn → đánh dấu hủy để khách thanh toán lại
        $dangCho = ThanhToanViPhamKhachHang::where('ma_vi_pham', $maViPham)
            ->where('trang_thai', ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN)
            ->orderByDesc('ma_thanh_toan')
            ->first();
        if ($dangCho) {
            if ($dangCho->created_at && $dangCho->created_at->gt(now()->subMinutes(self::PHUT_HET_HAN_VNPAY))) {
                return redirect()->route('vnpay.vi-pham.payment', $dangCho->ma_thanh_toan);
            }
            ThanhToanViPhamKhachHang::where('ma_vi_pham', $maViPham)
                ->where('trang_thai', ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN)
                ->update([
                    'trang_thai' => ThanhToanViPhamKhachHang::TT_DA_HUY,
                    'ghi_chu'    => 'Tự hủy: quá hạn thanh toán VNPay, khách tạo giao dịch mới.',
                ]);
        }

        // Số tiền = hoa_don.tong_tien (theo Q5-a)
        $soTien = (float) ($viPham->hoaDon?->tong_tien ?? 0);
        if ($soTien <= 0) {
            return back()->with('error', 'Không xác định được số tiền cần thanh toán.');
        }

        $tt = DB::transaction(function () use ($viPham, $khach, $soTien) {
            return ThanhToanViPhamKhachHang::create([
                'ma_vi_pham'    => $viPham->ma_vi_pham,
                'ma_khach_hang' => $khach->ma_khach_hang,
                'ma_hoa_don'    => $viPham->ma_hoa_don,
                'so_tien'       => $soTien,
                'phuong_thuc'   => ThanhToanViPhamKhachHang::PT_VNPAY,
                'trang_thai'    => ThanhToanViPhamKhachHang::TT_CHO_THANH_TOAN,
            ]);
        });

        return redirect()->route('vnpay.vi-pham.payment', $tt->ma_thanh_toan);
    }
}