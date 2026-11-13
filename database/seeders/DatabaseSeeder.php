<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Seeds a fully populated bilingual site. Every seeder is idempotent and safe to re-run.
 *
 * Model events are disabled while seeding (no activity-log noise, no revalidation
 * webhooks), so caches that are normally cleared by events are cleared here.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ShieldPermissionSeeder::class, // needs roles
            SettingSeeder::class,
            UiStringSeeder::class,
            HomeSectionSeeder::class,
            StatSeeder::class,
            ProcessStepSeeder::class,
            ServiceCategorySeeder::class,
            FaqSeeder::class,       // needs service categories
            PageSeeder::class,
        ]);

        Cache::forget(Setting::CACHE_KEY);
    }
}
