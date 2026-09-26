<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Đặt file này vào: database/migrations/
// Chạy: php artisan migrate
// Hoàn tác: php artisan migrate:rollback
//
// Thêm 3 cột cho UC7.2 (trạng thái nhận đơn của Shipper):
//   - trang_thai_hoat_dong : 'ONLINE' | 'OFFLINE'  (shipper tự bật/tắt)
//   - nhan_don             : true = ON, false = OFF (shipper tự bật/tắt)
//   - so_don_hien_tai      : số đơn shipper đang giữ (CONFIRMED + SHIPPING)
// is_full và co_the_nhan_don do hệ thống tự suy ra nên KHÔNG có cột.

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->string('trang_thai_hoat_dong', 10)->default('OFFLINE');
            $table->boolean('nhan_don')->default(false);
            $table->unsignedInteger('so_don_hien_tai')->default(0);
        });

        // Đồng bộ số đơn cho dữ liệu cũ: đếm các đơn shipper đang giữ
        DB::statement("
            UPDATE nhan_vien
            SET so_don_hien_tai = (
                SELECT COUNT(*)
                FROM hoa_don
                WHERE hoa_don.ma_nhan_vien_giao = nhan_vien.ma_nhan_vien
                  AND hoa_don.trang_thai IN ('CONFIRMED', 'SHIPPING')
            )
        ");
    }

    public function down(): void
    {
        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropColumn(['trang_thai_hoat_dong', 'nhan_don', 'so_don_hien_tai']);
        });
    }
};