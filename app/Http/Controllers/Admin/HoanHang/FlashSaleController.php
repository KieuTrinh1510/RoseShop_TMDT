<?php

namespace App\Http\Controllers\Admin\HoanHang;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoHangHong;
use App\Models\BoHoa;
use App\Models\ChiTietHangHoan;
use App\Models\ChiTietNhap;
use App\Models\FlashSale;
use App\Models\HangHoan;
use App\Models\InventoryReservation;
use App\Models\KiemTraChatLuong;
use App\Models\NhanVien;
use App\Models\PhatShipper;
use App\Models\SanPham;
use App\Services\KiemTraHangHoanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class FlashSaleController extends Controller
{
    private const TRANG_THAI = ['NHAP', 'DANG_BAN', 'HET_HANG', 'KET_THUC', 'AN'];
    private const TOLERANCE_PHUT = 1;

    // ─────────────────────────────────────────────────────────────
    // Danh sách Flash Sale đa nguồn + các dòng hàng hoàn có thể tạo
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $request->validate([
            'trang_thai' => ['nullable', 'in:' . implode(',', self::TRANG_THAI)],
            'nguon'      => ['nullable', 'in:CAN_HAN,HET_HAN,HANG_HOAN,HANG_CO_VAN_DE'],
            'filter'     => ['nullable', 'string'],
            'keyword'    => ['nullable', 'string'],
        ]);

        $this->dongBoTrangThai();

        $filter = $request->input('filter', 'all');
        $keyword = trim((string) $request->input('keyword', ''));

        $query = FlashSale::with([
            'sanPham',
            'chiTietNhap.phieuNhap',
            'boHoa',
            'chiTietHangHoan',
            'nguoiTao',
            'kiemTraChatLuongs.nhanVien'
        ]);

        if ($request->filled('nguon')) {
            $query->where('nguon', $request->input('nguon'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        if ($filter && $filter !== 'all') {
            switch ($filter) {
                case 'CAN_HAN':
                case 'HET_HAN':
                case 'HANG_HOAN':
                case 'HANG_CO_VAN_DE':
                    $query->where('nguon', $filter);
                    break;
                case 'dang_ban':
                    $query->where('trang_thai', 'DANG_BAN')->whereColumn('so_luong_da_ban', '<', 'so_luong');
                    break;
                case 'het_thoi_gian':
                    $query->where(function ($q) {
                        $q->where('trang_thai', 'KET_THUC')->orWhere('ket_thuc', '<', now());
                    });
                    break;
            }
        }

        if ($keyword !== '') {
            $kw = $keyword;
            $query->where(function ($q) use ($kw) {
                if (is_numeric($kw)) {
                    $q->orWhere('ma_flash_sale', (int) $kw)
                      ->orWhere('ma_san_pham', (int) $kw)
                      ->orWhere('ma_bo_hoa', (int) $kw);
                }
                $q->orWhereHas('sanPham', fn ($sq) => $sq->where('ten_san_pham', 'like', "%{$kw}%"))
                  ->orWhereHas('boHoa', fn ($bq) => $bq->where('ten_bo_hoa', 'like', "%{$kw}%"));
            });
        }

        $perPage = in_array((int)$request->get('per_page'), [10, 15, 20, 50, 100]) ? (int)$request->get('per_page') : 10;
        $flashSales = $query->orderByDesc('ma_flash_sale')->paginate($perPage)->withQueryString();

        // Thống kê nhanh
        $thongKe = [
            'tat_ca'        => FlashSale::count(),
            'dang_ban'      => FlashSale::where('trang_thai', 'DANG_BAN')->whereColumn('so_luong_da_ban', '<', 'so_luong')->count(),
            'het_thoi_gian' => FlashSale::where('trang_thai', 'KET_THUC')->orWhere('ket_thuc', '<', now())->count(),
            'het_hang'      => FlashSale::where('trang_thai', 'HET_HANG')->orWhereColumn('so_luong_da_ban', '>=', 'so_luong')->count(),
            'can_han'       => FlashSale::where('nguon', 'CAN_HAN')->count(),
            'het_han'       => FlashSale::where('nguon', 'HET_HAN')->count(),
            'hang_hoan'     => FlashSale::where('nguon', 'HANG_HOAN')->count(),
            'co_van_de'     => FlashSale::where('nguon', 'HANG_CO_VAN_DE')->count(),
        ];

        // Hàng hoàn có thể mở Flash Sale
        $coTheTao = ChiTietHangHoan::with(['sanPham', 'boHoa'])
            ->where('ket_qua', 'FLASH_SALE')
            ->where('so_luong_con_lai_flash_sale', '>', 0)
            ->whereHas('hangHoan', fn ($q) => $q->where('trang_thai', 'DA_KIEM_TRA'))
            ->orderBy('ma_chi_tiet_hang_hoan')
            ->get()
            ->map(function ($ct) {
                $giaGoc = (float) $ct->gia_ban_snapshot;
                return [
                    'ma_chi_tiet_hang_hoan'       => $ct->ma_chi_tiet_hang_hoan,
                    'la_bo_hoa'                   => !empty($ct->ma_bo_hoa),
                    'ten'                         => !empty($ct->ma_bo_hoa)
                        ? ($ct->boHoa?->ten_bo_hoa ?? 'Bó hoa (đã xóa)')
                        : ($ct->sanPham?->ten_san_pham ?? 'Sản phẩm (đã xóa)'),
                    'so_luong_con_ban_duoc'       => (int) $ct->so_luong_con_ban_duoc,
                    'so_luong_con_lai_flash_sale' => (int) $ct->so_luong_con_lai_flash_sale,
                    'gia_goc'                     => $giaGoc,
                    'gia_30'                      => round($giaGoc * 0.70, 2),
                    'gia_50'                      => round($giaGoc * 0.50, 2),
                    'gia_75'                      => round($giaGoc * 0.25, 2),
                ];
            });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data'    => ['flash_sales' => $flashSales, 'co_the_tao' => $coTheTao, 'thong_ke' => $thongKe],
            ]);
        }

        return view('admin.FlashSale', [
            'flashSales' => $flashSales,
            'coTheTao'   => $coTheTao,
            'thongKe'    => $thongKe,
            'filter'     => $filter,
            'keyword'    => $keyword,
            'nhanViens'  => NhanVien::orderBy('ten_nhan_vien')->get(['ma_nhan_vien', 'ten_nhan_vien']),
            'perPage'    => $perPage,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Tạo Flash Sale từ chi tiết hàng hoàn
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request, $maChiTietHangHoan)
    {
        $data = $request->validate([
            'so_luong'    => ['required', 'integer', 'min:1'],
            'ti_le_giam'  => ['required', 'in:30,50,75'],
            'bat_dau'     => ['required', 'date'],
            'ket_thuc'    => ['required', 'date'],
            'trang_thai'  => ['nullable', 'in:NHAP,DANG_BAN'],
        ], [
            'so_luong.required'   => 'Vui lòng nhập số lượng Flash Sale.',
            'so_luong.min'        => 'Số lượng Flash Sale phải lớn hơn 0.',
            'ti_le_giam.required' => 'Vui lòng chọn mức giảm giá 30%, 50% hoặc 75%.',
            'ti_le_giam.in'       => 'Mức giảm giá chỉ cho phép một trong 3 mức: 30%, 50% hoặc 75%.',
            'bat_dau.required'    => 'Vui lòng chọn thời gian bắt đầu.',
            'ket_thuc.required'   => 'Vui lòng chọn thời gian kết thúc.',
        ]);

        $this->validateThoiGianTaoMoi($data['bat_dau'], $data['ket_thuc']);

        try {
            $flashSale = DB::transaction(fn () => $this->taoFlashSale((int) $maChiTietHangHoan, $data), 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return $this->phanHoiLoi('Không tìm thấy chi tiết hàng hoàn.', 404);
        } catch (Throwable $e) {
            Log::error('Admin\\FlashSale@store thất bại', [
                'ma_chi_tiet_hang_hoan' => $maChiTietHangHoan,
                'error'                 => $e->getMessage(),
            ]);
            return $this->phanHoiLoi('Không thể tạo Flash Sale. Vui lòng thử lại.', 500);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã tạo Flash Sale thành công.',
                'data'    => $flashSale,
            ]);
        }

        return redirect()
            ->route('admin.hoan-hang.flash-sale.index')
            ->with('success', 'Đã tạo Flash Sale thành công từ hàng hoàn.');
    }

    // ─────────────────────────────────────────────────────────────
    // GIA HẠN FLASH SALE (Mục XIV, XV)
    // ─────────────────────────────────────────────────────────────
    public function extend(Request $request, $id)
    {
        $data = $request->validate([
            'ket_thuc_moi' => ['required', 'date', 'after:now'],
            'ghi_chu'      => ['required', 'string', 'min:5', 'max:1000'],
            'ma_nhan_vien' => ['nullable', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
        ], [
            'ket_thuc_moi.required' => 'Vui lòng chọn thời gian kết thúc mới.',
            'ket_thuc_moi.after'    => 'Thời gian kết thúc mới phải sau thời điểm hiện tại.',
            'ghi_chu.required'      => 'Bắt buộc nhập ghi chú/kết quả kiểm tra chất lượng khi gia hạn.',
            'ghi_chu.min'           => 'Ghi chú kiểm tra phải có ít nhất 5 ký tự.',
        ]);

        try {
            DB::transaction(function () use ($id, $data) {
                $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();

                $thoiGianCu = $fs->ket_thuc;
                $thoiGianMoi = Carbon::parse($data['ket_thuc_moi']);

                $nhanVienId = $data['ma_nhan_vien']
                    ?? (Auth::user()?->nhanVien?->ma_nhan_vien ?? NhanVien::first()?->ma_nhan_vien);

                // Cập nhật Flash Sale
                $fs->ket_thuc = $thoiGianMoi;
                if ($fs->trang_thai === 'KET_THUC') {
                    $fs->trang_thai = ($fs->so_luong <= $fs->so_luong_da_ban) ? 'HET_HANG' : 'DANG_BAN';
                }
                $fs->save();

                // Lưu audit log kiểm tra chất lượng khi gia hạn
                KiemTraChatLuong::create([
                    'doi_tuong_loai'     => 'FLASH_SALE',
                    'doi_tuong_id'       => $fs->ma_flash_sale,
                    'ma_san_pham'        => $fs->ma_san_pham,
                    'ma_chi_tiet_nhap'   => $fs->ma_chi_tiet_nhap,
                    'ma_bo_hoa'          => $fs->ma_bo_hoa,
                    'ma_flash_sale'      => $fs->ma_flash_sale,
                    'ket_qua'            => 'CON_BAN_DUOC',
                    'ghi_chu'            => trim($data['ghi_chu']),
                    'ma_nhan_vien'       => $nhanVienId,
                    'ma_nguoi_dung'      => Auth::id(),
                    'thoi_gian_kiem_tra' => now(),
                    'thoi_gian_cu'       => $thoiGianCu,
                    'thoi_gian_moi'      => $thoiGianMoi,
                ]);
            }, 3);
        } catch (Throwable $e) {
            Log::error('FlashSale@extend thất bại', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->phanHoiLoi('Không thể gia hạn Flash Sale: ' . $e->getMessage(), 500);
        }

        return redirect()->back()->with('success', 'Đã gia hạn Flash Sale thành công và lưu lịch sử kiểm tra chất lượng.');
    }

    // ─────────────────────────────────────────────────────────────
    // ─────────────────────────────────────────────────────────────
    // KẾT THÚC / DỪNG FLASH SALE (Không hoàn tồn vật lý vì chưa từng trừ)
    // ─────────────────────────────────────────────────────────────
    public function returnStock(Request $request, $id)
    {
        $request->validate([
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $msg = DB::transaction(function () use ($id, $request) {
                $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();

                // Flash Sale không phải kho riêng nên không increment tồn kho vật lý.
                // Khi kết thúc chương trình, chỉ cần chuyển trạng thái KET_THUC.
                $fs->trang_thai = 'KET_THUC';
                if (!$fs->ket_thuc || $fs->ket_thuc->gt(now())) {
                    $fs->ket_thuc = now();
                }
                $fs->save();

                return 'Đã kết thúc chương trình Flash Sale. Sản phẩm vẫn được giữ trong kho bán thường.';
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('FlashSale@returnStock thất bại', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->phanHoiLoi('Không thể kết thúc Flash Sale.', 500);
        }

        return redirect()->back()->with('success', $msg);
    }

    // ─────────────────────────────────────────────────────────────
    // Báo hỏng từ Flash Sale (Mục IX): Trừ tồn vật lý và lưu báo cáo
    // ─────────────────────────────────────────────────────────────
    public function reportDamage(Request $request, $id)
    {
        $data = $request->validate([
            'so_luong_hong' => ['required', 'integer', 'min:1'],
            'ly_do'         => ['required', 'string', 'max:255'],
            'ghi_chu'       => ['nullable', 'string', 'max:500'],
            'ma_nhan_vien'  => ['nullable', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
        ]);

        try {
            DB::transaction(function () use ($id, $data) {
                $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();
                $nhanVienId = $data['ma_nhan_vien'] ?? (Auth::user()?->nhanVien?->ma_nhan_vien ?? Auth::id());

                $conLai = max(0, (int) $fs->so_luong - (int) $fs->so_luong_da_ban);
                if ((int) $data['so_luong_hong'] > $conLai) {
                    $this->nem('so_luong_hong', "Chỉ còn {$conLai} sản phẩm trong hạn mức Flash Sale.");
                }

                $soLuongHong = (int) $data['so_luong_hong'];

                // 1. Giảm tồn vật lý thực tế khi báo hỏng
                if ($fs->ma_san_pham) {
                    $sp = SanPham::whereKey($fs->ma_san_pham)->lockForUpdate()->first();
                    if ($sp) {
                        $sp->decrement('so_luong', $soLuongHong);
                    }

                    $maLo = $fs->ma_chi_tiet_nhap;
                    if (!$maLo && $fs->ma_chi_tiet_hang_hoan) {
                        $ct = ChiTietHangHoan::find($fs->ma_chi_tiet_hang_hoan);
                        $maLo = $ct?->chiTietHoaDon?->ma_chi_tiet_nhap;
                    }

                    if ($maLo) {
                        $lo = ChiTietNhap::whereKey($maLo)->lockForUpdate()->first();
                        if ($lo) {
                            $lo->decrement('so_luong_con_lai', $soLuongHong);
                        }
                    }
                }

                if ($fs->ma_bo_hoa) {
                    $bo = BoHoa::whereKey($fs->ma_bo_hoa)->lockForUpdate()->first();
                    if ($bo) {
                        $bo->so_luong = max(0, (int) $bo->so_luong - $soLuongHong);
                        $bo->save();
                    }
                }

                // 2. Tính giá trị thiệt hại theo giá flash sale
                $thietHai = round($soLuongHong * (float) $fs->gia_flash_sale, 2);

                // 3. Ghi nhận tập trung vào bao_cao_hang_hong
                BaoCaoHangHong::create([
                    'ma_san_pham'       => $fs->ma_san_pham,
                    'ma_chi_tiet_nhap'  => $fs->ma_chi_tiet_nhap,
                    'ma_bo_hoa'         => $fs->ma_bo_hoa,
                    'ma_flash_sale'     => $fs->ma_flash_sale,
                    'so_luong_hong'     => $soLuongHong,
                    'gia_tri_thiet_hai' => $thietHai,
                    'nguon_hong'        => 'FLASH_SALE',
                    'ly_do'             => trim($data['ly_do']),
                    'ghi_chu'           => $data['ghi_chu'] ?? "Báo hỏng từ chương trình Flash Sale #{$fs->ma_flash_sale}.",
                    'ma_nhan_vien'      => (int) $nhanVienId,
                    'thoi_gian_bao_cao' => now(),
                ]);

                // Nếu có liên kết với chi tiết hàng hoàn cũ
                if ($fs->ma_chi_tiet_hang_hoan) {
                    $ct = ChiTietHangHoan::whereKey($fs->ma_chi_tiet_hang_hoan)->lockForUpdate()->first();
                    if ($ct) {
                        $ct->increment('so_luong_hong', $soLuongHong);
                    }
                }

                // 4. Giảm quota Flash Sale
                $fs->so_luong -= $soLuongHong;
                $fs->trang_thai = $fs->so_luong <= $fs->so_luong_da_ban ? 'HET_HANG' : $fs->trang_thai;
                $fs->save();
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Admin\\FlashSale@reportDamage thất bại', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->phanHoiLoi('Không thể báo hỏng hàng Flash Sale.', 500);
        }

        return redirect()->back()->with('success', 'Đã ghi nhận báo hỏng và chuyển vào Báo cáo hàng hỏng.');
    }

    // ─────────────────────────────────────────────────────────────
    // Cập nhật giá, số lượng, thời gian
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'ti_le_giam' => ['nullable', 'in:30,50,75'],
            'so_luong'   => ['nullable', 'integer', 'min:1'],
            'bat_dau'    => ['nullable', 'date'],
            'ket_thuc'   => ['nullable', 'date'],
            'trang_thai' => ['nullable', 'in:NHAP,DANG_BAN,AN'],
        ]);

        $this->validateThoiGianCapNhat($data);

        try {
            $flashSale = DB::transaction(fn () => $this->capNhatFlashSale((int) $id, $data), 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return $this->phanHoiLoi('Không tìm thấy Flash Sale.', 404);
        } catch (Throwable $e) {
            Log::error('Admin\\FlashSale@update thất bại', ['ma_flash_sale' => $id, 'error' => $e->getMessage()]);
            return $this->phanHoiLoi('Không thể cập nhật Flash Sale. Vui lòng thử lại.', 500);
        }

        return $this->phanHoiThanhCong($request, 'Đã cập nhật Flash Sale.', $flashSale);
    }

    // ─────────────────────────────────────────────────────────────
    // Kết thúc Flash Sale (Chuyển KET_THUC, KHÔNG tự báo hỏng/hủy)
    // ─────────────────────────────────────────────────────────────
    public function close(Request $request, $id)
    {
        try {
            $flashSale = DB::transaction(function () use ($id) {
                $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($fs->trang_thai === 'KET_THUC') {
                    $this->nem('flash_sale', 'Flash Sale này đã kết thúc trước đó.');
                }

                $fs->trang_thai = 'KET_THUC';
                if (!$fs->ket_thuc || $fs->ket_thuc->gt(now())) {
                    $fs->ket_thuc = now();
                }
                $fs->save();

                return $fs;
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Admin\\FlashSale@close thất bại', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->phanHoiLoi('Không thể kết thúc Flash Sale.', 500);
        }

        return $this->phanHoiThanhCong($request, 'Đã kết thúc chương trình Flash Sale.', $flashSale);
    }

    // ─────────────────────────────────────────────────────────────
    // Logic tạo Flash Sale từ hàng hoàn
    // ─────────────────────────────────────────────────────────────
    private function taoFlashSale(int $maChiTietHangHoan, array $data): FlashSale
    {
        $ct = ChiTietHangHoan::whereKey($maChiTietHangHoan)->lockForUpdate()->firstOrFail();

        if ($ct->ket_qua !== 'FLASH_SALE') {
            $this->nem('ma_chi_tiet_hang_hoan', 'Chỉ tạo Flash Sale từ hàng hoàn có kết quả kiểm tra là FLASH_SALE.');
        }

        $hangHoan = HangHoan::whereKey($ct->ma_hang_hoan)->first();
        if (!$hangHoan || $hangHoan->trang_thai !== 'DA_KIEM_TRA') {
            $this->nem('ma_chi_tiet_hang_hoan', 'Hàng hoàn chưa được kiểm tra xong.');
        }

        $soLuong = (int) $data['so_luong'];
        $conLai  = (int) $ct->so_luong_con_lai_flash_sale;

        if ($soLuong > $conLai) {
            $this->nem('so_luong', "Số lượng Flash Sale ({$soLuong}) vượt quá số lượng còn lại ({$conLai}).");
        }

        $daPhanBo = (int) FlashSale::where('ma_chi_tiet_hang_hoan', $ct->ma_chi_tiet_hang_hoan)->lockForUpdate()->sum('so_luong');
        if ($daPhanBo + $soLuong > (int) $ct->so_luong_con_ban_duoc) {
            $this->nem('so_luong', 'Tổng số lượng Flash Sale vượt quá số lượng còn bán được của hàng hoàn.');
        }

        $giaGoc = (float) $ct->gia_ban_snapshot;
        if ($giaGoc <= 0) {
            $this->nem('ti_le_giam', 'Giá bán gốc của hàng hoàn không hợp lệ.');
        }

        $tiLeGiam = (int) $data['ti_le_giam'];
        $giaFlash = round($giaGoc * (1 - $tiLeGiam / 100), 2);

        $flashSale = FlashSale::create([
            'ma_san_pham'           => $ct->ma_san_pham,
            'ma_bo_hoa'             => $ct->ma_bo_hoa,
            'ma_chi_tiet_hang_hoan' => $ct->ma_chi_tiet_hang_hoan,
            'gia_goc'               => $giaGoc,
            'gia_flash_sale'        => $giaFlash,
            'so_tien_giam'          => round($giaGoc - $giaFlash, 2),
            'ti_le_giam'            => $tiLeGiam,
            'so_luong'              => $soLuong,
            'so_luong_da_ban'       => 0,
            'bat_dau'               => Carbon::parse($data['bat_dau']),
            'ket_thuc'              => Carbon::parse($data['ket_thuc']),
            'trang_thai'            => $data['trang_thai'] ?? 'DANG_BAN',
            'nguon'                 => 'HANG_HOAN',
            'nguoi_tao'             => Auth::id(),
        ]);

        $ct->update(['so_luong_con_lai_flash_sale' => $conLai - $soLuong]);

        // Flash Sale là chương trình giá/quota gắn lên hàng tồn kho.
        // Hàng hoàn khi đã được kiểm tra (còn bán được) đã được đưa về tồn vật lý;
        // khi tạo Flash Sale KHÔNG trừ tồn vật lý lần nữa và KHÔNG ẩn bó hoa sang DRAFT.

        return $flashSale;
    }

    private function capNhatFlashSale(int $id, array $data): FlashSale
    {
        $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();
        $daBan = (int) $fs->so_luong_da_ban;

        // Số lượng
        $soLuongMoi = isset($data['so_luong']) ? (int) $data['so_luong'] : (int) $fs->so_luong;
        if ($soLuongMoi < $daBan) {
            $this->nem('so_luong', "Số lượng mới ({$soLuongMoi}) không được nhỏ hơn số lượng đã bán ({$daBan}).");
        }

        // Mức giảm giá
        $tiLeMoi = $fs->ti_le_giam;
        $giaMoi = (float) $fs->gia_flash_sale;
        if (isset($data['ti_le_giam']) && (int) $data['ti_le_giam'] !== (int) $fs->ti_le_giam) {
            if ($daBan > 0) {
                $this->nem('ti_le_giam', 'Flash Sale đã có người mua, không thể đổi mức giảm.');
            }
            $tiLeMoi = (int) $data['ti_le_giam'];
            $giaMoi = round((float) $fs->gia_goc * (1 - $tiLeMoi / 100), 2);
        }

        $batDau  = isset($data['bat_dau']) ? Carbon::parse($data['bat_dau']) : $fs->bat_dau;
        $ketThuc = isset($data['ket_thuc']) ? Carbon::parse($data['ket_thuc']) : $fs->ket_thuc;

        $fs->update([
            'ti_le_giam'     => $tiLeMoi,
            'gia_flash_sale' => $giaMoi,
            'so_tien_giam'   => round((float) $fs->gia_goc - $giaMoi, 2),
            'so_luong'       => $soLuongMoi,
            'bat_dau'        => $batDau,
            'ket_thuc'       => $ketThuc,
            'trang_thai'     => $data['trang_thai'] ?? $fs->trang_thai,
        ]);

        return $fs->refresh();
    }

    private function validateThoiGianTaoMoi(string $batDau, string $ketThuc): void
    {
        $batDauC  = Carbon::parse($batDau);
        $ketThucC = Carbon::parse($ketThuc);
        $nguong   = now()->subMinutes(self::TOLERANCE_PHUT);

        if ($batDauC->lt($nguong)) {
            $this->nem('bat_dau', 'Thời gian bắt đầu không được ở quá khứ.');
        }
        if ($ketThucC->lte(now())) {
            $this->nem('ket_thuc', 'Thời gian kết thúc phải sau thời điểm hiện tại.');
        }
        if ($ketThucC->lte($batDauC)) {
            $this->nem('ket_thuc', 'Thời gian kết thúc phải sau thời gian bắt đầu.');
        }
    }

    private function validateThoiGianCapNhat(array $data): void
    {
        $coBatDau  = array_key_exists('bat_dau', $data) && $data['bat_dau'] !== null && $data['bat_dau'] !== '';
        $coKetThuc = array_key_exists('ket_thuc', $data) && $data['ket_thuc'] !== null && $data['ket_thuc'] !== '';
        $nguong    = now()->subMinutes(self::TOLERANCE_PHUT);

        $batDauC  = $coBatDau  ? Carbon::parse($data['bat_dau'])  : null;
        $ketThucC = $coKetThuc ? Carbon::parse($data['ket_thuc']) : null;

        if ($coBatDau && $batDauC->lt($nguong)) {
            $this->nem('bat_dau', 'Thời gian bắt đầu không được ở quá khứ.');
        }
        if ($coKetThuc && $ketThucC->lte(now())) {
            $this->nem('ket_thuc', 'Thời gian kết thúc phải sau thời điểm hiện tại.');
        }
        if ($coBatDau && $coKetThuc && $ketThucC->lte($batDauC)) {
            $this->nem('ket_thuc', 'Thời gian kết thúc phải sau thời gian bắt đầu.');
        }
    }

    /**
     * Đồng bộ trạng thái: Hết giờ -> KET_THUC (Ngừng nhận đơn, KHÔNG tự hủy)
     */
    private function dongBoTrangThai(): void
    {
        FlashSale::where('trang_thai', 'DANG_BAN')
            ->where('ket_thuc', '<', now())
            ->update(['trang_thai' => 'KET_THUC']);
    }

    private function nem(string $truong, string $thongBao): void
    {
        throw ValidationException::withMessages([$truong => [$thongBao]]);
    }

    private function phanHoiThanhCong(Request $request, string $message, FlashSale $flashSale)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $flashSale]);
        }
        return redirect()->back()->with('success', $message);
    }

    private function phanHoiLoi(string $message, int $status)
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }
        return redirect()->back()->with('error', $message);
    }
}