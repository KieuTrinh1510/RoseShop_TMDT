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

    // Các bó hoa thực tế đã được tạo dựa trên mẫu này
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
}