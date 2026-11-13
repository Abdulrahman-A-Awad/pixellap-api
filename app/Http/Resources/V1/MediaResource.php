<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Services\PublicContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shared shape for every image / video:
 * { url, mime, width, height, alt, conversions: { thumb, md, lg } | null }
 *
 * `conversions` is null for SVGs and videos. A conversion that has not been
 * generated (yet) falls back to the original URL. All URLs are absolute (APP_URL).
 *
 * @property Media $resource
 */
class MediaResource extends JsonResource
{
    /** Mirrors App\Models\Concerns\HasWebpMedia::WEBP_CONVERSIONS. */
    public const CONVERSIONS = ['thumb', 'md', 'lg'];

    public static function fromModel(HasMedia $model, string $collection): ?self
    {
        $media = $model->getFirstMedia($collection);

        return $media === null ? null : new self($media);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $media = $this->resource;
        $original = $media->getFullUrl();
        $width = $media->getCustomProperty('width');
        $height = $media->getCustomProperty('height');

        return [
            'url' => $original,
            'mime' => $media->mime_type,
            'width' => is_numeric($width) ? (int) $width : null,
            'height' => is_numeric($height) ? (int) $height : null,
            'alt' => ($media->model !== null ? PublicContent::labelOf($media->model) : null) ?? '',
            'conversions' => $this->isConvertible($media)
                ? array_map(
                    fn (string $name): string => $media->hasGeneratedConversion($name) ? $media->getFullUrl($name) : $original,
                    array_combine(self::CONVERSIONS, self::CONVERSIONS),
                )
                : null,
        ];
    }

    private function isConvertible(Media $media): bool
    {
        $mime = (string) $media->mime_type;

        return str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml';
    }
}
