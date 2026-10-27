<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ChiTietNhap;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GioHangController extends Controller
{
    const DIEM_QUY_DOI = 1000;

    public function index()
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;
        if ($user) {
            CartService::restoreFromDatabase($user);
        }

        $gioHang    = session('gio_hang', []);
        $gioBoHoa   = session('gio_bo_hoa', []);
        $diemSuDung = session('diem_su_dung', 0);

        $khachId = $user?->khachHang?->getKey();
        $dsBoHoa = CartService::getBouquetCartDetails($gioBoHoa, $khachId);

        return view('customer.GioHang', compact('user', 'gioHang', 'gioBoHoa', 'dsBoHoa', 'diemSuDung'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.ma_chi_tiet_nhap' => 'required|integer|exists:chi_tiet_nhap,ma_chi_tiet_nhap',
            'items.*.so_luong'         => 'required|integer|min:1',
        ]);

        $gioHang = session('gio_hang', []);

        foreach ($request->items as $item) {
            $chiTiet = ChiTietNhap::with('sanPham')
                ->where('ma_chi_tiet_nhap', $item['ma_chi_tiet_nhap'])
                ->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
                ->first();

            if (!$chiTiet || !$chiTiet->sanPham) {
                continue;
            }

            $key = 'lo_' . $chiTiet->ma_chi_tiet_nhap;

            $soLuongMoi = ($gioHang[$key]['so_luong'] ?? 0) + (int) $item['so_luong'];
            $soLuongMoi = min($soLuongMoi, $chiTiet->so_luong_con_lai);

            $sp = $chiTiet->sanPham;
            $fs = $sp->active_flash_sale;
            $giaBan = (float) ($sp->gia_ban_hien_tai ?? $chiTiet->gia_ban);
            $maFlashSale = null;
            if ($fs && $fs->available_quota > 0) {
                $giaBan = (float) $fs->gia_flash_sale;
                $maFlashSale = $fs->ma_flash_sale;
            }

            $gioHang[$key] = [
                'ma_chi_tiet_nhap' => $chiTiet->ma_chi_tiet_nhap,
                'ma_san_pham'      => $chiTiet->ma_san_pham,
                'ten_san_pham'     => $chiTiet->sanPham->ten_san_pham,
                'hinh_anh'         => $chiTiet->sanPham->hinh_anh,
                'gia_ban'          => $giaBan,
                'gia_nhap'         => (float) $chiTiet->gia_nhap,
                'so_luong'         => $soLuongMoi,
                'so_luong_con_lai' => $chiTiet->so_luong_con_lai,
                'ma_flash_sale'    => $maFlashSale,
            ];
        }

        session(['gio_hang' => $gioHang]);
        session()->forget('diem_su_dung');
        CartService::syncToDatabase();

        $tongSoLuong = count($gioHang) + count(session('gio_bo_hoa', []));

        return response()->json([
            'success'     => true,
            'message'     => 'Đã thêm vào giỏ hàng!',
            'so_san_pham' => $tongSoLuong,
        ]);
    }

    public function buyNow(Request $request)
    {
        $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.ma_chi_tiet_nhap' => 'required|integer|exists:chi_tiet_nhap,ma_chi_tiet_nhap',
            'items.*.so_luong'         => 'required|integer|min:1',
        ]);

        $muaNgay = [];

        foreach ($request->items as $item) {
            $chiTiet = ChiTietNhap::with('sanPham')
                ->where('ma_chi_tiet_nhap', $item['ma_chi_tiet_nhap'])
                ->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
                ->first();

            if (!$chiTiet || !$chiTiet->sanPham) {
                continue;
            }

            $key = 'lo_' . $chiTiet->ma_chi_tiet_nhap;

            $sp = $chiTiet->sanPham;
            $fs = $sp->active_flash_sale;
            $giaBan = (float) ($sp->gia_ban_hien_tai ?? $chiTiet->gia_ban);
            $maFlashSale = null;
            if ($fs && $fs->available_quota > 0) {
                $giaBan = (float) $fs->gia_flash_sale;
                $maFlashSale = $fs->ma_flash_sale;
            }

            $muaNgay[$key] = [
                'ma_chi_tiet_nhap' => $chiTiet->ma_chi_tiet_nhap,
                'ma_san_pham'      => $chiTiet->ma_san_pham,
                'ten_san_pham'     => $chiTiet->sanPham->ten_san_pham,
                'hinh_anh'         => $chiTiet->sanPham->hinh_anh,
                'gia_ban'          => $giaBan,
                'gia_nhap'         => (float) $chiTiet->gia_nhap,
                'so_luong'         => min((int) $item['so_luong'], $chiTiet->so_luong_con_lai),
                'so_luong_con_lai' => $chiTiet->so_luong_con_lai,
                'ma_flash_sale'    => $maFlashSale,
            ];
        }

        session(['mua_ngay' => $muaNgay]);
        session()->forget('diem_su_dung');

        return response()->json([
            'success' => true,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'key'      => 'required|string',
            'so_luong' => 'required|integer|min:0',
        ]);

        $gioHang = session('gio_hang', []);

        if (!isset($gioHang[$request->key])) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        if ((int) $request->so_luong === 0) {
            unset($gioHang[$request->key]);
        } else {
            $max = $gioHang[$request->key]['so_luong_con_lai'];
            $gioHang[$request->key]['so_luong'] = min((int) $request->so_luong, $max);
        }

        session(['gio_hang' => $gioHang]);
        session()->forget('diem_su_dung');
        CartService::syncToDatabase();

        $tongTien = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
        $tongSoLuong = count($gioHang) + count(session('gio_bo_hoa', []));

        return response()->json([
            'success'     => true,
            'so_san_pham' => $tongSoLuong,
            'tong_tien'   => $tongTien,
        ]);
    }

    public function remove(Request $request)
    {
        $gioHang = session('gio_hang', []);

        unset($gioHang[$request->key]);

        session(['gio_hang' => $gioHang]);
        session()->forget('diem_su_dung');
        CartService::syncToDatabase();

        $tongTien = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
        $tongSoLuong = count($gioHang) + count(session('gio_bo_hoa', []));

        return response()->json([
            'success'     => true,
            'so_san_pham' => $tongSoLuong,
            'tong_tien'   => $tongTien,
        ]);
    }

    public function checkout(Request $request)
    {
        $selectedItems    = $request->input('selected_items', []);
        $selectedBouquets = $request->input('selected_bouquets', []);

        $gioHang  = session('gio_hang', []);
        $gioBoHoa = session('gio_bo_hoa', []);

        $checkout   = [];
        $checkoutBH = [];

        foreach ($selectedItems as $key) {
            if (isset($gioHang[$key])) {
                $checkout[$key] = $gioHang[$key];
            }
        }

        foreach ($selectedBouquets as $bKey) {
            if (isset($gioBoHoa[$bKey])) {
                $checkoutBH[$bKey] = $gioBoHoa[$bKey];
            }
        }

        if (empty($checkout) && empty($checkoutBH)) {
            return response()->json([
                'success' => false,
                'message' => 'Không có sản phẩm hoặc bó hoa hợp lệ được chọn.'
            ], 400);
        }

        session(['checkout_items'  => $checkout]);
        session(['checkout_bo_hoa' => $checkoutBH]);

        return response()->json(['success' => true]);
    }

    public function applyPoints(Request $request)
    {
        $request->validate([
            'diem' => 'required|integer|min:0'
        ]);

        $khachHang = Auth::user()->khachHang;

        if (!$khachHang) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin khách hàng.'
            ], 404);
        }

        $diem = min((int) $request->diem, $khachHang->diem_tich_luy);

        if ($diem <= 0) {
            session()->forget('diem_su_dung');
            return response()->json([
                'success'      => true,
                'diem_su_dung' => 0,
                'giam_gia'     => 0,
            ]);
        }

        session(['diem_su_dung' => $diem]);

        return response()->json([
            'success'      => true,
            'diem_su_dung' => $diem,
            'giam_gia'     => $diem * self::DIEM_QUY_DOI,
        ]);
    }
}