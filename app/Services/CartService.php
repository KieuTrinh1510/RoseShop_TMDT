<?php

namespace App\Services;

use App\Http\Controllers\Customer\BoHoaController as CustomerBoHoaController;
use App\Models\KhachHang;
use App\Models\MauBoHoa;
use App\Models\NguoiDung;
use App\Models\SanPham;
use Illuminate\Support\Facades\Auth;

class CartService
{
    /**
     * Đồng bộ giỏ hàng từ session vào database (bảng khach_hang)
     * Đảm bảo khách đăng xuất, đóng trình duyệt hoặc hết hạn session thì giỏ hàng không bị mất.
     */
    public static function syncToDatabase(?NguoiDung $user = null): void
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return;
        }

        $khachHang = $user->khachHang ?? KhachHang::find($user->ma_nguoi_dung);
        if (!$khachHang) {
            return;
        }

        $gioHang   = session('gio_hang', []);
        $gioBoHoa  = session('gio_bo_hoa', []);

        $khachHang->du_lieu_gio_hang = [
            'gio_hang'   => $gioHang,
            'gio_bo_hoa' => $gioBoHoa,
        ];
        $khachHang->save();
    }

    /**
     * Khôi phục giỏ hàng từ database vào session khi người dùng đăng nhập hoặc truy cập giỏ hàng.
     */
    public static function restoreFromDatabase(?NguoiDung $user = null): void
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return;
        }

        $khachHang = $user->khachHang ?? KhachHang::find($user->ma_nguoi_dung);
        if (!$khachHang || empty($khachHang->du_lieu_gio_hang)) {
            return;
        }

        $dbCart = $khachHang->du_lieu_gio_hang;

        // Khôi phục giỏ sản phẩm đơn lẻ (merge nếu session đang có sản phẩm mới thêm)
        $sessionGioHang = session('gio_hang', []);
        $dbGioHang      = (array) ($dbCart['gio_hang'] ?? []);
        if (!empty($dbGioHang)) {
            $mergedGioHang = array_merge($dbGioHang, $sessionGioHang);
            session(['gio_hang' => $mergedGioHang]);
        }

        // Khôi phục giỏ bó hoa mẫu (merge nếu session đang có bó hoa mới thêm)
        $sessionGioBoHoa = session('gio_bo_hoa', []);
        $dbGioBoHoa      = (array) ($dbCart['gio_bo_hoa'] ?? []);
        if (!empty($dbGioBoHoa)) {
            $mergedGioBoHoa = array_merge($dbGioBoHoa, $sessionGioBoHoa);
            session(['gio_bo_hoa' => $mergedGioBoHoa]);
        }
    }

    /**
     * Lấy danh sách chi tiết các bó hoa trong giỏ hàng (kèm thông tin mẫu, thiệp, giá)
     */
    public static function getBouquetCartDetails(array $gioBoHoa, ?int $khachId = null): array
    {
        $dsItem = [];

        foreach ($gioBoHoa as $key => $item) {
            if (empty($item['ma_mau_bo_hoa'])) {
                continue;
            }

            $mau = MauBoHoa::with('chiTietMauBoHoa.sanPham')->find($item['ma_mau_bo_hoa']);
            if (!$mau) {
                continue;
            }

            $thiep = !empty($item['ma_thiep']) ? SanPham::find($item['ma_thiep']) : null;

            try {
                $info = CustomerBoHoaController::duLieuCheckout([
                    'loai'          => 'MAU',
                    'ma_mau_bo_hoa' => $item['ma_mau_bo_hoa'],
                    'so_luong'      => $item['so_luong'] ?? 1,
                    'ma_thiep'      => $item['ma_thiep'] ?? null,
                    'loi_nhan'      => $item['loi_nhan'] ?? null,
                ], $khachId);
                $giaMotBo = (float) $info['quote']['gia_moi_bo'];
                $tongItem = (float) $info['quote']['tong_tien'];
            } catch (\Throwable $e) {
                $giaMotBo = 0;
                $tongItem = 0;
            }

            $dsItem[$key] = [
                'mau'        => $mau,
                'thiep'      => $thiep,
                'so_luong'   => (int) ($item['so_luong'] ?? 1),
                'loi_nhan'   => $item['loi_nhan'] ?? null,
                'gia_mot_bo' => $giaMotBo,
                'tong'       => $tongItem,
            ];
        }

        return $dsItem;
    }
}
