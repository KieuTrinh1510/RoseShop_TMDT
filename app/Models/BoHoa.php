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
        'gia_ban',
        'loai_bo_hoa',
        'kieu_bo_hoa',
        'size',
        'concept',
        'loi_nhan',
        'hinh_anh',
        'trang_thai',
    ];

    protected $casts = [
        'ma_khach_hang' => 'integer',
        'ma_mau_bo_hoa' => 'integer',
        'ma_hoa_don'    => 'integer',
        'so_luong'      => 'integer',
        'gia_ban'       => 'decimal:2',
        'loai_bo_hoa'   => 'string',
        'kieu_bo_hoa'   => 'string',
        'size'          => 'string',
        'concept'       => 'string',
        'trang_thai'    => 'string',
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

    public function reservations()
    {
        return $this->hasMany(
            InventoryReservation::class,
            'ma_bo_hoa',
            'ma_bo_hoa'
        );
    }
}