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
        Schema::create('bot_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('ma_dinh_danh', 100)->unique();
            $table->string('loai', 50)->index(); // MAU_BO_HOA, SAN_PHAM, TIN_TUC, CHINH_SACH, FAQ
            $table->string('tieu_de', 255);
            $table->mediumText('noi_dung');
            $table->text('tu_khoa')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_knowledge');
    }
};
