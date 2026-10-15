<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $today = Carbon::today();

        $tongSanPham = DB::table('san_pham')->count();

        $tongKhachHang = DB::table('khach_hang')->count();

        $tongDonHang = DB::table('hoa_don')->count();

        $doanhThuHomNay = DB::table('hoa_don')
            ->where('trang_thai', 'DELIVERED')
            ->whereDate('ngay_dat', $today)
            ->sum('tong_tien');

        // Cảnh báo sắp hết hạn (trong vòng 3 ngày tới hoặc đã quá hạn)
        $todayStr = Carbon::today()->toDateString();
        $thresholdDate = Carbon::today()->addDays(3)->toDateString();

        $hasNgayHetHanCTN = Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han');
        $loQuery = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->join('san_pham as sp', 'ctn.ma_san_pham', '=', 'sp.ma_san_pham')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->where('ctn.so_luong_con_lai', '>', 0)
            ->where(function ($q) {
                $q->whereIn('sp.loai_san_pham', ['HOA_TUOI', 'CHAU_HOA_TUOI'])
                  ->orWhereNotNull('ctn.ngay_het_han');
            })
            ->select(DB::raw($hasNgayHetHanCTN
                ? 'COALESCE(ctn.ngay_het_han, DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY))) as hsd'
                : 'DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY)) as hsd'
            ))
            ->having('hsd', '<=', $thresholdDate)
            ->get();

        $soLoHetHanHomNay = $loQuery->where('hsd', '<=', $todayStr)->count();
        $soLoSapHet = $loQuery->where('hsd', '>', $todayStr)->count();
        $soLoSapHetHan = $loQuery->count();

        $hasNgayHetHanBH = Schema::hasColumn('bo_hoa', 'ngay_het_han');
        $boQuery = DB::table('bo_hoa as bh')
            ->where('bh.trang_thai', 'ACTIVE')
            ->where('bh.so_luong', '>', 0)
            ->whereNull('bh.ma_hoa_don')
            ->select(DB::raw($hasNgayHetHanBH
                ? 'COALESCE(bh.ngay_het_han, DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY))) as hsd'
                : 'DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY)) as hsd'
            ))
            ->having('hsd', '<=', $thresholdDate)
            ->get();

        $soBoHetHanHomNay = $boQuery->where('hsd', '<=', $todayStr)->count();
        $soBoSapHet = $boQuery->where('hsd', '>', $todayStr)->count();
        $soBoSapHetHan = $boQuery->count();

        $canhBaoSapHetHan = $soLoSapHetHan + $soBoSapHetHan;

        // Xây dựng câu thông báo sinh động đúng theo yêu cầu
        $chiTietCanhBao = [];
        if ($soBoHetHanHomNay > 0) {
            $chiTietCanhBao[] = "{$soBoHetHanHomNay} bó hoa hết hạn";
        }
        if ($soBoSapHet > 0) {
            $chiTietCanhBao[] = "{$soBoSapHet} bó hoa sắp hết hạn";
        }
        if ($soLoHetHanHomNay > 0) {
            $chiTietCanhBao[] = "{$soLoHetHanHomNay} loại hoa hết hạn";
        }
        if ($soLoSapHet > 0) {
            $chiTietCanhBao[] = "{$soLoSapHet} hoa sắp hết hạn";
        }

        $thongBaoHetHan = !empty($chiTietCanhBao)
            ? 'Hôm nay có ' . implode(', ', $chiTietCanhBao) . ', bấm vào đây để xử lý'
            : '';

        $donChoXuLy = DB::table('hoa_don as hd')
            ->leftJoin('khach_hang as kh', 'hd.ma_khach_hang', '=', 'kh.ma_khach_hang')
            ->whereIn('hd.trang_thai', ['PENDING', 'CONFIRMED'])
            ->select(
                'hd.ma_hoa_don',
                'hd.ngay_dat',
                'hd.trang_thai',
                'kh.ten_khach_hang'
            )
            ->orderByDesc('hd.ngay_dat')
            ->limit(5)
            ->get();

        $topSanPham = DB::table('hoa_don as hd')
            ->join('chi_tiet_hoa_don as ct', 'hd.ma_hoa_don', '=', 'ct.ma_hoa_don')
            ->join('san_pham as sp', 'ct.ma_san_pham', '=', 'sp.ma_san_pham')
            ->where('hd.trang_thai', 'DELIVERED')
            ->select(
                'sp.ten_san_pham',
                DB::raw('SUM(ct.so_luong) as tong_ban'),
                DB::raw('SUM(ct.so_luong * ct.gia_ban_snapshot) as doanh_thu')
            )
            ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham')
            ->orderByDesc('tong_ban')
            ->limit(5)
            ->get();

        $hasNgayHetHan = Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han');
        $sanPhamSapHet = DB::table('san_pham as sp')
            ->leftJoin('chi_tiet_nhap as ctn', function ($join) {
                $join->on('sp.ma_san_pham', '=', 'ctn.ma_san_pham')
                    ->where('ctn.so_luong_con_lai', '>', 0);
            })
            ->leftJoin('phieu_nhap as pn', function ($join) {
                $join->on('ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
                    ->where('pn.trang_thai', 'CONFIRMED');
            })
            ->where('sp.trang_thai', 'DANG_BAN')
            ->where('sp.so_luong', '<=', 10)
            ->select(
                'sp.ma_san_pham',
                'sp.ten_san_pham',
                'sp.so_luong',
                'sp.loai_san_pham',
                DB::raw($hasNgayHetHan
                    ? 'MIN(CASE 
                        WHEN ctn.ngay_het_han IS NOT NULL THEN ctn.ngay_het_han
                        WHEN sp.loai_san_pham IN ("HOA_TUOI", "CHAU_HOA_TUOI") AND ctn.created_at IS NOT NULL THEN DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY))
                        ELSE NULL
                    END) as ngay_het_han'
                    : 'MIN(CASE 
                        WHEN sp.loai_san_pham IN ("HOA_TUOI", "CHAU_HOA_TUOI") AND ctn.created_at IS NOT NULL THEN DATE(DATE_ADD(ctn.created_at, INTERVAL 7 DAY))
                        ELSE NULL
                    END) as ngay_het_han'
                )
            )
            ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham', 'sp.so_luong', 'sp.loai_san_pham')
            ->orderBy('sp.so_luong')
            ->limit(5)
            ->get();
        $doanhThu7Ngay = DB::table('hoa_don')
            ->where('trang_thai', 'DELIVERED')
            ->whereDate('ngay_dat', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('DATE(ngay_dat) as ngay, SUM(tong_tien) as doanh_thu')
            ->groupByRaw('DATE(ngay_dat)')
            ->orderBy('ngay')
            ->get();
        $range = request('range', '7days');

        $startDate = match ($range) {
            'today' => now()->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subDays(6)->startOfDay(),
        };

        $doanhThuTheoNgay = DB::table('hoa_don')
            ->where('trang_thai', 'DELIVERED')
            ->whereDate('ngay_dat', '>=', $startDate)
            ->selectRaw('DATE(ngay_dat) as ngay, SUM(tong_tien) as doanh_thu')
            ->groupByRaw('DATE(ngay_dat)')
            ->orderBy('ngay')
            ->get();
        $thongBaoCuaHang = AdminNotificationService::getNotifications();

        return view('admin.AdminStaffDashboard', compact(
            'user',
            'tongSanPham',
            'tongKhachHang',
            'tongDonHang',
            'doanhThuHomNay',
            'canhBaoSapHetHan',
            'soLoSapHetHan',
            'soBoSapHetHan',
            'soLoHetHanHomNay',
            'soLoSapHet',
            'soBoHetHanHomNay',
            'soBoSapHet',
            'thongBaoHetHan',
            'thongBaoCuaHang',
            'donChoXuLy',
            'topSanPham',
            'sanPhamSapHet',
            'doanhThu7Ngay',
            'doanhThuTheoNgay',
            'range'
        ));
    }
}