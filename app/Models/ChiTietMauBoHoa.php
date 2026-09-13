<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietMauBoHoa extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_mau_bo_hoa';

    protected $primaryKey = 'ma_chi_tiet_mau';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ma_mau_bo_hoa',
        'ma_san_pham',
        'vai_tro',
        'so_luong',
    ];

    protected $casts = [
        'vai_tro'  => 'string',
        'so_luong' => 'integer',
    ];

    public function mauBoHoa()
    {
        return $this->belongsTo(
            MauBoHoa::class,
            'ma_mau_bo_hoa',
            'ma_mau_bo_hoa'
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
}