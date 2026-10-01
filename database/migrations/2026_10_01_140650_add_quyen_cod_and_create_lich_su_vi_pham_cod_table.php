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
        // 1. Bổ sung trường quyền COD cho khách hàng (CHO_PHEP hoặc GIOI_HAN)
        if (!Schema::hasColumn('khach_hang', 'quyen_cod')) {
            Schema::table('khach_hang', function (Blueprint $table) {
                $table->string('quyen_cod', 20)->default('CHO_PHEP')->after('diem_tich_luy');
            });
        }

        // 2. Bảng lịch sử vi phạm nhận hàng / COD
        if (!Schema::hasTable('lich_su_vi_pham_cod')) {
            Schema::create('lich_su_vi_pham_cod', function (Blueprint $table) {
                $table->id();
                $table->integer('ma_khach_hang')->index();
                $table->unsignedBigInteger('ma_hoa_don')->nullable()->index();
                $table->string('loai_vi_pham', 50)->default('BOM_HANG');
                $table->text('ly_do');
                $table->string('hanh_dong', 50)->default('GHI_NHAN'); // GIOI_HAN_COD, KHOI_PHUC_COD, GHI_NHAN
                $table->string('nguoi_thuc_hien', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_vi_pham_cod');

        if (Schema::hasColumn('khach_hang', 'quyen_cod')) {
            Schema::table('khach_hang', function (Blueprint $table) {
                $table->dropColumn('quyen_cod');
            });
        }
    }
};
