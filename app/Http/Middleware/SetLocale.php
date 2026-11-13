<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale from `?locale=` or the Accept-Language header,
 * restricted to config('app.available_locales').
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $available */
        $available = config('app.available_locales', ['en']);

        $requested = $request->query('locale');

        $locale = is_string($requested) && in_array($requested, $available, true)
            ? $requested
            : $this->fromHeader($request, $available);

        app()->setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    /**
     * @param  list<string>  $available
     */
    private function fromHeader(Request $request, array $available): string
    {
        if (! $request->headers->has('Accept-Language')) {
            return (string) config('app.locale', $available[0]);
        }

        // Symfony matches "ar-AE,ar;q=0.9" style headers against the allowed list
        // and falls back to the first allowed locale when nothing matches.
        return $request->getPreferredLanguage($available) ?? $available[0];
    }
}
