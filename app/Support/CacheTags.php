<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PageKey;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\ProcessStep;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\UiString;
use Illuminate\Database\Eloquent\Model;

/**
 * Single source of truth: which cache tags a content change invalidates.
 * Used for the local API cache AND the Next.js revalidation webhook.
 *
 *   site, home, categories, sitemap, category:{slug}, page:{key}
 */
final class CacheTags
{
    public const SITE = 'site';

    public const HOME = 'home';

    public const CATEGORIES = 'categories';

    public const SITEMAP = 'sitemap';

    public static function category(string $slug): string
    {
        return "category:{$slug}";
    }

    public static function page(string $key): string
    {
        return "page:{$key}";
    }

    /**
     * Every tag currently in use (for a full refresh).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SITE, self::HOME, self::CATEGORIES, self::SITEMAP,
            ...ServiceCategory::query()->pluck('slug')->map(fn (string $slug) => self::category($slug))->all(),
            ...array_map(fn (PageKey $key) => self::page($key->value), PageKey::cases()),
        ];
    }

    /**
     * Tags affected by a change to $model (including its previous parent/slug when changed).
     *
     * @return list<string>
     */
    public static function for(Model $model): array
    {
        $tags = match (true) {
            $model instanceof Setting, $model instanceof UiString => [self::SITE, self::HOME],
            $model instanceof HomeSection => [self::HOME, self::SITEMAP],
            $model instanceof Stat, $model instanceof ProcessStep, $model instanceof Client => [self::HOME],
            $model instanceof Faq => self::forFaq($model),
            $model instanceof ServiceCategory => [
                self::SITE, self::HOME, self::CATEGORIES, self::SITEMAP,
                ...self::categoryTags([$model->slug, $model->getOriginal('slug')]),
            ],
            $model instanceof Service => [
                self::HOME, self::CATEGORIES, self::SITEMAP,
                ...self::categoryTagsForIds([$model->service_category_id, $model->getOriginal('service_category_id')]),
            ],
            $model instanceof Page => [self::page($model->key->value), self::SITEMAP],
            $model instanceof PageBlock => self::forPageBlock($model),
            default => [],
        };

        return array_values(array_unique($tags));
    }

    /** @return list<string> */
    private static function forFaq(Faq $faq): array
    {
        $ids = [$faq->service_category_id, $faq->getOriginal('service_category_id')];
        $tags = self::categoryTagsForIds($ids);

        // A null category (now or before the change) means it is/was a home page FAQ.
        if (in_array(null, $ids, true)) {
            $tags[] = self::HOME;
        }

        return $tags;
    }

    /** @return list<string> */
    private static function forPageBlock(PageBlock $block): array
    {
        $key = Page::query()->whereKey($block->page_id)->value('key');

        return $key === null ? [] : [self::page($key instanceof PageKey ? $key->value : (string) $key), self::SITEMAP];
    }

    /**
     * @param  array<int, int|string|null>  $ids
     * @return list<string>
     */
    private static function categoryTagsForIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        return $ids === []
            ? []
            : self::categoryTags(ServiceCategory::query()->whereKey($ids)->pluck('slug')->all());
    }

    /**
     * @param  array<int, string|null>  $slugs
     * @return list<string>
     */
    private static function categoryTags(array $slugs): array
    {
        return array_map(
            fn (string $slug) => self::category($slug),
            array_values(array_unique(array_filter($slugs))),
        );
    }
}
