<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cho phép ma_san_pham nullable khi báo hỏng theo bó hoa thành phẩm
        DB::statement("ALTER TABLE `bao_cao_hang_hong` MODIFY `ma_san_pham` INT NULL");

        Schema::table('bao_cao_hang_hong', function (Blueprint $table) {
            $table->unsignedBigInteger('ma_bo_hoa')
                ->nullable()
                ->after('ma_chi_tiet_nhap');
            $table->enum('nguon_hong', [
                'CAN_HAN_HET_HAN',
                'HANG_HOAN',
                'TON_KHO',
                'FLASH_SALE',
                'SHIPPER',
                'KHAC'
            ])->default('TON_KHO')->after('so_luong_hong');
            $table->unsignedBigInteger('ma_flash_sale')
                ->nullable()
                ->after('nguon_hong');
            $table->integer('ma_hoa_don')
                ->nullable()
                ->after('ma_flash_sale');
            $table->decimal('gia_tri_thiet_hai', 12, 2)
                ->nullable()
                ->after('so_luong_hong');

            $table->foreign('ma_bo_hoa')
                ->references('ma_bo_hoa')->on('bo_hoa')
                ->nullOnDelete();
            $table->foreign('ma_flash_sale')
                ->references('ma_flash_sale')->on('flash_sales')
                ->nullOnDelete();
            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')->on('hoa_don')
                ->nullOnDelete();

            $table->index('nguon_hong');
            $table->index('ma_bo_hoa');
            $table->index('ma_flash_sale');
            $table->index('ma_hoa_don');
        });
    }

    public function down(): void
    {
        Schema::table('bao_cao_hang_hong', function (Blueprint $table) {
            $table->dropForeign(['ma_bo_hoa']);
            $table->dropForeign(['ma_flash_sale']);
            $table->dropForeign(['ma_hoa_don']);
            $table->dropIndex(['nguon_hong']);
            $table->dropIndex(['ma_bo_hoa']);
            $table->dropIndex(['ma_flash_sale']);
            $table->dropIndex(['ma_hoa_don']);
            $table->dropColumn(['ma_bo_hoa', 'nguon_hong', 'ma_flash_sale', 'ma_hoa_don', 'gia_tri_thiet_hai']);
        });

        DB::statement("ALTER TABLE `bao_cao_hang_hong` MODIFY `ma_san_pham` INT NOT NULL");
    }
};
