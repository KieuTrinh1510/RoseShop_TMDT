<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HangHoan extends Model
{
    use HasFactory;

    protected $table = 'hang_hoan';
    protected $primaryKey = 'ma_hang_hoan';

    protected $fillable = [
        'ma_hoa_don',
        'ma_giao_hang_that_bai',
        'trang_thai',
        'nguoi_kiem_tra',
        'thoi_diem_nhan_tai_kho',
        'thoi_diem_kiem_tra',
        'ghi_chu',
    ];

    protected $casts = [
        'thoi_diem_nhan_tai_kho' => 'datetime',
        'thoi_diem_kiem_tra' => 'datetime',
    ];

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function giaoHangThatBai()
    {
        return $this->belongsTo(GiaoHangThatBai::class, 'ma_giao_hang_that_bai', 'ma_giao_hang_that_bai');
    }

    public function nguoiKiemTra()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_kiem_tra', 'ma_nguoi_dung');
    }

    public function chiTiet()
    {
        return $this->hasMany(ChiTietHangHoan::class, 'ma_hang_hoan', 'ma_hang_hoan');
    }
}
