<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id('ma_flash_sale');
            // Đồng bộ với san_pham.ma_san_pham hiện tại (INT).
            $table->integer('ma_san_pham')->nullable();
            $table->unsignedBigInteger('ma_bo_hoa')->nullable();
            $table->unsignedBigInteger('ma_chi_tiet_hang_hoan')->nullable();
            $table->decimal('gia_goc', 12, 2);
            $table->decimal('gia_flash_sale', 12, 2);
            $table->decimal('so_tien_giam', 12, 2)->default(0);
            $table->unsignedInteger('so_luong');
            $table->unsignedInteger('so_luong_da_ban')->default(0);
            $table->dateTime('bat_dau')->nullable();
            $table->dateTime('ket_thuc')->nullable();
            $table->enum('trang_thai', ['NHAP', 'DANG_BAN', 'HET_HANG', 'KET_THUC', 'AN'])->default('NHAP');
            $table->timestamps();

            $table->foreign('ma_san_pham')
                ->references('ma_san_pham')->on('san_pham')
                ->nullOnDelete();
            $table->foreign('ma_bo_hoa')
                ->references('ma_bo_hoa')->on('bo_hoa')
                ->nullOnDelete();
            $table->foreign('ma_chi_tiet_hang_hoan')
                ->references('ma_chi_tiet_hang_hoan')->on('chi_tiet_hang_hoan')
                ->nullOnDelete();

            $table->index(['trang_thai', 'bat_dau', 'ket_thuc']);
            $table->index('ma_san_pham');
            $table->index('ma_bo_hoa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
