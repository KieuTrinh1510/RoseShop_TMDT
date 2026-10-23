<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoHoa extends Model
{
    use HasFactory;

    protected $table = 'bo_hoa';

    protected $primaryKey = 'ma_bo_hoa';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'ma_khach_hang',
        'ma_mau_bo_hoa',
        'ma_hoa_don',
        'ten_bo_hoa',
        'so_luong',
        'gia_von',
        'gia_ban',
        'loai_bo_hoa',
        'kieu_bo_hoa',
        'size',
        'concept',
        'loi_nhan',
        'hinh_anh',
        'trang_thai',
        'ngay_het_han',
    ];

    protected $casts = [
        'ma_khach_hang' => 'integer',
        'ma_mau_bo_hoa' => 'integer',
        'ma_hoa_don'    => 'integer',
        'so_luong'      => 'integer',
        'gia_von'       => 'decimal:2',
        'gia_ban'       => 'decimal:2',
        'loai_bo_hoa'   => 'string',
        'kieu_bo_hoa'   => 'string',
        'size'          => 'string',
        'concept'       => 'string',
        'trang_thai'    => 'string',
        'ngay_het_han'  => 'date',
    ];

    public function khachHang()
    {
        return $this->belongsTo(
            KhachHang::class,
            'ma_khach_hang',
            'ma_khach_hang'
        );
    }

    public function mauBoHoa()
    {
        return $this->belongsTo(
            MauBoHoa::class,
            'ma_mau_bo_hoa',
            'ma_mau_bo_hoa'
        );
    }

    public function hoaDon()
    {
        return $this->belongsTo(
            HoaDon::class,
            'ma_hoa_don',
            'ma_hoa_don'
        );
    }

    public function chiTietBoHoa()
    {
        return $this->hasMany(
            ChiTietBoHoa::class,
            'ma_bo_hoa',
            'ma_bo_hoa'
        );
    }

    public function scopeDraft($query)
    {
        return $query->where('trang_thai', 'DRAFT');
    }

    public function scopeActive($query)
    {
        return $query->where('trang_thai', 'ACTIVE');
    }

    public function scopeTuyChon($query)
    {
        return $query->where('loai_bo_hoa', 'TUY_CHON');
    }

    public function scopeHeThong($query)
    {
        return $query->where('loai_bo_hoa', 'HE_THONG');
    }

    public function customMixImages()
    {
        return $this->hasMany(
            CustomMixImage::class,
            'ma_bo_hoa',
            'ma_bo_hoa'
        )->orderBy('sort_order');
    }

    public function reservations()
    {
        return $this->hasMany(
            InventoryReservation::class,
            'ma_bo_hoa',
            'ma_bo_hoa'
        );
    }

    public function chiTietHangHoan()
    {
        return $this->hasMany(ChiTietHangHoan::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function flashSales()
    {
        return $this->hasMany(FlashSale::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function getTonThucTeAttribute(): int
    {
        return ($this->trang_thai === 'ACTIVE' && is_null($this->ma_hoa_don)) ? (int) $this->so_luong : 0;
    }

    public function getTonDangGiuAttribute(): int
    {
        if ($this->relationLoaded('reservations')) {
            return (int) $this->reservations->where('trang_thai', InventoryReservation::STATUS_RESERVED)->sum('so_luong');
        }
        return (int) $this->reservations()->where('trang_thai', InventoryReservation::STATUS_RESERVED)->sum('so_luong');
    }

    public function getTonKhaDungAttribute(): int
    {
        return max(0, $this->ton_thuc_te - $this->ton_dang_giu);
    }
}
