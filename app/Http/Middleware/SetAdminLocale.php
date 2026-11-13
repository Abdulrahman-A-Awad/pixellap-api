<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the signed-in user's admin_locale to the Filament panel.
 * Filament switches to RTL automatically when the locale is "ar".
 *
 * Registered as persistent panel middleware so Livewire requests get it too.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && in_array($user->admin_locale, config('app.available_locales', []), true)) {
            app()->setLocale($user->admin_locale);
        }

        return $next($request);
    }
}
