<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChiTietNhap;
use App\Models\DanhGiaLoHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoHangController extends Controller
{
    public function index(Request $request)
    {
        $query = ChiTietNhap::with([
            'sanPham',
            'phieuNhap.nhaCungCap',
            'phieuNhap.nhanVien',
            'danhGiaMoiNhat.nhanVien',
            'baoCaoHangHongs',
        ]);

        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                if (is_numeric($kw)) {
                    $q->orWhere('ma_chi_tiet_nhap', (int) $kw)
                      ->orWhere('ma_phieu_nhap', (int) $kw);
                }
                $q->orWhereHas('sanPham', function ($sq) use ($kw) {
                    $sq->where('ten_san_pham', 'LIKE', '%' . $kw . '%');
                })->orWhereHas('phieuNhap', function ($pq) use ($kw) {
                    $pq->where('ten_nha_cung_cap', 'LIKE', '%' . $kw . '%');
                });
            });
        }

        if ($request->filled('ket_qua')) {
            $kq = $request->ket_qua;
            if ($kq === 'CHUA_DANH_GIA') {
                $query->whereDoesntHave('danhGiaLoHangs');
            } else {
                $query->whereHas('danhGiaMoiNhat', function ($dq) use ($kq) {
                    $dq->where('ket_qua', $kq);
                });
            }
        }

        if ($request->filled('ton_kho')) {
            if ($request->ton_kho === 'con_hang') {
                $query->where('so_luong_con_lai', '>', 0);
            } elseif ($request->ton_kho === 'het_hang') {
                $query->where('so_luong_con_lai', '<=', 0);
            }
        }

        $loHangs = $query->orderBy('ma_chi_tiet_nhap', 'desc')->paginate(15)->withQueryString();

        return view('admin.LoHang.index', compact('loHangs'));
    }

    public function show($id)
    {
        $loHang = ChiTietNhap::with([
            'sanPham',
            'phieuNhap.nhaCungCap',
            'phieuNhap.nhanVien',
            'danhGiaLoHangs.nhanVien',
            'baoCaoHangHongs.nhanVien',
        ])->findOrFail($id);

        return view('admin.LoHang.show', compact('loHang'));
    }

    public function danhGia(Request $request, $id)
    {
        $chiTietNhap = ChiTietNhap::with('phieuNhap')->findOrFail($id);

        $request->validate([
            'ket_qua'      => ['required', 'in:DAT,CO_VAN_DE,KHONG_DAT'],
            'so_luong_loi' => ['nullable', 'integer', 'min:0', 'max:' . $chiTietNhap->so_luong],
            'ly_do'        => ['nullable', 'string', 'max:255', 'required_if:ket_qua,CO_VAN_DE,KHONG_DAT'],
            'ghi_chu'      => ['nullable', 'string', 'max:1000'],
        ], [
            'ket_qua.required'       => 'Vui lòng chọn kết quả đánh giá.',
            'ket_qua.in'             => 'Kết quả đánh giá không hợp lệ.',
            'so_luong_loi.integer'   => 'Số lượng lỗi phải là số nguyên.',
            'so_luong_loi.min'       => 'Số lượng lỗi không được âm.',
            'so_luong_loi.max'       => 'Số lượng lỗi không được vượt quá số lượng ban đầu của lô (' . $chiTietNhap->so_luong . ').',
            'ly_do.required_if'      => 'Vui lòng nhập lý do khi lô Có vấn đề hoặc Không đạt.',
            'ly_do.max'              => 'Lý do không được vượt quá 255 ký tự.',
            'ghi_chu.max'            => 'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        $user = Auth::user();
        $maNhanVien = $user?->nhanVien?->ma_nhan_vien;

        DanhGiaLoHang::updateOrCreate(
            ['ma_chi_tiet_nhap' => $chiTietNhap->ma_chi_tiet_nhap],
            [
                'ma_nhan_vien'       => $maNhanVien,
                'ket_qua'            => $request->ket_qua,
                'so_luong_loi'       => (int) ($request->so_luong_loi ?? 0),
                'ly_do'              => $request->ly_do,
                'ghi_chu'            => $request->ghi_chu,
                'thoi_gian_danh_gia' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Đã lưu kết quả đánh giá chất lượng cho lô #' . $chiTietNhap->ma_chi_tiet_nhap . '!');
    }
}
