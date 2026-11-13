<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Stat;
use Illuminate\Database\Seeder;

/**
 * Stats have no natural key, so rows are matched by position (sort).
 */
class StatSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $stats */
        $stats = (require __DIR__.'/data/home.php')['stats'];

        foreach ($stats as $index => $stat) {
            Stat::updateOrCreate(['sort' => $index + 1], $stat);
        }
    }
}
