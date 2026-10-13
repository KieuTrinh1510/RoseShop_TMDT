<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NhanVien;
use App\Models\NguoiDung;
use App\Models\HoaDon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipperController extends Controller
{
    /**
     * Danh sách Shipper (UC2.4 - a)
     */
    public function index(Request $request)
    {
        // Chỉ lấy những nhân viên có chức vụ SHIPPER hoặc vai trò SHIPPER
        $query = NhanVien::with('nguoiDung')
            ->where(function ($q) {
                $q->where('chuc_vu', 'SHIPPER')
                  ->orWhereHas('nguoiDung', function ($subQ) {
                      $subQ->where('vai_tro', 'SHIPPER');
                  });
            });

        // 1. Tìm kiếm theo từ khóa (Mã shipper, họ tên, email, SĐT)
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

        // 2. Lọc theo trạng thái tài khoản (HOAT_DONG, BI_KHOA)
        if ($request->filled('trang_thai')) {
            $status = $request->trang_thai;
            $query->whereHas('nguoiDung', function ($q) use ($status) {
                $q->where('trang_thai', $status);
            });
        }

        // 3. Lọc theo trạng thái làm việc (ONLINE, OFFLINE)
        if ($request->filled('trang_thai_hoat_dong')) {
            $query->where('trang_thai_hoat_dong', $request->trang_thai_hoat_dong);
        }

        // 4. Lọc theo trạng thái nhận đơn (1: Đang nhận, 0: Tắt nhận)
        if ($request->filled('nhan_don')) {
            $query->where('nhan_don', (int)$request->nhan_don);
        }

        // 5. Sắp xếp
        $sort = $request->get('sort', '');
        if ($sort === 'don_desc') {
            $query->orderBy('so_don_hien_tai', 'desc');
        } elseif ($sort === 'don_asc') {
            $query->orderBy('so_don_hien_tai', 'asc');
        } elseif ($sort === 'luong_desc') {
            $query->orderBy('luong', 'desc');
        } elseif ($sort === 'luong_asc') {
            $query->orderBy('luong', 'asc');
        } else {
            $query->orderBy('ma_nhan_vien', 'desc');
        }

        // 6. Phân trang: 10 (mặc định), 20, 50, 100
        $perPage = in_array((int)$request->get('per_page'), [10, 20, 50, 100]) ? (int)$request->get('per_page') : 10;
        $shippers = $query->paginate($perPage)->appends($request->query());

        // 7. Thống kê KPI tổng quan (4 chỉ số đầu trang)
        $tongShipper = NhanVien::where('chuc_vu', 'SHIPPER')->count();
        $dangHoatDong = NhanVien::where('chuc_vu', 'SHIPPER')->whereHas('nguoiDung', function ($q) {
            $q->where('trang_thai', NguoiDung::TRANG_THAI_HOAT_DONG);
        })->count();
        $dangOnline = NhanVien::where('chuc_vu', 'SHIPPER')->where('trang_thai_hoat_dong', 'ONLINE')->count();
        $dangGiaoHang = NhanVien::where('chuc_vu', 'SHIPPER')->where('so_don_hien_tai', '>', 0)->count();

        // 8. Đếm số đơn đã giao hoàn thành theo từng shipper
        $completedOrdersCount = HoaDon::query()
            ->whereNotNull('ma_nhan_vien_giao')
            ->where('trang_thai', 'DELIVERED')
            ->groupBy('ma_nhan_vien_giao')
            ->select('ma_nhan_vien_giao', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'ma_nhan_vien_giao');

        // 9. Danh sách ID shipper đã phát sinh lịch sử giao hàng (để kiểm tra xóa)
        $hasDeliveryHistoryIds = HoaDon::query()
            ->whereNotNull('ma_nhan_vien_giao')
            ->distinct()
            ->pluck('ma_nhan_vien_giao')
            ->toArray();

        return view('admin.Shipper', compact(
            'shippers',
            'perPage',
            'tongShipper',
            'dangHoatDong',
            'dangOnline',
            'dangGiaoHang',
            'completedOrdersCount',
            'hasDeliveryHistoryIds'
        ));
    }

    /**
     * Xem thông tin chi tiết Shipper (UC2.4 - b, c)
     */
    public function detail($id)
    {
        $shipper = NhanVien::with('nguoiDung')->findOrFail($id);

        // Thống kê lịch sử đơn hàng
        $soDonDangGiao = HoaDon::where('ma_nhan_vien_giao', $id)->where('trang_thai', 'SHIPPING')->count();
        $soDonDaGiao   = HoaDon::where('ma_nhan_vien_giao', $id)->where('trang_thai', 'DELIVERED')->count();
        $soDonDaHuy    = HoaDon::where('ma_nhan_vien_giao', $id)->where('trang_thai', 'CANCELLED')->count();
        $tongDon       = HoaDon::where('ma_nhan_vien_giao', $id)->count();

        // Công nợ COD đang giữ
        $tienCodDangGiu = HoaDon::where('ma_nhan_vien_giao', $id)
            ->where('trang_thai', 'DELIVERED')
            ->where('trang_thai_thanh_toan', 'DA_THANH_TOAN')
            ->where('phuong_thuc_thanh_toan', 'COD')
            ->sum('tong_tien');

        return response()->json([
            'success' => true,
            'data' => [
                'ma_nhan_vien'        => $shipper->ma_nhan_vien,
                'ten_nhan_vien'       => $shipper->ten_nhan_vien,
                'email'               => $shipper->email,
                'so_dien_thoai'       => $shipper->so_dien_thoai,
                'chuc_vu'             => $shipper->chuc_vu,
                'cong_viec'           => $shipper->cong_viec ?? 'Giao hàng',
                'luong'               => (float)$shipper->luong,
                'trang_thai_hoat_dong'=> $shipper->trang_thai_hoat_dong ?? 'OFFLINE',
                'nhan_don'            => (int)($shipper->nhan_don ?? 0),
                'so_don_hien_tai'     => (int)($shipper->so_don_hien_tai ?? 0),
                'tien_phat'           => (float)($shipper->tien_phat ?? 0),
                'ten_dang_nhap'       => $shipper->nguoiDung->ten_dang_nhap ?? 'N/A',
                'trang_thai_tai_khoan'=> $shipper->nguoiDung->trang_thai ?? 'HOAT_DONG',
                'thong_ke' => [
                    'tong_don'         => $tongDon,
                    'so_don_dang_giao' => $soDonDangGiao,
                    'so_don_da_giao'   => $soDonDaGiao,
                    'so_don_da_huy'    => $soDonDaHuy,
                    'tien_cod_dang_giu'=> $tienCodDangGiu,
                ]
            ]
        ]);
    }

    /**
     * Xem lịch sử giao hàng của Shipper (UC2.4 - d)
     */
    public function lichSuGiaoHang($id)
    {
        $shipper = NhanVien::findOrFail($id);

        $donHangs = HoaDon::with('khachHang')
            ->where('ma_nhan_vien_giao', $id)
            ->orderBy('ngay_dat', 'desc')
            ->get();

        $danhSach = $donHangs->map(function ($dh) {
            return [
                'ma_hoa_don'     => $dh->ma_hoa_don,
                'ten_khach_hang' => $dh->khachHang->ten_khach_hang ?? 'Khách lẻ',
                'so_dien_thoai'  => $dh->so_dien_thoai ?? ($dh->khachHang->so_dien_thoai ?? ''),
                'dia_chi_giao'   => $dh->dia_chi_giao ?? '',
                'ngay_dat'       => $dh->ngay_dat ? $dh->ngay_dat->format('d/m/Y H:i') : '',
                'ngay_giao'      => $dh->ngay_giao ? $dh->ngay_giao->format('d/m/Y H:i') : '',
                'tong_tien'      => (float)$dh->tong_tien,
                'trang_thai'     => $dh->trang_thai,
                'phuong_thuc'    => $dh->phuong_thuc_thanh_toan,
                'da_phat_tre'    => (bool)$dh->da_phat_tre,
            ];
        });

        return response()->json([
            'success' => true,
            'shipper' => [
                'ma_nhan_vien'  => $shipper->ma_nhan_vien,
                'ten_nhan_vien' => $shipper->ten_nhan_vien,
            ],
            'total'   => $donHangs->count(),
            'don_hang'=> $danhSach,
        ]);
    }

    /**
     * Tạm khóa / Mở khóa Shipper (UC2.4 - e, f)
     */
    public function toggleStatus($id, Request $request)
    {
        $shipper = NhanVien::with('nguoiDung')->findOrFail($id);
        $user    = $shipper->nguoiDung;

        if (!$user) {
            return back()->with('error', "Shipper \"{$shipper->ten_nhan_vien}\" chưa có tài khoản hệ thống!");
        }

        $targetStatus = $request->get('target_status');

        if ($targetStatus === 'BI_KHOA') {
            // Kiểm tra quy tắc nghiệp vụ: Đang khóa thì không khóa lại
            if ($user->trang_thai === NguoiDung::TRANG_THAI_BI_KHOA) {
                return back()->with('error', "Shipper \"{$shipper->ten_nhan_vien}\" đã ở trạng thái tạm khóa, không thể thực hiện lại!");
            }

            // Tạm khóa tài khoản
            $user->trang_thai = NguoiDung::TRANG_THAI_BI_KHOA;
            $user->save();

            // Shipper bị tạm khóa không được tiếp tục nhận đơn/giao hàng
            $shipper->nhan_don = 0;
            $shipper->trang_thai_hoat_dong = 'OFFLINE';
            $shipper->save();

            return back()->with('success', "Đã tạm khóa tài khoản Shipper \"{$shipper->ten_nhan_vien}\" thành công!");
        } elseif ($targetStatus === 'HOAT_DONG') {
            // Kiểm tra quy tắc nghiệp vụ: Chỉ cho phép mở khóa khi đang ở trạng thái bị khóa
            if ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG) {
                return back()->with('error', "Shipper \"{$shipper->ten_nhan_vien}\" đang ở trạng thái hoạt động!");
            }

            $user->trang_thai = NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();

            return back()->with('success', "Đã mở khóa hoạt động cho Shipper \"{$shipper->ten_nhan_vien}\" thành công!");
        } else {
            // Chuyển đổi trạng thái đảo ngược nếu không truyền rõ target_status
            $isLocking = ($user->trang_thai === NguoiDung::TRANG_THAI_HOAT_DONG);
            $user->trang_thai = $isLocking ? NguoiDung::TRANG_THAI_BI_KHOA : NguoiDung::TRANG_THAI_HOAT_DONG;
            $user->save();

            if ($isLocking) {
                $shipper->nhan_don = 0;
                $shipper->trang_thai_hoat_dong = 'OFFLINE';
                $shipper->save();
            }

            $msg = $isLocking ? 'tạm khóa' : 'mở khóa hoạt động cho';
            return back()->with('success', "Đã {$msg} Shipper \"{$shipper->ten_nhan_vien}\" thành công!");
        }
    }

    /**
     * Xóa Shipper (Tuân thủ Quy tắc nghiệp vụ UC2.4: Không xóa dữ liệu đã phát sinh lịch sử giao hàng)
     */
    public function destroy($id)
    {
        $shipper     = NhanVien::findOrFail($id);
        $maNguoiDung = $shipper->ma_nhan_vien;

        // 1. Chặn xóa nếu shipper đang có đơn hàng chưa hoàn tất
        $soDonChuaHoanTat = HoaDon::where('ma_nhan_vien_giao', $id)
            ->whereNotIn('trang_thai', ['DELIVERED', 'CANCELLED'])
            ->count();

        if ($soDonChuaHoanTat > 0) {
            return back()->with('error', "Không thể xóa: Shipper \"{$shipper->ten_nhan_vien}\" đang phụ trách {$soDonChuaHoanTat} đơn hàng chưa hoàn tất. Vui lòng điều phối lại đơn hàng trước khi xóa!");
        }

        // 2. Bảo toàn hồ sơ: Không xóa dữ liệu Shipper đã phát sinh lịch sử giao hàng
        $hasHistory = HoaDon::where('ma_nhan_vien_giao', $id)->exists();

        if ($hasHistory) {
            return back()->with('error', "Bảo toàn hồ sơ: Shipper \"{$shipper->ten_nhan_vien}\" đã phát sinh lịch sử giao hàng trong hệ thống, không được phép xóa! Vui lòng sử dụng tính năng \"Tạm khóa\".");
        }

        DB::transaction(function () use ($shipper, $maNguoiDung) {
            $shipper->delete();
            NguoiDung::where('ma_nguoi_dung', $maNguoiDung)->delete();
        });

        return redirect()->route('admin.shipper.index')
                         ->with('success', "Đã xóa Shipper \"{$shipper->ten_nhan_vien}\" thành công!");
    }
}
