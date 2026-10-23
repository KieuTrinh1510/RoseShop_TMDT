<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\KhachHang;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HoaDonController extends Controller
{
    // Nhãn hiển thị nguồn hủy / trạng thái hàng hoàn (chỉ dùng để trả dữ liệu cho View)
    private const NGUON_HUY_TEXT = [
        'CUSTOMER' => 'Do khách hàng',
        'SHIPPER'  => 'Do shipper',
    ];

    private const TRANG_THAI_HANG_HOAN_TEXT = [
        'CHO_MANG_VE_KHO' => 'Chờ mang về kho',
        'DA_NHAN_TAI_KHO' => 'Đã nhận tại kho',
        'DANG_KIEM_TRA'   => 'Đang kiểm tra',
        'DA_KIEM_TRA'     => 'Đã kiểm tra',
    ];

    public function index(Request $request)
    {
        $query = HoaDon::with([
            'khachHang',
            'shipper',
            'chiTietHoaDon.sanPham',
            'chiTietHoaDon.boHoa.mauBoHoa',   // ← [MỚI] để list có thể hiển thị tên bó nếu cần
            'hangHoan',
        ]);

        if ($request->filled('ma_khach_hang')) {
            $query->where('ma_khach_hang', $request->ma_khach_hang);
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('trang_thai_thanh_toan')) {
            $query->where('trang_thai_thanh_toan', $request->trang_thai_thanh_toan);
        }

        if ($request->filled('tu_ngay') && $request->filled('den_ngay')) {
            $query->whereBetween('ngay_dat', [
                Carbon::parse($request->tu_ngay)->startOfDay(),
                Carbon::parse($request->den_ngay)->endOfDay(),
            ]);
        } elseif ($request->filled('tu_ngay')) {
            $query->where('ngay_dat', '>=', Carbon::parse($request->tu_ngay)->startOfDay());
        } elseif ($request->filled('den_ngay')) {
            $query->where('ngay_dat', '<=', Carbon::parse($request->den_ngay)->endOfDay());
        }

        $perPage    = in_array((int)$request->get('per_page'), [10, 15, 20, 50, 100]) ? (int)$request->get('per_page') : 10;
        $hoaDons    = $query->orderByDesc('ngay_dat')->paginate($perPage)->withQueryString();
        $khachHangs = KhachHang::orderBy('ten_khach_hang')->get();

        return view('admin.HoaDon', compact('hoaDons', 'khachHangs'));
    }

    public function show($id)
    {
        $hoaDon = HoaDon::with([
            'khachHang',
            'shipper',
            'chiTietHoaDon.sanPham',
            'chiTietHoaDon.boHoa.mauBoHoa',   // ← [MỚI]
            'hangHoan.chiTiet.sanPham',
            'hangHoan.chiTiet.boHoa',
        ])->findOrFail($id);

        if (request()->ajax()) {
            // ── Chi tiết hóa đơn: ưu tiên dòng đại diện bó ──
            $chiTiet = $hoaDon->chiTietHoaDon->map(function ($ct) {
                return [
                    'ma_chi_tiet'       => $ct->ma_chi_tiet,
                    'ma_bo_hoa'         => $ct->ma_bo_hoa,
                    'ma_san_pham'       => $ct->ma_san_pham,
                    'ten_hien_thi'      => $ct->ten_hien_thi,
                    'size'              => $ct->boHoa?->size,
                    'loai'              => $ct->ma_bo_hoa ? 'bo_hoa' : 'san_pham',
                    'so_luong'          => $ct->so_luong,
                    'gia_ban_snapshot'  => $ct->gia_ban_snapshot,
                    'gia_nhap_snapshot' => $ct->gia_nhap_snapshot,
                    'thanh_tien'        => $ct->so_luong * $ct->gia_ban_snapshot,
                    'san_pham'          => $ct->sanPham
                        ? ['ten_san_pham' => $ct->sanPham->ten_san_pham]
                        : ['ten_san_pham' => null],
                ];
            });

            // Nếu hóa đơn có bó hoa → chỉ trả dòng bó.
            // Dòng con nguyên liệu vẫn nằm trong DB, chỉ ẩn khi hiển thị.
            if ($chiTiet->whereNotNull('ma_bo_hoa')->isNotEmpty()) {
                $chiTiet = $chiTiet->whereNotNull('ma_bo_hoa')->values();
            } else {
                // Fallback dữ liệu cũ: chi_tiet_hoa_don chưa có ma_bo_hoa,
                // nhưng bo_hoa có bản ghi gắn hóa đơn này.
                $boFallback = \App\Models\BoHoa::where('ma_hoa_don', $id)->get();
                if ($boFallback->isNotEmpty()) {
                    $chiTiet = $boFallback->map(fn ($bo) => [
                        'ma_chi_tiet'       => null,
                        'ma_bo_hoa'         => $bo->ma_bo_hoa,
                        'ma_san_pham'       => null,
                        'ten_hien_thi'      => $bo->ten_bo_hoa,
                        'size'              => $bo->size,
                        'loai'              => 'bo_hoa',
                        'so_luong'          => $bo->so_luong,
                        'gia_ban_snapshot'  => $bo->gia_ban,
                        'gia_nhap_snapshot' => $bo->gia_von,
                        'thanh_tien'        => $bo->so_luong * $bo->gia_ban,
                        'san_pham'          => ['ten_san_pham' => null],
                    ]);
                }
            }

            // Bổ sung thông tin bó hoa Custom Mix và ảnh mẫu nếu có
            $customBouquet = \App\Models\BoHoa::where('ma_hoa_don', $id)
                ->where('loai_bo_hoa', 'TUY_CHON')
                ->with('customMixImages')
                ->first();

            $anhMauCustom = [];
            if ($customBouquet && $customBouquet->customMixImages->isNotEmpty()) {
                $anhMauCustom = $customBouquet->customMixImages->map(fn ($img) => [
                    'id'   => $img->id,
                    'url'  => $img->url,
                    'name' => $img->original_name ?? 'Ảnh mẫu',
                ])->all();
            }

            $thongTinHuy = $this->thongTinHuyHangHoan($hoaDon);
            $kiemTraLog = $hoaDon->hangHoan?->chiTiet->map(function ($ct) {
                $laBo = !empty($ct->ma_bo_hoa);
                return [
                    'ten' => $laBo ? ($ct->boHoa?->ten_bo_hoa ?? 'Bó hoa') : ($ct->sanPham?->ten_san_pham ?? 'Sản phẩm'),
                    'loai' => $laBo ? 'Bó hoa' : 'Sản phẩm',
                    'theo_don' => (int) $ct->so_luong_theo_don,
                    'hong' => (int) $ct->so_luong_hong,
                    'con_ban' => (int) $ct->so_luong_con_ban_duoc,
                    'ket_qua' => $ct->ket_qua,
                    'ghi_chu' => $ct->ghi_chu,
                ];
            })->values() ?? collect();

            return response()->json([
                'success' => true,
                'data'    => [
                    'ma_hoa_don'              => $hoaDon->ma_hoa_don,
                    'ngay_dat'                => optional($hoaDon->ngay_dat)->format('d/m/Y H:i'),
                    'ngay_giao'               => optional($hoaDon->ngay_giao)->format('d/m/Y H:i'),
                    'trang_thai'              => $hoaDon->trang_thai,
                    'nguon_huy'               => $hoaDon->nguon_huy,
                    'nguon_huy_text'          => $thongTinHuy['nguon_huy_text'],
                    'ly_do_huy'               => $hoaDon->ly_do_huy,
                    'thoi_diem_huy'           => optional($hoaDon->thoi_diem_huy)->format('d/m/Y H:i'),
                    'co_hang_hoan'            => (bool) $hoaDon->co_hang_hoan,
                    'trang_thai_hang_hoan'    => $thongTinHuy['trang_thai_hang_hoan'],
                    'trang_thai_hang_hoan_text' => $thongTinHuy['trang_thai_hang_hoan_text'],
                    'co_the_kiem_tra_hang'    => $thongTinHuy['co_the_kiem_tra_hang'],
                    'da_kiem_tra_hang'        => $thongTinHuy['da_kiem_tra_hang'],
                    'kiem_tra_hang_url'       => $thongTinHuy['co_the_kiem_tra_hang']
                        ? route('admin.hoa-don.kiem-tra-hang', $hoaDon->ma_hoa_don)
                        : null,
                    'kiem_tra_log'            => $kiemTraLog,
                    'thoi_diem_kiem_tra'      => optional($hoaDon->hangHoan?->thoi_diem_kiem_tra)->format('d/m/Y H:i'),
                    'trang_thai_thanh_toan'   => $hoaDon->trang_thai_thanh_toan,
                    'phuong_thuc_thanh_toan'  => $hoaDon->phuong_thuc_thanh_toan,
                    'dia_chi_giao'            => $hoaDon->dia_chi_giao,
                    'so_dien_thoai'           => $hoaDon->so_dien_thoai,
                    'tong_tien'               => $hoaDon->tong_tien,
                    'khach_hang'              => $hoaDon->khachHang
                        ? [
                            'ten_khach_hang' => $hoaDon->khachHang->ten_khach_hang,
                            'so_dien_thoai'  => $hoaDon->khachHang->so_dien_thoai,
                            'email'          => $hoaDon->khachHang->email,
                        ]
                        : null,
                    'nhan_vien_giao'          => $hoaDon->shipper
                        ? ['ten_nhan_vien' => $hoaDon->shipper->ten_nhan_vien]
                        : null,
                    'chi_tiet_hoa_don'        => $chiTiet,
                    'custom_bouquet'          => $customBouquet ? [
                        'ten_bo_hoa'  => $customBouquet->ten_bo_hoa,
                        'size'        => $customBouquet->size,
                        'kieu_bo_hoa' => $customBouquet->kieu_bo_hoa,
                        'concept'     => $customBouquet->concept,
                        'loi_nhan'    => $customBouquet->loi_nhan,
                        'so_luong'    => $customBouquet->so_luong ?? 1,
                    ] : null,
                    'anh_mau_custom'          => $anhMauCustom,
                ],
            ]);
        }

        return view('hoa-don.show', compact('hoaDon'));
    }

    public function destroy($id)
    {
        $hoaDon = HoaDon::findOrFail($id);

        if (\App\Models\BoHoa::where('ma_hoa_don', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Hóa đơn bó hoa cần giữ để đối soát tồn kho. Xử lý đơn qua Xác nhận/Từ chối.'], 422);
        }

        if ($hoaDon->trang_thai === 'DELIVERED') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa hóa đơn đã giao thành công.',
            ], 403);
        }

        $coTheXoa = $hoaDon->trang_thai_thanh_toan === 'CHUA_THANH_TOAN'
                 || $hoaDon->trang_thai === 'CANCELLED';

        if (! $coTheXoa) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa hóa đơn đã thanh toán hoặc đang xử lý.',
            ], 403);
        }

        $hoaDon->delete();

        return response()->json([
            'success'    => true,
            'message'    => 'Đã xóa hóa đơn #HD-' . str_pad($id, 4, '0', STR_PAD_LEFT) . ' thành công.',
            'deleted_id' => $id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Thông tin hủy đơn + hàng hoàn cho danh sách / chi tiết hóa đơn
    // ─────────────────────────────────────────────────────────────

    private function thongTinHuyHangHoan(HoaDon $hd): array
    {
        $laHuy      = $hd->trang_thai === 'CANCELLED';
        $hangHoan   = $laHuy ? $hd->hangHoan : null;
        $ttHangHoan = $hangHoan?->trang_thai;

        return [
            'nguon_huy_text'            => $laHuy
                ? (self::NGUON_HUY_TEXT[strtoupper((string) $hd->nguon_huy)] ?? null)
                : null,
            'trang_thai_hang_hoan'      => $ttHangHoan,
            'trang_thai_hang_hoan_text' => $ttHangHoan
                ? (self::TRANG_THAI_HANG_HOAN_TEXT[$ttHangHoan] ?? $ttHangHoan)
                : null,
            'co_the_kiem_tra_hang'      => $laHuy
                && (bool) $hd->co_hang_hoan
                && $hangHoan !== null,
            'da_kiem_tra_hang'          => $ttHangHoan === 'DA_KIEM_TRA',
        ];
    }

    private function ganThongTinHuyHangHoan(HoaDon $hd): void
    {
        foreach ($this->thongTinHuyHangHoan($hd) as $key => $value) {
            $hd->setAttribute($key, $value);
        }
    }
}