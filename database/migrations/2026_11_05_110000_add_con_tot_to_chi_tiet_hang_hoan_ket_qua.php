<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE chi_tiet_hang_hoan MODIFY COLUMN ket_qua ENUM('CHO_KIEM_TRA', 'CON_TOT', 'FLASH_SALE', 'HONG', 'DA_XU_LY') NOT NULL DEFAULT 'CHO_KIEM_TRA'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE chi_tiet_hang_hoan MODIFY COLUMN ket_qua ENUM('CHO_KIEM_TRA', 'HONG', 'FLASH_SALE', 'DA_XU_LY') NOT NULL DEFAULT 'CHO_KIEM_TRA'");
    }
};

