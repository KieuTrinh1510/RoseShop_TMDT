<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViPhamKhachHang extends Model
{
    use HasFactory;

    protected $table = 'vi_pham_khach_hang';
    protected $primaryKey = 'ma_vi_pham';

    protected $fillable = [
        'ma_khach_hang',
        'ma_hoa_don',
        'ma_giao_hang_that_bai',
        'ly_do',
        'lan_vi_pham',
        'han_che_ap_dung',
        // [MỚI] Soft delete
        'da_go',
        'ly_do_go',
        'nguoi_go',
        'thoi_diem_go',
    ];

    protected $casts = [
        'lan_vi_pham'   => 'integer',
        'da_go'         => 'boolean',
        'thoi_diem_go'  => 'datetime',
    ];

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'ma_khach_hang', 'ma_khach_hang');
    }

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function giaoHangThatBai()
    {
        return $this->belongsTo(GiaoHangThatBai::class, 'ma_giao_hang_that_bai', 'ma_giao_hang_that_bai');
    }

    // [MỚI] Các giao dịch thanh toán vi phạm
    public function thanhToanViPham()
    {
        return $this->hasMany(
            ThanhToanViPhamKhachHang::class,
            'ma_vi_pham',
            'ma_vi_pham'
        )->orderByDesc('created_at');
    }

    // [MỚI] Scope: chỉ vi phạm chưa bị gỡ
    public function scopeChuaGo($query)
    {
        return $query->where('da_go', false);
    }

    // [MỚI] Helper: đã được thanh toán đủ chưa?
    public function daThanhToan(): bool
    {
        return $this->thanhToanViPham()
            ->where('trang_thai', ThanhToanViPhamKhachHang::TT_DA_THANH_TOAN)
            ->exists();
    }
}