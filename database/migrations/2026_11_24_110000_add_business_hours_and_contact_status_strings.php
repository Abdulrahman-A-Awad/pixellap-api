<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\UiString;
use App\Services\RevalidationService;
use App\Support\CacheTags;
use Illuminate\Database\Migrations\Migration;

/**
 * Contact polish on existing installations:
 * - `business_hours` setting (structured: working days + open/close time, Asia/Dubai) for the
 *   website's live "Open now / Closed" status; default Mon–Fri 09:00–18:00. Created only when missing.
 * - the opening-status interface strings (created only when missing);
 * - "Service of interest" → "Service needed" (English label of the service picker), only while it
 *   still has the original seeded text — an editor's wording is never overwritten.
 * Fresh installs get all of it from the seeders.
 */
return new class extends Migration
{
    private const KEYS = [
        'contact.open_now',
        'contact.closed_now',
        'contact.closes_at',
        'contact.opens_today',
        'contact.opens_tomorrow',
        'contact.opens_at',
        'contact.uae_time',
    ];

    private const SERVICE_LABEL_OLD = 'Service of interest';

    private const SERVICE_LABEL_NEW = 'Service needed';

    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'business_hours'],
            [
                'group' => 'contact',
                'type' => Setting::TYPE_JSON,
                'value' => ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'open' => '09:00', 'close' => '18:00', 'timezone' => 'Asia/Dubai'],
            ],
        );

        /** @var array<string, array<string, array{0: string, 1: string}>> $groups */
        $groups = require database_path('seeders/data/ui_strings.php');
        foreach (self::KEYS as $fullKey) {
            [$group, $key] = explode('.', $fullKey, 2);
            if (! isset($groups[$group][$key])) {
                continue;
            }
            [$en, $ar] = $groups[$group][$key];
            UiString::firstOrCreate(
                ['key' => $fullKey],
                ['group' => $group, 'value' => ['en' => $en, 'ar' => $ar]],
            );
        }

        $this->swapServiceLabel(self::SERVICE_LABEL_OLD, self::SERVICE_LABEL_NEW);

        $revalidation = app(RevalidationService::class);
        $revalidation->touch([CacheTags::SITE, CacheTags::HOME]);
        $revalidation->flush();
    }

    public function down(): void
    {
        $this->swapServiceLabel(self::SERVICE_LABEL_NEW, self::SERVICE_LABEL_OLD);
        UiString::query()->whereIn('key', self::KEYS)->delete();
        Setting::query()->where('key', 'business_hours')->delete();
    }

    private function swapServiceLabel(string $from, string $to): void
    {
        $label = UiString::query()->where('key', 'form.service')->first();
        if ($label !== null && $label->getTranslation('value', 'en', false) === $from) {
            $label->setTranslation('value', 'en', $to);
            $label->save();
        }
    }
};
