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

    // Bảng bo_hoa đã có created_at/updated_at (cần cho FIFO xóa DRAFT cũ nhất)
    public $timestamps = true;

    protected $fillable = [
        'ma_khach_hang',
        'ma_mau_bo_hoa',
        'ten_bo_hoa',
        'so_luong',
        'loai_bo_hoa',
        'kieu_bo_hoa',
        'size',
        'concept',
        'loi_nhan',
        'trang_thai',
    ];

    protected $casts = [
        'so_luong'     => 'integer',
        'loai_bo_hoa'  => 'string',
        'kieu_bo_hoa'  => 'string',
        'size'         => 'string',
        'trang_thai'   => 'string',
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
}