<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm ngay_het_han vào bảng bo_hoa nếu chưa có
        if (Schema::hasTable('bo_hoa') && !Schema::hasColumn('bo_hoa', 'ngay_het_han')) {
            Schema::table('bo_hoa', function (Blueprint $table) {
                $table->date('ngay_het_han')->nullable()->after('trang_thai');
            });
        }

        // 2. Thêm han_su_dung_ngay vào bảng mau_bo_hoa nếu chưa có
        if (Schema::hasTable('mau_bo_hoa') && !Schema::hasColumn('mau_bo_hoa', 'han_su_dung_ngay')) {
            Schema::table('mau_bo_hoa', function (Blueprint $table) {
                $table->integer('han_su_dung_ngay')->default(5)->nullable()->after('trang_thai');
            });
        }

        // 3. Đảm bảo bảng chi_tiet_nhap có cột ngay_het_han
        if (Schema::hasTable('chi_tiet_nhap') && !Schema::hasColumn('chi_tiet_nhap', 'ngay_het_han')) {
            Schema::table('chi_tiet_nhap', function (Blueprint $table) {
                $table->date('ngay_het_han')->nullable()->after('so_luong_con_lai');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bo_hoa') && Schema::hasColumn('bo_hoa', 'ngay_het_han')) {
            Schema::table('bo_hoa', function (Blueprint $table) {
                $table->dropColumn('ngay_het_han');
            });
        }

        if (Schema::hasTable('mau_bo_hoa') && Schema::hasColumn('mau_bo_hoa', 'han_su_dung_ngay')) {
            Schema::table('mau_bo_hoa', function (Blueprint $table) {
                $table->dropColumn('han_su_dung_ngay');
            });
        }
    }
};
