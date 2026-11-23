<?php

declare(strict_types=1);

use App\Models\UiString;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the interface strings of the redesigned category page (hero facts, explore action, process
 * eyebrow, category CTA title) on existing installations. Only keys that do not exist yet are
 * created: strings editors changed in the admin are never touched (UiStringSeeder would overwrite
 * them). Fresh installs get them from the seeder.
 */
return new class extends Migration
{
    private const KEYS = [
        'services.fact_bilingual',
        'services.explore_services',
        'services.process_eyebrow',
        'services.cta_title',
    ];

    public function up(): void
    {
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
    }

    public function down(): void
    {
        // Content rows: left in place (they may have been edited since).
    }
};
