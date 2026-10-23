<?php

namespace App\Http\Controllers\Admin\HoanHang;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoHangHong;
use App\Models\ChiTietHangHoan;
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
            'items.*.so_luong_hong'         => ['nullable', 'integer', 'min:0'],
            'items.*.so_luong_con_ban_duoc' => ['nullable', 'integer', 'min:0'],
            'items.*.xa_bo'                 => ['nullable', 'boolean'],
            'items.*.ly_do_hong'            => ['nullable', 'string', 'max:255'],
            'items.*.ma_nhan_vien'          => ['nullable', 'integer', 'exists:nhan_vien,ma_nhan_vien'],
            'items.*.ghi_chu'               => ['nullable', 'string', 'max:500'],
            'ghi_chu'                       => ['nullable', 'string', 'max:1000'],
        ], [
            'items.required'                        => 'Vui lòng nhập kết quả kiểm tra cho từng sản phẩm.',
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

        // Sau khi lưu → quay về danh sách hóa đơn (không dùng back() để tránh giữ state form)
        return redirect()->route('hoa-don.index')->with('success', $message);
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
        if ($hangHoan->trang_thai === 'DA_KIEM_TRA') {
            $this->nem('hoa_don', 'Hàng hoàn của đơn này đã được kiểm tra, không thể kiểm tra lại.');
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

        // Bắt buộc gửi đủ và đúng tất cả các dòng của phiếu
        $items  = (array) $request->input('items');
        $idsDon = $chiTiets->pluck('ma_chi_tiet_hang_hoan')->map(fn ($v) => (int) $v)->sort()->values()->all();
        $idsGui = collect(array_keys($items))->map(fn ($v) => (int) $v)->sort()->values()->all();
        if ($idsDon !== $idsGui) {
            $this->nem('items', 'Dữ liệu kiểm tra phải đủ và đúng các sản phẩm của phiếu hàng hoàn.');
        }

        $tongThietHai    = $tongHaoHut = 0.0;
        $tongShipperHong = $tongShipperHao = 0.0;
        $tongShipper     = $tongHeThong = 0.0;
        $maHD = '#HD-' . str_pad((string) $hoaDon->ma_hoa_don, 4, '0', STR_PAD_LEFT);

        foreach ($chiTiets as $ct) {
            $in      = $items[$ct->ma_chi_tiet_hang_hoan] ?? [];
            $key     = 'items.' . $ct->ma_chi_tiet_hang_hoan;
            $theoDon = (int) $ct->so_luong_theo_don;
            $laBo    = !empty($ct->ma_bo_hoa);
            $xaBo    = filter_var($in['xa_bo'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($xaBo && !$laBo) {
                $this->nem($key . '.xa_bo', 'Chỉ bó hoa mới có thể xả bó.');
            }

            if ($xaBo) {
                $hong = $theoDon;
                $ban  = 0;
            } else {
                $hong = (int) ($in['so_luong_hong'] ?? 0);
                $ban  = (int) ($in['so_luong_con_ban_duoc'] ?? 0);
            }

            if ($hong + $ban > $theoDon) {
                $this->nem($key, "Tổng số lượng hỏng + còn bán được ({$hong} + {$ban}) vượt quá số lượng thực tế ({$theoDon}).");
            }
            if ($hong + $ban === 0) {
                $this->nem($key, 'Mỗi sản phẩm phải có ít nhất 1 đơn vị hỏng hoặc còn bán được.');
            }
            if ($hong > 0 && blank($in['ly_do_hong'] ?? null)) {
                $this->nem($key . '.ly_do_hong', 'Vui lòng nhập lý do hỏng cho dòng đã xác nhận hỏng.');
            }
            if ($hong > 0 && empty($in['ma_nhan_vien'])) {
                $this->nem($key . '.ma_nhan_vien', 'Vui lòng chọn nhân viên báo cáo cho dòng đã xác nhận hỏng.');
            }

            // ── Tính tiền ──
            $giaBan   = (float) $ct->gia_ban_snapshot;
            $giaFlash = (float) $ct->gia_flash_sale_snapshot;
            if ($giaFlash <= 0 || $giaFlash > $giaBan) {
                $giaFlash = round($giaBan * (1 - self::TY_LE_GIAM_FLASH_SALE), 2);
            }

            $thietHai = round($hong * $giaBan, 2);
            $haoHut   = round($ban * max(0.0, $giaBan - $giaFlash), 2);
            $doanhThu = round($ban * $giaFlash, 2);
            $tong     = $thietHai + $haoHut;

            if ($loaiLoi === 'CUSTOMER') {
                $shipperHong = 0.0;
                $shipperHao  = 0.0;
                $mienPhat    = true;
            } else {
                $shipperHong = round($thietHai * self::TY_LE_SHIPPER_CHIU_HANG_HONG, 2);
                $shipperHao  = round($haoHut * self::TY_LE_SHIPPER_CHIU_HAO_HUT, 2);
                $mienPhat    = false;
            }
            $shipperChiu = round($shipperHong + $shipperHao, 2);
            $heThongChiu = round($tong - $shipperChiu, 2);

            $ct->update([
                'so_luong_hong'               => $hong,
                'so_luong_con_ban_duoc'       => $ban,
                'so_luong_con_lai_flash_sale' => $ban,
                'gia_flash_sale_snapshot'     => $giaFlash,
                'doanh_thu_thanh_ly'          => $doanhThu,
                'tien_thiet_hai_hang_hong'    => $thietHai,
                'tien_hao_hut_flash_sale'     => $haoHut,
                'tien_he_thong_chiu'          => $heThongChiu,
                'tien_shipper_chiu'           => $shipperChiu,
                'mien_phat_shipper'           => $mienPhat,
                'ket_qua'                     => $ban > 0 ? 'FLASH_SALE' : 'HONG',
                'ghi_chu'                     => $hong > 0
                    ? trim('Lý do hỏng: ' . ($in['ly_do_hong'] ?? '') . (($in['ghi_chu'] ?? '') !== '' ? ' — ' . $in['ghi_chu'] : ''))
                    : ($in['ghi_chu'] ?? $ct->ghi_chu),
            ]);

            if ($hong > 0) {
                $this->ghiNhanBaoCaoHangHong($ct, $hong, $in, $maHD);
            }
                                    if ($ban > 0) {
                if ($ct->ma_san_pham) {
                    $maLo = $ct->chiTietHoaDon?->ma_chi_tiet_nhap;
                    if (!$maLo && $ct->hangHoan?->ma_hoa_don) {
                        $maLo = InventoryReservation::where('ma_hoa_don', $ct->hangHoan->ma_hoa_don)
                            ->where('ma_san_pham', $ct->ma_san_pham)
                            ->whereNotNull('ma_chi_tiet_nhap')
                            ->orderByDesc('id')
                            ->value('ma_chi_tiet_nhap');
                    }
                    if ($maLo) {
                        ChiTietNhap::whereKey($maLo)->lockForUpdate()->increment('so_luong_con_lai', $ban);
                    }
                    SanPham::whereKey($ct->ma_san_pham)->lockForUpdate()->increment('so_luong', $ban);
                }

                // Bó hoa: reset bó về trạng thái bán được như bình thường
                if ($ct->ma_bo_hoa) {
                    $bo = BoHoa::whereKey($ct->ma_bo_hoa)->lockForUpdate()->first();
                    if ($bo) {
                        $bo->update([
                            'ma_hoa_don'    => null,
                            'ma_khach_hang' => null,
                            'loi_nhan'      => null,
                            'trang_thai'    => 'ACTIVE',
                            'so_luong'      => $ban,
                        ]);
                    }
                }
            }

            $tongThietHai    += $thietHai;
            $tongHaoHut      += $haoHut;
            $tongShipperHong += $shipperHong;
            $tongShipperHao  += $shipperHao;
            $tongShipper     += $shipperChiu;
            $tongHeThong     += $heThongChiu;
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
                // Cộng 20 điểm cho khách
                if ($hoaDon->ma_khach_hang) {
                    $khach = KhachHang::whereKey($hoaDon->ma_khach_hang)->lockForUpdate()->first();
                    if ($khach) {
                        $khach->increment('diem_tich_luy', self::DIEM_BOI_THUONG_KHACH);
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
                    'ly_do'                   => "Hủy đơn do lỗi shipper - bồi thường 20 điểm cho khách (đơn {$maHD}).",
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
                            . "Chúng tôi đã cộng " . self::DIEM_BOI_THUONG_KHACH . " điểm bồi thường vào tài khoản của bạn."
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
        $duLieuChung = [
            'ma_nhan_vien'      => (int) $in['ma_nhan_vien'],
            'ly_do'             => trim((string) $in['ly_do_hong']),
            'thoi_gian_bao_cao' => now(),
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

        // ── Bó hoa: ghi từng cấu phần theo lô của chi_tiet_bo_hoa ──
        $daGhiNhan = false;
        foreach ($ct->boHoa?->chiTietBoHoa ?? [] as $thanhPhan) {
            if (! $thanhPhan->ma_san_pham || ! $thanhPhan->ma_chi_tiet_nhap) {
                continue;
            }
            BaoCaoHangHong::create($duLieuChung + [
                'ma_san_pham'      => $thanhPhan->ma_san_pham,
                'ma_chi_tiet_nhap' => $thanhPhan->ma_chi_tiet_nhap,
                'so_luong_hong'    => (int) $thanhPhan->so_luong * $soLuongHong,
                'ghi_chu'          => "Hàng hoàn {$maHD}; bó hoa #{$ct->ma_bo_hoa}, dòng #{$ct->ma_chi_tiet_hang_hoan}.",
            ]);
            $daGhiNhan = true;
        }

        if (! $daGhiNhan) {
            $this->nem('items.' . $ct->ma_chi_tiet_hang_hoan,
                'Không truy được cấu phần/lô gốc của bó hoa hàng hoàn.');
        }
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