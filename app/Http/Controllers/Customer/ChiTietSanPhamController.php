<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Admin\BoHoaController as Bo;
use App\Http\Controllers\Controller;
use App\Models\{MauBoHoa, SanPham};
use Illuminate\Support\Facades\Auth;

class ChiTietSanPhamController extends Controller
{
    public function show($id)
    {
        $sanPham = SanPham::where('ma_san_pham', $id)
            ->where('trang_thai', 'DANG_BAN')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                  ->orderBy('gia_ban', 'asc');
            }])
            ->firstOrFail();

        $user = auth()->check() ? Auth::user()->load('khachHang') : null;

        $sanPhamLienQuan = SanPham::where('trang_thai', 'DANG_BAN')
            ->where('loai_san_pham', $sanPham->loai_san_pham)
            ->where('ma_san_pham', '!=', $sanPham->ma_san_pham)
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'));
            }])
            ->orderBy('ma_san_pham', 'desc')
            ->limit(6)
            ->get();

        return view('customer.ChiTietSanPham', [
            'type' => 'san-pham',
            'sanPham' => $sanPham,
            'user' => $user,
            'sanPhamLienQuan' => $sanPhamLienQuan,
        ]);
    }

    public function showMauBoHoa($id)
    {
        $boHoaMau = MauBoHoa::where('trang_thai', 'ACTIVE')
            ->with('chiTietMauBoHoa.sanPham')
            ->findOrFail($id);

        $formula = $boHoaMau->chiTietMauBoHoa->map(fn ($c) => [
            'ma_san_pham' => $c->ma_san_pham,
            'vai_tro'     => $c->vai_tro,
            'so_luong'    => $c->so_luong,
        ])->all();

        $readyBo = \App\Models\BoHoa::where('ma_mau_bo_hoa', $id)
            ->where('loai_bo_hoa', 'HE_THONG')
            ->where('trang_thai', 'ACTIVE')
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->orderBy('ma_bo_hoa')
            ->first();

        if ($readyBo) {
            $giaMoiBo = (float) $readyBo->gia_ban;
            $quote = [
                'tien_nguyen_lieu' => (float) ($readyBo->gia_von ?? round($giaMoiBo / 1.5, 2)),
                'phu_phi'          => ['size' => 0, 'kieu_dang' => 0, 'tong' => 0],
                'gia_moi_bo'       => $giaMoiBo,
                'tong_tien'        => $giaMoiBo,
            ];
        } else {
            try {
                $rows = Bo::phanBo($formula, false);
                $gia = Bo::tinhGiaVonVaGiaBan($rows, 1);
                $quote = [
                    'tien_nguyen_lieu' => $gia['gia_von'],
                    'phu_phi'          => ['size' => 0, 'kieu_dang' => 0, 'tong' => 0],
                    'gia_moi_bo'       => $gia['gia_ban'],
                    'tong_tien'        => $gia['gia_ban'],
                ];
            } catch (\Throwable $e) {
                $quote = [
                    'tien_nguyen_lieu' => 0,
                    'phu_phi'          => ['size' => 0, 'kieu_dang' => 0, 'tong' => 0],
                    'gia_moi_bo'       => 0,
                    'tong_tien'        => 0,
                ];
            }
        }

        return view('customer.ChiTietSanPham', [
            'type' => 'mau-bo-hoa',
            'boHoaMau' => $boHoaMau,
            'quote' => $quote,
            'dsThiep' => $this->danhSachThiep(),
        ]);
    }

    public function showTuTao()
    {
        $nhap = session('bo_hoa_tu_tao_nhap');
        if (!$nhap) {
            return redirect()->route('customer.bo-hoa.tu-tao')
                ->with('info', 'Vui lòng chọn nguyên liệu trước khi xem chi tiết.');
        }

        $chiTietPreview = collect($nhap['thanh_phan'])->map(function ($row) {
            $sp = SanPham::find($row['ma_san_pham']);
            return (object) [
                'vai_tro'      => $row['vai_tro'],
                'ma_san_pham'  => $row['ma_san_pham'],
                'so_luong'     => $row['so_luong'],
                'sanPham'      => $sp,
            ];
        });

        try {
            $quote = Bo::tinhGiaDuLieu([
                'size'        => $nhap['size'],
                'kieu_bo_hoa' => $nhap['kieu_bo_hoa'],
                'thanh_phan'  => $nhap['thanh_phan'],
                'so_luong'    => $nhap['so_luong'],
            ]);
        } catch (\Throwable $e) {
            $quote = $nhap['quote'] ?? [
                'tien_nguyen_lieu' => 0,
                'phu_phi'          => ['size' => 0, 'kieu_dang' => 0, 'tong' => 0],
                'gia_moi_bo'       => 0,
                'tong_tien'        => 0,
            ];
        }

        return view('customer.ChiTietSanPham', [
            'type'           => 'bo-hoa-tu-tao',
            'preview'        => $nhap,
            'chiTietPreview' => $chiTietPreview,
            'quote'          => $quote,
            'dsThiep'        => $this->danhSachThiep(),
        ]);
    }

    /**
     * Danh sách thiệp đang bán + còn tồn kho khả dụng (CONFIRMED, chưa hết hạn).
     * Chỉ những thiệp này mới cho khách chọn ở form chi tiết bó hoa.
     */
    private function danhSachThiep()
    {
        return SanPham::where('loai_san_pham', 'THIEP')
            ->where('trang_thai', 'DANG_BAN')
            ->orderBy('ten_san_pham')
            ->get()
            ->filter(fn ($sp) => \App\Services\InventoryReservationService::getAvailableForIngredient($sp->ma_san_pham) > 0)
            ->values();
    }
}