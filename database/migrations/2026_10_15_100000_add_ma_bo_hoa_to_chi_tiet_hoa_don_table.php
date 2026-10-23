<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chi_tiet_hoa_don', function (Blueprint $table) {
            // Bổ sung cột để lưu bó hoa FlashSale (không có ma_san_pham)
            $table->unsignedBigInteger('ma_bo_hoa')->nullable()->after('ma_san_pham');
            $table->foreign('ma_bo_hoa')
                ->references('ma_bo_hoa')
                ->on('bo_hoa')
                ->nullOnDelete();

            $table->index(['ma_bo_hoa'], 'idx_cthd_bo_hoa');
        });
    }

    public function down(): void
    {
        Schema::table('chi_tiet_hoa_don', function (Blueprint $table) {
            $table->dropIndex('idx_cthd_bo_hoa');
            $table->dropForeign(['ma_bo_hoa']);
            $table->dropColumn('ma_bo_hoa');
        });
    }
};