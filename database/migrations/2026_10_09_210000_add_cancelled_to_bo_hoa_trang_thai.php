<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `bo_hoa` MODIFY COLUMN `trang_thai` ENUM(
                'DRAFT',
                'ACTIVE',
                'CANCELLED'
            ) NOT NULL DEFAULT 'DRAFT'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `bo_hoa` MODIFY COLUMN `trang_thai` ENUM(
                'DRAFT',
                'ACTIVE'
            ) NOT NULL DEFAULT 'DRAFT'
        ");
    }
};
