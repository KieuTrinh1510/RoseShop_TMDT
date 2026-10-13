<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng thông báo dùng chung cho KHÁCH HÀNG và SHIPPER.
 * Người nhận xác định bằng ma_nguoi_dung (ma_khach_hang / ma_nhan_vien dùng chung
 * giá trị với nguoi_dung.ma_nguoi_dung như các bảng hiện tại).
 *
 * loai: shipping | arrived | apology | penalty | info
 *
 * Không đặt foreign key vì không chắc kiểu cột nguoi_dung.ma_nguoi_dung
 * (signed/unsigned). Nếu muốn, thêm sau khi đã kiểm tra kiểu cột.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_bao', function (Blueprint $table) {
            $table->id('ma_thong_bao');
            $table->unsignedInteger('ma_nguoi_dung');          // người nhận
            $table->string('loai', 20)->default('info');
            $table->text('noi_dung');
            $table->unsignedInteger('ma_hoa_don')->nullable(); // đơn liên quan (nếu có)
            $table->boolean('da_doc')->default(false);
            $table->timestamp('thoi_gian')->useCurrent();

            // Đếm chưa đọc + lấy danh sách theo người nhận
            $table->index(['ma_nguoi_dung', 'da_doc', 'thoi_gian'], 'idx_tb_nguoi_nhan');
            // Kiểm tra đã gửi thông báo loại X cho đơn Y chưa
            $table->index(['ma_hoa_don', 'loai'], 'idx_tb_don_loai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_bao');
    }
};