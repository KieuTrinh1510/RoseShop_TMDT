<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{BoHoa, MauBoHoa, PhuPhiBoHoa, SanPham, ChiTietNhap, HoaDon, BaoCaoHangHong, InventoryReservation};
use App\Services\InventoryReservationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class BoHoaController extends Controller
{
    public const SO_BONG = ['S' => 7, 'M' => 10, 'L' => 15, 'XL' => 20, 'XXL' => 30, 'SPECIAL' => 50];

    public const KIEU_DANG = [
        'ROUND' => 'Tròn', 'NATURAL' => 'Tự nhiên', 'CONE' => 'Hình nón',
        'CASCADING' => 'Thác đổ', 'ASYMMETRIC' => 'Bất đối xứng',
        'COMPACT' => 'Gọn', 'LONG_STEM' => 'Cành dài',
    ];

    public const CONCEPT = [
        'ROMANTIC' => 'Lãng mạn', 'ELEGANT' => 'Thanh lịch', 'MINIMALIST' => 'Tối giản',
        'NATURAL' => 'Tự nhiên', 'LUXURY' => 'Sang trọng', 'CUTE' => 'Dễ thương', 'VINTAGE' => 'Cổ điển',
    ];

    public const GIAY_GOI_QTY = ['S' => 4, 'M' => 6, 'L' => 8, 'XL' => 10, 'XXL' => 14, 'SPECIAL' => 20];
    public const RUY_BANG_QTY = ['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 2, 'XXL' => 2, 'SPECIAL' => 3];
    public const XOP_QTY      = ['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 1, 'XXL' => 2, 'SPECIAL' => 4];
    public const PHU_KIEN_MAX = ['S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5, 'SPECIAL' => 7];

    public const VAI_TRO = [
        'HOA_CHINH' => 'Hoa chính', 'HOA_TRANG_TRI' => 'Hoa trang trí',
        'GIAY_GOI' => 'Giấy gói', 'RUY_BANG' => 'Ruy băng',
        'PHU_KIEN_TRANG_TRI' => 'Phụ kiện trang trí', 'XOP' => 'Xốp',
        // THIEP giữ lại chỉ để đọc dữ liệu cũ; không cho chọn ở form tạo mẫu.
        'THIEP' => 'Thiệp',
    ];

    /** Vai trò hiển thị trong dropdown form tạo mẫu (đã bỏ THIEP). */
    public const VAI_TRO_CHO_PHEP = [
        'HOA_CHINH', 'HOA_TRANG_TRI', 'GIAY_GOI', 'RUY_BANG', 'PHU_KIEN_TRANG_TRI', 'XOP',
    ];

    public const MA_NHAN_VIEN_MAC_DINH = 7;

    public static function getDuLieuBoHoa(Request $r): array
    {
        // ─── CHỈ lấy bó hoa do ADMIN tạo (HE_THONG). Không hiển thị bó của khách (TUY_CHON).
        $q = BoHoa::with(['chiTietBoHoa.sanPham', 'chiTietBoHoa.chiTietNhap'])
            ->whereNull('ma_hoa_don')
            ->where('loai_bo_hoa', 'HE_THONG')
            ->where('so_luong', '>', 0);

        if ($r->filled('trang_thai_bo_hoa')) $q->where('trang_thai', $r->input('trang_thai_bo_hoa'));
        if ($r->filled('q_bo_hoa'))          $q->where('ten_bo_hoa', 'like', '%' . $r->input('q_bo_hoa') . '%');

        $all = $q->orderByDesc('ma_bo_hoa')->get();

        // Preload phụ phí 1 lần, key = "LOAI|GIA_TRI"
        $phuPhiMap = PhuPhiBoHoa::all()->keyBy(fn ($p) => $p->loai . '|' . $p->gia_tri);

        $grouped = $all->groupBy(fn ($bo) => $bo->ma_mau_bo_hoa ? 'mau_' . $bo->ma_mau_bo_hoa : 'bo_' . $bo->ma_bo_hoa);

        $tinhGiaMotBo = function (BoHoa $bo) use ($phuPhiMap): float {
            $nl = $bo->chiTietBoHoa->sum(
                fn ($ct) => ((float) ($ct->sanPham?->gia_ban_hien_tai ?? 0)) * (int) $ct->so_luong
            );
            $s = (float) ($phuPhiMap->get('SIZE|' . $bo->size)?->phu_phi ?? 0);
            $k = (float) ($phuPhiMap->get('KIEU_DANG|' . $bo->kieu_bo_hoa)?->phu_phi ?? 0);
            return round($nl + $s + $k, 2);
        };

        $merged = $grouped->map(function ($group) use ($tinhGiaMotBo) {
            $first    = $group->first();
            $totalQty = (int) $group->sum('so_luong');

            // Giá mỗi bó luôn được tính lại từ giá hiện tại của NVL + phụ phí.
            // Nhờ vậy admin list, modal chi tiết giá và trang khách luôn khớp nhau.
            $giaMotBo = $tinhGiaMotBo($first);

            return (object) [
                'ma_bo_hoa'     => $first->ma_bo_hoa,
                'ma_mau_bo_hoa' => $first->ma_mau_bo_hoa,
                'ten_bo_hoa'    => $first->ten_bo_hoa,
                'size'          => $first->size,
                'kieu_bo_hoa'   => $first->kieu_bo_hoa,
                'concept'       => $first->concept,
                'loai_bo_hoa'   => $first->loai_bo_hoa,
                'trang_thai'    => $first->trang_thai,
                'so_luong'      => $totalQty,
                'gia_ban'       => $giaMotBo,   // ← luôn khớp modal & trang khách
                'bo_dau_tien'   => $first,
                'so_bien_the'   => $group->count(),
            ];
        })->values();

        $page    = max(1, (int) $r->input('page_bo_hoa', 1));
        $perPage = 5;

        $danhSachBoHoa = new LengthAwarePaginator(
            $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $r->url(), 'pageName' => 'page_bo_hoa']
        );
        $danhSachBoHoa->withQueryString()->fragment('khoi-bo-hoa');

        // ─── Đếm số bó hoa CHƯA BÁN của mỗi mẫu để view disable nút Xóa ───
        // "Chưa bán" = chưa gán hóa đơn (ma_hoa_don IS NULL) VÀ còn hàng (so_luong > 0).
        // Bó đã bán hoặc đã báo hỏng hết không tính vào đây → mẫu vẫn xóa được.
        $mauList = MauBoHoa::with('chiTietMauBoHoa')
            ->orderByDesc('ma_mau_bo_hoa')
            ->get();

        $soBoTheoMau = BoHoa::whereIn('ma_mau_bo_hoa', $mauList->pluck('ma_mau_bo_hoa'))
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->select('ma_mau_bo_hoa', DB::raw('SUM(so_luong) as tong'))
            ->groupBy('ma_mau_bo_hoa')
            ->pluck('tong', 'ma_mau_bo_hoa');

        $mauList->each(function ($m) use ($soBoTheoMau) {
            $m->so_bo_hoa_chua_ban = (int) ($soBoTheoMau[$m->ma_mau_bo_hoa] ?? 0);
        });

        $reservedMap = InventoryReservation::where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->groupBy('ma_san_pham')
            ->selectRaw('ma_san_pham, SUM(so_luong) as tong_giu')
            ->pluck('tong_giu', 'ma_san_pham');

        $lotStockMap = ChiTietNhap::whereHas('phieuNhap', fn ($q) => $q->where('trang_thai', 'CONFIRMED'))
            ->where('so_luong_con_lai', '>', 0)
            ->groupBy('ma_san_pham')
            ->selectRaw('ma_san_pham, SUM(so_luong_con_lai) as tong_lot')
            ->pluck('tong_lot', 'ma_san_pham');

        $nguyenLieuBoHoa = SanPham::where('trang_thai', 'DANG_BAN')
            ->orderBy('ten_san_pham')
            ->get();

        $tonKhoKhaDungMap = [];

        $nguyenLieuBoHoa->each(function (SanPham $sp) use ($reservedMap, $lotStockMap, &$tonKhoKhaDungMap) {
            $onHand = (int) $sp->so_luong;
            $reserved = (int) ($reservedMap[$sp->ma_san_pham] ?? 0);
            $hasLots = isset($lotStockMap[$sp->ma_san_pham]);
            $lotAvail = $hasLots ? (int) $lotStockMap[$sp->ma_san_pham] : $onHand;
            $available = max(0, min($onHand, $lotAvail) - $reserved);
            $dvt = $sp->don_vi_tinh_text ?: ($sp->don_vi_tinh ?: 'cái');

            $sp->setAttribute('ton_thuc_te', $onHand);
            $sp->setAttribute('ton_dang_giu', $reserved);
            $sp->setAttribute('ton_kha_dung', $available);
            $sp->setAttribute('don_vi_tinh_label', $dvt);

            $tonKhoKhaDungMap[$sp->ma_san_pham] = $available;
        });

        return [
            'danhSachBoHoa'    => $danhSachBoHoa,
            'mauBoHoas'        => $mauList,
            'nguyenLieuBoHoa'  => $nguyenLieuBoHoa,
            'tonKhoKhaDungMap' => $tonKhoKhaDungMap,
            'phuPhiBoHoa'      => PhuPhiBoHoa::all(),
        ];
    }

    public static function normalizeTenMau(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    public static function validateBo(Request $r, bool $isTemplate = false): array
    {
        $rules = [
            'ten_bo_hoa'   => 'required|string|max:100',
            'size'         => ['required', Rule::in(array_keys(self::SO_BONG))],
            'kieu_bo_hoa'  => ['required', Rule::in(array_keys(self::KIEU_DANG))],
            'concept'      => ['nullable', Rule::in(array_keys(self::CONCEPT))],
            'mo_ta'        => 'nullable|string|max:2000',
            'hinh_anh'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'thanh_phan'   => 'required|array|min:1|max:100',
            'thanh_phan.*.ma_san_pham' => 'required|integer|exists:san_pham,ma_san_pham',
            // Chỉ cho phép các vai trò trong VAI_TRO_CHO_PHEP — chặn THIEP.
            'thanh_phan.*.vai_tro'     => ['required', Rule::in(self::VAI_TRO_CHO_PHEP)],
            'thanh_phan.*.so_luong'    => 'required|integer|min:1|max:1000',
        ];

        if (!$isTemplate) {
            $rules['so_luong']      = 'required|integer|min:1|max:100';
            $rules['ma_mau_bo_hoa'] = 'nullable|integer|exists:mau_bo_hoa,ma_mau_bo_hoa';
            $rules['loi_nhan']      = 'nullable|string|max:2000';
        }

        $d = $r->validate($rules, [
            'required' => 'Vui lòng nhập :attribute.',
            'exists'   => ':attribute chưa tồn tại hoặc chưa cấu hình phụ phí.',
            'integer'  => ':attribute phải là số nguyên.',
            'min'      => ':attribute phải từ :min trở lên.',
            'max'      => ':attribute vượt giới hạn :max.',
            'in'       => ':attribute không hợp lệ.',
            'image'    => 'Tệp tải lên phải là ảnh.',
            'mimes'    => 'Chỉ nhận ảnh JPG, PNG, WEBP.',
        ], [
            'ten_bo_hoa' => 'tên bó hoa', 'kieu_bo_hoa' => 'kiểu dáng',
            'so_luong' => 'số bó', 'thanh_phan' => 'thành phần',
            'thanh_phan.*.so_luong' => 'số lượng nguyên liệu',
            'thanh_phan.*.ma_san_pham' => 'nguyên liệu',
            'thanh_phan.*.vai_tro' => 'vai trò',
        ]);

        if ($isTemplate) {
            $tenChuan = self::normalizeTenMau($d['ten_bo_hoa']);
            if ($tenChuan === '') {
                throw ValidationException::withMessages(['ten_bo_hoa' => 'Vui lòng nhập tên mẫu bó hoa.']);
            }
            $exists = MauBoHoa::whereRaw('LOWER(TRIM(ten_mau)) = ?', [mb_strtolower($tenChuan, 'UTF-8')])->exists();
            if ($exists) {
                throw ValidationException::withMessages(['ten_bo_hoa' => 'Tên mẫu bó hoa đã tồn tại.']);
            }
            $d['ten_bo_hoa'] = $tenChuan;
        }

        $d['thanh_phan'] = array_map(
            fn ($row) => collect($row)->only(['ma_san_pham', 'vai_tro', 'so_luong'])->all(),
            $d['thanh_phan']
        );
        self::kiemTraCongThuc($d);
        self::kiemTraNguyenLieuDangBan($d['thanh_phan']);

        if ($isTemplate) {
            self::kiemTraTonKhaDungMau($d['thanh_phan']);
        }

        return $d;
    }

    public static function kiemTraCongThuc(array $d): void
    {
        $sp = SanPham::whereIn('ma_san_pham', array_column($d['thanh_phan'], 'ma_san_pham'))
            ->get()->keyBy('ma_san_pham');

        foreach ($d['thanh_phan'] as $row) {
            $types = match ($row['vai_tro']) {
                'HOA_CHINH', 'HOA_TRANG_TRI' => ['HOA_TUOI', 'HOA_GIA', 'HOA_SAP', 'HOA_GIAY_NHUN'],
                // ─── Phụ kiện chỉ nhận PHU_KIEN_TRANG_TRI (NVL), KHÔNG nhận PHU_KIEN (bán ra)
                'PHU_KIEN_TRANG_TRI'         => ['PHU_KIEN_TRANG_TRI'],
                'XOP'                        => ['MIENG_XOP'],
                'THIEP'                      => ['THIEP'],
                'GIAY_GOI'                   => ['GIAY_GOI'],
                'RUY_BANG'                   => ['RUY_BANG'],
                default                      => [$row['vai_tro']],
            };
            if (!$sp->has($row['ma_san_pham'])
                || !in_array($sp[$row['ma_san_pham']]->loai_san_pham, $types, true)) {
                self::loi('Nguyên liệu không phù hợp với vai trò.');
            }
        }

        $size = $d['size'];
        $sum  = fn ($role) => (int) collect($d['thanh_phan'])->where('vai_tro', $role)->sum('so_luong');

        $canBong  = self::SO_BONG[$size] ?? 0;
        $hoaChinh = $sum('HOA_CHINH');
        $hoaTT    = $sum('HOA_TRANG_TRI');

        if ($hoaChinh < 1) self::loi('Phải chọn ít nhất 1 loại hoa chính.');
        if ($hoaChinh + $hoaTT !== $canBong) {
            self::loi("Size {$size} cần đúng {$canBong} bông (hoa chính + hoa trang trí). Hiện có: {$hoaChinh} chính + {$hoaTT} trang trí.");
        }

        $checkFixed = function (string $role, int $expected, string $label) use ($sum) {
            $actual = $sum($role);
            if ($actual !== $expected) self::loi("Cần đúng {$expected} {$label} cho size này (hiện có {$actual}).");
        };
        $checkFixed('GIAY_GOI', self::GIAY_GOI_QTY[$size] ?? 0, 'tờ giấy gói');
        $checkFixed('RUY_BANG', self::RUY_BANG_QTY[$size] ?? 0, 'mét ruy băng');
        $checkFixed('XOP',      self::XOP_QTY[$size]      ?? 0, 'miếng xốp');

        $maxPK = self::PHU_KIEN_MAX[$size] ?? 0;
        if ($sum('PHU_KIEN_TRANG_TRI') > $maxPK) {
            self::loi("Size {$size} chỉ được tối đa {$maxPK} phụ kiện (có thể không chọn).");
        }

        self::kiemTraTrungHoaChinh($d);
    }

    public static function kiemTraTonKhaDungMau(array $thanhPhan): void
    {
        $grouped = collect($thanhPhan)->groupBy('ma_san_pham');
        $ids = $grouped->keys()->all();

        $sanPhams = SanPham::whereIn('ma_san_pham', $ids)->get()->keyBy('ma_san_pham');

        foreach ($grouped as $spId => $items) {
            /** @var SanPham|null $sp */
            $sp = $sanPhams->get($spId);
            if (!$sp) {
                self::loi("Sản phẩm ID #{$spId} không tồn tại.");
            }
            if ($sp->trang_thai !== 'DANG_BAN') {
                self::loi("Sản phẩm \"{$sp->ten_san_pham}\" đã ngừng bán, không thể dùng tạo mẫu.");
            }

            $tongCan = (int) $items->sum('so_luong');
            $reserved = InventoryReservationService::getReservedForProduct((int) $sp->ma_san_pham);
            $hasLots = ChiTietNhap::where('ma_san_pham', $sp->ma_san_pham)->exists();
            $onHand = (int) $sp->so_luong;
            if ($hasLots) {
                $confirmedLotStock = (int) ChiTietNhap::where('ma_san_pham', $sp->ma_san_pham)
                    ->whereHas('phieuNhap', fn ($q) => $q->where('trang_thai', 'CONFIRMED'))
                    ->sum('so_luong_con_lai');
                $onHand = min($onHand, $confirmedLotStock);
            }
            $available = max(0, $onHand - $reserved);

            if ($available <= 0) {
                self::loi("{$sp->ten_san_pham} đã hết hàng khả dụng trong kho.");
            }

            if ($tongCan > $available) {
                self::loi("{$sp->ten_san_pham} không còn đủ tồn khả dụng. Cần {$tongCan}, hiện còn {$available}.");
            }
        }
    }

    public static function kiemTraNguyenLieuDangBan(array $thanhPhan): void
    {
        $ids = collect($thanhPhan)->pluck('ma_san_pham')->filter()->unique()->values();
        if ($ids->isEmpty()) return;

        $ngungBan = SanPham::whereIn('ma_san_pham', $ids)
            ->where('trang_thai', '!=', 'DANG_BAN')
            ->get(['ma_san_pham', 'ten_san_pham']);

        if ($ngungBan->isNotEmpty()) {
            $ds = $ngungBan->pluck('ten_san_pham')->join(', ');
            self::loi("Các nguyên liệu sau đang ngừng bán, không thể dùng để tạo bó hoa: {$ds}.");
        }
    }

    public static function layPhuPhi(string $size, string $kieu): array
    {
        $s = PhuPhiBoHoa::where('loai', 'SIZE')->where('gia_tri', $size)->first();
        $k = PhuPhiBoHoa::where('loai', 'KIEU_DANG')->where('gia_tri', $kieu)->first();
        $phiSize = (float) ($s?->phu_phi ?? 0);
        $phiKieu = (float) ($k?->phu_phi ?? 0);
        return [
            'size'      => $phiSize,
            'kieu_dang' => $phiKieu,
            'tong'      => round($phiSize + $phiKieu, 2),
        ];
    }

    public static function phanBo(array $rows, bool $truKho = true, bool $khachMua = false): array
    {
        if (DB::transactionLevel() === 0) throw new \LogicException('Phân bổ cần transaction.');
        $result = [];
        foreach (collect($rows)->sortBy('ma_san_pham')->groupBy('ma_san_pham') as $id => $items) {
            $sp = SanPham::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($khachMua && $sp->trang_thai !== 'DANG_BAN') self::loi("{$sp->ten_san_pham} đã ngừng bán.");

            $tong = (int) $items->sum('so_luong');
            $lotsQuery = ChiTietNhap::with('phieuNhap')
                ->where('ma_san_pham', $id)
                ->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn ($q) => $q->where('trang_thai', 'CONFIRMED'));
            if (\Illuminate\Support\Facades\Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han')) {
                $lotsQuery->where(function ($q) {
                    $q->whereNull('ngay_het_han')
                      ->orWhere('ngay_het_han', '>=', now()->toDateString());
                });
            }
            $lots = $lotsQuery->orderBy('ma_chi_tiet_nhap')->lockForUpdate()->get()
                ->sortBy(fn ($lot) => [$lot->phieuNhap->ngay_nhap?->timestamp ?? 0, $lot->ma_chi_tiet_nhap]);

            if ($tong < 1 || $sp->so_luong < $tong || $lots->sum('so_luong_con_lai') < $tong) {
                self::loi("{$sp->ten_san_pham} không đủ tồn kho khả dụng (cần {$tong}, còn {$lots->sum('so_luong_con_lai')}).");
            }

            foreach ($items as $row) {
                $con = (int) $row['so_luong'];
                foreach ($lots as $lot) {
                    $lay = min($con, $lot->so_luong_con_lai);
                    if ($lay <= 0) continue;
                    $gia = (float) ($sp->gia_ban_hien_tai ?? 0);
                    $result[] = [
                        'ma_san_pham'       => (int) $id,
                        'ma_chi_tiet_nhap'  => $lot->getKey(),
                        'vai_tro'           => $row['vai_tro'] ?? null,
                        'so_luong'          => $lay,
                        'gia_nhap_snapshot' => $lot->gia_nhap,
                        'gia_ban_snapshot'  => $gia,
                    ];
                    $lot->so_luong_con_lai -= $lay;
                    if ($truKho) $lot->save();
                    $con -= $lay;
                    if ($con === 0) break;
                }
            }
            if ($truKho) $sp->decrement('so_luong', $tong);
        }
        return $result;
    }

    public static function hoanKho(array $rows): void
    {
        if (DB::transactionLevel() === 0) throw new \LogicException('Hoàn kho cần transaction.');
        foreach (collect($rows)->sortBy('ma_san_pham')->groupBy('ma_san_pham') as $id => $items) {
            $sp = SanPham::whereKey($id)->lockForUpdate()->firstOrFail();
            foreach ($items->sortBy('ma_chi_tiet_nhap') as $row) {
                if (!empty($row['ma_chi_tiet_nhap'])) {
                    ChiTietNhap::whereKey($row['ma_chi_tiet_nhap'])->where('ma_san_pham', $id)
                        ->lockForUpdate()->firstOrFail()
                        ->increment('so_luong_con_lai', $row['so_luong']);
                }
            }
            $sp->increment('so_luong', $items->sum('so_luong'));
        }
    }

    public static function giaPhanBo(array $rows, string $size, string $kieu, int $soBo = 1): array
    {
        $phi = self::layPhuPhi($size, $kieu);
        $nl  = collect($rows)->sum(fn ($r) => $r['gia_ban_snapshot'] * $r['so_luong']);
        $von = collect($rows)->sum(fn ($r) => $r['gia_nhap_snapshot'] * $r['so_luong']);
        $gia = round($nl / $soBo + $phi['tong'], 2);

        $byRole = collect($rows)->groupBy('vai_tro');
        $tienHoa = $byRole->filter(fn ($items, $role) => in_array($role, ['HOA_CHINH', 'HOA_TRANG_TRI']))
            ->flatten(1)->sum(fn ($r) => $r['gia_ban_snapshot'] * $r['so_luong']);
        $tienGiay  = ($byRole->get('GIAY_GOI') ?? collect())->sum(fn ($r) => $r['gia_ban_snapshot'] * $r['so_luong']);
        $tienRuy   = ($byRole->get('RUY_BANG') ?? collect())->sum(fn ($r) => $r['gia_ban_snapshot'] * $r['so_luong']);
        $tienXop   = ($byRole->get('XOP') ?? collect())->sum(fn ($r) => $r['gia_ban_snapshot'] * $r['so_luong']);
        $tienPK    = ($byRole->get('PHU_KIEN_TRANG_TRI') ?? collect())->sum(fn ($r) => $r['gia_ban_snapshot'] * $r['so_luong']);
        $tienThiep = ($byRole->get('THIEP') ?? collect())->sum(fn ($r) => $r['gia_ban_snapshot'] * $r['so_luong']);

        return [
            'tien_nguyen_lieu' => round($nl, 2),
            'tien_hoa'         => round($tienHoa, 2),
            'tien_giay'        => round($tienGiay, 2),
            'tien_ruy_bang'    => round($tienRuy, 2),
            'tien_xop'         => round($tienXop, 2),
            'tien_phu_kien'    => round($tienPK, 2),
            'tien_thiep'       => round($tienThiep, 2),
            'phu_phi'          => $phi,
            'gia_moi_bo'       => $gia,
            'gia_von_moi_bo'   => round($von / $soBo, 2),
            'tong_tien'        => round($gia * $soBo, 2),
        ];
    }

    public static function tinhGiaDuLieu(array $d): array
    {
        return DB::transaction(function () use ($d) {
            self::kiemTraCongThuc($d);
            $rows = array_map(
                fn ($r) => [...$r, 'so_luong' => $r['so_luong'] * ($d['so_luong'] ?? 1)],
                $d['thanh_phan']
            );
            return self::giaPhanBo(self::phanBo($rows, false), $d['size'], $d['kieu_bo_hoa'], $d['so_luong'] ?? 1);
        }, 3);
    }

    public static function luuChiTiet(BoHoa $bo, array $rows): void
    {
        $bo->chiTietBoHoa()->createMany(array_map(
            fn ($r) => collect($r)->only(['ma_san_pham', 'ma_chi_tiet_nhap', 'vai_tro', 'so_luong'])->all(),
            $rows
        ));
    }

    public static function anh(BoHoa $bo): string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file(public_path('img/bo-hoa/' . $bo->getKey() . '.' . $ext))) {
                return asset('img/bo-hoa/' . $bo->getKey() . '.' . $ext);
            }
        }
        return $bo->chiTietBoHoa->first()?->sanPham?->anh ?? asset('img/default.jpg');
    }

    public static function anhMau(MauBoHoa $m): string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file(public_path('img/mau-bo-hoa/' . $m->getKey() . '.' . $ext))) {
                return asset('img/mau-bo-hoa/' . $m->getKey() . '.' . $ext);
            }
        }
        return asset('img/default.jpg');
    }

    private function luuAnh(Request $r, BoHoa $bo): void
    {
        if (!$r->hasFile('hinh_anh')) return;
        $file = $r->file('hinh_anh');
        if (!is_dir(public_path('img/bo-hoa'))) mkdir(public_path('img/bo-hoa'), 0755, true);
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $old = public_path('img/bo-hoa/' . $bo->getKey() . '.' . $ext);
            if (is_file($old)) unlink($old);
        }
        $file->move(public_path('img/bo-hoa'), $bo->getKey() . '.' . $file->extension());
    }

    private function luuAnhMau(Request $r, MauBoHoa $m): void
    {
        if (!$r->hasFile('hinh_anh')) return;
        $file = $r->file('hinh_anh');
        if (!is_dir(public_path('img/mau-bo-hoa'))) mkdir(public_path('img/mau-bo-hoa'), 0755, true);
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $old = public_path('img/mau-bo-hoa/' . $m->getKey() . '.' . $ext);
            if (is_file($old)) unlink($old);
        }
        $file->move(public_path('img/mau-bo-hoa'), $m->getKey() . '.' . $file->extension());
    }

    public static function saoChepAnhTuMau(int $mauId, int $boId): void
    {
        if (!is_dir(public_path('img/bo-hoa'))) mkdir(public_path('img/bo-hoa'), 0755, true);
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $src = public_path('img/mau-bo-hoa/' . $mauId . '.' . $ext);
            if (is_file($src)) {
                copy($src, public_path('img/bo-hoa/' . $boId . '.' . $ext));
                return;
            }
        }
    }

    public function json(callable $callback, string $message = 'Thành công.')
    {
        try {
            return response()->json(['success' => true, 'message' => $message, 'data' => $callback()]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bó hoa hoặc mẫu đã chọn.'], 404);
        }
    }

    public function mau() { return $this->json(fn () => MauBoHoa::with('chiTietMauBoHoa')->get()); }
    public function phuPhi(Request $r) { return $this->json(fn () => self::layPhuPhi((string) $r->size, (string) $r->kieu_bo_hoa)); }

    public function luuPhuPhi(Request $r)
    {
        return $this->json(function () use ($r) {
            $d = $r->validate([
                'loai'    => ['required', Rule::in(['SIZE', 'KIEU_DANG'])],
                'gia_tri' => ['required', Rule::in(array_keys($r->loai === 'SIZE' ? self::SO_BONG : self::KIEU_DANG))],
                'phu_phi' => 'required|numeric|min:0|max:99999999',
            ], [
                'required' => 'Vui lòng điền đầy đủ phụ phí.',
                'in'       => 'Loại/giá trị phụ phí không hợp lệ.',
                'numeric'  => 'Phụ phí phải là số.',
                'min'      => 'Phụ phí không được âm.',
            ]);
            return PhuPhiBoHoa::updateOrCreate(
                collect($d)->only(['loai', 'gia_tri'])->all(),
                ['phu_phi' => $d['phu_phi']]
            );
        }, 'Đã lưu phụ phí.');
    }

    public function storeMau(Request $r)
    {
        return $this->json(function () use ($r) {
            $d = self::validateBo($r, true);
            $tenChuan = self::normalizeTenMau($d['ten_bo_hoa']);
            $d['ten_bo_hoa'] = $tenChuan;

            $m = DB::transaction(function () use ($d, $tenChuan) {
                // Chống race condition: lock & kiểm tra lại tên mẫu
                $exists = MauBoHoa::whereRaw('LOWER(TRIM(ten_mau)) = ?', [mb_strtolower($tenChuan, 'UTF-8')])
                    ->lockForUpdate()
                    ->exists();
                if ($exists) {
                    throw ValidationException::withMessages(['ten_bo_hoa' => 'Tên mẫu bó hoa đã tồn tại.']);
                }

                // Kiểm tra lại tồn kho khả dụng bên trong transaction
                self::kiemTraTonKhaDungMau($d['thanh_phan']);

                // Tạo mẫu: KHÔNG TRỪ KHO, KHÔNG RESERVATION, KHÔNG CONSUMED
                $mau = MauBoHoa::create([
                    ...collect($d)->only(['mo_ta', 'size', 'kieu_bo_hoa', 'concept'])->all(),
                    'ten_mau'    => $tenChuan,
                    'trang_thai' => 'INACTIVE',
                ]);
                $mau->chiTietMauBoHoa()->createMany($d['thanh_phan']);
                return $mau;
            }, 3);
            $this->luuAnhMau($r, $m);
            return $m->fresh();
        }, 'Đã tạo mẫu (trạng thái ẩn). Nhấn "Cho sử dụng" để bật.');
    }

    /**
     * ⚠️ Mẫu KHÔNG cho phép sửa sau khi tạo.
     * Route vẫn tồn tại để tương thích ngược (view cũ có thể gọi), nhưng
     * luôn trả lỗi để đảm bảo công thức mẫu bất biến.
     */
    public function updateMau(Request $r, int $id)
    {
        return $this->json(function () use ($id) {
            // Xác nhận mẫu tồn tại để trả message rõ ràng (thay vì "không tìm thấy")
            MauBoHoa::whereKey($id)->firstOrFail();

            self::loi(
                'Không thể sửa mẫu bó hoa sau khi tạo. ' .
                'Nếu muốn thay đổi công thức hoặc thông tin, vui lòng tạo mẫu mới.'
            );
        });
    }

    /**
     * Chỉ xóa mẫu khi KHÔNG còn bó hoa chưa bán nào được tạo từ mẫu đó.
     * Bó đã bán (có ma_hoa_don) hoặc đã báo hỏng hết (so_luong = 0) không tính.
     */
    public function destroyMau(int $id)
    {
        return $this->json(fn () => DB::transaction(function () use ($id) {
            $mau = MauBoHoa::whereKey($id)->lockForUpdate()->firstOrFail();

            // Đếm số bó hoa CHƯA BÁN còn hàng của mẫu này
            $soBoHoaChuaBan = BoHoa::where('ma_mau_bo_hoa', $mau->ma_mau_bo_hoa)
                ->whereNull('ma_hoa_don')
                ->where('so_luong', '>', 0)
                ->count();

            if ($soBoHoaChuaBan > 0) {
                self::loi(
                    "Không thể xóa mẫu \"{$mau->ten_mau}\" vì còn {$soBoHoaChuaBan} bó hoa " .
                    "được tạo sẵn từ mẫu này. Vui lòng xử lý (bán hoặc báo hỏng) các bó hoa trước khi xóa mẫu."
                );
            }

            // Các bó đã bán / đã hỏng hết của mẫu → set ma_mau_bo_hoa = null để
            // không bị orphan reference, nhưng vẫn giữ nguyên record bó hoa
            // (không làm mất lịch sử hóa đơn).
            BoHoa::where('ma_mau_bo_hoa', $mau->ma_mau_bo_hoa)
                ->update(['ma_mau_bo_hoa' => null]);

            $mau->delete();
        }), 'Đã xóa mẫu.');
    }

    public function trangThaiMau(int $id)
    {
        return $this->json(fn () => DB::transaction(function () use ($id) {
            $m = MauBoHoa::whereKey($id)->lockForUpdate()->firstOrFail();

            $m->trang_thai = $m->trang_thai === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
            $m->save();

            // Mẫu và bó HE_THONG dùng CHUNG 2 trạng thái ACTIVE / INACTIVE.
            // Tắt mẫu  → mọi bó chưa bán của mẫu chuyển INACTIVE.
            // Bật mẫu  → mọi bó chưa bán của mẫu chuyển ACTIVE.
            // Bỏ filter so_luong để không sót bó có so_luong = 0.
            // Không đụng bó đã gán hóa đơn (đã bán) — giữ nguyên lịch sử.
            $boStatus = $m->trang_thai === 'ACTIVE' ? 'ACTIVE' : 'DRAFT';

            BoHoa::where('ma_mau_bo_hoa', $m->ma_mau_bo_hoa)
                ->where('loai_bo_hoa', 'HE_THONG')       // ← thêm để không đụng bản nháp khách
                ->whereNull('ma_hoa_don')
                ->update(['trang_thai' => $boStatus]);
            return $m;
        }), 'Đã đổi trạng thái mẫu và đồng bộ bó hoa.');
    }

    public function editDataMau(int $id)
    {
        return $this->json(fn () => MauBoHoa::with('chiTietMauBoHoa.sanPham')->findOrFail($id));
    }

    public function store(Request $r)
    {
        return $this->json(function () use ($r) {
            $d = $r->validate([
                'ma_mau_bo_hoa' => 'required|integer|exists:mau_bo_hoa,ma_mau_bo_hoa',
                'so_luong'      => 'required|integer|min:1|max:100',
            ], [
                'ma_mau_bo_hoa.required' => 'Vui lòng chọn mẫu bó hoa.',
                'ma_mau_bo_hoa.exists'   => 'Mẫu bó hoa không tồn tại.',
                'so_luong.required'      => 'Vui lòng nhập số lượng.',
                'so_luong.integer'       => 'Số lượng phải là số nguyên.',
                'so_luong.min'           => 'Số lượng phải từ 1 trở lên.',
                'so_luong.max'           => 'Số lượng tối đa 100 bó mỗi lần.',
            ]);

            $mau = MauBoHoa::with('chiTietMauBoHoa')->findOrFail($d['ma_mau_bo_hoa']);

            if ($mau->trang_thai !== 'ACTIVE') {
                self::loi('Mẫu "' . $mau->ten_mau . '" đang ở trạng thái ẨN. Vui lòng bật "Cho sử dụng" trước khi tạo bó hoa.');
            }

            $formula = [
                'size'       => $mau->size,
                'thanh_phan' => $mau->chiTietMauBoHoa->map(fn ($c) => [
                    'ma_san_pham' => $c->ma_san_pham,
                    'vai_tro'     => $c->vai_tro,
                    'so_luong'    => $c->so_luong,
                ])->all(),
            ];
            self::kiemTraCongThuc($formula);
            self::kiemTraNguyenLieuDangBan($formula['thanh_phan']);

            $ids = DB::transaction(function () use ($d, $mau, $formula) {
                $ids = [];
                for ($i = 0; $i < $d['so_luong']; $i++) {
                    $rows = self::phanBo($formula['thanh_phan']);
                    $gia  = self::giaPhanBo($rows, $mau->size, $mau->kieu_bo_hoa);

                    $bo = new BoHoa();
                    $bo->ma_mau_bo_hoa = $mau->getKey();
                    $bo->ten_bo_hoa    = $mau->ten_mau;
                    $bo->size          = $mau->size;
                    $bo->kieu_bo_hoa   = $mau->kieu_bo_hoa;
                    $bo->concept       = $mau->concept;
                    $bo->loai_bo_hoa   = 'HE_THONG';
                    $bo->so_luong      = 1;
                    $bo->gia_ban       = $gia['gia_moi_bo'];
                    $bo->trang_thai    = 'ACTIVE';
                    $bo->save();

                    self::luuChiTiet($bo, $rows);
                    self::saoChepAnhTuMau($mau->getKey(), $bo->getKey());
                    $ids[] = $bo->getKey();
                }
                return $ids;
            }, 3);

            return [
                'ids'     => $ids,
                'message' => 'Đã tạo ' . count($ids) . ' bó hoa. Trạng thái đồng bộ theo mẫu.',
            ];
        }, 'Đã tạo bó hoa sẵn.');
    }

    public function baoHangHong(Request $r, int $id)
    {
        return $this->json(function () use ($r, $id) {
            $d = $r->validate([
                'so_luong_hong' => 'required|integer|min:1',
                'ly_do'         => 'required|string|max:255',
                'ghi_chu'       => 'nullable|string|max:1000',
            ], [
                'so_luong_hong.required' => 'Vui lòng nhập số lượng hỏng.',
                'so_luong_hong.integer'  => 'Số lượng hỏng phải là số nguyên.',
                'so_luong_hong.min'      => 'Số lượng hỏng phải từ 1 trở lên.',
                'ly_do.required'         => 'Vui lòng nhập lý do hỏng.',
                'ly_do.max'              => 'Lý do hỏng không vượt quá 255 ký tự.',
                'ghi_chu.max'            => 'Ghi chú không vượt quá 1000 ký tự.',
            ]);

            return DB::transaction(function () use ($d, $id, $r) {
                $bo = BoHoa::with('chiTietBoHoa')->whereKey($id)->lockForUpdate()->firstOrFail();

                if ($bo->ma_hoa_don) self::loi('Bó hoa đã được đặt, không thể báo hỏng.');

                $query = BoHoa::whereNull('ma_hoa_don')->where('so_luong', '>', 0);
                if ($bo->ma_mau_bo_hoa) {
                    $query->where('ma_mau_bo_hoa', $bo->ma_mau_bo_hoa);
                } else {
                    $query->where('ma_bo_hoa', $bo->ma_bo_hoa);
                }
                $group = $query->orderBy('ma_bo_hoa')->lockForUpdate()->get();
                $totalAvailable = (int) $group->sum('so_luong');

                if ($totalAvailable < 1) self::loi('Bó hoa không còn số lượng để báo hỏng.');
                if ($d['so_luong_hong'] > $totalAvailable) {
                    self::loi("Số lượng hỏng ({$d['so_luong_hong']}) vượt quá số lượng hiện có ({$totalAvailable}).");
                }

                $maNhanVien = Auth::user()?->nhanVien?->ma_nhan_vien
                    ?? $r->ma_nhan_vien
                    ?? self::MA_NHAN_VIEN_MAC_DINH;

                $thoiGian = now();
                $ghiChu   = $d['ghi_chu'] ?? null;
                $prefix   = "Từ bó hoa #{$bo->ma_bo_hoa} - {$bo->ten_bo_hoa}";
                $ghiChuFull = $ghiChu ? ($prefix . ' | ' . $ghiChu) : $prefix;

                $remaining = (int) $d['so_luong_hong'];
                foreach ($group as $b) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, $b->so_luong);
                    $b->so_luong -= $take;
                    if ($b->so_luong <= 0) {
                        $b->so_luong   = 0;
                        $b->trang_thai = 'DRAFT';
                    }
                    $b->save();
                    $remaining -= $take;
                }

                return $bo->fresh();
            });
        }, 'Đã ghi nhận báo cáo hỏng.');
    }

    public function chiTietGiaBoHoa(int $id)
    {
        return $this->json(function () use ($id) {
            $bo = BoHoa::with(['chiTietBoHoa.sanPham', 'chiTietBoHoa.chiTietNhap'])->findOrFail($id);

            // Đơn giá dòng = gia_ban_hien_tai của sản phẩm. KHÔNG fallback về lô nhập
            // nữa — để khớp đúng con số admin list và trang khách đang hiển thị.
            $chiTiet = $bo->chiTietBoHoa->map(function ($ct) {
                $giaBan = (float) ($ct->sanPham?->gia_ban_hien_tai ?? 0);
                return [
                    'ten_san_pham' => $ct->sanPham?->ten_san_pham ?? 'N/A',
                    'vai_tro'      => $ct->vai_tro,
                    'vai_tro_text' => self::VAI_TRO[$ct->vai_tro] ?? $ct->vai_tro,
                    'so_luong'     => (int) $ct->so_luong,
                    'don_vi_tinh'  => $ct->sanPham?->don_vi_tinh_text ?? $ct->sanPham?->don_vi_tinh ?? '',
                    'gia_ban'      => $giaBan,
                    'thanh_tien'   => $giaBan * (int) $ct->so_luong,
                ];
            });

            $tienNguyenLieu = (float) $chiTiet->sum('thanh_tien');
            $phuPhi         = self::layPhuPhi($bo->size, $bo->kieu_bo_hoa);

            // Giá bán = NL + phụ phí (đúng công thức đang dùng ở getDuLieuBoHoa()).
            // Nhờ vậy, con số dưới đáy modal LUÔN bằng cột "Giá bán" ngoài list.
            $giaBanTinhLai = round($tienNguyenLieu + $phuPhi['tong'], 2);

            return [
                'ma_bo_hoa'        => $bo->ma_bo_hoa,
                'ten_bo_hoa'       => $bo->ten_bo_hoa,
                'size'             => $bo->size,
                'kieu_bo_hoa'      => $bo->kieu_bo_hoa,
                'kieu_bo_hoa_text' => self::KIEU_DANG[$bo->kieu_bo_hoa] ?? $bo->kieu_bo_hoa,
                'concept'          => $bo->concept,
                'chi_tiet'         => $chiTiet,
                'tien_nguyen_lieu' => $tienNguyenLieu,
                'phu_phi'          => $phuPhi,
                'gia_ban'          => $giaBanTinhLai,
                // Giá vốn vẫn có thể dùng snapshot lô nhập để báo cáo lợi nhuận.
                'gia_von'          => (float) $bo->chiTietBoHoa->sum(
                    fn ($ct) => ((float) ($ct->chiTietNhap?->gia_nhap ?? 0)) * (int) $ct->so_luong
                ),
            ];
        });
    }

    public static function huyBoTrongDon(HoaDon $hd): bool
    {
        if (DB::transactionLevel() === 0 || $hd->trang_thai !== 'PENDING') {
            self::loi('Chỉ hoàn giữ kho của đơn PENDING trong transaction.');
        }

        // 1. Nếu đơn hàng có bản ghi InventoryReservation, ưu tiên gọi releaseOrder để release chuẩn
        if ($hd->reservations()->where('trang_thai', InventoryReservation::STATUS_RESERVED)->exists()) {
            InventoryReservationService::releaseOrder($hd);
        }

        // 2. Xử lý các bó hoa thuộc đơn hàng này
        $bos = BoHoa::where('ma_hoa_don', $hd->getKey())->orderBy('ma_bo_hoa')->lockForUpdate()->get();
        foreach ($bos as $bo) {
            // A. Bó hoa thành phẩm có sẵn (HE_THONG hoặc có ma_mau_bo_hoa):
            // Tuyệt đối KHÔNG gọi hoanKho (không tăng SanPham, không tăng ChiTietNhap vì nguyên liệu đã trừ lúc sản xuất).
            if ($bo->loai_bo_hoa === 'HE_THONG' || $bo->ma_mau_bo_hoa) {
                // Giải phóng reservation nếu còn sót
                InventoryReservation::where('ma_bo_hoa', $bo->ma_bo_hoa)
                    ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
                    ->update(['trang_thai' => InventoryReservation::STATUS_RELEASED]);

                $bo->ma_hoa_don    = null;
                $bo->ma_khach_hang = null;
                $bo->loi_nhan      = null;
                if ($bo->so_luong > 0) {
                    $bo->trang_thai = 'ACTIVE';
                }
                $bo->save();
            } else {
                // B. Bó làm theo đơn / tùy chọn (TUY_CHON):
                // Chưa trừ tồn kho vật lý lúc PENDING (chỉ reserve nguyên liệu), khi hủy thì release reservation,
                // KHÔNG tăng tồn kho SanPham/ChiTietNhap. Đánh dấu bó hoa đã hủy.
                InventoryReservation::where('ma_bo_hoa', $bo->ma_bo_hoa)
                    ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
                    ->update(['trang_thai' => InventoryReservation::STATUS_RELEASED]);

                $bo->trang_thai = 'CANCELLED';
                $bo->save();
            }
        }

        return $bos->isNotEmpty();
    }

    public static function loi(string $message): never
    {
        throw ValidationException::withMessages(['bo_hoa' => $message]);
    }

    public static function kiemTraTrungHoaChinh(array $d): void
    {
        $chinhIds = collect($d['thanh_phan'])->where('vai_tro', 'HOA_CHINH')->pluck('ma_san_pham');
        $decorIds = collect($d['thanh_phan'])->where('vai_tro', 'HOA_TRANG_TRI')->pluck('ma_san_pham');

        // Chống chọn cùng một hoa nhiều lần trong cùng một nhóm vai trò
        if ($chinhIds->count() !== $chinhIds->unique()->count()) {
            self::loi('Mỗi loại hoa chính chỉ được chọn một lần trong bó.');
        }
        if ($decorIds->count() !== $decorIds->unique()->count()) {
            self::loi('Mỗi loại hoa trang trí chỉ được chọn một lần trong bó.');
        }

        // Chống trùng hoa giữa hoa chính và hoa trang trí (hai chiều)
        $intersect = $chinhIds->unique()->intersect($decorIds->unique());
        if ($intersect->isNotEmpty()) {
            self::loi('Hoa đã chọn làm hoa chính không được chọn làm hoa trang trí và ngược lại.');
        }
    }
}