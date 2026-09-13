<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bo_hoa', function (Blueprint $table) {

            $table->id('ma_bo_hoa');

            $table->integer('ma_khach_hang')->nullable();

            $table->unsignedBigInteger('ma_mau_bo_hoa')->nullable();

            $table->integer('ma_hoa_don')->nullable();

            $table->string('ten_bo_hoa', 100);

            $table->unsignedInteger('so_luong')->default(0);

            $table->decimal('gia_ban', 12, 2)->nullable();

            $table->enum('loai_bo_hoa', [
                'HE_THONG',
                'TUY_CHON',
            ]);

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

            $table->text('loi_nhan')->nullable();

            $table->enum('trang_thai', [
                'DRAFT',
                'ACTIVE',
            ])->default('DRAFT');

            $table->timestamps();

            // Khách hàng
            $table->foreign('ma_khach_hang')
                ->references('ma_khach_hang')
                ->on('khach_hang')
                ->nullOnDelete();

            // Mẫu bó hoa
            $table->foreign('ma_mau_bo_hoa')
                ->references('ma_mau_bo_hoa')
                ->on('mau_bo_hoa')
                ->nullOnDelete();

            // Hóa đơn
            $table->foreign('ma_hoa_don')
                ->references('ma_hoa_don')
                ->on('hoa_don')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bo_hoa');
    }
};