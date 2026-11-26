<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the admin UI language to the Filament panel. Filament switches to RTL for "ar".
 *
 * - Signed in: users.admin_locale (Arabic when unset or invalid).
 * - Signed out (login page): the last language chosen in this browser (cookie set by the
 *   user-menu switch), else Arabic.
 * The column and the cookie are written only by the user-menu switch (SwitchAdminLocaleController).
 *
 * Registered as persistent panel middleware so Livewire requests get it too.
 */
class SetAdminLocale
{
    public const DEFAULT = 'ar';

    public const COOKIE = 'admin_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        app()->setLocale($user instanceof User
            ? self::current($user)
            : self::valid($request->cookie(self::COOKIE)) ?? self::DEFAULT);

        return $next($request);
    }

    public static function current(?User $user = null): string
    {
        $user ??= auth()->user();

        return self::valid($user instanceof User ? $user->admin_locale : null) ?? self::DEFAULT;
    }

    private static function valid(mixed $locale): ?string
    {
        return is_string($locale) && in_array($locale, config('app.available_locales', []), true) ? $locale : null;
    }
}
