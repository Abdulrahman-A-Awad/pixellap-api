<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetAdminLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The ONLY admin language switch (user menu → "English" / "العربية").
 * Writes users.admin_locale, which SetAdminLocale reads on every panel request, and a cookie
 * so the login page of this browser opens in the same language.
 */
class SwitchAdminLocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('app.available_locales', []), true), 404);

        $request->user()->forceFill(['admin_locale' => $locale])->save();
        app()->setLocale($locale);

        // Remembered for the login page of this browser (5 years).
        return redirect()->back(fallback: '/admin')
            ->withCookie(cookie()->forever(SetAdminLocale::COOKIE, $locale));
    }

    /** The language the menu offers: the other one. */
    public static function other(): string
    {
        return SetAdminLocale::current() === 'ar' ? 'en' : 'ar';
    }
}
