<?php

namespace App\Services;

use App\Models\HoaDon;
use App\Models\HangHoan;
use App\Models\ChiTietHangHoan;
use App\Models\PhatShipper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class KiemTraHangHoanService
{
    // ─────────────────────────────────────────────────────────────────────────
    // 1. LẤY DỮ LIỆU KIỂM TRA
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Trả về dữ liệu để admin điền kết quả kiểm tra từng dòng hàng hoàn.
     *
     * @param  int  $maHoaDon
     * @return array{hang_hoan: HangHoan, dong: array, nguon_huy: string}
     * @throws \Exception
     */
    public function layDuLieuKiemTra(int $maHoaDon): array
    {
        $hoaDon = HoaDon::with([
            'chiTietHoaDon.sanPham',
            'chiTietHoaDon.chiTietNhap',
        ])->find($maHoaDon);

        if (! $hoaDon) {
            throw new \Exception("Không tìm thấy hóa đơn #{$maHoaDon}.");
        }

        if ($hoaDon->trang_thai !== 'CANCELLED') {
            throw new \Exception("Hóa đơn #{$maHoaDon} chưa bị hủy, không thể kiểm tra hàng hoàn.");
        }

        if (! $hoaDon->co_hang_hoan) {
            throw new \Exception("Hóa đơn #{$maHoaDon} không có hàng hoàn.");
        }

        $hangHoan = HangHoan::with('chiTiet.chiTietHoaDon')
            ->where('ma_hoa_don', $maHoaDon)
            ->first();

        if (! $hangHoan) {
            throw new \Exception("Chưa có bản ghi hàng hoàn cho hóa đơn #{$maHoaDon}.");
        }

        $dong = [];
        foreach ($hangHoan->chiTiet as $ct) {
            $chiTietHD = $ct->chiTietHoaDon;

            $dong[] = [
                'ma_chi_tiet_hang_hoan' => $ct->ma_chi_tiet_hang_hoan,
                'ma_san_pham'           => $ct->ma_san_pham,
                'ma_bo_hoa'             => $ct->ma_bo_hoa,
                'ma_chi_tiet_hoa_don'   => $ct->ma_chi_tiet_hoa_don,
                'so_luong_theo_don'     => $ct->so_luong_theo_don,
                'gia_von_snapshot'      => $ct->gia_von_snapshot,
                'gia_ban_snapshot'      => $ct->gia_ban_snapshot,
                'ket_qua_hien_tai'      => $ct->ket_qua,
                'la_bo_hoa'             => ! is_null($ct->ma_bo_hoa),
                // Thông tin hiển thị
                'ten_san_pham'          => $ct->sanPham?->ten_san_pham
                                        ?? $ct->boHoa?->ten_bo_hoa
                                        ?? '—',
            ];
        }

        return [
            'hang_hoan'  => $hangHoan,
            'dong'       => $dong,
            'nguon_huy'  => $hoaDon->nguon_huy,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. LƯU KẾT QUẢ KIỂM TRA
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lưu kết quả kiểm tra từng dòng và tính tiền trách nhiệm.
     *
     * $rows là mảng, mỗi phần tử:
     * [
     *   'ma_chi_tiet_hang_hoan' => int,
     *   'so_luong_hong'         => int,
     *   'so_luong_con_ban_duoc' => int,
     *   'ghi_chu'               => string|null,
     *   'phuong_an'             => 'FLASH_SALE' | 'XA_BO' | 'HONG',
     * ]
     *
     * @param  int    $maHangHoan
     * @param  array  $rows
     * @param  int    $maNguoiKiemTra
     * @return HangHoan
     * @throws \Exception | ValidationException
     */
    public function luuKetQuaKiemTra(int $maHangHoan, array $rows, int $maNguoiKiemTra): HangHoan
    {
        return DB::transaction(function () use ($maHangHoan, $rows, $maNguoiKiemTra) {

            $hangHoan = HangHoan::with(['chiTiet', 'hoaDon'])->find($maHangHoan);

            if (! $hangHoan) {
                throw new \Exception("Không tìm thấy hàng hoàn #{$maHangHoan}.");
            }

            if ($hangHoan->trang_thai === 'DA_KIEM_TRA') {
                throw new \Exception("Hàng hoàn #{$maHangHoan} đã được kiểm tra, không xử lý lại.");
            }

            $hoaDon    = $hangHoan->hoaDon;
            $nguonHuy  = $hoaDon->nguon_huy; // 'SHIPPER' | 'CUSTOMER'

            // Index các dòng hiện có theo PK để tra nhanh
            $chiTietMap = $hangHoan->chiTiet->keyBy('ma_chi_tiet_hang_hoan');

            $tongTienHangHong      = 0;
            $tongTienHaoHutFlash   = 0; // chỉ tính được sau khi có gia_flash_sale

            foreach ($rows as $row) {
                $maChiTiet  = $row['ma_chi_tiet_hang_hoan'];
                $ct         = $chiTietMap->get($maChiTiet);

                if (! $ct) {
                    throw new \Exception("Dòng #{$maChiTiet} không thuộc hàng hoàn #{$maHangHoan}.");
                }

                // Không xử lý lại dòng đã hoàn tất
                if ($ct->ket_qua === 'DA_XU_LY') {
                    throw new \Exception("Dòng #{$maChiTiet} đã DA_XU_LY, không được xử lý lại.");
                }

                // ── Validate số lượng ──
                $soLuongHong      = (int) ($row['so_luong_hong'] ?? 0);
                $soLuongConBanDuoc = (int) ($row['so_luong_con_ban_duoc'] ?? 0);
                $phuongAn         = $row['phuong_an'] ?? 'HONG';

                if ($soLuongHong < 0 || $soLuongConBanDuoc < 0) {
                    throw new \Exception("Dòng #{$maChiTiet}: số lượng không được âm.");
                }

                if (($soLuongHong + $soLuongConBanDuoc) > $ct->so_luong_theo_don) {
                    throw new \Exception(
                        "Dòng #{$maChiTiet}: tổng số lượng ({$soLuongHong} + {$soLuongConBanDuoc}) "
                        . "vượt quá số lượng theo đơn ({$ct->so_luong_theo_don})."
                    );
                }

                // ── Xử lý bó hoa: chọn XẢ BÓ → toàn bộ là HONG ──
                $laBoHoa = ! is_null($ct->ma_bo_hoa);
                if ($laBoHoa && $phuongAn === 'XA_BO') {
                    $soLuongHong       = $ct->so_luong_theo_don;
                    $soLuongConBanDuoc = 0;
                    $phuongAn          = 'HONG'; // không tạo Flash Sale
                }

                // ── Tính tiền ──
                $tienThietHaiHangHong  = 0;
                $tienShipperChiu       = 0;
                $tienHeThongChiu       = 0;
                $mienPhatShipper       = false;
                $ketQua                = 'CHO_KIEM_TRA';

                if ($nguonHuy === 'CUSTOMER') {
                    $mienPhatShipper = true;
                    $tienShipperChiu = 0;

                    if ($soLuongHong > 0) {
                        // Tiền phạt hàng hỏng tính theo giá bán snapshot của đơn,
                        // không tính theo giá vốn nhập kho.
                        $tienThietHaiHangHong = $soLuongHong * (float) $ct->gia_ban_snapshot;
                        $tienHeThongChiu      = $tienThietHaiHangHong;
                    }
                    $ketQua = $soLuongConBanDuoc > 0 ? 'FLASH_SALE' : 'HONG';

                } elseif ($nguonHuy === 'SHIPPER') {
                    $mienPhatShipper = false;

                    if ($soLuongHong > 0) {
                        $tienThietHaiHangHong = $soLuongHong * (float) $ct->gia_ban_snapshot;
                        // Shipper chịu theo chính sách: toàn bộ tiền hàng hỏng
                        $tienShipperChiu = $tienThietHaiHangHong;
                        $tienHeThongChiu = 0;
                    }

                    // Hao hụt Flash Sale tính sau khi có gia_flash_sale_snapshot
                    // (FlashSaleService sẽ cập nhật lại tien_hao_hut_flash_sale,
                    //  tien_he_thong_chiu, tien_shipper_chiu sau khi admin đặt giá)
                    $ketQua = $soLuongConBanDuoc > 0 ? 'FLASH_SALE' : 'HONG';
                }

                // ── Ghi vào DB ──
                $ct->update([
                    'so_luong_hong'              => $soLuongHong,
                    'so_luong_con_ban_duoc'      => $soLuongConBanDuoc,
                    'so_luong_con_lai_flash_sale' => $soLuongConBanDuoc, // khởi tạo = con_ban_duoc
                    'tien_thiet_hai_hang_hong'   => $tienThietHaiHangHong,
                    'tien_shipper_chiu'          => $tienShipperChiu,
                    'tien_he_thong_chiu'         => $tienHeThongChiu,
                    'mien_phat_shipper'          => $mienPhatShipper,
                    'ket_qua'                    => $ketQua,
                    'ghi_chu'                    => $row['ghi_chu'] ?? null,
                ]);

                $tongTienHangHong += $tienThietHaiHangHong;
            }

            // ── Cập nhật trạng thái hàng hoàn ──
            $hangHoan->update([
                'trang_thai'          => 'DA_KIEM_TRA',
                'nguoi_kiem_tra'      => $maNguoiKiemTra,
                'thoi_diem_kiem_tra'  => Carbon::now(),
            ]);

            // ── Tạo phạt shipper (nếu lỗi SHIPPER và có hàng hỏng) ──
            if ($nguonHuy === 'SHIPPER' && $tongTienHangHong > 0) {
                $giaoHangThatBai = $hangHoan->giaoHangThatBai;
                PhatShipper::create([
                    'ma_nhan_vien'          => $hoaDon->ma_nhan_vien_giao,
                    'ma_hoa_don'            => $hoaDon->ma_hoa_don,
                    'ma_giao_hang_that_bai' => $hangHoan->ma_giao_hang_that_bai,
                    'so_tien'               => $tongTienHangHong,
                    'tien_hang_hong'        => $tongTienHangHong,
                    'tien_hao_hut_flash_sale' => 0, // cập nhật sau khi Flash Sale kết thúc
                    'ly_do'                 => 'Hàng hỏng do shipper gây ra khi giao đơn #' . $hoaDon->ma_hoa_don,
                    'trang_thai'            => 'CHUA_THU',
                ]);
            }

            return $hangHoan->fresh('chiTiet');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. CẬP NHẬT TIỀN HAO HỤT FLASH SALE SAU KHI CÓ GIÁ (gọi từ FlashSaleService)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Sau khi FlashSaleService đặt gia_flash_sale_snapshot, gọi hàm này
     * để tính lại tien_hao_hut_flash_sale và cập nhật phần shipper / hệ thống chịu.
     *
     * @param  int    $maChiTietHangHoan
     * @param  float  $giaFlashSale
     * @return ChiTietHangHoan
     * @throws \Exception
     */
    public function capNhatTienHaoHutFlashSale(int $maChiTietHangHoan, float $giaFlashSale): ChiTietHangHoan
    {
        return DB::transaction(function () use ($maChiTietHangHoan, $giaFlashSale) {

            $ct = ChiTietHangHoan::lockForUpdate()->find($maChiTietHangHoan);

            if (! $ct) {
                throw new \Exception("Không tìm thấy chi tiết hàng hoàn #{$maChiTietHangHoan}.");
            }

            if ($ct->ket_qua === 'DA_XU_LY') {
                throw new \Exception("Dòng #{$maChiTietHangHoan} đã DA_XU_LY.");
            }

            // Bó hoa đã xả bó → không tính hao hụt Flash Sale
            if ($ct->ket_qua === 'HONG' && $ct->ma_bo_hoa) {
                return $ct;
            }

            $giaBan   = (float) $ct->gia_ban_snapshot;
            $soLuong  = $ct->so_luong_con_ban_duoc;

            $haoHutMotDon   = max(0, $giaBan - $giaFlashSale);
            $tongHaoHut     = $haoHutMotDon * $soLuong;

            $hoaDon    = $ct->hangHoan->hoaDon;
            $nguonHuy  = $hoaDon->nguon_huy;

            $tienShipperChiu  = $ct->tien_shipper_chiu;
            $tienHeThongChiu  = $ct->tien_he_thong_chiu;

            if ($nguonHuy === 'SHIPPER') {
                // Hệ thống chịu 50%, shipper chịu 50% tiền hao hụt Flash Sale
                $tienHeThongChiu  += $tongHaoHut / 2;
                $tienShipperChiu  += $tongHaoHut / 2;
            } else {
                // CUSTOMER: hệ thống chịu toàn bộ hao hụt Flash Sale
                $tienHeThongChiu  += $tongHaoHut;
            }

            $ct->update([
                'gia_flash_sale_snapshot'   => $giaFlashSale,
                'tien_hao_hut_flash_sale'   => $tongHaoHut,
                'tien_he_thong_chiu'        => $tienHeThongChiu,
                'tien_shipper_chiu'         => $tienShipperChiu,
            ]);

            // Cập nhật lại phạt shipper nếu lỗi SHIPPER
            if ($nguonHuy === 'SHIPPER') {
                $phat = PhatShipper::where('ma_hoa_don', $hoaDon->ma_hoa_don)
                    ->where('trang_thai', 'CHUA_THU')
                    ->first();

                if ($phat) {
                    $phat->increment('tien_hao_hut_flash_sale', $tongHaoHut / 2);
                    $phat->increment('so_tien', $tongHaoHut / 2);
                }
            }

            return $ct->fresh();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. ĐÁNH DẤU DÒNG DA_XU_LY (gọi sau khi Flash Sale kết thúc)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Đánh dấu một dòng chi tiết hàng hoàn là DA_XU_LY.
     * Được gọi từ FlashSaleService khi Flash Sale kết thúc.
     *
     * @param  int  $maChiTietHangHoan
     * @return void
     * @throws \Exception
     */
    public function danhDauDaXuLy(int $maChiTietHangHoan): void
    {
        $ct = ChiTietHangHoan::find($maChiTietHangHoan);

        if (! $ct) {
            throw new \Exception("Không tìm thấy chi tiết hàng hoàn #{$maChiTietHangHoan}.");
        }

        if ($ct->ket_qua === 'DA_XU_LY') {
            return; // Idempotent
        }

        $ct->update(['ket_qua' => 'DA_XU_LY']);
    }
}
