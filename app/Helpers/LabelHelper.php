<?php

namespace App\Helpers;

class LabelHelper
{
    /** Map mã loại sản phẩm → nhãn hiển thị */
    private static array $loaiSanPhamMap = [
        'HOA_TUOI'           => 'Hoa tươi',
        'HOA_PHU'            => 'Hoa phụ',
        'CHAU_HOA_TUOI'      => 'Chậu hoa tươi',
        'CHAU_HOA_KHO'       => 'Chậu hoa khô',
        'HOA_KHO'            => 'Hoa khô',
        'PHU_KIEN'           => 'Phụ kiện',
        'QUA_TANG'           => 'Quà tặng',
        'GIAY_GOI'           => 'Giấy gói',
        'RUY_BANG'           => 'Ruy băng',
        'PHU_KIEN_TRANG_TRI' => 'Phụ kiện trang trí',
        'THIEP'              => 'Thiệp chúc mừng',
        'MIENG_XOP'          => 'Xốp / Mút cắm hoa',
        'SAN_PHAM_PREMIUM'   => 'Sản phẩm cao cấp',
    ];

    public static function loaiSanPham(?string $ma): string
    {
        return self::$loaiSanPhamMap[$ma] ?? ($ma ?? '-');
    }
}
