<?php

return [
    'system_settings'              => 'Cấu hình hệ thống',
    'system_settings_desc'         => 'Quản lý các thiết lập vận hành chung của RoseShop.',

    // Tabs
    'tab_shop'                     => 'Thông tin cửa hàng',
    'tab_interface'                => 'Giao diện & ngôn ngữ',
    'tab_sales'                    => 'Bán hàng & thanh toán',
    'tab_inventory'                => 'Kho & hạn sử dụng',
    'tab_flash_sale'               => 'Flash Sale',
    'tab_loyalty'                  => 'Khách hàng & điểm thưởng',
    'tab_violation'                => 'Chính sách vi phạm & COD',

    // Shop settings
    'shop_name'                    => 'Tên cửa hàng',
    'shop_logo'                    => 'Logo cửa hàng',
    'shop_logo_help'               => 'Định dạng hỗ trợ: JPG, PNG, WEBP, SVG (tối đa 2MB).',
    'shop_phone'                   => 'Số điện thoại',
    'shop_email'                   => 'Email liên hệ',
    'shop_address'                 => 'Địa chỉ cửa hàng',
    'opening_time'                 => 'Giờ mở cửa',
    'closing_time'                 => 'Giờ đóng cửa',
    'facebook_url'                 => 'Facebook Fanpage',
    'zalo_url'                     => 'Zalo CSKH',

    // Interface & Language
    'default_locale'               => 'Ngôn ngữ mặc định',
    'locale_vi'                    => 'Tiếng Việt',
    'locale_en'                    => 'English',
    'default_theme'                => 'Giao diện mặc định',
    'theme_light'                  => 'Sáng (Light Mode)',
    'theme_light_desc'             => 'Nền trắng sáng, chữ tối tiêu chuẩn',
    'theme_dark'                   => 'Tối (Dark Mode)',
    'theme_dark_desc'              => 'Nền tối, chữ sáng dịu mắt',

    // Sales & Payment
    'payment_cod'                  => 'Cho phép thanh toán COD',
    'payment_cod_desc'             => 'Khách hàng có thể thanh toán tiền mặt khi nhận hàng',
    'payment_vnpay'                => 'Cho phép thanh toán VNPay',
    'payment_vnpay_desc'           => 'Khách hàng có thể thanh toán trực tuyến qua cổng VNPay / Ngân hàng',
    'vnpay_timeout'                => 'Thời gian hiệu lực thanh toán VNPay',
    'vnpay_timeout_help'           => 'Thời gian hiệu lực của liên kết thanh toán VNPay trước khi hết hạn.',
    'vnpay_gateway_status'         => 'Trạng thái kết nối VNPay',
    'vnpay_configured'             => 'Đã cấu hình trong môi trường hệ thống',
    'vnpay_not_configured'         => 'Chưa cấu hình TMN Code / Hash Secret',

    // Inventory & Expiry
    'fresh_flower_expiry'          => 'HSD mặc định hoa tươi',
    'fresh_flower_expiry_help'     => 'Áp dụng khi tạo mới lô hàng nhập hoa tươi.',
    'finished_bouquet_expiry'      => 'HSD mặc định bó hoa thành phẩm',
    'finished_bouquet_expiry_help' => 'Áp dụng khi hoàn thành bó hoa thành phẩm mới.',
    'expiry_warning'               => 'Cảnh báo cận hạn trước',
    'expiry_warning_help'          => 'Hệ thống sẽ phát thông báo cảnh báo trước số ngày này.',
    'low_stock'                    => 'Ngưỡng cảnh báo tồn kho thấp',
    'low_stock_help'               => 'Sản phẩm có số lượng tồn kho dưới ngưỡng này sẽ được cảnh báo.',
    'hsd_notice_title'             => 'Chính sách quản lý Hạn sử dụng',
    'hsd_notice_body'              => 'Hạn sử dụng chỉ phục vụ mục đích giám sát, cảnh báo vận hành và hỗ trợ nhân viên kiểm tra chất lượng thực tế. Hệ thống không tự động hủy, không tự trừ kho hay tự động chuyển Flash Sale khi đến hạn.',

    // Flash Sale
    'allowed_discounts'            => 'Mức giảm giá được phép',
    'flash_sale_notice_title'      => 'Chính sách Flash Sale hệ thống',
    'flash_sale_notice_body'       => 'Hệ thống RoseShop quy định mức giảm giá cố định cho hàng hoàn và cận hạn gồm 3 mức: 30%, 50% và 75%. Khi tạo Flash Sale, sản phẩm chỉ được tạo suất khuyến mãi, không trừ số lượng tồn kho vật lý của lô nhập.',

    // Customer & Loyalty
    'compensation_points'          => 'Điểm bồi thường mặc định',
    'compensation_points_help'     => 'Số điểm tích lũy được tự động cộng vào tài khoản khách hàng khi xảy ra sự cố giao hàng thất bại / hàng hoàn cần đền bù.',

    // Violation & COD
    'violation_policy_title'       => 'Chính sách xử lý vi phạm & giới hạn COD',
    'violation_rule_1'             => '1 vi phạm chưa xử lý',
    'violation_rule_1_desc'        => 'Hệ thống gửi cảnh báo nhắc nhở. Khách hàng vẫn được phép mua hàng bình thường và vẫn được chọn phương thức COD.',
    'violation_rule_2'             => '2 vi phạm chưa xử lý',
    'violation_rule_2_desc'        => 'Hệ thống tự động khóa phương thức thanh toán COD. Khách hàng vẫn có thể đặt mua hàng nhưng bắt buộc phải thanh toán trực tuyến (VNPay).',
    'violation_rule_3'             => 'Từ 3 vi phạm trở lên',
    'violation_rule_3_desc'        => 'Tạm thời đình chỉ quyền mua hàng. Khách hàng bắt buộc phải thanh toán và xử lý hết các khoản vi phạm trước đó để được khôi phục quyền mua hàng.',

    // Sidebar navigation
    'nav_dashboard'                => 'Tổng quan',
    'nav_orders'                   => 'Đơn hàng',
    'nav_manage_orders'            => 'Quản lý đơn hàng',
    'nav_manage_invoices'          => 'Quản lý hóa đơn',
    'nav_products'                 => 'Sản phẩm',
    'nav_inventory'                => 'Quản lý kho',
    'nav_users'                    => 'Người dùng',
    'nav_delivery'                 => 'Giao hàng',
    'nav_reports'                  => 'Báo cáo & Thống kê',
    'nav_system'                   => 'Hệ thống',
    'nav_system_settings'          => 'Cấu hình hệ thống',
    'logout'                       => 'Đăng xuất',
];
