<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('custom_mix_images')) {
            Schema::create('custom_mix_images', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ma_bo_hoa');
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('ma_bo_hoa')
                    ->references('ma_bo_hoa')
                    ->on('bo_hoa')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_mix_images');
    }
};
