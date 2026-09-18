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
        'ma_bo_hoa',
        'ma_san_pham',
        'ma_chi_tiet_nhap',
        'vai_tro',
        'so_luong',
    ];

    protected $casts = [
        'ma_bo_hoa'        => 'integer',
        'ma_san_pham'      => 'integer',
        'ma_chi_tiet_nhap' => 'integer',
        'vai_tro'          => 'string',
        'so_luong'         => 'integer',
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

    public function scopeTheoVaiTro($query, string $vaiTro)
    {
        return $query->where('vai_tro', $vaiTro);
    }

    public function scopeXop($query)
    {
        return $query->where('vai_tro', self::VAI_TRO_XOP);
    }
}