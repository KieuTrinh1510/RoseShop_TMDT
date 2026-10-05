<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChiTietNhap;
use App\Models\DanhGiaLoHang;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NhaCungCapController extends Controller
{
    public function index(Request $request)
    {
        $query = NhaCungCap::withCount('phieuNhaps');

        if ($request->filled('keyword')) {
            $query->search($request->keyword);
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'oldest' => $query->orderBy('ma_nha_cung_cap', 'asc'),
            'name_asc' => $query->orderBy('ten_nha_cung_cap', 'asc'),
            'name_desc' => $query->orderBy('ten_nha_cung_cap', 'desc'),
            default => $query->orderBy('ma_nha_cung_cap', 'desc'),
        };

        $nhaCungCaps = $query->paginate(10)->withQueryString();

        $stats = [
            'tong_ncc'       => NhaCungCap::count(),
            'dang_hop_tac'   => NhaCungCap::where('trang_thai', NhaCungCap::TRANG_THAI_DANG_HOP_TAC)->count(),
            'ngung_hop_tac'  => NhaCungCap::where('trang_thai', NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC)->count(),
        ];

        return view('admin.NhaCungCap.index', compact('nhaCungCaps', 'stats'));
    }

    public function create()
    {
        return view('admin.NhaCungCap.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_nha_cung_cap' => [
                'required',
                'string',
                'max:100',
            ],
            'so_dien_thoai' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^0[0-9]{9}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
            ],
            'dia_chi' => [
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'ten_nha_cung_cap.required' => 'Vui lòng nhập tên nhà cung cấp.',
            'ten_nha_cung_cap.max'      => 'Tên nhà cung cấp không được vượt quá 100 ký tự.',
            'so_dien_thoai.size'       => 'Số điện thoại phải đúng 10 chữ số.',
            'so_dien_thoai.regex'      => 'Số điện thoại phải bắt đầu bằng số 0 và gồm 10 chữ số.',
            'email.email'              => 'Email không đúng định dạng.',
            'email.max'                => 'Email không được vượt quá 100 ký tự.',
            'dia_chi.max'              => 'Địa chỉ không được vượt quá 500 ký tự.',
        ]);

        $validated['trang_thai'] = NhaCungCap::TRANG_THAI_DANG_HOP_TAC;

        $ncc = NhaCungCap::create($validated);

        return redirect()->route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap)
            ->with('success', 'Thêm nhà cung cấp "' . $ncc->ten_nha_cung_cap . '" thành công!');
    }

    public function show($id)
    {
        $ncc = NhaCungCap::withCount('phieuNhaps')->findOrFail($id);

        $phieuNhaps = PhieuNhap::with(['nhanVien', 'chiTietNhaps.sanPham'])
            ->where('ma_nha_cung_cap', $id)
            ->orderBy('ma_phieu_nhap', 'desc')
            ->paginate(10, ['*'], 'pn_page')
            ->withQueryString();

        // Thống kê chất lượng
        $thongKe = $ncc->thong_ke_chat_luong;

        // Danh sách các lô đã nhập từ NCC này (chỉ từ phiếu đã CONFIRMED)
        $loHangs = $ncc->chiTietNhaps()
            ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
            ->with(['sanPham', 'phieuNhap.nhanVien', 'danhGiaMoiNhat.nhanVien', 'baoCaoHangHongs'])
            ->orderBy('chi_tiet_nhap.ma_chi_tiet_nhap', 'desc')
            ->paginate(15, ['*'], 'lo_page')
            ->withQueryString();

        // Tính tổng giá trị nhập của NCC (chỉ tính phiếu đã CONFIRMED)
        $tongGiaTriNhap = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.ma_nha_cung_cap', $id)
            ->where('pn.trang_thai', 'CONFIRMED')
            ->sum(DB::raw('ctn.so_luong * ctn.gia_nhap'));

        $tongMatHang = DB::table('chi_tiet_nhap as ctn')
            ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
            ->where('pn.ma_nha_cung_cap', $id)
            ->where('pn.trang_thai', 'CONFIRMED')
            ->count('ctn.ma_chi_tiet_nhap');

        return view('admin.NhaCungCap.show', compact('ncc', 'phieuNhaps', 'loHangs', 'thongKe', 'tongGiaTriNhap', 'tongMatHang'));
    }

    public function danhGiaLo(Request $request, $ma_chi_tiet_nhap)
    {
        $chiTietNhap = ChiTietNhap::with('phieuNhap')
            ->where('ma_chi_tiet_nhap', $ma_chi_tiet_nhap)
            ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
            ->firstOrFail();

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

    public function edit($id)
    {
        $ncc = NhaCungCap::findOrFail($id);

        return view('admin.NhaCungCap.edit', compact('ncc'));
    }

    public function update(Request $request, $id)
    {
        $ncc = NhaCungCap::findOrFail($id);

        $validated = $request->validate([
            'ten_nha_cung_cap' => [
                'required',
                'string',
                'max:100',
            ],
            'so_dien_thoai' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^0[0-9]{9}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
            ],
            'dia_chi' => [
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'ten_nha_cung_cap.required' => 'Vui lòng nhập tên nhà cung cấp.',
            'ten_nha_cung_cap.max'      => 'Tên nhà cung cấp không được vượt quá 100 ký tự.',
            'so_dien_thoai.size'       => 'Số điện thoại phải đúng 10 chữ số.',
            'so_dien_thoai.regex'      => 'Số điện thoại phải bắt đầu bằng số 0 và gồm 10 chữ số.',
            'email.email'              => 'Email không đúng định dạng.',
            'email.max'                => 'Email không được vượt quá 100 ký tự.',
            'dia_chi.max'              => 'Địa chỉ không được vượt quá 500 ký tự.',
        ]);

        $ncc->update($validated);

        return redirect()->route('admin.nha-cung-cap.show', $ncc->ma_nha_cung_cap)
            ->with('success', 'Cập nhật thông tin nhà cung cấp "' . $ncc->ten_nha_cung_cap . '" thành công!');
    }

    public function updateStatus(Request $request, $id)
    {
        $ncc = NhaCungCap::findOrFail($id);

        $newStatus = $request->input('trang_thai');

        if (!in_array($newStatus, [NhaCungCap::TRANG_THAI_DANG_HOP_TAC, NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC], true)) {
            // Nếu không truyền cụ thể, tự động đảo trạng thái
            $newStatus = ($ncc->trang_thai === NhaCungCap::TRANG_THAI_DANG_HOP_TAC)
                ? NhaCungCap::TRANG_THAI_NGUNG_HOP_TAC
                : NhaCungCap::TRANG_THAI_DANG_HOP_TAC;
        }

        // Nếu trạng thái đã trùng, không update lại
        if ($ncc->trang_thai === $newStatus) {
            $msg = ($newStatus === NhaCungCap::TRANG_THAI_DANG_HOP_TAC)
                ? 'Nhà cung cấp đã ở trạng thái Đang hợp tác.'
                : 'Nhà cung cấp đã ở trạng thái Ngừng hợp tác.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $ncc->trang_thai = $newStatus;
        $ncc->save();

        $actionText = ($newStatus === NhaCungCap::TRANG_THAI_DANG_HOP_TAC) ? 'Kích hoạt lại' : 'Ngừng hợp tác với';
        $message = "Đã {$actionText} nhà cung cấp \"{$ncc->ten_nha_cung_cap}\" thành công!";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'trang_thai' => $newStatus,
            ]);
        }

        return back()->with('success', $message);
    }
}
