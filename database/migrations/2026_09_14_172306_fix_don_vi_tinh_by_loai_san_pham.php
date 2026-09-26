<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chuẩn hoá lại don_vi_tinh cho toàn bộ san_pham dựa theo loai_san_pham.
     * Sửa dữ liệu cũ bị sai do trước đây lấy đơn vị từ form thay vì suy ra.
     */
    public function up(): void
    {
        $map = [
            // Hoa & cây
            'HOA_TUOI'           => 'CANH',
            'HOA_GIA'            => 'CANH',
            'HOA_SAP'            => 'CANH',
            'HOA_GIAY_NHUN'      => 'CANH',
            'CHAU_HOA_GIA'       => 'CHAU',
            'CHAU_HOA_TUOI'      => 'CHAU',
            'CAY_CANH'           => 'CHAU',

            // Sản phẩm bán ra khác
            'SAN_PHAM_PREMIUM'   => 'CAI',
            'TERRARIUM'          => 'CAI',
            'QUA_TANG'           => 'CAI',
            'PHU_KIEN'           => 'CAI',

            // Nguyên vật liệu
            'GIAY_GOI'           => 'TO',
            'RUY_BANG'           => 'MET',
            'PHU_KIEN_TRANG_TRI' => 'CAI',
            'THIEP'              => 'CAI',
            'MIENG_XOP'          => 'MIENG',
        ];

        foreach ($map as $loai => $donVi) {
            DB::table('san_pham')
                ->where('loai_san_pham', $loai)
                ->update(['don_vi_tinh' => $donVi]);
        }
    }

    public function down(): void
    {
        // Không rollback để tránh ghi đè đơn vị đúng về giá trị sai cũ.
    }
};