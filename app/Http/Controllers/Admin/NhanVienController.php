<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\NhanVien;
use App\Models\NguoiDung;
use App\Models\HoaDon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NhanVienController extends Controller
{
    public function index(Request $request)
    {
        $query = NhanVien::query();

        if ($request->filled('chuc_vu')) {
            $query->where('chuc_vu', $request->chuc_vu);
        }

        $sort = $request->get('sort', '');
        if ($sort === 'luong_desc') {
            $query->orderBy('luong', 'desc');
        } elseif ($sort === 'luong_asc') {
            $query->orderBy('luong', 'asc');
        }

        $nhanViens = $query->paginate(10)->appends($request->query());

        $chucVus = [
            'CSKH'     => 'CSKH',
            'VAN_HANH' => 'Vận hành',
            'THIET_KE' => 'Thiết kế',
            'ONLINE'   => 'Online',
            'SHIPPER'  => 'Shipper',
            'KHAC'     => 'Khác',
        ];

        $shipperDebts = HoaDon::query()
            ->select('ma_nhan_vien_giao', DB::raw('SUM(tong_tien) as tong_can_tra'))
            ->where('trang_thai', 'DELIVERED')
            ->where('trang_thai_thanh_toan', 'DA_THANH_TOAN')
            ->where('phuong_thuc_thanh_toan', 'COD')
            ->groupBy('ma_nhan_vien_giao')
            ->pluck('tong_can_tra', 'ma_nhan_vien_giao');

        return view('admin.NhanVien', compact('nhanViens', 'chucVus', 'shipperDebts'));
    }

    public function update(Request $request, $ma_nhan_vien)
    {
        $request->validate([
            'ten_nhan_vien' => 'required|string|max:100',
            'email'         => 'nullable|email|max:100',
            'so_dien_thoai' => 'nullable|string|max:15',
            'chuc_vu'       => 'required|in:CSKH,VAN_HANH,THIET_KE,ONLINE,SHIPPER,KHAC',
            'cong_viec'     => 'nullable|string',
            'luong'         => 'nullable|numeric|min:0',
        ]);

        $nhanVien = NhanVien::findOrFail($ma_nhan_vien);
        $nhanVien->update($request->only([
            'ten_nhan_vien', 'email', 'so_dien_thoai', 'chuc_vu', 'cong_viec', 'luong'
        ]));

        return redirect()->route('nhan-vien.index')
                         ->with('success', 'Cập nhật nhân viên thành công!');
    }

    public function destroy($ma_nhan_vien)
    {
        $nhanVien    = NhanVien::findOrFail($ma_nhan_vien);
        $maNguoiDung = $nhanVien->ma_nhan_vien;

        DB::transaction(function () use ($nhanVien, $maNguoiDung) {
            $nhanVien->delete();
            NguoiDung::where('ma_nguoi_dung', $maNguoiDung)->delete();
        });

        return redirect()->route('nhan-vien.index')
                         ->with('success', 'Xóa nhân viên thành công!');
    }

        public function payback($ma_nhan_vien)
    {
        $nhanVien = NhanVien::findOrFail($ma_nhan_vien);

        if ($nhanVien->chuc_vu !== 'SHIPPER') {
            return response()->json([
                'success' => false,
                'message' => 'Nhân viên này không phải Shipper.',
            ], 422);
        }

        // Đơn shipper đã giao + shipper đang giữ tiền mặt + chưa nộp shop
        $dsDon = HoaDon::where('ma_nhan_vien_giao', $ma_nhan_vien)
            ->where('trang_thai', 'DELIVERED')
            ->where('trang_thai_thanh_toan', 'DA_THANH_TOAN')
            ->where('da_thu_tien_mat', true)
            ->orderBy('ngay_giao')
            ->get(['ma_hoa_don', 'tong_tien', 'ngay_giao']);

        $tongCod  = (float) $dsDon->sum('tong_tien');
        $tienPhat = (float) ($nhanVien->tien_phat ?? 0);

        if ($tongCod <= 0 && $tienPhat <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Shipper ' . $nhanVien->ten_nhan_vien . ' không có khoản nào cần nộp.',
            ], 422);
        }

        DB::transaction(function () use ($nhanVien, $dsDon, $tongCod, $tienPhat) {
            // 1) Đánh dấu các đơn đã nộp
            if ($dsDon->isNotEmpty()) {
                HoaDon::whereIn('ma_hoa_don', $dsDon->pluck('ma_hoa_don'))
                    ->update(['trang_thai_thanh_toan' => 'DA_NOP']);
            }

            // 2) Reset tiền phạt
            $nhanVien->update(['tien_phat' => 0]);

            // 3) Thông báo cho shipper kèm breakdown để app show chi tiết
            $dsBreak = $dsDon->map(fn ($d) => [
                'ma_hoa_don'  => (int) $d->ma_hoa_don,
                'ma_hien_thi' => '#HD-' . str_pad($d->ma_hoa_don, 4, '0', STR_PAD_LEFT),
                'tien'        => (float) $d->tong_tien,
            ])->values()->all();

            $tongNop = $tongCod + $tienPhat;

            \App\Models\ThongBao::gui(
                (int) $nhanVien->ma_nhan_vien,
                'payment',
                '💰 Đã xác nhận nộp ' . number_format($tongNop, 0, ',', '.')
                    . ' đ. Cảm ơn bạn đã hoàn tất khoản cần nộp!',
                null,
                [
                    'tong_cod'  => $tongCod,
                    'tien_phat' => $tienPhat,
                    'tong_nop'  => $tongNop,
                    'so_don'    => $dsDon->count(),
                    'ds_don'    => $dsBreak,
                ]
            );
        });

        return response()->json([
            'success'   => true,
            'so_tien'   => $tongCod,
            'tien_phat' => $tienPhat,
            'so_don'    => $dsDon->count(),
            'message'   => 'Đã xác nhận ' . $nhanVien->ten_nhan_vien . ' nộp '
                         . number_format($tongCod + $tienPhat, 0, ',', '.') . ' đ ('
                         . $dsDon->count() . ' đơn).',
        ]);
    }
}