<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('ma_flash_sale')->nullable()->after('ma_bo_hoa');
            $table->foreign('ma_flash_sale')->references('ma_flash_sale')->on('flash_sales')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropForeign(['ma_flash_sale']);
            $table->dropColumn('ma_flash_sale');
        });
    }
};
