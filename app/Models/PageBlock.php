<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageBlockType;
use App\Models\Concerns\HasWebpMedia;
use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use App\Support\MimeTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

class PageBlock extends Model implements HasMedia, Sortable
{
    use HasWebpMedia;
    use IsContent;
    use IsSortable;

    protected $fillable = ['page_id', 'type', 'title', 'body', 'items', 'sort', 'is_visible'];

    /**
     * `items` holds an array of {title, text} per locale.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'body', 'items'];

    protected function casts(): array
    {
        return [
            'type' => PageBlockType::class,
            'sort' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** Sort within the parent page. */
    public function buildSortQuery(): Builder
    {
        return static::query()->where('page_id', $this->page_id);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);
    }

    protected function webpCollections(): array
    {
        return ['image'];
    }
}
