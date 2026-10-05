<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PhieuNhap;
use App\Models\ChiTietNhap;
use App\Models\NhanVien;
use App\Models\SanPham;
use App\Models\NhaCungCap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PhieuNhapController extends Controller
{
    public function index(Request $request)
    {
        $query = PhieuNhap::with(['nhanVien', 'nhaCungCap', 'chiTietNhaps.sanPham']);

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('tu_ngay')) {
            $query->whereDate('ngay_nhap', '>=', $request->tu_ngay);
        }
        if ($request->filled('den_ngay')) {
            $query->whereDate('ngay_nhap', '<=', $request->den_ngay);
        }

        if ($request->filled('tim_ncc')) {
            $tim = trim($request->tim_ncc);
            $query->where(function ($q) use ($tim) {
                $q->where('ten_nha_cung_cap', 'LIKE', '%' . $tim . '%')
                  ->orWhereHas('nhaCungCap', function ($sub) use ($tim) {
                      $sub->where('ten_nha_cung_cap', 'LIKE', '%' . $tim . '%')
                          ->orWhere('so_dien_thoai', 'LIKE', '%' . $tim . '%')
                          ->orWhere('email', 'LIKE', '%' . $tim . '%');
                  });
            });
        }

        $danhSachPhieuNhap = $query->orderBy('ma_phieu_nhap', 'desc')->get();

        $danhSachNhanVien = NhanVien::where('chuc_vu', 'VAN_HANH')->get();

        $danhSachNhaCungCap = NhaCungCap::where('trang_thai', NhaCungCap::TRANG_THAI_DANG_HOP_TAC)
            ->orderBy('ten_nha_cung_cap')
            ->get();

        $tonKhoTheoLo = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.trang_thai', 'CONFIRMED')
            ->groupBy('ctn.ma_san_pham')
            ->select('ctn.ma_san_pham', DB::raw('SUM(ctn.so_luong_con_lai) as tong_con_lai'))
            ->pluck('tong_con_lai', 'ma_san_pham');

        $danhSachSanPham = SanPham::orderBy('ten_san_pham')
                                  ->get()
                                  ->map(function ($sp) use ($tonKhoTheoLo) {
                                      $sp->ton_kho_lo = (int) ($tonKhoTheoLo[$sp->ma_san_pham] ?? 0);
                                      return $sp;
                                  });

        $selectedNccId = null;
        if ($request->filled('ma_nha_cung_cap')) {
            $nccTarget = NhaCungCap::find($request->ma_nha_cung_cap);
            if ($nccTarget && $nccTarget->isDangHopTac()) {
                $selectedNccId = $nccTarget->ma_nha_cung_cap;
            }
        }

        return view('admin.PhieuNhap', compact(
            'danhSachPhieuNhap',
            'danhSachNhanVien',
            'danhSachSanPham',
            'danhSachNhaCungCap',
            'selectedNccId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ngay_nhap'         => 'required|date',
            'ma_nhan_vien'      => 'required|exists:nhan_vien,ma_nhan_vien',
            'ma_nha_cung_cap'   => 'required|exists:nha_cung_cap,ma_nha_cung_cap',

            'san_pham'          => 'required|array|min:1',
            'san_pham.*'        => 'required|exists:san_pham,ma_san_pham|distinct',
            'so_luong'          => 'required|array|min:1',
            'so_luong.*'        => 'required|integer|min:1',
            'gia_nhap'          => 'required|array|min:1',
            'gia_nhap.*'        => 'required|numeric|min:0',
            'gia_ban'           => 'nullable|array',
            'gia_ban.*'         => 'nullable|numeric|min:0',
        ], [
            'ngay_nhap.required'        => 'Vui lòng chọn ngày nhập.',
            'ma_nhan_vien.required'     => 'Vui lòng chọn nhân viên.',
            'ma_nha_cung_cap.required'  => 'Vui lòng chọn nhà cung cấp.',
            'ma_nha_cung_cap.exists'    => 'Nhà cung cấp không hợp lệ.',
            'san_pham.required'         => 'Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập.',
            'san_pham.*.distinct'       => 'Không được chọn cùng 1 sản phẩm 2 lần trong cùng phiếu.',
            'san_pham.*.exists'         => 'Sản phẩm không hợp lệ.',
            'so_luong.*.min'            => 'Số lượng phải lớn hơn 0.',
            'so_luong.*.integer'        => 'Số lượng phải là số nguyên.',
            'gia_nhap.*.min'            => 'Giá nhập không được âm.',
            'gia_nhap.*.numeric'        => 'Giá nhập phải là số.',
            'gia_ban.*.numeric'         => 'Giá bán phải là số.',
            'gia_ban.*.min'             => 'Giá bán không được âm.',
        ]);

        $ncc = NhaCungCap::findOrFail($request->ma_nha_cung_cap);

        // Backend reject nếu NCC đã ngừng hợp tác
        if ($ncc->trang_thai !== NhaCungCap::TRANG_THAI_DANG_HOP_TAC) {
            return redirect()->back()
                ->withErrors(['ma_nha_cung_cap' => 'Nhà cung cấp này đã ngừng hợp tác, không thể tạo phiếu nhập mới.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $ncc) {
            $phieuNhap = PhieuNhap::create([
                'ngay_nhap'         => $request->ngay_nhap,
                'ma_nhan_vien'      => $request->ma_nhan_vien,
                'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
                'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
                'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
                'email_ncc'         => $ncc->email,
                'dia_chi_ncc'       => $ncc->dia_chi,
                'trang_thai'        => 'DRAFT',
            ]);

            foreach ($request->san_pham as $index => $maSanPham) {
                $giaNhap = $request->gia_nhap[$index];

                $giaBanNhap = $request->gia_ban[$index] ?? null;
                $giaBan = ($giaBanNhap !== null && $giaBanNhap !== '')
                    ? (float) $giaBanNhap
                    : (float) $giaNhap * 1.5;

                ChiTietNhap::create([
                    'ma_phieu_nhap'    => $phieuNhap->ma_phieu_nhap,
                    'ma_san_pham'      => $maSanPham,
                    'so_luong'         => $request->so_luong[$index],
                    'gia_nhap'         => $giaNhap,
                    'gia_ban'          => $giaBan,
                    'so_luong_con_lai' => $request->so_luong[$index],
                ]);
            }
        });

        return redirect()->route('phieu-nhap.index')
                         ->with('success', 'Tạo phiếu nhập thành công! Trạng thái: Đang tạo (Draft).');
    }

    public function editData($id)
    {
        $phieuNhap = PhieuNhap::with(['chiTietNhaps.sanPham', 'nhaCungCap', 'nguoiHuy.nhanVien'])->findOrFail($id);

        return response()->json($phieuNhap);
    }

    public function update(Request $request, $id)
    {
        $phieuNhap = PhieuNhap::findOrFail($id);

        if ($phieuNhap->trang_thai === 'CANCELLED') {
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Không thể chỉnh sửa phiếu nhập đã hủy!');
        }

        if ($phieuNhap->trang_thai !== 'DRAFT') {
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Không thể sửa phiếu nhập đã xác nhận!');
        }

        $request->validate([
            'ngay_nhap'         => 'required|date',
            'ma_nhan_vien'      => 'required|exists:nhan_vien,ma_nhan_vien',
            'ma_nha_cung_cap'   => 'required|exists:nha_cung_cap,ma_nha_cung_cap',
            'san_pham'          => 'required|array|min:1',
            'san_pham.*'        => 'required|exists:san_pham,ma_san_pham|distinct',
            'so_luong'          => 'required|array|min:1',
            'so_luong.*'        => 'required|integer|min:1',
            'gia_nhap'          => 'required|array|min:1',
            'gia_nhap.*'        => 'required|numeric|min:0',
            'gia_ban'           => 'nullable|array',
            'gia_ban.*'         => 'nullable|numeric|min:0',
        ], [
            'ma_nha_cung_cap.required' => 'Vui lòng chọn nhà cung cấp.',
            'ma_nha_cung_cap.exists'   => 'Nhà cung cấp không tồn tại.',
            'san_pham.required'        => 'Vui lòng thêm ít nhất 1 sản phẩm.',
            'san_pham.*.distinct'      => 'Không được chọn cùng 1 sản phẩm 2 lần.',
            'so_luong.*.min'           => 'Số lượng phải lớn hơn 0.',
            'gia_ban.*.numeric'        => 'Giá bán phải là số.',
            'gia_ban.*.min'            => 'Giá bán không được âm.',
        ]);

        $ncc = NhaCungCap::findOrFail($request->ma_nha_cung_cap);

        // Nếu thay đổi sang NCC khác thì NCC mới phải Đang hợp tác
        if ($phieuNhap->ma_nha_cung_cap != $ncc->ma_nha_cung_cap && $ncc->trang_thai !== NhaCungCap::TRANG_THAI_DANG_HOP_TAC) {
            return redirect()->back()
                ->withErrors(['ma_nha_cung_cap' => 'Nhà cung cấp được chọn đã ngừng hợp tác.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $phieuNhap, $ncc) {
            $phieuNhap->update([
                'ngay_nhap'         => $request->ngay_nhap,
                'ma_nhan_vien'      => $request->ma_nhan_vien,
                'ma_nha_cung_cap'   => $ncc->ma_nha_cung_cap,
                'ten_nha_cung_cap'  => $ncc->ten_nha_cung_cap,
                'so_dien_thoai_ncc' => $ncc->so_dien_thoai,
                'email_ncc'         => $ncc->email,
                'dia_chi_ncc'       => $ncc->dia_chi,
            ]);

            $phieuNhap->chiTietNhaps()->delete();

            foreach ($request->san_pham as $index => $maSanPham) {
                $giaNhap = $request->gia_nhap[$index];

                $giaBanNhap = $request->gia_ban[$index] ?? null;
                $giaBan = ($giaBanNhap !== null && $giaBanNhap !== '')
                    ? (float) $giaBanNhap
                    : (float) $giaNhap * 1.5;

                ChiTietNhap::create([
                    'ma_phieu_nhap'    => $phieuNhap->ma_phieu_nhap,
                    'ma_san_pham'      => $maSanPham,
                    'so_luong'         => $request->so_luong[$index],
                    'gia_nhap'         => $giaNhap,
                    'gia_ban'          => $giaBan,
                    'so_luong_con_lai' => $request->so_luong[$index],
                ]);
            }
        });

        return redirect()->route('phieu-nhap.index')
                         ->with('success', 'Cập nhật phiếu nhập thành công!');
    }

    
    public function destroy($id)
    {
        $phieuNhap = PhieuNhap::findOrFail($id);

        if ($phieuNhap->trang_thai === 'CANCELLED') {
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Không thể xóa phiếu nhập đã hủy! Phiếu đã hủy được lưu trữ để tra cứu lịch sử.');
        }

        if ($phieuNhap->trang_thai !== 'DRAFT') {
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Không thể xóa phiếu nhập đã xác nhận!');
        }

        $phieuNhap->delete();

        return redirect()->route('phieu-nhap.index')
                         ->with('success', 'Đã xóa phiếu nhập!');
    }

    public function confirm($id)
    {
        $phieuNhap = PhieuNhap::with('chiTietNhaps')->findOrFail($id);

        if ($phieuNhap->trang_thai === 'CANCELLED') {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác nhận phiếu nhập đã hủy!'
                ], 422);
            }
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Không thể xác nhận phiếu nhập đã hủy!');
        }

        if ($phieuNhap->trang_thai !== 'DRAFT') {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiếu nhập này đã được xác nhận rồi!'
                ], 422);
            }
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Phiếu nhập này đã được xác nhận rồi!');
        }

        DB::transaction(function () use ($phieuNhap) {

            foreach ($phieuNhap->chiTietNhaps as $chiTiet) {
                $sanPham = SanPham::findOrFail($chiTiet->ma_san_pham);
                $sanPham->so_luong += $chiTiet->so_luong;
                $sanPham->save();
            }

            $phieuNhap->trang_thai = PhieuNhap::TRANG_THAI_CONFIRMED;
            $phieuNhap->save();
        });

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Xác nhận thành công! Số lượng kho đã được cập nhật.'
            ]);
        }

        return redirect()->route('phieu-nhap.index')
                         ->with('success', 'Xác nhận thành công! Số lượng kho đã được cập nhật.');
    }

    public function cancel(Request $request, $id)
    {
        $phieuNhap = PhieuNhap::findOrFail($id);

        if ($phieuNhap->trang_thai === 'CONFIRMED') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể hủy phiếu nhập đã xác nhận!'
                ], 422);
            }
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Không thể hủy phiếu nhập đã xác nhận!');
        }

        if ($phieuNhap->trang_thai === 'CANCELLED') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiếu nhập này đã được hủy trước đó, không thể hủy lại!'
                ], 422);
            }
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Phiếu nhập này đã được hủy trước đó!');
        }

        if ($phieuNhap->trang_thai !== 'DRAFT') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể hủy phiếu nhập ở trạng thái Đang tạo (Draft)!'
                ], 422);
            }
            return redirect()->route('phieu-nhap.index')
                             ->with('error', 'Chỉ có thể hủy phiếu nhập ở trạng thái Đang tạo (Draft)!');
        }

        $request->validate([
            'ly_do_huy' => 'required|string',
        ], [
            'ly_do_huy.required' => 'Vui lòng nhập lý do hủy phiếu nhập.',
        ]);

        $lyDoHuy = trim($request->ly_do_huy);
        if ($lyDoHuy === '') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lý do hủy không được để trống.',
                ], 422);
            }
            return redirect()->back()
                             ->withErrors(['ly_do_huy' => 'Lý do hủy không được để trống.'])
                             ->withInput();
        }

        DB::transaction(function () use ($phieuNhap, $lyDoHuy) {
            $phieuNhap->trang_thai   = PhieuNhap::TRANG_THAI_CANCELLED;
            $phieuNhap->ly_do_huy    = $lyDoHuy;
            $phieuNhap->nguoi_huy_id = Auth::id();
            $phieuNhap->huy_luc      = now();
            $phieuNhap->save();
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => 'Hủy phiếu nhập thành công! Trạng thái: Đã hủy (Cancelled).',
                'trang_thai' => 'CANCELLED',
                'ly_do_huy'  => $lyDoHuy,
            ]);
        }

        return redirect()->route('phieu-nhap.index')
                         ->with('success', 'Hủy phiếu nhập thành công! Trạng thái: Đã hủy (Cancelled).');
    }

    public function show($id)
    {
        $phieuNhap = PhieuNhap::with([
            'nhanVien',
            'nguoiHuy.nhanVien',
            'nhaCungCap',
            'chiTietNhaps.sanPham',
            'chiTietNhaps.danhGiaMoiNhat',
            'chiTietNhaps.baoCaoHangHongs',
        ])->findOrFail($id);

        return view('admin.PhieuNhap.show', compact('phieuNhap'));
    }
}