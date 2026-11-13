<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageKey;
use App\Models\Concerns\HasWebpMedia;
use App\Models\Concerns\IsContent;
use App\Support\MimeTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * Fixed pages identified by key. Pages themselves are not sortable or
 * hideable (no sort / is_visible columns); their blocks are.
 */
class Page extends Model implements HasMedia
{
    use HasWebpMedia;
    use IsContent;

    protected $fillable = ['key', 'title', 'subtitle', 'seo_title', 'seo_description'];

    /** @var list<string> */
    public array $translatable = ['title', 'subtitle', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return [
            'key' => PageKey::class,
        ];
    }

    /** @return HasMany<PageBlock, $this> */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);
    }

    protected function webpCollections(): array
    {
        return ['hero'];
    }
}
