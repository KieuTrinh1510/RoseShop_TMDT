<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Admin\BoHoaController as Bo;
use App\Http\Controllers\Controller;
use App\Models\{BoHoa, ChiTietHoaDon, HoaDon, KhachHang, MauBoHoa, PhuPhiBoHoa, SanPham};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BoHoaController extends Controller
{
    /* ═══════════════════════════════════════════════════════════════════
       TRANG KHÁCH — DANH SÁCH MẪU
    ═══════════════════════════════════════════════════════════════════ */
    public function heThong()
    {
        $mauBoHoas = MauBoHoa::where('trang_thai', 'ACTIVE')
            ->with('chiTietMauBoHoa.sanPham')
            ->orderByDesc('ma_mau_bo_hoa')
            ->paginate(12);

        $phuPhiMap = PhuPhiBoHoa::all()->keyBy(fn ($p) => $p->loai . '|' . $p->gia_tri);

        $mauBoHoas->getCollection()->transform(function ($m) use ($phuPhiMap) {
            $nl = $m->chiTietMauBoHoa->sum(
                fn ($c) => ((float) ($c->sanPham?->gia_ban_hien_tai ?? 0)) * (int) $c->so_luong
            );
            $s = (float) ($phuPhiMap->get('SIZE|' . $m->size)?->phu_phi ?? 0);
            $k = (float) ($phuPhiMap->get('KIEU_DANG|' . $m->kieu_bo_hoa)?->phu_phi ?? 0);

            $m->gia_hien_thi = round($nl + $s + $k, 2);
            return $m;
        });

        return view('customer.bohoahethong', compact('mauBoHoas'));
    }

    /* ═══════════════════════════════════════════════════════════════════
       TỰ TẠO
    ═══════════════════════════════════════════════════════════════════ */
    public function create()
    {
        $hoaVaPhuKien = SanPham::whereIn('loai_san_pham', [
                'HOA_TUOI', 'HOA_GIA', 'HOA_SAP', 'HOA_GIAY_NHUN', 'PHU_KIEN',
            ])
            ->where('trang_thai', 'DANG_BAN')
            ->whereHas('chiTietNhaps', function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'));
            })
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'));
            }])
            ->orderBy('ten_san_pham')
            ->get();

        $nguyenVatLieu = SanPham::whereIn('loai_san_pham', [
                'GIAY_GOI', 'RUY_BANG', 'PHU_KIEN_TRANG_TRI', 'MIENG_XOP',
            ])
            ->where('trang_thai', 'DANG_BAN')
            ->whereHas('chiTietNhaps', function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'));
            })
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'));
            }])
            ->orderBy('ten_san_pham')
            ->get();

        $nguyenLieu = $hoaVaPhuKien
            ->concat($nguyenVatLieu)
            ->map(fn ($sp) => [
                'ma_san_pham'      => $sp->ma_san_pham,
                'ten_san_pham'     => $sp->ten_san_pham,
                'loai_san_pham'    => $sp->loai_san_pham,
                'don_vi_tinh'      => $sp->don_vi_tinh,
                'gia_ban_hien_tai' => $sp->gia_ban_hien_tai,
                'ton_kho'          => (int) $sp->chiTietNhaps->sum('so_luong_con_lai'),
            ])
            ->values();

        $phuPhi   = PhuPhiBoHoa::all();
        $duLieuCu = session('bo_hoa_tu_tao_nhap');

        return view('customer.bohoatuchon', [
            'nguyenLieu'    => $nguyenLieu,
            'phuPhi'        => $phuPhi,
            'duLieuCu'      => $duLieuCu,
            'SO_BONG'       => Bo::SO_BONG,
            'KIEU_DANG'     => Bo::KIEU_DANG,
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
            fn () => Bo::tinhGiaDuLieu($this->validateTuTao($request, false)),
            'Giá tạm tính, kiểm tra lại khi đặt hàng.'
        );
    }

    public function xacNhanTuTao(Request $request)
    {
        $data = $this->validateTuTao($request, true);
        $data['so_luong'] = min(10, (int) $data['so_luong']);

        $quote = Bo::tinhGiaDuLieu($data);

        $existingDraftId = session('bo_hoa_tu_tao_nhap.draft_id');

        session(['bo_hoa_tu_tao_nhap' => [
            'ten_bo_hoa'  => $data['ten_bo_hoa'],
            'size'        => $data['size'],
            'kieu_bo_hoa' => $data['kieu_bo_hoa'],
            'concept'     => $data['concept'],
            'so_luong'    => $data['so_luong'],
            'thanh_phan'  => $data['thanh_phan'],
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
            ->with('chiTietBoHoa')
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
        ]);
        $loiNhan = $data['loi_nhan'] ?? null;

        $khachId = $this->khachId($request);

        $draft = DB::transaction(function () use ($nhap, $khachId, $loiNhan) {
            $existing = null;
            if (!empty($nhap['draft_id'])) {
                $existing = BoHoa::tuyChon()->draft()
                    ->where('ma_bo_hoa', $nhap['draft_id'])
                    ->where('ma_khach_hang', $khachId)
                    ->whereNull('ma_hoa_don')
                    ->lockForUpdate()
                    ->first();
            }

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

            $old = BoHoa::tuyChon()->draft()
                ->where('ma_khach_hang', $khachId)
                ->where('ma_bo_hoa', '!=', $draft->ma_bo_hoa)
                ->whereNull('ma_hoa_don')
                ->get();
            foreach ($old as $o) {
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
            } catch (\Throwable $e) {
                $giaMotBo = 0;
                $tongItem = 0;
            }

            $dsItem[$key] = [
                'mau'       => $mau,
                'thiep'     => $thiep,
                'so_luong'  => (int) $item['so_luong'],
                'loi_nhan'  => $item['loi_nhan'] ?? null,
                'gia_mot_bo'=> $giaMotBo,
                'tong'      => $tongItem,
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

        return redirect()->route('customer.bo-hoa.gio.index')
            ->with('success', 'Đã thêm bó hoa vào giỏ!');
    }

    public function xoaKhoiGio(Request $request)
    {
        $key = $request->input('key');
        $gio = session('gio_bo_hoa', []);

        if (isset($gio[$key])) {
            unset($gio[$key]);
            session(['gio_bo_hoa' => $gio]);
        }

        return response()->json([
            'success'   => true,
            'so_bo_hoa' => count($gio),
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

        return $data;
    }

    /**
     * Chuẩn bị dữ liệu 1 bó để checkout.
     *
     * $checkout cần:
     *   - loai: 'MAU' hoặc 'TUY_CHON'
     *   - ma_mau_bo_hoa HOẶC ma_bo_hoa
     *   - so_luong
     *   - ma_thiep (nullable) — mã sản phẩm loại THIEP muốn thêm
     *   - loi_nhan (nullable)
     *
     * Trả về: source, ten, thanhPhan, thiep, quote, so_luong.
     * `thanhPhan` KHÔNG chứa thiệp — thiệp chỉ được chèn vào formula
     * khi tính giá và khi phân bổ kho (xem hoanTatBoHoa).
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
                ->with('chiTietBoHoa.sanPham')
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
            $ton = (int) $thiep->chiTietNhaps()
                ->where('so_luong_con_lai', '>', 0)
                ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
                ->sum('so_luong_con_lai');

            if ($ton < $soLuongCan) {
                throw new \RuntimeException(
                    "Thiệp \"{$thiep->ten_san_pham}\" chỉ còn {$ton}, không đủ cho {$soLuongCan} bó."
                );
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

        $quote = Bo::tinhGiaDuLieu([
            'size'        => $source->size,
            'kieu_bo_hoa' => $source->kieu_bo_hoa,
            'thanh_phan'  => $formula,
            'so_luong'    => (int) $checkout['so_luong'],
        ]);

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

            // Nếu có thiệp → KHÔNG dùng bó sẵn (vì bó sẵn không có thiệp).
            // Ngược lại → dùng bó sẵn như cũ.
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

                $phi = Bo::layPhuPhi($info['source']->size, $info['source']->kieu_bo_hoa);
                $tienNL = $bo->chiTietBoHoa->sum(
                    fn ($ct) => ((float) ($ct->sanPham?->gia_ban_hien_tai ?? 0)) * (int) $ct->so_luong
                );
                $giaMoiBo = round($tienNL + $phi['tong'], 2);

                $bo->update([
                    'ma_hoa_don'    => $hoaDon->getKey(),
                    'ma_khach_hang' => $khach->getKey(),
                    'loi_nhan'      => $checkout['loi_nhan'] ?? null,
                    'gia_ban'       => $giaMoiBo,
                ]);

                // 1. BÓ HOA THÀNH PHẨM CẮM SẴN: reserve bó hoa, KHÔNG reserve BOM nguyên liệu
                \App\Services\InventoryReservationService::reserveReadyBouquet($hoaDon, $bo);

                $tong += $giaMoiBo;
                --$need;
            }

            if ($need > 0) {
                // 2. BÓ THEO MẪU LÀM THEO ĐƠN: reserve BOM nguyên liệu từ các lô khả dụng theo FIFO
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
                $gia = Bo::giaPhanBo($phanBo, $info['source']->size, $info['source']->kieu_bo_hoa, $need);
                $bo->update(['gia_ban' => $gia['gia_moi_bo']]);

                Bo::luuChiTiet($bo, $phanBo);
                Bo::saoChepAnhTuMau($info['source']->getKey(), $bo->getKey());
                $tong += $gia['tong_tien'];
            }
        } else {
            // 3. CUSTOM MIX (TUY_CHON): reserve đúng nguyên liệu khách chọn theo chi_tiet_bo_hoa
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

            $bo = BoHoa::create([
                'ma_hoa_don'    => $hoaDon->getKey(),
                'ma_khach_hang' => $khach->getKey(),
                'ten_bo_hoa'    => $draft->ten_bo_hoa,
                'so_luong'      => $qty,
                'gia_ban'       => 0,
                'loai_bo_hoa'   => 'TUY_CHON',
                'kieu_bo_hoa'   => $draft->kieu_bo_hoa,
                'size'          => $draft->size,
                'concept'       => $draft->concept,
                'loi_nhan'      => $checkout['loi_nhan'] ?? $draft->loi_nhan,
                'trang_thai'    => 'ACTIVE',
            ]);

            $phanBo = \App\Services\InventoryReservationService::reserveBouquetIngredients($hoaDon, $bo, $formula);
            $gia = Bo::giaPhanBo($phanBo, $draft->size, $draft->kieu_bo_hoa, $qty);
            $bo->update(['gia_ban' => $gia['gia_moi_bo']]);

            Bo::luuChiTiet($bo, $phanBo);
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