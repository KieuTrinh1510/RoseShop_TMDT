<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoHoa;
use App\Models\InventoryReservation;
use App\Models\MauBoHoa;
use App\Models\SanPham;
use App\Services\InventoryReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TonKhoController extends Controller
{
    /**
     * Hiển thị bảng tổng hợp tồn kho:
     * - Sản phẩm / Nguyên vật liệu
     * - Bó hoa thành phẩm
     * Thống kê: On Hand | Reserved | Available | Trạng thái tồn
     */
    public function index(Request $request)
    {
        $loaiTonKho  = trim((string) ($request->input('loai_ton_kho') ?? '')); // '', 'san_pham', 'nguyen_vat_lieu', 'bo_hoa'
        $kw          = trim((string) ($request->input('keyword') ?? ''));
        $loaiSanPham = trim((string) ($request->input('loai_san_pham') ?? ''));
        $trangThaiKd = trim((string) ($request->input('trang_thai') ?? ''));
        $tinhTrangTon = trim((string) ($request->input('tinh_trang_ton') ?? ''));

        // Chuẩn hóa tương thích ngược nếu request gửi loai_san_pham='BO_HOA'
        if ($loaiSanPham === 'BO_HOA') {
            $loaiTonKho  = 'bo_hoa';
            $loaiSanPham = '';
        }

        // Logic ưu tiên nguồn dữ liệu:
        // - Khi chọn loai_ton_kho='bo_hoa': Bắt buộc lấy Bó hoa thành phẩm, bỏ qua bộ lọc loại sản phẩm.
        // - Khi chọn loai_ton_kho='san_pham': Chỉ lấy Sản phẩm hoa bán lẻ (loại trừ nguyên vật liệu).
        // - Khi chọn loai_ton_kho='nguyen_vat_lieu': Chỉ lấy Nguyên vật liệu.
        // - Khi chọn 'Tất cả' ('') :
        //     + Nếu có chọn loại sản phẩm cụ thể:
        //         * Nếu thuộc DANH_SACH_LOAI_NGUYEN_LIEU -> chỉ lấy NVL
        //         * Ngược lại -> chỉ lấy SP bán lẻ
        //     + Nếu không chọn -> lấy cả 3 nhóm (Sản phẩm, Nguyên vật liệu, Bó hoa)
        $layBoHoa         = false;
        $laySanPham       = false;
        $layNguyenVatLieu = false;

        if ($loaiTonKho === 'bo_hoa') {
            $layBoHoa    = true;
            $loaiSanPham = '';
        } elseif ($loaiTonKho === 'nguyen_vat_lieu') {
            $layNguyenVatLieu = true;
        } elseif ($loaiTonKho === 'san_pham') {
            $laySanPham = true;
        } else {
            // Tất cả kho hàng ('')
            if ($loaiSanPham !== '') {
                if (in_array($loaiSanPham, SanPham::DANH_SACH_LOAI_NGUYEN_LIEU, true)) {
                    $layNguyenVatLieu = true;
                } else {
                    $laySanPham = true;
                }
            } else {
                $layBoHoa         = true;
                $laySanPham       = true;
                $layNguyenVatLieu = true;
            }
        }

        // Tách số định danh nếu user tìm kiếm bằng mã (hỗ trợ #330, SP330, #BH37, BH37, 37, 330...)
        $cleanNum = preg_replace('/[^0-9]/', '', $kw);

        // ── 1. NGUỒN DỮ LIỆU SẢN PHẨM & NGUYÊN VẬT LIỆU ──
        $sanPhamList = collect();
        if ($laySanPham || $layNguyenVatLieu) {
            $spQuery = SanPham::with([
                'reservations' => fn ($q) => $q->where('trang_thai', InventoryReservation::STATUS_RESERVED),
            ]);

            // Lọc phân loại Sản phẩm vs Nguyên vật liệu theo field thực tế loai_san_pham
            if ($laySanPham && !$layNguyenVatLieu) {
                // Chỉ lấy mặt hàng hoa bán lẻ trực tiếp (loại trừ các loại nguyên vật liệu)
                $spQuery->whereNotIn('loai_san_pham', SanPham::DANH_SACH_LOAI_NGUYEN_LIEU);
            } elseif ($layNguyenVatLieu && !$laySanPham) {
                // Chỉ lấy nguyên vật liệu
                $spQuery->whereIn('loai_san_pham', SanPham::DANH_SACH_LOAI_NGUYEN_LIEU);
            }

            if ($kw !== '') {
                $spQuery->where(function ($q) use ($kw, $cleanNum) {
                    $q->where('ten_san_pham', 'like', "%{$kw}%")
                      ->orWhere('loai_san_pham', 'like', "%{$kw}%")
                      ->orWhere('don_vi_tinh', 'like', "%{$kw}%");

                    if ($cleanNum !== '') {
                        $q->orWhere('ma_san_pham', (int) $cleanNum);
                    }
                });
            }

            if (!empty($loaiSanPham) && $loaiSanPham !== 'BO_HOA') {
                if ($loaiSanPham === 'PHU_KIEN' || $loaiSanPham === 'PHU_KIEN_TRANG_TRI') {
                    $spQuery->whereIn('loai_san_pham', ['PHU_KIEN', 'PHU_KIEN_TRANG_TRI']);
                } elseif ($loaiSanPham === 'HOA_TUOI') {
                    $spQuery->whereIn('loai_san_pham', ['HOA_TUOI', 'SAN_PHAM_PREMIUM']);
                } else {
                    $spQuery->where('loai_san_pham', $loaiSanPham);
                }
            }

            if ($trangThaiKd !== '') {
                $spStatus = in_array($trangThaiKd, ['ACTIVE', 'DANG_BAN'], true) ? 'DANG_BAN' : 'NGUNG_BAN';
                $spQuery->where('trang_thai', $spStatus);
            }

            $sanPhamModels = $spQuery->orderBy('ten_san_pham')->get();

            $sanPhamList = $sanPhamModels->map(function ($sp) {
                $onHand = (int) $sp->ton_thuc_te;
                $reserved = (int) $sp->ton_dang_giu;
                $available = (int) $sp->ton_kha_dung;
                $isNvl = in_array($sp->loai_san_pham, SanPham::DANH_SACH_LOAI_NGUYEN_LIEU, true);

                return (object) [
                    'is_bo_hoa'          => false,
                    'is_nguyen_vat_lieu' => $isNvl,
                    'loai_ton_kho'       => $isNvl ? 'nguyen_vat_lieu' : 'san_pham',
                    'loai_nhom_text'     => $isNvl ? 'Nguyên vật liệu' : 'Sản phẩm',
                    'ma_dinh_danh'       => '#' . $sp->ma_san_pham,
                    'ma_san_pham'        => $sp->ma_san_pham,
                    'id'                 => $sp->ma_san_pham,
                    'ten_san_pham'       => $sp->ten_san_pham,
                    'hinh_anh'           => $sp->hinh_anh,
                    'loai_san_pham'      => $sp->loai_san_pham,
                    'loai_badge_text'    => \App\Helpers\LabelHelper::loaiSanPham($sp->loai_san_pham),
                    'loai_badge_class'   => $isNvl
                        ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
                        : 'bg-info-subtle text-info-emphasis border border-info-subtle',
                    'don_vi_tinh_text'   => $sp->don_vi_tinh_text,
                    'gia_ban_hien_tai'   => (float) $sp->gia_ban_hien_tai,
                    'ton_thuc_te'        => $onHand,
                    'ton_dang_giu'       => $reserved,
                    'ton_kha_dung'       => $available,
                    'trang_thai_kd'      => $sp->trang_thai,
                ];
            });
        }

        // ── 2. NGUỒN DỮ LIỆU BÓ HOA THÀNH PHẨM (GOM THEO MẪU BÓ HOA) ──
        $boHoaList = collect();
        if ($layBoHoa) {
            $mauQuery = MauBoHoa::with([
                'boHoas' => fn ($q) => $q->where('loai_bo_hoa', 'HE_THONG')
                    ->where('trang_thai', 'ACTIVE')
                    ->whereNull('ma_hoa_don')
                    ->where('so_luong', '>', 0)
                    ->with(['reservations' => fn ($r) => $r->where('trang_thai', InventoryReservation::STATUS_RESERVED)]),
            ]);

            if ($kw !== '') {
                $kwNorm = mb_strtolower(trim($kw), 'UTF-8');
                $bouquetKeywords = ['bh', '#bh', 'bó hoa', 'bo hoa', 'bó hoa thành phẩm', 'bo hoa thanh pham', 'thành phẩm', 'thanh pham', 'bó sẵn', 'bo san', 'bó', 'bo'];
                $isGeneralBouquetWord = in_array($kwNorm, $bouquetKeywords, true);
                $cleanSize = trim(preg_replace('/^size\s*/i', '', $kw));

                $mauQuery->where(function ($q) use ($kw, $cleanNum, $isGeneralBouquetWord, $cleanSize) {
                    $q->where('ten_mau', 'like', "%{$kw}%")
                      ->orWhere('kieu_bo_hoa', 'like', "%{$kw}%")
                      ->orWhere('concept', 'like', "%{$kw}%");

                    if ($cleanNum !== '') {
                        $q->orWhere('ma_mau_bo_hoa', (int) $cleanNum);
                    }

                    if ($cleanSize !== '' && in_array(strtoupper($cleanSize), ['S', 'M', 'L', 'XL', 'SPECIAL'], true)) {
                        $q->orWhere('size', strtoupper($cleanSize));
                    }

                    if ($isGeneralBouquetWord) {
                        $q->orWhereRaw('1=1');
                    }
                });
            }

            if ($trangThaiKd !== '') {
                $statusMap = in_array($trangThaiKd, ['DANG_BAN', 'ACTIVE'], true) ? 'ACTIVE' : 'INACTIVE';
                $mauQuery->where('trang_thai', $statusMap);
            }

            $mauModels = $mauQuery->orderBy('ten_mau')->get();

            $kieuMap = [
                'ROUND'     => 'Tròn',
                'LONG'      => 'Dài',
                'HEART'     => 'Trái tim',
                'CASCADE'   => 'Thác đổ',
                'FREE'      => 'Tự do',
                'BASKET'    => 'Giỏ hoa',
                'BOX'       => 'Hộp hoa',
            ];

            $boHoaList = $mauModels->map(function ($m) use ($kieuMap) {
                $activeBos = $m->boHoas;
                $onHand = (int) $activeBos->sum('so_luong');
                $reserved = (int) $activeBos->flatMap->reservations->sum('so_luong');
                $available = max(0, $onHand - $reserved);

                $anhMau = $activeBos->first(fn ($b) => !empty($b->hinh_anh))?->hinh_anh;
                if (!$anhMau) {
                    if (file_exists(public_path("img/mau-bo-hoa/{$m->ma_mau_bo_hoa}.jpg"))) {
                        $anhMau = "img/mau-bo-hoa/{$m->ma_mau_bo_hoa}.jpg";
                    } elseif (file_exists(public_path("img/bo-hoa/{$m->ma_mau_bo_hoa}.jpg"))) {
                        $anhMau = "img/bo-hoa/{$m->ma_mau_bo_hoa}.jpg";
                    }
                }

                $giaBan = (float) ($activeBos->first()?->gia_ban ?? 0);
                $kieuText = $kieuMap[$m->kieu_bo_hoa] ?? $m->kieu_bo_hoa;
                $sizeText = 'Size ' . ($m->size === 'SPECIAL' ? 'Special' : $m->size);
                $chiTietPhu = "{$sizeText} · {$kieuText}" . ($m->concept ? " · {$m->concept}" : '');

                return (object) [
                    'is_bo_hoa'          => true,
                    'is_nguyen_vat_lieu' => false,
                    'loai_ton_kho'       => 'bo_hoa',
                    'loai_nhom_text'     => 'Bó hoa thành phẩm',
                    'ma_dinh_danh'       => '#BH' . $m->ma_mau_bo_hoa,
                    'ma_san_pham'       => $m->ma_mau_bo_hoa,
                    'ma_mau_bo_hoa'     => $m->ma_mau_bo_hoa,
                    'id'                => $m->ma_mau_bo_hoa,
                    'ten_san_pham'      => $m->ten_mau,
                    'hinh_anh'          => $anhMau,
                    'loai_san_pham'     => 'Bó hoa thành phẩm',
                    'loai_badge_text'   => 'Bó hoa thành phẩm',
                    'loai_badge_class'  => 'bg-purple-subtle text-purple border border-purple-subtle',
                    'don_vi_tinh_text'  => $chiTietPhu,
                    'gia_ban_hien_tai'  => $giaBan,
                    'ton_thuc_te'       => $onHand,
                    'ton_dang_giu'      => $reserved,
                    'ton_kha_dung'      => $available,
                    'trang_thai_kd'     => $m->trang_thai === 'ACTIVE' ? 'DANG_BAN' : 'NGUNG_BAN',
                    'size'              => $m->size,
                    'kieu_bo_hoa'       => $m->kieu_bo_hoa,
                    'concept'           => $m->concept,
                ];
            });
        }

        // ── 3. GỘP VÀ LỌC THEO TÌNH TRẠNG TỒN (tinh_trang_ton) ──
        $combined = $sanPhamList->concat($boHoaList);

        if ($tinhTrangTon !== '') {
            $combined = $combined->filter(function ($it) use ($tinhTrangTon) {
                return match ($tinhTrangTon) {
                    'con_hang' => $it->ton_kha_dung > 0,
                    'sap_het'  => $it->ton_kha_dung > 0 && $it->ton_kha_dung <= 10,
                    'het_hang' => $it->ton_kha_dung <= 0,
                    'dang_giu' => $it->ton_dang_giu > 0,
                    default    => true,
                };
            })->values();
        }

        // Sắp xếp theo tên hiển thị
        $sorted = $combined->sortBy('ten_san_pham')->values();

        // ── 4. PHÂN TRANG (LengthAwarePaginator) ──
        $page = (int) LengthAwarePaginator::resolveCurrentPage('page');
        $perPage = in_array((int) $request->get('per_page'), [10, 15, 20, 50]) ? (int) $request->get('per_page') : 10;
        $maxPage = max(1, (int) ceil($sorted->count() / $perPage));
        if ($page > $maxPage) {
            $page = 1;
        }
        $pageItems = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $items = new LengthAwarePaginator(
            $pageItems,
            $sorted->count(),
            $perPage,
            $page,
            [
                'path'  => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        // ── 5. TÍNH TOÁN KPI THỐNG KÊ TOÀN DIỆN ──
        $allSp = SanPham::with(['reservations' => fn ($q) => $q->where('trang_thai', InventoryReservation::STATUS_RESERVED)])->get();

        $calcKpi = function ($collection) {
            $onHand    = (int) $collection->sum('ton_thuc_te');
            $reserved  = (int) $collection->sum('ton_dang_giu');
            $available = max(0, $onHand - $reserved);
            $sapHet    = (int) $collection->filter(fn ($s) => $s->ton_kha_dung > 0 && $s->ton_kha_dung <= 10)->count();
            return [$collection->count(), $onHand, $reserved, $available, $sapHet];
        };

        $retailSp = $allSp->filter(fn ($s) => !in_array($s->loai_san_pham, SanPham::DANH_SACH_LOAI_NGUYEN_LIEU, true));
        $nvlSp    = $allSp->filter(fn ($s) => in_array($s->loai_san_pham, SanPham::DANH_SACH_LOAI_NGUYEN_LIEU, true));

        [$spCount, $spOnHand, $spReserved, $spAvailable, $spSapHet]       = $calcKpi($retailSp);
        [$nvlCount, $nvlOnHand, $nvlReserved, $nvlAvailable, $nvlSapHet] = $calcKpi($nvlSp);

        $allMau = MauBoHoa::with([
            'boHoas' => fn ($q) => $q->where('loai_bo_hoa', 'HE_THONG')
                ->where('trang_thai', 'ACTIVE')
                ->whereNull('ma_hoa_don')
                ->where('so_luong', '>', 0)
                ->with(['reservations' => fn ($r) => $r->where('trang_thai', InventoryReservation::STATUS_RESERVED)]),
        ])->get();

        $boOnHand = 0;
        $boReserved = 0;
        $boSapHet = 0;
        foreach ($allMau as $m) {
            $oh = (int) $m->boHoas->sum('so_luong');
            $rs = (int) $m->boHoas->flatMap->reservations->sum('so_luong');
            $av = max(0, $oh - $rs);
            $boOnHand += $oh;
            $boReserved += $rs;
            if ($av > 0 && $av <= 10) {
                $boSapHet++;
            }
        }
        $boAvailable = max(0, $boOnHand - $boReserved);
        $boCount = $allMau->count();

        if ($loaiTonKho === 'bo_hoa') {
            $tongMatHang    = $boCount;
            $tongTonThucTe  = $boOnHand;
            $tongDangGiu    = $boReserved;
            $tongKhaDung    = $boAvailable;
            $tongSapHetHang = $boSapHet;
        } elseif ($loaiTonKho === 'nguyen_vat_lieu') {
            $tongMatHang    = $nvlCount;
            $tongTonThucTe  = $nvlOnHand;
            $tongDangGiu    = $nvlReserved;
            $tongKhaDung    = $nvlAvailable;
            $tongSapHetHang = $nvlSapHet;
        } elseif ($loaiTonKho === 'san_pham') {
            $tongMatHang    = $spCount;
            $tongTonThucTe  = $spOnHand;
            $tongDangGiu    = $spReserved;
            $tongKhaDung    = $spAvailable;
            $tongSapHetHang = $spSapHet;
        } else {
            if ($loaiSanPham !== '') {
                if (in_array($loaiSanPham, SanPham::DANH_SACH_LOAI_NGUYEN_LIEU, true)) {
                    $tongMatHang    = $nvlCount;
                    $tongTonThucTe  = $nvlOnHand;
                    $tongDangGiu    = $nvlReserved;
                    $tongKhaDung    = $nvlAvailable;
                    $tongSapHetHang = $nvlSapHet;
                } else {
                    $tongMatHang    = $spCount;
                    $tongTonThucTe  = $spOnHand;
                    $tongDangGiu    = $spReserved;
                    $tongKhaDung    = $spAvailable;
                    $tongSapHetHang = $spSapHet;
                }
            } else {
                $tongMatHang    = $spCount + $nvlCount + $boCount;
                $tongTonThucTe  = $spOnHand + $nvlOnHand + $boOnHand;
                $tongDangGiu    = $spReserved + $nvlReserved + $boReserved;
                $tongKhaDung    = $spAvailable + $nvlAvailable + $boAvailable;
                $tongSapHetHang = $spSapHet + $nvlSapHet + $boSapHet;
            }
        }

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
                'ton_thuc_te'  => (int) $sp->ton_thuc_te,
                'ton_dang_giu' => (int) $reservations->sum('so_luong'),
                'ton_kha_dung' => max(0, (int) $sp->so_luong - (int) $reservations->sum('so_luong')),
            ],
            'reservations' => $reservations,
        ]);
    }

    /**
     * API chi tiết các đơn hàng đang giữ Bó hoa thành phẩm này (phục vụ modal).
     */
    public function chiTietGiuBoHoa(int $id): JsonResponse
    {
        $mau = MauBoHoa::findOrFail($id);

        $activeBouquets = BoHoa::where('ma_mau_bo_hoa', $id)
            ->where('loai_bo_hoa', 'HE_THONG')
            ->where('trang_thai', 'ACTIVE')
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->get();

        $boIds = $activeBouquets->pluck('ma_bo_hoa')->all();

        $stock = InventoryReservationService::getStockForTemplate($id);

        $reservations = InventoryReservation::with(['hoaDon.khachHang', 'boHoa'])
            ->whereIn('ma_bo_hoa', $boIds)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($res) use ($mau) {
                return [
                    'id'               => $res->id,
                    'ma_hoa_don'       => $res->ma_hoa_don,
                    'khach_hang'       => $res->hoaDon?->khachHang?->ho_ten ?? 'Khách vãng lai',
                    'so_dien_thoai'    => $res->hoaDon?->so_dien_thoai ?? '',
                    'so_luong'         => (int) $res->so_luong,
                    'ma_chi_tiet_nhap' => null,
                    'so_lo'            => 'Bó thành phẩm',
                    'ten_bo_hoa'       => $res->boHoa?->ten_bo_hoa ?? $mau->ten_mau,
                    'thoi_gian'        => $res->created_at?->format('H:i d/m/Y') ?? '',
                ];
            });

        return response()->json([
            'san_pham' => [
                'ma_san_pham'  => $mau->ma_mau_bo_hoa,
                'ten_san_pham' => $mau->ten_mau,
                'don_vi_tinh'  => 'bó',
                'ton_thuc_te'  => $stock['on_hand'],
                'ton_dang_giu' => $stock['reserved'],
                'ton_kha_dung' => $stock['available'],
                'size'         => $mau->size,
                'kieu_bo_hoa'  => $mau->kieu_bo_hoa,
                'concept'      => $mau->concept,
            ],
            'reservations' => $reservations,
        ]);
    }
}
