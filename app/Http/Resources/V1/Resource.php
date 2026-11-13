<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base for v1 resources. Translatable attributes already resolve to the current
 * locale (falling back to en) through spatie/laravel-translatable; these helpers
 * normalise empty values.
 */
abstract class Resource extends JsonResource
{
    /** Empty string → null. */
    protected static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /** @return array{label: string, url: string}|null */
    protected static function link(mixed $label, mixed $url): ?array
    {
        $label = self::text($label);
        $url = self::text($url);

        return $label === null || $url === null ? null : ['label' => $label, 'url' => $url];
    }

    /** @return list<mixed> */
    protected static function list(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }
}
