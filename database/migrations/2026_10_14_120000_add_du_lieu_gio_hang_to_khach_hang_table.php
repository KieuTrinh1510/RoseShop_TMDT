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
        if (!Schema::hasColumn('khach_hang', 'du_lieu_gio_hang')) {
            Schema::table('khach_hang', function (Blueprint $table) {
                $table->longText('du_lieu_gio_hang')->nullable()->after('quyen_cod');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('khach_hang', 'du_lieu_gio_hang')) {
            Schema::table('khach_hang', function (Blueprint $table) {
                $table->dropColumn('du_lieu_gio_hang');
            });
        }
    }
};
