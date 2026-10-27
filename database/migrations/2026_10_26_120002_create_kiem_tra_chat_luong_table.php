<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiem_tra_chat_luong', function (Blueprint $table) {
            $table->id('id');
            $table->enum('doi_tuong_loai', [
                'LO_HANG',
                'BO_HOA',
                'HANG_HOAN',
                'FLASH_SALE',
                'SAN_PHAM'
            ]);
            $table->unsignedBigInteger('doi_tuong_id');

            $table->integer('ma_san_pham')->nullable();
            $table->integer('ma_chi_tiet_nhap')->nullable();
            $table->unsignedBigInteger('ma_bo_hoa')->nullable();
            $table->unsignedBigInteger('ma_flash_sale')->nullable();

            $table->enum('ket_qua', [
                'CON_BAN_DUOC',
                'FLASH_SALE',
                'HONG',
                'GIA_HAN'
            ]);

            $table->text('ghi_chu');
            $table->integer('ma_nhan_vien')->nullable();
            $table->integer('ma_nguoi_dung')->nullable();
            $table->date('hsd_luc_kiem_tra')->nullable();
            $table->dateTime('thoi_gian_kiem_tra');
            $table->dateTime('thoi_gian_cu')->nullable();
            $table->dateTime('thoi_gian_moi')->nullable();
            $table->timestamps();

            $table->foreign('ma_san_pham')
                ->references('ma_san_pham')->on('san_pham')
                ->nullOnDelete();
            $table->foreign('ma_chi_tiet_nhap')
                ->references('ma_chi_tiet_nhap')->on('chi_tiet_nhap')
                ->nullOnDelete();
            $table->foreign('ma_bo_hoa')
                ->references('ma_bo_hoa')->on('bo_hoa')
                ->nullOnDelete();
            $table->foreign('ma_flash_sale')
                ->references('ma_flash_sale')->on('flash_sales')
                ->nullOnDelete();
            $table->foreign('ma_nhan_vien')
                ->references('ma_nhan_vien')->on('nhan_vien')
                ->nullOnDelete();
            $table->foreign('ma_nguoi_dung')
                ->references('ma_nguoi_dung')->on('nguoi_dung')
                ->nullOnDelete();

            $table->index(['doi_tuong_loai', 'doi_tuong_id']);
            $table->index('thoi_gian_kiem_tra');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiem_tra_chat_luong');
    }
};
