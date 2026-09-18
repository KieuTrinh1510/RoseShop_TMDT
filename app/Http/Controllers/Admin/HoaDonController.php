<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\KhachHang;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HoaDonController extends Controller
{
    public function index(Request $request)
    {
        $query = HoaDon::with([
            'khachHang',
            'shipper',
            'chiTietHoaDon.sanPham',
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

        $hoaDons    = $query->orderByDesc('ngay_dat')->paginate(15)->withQueryString();
        $khachHangs = KhachHang::orderBy('ten_khach_hang')->get();

        return view('admin.HoaDon', compact('hoaDons', 'khachHangs'));
    }

    public function show($id)
    {
        $hoaDon = HoaDon::with([
            'khachHang',
            'shipper',
            'chiTietHoaDon.sanPham',
        ])->findOrFail($id);

        if (request()->ajax()) {
            // Nếu đơn có bó hoa ĐỀ XUẤT (loai_bo_hoa = HE_THONG) → KHÔNG hiển thị
            // các dòng ChiTietHoaDon (vật phẩm nhỏ), chỉ hiển thị dòng bó hoa.
            // Nếu là bó TỰ CHỌN → hiển thị các vật phẩm nhỏ qua ChiTietHoaDon.
            $hasHeThong = \App\Models\BoHoa::where('ma_hoa_don', $id)
                ->where('loai_bo_hoa', 'HE_THONG')
                ->exists();

            $chiTiet = $hasHeThong
                ? collect()
                : $hoaDon->chiTietHoaDon->map(function ($ct) {
                    return [
                        'ma_chi_tiet'       => $ct->ma_chi_tiet,
                        'ma_san_pham'       => $ct->ma_san_pham,
                        'so_luong'          => $ct->so_luong,
                        'gia_ban_snapshot'  => $ct->gia_ban_snapshot,
                        'gia_nhap_snapshot' => $ct->gia_nhap_snapshot,
                        'thanh_tien'        => $ct->so_luong * $ct->gia_ban_snapshot,
                        'san_pham'          => $ct->sanPham
                            ? ['ten_san_pham' => $ct->sanPham->ten_san_pham]
                            : ['ten_san_pham' => '(Đã xóa)'],
                    ];
                });

            // Chỉ hiển thị dòng bó hoa cho ĐỀ XUẤT (HE_THONG).
            // Bó TỰ CHỌN đã hiện qua ChiTietHoaDon ở trên nên không lặp lại.
            $boLines = \App\Models\BoHoa::where('ma_hoa_don', $id)
                ->where('loai_bo_hoa', 'HE_THONG')
                ->get()
                ->map(fn ($bo) => [
                    'ma_bo_hoa'        => $bo->ma_bo_hoa,
                    'ma_san_pham'      => null,
                    'so_luong'         => $bo->so_luong,
                    'gia_ban_snapshot' => $bo->gia_ban,
                    'thanh_tien'       => $bo->gia_ban * $bo->so_luong,
                    'san_pham'         => ['ten_san_pham' => $bo->ten_bo_hoa.' · '.$bo->size],
                    'loi_nhan'         => $bo->loi_nhan,
                ]);

            $chiTiet = $chiTiet->concat($boLines);

            return response()->json([
                'success' => true,
                'data'    => [
                    'ma_hoa_don'              => $hoaDon->ma_hoa_don,
                    'ngay_dat'                => optional($hoaDon->ngay_dat)->format('d/m/Y H:i'),
                    'ngay_giao'               => optional($hoaDon->ngay_giao)->format('d/m/Y H:i'),
                    'trang_thai'              => $hoaDon->trang_thai,
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
}