<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\RevalidationService;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Throwable;

/**
 * On upload: store raster image dimensions (for the API's width/height)
 * and revalidate the owner model's tags.
 */
class HandleMediaAdded
{
    public function __construct(private readonly RevalidationService $revalidation) {}

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if (str_starts_with((string) $media->mime_type, 'image/') && $media->mime_type !== 'image/svg+xml') {
            try {
                $size = @getimagesize($media->getPath());
                if ($size !== false) {
                    $media->setCustomProperty('width', $size[0]);
                    $media->setCustomProperty('height', $size[1]);
                    $media->saveQuietly();
                }
            } catch (Throwable) {
                // Dimensions are optional metadata.
            }
        }

        if ($media->model !== null) {
            $this->revalidation->touchModel($media->model);
        }
    }
}
