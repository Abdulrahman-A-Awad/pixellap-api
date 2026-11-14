<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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

    /** A file path on the public disk (settings have no media library). */
    public const TYPE_IMAGE = 'image';

    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'settings';

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

        // Replacing or clearing an image setting removes the previous file.
        static::updated(function (self $setting): void {
            $old = $setting->getOriginal('value');
            if ($setting->type === self::TYPE_IMAGE && is_string($old) && $old !== '' && $old !== $setting->value) {
                Storage::disk(self::IMAGE_DISK)->delete($old);
            }
        });
    }

    /**
     * Read a setting. Translatable settings resolve to the current locale,
     * falling back to the fallback locale.
     */
    /** Absolute URL of an image setting (APP_URL/storage/...), or null when empty. */
    public static function imageUrl(string $key): ?string
    {
        $path = static::get($key);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $url = Storage::disk(self::IMAGE_DISK)->url($path);

        // Guarantee an absolute URL even if the disk is configured with a relative one.
        return preg_match('#^https?://#i', $url) === 1 ? $url : rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

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
