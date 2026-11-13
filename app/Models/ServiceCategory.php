<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use App\Support\MimeTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

class ServiceCategory extends Model implements HasMedia, Sortable
{
    use HasWebpMedia;
    use IsContent;
    use IsSortable;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'description',
        'seo_title', 'seo_description', 'sort', 'is_visible',
    ];

    /** @var list<string> */
    public array $translatable = ['title', 'excerpt', 'description', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** @return HasMany<Faq, $this> */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }

    /** @return HasMany<ContactMessage, $this> */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile()->acceptsMimeTypes(MimeTypes::SVG);
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);
        $this->addMediaCollection('hero_video')->singleFile()->acceptsMimeTypes(MimeTypes::VIDEOS);
    }

    protected function webpCollections(): array
    {
        return ['cover'];
    }
}
