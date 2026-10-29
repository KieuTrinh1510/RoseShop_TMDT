<?php

namespace App\Http\Middleware;

use App\Services\SystemConfigService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales in RoseShop.
     */
    public const SUPPORTED_LOCALES = ['vi', 'en'];
    public const FALLBACK_LOCALE = 'vi';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if (!$locale || !in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = SystemConfigService::get('default_locale', self::FALLBACK_LOCALE);
        }

        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = self::FALLBACK_LOCALE;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
