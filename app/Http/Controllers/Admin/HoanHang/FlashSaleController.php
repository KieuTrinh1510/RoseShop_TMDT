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
use App\Models\NhanVien;
use App\Models\PhatShipper;
use App\Models\SanPham;
use App\Services\KiemTraHangHoanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class FlashSaleController extends Controller
{
    /** Giá Flash Sale gợi ý mặc định: giảm 40% so với giá bán snapshot. */
    private const TY_LE_GIAM_MAC_DINH = 0.40;

    private const TRANG_THAI = ['NHAP', 'DANG_BAN', 'HET_HANG', 'KET_THUC', 'AN'];

    /** Tolerance cho phép lệch 1 phút giữa datetime-local (độ phân giải phút) và now() (giây). */
    private const TOLERANCE_PHUT = 1;

    // ─────────────────────────────────────────────────────────────
    // Danh sách Flash Sale + các dòng hàng hoàn có thể mở Flash Sale
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $request->validate([
            'trang_thai' => ['nullable', 'in:' . implode(',', self::TRANG_THAI)],
        ]);

        $this->dongBoTrangThai();

        $query = FlashSale::with(['sanPham', 'boHoa', 'chiTietHangHoan']);

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        } else {
            $query->whereIn('trang_thai', ['NHAP', 'DANG_BAN', 'AN'])
                  ->whereColumn('so_luong_da_ban', '<', 'so_luong');
        }

        $flashSales = $query->orderByDesc('ma_flash_sale')->paginate(15)->withQueryString();

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
                    'gia_flash_sale_de_xuat'      => round($giaGoc * (1 - self::TY_LE_GIAM_MAC_DINH), 2),
                ];
            });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data'    => ['flash_sales' => $flashSales, 'co_the_tao' => $coTheTao],
            ]);
        }

        return view('admin.FlashSale', [
            'flashSales'      => $flashSales,
            'coTheTao'        => $coTheTao,
            'tyLeGiamMacDinh' => self::TY_LE_GIAM_MAC_DINH,
            'nhanViens'       => NhanVien::orderBy('ten_nhan_vien')->get(['ma_nhan_vien', 'ten_nhan_vien']),
        ]);
    }

    /** Báo hỏng một phần hàng đang mở Flash Sale; giữ lại lịch sử chương trình. */
    public function reportDamage(Request $request, $id)
    {
        $data = $request->validate([
            'so_luong_hong' => ['required', 'integer', 'min:1'],
            'ly_do'         => ['required', 'string', 'max:255'],
            'ma_nhan_vien'  => ['required', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
        ]);

        try {
            DB::transaction(function () use ($id, $data) {
                $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();

                $conLai = max(0, (int) $fs->so_luong - (int) $fs->so_luong_da_ban);
                if ((int) $data['so_luong_hong'] > $conLai) {
                    $this->nem('so_luong_hong', "Chỉ còn {$conLai} sản phẩm trong Flash Sale.");
                }

                $ct = ChiTietHangHoan::with(['chiTietHoaDon', 'boHoa.chiTietBoHoa', 'hangHoan'])
                    ->whereKey($fs->ma_chi_tiet_hang_hoan)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ghiNhanHongFlashSale($ct, (int) $data['so_luong_hong'], $data);

                $fs->so_luong  -= (int) $data['so_luong_hong'];
                $fs->trang_thai = $fs->so_luong <= $fs->so_luong_da_ban ? 'HET_HANG' : $fs->trang_thai;
                $fs->save();

                $ct->increment('so_luong_hong', (int) $data['so_luong_hong']);
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Admin\\FlashSale@reportDamage thất bại', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->phanHoiLoi('Không thể báo hỏng hàng Flash Sale.', 500);
        }

        return redirect()->back()->with('success', 'Đã ghi nhận hàng hỏng và cập nhật Flash Sale.');
    }

    // ─────────────────────────────────────────────────────────────
    // Tạo Flash Sale từ chi tiết hàng hoàn
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request, $maChiTietHangHoan)
    {
        $data = $request->validate([
            'so_luong'       => ['required', 'integer', 'min:1'],
            'gia_flash_sale' => ['nullable', 'numeric', 'min:0.01'],
            'bat_dau'        => ['required', 'date'],
            'ket_thuc'       => ['required', 'date'],
            'trang_thai'     => ['nullable', 'in:NHAP,DANG_BAN'],
        ], [
            'so_luong.required' => 'Vui lòng nhập số lượng Flash Sale.',
            'so_luong.min'      => 'Số lượng Flash Sale phải lớn hơn 0.',
            'bat_dau.required'  => 'Vui lòng chọn thời gian bắt đầu.',
            'ket_thuc.required' => 'Vui lòng chọn thời gian kết thúc.',
        ]);

        // ═══ [MỚI - Q11] Validate thời gian nghiệp vụ: mọi mốc phải từ hiện tại trở đi ═══
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
                'file'                  => $e->getFile() . ':' . $e->getLine(),
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
            ->with('success', 'Đã tạo Flash Sale thành công.');
    }

    // ─────────────────────────────────────────────────────────────
    // Cập nhật giá, số lượng, thời gian
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'gia_flash_sale' => ['nullable', 'numeric', 'min:0.01'],
            'so_luong'       => ['nullable', 'integer', 'min:1'],
            'bat_dau'        => ['nullable', 'date'],
            'ket_thuc'       => ['nullable', 'date'],
            'trang_thai'     => ['nullable', 'in:NHAP,DANG_BAN,AN'],
        ]);

        // ═══ [MỚI - Q11] Validate thời gian nếu admin có set mới ═══
        $this->validateThoiGianCapNhat($data);

        try {
            $flashSale = DB::transaction(fn () => $this->capNhatFlashSale((int) $id, $data), 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return $this->phanHoiLoi('Không tìm thấy Flash Sale.', 404);
        } catch (Throwable $e) {
            Log::error('Admin\\FlashSale@update thất bại', [
                'ma_flash_sale' => $id,
                'error'         => $e->getMessage(),
                'file'          => $e->getFile() . ':' . $e->getLine(),
            ]);
            return $this->phanHoiLoi('Không thể cập nhật Flash Sale. Vui lòng thử lại.', 500);
        }

        return $this->phanHoiThanhCong($request, 'Đã cập nhật Flash Sale.', $flashSale);
    }

    // ─────────────────────────────────────────────────────────────
    // Kết thúc Flash Sale
    // ─────────────────────────────────────────────────────────────
    public function close(Request $request, $id)
    {
        $request->validate([
            'bo_hong'  => ['nullable', 'boolean'],
            'hoan_ton' => ['nullable', 'boolean'],
        ]);

        $boHong  = $request->boolean('bo_hong');
        $hoanTon = $request->boolean('hoan_ton');

        try {
            [$flashSale, $ghiChu] = DB::transaction(fn () => $this->ketThucFlashSale((int) $id, $boHong, $hoanTon), 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return $this->phanHoiLoi('Không tìm thấy Flash Sale.', 404);
        } catch (Throwable $e) {
            Log::error('Admin\\FlashSale@close thất bại', [
                'ma_flash_sale' => $id,
                'error'         => $e->getMessage(),
                'file'          => $e->getFile() . ':' . $e->getLine(),
            ]);
            return $this->phanHoiLoi('Không thể kết thúc Flash Sale. Vui lòng thử lại.', 500);
        }

        $message = trim('Đã kết thúc Flash Sale. ' . implode(' ', array_filter($ghiChu)));
        return $this->phanHoiThanhCong($request, $message, $flashSale);
    }

    // ═════════════════════════════════════════════════════════════
    // [MỚI - Q11] Validate thời gian
    // ═════════════════════════════════════════════════════════════

    /**
     * Validate thời gian khi TẠO MỚI:
     *   - bat_dau >= now - TOLERANCE_PHUT (cho phép lệch 1 phút do datetime-local độ phân giải phút)
     *   - ket_thuc > now
     *   - ket_thuc > bat_dau
     */
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

    /**
     * Validate thời gian khi CẬP NHẬT / GIA HẠN:
     *   - Chỉ validate các trường admin có gửi lên (isset + not null).
     *   - bat_dau mới: >= now - TOLERANCE_PHUT
     *   - ket_thuc mới: > now
     *   - Nếu gửi cả 2: ket_thuc > bat_dau
     */
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

    // ═════════════════════════════════════════════════════════════
    // Nghiệp vụ (chạy trong transaction)
    // ═════════════════════════════════════════════════════════════
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
            $this->nem('so_luong', "Số lượng Flash Sale ({$soLuong}) vượt quá số lượng còn lại có thể bán ({$conLai}).");
        }

        $daPhanBo = (int) FlashSale::where('ma_chi_tiet_hang_hoan', $ct->ma_chi_tiet_hang_hoan)->lockForUpdate()->sum('so_luong');
        if ($daPhanBo + $soLuong > (int) $ct->so_luong_con_ban_duoc) {
            $this->nem('so_luong', 'Tổng số lượng Flash Sale vượt quá số lượng còn bán được của hàng hoàn.');
        }

        $giaGoc = (float) $ct->gia_ban_snapshot;
        if ($giaGoc <= 0) {
            $this->nem('gia_flash_sale', 'Giá bán gốc của hàng hoàn không hợp lệ.');
        }

        $giaFlash = isset($data['gia_flash_sale']) && $data['gia_flash_sale'] !== ''
            ? round((float) $data['gia_flash_sale'], 2)
            : round($giaGoc * (1 - self::TY_LE_GIAM_MAC_DINH), 2);

        if ($giaFlash <= 0) {
            $this->nem('gia_flash_sale', 'Giá Flash Sale phải lớn hơn 0.');
        }
        if ($giaFlash > $giaGoc) {
            $this->nem('gia_flash_sale', 'Giá Flash Sale không được lớn hơn giá gốc.');
        }

        $flashSale = FlashSale::create([
            'ma_san_pham'           => $ct->ma_san_pham,
            'ma_bo_hoa'             => $ct->ma_bo_hoa,
            'ma_chi_tiet_hang_hoan' => $ct->ma_chi_tiet_hang_hoan,
            'gia_goc'               => $giaGoc,
            'gia_flash_sale'        => $giaFlash,
            'so_tien_giam'          => round($giaGoc - $giaFlash, 2),
            'so_luong'              => $soLuong,
            'so_luong_da_ban'       => 0,
            'bat_dau'               => Carbon::parse($data['bat_dau']),
            'ket_thuc'              => Carbon::parse($data['ket_thuc']),
            'trang_thai'            => $data['trang_thai'] ?? 'DANG_BAN',
        ]);

        $ct->update(['so_luong_con_lai_flash_sale' => $conLai - $soLuong]);

        // Tách hàng khỏi lot thường để dành riêng cho FlashSale
        if ($ct->ma_san_pham) {
            $maLo = $ct->chiTietHoaDon?->ma_chi_tiet_nhap;
            if ($maLo) {
                $loGoc = ChiTietNhap::whereKey($maLo)->lockForUpdate()->first();
                if (!$loGoc || (int) $loGoc->so_luong_con_lai < $soLuong) {
                    $this->nem('so_luong', 'Lô hàng không đủ tồn kho để tách cho Flash Sale.');
                }
                $loGoc->decrement('so_luong_con_lai', $soLuong);
            }
            SanPham::whereKey($ct->ma_san_pham)->lockForUpdate()->decrement('so_luong', $soLuong);
        }

        // Bó hoa: ẩn khỏi khách thường (chỉ bán qua FlashSale)
        if ($ct->ma_bo_hoa) {
            $bo = BoHoa::whereKey($ct->ma_bo_hoa)->lockForUpdate()->first();
            if ($bo) {
                $bo->update(['trang_thai' => 'DRAFT']);
            }
        }

        return $flashSale;
    }

    private function capNhatFlashSale(int $id, array $data): FlashSale
    {
        $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();

        $ct = $fs->ma_chi_tiet_hang_hoan
            ? ChiTietHangHoan::whereKey($fs->ma_chi_tiet_hang_hoan)->lockForUpdate()->first()
            : null;

        $daBan  = (int) $fs->so_luong_da_ban;
        $giaCu  = (float) $fs->gia_flash_sale;

        // ── Số lượng ──
        $soLuongMoi = isset($data['so_luong']) ? (int) $data['so_luong'] : (int) $fs->so_luong;
        if ($soLuongMoi < $daBan) {
            $this->nem('so_luong', "Số lượng mới ({$soLuongMoi}) không được nhỏ hơn số lượng đã bán ({$daBan}).");
        }
        $delta = $soLuongMoi - (int) $fs->so_luong;
        if ($ct && $delta !== 0) {
            $conLai = (int) $ct->so_luong_con_lai_flash_sale;
            if ($delta > $conLai) {
                $this->nem('so_luong', "Không đủ hàng hoàn để tăng thêm {$delta} (chỉ còn {$conLai}).");
            }
            $ct->update(['so_luong_con_lai_flash_sale' => $conLai - $delta]);

            if ($ct->ma_san_pham) {
                if ($delta > 0) {
                    $maLo = $ct->chiTietHoaDon?->ma_chi_tiet_nhap;
                    if ($maLo) {
                        ChiTietNhap::whereKey($maLo)->lockForUpdate()->decrement('so_luong_con_lai', $delta);
                    }
                    SanPham::whereKey($ct->ma_san_pham)->lockForUpdate()->decrement('so_luong', $delta);
                } elseif ($delta < 0) {
                    $hoan = -$delta;
                    $maLo = $ct->chiTietHoaDon?->ma_chi_tiet_nhap;
                    if ($maLo) {
                        ChiTietNhap::whereKey($maLo)->lockForUpdate()->increment('so_luong_con_lai', $hoan);
                    }
                    SanPham::whereKey($ct->ma_san_pham)->lockForUpdate()->increment('so_luong', $hoan);
                }
            }
        }

        // ── Giá ──
        $giaMoi = $giaCu;
        if (isset($data['gia_flash_sale']) && $data['gia_flash_sale'] !== '') {
            $giaNhap = round((float) $data['gia_flash_sale'], 2);
            if ($giaNhap !== $giaCu) {
                if ($daBan > 0) {
                    $this->nem('gia_flash_sale', 'Flash Sale đã có người mua, không thể đổi giá.');
                }
                if ($giaNhap > (float) $fs->gia_goc) {
                    $this->nem('gia_flash_sale', 'Giá Flash Sale không được lớn hơn giá gốc.');
                }
                $giaMoi = $giaNhap;
            }
        }
        $giaThayDoi = ($giaMoi !== $giaCu);

        // ── Thời gian ──
        $batDau  = isset($data['bat_dau']) ? Carbon::parse($data['bat_dau']) : $fs->bat_dau;
        $ketThuc = isset($data['ket_thuc']) ? Carbon::parse($data['ket_thuc']) : $fs->ket_thuc;
        if ($batDau && $ketThuc && $ketThuc->lte($batDau)) {
            $this->nem('ket_thuc', 'Thời gian kết thúc phải sau thời gian bắt đầu.');
        }

        // ── Trạng thái ──
        $trangThai  = $data['trang_thai'] ?? null;
        $moLai      = $ketThuc && $ketThuc->gt(now());
        $dangKet    = in_array($fs->trang_thai, ['KET_THUC', 'HET_HANG'], true);

        if ($trangThai === null) {
            if ($dangKet && $moLai) {
                $trangThai = $soLuongMoi <= $daBan ? 'HET_HANG' : 'DANG_BAN';
            } elseif (in_array($fs->trang_thai, ['DANG_BAN', 'HET_HANG'], true)) {
                $trangThai = $soLuongMoi <= $daBan ? 'HET_HANG' : 'DANG_BAN';
            } else {
                $trangThai = $fs->trang_thai;
            }
        } else {
            if (in_array($trangThai, ['DANG_BAN', 'HET_HANG'], true)) {
                $trangThai = $soLuongMoi <= $daBan ? 'HET_HANG' : 'DANG_BAN';
            }
        }

        $fs->update([
            'gia_flash_sale' => $giaMoi,
            'so_tien_giam'   => round((float) $fs->gia_goc - (float) $giaMoi, 2),
            'so_luong'       => $soLuongMoi,
            'bat_dau'        => $batDau,
            'ket_thuc'       => $ketThuc,
            'trang_thai'     => $trangThai,
        ]);

        // ═══ [MỚI - Q11] Nếu giá thay đổi và chưa có ai mua → cập nhật lại tiền hao hụt ═══
        // Chỉ chạy khi FS gắn với chi_tiet_hang_hoan và vi phạm chưa bị đánh dấu DA_XU_LY.
        if ($giaThayDoi && $ct && $ct->ma_chi_tiet_hang_hoan && $ct->ket_qua !== 'DA_XU_LY') {
            app(KiemTraHangHoanService::class)
                ->capNhatTienHaoHutFlashSale((int) $ct->ma_chi_tiet_hang_hoan, (float) $giaMoi);
        }

        return $fs->refresh();
    }

    private function ketThucFlashSale(int $id, bool $boHong, bool $hoanTon): array
    {
        $fs = FlashSale::whereKey($id)->lockForUpdate()->firstOrFail();

        if ($fs->trang_thai === 'KET_THUC') {
            $this->nem('flash_sale', 'Flash Sale này đã kết thúc trước đó.');
        }

        $ct = $fs->ma_chi_tiet_hang_hoan
            ? ChiTietHangHoan::whereKey($fs->ma_chi_tiet_hang_hoan)->lockForUpdate()->first()
            : null;

        $ghiChu = [];

        if ($boHong) {
            if (empty($fs->ma_bo_hoa) || !$ct) {
                $this->nem('bo_hong', 'Chỉ áp dụng cho Flash Sale bó hoa có liên kết hàng hoàn.');
            }
            $ghiChu[] = $this->mienPhatShipper($ct);
        } elseif ($hoanTon) {
            if (!empty($fs->ma_bo_hoa)) {
                $this->nem('hoan_ton', 'Bó hoa không hoàn tồn về lại Flash Sale.');
            }
            $conTon = max(0, (int) $fs->so_luong - (int) $fs->so_luong_da_ban);
            if ($ct && $conTon > 0) {
                $ct->update([
                    'so_luong_con_lai_flash_sale' => (int) $ct->so_luong_con_lai_flash_sale + $conTon,
                ]);
                $fs->so_luong = (int) $fs->so_luong_da_ban;
                $ghiChu[] = "Đã hoàn {$conTon} đơn vị chưa bán về hàng hoàn.";
            }
        }

        $chuaBan = max(0, (int) $fs->so_luong - (int) $fs->so_luong_da_ban);
        if ($chuaBan > 0 && $ct) {
            if ($ct->ma_san_pham && $ct->chiTietHoaDon?->ma_chi_tiet_nhap) {
                ChiTietNhap::whereKey($ct->chiTietHoaDon->ma_chi_tiet_nhap)
                    ->lockForUpdate()->increment('so_luong_con_lai', $chuaBan);
                SanPham::whereKey($ct->ma_san_pham)->lockForUpdate()->increment('so_luong', $chuaBan);
            }
            if ($ct->ma_bo_hoa) {
                $bo = BoHoa::whereKey($ct->ma_bo_hoa)->lockForUpdate()->first();
                if ($bo) $bo->update(['trang_thai' => 'ACTIVE']);
            }
            $ghiChu[] = "Đã hoàn {$chuaBan} đơn vị chưa bán về tồn kho thường.";
        }

        $fs->trang_thai = 'KET_THUC';
        if (!$fs->ket_thuc || $fs->ket_thuc->gt(now())) {
            $fs->ket_thuc = now();
        }
        $fs->save();

        return [$fs->refresh(), $ghiChu];
    }

    private function mienPhatShipper(ChiTietHangHoan $ct): string
    {
        if ($ct->mien_phat_shipper) {
            return 'Dòng hàng hoàn này đã được miễn phạt shipper trước đó.';
        }

        $shipperChiu = (float) $ct->tien_shipper_chiu;
        $hong        = (float) $ct->tien_thiet_hai_hang_hong;
        $hao         = (float) $ct->tien_hao_hut_flash_sale;
        $tong        = $hong + $hao;
        $phanHong    = $tong > 0 ? round($shipperChiu * $hong / $tong, 2) : 0.0;
        $phanHao     = round($shipperChiu - $phanHong, 2);

        $ct->update([
            'mien_phat_shipper'  => true,
            'tien_shipper_chiu'  => 0,
            'tien_he_thong_chiu' => round((float) $ct->tien_he_thong_chiu + $shipperChiu, 2),
        ]);

        if ($shipperChiu <= 0) {
            return 'Đã đánh dấu miễn phạt shipper.';
        }

        $hangHoan = HangHoan::whereKey($ct->ma_hang_hoan)->first();
        $phat = $hangHoan
            ? PhatShipper::where('ma_giao_hang_that_bai', $hangHoan->ma_giao_hang_that_bai)->lockForUpdate()->first()
            : null;

        if (!$phat) {
            return 'Đã miễn phạt shipper (không tìm thấy phiếu phạt để điều chỉnh).';
        }
        if ($phat->trang_thai === 'DA_THU') {
            return 'Đã miễn phạt shipper, nhưng phiếu phạt đã thu tiền nên cần hoàn tiền thủ công.';
        }
        if ($phat->trang_thai === 'MIEN_PHAT') {
            return 'Đã miễn phạt shipper.';
        }

        $conPhat = max(0.0, round((float) $phat->so_tien - $shipperChiu, 2));
        $phat->update([
            'so_tien'                 => $conPhat,
            'tien_hang_hong'          => max(0.0, round((float) $phat->tien_hang_hong - $phanHong, 2)),
            'tien_hao_hut_flash_sale' => max(0.0, round((float) $phat->tien_hao_hut_flash_sale - $phanHao, 2)),
            'trang_thai'              => $conPhat <= 0 ? 'MIEN_PHAT' : $phat->trang_thai,
        ]);

        return $conPhat <= 0
            ? 'Đã miễn phạt shipper (phiếu phạt chuyển sang MIEN_PHAT).'
            : 'Đã giảm khoản phạt shipper tương ứng với bó bị hỏng.';
    }

    /**
     * Đồng bộ trạng thái theo thời gian.
     * KHÔNG auto HET_HANG khi đã bán hết vì admin còn có thể báo hỏng phần tồn.
     */
    private function dongBoTrangThai(): void
    {
        FlashSale::where('trang_thai', 'DANG_BAN')
            ->where('ket_thuc', '<', now())
            ->update(['trang_thai' => 'KET_THUC']);
    }

    private function ghiNhanHongFlashSale(ChiTietHangHoan $ct, int $soLuongHong, array $data): void
    {
        $chung = [
            'ma_nhan_vien'      => (int) $data['ma_nhan_vien'],
            'ly_do'             => trim((string) $data['ly_do']),
            'thoi_gian_bao_cao' => now(),
            'ghi_chu'           => "Hàng Flash Sale từ dòng hoàn #{$ct->ma_chi_tiet_hang_hoan}.",
        ];

        if ($ct->ma_san_pham) {
            $maLo = $ct->chiTietHoaDon?->ma_chi_tiet_nhap;

            if (! $maLo && $ct->hangHoan?->ma_hoa_don) {
                $maLo = InventoryReservation::where('ma_hoa_don', $ct->hangHoan->ma_hoa_don)
                    ->where('ma_san_pham', $ct->ma_san_pham)
                    ->whereNotNull('ma_chi_tiet_nhap')
                    ->orderByDesc('id')
                    ->value('ma_chi_tiet_nhap');
            }

            if (! $maLo) {
                $this->nem('so_luong_hong', 'Không truy được lô đã xuất của sản phẩm Flash Sale.');
            }

            BaoCaoHangHong::create($chung + [
                'ma_san_pham'      => $ct->ma_san_pham,
                'ma_chi_tiet_nhap' => $maLo,
                'so_luong_hong'    => $soLuongHong,
            ]);
            return;
        }

        $daGhiNhan = false;
        foreach ($ct->boHoa?->chiTietBoHoa ?? [] as $thanhPhan) {
            if (! $thanhPhan->ma_san_pham || ! $thanhPhan->ma_chi_tiet_nhap) {
                continue;
            }
            BaoCaoHangHong::create($chung + [
                'ma_san_pham'      => $thanhPhan->ma_san_pham,
                'ma_chi_tiet_nhap' => $thanhPhan->ma_chi_tiet_nhap,
                'so_luong_hong'    => (int) $thanhPhan->so_luong * $soLuongHong,
            ]);
            $daGhiNhan = true;
        }

        if (! $daGhiNhan) {
            $this->nem('so_luong_hong', 'Không truy được cấu phần/lô gốc của bó hoa Flash Sale.');
        }
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