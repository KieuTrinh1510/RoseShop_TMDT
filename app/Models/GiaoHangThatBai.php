<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiaoHangThatBai extends Model
{
    use HasFactory;

    protected $table = 'giao_hang_that_bai';
    protected $primaryKey = 'ma_giao_hang_that_bai';

    protected $fillable = [
        'ma_hoa_don',
        'ma_nhan_vien_giao',
        'loai_loi',
        'ly_do',
        'ghi_chu',
        'thoi_diem_bao',
        'bat_dau_dem_10_phut',
        'ket_thuc_dem_10_phut',
        'trang_thai',
    ];

    protected $casts = [
        'thoi_diem_bao' => 'datetime',
        'bat_dau_dem_10_phut' => 'datetime',
        'ket_thuc_dem_10_phut' => 'datetime',
    ];

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function shipper()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nhan_vien_giao', 'ma_nhan_vien');
    }

    public function hangHoan()
    {
        return $this->hasOne(HangHoan::class, 'ma_giao_hang_that_bai', 'ma_giao_hang_that_bai');
    }
}
