<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('giao_hang_that_bai', function (Blueprint $table) {
            $table->unique(
                'ma_hoa_don',
                'giao_hang_that_bai_ma_hoa_don_unique'
            );
        });

        Schema::table('vi_pham_khach_hang', function (Blueprint $table) {
            $table->unique(
                'ma_hoa_don',
                'vi_pham_khach_hang_ma_hoa_don_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('giao_hang_that_bai', function (Blueprint $table) {
            $table->dropUnique('giao_hang_that_bai_ma_hoa_don_unique');
        });

        Schema::table('vi_pham_khach_hang', function (Blueprint $table) {
            $table->dropUnique('vi_pham_khach_hang_ma_hoa_don_unique');
        });
    }
};