<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Categories are matched by slug; their services by (category, position).
 */
class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $categories */
        $categories = require __DIR__.'/data/service_categories.php';

        foreach ($categories as $index => $data) {
            $category = ServiceCategory::updateOrCreate(
                ['slug' => $data['slug']],
                [...Arr::except($data, ['slug', 'services', 'faqs']), 'sort' => $index + 1],
            );

            foreach ($data['services'] as $position => $service) {
                Service::updateOrCreate(
                    ['service_category_id' => $category->id, 'sort' => $position + 1],
                    $service,
                );
            }
        }
    }
}
