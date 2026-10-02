<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LichSuViPhamCod extends Model
{
    use HasFactory;

    protected $table = 'lich_su_vi_pham_cod';

    protected $fillable = [
        'ma_khach_hang',
        'ma_hoa_don',
        'loai_vi_pham',
        'ly_do',
        'hanh_dong',
        'nguoi_thuc_hien',
    ];

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'ma_khach_hang', 'ma_khach_hang');
    }

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }
}
