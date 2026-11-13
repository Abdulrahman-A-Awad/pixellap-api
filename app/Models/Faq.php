<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;

class Faq extends Model implements Sortable
{
    use IsContent;
    use IsSortable;

    protected $fillable = ['service_category_id', 'question', 'answer', 'sort', 'is_visible'];

    /** @var list<string> */
    public array $translatable = ['question', 'answer'];

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

    /**
     * FAQs without a category belong to the home page.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForHome(Builder $query): void
    {
        $query->whereNull('service_category_id');
    }

    /** Sort within the parent category (or within the home page group). */
    public function buildSortQuery(): Builder
    {
        return $this->service_category_id === null
            ? static::query()->whereNull('service_category_id')
            : static::query()->where('service_category_id', $this->service_category_id);
    }
}
