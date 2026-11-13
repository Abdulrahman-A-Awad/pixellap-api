<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use App\Support\MimeTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

class Service extends Model implements HasMedia, Sortable
{
    use HasWebpMedia;
    use IsContent;
    use IsSortable;

    protected $fillable = ['service_category_id', 'title', 'description', 'features', 'sort', 'is_visible'];

    /**
     * `features` holds an array of strings per locale.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'description', 'features'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /** @return BelongsTo<ServiceCategory, $this> */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /** Sort within the parent category. */
    public function buildSortQuery(): Builder
    {
        return static::query()->where('service_category_id', $this->service_category_id);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile()->acceptsMimeTypes(MimeTypes::ICONS);
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);
    }

    protected function webpCollections(): array
    {
        return ['icon', 'image'];
    }
}
