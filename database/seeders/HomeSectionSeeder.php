<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\HomeSectionKey;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array<string, mixed>> $sections */
        $sections = (require __DIR__.'/data/home.php')['sections'];

        foreach (HomeSectionKey::cases() as $index => $key) {
            HomeSection::updateOrCreate(
                ['key' => $key->value],
                [...($sections[$key->value] ?? []), 'sort' => $index + 1],
            );
        }
    }
}
