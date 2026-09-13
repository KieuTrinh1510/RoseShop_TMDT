<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mau_bo_hoa', function (Blueprint $table) {
            $table->id('ma_mau_bo_hoa');

            $table->string('ten_mau', 100);
            $table->text('mo_ta')->nullable();

            $table->enum('kieu_bo_hoa', [
                'ROUND',
                'NATURAL',
                'CONE',
                'CASCADING',
                'ASYMMETRIC',
                'COMPACT',
                'LONG_STEM',
            ]);

            $table->enum('size', [
                'S',
                'M',
                'L',
                'XL',
                'XXL',
                'SPECIAL',
            ]);

            $table->string('concept', 100)->nullable();

            // Mẫu dùng 3 trạng thái, khác bo_hoa (chỉ DRAFT/ACTIVE)
            $table->enum('trang_thai', [
                'DRAFT',
                'ACTIVE',
                'INACTIVE',
            ])->default('DRAFT');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mau_bo_hoa');
    }
};