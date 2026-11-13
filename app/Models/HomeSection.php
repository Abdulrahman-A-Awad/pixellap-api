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
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);
        $this->addMediaCollection('video')->singleFile()->acceptsMimeTypes(MimeTypes::VIDEOS);
    }

    protected function webpCollections(): array
    {
        return ['image'];
    }
}
