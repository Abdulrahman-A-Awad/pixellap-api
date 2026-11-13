<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Settings hold operational data (phone, VAT, socials) that editors fill in,
     * so re-seeding only adds missing keys and never overwrites existing values.
     */
    public function run(): void
    {
        /** @var list<array{key: string, group: string, type: string, value: mixed}> $settings */
        $settings = require __DIR__.'/data/settings.php';

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                ['group' => $setting['group'], 'type' => $setting['type'], 'value' => $setting['value']],
            );
        }
    }
}
