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

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 10;
        }

        $query = SanPham::whereIn('loai_san_pham', $maLoai);

        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('ten_san_pham', 'like', '%' . $request->keyword . '%')
                  ->orWhere('mo_ta', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('loai_san_pham')) {
            $query->where('loai_san_pham', $request->loai_san_pham);
        }

        $nguyenVatLieu = $query
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                ->orderBy('gia_ban', 'asc'); 
            }])
            ->orderBy('ma_san_pham', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $nguyenVatLieu->getCollection()->transform(function($sp) {
            $sp->ton_kho_thuc_te = $sp->chiTietNhaps->sum('so_luong_con_lai');
            return $sp;
        });

        $danhSachLoaiNguyenLieu = $this->danhSachLoaiNguyenLieu;

        return view('admin.NguyenVatLieu.index', compact(
            'nguyenVatLieu', 'danhSachLoaiNguyenLieu'
        ));
    }
}