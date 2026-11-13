<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media library with the standard webp conversions:
 * thumb (400w), md (1024w), lg (1920w).
 *
 * Models implement Spatie\MediaLibrary\HasMedia, define registerMediaCollections(),
 * and list the image collections to convert in webpCollections().
 * SVGs and videos are never converted.
 */
trait HasWebpMedia
{
    use InteractsWithMedia;

    /** @var array<string, int> */
    public const WEBP_CONVERSIONS = ['thumb' => 400, 'md' => 1024, 'lg' => 1920];

    /**
     * Image collections that receive webp conversions.
     *
     * @return list<string>
     */
    abstract protected function webpCollections(): array;

    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media !== null && $media->mime_type === 'image/svg+xml') {
            return;
        }

        foreach (self::WEBP_CONVERSIONS as $name => $width) {
            $this->addMediaConversion($name)
                ->format('webp')
                ->width($width)
                ->nonQueued()
                ->performOnCollections(...$this->webpCollections());
        }
    }
}
