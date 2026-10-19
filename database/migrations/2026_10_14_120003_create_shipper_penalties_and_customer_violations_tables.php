<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phat_shipper', function (Blueprint $table) {
            $table->id('ma_phat_shipper');
            // Schema thực tế hiện tại của nhan_vien.ma_nhan_vien dùng INT.
            $table->integer('ma_nhan_vien');
            // Phải đồng kiểu với hoa_don.ma_hoa_don hiện tại (INT).
            $table->integer('ma_hoa_don');
            $table->unsignedBigInteger('ma_giao_hang_that_bai')->nullable();
            $table->decimal('so_tien', 12, 2)->default(0);
            $table->decimal('tien_hang_hong', 12, 2)->default(0);
            $table->decimal('tien_hao_hut_flash_sale', 12, 2)->default(0);
            $table->string('ly_do', 255);
            $table->enum('trang_thai', ['CHUA_THU', 'DA_THU', 'MIEN_PHAT'])->default('CHUA_THU');
            $table->timestamps();

            $table->foreign('ma_nhan_vien')
                ->references('ma_nhan_vien')->on('nhan_vien')
                ->cascadeOnDelete();
            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')->on('hoa_don')
                ->cascadeOnDelete();
            $table->foreign('ma_giao_hang_that_bai')
                ->references('ma_giao_hang_that_bai')->on('giao_hang_that_bai')
                ->nullOnDelete();

            $table->index(['ma_nhan_vien', 'trang_thai']);
            $table->index('ma_hoa_don');
        });

        Schema::create('vi_pham_khach_hang', function (Blueprint $table) {
            $table->id('ma_vi_pham');
            // Schema thực tế hiện tại của khach_hang.ma_khach_hang dùng INT.
            $table->integer('ma_khach_hang');
            // Phải đồng kiểu với hoa_don.ma_hoa_don hiện tại (INT).
            $table->integer('ma_hoa_don')->nullable();
            $table->unsignedBigInteger('ma_giao_hang_that_bai')->nullable();
            $table->string('ly_do', 255);
            $table->unsignedInteger('lan_vi_pham')->default(1);
            $table->enum('han_che_ap_dung', ['CANH_BAO', 'CAM_COD', 'TAM_KHOA_MUA_HANG'])->default('CANH_BAO');
            $table->timestamps();

            $table->foreign('ma_khach_hang')
                ->references('ma_khach_hang')->on('khach_hang')
                ->cascadeOnDelete();
            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')->on('hoa_don')
                ->nullOnDelete();
            $table->foreign('ma_giao_hang_that_bai')
                ->references('ma_giao_hang_that_bai')->on('giao_hang_that_bai')
                ->nullOnDelete();

            $table->index(['ma_khach_hang', 'lan_vi_pham']);
            $table->index('han_che_ap_dung');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vi_pham_khach_hang');
        Schema::dropIfExists('phat_shipper');
    }
};
