<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\NhanVien;
use App\Models\ChiTietNhap;
use App\Models\SanPham;
use App\Models\KhachHang;
use App\Http\Controllers\Shipper\ShipperThongBaoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DonHangController extends Controller
{
    const DIEM_QUY_DOI = 1000;   
    const TICH_DIEM    = 100000; 

    
    public function index(Request $request)
    {
        $query = HoaDon::with(['khachHang', 'chiTietHoaDon.sanPham'])
            ->where('trang_thai', 'PENDING');

        if ($request->filled('trang_thai_thanh_toan')) {
            $query->where('trang_thai_thanh_toan', $request->trang_thai_thanh_toan);
        }
        if ($request->filled('tu_tien')) {
            $query->where('tong_tien', '>=', (float) $request->tu_tien);
        }
        if ($request->filled('den_tien')) {
            $query->where('tong_tien', '<=', (float) $request->den_tien);
        }
        if ($request->filled('tu_ngay')) {
            $query->where('ngay_dat', '>=', Carbon::parse($request->tu_ngay)->startOfDay());
        }
        if ($request->filled('den_ngay')) {
            $query->where('ngay_dat', '<=', Carbon::parse($request->den_ngay)->endOfDay());
        }

        $donHangs = $query->orderByDesc('ngay_dat')->paginate(15)->withQueryString();
        $shippers = NhanVien::where('chuc_vu', 'SHIPPER')
    ->where('trang_thai_hoat_dong', 'ONLINE')
    ->get()
    ->map(function ($nv) {
        // Đếm live từ hoa_don — không tin cột so_don_hien_tai
        // (Node không còn ghi cột, và PHP cũng sẽ bỏ ghi)
        $nv->so_don_live = HoaDon::where('ma_nhan_vien_giao', $nv->ma_nhan_vien)
            ->whereIn('trang_thai', ['CONFIRMED', 'SHIPPING'])
            ->count();
        return $nv;
    })
    ->filter(fn ($nv) => $nv->so_don_live < 10)
    ->values();

        $boTheoDon = \App\Models\BoHoa::whereIn('ma_hoa_don', $donHangs->pluck('ma_hoa_don'))->get()->groupBy('ma_hoa_don');
        return view('admin.DonHang', compact('donHangs', 'shippers', 'boTheoDon'));
    }

    public function confirm(Request $request, $id)
    {
        return $this->xuLy($request, (int) $id, true);
    }

    public function cancel(Request $request, $id)
    {
        return $this->xuLy($request, (int) $id, false);
    }

    private function xuLy(Request $request, int $id, bool $xacNhan)
{
    // Điểm hoàn (nếu hủy đơn) được xuLyDon() ghi ra ngoài qua tham chiếu,
    // dùng để hiển thị chi tiết cho khách hàng và admin (lấy ý tưởng từ bản cũ).
    $diemDaDung = 0;
    $diemDaCong = 0;

    try {
        $request->validate(['ly_do' => 'nullable|string|max:1000']);
        $hd = $this->xuLyDon($id, $xacNhan,
            $request->filled('ma_nhan_vien_giao') ? (int) $request->ma_nhan_vien_giao : null,
            $diemDaDung, $diemDaCong);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
    }
    $maHD = '#HD-'.str_pad($id, 4, '0', STR_PAD_LEFT);

    // ===== Thông báo cho admin: nêu rõ số điểm đã hoàn / thu hồi (từ bản 1) =====
    $msgDiem = '';
    if (!$xacNhan && ($diemDaDung > 0 || $diemDaCong > 0)) {
        $parts = [];
        if ($diemDaDung > 0) $parts[] = "hoàn {$diemDaDung} điểm đã dùng";
        if ($diemDaCong > 0) $parts[] = "thu hồi {$diemDaCong} điểm tích lũy đã cộng";
        $msgDiem = ' Điểm: '.implode(', ', $parts).'.';
    }

    $message = $xacNhan
        ? "Đơn hàng {$maHD} đã được xác nhận và giao cho shipper."
        : "Đơn hàng {$maHD} đã bị từ chối. Hàng đang giữ đã được hoàn kho."
            .($request->filled('ly_do') ? ' Lý do: '.$request->ly_do : '')
            ." Nếu đã thanh toán, vui lòng liên hệ cửa hàng để được hoàn tiền."
            .$msgDiem;

    // ===== Thông báo cho khách hàng: nêu rõ số điểm đã hoàn (từ bản 1) =====
    $phanHoanDiem = (!$xacNhan && $diemDaDung > 0)
        ? " Đã hoàn lại {$diemDaDung} điểm tích lũy bạn đã sử dụng."
        : '';
    $noiDungKhach = $xacNhan
        ? "✅ Đơn hàng {$maHD} của bạn đã được xác nhận và đang chờ shipper lấy hàng!"
        : "❌ Đơn hàng {$maHD} của bạn đã bị từ chối.{$phanHoanDiem}"
            ." Nếu bạn đã thanh toán, vui lòng liên hệ cửa hàng để được hoàn tiền.";

    $key = "thong_bao_khach_{$hd->ma_khach_hang}";
    $ds = Cache::get($key, []);
    array_unshift($ds, ['id' => uniqid('tb_', true), 'noi_dung' => $noiDungKhach,
        'loai' => $xacNhan ? 'confirmed' : 'cancelled', 'thoi_gian' => now()->format('H:i, d/m/Y')]);
    Cache::put($key, array_slice($ds, 0, 20), now()->addDays(7));
        if ($xacNhan) {
        $hang = $hd->chiTietHoaDon->groupBy(fn ($ct) => 'sp_'.$ct->ma_san_pham)
            ->map(fn ($rows) => ($rows->first()->sanPham->ten_san_pham ?? 'Sản phẩm').' (×'.$rows->sum('so_luong').')')->join(', ');
        $boText = \App\Models\BoHoa::where('ma_hoa_don', $id)->get()
            ->map(fn ($bo) => $bo->ten_bo_hoa.' (×'.$bo->so_luong.')')->join(', ');
        $hang = trim($hang.', '.$boText, ', ');

        \App\Models\ThongBao::gui(
            (int) $hd->ma_nhan_vien_giao,
            'order_new',
            "🔔 Bạn có đơn hàng mới {$maHD}: {$hang}. Giao đến: {$hd->dia_chi_giao}.",
            (int) $hd->ma_hoa_don
        );
    }
    // Số đơn live của shipper vừa được gán, để JS cập nhật dropdown không cần reload
    $shipperInfo = null;
    if ($xacNhan && $hd->ma_nhan_vien_giao) {
        $shipperInfo = [
            'ma_nhan_vien' => (int) $hd->ma_nhan_vien_giao,
            'so_don'       => HoaDon::where('ma_nhan_vien_giao', $hd->ma_nhan_vien_giao)
                ->whereIn('trang_thai', ['CONFIRMED', 'SHIPPING'])
                ->count(),
        ];
    }
    return response()->json(['success' => true, 'message' => $message, 'shipper' => $shipperInfo]);
}

private function xuLyDon(int $id, bool $xacNhan, ?int $shipperId, int &$diemDaDungOut = 0, int &$diemDaCongOut = 0): HoaDon
{
    return DB::transaction(function () use ($id, $xacNhan, $shipperId, &$diemDaDungOut, &$diemDaCongOut) {
        $hd = HoaDon::whereKey($id)->lockForUpdate()->firstOrFail();
        if ($hd->trang_thai !== 'PENDING') BoHoaController::loi('Chỉ xử lý đơn PENDING. Đơn này đã được xử lý.');

        if ($xacNhan) {
            // ── Lock row shipper + validate (chống race condition khi 2 admin cùng gán) ──
            $nv = NhanVien::whereKey($shipperId)->lockForUpdate()->first();
            if (!$nv || $nv->chuc_vu !== 'SHIPPER') {
                BoHoaController::loi('Vui lòng chọn nhân viên có chức vụ SHIPPER.');
            }
            if ($nv->trang_thai_hoat_dong !== 'ONLINE') {
                BoHoaController::loi('Shipper đang OFFLINE, không thể chỉ định đơn.');
            }
            // Đếm live từ hoa_don (row shipper đã bị lock nên chống được race condition)
            $soDonLive = HoaDon::where('ma_nhan_vien_giao', $nv->ma_nhan_vien)
                ->whereIn('trang_thai', ['CONFIRMED', 'SHIPPING'])
                ->count();
            if ($soDonLive >= 10) {
                BoHoaController::loi('Shipper đã đủ 10/10 đơn, không thể nhận thêm.');
            }

            if (!$hd->dia_chi_giao || !$hd->so_dien_thoai || !$hd->phuong_thuc_thanh_toan) {
                BoHoaController::loi('Khách chưa hoàn tất thông tin giao hàng và phương thức thanh toán.');
            }

            // Lock reserved bouquets. Both types already left available stock at checkout.
            \App\Models\BoHoa::where('ma_hoa_don', $id)->orderBy('ma_bo_hoa')->lockForUpdate()->get();

            // KHÔNG còn ghi so_don_hien_tai: số đơn được đếm live từ hoa_don.

        } else {
            $laBo = BoHoaController::huyBoTrongDon($hd);
            $items = $hd->chiTietHoaDon()->get();
            // Bouquet allocations are returned by huyBoTrongDon() using their
            // own lot records.  Returning ChiTietHoaDon as well would add the
            // same stock back a second time.
            if (!$laBo) BoHoaController::hoanKho($items->toArray());
            if (!$laBo && $items->isNotEmpty()) {
                $khach = KhachHang::whereKey($hd->ma_khach_hang)->lockForUpdate()->first();
                if ($khach) {
                    // ===== Logic điểm tích lũy (tính ngược từ dữ liệu có sẵn, từ bản 1) =====
                    //  goc     = sum(gia_ban_snapshot × so_luong) từ chi tiết hóa đơn
                    //  daDung  = (goc - tong_tien) / DIEM_QUY_DOI
                    //            → nếu > 0 tức khách có dùng điểm khi đặt → hoàn lại khi hủy
                    //  daCong  = floor(tong_tien / TICH_DIEM)
                    //            → điểm đã cộng khi đặt đơn → thu hồi khi hủy
                    $goc = $items->sum(fn ($ct) => $ct->gia_ban_snapshot * $ct->so_luong);
                    $daDung = max(0, (int) round(($goc - $hd->tong_tien) / self::DIEM_QUY_DOI));
                    $daCong = (int) floor($hd->tong_tien / self::TICH_DIEM);
                    $khach->update(['diem_tich_luy' => max(0, $khach->diem_tich_luy + $daDung - $daCong)]);
                    $diemDaDungOut = $daDung;
                    $diemDaCongOut = $daCong;
                }
            }
        }

        $hd->update($xacNhan
            ? ['trang_thai' => 'CONFIRMED', 'ma_nhan_vien_giao' => $shipperId]
            : ['trang_thai' => 'CANCELLED']);
        return $hd->load('chiTietHoaDon.sanPham');
    }, 3);
}}