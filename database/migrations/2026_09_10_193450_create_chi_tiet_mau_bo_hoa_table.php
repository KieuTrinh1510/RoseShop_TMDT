<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chi_tiet_mau_bo_hoa', function (Blueprint $table) {
            $table->id('ma_chi_tiet_mau');

            $table->unsignedBigInteger('ma_mau_bo_hoa'); // đúng, vì mau_bo_hoa dùng $table->id()
            $table->integer('ma_san_pham'); // SỬA: san_pham.ma_san_pham là int signed thường

            $table->enum('vai_tro', [
                'HOA_CHINH',
                'HOA_TRANG_TRI',
                'GIAY_GOI',
                'RUY_BANG',
                'PHU_KIEN_TRANG_TRI',
                'THIEP',
            ]);

            $table->unsignedInteger('so_luong');

            $table->foreign('ma_mau_bo_hoa')
                ->references('ma_mau_bo_hoa')
                ->on('mau_bo_hoa')
                ->cascadeOnDelete();

            $table->foreign('ma_san_pham')
                ->references('ma_san_pham')
                ->on('san_pham');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_mau_bo_hoa');
    }
};