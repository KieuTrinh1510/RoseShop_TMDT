<?php

namespace App\Services;

use App\Models\ChiTietHangHoan;
use App\Models\FlashSale;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FlashSaleService
{
    // ─────────────────────────────────────────────────────────────────────────
    // 1. TẠO FLASH SALE TỪ CHI TIẾT HÀNG HOÀN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tạo bản ghi Flash Sale từ một dòng chi_tiet_hang_hoan.
     *
     * @param  int    $maChiTietHangHoan
     * @param  float  $giaFlashSale          Giá admin nhập (hoặc gợi ý tự động)
     * @param  Carbon $batDau
     * @param  Carbon $ketThuc
     * @param  string $trangThai             'NHAP' (mặc định) hoặc 'DANG_BAN'
     * @return FlashSale
     * @throws \Exception
     */
    public function taoFlashSale(
        int    $maChiTietHangHoan,
        float  $giaFlashSale,
        Carbon $batDau,
        Carbon $ketThuc,
        string $trangThai = 'NHAP'
    ): FlashSale {
        return DB::transaction(function () use (
            $maChiTietHangHoan, $giaFlashSale, $batDau, $ketThuc, $trangThai
        ) {
            // Lock để tránh tạo vượt số lượng khi có request đồng thời
            $ct = ChiTietHangHoan::lockForUpdate()->find($maChiTietHangHoan);

            if (! $ct) {
                throw new \Exception("Không tìm thấy chi tiết hàng hoàn #{$maChiTietHangHoan}.");
            }

            if ($ct->ket_qua !== 'FLASH_SALE') {
                throw new \Exception(
                    "Dòng #{$maChiTietHangHoan} không có ket_qua = FLASH_SALE "
                    . "(hiện tại: {$ct->ket_qua})."
                );
            }

            if ($ct->so_luong_con_ban_duoc <= 0) {
                throw new \Exception("Dòng #{$maChiTietHangHoan}: không có hàng còn bán được.");
            }

            if ($ct->so_luong_con_lai_flash_sale <= 0) {
                throw new \Exception("Dòng #{$maChiTietHangHoan}: số lượng Flash Sale đã hết.");
            }

            // ── Validate giá ──
            $giaGoc = (float) $ct->gia_ban_snapshot;
            $this->validateGiaFlashSale($giaFlashSale, $giaGoc);

            $soTienGiam = $giaGoc - $giaFlashSale;

            // ── Tạo Flash Sale ──
            $flashSale = FlashSale::create([
                'ma_san_pham'            => $ct->ma_san_pham,
                'ma_bo_hoa'              => $ct->ma_bo_hoa,
                'ma_chi_tiet_hang_hoan'  => $ct->ma_chi_tiet_hang_hoan,
                'gia_goc'                => $giaGoc,
                'gia_flash_sale'         => $giaFlashSale,
                'so_tien_giam'           => $soTienGiam,
                'so_luong'               => $ct->so_luong_con_lai_flash_sale,
                'so_luong_da_ban'        => 0,
                'bat_dau'                => $batDau,
                'ket_thuc'               => $ketThuc,
                'trang_thai'             => $trangThai,
            ]);

            // ── Cập nhật snapshot giá trên hàng hoàn ──
            $ct->update(['gia_flash_sale_snapshot' => $giaFlashSale]);

            // ── Cập nhật tiền hao hụt qua KiemTraHangHoanService ──
            app(KiemTraHangHoanService::class)
                ->capNhatTienHaoHutFlashSale($maChiTietHangHoan, $giaFlashSale);

            return $flashSale;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. GỢI Ý GIÁ FLASH SALE (giảm 40%)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Trả về giá gợi ý = gia_ban_snapshot * 0.6.
     *
     * @param  int  $maChiTietHangHoan
     * @return float
     * @throws \Exception
     */
    public function giaGoiY(int $maChiTietHangHoan): float
    {
        $ct = ChiTietHangHoan::find($maChiTietHangHoan);

        if (! $ct) {
            throw new \Exception("Không tìm thấy chi tiết hàng hoàn #{$maChiTietHangHoan}.");
        }

        return round((float) $ct->gia_ban_snapshot * 0.6, 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. THEO DÕI BÁN HÀNG (gọi khi có đơn mua Flash Sale)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cập nhật số lượng đã bán, giảm tồn kho Flash Sale và hàng hoàn.
     * Tự chuyển trang_thai = HET_HANG khi hết.
     *
     * @param  int  $maFlashSale
     * @param  int  $soLuongMua
     * @return FlashSale
     * @throws \Exception
     */
    public function ghiNhanBan(int $maFlashSale, int $soLuongMua): FlashSale
    {
        if ($soLuongMua <= 0) {
            throw new \Exception("Số lượng mua phải lớn hơn 0.");
        }

        return DB::transaction(function () use ($maFlashSale, $soLuongMua) {

            $fs = FlashSale::lockForUpdate()->find($maFlashSale);

            if (! $fs) {
                throw new \Exception("Không tìm thấy Flash Sale #{$maFlashSale}.");
            }

            if ($fs->trang_thai !== 'DANG_BAN') {
                throw new \Exception("Flash Sale #{$maFlashSale} không ở trạng thái DANG_BAN.");
            }

            $conLai = $fs->so_luong - $fs->so_luong_da_ban;
            if ($soLuongMua > $conLai) {
                throw new \Exception(
                    "Flash Sale #{$maFlashSale}: chỉ còn {$conLai} sản phẩm, "
                    . "không đủ {$soLuongMua}."
                );
            }

            $fs->increment('so_luong_da_ban', $soLuongMua);
            $fs->refresh();

            $conLaiSauBan = $fs->so_luong - $fs->so_luong_da_ban;

            if ($conLaiSauBan <= 0) {
                $fs->update(['trang_thai' => 'HET_HANG']);
            }

            // ── Giảm so_luong_con_lai_flash_sale trên hàng hoàn ──
            if ($fs->ma_chi_tiet_hang_hoan) {
                $ct = ChiTietHangHoan::lockForUpdate()->find($fs->ma_chi_tiet_hang_hoan);
                if ($ct) {
                    $ct->decrement('so_luong_con_lai_flash_sale', $soLuongMua);
                }
            }

            return $fs->fresh();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. KẾT THÚC FLASH SALE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Chuyển trang_thai = KET_THUC và đánh dấu dòng hàng hoàn là DA_XU_LY.
     * Không xóa dữ liệu lịch sử.
     *
     * @param  int  $maFlashSale
     * @return FlashSale
     * @throws \Exception
     */
    public function ketThucFlashSale(int $maFlashSale): FlashSale
    {
        return DB::transaction(function () use ($maFlashSale) {

            $fs = FlashSale::find($maFlashSale);

            if (! $fs) {
                throw new \Exception("Không tìm thấy Flash Sale #{$maFlashSale}.");
            }

            if (in_array($fs->trang_thai, ['KET_THUC', 'AN'])) {
                throw new \Exception("Flash Sale #{$maFlashSale} đã kết thúc hoặc bị ẩn.");
            }

            $fs->update(['trang_thai' => 'KET_THUC']);

            // Đánh dấu dòng hàng hoàn đã xử lý xong
            if ($fs->ma_chi_tiet_hang_hoan) {
                app(KiemTraHangHoanService::class)
                    ->danhDauDaXuLy($fs->ma_chi_tiet_hang_hoan);
            }

            return $fs->fresh();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. ẨN FLASH SALE (không xóa)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ẩn Flash Sale. Không xóa, không ảnh hưởng lịch sử giao dịch.
     * Chỉ được ẩn Flash Sale chưa có giao dịch.
     *
     * @param  int  $maFlashSale
     * @return FlashSale
     * @throws \Exception
     */
    public function anFlashSale(int $maFlashSale): FlashSale
    {
        $fs = FlashSale::find($maFlashSale);

        if (! $fs) {
            throw new \Exception("Không tìm thấy Flash Sale #{$maFlashSale}.");
        }

        if ($fs->so_luong_da_ban > 0) {
            throw new \Exception(
                "Flash Sale #{$maFlashSale} đã có giao dịch, không thể ẩn."
            );
        }

        $fs->update(['trang_thai' => 'AN']);
        return $fs->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kiểm tra ràng buộc giá Flash Sale.
     *
     * @throws \Exception
     */
    private function validateGiaFlashSale(float $giaFlashSale, float $giaGoc): void
    {
        if ($giaFlashSale < 0) {
            throw new \Exception("Giá Flash Sale không được nhỏ hơn 0.");
        }

        if ($giaFlashSale > $giaGoc) {
            throw new \Exception(
                "Giá Flash Sale ({$giaFlashSale}) không được lớn hơn giá gốc ({$giaGoc})."
            );
        }
    }
}