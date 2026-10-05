<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryReservation;
use App\Models\SanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TonKhoController extends Controller
{
    /**
     * Hiển thị bảng tổng hợp tồn kho UC4.4:
     * Mặt hàng | Tồn thực tế (on_hand) | Đang giữ (reserved) | Khả dụng (available) | Trạng thái
     */
    public function index(Request $request)
    {
        $query = SanPham::with([
            'reservations' => fn ($q) => $q->where('trang_thai', InventoryReservation::STATUS_RESERVED),
            'chiTietNhaps' => fn ($q) => $q->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn ($p) => $p->where('trang_thai', 'CONFIRMED')),
        ]);

        // Tìm kiếm
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                $q->where('ten_san_pham', 'like', "%{$kw}%")
                  ->orWhere('ma_san_pham', $kw);
            });
        }

        // Lọc theo loại sản phẩm
        if ($request->filled('loai_san_pham')) {
            $query->where('loai_san_pham', $request->loai_san_pham);
        }

        // Lọc theo trạng thái kinh doanh
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $sqlTonKhaDung = '(COALESCE(san_pham.so_luong, 0) - COALESCE((SELECT SUM(so_luong) FROM inventory_reservations WHERE inventory_reservations.ma_san_pham = san_pham.ma_san_pham AND inventory_reservations.trang_thai = "' . InventoryReservation::STATUS_RESERVED . '"), 0))';

        // Lọc theo tình trạng tồn kho
        if ($request->filled('tinh_trang_ton')) {
            match ($request->tinh_trang_ton) {
                'con_hang' => $query->whereRaw("{$sqlTonKhaDung} > 0"),
                'sap_het'  => $query->whereRaw("{$sqlTonKhaDung} > 0 AND {$sqlTonKhaDung} <= 10"),
                'het_hang' => $query->whereRaw("{$sqlTonKhaDung} <= 0"),
                'dang_giu' => $query->whereHas('reservations', fn ($q) => $q->where('trang_thai', InventoryReservation::STATUS_RESERVED)),
                default    => null,
            };
        }

        $items = $query->orderBy('ten_san_pham')->paginate(20)->withQueryString();

        // Thống kê tổng quan
        $tongMatHang = SanPham::count();
        $tongTonThucTe = (int) SanPham::sum('so_luong');
        $tongDangGiu = (int) InventoryReservation::where('trang_thai', InventoryReservation::STATUS_RESERVED)->sum('so_luong');
        $tongKhaDung = max(0, $tongTonThucTe - $tongDangGiu);
        $tongSapHetHang = SanPham::whereRaw("{$sqlTonKhaDung} > 0 AND {$sqlTonKhaDung} <= 10")->count();

        return view('admin.TonKho.index', compact(
            'items',
            'tongMatHang',
            'tongTonThucTe',
            'tongDangGiu',
            'tongKhaDung',
            'tongSapHetHang'
        ));
    }

    /**
     * API chi tiết các đơn hàng đang giữ sản phẩm này (phục vụ modal).
     */
    public function chiTietGiu(int $id): JsonResponse
    {
        $sp = SanPham::findOrFail($id);

        $reservations = InventoryReservation::with(['hoaDon.khachHang', 'chiTietNhap.phieuNhap', 'boHoa'])
            ->where('ma_san_pham', $id)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($res) {
                return [
                    'id'               => $res->id,
                    'ma_hoa_don'       => $res->ma_hoa_don,
                    'khach_hang'       => $res->hoaDon?->khachHang?->ho_ten ?? 'Khách vãng lai',
                    'so_dien_thoai'    => $res->hoaDon?->so_dien_thoai ?? '',
                    'so_luong'         => (int) $res->so_luong,
                    'ma_chi_tiet_nhap' => $res->ma_chi_tiet_nhap,
                    'so_lo'            => $res->chiTietNhap ? "Lô #{$res->ma_chi_tiet_nhap}" : 'Chưa gắn',
                    'ten_bo_hoa'       => $res->boHoa?->ten_bo_hoa,
                    'thoi_gian'        => $res->created_at?->format('H:i d/m/Y') ?? '',
                ];
            });

        return response()->json([
            'san_pham'     => [
                'ma_san_pham'  => $sp->ma_san_pham,
                'ten_san_pham' => $sp->ten_san_pham,
                'don_vi_tinh'  => $sp->don_vi_tinh_text,
                'ton_thuc_te'  => (int) $sp->so_luong,
                'ton_dang_giu' => (int) $reservations->sum('so_luong'),
                'ton_kha_dung' => max(0, (int) $sp->so_luong - (int) $reservations->sum('so_luong')),
            ],
            'reservations' => $reservations,
        ]);
    }
}
