<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Admin\BoHoaController as Bo;
use App\Http\Controllers\Controller;
use App\Models\{BoHoa, ChiTietHoaDon, HoaDon, KhachHang, MauBoHoa, PhuPhiBoHoa, SanPham};
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BoHoaController extends Controller
{
    /* ═══════════════════════════════════════════════════════════════════
       TRANG KHÁCH — DANH SÁCH MẪU
    ═══════════════════════════════════════════════════════════════════ */
    public function heThong(Request $request)
    {
        $query = MauBoHoa::where('trang_thai', 'ACTIVE')
            ->with('chiTietMauBoHoa.sanPham');

        // Lọc theo danh mục bó hoa (tên mẫu / chủ đề)
        if ($request->filled('loai')) {
            $query->whereIn('ten_mau', (array) $request->loai);
        }

        // Lọc theo kích thước size
        if ($request->filled('size')) {
            $query->whereIn('size', (array) $request->size);
        }

        $activeReadyBouquets = BoHoa::where('loai_bo_hoa', 'HE_THONG')
            ->where('trang_thai', 'ACTIVE')
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->get()
            ->groupBy('ma_mau_bo_hoa');

        $mauList = $query->withCount(['boHoas as da_ban' => fn ($q) => $q->whereNotNull('ma_hoa_don')])
            ->orderByDesc('ma_mau_bo_hoa')
            ->get();

        // Tồn khả dụng của toàn bộ nguyên liệu trong trang — 1 lần truy vấn, tránh N+1.
        $spIds = $mauList
            ->flatMap(fn ($m) => $m->chiTietMauBoHoa->pluck('ma_san_pham'))
            ->filter()->unique()->values()->all();
        $availMap = \App\Services\InventoryReservationService::getBatchAvailableForIngredients($spIds);

        $allMaus = $mauList->map(function ($m) use ($activeReadyBouquets, $availMap) {
            $kd = self::kiemTraKhaDungMau($m, 1, null, $availMap);

            $m->het_hang           = !$kd['kha_dung'];
            $m->ly_do_khong_ban    = $kd['ly_do'];          // null | OUT_OF_STOCK | FORMULA_EMPTY
            $m->thieu_nguyen_lieu  = $kd['thieu'];
            $m->gia_hien_thi       = null;                  // null = KHÔNG có giá hợp lệ, view không được in 0đ

            if (!$kd['kha_dung']) {
                return $m;
            }

            // Còn bó thành phẩm → giá của bó thành phẩm (bỏ qua nếu dữ liệu cũ gia_ban = 0)
            if ($kd['trang_thai'] === self::TT_CON_BO_THANH_PHAM) {
                $readyBo = $activeReadyBouquets->get($m->ma_mau_bo_hoa)?->first();
                if ($readyBo && (float) $readyBo->gia_ban > 0) {
                    $m->gia_hien_thi = (float) $readyBo->gia_ban;
                    return $m;
                }
            }

            // Hết bó thành phẩm nhưng đủ nguyên liệu → giá ước tính
            $m->gia_hien_thi = self::tinhGiaUocTinhMau($m);
            if ($m->gia_hien_thi === null) {
                $m->het_hang        = true;
                $m->ly_do_khong_ban = 'PRICE_UNAVAILABLE';
            }
            return $m;
        });

        // Sắp xếp: Ưu tiên các mẫu còn hàng lên đầu -> Bán chạy nhất (da_ban desc) -> Mới nhất
        $allMaus = $allMaus->sortBy([
            fn ($a, $b) => ($a->het_hang ? 1 : 0) <=> ($b->het_hang ? 1 : 0),
            fn ($a, $b) => ($b->da_ban ?? 0) <=> ($a->da_ban ?? 0),
            fn ($a, $b) => $b->ma_mau_bo_hoa <=> $a->ma_mau_bo_hoa,
        ])->values();

        // Lọc theo khoảng giá
        if ($request->filled('price')) {
            $prices = (array) $request->price;
            $allMaus = $allMaus->filter(function ($m) use ($prices) {
                $price = $m->gia_hien_thi;
                if ($price === null) return false;
                foreach ($prices as $p) {
                    if ($p === 'duoi_350000' && $price < 350000) return true;
                    if ($p === '350000_450000' && $price >= 350000 && $price <= 450000) return true;
                    if ($p === '450000_550000' && $price >= 450000 && $price <= 550000) return true;
                    if ($p === 'tren_550000' && $price > 550000) return true;
                }
                return false;
            })->values();
        }

        $page = (int) $request->input('page', 1);
        $perPage = 12;
        $mauBoHoas = new LengthAwarePaginator(
            $allMaus->forPage($page, $perPage)->values(),
            $allMaus->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $categories = MauBoHoa::where('trang_thai', 'ACTIVE')
            ->distinct()
            ->orderBy('ten_mau')
            ->pluck('ten_mau');

        return view('customer.bohoahethong', compact('mauBoHoas', 'categories'));
    }

    /* ═══════════════════════════════════════════════════════════════════
       TỰ TẠO
    ═══════════════════════════════════════════════════════════════════ */
    public function create()
    {
        $nguyenLieuRaw = SanPham::whereIn('loai_san_pham', [
                'HOA_TUOI', 'HOA_GIA', 'HOA_SAP', 'HOA_GIAY_NHUN',
                'GIAY_GOI', 'RUY_BANG', 'PHU_KIEN_TRANG_TRI', 'MIENG_XOP',
            ])
            ->where('trang_thai', 'DANG_BAN')
            ->orderBy('ten_san_pham')
            ->get();

        $allSpIds = $nguyenLieuRaw->pluck('ma_san_pham')->all();
        $availMap = \App\Services\InventoryReservationService::getBatchAvailableForIngredients($allSpIds);

        $nguyenLieu = $nguyenLieuRaw->map(fn ($sp) => [
            'ma_san_pham'      => $sp->ma_san_pham,
            'ten_san_pham'     => $sp->ten_san_pham,
            'loai_san_pham'    => $sp->loai_san_pham,
            'don_vi_tinh'      => $sp->don_vi_tinh,
            'don_vi_tinh_text' => $sp->don_vi_tinh_text ?: ($sp->don_vi_tinh ?: 'cái'),
            'gia_ban_hien_tai' => (float) ($sp->gia_ban_hien_tai ?? 0),
            'anh'              => $sp->anh,
            'ton_kho'          => (int) ($availMap[$sp->ma_san_pham] ?? 0),
            'available'        => (int) ($availMap[$sp->ma_san_pham] ?? 0),
        ])->values();

        $phuPhi   = PhuPhiBoHoa::all();
        $duLieuCu = session('bo_hoa_tu_tao_nhap');

        return view('customer.bohoatuchon', [
            'nguyenLieu'    => $nguyenLieu,
            'phuPhi'        => $phuPhi,
            'duLieuCu'      => $duLieuCu,
            'SO_BONG'       => Bo::SO_BONG,
            'KIEU_DANG'     => Bo::KIEU_DANG,
            'CONCEPT'       => Bo::CONCEPT,
            'VAI_TRO'       => Bo::VAI_TRO,
            'GIAY_GOI_QTY'  => Bo::GIAY_GOI_QTY,
            'RUY_BANG_QTY'  => Bo::RUY_BANG_QTY,
            'XOP_QTY'       => Bo::XOP_QTY,
            'PHU_KIEN_MAX'  => Bo::PHU_KIEN_MAX,
        ]);
    }

    public function quote(Request $request)
    {
        return app(Bo::class)->json(
            function () use ($request) {
                $data = $this->validateTuTao($request, false);
                return Bo::tinhGiaDuLieu($data);
            },
            'Giá tạm tính, kiểm tra lại khi đặt hàng.'
        );
    }

    public function xacNhanTuTao(Request $request)
    {
        $data = $this->validateTuTao($request, true);
        $data['so_luong'] = min(10, max(1, (int) $data['so_luong']));

        // Validate ảnh mẫu mong muốn (tối đa 3 ảnh, mỗi ảnh <= 5MB)
        $request->validate([
            'anh_mau'       => 'nullable|array|max:3',
            'anh_mau.*'     => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'giu_anh_cu'    => 'nullable|array',
            'giu_anh_cu.*'  => 'string',
        ], [
            'anh_mau.max'     => 'Bạn chỉ được tải lên tối đa 3 ảnh mẫu.',
            'anh_mau.*.image' => 'File tải lên phải là hình ảnh.',
            'anh_mau.*.mimes' => 'Chỉ chấp nhận ảnh định dạng JPG, JPEG, PNG, WEBP.',
            'anh_mau.*.max'   => 'Kích thước mỗi ảnh không được vượt quá 5MB.',
        ]);

        $anhMauPaths = [];

        // Giữ lại ảnh cũ từ session nếu người dùng không bấm xóa
        if ($request->filled('giu_anh_cu') && is_array($request->input('giu_anh_cu'))) {
            foreach ($request->input('giu_anh_cu') as $oldPath) {
                $cleanPath = basename($oldPath);
                $relPath = 'custom-mix/' . $cleanPath;
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relPath)) {
                    $anhMauPaths[] = $relPath;
                }
            }
        }

        // Upload ảnh mới
        if ($request->hasFile('anh_mau')) {
            foreach ($request->file('anh_mau') as $file) {
                if (count($anhMauPaths) >= 3) break;
                if ($file && $file->isValid()) {
                    $path = $file->store('custom-mix', 'public');
                    $anhMauPaths[] = $path;
                }
            }
        }

        $quote = Bo::tinhGiaDuLieu($data);
        $existingDraftId = session('bo_hoa_tu_tao_nhap.draft_id');

        session(['bo_hoa_tu_tao_nhap' => [
            'ten_bo_hoa'  => $data['ten_bo_hoa'],
            'size'        => $data['size'],
            'kieu_bo_hoa' => $data['kieu_bo_hoa'],
            'concept'     => $data['concept'],
            'so_luong'    => $data['so_luong'],
            'thanh_phan'  => $data['thanh_phan'],
            'anh_mau'     => $anhMauPaths,
            'quote'       => $quote,
            'draft_id'    => $existingDraftId,
        ]]);

        return redirect()->route('customer.bo-hoa-tu-tao.chi-tiet');
    }

    public function tiepTucTuTao(Request $request, int $id)
    {
        $khachId = $this->khachId($request);

        $draft = BoHoa::tuyChon()->draft()
            ->where('ma_bo_hoa', $id)
            ->where('ma_khach_hang', $khachId)
            ->whereNull('ma_hoa_don')
            ->with(['chiTietBoHoa', 'customMixImages'])
            ->firstOrFail();

        session(['bo_hoa_tu_tao_nhap' => [
            'ten_bo_hoa'  => $draft->ten_bo_hoa,
            'size'        => $draft->size,
            'kieu_bo_hoa' => $draft->kieu_bo_hoa,
            'concept'     => $draft->concept,
            'so_luong'    => $draft->so_luong,
            'thanh_phan'  => $draft->chiTietBoHoa->map(fn ($c) => [
                'ma_san_pham' => $c->ma_san_pham,
                'vai_tro'     => $c->vai_tro,
                'so_luong'    => $c->so_luong,
            ])->all(),
            'anh_mau'     => $draft->customMixImages->pluck('path')->all(),
            'draft_id'    => $draft->ma_bo_hoa,
        ]]);

        return redirect()->route('customer.bo-hoa.tu-tao')
            ->with('info', 'Đã tải lại thiết kế bản nháp. Bạn có thể chỉnh sửa và xác nhận lại.');
    }

    public function muaTuTao(Request $request)
    {
        $nhap = session('bo_hoa_tu_tao_nhap');
        if (!$nhap) {
            return redirect()->route('customer.bo-hoa.tu-tao')
                ->with('error', 'Phiên thiết kế đã hết hạn, vui lòng tạo lại.');
        }

        $data = $request->validate([
            'loi_nhan' => 'nullable|string|max:2000',
            'ma_thiep' => 'nullable|integer|exists:san_pham,ma_san_pham',
        ]);
        $loiNhan = $data['loi_nhan'] ?? null;
        $maThiep = $data['ma_thiep'] ?? null;

        $khachId = $this->khachId($request);

        $draft = DB::transaction(function () use ($nhap, $khachId, $loiNhan, $maThiep) {
            $existing = null;
            if (!empty($nhap['draft_id'])) {
                $existing = BoHoa::tuyChon()->draft()
                    ->where('ma_bo_hoa', $nhap['draft_id'])
                    ->where('ma_khach_hang', $khachId)
                    ->whereNull('ma_hoa_don')
                    ->lockForUpdate()
                    ->first();
            }

            $firstImg = !empty($nhap['anh_mau'][0]) ? 'storage/' . $nhap['anh_mau'][0] : null;

            $payload = [
                'ma_khach_hang' => $khachId,
                'ten_bo_hoa'    => $nhap['ten_bo_hoa'],
                'size'          => $nhap['size'],
                'kieu_bo_hoa'   => $nhap['kieu_bo_hoa'],
                'concept'       => $nhap['concept'],
                'so_luong'      => $nhap['so_luong'],
                'loai_bo_hoa'   => 'TUY_CHON',
                'trang_thai'    => 'DRAFT',
                'loi_nhan'      => $loiNhan,
                'hinh_anh'      => $firstImg,
            ];

            if ($existing) {
                $existing->update($payload);
                $existing->chiTietBoHoa()->delete();
                $existing->chiTietBoHoa()->createMany($nhap['thanh_phan']);
                $draft = $existing;
            } else {
                $draft = BoHoa::create($payload);
                $draft->chiTietBoHoa()->createMany($nhap['thanh_phan']);
            }

            // Lưu danh sách ảnh mẫu mong muốn vào custom_mix_images
            $draft->customMixImages()->delete();
            if (!empty($nhap['anh_mau']) && is_array($nhap['anh_mau'])) {
                foreach ($nhap['anh_mau'] as $idx => $path) {
                    $draft->customMixImages()->create([
                        'path'       => $path,
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            // Dọn dẹp bản nháp cũ chưa chốt
            $old = BoHoa::tuyChon()->draft()
                ->where('ma_khach_hang', $khachId)
                ->where('ma_bo_hoa', '!=', $draft->ma_bo_hoa)
                ->whereNull('ma_hoa_don')
                ->get();
            foreach ($old as $o) {
                $o->customMixImages()->delete();
                $o->chiTietBoHoa()->delete();
                $o->delete();
            }

            return $draft;
        }, 3);

        session()->forget('bo_hoa_tu_tao_nhap');
        session(['bo_hoa_checkout' => [
            'loai'      => 'TUY_CHON',
            'ma_bo_hoa' => $draft->ma_bo_hoa,
            'so_luong'  => (int) $draft->so_luong,
            'ma_thiep'  => $maThiep,
            'loi_nhan'  => $loiNhan,
        ]]);
        session()->forget(['mua_ngay', 'checkout_items', 'diem_su_dung']);

        return redirect()->route('customer.thanh-toan');
    }

    /* ═══════════════════════════════════════════════════════════════════
       MUA MẪU TRỰC TIẾP (Mua ngay — không qua giỏ bó hoa)
    ═══════════════════════════════════════════════════════════════════ */
    public function muaMau(Request $request, int $id)
    {
        $data = $request->validate([
            'so_luong' => 'required|integer|min:1|max:10',
            'ma_thiep' => 'nullable|integer|exists:san_pham,ma_san_pham',
            'loi_nhan' => 'nullable|string|max:2000',
        ], [
            'so_luong.required' => 'Vui lòng nhập số lượng.',
            'so_luong.min'      => 'Số lượng phải từ 1 trở lên.',
            'so_luong.max'      => 'Số lượng tối đa 10 bó mỗi lần.',
        ]);

        $checkout = [
            'loai'          => 'MAU',
            'ma_mau_bo_hoa' => $id,
            'so_luong'      => $data['so_luong'],
            'ma_thiep'      => $data['ma_thiep'] ?? null,
            'loi_nhan'      => $data['loi_nhan'] ?? null,
        ];

        try {
            $khachId = $this->khachId($request);
            self::duLieuCheckout($checkout, $khachId);
        } catch (ValidationException $e) {
            return redirect()->back()
                ->with('error', collect($e->errors())->flatten()->first() ?? 'Nguyên liệu/thiệp không hợp lệ.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        session(['bo_hoa_checkout' => $checkout]);
        session()->forget(['mua_ngay', 'checkout_items', 'diem_su_dung']);

        return redirect()->route('customer.thanh-toan');
    }

    /* ═══════════════════════════════════════════════════════════════════
       GIỎ BÓ HOA MẪU (hướng C — tách riêng khỏi giỏ sản phẩm đơn lẻ)
       - Chỉ thêm / xóa, KHÔNG sửa số lượng.
       - Không giữ kho khi thêm vào giỏ.
    ═══════════════════════════════════════════════════════════════════ */

    public function gioBoHoa()
    {
        $khachId  = $this->khachId(request());
        $gioBoHoa = session('gio_bo_hoa', []);

        $dsItem = [];
        foreach ($gioBoHoa as $key => $item) {
            $mau = MauBoHoa::with('chiTietMauBoHoa.sanPham')->find($item['ma_mau_bo_hoa']);
            if (!$mau) continue;

            $thiep = !empty($item['ma_thiep']) ? SanPham::find($item['ma_thiep']) : null;

            try {
                $info = self::duLieuCheckout([
                    'loai'          => 'MAU',
                    'ma_mau_bo_hoa' => $item['ma_mau_bo_hoa'],
                    'so_luong'      => $item['so_luong'],
                    'ma_thiep'      => $item['ma_thiep'] ?? null,
                    'loi_nhan'      => $item['loi_nhan'] ?? null,
                ], $khachId);
                $giaMotBo = (float) $info['quote']['gia_moi_bo'];
                $tongItem = (float) $info['quote']['tong_tien'];
                $khongKhaDung = false;
                $lyDoLoi      = null;
            } catch (\Throwable $e) {
                $giaMotBo     = 0;
                $tongItem     = 0;
                $khongKhaDung = true;                       // view GioBoHoa nên hiện "Hết hàng" + chặn thanh toán
                $lyDoLoi      = $e->getMessage();
            }

            $dsItem[$key] = [
                'mau'       => $mau,
                'thiep'     => $thiep,
                'so_luong'  => (int) $item['so_luong'],
                'loi_nhan'  => $item['loi_nhan'] ?? null,
                'gia_mot_bo'=> $giaMotBo,
                'tong'      => $tongItem,
                'khong_kha_dung' => $khongKhaDung,
                'ly_do_loi'      => $lyDoLoi,
            ];
        }

        return view('customer.GioBoHoa', compact('dsItem'));
    }

    public function themVaoGio(Request $request)
    {
        $data = $request->validate([
            'ma_mau_bo_hoa' => 'required|integer|exists:mau_bo_hoa,ma_mau_bo_hoa',
            'so_luong'      => 'required|integer|min:1|max:10',
            'ma_thiep'      => 'nullable|integer|exists:san_pham,ma_san_pham',
            'loi_nhan'      => 'nullable|string|max:2000',
        ], [
            'so_luong.max' => 'Số lượng tối đa 10 bó mỗi lần.',
        ]);

        // Chỉ add khi tính giá được (NVL đang bán, tồn đủ, thiệp hợp lệ)
        try {
            $khachId = $this->khachId($request);
            self::duLieuCheckout([
                'loai'          => 'MAU',
                'ma_mau_bo_hoa' => $data['ma_mau_bo_hoa'],
                'so_luong'      => $data['so_luong'],
                'ma_thiep'      => $data['ma_thiep'] ?? null,
                'loi_nhan'      => $data['loi_nhan'] ?? null,
            ], $khachId);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage() ?: 'Không thể thêm bó hoa vào giỏ.');
        }

        $gio = session('gio_bo_hoa', []);
        $key = uniqid('bo_');
        $gio[$key] = [
            'ma_mau_bo_hoa' => (int) $data['ma_mau_bo_hoa'],
            'ma_thiep'      => $data['ma_thiep'] ?? null,
            'so_luong'      => (int) $data['so_luong'],
            'loi_nhan'      => $data['loi_nhan'] ?? null,
        ];
        session(['gio_bo_hoa' => $gio]);
        \App\Services\CartService::syncToDatabase();

        return redirect()->route('customer.gio-hang')
            ->with('success', 'Đã thêm bó hoa vào giỏ hàng!');
    }

    public function xoaKhoiGio(Request $request)
    {
        $key = $request->input('key');
        $gio = session('gio_bo_hoa', []);

        if (isset($gio[$key])) {
            unset($gio[$key]);
            session(['gio_bo_hoa' => $gio]);
            \App\Services\CartService::syncToDatabase();
        }

        $tongSoLuong = count(session('gio_hang', [])) + count($gio);

        return response()->json([
            'success'     => true,
            'so_bo_hoa'   => count($gio),
            'so_san_pham' => $tongSoLuong,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════════
       HELPERS
    ═══════════════════════════════════════════════════════════════════ */
    private function validateTuTao(Request $request, bool $strict): array
    {
        $data = Bo::validateBo($request);

        if ($strict && empty($data['concept'])) {
            throw ValidationException::withMessages(['concept' => 'Vui lòng chọn concept cho bó hoa.']);
        }

        Bo::kiemTraTrungHoaChinh($data);

        // Kiểm tra tồn kho khả dụng thực tế của từng nguyên liệu nhân theo số lượng bó đặt
        $soLuongBo = max(1, (int) ($data['so_luong'] ?? 1));
        foreach ($data['thanh_phan'] as $row) {
            $spId = $row['ma_san_pham'];
            $soLuongCan = $row['so_luong'] * $soLuongBo;
            $avail = \App\Services\InventoryReservationService::getAvailableForIngredient($spId);
            if ($avail < $soLuongCan) {
                $sp = SanPham::find($spId);
                $ten = $sp?->ten_san_pham ?? "ID #{$spId}";
                $dvt = $sp?->don_vi_tinh_text ?: ($sp?->don_vi_tinh ?: 'đơn vị');
                throw ValidationException::withMessages([
                    'bo_hoa' => "Nguyên liệu \"{$ten}\" không đủ tồn kho khả dụng. Cần tổng {$soLuongCan} {$dvt} cho {$soLuongBo} bó, hiện chỉ còn {$avail} {$dvt}."
                ]);
            }
        }

        return $data;
    }

    /**
     * Giá ước tính 1 bó theo công thức mẫu (khi hết bó thành phẩm).
     * Trả về NULL (không phải 0) nếu không tính được giá — caller phải coi là "không bán được".
     */
    public static function tinhGiaUocTinhMau($m): ?float
    {
        $formula = collect($m->chiTietMauBoHoa ?? [])->map(fn ($c) => [
            'ma_san_pham' => $c->ma_san_pham,
            'vai_tro'     => $c->vai_tro,
            'so_luong'    => $c->so_luong,
        ])->all();

        try {
            $rows = Bo::phanBo($formula, false);
            $gia  = (float) Bo::tinhGiaVonVaGiaBan($rows)['gia_ban'];
            return $gia > 0 ? $gia : null;
        } catch (ValidationException $e) {
            // Thiếu tồn / ngừng bán… là tình huống nghiệp vụ bình thường → không có giá.
            return null;
        } catch (\Throwable $e) {
            // Lỗi bất ngờ (DB, dữ liệu hỏng…) → KHÔNG được che giấu, ghi log để còn biết.
            report($e);
            return null;
        }
    }

    public const TT_CON_BO_THANH_PHAM = 'CON_BO_THANH_PHAM';
    public const TT_DU_NGUYEN_LIEU    = 'DU_NGUYEN_LIEU';
    public const TT_HET_HANG          = 'HET_HANG';

    /**
     * Quyết định "mẫu này có bán được không" — nguồn sự thật DUY NHẤT cho cả
     * trang danh sách lẫn checkout (duLieuCheckout).
     *
     * - CON_BO_THANH_PHAM : còn bó thành phẩm khả dụng đủ số lượng (không kèm thiệp).
     * - DU_NGUYEN_LIEU    : hết/thiếu bó thành phẩm nhưng nguyên liệu đủ để làm theo đơn.
     * - HET_HANG          : có ít nhất 1 nguyên liệu không đủ tồn khả dụng
     *                       (đã trừ hàng giữ chỗ, bỏ lô quá hạn, bỏ SP ngừng bán).
     *
     * @param array<int,int>|null $availMap [ma_san_pham => tồn khả dụng]; truyền sẵn khi chạy hàng loạt.
     * @return array{kha_dung:bool,trang_thai:string,ly_do:?string,thieu:array<int,array{ma_san_pham:int,ten:string,can:int,con:int}>}
     */
    public static function kiemTraKhaDungMau(MauBoHoa $mau, int $soLuong = 1, ?SanPham $thiep = null, ?array $availMap = null): array
    {
        $soLuong   = max(1, $soLuong);
        $thanhPhan = $mau->relationLoaded('chiTietMauBoHoa')
            ? $mau->chiTietMauBoHoa
            : $mau->chiTietMauBoHoa()->with('sanPham')->get();

        // 1. Bó thành phẩm còn khả dụng (bó sẵn không có thiệp → có thiệp thì phải làm theo đơn)
        $sanCo = $thiep
            ? 0
            : (int) \App\Services\InventoryReservationService::getStockForTemplate((int) $mau->getKey())['available'];
        $canLam = max(0, $soLuong - $sanCo);

        if ($canLam === 0) {
            return ['kha_dung' => true, 'trang_thai' => self::TT_CON_BO_THANH_PHAM, 'ly_do' => null, 'thieu' => []];
        }

        if ($thanhPhan->isEmpty()) {
            return ['kha_dung' => false, 'trang_thai' => self::TT_HET_HANG, 'ly_do' => 'FORMULA_EMPTY', 'thieu' => []];
        }

        // 2. Cộng dồn nhu cầu theo sản phẩm (cùng 1 SP có thể xuất hiện nhiều dòng công thức)
        $can = [];
        $ten = [];
        foreach ($thanhPhan as $c) {
            $id = (int) $c->ma_san_pham;
            $can[$id] = ($can[$id] ?? 0) + (int) $c->so_luong * $canLam;
            $ten[$id] = $c->sanPham?->ten_san_pham ?? "Nguyên liệu #{$id}";
        }
        if ($thiep) {
            $id = (int) $thiep->ma_san_pham;
            $can[$id] = ($can[$id] ?? 0) + $canLam;
            $ten[$id] = $thiep->ten_san_pham;
        }

        $availMap ??= \App\Services\InventoryReservationService::getBatchAvailableForIngredients(array_keys($can));

        $thieu = [];
        foreach ($can as $id => $canId) {
            $con = (int) ($availMap[$id] ?? 0);   // SP không tồn tại → 0
            if ($con < $canId) {
                $thieu[] = ['ma_san_pham' => $id, 'ten' => $ten[$id], 'can' => $canId, 'con' => $con];
            }
        }

        if ($thieu) {
            return ['kha_dung' => false, 'trang_thai' => self::TT_HET_HANG, 'ly_do' => 'OUT_OF_STOCK', 'thieu' => $thieu];
        }

        return ['kha_dung' => true, 'trang_thai' => self::TT_DU_NGUYEN_LIEU, 'ly_do' => null, 'thieu' => []];
    }

    private static function thongBaoHetHang(string $tenMau, array $kd): string
    {
        if (empty($kd['thieu'])) {
            return "Mẫu \"{$tenMau}\" hiện chưa thể bán (chưa có công thức nguyên liệu).";
        }
        $t   = $kd['thieu'][0];
        $them = count($kd['thieu']) > 1 ? ' và ' . (count($kd['thieu']) - 1) . ' nguyên liệu khác' : '';
        return "Mẫu \"{$tenMau}\" tạm hết hàng: nguyên liệu \"{$t['ten']}\" cần {$t['can']}, hiện còn {$t['con']}{$them}.";
    }

    /**
     * Chuẩn bị dữ liệu 1 bó để checkout.
     */
    public static function duLieuCheckout(array $checkout, int $khachId): array
    {
        if (($checkout['loai'] ?? '') === 'MAU') {
            $source = MauBoHoa::where('trang_thai', 'ACTIVE')
                ->with('chiTietMauBoHoa.sanPham')
                ->findOrFail($checkout['ma_mau_bo_hoa']);
            $ten = $source->ten_mau;
            $thanhPhan = $source->chiTietMauBoHoa;
        } else {
            $source = BoHoa::tuyChon()->draft()->where('ma_khach_hang', $khachId)
                ->with(['chiTietBoHoa.sanPham', 'customMixImages'])
                ->findOrFail($checkout['ma_bo_hoa']);
            $ten = $source->ten_bo_hoa;
            $thanhPhan = $source->chiTietBoHoa;
        }

        // Validate NVL đang bán
        try {
            Bo::kiemTraNguyenLieuDangBan(
                collect($thanhPhan)->map(fn ($c) => ['ma_san_pham' => $c->ma_san_pham])->all()
            );
        } catch (ValidationException $e) {
            throw new \RuntimeException(
                collect($e->errors())->flatten()->first() ?? 'Nguyên vật liệu không hợp lệ.'
            );
        }

        // Validate tồn kho khả dụng cho Custom Mix theo số lượng bó
        if (($checkout['loai'] ?? '') === 'TUY_CHON') {
            $soLuongBo = (int) ($checkout['so_luong'] ?? 1);
            foreach ($thanhPhan as $ct) {
                $can = $ct->so_luong * $soLuongBo;
                $avail = \App\Services\InventoryReservationService::getAvailableForIngredient($ct->ma_san_pham);
                if ($avail < $can) {
                    $tenSp = $ct->sanPham?->ten_san_pham ?? "Nguyên liệu #{$ct->ma_san_pham}";
                    $dvt = $ct->sanPham?->don_vi_tinh_text ?: ($ct->sanPham?->don_vi_tinh ?: 'đơn vị');
                    throw new \RuntimeException("Nguyên liệu \"{$tenSp}\" không đủ tồn kho khả dụng (cần tổng {$can} {$dvt} cho {$soLuongBo} bó, hiện còn {$avail} {$dvt}).");
                }
            }
        }

        // Validate thiệp (nếu có)
        $thiep = null;
        if (!empty($checkout['ma_thiep'])) {
            $thiep = SanPham::whereKey($checkout['ma_thiep'])
                ->where('loai_san_pham', 'THIEP')
                ->where('trang_thai', 'DANG_BAN')
                ->first();

            if (!$thiep) {
                throw new \RuntimeException('Thiệp đã chọn không tồn tại hoặc đã ngừng bán.');
            }

            // Check tồn kho thiệp — cần ít nhất = số bó (1 thiệp/bó)
            $soLuongCan = (int) ($checkout['so_luong'] ?? 1);
            $ton = \App\Services\InventoryReservationService::getAvailableForIngredient($thiep->ma_san_pham);

            if ($ton < $soLuongCan) {
                throw new \RuntimeException(
                    "Thiệp \"{$thiep->ten_san_pham}\" chỉ còn {$ton} khả dụng, không đủ cho {$soLuongCan} bó."
                );
            }
        }

        // Mẫu bó hoa: PHẢI đủ bó thành phẩm hoặc đủ nguyên liệu (đã trừ hàng giữ chỗ).
        // Trước đây nhánh MAU không kiểm tra → khách vẫn "mua" được mẫu hết nguyên liệu với giá 0đ.
        if (($checkout['loai'] ?? '') === 'MAU') {
            $kd = self::kiemTraKhaDungMau($source, (int) ($checkout['so_luong'] ?? 1), $thiep);
            if (!$kd['kha_dung']) {
                throw new \RuntimeException(self::thongBaoHetHang($ten, $kd));
            }
        }

        // Formula cho 1 bó
        $formula = collect($thanhPhan)->map(fn ($c) => [
            'ma_san_pham' => $c->ma_san_pham,
            'vai_tro'     => $c->vai_tro,
            'so_luong'    => $c->so_luong,
        ])->all();

        // Nếu có thiệp → thêm 1 dòng THIEP vào formula (mỗi bó 1 thiệp)
        if ($thiep) {
            $formula[] = [
                'ma_san_pham' => $thiep->ma_san_pham,
                'vai_tro'     => 'THIEP',
                'so_luong'    => 1,
            ];
        }

        if (($checkout['loai'] ?? '') === 'MAU') {
            $soLuong = max(1, (int) ($checkout['so_luong'] ?? 1));
            $readyBo = !$thiep ? BoHoa::where('loai_bo_hoa', 'HE_THONG')
                ->whereNull('ma_hoa_don')
                ->where('ma_mau_bo_hoa', $source->getKey())
                ->where('so_luong', '>', 0)
                ->where('trang_thai', 'ACTIVE')
                ->orderBy('ma_bo_hoa')
                ->first() : null;

            if ($readyBo && (float) $readyBo->gia_ban > 0) {
                $giaMoiBo = (float) $readyBo->gia_ban;
                $tienThiep = $thiep ? ((float) ($thiep->gia_ban_hien_tai ?? 0)) * $soLuong : 0;
                $quote = [
                    'gia_moi_bo'       => $giaMoiBo + ($thiep ? (float) ($thiep->gia_ban_hien_tai ?? 0) : 0),
                    'tong_tien'        => round($giaMoiBo * $soLuong + $tienThiep, 2),
                    'tien_nguyen_lieu' => (float) ($readyBo->gia_von ?? round($giaMoiBo / 1.5, 2)) * $soLuong,
                    'phu_phi'          => ['size' => 0, 'kieu_dang' => 0, 'tong' => 0],
                    'gia_von_moi_bo'   => (float) ($readyBo->gia_von ?? round($giaMoiBo / 1.5, 2)),
                ];
            } else {
                // Không còn nuốt lỗi thành quote = 0: không tính được giá thì KHÔNG cho checkout.
                // $formula chỉ là nguyên liệu của 1 bó; tinhGiaVonVaGiaBan() yêu cầu $rows là nguyên liệu
                // của TOÀN BỘ $soLuong bó (nó tự chia lại cho 1 bó) → phải nhân số lượng trước khi phân bổ.
                $formulaNhieuBo = array_map(
                    fn ($r) => [...$r, 'so_luong' => $r['so_luong'] * $soLuong],
                    $formula
                );

                try {
                    $rows = Bo::phanBo($formulaNhieuBo, false);
                } catch (ValidationException $e) {
                    throw new \RuntimeException(
                        collect($e->errors())->flatten()->first() ?? self::thongBaoHetHang($ten, ['thieu' => []])
                    );
                }

                $gia = Bo::tinhGiaVonVaGiaBan($rows, $soLuong);
                if ((float) $gia['gia_ban'] <= 0) {
                    throw new \RuntimeException("Không xác định được giá của mẫu \"{$ten}\", vui lòng thử lại sau.");
                }

                $quote = [
                    'gia_moi_bo'       => $gia['gia_ban'],
                    'tong_tien'        => $gia['tong_gia_ban'],
                    'tien_nguyen_lieu' => $gia['tong_gia_von'],
                    'phu_phi'          => ['size' => 0, 'kieu_dang' => 0, 'tong' => 0],
                    'gia_von_moi_bo'   => $gia['gia_von'],
                ];
            }
        } else {
            // TUY_CHON (Custom Mix): giữ nguyên business hiện tại
            $quote = Bo::tinhGiaDuLieu([
                'size'        => $source->size,
                'kieu_bo_hoa' => $source->kieu_bo_hoa,
                'thanh_phan'  => $formula,
                'so_luong'    => (int) $checkout['so_luong'],
            ]);
        }

        return compact('source', 'ten', 'thanhPhan', 'thiep', 'quote')
            + ['so_luong' => (int) $checkout['so_luong']];
    }

    /* ═══════════════════════════════════════════════════════════════════
       TẠO ĐƠN TỪ CHECKOUT (1 bó — mua ngay)
    ═══════════════════════════════════════════════════════════════════ */
    public static function taoDonTuCheckout(array $checkout, KhachHang $khach, array $donData, int $diemSuDung = 0): HoaDon
    {
        $hoaDon = HoaDon::create([
            ...$donData,
            'ma_khach_hang' => $khach->getKey(),
            'trang_thai' => 'PENDING',
            'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'ngay_dat' => now(),
            'tong_tien' => 0,
        ]);

        $laVnpay = ($donData['phuong_thuc_thanh_toan'] ?? null) === 'NGAN_HANG';

        self::hoanTatBoHoa($hoaDon, $checkout, $khach, $diemSuDung);

        if ($laVnpay) {
            $hoaDon->vnpay_cart_snapshot = [
                'loai_don'        => 'bo_hoa',
                'bo_hoa_checkout' => $checkout,
                'diem_su_dung'    => $diemSuDung,
            ];
            $hoaDon->save();
        }

        return $hoaDon;
    }

    /* ═══════════════════════════════════════════════════════════════════
       TẠO ĐƠN TỪ GIỎ BÓ HOA (nhiều bó — 1 hóa đơn)
    ═══════════════════════════════════════════════════════════════════ */
    public static function taoDonTuGioBoHoa(array $dsCheckout, KhachHang $khach, array $donData, int $diemSuDung = 0): HoaDon
    {
        $hoaDon = HoaDon::create([
            ...$donData,
            'ma_khach_hang' => $khach->getKey(),
            'trang_thai' => 'PENDING',
            'trang_thai_thanh_toan' => 'CHUA_THANH_TOAN',
            'ngay_dat' => now(),
            'tong_tien' => 0,
        ]);

        // Tính tổng tiền
        $tongGoc = 0;
        foreach ($dsCheckout as $checkout) {
            $info = self::duLieuCheckout($checkout, $khach->getKey());
            $tongGoc += (float) $info['quote']['tong_tien'];
        }
        $giam     = min($diemSuDung * 1000, $tongGoc);
        $tongTien = max(0, $tongGoc - $giam);

        $hoaDon->tong_tien = $tongTien;
        $hoaDon->save();

        self::hoanTatGioBoHoa($hoaDon, $dsCheckout, $khach, $diemSuDung);

        $laVnpay = ($donData['phuong_thuc_thanh_toan'] ?? null) === 'NGAN_HANG';
        if ($laVnpay) {
            $hoaDon->vnpay_cart_snapshot = [
                'loai_don'     => 'gio_bo_hoa',
                'ds_checkout'  => $dsCheckout,
                'diem_su_dung' => $diemSuDung,
            ];
            $hoaDon->save();
        }

        return $hoaDon;
    }

    /* ═══════════════════════════════════════════════════════════════════
       HOÀN TẤT BÓ HOA (1 checkout)
       - Mọi bó hoa (MAU sẵn / MAU làm theo đơn / TUY_CHON) đều tạo 1 dòng
         ChiTietHoaDon ĐẠI DIỆN với ma_bo_hoa + so_luong + giá bó.
       - Dòng con nguyên liệu vẫn được tạo cho TUY_CHON để phục vụ
         return legacy — KHÔNG bị xóa.
    ═══════════════════════════════════════════════════════════════════ */
    public static function hoanTatBoHoa(
        HoaDon $hoaDon,
        array $checkout,
        KhachHang $khach,
        int $diemSuDung = 0,
        bool $capNhatTongTien = true,
        bool $xuLyDiem = true
    ): void {
        $info = self::duLieuCheckout($checkout, $khach->getKey());
        $qty  = $info['so_luong'];
        $tong = 0;

        if (($checkout['loai'] ?? '') === 'MAU') {
            $coThiep = !empty($info['thiep']);

            // Nếu có thiệp → KHÔNG dùng bó sẵn (bó sẵn không có thiệp).
            $ready = collect();
            if (!$coThiep) {
                $ready = BoHoa::where('loai_bo_hoa', 'HE_THONG')
                    ->whereNull('ma_hoa_don')
                    ->where('ma_mau_bo_hoa', $info['source']->getKey())
                    ->where('so_luong', 1)
                    ->whereDoesntHave('reservations', fn ($q) => $q->where('trang_thai', 'RESERVED'))
                    ->orderBy('ma_bo_hoa')
                    ->lockForUpdate()
                    ->with('chiTietBoHoa.sanPham')
                    ->get();
            }

            $need = $qty;
            foreach ($ready as $bo) {
                if ($need < 1) break;

                $giaMoiBo = (float) $bo->gia_ban;

                $bo->update([
                    'ma_hoa_don'    => $hoaDon->getKey(),
                    'ma_khach_hang' => $khach->getKey(),
                    'loi_nhan'      => $checkout['loi_nhan'] ?? null,
                ]);

                // 1. BÓ HOA THÀNH PHẨM CẮM SẴN: reserve bó hoa, KHÔNG reserve BOM
                \App\Services\InventoryReservationService::reserveReadyBouquet($hoaDon, $bo);

                // ── [MỚI] Dòng đại diện bó trong chi_tiet_hoa_don ──
                ChiTietHoaDon::create([
                    'ma_hoa_don'        => $hoaDon->getKey(),
                    'ma_bo_hoa'         => $bo->ma_bo_hoa,
                    'ma_san_pham'       => null,
                    'ma_chi_tiet_nhap'  => null,
                    'so_luong'          => 1,
                    'gia_nhap_snapshot' => (float) ($bo->gia_von ?? 0),
                    'gia_ban_snapshot'  => (float) $bo->gia_ban,
                ]);

                $tong += $giaMoiBo;
                --$need;
            }

            if ($need > 0) {
                // 2. BÓ THEO MẪU LÀM THEO ĐƠN: reserve BOM nguyên liệu theo FIFO
                $formula = $info['thanhPhan']->map(fn ($c) => [
                    'ma_san_pham' => $c->ma_san_pham,
                    'vai_tro'     => $c->vai_tro,
                    'so_luong'    => $c->so_luong * $need,
                ])->all();

                if ($coThiep) {
                    $formula[] = [
                        'ma_san_pham' => $info['thiep']->ma_san_pham,
                        'vai_tro'     => 'THIEP',
                        'so_luong'    => $need,
                    ];
                }

                $bo = BoHoa::create([
                    'ma_mau_bo_hoa' => $info['source']->getKey(),
                    'ma_hoa_don'    => $hoaDon->getKey(),
                    'ma_khach_hang' => $khach->getKey(),
                    'ten_bo_hoa'    => $info['ten'],
                    'so_luong'      => $need,
                    'gia_ban'       => 0,
                    'loai_bo_hoa'   => 'HE_THONG',
                    'kieu_bo_hoa'   => $info['source']->kieu_bo_hoa,
                    'size'          => $info['source']->size,
                    'concept'       => $info['source']->concept,
                    'loi_nhan'      => $checkout['loi_nhan'] ?? null,
                    'trang_thai'    => 'ACTIVE',
                ]);

                $phanBo = \App\Services\InventoryReservationService::reserveBouquetIngredients($hoaDon, $bo, $formula);
                $gia = Bo::tinhGiaVonVaGiaBan($phanBo, $need);
                $bo->update([
                    'gia_von' => $gia['gia_von'],
                    'gia_ban' => $gia['gia_ban'],
                ]);

                Bo::luuChiTiet($bo, $phanBo);
                Bo::saoChepAnhTuMau($info['source']->getKey(), $bo->getKey());

                // ── [MỚI] Dòng đại diện bó trong chi_tiet_hoa_don ──
                ChiTietHoaDon::create([
                    'ma_hoa_don'        => $hoaDon->getKey(),
                    'ma_bo_hoa'         => $bo->ma_bo_hoa,
                    'ma_san_pham'       => null,
                    'ma_chi_tiet_nhap'  => null,
                    'so_luong'          => $need,
                    'gia_nhap_snapshot' => (float) $bo->gia_von,
                    'gia_ban_snapshot'  => (float) $bo->gia_ban,
                ]);

                $tong += $gia['tong_gia_ban'];
            }
        } else {
            // 3. CUSTOM MIX (TUY_CHON): reserve đúng nguyên liệu khách chọn
            $draft = $info['source'];
            $formula = $info['thanhPhan']->map(fn ($c) => [
                'ma_san_pham' => $c->ma_san_pham,
                'vai_tro'     => $c->vai_tro,
                'so_luong'    => $c->so_luong * $qty,
            ])->all();

            if (!empty($info['thiep'])) {
                $formula[] = [
                    'ma_san_pham' => $info['thiep']->ma_san_pham,
                    'vai_tro'     => 'THIEP',
                    'so_luong'    => $qty,
                ];
            }

            $draft->update([
                'ma_hoa_don'    => $hoaDon->getKey(),
                'ma_khach_hang' => $khach->getKey(),
                'so_luong'      => $qty,
                'loi_nhan'      => $checkout['loi_nhan'] ?? $draft->loi_nhan,
                'trang_thai'    => 'ACTIVE',
            ]);
            $bo = $draft;

            $phanBo = \App\Services\InventoryReservationService::reserveBouquetIngredients($hoaDon, $bo, $formula);
            $gia = Bo::giaPhanBo($phanBo, $draft->size, $draft->kieu_bo_hoa, $qty);
            $bo->update(['gia_ban' => $gia['gia_moi_bo']]);

            $bo->chiTietBoHoa()->delete();
            Bo::luuChiTiet($bo, $phanBo);

            // ── [MỚI] Dòng đại diện bó (hiển thị chính) ──
            ChiTietHoaDon::create([
                'ma_hoa_don'        => $hoaDon->getKey(),
                'ma_bo_hoa'         => $bo->ma_bo_hoa,
                'ma_san_pham'       => null,
                'ma_chi_tiet_nhap'  => null,
                'so_luong'          => $qty,
                'gia_nhap_snapshot' => (float) $bo->gia_von,
                'gia_ban_snapshot'  => (float) $bo->gia_ban,
            ]);

            // ── Dòng con (nguyên liệu) — GIỮ NGUYÊN cho return legacy ──
            self::taoChiTietHoaDon($hoaDon, $phanBo);

            $tong = $gia['tong_tien'];
        }

        if ($capNhatTongTien) {
            $giam = min($diemSuDung * 1000, $tong);
            if ($xuLyDiem && $diemSuDung > 0) $khach->decrement('diem_tich_luy', $diemSuDung);
            if ($xuLyDiem && ($diemMoi = (int) floor(($tong - $giam) / 100000)) > 0) {
                $khach->increment('diem_tich_luy', $diemMoi);
            }
            $hoaDon->update(['tong_tien' => $tong - $giam]);
        } else {
            if ($xuLyDiem && $diemSuDung > 0) $khach->decrement('diem_tich_luy', $diemSuDung);
            if ($xuLyDiem) {
                $diemMoi = (int) floor(((float) $hoaDon->tong_tien) / 100000);
                if ($diemMoi > 0) $khach->increment('diem_tich_luy', $diemMoi);
            }
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       HOÀN TẤT GIỎ BÓ HOA (nhiều bó — 1 hóa đơn)
       - Loop qua từng bó, xử lý kho/BoHoa, KHÔNG trừ/cộng điểm trong đó.
       - Điểm xử lý 1 lần ở ngoài dựa trên tổng tiền hóa đơn.
    ═══════════════════════════════════════════════════════════════════ */
    public static function hoanTatGioBoHoa(HoaDon $hoaDon, array $dsCheckout, KhachHang $khach, int $diemSuDung = 0): void
    {
        foreach ($dsCheckout as $checkout) {
            self::hoanTatBoHoa($hoaDon, $checkout, $khach, 0, false, false);
        }

        if ($diemSuDung > 0) $khach->decrement('diem_tich_luy', $diemSuDung);
        $diemMoi = (int) floor(((float) $hoaDon->tong_tien) / 100000);
        if ($diemMoi > 0) $khach->increment('diem_tich_luy', $diemMoi);
    }

    private static function taoChiTietHoaDon(HoaDon $hoaDon, array $rows): void
    {
        $grouped = collect($rows)->groupBy(
            fn ($r) => ($r['ma_san_pham'] ?? 0).'-'.($r['ma_chi_tiet_nhap'] ?? 0)
        );

        foreach ($grouped as $items) {
            $first = $items->first();
            ChiTietHoaDon::create([
                'ma_hoa_don'        => $hoaDon->getKey(),
                'ma_san_pham'       => $first['ma_san_pham'],
                'ma_chi_tiet_nhap'  => $first['ma_chi_tiet_nhap'] ?? null,
                'so_luong'          => $items->sum('so_luong'),
                'gia_nhap_snapshot' => $first['gia_nhap_snapshot'] ?? 0,
                'gia_ban_snapshot'  => $first['gia_ban_snapshot'] ?? 0,
            ]);
        }
    }

    private function khachId(Request $request): int
    {
        $khach = $request->user()->khachHang;
        abort_unless($khach, 403, 'Tài khoản chưa có hồ sơ khách hàng.');
        return $khach->getKey();
    }
}