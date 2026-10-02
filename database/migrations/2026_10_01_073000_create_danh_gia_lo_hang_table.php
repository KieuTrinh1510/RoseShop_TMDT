<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('danh_gia_lo_hang', function (Blueprint $table) {
            $table->id('ma_danh_gia');
            $table->integer('ma_chi_tiet_nhap');
            $table->integer('ma_nhan_vien')->nullable();
            $table->enum('ket_qua', ['DAT', 'CO_VAN_DE', 'KHONG_DAT'])->default('DAT');
            $table->integer('so_luong_loi')->default(0);
            $table->string('ly_do', 255)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('hinh_anh', 255)->nullable();
            $table->timestamp('thoi_gian_danh_gia')->useCurrent();
            $table->timestamps();

            $table->foreign('ma_chi_tiet_nhap')
                ->references('ma_chi_tiet_nhap')
                ->on('chi_tiet_nhap')
                ->cascadeOnDelete();

            $table->foreign('ma_nhan_vien')
                ->references('ma_nhan_vien')
                ->on('nhan_vien')
                ->nullOnDelete();

            $table->index('ma_chi_tiet_nhap');
            $table->index('ket_qua');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danh_gia_lo_hang');
    }
};
