<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    protected $table      = 'thong_bao';
    protected $primaryKey = 'ma_thong_bao';
    public $timestamps    = false;

    protected $fillable = [
        'ma_nguoi_dung', 'loai', 'noi_dung', 'du_lieu', 'ma_hoa_don', 'da_doc', 'thoi_gian',
    ];

    protected $casts = [
        'da_doc'    => 'boolean',
        'thoi_gian' => 'datetime',
        'du_lieu'   => 'array',
    ];

    /**
     * Gửi 1 thông báo. Nuốt lỗi để không làm hỏng luồng nghiệp vụ chính
     * (vd: admin gán đơn, ghi thông báo fail cũng không rollback đơn).
     *
     * $loai: order_new | penalty | payment | shipping | arrived | delivered | apology | confirmed | cancelled | info
     */
    public static function gui(
        int $maNguoiDung,
        string $loai,
        string $noiDung,
        ?int $maHoaDon = null,
        ?array $duLieu = null
    ): void {
        try {
            self::create([
                'ma_nguoi_dung' => $maNguoiDung,
                'loai'          => $loai,
                'noi_dung'      => $noiDung,
                'du_lieu'       => $duLieu,
                'ma_hoa_don'    => $maHoaDon,
                'da_doc'        => false,
                'thoi_gian'     => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('[ThongBao::gui] ' . $e->getMessage());
        }
    }
}