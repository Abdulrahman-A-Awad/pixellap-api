<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HomeSectionKey;
use App\Models\Concerns\HasWebpMedia;
use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use App\Support\MimeTypes;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

class HomeSection extends Model implements HasMedia, Sortable
{
    /**
     * Hero loop video variants: orientation → format → collection (one file each).
     * Wide = desktop / landscape, vertical = phones / portrait. WebM first, MP4 fallback,
     * poster = first frame (also the static hero).
     *
     * @var array<string, array<string, string>>
     */
    public const HERO_MEDIA = [
        'wide' => ['webm' => 'video_wide_webm', 'mp4' => 'video_wide_mp4', 'poster' => 'poster_wide'],
        'vertical' => ['webm' => 'video_vertical_webm', 'mp4' => 'video_vertical_mp4', 'poster' => 'poster_vertical'],
    ];

    use HasWebpMedia;
    use IsContent;
    use IsSortable;

    protected $fillable = [
        'key', 'eyebrow', 'title', 'subtitle',
        'cta_label', 'cta_url', 'cta2_label', 'cta2_url',
        'sort', 'is_visible',
    ];

    /** @var list<string> */
    public array $translatable = ['eyebrow', 'title', 'subtitle', 'cta_label', 'cta2_label'];

    protected function casts(): array
    {
        return [
            'key' => HomeSectionKey::class,
            'sort' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        // about / contact_cta.
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);

        // Hero only. Posters are small webp frames served as-is (no conversions).
        foreach (self::HERO_MEDIA as $variant) {
            $this->addMediaCollection($variant['webm'])->singleFile()->acceptsMimeTypes(MimeTypes::WEBM);
            $this->addMediaCollection($variant['mp4'])->singleFile()->acceptsMimeTypes(MimeTypes::MP4);
            $this->addMediaCollection($variant['poster'])->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);
        }
    }

    /** @return list<string> */
    public static function heroMediaCollections(): array
    {
        return array_merge(...array_map(array_values(...), array_values(self::HERO_MEDIA)));
    }

    protected function webpCollections(): array
    {
        return ['image'];
    }
}
