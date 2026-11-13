<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Flips the current user's admin UI language between English and Arabic.
 */
class ToggleAdminLocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'admin_locale' => $user->admin_locale === 'ar' ? 'en' : 'ar',
        ])->save();

        return redirect()->back(fallback: '/admin');
    }
}
