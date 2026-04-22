<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController
{
    private const ALLOWED_LOCALES = ['en', 'km'];

    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::ALLOWED_LOCALES, true), 404);

        session(['locale' => $locale]);
        app()->setLocale($locale);

        $redirect = $request->query('redirect');

        if (is_string($redirect) && $redirect !== '' && str_starts_with($redirect, url('/'))) {
            return redirect()->to($redirect);
        }

        return back();
    }
}
