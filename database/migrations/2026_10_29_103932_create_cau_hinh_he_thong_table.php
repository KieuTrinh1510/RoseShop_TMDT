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
        Schema::create('cau_hinh_he_thong', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string'); // string, integer, boolean, text, json
            $table->string('group', 50)->index(); // shop, interface, sales, inventory, loyalty, violation
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Khởi tạo các cấu hình mặc định an toàn cho hệ thống RoseShop
        $now = now();
        $defaults = [
            // 1. Thông tin cửa hàng
            [
                'key' => 'shop_name',
                'value' => 'RoseShop',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Tên thương hiệu cửa hàng',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'shop_logo',
                'value' => null,
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Đường dẫn file logo cửa hàng',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'shop_phone',
                'value' => '0901234567',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Số điện thoại liên hệ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'shop_email',
                'value' => 'roseshop@gmail.com',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Email hỗ trợ khách hàng',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'shop_address',
                'value' => '123 Đường Hoa Hồng, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Địa chỉ showroom/cửa hàng chính',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'opening_time',
                'value' => '07:00',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Giờ mở cửa phục vụ hàng ngày',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'closing_time',
                'value' => '22:00',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Giờ đóng cửa',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'facebook_url',
                'value' => 'https://facebook.com/roseshop.flower',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Fanpage Facebook cửa hàng',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'zalo_url',
                'value' => 'https://zalo.me/0901234567',
                'type' => 'string',
                'group' => 'shop',
                'description' => 'Kênh Zalo CSKH',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 2. Giao diện & ngôn ngữ
            [
                'key' => 'default_locale',
                'value' => 'vi',
                'type' => 'string',
                'group' => 'interface',
                'description' => 'Ngôn ngữ mặc định hệ thống (vi / en)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'default_theme',
                'value' => 'light',
                'type' => 'string',
                'group' => 'interface',
                'description' => 'Chế độ giao diện mặc định (light / dark)',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 3. Bán hàng & thanh toán
            [
                'key' => 'payment_cod_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'sales',
                'description' => 'Cho phép thanh toán khi nhận hàng (COD)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'payment_vnpay_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'sales',
                'description' => 'Cho phép thanh toán trực tuyến qua cổng VNPay',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'vnpay_timeout_minutes',
                'value' => '15',
                'type' => 'integer',
                'group' => 'sales',
                'description' => 'Thời gian hết hạn phiên thanh toán VNPay (phút)',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 4. Kho & hạn sử dụng
            [
                'key' => 'fresh_flower_expiry_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'inventory',
                'description' => 'Hạn sử dụng mặc định của hoa tươi (ngày)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'finished_bouquet_expiry_days',
                'value' => '5',
                'type' => 'integer',
                'group' => 'inventory',
                'description' => 'Hạn sử dụng mặc định của bó hoa thành phẩm (ngày)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'expiry_warning_days',
                'value' => '2',
                'type' => 'integer',
                'group' => 'inventory',
                'description' => 'Số ngày phát cảnh báo trước khi đến hạn sử dụng (ngày)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'low_stock_threshold',
                'value' => '10',
                'type' => 'integer',
                'group' => 'inventory',
                'description' => 'Ngưỡng cảnh báo tồn kho thấp (đơn vị sản phẩm)',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 5. Khách hàng & điểm thưởng
            [
                'key' => 'compensation_points',
                'value' => '20',
                'type' => 'integer',
                'group' => 'loyalty',
                'description' => 'Số điểm tích lũy đền bù cho khách hàng khi giao hàng có sự cố (điểm)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('cau_hinh_he_thong')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_he_thong');
    }
};
