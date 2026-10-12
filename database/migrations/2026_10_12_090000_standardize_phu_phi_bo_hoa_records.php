<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Xóa các bản ghi rác / mã tiếng Việt không chuẩn
        DB::table('phu_phi_bo_hoa')->whereIn('gia_tri', ['NHO', 'TRON'])->delete();

        // 2. Chuẩn hóa phụ phí SIZE theo đúng danh mục SO_BONG
        $sizes = [
            'S'       => 5000,
            'M'       => 5000,
            'L'       => 10000,
            'XL'      => 15000,
            'XXL'     => 20000,
            'SPECIAL' => 30000,
        ];

        foreach ($sizes as $size => $phuPhi) {
            DB::table('phu_phi_bo_hoa')->updateOrInsert(
                ['loai' => 'SIZE', 'gia_tri' => $size],
                ['phu_phi' => $phuPhi]
            );
        }

        // 3. Chuẩn hóa phụ phí KIEU_DANG theo đúng danh mục KIEU_DANG
        $styles = [
            'ROUND'      => 15000,
            'NATURAL'    => 10000,
            'CONE'       => 10000,
            'CASCADING'  => 2000,
            'ASYMMETRIC' => 5000,
            'COMPACT'    => 5000,
            'LONG_STEM'  => 5000,
        ];

        foreach ($styles as $style => $phuPhi) {
            DB::table('phu_phi_bo_hoa')->updateOrInsert(
                ['loai' => 'KIEU_DANG', 'gia_tri' => $style],
                ['phu_phi' => $phuPhi]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback
    }
};
