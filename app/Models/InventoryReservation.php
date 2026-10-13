<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    protected $table = 'inventory_reservations';

    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_CONSUMED = 'CONSUMED';
    public const STATUS_RELEASED = 'RELEASED';

    protected $fillable = [
        'ma_hoa_don',
        'ma_san_pham',
        'ma_chi_tiet_nhap',
        'ma_bo_hoa',
        'so_luong',
        'trang_thai',
    ];

    protected $casts = [
        'so_luong' => 'integer',
    ];

    public function hoaDon(): BelongsTo
    {
        return $this->belongsTo(HoaDon::class, 'ma_hoa_don', 'ma_hoa_don');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'ma_san_pham', 'ma_san_pham');
    }

    public function chiTietNhap(): BelongsTo
    {
        return $this->belongsTo(ChiTietNhap::class, 'ma_chi_tiet_nhap', 'ma_chi_tiet_nhap');
    }

    public function boHoa(): BelongsTo
    {
        return $this->belongsTo(BoHoa::class, 'ma_bo_hoa', 'ma_bo_hoa');
    }

    public function scopeReserved($query)
    {
        return $query->where('trang_thai', self::STATUS_RESERVED);
    }

    public function scopeConsumed($query)
    {
        return $query->where('trang_thai', self::STATUS_CONSUMED);
    }

    public function scopeReleased($query)
    {
        return $query->where('trang_thai', self::STATUS_RELEASED);
    }

    public function isReserved(): bool
    {
        return $this->trang_thai === self::STATUS_RESERVED;
    }

    public function isConsumed(): bool
    {
        return $this->trang_thai === self::STATUS_CONSUMED;
    }

    public function isReleased(): bool
    {
        return $this->trang_thai === self::STATUS_RELEASED;
    }
}
