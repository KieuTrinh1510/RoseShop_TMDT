<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoaDon extends Model
{
    use HasFactory;

    protected $table = 'hoa_don';
    protected $primaryKey = 'ma_hoa_don';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ma_khach_hang',
        'ngay_dat',
        'tong_tien',
        'trang_thai',
        'nguon_huy',
        'ly_do_huy',
        'thoi_diem_huy',
        'co_hang_hoan',
        'trang_thai_thanh_toan',
        'da_thu_tien_mat',
        'phuong_thuc_thanh_toan',
        'dia_chi_giao',
        'so_dien_thoai',
        'ngay_giao',
        'ngay_bat_giao',
        'ngay_den_noi',
        'da_phat_tre',
        'ma_nhan_vien_giao',
        'vnpay_transaction_no',
        'vnpay_bank_code',
        'vnpay_pay_date',
        'vnpay_response_code',
        'vnpay_cart_snapshot',
    ];

    protected $casts = [
        'tong_tien' => 'decimal:2',
        'ngay_dat' => 'datetime',
        'ngay_giao' => 'datetime',
        'ngay_bat_giao' => 'datetime',
        'ngay_den_noi' => 'datetime',
        'thoi_diem_huy' => 'datetime',
        'co_hang_hoan' => 'boolean',
        'vnpay_cart_snapshot' => 'array',
    ];
    /**
 * Trả về danh sách chi tiết cần hiển thị:
 *   - Nếu hóa đơn có bó hoa (dòng ma_bo_hoa) → chỉ trả các dòng bó.
 *   - Ngược lại → trả tất cả dòng (sản phẩm lẻ).
 *
 * Dòng con của bó (nguyên liệu) KHÔNG bị xóa — chỉ bị ẩn khi hiển thị.
 */
public function getChiTietHienThiAttribute()
{
    $all = $this->chiTietHoaDon;

    $coBo = $all->whereNotNull('ma_bo_hoa')->isNotEmpty();

    return $coBo
        ? $all->whereNotNull('ma_bo_hoa')->values()
        : $all->values();
}

public function getCoBoHoaAttribute(): bool
{
    return $this->chiTietHoaDon
        ->whereNotNull('ma_bo_hoa')
        ->isNotEmpty();
}

    public function chiTietHoaDon()
    {
        return $this->hasMany(ChiTietHoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function khachHang()
    {
        return $this->belongsTo(KhachHang::class, 'ma_khach_hang', 'ma_khach_hang');
    }

    public function shipper()
    {
        return $this->belongsTo(NhanVien::class, 'ma_nhan_vien_giao', 'ma_nhan_vien');
    }

    public function reservations()
    {
        return $this->hasMany(InventoryReservation::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function giaoHangThatBai()
    {
        return $this->hasMany(GiaoHangThatBai::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function hangHoan()
    {
        return $this->hasOne(HangHoan::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function phatShipper()
    {
        return $this->hasMany(PhatShipper::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function boHoas()
    {
        return $this->hasMany(BoHoa::class, 'ma_hoa_don', 'ma_hoa_don');
    }
}
