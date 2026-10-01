<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NhanVien;
use App\Models\NguoiDung;
use App\Models\HoaDon;
use App\Models\PhieuNhap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NhanVienController extends Controller
{
    public function index(Request $request)
    {
        $query = NhanVien::with('nguoiDung');

        // Tìm kiếm theo từ khóa (mã NV, tên, email, số điện thoại)
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                $q->where('ten_nhan_vien', 'like', "%{$kw}%")
                  ->orWhere('email', 'like', "%{$kw}%")
                  ->orWhere('so_dien_thoai', 'like', "%{$kw}%");

                if (is_numeric($kw)) {
                    $q->orWhere('ma_nhan_vien', (int)$kw);
                }
            });
        }

        // Lọc theo chức vụ (Đặc tả R2.2)
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

        // Sắp xếp theo mức lương (Đặc tả R2.2)
        $sort = $request->get('sort', '');
        if ($sort === 'luong_desc') {
            $query->orderBy('luong', 'desc');
        } elseif ($sort === 'luong_asc') {
            $query->orderBy('luong', 'asc');
        } else {
            $query->orderBy('ma_nhan_vien', 'desc');
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

        // Danh sách số đơn hàng CHƯA HOÀN TẤT mà nhân viên đang phụ trách (chặn xóa)
        $inProgressOrderCounts = HoaDon::query()
            ->whereNotNull('ma_nhan_vien_giao')
            ->whereNotIn('trang_thai', ['DELIVERED', 'CANCELLED'])
            ->groupBy('ma_nhan_vien_giao')
            ->select('ma_nhan_vien_giao', DB::raw('COUNT(*) as so_don'))
            ->pluck('so_don', 'ma_nhan_vien_giao');

        // Danh sách ID nhân viên đã phát sinh nghiệp vụ (để bảo toàn hồ sơ, chặn xóa vật lý)
        $hasBusinessIds = array_unique(array_merge(
            HoaDon::whereNotNull('ma_nhan_vien_giao')->pluck('ma_nhan_vien_giao')->toArray(),
            PhieuNhap::pluck('ma_nhan_vien')->toArray()
        ));

        return view('admin.NhanVien', compact(
            'nhanViens',
            'chucVus',
            'shipperDebts',
            'tongNhanVien',
            'soNhanVien',
            'soShipper',
            'dangHoatDong',
            'perPage',
            'hasBusinessIds',
            'inProgressOrderCounts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_nhan_vien'     => 'required|string|max:100',
            'email'             => 'required|email|max:100|unique:nhan_vien,email|unique:nguoi_dung,ten_dang_nhap',
            'so_dien_thoai'     => 'required|string|regex:/^\d{10,11}$/',
            'chuc_vu'           => 'required|in:CSKH,VAN_HANH,THIET_KE,ONLINE,SHIPPER,KHAC',
            'cong_viec'         => 'required|string|max:255',
            'luong'             => 'required|numeric|gt:0',
            'mat_khau'          => 'required|string|min:6',
            'xac_nhan_mat_khau' => 'required|same:mat_khau',
        ], [
            'ten_nhan_vien.required'     => 'Vui lòng nhập họ và tên.',
            'email.required'             => 'Vui lòng nhập email.',
            'email.email'                => 'Email không đúng định dạng.',
            'email.unique'               => 'Email đã được sử dụng. Vui lòng nhập email khác.',
            'so_dien_thoai.required'     => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.regex'        => 'Số điện thoại chỉ được chứa chữ số và phải có độ dài hợp lệ.',
            'chuc_vu.required'           => 'Vui lòng chọn chức vụ.',
            'cong_viec.required'         => 'Vui lòng nhập mô tả công việc.',
            'luong.required'             => 'Vui lòng nhập mức lương.',
            'luong.numeric'              => 'Lương phải là số.',
            'luong.gt'                   => 'Lương phải là số và lớn hơn 0.',
            'mat_khau.required'          => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min'               => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'xac_nhan_mat_khau.required' => 'Vui lòng xác nhận mật khẩu.',
            'xac_nhan_mat_khau.same'     => 'Mật khẩu xác nhận không khớp.',
        ]);

        DB::beginTransaction();
        try {
            $vaiTro = ($request->chuc_vu === 'SHIPPER') ? 'SHIPPER' : 'NHAN_VIEN';
            $user = NguoiDung::create([
                'ten_dang_nhap'     => $request->email, // Email đồng thời là thông tin đăng nhập
                'mat_khau'          => $request->mat_khau,
                'vai_tro'           => $vaiTro,
                'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG, // Hệ thống xác định
                'email_verified_at' => now(),
            ]);

            NhanVien::create([
                'ma_nhan_vien'  => $user->ma_nguoi_dung, // Mã NV tự sinh từ hệ thống
                'ten_nhan_vien' => $request->ten_nhan_vien,
                'email'         => $request->email,
                'so_dien_thoai' => $request->so_dien_thoai,
                'chuc_vu'       => $request->chuc_vu,
                'cong_viec'     => $request->cong_viec,
                'luong'         => $request->luong,
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
        $nhanVien = NhanVien::findOrFail($ma_nhan_vien);
        $user = NguoiDung::find($ma_nhan_vien);

        $request->validate([
            'ten_nhan_vien' => 'required|string|max:100',
            'email'         => 'required|email|max:100|unique:nhan_vien,email,' . $ma_nhan_vien . ',ma_nhan_vien',
            'so_dien_thoai' => 'required|string|regex:/^\d{10,11}$/',
            'chuc_vu'       => 'required|in:CSKH,VAN_HANH,THIET_KE,ONLINE,SHIPPER,KHAC',
            'cong_viec'     => 'required|string|max:255',
            'luong'         => 'required|numeric|gt:0',
        ], [
            'ten_nhan_vien.required' => 'Vui lòng nhập họ và tên.',
            'email.required'         => 'Vui lòng nhập email.',
            'email.email'            => 'Email không đúng định dạng.',
            'email.unique'           => 'Email đã được sử dụng. Vui lòng nhập email khác.',
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.regex'    => 'Số điện thoại chỉ được chứa chữ số và phải có độ dài hợp lệ.',
            'chuc_vu.required'       => 'Vui lòng chọn chức vụ.',
            'cong_viec.required'     => 'Vui lòng nhập mô tả công việc.',
            'luong.required'         => 'Vui lòng nhập mức lương.',
            'luong.numeric'          => 'Lương phải là số.',
            'luong.gt'               => 'Lương phải là số và lớn hơn 0.',
        ]);

        $nhanVien->update($request->only([
            'ten_nhan_vien', 'email', 'so_dien_thoai', 'chuc_vu', 'cong_viec', 'luong'
        ]));

        if ($user) {
            // Cập nhật email/vai trò tương ứng nếu thay đổi
            $user->vai_tro = ($request->chuc_vu === 'SHIPPER') ? 'SHIPPER' : 'NHAN_VIEN';
            $user->save();
        }

        return redirect()->route('nhan-vien.index')
                         ->with('success', "Cập nhật thông tin nhân viên \"{$nhanVien->ten_nhan_vien}\" thành công!");
    }

    public function toggleStatus(Request $request, $ma_nhan_vien)
    {
        $nhanVien = NhanVien::findOrFail($ma_nhan_vien);
        $user = NguoiDung::find($nhanVien->ma_nhan_vien);

        if (!$user) {
            return back()->with('error', 'Không tìm thấy tài khoản người dùng tương ứng.');
        }

        $targetStatus = $request->input('target_status');

        if ($targetStatus === 'BI_KHOA') {
            if ($user->trang_thai === NguoiDung::TRANG_THAI_BI_KHOA) {
                return back()->with('error', "Nhân viên \"{$nhanVien->ten_nhan_vien}\" đã ở trạng thái ngừng hoạt động, không thể thực hiện lại!");
            }
            $user->trang_thai = NguoiDung::TRANG_THAI_BI_KHOA;
            $user->save();
            return back()->with('success', "Đã ngừng hoạt động (khóa tài khoản) nhân viên \"{$nhanVien->ten_nhan_vien}\" thành công!");
        } elseif ($targetStatus === 'HOAT_DONG') {
            if ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) {
                return back()->with('error', "Nhân viên \"{$nhanVien->ten_nhan_vien}\" đang hoạt động bình thường!");
            }
            $user->trang_thai = NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();
            return back()->with('success', "Đã mở lại hoạt động cho nhân viên \"{$nhanVien->ten_nhan_vien}\" thành công!");
        } else {
            $isLocking = ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG);
            $user->trang_thai = $isLocking ? NguoiDung::TRANG_THAI_BI_KHOA : NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();
            $msg = $isLocking ? 'ngừng hoạt động (khóa)' : 'mở lại hoạt động cho';
            return back()->with('success', "Đã {$msg} nhân viên \"{$nhanVien->ten_nhan_vien}\" thành công!");
        }
    }

    public function destroy($ma_nhan_vien)
    {
        $nhanVien    = NhanVien::findOrFail($ma_nhan_vien);
        $maNguoiDung = $nhanVien->ma_nhan_vien;

        // 1. CHẶN XÓA NẾU ĐANG CÓ ĐƠN HÀNG CHƯA HOÀN TẤT
        $soDonChuaHoanTat = HoaDon::where('ma_nhan_vien_giao', $ma_nhan_vien)
            ->whereNotIn('trang_thai', ['DELIVERED', 'CANCELLED'])
            ->count();

        if ($soDonChuaHoanTat > 0) {
            return back()->with('error', "Không thể xóa: Nhân viên đang phụ trách {$soDonChuaHoanTat} đơn hàng chưa hoàn tất. Vui lòng hoàn tất/điều phối các đơn trước khi thực hiện xóa.");
        }

        // 2. BẢO TOÀN HỒ SƠ: Chặn xóa vật lý nếu nhân viên đã phát sinh nghiệp vụ trước đó
        $hasInvoices = HoaDon::where('ma_nhan_vien_giao', $ma_nhan_vien)->exists();
        $hasImports  = PhieuNhap::where('ma_nhan_vien', $ma_nhan_vien)->exists();

        if ($hasInvoices || $hasImports) {
            return back()->with('error', "Bảo toàn hồ sơ: Nhân viên \"{$nhanVien->ten_nhan_vien}\" đã phát sinh nghiệp vụ trong hệ thống (giao hàng / nhập hàng), không được phép xóa vật lý! Vui lòng sử dụng tính năng \"Khóa tài khoản\".");
        }

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