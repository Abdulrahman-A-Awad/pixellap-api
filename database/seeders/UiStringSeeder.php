<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\UiString;
use Illuminate\Database\Seeder;

class UiStringSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array<string, array{0: string, 1: string}>> $groups */
        $groups = require __DIR__.'/data/ui_strings.php';

        foreach ($groups as $group => $strings) {
            foreach ($strings as $key => [$en, $ar]) {
                UiString::updateOrCreate(
                    ['key' => "{$group}.{$key}"],
                    ['group' => $group, 'value' => ['en' => $en, 'ar' => $ar]],
                );
            }
        }
    }
}
