<?php

declare(strict_types=1);

namespace App\Support;

use App\Console\Commands\CheckProduction;
use App\Enums\HomeSectionKey;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\HomeSectionResource;
use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Resources\UserResource;
use App\Models\Client;
use App\Models\HomeSection;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * "Is the website ready to launch?" checklist for the dashboard (WebsiteReadiness widget).
 *
 * Cached for 60 s; any content / media / setting / user save clears it (RevalidationService::touch()
 * and User model events call forget()). Labels are English translation keys (lang/ar.json),
 * translated at render time so one cache entry serves both admin languages.
 */
final class WebsiteReadiness
{
    public const CACHE_KEY = 'admin_website_readiness';

    public const TTL = 60;

    /** Contact settings that must be filled in before launch. */
    public const CONTACT_KEYS = ['email', 'phone', 'whatsapp', 'address', 'vat_number'];

    /** Seed placeholders in stat labels (EN / AR). */
    public const PLACEHOLDER_MARKERS = ['(placeholder)', 'قيمة مؤقتة'];

    /**
     * @return list<array{key: string, label: string, ok: bool, detail: ?list<string>, url: ?string}>
     */
    public static function checks(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, fn (): array => self::run());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function allSet(): bool
    {
        return collect(self::checks())->every(fn (array $check): bool => $check['ok']);
    }

    /** @return list<array{key: string, label: string, ok: bool, detail: ?list<string>, url: ?string}> */
    private static function run(): array
    {
        $settings = ManageSettings::getUrl();
        $hero = HomeSection::query()->where('key', HomeSectionKey::Hero)->first();
        $statsSection = HomeSection::query()->where('key', HomeSectionKey::Stats)->first();
        $clientsSection = HomeSection::query()->where('key', HomeSectionKey::Clients)->first();
        $editSection = fn (?HomeSection $section): ?string => $section ? HomeSectionResource::getUrl('edit', ['record' => $section]) : null;

        $todo = collect(self::CONTACT_KEYS)
            ->filter(fn (string $key): bool => self::isPlaceholder(Setting::query()->where('key', $key)->value('value')))
            ->values();

        $recipient = (string) Setting::get('email');
        $mailer = (string) config('mail.default');
        $mailOk = ! in_array($mailer, ['log', 'array'], true)
            && ($mailer !== 'smtp' || filled(config('mail.mailers.smtp.host')))
            && (filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false || filled(config('pixellap.contact_notify_email')));

        $missingVideos = collect(HomeSection::HERO_MEDIA)
            ->filter(fn (array $c, string $variant): bool => $hero === null
                || ($hero->getFirstMedia($c['webm']) === null && $hero->getFirstMedia($c['mp4']) === null))
            ->keys();

        $categoriesMissing = ServiceCategory::query()->with('media')->orderBy('sort')->get()
            ->filter(fn (ServiceCategory $c): bool => $c->getFirstMedia('icon') === null || $c->getFirstMedia('cover') === null)
            ->map(fn (ServiceCategory $c): string => (string) $c->title);

        $placeholderStats = Stat::query()->get()->filter(fn (Stat $stat): bool => collect($stat->getTranslations('label'))
            ->contains(fn (?string $label): bool => collect(self::PLACEHOLDER_MARKERS)->contains(fn (string $m): bool => str_contains((string) $label, $m))));

        $logos = Client::query()->visible()->whereHas('media', fn ($q) => $q->where('collection_name', 'logo'))->count();

        return [
            self::check('contact', 'Contact details filled in', $todo->isEmpty(),
                $todo->isEmpty() ? null : $todo->map(fn (string $k): string => self::settingLabel($k))->all(), $settings),
            self::check('mail', 'Email delivery set up (SMTP + notification address)', $mailOk,
                $mailOk ? null : ['Ask your developer to set the MAIL_* values on the server, and fill in the contact email.'], $settings),
            self::check('hero_videos', 'Home page loop videos uploaded', $missingVideos->isEmpty(),
                $missingVideos->isEmpty() ? null : $missingVideos->map(fn (string $v): string => $v === 'wide' ? 'Wide (desktop)' : 'Vertical (mobile)')->values()->all(), $editSection($hero)),
            self::check('showreel', 'Showreel link added', ! self::isPlaceholder(Setting::query()->where('key', 'showreel_url')->value('value')),
                null, $settings),
            self::check('clients', 'Client logos added', $logos > 0, null, $editSection($clientsSection)),
            self::check('categories', 'Every service has an icon and a cover photo', $categoriesMissing->isEmpty(),
                $categoriesMissing->isEmpty() ? null : $categoriesMissing->values()->all(), ServiceCategoryResource::getUrl('index')),
            self::check('stats', 'Real numbers instead of placeholders', $placeholderStats->isEmpty(),
                $placeholderStats->isEmpty() ? null : $placeholderStats->map(fn (Stat $s): string => (string) $s->label)->values()->all(), $editSection($statsSection)),
            self::check('og_image', 'Social sharing image uploaded', Setting::imageUrl('og_image') !== null, null, $settings),
            self::check('default_admin', 'Default admin account removed', ! User::query()->where('email', CheckProduction::DEFAULT_ADMIN_EMAIL)->exists(),
                null, UserResource::getUrl('index')),
        ];
    }

    /** @return array{key: string, label: string, ok: bool, detail: ?list<string>, url: ?string} */
    private static function check(string $key, string $label, bool $ok, ?array $detail, ?string $url): array
    {
        return ['key' => $key, 'label' => $label, 'ok' => $ok, 'detail' => $detail, 'url' => $url];
    }

    /** Empty or still a seed "TODO" value (any type: text, {en, ar}, …). */
    public static function isPlaceholder(mixed $value): bool
    {
        $text = is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE);

        return blank(is_string($value) ? trim($value) : array_filter((array) $value)) || str_contains($text, 'TODO');
    }

    private static function settingLabel(string $key): string
    {
        return ManageSettings::LABELS[$key] ?? $key;
    }
}
