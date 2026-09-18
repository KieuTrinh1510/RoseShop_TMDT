<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $vaiTro = [
            'HOA_CHINH',
            'HOA_TRANG_TRI',
            'GIAY_GOI',
            'RUY_BANG',
            'PHU_KIEN_TRANG_TRI',
            'THIEP',
            'XOP', 
        ];

        Schema::table('chi_tiet_bo_hoa', function (Blueprint $table) use ($vaiTro) {
            $table->enum('vai_tro', $vaiTro)->change();
        });

        Schema::table('chi_tiet_mau_bo_hoa', function (Blueprint $table) use ($vaiTro) {
            $table->enum('vai_tro', $vaiTro)->change();
        });
    }

    public function down(): void
    {
        // Không rollback để tránh mất dữ liệu nếu đã có bản ghi dùng XOP
    }
};