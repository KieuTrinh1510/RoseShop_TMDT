<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoHangHong;
use Illuminate\Http\Request;

class BaoCaoHangHongController extends Controller
{
    /**
     * Danh sách báo cáo hàng hỏng tập trung cho toàn bộ hệ thống
     */
    public function index(Request $request)
    {
        $query = BaoCaoHangHong::with([
            'sanPham',
            'chiTietNhap.phieuNhap',
            'boHoa.mauBoHoa',
            'flashSale',
            'hoaDon',
            'nhanVien'
        ]);

        if ($request->filled('nguon_hong')) {
            $query->where('nguon_hong', $request->nguon_hong);
        }

        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                if (is_numeric($kw)) {
                    $q->orWhere('ma_bao_cao', (int) $kw)
                      ->orWhere('ma_san_pham', (int) $kw)
                      ->orWhere('ma_bo_hoa', (int) $kw)
                      ->orWhere('ma_chi_tiet_nhap', (int) $kw)
                      ->orWhere('ma_hoa_don', (int) $kw);
                }
                $q->orWhereHas('sanPham', fn ($sq) => $sq->where('ten_san_pham', 'like', "%{$kw}%"))
                  ->orWhereHas('boHoa', fn ($bq) => $bq->where('ten_bo_hoa', 'like', "%{$kw}%"))
                  ->orWhere('ly_do', 'like', "%{$kw}%")
                  ->orWhere('ghi_chu', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('tu_ngay')) {
            $query->whereDate('thoi_gian_bao_cao', '>=', $request->tu_ngay);
        }
        if ($request->filled('den_ngay')) {
            $query->whereDate('thoi_gian_bao_cao', '<=', $request->den_ngay);
        }

        $baoCaoHangHongs = $query->orderByDesc('thoi_gian_bao_cao')
            ->orderByDesc('ma_bao_cao')
            ->paginate(15)
            ->withQueryString();

        $thongKe = [
            'tong_so_luong' => BaoCaoHangHong::sum('so_luong_hong'),
            'tong_lan'      => BaoCaoHangHong::count(),
            'tong_thiet_hai'=> BaoCaoHangHong::sum('gia_tri_thiet_hai'),
        ];

        return view('admin.BaoCaoHangHong.index', compact('baoCaoHangHongs', 'thongKe'));
    }

    /**
     * API xem chi tiết báo cáo hàng hỏng
     */
    public function show($id)
    {
        $item = BaoCaoHangHong::with([
            'sanPham',
            'chiTietNhap.phieuNhap',
            'boHoa.mauBoHoa',
            'flashSale',
            'hoaDon',
            'nhanVien'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'ma_bao_cao'        => $item->ma_bao_cao,
                'ten_mat_hang'      => $item->boHoa?->ten_bo_hoa ?? $item->sanPham?->ten_san_pham ?? 'Chưa rõ',
                'loai_mat_hang'     => $item->ma_bo_hoa ? 'Bó hoa thành phẩm' : ($item->sanPham ? 'Lô hoa / Sản phẩm' : 'Khác'),
                'ma_san_pham'       => $item->ma_san_pham,
                'ma_bo_hoa'         => $item->ma_bo_hoa,
                'ma_chi_tiet_nhap'  => $item->ma_chi_tiet_nhap,
                'ma_phieu_nhap'     => $item->chiTietNhap?->ma_phieu_nhap,
                'ma_hoa_don'        => $item->ma_hoa_don,
                'ma_flash_sale'     => $item->ma_flash_sale,
                'so_luong_hong'     => (int) $item->so_luong_hong,
                'gia_tri_thiet_hai' => (float) $item->gia_tri_thiet_hai,
                'nguon_hong'        => $item->nguon_hong,
                'ly_do'             => $item->ly_do,
                'ghi_chu'           => $item->ghi_chu,
                'nguoi_bao_cao'     => $item->nhanVien?->ten_nhan_vien ?? 'Hệ thống',
                'thoi_gian_bao_cao' => optional($item->thoi_gian_bao_cao)->format('d/m/Y H:i:s'),
            ],
        ]);
    }
}
