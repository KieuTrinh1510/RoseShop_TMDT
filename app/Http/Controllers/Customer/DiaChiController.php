<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiaChiKhachHang;
use App\Models\KhachHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiaChiController extends Controller
{
    /**
     * Lấy danh sách địa chỉ của khách hàng đang đăng nhập
     */
    public function index(Request $request)
    {
        $khachHang = $this->getKhachHang();
        if (!$khachHang) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng.'], 403);
        }

        $danhSach = $khachHang->danhSachDiaChi()->get();
        return response()->json([
            'success'   => true,
            'danh_sach' => $danhSach,
        ]);
    }

    /**
     * Thêm địa chỉ mới
     */
    public function store(Request $request)
    {
        $khachHang = $this->getKhachHang();
        if (!$khachHang) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng.'], 403);
        }

        $validated = $this->validateDiaChi($request);

        $laMacDinh = $request->boolean('la_mac_dinh');
        $coDiaChiNaoChua = $khachHang->danhSachDiaChi()->exists();
        if (!$coDiaChiNaoChua) {
            $laMacDinh = true; // Địa chỉ đầu tiên luôn là mặc định
        }

        $diaChiDayDu = DiaChiKhachHang::buildDiaChiDayDu(
            $validated['dia_chi_chi_tiet'],
            $validated['xa_phuong'],
            $validated['quan_huyen']
        );

        $diaChi = DB::transaction(function () use ($khachHang, $validated, $diaChiDayDu, $laMacDinh) {
            if ($laMacDinh) {
                DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
                    ->update(['la_mac_dinh' => false]);

                $khachHang->update(['dia_chi' => $diaChiDayDu]);
            }

            return DiaChiKhachHang::create([
                'ma_khach_hang'    => $khachHang->ma_khach_hang,
                'ten_nguoi_nhan'   => $validated['ten_nguoi_nhan'],
                'so_dien_thoai'    => $validated['so_dien_thoai'],
                'quan_huyen'       => $validated['quan_huyen'],
                'xa_phuong'        => $validated['xa_phuong'],
                'dia_chi_chi_tiet' => $validated['dia_chi_chi_tiet'],
                'dia_chi_day_du'   => $diaChiDayDu,
                'la_mac_dinh'      => $laMacDinh,
            ]);
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Thêm địa chỉ nhận hàng thành công!',
            'data'      => $diaChi,
            'danh_sach' => $khachHang->danhSachDiaChi()->get(),
        ]);
    }

    /**
     * Cập nhật địa chỉ hiện có
     */
    public function update(Request $request, $id)
    {
        $khachHang = $this->getKhachHang();
        if (!$khachHang) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng.'], 403);
        }

        $diaChi = DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
            ->findOrFail($id);

        $validated = $this->validateDiaChi($request);
        $laMacDinh = $request->boolean('la_mac_dinh');

        $diaChiDayDu = DiaChiKhachHang::buildDiaChiDayDu(
            $validated['dia_chi_chi_tiet'],
            $validated['xa_phuong'],
            $validated['quan_huyen']
        );

        DB::transaction(function () use ($khachHang, $diaChi, $validated, $diaChiDayDu, $laMacDinh) {
            if ($laMacDinh) {
                DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
                    ->where('ma_dia_chi', '!=', $diaChi->ma_dia_chi)
                    ->update(['la_mac_dinh' => false]);

                $khachHang->update(['dia_chi' => $diaChiDayDu]);
            }

            $diaChi->update([
                'ten_nguoi_nhan'   => $validated['ten_nguoi_nhan'],
                'so_dien_thoai'    => $validated['so_dien_thoai'],
                'quan_huyen'       => $validated['quan_huyen'],
                'xa_phuong'        => $validated['xa_phuong'],
                'dia_chi_chi_tiet' => $validated['dia_chi_chi_tiet'],
                'dia_chi_day_du'   => $diaChiDayDu,
                'la_mac_dinh'      => $laMacDinh ? true : $diaChi->la_mac_dinh,
            ]);
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Cập nhật địa chỉ nhận hàng thành công!',
            'data'      => $diaChi->fresh(),
            'danh_sach' => $khachHang->danhSachDiaChi()->get(),
        ]);
    }

    /**
     * Thiết lập địa chỉ làm mặc định
     */
    public function setMacDinh(Request $request, $id)
    {
        $khachHang = $this->getKhachHang();
        if (!$khachHang) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng.'], 403);
        }

        $diaChi = DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
            ->findOrFail($id);

        DB::transaction(function () use ($khachHang, $diaChi) {
            DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
                ->update(['la_mac_dinh' => false]);

            $diaChi->update(['la_mac_dinh' => true]);
            $khachHang->update(['dia_chi' => $diaChi->dia_chi_day_du]);
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Đã thiết lập địa chỉ mặc định!',
            'data'      => $diaChi->fresh(),
            'danh_sach' => $khachHang->danhSachDiaChi()->get(),
        ]);
    }

    /**
     * Xóa địa chỉ
     */
    public function destroy($id)
    {
        $khachHang = $this->getKhachHang();
        if (!$khachHang) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng.'], 403);
        }

        $diaChi = DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)
            ->findOrFail($id);

        $wasDefault = $diaChi->la_mac_dinh;
        $diaChi->delete();

        // Nếu vừa xóa địa chỉ mặc định, tự động gán địa chỉ đầu tiên còn lại làm mặc định
        if ($wasDefault) {
            $firstOther = DiaChiKhachHang::where('ma_khach_hang', $khachHang->ma_khach_hang)->first();
            if ($firstOther) {
                $firstOther->update(['la_mac_dinh' => true]);
                $khachHang->update(['dia_chi' => $firstOther->dia_chi_day_du]);
            }
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Đã xóa địa chỉ thành công!',
            'danh_sach' => $khachHang->danhSachDiaChi()->get(),
        ]);
    }

    private function getKhachHang(): ?KhachHang
    {
        $user = Auth::user();
        if (!$user) return null;
        return KhachHang::find($user->ma_nguoi_dung);
    }

    private function validateDiaChi(Request $request): array
    {
        return $request->validate([
            'ten_nguoi_nhan'   => 'required|string|max:100',
            'so_dien_thoai'    => 'required|string|size:10|regex:/^\d{10}$/',
            'quan_huyen'       => ['required', 'string', 'max:100', Rule::in(ThanhToanController::HANOI_DISTRICTS)],
            'xa_phuong'        => 'required|string|max:100',
            'dia_chi_chi_tiet' => 'required|string|max:255',
        ], [
            'ten_nguoi_nhan.required'   => 'Vui lòng nhập tên người nhận.',
            'so_dien_thoai.required'    => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.size'        => 'Số điện thoại phải đúng 10 chữ số.',
            'so_dien_thoai.regex'       => 'Số điện thoại chỉ được chứa chữ số.',
            'quan_huyen.required'       => 'Vui lòng chọn quận/huyện.',
            'quan_huyen.in'             => 'Quận/huyện không hợp lệ.',
            'xa_phuong.required'        => 'Vui lòng chọn xã/phường.',
            'dia_chi_chi_tiet.required' => 'Vui lòng nhập địa chỉ cụ thể.',
        ]);
    }
}
