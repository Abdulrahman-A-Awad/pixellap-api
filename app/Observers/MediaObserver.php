<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\RevalidationService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media updates/deletions revalidate the owner model's tags.
 * (Additions are handled by App\Listeners\HandleMediaAdded.)
 */
class MediaObserver
{
    public function __construct(private readonly RevalidationService $revalidation) {}

    public function updated(Media $media): void
    {
        $this->touchOwner($media);
    }

    public function deleted(Media $media): void
    {
        $this->touchOwner($media);
    }

    private function touchOwner(Media $media): void
    {
        $owner = $media->model;

        if ($owner !== null) {
            $this->revalidation->touchModel($owner);
        }
    }
}
