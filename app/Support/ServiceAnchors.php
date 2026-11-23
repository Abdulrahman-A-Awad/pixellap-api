<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Stable in-page anchors and short descriptions for a category's services, shared by
 * GET /service-categories?include=services (services index) and GET /service-categories/{slug}
 * (category page), so a link like /services/{slug}#{anchor} always lands on the right card.
 */
final class ServiceAnchors
{
    public const SHORT_MAX = 140;

    /**
     * Anchor per service id: the slugified ENGLISH title (identical in both locales), unique within the
     * category (in display order: a repeated title gets "-2", "-3"…). Empty title → "service-{id}".
     *
     * @param  Collection<int, Service>  $services  the category's services, in display order
     * @return array<int, string> service id => anchor
     */
    public static function for(Collection $services): array
    {
        $used = [];
        $anchors = [];
        foreach ($services as $service) {
            $base = Str::slug((string) $service->getTranslation('title', 'en', false)) ?: 'service-'.$service->id;
            $anchor = $base;
            for ($n = 2; isset($used[$anchor]); $n++) {
                $anchor = "{$base}-{$n}";
            }
            $used[$anchor] = true;
            $anchors[$service->id] = $anchor;
        }

        return $anchors;
    }

    /**
     * First sentence of a plain-text description (ends with . ! ? or the Arabic ؟), at most
     * SHORT_MAX characters — longer text is cut at a word boundary and ends with "..." (the brand
     * font has no "…" glyph).
     */
    public static function short(?string $description): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $description) ?? '');
        if ($text === '') {
            return '';
        }
        $first = preg_match('/^(.+?[.!?؟])(?:\s|$)/u', $text, $m) === 1 ? $m[1] : $text;
        if (mb_strlen($first) <= self::SHORT_MAX) {
            return $first;
        }
        $cut = mb_substr($first, 0, self::SHORT_MAX - 3);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space !== false && $space > 60 ? mb_substr($cut, 0, $space) : $cut, ' ,،;:').'...';
    }
}
