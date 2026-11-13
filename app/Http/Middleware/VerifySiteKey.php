<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the shared X-Site-Key header on read (GET/HEAD) API requests.
 *
 * In the local environment the check is skipped when the header is absent,
 * so endpoints can be opened directly in a browser during development.
 * A header that IS sent must always be correct.
 */
class VerifySiteKey
{
    public const HEADER = 'X-Site-Key';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $provided = $request->header(self::HEADER);

        if ($provided === null && app()->environment('local')) {
            return $next($request);
        }

        if (! self::isValid($request)) {
            return response()->json(['message' => 'Invalid site key.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    /** True when the request carries the correct X-Site-Key (constant-time compare). */
    public static function isValid(Request $request): bool
    {
        $expected = (string) config('pixellap.api_site_key');
        $provided = $request->header(self::HEADER);

        return $expected !== '' && is_string($provided) && hash_equals($expected, $provided);
    }
}
