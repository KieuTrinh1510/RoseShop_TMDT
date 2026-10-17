<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // Danh sách Flash Sale đang bán
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $flashSales = $this->truyVanDangBan()
            ->orderBy('ket_thuc')
            ->paginate(12);

        // Danh sách thiệp khả dụng (để chọn khi mua bó hoa FlashSale)
        $dsThiep = SanPham::where('loai_san_pham', 'THIEP')
            ->where('trang_thai', 'DANG_BAN')
            ->orderBy('ten_san_pham')
            ->get()
            ->filter(fn ($sp) =>
                InventoryReservationService::getAvailableForIngredient($sp->ma_san_pham) > 0
            )
            ->values();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $flashSales->through(fn (FlashSale $fs) => $this->chuanHoa($fs)),
            ]);
        }

        return view('customer.FlashSale.index', compact('flashSales', 'dsThiep'));
    }

    // ─────────────────────────────────────────────────────────────
    // Chi tiết một Flash Sale (dùng chung view ChiTietSanPham)
    // ─────────────────────────────────────────────────────────────
    public function show(Request $request, $id)
    {
        $fs   = $this->truyVanDangBan()->where('ma_flash_sale', $id)->firstOrFail();
        $data = $this->chuanHoa($fs);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('customer.ChiTietSanPham', [
            'type'      => 'flash-sale',
            'flashSale' => $data,
        ]);
    }

    /**
     * Đưa hàng Flash Sale vào luồng thanh toán.
     * - Sản phẩm lẻ: 1 dòng trong session mua_ngay.
     * - Bó hoa: 1 dòng bó hoa FS + (tuỳ chọn) 1 dòng thiệp riêng.
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'ma_flash_sale' => ['required', 'integer'],
            'so_luong'      => ['required', 'integer', 'min:1'],
            'ma_thiep'      => ['nullable', 'integer', 'exists:san_pham,ma_san_pham'],
             'loi_nhan'      => ['nullable', 'string', 'max:2000'], 
        ]);

        $fs = $this->truyVanDangBan()->whereKey($data['ma_flash_sale'])->firstOrFail();

        $conLai = (int) $fs->so_luong - (int) $fs->so_luong_da_ban;
        if ((int) $data['so_luong'] > $conLai) {
            return back()->with('error', "Flash Sale chỉ còn {$conLai} sản phẩm.");
        }

        $muaNgay = [];
        $soLuong = (int) $data['so_luong'];

        // ── Sản phẩm lẻ ──
        if ($fs->ma_san_pham) {
            $muaNgay['flash_' . $fs->ma_flash_sale] = [
                'ma_flash_sale'    => $fs->ma_flash_sale,
                'ma_san_pham'      => $fs->ma_san_pham,
                'ma_bo_hoa'        => null,
                'ten_san_pham'     => $fs->sanPham?->ten_san_pham ?? 'Sản phẩm Flash Sale',
                'hinh_anh'         => $fs->sanPham?->hinh_anh,
                'gia_ban'          => (float) $fs->gia_flash_sale,
                'gia_nhap'         => (float) ($fs->chiTietHangHoan?->gia_von_snapshot ?? 0),
                'so_luong'         => $soLuong,
                'so_luong_con_lai' => $conLai,
            ];

            session(['mua_ngay' => $muaNgay]);
            session()->forget(['checkout_items', 'bo_hoa_checkout', 'diem_su_dung']);

            return redirect()->route('customer.thanh-toan');
        }

        // ── Bó hoa FlashSale ──
        // Xác định thiệp (nếu có)
        $maThiep = null;
        $thiepModel = null;

        if (!empty($data['ma_thiep'])) {
            $thiepModel = SanPham::whereKey($data['ma_thiep'])
                ->where('loai_san_pham', 'THIEP')
                ->where('trang_thai', 'DANG_BAN')
                ->first();

            if (!$thiepModel) {
                return back()->with('error', 'Thiệp đã chọn không tồn tại hoặc đã ngừng bán.');
            }

            $tonThiep = InventoryReservationService::getAvailableForIngredient($thiepModel->ma_san_pham);
            if ($tonThiep < $soLuong) {
                return back()->with('error', "Thiệp \"{$thiepModel->ten_san_pham}\" chỉ còn {$tonThiep}, không đủ cho {$soLuong} bó.");
            }

            $maThiep = $thiepModel->ma_san_pham;
        }

        // Dòng bó hoa FS
        $muaNgay['flash_bo_' . $fs->ma_flash_sale] = [
            'ma_flash_sale'    => $fs->ma_flash_sale,
            'ma_san_pham'      => null,
            'ma_bo_hoa'        => $fs->ma_bo_hoa,
            'ma_thiep'         => $maThiep,
            'loi_nhan'         => $data['loi_nhan'] ?? null,
            'ten_san_pham'     => $fs->boHoa?->ten_bo_hoa ?? 'Bó hoa Flash Sale',
            'hinh_anh'         => $fs->boHoa?->hinh_anh,
            'gia_ban'          => (float) $fs->gia_flash_sale,
            'gia_nhap'         => (float) ($fs->chiTietHangHoan?->gia_von_snapshot ?? 0),
            'so_luong'         => $soLuong,
            'so_luong_con_lai' => $conLai,
        ];

        // Dòng thiệp riêng (reserve theo FIFO như sản phẩm lẻ thường)
        if ($thiepModel) {
            $muaNgay['thiep_bo_' . $fs->ma_flash_sale] = [
                'ma_flash_sale'    => null,
                'ma_san_pham'      => $thiepModel->ma_san_pham,
                'ma_bo_hoa'        => null,
                'ten_san_pham'     => $thiepModel->ten_san_pham,
                'hinh_anh'         => $thiepModel->hinh_anh,
                'gia_ban'          => (float) ($thiepModel->gia_ban_hien_tai ?? 0),
                'gia_nhap'         => 0,
                'so_luong'         => $soLuong,
                'so_luong_con_lai' => 9999,
            ];
        }

        session(['mua_ngay' => $muaNgay]);
        session()->forget(['checkout_items', 'bo_hoa_checkout', 'diem_su_dung']);

        return redirect()->route('customer.thanh-toan');
    }

    // ═════════════════════════════════════════════════════════════
    // Helpers
    // ═════════════════════════════════════════════════════════════

    /** Chỉ Flash Sale DANG_BAN, đang trong khung giờ, còn số lượng, còn sản phẩm/bó gốc. */
    private function truyVanDangBan()
    {
        $now = now();

        return FlashSale::query()
            ->with(['sanPham', 'boHoa', 'chiTietHangHoan'])
            ->where('trang_thai', 'DANG_BAN')
            ->where('bat_dau', '<=', $now)
            ->where('ket_thuc', '>=', $now)
            ->whereColumn('so_luong_da_ban', '<', 'so_luong')
            ->where(function ($q) {
                $q->whereHas('sanPham')->orWhereHas('boHoa');
            });
    }

    /** Chuẩn hóa dữ liệu trả cho view / checkout. */
    private function chuanHoa(FlashSale $fs): array
    {
        $laBo     = !empty($fs->ma_bo_hoa);
        $giaGoc   = (float) $fs->gia_goc;
        $giaFlash = (float) $fs->gia_flash_sale;
        $conLai   = max(0, (int) $fs->so_luong - (int) $fs->so_luong_da_ban);

        if ($laBo) {
            $ten  = $fs->boHoa?->ten_bo_hoa ?? 'Bó hoa';
            $anh  = $fs->boHoa
                ? \App\Http\Controllers\Admin\BoHoaController::anh($fs->boHoa)
                : asset('img/default.jpg');
            $moTa = $fs->boHoa?->concept;
        } else {
            $ten  = $fs->sanPham?->ten_san_pham ?? 'Sản phẩm';
            $anh  = $fs->sanPham?->hinh_anh ? asset($fs->sanPham->hinh_anh) : asset('img/default.jpg');
            $moTa = $fs->sanPham?->mo_ta;
        }

        return [
            'ma_flash_sale'   => $fs->ma_flash_sale,
            'loai'            => $laBo ? 'BO_HOA' : 'SAN_PHAM',
            'ma_san_pham'     => $fs->ma_san_pham,
            'ma_bo_hoa'       => $fs->ma_bo_hoa,
            'ten'             => $ten,
            'anh'             => $anh,
            'mo_ta'           => $moTa,
            'gia_goc'         => $giaGoc,
            'gia_flash_sale'  => $giaFlash,
            'so_tien_giam'    => round($giaGoc - $giaFlash, 2),
            'phan_tram_giam'  => $giaGoc > 0 ? (int) round(($giaGoc - $giaFlash) / $giaGoc * 100) : 0,
            'so_luong'        => (int) $fs->so_luong,
            'so_luong_da_ban' => (int) $fs->so_luong_da_ban,
            'so_luong_con_lai'=> $conLai,
            'bat_dau'         => optional($fs->bat_dau)->format('Y-m-d H:i:s'),
            'ket_thuc'        => optional($fs->ket_thuc)->format('Y-m-d H:i:s'),
            'checkout'        => [
                'ma_flash_sale'   => $fs->ma_flash_sale,
                'ma_san_pham'     => $fs->ma_san_pham,
                'ma_bo_hoa'       => $fs->ma_bo_hoa,
                'don_gia'         => $giaFlash,
                'so_luong_toi_da' => $conLai,
            ],
        ];
    }
}