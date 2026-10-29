<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminNotificationService
{
    const CACHE_KEY_PUSHED = 'thong_bao_admin_pushed';

    /**
     * Lấy toàn bộ danh sách thông báo của cửa hàng:
     * 1. Đơn hàng (Đơn mới, Đã xác nhận, Hoàn tất)
     * 2. Biến động kho (Nhập hàng tăng tồn kho, Tồn kho giảm/hết hàng, Xuất hàng hỏng)
     * 3. Hạn sử dụng (Bó hoa / Lô hoa sắp & đã hết hạn)
     * 4. Các thông báo hệ thống được push vào Cache
     */
    public static function getNotifications(): array
    {
        $notifications = [];

        // ─────────────────────────────────────────────────────────────
        // 1. THÔNG BÁO ĐƠN HÀNG (Có đơn mới, xác nhận, hoàn tất)
        // ─────────────────────────────────────────────────────────────
        try {
            $orders = DB::table('hoa_don as hd')
                ->leftJoin('khach_hang as kh', 'hd.ma_khach_hang', '=', 'kh.ma_khach_hang')
                ->select('hd.*', 'kh.ten_khach_hang')
                ->orderByDesc('hd.ngay_dat')
                ->limit(8)
                ->get();

            foreach ($orders as $hd) {
                $code = '#HD' . str_pad($hd->ma_hoa_don, 4, '0', STR_PAD_LEFT);
                $khName = $hd->ten_khach_hang ?: 'Khách hàng';
                $tongTienFmt = number_format($hd->tong_tien) . '₫';
                $dt = Carbon::parse($hd->ngay_dat);
                $timeText = $dt->locale('vi')->diffForHumans();

                if ($hd->trang_thai === 'PENDING') {
                    $notifications[] = [
                        'id'          => 'order_pending_' . $hd->ma_hoa_don,
                        'category'    => 'order',
                        'type'        => 'order_new',
                        'badge'       => 'Đơn mới',
                        'badge_style' => 'background:#FEF3F2; color:#B42318; border:1px solid #FECDCA;',
                        'icon_bg'     => '#FEF3F2',
                        'icon_color'  => '#D92D20',
                        'icon_type'   => 'shopping_bag',
                        'title'       => "Đơn hàng mới {$code}",
                        'message'     => "{$khName} vừa đặt đơn trị giá {$tongTienFmt} đang chờ bạn xác nhận.",
                        'time'        => $timeText,
                        'full_time'   => $dt->copy()->addHours(7)->format('H:i - d/m/Y'),
                        'link'        => url("/hoa-don?highlight={$hd->ma_hoa_don}"),
                        'link_text'   => 'Xem đơn hàng',
                        'timestamp'   => $dt->timestamp,
                    ];
                } elseif ($hd->trang_thai === 'CONFIRMED') {
                    $notifications[] = [
                        'id'          => 'order_confirmed_' . $hd->ma_hoa_don,
                        'category'    => 'order',
                        'type'        => 'order_confirmed',
                        'badge'       => 'Đã duyệt',
                        'badge_style' => 'background:#EFF8FF; color:#175CD3; border:1px solid #B2DDFF;',
                        'icon_bg'     => '#EFF8FF',
                        'icon_color'  => '#1570EF',
                        'icon_type'   => 'check_circle',
                        'title'       => "Đơn {$code} đã duyệt",
                        'message'     => "Đơn hàng trị giá {$tongTienFmt} đã xác nhận, sẵn sàng giao tới khách hàng.",
                        'time'        => $timeText,
                        'full_time'   => $dt->copy()->addHours(7)->format('H:i - d/m/Y'),
                        'link'        => url("/hoa-don?highlight={$hd->ma_hoa_don}"),
                        'link_text'   => 'Chi tiết đơn',
                        'timestamp'   => $dt->timestamp,
                    ];
                } elseif ($hd->trang_thai === 'DELIVERED') {
                    $notifications[] = [
                        'id'          => 'order_delivered_' . $hd->ma_hoa_don,
                        'category'    => 'order',
                        'type'        => 'order_delivered',
                        'badge'       => 'Thành công',
                        'badge_style' => 'background:#ECFDF3; color:#027A48; border:1px solid #A6F4C5;',
                        'icon_bg'     => '#ECFDF3',
                        'icon_color'  => '#12B76A',
                        'icon_type'   => 'trending_up',
                        'title'       => "Giao thành công {$code}",
                        'message'     => "Đơn hàng đã giao thành công, ghi nhận doanh thu +{$tongTienFmt}.",
                        'time'        => $timeText,
                        'full_time'   => $dt->copy()->addHours(7)->format('H:i - d/m/Y'),
                        'link'        => url("/hoa-don?highlight={$hd->ma_hoa_don}"),
                        'link_text'   => 'Xem hóa đơn',
                        'timestamp'   => $dt->timestamp,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // ─────────────────────────────────────────────────────────────
        // 2. BIẾN ĐỘNG TỒN KHO: NHẬP HÀNG (TỒN KHO TĂNG LÊN)
        // ─────────────────────────────────────────────────────────────
        try {
            $receipts = DB::table('phieu_nhap as pn')
                ->leftJoin('nha_cung_cap as ncc', 'pn.ma_nha_cung_cap', '=', 'ncc.ma_nha_cung_cap')
                ->whereIn('pn.trang_thai', ['CONFIRMED', 'DRAFT'])
                ->select('pn.*', 'ncc.ten_nha_cung_cap')
                ->orderByDesc('pn.ma_phieu_nhap')
                ->limit(6)
                ->get();

            foreach ($receipts as $pn) {
                $code = '#PN' . str_pad($pn->ma_phieu_nhap, 4, '0', STR_PAD_LEFT);
                $nccName = $pn->ten_nha_cung_cap ?: 'Nhà cung cấp';
                
                // QUAN TRỌNG: Sử dụng updated_at hoặc created_at của hệ thống để khớp chuẩn thời gian thực với server
                $dt = Carbon::parse($pn->updated_at ?: $pn->created_at);
                $timeText = $dt->locale('vi')->diffForHumans();

                // Đếm số lượng sản phẩm nhập trong phiếu
                $soLo = DB::table('chi_tiet_nhap')->where('ma_phieu_nhap', $pn->ma_phieu_nhap)->count();
                $tongSoLuong = DB::table('chi_tiet_nhap')->where('ma_phieu_nhap', $pn->ma_phieu_nhap)->sum('so_luong');
                $isDraft = ($pn->trang_thai === 'DRAFT');

                $notifications[] = [
                    'id'          => 'stock_in_' . $pn->ma_phieu_nhap,
                    'category'    => 'stock',
                    'type'        => 'stock_in',
                    'badge'       => $isDraft ? 'Phiếu tạm' : 'Nhập kho (+)',
                    'badge_style' => $isDraft 
                        ? 'background:#FFFAEB; color:#B54708; border:1px solid #FEDF89;' 
                        : 'background:#EFF8FF; color:#175CD3; border:1px solid #B2DDFF;',
                    'icon_bg'     => $isDraft ? '#FFFAEB' : '#F0F9FF',
                    'icon_color'  => $isDraft ? '#DC6803' : '#0284C7',
                    'icon_type'   => 'arrow_down_circle',
                    'title'       => $isDraft ? "Phiếu nhập mới {$code}" : "Nhập hàng thành công {$code}",
                    'message'     => $isDraft 
                        ? "Đã tạo phiếu nhập {$soLo} loại sản phẩm từ {$nccName}, đang chờ duyệt."
                        : "Đã nhập {$soLo} loại sản phẩm (tổng {$tongSoLuong} đơn vị) từ {$nccName} vào kho.",
                    'time'        => $timeText,
                    'full_time'   => Carbon::parse($pn->ngay_nhap ?: $pn->created_at)->format('H:i - d/m/Y'),
                    'link'        => route('phieu-nhap.index'),
                    'link_text'   => 'Xem phiếu nhập',
                    'timestamp'   => $dt->timestamp,
                ];
            }
        } catch (\Throwable $e) {}

        // ─────────────────────────────────────────────────────────────
        // 3. BIẾN ĐỘNG TỒN KHO: TỒN KHO GIẢM SÂU / HẾT HÀNG
        // ─────────────────────────────────────────────────────────────
        try {
            $lowProducts = DB::table('san_pham')
                ->where('trang_thai', 'DANG_BAN')
                ->where('so_luong', '<=', 10)
                ->orderBy('so_luong')
                ->limit(5)
                ->get();

            // Ghi nhận kiểm tra tồn kho định kỳ đầu ngày hôm nay
            $stockDt = Carbon::today()->startOfDay();

            foreach ($lowProducts as $sp) {
                $isOut = ((int) $sp->so_luong === 0);
                $dvt = $sp->don_vi_tinh ?: 'sản phẩm';

                $notifications[] = [
                    'id'          => 'stock_low_' . $sp->ma_san_pham,
                    'category'    => 'stock',
                    'type'        => $isOut ? 'stock_out' : 'stock_low',
                    'badge'       => $isOut ? 'Hết hàng (0)' : 'Kho giảm (-)',
                    'badge_style' => $isOut 
                        ? 'background:#FEF3F2; color:#B42318; border:1px solid #FECDCA;'
                        : 'background:#FFFAEB; color:#B54708; border:1px solid #FEDF89;',
                    'icon_bg'     => $isOut ? '#FEF3F2' : '#FFFAEB',
                    'icon_color'  => $isOut ? '#D92D20' : '#DC6803',
                    'icon_type'   => 'alert_triangle',
                    'title'       => $isOut ? "Hết hàng: {$sp->ten_san_pham}" : "Tồn kho giảm: {$sp->ten_san_pham}",
                    'message'     => $isOut 
                        ? "Sản phẩm đã hết hàng hoàn toàn trong kho. Vui lòng tạo phiếu nhập bổ sung."
                        : "Số lượng chỉ còn {$sp->so_luong} {$dvt} (chạm ngưỡng báo động).",
                    'time'        => 'Hôm nay',
                    'full_time'   => Carbon::now()->format('d/m/Y'),
                    'link'        => route('admin.ton-kho'),
                    'link_text'   => 'Kiểm tra tồn kho',
                    'timestamp'   => $stockDt->timestamp - 20,
                ];
            }
        } catch (\Throwable $e) {}

        // ─────────────────────────────────────────────────────────────
        // 4. BIẾN ĐỘNG KHO: HÀO HỤT / BÁO CÁO HÀNG HỎNG
        // ─────────────────────────────────────────────────────────────
        try {
            $damages = DB::table('bao_cao_hang_hong as bc')
                ->join('san_pham as sp', 'bc.ma_san_pham', '=', 'sp.ma_san_pham')
                ->select('bc.*', 'sp.ten_san_pham', 'sp.don_vi_tinh')
                ->orderByDesc('bc.thoi_gian_bao_cao')
                ->limit(3)
                ->get();

            foreach ($damages as $bc) {
                $dt = Carbon::parse($bc->thoi_gian_bao_cao);
                $dvt = $bc->don_vi_tinh ?: 'đơn vị';

                $notifications[] = [
                    'id'          => 'damage_' . $bc->ma_bao_cao,
                    'category'    => 'stock',
                    'type'        => 'damage',
                    'badge'       => 'Hao hụt (-)',
                    'badge_style' => 'background:#FDF2FA; color:#C11574; border:1px solid #FCCEEE;',
                    'icon_bg'     => '#FDF2FA',
                    'icon_color'  => '#C11574',
                    'icon_type'   => 'shield_alert',
                    'title'       => "Hao hụt kho: {$bc->ten_san_pham}",
                    'message'     => "Đã giảm {$bc->so_luong_hong} {$dvt} khỏi tồn kho do: {$bc->ly_do}.",
                    'time'        => $dt->locale('vi')->diffForHumans(),
                    'full_time'   => $dt->format('H:i - d/m/Y'),
                    'link'        => route('admin.ton-kho'),
                    'link_text'   => 'Xem kho hàng',
                    'timestamp'   => $dt->timestamp,
                ];
            }
        } catch (\Throwable $e) {}

        // ─────────────────────────────────────────────────────────────
        // 5. CẢNH BÁO HẠN SỬ DỤNG (BÓ HOA & LÔ HOA)
        // ─────────────────────────────────────────────────────────────
        try {
            $today = Carbon::today();
            $todayStr = $today->toDateString();
            $warningDays = \App\Services\SystemConfigService::getInt('expiry_warning_days', 2);
            $thresholdStr = $today->copy()->addDays($warningDays)->toDateString();
            $expiryDt = $today->copy()->startOfDay(); // Rà soát hạn dùng đầu ngày hôm nay
            $bouquetDays = \App\Services\SystemConfigService::getInt('finished_bouquet_expiry_days', 5);
            $freshFlowerDays = \App\Services\SystemConfigService::getInt('fresh_flower_expiry_days', 7);

            // Bó hoa
            $hasNgayHetHanBH = Schema::hasColumn('bo_hoa', 'ngay_het_han');
            $boHoas = DB::table('bo_hoa as bh')
                ->where('bh.trang_thai', 'ACTIVE')
                ->where('bh.so_luong', '>', 0)
                ->whereNull('bh.ma_hoa_don')
                ->select(DB::raw($hasNgayHetHanBH
                    ? "COALESCE(bh.ngay_het_han, DATE(DATE_ADD(bh.created_at, INTERVAL {$bouquetDays} DAY))) as hsd"
                    : "DATE(DATE_ADD(bh.created_at, INTERVAL {$bouquetDays} DAY)) as hsd"
                ))
                ->having('hsd', '<=', $thresholdStr)
                ->get();

            $soBoQuaHan = $boHoas->where('hsd', '<=', $todayStr)->count();
            $soBoSapHet = $boHoas->where('hsd', '>', $todayStr)->count();

            if ($soBoQuaHan > 0 || $soBoSapHet > 0) {
                $detailTxt = [];
                if ($soBoQuaHan > 0) $detailTxt[] = "{$soBoQuaHan} bó hoa đã quá hạn/hết hạn hôm nay";
                if ($soBoSapHet > 0) $detailTxt[] = "{$soBoSapHet} bó hoa sắp hết hạn";

                $notifications[] = [
                    'id'          => 'expiry_bouquets_alert',
                    'category'    => 'expiry',
                    'type'        => 'expiry',
                    'badge'       => 'Hạn sử dụng',
                    'badge_style' => 'background:#FEF3F2; color:#B42318; border:1px solid #FECDCA;',
                    'icon_bg'     => '#FEF3F2',
                    'icon_color'  => '#E11D48',
                    'icon_type'   => 'hourglass',
                    'title'       => "Cảnh báo bó hoa đến hạn",
                    'message'     => "Hiện có " . implode(' và ', $detailTxt) . ". Cần thanh lý hoặc xả hoa để tránh thiệt hại.",
                    'time'        => 'Hôm nay',
                    'full_time'   => $today->format('d/m/Y'),
                    'link'        => route('admin.san-pham-het-han'),
                    'link_text'   => 'Xử lý ngay',
                    'timestamp'   => $expiryDt->timestamp,
                ];
            }

            // Lô hoa
            $hasNgayHetHanCTN = Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han');
            $loHangs = DB::table('chi_tiet_nhap as ctn')
                ->join('phieu_nhap as pn', 'ctn.ma_phieu_nhap', '=', 'pn.ma_phieu_nhap')
                ->join('san_pham as sp', 'ctn.ma_san_pham', '=', 'sp.ma_san_pham')
                ->where('pn.trang_thai', 'CONFIRMED')
                ->where('ctn.so_luong_con_lai', '>', 0)
                ->where(function ($q) {
                    $q->whereIn('sp.loai_san_pham', ['HOA_TUOI', 'CHAU_HOA_TUOI'])
                      ->orWhereNotNull('ctn.ngay_het_han');
                })
                ->select('sp.ten_san_pham', DB::raw($hasNgayHetHanCTN
                    ? "COALESCE(ctn.ngay_het_han, DATE(DATE_ADD(ctn.created_at, INTERVAL {$freshFlowerDays} DAY))) as hsd"
                    : "DATE(DATE_ADD(ctn.created_at, INTERVAL {$freshFlowerDays} DAY)) as hsd"
                ))
                ->having('hsd', '<=', $thresholdStr)
                ->get();

            if ($loHangs->count() > 0) {
                $tenHoaList = $loHangs->pluck('ten_san_pham')->unique()->take(2)->implode(', ');
                $notifications[] = [
                    'id'          => 'expiry_lots_alert',
                    'category'    => 'expiry',
                    'type'        => 'expiry',
                    'badge'       => 'Hạn hoa tươi',
                    'badge_style' => 'background:#FFFAEB; color:#B54708; border:1px solid #FEDF89;',
                    'icon_bg'     => '#FFFAEB',
                    'icon_color'  => '#D97706',
                    'icon_type'   => 'flower',
                    'title'       => "Lô hoa tươi sắp hết hạn",
                    'message'     => "Có {$loHangs->count()} lô hoa ({$tenHoaList}...) sắp hết hạn bảo quản trong 1-3 ngày tới.",
                    'time'        => 'Hôm nay',
                    'full_time'   => $today->format('d/m/Y'),
                    'link'        => route('admin.san-pham-het-han'),
                    'link_text'   => 'Kiểm tra lô hoa',
                    'timestamp'   => $expiryDt->timestamp - 10,
                ];
            }
        } catch (\Throwable $e) {}

        // ─────────────────────────────────────────────────────────────
        // 6. THÔNG BÁO TỪ HỆ THỐNG / PUSH TỪ CACHE
        // ─────────────────────────────────────────────────────────────
        $pushed = Cache::get(self::CACHE_KEY_PUSHED, []);
        if (is_array($pushed) && !empty($pushed)) {
            foreach ($pushed as $p) {
                $notifications[] = $p;
            }
        }

        // Sắp xếp tất cả thông báo theo thời gian mới nhất lên đầu
        usort($notifications, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        return $notifications;
    }

    /**
     * Đẩy thông báo tùy biến vào hàng đợi thông báo admin
     */
    public static function push(
        string $title,
        string $message,
        string $category = 'system',
        string $badge = 'Thông báo',
        ?string $link = null,
        string $linkText = 'Chi tiết'
    ): void {
        $pushed = Cache::get(self::CACHE_KEY_PUSHED, []);
        $now = Carbon::now();

        $newItem = [
            'id'          => 'pushed_' . uniqid(),
            'category'    => $category,
            'type'        => 'custom',
            'badge'       => $badge,
            'badge_style' => 'background:#F8F9FA; color:#344054; border:1px solid #EAECF0;',
            'icon_bg'     => '#F8F9FA',
            'icon_color'  => '#475467',
            'icon_type'   => 'bell',
            'title'       => $title,
            'message'     => $message,
            'time'        => 'Vừa xong',
            'full_time'   => $now->format('H:i - d/m/Y'),
            'link'        => $link ?: url('/admin/dashboard'),
            'link_text'   => $linkText,
            'timestamp'   => $now->timestamp,
        ];

        array_unshift($pushed, $newItem);
        $pushed = array_slice($pushed, 0, 30);
        Cache::put(self::CACHE_KEY_PUSHED, $pushed, now()->addDays(30));
    }
}
