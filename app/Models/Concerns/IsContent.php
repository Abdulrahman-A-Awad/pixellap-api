<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

/**
 * Shared behaviour for every CMS content model:
 * translations + dirty-only activity logging.
 */
trait IsContent
{
    use HasTranslations;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('content')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Only rows editors have marked visible.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_visible'), true);
    }
}
