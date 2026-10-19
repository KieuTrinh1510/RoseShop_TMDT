<?php

namespace App\Http\Controllers\Admin\HoanHang;

use App\Http\Controllers\Controller;
use App\Models\ChiTietHangHoan;
use App\Models\GiaoHangThatBai;
use App\Models\HangHoan;
use App\Models\PhatShipper;
use App\Models\ViPhamKhachHang;
use Illuminate\Http\Request;

class GiaoHangThatBaiController extends Controller
{
    private const TRANG_THAI_HANG_HOAN = [
        'CHO_MANG_VE_KHO', 'DA_NHAN_TAI_KHO', 'DANG_KIEM_TRA', 'DA_KIEM_TRA',
    ];

    // ─────────────────────────────────────────────────────────────
    // Danh sách giao hàng thất bại (chỉ đọc)
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $request->validate([
            'loai_loi'            => ['nullable', 'in:CUSTOMER,SHIPPER'],
            'trang_thai_hang_hoan' => ['nullable', 'in:' . implode(',', self::TRANG_THAI_HANG_HOAN)],
            'ma_hoa_don'          => ['nullable', 'integer', 'min:1'],
            'tu_ngay'             => ['nullable', 'date'],
            'den_ngay'            => ['nullable', 'date', 'after_or_equal:tu_ngay'],
        ]);

        $query = GiaoHangThatBai::with(['hoaDon.khachHang', 'shipper', 'hangHoan.chiTiet']);

        if ($request->filled('loai_loi')) {
            $query->where('loai_loi', $request->input('loai_loi'));
        }
        if ($request->filled('trang_thai_hang_hoan')) {
            $query->whereHas('hangHoan', fn ($q) => $q->where('trang_thai', $request->input('trang_thai_hang_hoan')));
        }
        if ($request->filled('ma_hoa_don')) {
            $query->where('ma_hoa_don', (int) $request->input('ma_hoa_don'));
        }
        if ($request->filled('tu_ngay')) {
            $query->where('thoi_diem_bao', '>=', \Carbon\Carbon::parse($request->input('tu_ngay'))->startOfDay());
        }
        if ($request->filled('den_ngay')) {
            $query->where('thoi_diem_bao', '<=', \Carbon\Carbon::parse($request->input('den_ngay'))->endOfDay());
        }

        $danhSach = $query->orderByDesc('thoi_diem_bao')->paginate(15)->withQueryString();

