<?php

namespace App\Models;

use App\Models\GioHang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NguoiDung extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'nguoi_dung';
    protected $primaryKey = 'ma_nguoi_dung';
    public $incrementing = true; 
    protected $keyType = 'int';

    public const TRANG_THAI_CHO_XAC_THUC = 'CHO_XAC_THUC';
    public const TRANG_THAI_HOAT_DONG    = 'HOAT_DONG';
    public const TRANG_THAI_BI_KHOA      = 'BI_KHOA';

    public $timestamps = false;

    protected $fillable = [
        'ten_dang_nhap',
        'mat_khau',
        'vai_tro',
        'trang_thai',
        'email_verified_at',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected $casts = [
        'vai_tro'           => 'string',
        'trang_thai'        => 'string',
        'email_verified_at' => 'datetime',
    ];

    public function getAuthIdentifierName()
    {
        return 'ma_nguoi_dung';
    }

    public function getAuthPasswordName()
    {
        return 'mat_khau';
    }

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }

    public function khachHang()
    {
        return $this->hasOne(KhachHang::class, 'ma_khach_hang', 'ma_nguoi_dung');
    }

    public function nhanVien()
    {
        return $this->hasOne(NhanVien::class, 'ma_nhan_vien', 'ma_nguoi_dung');
    }

    public function isKhachHang()
    {
        return $this->vai_tro === 'KHACH_HANG';
    }

    public function isNhanVien()
    {
        return in_array($this->vai_tro, ['NHAN_VIEN', 'SHIPPER']);
    }

    public function isAdmin()
    {
        return $this->vai_tro === 'ADMIN';
    }

    public function setMatKhauAttribute($value)
    {
        $this->attributes['mat_khau'] = \Illuminate\Support\Facades\Hash::needsRehash($value) ? bcrypt($value) : $value;
    }

    public function getEmailForVerification(): ?string
    {
        if ($this->isKhachHang() && $this->khachHang) {
            return $this->khachHang->email;
        }
        if ($this->isNhanVien() && $this->nhanVien) {
            return $this->nhanVien->email;
        }
        return null;
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at) && $this->trang_thai === self::TRANG_THAI_HOAT_DONG;
    }

    public function markEmailAsVerified(): bool
    {
        $this->email_verified_at = $this->freshTimestamp();
        $this->trang_thai = self::TRANG_THAI_HOAT_DONG;
        return $this->save();
    }

    public function isChoXacThuc(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_CHO_XAC_THUC;
    }

    public function isHoatDong(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_HOAT_DONG;
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(GioHang::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }
}