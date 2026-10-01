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
        $query = NhanVien::with('nguoiDung');

        // Tìm kiếm theo từ khóa (tên, email, số điện thoại)
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                $q->where('ten_nhan_vien', 'like', "%{$kw}%")
                  ->orWhere('email', 'like', "%{$kw}%")
                  ->orWhere('so_dien_thoai', 'like', "%{$kw}%");
            });
        }

        // Lọc theo chức vụ
        if ($request->filled('chuc_vu')) {
            $query->where('chuc_vu', $request->chuc_vu);
        }

        // Lọc theo trạng thái tài khoản
        if ($request->filled('trang_thai')) {
            $status = $request->trang_thai;
            $query->whereHas('nguoiDung', function ($q) use ($status) {
                $q->where('trang_thai', $status);
            });
        }

        // Sắp xếp
        $sort = $request->get('sort', '');
        if ($sort === 'luong_desc') {
            $query->orderBy('luong', 'desc');
        } elseif ($sort === 'luong_asc') {
            $query->orderBy('luong', 'asc');
        } else {
            $query->orderBy('ma_nhan_vien', 'asc');
        }

        // Số lượng bản ghi trên một trang: 10 (mặc định), 20, 50, 100
        $perPage = in_array((int)$request->get('per_page'), [10, 20, 50, 100]) ? (int)$request->get('per_page') : 10;

        $nhanViens = $query->paginate($perPage)->appends($request->query());

        // 4 chỉ số KPI thống kê
        $tongNhanVien = NhanVien::count();
        $soNhanVien   = NhanVien::where('chuc_vu', '!=', 'SHIPPER')->count();
        $soShipper    = NhanVien::where('chuc_vu', 'SHIPPER')->count();
        $dangHoatDong = NhanVien::whereHas('nguoiDung', function ($q) {
            $q->where('trang_thai', 'HOAT_DONG');
        })->count();

        $chucVus = [
            'CSKH'     => 'CSKH',
            'VAN_HANH' => 'Vận hành',
            'THIET_KE' => 'Thiết kế',
            'ONLINE'   => 'Online',
            'SHIPPER'  => 'Shipper',
            'KHAC'     => 'Khác',
        ];

        // Công nợ COD của shipper
        $shipperDebts = HoaDon::query()
            ->select('ma_nhan_vien_giao', DB::raw('SUM(tong_tien) as tong_can_tra'))
            ->where('trang_thai', 'DELIVERED')
            ->where('trang_thai_thanh_toan', 'DA_THANH_TOAN')
            ->where('phuong_thuc_thanh_toan', 'COD')
            ->groupBy('ma_nhan_vien_giao')
            ->pluck('tong_can_tra', 'ma_nhan_vien_giao');

        return view('admin.NhanVien', compact(
            'nhanViens',
            'chucVus',
            'shipperDebts',
            'tongNhanVien',
            'soNhanVien',
            'soShipper',
            'dangHoatDong',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_dang_nhap' => 'required|string|max:50|unique:nguoi_dung,ten_dang_nhap',
            'mat_khau'      => 'required|string|min:6',
            'ten_nhan_vien' => 'required|string|max:100',
            'email'         => 'nullable|email|max:100',
            'so_dien_thoai' => 'nullable|string|max:15',
            'chuc_vu'       => 'required|in:CSKH,VAN_HANH,THIET_KE,ONLINE,SHIPPER,KHAC',
            'cong_viec'     => 'nullable|string',
            'luong'         => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $vaiTro = ($request->chuc_vu === 'SHIPPER') ? 'SHIPPER' : 'NHAN_VIEN';
            $user = NguoiDung::create([
                'ten_dang_nhap'     => $request->ten_dang_nhap,
                'mat_khau'          => $request->mat_khau,
                'vai_tro'           => $vaiTro,
                'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
                'email_verified_at' => now(),
            ]);

            NhanVien::create([
                'ma_nhan_vien'  => $user->ma_nguoi_dung,
                'ten_nhan_vien' => $request->ten_nhan_vien,
                'email'         => $request->email,
                'so_dien_thoai' => $request->so_dien_thoai,
                'chuc_vu'       => $request->chuc_vu,
                'cong_viec'     => $request->cong_viec,
                'luong'         => $request->luong ?? 0,
            ]);

            DB::commit();
            return redirect()->route('nhan-vien.index')->with('success', 'Thêm mới nhân viên thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Lỗi khi thêm nhân viên: ' . $e->getMessage())->withInput();
        }
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

    public function toggleStatus($ma_nhan_vien)
    {
        $nhanVien = NhanVien::findOrFail($ma_nhan_vien);
        $user = NguoiDung::find($nhanVien->ma_nhan_vien);

        if ($user) {
            $user->trang_thai = ($user->trang_thai === NguoiDung::TRANG_THAI_BI_KHOA)
                ? NguoiDung::TRANG_THAI_HOAT_DONG
                : NguoiDung::TRANG_THAI_BI_KHOA;
            $user->save();

            $statusText = ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) ? 'Mở khóa' : 'Khóa';
            return back()->with('success', "Đã {$statusText} tài khoản của nhân viên \"{$nhanVien->ten_nhan_vien}\" thành công!");
        }

        return back()->with('error', 'Không tìm thấy tài khoản người dùng tương ứng.');
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

        $soTienDaTra = HoaDon::where('ma_nhan_vien_giao', $ma_nhan_vien)
            ->where('trang_thai', 'DELIVERED')
            ->where('trang_thai_thanh_toan', 'DA_THANH_TOAN')
            ->where('phuong_thuc_thanh_toan', 'COD')
            ->sum('tong_tien');

        if ($soTienDaTra == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Shipper ' . $nhanVien->ten_nhan_vien . ' không có khoản nào cần nộp.',
            ], 422);
        }

        $updated = HoaDon::where('ma_nhan_vien_giao', $ma_nhan_vien)
            ->where('trang_thai', 'DELIVERED')
            ->where('trang_thai_thanh_toan', 'DA_THANH_TOAN')
            ->where('phuong_thuc_thanh_toan', 'COD')
            ->update(['trang_thai_thanh_toan' => 'DA_NOP']);

        return response()->json([
            'success'      => true,
            'so_tien'      => $soTienDaTra,
            'so_don'       => $updated,
            'message'      => 'Đã xác nhận ' . $nhanVien->ten_nhan_vien . ' nộp '
                              . number_format($soTienDaTra, 0, ',', '.') . ' đ ('
                              . $updated . ' đơn).',
        ]);
    }
}