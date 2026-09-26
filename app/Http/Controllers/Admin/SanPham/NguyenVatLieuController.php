<?php

namespace App\Http\Controllers\Admin\SanPham;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use Illuminate\Http\Request;

class NguyenVatLieuController extends Controller
{
    /** 5 loại nguyên vật liệu DUY NHẤT thuộc trang này. */
    private array $danhSachLoaiNguyenLieu = [
        'GIAY_GOI'           => 'Giấy gói',
        'RUY_BANG'           => 'Ruy băng',
        'PHU_KIEN_TRANG_TRI' => 'Phụ kiện trang trí',
        'THIEP'              => 'Thiệp',
        'MIENG_XOP'          => 'Mút xốp',
    ];

    /** Đơn vị tính chỉ cho 5 loại NVL. */
    private array $donViTheoLoai = [
        'GIAY_GOI'           => 'TO',
        'RUY_BANG'           => 'MET',
        'PHU_KIEN_TRANG_TRI' => 'CAI',
        'THIEP'              => 'CAI',
        'MIENG_XOP'          => 'MIENG',
    ];

    public function index(Request $request)
    {
        $maLoai = array_keys($this->danhSachLoaiNguyenLieu);

        $nguyenVatLieu = SanPham::whereIn('loai_san_pham', $maLoai)
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                ->orderBy('gia_ban', 'asc'); 
            }])
            ->orderBy('ma_san_pham', 'desc')
            ->get()
            ->each(fn($sp) => $sp->ton_kho_thuc_te = $sp->chiTietNhaps->sum('so_luong_con_lai'));

        $danhSachLoaiNguyenLieu = $this->danhSachLoaiNguyenLieu;

        return view('admin.SanPham.QuanLyNguyenVatLieu', compact(
            'nguyenVatLieu', 'danhSachLoaiNguyenLieu'
        ));
    }
}