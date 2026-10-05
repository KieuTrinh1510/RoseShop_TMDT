<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_nhap', function (Blueprint $table) {
            if (!Schema::hasColumn('phieu_nhap', 'ly_do_huy')) {
                $table->text('ly_do_huy')->nullable()->after('trang_thai');
            }
            if (!Schema::hasColumn('phieu_nhap', 'nguoi_huy_id')) {
                $table->integer('nguoi_huy_id')->nullable()->after('ly_do_huy');
            }
            if (!Schema::hasColumn('phieu_nhap', 'huy_luc')) {
                $table->dateTime('huy_luc')->nullable()->after('nguoi_huy_id');
            }
        });

        // Ensure foreign key exists
        try {
            Schema::table('phieu_nhap', function (Blueprint $table) {
                $table->foreign('nguoi_huy_id')
                    ->references('ma_nguoi_dung')
                    ->on('nguoi_dung')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Already created or not supported by driver
        }
    }

    public function down(): void
    {
        Schema::table('phieu_nhap', function (Blueprint $table) {
            try {
                $table->dropForeign(['nguoi_huy_id']);
            } catch (\Throwable $e) {
            }
            $table->dropColumn(['ly_do_huy', 'nguoi_huy_id', 'huy_luc']);
        });
    }
};
