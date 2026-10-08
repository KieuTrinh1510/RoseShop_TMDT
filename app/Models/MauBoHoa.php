<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MauBoHoa extends Model
{
    use HasFactory;

    protected $table = 'mau_bo_hoa';

    protected $primaryKey = 'ma_mau_bo_hoa';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'ten_mau',
        'mo_ta',
        'kieu_bo_hoa',
        'size',
        'concept',
        'trang_thai',
    ];

    protected $casts = [
        'kieu_bo_hoa' => 'string',
        'size'        => 'string',
        'concept'     => 'string',
        'trang_thai'  => 'string',
    ];

    public function chiTietMauBoHoa()
    {
        return $this->hasMany(
            ChiTietMauBoHoa::class,
            'ma_mau_bo_hoa',
            'ma_mau_bo_hoa'
        );
    }

    public function boHoas()
    {
        return $this->hasMany(
            BoHoa::class,
            'ma_mau_bo_hoa',
            'ma_mau_bo_hoa'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('trang_thai', 'ACTIVE');
    }

    public function scopeDraft($query)
    {
        return $query->where('trang_thai', 'DRAFT');
    }

    public function scopeInactive($query)
    {
        return $query->where('trang_thai', 'INACTIVE');
    }

    public function getTonThucTeAttribute(): int
    {
        return (int) $this->boHoas()
            ->where('loai_bo_hoa', 'HE_THONG')
            ->where('trang_thai', 'ACTIVE')
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->sum('so_luong');
    }

    public function getTonDangGiuAttribute(): int
    {
        $boIds = $this->boHoas()
            ->where('loai_bo_hoa', 'HE_THONG')
            ->where('trang_thai', 'ACTIVE')
            ->whereNull('ma_hoa_don')
            ->where('so_luong', '>', 0)
            ->pluck('ma_bo_hoa');

        if ($boIds->isEmpty()) {
            return 0;
        }

        return (int) \App\Models\InventoryReservation::whereIn('ma_bo_hoa', $boIds)
            ->where('trang_thai', \App\Models\InventoryReservation::STATUS_RESERVED)
            ->sum('so_luong');
    }

    public function getTonKhaDungAttribute(): int
    {
        return max(0, $this->ton_thuc_te - $this->ton_dang_giu);
    }
}