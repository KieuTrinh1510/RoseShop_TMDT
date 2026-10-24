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
        'du_lieu'   => $tb->du_lieu,   // ← [MỚI] để view đọc thêm thông tin
        'thoi_gian' => $tb->thoi_gian->format('H:i, d/m/Y'),
        'da_doc'    => $tb->da_doc,
    ])
    ->all();

$soThongBao = ThongBao::where('ma_nguoi_dung', $maKhachHang)->where('da_doc', false)->count();

// Khách mở tab thông báo thì coi như đã đọc (số $soThongBao ở trên vẫn là số trước khi đọc)
if ($activeTab === 'thong-bao' && $soThongBao > 0) {
    ThongBao::where('ma_nguoi_dung', $maKhachHang)->where('da_doc', false)->update(['da_doc' => true]);
}

        $donHienTai = HoaDon::with([
        'chiTietHoaDon.sanPham',
        'chiTietHoaDon.boHoa.mauBoHoa',   // ← [MỚI] ưu tiên hiển thị tên bó
        'shipper',
    ])
    ->where('ma_khach_hang', $maKhachHang)
    ->whereIn('trang_thai', ['PENDING', 'CONFIRMED', 'SHIPPING'])
    ->orderByDesc('ngay_dat')
    ->get();

$soDonHienTai = $donHienTai->count();

// Fallback cho dữ liệu cũ: nếu hóa đơn chưa có ct.ma_bo_hoa nhưng bo_hoa có ma_hoa_don
// → gán tạm relation __fallbackBoHoa để view đọc.
$boFallbackMap = BoHoa::whereIn('ma_hoa_don', $donHienTai->pluck('ma_hoa_don'))
    ->get()
    ->groupBy('ma_hoa_don');

$donHienTai->each(function ($hd) use ($boFallbackMap) {
    $coBoTrongChiTiet = $hd->chiTietHoaDon->whereNotNull('ma_bo_hoa')->isNotEmpty();
    if (!$coBoTrongChiTiet && $boFallbackMap->has($hd->ma_hoa_don)) {
        $hd->setRelation('__fallbackBoHoa', $boFallbackMap->get($hd->ma_hoa_don)->first());
    }
});

// Giữ nguyên $boTheoDon như cũ (deprecated nhưng không xóa để tránh vỡ chỗ khác)
$boTheoDon = BoHoa::whereIn('ma_hoa_don', $donHienTai->pluck('ma_hoa_don'))
    ->where('loai_bo_hoa', 'HE_THONG')
    ->get()
    ->groupBy('ma_hoa_don');

$boHoaDaLuu = BoHoa::tuyChon()->draft()->where('ma_khach_hang', $maKhachHang)
    ->whereNull('ma_hoa_don')->with('chiTietBoHoa.sanPham')->orderByDesc('created_at')->get();

        $lichSuDonHang = HoaDon::with([
        'chiTietHoaDon.sanPham',
        'chiTietHoaDon.boHoa.mauBoHoa',   // ← [MỚI]
    ])
    ->where('ma_khach_hang', $maKhachHang)
    ->whereIn('trang_thai', ['DELIVERED', 'CANCELLED'])
    ->orderByDesc('ngay_dat')
    ->get();

$boFallbackMapLs = BoHoa::whereIn('ma_hoa_don', $lichSuDonHang->pluck('ma_hoa_don'))
    ->get()
    ->groupBy('ma_hoa_don');

$lichSuDonHang->each(function ($hd) use ($boFallbackMapLs) {
    $coBoTrongChiTiet = $hd->chiTietHoaDon->whereNotNull('ma_bo_hoa')->isNotEmpty();
    if (!$coBoTrongChiTiet && $boFallbackMapLs->has($hd->ma_hoa_don)) {
        $hd->setRelation('__fallbackBoHoa', $boFallbackMapLs->get($hd->ma_hoa_don)->first());
    }
});

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