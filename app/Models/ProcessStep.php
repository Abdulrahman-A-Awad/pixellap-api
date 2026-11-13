<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;

class ProcessStep extends Model implements Sortable
{
    use IsContent;
    use IsSortable;

    protected $fillable = ['title', 'description', 'icon', 'sort', 'is_visible'];

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
