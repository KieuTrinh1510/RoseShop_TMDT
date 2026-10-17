<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hang_hoan', function (Blueprint $table) {
            $table->id('ma_hang_hoan');
            // Phải đồng kiểu với hoa_don.ma_hoa_don hiện tại (INT).
            $table->integer('ma_hoa_don');
            $table->unsignedBigInteger('ma_giao_hang_that_bai')->nullable();
            $table->enum('trang_thai', [
                'CHO_MANG_VE_KHO',
                'DA_NHAN_TAI_KHO',
                'DANG_KIEM_TRA',
                'DA_KIEM_TRA',
            ])->default('CHO_MANG_VE_KHO');
            // Schema thực tế hiện tại của nguoi_dung.ma_nguoi_dung dùng INT.
            $table->integer('nguoi_kiem_tra')->nullable();
            $table->dateTime('thoi_diem_nhan_tai_kho')->nullable();
            $table->dateTime('thoi_diem_kiem_tra')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')->on('hoa_don')
                ->cascadeOnDelete();
            $table->foreign('ma_giao_hang_that_bai')
                ->references('ma_giao_hang_that_bai')->on('giao_hang_that_bai')
                ->nullOnDelete();
            $table->foreign('nguoi_kiem_tra')
                ->references('ma_nguoi_dung')->on('nguoi_dung')
                ->nullOnDelete();

            $table->unique('ma_hoa_don');
            $table->index('trang_thai');
        });

        Schema::create('chi_tiet_hang_hoan', function (Blueprint $table) {
            $table->id('ma_chi_tiet_hang_hoan');
            $table->unsignedBigInteger('ma_hang_hoan');
            // Schema thực tế hiện tại của san_pham.ma_san_pham dùng INT.
            $table->integer('ma_san_pham')->nullable();
            $table->unsignedBigInteger('ma_bo_hoa')->nullable();
            $table->integer('ma_chi_tiet_hoa_don')->nullable();
            $table->unsignedInteger('so_luong_theo_don');
            $table->unsignedInteger('so_luong_hong')->default(0);
            $table->unsignedInteger('so_luong_con_ban_duoc')->default(0);
            $table->unsignedInteger('so_luong_con_lai_flash_sale')->default(0);
            $table->decimal('gia_von_snapshot', 12, 2)->nullable();
            $table->decimal('gia_ban_snapshot', 12, 2)->nullable();
            $table->decimal('gia_flash_sale_snapshot', 12, 2)->nullable();
            $table->decimal('doanh_thu_thanh_ly', 12, 2)->default(0);
            $table->decimal('tien_hao_hut_flash_sale', 12, 2)->default(0);
            $table->decimal('tien_thiet_hai_hang_hong', 12, 2)->default(0);
            $table->decimal('tien_he_thong_chiu', 12, 2)->default(0);
            $table->decimal('tien_shipper_chiu', 12, 2)->default(0);
            $table->boolean('mien_phat_shipper')->default(false);
            $table->enum('ket_qua', [
                'CHO_KIEM_TRA',
                'HONG',
                'FLASH_SALE',
                'DA_XU_LY',
            ])->default('CHO_KIEM_TRA');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->foreign('ma_hang_hoan')
                ->references('ma_hang_hoan')->on('hang_hoan')
                ->cascadeOnDelete();
            $table->foreign('ma_san_pham')
                ->references('ma_san_pham')->on('san_pham')
                ->nullOnDelete();
            $table->foreign('ma_bo_hoa')
                ->references('ma_bo_hoa')->on('bo_hoa')
                ->nullOnDelete();
            $table->foreign('ma_chi_tiet_hoa_don')
                ->references('ma_chi_tiet')->on('chi_tiet_hoa_don')
                ->nullOnDelete();

            $table->index(['ma_hang_hoan', 'ket_qua']);
            $table->index(['ma_san_pham', 'ket_qua']);
            $table->index(['ma_bo_hoa', 'ket_qua']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_hang_hoan');
        Schema::dropIfExists('hang_hoan');
    }
};
