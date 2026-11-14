<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production only: the admin panel is served over HTTPS exclusively.
 * GET/HEAD are redirected to https://, anything else is refused (no credentials over plain HTTP).
 * Behind Nginx, $request->secure() comes from the FastCGI HTTPS param.
 */
class RequireHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() || $request->secure()) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        abort(403, 'HTTPS required.');
    }
}
