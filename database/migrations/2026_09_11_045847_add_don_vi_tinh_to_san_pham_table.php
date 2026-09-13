<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('san_pham', function (Blueprint $table) {
            $table->enum('don_vi_tinh', [
                'CANH',
                'CAI',
                'CHAU',
                'TO',
                'MET',
            ])->default('CAI')->after('so_luong');
        });

        // Gán đơn vị cho dữ liệu sản phẩm hiện có
        DB::table('san_pham')
            ->whereIn('loai_san_pham', [
                'HOA_TUOI',
                'HOA_GIA',
                'HOA_SAP',
                'HOA_GIAY_NHUN',
            ])
            ->update(['don_vi_tinh' => 'CANH']);

        DB::table('san_pham')
            ->whereIn('loai_san_pham', [
                'CHAU_HOA_GIA',
                'CHAU_HOA_TUOI',
                'CAY_CANH',
            ])
            ->update(['don_vi_tinh' => 'CHAU']);

        DB::table('san_pham')
            ->whereIn('loai_san_pham', [
                'SAN_PHAM_PREMIUM',
                'TERRARIUM',
                'PHU_KIEN_TRANG_TRI',
                'THIEP',
                'QUA_TANG',
                'PHU_KIEN',
            ])
            ->update(['don_vi_tinh' => 'CAI']);

        DB::table('san_pham')
            ->where('loai_san_pham', 'GIAY_GOI')
            ->update(['don_vi_tinh' => 'TO']);

        DB::table('san_pham')
            ->where('loai_san_pham', 'RUY_BANG')
            ->update(['don_vi_tinh' => 'MET']);
    }

    public function down(): void
    {
        Schema::table('san_pham', function (Blueprint $table) {
            $table->dropColumn('don_vi_tinh');
        });
    }
};