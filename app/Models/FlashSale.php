<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    use HasFactory;

    protected $table = 'flash_sales';
    protected $primaryKey = 'ma_flash_sale';

    protected $fillable = [
        'ma_san_pham',
        'ma_chi_tiet_nhap',
        'ma_bo_hoa',
        'ma_chi_tiet_hang_hoan',
        'gia_goc',
        'gia_flash_sale',
        'so_tien_giam',
        'ti_le_giam',
        'so_luong',
        'so_luong_da_ban',
        'bat_dau',
        'ket_thuc',
        'trang_thai',
        'nguon',
        'nguoi_tao',
    ];

    protected $casts = [
        'gia_goc' => 'decimal:2',
        'gia_flash_sale' => 'decimal:2',
        'so_tien_giam' => 'decimal:2',
        'ti_le_giam' => 'integer',
        'so_luong' => 'integer',
        'so_luong_da_ban' => 'integer',
        'bat_dau' => 'datetime',
        'ket_thuc' => 'datetime',
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

    public function chiTietHangHoan()
    {
        return $this->belongsTo(ChiTietHangHoan::class, 'ma_chi_tiet_hang_hoan', 'ma_chi_tiet_hang_hoan');
    }

    public function nguoiTao()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao', 'ma_nguoi_dung');
    }

    public function kiemTraChatLuongs()
    {
        return $this->hasMany(KiemTraChatLuong::class, 'ma_flash_sale', 'ma_flash_sale');
    }

    public function inventoryReservations()
    {
        return $this->hasMany(InventoryReservation::class, 'ma_flash_sale', 'ma_flash_sale');
    }

    public function getReservedQuantityAttribute(): int
    {
        return (int) InventoryReservation::where('ma_flash_sale', $this->ma_flash_sale)
            ->where('trang_thai', InventoryReservation::STATUS_RESERVED)
            ->sum('so_luong');
    }

    public function getAvailableQuotaAttribute(): int
    {
        $consumed = (int) $this->so_luong_da_ban;
        $reserved = $this->reserved_quantity;
        return max(0, (int) $this->so_luong - $consumed - $reserved);
    }

    public function getPhanTramGiamAttribute(): int
    {
        if (!is_null($this->ti_le_giam) && (int) $this->ti_le_giam > 0) {
            return (int) $this->ti_le_giam;
        }
        $giaGoc = (float) $this->gia_goc;
        $giaSale = (float) $this->gia_flash_sale;
        if ($giaGoc > 0 && $giaSale < $giaGoc) {
            return (int) round((($giaGoc - $giaSale) / $giaGoc) * 100);
        }
        return 0;
    }

    public function isCurrentlyActive(): bool
    {
        $now = now();
        return $this->trang_thai === 'DANG_BAN'
            && (!$this->bat_dau || $this->bat_dau <= $now)
            && (!$this->ket_thuc || $this->ket_thuc >= $now)
            && $this->available_quota > 0;
    }
}

