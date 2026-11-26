<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\HomeSectionKey;
use App\Filament\Pages\ManageSettings;
use App\Filament\Support\PermissionMatrix;
use App\Models\Client;
use App\Models\ContactMessage;
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
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Activity log rows in plain words for the admin:
 *   "Abdelrahman edited the Home page section: Hero"
 *   "System added an interface text: nav.services"
 *
 * Every English phrase is a translation key (lang/ar.json); Arabic keeps the same order
 * ("عبدالرحمن عدّل قسم الصفحة الرئيسية: Hero").
 */
final class ActivityDescriber
{
    /** Event written by "Clear entire log". */
    public const CLEARED = 'cleared';

    public const EVENTS = [
        'created' => ['label' => 'Added', 'verb' => 'added', 'color' => 'success'],
        'updated' => ['label' => 'Edited', 'verb' => 'edited', 'color' => 'info'],
        'deleted' => ['label' => 'Deleted', 'verb' => 'deleted', 'color' => 'danger'],
        'restored' => ['label' => 'Restored', 'verb' => 'restored', 'color' => 'warning'],
        'reordered' => ['label' => 'Reordered', 'verb' => 'reordered', 'color' => 'gray'],
        self::CLEARED => ['label' => 'Cleared', 'verb' => 'cleared', 'color' => 'danger'],
    ];

    /**
     * Subject class => area (PermissionMatrix key), "the …" (edited / deleted) and "a …" (added).
     *
     * @return array<class-string<Model>, array{area: string, the: string, a: string}>
     */
    public static function subjects(): array
    {
        return [
            HomeSection::class => ['area' => 'home', 'the' => 'the Home page section', 'a' => 'a Home page section'],
            Stat::class => ['area' => 'home', 'the' => 'the number', 'a' => 'a number'],
            ProcessStep::class => ['area' => 'home', 'the' => 'the process step', 'a' => 'a process step'],
            Client::class => ['area' => 'home', 'the' => 'the client logo', 'a' => 'a client logo'],
            Faq::class => ['area' => 'home', 'the' => 'the FAQ', 'a' => 'an FAQ'],
            ServiceCategory::class => ['area' => 'services', 'the' => 'the service category', 'a' => 'a service category'],
            Service::class => ['area' => 'services', 'the' => 'the service', 'a' => 'a service'],
            Page::class => ['area' => 'pages', 'the' => 'the page', 'a' => 'a page'],
            PageBlock::class => ['area' => 'pages', 'the' => 'the page block', 'a' => 'a page block'],
            Setting::class => ['area' => 'settings', 'the' => 'the site setting', 'a' => 'a site setting'],
            UiString::class => ['area' => 'ui', 'the' => 'the interface text', 'a' => 'an interface text'],
            ContactMessage::class => ['area' => 'messages', 'the' => 'the message', 'a' => 'a message'],
            User::class => ['area' => 'users', 'the' => 'the user', 'a' => 'a user'],
        ];
    }

    /** @return array<string, string> area key => translated label, for the "Area" filter */
    public static function areaOptions(): array
    {
        $areas = PermissionMatrix::areas();

        return collect(self::subjects())
            ->pluck('area')
            ->unique()
            ->mapWithKeys(fn (string $area): array => [$area => __($areas[$area]['label'] ?? Str::headline($area))])
            ->all();
    }

    /** @return list<class-string<Model>> */
    public static function classesFor(string $area): array
    {
        return collect(self::subjects())->filter(fn (array $s): bool => $s['area'] === $area)->keys()->all();
    }

    /** @return array<string, string> event => translated badge label */
    public static function eventOptions(): array
    {
        return collect(self::EVENTS)->map(fn (array $e): string => __($e['label']))->all();
    }

    public static function eventLabel(?string $event): string
    {
        return isset(self::EVENTS[$event]) ? __(self::EVENTS[$event]['label']) : __(Str::headline((string) $event));
    }

    public static function eventColor(?string $event): string
    {
        return self::EVENTS[$event]['color'] ?? 'gray';
    }

    public static function causer(Activity $activity): string
    {
        $causer = $activity->causer;
        if ($causer instanceof User) {
            return $causer->name;
        }

        // Leads arrive from the public contact form.
        return $activity->subject_type === ContactMessage::class && $activity->event === 'created'
            ? __('Website')
            : __('System');
    }

    /** "Abdelrahman edited the Home page section: Hero" */
    public static function sentence(Activity $activity): string
    {
        $causer = self::causer($activity);

        if ($activity->event === self::CLEARED) {
            return __(':causer cleared the activity log', ['causer' => $causer]);
        }

        $subject = self::subjects()[$activity->subject_type] ?? null;
        $item = $subject
            ? __($activity->event === 'created' ? $subject['a'] : $subject['the'])
            : __('an item');
        $title = self::title($activity);

        $key = match ($activity->event) {
            'created' => ':causer added :item',
            'deleted' => ':causer deleted :item',
            'restored' => ':causer restored :item',
            'reordered' => ':causer reordered the items of :item',
            default => ':causer edited :item',
        };

        $text = __($key, ['causer' => $causer, 'item' => $item]);

        return $title !== null ? "{$text}: {$title}" : $text;
    }

    /** Area label of the row ("Home page"), or null. */
    public static function area(Activity $activity): ?string
    {
        $area = self::subjects()[$activity->subject_type]['area'] ?? null;

        return $area ? __(PermissionMatrix::areas()[$area]['label']) : null;
    }

    /** The record's human title: from the live record, else from the logged attributes. */
    public static function title(Activity $activity): ?string
    {
        $subject = $activity->subject;
        $logged = [
            ...(array) ($activity->properties['old'] ?? []),
            ...(array) ($activity->properties['attributes'] ?? []),
        ];
        $read = fn (string $attribute): mixed => $subject instanceof Model
            ? ($subject->getAttributes()[$attribute] ?? null)
            : ($logged[$attribute] ?? null);

        $key = $read('key');

        $title = match ($activity->subject_type) {
            HomeSection::class => is_string($key) && HomeSectionKey::tryFrom($key) ? __(HomeSectionKey::from($key)->label()) : null,
            Setting::class => is_string($key) ? __(ManageSettings::LABELS[$key] ?? Str::headline($key)) : null,
            UiString::class => is_string($key) ? $key : null,
            default => null,
        };

        foreach (['title', 'name', 'question', 'label', 'key'] as $attribute) {
            if ($title !== null) {
                break;
            }
            $title = self::text($read($attribute));
        }

        return $title === null || $title === '' ? null : Str::limit(Str::squish(strip_tags($title)), 70);
    }

    /** Plain string, or the current locale of a {en, ar} value (raw JSON from the DB included). */
    private static function text(mixed $value): ?string
    {
        if (is_string($value) && str_starts_with(ltrim($value), '{')) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : $value;
        }

        if (is_array($value)) {
            $value = $value[app()->getLocale()] ?? $value['en'] ?? collect($value)->filter()->first();
        }

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
