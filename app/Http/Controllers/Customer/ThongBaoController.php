<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\BoHoa;
use App\Models\ThongBao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ThongBaoController extends Controller
{
    private function cacheKey(int $maKhach): string
    {
        return "thong_bao_khach_{$maKhach}";
    }

    public function index(Request $request)
    {
        $user        = Auth::user();
        $maKhachHang = (int) $user->ma_nguoi_dung;

        $activeTab = in_array($request->get('tab'), ['thong-bao', 'don-hien-tai', 'lich-su', 'bo-hoa-da-luu'])
            ? $request->get('tab')
            : 'thong-bao';

        // Map về đúng dạng mảng cũ ('id','noi_dung','loai','thoi_gian','ma_hd') để view Blade không phải sửa
$thongBaos = ThongBao::where('ma_nguoi_dung', $maKhachHang)
    ->orderByDesc('thoi_gian')
    ->orderByDesc('ma_thong_bao')
    ->limit(20)
    ->get()
    ->map(fn ($tb) => [
        'id'        => $tb->ma_thong_bao,
        'ma_hd'     => $tb->ma_hoa_don,
        'loai'      => $tb->loai,
        'noi_dung'  => $tb->noi_dung,
        'thoi_gian' => $tb->thoi_gian->format('H:i, d/m/Y'),
        'da_doc'    => $tb->da_doc,
    ])
    ->all();

$soThongBao = ThongBao::where('ma_nguoi_dung', $maKhachHang)->where('da_doc', false)->count();

// Khách mở tab thông báo thì coi như đã đọc (số $soThongBao ở trên vẫn là số trước khi đọc)
if ($activeTab === 'thong-bao' && $soThongBao > 0) {
    ThongBao::where('ma_nguoi_dung', $maKhachHang)->where('da_doc', false)->update(['da_doc' => true]);
}

        $donHienTai = HoaDon::with(['chiTietHoaDon.sanPham', 'shipper'])
            ->where('ma_khach_hang', $maKhachHang)
            ->whereIn('trang_thai', ['PENDING', 'CONFIRMED', 'SHIPPING'])
            ->orderByDesc('ngay_dat')
            ->get();

        $soDonHienTai = $donHienTai->count();

        // Chỉ lấy bó hoa ĐỀ XUẤT (HE_THONG) để hiển thị dòng "tên mẫu × SL".
        // Bó TỰ CHỌN hiển thị qua ChiTietHoaDon (các vật phẩm nhỏ).
        $boTheoDon = BoHoa::whereIn('ma_hoa_don', $donHienTai->pluck('ma_hoa_don'))
            ->where('loai_bo_hoa', 'HE_THONG')
            ->get()
            ->groupBy('ma_hoa_don');

        $boHoaDaLuu = BoHoa::tuyChon()->draft()->where('ma_khach_hang', $maKhachHang)
            ->whereNull('ma_hoa_don')->with('chiTietBoHoa.sanPham')->orderByDesc('created_at')->get();

        $lichSuDonHang = HoaDon::with(['chiTietHoaDon.sanPham'])
            ->where('ma_khach_hang', $maKhachHang)
            ->whereIn('trang_thai', ['DELIVERED', 'CANCELLED'])
            ->orderByDesc('ngay_dat')
            ->get();

        // Cùng logic với $boTheoDon — chỉ lấy bó đề xuất cho lịch sử
        $boTheoDonLichSu = BoHoa::whereIn('ma_hoa_don', $lichSuDonHang->pluck('ma_hoa_don'))
            ->where('loai_bo_hoa', 'HE_THONG')
            ->get()
            ->groupBy('ma_hoa_don');

        return view('Customer.ThongBao', compact(
            'thongBaos',
            'soThongBao',
            'donHienTai',
            'soDonHienTai',
            'boTheoDon',
            'boTheoDonLichSu',
            'boHoaDaLuu',
            'lichSuDonHang',
            'activeTab'
        ));
    }

    public function soChuaDoc()
{
    $maKhachHang = (int) Auth::user()->ma_nguoi_dung;
    $count = ThongBao::where('ma_nguoi_dung', $maKhachHang)->where('da_doc', false)->count();

    return response()->json(['count' => $count]);
}

    public function xoa(Request $request, string $id)
{
    $maKhachHang = (int) Auth::user()->ma_nguoi_dung;

    ThongBao::where('ma_nguoi_dung', $maKhachHang)
        ->where('ma_thong_bao', (int) $id)
        ->delete();

    return response()->json(['success' => true]);
}
}