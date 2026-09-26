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

        try {
            $quote = Bo::tinhGiaDuLieu([
                'size'        => $boHoaMau->size,
                'kieu_bo_hoa' => $boHoaMau->kieu_bo_hoa,
                'thanh_phan'  => $formula,
                'so_luong'    => 1,
            ]);
        } catch (\Throwable $e) {
            $phi = Bo::layPhuPhi($boHoaMau->size, $boHoaMau->kieu_bo_hoa);
            $nl  = $boHoaMau->chiTietMauBoHoa->sum(
                fn ($c) => ((float) ($c->sanPham?->gia_ban_hien_tai ?? 0)) * $c->so_luong
            );
            $giaMoiBo = round($nl + $phi['tong'], 2);
            $quote = [
                'tien_nguyen_lieu' => $nl,
                'phu_phi'          => $phi,
                'gia_moi_bo'       => $giaMoiBo,
                'tong_tien'        => $giaMoiBo,
            ];
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
            'type' => 'bo-hoa-tu-tao',
            'preview' => $nhap,
            'chiTietPreview' => $chiTietPreview,
            'quote' => $quote,
        ]);
    }

    /**
     * Danh sách thiệp đang bán + còn tồn kho (CONFIRMED).
     * Chỉ những thiệp này mới cho khách chọn ở form chi tiết bó hoa.
     */
    private function danhSachThiep()
    {
        return SanPham::where('loai_san_pham', 'THIEP')
            ->where('trang_thai', 'DANG_BAN')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'));
            }])
            ->orderBy('ten_san_pham')
            ->get()
            ->filter(fn ($sp) => $sp->chiTietNhaps->sum('so_luong_con_lai') > 0)
            ->values();
    }
}