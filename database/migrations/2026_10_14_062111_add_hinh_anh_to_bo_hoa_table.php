
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bo_hoa', 'hinh_anh')) {
            Schema::table('bo_hoa', function (Blueprint $table) {
                $table->string('hinh_anh')
                    ->nullable()
                    ->after('loi_nhan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bo_hoa', 'hinh_anh')) {
            Schema::table('bo_hoa', function (Blueprint $table) {
                $table->dropColumn('hinh_anh');
            });
        }
    }
};
