<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột du_lieu (JSON) để lưu dữ liệu có cấu trúc kèm theo thông báo.
 * Ví dụ: khi admin xác nhận shipper đã nộp tiền, ta cần lưu breakdown
 * (danh sách đơn + tiền phạt) để app hiển thị chi tiết khi shipper tap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thong_bao', function (Blueprint $table) {
            $table->json('du_lieu')->nullable()->after('noi_dung');
        });
    }

    public function down(): void
    {
        Schema::table('thong_bao', function (Blueprint $table) {
            $table->dropColumn('du_lieu');
        });
    }
};