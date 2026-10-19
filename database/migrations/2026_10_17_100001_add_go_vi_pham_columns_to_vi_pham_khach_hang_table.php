<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột soft delete cho vi_pham_khach_hang.
 * Không xóa vật lý bản ghi — chỉ đánh dấu đã gỡ để giữ lịch sử.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vi_pham_khach_hang', function (Blueprint $table) {
            $table->boolean('da_go')->default(false)->after('han_che_ap_dung');
            $table->string('ly_do_go', 500)->nullable()->after('da_go');
            $table->string('nguoi_go', 100)->nullable()->after('ly_do_go');
            $table->dateTime('thoi_diem_go')->nullable()->after('nguoi_go');

            // Index tra cứu vi phạm chưa gỡ theo khách
            $table->index(['ma_khach_hang', 'da_go'], 'idx_vp_kh_chua_go');
        });
    }

    public function down(): void
    {
        Schema::table('vi_pham_khach_hang', function (Blueprint $table) {
            $table->dropIndex('idx_vp_kh_chua_go');
            $table->dropColumn(['da_go', 'ly_do_go', 'nguoi_go', 'thoi_diem_go']);
        });
    }
};