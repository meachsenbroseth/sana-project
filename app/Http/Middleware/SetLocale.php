<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const ALLOWED_LOCALES = ['en', 'km'];

    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = config('app.locale');

        if (! is_string($defaultLocale) || ! in_array($defaultLocale, self::ALLOWED_LOCALES, true)) {
            $defaultLocale = self::ALLOWED_LOCALES[0];
        }

        $locale = session('locale');

        if (! is_string($locale) || ! in_array($locale, self::ALLOWED_LOCALES, true)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
