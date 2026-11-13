<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RevalidateFrontend;
use App\Support\ApiCache;
use App\Support\CacheTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Collects cache tags touched during a request and, when the app terminates,
 * flushes the local API cache for them and queues ONE frontend revalidation.
 *
 * Bound as a singleton; flush() is registered on app()->terminating() in AppServiceProvider.
 */
final class RevalidationService
{
    /** @var array<string, true> */
    private array $pending = [];

    public function __construct(private readonly ApiCache $cache) {}

    /** @param  list<string>  $tags */
    public function touch(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->pending[$tag] = true;
        }
    }

    public function touchModel(Model $model): void
    {
        $this->touch(CacheTags::for($model));
    }

    /** @return list<string> */
    public function pending(): array
    {
        return array_keys($this->pending);
    }

    public function flush(): void
    {
        if ($this->pending === []) {
            return;
        }

        $tags = $this->pending();
        $this->pending = [];

        $this->cache->flush($tags);

        try {
            RevalidateFrontend::dispatch($tags);
        } catch (Throwable $e) {
            // Never let revalidation break an admin save (e.g. sync queue + frontend down).
            Log::warning('Frontend revalidation could not be dispatched.', ['tags' => $tags, 'error' => $e->getMessage()]);
        }
    }
}
