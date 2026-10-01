<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nha_cung_cap', function (Blueprint $table) {
            $table->id('ma_nha_cung_cap');
            $table->string('ten_nha_cung_cap', 100);
            $table->string('so_dien_thoai', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('dia_chi')->nullable();
            $table->enum('trang_thai', ['DANG_HOP_TAC', 'NGUNG_HOP_TAC'])->default('DANG_HOP_TAC');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nha_cung_cap');
    }
};
