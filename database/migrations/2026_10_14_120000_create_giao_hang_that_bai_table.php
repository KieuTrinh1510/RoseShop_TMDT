<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giao_hang_that_bai', function (Blueprint $table) {
            $table->id('ma_giao_hang_that_bai');
            // Schema thực tế hiện tại của hoa_don dùng INT cho ma_hoa_don.
            $table->integer('ma_hoa_don');
            // Schema thực tế hiện tại của nhan_vien.ma_nhan_vien dùng INT.
            $table->integer('ma_nhan_vien_giao')->nullable();
            $table->enum('loai_loi', ['SHIPPER', 'CUSTOMER']);
            $table->string('ly_do', 150);
            $table->text('ghi_chu')->nullable();
            $table->dateTime('thoi_diem_bao');
            $table->dateTime('bat_dau_dem_10_phut')->nullable();
            $table->dateTime('ket_thuc_dem_10_phut')->nullable();
            $table->enum('trang_thai', [
                'DA_BAO',
                'CHO_HOAN_HANG',
                'DA_TIEP_NHAN',
                'DA_XU_LY',
            ])->default('DA_BAO');
            $table->timestamps();

            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')->on('hoa_don')
                ->cascadeOnDelete();

            $table->foreign('ma_nhan_vien_giao')
                ->references('ma_nhan_vien')->on('nhan_vien')
                ->nullOnDelete();

            $table->index(['ma_hoa_don', 'loai_loi']);
            $table->index(['trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giao_hang_that_bai');
    }
};
