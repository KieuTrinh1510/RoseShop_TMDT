<?php

namespace App\Http\Controllers\Admin\HoanHang;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoHangHong;
use App\Models\ChiTietHangHoan;
use App\Models\FlashSale;
use App\Models\GiaoHangThatBai;
use App\Models\HangHoan;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\NhanVien;
use App\Models\PhatShipper;
use App\Models\ViPhamKhachHang;
use App\Models\BoHoa;     
use App\Models\ChiTietNhap;        
use App\Models\SanPham;     
use App\Models\KhachHang;
use App\Models\ThongBao;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class KiemTraHangController extends Controller
{
    /** Giá Flash Sale gợi ý mặc định: giảm 40% so với gia_ban_snapshot. */
    private const TY_LE_GIAM_FLASH_SALE = 0.40;

    /** Hàng hỏng do shipper: shipper chịu 100% giá bán snapshot. */
    private const TY_LE_SHIPPER_CHIU_HANG_HONG = 1.00;

    /** Hao hụt do bán Flash Sale: shipper chịu 50%, hệ thống chịu 50%. */
    private const TY_LE_SHIPPER_CHIU_HAO_HUT = 0.50;

    /** Bồi thường cố định cho khách khi shipper hủy đơn (điểm và VNĐ). */
    private const DIEM_BOI_THUONG_KHACH = 20;
    private const TIEN_PHAT_BOI_THUONG   = 20000;

    // ─────────────────────────────────────────────────────────────
    // Hiển thị đơn CANCELLED và các sản phẩm cần kiểm tra
    // ─────────────────────────────────────────────────────────────
    public function index($maHoaDon)
    {
        $hoaDon = HoaDon::with(['khachHang', 'shipper'])->findOrFail($maHoaDon);

        if ($loi = $this->loiDieuKienDon($hoaDon)) {
            return $this->phanHoiLoi($loi, 422);
        }

        $hangHoan = HangHoan::with([
            'giaoHangThatBai.shipper',
            'chiTiet.sanPham',
            'chiTiet.chiTietHoaDon',
            'chiTiet.boHoa.chiTietBoHoa',
            'nguoiKiemTra',
        ])->where('ma_hoa_don', $hoaDon->ma_hoa_don)->first();

        if (!$hangHoan) {
            return $this->phanHoiLoi('Đơn này chưa có phiếu hàng hoàn nên chưa thể kiểm tra.', 422);
        }

        $data = [
            'hoaDon'          => $hoaDon,
            'hangHoan'        => $hangHoan,
            'giaoHangThatBai' => $hangHoan->giaoHangThatBai,
            'loaiLoi'         => strtoupper((string) optional($hangHoan->giaoHangThatBai)->loai_loi),
            'daKiemTra'       => $hangHoan->trang_thai === 'DA_KIEM_TRA',
            'dongKiemTra'     => $hangHoan->chiTiet->map(fn ($ct) => $this->moTaDong($ct))->values(),
            'tyLeGiamMacDinh' => self::TY_LE_GIAM_FLASH_SALE,
            'nhanViens' => $this->danhSachNhanVienBaoCao(),
        ];

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        $data['khachHang']       = $hoaDon->khachHang;
        $data['shipper']         = optional($hangHoan->giaoHangThatBai)->shipper;
        $data['chiTietHangHoan'] = $hangHoan->chiTiet;

        return view('admin.HoaDon.KiemTraHang', $data);
    }

    // ─────────────────────────────────────────────────────────────
    // Lưu kết quả kiểm tra + quyết toán
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request, $maHoaDon)
    {
        $request->validate([
            'items'                         => ['required', 'array', 'min:1'],
            'items.*.huong_xu_ly'           => ['nullable', 'string', 'in:CON_TOT,FLASH_SALE,HONG'],
            'items.*.ti_le_giam'            => ['nullable', 'integer', 'in:30,50,75'],
            'items.*.so_luong_hong'         => ['nullable', 'integer', 'min:0'],
            'items.*.so_luong_con_ban_duoc' => ['nullable', 'integer', 'min:0'],
            'items.*.xa_bo'                 => ['nullable', 'boolean'],
            'items.*.ly_do_hong'            => ['nullable', 'string', 'max:255'],
            'items.*.ma_nhan_vien'          => ['nullable', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
            'items.*.ghi_chu'               => ['nullable', 'string', 'max:500'],
            'ghi_chu'                       => ['nullable', 'string', 'max:1000'],
        ], [
            'items.required'                        => 'Vui lòng nhập kết quả kiểm tra cho từng sản phẩm.',
            'items.*.huong_xu_ly.in'                => 'Hướng xử lý phải là Còn tốt (CON_TOT), Flash Sale (FLASH_SALE) hoặc Báo hỏng (HONG).',
            'items.*.ti_le_giam.in'                 => 'Mức giảm Flash Sale chỉ được chọn 30%, 50% hoặc 75%.',
            'items.*.so_luong_hong.integer'         => 'Số lượng hỏng phải là số nguyên.',
            'items.*.so_luong_hong.min'             => 'Số lượng hỏng không được âm.',
            'items.*.so_luong_con_ban_duoc.integer' => 'Số lượng còn bán được phải là số nguyên.',
            'items.*.so_luong_con_ban_duoc.min'     => 'Số lượng còn bán được không được âm.',
        ]);

        try {
            $ketQua = DB::transaction(fn () => $this->quyetToan($request, (int) $maHoaDon), 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return $this->phanHoiLoi('Không tìm thấy hóa đơn.', 404);
        } catch (Throwable $e) {
            Log::error('KiemTraHang@store thất bại', [
                'ma_hoa_don' => $maHoaDon,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile() . ':' . $e->getLine(),
            ]);
            return $this->phanHoiLoi('Không thể lưu kết quả kiểm tra. Vui lòng thử lại.', 500);
        }

        $message = 'Đã lưu kết quả kiểm tra hàng hoàn đơn #HD-' . str_pad((string) $maHoaDon, 4, '0', STR_PAD_LEFT) . '.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $ketQua]);
        }

        // Sau khi lưu → quay về danh sách đơn hàng tab Giao thất bại
        return redirect()->route('don-hang.index', ['tab' => 'failed'])->with('success', $message);
    }

    /**
     * Xác nhận nhanh hàng hoàn sang trạng thái "ĐÃ KIỂM TRA" ngay tại chỗ (AJAX, không cần chuyển trang).
     */
    public function xacNhanDaKiemTra(Request $request, $maHoaDon)
    {
        $hoaDon = HoaDon::findOrFail($maHoaDon);
        $hangHoan = HangHoan::where('ma_hoa_don', $hoaDon->ma_hoa_don)->lockForUpdate()->first();

        if (!$hangHoan) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy phiếu hàng hoàn cho đơn này.'], 404);
        }

        if ($hangHoan->trang_thai === 'DA_KIEM_TRA') {
            return response()->json([
                'success' => true,
                'message' => 'Hàng hoàn đã ở trạng thái ĐÃ KIỂM TRA.',
                'trang_thai' => 'DA_KIEM_TRA',
            ]);
        }

        $bayGio = now();
        $hangHoan->update([
            'trang_thai'             => 'DA_KIEM_TRA',
            'nguoi_kiem_tra'         => Auth::id(),
            'thoi_diem_nhan_tai_kho' => $hangHoan->thoi_diem_nhan_tai_kho ?? $bayGio,
            'thoi_diem_kiem_tra'     => $bayGio,
        ]);

        if ($hangHoan->ma_giao_hang_that_bai) {
            GiaoHangThatBai::whereKey($hangHoan->ma_giao_hang_that_bai)->update(['trang_thai' => 'DA_XU_LY']);
        }

        $message = 'Đã xác nhận kiểm tra hàng hoàn đơn #HD-' . str_pad((string) $maHoaDon, 4, '0', STR_PAD_LEFT) . ' thành công.';

        return response()->json([
            'success'            => true,
            'message'            => $message,
            'trang_thai'         => 'DA_KIEM_TRA',
            'ngay_kiem_tra'      => $bayGio->format('d/m/Y H:i'),
            'nguoi_kiem_tra'     => Auth::user()?->ho_ten ?? Auth::user()?->ten_dang_nhap ?? 'Admin',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Nghiệp vụ quyết toán (chạy trong transaction)
    // ─────────────────────────────────────────────────────────────
    private function quyetToan(Request $request, int $maHoaDon): array
    {
        $hoaDon = HoaDon::whereKey($maHoaDon)->lockForUpdate()->firstOrFail();

        if ($loi = $this->loiDieuKienDon($hoaDon)) {
            $this->nem('hoa_don', $loi);
        }

        $hangHoan = HangHoan::where('ma_hoa_don', $hoaDon->ma_hoa_don)->lockForUpdate()->first();
        if (!$hangHoan) {
            $this->nem('hoa_don', 'Đơn này chưa có phiếu hàng hoàn.');
        }

        $ghtb = GiaoHangThatBai::whereKey($hangHoan->ma_giao_hang_that_bai)->lockForUpdate()->first();
        if (!$ghtb) {
            $this->nem('hoa_don', 'Không tìm thấy bản ghi giao hàng thất bại gắn với hàng hoàn.');
        }

        $loaiLoi = strtoupper((string) $ghtb->loai_loi);
        if (!in_array($loaiLoi, ['CUSTOMER', 'SHIPPER'], true)) {
            $this->nem('hoa_don', 'Nguồn lỗi của lần giao thất bại không hợp lệ (chỉ CUSTOMER hoặc SHIPPER).');
        }

        // Eager load đầy đủ để tránh lỗi khi ghi báo cáo hỏng
        $chiTiets = ChiTietHangHoan::with([
                'sanPham',
                'chiTietHoaDon',
                'boHoa.chiTietBoHoa',
                'hangHoan',
            ])
            ->where('ma_hang_hoan', $hangHoan->ma_hang_hoan)
            ->orderBy('ma_chi_tiet_hang_hoan')
            ->lockForUpdate()
            ->get();

        if ($chiTiets->isEmpty()) {
            $this->nem('items', 'Phiếu hàng hoàn chưa có chi tiết sản phẩm để kiểm tra.');
        }

        // Kiểm tra xem tất cả các dòng đã hoàn tất xử lý chưa (chặn không cho xử lý lại)
        $daHoanTatHet = $chiTiets->every(fn ($ct) => $ct->ket_qua !== 'CHO_KIEM_TRA');
        if ($daHoanTatHet) {
            $this->nem('hoa_don', 'Hàng hoàn của đơn này đã được kiểm tra, không thể kiểm tra lại.');
        }

        $items  = (array) $request->input('items');
        if (empty($items)) {
            $this->nem('items', 'Dữ liệu kiểm tra phải đủ và đúng các sản phẩm của phiếu hàng hoàn.');
        }

        $idsDon = $chiTiets->pluck('ma_chi_tiet_hang_hoan')->map(fn ($v) => (int) $v)->sort()->values()->all();
        $idsGui = collect(array_keys($items))->map(fn ($v) => (int) $v)->sort()->values()->all();

        // Kiểm tra các ID gửi lên phải thuộc phiếu hàng hoàn này
        if (array_diff($idsGui, $idsDon)) {
            $this->nem('items', 'Dữ liệu kiểm tra có sản phẩm không thuộc phiếu hàng hoàn này.');
        }

        $tongThietHai    = $tongHaoHut = 0.0;
        $tongShipperHong = $tongShipperHao = 0.0;
        $tongShipper     = $tongHeThong = 0.0;
        $maHD = '#HD-' . str_pad((string) $hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT);

        foreach ($chiTiets as $ct) {
            if (!isset($items[$ct->ma_chi_tiet_hang_hoan])) {
                continue;
            }

            if ($ct->ket_qua !== 'CHO_KIEM_TRA') {
                $this->nem('items.' . $ct->ma_chi_tiet_hang_hoan, 'Mặt hàng này đã được kiểm tra và ra quyết định xử lý trước đó.');
            }

            $in      = $items[$ct->ma_chi_tiet_hang_hoan] ?? [];
            $key     = 'items.' . $ct->ma_chi_tiet_hang_hoan;
            $theoDon = (int) $ct->so_luong_theo_don;
            $laBo    = !empty($ct->ma_bo_hoa);
            $xaBo    = filter_var($in['xa_bo'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($xaBo && !$laBo) {
                $this->nem($key . '.xa_bo', 'Chỉ bó hoa mới có thể xả bó.');
            }

            // Hỗ trợ cả 2 chế độ: chọn 1 nhánh đơn hoặc phân bổ số lượng từng nhánh
            $huongXuLy    = $in['huong_xu_ly'] ?? null;
            $soLuongTot   = isset($in['so_luong_tot']) ? (int) $in['so_luong_tot'] : 0;
            $soLuongFlash = isset($in['so_luong_flash_sale']) ? (int) $in['so_luong_flash_sale'] : 0;
            $soLuongHong  = isset($in['so_luong_hong']) ? (int) $in['so_luong_hong'] : 0;

            if ($xaBo) {
                $soLuongHong  = $theoDon;
                $soLuongTot   = 0;
                $soLuongFlash = 0;
                $huongXuLy    = 'HONG';
            } elseif ($soLuongTot === 0 && $soLuongFlash === 0 && $soLuongHong === 0) {
                if ($huongXuLy === 'CON_TOT') {
                    $soLuongTot = $theoDon;
                } elseif ($huongXuLy === 'FLASH_SALE') {
                    $soLuongFlash = $theoDon;
                } elseif ($huongXuLy === 'HONG') {
                    $soLuongHong = $theoDon;
                } else {
                    if (!empty($in['ti_le_giam'])) {
                        $soLuongFlash = (int)($in['so_luong_con_ban_duoc'] ?? $theoDon);
                        $huongXuLy = 'FLASH_SALE';
                    } elseif ((int)($in['so_luong_con_ban_duoc'] ?? 0) > 0) {
                        $soLuongTot = (int) $in['so_luong_con_ban_duoc'];
                        $huongXuLy = 'CON_TOT';
                    } else {
                        $soLuongHong = $theoDon;
                        $huongXuLy = 'HONG';
                    }
                }
            }

            if ($soLuongTot < 0 || $soLuongFlash < 0 || $soLuongHong < 0) {
                $this->nem($key, 'Số lượng của các nhánh kiểm tra không được âm.');
            }

            $tongXacNhan = $soLuongTot + $soLuongFlash + $soLuongHong;
            if ($tongXacNhan !== $theoDon) {
                $this->nem($key, "Tổng số lượng kiểm tra ({$tongXacNhan}) phải đúng bằng số lượng hàng hoàn theo đơn ({$theoDon}).");
            }

            $giaBan   = (float) $ct->gia_ban_snapshot;
            $thietHai = 0.0;
            $haoHut   = 0.0;
            $doanhThu = 0.0;
            $shipperHong = 0.0;
            $shipperHao  = 0.0;

            // Truy tìm lô xuất kho ban đầu của sản phẩm
            $maLo = null;
            if ($ct->ma_san_pham) {
                $maLo = $ct->chiTietHoaDon?->ma_chi_tiet_nhap;
                if (!$maLo && $ct->hangHoan?->ma_hoa_don) {
                    $maLo = InventoryReservation::where('ma_hoa_don', $ct->hangHoan->ma_hoa_don)
                        ->where('ma_san_pham', $ct->ma_san_pham)
                        ->whereNotNull('ma_chi_tiet_nhap')
                        ->orderByDesc('id')
                        ->value('ma_chi_tiet_nhap');
                }
            }

            $lot = $maLo ? ChiTietNhap::find($maLo) : null;
            $hsd = $lot?->ngay_het_han ? \Carbon\Carbon::parse($lot->ngay_het_han)->endOfDay() : null;

            // ═════════════════════════════════════════════════════════════════
            // 1. XỬ LÝ NHÁNH CÒN TỐT (RESTOCK BÁN GIÁ HIỆN TẠI)
            // ═════════════════════════════════════════════════════════════════
            if ($soLuongTot > 0) {
                if ($ct->ma_san_pham) {
                    if ($maLo) {
                        ChiTietNhap::whereKey($maLo)->lockForUpdate()->increment('so_luong_con_lai', $soLuongTot);
                    }
                    SanPham::whereKey($ct->ma_san_pham)->lockForUpdate()->increment('so_luong', $soLuongTot);
                }
                if ($ct->ma_bo_hoa) {
                    $bo = BoHoa::whereKey($ct->ma_bo_hoa)->lockForUpdate()->first();
                    if ($bo) {
                        $bo->update([
                            'ma_hoa_don'    => null,
                            'ma_khach_hang' => null,
                            'loi_nhan'      => null,
                            'trang_thai'    => 'ACTIVE',
                            'so_luong'      => $soLuongTot,
                        ]);
                    }
                }
            }

            // ═════════════════════════════════════════════════════════════════
            // 2. XỬ LÝ NHÁNH GIẢM CHẤT LƯỢNG (FLASH SALE 30% / 50% / 75%)
            // ═════════════════════════════════════════════════════════════════
            $giaFlash = 0.0;
            if ($soLuongFlash > 0) {
                if ($hsd && $hsd->isPast()) {
                    $this->nem($key, "Lô hoa này đã hết hạn sử dụng vào ngày {$lot->ngay_het_han}, không được bán Flash Sale mà phải Báo hỏng.");
                }

                $tiLeGiam = (int) ($in['ti_le_giam'] ?? 0);
                if (!in_array($tiLeGiam, [30, 50, 75], true)) {
                    $this->nem($key . '.ti_le_giam', 'Mức giảm Flash Sale chỉ được chọn 30%, 50% hoặc 75%.');
                }

                $daCoFs = FlashSale::where('ma_chi_tiet_hang_hoan', $ct->ma_chi_tiet_hang_hoan)->exists();
                if ($daCoFs) {
                    $this->nem($key, 'Dòng hàng hoàn này đã được tạo Flash Sale trước đó, không thể tạo trùng.');
                }

                $giaFlash   = round($giaBan * (1 - $tiLeGiam / 100), 2);
                $soTienGiam = round($giaBan - $giaFlash, 2);
                $haoHut     = round($soLuongFlash * $soTienGiam, 2);
                $doanhThu   = round($soLuongFlash * $giaFlash, 2);

                if ($loaiLoi === 'CUSTOMER') {
                    $shipperHao = 0.0;
                } else {
                    $shipperHao = round($haoHut * self::TY_LE_SHIPPER_CHIU_HAO_HUT, 2);
                }

                // Thời gian kết thúc Flash Sale: tuân thủ HSD thực tế của hoa
                $fsEnd = now()->addDays(\App\Services\SystemConfigService::getInt('expiry_warning_days', 2));
                if ($hsd && $fsEnd->gt($hsd)) {
                    $fsEnd = $hsd;
                }
                if (!empty($in['ket_thuc'])) {
                    $customEnd = \Carbon\Carbon::parse($in['ket_thuc']);
                    if ($hsd && $customEnd->gt($hsd)) {
                        $this->nem($key . '.ket_thuc', "Thời gian kết thúc Flash Sale không được vượt quá hạn sử dụng ({$lot->ngay_het_han}).");
                    }
                    $fsEnd = $customEnd;
                }

                // Hồi tồn kho vật lý để phục vụ quota reservation khi khách đặt đơn Flash Sale
                if ($ct->ma_san_pham) {
                    if ($maLo) {
                        ChiTietNhap::whereKey($maLo)->lockForUpdate()->increment('so_luong_con_lai', $soLuongFlash);
                    }
                    SanPham::whereKey($ct->ma_san_pham)->lockForUpdate()->increment('so_luong', $soLuongFlash);
                }
                if ($ct->ma_bo_hoa) {
                    $bo = BoHoa::whereKey($ct->ma_bo_hoa)->lockForUpdate()->first();
                    if ($bo) {
                        $bo->update([
                            'ma_hoa_don'    => null,
                            'ma_khach_hang' => null,
                            'loi_nhan'      => null,
                            'trang_thai'    => 'ACTIVE',
                            'so_luong'      => $soLuongFlash,
                        ]);
                    }
                }

                FlashSale::create([
                    'ma_san_pham'           => $ct->ma_san_pham,
                    'ma_chi_tiet_nhap'      => $maLo,
                    'ma_bo_hoa'             => $ct->ma_bo_hoa,
                    'ma_chi_tiet_hang_hoan' => $ct->ma_chi_tiet_hang_hoan,
                    'gia_goc'               => $giaBan,
                    'gia_flash_sale'        => $giaFlash,
                    'so_tien_giam'          => $soTienGiam,
                    'ti_le_giam'            => $tiLeGiam,
                    'so_luong'              => $soLuongFlash,
                    'so_luong_da_ban'       => 0,
                    'bat_dau'               => now(),
                    'ket_thuc'              => $fsEnd,
                    'trang_thai'            => 'DANG_BAN',
                    'nguon'                 => 'HANG_HOAN',
                    'nguoi_tao'             => Auth::id(),
                ]);
            }

            // ═════════════════════════════════════════════════════════════════
            // 3. XỬ LÝ NHÁNH HỎNG HOÀN TOÀN (BÁO HỎNG - KHÔNG RESTOCK)
            // ═════════════════════════════════════════════════════════════════
            if ($soLuongHong > 0) {
                if (blank($in['ly_do_hong'] ?? null)) {
                    $this->nem($key . '.ly_do_hong', 'Vui lòng nhập lý do hỏng cho các sản phẩm báo hỏng.');
                }
                if (empty($in['ma_nhan_vien'])) {
                    $this->nem($key . '.ma_nhan_vien', 'Vui lòng chọn nhân viên báo cáo cho các sản phẩm báo hỏng.');
                }

                $thietHai = round($soLuongHong * $giaBan, 2);
                if ($loaiLoi === 'CUSTOMER') {
                    $shipperHong = 0.0;
                } else {
                    $shipperHong = round($thietHai * self::TY_LE_SHIPPER_CHIU_HANG_HONG, 2);
                }

                if ($ct->ma_bo_hoa && $soLuongTot === 0 && $soLuongFlash === 0) {
                    $bo = BoHoa::whereKey($ct->ma_bo_hoa)->lockForUpdate()->first();
                    if ($bo) {
                        $bo->update(['trang_thai' => 'DRAFT', 'so_luong' => 0]);
                    }
                }

                $this->ghiNhanBaoCaoHangHong($ct, $soLuongHong, $in, $maHD);
            }

            $heThongHao  = round($haoHut - $shipperHao, 2);
            $heThongHong = round($thietHai - $shipperHong, 2);

            // Xác định kết quả kiểm tra chất lượng (CON_TOT, FLASH_SALE, HONG)
            $ketQuaDauCuoi = 'CON_TOT';
            if ($soLuongFlash > 0) {
                $ketQuaDauCuoi = 'FLASH_SALE';
            } elseif ($soLuongHong > 0 && $soLuongTot === 0) {
                $ketQuaDauCuoi = 'HONG';
            }

            $ct->update([
                'so_luong_hong'               => $soLuongHong,
                'so_luong_con_ban_duoc'       => $soLuongTot + $soLuongFlash,
                'so_luong_con_lai_flash_sale' => 0,
                'gia_flash_sale_snapshot'     => $soLuongFlash > 0 ? $giaFlash : null,
                'doanh_thu_thanh_ly'          => $doanhThu,
                'tien_thiet_hai_hang_hong'    => $thietHai,
                'tien_hao_hut_flash_sale'     => $haoHut,
                'tien_he_thong_chiu'          => $heThongHong + $heThongHao,
                'tien_shipper_chiu'           => $shipperHong + $shipperHao,
                'mien_phat_shipper'           => ($loaiLoi === 'CUSTOMER'),
                'ket_qua'                     => $ketQuaDauCuoi,
                'ghi_chu'                     => $in['ghi_chu'] ?? ($soLuongHong > 0 ? ($in['ly_do_hong'] ?? '') : 'Kiểm tra hàng hoàn tất'),
            ]);

            $tongThietHai    += $thietHai;
            $tongHaoHut      += $haoHut;
            $tongShipperHong += $shipperHong;
            $tongShipperHao  += $shipperHao;
            $tongShipper     += ($shipperHong + $shipperHao);
            $tongHeThong     += ($heThongHong + $heThongHao);
        }

        $bayGio = now();

        $hangHoan->update([
            'trang_thai'             => 'DA_KIEM_TRA',
            'nguoi_kiem_tra'         => Auth::id(),
            'thoi_diem_nhan_tai_kho' => $hangHoan->thoi_diem_nhan_tai_kho ?? $bayGio,
            'thoi_diem_kiem_tra'     => $bayGio,
            'ghi_chu'                => $request->filled('ghi_chu') ? $request->input('ghi_chu') : $hangHoan->ghi_chu,
        ]);

        $ghtb->update(['trang_thai' => 'DA_XU_LY']);

        // ═══════════════════════════════════════════════════════════════
        // ── Lỗi SHIPPER:
        //    (1) Bồi thường cố định 20 điểm cho khách + phạt shipper 20k
        //    (2) Phạt hàng hỏng / hao hụt Flash Sale (nếu có)
        // Chạy 1 lần duy nhất cho mỗi giao_hang_that_bai (idempotent).
        // ═══════════════════════════════════════════════════════════════
        if ($loaiLoi === 'SHIPPER') {
            $maShipper = $ghtb->ma_nhan_vien_giao ?: $hoaDon->ma_nhan_vien_giao;
            if (!$maShipper) {
                $this->nem('hoa_don', 'Không xác định được shipper để lập khoản bồi thường/phạt.');
            }

            // ── (1) BỒI THƯỜNG 20 ĐIỂM + PHẠT SHIPPER 20K ──
            $daCoBoiThuong = PhatShipper::where('ma_giao_hang_that_bai', $ghtb->ma_giao_hang_that_bai)
                ->where('ly_do', 'like', '%bồi thường 20 điểm%')
                ->exists();

            if (!$daCoBoiThuong) {
                $diemBoiThuong = \App\Services\SystemConfigService::getInt('compensation_points', self::DIEM_BOI_THUONG_KHACH);

                // Cộng điểm bồi thường cho khách
                if ($hoaDon->ma_khach_hang) {
                    $khach = KhachHang::whereKey($hoaDon->ma_khach_hang)->lockForUpdate()->first();
                    if ($khach) {
                        $khach->increment('diem_tich_luy', $diemBoiThuong);
                    }
                }

                // Tạo phiếu phạt 20k RIÊNG (không gộp với phạt hàng hỏng/hao hụt)
                PhatShipper::create([
                    'ma_nhan_vien'            => $maShipper,
                    'ma_hoa_don'              => $hoaDon->ma_hoa_don,
                    'ma_giao_hang_that_bai'   => $ghtb->ma_giao_hang_that_bai,
                    'so_tien'                 => self::TIEN_PHAT_BOI_THUONG,
                    'tien_hang_hong'          => 0,
                    'tien_hao_hut_flash_sale' => 0,
                    'ly_do'                   => "Hủy đơn do lỗi shipper - bồi thường {$diemBoiThuong} điểm cho khách (đơn {$maHD}).",
                    'trang_thai'              => 'CHUA_THU',
                ]);

                // Cộng dồn vào nhan_vien.tien_phat (giống phạt giao trễ 15k)
                NhanVien::whereKey($maShipper)->increment('tien_phat', self::TIEN_PHAT_BOI_THUONG);

                // Gửi thông báo cho khách hàng
                if ($hoaDon->ma_khach_hang) {
                    $hoanTienText = $hoaDon->trang_thai_thanh_toan === 'DA_THANH_TOAN'
                        ? ' Vì đơn hàng đã được thanh toán, hệ thống sẽ xử lý hoàn tiền cho bạn sau.'
                        : '';
                    ThongBao::gui(
                        (int) $hoaDon->ma_khach_hang,
                        'apology',
                        "🌸 Xin lỗi vì sự bất tiện! Đơn hàng {$maHD} bị hủy do sự cố phía shipper. "
                            . "Chúng tôi đã cộng " . $diemBoiThuong . " điểm bồi thường vào tài khoản của bạn."
                            . $hoanTienText,
                        (int) $hoaDon->ma_hoa_don
                    );
                }
            }

            // ── (2) PHẠT HÀNG HỎNG / HAO HỤT FLASH SALE ──
            if ($tongShipper > 0) {
                // Kiểm tra riêng, loại trừ phiếu bồi thường 20 điểm ở trên
                $daCoPhatHangHong = PhatShipper::where('ma_giao_hang_that_bai', $ghtb->ma_giao_hang_that_bai)
                    ->where('ly_do', 'not like', '%bồi thường 20 điểm%')
                    ->exists();

                if (!$daCoPhatHangHong) {
                    PhatShipper::create([
                        'ma_nhan_vien'            => $maShipper,
                        'ma_hoa_don'              => $hoaDon->ma_hoa_don,
                        'ma_giao_hang_that_bai'   => $ghtb->ma_giao_hang_that_bai,
                        'so_tien'                 => round($tongShipper, 2),
                        'tien_hang_hong'          => round($tongShipperHong, 2),
                        'tien_hao_hut_flash_sale' => round($tongShipperHao, 2),
                        'ly_do'                   => "Giao hàng thất bại do shipper (đơn {$maHD}). Quyết toán sau kiểm tra hàng hoàn.",
                        'trang_thai'              => 'CHUA_THU',
                    ]);

                    // Cộng vào nhan_vien.tien_phat
                    NhanVien::whereKey($maShipper)->increment('tien_phat', round($tongShipper, 2));
                }
            }
        }

        // ── Lỗi CUSTOMER: ghi vi phạm khách hàng (shipper không bị phạt) ──
        // Đơn đã thanh toán trước (VNPay) bị hủy do khách → KHÔNG tích vi phạm.
        if ($loaiLoi === 'CUSTOMER' && $hoaDon->ma_khach_hang
            && $hoaDon->trang_thai_thanh_toan !== 'DA_THANH_TOAN') {
            $daCoViPham = ViPhamKhachHang::where('ma_giao_hang_that_bai', $ghtb->ma_giao_hang_that_bai)->exists();
            if (!$daCoViPham) {
                // Chỉ đếm vi phạm CHƯA GỠ (da_go = false)
                $lan = ViPhamKhachHang::where('ma_khach_hang', $hoaDon->ma_khach_hang)
                    ->where('da_go', false)
                    ->count() + 1;
                ViPhamKhachHang::create([
                    'ma_khach_hang'         => $hoaDon->ma_khach_hang,
                    'ma_hoa_don'            => $hoaDon->ma_hoa_don,
                    'ma_giao_hang_that_bai' => $ghtb->ma_giao_hang_that_bai,
                    'ly_do'                 => $ghtb->ly_do ?: "Không nhận hàng / không liên lạc được (đơn {$maHD}).",
                    'lan_vi_pham'           => $lan,
                    'han_che_ap_dung'       => $this->capDoHanChe($lan),
                ]);
            }

            // Cập nhật lại quyền COD dựa trên tổng vi phạm chưa gỡ (tính cả bản ghi vừa tạo
            // hoặc bản ghi đã được backend Node tạo sẵn lúc finalize). Đặt NGOÀI khối
            // `if (!$daCoViPham)` để vẫn chạy khi vi phạm đã tồn tại trước đó.
            $tongViPham = ViPhamKhachHang::where('ma_khach_hang', $hoaDon->ma_khach_hang)
                ->where('da_go', false)
                ->count();

            if ($tongViPham >= 2) {
                KhachHang::whereKey($hoaDon->ma_khach_hang)->update(['quyen_cod' => 'GIOI_HAN']);
            }
        }

        return [
            'loai_loi'                 => $loaiLoi,
            'tong_thiet_hai_hang_hong' => round($tongThietHai, 2),
            'tong_hao_hut_flash_sale'  => round($tongHaoHut, 2),
            'tong_he_thong_chiu'       => round($tongHeThong, 2),
            'tong_shipper_chiu'        => round($tongShipper, 2),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────
    private function loiDieuKienDon(HoaDon $hoaDon): ?string
    {
        if ($hoaDon->trang_thai !== 'CANCELLED') {
            return 'Chỉ kiểm tra hàng hoàn cho đơn có trạng thái CANCELLED.';
        }
        if (!$hoaDon->co_hang_hoan) {
            return 'Đơn này không có hàng hoàn cần kiểm tra.';
        }
        return null;
    }

    private function capDoHanChe(int $lanViPham): string
    {
        return match (true) {
            $lanViPham <= 1  => 'CANH_BAO',
            $lanViPham === 2 => 'CAM_COD',
            default          => 'TAM_KHOA_MUA_HANG',
        };
    }

    /**
     * Ghi log hàng hỏng theo đúng lô đã xuất; không trừ lô lần hai.
     * Thứ tự truy lô: chi_tiet_hoa_don → inventory_reservations → lỗi.
     */
    private function ghiNhanBaoCaoHangHong(ChiTietHangHoan $ct, int $soLuongHong, array $in, string $maHD): void
    {
        $giaGoc = (float) $ct->gia_ban_snapshot;
        $maHoaDon = $ct->hangHoan?->ma_hoa_don;

        $duLieuChung = [
            'ma_nhan_vien'      => (int) $in['ma_nhan_vien'],
            'ly_do'             => trim((string) $in['ly_do_hong']),
            'thoi_gian_bao_cao' => now(),
            'nguon_hong'        => 'HANG_HOAN',
            'ma_hoa_don'        => $maHoaDon,
            'gia_tri_thiet_hai' => round($soLuongHong * $giaGoc, 2),
        ];

        // ── Sản phẩm lẻ ──
        if ($ct->ma_san_pham) {
            $maLo = $ct->chiTietHoaDon?->ma_chi_tiet_nhap;

            // Fallback 1: truy từ reservation của hóa đơn gốc
            if (! $maLo && $ct->hangHoan?->ma_hoa_don) {
                $maLo = InventoryReservation::where('ma_hoa_don', $ct->hangHoan->ma_hoa_don)
                    ->where('ma_san_pham', $ct->ma_san_pham)
                    ->whereNotNull('ma_chi_tiet_nhap')
                    ->orderByDesc('id')
                    ->value('ma_chi_tiet_nhap');
            }

            if (! $maLo) {
                $this->nem('items.' . $ct->ma_chi_tiet_hang_hoan,
                    'Không truy được lô đã xuất của sản phẩm hàng hoàn.');
            }

            BaoCaoHangHong::create($duLieuChung + [
                'ma_san_pham'      => $ct->ma_san_pham,
                'ma_chi_tiet_nhap' => $maLo,
                'so_luong_hong'    => $soLuongHong,
                'ghi_chu'          => "Hàng hoàn {$maHD}; dòng #{$ct->ma_chi_tiet_hang_hoan}.",
            ]);
            return;
        }

        // ── Bó hoa: ghi trực tiếp bó hoa thành phẩm hỏng ──
        if ($ct->ma_bo_hoa) {
            BaoCaoHangHong::create($duLieuChung + [
                'ma_san_pham'      => null,
                'ma_bo_hoa'        => $ct->ma_bo_hoa,
                'so_luong_hong'    => $soLuongHong,
                'ghi_chu'          => "Hàng hoàn {$maHD}; bó hoa thành phẩm #{$ct->ma_bo_hoa}, dòng #{$ct->ma_chi_tiet_hang_hoan}.",
            ]);
            return;
        }

        $this->nem('items.' . $ct->ma_chi_tiet_hang_hoan, 'Không xác định được loại mặt hàng hoàn để báo hỏng.');
    }


    private function moTaDong(ChiTietHangHoan $ct): array
    {
        $laBo = !empty($ct->ma_bo_hoa);

        if ($laBo) {
            $ten = $ct->boHoa?->ten_bo_hoa ?? 'Bó hoa (đã xóa)';
            $anh = asset($ct->boHoa?->hinh_anh ?? 'img/default.jpg');
        } else {
            $ten = $ct->sanPham?->ten_san_pham ?? 'Sản phẩm (đã xóa)';
            $anh = $ct->sanPham?->anh ?? asset('img/default.jpg');
        }

        $giaBan = (float) $ct->gia_ban_snapshot;
        $giaFlashDeXuat = (float) $ct->gia_flash_sale_snapshot > 0
            ? (float) $ct->gia_flash_sale_snapshot
            : round($giaBan * (1 - self::TY_LE_GIAM_FLASH_SALE), 2);

        $fs = FlashSale::where('ma_chi_tiet_hang_hoan', $ct->ma_chi_tiet_hang_hoan)->first();

        return [
            'ma_chi_tiet_hang_hoan'  => $ct->ma_chi_tiet_hang_hoan,
            'la_bo_hoa'              => $laBo,
            'ten'                    => $ten,
            'anh'                    => $anh,
            'so_luong_theo_don'      => (int) $ct->so_luong_theo_don,
            'so_luong_hong'          => (int) $ct->so_luong_hong,
            'so_luong_con_ban_duoc'  => (int) $ct->so_luong_con_ban_duoc,
            'gia_von'                => (float) $ct->gia_von_snapshot,
            'gia_ban'                => $giaBan,
            'gia_flash_sale_de_xuat' => $giaFlashDeXuat,
            'ti_le_giam'             => $fs ? $fs->ti_le_giam : null,
            'ket_qua'                => $ct->ket_qua,
            'ghi_chu'                => $ct->ghi_chu,
        ];
    }
    private function danhSachNhanVienBaoCao()
{
    return NhanVien::where(function ($q) {
            $q->whereDoesntHave('nguoiDung')
              ->orWhereHas('nguoiDung', fn ($sub) => $sub->where('vai_tro', '!=', 'SHIPPER'));
        })
        ->orderBy('ten_nhan_vien')
        ->get(['ma_nhan_vien', 'ten_nhan_vien']);
}
    private function nem(string $truong, string $thongBao): void
    {
        throw ValidationException::withMessages([$truong => [$thongBao]]);
    }

    private function phanHoiLoi(string $thongBao, int $status)
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $thongBao], $status);
        }
        return redirect()->route('hoa-don.index')->with('error', $thongBao);
    }
}