        // Gắn số liệu tiền đã quyết toán (chỉ đọc, không tính lại / không tạo phạt)
        $danhSach->getCollection()->each(function (GiaoHangThatBai $g) {
            $tong = $this->tongHopTien($g->hangHoan);
            $g->setAttribute('da_quyet_toan', $tong['da_quyet_toan']);
            $g->setAttribute('tong_he_thong_chiu', $tong['tong_he_thong_chiu']);
            $g->setAttribute('tong_shipper_chiu', $tong['tong_shipper_chiu']);
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $danhSach]);
        }

        return view('admin.GiaoHangThatBai', [
            'danhSach'            => $danhSach,
            'trangThaiHangHoan'   => self::TRANG_THAI_HANG_HOAN,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Chi tiết một lần giao hàng thất bại (chỉ đọc)
    // ─────────────────────────────────────────────────────────────
    public function show(Request $request, $id)
    {
        $ghtb = GiaoHangThatBai::with([
            'hoaDon.khachHang',
            'shipper',
            'hangHoan.nguoiKiemTra',
            'hangHoan.chiTiet.sanPham',
            'hangHoan.chiTiet.boHoa',
        ])->findOrFail($id);

        $hangHoan  = $ghtb->hangHoan;
        $tong      = $this->tongHopTien($hangHoan);
        $daQuyetToan = $tong['da_quyet_toan'];

        $dong = $hangHoan
            ? $hangHoan->chiTiet->map(fn (ChiTietHangHoan $ct) => $this->moTaDong($ct, $daQuyetToan))->values()
            : collect();

        $sanPhamHong   = $dong->filter(fn ($d) => $d['so_luong_hong'] > 0)->values();
        $sanPhamConBan = $dong->filter(fn ($d) => $d['so_luong_con_ban_duoc'] > 0)->values();

        // Chỉ đọc các khoản đã được tạo ở bước quyết toán — không tạo mới tại đây
        $khoanPhat = PhatShipper::with('shipper')
            ->where('ma_giao_hang_that_bai', $ghtb->ma_giao_hang_that_bai)
            ->orderBy('ma_phat_shipper')
            ->get();

        $viPham = ViPhamKhachHang::with('khachHang')
            ->where('ma_giao_hang_that_bai', $ghtb->ma_giao_hang_that_bai)
            ->orderBy('ma_vi_pham')
            ->get();

        $data = [
            'giaoHangThatBai' => $ghtb,
            'hoaDon'          => $ghtb->hoaDon,
            'khachHang'       => $ghtb->hoaDon?->khachHang,
            'shipper'         => $ghtb->shipper,
            'hangHoan'        => $hangHoan,
            'daQuyetToan'     => $daQuyetToan,
            'dongHangHoan'    => $dong,
            'sanPhamHong'     => $sanPhamHong,
            'sanPhamConBan'   => $sanPhamConBan,
            'tongTien'        => $tong,
            'khoanPhat'       => $khoanPhat,
            'viPham'          => $viPham,
        ];

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('admin.GiaoHangThatBaiChiTiet', $data);
    }

    // ═════════════════════════════════════════════════════════════
    // Helpers (chỉ đọc)
    // ═════════════════════════════════════════════════════════════

    /** Tổng hợp tiền từ các dòng đã quyết toán. Chưa kiểm tra xong → trả null cho các khoản tiền. */
    private function tongHopTien(?HangHoan $hangHoan): array
    {
        $daQuyetToan = $hangHoan && $hangHoan->trang_thai === 'DA_KIEM_TRA';

        if (!$daQuyetToan) {
            return [
                'da_quyet_toan'            => false,
                'tong_thiet_hai_hang_hong' => null,
                'tong_hao_hut_flash_sale'  => null,
                'tong_he_thong_chiu'       => null,
                'tong_shipper_chiu'        => null,
            ];
        }

        $chiTiet = $hangHoan->relationLoaded('chiTiet')
            ? $hangHoan->chiTiet
            : $hangHoan->chiTiet()->get();

        return [
            'da_quyet_toan'            => true,
            'tong_thiet_hai_hang_hong' => round((float) $chiTiet->sum('tien_thiet_hai_hang_hong'), 2),
            'tong_hao_hut_flash_sale'  => round((float) $chiTiet->sum('tien_hao_hut_flash_sale'), 2),
            'tong_he_thong_chiu'       => round((float) $chiTiet->sum('tien_he_thong_chiu'), 2),
            'tong_shipper_chiu'        => round((float) $chiTiet->sum('tien_shipper_chiu'), 2),
        ];
    }

    private function moTaDong(ChiTietHangHoan $ct, bool $daQuyetToan): array
    {
        $laBo = !empty($ct->ma_bo_hoa);

        if ($laBo) {
            $ten = $ct->boHoa?->ten_bo_hoa ?? 'Bó hoa (đã xóa)';
            $anh = asset($ct->boHoa?->hinh_anh ?? 'img/default.jpg');
        } else {
            $ten = $ct->sanPham?->ten_san_pham ?? 'Sản phẩm (đã xóa)';
            $anh = $ct->sanPham?->anh ?? asset('img/default.jpg');
        }

        $tien = fn ($v) => $daQuyetToan ? (float) $v : null;

        return [
            'ma_chi_tiet_hang_hoan'    => $ct->ma_chi_tiet_hang_hoan,
            'la_bo_hoa'                => $laBo,
            'ten'                      => $ten,
            'anh'                      => $anh,
            'so_luong_theo_don'        => (int) $ct->so_luong_theo_don,
            'so_luong_hong'            => (int) $ct->so_luong_hong,
            'so_luong_con_ban_duoc'    => (int) $ct->so_luong_con_ban_duoc,
            'ket_qua'                  => $ct->ket_qua,
            'mien_phat_shipper'        => (bool) $ct->mien_phat_shipper,
            'tien_thiet_hai_hang_hong' => $tien($ct->tien_thiet_hai_hang_hong),
            'tien_hao_hut_flash_sale'  => $tien($ct->tien_hao_hut_flash_sale),
            'tien_he_thong_chiu'       => $tien($ct->tien_he_thong_chiu),
            'tien_shipper_chiu'        => $tien($ct->tien_shipper_chiu),
            'ghi_chu'                  => $ct->ghi_chu,
        ];
    }
}