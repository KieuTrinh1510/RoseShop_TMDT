<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use App\Models\HoaDon;
use App\Models\SanPham;
use App\Models\KhachHang;
use App\Models\BaoCaoHangHong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BaoCaoController extends Controller

{
    private const LOAI_TUOI = ['HOA_TUOI', 'CHAU_HOA_TUOI'];

    private const MA_NHAN_VIEN_MAC_DINH = 7;

    public function index(Request $request)
    {
        
        $tab = $request->get('tab', 'san-pham');
        if (!in_array($tab, ['san-pham', 'doanh-thu'], true)) {
            $tab = 'san-pham';
        }

        $tongDoanhThu = HoaDon::where(function ($q) {
            $q->where('trang_thai', 'DELIVERED')
              ->orWhere('trang_thai_thanh_toan', 'DA_THANH_TOAN');
        })->sum('tong_tien');

        $tongLoiNhuan = DB::table('chi_tiet_hoa_don as ct')
            ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->where('hd.trang_thai', 'DELIVERED')
            ->selectRaw('SUM((ct.gia_ban_snapshot - ct.gia_nhap_snapshot) * ct.so_luong) as loi_nhuan')
            ->value('loi_nhuan') ?? 0;

        $tongHoaDon = HoaDon::count();

        $tongSanPhamBan = DB::table('chi_tiet_hoa_don as ct')
            ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->where('hd.trang_thai', 'DELIVERED')
            ->sum('ct.so_luong') ?? 0;

        $donHangHomNay = HoaDon::whereDate('ngay_dat', Carbon::today())->count();

        $doanhThuHomNay = HoaDon::whereDate('ngay_dat', Carbon::today())
            ->where('trang_thai', 'DELIVERED')
            ->sum('tong_tien');

        $sanPhamSapHetSo = SanPham::where('so_luong', '<', 5)
            ->where('trang_thai', 'DANG_BAN')
            ->count();

        $tongHangHongHomNay = BaoCaoHangHong::whereDate('thoi_gian_bao_cao', Carbon::today())
            ->sum('so_luong_hong');

        $doanhThu7Ngay = $this->layDoanhThuTheoNgay(
             Carbon::today()->subDays(6)->startOfDay(),
            Carbon::today()->endOfDay()
        );

        $trangThaiDonHang = HoaDon::selectRaw('trang_thai, COUNT(*) as so_luong')
            ->groupBy('trang_thai')
            ->pluck('so_luong', 'trang_thai')
            ->toArray();

        $topSanPhamDashboard = $this->layTopSanPham(5);

        $data = compact(
            'tab',
            'tongDoanhThu', 'tongLoiNhuan', 'tongHoaDon', 'tongSanPhamBan',
            'donHangHomNay', 'doanhThuHomNay', 'sanPhamSapHetSo', 'tongHangHongHomNay',
            'doanhThu7Ngay', 'trangThaiDonHang', 'topSanPhamDashboard'
        );

        if ($tab === 'doanh-thu') {
            $data = array_merge($data, $this->dataDoanhThu($request));
        } elseif ($tab === 'ton-kho') {
            $data = array_merge($data, $this->dataTonKho($request));
        } elseif ($tab === 'khach-hang') {
            $data = array_merge($data, $this->dataKhachHang($request));
        } elseif ($tab === 'hang-hong') {
            $data = array_merge($data, $this->dataHangHong($request));
        }

        return view('admin.BaoCao', $data);

    }

    public function doanhThu(Request $request)
{
    $data = $this->dataDoanhThu($request);

    $data['tab'] = 'doanh-thu';

    return view('BaoCao.DoanhThu', $data);
}

public function chiTietSanPham($id, Request $request)
{
    $ngayBatDau = $request->filled('ngay_bat_dau')
        ? Carbon::parse($request->ngay_bat_dau)->startOfDay()
        : Carbon::now()->startOfMonth()->startOfDay();

    $ngayKetThuc = $request->filled('ngay_ket_thuc')
        ? Carbon::parse($request->ngay_ket_thuc)->endOfDay()
        : Carbon::now()->endOfDay();

    if ($ngayBatDau->gt($ngayKetThuc)) {
        $temp = $ngayBatDau;
        $ngayBatDau = $ngayKetThuc->copy()->startOfDay();
        $ngayKetThuc = $temp->copy()->endOfDay();
    }

    $sanPham = SanPham::findOrFail($id);

    // 1. Lịch sử bán trong khoảng lọc: Đơn DELIVERED theo delivered timestamp
    $lichSuGiaBan = DB::table('chi_tiet_hoa_don as ct')
        ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
        ->where('ct.ma_san_pham', $id)
        ->where('hd.trang_thai', 'DELIVERED')
        ->whereBetween(DB::raw('COALESCE(hd.ngay_giao, hd.ngay_den_noi, hd.ngay_dat)'), [$ngayBatDau, $ngayKetThuc])
        ->select(
            'hd.ma_hoa_don',
            DB::raw('COALESCE(hd.ngay_giao, hd.ngay_den_noi, hd.ngay_dat) as ngay_giao'),
            'ct.so_luong',
            'ct.gia_ban_snapshot',
            DB::raw('(ct.so_luong * ct.gia_ban_snapshot) as thanh_tien')
        )
        ->orderByDesc(DB::raw('COALESCE(hd.ngay_giao, hd.ngay_den_noi, hd.ngay_dat)'))
        ->get();

    // 2. Lịch sử nhập trong khoảng lọc: Phiếu nhập CONFIRMED
    $lichSuNhap = DB::table('chi_tiet_nhap as ctn')
        ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
        ->where('ctn.ma_san_pham', $id)
        ->where('pn.trang_thai', 'CONFIRMED')
        ->whereBetween('pn.ngay_nhap', [$ngayBatDau, $ngayKetThuc])
        ->select(
            'pn.ma_phieu_nhap',
            'pn.ngay_nhap',
            'ctn.so_luong',
            'ctn.gia_nhap',
            'ctn.gia_ban'
        )
        ->orderByDesc('pn.ngay_nhap')
        ->get();

    // 3. Lịch sử tiêu hao sản xuất bó hoa trong khoảng lọc
    $lichSuSanXuat = DB::table('chi_tiet_bo_hoa as ctbh')
        ->join('bo_hoa as bh', 'ctbh.ma_bo_hoa', '=', 'bh.ma_bo_hoa')
        ->leftJoin('hoa_don as hd', 'bh.ma_hoa_don', '=', 'hd.ma_hoa_don')
        ->where('ctbh.ma_san_pham', $id)
        ->where(function ($q) {
            $q->whereNull('bh.ma_hoa_don')
              ->orWhereIn('hd.trang_thai', ['CONFIRMED', 'SHIPPING', 'DELIVERED']);
        })
        ->whereBetween('bh.created_at', [$ngayBatDau, $ngayKetThuc])
        ->select(
            'bh.ma_bo_hoa',
            'bh.ten_bo_hoa',
            'bh.loai_bo_hoa',
            'bh.created_at',
            'ctbh.vai_tro',
            'ctbh.so_luong'
        )
        ->orderByDesc('bh.created_at')
        ->get();

    // Tính tổng số lượng
    $tongNhap = (int) $lichSuNhap->sum('so_luong');
    $tongDaBan = (int) $lichSuGiaBan->sum('so_luong');
    $tongTieuHao = (int) $lichSuSanXuat->sum('so_luong');
    $tongDoanhThu = (float) $lichSuGiaBan->sum('thanh_tien');

    // Hàng hỏng trong kỳ
    $tongHangHong = (int) BaoCaoHangHong::where('ma_san_pham', $id)
        ->whereBetween('thoi_gian_bao_cao', [$ngayBatDau, $ngayKetThuc])
        ->sum('so_luong_hong');

    // Tồn kho vật lý (On-Hand) và Khả dụng (Available)
    $hasLots = DB::table('chi_tiet_nhap')->where('ma_san_pham', $id)->exists();
    $lotStock = (int) DB::table('chi_tiet_nhap as ctn')
        ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
        ->where('pn.trang_thai', 'CONFIRMED')
        ->where('ctn.ma_san_pham', $id)
        ->sum('ctn.so_luong_con_lai');

    $tonKhoHienTai = $hasLots ? $lotStock : (int) $sanPham->so_luong;

    $dangGiu = (int) DB::table('inventory_reservations')
        ->where('ma_san_pham', $id)
        ->where('trang_thai', 'RESERVED')
        ->sum('so_luong');

    $tonKhaDung = max(0, $tonKhoHienTai - $dangGiu);

    return response()->json([
        'san_pham' => [
            'ma_san_pham'  => $sanPham->ma_san_pham,
            'ten_san_pham' => $sanPham->ten_san_pham,
            'loai_san_pham' => \App\Helpers\LabelHelper::loaiSanPham($sanPham->loai_san_pham),
            'so_luong'     => $tonKhoHienTai,
            'trang_thai'   => $sanPham->trang_thai,
            'hinh_anh'     => $sanPham->hinh_anh,
        ],
        'tong_quan' => [
            'nhap_trong_ky'     => $tongNhap,
            'tong_da_ban'       => $tongDaBan,
            'tieu_hao_san_xuat' => $tongTieuHao,
            'tong_hang_hong'    => $tongHangHong,
            'ton_kho_hien_tai'  => $tonKhoHienTai,
            'ton_kha_dung'      => $tonKhaDung,
            'dang_giu'          => $dangGiu,
            'tong_doanh_thu'    => $tongDoanhThu,
        ],
        'lich_su_gia_ban' => $lichSuGiaBan,
        'lich_su_nhap'    => $lichSuNhap,
        'lich_su_san_xuat' => $lichSuSanXuat,
    ]);
}
public function exportDoanhThu(Request $request)
{
    $data = $this->dataDoanhThu($request);

    $filename = 'bao_cao_doanh_thu_' . now()->format('Ymd_His') . '.csv';

    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    return response()->streamDownload(function () use ($data) {
        $file = fopen('php://output', 'w');

        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($file, [
            'Thời gian',
            'Số đơn hàng',
            'Doanh thu',
            'Giá vốn',
            'Lợi nhuận',
        ]);

        foreach ($data['chiTietDoanhThu'] as $row) {
            fputcsv($file, [
                $row->thoi_gian,
                $row->so_don_hang,
                $row->doanh_thu,
                $row->gia_von,
                $row->loi_nhuan,
            ]);
        }

        fclose($file);
    }, $filename, $headers);
}
    /**
     * Trang báo cáo sản phẩm độc lập — route GET /bao-cao/san-pham
     */
    public function baoCaoSanPham(Request $request)
    {
        $data = $this->getBaoCaoSanPhamData($request);

        return view('BaoCao.SanPham', $data);
    }

    /**
     * Xuất Excel / CSV báo cáo sản phẩm — route GET /bao-cao/san-pham/export
     */
    public function exportBaoCaoSanPham(Request $request)
    {
        $data = $this->getBaoCaoSanPhamData($request);
        extract($data);

        $filename = 'bao_cao_san_pham_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use (
            $baoCaoSanPham,
            $tongNhapThem,
            $tongDaBan,
            $tongTieuHaoSanXuat,
            $tongHangHong,
            $tongTonKhoHienTai,
            $giaNhapTrungBinhChung,
            $tyLeHongChung,
            $tongDoanhThuSanPham
        ) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM UTF-8 để Excel đọc đúng tiếng Việt

            fputcsv($file, [
                '#',
                'Sản phẩm',
                'Loại sản phẩm',
                'Nhập trong kỳ',
                'Bán trong kỳ',
                'Tiêu hao sản xuất',
                'Hàng hỏng',
                'Tồn hiện tại (On-Hand)',
                'Khả dụng (Available)',
                'Giá nhập TB (VNĐ)',
                'Giá bán hiện tại (VNĐ)',
                'Doanh thu (VNĐ)',
                'Tỷ lệ hao hụt (%)',
                'Trạng thái',
            ]);

            foreach ($baoCaoSanPham as $i => $row) {
                fputcsv($file, [
                    $i + 1,
                    $row['ten_san_pham'],
                    \App\Helpers\LabelHelper::loaiSanPham($row['loai_san_pham']),
                    $row['nhap_them'],
                    $row['da_ban'],
                    $row['tieu_hao_san_xuat'],
                    $row['hang_hong'],
                    $row['ton_kho_hien_tai'],
                    $row['ton_kha_dung'],
                    $row['gia_nhap_tb'] !== null ? number_format($row['gia_nhap_tb'], 0, ',', '.') : '-',
                    $row['gia_ban_hien_tai'] > 0 ? number_format($row['gia_ban_hien_tai'], 0, ',', '.') : '-',
                    number_format($row['doanh_thu'], 0, ',', '.'),
                    number_format($row['ty_le_hong'], 2, ',', '.') . '%',
                    $row['trang_thai'] === 'DANG_BAN' ? 'Còn hàng' : 'Ngừng bán',
                ]);
            }

            fputcsv($file, [
                'TỔNG CỘNG',
                '',
                '',
                $tongNhapThem,
                $tongDaBan,
                $tongTieuHaoSanXuat,
                $tongHangHong,
                $tongTonKhoHienTai,
                '',
                $giaNhapTrungBinhChung !== null ? number_format($giaNhapTrungBinhChung, 0, ',', '.') : '-',
                '-',
                number_format($tongDoanhThuSanPham, 0, ',', '.'),
                number_format($tyLeHongChung, 2, ',', '.') . '%',
                '',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    /**
     * API JSON — chi tiết hàng hỏng theo sản phẩm — route GET /bao-cao/san-pham/{id}/hang-hong
     * Query param tuỳ chọn: ngay_bat_dau, ngay_ket_thuc
     */
    public function chiTietHangHong(Request $request, $id)
    {
        $sanPham = SanPham::findOrFail($id);

        $query = BaoCaoHangHong::with(['nhanVien', 'chiTietNhap.phieuNhap'])
            ->where('ma_san_pham', $id);

        if ($request->filled('ngay_bat_dau')) {
            $query->where('thoi_gian_bao_cao', '>=',
                Carbon::parse($request->ngay_bat_dau)->startOfDay());
        }
        if ($request->filled('ngay_ket_thuc')) {
            $query->where('thoi_gian_bao_cao', '<=',
                Carbon::parse($request->ngay_ket_thuc)->endOfDay());
        }

        $chiTiet = $query->orderByDesc('thoi_gian_bao_cao')
            ->get()
            ->map(fn($item) => [
                'ngay_ghi_nhan'      => optional($item->thoi_gian_bao_cao)->format('d/m/Y H:i'),
                'so_luong_hong'      => $item->so_luong_hong,
                'ly_do_hong'         => $item->ly_do ?? '-',
                'ghi_chu'            => $item->ghi_chu ?? '-',
                'nhan_vien_ghi_nhan' => $item->nhanVien?->ten_nhan_vien ?? 'Không rõ',
                // Thông tin lô nhập bị hỏng
                'ma_phieu_nhap'      => $item->chiTietNhap?->ma_phieu_nhap ?? null,
                'ngay_nhap_lo'       => optional($item->chiTietNhap?->phieuNhap?->ngay_nhap)->format('d/m/Y') ?? '-',
                'sl_lo_con_lai'      => $item->chiTietNhap?->so_luong_con_lai ?? '-',
            ]);

        return response()->json([
            'ten_san_pham'       => $sanPham->ten_san_pham,
            'tong_so_luong_hong' => $chiTiet->sum('so_luong_hong'),
            'data'               => $chiTiet,
        ]);
    }

    /**
     * Truy vấn và tính toán dữ liệu báo cáo sản phẩm dùng chung cho view và export.
     */
    private function getBaoCaoSanPhamData(Request $request): array
    {
        // 1. Bộ lọc thời gian & validate
        $ngayBatDau = $request->filled('ngay_bat_dau')
            ? Carbon::parse($request->ngay_bat_dau)->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $ngayKetThuc = $request->filled('ngay_ket_thuc')
            ? Carbon::parse($request->ngay_ket_thuc)->endOfDay()
            : Carbon::now()->endOfDay();

        $dateWarning = null;
        if ($ngayBatDau->gt($ngayKetThuc)) {
            $temp = $ngayBatDau;
            $ngayBatDau = $ngayKetThuc->copy()->startOfDay();
            $ngayKetThuc = $temp->copy()->endOfDay();
            $dateWarning = 'Ngày bắt đầu lớn hơn ngày kết thúc. Hệ thống đã tự động đảo khoảng ngày phù hợp.';
        }

        $keyword = trim($request->get('keyword', ''));
        $loaiSanPham = $request->get('loai_san_pham');
        $trangThai = $request->get('trang_thai');

        // 2. Danh sách sản phẩm & nguyên liệu
        $sanPhams = SanPham::query()
            ->select(
                'ma_san_pham',
                'ten_san_pham',
                'loai_san_pham',
                'trang_thai',
                'hinh_anh',
                'so_luong',
                'gia_ban_hien_tai'
            )
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('ten_san_pham', 'like', '%' . $keyword . '%');
            })
            ->when($loaiSanPham, function ($query) use ($loaiSanPham) {
                $query->where('loai_san_pham', $loaiSanPham);
            })
            ->when($trangThai, function ($query) use ($trangThai) {
                $query->where('trang_thai', $trangThai);
            })
            ->orderBy('ten_san_pham')
            ->get();

        // 3. Nhập thêm trong khoảng ngày (Phiếu nhập CONFIRMED)
        $nhapThemData = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->whereBetween('pn.ngay_nhap', [$ngayBatDau, $ngayKetThuc])
            ->groupBy('ctn.ma_san_pham')
            ->select(
                'ctn.ma_san_pham',
                DB::raw('SUM(ctn.so_luong) as tong_nhap'),
                DB::raw('SUM(ctn.so_luong * ctn.gia_nhap) as tong_tien_nhap')
            )
            ->get()
            ->keyBy('ma_san_pham');

        // 4. Đã bán + doanh thu theo thời điểm DELIVERED thực tế (ngay_giao / ngay_den_noi / ngay_dat)
        $daBanData = DB::table('chi_tiet_hoa_don as cthd')
            ->join('hoa_don as hd', 'cthd.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->where('hd.trang_thai', 'DELIVERED')
            ->whereNotNull('cthd.ma_san_pham')
            ->whereBetween(DB::raw('COALESCE(hd.ngay_giao, hd.ngay_den_noi, hd.ngay_dat)'), [$ngayBatDau, $ngayKetThuc])
            ->groupBy('cthd.ma_san_pham')
            ->select(
                'cthd.ma_san_pham',
                DB::raw('SUM(cthd.so_luong) as tong_da_ban'),
                DB::raw('SUM(cthd.so_luong * cthd.gia_ban_snapshot) as doanh_thu')
            )
            ->get()
            ->keyBy('ma_san_pham');

        // 5. Tiêu hao để sản xuất bó hoa thành phẩm trong kỳ (không tính BOM mẫu)
        $tieuHaoData = DB::table('chi_tiet_bo_hoa as ctbh')
            ->join('bo_hoa as bh', 'ctbh.ma_bo_hoa', '=', 'bh.ma_bo_hoa')
            ->leftJoin('hoa_don as hd', 'bh.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->where(function ($q) {
                $q->whereNull('bh.ma_hoa_don')
                  ->orWhereIn('hd.trang_thai', ['CONFIRMED', 'SHIPPING', 'DELIVERED']);
            })
            ->whereBetween('bh.created_at', [$ngayBatDau, $ngayKetThuc])
            ->groupBy('ctbh.ma_san_pham')
            ->select(
                'ctbh.ma_san_pham',
                DB::raw('SUM(ctbh.so_luong) as tong_tieu_hao')
            )
            ->get()
            ->keyBy('ma_san_pham');

        // 6. Hàng hỏng ghi nhận trong kỳ (chỉ tính record của sản phẩm/nguyên liệu)
        $hangHongData = BaoCaoHangHong::query()
            ->whereNotNull('ma_san_pham')
            ->whereBetween('thoi_gian_bao_cao', [$ngayBatDau, $ngayKetThuc])
            ->groupBy('ma_san_pham')
            ->select(
                'ma_san_pham',
                DB::raw('SUM(so_luong_hong) as tong_hong')
            )
            ->get()
            ->keyBy('ma_san_pham');

        // 7. Tồn kho vật lý thực tế (On-Hand) từ các lô hàng CONFIRMED còn tồn
        $lotStockData = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->groupBy('ctn.ma_san_pham')
            ->select(
                'ctn.ma_san_pham',
                DB::raw('SUM(ctn.so_luong_con_lai) as tong_ton_lot')
            )
            ->get()
            ->keyBy('ma_san_pham');

        $hasLotsMap = DB::table('chi_tiet_nhap')
            ->select('ma_san_pham')
            ->distinct()
            ->pluck('ma_san_pham')
            ->flip();

        // Tồn đang giữ chỗ (RESERVED)
        $reservedData = DB::table('inventory_reservations')
            ->where('trang_thai', 'RESERVED')
            ->whereNotNull('ma_san_pham')
            ->groupBy('ma_san_pham')
            ->select(
                'ma_san_pham',
                DB::raw('SUM(so_luong) as tong_reserved')
            )
            ->get()
            ->keyBy('ma_san_pham');

        // 8. Giá nhập gần nhất từ lô đã xác nhận (tham khảo khi kỳ không có nhập mới)
        $giaNhapMoiNhatData = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->select(
                'ctn.ma_san_pham',
                'ctn.gia_nhap',
                'pn.ngay_nhap',
                'ctn.ma_chi_tiet_nhap'
            )
            ->orderByDesc('pn.ngay_nhap')
            ->orderByDesc('ctn.ma_chi_tiet_nhap')
            ->get()
            ->unique('ma_san_pham')
            ->keyBy('ma_san_pham');

        // 9. Ghép dữ liệu thành bảng báo cáo chính
        $baoCaoSanPham = $sanPhams->map(function ($sp) use (
            $nhapThemData,
            $daBanData,
            $tieuHaoData,
            $hangHongData,
            $lotStockData,
            $hasLotsMap,
            $reservedData,
            $giaNhapMoiNhatData
        ) {
            $ma = $sp->ma_san_pham;

            $nhapThem = (int) ($nhapThemData[$ma]->tong_nhap ?? 0);
            $tienNhap = (float) ($nhapThemData[$ma]->tong_tien_nhap ?? 0);

            $daBan = (int) ($daBanData[$ma]->tong_da_ban ?? 0);
            $doanhThu = (float) ($daBanData[$ma]->doanh_thu ?? 0);

            $tieuHao = (int) ($tieuHaoData[$ma]->tong_tieu_hao ?? 0);
            $hangHong = (int) ($hangHongData[$ma]->tong_hong ?? 0);

            // Nguồn On-Hand vật lý thực tế
            $onHand = isset($hasLotsMap[$ma])
                ? (int) ($lotStockData[$ma]->tong_ton_lot ?? 0)
                : (int) $sp->so_luong;

            // Đang giữ & Khả dụng
            $reserved = (int) ($reservedData[$ma]->tong_reserved ?? 0);
            $available = max(0, $onHand - $reserved);

            // Giá nhập trung bình tham khảo
            $giaNhapTB = $nhapThem > 0
                ? round($tienNhap / $nhapThem, 2)
                : (isset($giaNhapMoiNhatData[$ma]) ? (float) $giaNhapMoiNhatData[$ma]->gia_nhap : null);

            // Tỷ lệ hao hụt: Hàng hỏng / Tổng lượng hàng quản lý trong kỳ
            $tongLuongQuanLy = $onHand + $daBan + $tieuHao + $hangHong;
            $tyLeHaoHut = $tongLuongQuanLy > 0
                ? round(($hangHong / $tongLuongQuanLy) * 100, 2)
                : 0;

            return [
                'ma_san_pham'       => $ma,
                'ten_san_pham'      => $sp->ten_san_pham,
                'loai_san_pham'     => $sp->loai_san_pham,
                'trang_thai'        => $sp->trang_thai,
                'hinh_anh'          => $sp->hinh_anh,

                'nhap_them'         => $nhapThem,
                'da_ban'            => $daBan,
                'tieu_hao_san_xuat' => $tieuHao,
                'hang_hong'         => $hangHong,
                'ton_kho_hien_tai'  => $onHand,
                'ton_kha_dung'      => $available,
                'dang_giu'          => $reserved,

                'gia_nhap_tb'       => $giaNhapTB,
                'gia_ban_hien_tai'  => (float) ($sp->gia_ban_hien_tai ?? 0),
                'ty_le_hong'        => $tyLeHaoHut,
                'doanh_thu'         => $doanhThu,
            ];
        });

        // 10. Dòng tổng cộng
        $tongNhapThem = $baoCaoSanPham->sum('nhap_them');
        $tongDaBan = $baoCaoSanPham->sum('da_ban');
        $tongTieuHaoSanXuat = $baoCaoSanPham->sum('tieu_hao_san_xuat');
        $tongHangHong = $baoCaoSanPham->sum('hang_hong');
        $tongTonKhoHienTai = $baoCaoSanPham->sum('ton_kho_hien_tai');
        $tongDoanhThuSanPham = $baoCaoSanPham->sum('doanh_thu');

        $tongTriGiaNhap = $baoCaoSanPham->sum(function ($row) {
            return $row['nhap_them'] * ($row['gia_nhap_tb'] ?? 0);
        });

        $giaNhapTrungBinhChung = $tongNhapThem > 0
            ? round($tongTriGiaNhap / $tongNhapThem, 2)
            : null;

        $tongLuongQuanLyChung = $tongTonKhoHienTai + $tongDaBan + $tongTieuHaoSanXuat + $tongHangHong;
        $tyLeHongChung = $tongLuongQuanLyChung > 0
            ? round(($tongHangHong / $tongLuongQuanLyChung) * 100, 2)
            : 0;

        // 11. Top 5 sản phẩm bán chạy (dùng cùng điều kiện DELIVERED theo delivered timestamp)
        $topSanPhamBanChay = DB::table('chi_tiet_hoa_don as ct')
            ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->join('san_pham as sp', 'ct.ma_san_pham', '=', 'sp.ma_san_pham')
            ->where('hd.trang_thai', 'DELIVERED')
            ->whereNotNull('ct.ma_san_pham')
            ->whereBetween(DB::raw('COALESCE(hd.ngay_giao, hd.ngay_den_noi, hd.ngay_dat)'), [$ngayBatDau, $ngayKetThuc])
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('sp.ten_san_pham', 'like', '%' . $keyword . '%');
            })
            ->when($loaiSanPham, function ($query) use ($loaiSanPham) {
                $query->where('sp.loai_san_pham', $loaiSanPham);
            })
            ->when($trangThai, function ($query) use ($trangThai) {
                $query->where('sp.trang_thai', $trangThai);
            })
            ->select(
                'sp.ten_san_pham',
                DB::raw('SUM(ct.so_luong) as tong_da_ban')
            )
            ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham')
            ->orderByDesc('tong_da_ban')
            ->limit(5)
            ->get();

        return [
            'baoCaoSanPham'         => $baoCaoSanPham,
            'topSanPhamBanChay'     => $topSanPhamBanChay,

            'tongNhapThem'          => $tongNhapThem,
            'tongDaBan'             => $tongDaBan,
            'tongTieuHaoSanXuat'    => $tongTieuHaoSanXuat,
            'tongHangHong'          => $tongHangHong,
            'tongTonKhoHienTai'     => $tongTonKhoHienTai,
            'tongDoanhThuSanPham'   => $tongDoanhThuSanPham,

            'giaNhapTrungBinhChung' => $giaNhapTrungBinhChung,
            'tyLeHongChung'         => $tyLeHongChung,

            'ngayBatDau'            => $ngayBatDau,
            'ngayKetThuc'           => $ngayKetThuc,
            'keyword'               => $keyword,
            'loaiSanPham'           => $loaiSanPham,
            'trangThai'             => $trangThai,
            'dateWarning'           => $dateWarning,
        ];
    }

    // baoHangHong đã chuyển sang SanPhamController

    private function dataHangHong(Request $request): array
    {
        $hhTongSoLuong   = BaoCaoHangHong::sum('so_luong_hong');
        $hhTongLanBaoCao = BaoCaoHangHong::count();

        // Base select dùng chung: join thêm chi_tiet_nhap + phieu_nhap để lấy thông tin lô
        $baseSelect = [
            'bc.ma_bao_cao',
            'sp.ten_san_pham',
            'bc.so_luong_hong',
            'bc.thoi_gian_bao_cao',
            'bc.ly_do',
            'bc.ghi_chu',
            'nv.ten_nhan_vien',
            'pn.ma_phieu_nhap',
            'pn.ngay_nhap',
            'ctn.so_luong as sl_lo_ban_dau',
            'ctn.so_luong_con_lai as sl_lo_con_lai',
        ];

        // Danh sách hàng tươi hỏng (kèm thông tin lô nhập)
        $hhDanhSachTuoi = DB::table('bao_cao_hang_hong as bc')
            ->join('san_pham as sp',        'bc.ma_san_pham',      '=', 'sp.ma_san_pham')
            ->join('nhan_vien as nv',        'bc.ma_nhan_vien',     '=', 'nv.ma_nhan_vien')
            ->leftJoin('chi_tiet_nhap as ctn', 'bc.ma_chi_tiet_nhap', '=', 'ctn.ma_chi_tiet_nhap')
            ->leftJoin('phieu_nhap as pn',   'ctn.ma_phieu_nhap',   '=', 'pn.ma_phieu_nhap')
            ->whereIn('sp.loai_san_pham', self::LOAI_TUOI)
            ->select($baseSelect)
            ->orderBy('bc.thoi_gian_bao_cao', 'desc')
            ->get();

        $hhTongTuoi = $hhDanhSachTuoi->sum('so_luong_hong');

        // Danh sách hàng không tươi hỏng (kèm thông tin lô nhập)
        $hhDanhSachKhacTuoi = DB::table('bao_cao_hang_hong as bc')
            ->join('san_pham as sp',          'bc.ma_san_pham',      '=', 'sp.ma_san_pham')
            ->join('nhan_vien as nv',          'bc.ma_nhan_vien',     '=', 'nv.ma_nhan_vien')
            ->leftJoin('chi_tiet_nhap as ctn', 'bc.ma_chi_tiet_nhap', '=', 'ctn.ma_chi_tiet_nhap')
            ->leftJoin('phieu_nhap as pn',     'ctn.ma_phieu_nhap',   '=', 'pn.ma_phieu_nhap')
            ->whereNotIn('sp.loai_san_pham', self::LOAI_TUOI)
            ->select($baseSelect)
            ->orderBy('bc.thoi_gian_bao_cao', 'desc')
            ->get();

        $hhTongKhacTuoi = $hhDanhSachKhacTuoi->sum('so_luong_hong');

        // Top 5 sản phẩm hỏng nhiều nhất
        $hhTopHong = DB::table('bao_cao_hang_hong as bc')
            ->join('san_pham as sp', 'bc.ma_san_pham', '=', 'sp.ma_san_pham')
            ->select('sp.ten_san_pham', DB::raw('SUM(bc.so_luong_hong) as tong_hong'))
            ->groupBy('bc.ma_san_pham', 'sp.ten_san_pham')
            ->orderBy('tong_hong', 'desc')
            ->limit(5)
            ->get();

        return compact(
            'hhTongSoLuong', 'hhTongLanBaoCao',
            'hhDanhSachTuoi', 'hhTongTuoi',
            'hhDanhSachKhacTuoi', 'hhTongKhacTuoi',
            'hhTopHong'
        );
    }

    
    private function dataDoanhThu(Request $request): array
    {
        $tuNgay = $request->input('tu_ngay', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $denNgay = $request->input('den_ngay', Carbon::now()->format('Y-m-d'));
        $start = Carbon::parse($tuNgay)->startOfDay();
        $end = Carbon::parse($denNgay)->endOfDay();

        $kieuHienThi = $request->input('kieu_hien_thi');
        if (empty($kieuHienThi)) {
            $diffDays = $start->diffInDays($end);
            if ($diffDays <= 62) {
                $kieuHienThi = 'ngay';
            } elseif ($diffDays <= 366) {
                $kieuHienThi = 'thang';
            } else {
                $kieuHienThi = 'nam';
            }
        }
        $nhomKinhDoanh = $request->input('nhom_kinh_doanh');
        $pttt = $request->input('phuong_thuc_thanh_toan');

        // Nguồn 1: Sản phẩm bán lẻ (chi_tiet_hoa_don)
        $spRows = collect();
        if (empty($nhomKinhDoanh) || $nhomKinhDoanh === 'hoa_le') {
            $spQuery = DB::table('hoa_don as hd')
                ->join('chi_tiet_hoa_don as ct', 'hd.ma_hoa_don', '=', 'ct.ma_hoa_don')
                ->join('san_pham as sp', 'ct.ma_san_pham', '=', 'sp.ma_san_pham')
                ->leftJoin('chi_tiet_nhap as ctn', 'ct.ma_chi_tiet_nhap', '=', 'ctn.ma_chi_tiet_nhap')
                ->where('hd.trang_thai', 'DELIVERED')
                ->whereBetween('hd.ngay_dat', [$start, $end]);

            if ($pttt === 'COD') {
                $spQuery->where('hd.phuong_thuc_thanh_toan', 'COD');
            } elseif ($pttt === 'NGAN_HANG') {
                $spQuery->where('hd.phuong_thuc_thanh_toan', '!=', 'COD');
            }

            $spRows = $spQuery->selectRaw("
                hd.ma_hoa_don,
                hd.ngay_dat,
                sp.ma_san_pham as item_id,
                sp.ten_san_pham as item_name,
                sp.hinh_anh as item_image,
                'hoa_le' as nhom_hang,
                CASE WHEN sp.loai_san_pham = 'HOA_TUOI' THEN 'Hoa lẻ' ELSE 'Sản phẩm lẻ' END as ten_nhom,
                ct.so_luong as so_luong,
                (ct.so_luong * ct.gia_ban_snapshot) as doanh_thu,
                (ct.so_luong * COALESCE(ct.gia_nhap_snapshot, ctn.gia_nhap, 0)) as gia_von
            ")->get();
        }

        // Nguồn 2: Bó hoa thành phẩm & Bó hoa khác (bo_hoa)
        $boRows = collect();
        if (empty($nhomKinhDoanh) || in_array($nhomKinhDoanh, ['bo_hoa_co_san', 'custom_mix'])) {
            $boQuery = DB::table('hoa_don as hd')
                ->join('bo_hoa as bo', 'hd.ma_hoa_don', '=', 'bo.ma_hoa_don')
                ->where('hd.trang_thai', 'DELIVERED')
                ->whereBetween('hd.ngay_dat', [$start, $end]);

            if ($nhomKinhDoanh === 'bo_hoa_co_san') {
                $boQuery->where('bo.loai_bo_hoa', '!=', 'TUY_CHON');
            } elseif ($nhomKinhDoanh === 'custom_mix') {
                $boQuery->where('bo.loai_bo_hoa', '=', 'TUY_CHON');
            }

            if ($pttt === 'COD') {
                $boQuery->where('hd.phuong_thuc_thanh_toan', 'COD');
            } elseif ($pttt === 'NGAN_HANG') {
                $boQuery->where('hd.phuong_thuc_thanh_toan', '!=', 'COD');
            }

            $hasHinhAnhBo = \Illuminate\Support\Facades\Schema::hasColumn('bo_hoa', 'hinh_anh');
            $hasGiaVonBo  = \Illuminate\Support\Facades\Schema::hasColumn('bo_hoa', 'gia_von');
            $colHinhAnh   = $hasHinhAnhBo ? 'bo.hinh_anh' : 'NULL';
            $colGiaVon    = $hasGiaVonBo  ? 'bo.gia_von'  : 'NULL';

            $boRows = $boQuery->selectRaw("
                hd.ma_hoa_don,
                hd.ngay_dat,
                bo.ma_bo_hoa as item_id,
                bo.ten_bo_hoa as item_name,
                {$colHinhAnh} as item_image,
                CASE WHEN bo.loai_bo_hoa = 'TUY_CHON' THEN 'custom_mix' ELSE 'bo_hoa_co_san' END as nhom_hang,
                CASE WHEN bo.loai_bo_hoa = 'TUY_CHON' THEN 'Custom Mix' ELSE 'Bó hoa' END as ten_nhom,
                1 as so_luong,
                bo.gia_ban as doanh_thu,
                COALESCE({$colGiaVon}, (
                    SELECT SUM(ctbh.so_luong * ctn.gia_nhap)
                    FROM chi_tiet_bo_hoa ctbh
                    JOIN chi_tiet_nhap ctn ON ctbh.ma_chi_tiet_nhap = ctn.ma_chi_tiet_nhap
                    WHERE ctbh.ma_bo_hoa = bo.ma_bo_hoa
                ), 0) as gia_von
            ")->get();
        }

        $allItems = $spRows->concat($boRows);

        // Tổng đơn hàng
        if (!empty($nhomKinhDoanh)) {
            $tongDonHang = $allItems->pluck('ma_hoa_don')->unique()->count();
        } else {
            $hdCountQuery = DB::table('hoa_don')
                ->where('trang_thai', 'DELIVERED')
                ->whereBetween('ngay_dat', [$start, $end]);

            if ($pttt === 'COD') {
                $hdCountQuery->where('phuong_thuc_thanh_toan', 'COD');
            } elseif ($pttt === 'NGAN_HANG') {
                $hdCountQuery->where('phuong_thuc_thanh_toan', '!=', 'COD');
            }
            $tongDonHang = $hdCountQuery->count();
        }

        $tongSanPhamBan = (int) $allItems->sum('so_luong');
        $tongDoanhThu = (float) $allItems->sum('doanh_thu');
        $tongVon = (float) $allItems->sum('gia_von');
        $tongLoiNhuan = $tongDoanhThu - $tongVon;
        $giaTriDonTrungBinh = $tongDonHang > 0 ? round($tongDoanhThu / $tongDonHang) : 0;

        // Nhóm timeline
        $grouped = $allItems->groupBy(function ($it) use ($kieuHienThi) {
            $d = Carbon::parse($it->ngay_dat);
            if ($kieuHienThi === 'ngay') {
                return $d->format('d/m/Y');
            }
            if ($kieuHienThi === 'quy') {
                return 'Quý ' . $d->quarter . '/' . $d->year;
            }
            if ($kieuHienThi === 'nam') {
                return $d->format('Y');
            }
            return $d->format('m/Y');
        });

        $duLieuTheoMoc = $grouped->map(function ($items, $moc) {
            $dt = (float) $items->sum('doanh_thu');
            $cost = (float) $items->sum('gia_von');
            return (object) [
                'thoi_gian' => $moc,
                'so_don_hang' => (int) $items->pluck('ma_hoa_don')->unique()->count(),
                'tong_san_pham_ban' => (int) $items->sum('so_luong'),
                'doanh_thu' => $dt,
                'gia_von' => $cost,
                'loi_nhuan' => $dt - $cost,
            ];
        });

        $mocThoiGian = collect();
        $current = $start->copy();
        while ($current <= $end) {
            if ($kieuHienThi === 'ngay') {
                $key = $current->format('d/m/Y');
                $current->addDay();
            } elseif ($kieuHienThi === 'quy') {
                $key = 'Quý ' . $current->quarter . '/' . $current->year;
                $current->addQuarter();
            } elseif ($kieuHienThi === 'nam') {
                $key = $current->format('Y');
                $current->addYear();
            } else {
                $key = $current->format('m/Y');
                $current->addMonth();
            }
            $mocThoiGian->push($key);
        }

        $chiTietDoanhThu = $mocThoiGian->unique()->map(function ($moc) use ($duLieuTheoMoc) {
            $row = $duLieuTheoMoc->get($moc);
            return (object) [
                'thoi_gian' => $moc,
                'so_don_hang' => (int) ($row->so_don_hang ?? 0),
                'tong_san_pham_ban' => (int) ($row->tong_san_pham_ban ?? 0),
                'doanh_thu' => (float) ($row->doanh_thu ?? 0),
                'gia_von' => (float) ($row->gia_von ?? 0),
                'loi_nhuan' => (float) ($row->loi_nhuan ?? 0),
            ];
        });

        // Tính doanh thu theo nhóm sản phẩm
        $flashSaleHdIds = DB::table('inventory_reservations')
            ->whereNotNull('ma_flash_sale')
            ->pluck('ma_hoa_don')
            ->unique()
            ->toArray();

        $dtBoHoa = 0;
        $dtCustomMix = 0;
        $dtHoaLe = 0;
        $dtFlashSale = 0;

        foreach ($allItems as $item) {
            $amount = (float) $item->doanh_thu;
            if (in_array($item->ma_hoa_don, $flashSaleHdIds)) {
                $dtFlashSale += $amount;
            } elseif ($item->nhom_hang === 'bo_hoa_co_san') {
                $dtBoHoa += $amount;
            } elseif ($item->nhom_hang === 'custom_mix') {
                $dtCustomMix += $amount;
            } else {
                $dtHoaLe += $amount;
            }
        }

        $totalNhom = $dtBoHoa + $dtCustomMix + $dtHoaLe + $dtFlashSale;

        $pctBoHoa = $totalNhom > 0 ? round(($dtBoHoa / $totalNhom) * 100, 1) : 0;
        $pctCustomMix = $totalNhom > 0 ? round(($dtCustomMix / $totalNhom) * 100, 1) : 0;
        $pctHoaLe = $totalNhom > 0 ? round(($dtHoaLe / $totalNhom) * 100, 1) : 0;
        $pctFlashSale = $totalNhom > 0 ? round(($dtFlashSale / $totalNhom) * 100, 1) : 0;

        $dataNhomSanPham = [
            'bo_hoa_co_san' => ['doanh_thu' => $dtBoHoa, 'pct' => $pctBoHoa],
            'custom_mix' => ['doanh_thu' => $dtCustomMix, 'pct' => $pctCustomMix],
            'hoa_le' => ['doanh_thu' => $dtHoaLe, 'pct' => $pctHoaLe],
            'flash_sale' => ['doanh_thu' => $dtFlashSale, 'pct' => $pctFlashSale],
            'chart_data' => [$dtBoHoa, $dtCustomMix, $dtHoaLe, $dtFlashSale],
            'total' => $totalNhom,
        ];

        // Tính doanh thu theo phương thức thanh toán
        $hdPttt = DB::table('hoa_don')
            ->where('trang_thai', 'DELIVERED')
            ->whereBetween('ngay_dat', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN phuong_thuc_thanh_toan = 'COD' THEN tong_tien ELSE 0 END) as cod,
                SUM(CASE WHEN phuong_thuc_thanh_toan != 'COD' THEN tong_tien ELSE 0 END) as vnpay
            ")
            ->first();

        $dtCod = (float) ($hdPttt->cod ?? 0);
        $dtVnPay = (float) ($hdPttt->vnpay ?? 0);
        $totalPttt = $dtCod + $dtVnPay;

        $pctCod = $totalPttt > 0 ? round(($dtCod / $totalPttt) * 100, 1) : 0;
        $pctVnPay = $totalPttt > 0 ? round(($dtVnPay / $totalPttt) * 100, 1) : 0;

        $dataPttt = [
            'cod' => ['doanh_thu' => $dtCod, 'pct' => $pctCod],
            'vnpay' => ['doanh_thu' => $dtVnPay, 'pct' => $pctVnPay],
            'chart_data' => [$dtCod, $dtVnPay],
            'total' => $totalPttt,
        ];

        // Top 5 mặt hàng theo doanh thu
        $topSanPham = $allItems->groupBy('item_name')->map(function ($items, $name) {
            $first = $items->first();
            $dt = (float) $items->sum('doanh_thu');
            $cost = (float) $items->sum('gia_von');
            return (object) [
                'ten_san_pham' => $name,
                'hinh_anh' => $first->item_image,
                'nhom_hang' => $first->nhom_hang,
                'ten_nhom' => $first->ten_nhom,
                'tong_san_pham_ban' => (int) $items->sum('so_luong'),
                'doanh_thu' => $dt,
                'gia_von' => $cost,
                'loi_nhuan' => $dt - $cost,
            ];
        })->sortByDesc('doanh_thu')->values()->take(5);

        // Top 5 mặt hàng theo lợi nhuận
        $topLoiNhuan = $allItems->groupBy('item_name')->map(function ($items, $name) {
            $first = $items->first();
            $dt = (float) $items->sum('doanh_thu');
            $cost = (float) $items->sum('gia_von');
            return (object) [
                'ten_san_pham' => $name,
                'hinh_anh' => $first->item_image,
                'nhom_hang' => $first->nhom_hang,
                'ten_nhom' => $first->ten_nhom,
                'tong_san_pham_ban' => (int) $items->sum('so_luong'),
                'doanh_thu' => $dt,
                'gia_von' => $cost,
                'loi_nhuan' => $dt - $cost,
            ];
        })->sortByDesc('loi_nhuan')->values()->take(5);

        return [
            'tuNgay' => $tuNgay,
            'denNgay' => $denNgay,
            'kieuHienThi' => $kieuHienThi,
            'tongDonHang' => $tongDonHang,
            'tongSanPhamBan' => $tongSanPhamBan,
            'tongDoanhThu' => $tongDoanhThu,
            'tongVon' => $tongVon,
            'tongLoiNhuan' => $tongLoiNhuan,
            'giaTriDonTrungBinh' => $giaTriDonTrungBinh,
            'chiTietDoanhThu' => $chiTietDoanhThu,
            'topSanPham' => $topSanPham,
            'topLoiNhuan' => $topLoiNhuan,
            'dataNhomSanPham' => $dataNhomSanPham,
            'dataPttt' => $dataPttt,
        ];
    }

    private function dataSanPham(Request $request): array
    // {
    //     $tuNgay  = $request->filled('tu_ngay')
    //         ? Carbon::parse($request->tu_ngay)->startOfDay()
    //         : Carbon::now()->startOfMonth();
    //     $denNgay = $request->filled('den_ngay')
    //         ? Carbon::parse($request->den_ngay)->endOfDay()
    //         : Carbon::now()->endOfDay();
    //     $keyword = trim($request->get('keyword', ''));
    //     $spTop   = (int) $request->get('top', 10);

    //     $sanPhams = SanPham::query()
    //         ->select('ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'trang_thai')
    //         ->when($keyword, function ($query, $keyword) {
    //             $query->where('ten_san_pham', 'like', '%' . $keyword . '%');
    //         })
    //         ->orderBy('ten_san_pham')
    //         ->get();

    //     $nhapTruocKy = DB::table('chi_tiet_nhap as ctn')
    //         ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
    //         ->where('pn.trang_thai', 'CONFIRMED')
    //         ->where('pn.ngay_nhap', '<', $tuNgay)
    //         ->groupBy('ctn.ma_san_pham')
    //         ->select('ctn.ma_san_pham', DB::raw('SUM(ctn.so_luong) as tong_nhap'))
    //         ->pluck('tong_nhap', 'ma_san_pham');

    //     $daBanTruocKy = DB::table('chi_tiet_hoa_don as cthd')
    //         ->join('hoa_don as hd', 'cthd.ma_hoa_don', '=', 'hd.ma_hoa_don')
    //         ->where('hd.trang_thai', 'DELIVERED')
    //         ->where('hd.ngay_dat', '<', $tuNgay)
    //         ->groupBy('cthd.ma_san_pham')
    //         ->select('cthd.ma_san_pham', DB::raw('SUM(cthd.so_luong) as tong_ban'))
    //         ->pluck('tong_ban', 'ma_san_pham');

    //     $hongTruocKy = BaoCaoHangHong::query()
    //         ->where('thoi_gian_bao_cao', '<', $tuNgay)
    //         ->groupBy('ma_san_pham')
    //         ->select('ma_san_pham', DB::raw('SUM(so_luong_hong) as tong_hong'))
    //         ->pluck('tong_hong', 'ma_san_pham');

    //     $nhapTrongKy = DB::table('chi_tiet_nhap as ctn')
    //         ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
    //         ->where('pn.trang_thai', 'CONFIRMED')
    //         ->whereBetween('pn.ngay_nhap', [$tuNgay, $denNgay])
    //         ->groupBy('ctn.ma_san_pham')
    //         ->select(
    //             'ctn.ma_san_pham',
    //             DB::raw('SUM(ctn.so_luong) as tong_nhap'),
    //             DB::raw('SUM(ctn.so_luong * ctn.gia_nhap) as tong_tien_nhap')
    //         )
    //         ->get()
    //         ->keyBy('ma_san_pham');

    //     $daBanTrongKy = DB::table('chi_tiet_hoa_don as cthd')
    //         ->join('hoa_don as hd', 'cthd.ma_hoa_don', '=', 'hd.ma_hoa_don')
    //         ->where('hd.trang_thai', 'DELIVERED')
    //         ->whereBetween('hd.ngay_dat', [$tuNgay, $denNgay])
    //         ->groupBy('cthd.ma_san_pham')
    //         ->select('cthd.ma_san_pham', DB::raw('SUM(cthd.so_luong) as tong_ban'))
    //         ->pluck('tong_ban', 'ma_san_pham');

    //     $hongTrongKy = BaoCaoHangHong::query()
    //         ->whereBetween('thoi_gian_bao_cao', [$tuNgay, $denNgay])
    //         ->groupBy('ma_san_pham')
    //         ->select('ma_san_pham', DB::raw('SUM(so_luong_hong) as tong_hong'))
    //         ->pluck('tong_hong', 'ma_san_pham');

    //     $giaBanHienTai = DB::table('chi_tiet_nhap as ctn')
    //         ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
    //         ->where('pn.trang_thai', 'CONFIRMED')
    //         ->orderByDesc('pn.ngay_nhap')
    //         ->orderByDesc('ctn.ma_chi_tiet_nhap')
    //         ->get(['ctn.ma_san_pham', 'ctn.gia_ban'])
    //         ->unique('ma_san_pham')
    //         ->pluck('gia_ban', 'ma_san_pham');

    //     $baoCaoSanPham = $sanPhams->map(function ($sanPham) use (
    //         $nhapTruocKy,
    //         $daBanTruocKy,
    //         $hongTruocKy,
    //         $nhapTrongKy,
    //         $daBanTrongKy,
    //         $hongTrongKy,
    //         $giaBanHienTai
    //     ) {
    //         $ma = $sanPham->ma_san_pham;
    //         $tongNhapTruocKy = (int) ($nhapTruocKy[$ma] ?? 0);
    //         $tongDaBanTruocKy = (int) ($daBanTruocKy[$ma] ?? 0);
    //         $tongHongTruocKy = (int) ($hongTruocKy[$ma] ?? 0);
    //         $tongNhapTrongKy = (int) ($nhapTrongKy[$ma]->tong_nhap ?? 0);
    //         $tongTienNhapTrongKy = (float) ($nhapTrongKy[$ma]->tong_tien_nhap ?? 0);
    //         $tongDaBanTrongKy = (int) ($daBanTrongKy[$ma] ?? 0);
    //         $tongHongTrongKy = (int) ($hongTrongKy[$ma] ?? 0);

    //         $tonDauKy = $tongNhapTruocKy - $tongDaBanTruocKy - $tongHongTruocKy;
    //         $tonCuoiKy = $tonDauKy + $tongNhapTrongKy - $tongDaBanTrongKy - $tongHongTrongKy;
    //         $giaNhapTrungBinh = $tongNhapTrongKy > 0
    //             ? round($tongTienNhapTrongKy / $tongNhapTrongKy, 2)
    //             : 0;
    //         $giaBan = isset($giaBanHienTai[$ma]) ? (float) $giaBanHienTai[$ma] : 0;
    //         $tyLeHong = $tongNhapTrongKy > 0
    //             ? round(($tongHongTrongKy / $tongNhapTrongKy) * 100, 2)
    //             : 0;

    //         return [
    //             'ma_san_pham' => $ma,
    //             'ten_san_pham' => $sanPham->ten_san_pham,
    //             'loai_san_pham' => $sanPham->loai_san_pham,
    //             'trang_thai' => $sanPham->trang_thai,
    //             'ton_dau_ky' => $tonDauKy,
    //             'nhap_trong_ky' => $tongNhapTrongKy,
    //             'da_ban_trong_ky' => $tongDaBanTrongKy,
    //             'hang_hong_trong_ky' => $tongHongTrongKy,
    //             'ton_cuoi_ky' => $tonCuoiKy,
    //             'gia_nhap_tb_trong_ky' => $giaNhapTrungBinh,
    //             'gia_ban_hien_tai' => $giaBan,
    //             'ty_le_hong' => $tyLeHong,
    //         ];
    //     });

    //     $tongTonDauKy = $baoCaoSanPham->sum('ton_dau_ky');
    //     $tongNhapTrongKy = $baoCaoSanPham->sum('nhap_trong_ky');
    //     $tongDaBanTrongKy = $baoCaoSanPham->sum('da_ban_trong_ky');
    //     $tongHangHongTrongKy = $baoCaoSanPham->sum('hang_hong_trong_ky');
    //     $tongTonCuoiKy = $baoCaoSanPham->sum('ton_cuoi_ky');

    //     $tongTriGiaNhapTrongKy = $baoCaoSanPham->sum(function ($row) {
    //         return $row['nhap_trong_ky'] * $row['gia_nhap_tb_trong_ky'];
    //     });

    //     $giaNhapTrungBinhChung = $tongNhapTrongKy > 0
    //         ? round($tongTriGiaNhapTrongKy / $tongNhapTrongKy, 2)
    //         : 0;

    //     $tyLeHongChung = $tongNhapTrongKy > 0
    //         ? round(($tongHangHongTrongKy / $tongNhapTrongKy) * 100, 2)
    //         : 0;

    //     $spDanhSach = DB::table('chi_tiet_hoa_don as ct')
    //         ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
    //         ->join('san_pham as sp', 'ct.ma_san_pham', '=', 'sp.ma_san_pham')
    //         ->where('hd.trang_thai', 'DELIVERED')
    //         ->whereBetween('hd.ngay_dat', [$tuNgay, $denNgay])
    //         ->when($keyword, function ($query, $keyword) {
    //             $query->where('sp.ten_san_pham', 'like', '%' . $keyword . '%');
    //         })
    //         ->select('sp.ten_san_pham', DB::raw('SUM(ct.so_luong) as tong_ban'))
    //         ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham')
    //         ->orderByDesc('tong_ban')
    //         ->limit($spTop)
    //         ->get();

    //     $topSanPhamBanChay = DB::table('chi_tiet_hoa_don as ct')
    //         ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
    //         ->join('san_pham as sp', 'ct.ma_san_pham', '=', 'sp.ma_san_pham')
    //         ->where('hd.trang_thai', 'DELIVERED')
    //         ->whereBetween('hd.ngay_dat', [$tuNgay, $denNgay])
    //         ->when($keyword, function ($query, $keyword) {
    //             $query->where('sp.ten_san_pham', 'like', '%' . $keyword . '%');
    //         })
    //         ->select('sp.ten_san_pham', DB::raw('SUM(ct.so_luong) as tong_da_ban'))
    //         ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham')
    //         ->orderByDesc('tong_da_ban')
    //         ->limit(5)
    //         ->get();

    //     $spTongBan = $spDanhSach->sum('tong_ban');

    //     return compact(
    //         'tuNgay', 'denNgay', 'keyword', 'spTop', 'spDanhSach', 'spTongBan',
    //         'baoCaoSanPham', 'topSanPhamBanChay',
    //         'tongTonDauKy', 'tongNhapTrongKy', 'tongDaBanTrongKy',
    //         'tongHangHongTrongKy', 'tongTonCuoiKy',
    //         'giaNhapTrungBinhChung', 'tyLeHongChung'
    //     );
    // }
    {
        $tuNgay  = $request->filled('tu_ngay')
            ? Carbon::parse($request->tu_ngay)->startOfDay()
            : Carbon::now()->startOfMonth();
        $denNgay = $request->filled('den_ngay')
            ? Carbon::parse($request->den_ngay)->endOfDay()
            : Carbon::now()->endOfDay();
        $keyword = trim($request->get('keyword', ''));
        $spTop   = (int) $request->get('top', 10);

        $sanPhams = SanPham::query()
            ->select('ma_san_pham', 'ten_san_pham', 'loai_san_pham', 'trang_thai')
            ->when($keyword, function ($query, $keyword) {
                $query->where('ten_san_pham', 'like', '%' . $keyword . '%');
            })
            ->orderBy('ten_san_pham')
            ->get();

        $nhapTruocKy = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->where('pn.ngay_nhap', '<', $tuNgay)
            ->groupBy('ctn.ma_san_pham')
            ->select('ctn.ma_san_pham', DB::raw('SUM(ctn.so_luong) as tong_nhap'))
            ->pluck('tong_nhap', 'ma_san_pham');

        $daBanTruocKy = DB::table('chi_tiet_hoa_don as cthd')
            ->join('hoa_don as hd', 'cthd.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->where('hd.trang_thai', 'DELIVERED')
            ->where('hd.ngay_dat', '<', $tuNgay)
            ->groupBy('cthd.ma_san_pham')
            ->select('cthd.ma_san_pham', DB::raw('SUM(cthd.so_luong) as tong_ban'))
            ->pluck('tong_ban', 'ma_san_pham');

        $hongTruocKy = BaoCaoHangHong::query()
            ->where('thoi_gian_bao_cao', '<', $tuNgay)
            ->groupBy('ma_san_pham')
            ->select('ma_san_pham', DB::raw('SUM(so_luong_hong) as tong_hong'))
            ->pluck('tong_hong', 'ma_san_pham');

        $nhapTrongKy = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->whereBetween('pn.ngay_nhap', [$tuNgay, $denNgay])
            ->groupBy('ctn.ma_san_pham')
            ->select(
                'ctn.ma_san_pham',
                DB::raw('SUM(ctn.so_luong) as tong_nhap'),
                DB::raw('SUM(ctn.so_luong * ctn.gia_nhap) as tong_tien_nhap')
            )
            ->get()
            ->keyBy('ma_san_pham');

        $daBanTrongKy = DB::table('chi_tiet_hoa_don as cthd')
            ->join('hoa_don as hd', 'cthd.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->where('hd.trang_thai', 'DELIVERED')
            ->whereBetween('hd.ngay_dat', [$tuNgay, $denNgay])
            ->groupBy('cthd.ma_san_pham')
            ->select('cthd.ma_san_pham', DB::raw('SUM(cthd.so_luong) as tong_ban'))
            ->pluck('tong_ban', 'ma_san_pham');

        $hongTrongKy = BaoCaoHangHong::query()
            ->whereBetween('thoi_gian_bao_cao', [$tuNgay, $denNgay])
            ->groupBy('ma_san_pham')
            ->select('ma_san_pham', DB::raw('SUM(so_luong_hong) as tong_hong'))
            ->pluck('tong_hong', 'ma_san_pham');

        $giaBanHienTai = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->orderByDesc('pn.ngay_nhap')
            ->orderByDesc('ctn.ma_chi_tiet_nhap')
            ->get(['ctn.ma_san_pham', 'ctn.gia_ban'])
            ->unique('ma_san_pham')
            ->pluck('gia_ban', 'ma_san_pham');

        $baoCaoSanPham = $sanPhams->map(function ($sanPham) use (
            $nhapTruocKy, $daBanTruocKy, $hongTruocKy,
            $nhapTrongKy, $daBanTrongKy, $hongTrongKy, $giaBanHienTai
        ) {
            $ma = $sanPham->ma_san_pham;
            $tongNhapTruocKy     = (int)   ($nhapTruocKy[$ma] ?? 0);
            $tongDaBanTruocKy    = (int)   ($daBanTruocKy[$ma] ?? 0);
            $tongHongTruocKy     = (int)   ($hongTruocKy[$ma] ?? 0);
            $tongNhapTrongKy     = (int)   ($nhapTrongKy[$ma]->tong_nhap ?? 0);
            $tongTienNhapTrongKy = (float) ($nhapTrongKy[$ma]->tong_tien_nhap ?? 0);
            $tongDaBanTrongKy    = (int)   ($daBanTrongKy[$ma] ?? 0);
            $tongHongTrongKy     = (int)   ($hongTrongKy[$ma] ?? 0);

            $tonDauKy         = $tongNhapTruocKy - $tongDaBanTruocKy - $tongHongTruocKy;
            $tonCuoiKy        = $tonDauKy + $tongNhapTrongKy - $tongDaBanTrongKy - $tongHongTrongKy;
            $giaNhapTrungBinh = $tongNhapTrongKy > 0
                ? round($tongTienNhapTrongKy / $tongNhapTrongKy, 2)
                : 0;
            $giaBan   = isset($giaBanHienTai[$ma]) ? (float) $giaBanHienTai[$ma] : 0;
            $tyLeHong = $tongNhapTrongKy > 0
                ? round(($tongHongTrongKy / $tongNhapTrongKy) * 100, 2)
                : 0;

            return [
                'ma_san_pham'          => $ma,
                'ten_san_pham'         => $sanPham->ten_san_pham,
                'loai_san_pham'        => $sanPham->loai_san_pham,
                'trang_thai'           => $sanPham->trang_thai,
                'ton_dau_ky'           => $tonDauKy,
                'nhap_trong_ky'        => $tongNhapTrongKy,
                'da_ban_trong_ky'      => $tongDaBanTrongKy,
                'hang_hong_trong_ky'   => $tongHongTrongKy,
                'ton_cuoi_ky'          => $tonCuoiKy,
                'gia_nhap_tb_trong_ky' => $giaNhapTrungBinh,
                'gia_ban_hien_tai'     => $giaBan,
                'ty_le_hong'           => $tyLeHong,
            ];
        });

        $tongTonDauKy        = $baoCaoSanPham->sum('ton_dau_ky');
        $tongNhapTrongKy     = $baoCaoSanPham->sum('nhap_trong_ky');
        $tongDaBanTrongKy    = $baoCaoSanPham->sum('da_ban_trong_ky');
        $tongHangHongTrongKy = $baoCaoSanPham->sum('hang_hong_trong_ky');
        $tongTonCuoiKy       = $baoCaoSanPham->sum('ton_cuoi_ky');

        $tongTriGiaNhapTrongKy = $baoCaoSanPham->sum(fn ($row) =>
            $row['nhap_trong_ky'] * $row['gia_nhap_tb_trong_ky']
        );

        $giaNhapTrungBinhChung = $tongNhapTrongKy > 0
            ? round($tongTriGiaNhapTrongKy / $tongNhapTrongKy, 2)
            : 0;

        $tyLeHongChung = $tongNhapTrongKy > 0
            ? round(($tongHangHongTrongKy / $tongNhapTrongKy) * 100, 2)
            : 0;

        $topSanPhamBanChay = DB::table('chi_tiet_hoa_don as ct')
            ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->join('san_pham as sp', 'ct.ma_san_pham', '=', 'sp.ma_san_pham')
            ->where('hd.trang_thai', 'DELIVERED')
            ->whereBetween('hd.ngay_dat', [$tuNgay, $denNgay])
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('sp.ten_san_pham', 'like', '%' . $keyword . '%');
            })
            ->select('sp.ten_san_pham', DB::raw('SUM(ct.so_luong) as tong_da_ban'))
            ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham')
            ->orderByDesc('tong_da_ban')
            ->limit(5)
            ->get();

        return compact(
            'tuNgay', 'denNgay', 'keyword', 'spTop',
            'baoCaoSanPham', 'topSanPhamBanChay',
            'tongTonDauKy', 'tongNhapTrongKy', 'tongDaBanTrongKy',
            'tongHangHongTrongKy', 'tongTonCuoiKy',
            'giaNhapTrungBinhChung', 'tyLeHongChung'
        );
    }

    private function dataTonKho(Request $request): array
    {
        $tkNguong = (int) $request->get('nguong', 5);

        $tkSapHet = SanPham::where('so_luong', '<', $tkNguong)
            ->where('trang_thai', 'DANG_BAN')
            ->orderBy('so_luong')
            ->get();

        $tkLoHang = DB::table('chi_tiet_nhap as ctn')
            ->join('san_pham as sp', 'ctn.ma_san_pham', '=', 'sp.ma_san_pham')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->where('ctn.so_luong_con_lai', '>', 0)
            ->select('sp.ten_san_pham', 'ctn.gia_nhap', 'ctn.gia_ban', 'ctn.so_luong', 'ctn.so_luong_con_lai', 'pn.ngay_nhap')
            ->orderBy('pn.ngay_nhap', 'desc')
            ->get();

        $tkNhapXuat = DB::table('san_pham as sp')
            ->leftJoin('chi_tiet_nhap as ctn', 'sp.ma_san_pham', '=', 'ctn.ma_san_pham')
            ->leftJoin('phieu_nhap as pn', function ($join) {
                $join->on('ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
                     ->where('pn.trang_thai', 'CONFIRMED');
            })
            ->leftJoin('chi_tiet_hoa_don as cthd', 'sp.ma_san_pham', '=', 'cthd.ma_san_pham')
            ->leftJoin('hoa_don as hd', function ($join) {
                $join->on('cthd.ma_hoa_don', '=', 'hd.ma_hoa_don')
                     ->where('hd.trang_thai', 'DELIVERED');
            })
            ->select(
                'sp.ma_san_pham', 'sp.ten_san_pham', 'sp.so_luong as ton_hien_tai',
                DB::raw('SUM(DISTINCT ctn.so_luong) as tong_nhap'),
                DB::raw('SUM(cthd.so_luong) as tong_ban')
            )
            ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham', 'sp.so_luong')
            ->orderBy('sp.ten_san_pham')
            ->get();

        return compact('tkNguong', 'tkSapHet', 'tkLoHang', 'tkNhapXuat');
    }

    private function dataKhachHang(Request $request): array
    {
        $khTop = (int) $request->get('top', 10);

        $khDanhSach = DB::table('khach_hang as kh')
            ->join('hoa_don as hd', 'kh.ma_khach_hang', '=', 'hd.ma_khach_hang')
            ->where('hd.trang_thai', 'DELIVERED')
            ->select(
                'kh.ma_khach_hang', 'kh.ten_khach_hang', 'kh.so_dien_thoai', 'kh.email',
                DB::raw('COUNT(hd.ma_hoa_don) as so_don_hang'),
                DB::raw('SUM(hd.tong_tien) as tong_tien_mua')
            )
            ->groupBy('kh.ma_khach_hang', 'kh.ten_khach_hang', 'kh.so_dien_thoai', 'kh.email')
            ->orderBy('tong_tien_mua', 'desc')
            ->limit($khTop)
            ->get();

        $khTongKhachHang = KhachHang::count();

        return compact('khTop', 'khDanhSach', 'khTongKhachHang');
    }

    private function layDoanhThuTheoNgay($tuNgay, $denNgay): array
    {
        $rawData = HoaDon::whereBetween('ngay_dat', [$tuNgay, $denNgay])
            ->where(function ($q) {
                $q->where('trang_thai', 'DELIVERED')
                  ->orWhere('trang_thai_thanh_toan', 'DA_THANH_TOAN');
            })
            ->selectRaw('DATE(ngay_dat) as ngay, SUM(tong_tien) as doanh_thu')
            ->groupBy('ngay')
            ->orderBy('ngay')
            ->get()
            ->keyBy('ngay');

        $labels  = [];
        $data    = [];
        $current = Carbon::parse($tuNgay)->copy();
        $end     = Carbon::parse($denNgay)->copy();

        while ($current->lte($end)) {
            $key      = $current->format('Y-m-d');
            $labels[] = $current->format('d/m');
            $data[]   = isset($rawData[$key]) ? (float) $rawData[$key]->doanh_thu : 0;
            $current->addDay();
        }

        return compact('labels', 'data');
    }

    private function layTopSanPham(int $top = 10)
    {
        return DB::table('chi_tiet_hoa_don as ct')
            ->join('hoa_don as hd', 'ct.ma_hoa_don', '=', 'hd.ma_hoa_don')
            ->join('san_pham as sp', 'ct.ma_san_pham', '=', 'sp.ma_san_pham')
            ->where('hd.trang_thai', 'DELIVERED')
            ->select(
                'sp.ten_san_pham',
                DB::raw('SUM(ct.so_luong) as tong_ban'),
                DB::raw('SUM(ct.so_luong * ct.gia_ban_snapshot) as tong_doanh_thu')
            )
            ->groupBy('sp.ma_san_pham', 'sp.ten_san_pham')
            ->orderBy('tong_ban', 'desc')
            ->limit($top)
            ->get();
    }

}