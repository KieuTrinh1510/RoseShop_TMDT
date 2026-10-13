<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KhachHang;
use App\Models\NguoiDung;
use App\Models\HoaDon;
use App\Models\LichSuViPhamCod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KhachHangController extends Controller
{
    /**
     * Danh sách khách hàng với bộ lọc, tìm kiếm, sắp xếp và phân trang chuẩn.
     */
    public function index(Request $request)
    {
        $query = KhachHang::with(['nguoiDung', 'lichSuViPhamCod'])
            ->withCount('hoaDons')
            ->withSum('hoaDons', 'tong_tien');

        // 1. Tìm kiếm theo từ khóa (tên, số điện thoại, email, mã KH)
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                $q->where('ten_khach_hang', 'like', "%{$kw}%")
                  ->orWhere('so_dien_thoai', 'like', "%{$kw}%")
                  ->orWhere('email', 'like', "%{$kw}%");

                if (is_numeric($kw)) {
                    $q->orWhere('ma_khach_hang', (int)$kw);
                }
            });
        }

        // 2. Lọc theo trạng thái tài khoản người dùng
        if ($request->filled('trang_thai')) {
            $status = $request->trang_thai;
            $query->whereHas('nguoiDung', function ($q) use ($status) {
                $q->where('trang_thai', $status);
            });
        }

        // 3. Lọc theo trạng thái quyền COD
        if ($request->filled('quyen_cod')) {
            $query->where('quyen_cod', $request->quyen_cod);
        }

        // 4. Sắp xếp theo điểm tích lũy hoặc mới nhất
        $sort = $request->get('sort', '');
        if ($sort === 'diem_desc') {
            $query->orderBy('diem_tich_luy', 'desc');
        } elseif ($sort === 'diem_asc') {
            $query->orderBy('diem_tich_luy', 'asc');
        } else {
            $query->orderBy('ma_khach_hang', 'desc');
        }

        // 5. Phân trang: 10 dòng/trang mặc định (hỗ trợ 10, 20, 50, 100)
        $perPage = in_array((int)$request->get('per_page'), [10, 20, 50, 100]) ? (int)$request->get('per_page') : 10;
        $khachHangs = $query->paginate($perPage)->appends($request->query());

        // 6. 4 Chỉ số KPI thống kê thực tế
        $tongKhachHang   = KhachHang::count();
        $khachThanThiet  = KhachHang::where('diem_tich_luy', '>=', 50)->count();
        $tongDiemTichLuy = (int)KhachHang::sum('diem_tich_luy');
        $dangHoatDong    = KhachHang::whereHas('nguoiDung', function ($q) {
            $q->where('trang_thai', NguoiDung::TRANG_THAI_HOAT_DONG);
        })->count();
        $biGioiHanCod    = KhachHang::where('quyen_cod', 'GIOI_HAN')->count();

        // 7. Danh sách ID khách hàng đã phát sinh hóa đơn (để bảo toàn hồ sơ)
        $hasOrderCustomerIds = HoaDon::whereNotNull('ma_khach_hang')
            ->pluck('ma_khach_hang')
            ->unique()
            ->toArray();

        return view('admin.KhachHang', compact(
            'khachHangs',
            'tongKhachHang',
            'khachThanThiet',
            'tongDiemTichLuy',
            'dangHoatDong',
            'biGioiHanCod',
            'perPage',
            'hasOrderCustomerIds'
        ));
    }

    /**
     * Cập nhật thông tin khách hàng.
     * Lưu ý: Không cho phép Admin sửa trực tiếp ô Điểm tích lũy để bảo toàn lịch sử giao dịch.
     */
    public function update(Request $request, $id)
    {
        $khachHang = KhachHang::findOrFail($id);

        $request->validate([
            'ten_khach_hang' => 'required|string|max:100',
            'email'          => 'required|email|max:100|unique:khach_hang,email,' . $id . ',ma_khach_hang',
            'so_dien_thoai'  => 'required|string|regex:/^\d{10,11}$/',
            'dia_chi'        => 'required|string|max:255',
        ], [
            'ten_khach_hang.required' => 'Vui lòng nhập họ và tên.',
            'email.required'          => 'Vui lòng nhập email.',
            'email.email'             => 'Email không đúng định dạng.',
            'email.unique'            => 'Email đã được sử dụng. Vui lòng nhập email khác.',
            'so_dien_thoai.required'  => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.regex'     => 'Số điện thoại chỉ được chứa chữ số và phải có độ dài hợp lệ.',
            'dia_chi.required'        => 'Vui lòng nhập địa chỉ.',
        ]);

        // Cập nhật thông tin hồ sơ, tuyệt đối KHÔNG cập nhật điểm tích lũy thủ công
        $khachHang->update([
            'ten_khach_hang' => $request->ten_khach_hang,
            'email'          => $request->email,
            'so_dien_thoai'  => $request->so_dien_thoai,
            'dia_chi'        => $request->dia_chi,
        ]);

        return redirect()->back()->with('success', "Cập nhật thông tin khách hàng \"{$khachHang->ten_khach_hang}\" thành công!");
    }

    /**
     * Giới hạn quyền sử dụng COD của khách hàng.
     */
    public function limitCod(Request $request, $id)
    {
        $khachHang = KhachHang::findOrFail($id);

        // Chặn thao tác sai trạng thái
        if ($khachHang->quyen_cod === 'GIOI_HAN') {
            return back()->with('error', "Khách hàng \"{$khachHang->ten_khach_hang}\" hiện đã ở trạng thái bị giới hạn quyền COD!");
        }

        $request->validate([
            'loai_vi_pham' => 'required|string|max:50',
            'ly_do'        => 'required|string|max:500',
        ], [
            'loai_vi_pham.required' => 'Vui lòng chọn loại vi phạm.',
            'ly_do.required'        => 'Vui lòng nhập lý do giới hạn quyền COD.',
        ]);

        DB::beginTransaction();
        try {
            $khachHang->quyen_cod = 'GIOI_HAN';
            $khachHang->save();

            // Ghi nhận vào lịch sử vi phạm
            LichSuViPhamCod::create([
                'ma_khach_hang'   => $khachHang->ma_khach_hang,
                'ma_hoa_don'      => $request->filled('ma_hoa_don') ? $request->ma_hoa_don : null,
                'loai_vi_pham'    => $request->loai_vi_pham,
                'ly_do'           => $request->ly_do,
                'hanh_dong'       => 'GIOI_HAN_COD',
                'nguoi_thuc_hien' => auth()->user()->ten_dang_nhap ?? 'Admin',
            ]);

            DB::commit();
            return back()->with('success', "Đã giới hạn quyền đặt hàng COD đối với khách hàng \"{$khachHang->ten_khach_hang}\"!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật quyền COD: ' . $e->getMessage());
        }
    }

    /**
     * Khôi phục quyền sử dụng COD cho khách hàng.
     */
    public function restoreCod(Request $request, $id)
    {
        $khachHang = KhachHang::findOrFail($id);

        // Chặn thao tác sai trạng thái
        if ($khachHang->quyen_cod === 'CHO_PHEP') {
            return back()->with('error', "Khách hàng \"{$khachHang->ten_khach_hang}\" hiện đang được phép sử dụng COD bình thường!");
        }

        $request->validate([
            'ly_do' => 'required|string|max:500',
        ], [
            'ly_do.required' => 'Vui lòng nhập lý do / căn cứ khôi phục quyền COD.',
        ]);

        DB::beginTransaction();
        try {
            $khachHang->quyen_cod = 'CHO_PHEP';
            $khachHang->save();

            // Ghi nhận khôi phục vào lịch sử (bảo toàn lịch sử vi phạm trước đó)
            LichSuViPhamCod::create([
                'ma_khach_hang'   => $khachHang->ma_khach_hang,
                'loai_vi_pham'    => 'KHOI_PHUC',
                'ly_do'           => $request->ly_do,
                'hanh_dong'       => 'KHOI_PHUC_COD',
                'nguoi_thuc_hien' => auth()->user()->ten_dang_nhap ?? 'Admin',
            ]);

            DB::commit();
            return back()->with('success', "Đã khôi phục quyền đặt hàng COD cho khách hàng \"{$khachHang->ten_khach_hang}\" thành công!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi khôi phục quyền COD: ' . $e->getMessage());
        }
    }

    /**
     * Khóa hoặc Mở khóa tài khoản khách hàng.
     */
    public function toggleStatus(Request $request, $id)
    {
        $khachHang = KhachHang::findOrFail($id);
        $user = NguoiDung::find($khachHang->ma_khach_hang);

        if (!$user) {
            return back()->with('error', 'Không tìm thấy tài khoản người dùng tương ứng.');
        }

        $targetStatus = $request->input('target_status');

        if ($targetStatus === NguoiDung::TRANG_THAI_BI_KHOA) {
            if ($user->trang_thai === NguoiDung::TRANG_THAI_BI_KHOA) {
                return back()->with('error', "Tài khoản khách hàng \"{$khachHang->ten_khach_hang}\" đã ở trạng thái bị khóa!");
            }
            $user->trang_thai = NguoiDung::TRANG_THAI_BI_KHOA;
            $user->save();
            return back()->with('success', "Đã khóa tài khoản khách hàng \"{$khachHang->ten_khach_hang}\" thành công!");
        } elseif ($targetStatus === NguoiDung::TRANG_THAI_HOAT_DONG) {
            if ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) {
                return back()->with('error', "Tài khoản khách hàng \"{$khachHang->ten_khach_hang}\" đang hoạt động bình thường!");
            }
            $user->trang_thai = NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();
            return back()->with('success', "Đã mở khóa tài khoản khách hàng \"{$khachHang->ten_khach_hang}\" thành công!");
        } else {
            $isLocking = ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG);
            $user->trang_thai = $isLocking ? NguoiDung::TRANG_THAI_BI_KHOA : NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();
            $actionText = $isLocking ? 'khóa' : 'mở khóa';
            return back()->with('success', "Đã {$actionText} tài khoản khách hàng \"{$khachHang->ten_khach_hang}\" thành công!");
        }
    }

    /**
     * Xóa khách hàng (có kiểm tra bảo toàn hồ sơ đơn hàng).
     */
    public function destroy($id)
    {
        $khachHang = KhachHang::findOrFail($id);
        $soHoaDon = HoaDon::where('ma_khach_hang', $id)->count();

        // BẢO TOÀN HỒ SƠ: Chặn xóa nếu khách hàng đã có đơn hàng trong hệ thống
        if ($soHoaDon > 0) {
            return back()->with('error', "Bảo toàn hồ sơ: Khách hàng \"{$khachHang->ten_khach_hang}\" đã phát sinh {$soHoaDon} đơn hàng trong hệ thống, không được phép xóa vật lý! Vui lòng sử dụng tính năng \"Khóa tài khoản\".");
        }

        DB::beginTransaction();
        try {
            $maNguoiDung = $khachHang->ma_khach_hang;
            $tenKhachHang = $khachHang->ten_khach_hang;

            $khachHang->delete();
            NguoiDung::destroy($maNguoiDung);

            DB::commit();
            return back()->with('success', "Xóa khách hàng \"{$tenKhachHang}\" thành công!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }
}