<?php

declare(strict_types=1);

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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Settings as the single source of truth for the brand (admin prompt "site settings + home page"):
 *
 *  1. `site_name` becomes translatable: {en: <current value>, ar: "بكسل لاب"}.
 *  2. `socials` gains `facebook` and `snapchat` (existing links kept).
 *  3. The seeded Arabic SEO title becomes "بكسل لاب – …" (an edited title is left alone).
 *  4. Hero button 2 no longer doubles as the reel button: "#reel" (or empty) → "Explore our
 *     services" → /services. The reel button is now automatic (Settings → showreel link).
 *  5. The services section link label gets its default when empty.
 *  6. In ARABIC values only, the standalone word "Pixellap" → "بكسل لاب" (home sections, pages,
 *     page blocks, FAQs, service categories, services, stats, process steps, ui strings and
 *     translatable settings). English is untouched. Counts are printed per table.
 *
 * Query builder only (no model events): one revalidation at the end of the deploy
 * (`pixellap:revalidate-all`) refreshes the API cache and the website.
 */
return new class extends Migration
{
    private const AR_NAME = 'بكسل لاب';

    /** Seeded Arabic SEO title before this change (replaced only if still unchanged). */
    private const OLD_AR_SEO_TITLE = 'Pixellap — وكالة إبداعية للإعلام والتسويق الرقمي في الإمارات';

    private const NEW_AR_SEO_TITLE = 'بكسل لاب – وكالة إبداعية للإعلام والتسويق الرقمي في الإمارات';

    /** "Pixellap" as a word — not inside an e-mail, URL, handle or longer word. */
    private const WORD = '/(?<![@\/\w.-])Pixellap(?![\w@-])/u';

    public function up(): void
    {
        $this->localizeSiteName();
        $this->addSocialNetworks();
        $this->heroSecondButton();
        $this->servicesLinkLabel();

        $counts = [];
        foreach ([HomeSection::class, Page::class, PageBlock::class, Faq::class, ServiceCategory::class, Service::class, Stat::class, ProcessStep::class, UiString::class] as $model) {
            $instance = new $model;
            $counts[$instance->getTable()] = $this->replaceInArabic($instance->getTable(), $instance->getTranslatableAttributes());
        }
        $counts['settings'] = $this->replaceInSettings();

        Cache::forget(Setting::CACHE_KEY);

        if (! app()->runningUnitTests()) {
            $total = array_sum($counts);
            echo '  Arabic "Pixellap" → "'.self::AR_NAME."\": {$total} value(s) changed (".
                collect($counts)->map(fn (int $n, string $table): string => "{$table}: {$n}")->implode(', ').")\n";
        }
    }

    public function down(): void
    {
        // Content change: not reverted (editors may have edited the values since).
    }

    private function localizeSiteName(): void
    {
        $row = DB::table('settings')->where('key', 'site_name')->first();
        if ($row === null) {
            return;
        }
        if ($row->type === Setting::TYPE_TRANSLATABLE) {
            return;
        }

        $current = json_decode((string) $row->value, true);
        $en = is_string($current) && trim($current) !== '' ? $current : 'Pixellap';

        DB::table('settings')->where('id', $row->id)->update([
            'type' => Setting::TYPE_TRANSLATABLE,
            'value' => json_encode(['en' => $en, 'ar' => self::AR_NAME]),
            'updated_at' => now(),
        ]);
    }

    private function addSocialNetworks(): void
    {
        $row = DB::table('settings')->where('key', 'socials')->first();
        if ($row === null) {
            return;
        }

        $socials = json_decode((string) $row->value, true);
        $socials = is_array($socials) ? $socials : [];
        $merged = ['facebook' => $socials['facebook'] ?? '', ...$socials, 'snapchat' => $socials['snapchat'] ?? ''];

        if ($merged !== $socials) {
            DB::table('settings')->where('id', $row->id)->update(['value' => json_encode($merged), 'updated_at' => now()]);
        }
    }

    private function heroSecondButton(): void
    {
        $hero = DB::table('home_sections')->where('key', 'hero')->first();
        if ($hero === null) {
            return;
        }

        $url = trim((string) $hero->cta2_url);
        if ($url !== '' && $url !== '#reel') {
            return; // an editor already points button 2 somewhere else
        }

        $label = json_decode((string) $hero->cta2_label, true);
        $label = is_array($label) ? $label : [];
        $reelLabels = ['Watch the reel', 'شاهد العرض التعريفي', ''];
        $en = in_array(trim((string) ($label['en'] ?? '')), $reelLabels, true) ? 'Explore our services' : $label['en'];
        $ar = in_array(trim((string) ($label['ar'] ?? '')), $reelLabels, true) ? 'استكشف خدماتنا' : $label['ar'];

        DB::table('home_sections')->where('id', $hero->id)->update([
            'cta2_url' => '/services',
            'cta2_label' => json_encode(['en' => $en, 'ar' => $ar]),
            'updated_at' => now(),
        ]);
    }

    private function servicesLinkLabel(): void
    {
        $services = DB::table('home_sections')->where('key', 'services')->first();
        if ($services === null) {
            return;
        }

        $label = json_decode((string) $services->cta_label, true);
        $label = is_array($label) ? $label : [];
        $filled = [
            'en' => trim((string) ($label['en'] ?? '')) !== '' ? $label['en'] : 'Explore our services',
            'ar' => trim((string) ($label['ar'] ?? '')) !== '' ? $label['ar'] : 'استكشف خدماتنا',
        ];

        if ($filled !== $label || trim((string) $services->cta_url) === '') {
            DB::table('home_sections')->where('id', $services->id)->update([
                'cta_label' => json_encode($filled),
                'cta_url' => trim((string) $services->cta_url) !== '' ? $services->cta_url : '/services',
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  list<string>  $columns
     * @return int number of changed values (one per row + column)
     */
    private function replaceInArabic(string $table, array $columns): int
    {
        $changed = 0;
        foreach (DB::table($table)->get(['id', ...$columns]) as $row) {
            $update = [];
            foreach ($columns as $column) {
                $value = json_decode((string) $row->{$column}, true);
                if (! is_array($value) || ! array_key_exists('ar', $value)) {
                    continue;
                }
                $ar = $this->replaceDeep($value['ar']);
                if ($ar !== $value['ar']) {
                    $value['ar'] = $ar;
                    $update[$column] = json_encode($value);
                    $changed++;
                }
            }
            if ($update !== []) {
                DB::table($table)->where('id', $row->id)->update($update);
            }
        }

        return $changed;
    }

    private function replaceInSettings(): int
    {
        $changed = 0;
        foreach (DB::table('settings')->where('type', Setting::TYPE_TRANSLATABLE)->get() as $row) {
            $value = json_decode((string) $row->value, true);
            if (! is_array($value) || ! isset($value['ar']) || ! is_string($value['ar'])) {
                continue;
            }

            $ar = $row->key === 'seo_default_title' && $value['ar'] === self::OLD_AR_SEO_TITLE
                ? self::NEW_AR_SEO_TITLE
                : $this->replaceDeep($value['ar']);

            if ($ar !== $value['ar']) {
                $value['ar'] = $ar;
                DB::table('settings')->where('id', $row->id)->update(['value' => json_encode($value), 'updated_at' => now()]);
                $changed++;
            }
        }

        return $changed;
    }

    /** Strings and nested lists (block items, service features). */
    private function replaceDeep(mixed $value): mixed
    {
        if (is_string($value)) {
            return preg_replace(self::WORD, self::AR_NAME, $value) ?? $value;
        }
        if (is_array($value)) {
            return array_map(fn (mixed $v): mixed => $this->replaceDeep($v), $value);
        }

        return $value;
    }
};
