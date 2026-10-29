<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CauHinhController extends Controller
{
    /**
     * Hiển thị trang Cấu hình hệ thống (Duy nhất 1 page với tab dọc bên trái)
     */
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $activeTab = $request->query('tab', 'shop');
        $validTabs = ['shop', 'interface', 'sales', 'inventory', 'flash_sale', 'loyalty', 'violation'];
        if (!in_array($activeTab, $validTabs, true)) {
            $activeTab = 'shop';
        }

        $configs = SystemConfigService::all();

        // Kiểm tra trạng thái VNPay mà TUYỆT ĐỐI không để lộ Secret Key
        $vnpayConfigured = !empty(env('VNP_TMN_CODE')) && !empty(env('VNP_HASH_SECRET'));

        // Chính sách Flash Sale hệ thống (Cố định - READ ONLY)
        $flashSalePolicies = [
            [
                'rate'        => '30%',
                'code'        => '30',
                'description' => 'Mức giảm nhẹ dành cho sản phẩm hoàn còn chất lượng cao hoặc cận hạn nhẹ.',
                'is_active'   => true,
            ],
            [
                'rate'        => '50%',
                'code'        => '50',
                'description' => 'Mức giảm tiêu chuẩn nhằm thu hồi vốn nhanh cho hàng hoàn và lô cận hạn.',
                'is_active'   => true,
            ],
            [
                'rate'        => '75%',
                'code'        => '75',
                'description' => 'Mức giảm xả kho tối đa cho hàng sắp hết hạn trong ngày.',
                'is_active'   => true,
            ],
        ];

        // Chính sách Vi phạm & COD (Cố định - READ ONLY)
        $violationPolicies = [
            [
                'level'       => 1,
                'title'       => __('admin.violation_rule_1'),
                'description' => __('admin.violation_rule_1_desc'),
                'status'      => 'warning',
                'badge'       => 'Cảnh báo',
                'cod_allowed' => true,
                'buy_allowed' => true,
            ],
            [
                'level'       => 2,
                'title'       => __('admin.violation_rule_2'),
                'description' => __('admin.violation_rule_2_desc'),
                'status'      => 'danger',
                'badge'       => 'Khóa COD',
                'cod_allowed' => false,
                'buy_allowed' => true,
            ],
            [
                'level'       => 3,
                'title'       => __('admin.violation_rule_3'),
                'description' => __('admin.violation_rule_3_desc'),
                'status'      => 'dark',
                'badge'       => 'Khóa mua hàng',
                'cod_allowed' => false,
                'buy_allowed' => false,
            ],
        ];

        return view('admin.CauHinh.index', [
            'activeTab'          => $activeTab,
            'configs'            => $configs,
            'vnpayConfigured'    => $vnpayConfigured,
            'flashSalePolicies'  => $flashSalePolicies,
            'violationPolicies'  => $violationPolicies,
        ]);
    }

    /**
     * Cập nhật cấu hình theo từng nhóm với validate chặt chẽ
     */
    public function update(Request $request)
    {
        $this->ensureAdmin();

        $group = $request->input('group');
        $validGroups = ['shop', 'interface', 'sales', 'inventory', 'loyalty'];

        if (!in_array($group, $validGroups, true)) {
            return redirect()->route('admin.he-thong.cau-hinh')
                ->with('error', 'Nhóm cấu hình không hợp lệ.');
        }

        $valuesToUpdate = [];

        // 1. Nhóm Thông tin cửa hàng
        if ($group === 'shop') {
            $validated = $request->validate([
                'shop_name'     => 'required|string|max:100',
                'shop_phone'    => 'nullable|string|max:20',
                'shop_email'    => 'nullable|email|max:100',
                'shop_address'  => 'nullable|string|max:255',
                'opening_time'  => 'nullable|string|max:10',
                'closing_time'  => 'nullable|string|max:10',
                'facebook_url'  => 'nullable|url|max:255',
                'zalo_url'      => 'nullable|url|max:255',
                'shop_logo'     => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            ], [
                'shop_name.required' => 'Tên cửa hàng không được để trống.',
                'shop_email.email'   => 'Địa chỉ email không đúng định dạng.',
                'shop_logo.image'    => 'File tải lên phải là hình ảnh hợp lệ.',
                'shop_logo.mimes'    => 'Logo chỉ chấp nhận định dạng: JPG, PNG, WEBP, SVG.',
                'shop_logo.max'      => 'Dung lượng logo không được vượt quá 2MB.',
                'facebook_url.url'   => 'Đường dẫn Facebook phải là một URL hợp lệ.',
                'zalo_url.url'       => 'Đường dẫn Zalo phải là một URL hợp lệ.',
            ]);

            $valuesToUpdate = [
                'shop_name'    => $validated['shop_name'],
                'shop_phone'   => $validated['shop_phone'] ?? null,
                'shop_email'   => $validated['shop_email'] ?? null,
                'shop_address' => $validated['shop_address'] ?? null,
                'opening_time' => $validated['opening_time'] ?? null,
                'closing_time' => $validated['closing_time'] ?? null,
                'facebook_url' => $validated['facebook_url'] ?? null,
                'zalo_url'     => $validated['zalo_url'] ?? null,
            ];

            // Xử lý upload Logo an toàn (nếu có lỗi không làm mất logo hiện tại)
            if ($request->hasFile('shop_logo')) {
                try {
                    $file = $request->file('shop_logo');
                    $path = $file->store('logos', 'public');
                    if ($path) {
                        $valuesToUpdate['shop_logo'] = $path;
                    }
                } catch (Throwable $e) {
                    Log::error('Lỗi khi tải logo cửa hàng: ' . $e->getMessage());
                }
            }
        }

        // 2. Nhóm Giao diện & ngôn ngữ
        elseif ($group === 'interface') {
            $validated = $request->validate([
                'default_locale' => 'required|in:vi,en',
                'default_theme'  => 'required|in:light,dark',
            ], [
                'default_locale.in' => 'Ngôn ngữ chỉ chấp nhận Tiếng Việt (vi) hoặc English (en).',
                'default_theme.in'  => 'Giao diện chỉ chấp nhận Sáng (light) hoặc Tối (dark).',
            ]);

            $valuesToUpdate = [
                'default_locale' => $validated['default_locale'],
                'default_theme'  => $validated['default_theme'],
            ];

            // Cập nhật session và runtime locale ngay lập tức
            session(['locale' => $validated['default_locale']]);
            App::setLocale($validated['default_locale']);
        }

        // 3. Nhóm Bán hàng & thanh toán
        elseif ($group === 'sales') {
            $validated = $request->validate([
                'payment_cod_enabled'   => 'nullable|boolean',
                'payment_vnpay_enabled' => 'nullable|boolean',
                'vnpay_timeout_minutes' => 'required|integer|min:1|max:1440',
            ], [
                'vnpay_timeout_minutes.required' => 'Thời gian hết hạn VNPay không được để trống.',
                'vnpay_timeout_minutes.integer'  => 'Thời gian hết hạn VNPay phải là số nguyên phút.',
                'vnpay_timeout_minutes.min'      => 'Thời gian hết hạn VNPay phải lớn hơn 0 phút.',
                'vnpay_timeout_minutes.max'      => 'Thời gian hết hạn VNPay không được vượt quá 1440 phút (24h).',
            ]);

            $valuesToUpdate = [
                'payment_cod_enabled'   => $request->boolean('payment_cod_enabled'),
                'payment_vnpay_enabled' => $request->boolean('payment_vnpay_enabled'),
                'vnpay_timeout_minutes' => (int) $validated['vnpay_timeout_minutes'],
            ];
        }

        // 4. Nhóm Kho & hạn sử dụng
        elseif ($group === 'inventory') {
            $validated = $request->validate([
                'fresh_flower_expiry_days'     => 'required|integer|min:1|max:365',
                'finished_bouquet_expiry_days' => 'required|integer|min:1|max:365',
                'expiry_warning_days'          => 'required|integer|min:0|max:60',
                'low_stock_threshold'          => 'required|integer|min:0|max:10000',
            ], [
                'fresh_flower_expiry_days.min'     => 'HSD hoa tươi phải từ 1 ngày trở lên.',
                'finished_bouquet_expiry_days.min' => 'HSD bó hoa thành phẩm phải từ 1 ngày trở lên.',
                'expiry_warning_days.min'          => 'Cảnh báo cận hạn trước phải >= 0 ngày.',
                'low_stock_threshold.min'          => 'Ngưỡng tồn kho thấp phải >= 0 đơn vị.',
            ]);

            $valuesToUpdate = [
                'fresh_flower_expiry_days'     => (int) $validated['fresh_flower_expiry_days'],
                'finished_bouquet_expiry_days' => (int) $validated['finished_bouquet_expiry_days'],
                'expiry_warning_days'          => (int) $validated['expiry_warning_days'],
                'low_stock_threshold'          => (int) $validated['low_stock_threshold'],
            ];
        }

        // 5. Nhóm Khách hàng & điểm thưởng
        elseif ($group === 'loyalty') {
            $validated = $request->validate([
                'compensation_points' => 'required|integer|min:0|max:10000',
            ], [
                'compensation_points.required' => 'Điểm bồi thường không được để trống.',
                'compensation_points.min'      => 'Điểm bồi thường phải >= 0 điểm.',
            ]);

            $valuesToUpdate = [
                'compensation_points' => (int) $validated['compensation_points'],
            ];
        }

        // Thực hiện cập nhật an toàn qua whitelist
        SystemConfigService::updateGroup($group, $valuesToUpdate);

        return redirect()->route('admin.he-thong.cau-hinh', ['tab' => $group])
            ->with('success', __('common.updated_successfully'));
    }

    /**
     * Kiểm tra quyền ADMIN chặt chẽ
     */
    private function ensureAdmin(): void
    {
        if (!Auth::check() || Auth::user()->vai_tro !== 'ADMIN') {
            abort(403, 'Bạn không có quyền truy cập trang Cấu hình hệ thống.');
        }
    }
}
