<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChiTietNhap;
use App\Models\BoHoa;
use App\Models\SanPham;
use App\Models\FlashSale;
use App\Models\BaoCaoHangHong;
use App\Models\KiemTraChatLuong;
use App\Models\NhanVien;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SanPhamHetHanController extends Controller
{
    /**
     * Hiển thị trung tâm kiểm tra và ra quyết định xử lý HSD:
     * - Lô hoa tươi (theo chi tiết nhập kho)
     * - Bó hoa thành phẩm (vật lý, có tồn kho, có HSD)
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $todayStr = $today->toDateString();
        // Cảnh báo chính: còn 1-2 ngày (ngày mai và ngày kia)
        $thresholdStr = $today->copy()->addDays(2)->toDateString();

        $filter = $request->input('filter', 'all'); // 'all', 'sap_het', 'hom_nay', 'qua_han'
        $keyword = trim((string) $request->input('keyword', ''));

        // ─────────────────────────────────────────────────────────────────
        // 1. QUERY CÁC LÔ HOA TƯƠI
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
                'ctn.gia_ban',
                'sp.gia_ban_hien_tai',
                'pn.ngay_nhap',
                'ctn.created_at as ngay_tao_lo',
                DB::raw('COALESCE(ctn.ngay_het_han, DATE(DATE_ADD(COALESCE(pn.ngay_nhap, ctn.created_at), INTERVAL 7 DAY))) as hsd'),
                'ncc.ma_nha_cung_cap',
                'ncc.ten_nha_cung_cap',
                'ncc.so_dien_thoai as sdt_ncc'
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

        // Áp dụng bộ lọc cảnh báo
        if ($filter === 'qua_han') {
            $loQuery->having('hsd', '<', $todayStr);
        } elseif ($filter === 'hom_nay') {
            $loQuery->having('hsd', '=', $todayStr);
        } elseif ($filter === 'sap_het') {
            $loQuery->having('hsd', '>', $todayStr)
                    ->having('hsd', '<=', $thresholdStr);
        } else {
            // 'all': Chỉ lấy các mục cần kiểm tra (đã quá hạn, hôm nay hoặc còn 1-2 ngày)
            $loQuery->having('hsd', '<=', $thresholdStr);
        }

        $perPage = in_array((int)$request->get('per_page'), [10, 15, 20, 50, 100]) ? (int)$request->get('per_page') : 10;
        $loHangs = $loQuery->orderBy('hsd', 'asc')
            ->orderBy('ctn.so_luong_con_lai', 'desc')
            ->paginate($perPage, ['*'], 'page_lo')
            ->withQueryString();

        // Lấy lịch sử kiểm tra chất lượng gần nhất cho các lô trên trang hiện tại
        $loIds = $loHangs->pluck('ma_chi_tiet_nhap')->toArray();
        $kiemTraLo = KiemTraChatLuong::with(['nhanVien', 'nguoiDung'])
            ->where('doi_tuong_loai', 'LO_HANG')
            ->whereIn('doi_tuong_id', $loIds)
            ->orderByDesc('thoi_gian_kiem_tra')
            ->get()
            ->groupBy('doi_tuong_id')
            ->map(fn ($group) => $group->first());

        $loHangs->through(function ($item) use ($today, $todayStr, $kiemTraLo) {
            $hsdDate = Carbon::parse($item->hsd)->startOfDay();
            $diff = $today->diffInDays($hsdDate, false);
            $item->diffDays = $diff;
            $item->ngayNhapFmt = $item->ngay_nhap ? Carbon::parse($item->ngay_nhap)->format('d/m/Y') : ($item->ngay_tao_lo ? Carbon::parse($item->ngay_tao_lo)->format('d/m/Y') : '—');
            $item->hsdFmt = $hsdDate->format('d/m/Y');
            $item->giaBanHienTai = (float) ($item->gia_ban_hien_tai > 0 ? $item->gia_ban_hien_tai : ($item->gia_ban > 0 ? $item->gia_ban : $item->gia_nhap * 1.5));
            $item->giaTriTon = (float) $item->giaBanHienTai * (int) $item->so_luong_con_lai;

            // Phân loại cảnh báo
            if ($diff < 0) {
                $item->canhBaoCode = 'DA_QUA_HAN';
                $item->canhBaoText = 'Đã quá hạn ' . abs($diff) . ' ngày';
            } elseif ($diff == 0) {
                $item->canhBaoCode = 'HET_HAN_HOM_NAY';
                $item->canhBaoText = 'Hết hạn hôm nay';
            } elseif ($diff <= 2) {
                $item->canhBaoCode = 'SAP_HET_HAN';
                $item->canhBaoText = 'Sắp hết hạn (' . $diff . ' ngày)';
            } else {
                $item->canhBaoCode = 'AN_TOAN';
                $item->canhBaoText = 'Còn ' . $diff . ' ngày';
            }

            // Kiểm tra chất lượng gần nhất
            $kt = $kiemTraLo->get($item->ma_chi_tiet_nhap);
            $item->kiemTraGanNhat = $kt;

            return $item;
        });

        // ─────────────────────────────────────────────────────────────────
        // 2. QUERY CÁC BÓ HOA THÀNH PHẨM
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
                'bh.gia_von',
                'bh.hinh_anh',
                'bh.created_at as ngay_tao',
                DB::raw('COALESCE(bh.ngay_het_han, DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY))) as hsd')
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
            ->paginate($perPage, ['*'], 'page_bo')
            ->withQueryString();

        // Lấy lịch sử kiểm tra cho bó hoa trên trang hiện tại
        $boIds = $boHoas->pluck('ma_bo_hoa')->toArray();
        $kiemTraBo = KiemTraChatLuong::with(['nhanVien', 'nguoiDung'])
            ->where('doi_tuong_loai', 'BO_HOA')
            ->whereIn('doi_tuong_id', $boIds)
            ->orderByDesc('thoi_gian_kiem_tra')
            ->get()
            ->groupBy('doi_tuong_id')
            ->map(fn ($group) => $group->first());

        $boHoas->through(function ($item) use ($today, $todayStr, $kiemTraBo) {
            $hsdDate = Carbon::parse($item->hsd)->startOfDay();
            $diff = $today->diffInDays($hsdDate, false);
            $item->diffDays = $diff;
            $item->ngayTaoFmt = $item->ngay_tao ? Carbon::parse($item->ngay_tao)->format('d/m/Y') : '—';
            $item->hsdFmt = $hsdDate->format('d/m/Y');
            $item->giaTriTon = (float) $item->gia_ban * (int) $item->so_luong;

            if ($diff < 0) {
                $item->canhBaoCode = 'DA_QUA_HAN';
                $item->canhBaoText = 'Đã quá hạn ' . abs($diff) . ' ngày';
            } elseif ($diff == 0) {
                $item->canhBaoCode = 'HET_HAN_HOM_NAY';
                $item->canhBaoText = 'Hết hạn hôm nay';
            } elseif ($diff <= 2) {
                $item->canhBaoCode = 'SAP_HET_HAN';
                $item->canhBaoText = 'Sắp hết hạn (' . $diff . ' ngày)';
            } else {
                $item->canhBaoCode = 'AN_TOAN';
                $item->canhBaoText = 'Còn ' . $diff . ' ngày';
            }

            // Fallback ảnh từ mẫu bó hoa
            if (empty($item->hinh_anh) && !empty($item->ma_mau_bo_hoa)) {
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                    if (file_exists(public_path('img/mau-bo-hoa/' . $item->ma_mau_bo_hoa . '.' . $ext))) {
                        $item->hinh_anh = 'img/mau-bo-hoa/' . $item->ma_mau_bo_hoa . '.' . $ext;
                        break;
                    }
                }
            }

            $item->kiemTraGanNhat = $kiemTraBo->get($item->ma_bo_hoa);

            return $item;
        });

        // ─────────────────────────────────────────────────────────────────
        // 3. THỐNG KÊ TOÀN CỤC (Theo chuẩn 1-2 ngày)
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
                DB::raw('COALESCE(ctn.ngay_het_han, DATE(DATE_ADD(COALESCE(pn.ngay_nhap, ctn.created_at), INTERVAL 7 DAY))) as hsd'),
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
                DB::raw('COALESCE(bh.ngay_het_han, DATE(DATE_ADD(bh.created_at, INTERVAL 5 DAY))) as hsd'),
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

        $nhanViens = NhanVien::orderBy('ten_nhan_vien')->get(['ma_nhan_vien', 'ten_nhan_vien']);
        $isAdmin = auth()->check() && auth()->user()->vai_tro === 'ADMIN';

        return view('admin.SanPhamHetHan.index', compact(
            'loHangs',
            'boHoas',
            'thongKe',
            'filter',
            'keyword',
            'nhanViens',
            'isAdmin',
            'perPage'
        ));
    }

    /**
     * QUYẾT ĐỊNH A: BÁN TIẾP GIÁ THƯỜNG
     * Người vận hành kiểm tra chất lượng đạt, cho phép tiếp tục bán giá thường.
     * Bắt buộc lưu audit log vào kiem_tra_chat_luong.
     */
    public function banTiep(Request $request)
    {
        $data = $request->validate([
            'loai_doi_tuong' => ['required', 'in:LO_HANG,BO_HOA'],
            'id'             => ['required', 'integer'],
            'ghi_chu'        => ['required', 'string', 'min:5', 'max:1000'],
            'ma_nhan_vien'   => ['nullable', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
        ], [
            'ghi_chu.required' => 'Bắt buộc nhập ghi chú/kết quả kiểm tra thực tế (ví dụ: hoa vẫn tươi, cánh cứng...).',
            'ghi_chu.min'      => 'Ghi chú kiểm tra phải có ít nhất 5 ký tự.',
        ]);

        $nhanVienId = $data['ma_nhan_vien'] 
            ?? (Auth::user()?->nhanVien?->ma_nhan_vien ?? NhanVien::first()?->ma_nhan_vien);

        $maSanPham = null;
        $maChiTietNhap = null;
        $maBoHoa = null;
        $hsd = null;
        $tenDoiTuong = '';

        if ($data['loai_doi_tuong'] === 'LO_HANG') {
            $lo = ChiTietNhap::with('sanPham')->findOrFail($data['id']);
            $maSanPham = $lo->ma_san_pham;
            $maChiTietNhap = $lo->ma_chi_tiet_nhap;
            $hsd = $lo->ngay_het_han;
            $tenDoiTuong = 'Lô #' . $lo->ma_chi_tiet_nhap . ' (' . ($lo->sanPham?->ten_san_pham ?? 'SP') . ')';
        } else {
            $bo = BoHoa::findOrFail($data['id']);
            $maBoHoa = $bo->ma_bo_hoa;
            $hsd = $bo->ngay_het_han;
            $tenDoiTuong = 'Bó hoa thành phẩm #' . $bo->ma_bo_hoa . ' (' . $bo->ten_bo_hoa . ')';
        }

        KiemTraChatLuong::create([
            'doi_tuong_loai'     => $data['loai_doi_tuong'],
            'doi_tuong_id'       => $data['id'],
            'ma_san_pham'        => $maSanPham,
            'ma_chi_tiet_nhap'   => $maChiTietNhap,
            'ma_bo_hoa'          => $maBoHoa,
            'ket_qua'            => 'CON_BAN_DUOC',
            'ghi_chu'            => trim($data['ghi_chu']),
            'ma_nhan_vien'       => $nhanVienId,
            'ma_nguoi_dung'      => Auth::id(),
            'hsd_luc_kiem_tra'   => $hsd,
            'thoi_gian_kiem_tra' => now(),
        ]);

        return redirect()->back()->with('success', "Đã xác nhận đạt chất lượng cho {$tenDoiTuong}. Cho phép tiếp tục bán giá thường.");
    }

    /**
     * QUYẾT ĐỊNH B: ĐƯA VÀO FLASH SALE
     * Hỗ trợ nguồn CAN_HAN, HET_HAN, HANG_CO_VAN_DE.
     * Mức giảm cố định 30%, 50%, 75% tính trên GIÁ BÁN.
     */
    public function taoFlashSale(Request $request)
    {
        $data = $request->validate([
            'loai_doi_tuong' => ['required', 'in:LO_HANG,BO_HOA'],
            'id'             => ['required', 'integer'],
            'so_luong'       => ['required', 'integer', 'min:1'],
            'ti_le_giam'     => ['required', 'in:30,50,75'],
            'bat_dau'        => ['required', 'date'],
            'ket_thuc'       => ['required', 'date', 'after:bat_dau'],
            'nguon'          => ['required', 'in:CAN_HAN,HET_HAN,HANG_CO_VAN_DE'],
            'ghi_chu'        => ['nullable', 'string', 'max:1000'],
            'ma_nhan_vien'   => ['nullable', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
        ], [
            'ti_le_giam.required' => 'Vui lòng chọn mức giảm giá 30%, 50% hoặc 75%.',
            'ti_le_giam.in'       => 'Mức giảm giá chỉ cho phép một trong 3 mức: 30%, 50% hoặc 75%.',
            'so_luong.min'        => 'Số lượng đưa Flash Sale phải từ 1 trở lên.',
            'ket_thuc.after'      => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ]);

        // Nếu là HANG_CO_VAN_DE bắt buộc phải có mô tả
        if ($data['nguon'] === 'HANG_CO_VAN_DE' && blank($data['ghi_chu'] ?? null)) {
            throw ValidationException::withMessages([
                'ghi_chu' => ['Hàng có vấn đề bắt buộc phải nhập mô tả tình trạng sản phẩm.']
            ]);
        }

        $flashSale = DB::transaction(function () use ($data) {
            $soLuong = (int) $data['so_luong'];
            $tiLeGiam = (int) $data['ti_le_giam'];
            $nhanVienId = $data['ma_nhan_vien'] 
                ?? (Auth::user()?->nhanVien?->ma_nhan_vien ?? NhanVien::first()?->ma_nhan_vien);

            if ($data['loai_doi_tuong'] === 'LO_HANG') {
                $lo = ChiTietNhap::whereKey($data['id'])->lockForUpdate()->firstOrFail();
                $sp = SanPham::whereKey($lo->ma_san_pham)->lockForUpdate()->firstOrFail();

                // Validate đối tượng: Không cho phép nguyên vật liệu nội bộ
                $loaiCam = ['GIAY_GOI', 'RUY_BANG', 'MIENG_XOP', 'PHU_KIEN', 'VAT_TU'];
                if (in_array($sp->loai_san_pham, $loaiCam, true)) {
                    throw ValidationException::withMessages([
                        'loai_doi_tuong' => ['Nguyên vật liệu nội bộ không được đưa vào Flash Sale.']
                    ]);
                }

                if ($soLuong > (int) $lo->so_luong_con_lai) {
                    throw ValidationException::withMessages([
                        'so_luong' => ["Số lượng vượt quá tồn kho của lô (#{$lo->so_luong_con_lai})."]
                    ]);
                }

                // Giá gốc snapshot = giá bán hiện tại
                $giaGoc = (float) ($sp->gia_ban_hien_tai > 0 ? $sp->gia_ban_hien_tai : ($lo->gia_ban > 0 ? $lo->gia_ban : $lo->gia_nhap * 1.5));
                $giaFlashSale = round($giaGoc * (1 - $tiLeGiam / 100), 2);
                $soTienGiam = round($giaGoc - $giaFlashSale, 2);

                // Flash Sale là chương trình giá/quota, KHÔNG trừ tồn kho vật lý khi tạo.
                // Tồn kho chỉ trừ khi đơn hàng được confirm (CONSUMED) hoặc khi báo hỏng.

                $fs = FlashSale::create([
                    'ma_san_pham'           => $sp->ma_san_pham,
                    'ma_chi_tiet_nhap'      => $lo->ma_chi_tiet_nhap,
                    'ma_bo_hoa'             => null,
                    'ma_chi_tiet_hang_hoan' => null,
                    'gia_goc'               => $giaGoc,
                    'gia_flash_sale'        => $giaFlashSale,
                    'so_tien_giam'          => $soTienGiam,
                    'ti_le_giam'            => $tiLeGiam,
                    'so_luong'              => $soLuong,
                    'so_luong_da_ban'       => 0,
                    'bat_dau'               => Carbon::parse($data['bat_dau']),
                    'ket_thuc'              => Carbon::parse($data['ket_thuc']),
                    'trang_thai'            => 'DANG_BAN',
                    'nguon'                 => $data['nguon'],
                    'nguoi_tao'             => Auth::id(),
                ]);

                KiemTraChatLuong::create([
                    'doi_tuong_loai'     => 'LO_HANG',
                    'doi_tuong_id'       => $lo->ma_chi_tiet_nhap,
                    'ma_san_pham'        => $sp->ma_san_pham,
                    'ma_chi_tiet_nhap'   => $lo->ma_chi_tiet_nhap,
                    'ma_flash_sale'      => $fs->ma_flash_sale,
                    'ket_qua'            => 'FLASH_SALE',
                    'ghi_chu'            => trim($data['ghi_chu'] ?? "Tạo Flash Sale {$soLuong} sp ({$tiLeGiam}%) từ nguồn {$data['nguon']}."),
                    'ma_nhan_vien'       => $nhanVienId,
                    'ma_nguoi_dung'      => Auth::id(),
                    'hsd_luc_kiem_tra'   => $lo->ngay_het_han,
                    'thoi_gian_kiem_tra' => now(),
                ]);

                return $fs;
            } else {
                $bo = BoHoa::whereKey($data['id'])->lockForUpdate()->firstOrFail();

                if ($soLuong > (int) $bo->so_luong) {
                    throw ValidationException::withMessages([
                        'so_luong' => ["Số lượng vượt quá tồn kho của bó hoa (#{$bo->so_luong})."]
                    ]);
                }

                $giaGoc = (float) $bo->gia_ban;
                $giaFlashSale = round($giaGoc * (1 - $tiLeGiam / 100), 2);
                $soTienGiam = round($giaGoc - $giaFlashSale, 2);

                // Flash Sale là chương trình giá/quota, KHÔNG trừ tồn kho và KHÔNG chuyển sang DRAFT.
                // Bó hoa vẫn giữ nguyên on-hand trong kho.

                $fs = FlashSale::create([
                    'ma_san_pham'           => null,
                    'ma_chi_tiet_nhap'      => null,
                    'ma_bo_hoa'             => $bo->ma_bo_hoa,
                    'ma_chi_tiet_hang_hoan' => null,
                    'gia_goc'               => $giaGoc,
                    'gia_flash_sale'        => $giaFlashSale,
                    'so_tien_giam'          => $soTienGiam,
                    'ti_le_giam'            => $tiLeGiam,
                    'so_luong'              => $soLuong,
                    'so_luong_da_ban'       => 0,
                    'bat_dau'               => Carbon::parse($data['bat_dau']),
                    'ket_thuc'              => Carbon::parse($data['ket_thuc']),
                    'trang_thai'            => 'DANG_BAN',
                    'nguon'                 => $data['nguon'],
                    'nguoi_tao'             => Auth::id(),
                ]);

                KiemTraChatLuong::create([
                    'doi_tuong_loai'     => 'BO_HOA',
                    'doi_tuong_id'       => $bo->ma_bo_hoa,
                    'ma_bo_hoa'          => $bo->ma_bo_hoa,
                    'ma_flash_sale'      => $fs->ma_flash_sale,
                    'ket_qua'            => 'FLASH_SALE',
                    'ghi_chu'            => trim($data['ghi_chu'] ?? "Chuyển {$soLuong} bó hoa vào Flash Sale ({$tiLeGiam}%) từ nguồn {$data['nguon']}."),
                    'ma_nhan_vien'       => $nhanVienId,
                    'ma_nguoi_dung'      => Auth::id(),
                    'hsd_luc_kiem_tra'   => $bo->ngay_het_han,
                    'thoi_gian_kiem_tra' => now(),
                ]);

                return $fs;
            }
        });

        return redirect()->back()
            ->with('success', "Đã đưa vào Flash Sale thành công (Giảm {$data['ti_le_giam']}%).")
            ->with('flash_sale_link', route('admin.hoan-hang.flash-sale.index'));
    }

    /**
     * QUYẾT ĐỊNH C: BÁO HỎNG
     * Ghi nhận tập trung vào bao_cao_hang_hong và giảm đúng tồn kho vật lý.
     */
    public function baoHong(Request $request)
    {
        $data = $request->validate([
            'loai_doi_tuong' => ['required', 'in:LO_HANG,BO_HOA'],
            'id'             => ['required', 'integer'],
            'so_luong_hong'  => ['required', 'integer', 'min:1'],
            'ly_do'          => ['required', 'string', 'max:255'],
            'ghi_chu'        => ['nullable', 'string', 'max:1000'],
            'ma_nhan_vien'   => ['nullable', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
        ], [
            'so_luong_hong.min' => 'Số lượng hỏng phải từ 1 trở lên.',
            'ly_do.required'    => 'Vui lòng nhập lý do báo hỏng.',
        ]);

        DB::transaction(function () use ($data) {
            $slHong = (int) $data['so_luong_hong'];
            $nhanVienId = $data['ma_nhan_vien'] 
                ?? (Auth::user()?->nhanVien?->ma_nhan_vien ?? NhanVien::first()?->ma_nhan_vien);

            if ($data['loai_doi_tuong'] === 'LO_HANG') {
                $lo = ChiTietNhap::whereKey($data['id'])->lockForUpdate()->firstOrFail();
                $sp = SanPham::whereKey($lo->ma_san_pham)->lockForUpdate()->firstOrFail();

                if ($slHong > (int) $lo->so_luong_con_lai) {
                    throw ValidationException::withMessages([
                        'so_luong_hong' => ["Số lượng hỏng vượt quá tồn kho của lô (#{$lo->so_luong_con_lai})."]
                    ]);
                }

                $thietHai = round($slHong * (float) $lo->gia_nhap, 2);

                $lo->decrement('so_luong_con_lai', $slHong);
                $sp->decrement('so_luong', $slHong);

                if ($sp->fresh()->so_luong <= 0) {
                    $sp->update(['trang_thai' => 'NGUNG_BAN']);
                }

                BaoCaoHangHong::create([
                    'ma_san_pham'       => $sp->ma_san_pham,
                    'ma_chi_tiet_nhap'  => $lo->ma_chi_tiet_nhap,
                    'ma_bo_hoa'         => null,
                    'ma_nhan_vien'      => $nhanVienId,
                    'so_luong_hong'     => $slHong,
                    'gia_tri_thiet_hai' => $thietHai,
                    'nguon_hong'        => 'CAN_HAN_HET_HAN',
                    'ly_do'             => trim($data['ly_do']),
                    'ghi_chu'           => $data['ghi_chu'] ?? 'Báo hỏng từ trang cận hạn/hết hạn.',
                    'thoi_gian_bao_cao' => now(),
                ]);

                KiemTraChatLuong::create([
                    'doi_tuong_loai'     => 'LO_HANG',
                    'doi_tuong_id'       => $lo->ma_chi_tiet_nhap,
                    'ma_san_pham'        => $sp->ma_san_pham,
                    'ma_chi_tiet_nhap'   => $lo->ma_chi_tiet_nhap,
                    'ket_qua'            => 'HONG',
                    'ghi_chu'            => 'Báo hỏng ' . $slHong . ' sp. Lý do: ' . $data['ly_do'],
                    'ma_nhan_vien'       => $nhanVienId,
                    'ma_nguoi_dung'      => Auth::id(),
                    'hsd_luc_kiem_tra'   => $lo->ngay_het_han,
                    'thoi_gian_kiem_tra' => now(),
                ]);
            } else {
                $bo = BoHoa::whereKey($data['id'])->lockForUpdate()->firstOrFail();

                if ($slHong > (int) $bo->so_luong) {
                    throw ValidationException::withMessages([
                        'so_luong_hong' => ["Số lượng hỏng vượt quá tồn kho của bó (#{$bo->so_luong})."]
                    ]);
                }

                $thietHai = round($slHong * (float) ($bo->gia_von ?? $bo->gia_ban), 2);

                $bo->decrement('so_luong', $slHong);
                if ($bo->fresh()->so_luong <= 0) {
                    $bo->update(['trang_thai' => 'CANCELLED']);
                }

                BaoCaoHangHong::create([
                    'ma_san_pham'       => null,
                    'ma_chi_tiet_nhap'  => null,
                    'ma_bo_hoa'         => $bo->ma_bo_hoa,
                    'ma_nhan_vien'      => $nhanVienId,
                    'so_luong_hong'     => $slHong,
                    'gia_tri_thiet_hai' => $thietHai,
                    'nguon_hong'        => 'CAN_HAN_HET_HAN',
                    'ly_do'             => trim($data['ly_do']),
                    'ghi_chu'           => $data['ghi_chu'] ?? 'Báo hỏng bó hoa thành phẩm từ trang cận hạn/hết hạn.',
                    'thoi_gian_bao_cao' => now(),
                ]);

                KiemTraChatLuong::create([
                    'doi_tuong_loai'     => 'BO_HOA',
                    'doi_tuong_id'       => $bo->ma_bo_hoa,
                    'ma_bo_hoa'          => $bo->ma_bo_hoa,
                    'ket_qua'            => 'HONG',
                    'ghi_chu'            => 'Báo hỏng ' . $slHong . ' bó. Lý do: ' . $data['ly_do'],
                    'ma_nhan_vien'       => $nhanVienId,
                    'ma_nguoi_dung'      => Auth::id(),
                    'hsd_luc_kiem_tra'   => $bo->ngay_het_han,
                    'thoi_gian_kiem_tra' => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', "Đã ghi nhận báo hỏng thành công. Dữ liệu đã chuyển vào Báo cáo hàng hỏng.");
    }
}