<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietBoHoa extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_bo_hoa';

    protected $primaryKey = 'ma_chi_tiet';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ma_bo_hoa',
        'ma_san_pham',
        'ma_chi_tiet_nhap',
        'vai_tro',
        'so_luong',
    ];

    protected $casts = [
        'vai_tro'  => 'string',
        'so_luong' => 'integer',
    ];

    public function boHoa()
    {
        return $this->belongsTo(
            BoHoa::class,
            'ma_bo_hoa',
            'ma_bo_hoa'
        );
    }

    public function sanPham()
    {
        return $this->belongsTo(
            SanPham::class,
            'ma_san_pham',
            'ma_san_pham'
        );
    }

    public function chiTietNhap()
    {
        return $this->belongsTo(
            ChiTietNhap::class,
            'ma_chi_tiet_nhap',
            'ma_chi_tiet_nhap'
        );
    }

    // Chưa phân bổ lô (còn ở trạng thái DRAFT)
    public function scopeChuaCoLo($query)
    {
        return $query->whereNull('ma_chi_tiet_nhap');
    }

    // Đã phân bổ lô (đã ACTIVE, đã trừ tồn kho)
    public function scopeDaCoLo($query)
    {
        return $query->whereNotNull('ma_chi_tiet_nhap');
    }
}