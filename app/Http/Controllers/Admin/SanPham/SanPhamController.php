<?php

namespace App\Http\Controllers\Admin\SanPham;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use App\Models\ChiTietNhap;
use App\Models\BaoCaoHangHong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SanPhamController extends Controller
{
    /**
     * Map đầy đủ — BẮT BUỘC giữ vì form NVL cũng post về route san-pham.store,
     * validate `in:` cần full danh sách. Không dùng để render dropdown trang SP nữa.
     */
    private $danhSachLoai = [
        'HOA_TUOI'         => 'Hoa Tươi',
        'HOA_GIA'          => 'Hoa Giả',
        'SAN_PHAM_PREMIUM' => 'Sản Phẩm Premium',
        'CHAU_HOA_GIA'     => 'Chậu Hoa Giả',
        'CHAU_HOA_TUOI'    => 'Chậu Hoa Tươi',
        'CAY_CANH'         => 'Cây Cảnh',
        'HOA_SAP'          => 'Hoa Sáp',
        'HOA_GIAY_NHUN'    => 'Hoa Giấy Nhún',
        'TERRARIUM'        => 'Terrarium',
        'PHU_KIEN'         => 'Phụ Kiện',
        'QUA_TANG'         => 'Quà Tặng',
        'GIAY_GOI' => 'Giấy gói', 'RUY_BANG' => 'Ruy băng',
        'PHU_KIEN_TRANG_TRI' => 'Phụ kiện trang trí', 'THIEP' => 'Thiệp',
        'MIENG_XOP' => 'Mút xốp',
    ];

    private $maLoaiNguyenLieu = [
        'GIAY_GOI', 'RUY_BANG', 'PHU_KIEN_TRANG_TRI', 'THIEP', 'MIENG_XOP',
    ];

    private array $donViTheoLoai = [
        'HOA_TUOI'           => 'CANH',
        'HOA_GIA'            => 'CANH',
        'HOA_SAP'            => 'CANH',
        'HOA_GIAY_NHUN'      => 'CANH',
        'CHAU_HOA_GIA'       => 'CHAU',
        'CHAU_HOA_TUOI'      => 'CHAU',
        'CAY_CANH'           => 'CHAU',
        'SAN_PHAM_PREMIUM'   => 'CAI',
        'TERRARIUM'          => 'CAI',
        'QUA_TANG'           => 'CAI',
        'PHU_KIEN'           => 'CAI',
        'GIAY_GOI'           => 'TO',
        'RUY_BANG'           => 'MET',
        'PHU_KIEN_TRANG_TRI' => 'CAI',
        'THIEP'              => 'CAI',
        'MIENG_XOP'          => 'MIENG',
    ];

    public function index(Request $request)
    {
        $query = SanPham::query()->whereNotIn('loai_san_pham', $this->maLoaiNguyenLieu);

        if ($request->filled('loai_san_pham')) {
            $query->where('loai_san_pham', $request->loai_san_pham);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 10;
        }

        $danhSachSanPham = $query
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                  ->orderBy('gia_ban', 'asc');
            }])
            ->orderBy('ma_san_pham', 'desc')
            ->paginate($perPage)->withQueryString();

        $danhSachLoai      = $this->danhSachLoai;
        $danhSachLoaiBanRa = collect($this->danhSachLoai)->except($this->maLoaiNguyenLieu);

        // KHÔNG query NVL, KHÔNG gọi getDuLieuBoHoa() — 2 trang kia tự lo.
        return view('admin.SanPham.SanPham', compact(
            'danhSachSanPham', 'danhSachLoai', 'danhSachLoaiBanRa'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_san_pham'     => 'required|string|max:100',
            'loai_san_pham'    => 'required|in:' . implode(',', array_keys($this->danhSachLoai)),
            'gia_ban_hien_tai' => 'nullable|numeric|min:0',
            'mo_ta'            => 'nullable|string',
            'hinh_anh'         => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ], [
            'ten_san_pham.required'  => 'Vui lòng nhập tên sản phẩm.',
            'loai_san_pham.required' => 'Vui lòng chọn loại sản phẩm.',
            'loai_san_pham.in'       => 'Loại sản phẩm không hợp lệ.',
            'hinh_anh.image'         => 'File tải lên phải là hình ảnh.',
            'hinh_anh.mimes'         => 'Chỉ chấp nhận JPG, PNG, GIF, WEBP.',
            'hinh_anh.max'           => 'Ảnh không được vượt quá 2MB.',
        ]);

        $donViTinh = $this->donViTheoLoai[$request->loai_san_pham] ?? 'CAI';

        $duongDanAnh = null;
        if ($request->hasFile('hinh_anh')) {
            $file    = $request->file('hinh_anh');
            $tenFile = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('img'), $tenFile);
            $duongDanAnh = 'img/' . $tenFile;
        }

        SanPham::create([
            'ten_san_pham'      => $request->ten_san_pham,
            'so_luong'          => 0,
            'don_vi_tinh'       => $donViTinh,
            'loai_san_pham'     => $request->loai_san_pham,
            'gia_ban_hien_tai'  => $request->gia_ban_hien_tai,
            'mo_ta'             => $request->mo_ta,
            'hinh_anh'          => $duongDanAnh,
            'trang_thai'        => 'NGUNG_BAN',
        ]);

        // Redirect về đúng trang theo loại vừa tạo.
        $isNguyenLieu = in_array($request->loai_san_pham, $this->maLoaiNguyenLieu, true);
        $route = $isNguyenLieu
            ? route('san-pham.nguyen-vat-lieu.index')
            : route('san-pham.index');

        return redirect($route)->with('success',
            'Thêm ' . ($isNguyenLieu ? 'nguyên vật liệu' : 'sản phẩm')
            . ' thành công! Trạng thái mặc định là Ngừng bán. Hãy nhập hàng rồi bật lại để bán.');
    }

    public function editData($id)
    {
        $sanPham = SanPham::with(['chiTietNhaps' => function ($q) {
            $q->where('so_luong_con_lai', '>', 0)
              ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'));
        }])->findOrFail($id);

        $sanPham->ton_kho_thuc_te = $sanPham->chiTietNhaps->sum('so_luong_con_lai');

        return response()->json($sanPham);
    }

    public function update(Request $request, $id)
    {
        $sanPham = SanPham::findOrFail($id);

        $request->validate([
            'ten_san_pham' => 'required|string|max:100',
            'mo_ta'        => 'nullable|string',
            'hinh_anh'     => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ], [
            'ten_san_pham.required' => 'Vui lòng nhập tên sản phẩm.',
            'hinh_anh.image'        => 'File tải lên phải là hình ảnh.',
            'hinh_anh.mimes'        => 'Chỉ chấp nhận JPG, PNG, GIF, WEBP.',
            'hinh_anh.max'          => 'Ảnh không được vượt quá 2MB.',
        ]);

        $duongDanAnh = $sanPham->hinh_anh;
        if ($request->hasFile('hinh_anh')) {
            if ($sanPham->hinh_anh && file_exists(public_path($sanPham->hinh_anh))) {
                unlink(public_path($sanPham->hinh_anh));
            }
            $file    = $request->file('hinh_anh');
            $tenFile = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('img'), $tenFile);
            $duongDanAnh = 'img/' . $tenFile;
        }

        $donViTinh = $this->donViTheoLoai[$sanPham->loai_san_pham] ?? $sanPham->don_vi_tinh;

        $sanPham->update([
            'ten_san_pham'     => $request->ten_san_pham,
            'don_vi_tinh'      => $donViTinh,
            'gia_ban_hien_tai' => $request->gia_ban_hien_tai,
            'mo_ta'            => $request->mo_ta,
            'hinh_anh'         => $duongDanAnh,
        ]);

        $isNguyenLieu = in_array($sanPham->loai_san_pham, $this->maLoaiNguyenLieu, true);
        $route = $isNguyenLieu
            ? route('san-pham.nguyen-vat-lieu.index')
            : route('san-pham.index');

        return redirect($route)->with('success',
            'Cập nhật "' . $sanPham->ten_san_pham . '" thành công!');
    }

    public function toggleTrangThai($id)
    {
        $sanPham = SanPham::findOrFail($id);

        $isNguyenLieu = in_array($sanPham->loai_san_pham, $this->maLoaiNguyenLieu, true);
        $loaiLabel    = $isNguyenLieu ? 'nguyên vật liệu' : 'sản phẩm';

        if ($sanPham->trang_thai === 'DANG_BAN') {
            $sanPham->trang_thai = 'NGUNG_BAN';
            $thongBao = "Đã ẩn {$loaiLabel} \"{$sanPham->ten_san_pham}\"!";
            $thongBao .= $isNguyenLieu
                ? ' Nguyên vật liệu sẽ không được chọn khi tạo bó hoa.'
                : ' Khách hàng sẽ không thể mua.';
        } else {
            $sanPham->trang_thai = 'DANG_BAN';
            $thongBao = "Đã hiện {$loaiLabel} \"{$sanPham->ten_san_pham}\"!";
            $thongBao .= $isNguyenLieu
                ? ' Nguyên vật liệu đã sẵn sàng để chọn khi tạo bó hoa.'
                : ' Khách hàng có thể mua.';
        }

        $sanPham->save();

        $route = $isNguyenLieu
            ? route('san-pham.nguyen-vat-lieu.index')
            : route('san-pham.index');

        return redirect($route)->with('success', $thongBao);
    }

    public function loNhap($id)
    {
        SanPham::findOrFail($id);

        $loNhaps = ChiTietNhap::with('phieuNhap')
            ->where('ma_san_pham', $id)
            ->where('so_luong_con_lai', '>', 0)
            ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
            ->orderByDesc('ma_chi_tiet_nhap')
            ->get()
            ->map(function ($ct) {
                $ngay = optional($ct->phieuNhap?->ngay_nhap)->format('d/m/Y') ?? '?';
                $maPN = $ct->ma_phieu_nhap;
                return [
                    'ma_chi_tiet_nhap' => $ct->ma_chi_tiet_nhap,
                    'so_luong_con_lai' => $ct->so_luong_con_lai,
                    'label' => "Phiếu #{$maPN} — {$ngay} — Còn {$ct->so_luong_con_lai} sp",
                ];
            });

        return response()->json(['lo_nhaps' => $loNhaps]);
    }

    public function baoHangHong(Request $request)
    {
        $request->validate([
            'ma_san_pham'      => 'required|integer|exists:san_pham,ma_san_pham',
            'ma_chi_tiet_nhap' => 'required|integer|exists:chi_tiet_nhap,ma_chi_tiet_nhap',
            'so_luong_hong'    => 'required|integer|min:1',
            'ly_do'            => 'required|string|max:255',
            'ghi_chu'          => 'nullable|string|max:1000',
        ], [
            'ma_san_pham.required'      => 'Thiếu thông tin sản phẩm.',
            'ma_san_pham.exists'        => 'Sản phẩm không tồn tại.',
            'ma_chi_tiet_nhap.required' => 'Vui lòng chọn lô nhập bị hỏng.',
            'ma_chi_tiet_nhap.exists'   => 'Lô nhập không tồn tại.',
            'so_luong_hong.required'    => 'Vui lòng nhập số lượng hỏng.',
            'so_luong_hong.integer'     => 'Số lượng hỏng phải là số nguyên.',
            'so_luong_hong.min'         => 'Số lượng hỏng phải từ 1 trở lên.',
            'ly_do.required'            => 'Vui lòng nhập lý do hỏng.',
            'ly_do.max'                 => 'Lý do hỏng không vượt quá 255 ký tự.',
            'ghi_chu.max'               => 'Ghi chú không vượt quá 1000 ký tự.',
        ]);

        $chiTietNhap = ChiTietNhap::with('phieuNhap')
            ->where('ma_chi_tiet_nhap', $request->ma_chi_tiet_nhap)
            ->where('ma_san_pham', $request->ma_san_pham)
            ->whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
            ->firstOrFail();

        if ($request->so_luong_hong > $chiTietNhap->so_luong_con_lai) {
            return redirect()->back()
                ->withErrors([
                    'so_luong_hong' => "Số lượng hỏng ({$request->so_luong_hong}) vượt quá tồn kho của lô này ({$chiTietNhap->so_luong_con_lai} sp).",
                ])
                ->withInput();
        }

        $sanPham = SanPham::findOrFail($request->ma_san_pham);

        DB::transaction(function () use ($request, $chiTietNhap, $sanPham) {
            $chiTietNhap->decrement('so_luong_con_lai', $request->so_luong_hong);
            $sanPham->decrement('so_luong', $request->so_luong_hong);

            if ($sanPham->fresh()->so_luong <= 0) {
                $sanPham->update(['trang_thai' => 'NGUNG_BAN']);
            }

            BaoCaoHangHong::create([
                'ma_san_pham'       => $request->ma_san_pham,
                'ma_chi_tiet_nhap'  => $chiTietNhap->ma_chi_tiet_nhap,
                'ma_nhan_vien'      => Auth::user()?->nhanVien?->ma_nhan_vien
                                       ?? $request->ma_nhan_vien
                                       ?? 7,
                'so_luong_hong'     => $request->so_luong_hong,
                'ly_do'             => $request->ly_do,
                'ghi_chu'           => $request->ghi_chu,
                'thoi_gian_bao_cao' => now(),
            ]);
        });

        $slHong   = $request->so_luong_hong;
        $tenSp    = $sanPham->ten_san_pham;
        $slConLai = $chiTietNhap->fresh()->so_luong_con_lai;
        $maPN     = $chiTietNhap->ma_phieu_nhap;

        if ($request->filled('redirect_to')) {
            return redirect($request->redirect_to)
                ->with('success', "Đã báo cáo {$slHong} sp hỏng của \"{$tenSp}\" tại lô Phiếu #{$maPN}. Tồn kho lô còn lại: {$slConLai} sp.");
        }

        if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), 'lo-hang')) {
            return redirect()->back()
                ->with('success', "Đã báo cáo {$slHong} sp hỏng của \"{$tenSp}\" tại lô Phiếu #{$maPN}. Tồn kho lô còn lại: {$slConLai} sp.");
        }

        $isNguyenLieu = in_array($sanPham->loai_san_pham, $this->maLoaiNguyenLieu, true);
        $route = $isNguyenLieu
            ? route('san-pham.nguyen-vat-lieu.index')
            : route('san-pham.index');

        return redirect($route)
            ->with('success', "Đã báo cáo {$slHong} sp hỏng của \"{$tenSp}\" tại lô Phiếu #{$maPN}. Tồn kho lô còn lại: {$slConLai} sp.");
    }
}