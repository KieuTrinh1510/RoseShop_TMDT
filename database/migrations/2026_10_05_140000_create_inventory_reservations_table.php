<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->integer('ma_hoa_don');
            $table->integer('ma_san_pham')->nullable();
            $table->integer('ma_chi_tiet_nhap')->nullable();
            $table->unsignedBigInteger('ma_bo_hoa')->nullable();
            $table->unsignedInteger('so_luong');
            $table->enum('trang_thai', ['RESERVED', 'CONSUMED', 'RELEASED'])->default('RESERVED');
            $table->timestamps();

            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')
                ->on('hoa_don')
                ->cascadeOnDelete();

            $table->foreign('ma_san_pham')
                ->references('ma_san_pham')
                ->on('san_pham')
                ->nullOnDelete();

            $table->foreign('ma_chi_tiet_nhap')
                ->references('ma_chi_tiet_nhap')
                ->on('chi_tiet_nhap')
                ->nullOnDelete();

            $table->foreign('ma_bo_hoa')
                ->references('ma_bo_hoa')
                ->on('bo_hoa')
                ->nullOnDelete();

            $table->index(['ma_hoa_don', 'trang_thai']);
            $table->index(['ma_san_pham', 'trang_thai']);
            $table->index(['ma_chi_tiet_nhap', 'trang_thai']);
            $table->index(['ma_bo_hoa', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
