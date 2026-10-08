<?php

namespace App\Services;

use App\Models\BoHoa;
use App\Models\ChiTietNhap;
use App\Models\HoaDon;
use App\Models\InventoryReservation;
use App\Models\SanPham;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReservationService
{
    /**
     * Lấy tổng số lượng đang giữ (RESERVED) của sản phẩm.
     */
    public static function getReservedForProduct(int $maSanPham): int
    {
        return (int) InventoryReservation::where('ma_san_pham', $maSanPham)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->sum('so_luong');
    }

    /**
     * Lấy tổng số lượng đang giữ (RESERVED) của một lô hàng.
     */
    public static function getReservedForLot(int $maChiTietNhap): int
    {
        return (int) InventoryReservation::where('ma_chi_tiet_nhap', $maChiTietNhap)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->sum('so_luong');
    }

    /**
     * Lấy số lượng tồn khả dụng của sản phẩm (available = on_hand - reserved).
     */
    public static function getAvailableForProduct(SanPham $sp): int
    {
        $reserved = self::getReservedForProduct($sp->ma_san_pham);
        return max(0, (int) $sp->so_luong - $reserved);
    }

    /**
     * Lấy tổng số lượng đang giữ (RESERVED) của một bó hoa thành phẩm cụ thể.
     */
    public static function getReservedForBouquet(int $maBoHoa): int
    {
        return (int) InventoryReservation::where('ma_bo_hoa', $maBoHoa)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->sum('so_luong');
    }

    /**
     * Lấy số lượng tồn khả dụng của một bó hoa thành phẩm cụ thể.
     */
    public static function getAvailableForBouquet(BoHoa $bo): int
    {
        $onHand = ($bo->trang_thai === 'ACTIVE' && is_null($bo->ma_hoa_don)) ? (int) $bo->so_luong : 0;
        $reserved = self::getReservedForBouquet($bo->ma_bo_hoa);
        return max(0, $onHand - $reserved);
    }

    /**
     * Lấy thông tin tồn kho chuẩn hóa [on_hand, reserved, available] cho một Mẫu bó hoa thành phẩm.
     * On Hand   = SUM(bo_hoa.so_luong) (với loai_bo_hoa = HE_THONG, trang_thai = ACTIVE, ma_hoa_don is null, so_luong > 0)
     * Reserved  = SUM(inventory_reservations.so_luong) của các bó trong nhóm (trang_thai = RESERVED)
     * Available = max(0, On Hand - Reserved)
     */
    public static function getStockForTemplate(int $maMauBoHoa): array
    {
        $activeBouquets = BoHoa::where('ma_mau_bo_hoa', $maMauBoHoa)
            ->where('loai_bo_hoa', 'HE_THONG')
            ->where('trang_thai', 'ACTIVE')
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->get(['ma_bo_hoa', 'so_luong']);

        $onHand = (int) $activeBouquets->sum('so_luong');
        $boIds = $activeBouquets->pluck('ma_bo_hoa')->all();

        $reserved = 0;
        if (!empty($boIds)) {
            $reserved = (int) InventoryReservation::whereIn('ma_bo_hoa', $boIds)
                ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
                ->sum('so_luong');
        }

        $available = max(0, $onHand - $reserved);

        return [
            'on_hand'   => $onHand,
            'reserved'  => $reserved,
            'available' => $available,
        ];
    }

    /**
     * Lấy số lượng tồn khả dụng của một lô hàng.
     */
    public static function getAvailableForLot(ChiTietNhap $lot): int
    {
        $reserved = self::getReservedForLot($lot->ma_chi_tiet_nhap);
        return max(0, (int) $lot->so_luong_con_lai - $reserved);
    }

    /**
     * Giữ tồn kho cho các sản phẩm đơn lẻ (Giỏ hàng thường).
     *
     * @param HoaDon $hoaDon
     * @param array $gioHang Mảng các sản phẩm ['ma_san_pham', 'so_luong', 'gia_ban', ...]
     * @return array Danh sách phân bổ chi tiết dùng để lưu ChiTietHoaDon
     */
    public static function reserveSingleItems(HoaDon $hoaDon, array $gioHang): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Giữ tồn kho phải chạy trong DB Transaction.');
        }

        $allocatedRows = [];

        foreach (collect($gioHang)->sortBy('ma_san_pham')->groupBy('ma_san_pham') as $spId => $items) {
            $sp = SanPham::whereKey($spId)->lockForUpdate()->firstOrFail();

            if ($sp->trang_thai !== 'DANG_BAN') {
                self::loi("Sản phẩm \"{$sp->ten_san_pham}\" đã ngừng bán.");
            }

            $tongCan = (int) $items->sum('so_luong');
            if ($tongCan < 1) {
                self::loi("Số lượng đặt của \"{$sp->ten_san_pham}\" không hợp lệ.");
            }

            // Tính tồn đang giữ hiện tại của sản phẩm
            $reservedSp = (int) InventoryReservation::where('ma_san_pham', $spId)
                ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
                ->lockForUpdate()
                ->sum('so_luong');

            $availableSp = (int) $sp->so_luong - $reservedSp;
            if ($availableSp < $tongCan) {
                self::loi("Sản phẩm \"{$sp->ten_san_pham}\" không đủ tồn kho khả dụng (cần {$tongCan}, còn {$availableSp}).");
            }

            // Lấy các lô từ phiếu nhập đã CONFIRMED theo FIFO
            $lots = ChiTietNhap::with('phieuNhap')
                ->where('ma_san_pham', $spId)
                ->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn ($q) => $q->where('trang_thai', 'CONFIRMED'))
                ->orderBy('ma_chi_tiet_nhap')
                ->lockForUpdate()
                ->get()
                ->sortBy(fn ($lot) => [$lot->phieuNhap->ngay_nhap?->timestamp ?? 0, $lot->ma_chi_tiet_nhap]);

            $totalLotAvailable = 0;
            $lotAvailabilities = [];

            foreach ($lots as $lot) {
                $reservedLot = (int) InventoryReservation::where('ma_chi_tiet_nhap', $lot->ma_chi_tiet_nhap)
                    ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
                    ->lockForUpdate()
                    ->sum('so_luong');

                $avail = max(0, (int) $lot->so_luong_con_lai - $reservedLot);
                $lotAvailabilities[$lot->ma_chi_tiet_nhap] = $avail;
                $totalLotAvailable += $avail;
            }

            if ($totalLotAvailable < $tongCan) {
                self::loi("Các lô hàng của \"{$sp->ten_san_pham}\" không đủ tồn kho khả dụng (cần {$tongCan}, còn {$totalLotAvailable}).");
            }

            // Tiến hành reserve từng lô theo FIFO
            foreach ($items as $row) {
                $con = (int) $row['so_luong'];

                foreach ($lots as $lot) {
                    $availLot = $lotAvailabilities[$lot->ma_chi_tiet_nhap] ?? 0;
                    if ($availLot <= 0) {
                        continue;
                    }

                    $lay = min($con, $availLot);
                    if ($lay <= 0) {
                        continue;
                    }

                    // TẠO BẢN GHI RESERVATION (KHÔNG TRỪ ON_HAND)
                    InventoryReservation::create([
                        'ma_hoa_don'       => $hoaDon->ma_hoa_don,
                        'ma_san_pham'      => (int) $spId,
                        'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
                        'ma_bo_hoa'        => null,
                        'so_luong'         => $lay,
                        'trang_thai'       => InventoryReservation::STATUS_RESERVED,
                    ]);

                    $lotAvailabilities[$lot->ma_chi_tiet_nhap] -= $lay;

                    $allocatedRows[] = [
                        'ma_san_pham'       => (int) $spId,
                        'ma_chi_tiet_nhap'  => $lot->ma_chi_tiet_nhap,
                        'so_luong'          => $lay,
                        'gia_nhap_snapshot' => $lot->gia_nhap,
                        'gia_ban_snapshot'  => (float) ($sp->gia_ban_hien_tai ?? $row['gia_ban'] ?? 0),
                    ];

                    $con -= $lay;
                    if ($con === 0) {
                        break;
                    }
                }
            }
        }

        return $allocatedRows;
    }

    /**
     * Giữ tồn kho cho BÓ HOA THÀNH PHẨM ĐÃ CẮM SẴN (BoHoa HE_THONG có sẵn).
     * Tuyệt đối KHÔNG reserve hay trừ BOM nguyên vật liệu lần nữa!
     */
    public static function reserveReadyBouquet(HoaDon $hoaDon, BoHoa $boHoa): InventoryReservation
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Giữ tồn kho phải chạy trong DB Transaction.');
        }

        $locked = BoHoa::whereKey($boHoa->ma_bo_hoa)->lockForUpdate()->firstOrFail();

        // Kiểm tra xem bó hoa này đã bị đơn khác giữ chưa
        $existing = InventoryReservation::where('ma_bo_hoa', $locked->ma_bo_hoa)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->lockForUpdate()
            ->first();

        if ($existing && (int) $existing->ma_hoa_don !== (int) $hoaDon->ma_hoa_don) {
            self::loi("Bó hoa \"{$locked->ten_bo_hoa}\" (#{$locked->ma_bo_hoa}) đã được giữ cho một đơn hàng khác.");
        }

        // Tạo reservation cho chính bó hoa thành phẩm
        $res = InventoryReservation::create([
            'ma_hoa_don'       => $hoaDon->ma_hoa_don,
            'ma_san_pham'      => null,
            'ma_chi_tiet_nhap' => null,
            'ma_bo_hoa'        => $locked->ma_bo_hoa,
            'so_luong'         => 1,
            'trang_thai'       => InventoryReservation::STATUS_RESERVED,
        ]);

        return $res;
    }

    /**
     * Giữ tồn kho nguyên vật liệu cho Bó hoa làm theo đơn (BOM mẫu MAU hoặc Custom Mix TUY_CHON).
     *
     * @param HoaDon $hoaDon
     * @param BoHoa $boHoa
     * @param array $formula Danh sách nguyên liệu cần: [['ma_san_pham' => x, 'vai_tro' => y, 'so_luong' => z]]
     * @return array Danh sách phân bổ chi tiết dùng để lưu ChiTietBoHoa
     */
    public static function reserveBouquetIngredients(HoaDon $hoaDon, BoHoa $boHoa, array $formula): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Giữ tồn kho phải chạy trong DB Transaction.');
        }

        $allocatedRows = [];

        foreach (collect($formula)->sortBy('ma_san_pham')->groupBy('ma_san_pham') as $spId => $items) {
            $sp = SanPham::whereKey($spId)->lockForUpdate()->firstOrFail();

            if ($sp->trang_thai !== 'DANG_BAN') {
                self::loi("Nguyên liệu \"{$sp->ten_san_pham}\" đã ngừng bán.");
            }

            $tongCan = (int) $items->sum('so_luong');
            if ($tongCan < 1) {
                continue;
            }

            // Tính tồn đang giữ hiện tại của sản phẩm
            $reservedSp = (int) InventoryReservation::where('ma_san_pham', $spId)
                ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
                ->lockForUpdate()
                ->sum('so_luong');

            $availableSp = (int) $sp->so_luong - $reservedSp;
            if ($availableSp < $tongCan) {
                self::loi("Nguyên liệu \"{$sp->ten_san_pham}\" không đủ tồn kho khả dụng (cần {$tongCan}, còn {$availableSp}).");
            }

            // Lấy các lô từ phiếu nhập đã CONFIRMED theo FIFO
            $lots = ChiTietNhap::with('phieuNhap')
                ->where('ma_san_pham', $spId)
                ->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn ($q) => $q->where('trang_thai', 'CONFIRMED'))
                ->orderBy('ma_chi_tiet_nhap')
                ->lockForUpdate()
                ->get()
                ->sortBy(fn ($lot) => [$lot->phieuNhap->ngay_nhap?->timestamp ?? 0, $lot->ma_chi_tiet_nhap]);

            $totalLotAvailable = 0;
            $lotAvailabilities = [];

            foreach ($lots as $lot) {
                $reservedLot = (int) InventoryReservation::where('ma_chi_tiet_nhap', $lot->ma_chi_tiet_nhap)
                    ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
                    ->lockForUpdate()
                    ->sum('so_luong');

                $avail = max(0, (int) $lot->so_luong_con_lai - $reservedLot);
                $lotAvailabilities[$lot->ma_chi_tiet_nhap] = $avail;
                $totalLotAvailable += $avail;
            }

            if ($totalLotAvailable < $tongCan) {
                self::loi("Lô nguyên liệu \"{$sp->ten_san_pham}\" không đủ tồn khả dụng (cần {$tongCan}, còn {$totalLotAvailable}).");
            }

            // Tiến hành reserve từng lô theo FIFO
            foreach ($items as $row) {
                $con = (int) $row['so_luong'];

                foreach ($lots as $lot) {
                    $availLot = $lotAvailabilities[$lot->ma_chi_tiet_nhap] ?? 0;
                    if ($availLot <= 0) {
                        continue;
                    }

                    $lay = min($con, $availLot);
                    if ($lay <= 0) {
                        continue;
                    }

                    // TẠO RESERVATION GẮN VỚI SẢN PHẨM + LÔ + BÓ HOA
                    InventoryReservation::create([
                        'ma_hoa_don'       => $hoaDon->ma_hoa_don,
                        'ma_san_pham'      => (int) $spId,
                        'ma_chi_tiet_nhap' => $lot->ma_chi_tiet_nhap,
                        'ma_bo_hoa'        => $boHoa->ma_bo_hoa,
                        'so_luong'         => $lay,
                        'trang_thai'       => InventoryReservation::STATUS_RESERVED,
                    ]);

                    $lotAvailabilities[$lot->ma_chi_tiet_nhap] -= $lay;

                    $allocatedRows[] = [
                        'ma_san_pham'       => (int) $spId,
                        'ma_chi_tiet_nhap'  => $lot->ma_chi_tiet_nhap,
                        'vai_tro'           => $row['vai_tro'] ?? null,
                        'so_luong'          => $lay,
                        'gia_nhap_snapshot' => $lot->gia_nhap,
                        'gia_ban_snapshot'  => (float) ($sp->gia_ban_hien_tai ?? 0),
                    ];

                    $con -= $lay;
                    if ($con === 0) {
                        break;
                    }
                }
            }
        }

        return $allocatedRows;
    }

    /**
     * XÁC NHẬN ĐƠN HÀNG (CONFIRM):
     * RESERVED -> CONSUMED
     * Trừ trực tiếp on_hand của sản phẩm và số lượng còn lại của lô hàng.
     */
    public static function confirmOrder(HoaDon $hoaDon): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Xác nhận đơn phải chạy trong DB Transaction.');
        }

        $allRes = InventoryReservation::where('ma_hoa_don', $hoaDon->ma_hoa_don)->lockForUpdate()->get();
        if ($allRes->isNotEmpty() && $allRes->every(fn ($r) => $r->trang_thai === InventoryReservation::STATUS_CONSUMED)) {
            throw new \LogicException("Đơn hàng #{$hoaDon->ma_hoa_don} đã được xác nhận trừ kho (CONSUMED) trước đó.");
        }

        $reservations = $allRes->where('trang_thai', InventoryReservation::STATUS_RESERVED);
        if ($reservations->isEmpty()) {
            return;
        }

        foreach ($reservations as $res) {
            // 1. Trừ số lượng lô (nếu có gắn lô)
            if ($res->ma_chi_tiet_nhap) {
                $lot = ChiTietNhap::whereKey($res->ma_chi_tiet_nhap)->lockForUpdate()->first();
                if ($lot) {
                    $lot->decrement('so_luong_con_lai', $res->so_luong);
                }
            }

            // 2. Trừ tồn thực tế của sản phẩm
            if ($res->ma_san_pham) {
                $sp = SanPham::whereKey($res->ma_san_pham)->lockForUpdate()->first();
                if ($sp) {
                    $sp->decrement('so_luong', $res->so_luong);
                    if ($sp->fresh()->so_luong <= 0) {
                        $sp->update(['trang_thai' => 'NGUNG_BAN']);
                    }
                }
            }

            // 3. Nếu là bó hoa thành phẩm có sẵn: trừ tồn thực tế của bó hoa
            if ($res->ma_bo_hoa && !$res->ma_san_pham) {
                $bo = BoHoa::whereKey($res->ma_bo_hoa)->lockForUpdate()->first();
                if ($bo) {
                    $bo->so_luong = max(0, (int) $bo->so_luong - (int) $res->so_luong);
                    if (!$bo->ma_hoa_don && $hoaDon->ma_hoa_don) {
                        $bo->ma_hoa_don = $hoaDon->ma_hoa_don;
                    }
                    $bo->save();
                }
            }

            // 4. Chuyển trạng thái reservation sang CONSUMED
            $res->update(['trang_thai' => InventoryReservation::STATUS_CONSUMED]);
        }
    }

    /**
     * HỦY / TỪ CHỐI / THẤT BẠI ĐƠN HÀNG (CANCEL / REJECT / PAYMENT FAIL):
     * RESERVED -> RELEASED
     * Tuyệt đối KHÔNG cộng lại on_hand (vì on_hand chưa từng bị trừ).
     * Khả năng khả dụng tự động phục hồi.
     */
    public static function releaseOrder(HoaDon $hoaDon): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Giải phóng giữ kho phải chạy trong DB Transaction.');
        }

        $allRes = InventoryReservation::where('ma_hoa_don', $hoaDon->ma_hoa_don)->lockForUpdate()->get();
        if ($allRes->isNotEmpty() && $allRes->every(fn ($r) => $r->trang_thai === InventoryReservation::STATUS_RELEASED)) {
            throw new \LogicException("Đơn hàng #{$hoaDon->ma_hoa_don} đã được giải phóng tồn kho (RELEASED) trước đó.");
        }

        $reservations = $allRes->where('trang_thai', InventoryReservation::STATUS_RESERVED);
        if ($reservations->isEmpty()) {
            return;
        }

        foreach ($reservations as $res) {
            // Nếu là bó hoa có sẵn: trả bó hoa về trạng thái sẵn sàng trong kho
            if ($res->ma_bo_hoa && !$res->ma_san_pham) {
                $bo = BoHoa::whereKey($res->ma_bo_hoa)->lockForUpdate()->first();
                if ($bo) {
                    $bo->update([
                        'ma_hoa_don'    => null,
                        'ma_khach_hang' => null,
                        'loi_nhan'      => null,
                        'trang_thai'    => 'ACTIVE',
                    ]);
                }
            }

            // Chuyển trạng thái reservation sang RELEASED
            $res->update(['trang_thai' => InventoryReservation::STATUS_RELEASED]);
        }

        // Cập nhật các bó hoa làm theo đơn / custom mix của hóa đơn này sang CANCELLED
        $customBouquets = BoHoa::where('ma_hoa_don', $hoaDon->ma_hoa_don)
            ->whereNotNull('ma_hoa_don')
            ->lockForUpdate()
            ->get();

        foreach ($customBouquets as $cb) {
            $isReady = $reservations->contains(fn ($r) => $r->ma_bo_hoa == $cb->ma_bo_hoa && !$r->ma_san_pham);
            if (!$isReady) {
                $cb->update(['trang_thai' => 'CANCELLED']);
            }
        }
    }

    private static function loi(string $msg): never
    {
        throw ValidationException::withMessages(['ton_kho' => $msg]);
    }
}
