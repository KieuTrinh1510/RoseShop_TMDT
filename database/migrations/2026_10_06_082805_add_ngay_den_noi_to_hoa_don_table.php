<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            // Mốc shipper bấm "Nhận hàng" (đồng hồ 3h hoa tươi tính từ đây)
            if (!Schema::hasColumn('hoa_don', 'ngay_bat_giao')) {
                $table->dateTime('ngay_bat_giao')->nullable()->after('ngay_giao');
            }

            // Đã xử lý phạt giao trễ chưa (UC4.2.10, mỗi đơn tối đa 1 lần)
            if (!Schema::hasColumn('hoa_don', 'da_phat_tre')) {
                $table->boolean('da_phat_tre')->default(false)->after('ngay_bat_giao');
            }

            // Mốc shipper bấm "Đã đến điểm giao" (bắt buộc trước khi hoàn thành)
            if (!Schema::hasColumn('hoa_don', 'ngay_den_noi')) {
                $table->dateTime('ngay_den_noi')->nullable()->after('ngay_bat_giao');
            }
        });
    }

    public function down(): void
    {
        // Chỉ gỡ cột do migration này chắc chắn thêm. ngay_bat_giao và da_phat_tre
        // có thể đã tồn tại từ trước nên không xóa để tránh mất dữ liệu.
        Schema::table('hoa_don', function (Blueprint $table) {
            if (Schema::hasColumn('hoa_don', 'ngay_den_noi')) {
                $table->dropColumn('ngay_den_noi');
            }
        });
    }
};