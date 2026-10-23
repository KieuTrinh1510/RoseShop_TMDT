<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhatShipper extends Model
{
    use HasFactory;

    protected $table = 'phat_shipper';
    protected $primaryKey = 'ma_phat_shipper';

    protected $fillable = [
        'ma_nhan_vien',
        'ma_hoa_don',
        'ma_giao_hang_that_bai',
        'so_tien',
        'tien_hang_hong',
        'tien_hao_hut_flash_sale',
        'ly_do',
        'trang_thai',
    ];

    protected $casts = [
        'so_tien' => 'decimal:2',
        'tien_hang_hong' => 'decimal:2',
        'tien_hao_hut_flash_sale' => 'decimal:2',
    ];

    public function shipper()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nhan_vien', 'ma_nhan_vien');
    }

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function giaoHangThatBai()
    {
        return $this->belongsTo(GiaoHangThatBai::class, 'ma_giao_hang_that_bai', 'ma_giao_hang_that_bai');
    }
}
