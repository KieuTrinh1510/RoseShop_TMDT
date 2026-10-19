<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietHoaDon extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_hoa_don';
    protected $primaryKey = 'ma_chi_tiet';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ma_hoa_don',
        'ma_san_pham',
        'ma_bo_hoa',
        'ma_chi_tiet_nhap',
        'so_luong',
        'gia_nhap_snapshot',
        'gia_ban_snapshot',
    ];

    protected $casts = [
        'so_luong'          => 'integer',
        'ma_san_pham'       => 'integer',
        'ma_bo_hoa'         => 'integer',
        'gia_nhap_snapshot' => 'decimal:2',
        'gia_ban_snapshot'  => 'decimal:2',
    ];

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'ma_san_pham', 'ma_san_pham');
    }

    public function boHoa()
    {
        return $this->belongsTo(BoHoa::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function chiTietNhap()
    {
        return $this->belongsTo(ChiTietNhap::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap');
    }

    public function getThanhTienAttribute()
    {
        return $this->so_luong * $this->gia_ban_snapshot;
    }

    /**
     * Tên hiển thị ưu tiên:
     *   1. Bó hoa (ten_bo_hoa → mauBoHoa.ten_mau → fallback mã)
     *   2. Sản phẩm lẻ (ten_san_pham)
     *   3. Fallback theo mã chi tiết
     */
    public function getTenHienThiAttribute(): string
    {
        if ($this->ma_bo_hoa) {
            $bo = $this->relationLoaded('boHoa') ? $this->boHoa : $this->boHoa()->first();

            if ($bo) {
                if (!empty($bo->ten_bo_hoa)) {
                    return $bo->ten_bo_hoa;
                }
                $mau = $bo->relationLoaded('mauBoHoa') ? $bo->mauBoHoa : $bo->mauBoHoa()->first();
                if ($mau?->ten_mau) {
                    return $mau->ten_mau;
                }
            }
            return "Bó hoa #{$this->ma_bo_hoa}";
        }

        if ($this->sanPham) {
            return $this->sanPham->ten_san_pham;
        }

        return "Chi tiết #{$this->ma_chi_tiet}";
    }

    public function getLaBoHoaAttribute(): bool
    {
        return !is_null($this->ma_bo_hoa);
    }
}