<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Tag-aware cache for API responses that works on any cache driver (file/database
 * locally, no Redis): each tag keeps a registry of its keys under "api_tags:{tag}".
 */
final class ApiCache
{
    public const TTL = 3600;

    private const REGISTRY_PREFIX = 'api_tags:';

    /**
     * @template T
     *
     * @param  list<string>  $tags
     * @param  Closure(): T  $callback
     * @return T
     */
    public function remember(string $key, array $tags, Closure $callback): mixed
    {
        return Cache::remember($key, self::TTL, function () use ($key, $tags, $callback) {
            $value = $callback();

            foreach ($tags as $tag) {
                $this->register($tag, $key);
            }

            return $value;
        });
    }

    /** @param  list<string>  $tags */
    public function flush(array $tags): void
    {
        foreach ($tags as $tag) {
            foreach (Cache::get(self::REGISTRY_PREFIX.$tag, []) as $key) {
                Cache::forget($key);
            }

            Cache::forget(self::REGISTRY_PREFIX.$tag);
        }
    }

    private function register(string $tag, string $key): void
    {
        $keys = Cache::get(self::REGISTRY_PREFIX.$tag, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::REGISTRY_PREFIX.$tag, $keys);
        }
    }
}
