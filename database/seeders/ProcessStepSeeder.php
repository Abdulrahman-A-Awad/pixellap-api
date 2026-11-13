<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProcessStep;
use Illuminate\Database\Seeder;

/**
 * Process steps have no natural key, so rows are matched by position (sort).
 */
class ProcessStepSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $steps */
        $steps = (require __DIR__.'/data/home.php')['process_steps'];

        foreach ($steps as $index => $step) {
            ProcessStep::updateOrCreate(['sort' => $index + 1], $step);
        }
    }
}
