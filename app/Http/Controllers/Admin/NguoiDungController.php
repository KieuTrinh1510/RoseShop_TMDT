<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\KhachHang;
use App\Models\NhanVien;
use App\Models\HoaDon;
use App\Models\PhieuNhap;
use App\Models\LichSuViPhamCod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NguoiDungController extends Controller
{
    public function index(Request $request)
    {
        $query = NguoiDung::with(['khachHang', 'nhanVien']);

        // 1. Tìm kiếm theo từ khóa (Mã TK, Tên đăng nhập, Tên, Email, SĐT)
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                $q->where('ten_dang_nhap', 'like', "%{$kw}%");
                if (is_numeric($kw)) {
                    $q->orWhere('ma_nguoi_dung', (int)$kw);
                }
                $q->orWhereHas('khachHang', function ($sub) use ($kw) {
                    $sub->where('ten_khach_hang', 'like', "%{$kw}%")
                        ->orWhere('email', 'like', "%{$kw}%")
                        ->orWhere('so_dien_thoai', 'like', "%{$kw}%");
                });
                $q->orWhereHas('nhanVien', function ($sub) use ($kw) {
                    $sub->where('ten_nhan_vien', 'like', "%{$kw}%")
                        ->orWhere('email', 'like', "%{$kw}%")
                        ->orWhere('so_dien_thoai', 'like', "%{$kw}%");
                });
            });
        }

        // 2. Lọc theo vai trò (ADMIN, NHAN_VIEN, SHIPPER, KHACH_HANG)
        if ($request->filled('vai_tro')) {
            $query->where('vai_tro', $request->vai_tro);
        }

        // 3. Lọc theo trạng thái tài khoản (HOAT_DONG, BI_KHOA)
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        // 4. Sắp xếp
        $sort = $request->get('sort', '');
        if ($sort === 'id_asc') {
            $query->orderBy('ma_nguoi_dung', 'asc');
        } elseif ($sort === 'username_asc') {
            $query->orderBy('ten_dang_nhap', 'asc');
        } elseif ($sort === 'username_desc') {
            $query->orderBy('ten_dang_nhap', 'desc');
        } else {
            $query->orderBy('ma_nguoi_dung', 'desc');
        }

        // 5. Phân trang: 10 (mặc định), 20, 50, 100
        $perPage = in_array((int)$request->get('per_page'), [10, 20, 50, 100]) ? (int)$request->get('per_page') : 10;
        $nguoiDungs = $query->paginate($perPage)->appends($request->query());

        // 6. Thống kê KPI
        $tongTaiKhoan   = NguoiDung::count();
        $soAdmin        = NguoiDung::where('vai_tro', 'ADMIN')->count();
        $soNhanSu       = NguoiDung::whereIn('vai_tro', ['NHAN_VIEN', 'SHIPPER'])->count();
        $soKhachHang    = NguoiDung::where('vai_tro', 'KHACH_HANG')->count();
        $soDangHoatDong = NguoiDung::where('trang_thai', NguoiDung::TRANG_THAI_HOAT_DONG)->count();
        $soBiKhoa       = NguoiDung::where('trang_thai', NguoiDung::TRANG_THAI_BI_KHOA)->count();

        // 7. Danh sách ID tài khoản đã phát sinh dữ liệu nghiệp vụ (bảo toàn hồ sơ, chặn xóa vật lý)
        $orderCustomers = HoaDon::pluck('ma_khach_hang')->toArray();
        $orderStaff     = HoaDon::whereNotNull('ma_nhan_vien_giao')->pluck('ma_nhan_vien_giao')->toArray();
        $importStaff    = PhieuNhap::pluck('ma_nhan_vien')->toArray();
        $codCustomers   = LichSuViPhamCod::pluck('ma_khach_hang')->toArray();

        $hasBusinessIds = array_unique(array_merge($orderCustomers, $orderStaff, $importStaff, $codCustomers));

        // 8. Mã tài khoản tiếp theo
        $maxId = (int)NguoiDung::max('ma_nguoi_dung');
        $nextMaTK = 'TK-' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);

        return view('admin.NguoiDung', compact(
            'nguoiDungs',
            'tongTaiKhoan',
            'soAdmin',
            'soNhanSu',
            'soKhachHang',
            'soDangHoatDong',
            'soBiKhoa',
            'perPage',
            'hasBusinessIds',
            'nextMaTK'
        ));
    }

    public function checkUsername(Request $request)
    {
        $exists = NguoiDung::where('ten_dang_nhap', $request->ten_dang_nhap)
            ->when($request->user_id, fn($q) => $q->where('ma_nguoi_dung', '!=', $request->user_id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function editData($id)
    {
        $user = NguoiDung::with(['khachHang', 'nhanVien'])->findOrFail($id);
        $data = $user->toArray();

        if ($user->isKhachHang() && $user->khachHang) {
            $data = array_merge($data, $user->khachHang->toArray());
        } elseif ($user->isNhanVien() && $user->nhanVien) {
            $data = array_merge($data, $user->nhanVien->toArray());
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_dang_nhap' => 'required|unique:nguoi_dung',
            'mat_khau' => 'required|min:6',
            'vai_tro' => 'required|in:KHACH_HANG,NHAN_VIEN,SHIPPER,ADMIN',
        ]);

        DB::beginTransaction();
        try {
            $user = NguoiDung::create([
                'ten_dang_nhap'     => $request->ten_dang_nhap,
                'mat_khau'          => $request->mat_khau,
                'vai_tro'           => $request->vai_tro,
                'trang_thai'        => NguoiDung::TRANG_THAI_HOAT_DONG,
                'email_verified_at' => now(),
            ]);

            if ($request->vai_tro == 'KHACH_HANG') {
                $this->validateKhachHang($request);
                KhachHang::create([
                    'ma_khach_hang' => $user->ma_nguoi_dung,
                    'ten_khach_hang' => $request->ten_khach_hang,
                    'so_dien_thoai' => $request->so_dien_thoai,
                    'email' => $request->email,
                    'dia_chi' => $request->dia_chi,
                    'diem_tich_luy' => $request->diem_tich_luy ?? 0,
                ]);
            } elseif (in_array($request->vai_tro, ['NHAN_VIEN', 'SHIPPER'])) {
                $this->validateNhanVien($request);
                NhanVien::create([
                    'ma_nhan_vien' => $user->ma_nguoi_dung,
                    'ten_nhan_vien' => $request->ten_nhan_vien,
                    'email' => $request->email_nv,
                    'so_dien_thoai' => $request->so_dien_thoai_nv,
                    'chuc_vu' => $request->chuc_vu,
                    'cong_viec' => $request->cong_viec,
                    'luong' => $request->luong,
                ]);
            }

            DB::commit();
            return redirect()->route('nguoi-dung.index')->with('success', 'Thêm người dùng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Lỗi: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $user = NguoiDung::findOrFail($id);

        $request->validate([
            'ten_dang_nhap' => 'required|unique:nguoi_dung,ten_dang_nhap,' . $id . ',ma_nguoi_dung',
            'mat_khau' => 'nullable|min:6',
        ]);

        DB::beginTransaction();
        try {
            $user->ten_dang_nhap = $request->ten_dang_nhap;
            if ($request->filled('mat_khau')) {
                $user->mat_khau = $request->mat_khau;
            }
            $user->save();

            if ($user->isKhachHang()) {
                $this->validateKhachHang($request);
                KhachHang::updateOrCreate(
                    ['ma_khach_hang' => $user->ma_nguoi_dung],
                    [
                        'ten_khach_hang' => $request->ten_khach_hang,
                        'so_dien_thoai' => $request->so_dien_thoai,
                        'email' => $request->email,
                        'dia_chi' => $request->dia_chi,
                        'diem_tich_luy' => $request->diem_tich_luy ?? 0,
                    ]
                );
            } elseif ($user->isNhanVien()) {
                $this->validateNhanVien($request);
                NhanVien::updateOrCreate(
                    ['ma_nhan_vien' => $user->ma_nguoi_dung],
                    [
                        'ten_nhan_vien' => $request->ten_nhan_vien,
                        'email' => $request->email_nv,
                        'so_dien_thoai' => $request->so_dien_thoai_nv,
                        'chuc_vu' => $request->chuc_vu,
                        'cong_viec' => $request->cong_viec,
                        'luong' => $request->luong,
                    ]
                );
            }
            

            DB::commit();
            return redirect()->route('nguoi-dung.index')->with('success', 'Cập nhật thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Lỗi: ' . $e->getMessage())->withInput();
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            abort(403, 'Chỉ Quản trị viên (Admin) mới có quyền khóa hoặc mở khóa tài khoản.');
        }

        if ((int)$id === (int)auth()->id()) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình đang đăng nhập!');
        }

        $user = NguoiDung::findOrFail($id);
        $targetStatus = $request->input('target_status');

        if ($targetStatus === NguoiDung::TRANG_THAI_BI_KHOA) {
            // Chặn thao tác trùng
            if ($user->trang_thai === NguoiDung::TRANG_THAI_BI_KHOA) {
                return back()->with('error', "Tài khoản \"{$user->ten_dang_nhap}\" đã ở trạng thái Bị khóa, không thể khóa lại!");
            }
            $user->trang_thai = NguoiDung::TRANG_THAI_BI_KHOA;
            $user->save();
            return back()->with('success', "Đã khóa tài khoản \"{$user->ten_dang_nhap}\" thành công!");
        } elseif ($targetStatus === NguoiDung::TRANG_THAI_HOAT_DONG) {
            // Chặn thao tác trùng
            if ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) {
                return back()->with('error', "Tài khoản \"{$user->ten_dang_nhap}\" đang ở trạng thái Hoạt động, không thể mở khóa!");
            }
            $user->trang_thai = NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();
            return back()->with('success', "Đã mở khóa tài khoản \"{$user->ten_dang_nhap}\" thành công!");
        } else {
            $isLocking = ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG);
            $user->trang_thai = $isLocking ? NguoiDung::TRANG_THAI_BI_KHOA : NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();
            $action = $isLocking ? 'Khóa' : 'Mở khóa';
            return back()->with('success', "Đã {$action} tài khoản \"{$user->ten_dang_nhap}\" thành công!");
        }
    }

    public function destroy(Request $request, $id)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            abort(403, 'Chỉ Quản trị viên (Admin) mới có quyền xóa tài khoản.');
        }

        if ((int)$id === (int)auth()->id()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Bạn không thể tự xóa tài khoản của chính mình đang đăng nhập!'], 422);
            }
            return back()->with('error', 'Bạn không thể tự xóa tài khoản của chính mình đang đăng nhập!');
        }

        $user = NguoiDung::findOrFail($id);

        // BẢO TOÀN DỮ LIỆU NGHIỆP VỤ: Không xóa vật lý nếu đã phát sinh dữ liệu
        $hasOrders = HoaDon::where('ma_khach_hang', $id)->orWhere('ma_nhan_vien_giao', $id)->exists();
        $hasImports = PhieuNhap::where('ma_nhan_vien', $id)->exists();
        $hasCodViolations = LichSuViPhamCod::where('ma_khach_hang', $id)->exists();

        if ($hasOrders || $hasImports || $hasCodViolations) {
            $msg = 'Bảo toàn dữ liệu: Tài khoản "' . $user->ten_dang_nhap . '" đã phát sinh dữ liệu nghiệp vụ trong hệ thống (đơn hàng/nhập kho/lịch sử COD), không được phép xóa vật lý! Vui lòng sử dụng tính năng "Khóa tài khoản" thay thế.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        DB::beginTransaction();
        try {
            if ($user->isKhachHang()) {
                KhachHang::where('ma_khach_hang', $id)->delete();
            } elseif ($user->isNhanVien()) {
                NhanVien::where('ma_nhan_vien', $id)->delete();
            }

            $user->delete();
            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Xóa tài khoản thành công!']);
            }
            return back()->with('success', 'Xóa tài khoản thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Lỗi xóa tài khoản: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    private function validateKhachHang(Request $request)
    {
        $request->validate([
            'ten_khach_hang' => 'required|string|max:100',
            'so_dien_thoai' => 'required|string|size:10|regex:/^\d{10}$/',
            'email' => 'required|email|max:100',
            'dia_chi' => 'nullable|string',
            'diem_tich_luy' => 'nullable|integer',
        ]);
    }

    private function validateNhanVien(Request $request)
    {
        $request->validate([
            'ten_nhan_vien' => 'required|string|max:100',
            'email_nv' => 'required|email|max:100',
            'so_dien_thoai_nv' => 'required|string|size:10|regex:/^\d{10}$/',
            'chuc_vu' => 'required|in:CSKH,VAN_HANH,THIET_KE,ONLINE,SHIPPER,KHAC',
            'cong_viec' => 'nullable|string',
            'luong' => 'nullable|numeric|min:0',
        ]);
    }

    /**
     * Admin đặt lại mật khẩu mới cho tài khoản nội bộ (không xem hoặc phục hồi mật khẩu cũ).
     */
    public function resetPassword(Request $request, $id)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }

        $user = NguoiDung::findOrFail($id);

        if ($user->isKhachHang()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chức năng này chỉ áp dụng cho tài khoản nội bộ. Khách hàng thực hiện qua luồng Quên mật khẩu email.',
                ], 422);
            }
            return back()->withErrors('Chức năng này chỉ áp dụng cho tài khoản nội bộ.');
        }

        $request->validate([
            'mat_khau' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'mat_khau.required'  => 'Vui lòng nhập mật khẩu mới.',
            'mat_khau.min'       => 'Mật khẩu mới phải có tối thiểu 6 ký tự.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        // Cập nhật mật khẩu mới, không đọc/giải mã mật khẩu cũ
        $user->mat_khau = $request->mat_khau;
        $user->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đặt lại mật khẩu cho tài khoản ' . $user->ten_dang_nhap . ' thành công!',
            ]);
        }

        return redirect()->route('nguoi-dung.index')->with('success', 'Đặt lại mật khẩu cho tài khoản ' . $user->ten_dang_nhap . ' thành công!');
    }
}