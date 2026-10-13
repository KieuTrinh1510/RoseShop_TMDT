<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Đánh dấu đơn mà SHIPPER đã thu tiền mặt từ khách (đang giữ).
 *
 * Tách biệt với trang_thai_thanh_toan (trạng thái "khách đã trả shop chưa"):
 *   - NGAN_HANG khách CK trước  → DA_THANH_TOAN, da_thu_tien_mat = false (shipper không giữ)
 *   - COD khách trả khi giao    → DA_THANH_TOAN, da_thu_tien_mat = true  (shipper giữ 100k)
 *   - NGAN_HANG khách chưa CK,
 *     shipper thu tiền mặt      → DA_THANH_TOAN, da_thu_tien_mat = true  (shipper giữ)
 *
 * Khi admin xác nhận "đã nhận tiền từ shipper":
 *   → trang_thai_thanh_toan = 'DA_NOP' (đơn rớt khỏi tinhTienCanNop)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->boolean('da_thu_tien_mat')
                  ->default(false)
                  ->after('trang_thai_thanh_toan');
        });

        // Đồng bộ dữ liệu cũ: đơn COD đã giao + đã thanh toán → shipper đang giữ tiền mặt
        DB::statement("
            UPDATE hoa_don
            SET da_thu_tien_mat = 1
            WHERE trang_thai = 'DELIVERED'
              AND trang_thai_thanh_toan = 'DA_THANH_TOAN'
              AND phuong_thuc_thanh_toan = 'COD'
        ");
    }

    public function down(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->dropColumn('da_thu_tien_mat');
        });
    }
};