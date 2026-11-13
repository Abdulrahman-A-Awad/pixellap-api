<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Builds the public API envelope and caches GET responses per endpoint + params + locale.
 *
 *   { "data": ..., "meta": { "locale": "ar", "generated_at": "2026-11-13T10:00:00+04:00" } }
 *
 * The encoded JSON string is cached (not the resource objects), so a cache hit
 * returns exactly the bytes that were generated, including `generated_at`.
 */
final class ApiResponder
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    public function __construct(private readonly ApiCache $cache) {}

    /**
     * @param  string  $endpoint  endpoint + params, e.g. "pages/about"
     * @param  list<string>  $tags  see App\Support\CacheTags
     * @param  Closure(): mixed  $data  resource / array to put under "data"
     */
    public function cached(string $endpoint, array $tags, Closure $data): JsonResponse
    {
        $key = 'api:v1:'.$endpoint.':'.app()->getLocale();

        $json = $this->cache->remember($key, $tags, fn (): string => $this->encode($data()));

        return JsonResponse::fromJsonString($json);
    }

    public function make(mixed $data, int $status = 200): JsonResponse
    {
        return JsonResponse::fromJsonString($this->encode($data), $status);
    }

    private function encode(mixed $data): string
    {
        return json_encode([
            'data' => $data,
            'meta' => [
                'locale' => app()->getLocale(),
                'generated_at' => now()->toIso8601String(),
            ],
        ], self::JSON_FLAGS);
    }
}
