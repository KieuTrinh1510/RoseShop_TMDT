<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietHangHoan extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_hang_hoan';
    protected $primaryKey = 'ma_chi_tiet_hang_hoan';

    protected $fillable = [
        'ma_hang_hoan',
        'ma_san_pham',
        'ma_bo_hoa',
        'ma_chi_tiet_hoa_don',
        'so_luong_theo_don',
        'so_luong_hong',
        'so_luong_con_ban_duoc',
        'so_luong_con_lai_flash_sale',
        'gia_von_snapshot',
        'gia_ban_snapshot',
        'gia_flash_sale_snapshot',
        'doanh_thu_thanh_ly',
        'tien_hao_hut_flash_sale',
        'tien_thiet_hai_hang_hong',
        'tien_he_thong_chiu',
        'tien_shipper_chiu',
        'mien_phat_shipper',
        'ket_qua',
        'ghi_chu',
    ];

    protected $casts = [
        'so_luong_theo_don' => 'integer',
        'so_luong_hong' => 'integer',
        'so_luong_con_ban_duoc' => 'integer',
        'so_luong_con_lai_flash_sale' => 'integer',
        'gia_von_snapshot' => 'decimal:2',
        'gia_ban_snapshot' => 'decimal:2',
        'gia_flash_sale_snapshot' => 'decimal:2',
        'doanh_thu_thanh_ly' => 'decimal:2',
        'tien_hao_hut_flash_sale' => 'decimal:2',
        'tien_thiet_hai_hang_hong' => 'decimal:2',
        'tien_he_thong_chiu' => 'decimal:2',
        'tien_shipper_chiu' => 'decimal:2',
        'mien_phat_shipper' => 'boolean',
    ];

    public function hangHoan()
    {
        return $this->belongsTo(HangHoan::class, 'ma_hang_hoan', 'ma_hang_hoan');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'ma_san_pham', 'ma_san_pham');
    }

    public function boHoa()
    {
        return $this->belongsTo(BoHoa::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function chiTietHoaDon()
    {
        return $this->belongsTo(ChiTietHoaDon::class, 'ma_chi_tiet_hoa_don', 'ma_chi_tiet');
    }

    public function flashSales()
    {
        return $this->hasMany(FlashSale::class, 'ma_chi_tiet_hang_hoan', 'ma_chi_tiet_hang_hoan');
    }
}
