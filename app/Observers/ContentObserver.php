<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\RevalidationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Registered on every content model (see AppServiceProvider::CONTENT_MODELS).
 * Seeding runs with model events disabled, so it never triggers revalidation.
 */
class ContentObserver
{
    public function __construct(private readonly RevalidationService $revalidation) {}

    public function saved(Model $model): void
    {
        $this->revalidation->touchModel($model);
    }

    public function deleted(Model $model): void
    {
        $this->revalidation->touchModel($model);
    }

    public function restored(Model $model): void
    {
        $this->revalidation->touchModel($model);
    }
}
