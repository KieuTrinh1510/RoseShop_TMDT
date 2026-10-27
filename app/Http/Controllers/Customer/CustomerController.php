<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Các loại KHÔNG BAO GIỜ được hiển thị trên trang chủ khách hàng.
     * Đây là nguyên vật liệu nội bộ — chỉ dùng để tạo bó hoa, không bán trực tiếp.
     * Dù ở trạng thái DANG_BAN (Hiện) hay NGUNG_BAN (Ẩn), khách đều không thấy.
     */
    private const MA_LOAI_NGUYEN_LIEU = [
        'GIAY_GOI',
        'RUY_BANG',
        'PHU_KIEN_TRANG_TRI',
        'THIEP',
        'MIENG_XOP',
    ];

    public function dashboard(Request $request)
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;

        $keyword = trim($request->get('q', ''));

        // Tìm kiếm: chặn nguyên vật liệu — chỉ sản phẩm bán ra mới lên trang chủ.
        $sanPhams = SanPham::where('trang_thai', 'DANG_BAN')
            ->whereNotIn('loai_san_pham', self::MA_LOAI_NGUYEN_LIEU)
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                    ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                    ->orderBy('gia_ban', 'asc');
            }])
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('ten_san_pham', 'like', "%{$keyword}%")
                        ->orWhere('loai_san_pham', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('ma_san_pham', 'desc')
            ->get();

        // Block "Hoa tươi bán chạy": loại bỏ nguyên vật liệu + phụ kiện + quà tặng.
        $hoaTuoiBanChay = SanPham::where('trang_thai', 'DANG_BAN')
            ->whereNotIn('loai_san_pham', array_merge(
                self::MA_LOAI_NGUYEN_LIEU,
                ['PHU_KIEN', 'QUA_TANG']
            ))
            ->withSum(['chiTietHoaDons as da_ban' => fn($q) => $q->whereHas('hoaDon', fn($h) => $h->whereNotIn('trang_thai', ['CANCELLED', 'DA_HUY']))], 'so_luong')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                    ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                    ->orderBy('gia_ban', 'asc');
            }])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($b->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0) <=> ($a->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0),
                fn ($a, $b) => ($b->da_ban ?? 0) <=> ($a->da_ban ?? 0),
                fn ($a, $b) => $b->ma_san_pham <=> $a->ma_san_pham,
            ])
            ->take(8)
            ->values();

        $phuKienNoiBat = SanPham::where('trang_thai', 'DANG_BAN')
            ->where('loai_san_pham', 'PHU_KIEN')
            ->withSum(['chiTietHoaDons as da_ban' => fn($q) => $q->whereHas('hoaDon', fn($h) => $h->whereNotIn('trang_thai', ['CANCELLED', 'DA_HUY']))], 'so_luong')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                    ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                    ->orderBy('gia_ban', 'asc');
            }])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($b->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0) <=> ($a->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0),
                fn ($a, $b) => ($b->da_ban ?? 0) <=> ($a->da_ban ?? 0),
                fn ($a, $b) => $b->ma_san_pham <=> $a->ma_san_pham,
            ])
            ->take(6)
            ->values();

        $quaTangNoiBat = SanPham::where('trang_thai', 'DANG_BAN')
            ->where('loai_san_pham', 'QUA_TANG')
            ->withSum(['chiTietHoaDons as da_ban' => fn($q) => $q->whereHas('hoaDon', fn($h) => $h->whereNotIn('trang_thai', ['CANCELLED', 'DA_HUY']))], 'so_luong')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                    ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                    ->orderBy('gia_ban', 'asc');
            }])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($b->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0) <=> ($a->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0),
                fn ($a, $b) => ($b->da_ban ?? 0) <=> ($a->da_ban ?? 0),
                fn ($a, $b) => $b->ma_san_pham <=> $a->ma_san_pham,
            ])
            ->take(6)
            ->values();

        // Section Flash Sale trang chủ:
        // Lấy các flash sale đang active, thời gian hiện tại nằm trong bat_dau -> ket_thuc,
        // chưa hết quota và sản phẩm / bó hoa có tồn kho vật lý khả dụng > 0.
        $activeFlashSales = \App\Models\FlashSale::with(['sanPham.chiTietNhaps.phieuNhap', 'boHoa.mauBoHoa'])
            ->where('trang_thai', 'DANG_BAN')
            ->where(function ($q) {
                $q->whereNull('bat_dau')->orWhere('bat_dau', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ket_thuc')->orWhere('ket_thuc', '>=', now());
            })
            ->whereColumn('so_luong', '>', 'so_luong_da_ban')
            ->orderBy('ket_thuc', 'asc')
            ->get()
            ->filter(function ($fs) {
                if ($fs->available_quota <= 0) {
                    return false;
                }
                if ($fs->ma_bo_hoa && $fs->boHoa) {
                    return \App\Services\InventoryReservationService::getAvailableForBouquet($fs->boHoa) > 0;
                }
                if ($fs->ma_san_pham && $fs->sanPham) {
                    return \App\Services\InventoryReservationService::getAvailableForIngredient($fs->ma_san_pham) > 0;
                }
                return false;
            })
            ->values();

        $nearestFlashSaleEnd = $activeFlashSales->min('ket_thuc');

        return view('customer.Dashboard', compact(
            'user',
            'sanPhams',
            'hoaTuoiBanChay',
            'phuKienNoiBat',
            'quaTangNoiBat',
            'activeFlashSales',
            'nearestFlashSaleEnd'
        ));
    }

    public function hoaTuoi(Request $request)
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;

        // Loại bỏ nguyên vật liệu + phụ kiện + quà tặng khỏi trang "Hoa tươi".
        $excludedTypes = array_merge(
            self::MA_LOAI_NGUYEN_LIEU,
            ['PHU_KIEN', 'QUA_TANG']
        );

        $query = SanPham::where('trang_thai', 'DANG_BAN')
            ->whereNotIn('loai_san_pham', $excludedTypes)
            ->withSum(['chiTietHoaDons as da_ban' => fn($q) => $q->whereHas('hoaDon', fn($h) => $h->whereNotIn('trang_thai', ['CANCELLED', 'DA_HUY']))], 'so_luong')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                    ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                    ->orderBy('gia_ban', 'asc');
            }]);

        if ($request->filled('loai')) {
            $query->whereIn('loai_san_pham', $request->loai);
        }

        $this->applyPriceFilter($query, $request);

        // Sắp xếp: Ưu tiên còn hàng lên đầu -> Bán chạy nhất (da_ban desc) -> Mới nhất
        $hoaTuois = $query->get()->sortBy([
            fn ($a, $b) => ($b->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0) <=> ($a->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0),
            fn ($a, $b) => ($b->da_ban ?? 0) <=> ($a->da_ban ?? 0),
            fn ($a, $b) => $b->ma_san_pham <=> $a->ma_san_pham,
        ])->values();

        // Categories cho filter sidebar — cũng phải loại bỏ nguyên vật liệu + phụ kiện + quà tặng.
        $categories = SanPham::where('trang_thai', 'DANG_BAN')
            ->whereNotIn('loai_san_pham', $excludedTypes)
            ->select('loai_san_pham')
            ->distinct()
            ->pluck('loai_san_pham');

        return view('customer.HoaTuoi', compact(
            'user',
            'hoaTuois',
            'categories'
        ));
    }

    public function phuKien(Request $request)
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;

        $query = SanPham::where('trang_thai', 'DANG_BAN')
            ->where('loai_san_pham', 'PHU_KIEN')
            ->withSum(['chiTietHoaDons as da_ban' => fn($q) => $q->whereHas('hoaDon', fn($h) => $h->whereNotIn('trang_thai', ['CANCELLED', 'DA_HUY']))], 'so_luong')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                    ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                    ->orderBy('gia_ban', 'asc');
            }]);

        $this->applyPriceFilter($query, $request);

        // Sắp xếp: Ưu tiên còn hàng lên đầu -> Bán chạy nhất (da_ban desc) -> Mới nhất
        $phuKiens = $query->get()->sortBy([
            fn ($a, $b) => ($b->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0) <=> ($a->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0),
            fn ($a, $b) => ($b->da_ban ?? 0) <=> ($a->da_ban ?? 0),
            fn ($a, $b) => $b->ma_san_pham <=> $a->ma_san_pham,
        ])->values();

        return view('customer.PhuKien', compact(
            'user',
            'phuKiens'
        ));
    }

    public function quaTang(Request $request)
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;

        $query = SanPham::where('trang_thai', 'DANG_BAN')
            ->where('loai_san_pham', 'QUA_TANG')
            ->withSum(['chiTietHoaDons as da_ban' => fn($q) => $q->whereHas('hoaDon', fn($h) => $h->whereNotIn('trang_thai', ['CANCELLED', 'DA_HUY']))], 'so_luong')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                    ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                    ->orderBy('gia_ban', 'asc');
            }]);

        $this->applyPriceFilter($query, $request);

        // Sắp xếp: Ưu tiên còn hàng lên đầu -> Bán chạy nhất (da_ban desc) -> Mới nhất
        $quaTangs = $query->get()->sortBy([
            fn ($a, $b) => ($b->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0) <=> ($a->chiTietNhaps->sum('so_luong_con_lai') > 0 ? 1 : 0),
            fn ($a, $b) => ($b->da_ban ?? 0) <=> ($a->da_ban ?? 0),
            fn ($a, $b) => $b->ma_san_pham <=> $a->ma_san_pham,
        ])->values();

        return view('customer.QuaTang', compact(
            'user',
            'quaTangs'
        ));
    }

    public function gioiThieu()
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;

        return view('customer.GioiThieu', compact('user'));
    }

    public function lienHe()
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;

        return view('customer.LienHe', compact('user'));
    }

    private function applyPriceFilter($query, Request $request): void
    {
        if (!$request->filled('price')) {
            return;
        }

        $query->where(function ($sub) use ($request) {
            foreach ($request->price as $price) {
                if ($price === 'duoi_20000') {
                    $sub->orWhere('gia_ban_hien_tai', '<', 20000);
                }

                if ($price === '20000_50000') {
                    $sub->orWhereBetween('gia_ban_hien_tai', [20000, 50000]);
                }

                if ($price === '50000_100000') {
                    $sub->orWhereBetween('gia_ban_hien_tai', [50000, 100000]);
                }

                if ($price === 'tren_100000') {
                    $sub->orWhere('gia_ban_hien_tai', '>', 100000);
                }
            }
        });
    }
}