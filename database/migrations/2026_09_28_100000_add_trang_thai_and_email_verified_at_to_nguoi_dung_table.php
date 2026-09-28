<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nguoi_dung', function (Blueprint $table) {
            $table->string('trang_thai', 30)->default('HOAT_DONG')->after('vai_tro');
            $table->timestamp('email_verified_at')->nullable()->after('trang_thai');
        });

        // Đảm bảo toàn bộ tài khoản hiện tại được kích hoạt HOAT_DONG và verified
        DB::table('nguoi_dung')->update([
            'trang_thai' => 'HOAT_DONG',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nguoi_dung', function (Blueprint $table) {
            $table->dropColumn(['trang_thai', 'email_verified_at']);
        });
    }
};
