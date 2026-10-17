<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChiTietNhap;
use App\Models\BoHoa;
use App\Models\NhaCungCap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SanPhamHetHanController extends Controller
{
    /**
     * Hiển thị trang cảnh báo & xử lý tất cả sản phẩm hết hạn / sắp hết hạn:
     * - Các loại hoa sắp / đã hết hạn (theo lô hàng)
     * - Các bó hoa sắp / đã hết hạn (bó hoa mẫu / thành phẩm)
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $thresholdStr = $today->copy()->addDays(3)->toDateString();

        $filter = $request->input('filter', 'all'); // 'all', 'qua_han', 'hom_nay', 'sap_het'
        $keyword = trim((string) $request->input('keyword', ''));

        $hasNgayHetHanCTN = Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han');
        $hasNgayHetHanBH  = Schema::hasColumn('bo_hoa', 'ngay_het_han');
        $hasHinhAnhBH     = Schema::hasColumn('bo_hoa', 'hinh_anh');

        // ─────────────────────────────────────────────────────────────────
        // 1. QUERY CÁC LOẠI HOA SẮP / ĐÃ HẾT HẠN (LÔ HÀNG)
        // ─────────────────────────────────────────────────────────────────
        $loQuery = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->join('san_pham as sp', 'ctn.ma_san_pham', '=', 'sp.ma_san_pham')
            ->leftJoin('nha_cung_cap as ncc', 'pn.ma_nha_cung_cap', '=', 'ncc.ma_nha_cung_cap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->where('ctn.so_luong_con_lai', '>', 0)
            ->where(function ($q) {
                $q->whereIn('sp.loai_san_pham', ['HOA_TUOI', 'CHAU_HOA_TUOI'])
                  ->orWhereNotNull('ctn.ngay_het_han');
            })
            ->select(
                'ctn.ma_chi_tiet_nhap',
                'ctn.ma_phieu_nhap',
                'ctn.ma_san_pham',
                'sp.ten_san_pham',
                'sp.hinh_anh',
                'sp.loai_san_pham',
                'sp.don_vi_tinh',
                'ctn.so_luong',
                'ctn.so_luong_con_lai',
                'ctn.gia_nhap',
                'pn.ngay_nhap',
                'ctn.created_at as ngay_tao_lo',
                DB::raw($hasNgayHetHanCTN 
                    ? 'COALESCE(ctn.ngay_het_han, DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY))) as hsd'
                    : 'DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY)) as hsd'
                ),
                'ncc.ma_nha_cung_cap',
                'ncc.ten_nha_cung_cap',
                'ncc.so_dien_thoai as sdt_ncc',
                'ncc.email as email_ncc',
                'ncc.dia_chi as dia_chi_ncc'
            );

        if ($keyword !== '') {
            $loQuery->where(function ($q) use ($keyword) {
                if (is_numeric($keyword)) {
                    $q->orWhere('ctn.ma_chi_tiet_nhap', (int) $keyword)
                      ->orWhere('ctn.ma_phieu_nhap', (int) $keyword)
                      ->orWhere('ctn.ma_san_pham', (int) $keyword);
                }
                $q->orWhere('sp.ten_san_pham', 'like', '%' . $keyword . '%')
                  ->orWhere('ncc.ten_nha_cung_cap', 'like', '%' . $keyword . '%');
            });
        }

        // Áp dụng điều kiện HSD theo filter
        if ($filter === 'qua_han') {
            $loQuery->having('hsd', '<', $todayStr);
        } elseif ($filter === 'hom_nay') {
            $loQuery->having('hsd', '=', $todayStr);
        } elseif ($filter === 'sap_het') {
            $loQuery->having('hsd', '>', $todayStr)
                    ->having('hsd', '<=', $thresholdStr);
        } else {
            // 'all': Lấy tất cả các lô đã quá hạn hoặc sắp hết hạn trong 3 ngày tới
            $loQuery->having('hsd', '<=', $thresholdStr);
        }

        $loHangs = $loQuery->orderBy('hsd', 'asc')
            ->orderBy('ctn.so_luong_con_lai', 'desc')
            ->get()
            ->map(function ($item) use ($today) {
                $hsdDate = Carbon::parse($item->hsd)->startOfDay();
                $diff = $today->diffInDays($hsdDate, false);
                $item->diffDays = $diff;
                $item->ngayNhapFmt = $item->ngay_nhap ? Carbon::parse($item->ngay_nhap)->format('d/m/Y') : ($item->ngay_tao_lo ? Carbon::parse($item->ngay_tao_lo)->format('d/m/Y') : '—');
                $item->hsdFmt = $hsdDate->format('d/m/Y');
                $item->giaTriTon = (float) $item->gia_nhap * (int) $item->so_luong_con_lai;
                return $item;
            });

        // ─────────────────────────────────────────────────────────────────
        // 2. QUERY CÁC BÓ HOA SẮP / ĐÃ HẾT HẠN
        // ─────────────────────────────────────────────────────────────────
        $boQuery = DB::table('bo_hoa as bh')
            ->leftJoin('mau_bo_hoa as mbh', 'bh.ma_mau_bo_hoa', '=', 'mbh.ma_mau_bo_hoa')
            ->where('bh.trang_thai', 'ACTIVE')
            ->where('bh.so_luong', '>', 0)
            ->whereNull('bh.ma_hoa_don')
            ->select(
                'bh.ma_bo_hoa',
                'bh.ten_bo_hoa',
                'bh.ma_mau_bo_hoa',
                'mbh.ten_mau',
                'bh.so_luong',
                'bh.gia_ban',
                DB::raw($hasHinhAnhBH ? 'bh.hinh_anh' : 'NULL as hinh_anh'),
                'bh.created_at as ngay_tao',
                DB::raw($hasNgayHetHanBH
                    ? 'COALESCE(bh.ngay_het_han, DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY))) as hsd'
                    : 'DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY)) as hsd'
                )
            );

        if ($keyword !== '') {
            $boQuery->where(function ($q) use ($keyword) {
                if (is_numeric($keyword)) {
                    $q->orWhere('bh.ma_bo_hoa', (int) $keyword);
                }
                $q->orWhere('bh.ten_bo_hoa', 'like', '%' . $keyword . '%')
                  ->orWhere('mbh.ten_mau', 'like', '%' . $keyword . '%');
            });
        }

        if ($filter === 'qua_han') {
            $boQuery->having('hsd', '<', $todayStr);
        } elseif ($filter === 'hom_nay') {
            $boQuery->having('hsd', '=', $todayStr);
        } elseif ($filter === 'sap_het') {
            $boQuery->having('hsd', '>', $todayStr)
                    ->having('hsd', '<=', $thresholdStr);
        } else {
            $boQuery->having('hsd', '<=', $thresholdStr);
        }

        $boHoas = $boQuery->orderBy('hsd', 'asc')
            ->orderBy('bh.so_luong', 'desc')
            ->get()
            ->map(function ($item) use ($today) {
                $hsdDate = Carbon::parse($item->hsd)->startOfDay();
                $diff = $today->diffInDays($hsdDate, false);
                $item->diffDays = $diff;
                $item->ngayTaoFmt = $item->ngay_tao ? Carbon::parse($item->ngay_tao)->format('d/m/Y') : '—';
                $item->hsdFmt = $hsdDate->format('d/m/Y');
                $item->giaTriTon = (float) $item->gia_ban * (int) $item->so_luong;

                // Fallback ảnh từ mẫu bó hoa nếu bó hoa chưa có ảnh riêng hoặc database chưa có cột ảnh
                if (empty($item->hinh_anh) && !empty($item->ma_mau_bo_hoa)) {
                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                        if (file_exists(public_path('img/mau-bo-hoa/' . $item->ma_mau_bo_hoa . '.' . $ext))) {
                            $item->hinh_anh = 'img/mau-bo-hoa/' . $item->ma_mau_bo_hoa . '.' . $ext;
                            break;
                        }
                    }
                }

                return $item;
            });

        // ─────────────────────────────────────────────────────────────────
        // 3. TỔNG HỢP SỐ LIỆU THỐNG KÊ TOÀN CỤC
        // ─────────────────────────────────────────────────────────────────
        $allLo = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->join('san_pham as sp', 'ctn.ma_san_pham', '=', 'sp.ma_san_pham')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->where('ctn.so_luong_con_lai', '>', 0)
            ->where(function ($q) {
                $q->whereIn('sp.loai_san_pham', ['HOA_TUOI', 'CHAU_HOA_TUOI'])
                  ->orWhereNotNull('ctn.ngay_het_han');
            })
            ->select(
                DB::raw($hasNgayHetHanCTN 
                    ? 'COALESCE(ctn.ngay_het_han, DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY))) as hsd'
                    : 'DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY)) as hsd'
                ),
                'ctn.gia_nhap',
                'ctn.so_luong_con_lai'
            )
            ->having('hsd', '<=', $thresholdStr)
            ->get();

        $allBo = DB::table('bo_hoa as bh')
            ->where('bh.trang_thai', 'ACTIVE')
            ->where('bh.so_luong', '>', 0)
            ->whereNull('bh.ma_hoa_don')
            ->select(
                DB::raw($hasNgayHetHanBH
                    ? 'COALESCE(bh.ngay_het_han, DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY))) as hsd'
                    : 'DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY)) as hsd'
                ),
                'bh.gia_ban',
                'bh.so_luong'
            )
            ->having('hsd', '<=', $thresholdStr)
            ->get();

        $loQuaHanCount = $allLo->where('hsd', '<', $todayStr)->count();
        $loHomNayCount = $allLo->where('hsd', '==', $todayStr)->count();
        $loSapHetCount = $allLo->where('hsd', '>', $todayStr)->where('hsd', '<=', $thresholdStr)->count();

        $boQuaHanCount = $allBo->where('hsd', '<', $todayStr)->count();
        $boHomNayCount = $allBo->where('hsd', '==', $todayStr)->count();
        $boSapHetCount = $allBo->where('hsd', '>', $todayStr)->where('hsd', '<=', $thresholdStr)->count();

        $tongGiaTriLo = $allLo->sum(fn ($i) => (float) $i->gia_nhap * (int) $i->so_luong_con_lai);
        $tongGiaTriBo = $allBo->sum(fn ($i) => (float) $i->gia_ban * (int) $i->so_luong);

        $thongKe = [
            'tongLo'       => $allLo->count(),
            'loQuaHan'     => $loQuaHanCount,
            'loHomNay'     => $loHomNayCount,
            'loSapHet'     => $loSapHetCount,
            'tongBo'       => $allBo->count(),
            'boQuaHan'     => $boQuaHanCount,
            'boHomNay'     => $boHomNayCount,
            'boSapHet'     => $boSapHetCount,
            'tongCanXuLy'  => $allLo->count() + $allBo->count(),
            'tongGiaTri'   => $tongGiaTriLo + $tongGiaTriBo,
        ];

        $isAdmin = auth()->check() && auth()->user()->vai_tro === 'ADMIN';

        return view('admin.SanPhamHetHan.index', compact(
            'loHangs',
            'boHoas',
            'thongKe',
            'filter',
            'keyword',
            'isAdmin'
        ));
    }
}