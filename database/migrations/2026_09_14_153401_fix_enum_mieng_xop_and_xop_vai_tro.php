<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    
    public function up(): void
    {
        // 1. san_pham.loai_san_pham — thêm MIENG_XOP
        DB::statement("
            ALTER TABLE `san_pham` MODIFY COLUMN `loai_san_pham` ENUM(
                'HOA_TUOI',
                'HOA_GIA',
                'SAN_PHAM_PREMIUM',
                'CHAU_HOA_GIA',
                'CHAU_HOA_TUOI',
                'CAY_CANH',
                'HOA_SAP',
                'HOA_GIAY_NHUN',
                'TERRARIUM',
                'GIAY_GOI',
                'RUY_BANG',
                'PHU_KIEN_TRANG_TRI',
                'THIEP',
                'QUA_TANG',
                'PHU_KIEN',
                'MIENG_XOP'
            )
        ");

        // 2. san_pham.don_vi_tinh — thêm MIENG (idempotent, chạy lại vẫn OK)
        DB::statement("
            ALTER TABLE `san_pham` MODIFY COLUMN `don_vi_tinh` ENUM(
                'CANH',
                'CAI',
                'CHAU',
                'TO',
                'MET',
                'MIENG'
            ) DEFAULT 'CAI'
        ");

        // 3. chi_tiet_bo_hoa.vai_tro — thêm XOP
        DB::statement("
            ALTER TABLE `chi_tiet_bo_hoa` MODIFY COLUMN `vai_tro` ENUM(
                'HOA_CHINH',
                'HOA_TRANG_TRI',
                'GIAY_GOI',
                'RUY_BANG',
                'PHU_KIEN_TRANG_TRI',
                'THIEP',
                'XOP'
            )
        ");

        // 4. chi_tiet_mau_bo_hoa.vai_tro — thêm XOP
        DB::statement("
            ALTER TABLE `chi_tiet_mau_bo_hoa` MODIFY COLUMN `vai_tro` ENUM(
                'HOA_CHINH',
                'HOA_TRANG_TRI',
                'GIAY_GOI',
                'RUY_BANG',
                'PHU_KIEN_TRANG_TRI',
                'THIEP',
                'XOP'
            )
        ");
    }

    public function down(): void
    {
        // Không rollback để tránh mất dữ liệu nếu đã có bản ghi dùng MIENG_XOP / XOP
    }
};