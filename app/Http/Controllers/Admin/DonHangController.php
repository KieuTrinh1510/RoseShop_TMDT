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
        $tab = $request->get('tab', 'all');
        if (!in_array($tab, ['all', 'pending', 'confirmed', 'shipping', 'delivered', 'failed', 'cancelled'])) {
            $tab = 'all';
        }

        $query = HoaDon::with([
            'khachHang.viPhamGiaoHang' => fn($q) => $q->where('da_go', false),
            'shipper',
            'chiTietHoaDon.sanPham',
            'giaoHangThatBai.shipper',
            'hangHoan.nguoiKiemTra',
            'hangHoan.chiTiet.sanPham',
            'hangHoan.chiTiet.boHoa',
            'phatShipper',
            'boHoas',
        ]);

        // 1. Áp dụng filter theo Tab
        switch ($tab) {
            case 'pending':
                $query->where('trang_thai', 'PENDING');
                break;
            case 'confirmed':
                $query->where('trang_thai', 'CONFIRMED');
                break;
            case 'shipping':
                $query->where('trang_thai', 'SHIPPING');
                break;
            case 'delivered':
                $query->where('trang_thai', 'DELIVERED');
                break;
            case 'failed':
                $query->where(function ($q) {
                    $q->where('co_hang_hoan', 1)
                      ->orWhereHas('giaoHangThatBai');
                });
                break;
            case 'cancelled':
                $query->where('trang_thai', 'CANCELLED')
                      ->where(function ($q) {
                          $q->where('co_hang_hoan', 0)->orWhereNull('co_hang_hoan');
                      })
                      ->whereDoesntHave('giaoHangThatBai');
                break;
            case 'all':
            default:
                // Hiển thị toàn bộ
                break;
        }

        // 2. Tìm kiếm chung: mã đơn, tên khách, số điện thoại
        if ($request->filled('q')) {
            $q = trim($request->q);
            $cleanId = preg_replace('/\D/', '', $q);

            $query->where(function ($sq) use ($q, $cleanId) {
                if ($cleanId !== '') {
                    $sq->where('ma_hoa_don', (int) $cleanId);
                }
                $sq->orWhereHas('khachHang', function ($kq) use ($q) {
                    $kq->where('ten_khach_hang', 'like', "%{$q}%")
                       ->orWhere('so_dien_thoai', 'like', "%{$q}%");
                })->orWhere('so_dien_thoai', 'like', "%{$q}%");
            });
        }

        // 3. Các bộ lọc bổ sung
        if ($request->filled('trang_thai_thanh_toan')) {
            $query->where('trang_thai_thanh_toan', $request->trang_thai_thanh_toan);
        }
        if ($request->filled('phuong_thuc_thanh_toan')) {
            $query->where('phuong_thuc_thanh_toan', $request->phuong_thuc_thanh_toan);
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

        // 4. Tab counts: tính một query aggregate duy nhất tránh N+1
        $countsRaw = HoaDon::selectRaw("
            COUNT(*) as count_all,
            SUM(CASE WHEN trang_thai = 'PENDING' THEN 1 ELSE 0 END) as count_pending,
            SUM(CASE WHEN trang_thai = 'CONFIRMED' THEN 1 ELSE 0 END) as count_confirmed,
            SUM(CASE WHEN trang_thai = 'SHIPPING' THEN 1 ELSE 0 END) as count_shipping,
            SUM(CASE WHEN trang_thai = 'DELIVERED' THEN 1 ELSE 0 END) as count_delivered,
            SUM(CASE WHEN (co_hang_hoan = 1 OR EXISTS (SELECT 1 FROM giao_hang_that_bai WHERE giao_hang_that_bai.ma_hoa_don = hoa_don.ma_hoa_don)) THEN 1 ELSE 0 END) as count_failed,
            SUM(CASE WHEN (trang_thai = 'CANCELLED' AND (co_hang_hoan = 0 OR co_hang_hoan IS NULL) AND NOT EXISTS (SELECT 1 FROM giao_hang_that_bai WHERE giao_hang_that_bai.ma_hoa_don = hoa_don.ma_hoa_don)) THEN 1 ELSE 0 END) as count_cancelled
        ")->first();

        $tabCounts = [
            'all'       => (int) ($countsRaw->count_all ?? 0),
            'pending'   => (int) ($countsRaw->count_pending ?? 0),
            'confirmed' => (int) ($countsRaw->count_confirmed ?? 0),
            'shipping'  => (int) ($countsRaw->count_shipping ?? 0),
            'delivered' => (int) ($countsRaw->count_delivered ?? 0),
            'failed'    => (int) ($countsRaw->count_failed ?? 0),
            'cancelled' => (int) ($countsRaw->count_cancelled ?? 0),
        ];

        // 5. Phân trang
        $perPage  = in_array((int)$request->get('per_page'), [10, 15, 20, 50, 100]) ? (int)$request->get('per_page') : 15;
        $donHangs = $query->orderByDesc('ngay_dat')->paginate($perPage)->withQueryString();

        // 6. Shipper khả dụng phục vụ duyệt đơn
        $shippers = NhanVien::where('chuc_vu', 'SHIPPER')
            ->where('trang_thai_hoat_dong', 'ONLINE')
            ->get()
            ->map(function ($nv) {
                $nv->so_don_live = HoaDon::where('ma_nhan_vien_giao', $nv->ma_nhan_vien)
                    ->whereIn('trang_thai', ['CONFIRMED', 'SHIPPING'])
                    ->count();
                return $nv;
            })
            ->filter(fn ($nv) => $nv->so_don_live < 10)
            ->values();

        $boTheoDon = \App\Models\BoHoa::whereIn('ma_hoa_don', $donHangs->pluck('ma_hoa_don'))->get()->groupBy('ma_hoa_don');

        return view('admin.DonHang', compact('donHangs', 'shippers', 'boTheoDon', 'tab', 'tabCounts'));
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
                if (!NhanVien::whereKey($shipperId)->where('chuc_vu', 'SHIPPER')->exists()) BoHoaController::loi('Vui lòng chọn nhân viên có chức vụ SHIPPER.');
                if (!$hd->dia_chi_giao || !$hd->so_dien_thoai || !$hd->phuong_thuc_thanh_toan) BoHoaController::loi('Khách chưa hoàn tất thông tin giao hàng và phương thức thanh toán.');
                // Lock reserved bouquets. Both types already left available stock at checkout.
                \App\Models\BoHoa::where('ma_hoa_don', $id)->orderBy('ma_bo_hoa')->lockForUpdate()->get();
                \App\Services\InventoryReservationService::confirmOrder($hd);
            } else {
                $laBo = BoHoaController::huyBoTrongDon($hd);
                $hasReservations = \App\Models\InventoryReservation::where('ma_hoa_don', $hd->ma_hoa_don)->exists();
                $items = $hd->chiTietHoaDon()->get();
                if (!$laBo && !$hasReservations) {
                    BoHoaController::hoanKho($items->toArray());
                }
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
            $hd->update($xacNhan ? ['trang_thai' => 'CONFIRMED', 'ma_nhan_vien_giao' => $shipperId] : ['trang_thai' => 'CANCELLED']);
            return $hd->load('chiTietHoaDon.sanPham');
        }, 3);
    }

}