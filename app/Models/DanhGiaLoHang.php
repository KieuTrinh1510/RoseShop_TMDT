<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanhGiaLoHang extends Model
{
    use HasFactory;

    protected $table = 'danh_gia_lo_hang';
    protected $primaryKey = 'ma_danh_gia';
    public $incrementing = true;
    protected $keyType = 'int';

    public const KET_QUA_DAT = 'DAT';
    public const KET_QUA_CO_VAN_DE = 'CO_VAN_DE';
    public const KET_QUA_KHONG_DAT = 'KHONG_DAT';

    protected $fillable = [
        'ma_chi_tiet_nhap',
        'ma_nhan_vien',
        'ket_qua',
        'so_luong_loi',
        'ly_do',
        'ghi_chu',
        'hinh_anh',
        'thoi_gian_danh_gia',
    ];

    protected $casts = [
        'so_luong_loi'        => 'integer',
        'thoi_gian_danh_gia' => 'datetime',
    ];

    public function chiTietNhap()
    {
        return $this->belongsTo(ChiTietNhap::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap');
    }

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nhan_vien', 'ma_nhan_vien');
    }

    public function getTenKetQuaAttribute(): string
    {
        return match ($this->ket_qua) {
            self::KET_QUA_DAT        => 'Đạt',
            self::KET_QUA_CO_VAN_DE  => 'Có vấn đề',
            self::KET_QUA_KHONG_DAT  => 'Không đạt',
            default                  => 'Chưa xác định',
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->ket_qua) {
            self::KET_QUA_DAT        => 'bg-success',
            self::KET_QUA_CO_VAN_DE  => 'bg-warning text-dark',
            self::KET_QUA_KHONG_DAT  => 'bg-danger',
            default                  => 'bg-secondary',
        };
    }
}
