<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;

class Stat extends Model implements Sortable
{
    use IsContent;
    use IsSortable;

    protected $fillable = ['value', 'prefix', 'suffix', 'label', 'sort', 'is_visible'];

    /** @var list<string> */
    public array $translatable = ['label'];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'sort' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
