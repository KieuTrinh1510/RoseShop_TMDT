<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            // Chỉ phân loại bên gây ra việc hủy đơn.
            // Nội dung cụ thể được lưu ở ly_do_huy.
            $table->enum('nguon_huy', ['CUSTOMER', 'SHIPPER'])
                ->nullable()
                ->after('trang_thai');

            $table->text('ly_do_huy')
                ->nullable()
                ->after('nguon_huy');

            $table->dateTime('thoi_diem_huy')
                ->nullable()
                ->after('ly_do_huy');

            $table->boolean('co_hang_hoan')
                ->default(false)
                ->after('thoi_diem_huy');

            $table->index(['trang_thai', 'nguon_huy']);
            $table->index(['co_hang_hoan', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::table('hoa_don', function (Blueprint $table) {
            $table->dropIndex(['trang_thai', 'nguon_huy']);
            $table->dropIndex(['co_hang_hoan', 'trang_thai']);
            $table->dropColumn([
                'nguon_huy',
                'ly_do_huy',
                'thoi_diem_huy',
                'co_hang_hoan',
            ]);
        });
    }
};
