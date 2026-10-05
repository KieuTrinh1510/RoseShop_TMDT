<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietNhap extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_nhap';
    protected $primaryKey = 'ma_chi_tiet_nhap';
    public $incrementing = true;
    protected $keyType = 'int';

    
    public $timestamps = true;

    protected $fillable = [
        'ma_phieu_nhap',
        'ma_san_pham',
        'so_luong',
        'gia_nhap',
        'gia_ban',          
        'so_luong_con_lai', 
    ];

    protected $casts = [
        'so_luong' => 'integer',
        'gia_nhap' => 'decimal:2',
        'gia_ban' => 'decimal:2',
        'so_luong_con_lai' => 'integer',
    ];

    public function phieuNhap()
    {
        return $this->belongsTo(PhieuNhap::class, 'ma_phieu_nhap', 'ma_phieu_nhap');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'ma_san_pham', 'ma_san_pham');
    }

    public function danhGiaLoHangs()
    {
        return $this->hasMany(DanhGiaLoHang::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap');
    }

    public function danhGiaMoiNhat()
    {
        return $this->hasOne(DanhGiaLoHang::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap')
            ->latestOfMany('thoi_gian_danh_gia');
    }

    public function baoCaoHangHongs()
    {
        return $this->hasMany(BaoCaoHangHong::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap');
    }

    public function reservations()
    {
        return $this->hasMany(InventoryReservation::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap');
    }

    public function getTonThucTeAttribute(): int
    {
        return (int) $this->so_luong_con_lai;
    }

    public function getTonDangGiuAttribute(): int
    {
        if ($this->relationLoaded('reservations')) {
            return (int) $this->reservations->where('trang_thai', InventoryReservation::STATUS_RESERVED)->sum('so_luong');
        }
        return (int) $this->reservations()->reserved()->sum('so_luong');
    }

    public function getTonKhaDungAttribute(): int
    {
        return max(0, $this->ton_thuc_te - $this->ton_dang_giu);
    }
}