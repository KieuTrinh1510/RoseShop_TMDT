<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChiTietNhap;
use App\Models\DanhGiaLoHang;
use App\Models\InventoryReservation;
use App\Models\NhaCungCap;
use Carbon\Carbon;
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
        ])->whereHas('phieuNhap', function ($q) {
            $q->where('trang_thai', 'CONFIRMED');
        });

        // 1. Tìm kiếm theo từ khóa (Mã lô, mã phiếu, mã SP, tên sản phẩm, tên nhà cung cấp)
        if ($request->filled('keyword')) {
            $kw = trim($request->keyword);
            $query->where(function ($q) use ($kw) {
                if (is_numeric($kw)) {
                    $q->orWhere('ma_chi_tiet_nhap', (int) $kw)
                        ->orWhere('ma_phieu_nhap', (int) $kw)
                        ->orWhere('ma_san_pham', (int) $kw);
                }
                $q->orWhereHas('sanPham', function ($sq) use ($kw) {
                    $sq->where('ten_san_pham', 'LIKE', '%' . $kw . '%');
                })->orWhereHas('phieuNhap', function ($pq) use ($kw) {
                    $pq->where('ten_nha_cung_cap', 'LIKE', '%' . $kw . '%');
                });
            });
        }

        // 2. Lọc theo Nhà cung cấp
        if ($request->filled('ma_nha_cung_cap')) {
            $query->whereHas('phieuNhap', function ($pq) use ($request) {
                $pq->where('ma_nha_cung_cap', $request->ma_nha_cung_cap);
            });
        }

        // 3. Lọc theo đánh giá chất lượng
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

        // 4. Lọc theo tình trạng tồn kho của lô
        if ($request->filled('ton_kho')) {
            if ($request->ton_kho === 'con_hang') {
                $query->where('so_luong_con_lai', '>', 0);
            } elseif ($request->ton_kho === 'het_hang') {
                $query->where('so_luong_con_lai', '<=', 0);
            }
        }

        $perPage = in_array((int) $request->get('per_page'), [10, 15, 20, 50]) ? (int) $request->get('per_page') : 10;
        $loHangs = $query->orderBy('ma_chi_tiet_nhap', 'desc')->paginate($perPage)->withQueryString();

        $nhaCungCaps = NhaCungCap::orderBy('ten_nha_cung_cap')->get(['ma_nha_cung_cap', 'ten_nha_cung_cap']);

        return view('admin.LoHang.index', compact('loHangs', 'nhaCungCaps'));
    }

    public function show($id)
    {
        $loHang = ChiTietNhap::with([
            'sanPham',
            'phieuNhap.nhaCungCap',
            'phieuNhap.nhanVien',
            'danhGiaLoHangs.nhanVien',
            'baoCaoHangHongs.nhanVien',
        ])->whereHas('phieuNhap', function ($q) {
            $q->where('trang_thai', 'CONFIRMED');
        })->findOrFail($id);

        // 1. Lịch sử xuất kho theo lô: nguồn chuẩn xác định lượng thực sự đã xuất là inventory_reservations CONSUMED
        $lichSuXuatKho = InventoryReservation::with(['hoaDon.khachHang', 'boHoa', 'sanPham'])
            ->where('ma_chi_tiet_nhap', $loHang->ma_chi_tiet_nhap)
            ->where('trang_thai', InventoryReservation::STATUS_CONSUMED)
            ->orderBy('updated_at', 'desc')
            ->get();

        $tongDaXuat = (int) $lichSuXuatKho->sum('so_luong');

        // 2. Tồn đang giữ trên lô (RESERVED)
        $danhSachDangGiu = InventoryReservation::with(['hoaDon.khachHang', 'boHoa'])
            ->where('ma_chi_tiet_nhap', $loHang->ma_chi_tiet_nhap)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->orderBy('created_at', 'desc')
            ->get();

        $tongDangGiu = (int) $danhSachDangGiu->sum('so_luong');
        $tonKhaDung = max(0, (int) $loHang->so_luong_con_lai - $tongDangGiu);

        // 3. Tổng hàng hỏng / hao hụt
        $tongHangHong = (int) $loHang->baoCaoHangHongs->sum('so_luong_hong');

        // 4. Lịch sử biến động lô (Timeline tổng hợp)
        $timeline = collect();

        // Mốc Nhập kho ban đầu
        $ngayNhap = $loHang->phieuNhap?->ngay_nhap ?? $loHang->created_at;
        $timeline->push([
            'loai' => 'NHAP_KHO',
            'tieu_de' => 'Nhập kho ban đầu',
            'so_luong_thay_doi' => +$loHang->so_luong,
            'thoi_gian' => $ngayNhap,
            'nguoi_thuc_hien' => $loHang->phieuNhap?->nhanVien?->ten_nhan_vien ?? 'Hệ thống',
            'chi_tiet' => 'Nhập kho theo Phiếu nhập #PN-' . $loHang->ma_phieu_nhap . ' (NCC: ' . ($loHang->phieuNhap?->ten_nha_cung_cap ?? '—') . ')',
            'badge_class' => 'bg-primary',
            'icon' => 'bi-box-arrow-in-down',
        ]);

        // Mốc Các lần xuất kho bán hàng (CONSUMED)
        foreach ($lichSuXuatKho as $xuat) {
            $loaiSp = $xuat->boHoa ? "Bó hoa: {$xuat->boHoa->ten_bo_hoa}" : "Sản phẩm đơn lẻ";
            $khach = $xuat->hoaDon?->khachHang?->ho_ten ?? ($xuat->hoaDon?->so_dien_thoai ? "SĐT: {$xuat->hoaDon->so_dien_thoai}" : 'Khách mua');
            $timeline->push([
                'loai' => 'XUAT_KHO',
                'tieu_de' => 'Xuất kho bán hàng',
                'so_luong_thay_doi' => -$xuat->so_luong,
                'thoi_gian' => $xuat->updated_at ?? $xuat->created_at,
                'nguoi_thuc_hien' => $khach,
                'chi_tiet' => "Xuất cho đơn hàng #{$xuat->ma_hoa_don} ({$loaiSp})",
                'badge_class' => 'bg-success',
                'icon' => 'bi-cart-check',
            ]);
        }

        // Mốc Các lần hàng hỏng / hao hụt
        foreach ($loHang->baoCaoHangHongs as $hong) {
            $timeline->push([
                'loai' => 'HANG_HONG',
                'tieu_de' => 'Hàng hỏng / hao hụt',
                'so_luong_thay_doi' => -$hong->so_luong_hong,
                'thoi_gian' => $hong->thoi_gian_bao_cao ?? $hong->created_at,
                'nguoi_thuc_hien' => $hong->nhanVien?->ten_nhan_vien ?? 'Nhân viên kho',
                'chi_tiet' => 'Báo hỏng: ' . ($hong->ly_do ?: 'Không ghi nhận') . ($hong->ghi_chu ? " ({$hong->ghi_chu})" : ''),
                'badge_class' => 'bg-danger',
                'icon' => 'bi-trash3',
            ]);
        }

        // Sắp xếp timeline theo thứ tự thời gian mới nhất lên trước
        $timeline = $timeline->sortByDesc(function ($item) {
            return $item['thoi_gian'] ? Carbon::parse($item['thoi_gian'])->timestamp : 0;
        })->values();

        return view('admin.LoHang.show', compact(
            'loHang',
            'lichSuXuatKho',
            'tongDaXuat',
            'danhSachDangGiu',
            'tongDangGiu',
            'tonKhaDung',
            'tongHangHong',
            'timeline'
        ));
    }

    public function danhGia(Request $request, $id)
    {
        $chiTietNhap = ChiTietNhap::with('phieuNhap')
            ->whereHas('phieuNhap', function ($q) {
                $q->where('trang_thai', 'CONFIRMED');
            })->findOrFail($id);

        // Guard Backend: Chặn đánh giá QC mới khi lô đã hết hàng
        if ($chiTietNhap->so_luong_con_lai <= 0) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Không thể đánh giá chất lượng cho lô hàng đã hết hàng!'], 422);
            }
            return redirect()->back()
                ->withErrors(['lo_hang' => 'Không thể đánh giá chất lượng cho lô hàng đã hết hàng!'])
                ->withInput();
        }

        $request->validate([
            'ket_qua' => ['required', 'in:DAT,CO_VAN_DE,KHONG_DAT'],
            'so_luong_loi' => ['nullable', 'integer', 'min:0', 'max:' . $chiTietNhap->so_luong],
            'ly_do' => ['nullable', 'string', 'max:255', 'required_if:ket_qua,CO_VAN_DE,KHONG_DAT'],
            'ghi_chu' => ['nullable', 'string', 'max:1000'],
        ], [
            'ket_qua.required' => 'Vui lòng chọn kết quả đánh giá.',
            'ket_qua.in' => 'Kết quả đánh giá không hợp lệ.',
            'so_luong_loi.integer' => 'Số lượng lỗi phải là số nguyên.',
            'so_luong_loi.min' => 'Số lượng lỗi không được âm.',
            'so_luong_loi.max' => 'Số lượng lỗi không được vượt quá số lượng ban đầu của lô (' . $chiTietNhap->so_luong . ').',
            'ly_do.required_if' => 'Vui lòng nhập lý do khi lô Có vấn đề hoặc Không đạt.',
            'ly_do.max' => 'Lý do không được vượt quá 255 ký tự.',
            'ghi_chu.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        $user = Auth::user();
        $maNhanVien = $user?->nhanVien?->ma_nhan_vien;

        DanhGiaLoHang::updateOrCreate(
            ['ma_chi_tiet_nhap' => $chiTietNhap->ma_chi_tiet_nhap],
            [
                'ma_nhan_vien' => $maNhanVien,
                'ket_qua' => $request->ket_qua,
                'so_luong_loi' => (int) ($request->so_luong_loi ?? 0),
                'ly_do' => $request->ly_do,
                'ghi_chu' => $request->ghi_chu,
                'thoi_gian_danh_gia' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Đã lưu kết quả đánh giá chất lượng cho lô #' . $chiTietNhap->ma_chi_tiet_nhap . '!');
    }
}
