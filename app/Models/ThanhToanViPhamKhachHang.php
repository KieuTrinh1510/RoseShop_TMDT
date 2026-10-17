<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThanhToanViPhamKhachHang extends Model
{
    use HasFactory;

    protected $table = 'thanh_toan_vi_pham_khach_hang';
    protected $primaryKey = 'ma_thanh_toan';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'ma_vi_pham',
        'ma_khach_hang',
        'ma_hoa_don',
        'so_tien',
        'phuong_thuc',
        'ma_giao_dich',
        'trang_thai',
        'ghi_chu',
        'thoi_diem_thanh_toan',
    ];

    protected $casts = [
        'so_tien'              => 'decimal:2',
        'thoi_diem_thanh_toan' => 'datetime',
    ];

    // ─── Hằng số trạng thái ───
    public const TT_CHO_THANH_TOAN = 'CHO_THANH_TOAN';
    public const TT_DA_THANH_TOAN  = 'DA_THANH_TOAN';
    public const TT_DA_HUY         = 'DA_HUY';
    public const TT_THAT_BAI       = 'THAT_BAI';

    // ─── Hằng số phương thức ───
    public const PT_VNPAY    = 'VNPAY';
    public const PT_THU_CONG = 'THU_CONG';

    // ─── Relations ───
    public function viPham()
    {
        return $this->belongsTo(
            ViPhamKhachHang::class,
            'ma_vi_pham',
            'ma_vi_pham'
        );
    }

    public function khachHang()
    {
        return $this->belongsTo(
            KhachHang::class,
            'ma_khach_hang',
            'ma_khach_hang'
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

    // ─── Scopes ───
    public function scopeChuaThanhToan($query)
    {
        return $query->where('trang_thai', self::TT_CHO_THANH_TOAN);
    }

    public function scopeDaThanhToan($query)
    {
        return $query->where('trang_thai', self::TT_DA_THANH_TOAN);
    }
}