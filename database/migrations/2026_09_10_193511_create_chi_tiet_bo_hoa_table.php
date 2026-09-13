<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chi_tiet_bo_hoa', function (Blueprint $table) {

            $table->id('ma_chi_tiet');

            // bo_hoa được tạo mới bằng $table->id() => bigint unsigned
            $table->unsignedBigInteger('ma_bo_hoa');

            // san_pham.ma_san_pham là int signed thường
            $table->integer('ma_san_pham');

            // chi_tiet_nhap.ma_chi_tiet_nhap là int signed thường
            // Nullable: DRAFT chưa phân bổ lô
            $table->integer('ma_chi_tiet_nhap')->nullable();

            $table->enum('vai_tro', [
                'HOA_CHINH',
                'HOA_TRANG_TRI',
                'GIAY_GOI',
                'RUY_BANG',
                'PHU_KIEN_TRANG_TRI',
                'THIEP',
            ]);

            $table->integer('so_luong');

            $table->foreign('ma_bo_hoa')
                ->references('ma_bo_hoa')
                ->on('bo_hoa')
                ->onDelete('cascade');

            $table->foreign('ma_san_pham')
                ->references('ma_san_pham')
                ->on('san_pham');

            $table->foreign('ma_chi_tiet_nhap')
                ->references('ma_chi_tiet_nhap')
                ->on('chi_tiet_nhap')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_bo_hoa');
    }
};