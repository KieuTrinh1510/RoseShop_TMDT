<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuCauDatLaiMatKhau extends Model
{
    use HasFactory;

    protected $table = 'yeu_cau_dat_lai_mat_khau';

    public const TRANG_THAI_CO_HIEU_LUC   = 'CO_HIEU_LUC';
    public const TRANG_THAI_DA_SU_DUNG    = 'DA_SU_DUNG';
    public const TRANG_THAI_VO_HIEU_HOA   = 'VO_HIEU_HOA';

    protected $fillable = [
        'ma_nguoi_dung',
        'email',
        'ma_xac_thuc',
        'token',
        'trang_thai',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function isCoHieuLuc(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_CO_HIEU_LUC;
    }

    public function isValid(): bool
    {
        return $this->isCoHieuLuc() && ! $this->isExpired();
    }
}
