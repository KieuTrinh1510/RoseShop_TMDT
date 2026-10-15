<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * hoa_don.ngay_bat_giao : mốc shipper bấm "nhận hàng" (thay cho key cache shipping_start_{id})
 * hoa_don.da_phat_tre   : đã xử lý phạt giao trễ chưa (thay cho key shipping_penalty_done_{id})
 * nhan_vien.tien_phat   : tổng tiền phạt cộng dồn (thay cho key cache shipper_penalty_{id})
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->dateTime('ngay_bat_giao')->nullable()->after('ngay_giao');
            $table->boolean('da_phat_tre')->default(false)->after('ngay_bat_giao');

            // Job quét đơn giao trễ: WHERE trang_thai='SHIPPING' AND da_phat_tre=0 AND ngay_bat_giao < ...
            $table->index(['trang_thai', 'da_phat_tre', 'ngay_bat_giao'], 'idx_hd_giao_tre');
        });

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->decimal('tien_phat', 12, 2)->default(0);
        });

        // Đơn đang SHIPPING từ trước (mốc cũ nằm trong cache, không lấy lại được)
        // -> lấy thời điểm chạy migration làm mốc để đồng hồ 3h vẫn hoạt động.
        DB::table('hoa_don')
            ->where('trang_thai', 'SHIPPING')
            ->whereNull('ngay_bat_giao')
            ->update(['ngay_bat_giao' => now()]);
    }

    public function down(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->dropIndex('idx_hd_giao_tre');
            $table->dropColumn(['ngay_bat_giao', 'da_phat_tre']);
        });

        Schema::table('nhan_vien', function (Blueprint $table) {
            $table->dropColumn('tien_phat');
        });
    }
};