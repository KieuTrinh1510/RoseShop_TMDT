<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE giao_hang_that_bai
            MODIFY trang_thai ENUM(
                'DA_BAO',
                'CHO_HOAN_HANG',
                'DA_TIEP_NHAN',
                'DA_XU_LY',
                'DANG_CHO_KHACH',
                'KHACH_DA_NHAN'
            ) NOT NULL DEFAULT 'DA_BAO'
        ");
    }

    public function down(): void
    {
        DB::table('giao_hang_that_bai')
            ->whereIn('trang_thai', ['DANG_CHO_KHACH', 'KHACH_DA_NHAN'])
            ->update(['trang_thai' => 'DA_BAO']);

        DB::statement("
            ALTER TABLE giao_hang_that_bai
            MODIFY trang_thai ENUM(
                'DA_BAO',
                'CHO_HOAN_HANG',
                'DA_TIEP_NHAN',
                'DA_XU_LY'
            ) NOT NULL DEFAULT 'DA_BAO'
        ");
    }
};