<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bo_hoa', 'gia_von')) {
            Schema::table('bo_hoa', function (Blueprint $table) {
                $table->decimal('gia_von', 12, 2)->nullable()->after('gia_ban');
            });
        }

        // Backfill giá vốn và tính lại giá bán cho các bó hoa HE_THONG hiện có
        $readyBouquets = DB::table('bo_hoa')
            ->where('loai_bo_hoa', 'HE_THONG')
            ->get();

        foreach ($readyBouquets as $bo) {
            $cost = (float) DB::table('chi_tiet_bo_hoa as ctbh')
                ->join('chi_tiet_nhap as ctn', 'ctbh.ma_chi_tiet_nhap', '=', 'ctn.ma_chi_tiet_nhap')
                ->where('ctbh.ma_bo_hoa', $bo->ma_bo_hoa)
                ->sum(DB::raw('ctbh.so_luong * ctn.gia_nhap'));

            if ($cost > 0) {
                $sellingPrice = round($cost * 1.5, 2);
                DB::table('bo_hoa')
                    ->where('ma_bo_hoa', $bo->ma_bo_hoa)
                    ->update([
                        'gia_von' => $cost,
                        'gia_ban' => $sellingPrice,
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bo_hoa', 'gia_von')) {
            Schema::table('bo_hoa', function (Blueprint $table) {
                $table->dropColumn('gia_von');
            });
        }
    }
};
