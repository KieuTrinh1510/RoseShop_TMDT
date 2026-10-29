<?php

return [
    'system_settings'              => 'System Settings',
    'system_settings_desc'         => 'Manage operational and global configuration for RoseShop.',

    // Tabs
    'tab_shop'                     => 'Store Information',
    'tab_interface'                => 'Interface & Language',
    'tab_sales'                    => 'Sales & Payment',
    'tab_inventory'                => 'Inventory & Expiry',
    'tab_flash_sale'               => 'Flash Sale',
    'tab_loyalty'                  => 'Customer & Loyalty',
    'tab_violation'                => 'Violation & COD Policy',

    // Shop settings
    'shop_name'                    => 'Store Name',
    'shop_logo'                    => 'Store Logo',
    'shop_logo_help'               => 'Supported formats: JPG, PNG, WEBP, SVG (max 2MB).',
    'shop_phone'                   => 'Phone Number',
    'shop_email'                   => 'Contact Email',
    'shop_address'                 => 'Store Address',
    'opening_time'                 => 'Opening Time',
    'closing_time'                 => 'Closing Time',
    'facebook_url'                 => 'Facebook Fanpage',
    'zalo_url'                     => 'Zalo Support URL',

    // Interface & Language
    'default_locale'               => 'Default Language',
    'locale_vi'                    => 'Tiếng Việt (Vietnamese)',
    'locale_en'                    => 'English',
    'default_theme'                => 'Default Theme',
    'theme_light'                  => 'Light Mode',
    'theme_light_desc'             => 'Standard light background with dark text',
    'theme_dark'                   => 'Dark Mode',
    'theme_dark_desc'              => 'Dark background with soothing eye-friendly contrast',

    // Sales & Payment
    'payment_cod'                  => 'Enable Cash on Delivery (COD)',
    'payment_cod_desc'             => 'Allow customers to pay in cash upon receiving goods',
    'payment_vnpay'                => 'Enable VNPay Online Payment',
    'payment_vnpay_desc'           => 'Allow customers to pay online via VNPay / Bank cards',
    'vnpay_timeout'                => 'VNPay Payment Timeout',
    'vnpay_timeout_help'           => 'Validity duration of payment link before expiry.',
    'vnpay_gateway_status'         => 'VNPay Gateway Connection Status',
    'vnpay_configured'             => 'Configured in system environment',
    'vnpay_not_configured'         => 'Missing TMN Code / Hash Secret configuration',

    // Inventory & Expiry
    'fresh_flower_expiry'          => 'Default Fresh Flower Expiry',
    'fresh_flower_expiry_help'     => 'Applied when receiving new fresh flower lots.',
    'finished_bouquet_expiry'      => 'Default Finished Bouquet Expiry',
    'finished_bouquet_expiry_help' => 'Applied when assembling new finished bouquets.',
    'expiry_warning'               => 'Expiry Warning Advance Notice',
    'expiry_warning_help'          => 'System will issue advance notifications this many days prior to expiry.',
    'low_stock'                    => 'Low Stock Warning Threshold',
    'low_stock_help'               => 'Products with inventory below this quantity will trigger warnings.',
    'hsd_notice_title'             => 'Expiry Date Management Policy',
    'hsd_notice_body'              => 'Expiry dates are strictly utilized for monitoring, warning alerts, and assisting staff during physical quality inspections. The system does not automatically dispose of stock or auto-convert lots to Flash Sale.',

    // Flash Sale
    'allowed_discounts'            => 'Allowed Discount Rates',
    'flash_sale_notice_title'      => 'Fixed System Flash Sale Policy',
    'flash_sale_notice_body'       => 'RoseShop enforces fixed discount tiers of 30%, 50%, and 75% for returned and near-expiry items. Creating a Flash Sale allocates a sale promotion quota without reducing physical stock quantity from the inventory lot.',

    // Customer & Loyalty
    'compensation_points'          => 'Default Compensation Points',
    'compensation_points_help'     => 'Loyalty points automatically awarded to customer accounts when delivery failure / return issues occur.',

    // Violation & COD
    'violation_policy_title'       => 'Violation Handling & COD Restriction Policy',
    'violation_rule_1'             => '1 Pending Violation',
    'violation_rule_1_desc'        => 'System reminder issued. Customer can still make purchases and use COD.',
    'violation_rule_2'             => '2 Pending Violations',
    'violation_rule_2_desc'        => 'COD payment locked. Customer can still make purchases but must pay online via VNPay.',
    'violation_rule_3'             => '3 or More Pending Violations',
    'violation_rule_3_desc'        => 'Purchasing privileges temporarily suspended. Customer must resolve and pay all pending violations first.',

    // Sidebar navigation
    'nav_dashboard'                => 'Dashboard',
    'nav_orders'                   => 'Orders',
    'nav_manage_orders'            => 'Manage Orders',
    'nav_manage_invoices'          => 'Manage Invoices',
    'nav_products'                 => 'Products',
    'nav_inventory'                => 'Inventory',
    'nav_users'                    => 'Users',
    'nav_delivery'                 => 'Delivery',
    'nav_reports'                  => 'Reports & Analytics',
    'nav_system'                   => 'System',
    'nav_system_settings'          => 'System Settings',
    'logout'                       => 'Log out',
];
