<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Leads submitted through the public contact form.
 */
class ContactMessage extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'company', 'service_category_id', 'message',
        'locale', 'status', 'assigned_to', 'internal_notes', 'ip', 'user_agent',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
        ];
    }

    /** @return BelongsTo<ServiceCategory, $this> */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** Track how the team handles a lead, not the submitted content. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contact')
            ->logOnly(['status', 'assigned_to', 'internal_notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
