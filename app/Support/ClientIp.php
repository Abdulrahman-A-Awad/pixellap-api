<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Middleware\VerifySiteKey;
use Illuminate\Http\Request;

/**
 * The visitor's IP for requests relayed by the Next.js server.
 *
 * Next.js calls POST /contact server-side, so $request->ip() is the Next server for
 * every visitor. It forwards the visitor IP in X-Client-IP. That header is trusted
 * ONLY together with a valid X-Site-Key (otherwise anyone could spoof it to dodge
 * the rate limit); in every other case the connecting IP is used.
 */
final class ClientIp
{
    public const HEADER = 'X-Client-IP';

    public static function resolve(Request $request): string
    {
        $forwarded = $request->header(self::HEADER);

        if (
            VerifySiteKey::isValid($request)
            && is_string($forwarded)
            && filter_var(trim($forwarded), FILTER_VALIDATE_IP) !== false
        ) {
            return trim($forwarded);
        }

        return (string) $request->ip();
    }
}
