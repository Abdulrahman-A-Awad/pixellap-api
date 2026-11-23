<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PageKey;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\ProcessStep;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\UiString;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Read queries behind the public API. Only visible rows, always ordered by `sort`.
 * Resources turn these models into JSON; controllers only wire the two together.
 */
final class PublicContent
{
    /** Settings exposed by GET /site (everything else stays private). */
    public const PUBLIC_SETTINGS = [
        'site_name', 'tagline', 'showreel_url',
        'email', 'phone', 'whatsapp', 'address', 'map_embed_url', 'working_hours', 'business_hours', 'vat_number',
        'response_time', 'socials',
        'seo_default_title', 'seo_default_description', 'og_image',
    ];

    /** Image settings: stored as a public-disk path, returned as an absolute URL. */
    public const IMAGE_SETTINGS = ['og_image'];

    /** Week days accepted in `business_hours.days`, Monday first. */
    public const WEEK_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $out = [];
        foreach (self::PUBLIC_SETTINGS as $key) {
            $out[$key] = match (true) {
                in_array($key, self::IMAGE_SETTINGS, true) => Setting::imageUrl($key),
                $key === 'business_hours' => $this->businessHours(Setting::get($key)),
                default => Setting::get($key),
            };
        }

        return $out;
    }

    /**
     * Structured opening hours, normalised: {days: ["mon", …] (week order), open: "HH:MM", close: "HH:MM",
     * timezone}. Null when missing or unusable (bad time, or close not after open).
     *
     * @return array{days: list<string>, open: string, close: string, timezone: string}|null
     */
    private function businessHours(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }
        $time = fn (mixed $t): ?string => is_string($t) && preg_match('/^([01]\d|2[0-3]):[0-5]\d/', $t) === 1 ? substr($t, 0, 5) : null;
        $open = $time($value['open'] ?? null);
        $close = $time($value['close'] ?? null);
        if ($open === null || $close === null || $close <= $open) {
            return null;
        }
        $days = array_values(array_intersect(self::WEEK_DAYS, (array) ($value['days'] ?? [])));
        $timezone = is_string($value['timezone'] ?? null) && in_array($value['timezone'], timezone_identifiers_list(), true)
            ? $value['timezone']
            : 'Asia/Dubai';

        return ['days' => $days, 'open' => $open, 'close' => $close, 'timezone' => $timezone];
    }

    /** @return array<string, string> flat {"nav.home": "Home", ...} in the current locale */
    public function uiStrings(): array
    {
        return UiString::query()
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (UiString $s): array => [$s->key => (string) $s->value])
            ->all();
    }

    public function uiString(string $key, string $default = ''): string
    {
        $value = UiString::query()->where('key', $key)->first()?->value;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /** @return Collection<int, HomeSection> */
    public function homeSections(): Collection
    {
        return HomeSection::query()->visible()->ordered()->with('media')->get();
    }

    /** @return Collection<int, Stat> */
    public function stats(): Collection
    {
        return Stat::query()->visible()->ordered()->get();
    }

    /** @return Collection<int, ProcessStep> */
    public function processSteps(): Collection
    {
        return ProcessStep::query()->visible()->ordered()->get();
    }

    /** @return Collection<int, Client> */
    public function clients(): Collection
    {
        return Client::query()->visible()->ordered()->with('media')->get();
    }

    /** @return Collection<int, Faq> */
    public function homeFaqs(): Collection
    {
        return Faq::query()->forHome()->visible()->ordered()->get();
    }

    /**
     * Visible categories with their visible services (for cards / tags) and a visible services count.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function categories(): Collection
    {
        return ServiceCategory::query()
            ->visible()
            ->ordered()
            ->with(['media', 'services' => fn (HasMany $q) => $q->visible()->ordered()])
            ->withCount(['services' => fn (Builder $q) => $q->where('is_visible', true)])
            ->get();
    }

    public function category(string $slug): ?ServiceCategory
    {
        return ServiceCategory::query()
            ->visible()
            ->where('slug', $slug)
            ->with([
                'media',
                'services' => fn (HasMany $q) => $q->visible()->ordered()->with('media'),
                'faqs' => fn (HasMany $q) => $q->visible()->ordered(),
            ])
            ->first();
    }

    public function page(string $key): ?Page
    {
        if (PageKey::tryFrom($key) === null) {
            return null;
        }

        return Page::query()
            ->where('key', $key)
            ->with(['media', 'blocks' => fn (HasMany $q) => $q->visible()->ordered()->with('media')])
            ->first();
    }

    /**
     * Every public URL in both locales with its last content change.
     *
     * @return list<array{path: string, locale: string, updated_at: string|null}>
     */
    public function sitemap(): array
    {
        $entries = ['' => $this->latest(HomeSection::query()->visible()->max('updated_at'))];

        $pages = Page::query()->with('blocks:id,page_id,updated_at,is_visible')->get()->keyBy(fn (Page $p) => $p->key->value);
        foreach ([PageKey::About, PageKey::Services, PageKey::Contact] as $key) {
            $page = $pages->get($key->value);
            $entries['/'.$key->value] = $page === null ? null : $this->latest(
                $page->updated_at,
                $page->blocks->where('is_visible', true)->max('updated_at'),
            );
        }

        foreach (ServiceCategory::query()->visible()->ordered()->with('services:id,service_category_id,updated_at')->get() as $category) {
            $entries['/services/'.$category->slug] = $this->latest($category->updated_at, $category->services->max('updated_at'));
        }

        $out = [];
        foreach ((array) config('app.available_locales') as $locale) {
            foreach ($entries as $path => $updatedAt) {
                $out[] = ['path' => "/{$locale}{$path}", 'locale' => $locale, 'updated_at' => $updatedAt?->toIso8601String()];
            }
        }

        return $out;
    }

    /** Newest of the given timestamps (null-safe). */
    private function latest(mixed ...$dates): ?CarbonInterface
    {
        $dates = array_filter(array_map(
            fn (mixed $d): ?CarbonInterface => $d === null ? null : ($d instanceof CarbonInterface ? $d : Carbon::parse((string) $d)),
            $dates,
        ));

        return $dates === [] ? null : max($dates);
    }

    /** Title-ish attribute of a media owner, used as image alt text. */
    public static function labelOf(Model $owner): ?string
    {
        foreach (['title', 'name'] as $attribute) {
            $value = $owner->getAttribute($attribute);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
