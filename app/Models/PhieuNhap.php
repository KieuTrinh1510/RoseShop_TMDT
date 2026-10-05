<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuNhap extends Model
{
    use HasFactory;

    protected $table = 'phieu_nhap';
    protected $primaryKey = 'ma_phieu_nhap';
    public $incrementing = true;
    protected $keyType = 'int';

    public const TRANG_THAI_DRAFT = 'DRAFT';
    public const TRANG_THAI_CONFIRMED = 'CONFIRMED';
    public const TRANG_THAI_CANCELLED = 'CANCELLED';

    public $timestamps = true;

    public function isDraft(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_CANCELLED;
    }

    protected $fillable = [
        'ngay_nhap',
        'ma_nhan_vien',
        'ma_nha_cung_cap',
        'ten_nha_cung_cap',
        'so_dien_thoai_ncc',
        'email_ncc',
        'dia_chi_ncc',
        'trang_thai',
        'ly_do_huy',
        'nguoi_huy_id',
        'huy_luc',
    ];

    protected $casts = [
        'ngay_nhap' => 'datetime',
        'huy_luc'   => 'datetime',
    ];

    public function nhaCungCap()
    {
        return $this->belongsTo(NhaCungCap::class, 'ma_nha_cung_cap', 'ma_nha_cung_cap');
    }

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nhan_vien', 'ma_nhan_vien');
    }

    public function nguoiHuy()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_huy_id', 'ma_nguoi_dung');
    }

    public function getTenNguoiHuyAttribute(): string
    {
        if (!$this->nguoiHuy) {
            return '—';
        }
        return $this->nguoiHuy->nhanVien?->ten_nhan_vien 
            ?? $this->nguoiHuy->ten_dang_nhap 
            ?? ('User #' . $this->nguoi_huy_id);
    }

    public function chiTietNhaps()
    {
        return $this->hasMany(ChiTietNhap::class, 'ma_phieu_nhap', 'ma_phieu_nhap');
    }
}