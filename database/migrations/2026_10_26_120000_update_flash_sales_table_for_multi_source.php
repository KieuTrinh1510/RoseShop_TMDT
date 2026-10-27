<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->enum('nguon', ['CAN_HAN', 'HET_HAN', 'HANG_HOAN', 'HANG_CO_VAN_DE'])
                ->default('HANG_HOAN')
                ->after('trang_thai');
            $table->integer('ma_chi_tiet_nhap')
                ->nullable()
                ->after('ma_san_pham');
            $table->unsignedTinyInteger('ti_le_giam')
                ->nullable()
                ->after('so_tien_giam');
            $table->integer('nguoi_tao')
                ->nullable()
                ->after('trang_thai');

            $table->foreign('ma_chi_tiet_nhap')
                ->references('ma_chi_tiet_nhap')->on('chi_tiet_nhap')
                ->nullOnDelete();
            $table->foreign('nguoi_tao')
                ->references('ma_nguoi_dung')->on('nguoi_dung')
                ->nullOnDelete();

            $table->index('nguon');
            $table->index('ma_chi_tiet_nhap');
        });
    }

    public function down(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropForeign(['ma_chi_tiet_nhap']);
            $table->dropForeign(['nguoi_tao']);
            $table->dropIndex(['nguon']);
            $table->dropIndex(['ma_chi_tiet_nhap']);
            $table->dropColumn(['nguon', 'ma_chi_tiet_nhap', 'ti_le_giam', 'nguoi_tao']);
        });
    }
};
