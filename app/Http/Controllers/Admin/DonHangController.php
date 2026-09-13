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
        $shippers = NhanVien::where('chuc_vu', 'SHIPPER')->get();

        return view('admin.DonHang', compact('donHangs', 'shippers'));
    }

    public function confirm(Request $request, $id)
    {
        $request->validate([
            'ma_nhan_vien_giao' => 'required|exists:nhan_vien,ma_nhan_vien',
        ], [
            'ma_nhan_vien_giao.required' => 'Vui lòng chọn shipper trước khi xác nhận đơn hàng.',
            'ma_nhan_vien_giao.exists'   => 'Shipper không hợp lệ.',
        ]);

        $hoaDon = HoaDon::findOrFail($id);

        if ($hoaDon->trang_thai !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể xác nhận đơn hàng đang ở trạng thái PENDING.',
            ], 422);
        }

        $hoaDon->update([
            'trang_thai'        => 'CONFIRMED',
            'ma_nhan_vien_giao' => $request->ma_nhan_vien_giao,
        ]);

        $hoaDon->load('chiTietHoaDon.sanPham');

        // ===== THAY ĐỔI =====
        // Sau khi ThanhToanController::store() được sửa để ghi 1 dòng ChiTietHoaDon
        // cho MỖI batch (thay vì 1 dòng gộp cho mỗi sản phẩm), một sản phẩm mua từ
        // nhiều lô sẽ có nhiều dòng ChiTietHoaDon. Nếu không group lại, tin nhắn gửi
        // shipper sẽ hiện trùng lặp kiểu "Hoa A (×2), Hoa A (×2)" thay vì "Hoa A (×4)".
        // Group theo ma_san_pham và cộng tổng so_luong trước khi build chuỗi hiển thị.
        // Lưu ý: đây chỉ là gộp lúc hiển thị text, dữ liệu batch trong DB không đổi.
        $danhSachSanPham = $hoaDon->chiTietHoaDon
            ->groupBy('ma_san_pham')
            ->map(function ($items) {
                $first = $items->first();
                $tenSanPham = $first->sanPham->ten_san_pham ?? 'Sản phẩm #' . $first->ma_san_pham;
                $tongSoLuong = $items->sum('so_luong');
                return $tenSanPham . ' (×' . $tongSoLuong . ')';
            })
            ->join(', ');

        $coHoaTuoi = $hoaDon->chiTietHoaDon->contains(function ($item) {
            $ten = strtolower($item->sanPham->ten_san_pham ?? '');
            return str_contains($ten, 'hoa') || str_contains($ten, 'flower') || str_contains($ten, 'bó');
        });

        $maHD   = '#HD-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        $diaChi = $hoaDon->dia_chi_giao;

        $noiDung = $coHoaTuoi
            ? "🌸 Bạn có đơn hàng mới {$maHD} gồm: {$danhSachSanPham}. Đây là hoa tươi — hãy giao thành công trong 3 tiếng đến địa chỉ: {$diaChi}."
            : "📦 Bạn có đơn hàng mới {$maHD} gồm: {$danhSachSanPham}. Giao đến địa chỉ: {$diaChi}.";
        $loai = $coHoaTuoi ? 'warning' : 'info';

        ShipperThongBaoController::push((int) $request->ma_nhan_vien_giao, $noiDung, $loai);

        $maKhach  = (int) $hoaDon->ma_khach_hang;
        $keyKhach = "thong_bao_khach_{$maKhach}";
        $dsKhach  = Cache::get($keyKhach, []);
        array_unshift($dsKhach, [
            'id'        => uniqid('tb_', true),
            'noi_dung'  => "✅ Đơn hàng {$maHD} của bạn đã được xác nhận và đang chờ shipper lấy hàng!",
            'loai'      => 'confirmed',
            'thoi_gian' => now()->format('H:i, d/m/Y'),
        ]);
        Cache::put($keyKhach, array_slice($dsKhach, 0, 20), now()->addDays(7));

        return response()->json([
            'success' => true,
            'message' => 'Đã xác nhận đơn hàng ' . $maHD . ' thành công.',
        ]);
    }

    
    // HỦY ĐƠN HÀNG
    // Logic điểm tích lũy (tính ngược từ dữ liệu có sẵn, không cần cột mới):
    //
    //  tongGoc       = sum(gia_ban_snapshot × so_luong) từ chi tiết hóa đơn
    //  diemDaDung    = (tongGoc - tong_tien) / DIEM_QUY_DOI
    //                  → nếu > 0 tức khách có dùng điểm → hoàn lại
    //
    //  diemDaCong    = floor(tong_tien / TICH_DIEM)
    //                  → điểm đã cộng khi đặt đơn → thu hồi khi hủy
    
    public function cancel($id)
    {
        $hoaDon = HoaDon::with(['chiTietHoaDon', 'khachHang'])->findOrFail($id);

        if ($hoaDon->trang_thai !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể từ chối đơn hàng đang ở trạng thái PENDING.',
            ], 422);
        }

        $maHD      = '#HD-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        $khachHang = $hoaDon->khachHang;

        
        $tongGoc = $hoaDon->chiTietHoaDon->sum(
            fn($ct) => $ct->gia_ban_snapshot * $ct->so_luong
        );

        $chenhLech  = $tongGoc - $hoaDon->tong_tien; 
        $diemDaDung = ($chenhLech > 0)
            ? (int) round($chenhLech / self::DIEM_QUY_DOI)
            : 0;

        $diemDaCong = (int) floor($hoaDon->tong_tien / self::TICH_DIEM);

        
        $diemHienTai = $khachHang ? (int) $khachHang->diem_tich_luy : 0;

        
        $diemSauHuy = max(0, $diemHienTai + $diemDaDung - $diemDaCong);

        DB::transaction(function () use ($hoaDon, $khachHang, $diemHienTai, $diemSauHuy) {

            // ===== THAY ĐỔI QUAN TRỌNG =====
            // TRƯỚC: hệ thống tự "đoán" 1 batch bất kỳ (lô có ma_chi_tiet_nhap lớn nhất)
            // rồi dồn toàn bộ số lượng vào đó — SAI vì không liên quan gì tới batch
            // thực sự đã bị trừ lúc mua hàng.
            //
            // SAU: đọc trực tiếp ma_chi_tiet_nhap đã được lưu chính xác trong
            // ChiTietHoaDon (tại thời điểm mua hàng, xem ThanhToanController::store()).
            // Không còn tìm/đoán batch nào khác. Nếu 1 sản phẩm được lấy từ nhiều batch,
            // hoaDon->chiTietHoaDon sẽ có nhiều dòng tương ứng, mỗi dòng tự hoàn đúng
            // batch + đúng số lượng của chính nó.
            foreach ($hoaDon->chiTietHoaDon as $ct) {
                if ($ct->ma_chi_tiet_nhap) {
                    ChiTietNhap::where('ma_chi_tiet_nhap', $ct->ma_chi_tiet_nhap)
                        ->increment('so_luong_con_lai', $ct->so_luong);
                }
                // Nếu ma_chi_tiet_nhap là null: đây là đơn hàng cũ được tạo TRƯỚC khi
                // hệ thống lưu batch allocation. Không có dữ liệu để xác định đúng batch
                // nên không thể hoàn chính xác theo lô — chỉ hoàn tổng SanPham.so_luong
                // ở dưới. Đây là giới hạn dữ liệu lịch sử, không phải lỗi logic.

                SanPham::where('ma_san_pham', $ct->ma_san_pham)
                    ->increment('so_luong', $ct->so_luong);
            }

            if ($khachHang && $diemSauHuy !== $diemHienTai) {
                $khachHang->diem_tich_luy = $diemSauHuy;
                $khachHang->save();
            }

            $hoaDon->update(['trang_thai' => 'CANCELLED']);
        });

        $maKhach  = (int) $hoaDon->ma_khach_hang;
        $keyKhach = "thong_bao_khach_{$maKhach}";
        $dsKhach  = Cache::get($keyKhach, []);

        $phanHoanDiem = $diemDaDung > 0
            ? " Đã hoàn lại {$diemDaDung} điểm tích lũy bạn đã sử dụng."
            : '';

        array_unshift($dsKhach, [
            'id'        => uniqid('tb_', true),
            'noi_dung'  => "❌ Đơn hàng {$maHD} của bạn đã bị từ chối.{$phanHoanDiem}"
                         . " Nếu bạn đã thanh toán, vui lòng liên hệ cửa hàng để được hoàn tiền.",
            'loai'      => 'cancelled',
            'thoi_gian' => now()->format('H:i, d/m/Y'),
        ]);
        Cache::put($keyKhach, array_slice($dsKhach, 0, 20), now()->addDays(7));

        $msgDiem = '';
        if ($diemDaDung > 0 || $diemDaCong > 0) {
            $parts = [];
            if ($diemDaDung > 0) $parts[] = "hoàn {$diemDaDung} điểm đã dùng";
            if ($diemDaCong > 0) $parts[] = "thu hồi {$diemDaCong} điểm tích lũy đã cộng";
            $msgDiem = ' Điểm: ' . implode(', ', $parts) . '.';
        }

        return response()->json([
            'success' => true,
            'message' => "Đã từ chối đơn hàng {$maHD}. Số lượng kho đã được hoàn lại.{$msgDiem}",
        ]);
    }
}