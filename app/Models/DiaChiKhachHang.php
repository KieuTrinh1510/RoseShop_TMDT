<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaChiKhachHang extends Model
{
    use HasFactory;

    protected $table = 'dia_chi_khach_hang';
    protected $primaryKey = 'ma_dia_chi';

    protected $fillable = [
        'ma_khach_hang',
        'ten_nguoi_nhan',
        'so_dien_thoai',
        'quan_huyen',
        'xa_phuong',
        'dia_chi_chi_tiet',
        'dia_chi_day_du',
        'la_mac_dinh',
    ];

    protected $casts = [
        'la_mac_dinh' => 'boolean',
    ];

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'ma_khach_hang', 'ma_khach_hang');
    }

    /**
     * Tự động sinh địa chỉ đầy đủ chuẩn định dạng
     */
    public static function buildDiaChiDayDu(string $chiTiet, string $phuong, string $quan): string
    {
        $ct = trim($chiTiet);
        $p = trim($phuong);
        $q = trim($quan);
        return "{$ct}, {$p}, {$q}, Hà Nội";
    }
}
