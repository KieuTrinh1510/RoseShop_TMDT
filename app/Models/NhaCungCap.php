<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhaCungCap extends Model
{
    use HasFactory;

    protected $table = 'nha_cung_cap';
    protected $primaryKey = 'ma_nha_cung_cap';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    public const TRANG_THAI_DANG_HOP_TAC = 'DANG_HOP_TAC';
    public const TRANG_THAI_NGUNG_HOP_TAC = 'NGUNG_HOP_TAC';

    protected $fillable = [
        'ten_nha_cung_cap',
        'so_dien_thoai',
        'email',
        'dia_chi',
        'trang_thai',
    ];

    public function phieuNhaps()
    {
        return $this->hasMany(PhieuNhap::class, 'ma_nha_cung_cap', 'ma_nha_cung_cap');
    }

    public function chiTietNhaps()
    {
        return $this->hasManyThrough(
            ChiTietNhap::class,
            PhieuNhap::class,
            'ma_nha_cung_cap',
            'ma_phieu_nhap',
            'ma_nha_cung_cap',
            'ma_phieu_nhap'
        );
    }

    public function getThongKeChatLuongAttribute(): array
    {
        $tongPhieuNhap = $this->phieuNhaps()->count();
        $lots = $this->chiTietNhaps()->with(['danhGiaMoiNhat', 'baoCaoHangHongs'])->get();
        $tongSoLo = $lots->count();

        $soLoDaDanhGia = 0;
        $soLoDat = 0;
        $soLoCoVanDe = 0;
        $soLoKhongDat = 0;

        foreach ($lots as $lot) {
            $dg = $lot->danhGiaMoiNhat;
            if ($dg) {
                $soLoDaDanhGia++;
                if ($dg->ket_qua === DanhGiaLoHang::KET_QUA_DAT) {
                    $soLoDat++;
                } elseif ($dg->ket_qua === DanhGiaLoHang::KET_QUA_CO_VAN_DE) {
                    $soLoCoVanDe++;
                } elseif ($dg->ket_qua === DanhGiaLoHang::KET_QUA_KHONG_DAT) {
                    $soLoKhongDat++;
                }
            }
        }

        $tyLeKhongDat = $soLoDaDanhGia > 0
            ? round(($soLoKhongDat / $soLoDaDanhGia) * 100, 2)
            : null;

        $tongLuongHong = (int) $lots->sum(function ($lot) {
            return $lot->baoCaoHangHongs->sum('so_luong_hong');
        });

        return [
            'tong_phieu_nhap'   => $tongPhieuNhap,
            'tong_so_lo'        => $tongSoLo,
            'so_lo_da_danh_gia' => $soLoDaDanhGia,
            'so_lo_dat'         => $soLoDat,
            'so_lo_co_van_de'   => $soLoCoVanDe,
            'so_lo_khong_dat'   => $soLoKhongDat,
            'ty_le_khong_dat'   => $tyLeKhongDat,
            'tong_luong_hong'   => $tongLuongHong,
        ];
    }

    public function scopeDangHopTac($query)
    {
        return $query->where('trang_thai', self::TRANG_THAI_DANG_HOP_TAC);
    }

    public function scopeSearch($query, ?string $keyword)
    {
        if (!$keyword) {
            return $query;
        }

        $keyword = trim($keyword);

        return $query->where(function ($q) use ($keyword) {
            $q->where('ten_nha_cung_cap', 'LIKE', '%' . $keyword . '%')
              ->orWhere('so_dien_thoai', 'LIKE', '%' . $keyword . '%')
              ->orWhere('email', 'LIKE', '%' . $keyword . '%');

            if (is_numeric($keyword)) {
                $q->orWhere('ma_nha_cung_cap', (int) $keyword);
            }
        });
    }

    public function isDangHopTac(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_DANG_HOP_TAC;
    }
}
