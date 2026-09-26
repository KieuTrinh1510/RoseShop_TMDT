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

    
    public const VAI_TRO_HOA_CHINH          = 'HOA_CHINH';
    public const VAI_TRO_HOA_TRANG_TRI      = 'HOA_TRANG_TRI';
    public const VAI_TRO_GIAY_GOI           = 'GIAY_GOI';
    public const VAI_TRO_RUY_BANG           = 'RUY_BANG';
    public const VAI_TRO_PHU_KIEN_TRANG_TRI = 'PHU_KIEN_TRANG_TRI';
    public const VAI_TRO_THIEP              = 'THIEP';
    public const VAI_TRO_XOP                = 'XOP';

    public const VAI_TRO = [
        self::VAI_TRO_HOA_CHINH,
        self::VAI_TRO_HOA_TRANG_TRI,
        self::VAI_TRO_GIAY_GOI,
        self::VAI_TRO_RUY_BANG,
        self::VAI_TRO_PHU_KIEN_TRANG_TRI,
        self::VAI_TRO_THIEP,
        self::VAI_TRO_XOP,
    ];

    protected $fillable = [
        'ma_mau_bo_hoa',
        'ma_san_pham',
        'vai_tro',
        'so_luong',
    ];

    protected $casts = [
        'ma_mau_bo_hoa' => 'integer',
        'ma_san_pham'   => 'integer',
        'vai_tro'       => 'string',
        'so_luong'      => 'integer',
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

    public function scopeTheoVaiTro($query, string $vaiTro)
    {
        return $query->where('vai_tro', $vaiTro);
    }

    public function scopeXop($query)
    {
        return $query->where('vai_tro', self::VAI_TRO_XOP);
    }
}