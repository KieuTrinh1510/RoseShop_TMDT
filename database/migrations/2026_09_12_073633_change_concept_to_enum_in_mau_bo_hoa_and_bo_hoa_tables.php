<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $concepts = [
            'ROMANTIC',
            'ELEGANT',
            'MINIMALIST',
            'NATURAL',
            'LUXURY',
            'CUTE',
            'VINTAGE',
        ];

        // mau_bo_hoa
        Schema::table('mau_bo_hoa', function (Blueprint $table) use ($concepts) {
            $table->enum('concept', $concepts)
                ->nullable()
                ->change();
        });

        // bo_hoa
        Schema::table('bo_hoa', function (Blueprint $table) use ($concepts) {
            $table->enum('concept', $concepts)
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        // Trả về kiểu string nếu rollback migration
        Schema::table('mau_bo_hoa', function (Blueprint $table) {
            $table->string('concept', 100)
                ->nullable()
                ->change();
        });

        Schema::table('bo_hoa', function (Blueprint $table) {
            $table->string('concept', 100)
                ->nullable()
                ->change();
        });
    }
};