<?php

namespace App\Services;

use App\Models\CauHinhHeThong;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemConfigService
{
    public const CACHE_KEY = 'system_config_map';
    public const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * Fallback mặc định an toàn cho từng key (tránh crash khi DB chưa sẵn sàng hoặc thiếu record)
     */
    private const DEFAULTS = [
        'shop_name'                    => 'RoseShop',
        'shop_logo'                    => null,
        'shop_phone'                   => '0901234567',
        'shop_email'                   => 'roseshop@gmail.com',
        'shop_address'                 => '123 Đường Hoa Hồng, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
        'opening_time'                 => '07:00',
        'closing_time'                 => '22:00',
        'facebook_url'                 => 'https://facebook.com/roseshop.flower',
        'zalo_url'                     => 'https://zalo.me/0901234567',
        'default_locale'               => 'vi',
        'default_theme'                => 'light',
        'payment_cod_enabled'          => true,
        'payment_vnpay_enabled'        => true,
        'vnpay_timeout_minutes'        => 15,
        'fresh_flower_expiry_days'     => 7,
        'finished_bouquet_expiry_days' => 5,
        'expiry_warning_days'          => 2,
        'low_stock_threshold'          => 10,
        'compensation_points'          => 20,
    ];

    /**
     * Whitelist các key cấu hình được phép cập nhật theo từng nhóm
     */
    public const WHITELIST_GROUPS = [
        'shop' => [
            'shop_name',
            'shop_logo',
            'shop_phone',
            'shop_email',
            'shop_address',
            'opening_time',
            'closing_time',
            'facebook_url',
            'zalo_url',
        ],
        'interface' => [
            'default_locale',
            'default_theme',
        ],
        'sales' => [
            'payment_cod_enabled',
            'payment_vnpay_enabled',
            'vnpay_timeout_minutes',
        ],
        'inventory' => [
            'fresh_flower_expiry_days',
            'finished_bouquet_expiry_days',
            'expiry_warning_days',
            'low_stock_threshold',
        ],
        'loyalty' => [
            'compensation_points',
        ],
    ];

    /**
     * Lấy toàn bộ cấu hình dưới dạng associative array [key => value]
     * Sử dụng Cache để tối ưu hiệu năng không query DB nhiều lần.
     */
    public static function all(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
                if (!Schema::hasTable('cau_hinh_he_thong')) {
                    return self::DEFAULTS;
                }

                $records = CauHinhHeThong::all();
                $result = self::DEFAULTS;

                foreach ($records as $item) {
                    $result[$item->key] = self::castValue($item->value, $item->type);
                }

                return $result;
            });
        } catch (Throwable $e) {
            return self::DEFAULTS;
        }
    }

    /**
     * Lấy giá trị của một config key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        if ($default !== null) {
            return $default;
        }

        return self::DEFAULTS[$key] ?? null;
    }

    /**
     * Lấy giá trị dạng int
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $val = self::get($key, $default);
        return is_numeric($val) ? (int) $val : $default;
    }

    /**
     * Lấy giá trị dạng boolean
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $val = self::get($key, $default);
        if (is_bool($val)) {
            return $val;
        }
        if (is_string($val)) {
            return in_array(strtolower($val), ['1', 'true', 'on', 'yes'], true);
        }
        if (is_numeric($val)) {
            return (int) $val === 1;
        }
        return (bool) $val;
    }

    /**
     * Lấy logo URL của cửa hàng (ưu tiên logo tải lên, fallback về logo mặc định asset('img/logo.png'))
     */
    public static function getLogoUrl(): string
    {
        $logo = self::get('shop_logo');
        if (!empty($logo)) {
            if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
                return $logo;
            }
            if (Storage::disk('public')->exists($logo)) {
                return asset('storage/' . $logo);
            }
        }
        return asset('img/logo.png');
    }

    /**
     * Cập nhật một config key đơn lẻ (bắt buộc nằm trong whitelist)
     */
    public static function set(string $key, mixed $value): bool
    {
        if (!self::isKeyWhitelisted($key)) {
            return false;
        }

        $type = self::detectType($value);
        $storedValue = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        CauHinhHeThong::updateOrCreate(
            ['key' => $key],
            [
                'value'      => $storedValue,
                'type'       => $type,
                'updated_at' => now(),
            ]
        );

        self::clearCache();
        return true;
    }

    /**
     * Cập nhật nhiều key theo nhóm với kiểm tra whitelist nghiêm ngặt
     */
    public static function updateGroup(string $group, array $values): int
    {
        if (!isset(self::WHITELIST_GROUPS[$group])) {
            return 0;
        }

        $allowedKeys = self::WHITELIST_GROUPS[$group];
        $count = 0;

        foreach ($values as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                continue;
            }

            $type = self::detectType($value);
            $storedValue = is_bool($value) ? ($value ? '1' : '0') : ($value === null ? null : (string) $value);

            CauHinhHeThong::updateOrCreate(
                ['key' => $key],
                [
                    'value'      => $storedValue,
                    'type'       => $type,
                    'group'      => $group,
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        self::clearCache();
        return $count;
    }

    /**
     * Xóa cache để dữ liệu mới có hiệu lực ngay lập tức
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Kiểm tra xem key có thuộc danh sách được phép sửa hay không
     */
    public static function isKeyWhitelisted(string $key): bool
    {
        foreach (self::WHITELIST_GROUPS as $groupKeys) {
            if (in_array($key, $groupKeys, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ép kiểu dữ liệu tương ứng từ database
     */
    private static function castValue(?string $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Tự động nhận diện kiểu dữ liệu
     */
    private static function detectType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }
}
