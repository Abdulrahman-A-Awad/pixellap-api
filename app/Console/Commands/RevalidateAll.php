<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RevalidateFrontend;
use App\Support\ApiCache;
use App\Support\CacheTags;
use Illuminate\Console\Command;
use Throwable;

/**
 * Full website refresh (same as the dashboard's "Refresh website cache"), run synchronously:
 * drops every cached API response and tells Next.js to revalidate every tag.
 * Called by the deploy scripts after migrations / seeding / a new frontend build.
 */
class RevalidateAll extends Command
{
    protected $signature = 'pixellap:revalidate-all';

    protected $description = 'Flush the API cache and revalidate every page of the Next.js site';

    public function handle(ApiCache $cache): int
    {
        $tags = CacheTags::all();
        $cache->flush($tags);
        $this->components->info('API cache flushed ('.count($tags).' tags).');

        if ((string) config('pixellap.frontend_revalidate_url') === '') {
            $this->components->warn('FRONTEND_REVALIDATE_URL is empty: the website was not asked to revalidate.');

            return self::FAILURE;
        }

        try {
            RevalidateFrontend::dispatchSync($tags);
        } catch (Throwable $e) {
            $this->components->error('Website revalidation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Website revalidated.');

        return self::SUCCESS;
    }
}
