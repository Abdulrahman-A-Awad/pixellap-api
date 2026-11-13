<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells the Next.js frontend to revalidate the given cache tags:
 * POST {FRONTEND_REVALIDATE_URL}  X-Revalidate-Secret: …  {"tags": [...]}
 */
class RevalidateFrontend implements ShouldQueue
{
    use Queueable;

    public int $timeout = 10;

    public int $tries = 3;

    /** @param  list<string>  $tags */
    public function __construct(public readonly array $tags) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(): void
    {
        $url = (string) config('pixellap.frontend_revalidate_url');

        if ($url === '' || $this->tags === []) {
            return;
        }

        Http::timeout(8)
            ->connectTimeout(3)
            ->acceptJson()
            ->withHeaders(['X-Revalidate-Secret' => (string) config('pixellap.revalidate_secret')])
            ->post($url, ['tags' => $this->tags])
            ->throw();
    }

    /** Final failure (after all tries): log only — never surfaces in the admin UI. */
    public function failed(?Throwable $exception): void
    {
        Log::warning('Frontend revalidation failed.', [
            'tags' => $this->tags,
            'error' => $exception?->getMessage(),
        ]);
    }
}
