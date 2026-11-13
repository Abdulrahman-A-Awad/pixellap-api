<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\EloquentSortable\SortableTrait;

/**
 * spatie/eloquent-sortable on the `sort` column.
 * Provides the `ordered()` scope. Models implement Spatie\EloquentSortable\Sortable.
 *
 * New rows go to the end of their group unless a `sort` value is given explicitly
 * (seeders pass explicit values so re-runs stay stable).
 */
trait IsSortable
{
    use SortableTrait;

    /** @var array{order_column_name: string, sort_when_creating: bool} */
    public array $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];

    public function shouldSortWhenCreating(): bool
    {
        return blank($this->getAttribute('sort'));
    }
}
