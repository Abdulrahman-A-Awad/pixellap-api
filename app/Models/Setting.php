<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Key/value site settings.
 *
 * `value` is stored as JSON whatever the type:
 *  - text:         "string"
 *  - translatable: {"en": "...", "ar": "..."}
 *  - json:         any object/array
 *  - boolean:      true|false
 */
class Setting extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'settings';

    public const TYPE_TEXT = 'text';

    public const TYPE_TRANSLATABLE = 'translatable';

    public const TYPE_JSON = 'json';

    public const TYPE_BOOLEAN = 'boolean';

    protected $fillable = ['key', 'group', 'type', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Read a setting. Translatable settings resolve to the current locale,
     * falling back to the fallback locale.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        /** @var array<string, array{type: string, value: mixed}> $all */
        $all = Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()
                ->get(['key', 'type', 'value'])
                ->mapWithKeys(fn (self $s): array => [$s->key => ['type' => $s->type, 'value' => $s->value]])
                ->all(),
        );

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        ['type' => $type, 'value' => $value] = $all[$key];

        if ($type === self::TYPE_TRANSLATABLE && is_array($value)) {
            $value = $value[app()->getLocale()]
                ?? $value[(string) config('app.fallback_locale')]
                ?? null;
        }

        return $value ?? $default;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('settings')
            ->logOnly(['key', 'value'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
