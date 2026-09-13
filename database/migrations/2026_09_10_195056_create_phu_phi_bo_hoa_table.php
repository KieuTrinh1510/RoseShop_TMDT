<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phu_phi_bo_hoa', function (Blueprint $table) {

            $table->id('ma_phu_phi');

            $table->enum('loai', [
                'SIZE',
                'KIEU_DANG'
            ]);

            $table->string('gia_tri', 50);

            $table->decimal('phu_phi', 12, 2)->default(0);

            $table->unique(['loai', 'gia_tri']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phu_phi_bo_hoa');
    }
};