<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    use HasFactory;

    protected $table = 'flash_sales';
    protected $primaryKey = 'ma_flash_sale';

    protected $fillable = [
        'ma_san_pham',
        'ma_bo_hoa',
        'ma_chi_tiet_hang_hoan',
        'gia_goc',
        'gia_flash_sale',
        'so_tien_giam',
        'so_luong',
        'so_luong_da_ban',
        'bat_dau',
        'ket_thuc',
        'trang_thai',
    ];

    protected $casts = [
        'gia_goc' => 'decimal:2',
        'gia_flash_sale' => 'decimal:2',
        'so_tien_giam' => 'decimal:2',
        'so_luong' => 'integer',
        'so_luong_da_ban' => 'integer',
        'bat_dau' => 'datetime',
        'ket_thuc' => 'datetime',
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'ma_san_pham', 'ma_san_pham');
    }

    public function boHoa()
    {
        return $this->belongsTo(BoHoa::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function chiTietHangHoan()
    {
        return $this->belongsTo(ChiTietHangHoan::class, 'ma_chi_tiet_hang_hoan', 'ma_chi_tiet_hang_hoan');
    }
}
