<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaoCaoHangHong extends Model
{
    use HasFactory;

    protected $table = 'bao_cao_hang_hong';

    protected $primaryKey = 'ma_bao_cao';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ma_san_pham',
        'ma_chi_tiet_nhap',
        'ma_bo_hoa',
        'ma_nhan_vien',
        'so_luong_hong',
        'gia_tri_thiet_hai',
        'nguon_hong',
        'ma_flash_sale',
        'ma_hoa_don',
        'ly_do',
        'ghi_chu',
        'thoi_gian_bao_cao',
    ];

    protected $casts = [
        'thoi_gian_bao_cao' => 'datetime',
        'so_luong_hong'     => 'integer',
        'gia_tri_thiet_hai' => 'decimal:2',
    ];

    public function sanPham()
    {
        return $this->belongsTo(
            SanPham::class,
            'ma_san_pham',
            'ma_san_pham'
        );
    }

    public function boHoa()
    {
        return $this->belongsTo(
            BoHoa::class,
            'ma_bo_hoa',
            'ma_bo_hoa'
        );
    }

    public function nhanVien()
    {
        return $this->belongsTo(
            NhanVien::class,
            'ma_nhan_vien',
            'ma_nhan_vien'
        );
    }

    public function chiTietNhap()
    {
        return $this->belongsTo(
            ChiTietNhap::class,
            'ma_chi_tiet_nhap',
            'ma_chi_tiet_nhap'
        );
    }

    public function flashSale()
    {
        return $this->belongsTo(
            FlashSale::class,
            'ma_flash_sale',
            'ma_flash_sale'
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
}