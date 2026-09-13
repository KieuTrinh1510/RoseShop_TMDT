<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('san_pham', function (Blueprint $table) {
            $table->enum('loai_san_pham', [
                'HOA_TUOI',
                'HOA_GIA',
                'SAN_PHAM_PREMIUM',
                'CHAU_HOA_GIA',
                'CHAU_HOA_TUOI',
                'CAY_CANH',
                'HOA_SAP',
                'HOA_GIAY_NHUN',
                'TERRARIUM',

                // Các loại mới
                'GIAY_GOI',
                'RUY_BANG',
                'PHU_KIEN_TRANG_TRI',
                'THIEP',

                'QUA_TANG',

                // Giữ lại để bảo toàn dữ liệu cũ
                'PHU_KIEN',
            ])->change();
        });
    }

    public function down(): void
    {
        // Không rollback enum cũ ở đây vì có thể
        // đã có sản phẩm sử dụng loại mới.
    }
};