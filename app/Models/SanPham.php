<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanPham extends Model
{
    use HasFactory;

    protected $table = 'san_pham';

    protected $primaryKey = 'ma_san_pham';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    // Loại sản phẩm
    public const LOAI_HOA_TUOI           = 'HOA_TUOI';
    public const LOAI_HOA_GIA            = 'HOA_GIA';
    public const LOAI_SAN_PHAM_PREMIUM   = 'SAN_PHAM_PREMIUM';
    public const LOAI_CHAU_HOA_GIA       = 'CHAU_HOA_GIA';
    public const LOAI_CHAU_HOA_TUOI      = 'CHAU_HOA_TUOI';
    public const LOAI_CAY_CANH           = 'CAY_CANH';
    public const LOAI_HOA_SAP            = 'HOA_SAP';
    public const LOAI_HOA_GIAY_NHUN      = 'HOA_GIAY_NHUN';
    public const LOAI_TERRARIUM          = 'TERRARIUM';
    public const LOAI_GIAY_GOI           = 'GIAY_GOI';
    public const LOAI_RUY_BANG           = 'RUY_BANG';
    public const LOAI_PHU_KIEN_TRANG_TRI = 'PHU_KIEN_TRANG_TRI';
    public const LOAI_THIEP              = 'THIEP';
    public const LOAI_QUA_TANG           = 'QUA_TANG';
    public const LOAI_PHU_KIEN           = 'PHU_KIEN';
    public const LOAI_MIENG_XOP          = 'MIENG_XOP';

    public const DVT_CANH  = 'CANH';
    public const DVT_CAI   = 'CAI';
    public const DVT_CHAU  = 'CHAU';
    public const DVT_TO    = 'TO';
    public const DVT_MET   = 'MET';
    public const DVT_MIENG = 'MIENG';

    protected $fillable = [
        'ten_san_pham',
        'so_luong',
        'don_vi_tinh',
        'loai_san_pham',
        'mo_ta',
        'hinh_anh',
        'trang_thai',
        'gia_ban_hien_tai',
    ];

    protected $casts = [
        'so_luong'         => 'integer',
        'gia_ban_hien_tai' => 'float',
        'loai_san_pham'    => 'string',
        'don_vi_tinh'      => 'string',
        'trang_thai'       => 'string',
    ];

    public function chiTietHoaDons()
    {
        return $this->hasMany(
            ChiTietHoaDon::class,
            'ma_san_pham',
            'ma_san_pham'
        );
    }

    public function chiTietNhaps()
    {
        return $this->hasMany(
            ChiTietNhap::class,
            'ma_san_pham',
            'ma_san_pham'
        );
    }

    public function chiTietBoHoas()
    {
        return $this->hasMany(
            ChiTietBoHoa::class,
            'ma_san_pham',
            'ma_san_pham'
        );
    }

    public function chiTietMauBoHoas()
    {
        return $this->hasMany(
            ChiTietMauBoHoa::class,
            'ma_san_pham',
            'ma_san_pham'
        );
    }

    public function getAnhAttribute(): string
    {
        return asset(
            $this->hinh_anh ?? 'img/default.jpg'
        );
    }

    public function getDonViTinhTextAttribute(): string
    {
        return match ($this->don_vi_tinh) {
            self::DVT_CANH  => 'Cành',
            self::DVT_CAI   => 'Cái',
            self::DVT_CHAU  => 'Chậu',
            self::DVT_TO    => 'Tờ',
            self::DVT_MET   => 'Mét',
            self::DVT_MIENG => 'Miếng',
            default         => '',
        };
    }

    public function scopeDangBan($query)
    {
        return $query->where('trang_thai', 'DANG_BAN');
    }

    public function scopeNgungBan($query)
    {
        return $query->where('trang_thai', 'NGUNG_BAN');
    }

    public function scopeTheoLoai($query, string $loai)
    {
        return $query->where('loai_san_pham', $loai);
    }

    public function scopeXop($query)
    {
        return $query->where('loai_san_pham', self::LOAI_MIENG_XOP);
    }
}