<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\ProcessStep;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\UiString;
use App\Models\User;
use App\Observers\ContentObserver;
use App\Observers\MediaObserver;
use App\Policies\ActivityPolicy;
use App\Services\RevalidationService;
use App\Support\ApiCache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Role that bypasses every permission check.
     */
    public const SUPER_ADMIN_ROLE = 'super_admin';

    /**
     * Models whose changes revalidate the website (see App\Support\CacheTags).
     */
    public const CONTENT_MODELS = [
        Setting::class, UiString::class, HomeSection::class, Stat::class, ProcessStep::class,
        Client::class, Faq::class, ServiceCategory::class, Service::class, Page::class, PageBlock::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApiCache::class);
        $this->app->singleton(RevalidationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Vendor model: not auto-discovered from App\Policies.
        Gate::policy(Activity::class, ActivityPolicy::class);

        Gate::before(function ($user, string $ability, array $arguments): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            // Nobody — not even a super admin — can delete their own account.
            if (in_array($ability, ['delete', 'forceDelete'], true)
                && ($arguments[0] ?? null) instanceof User
                && $arguments[0]->is($user)) {
                return false;
            }

            // Super Admin passes every other Gate / policy check. Returning null
            // lets normal permission checks run for everyone else.
            return $user->hasRole(self::SUPER_ADMIN_ROLE) ? true : null;
        });

        // Revalidation: collect tags during the request, flush once at the end.
        foreach (self::CONTENT_MODELS as $model) {
            $model::observe(ContentObserver::class);
        }
        Media::observe(MediaObserver::class);

        $this->app->terminating(fn () => $this->app->make(RevalidationService::class)->flush());
    }
}
