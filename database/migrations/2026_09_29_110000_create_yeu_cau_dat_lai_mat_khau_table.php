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
        Schema::create('yeu_cau_dat_lai_mat_khau', function (Blueprint $table) {
            $table->id();
            $table->integer('ma_nguoi_dung');
            $table->string('email', 100)->index();
            $table->string('ma_xac_thuc', 6)->index();
            $table->string('token', 64)->index();
            $table->string('trang_thai', 20)->default('CO_HIEU_LUC')->index();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('ma_nguoi_dung')
                  ->references('ma_nguoi_dung')
                  ->on('nguoi_dung')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yeu_cau_dat_lai_mat_khau');
    }
};
