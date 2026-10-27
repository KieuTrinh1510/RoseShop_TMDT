<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KiemTraChatLuong extends Model
{
    use HasFactory;

    protected $table = 'kiem_tra_chat_luong';

    protected $fillable = [
        'doi_tuong_loai',
        'doi_tuong_id',
        'ma_san_pham',
        'ma_chi_tiet_nhap',
        'ma_bo_hoa',
        'ma_flash_sale',
        'ket_qua',
        'ghi_chu',
        'ma_nhan_vien',
        'ma_nguoi_dung',
        'hsd_luc_kiem_tra',
        'thoi_gian_kiem_tra',
        'thoi_gian_cu',
        'thoi_gian_moi',
    ];

    protected $casts = [
        'thoi_gian_kiem_tra' => 'datetime',
        'thoi_gian_cu'       => 'datetime',
        'thoi_gian_moi'      => 'datetime',
        'hsd_luc_kiem_tra'   => 'date',
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'ma_san_pham', 'ma_san_pham');
    }

    public function chiTietNhap()
    {
        return $this->belongsTo(ChiTietNhap::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap');
    }

    public function boHoa()
    {
        return $this->belongsTo(BoHoa::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class, 'ma_flash_sale', 'ma_flash_sale');
    }

    public function nhanVien()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nhan_vien', 'ma_nhan_vien');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }
}
