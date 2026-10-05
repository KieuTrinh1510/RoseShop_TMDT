<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `phieu_nhap` MODIFY COLUMN `trang_thai` ENUM(
                    'DRAFT',
                    'CONFIRMED',
                    'CANCELLED'
                ) DEFAULT 'DRAFT'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `phieu_nhap` MODIFY COLUMN `trang_thai` ENUM(
                    'DRAFT',
                    'CONFIRMED'
                ) DEFAULT 'DRAFT'
            ");
        }
    }
};
