<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Home FAQs (no category) + per-category FAQs. Matched by (category, position).
 * Must run after ServiceCategorySeeder.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $homeFaqs */
        $homeFaqs = (require __DIR__.'/data/home.php')['faqs'];
        $this->seedGroup(null, $homeFaqs);

        /** @var list<array<string, mixed>> $categories */
        $categories = require __DIR__.'/data/service_categories.php';
        $ids = ServiceCategory::query()->pluck('id', 'slug');

        foreach ($categories as $category) {
            $this->seedGroup($ids[$category['slug']], $category['faqs']);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $faqs
     */
    private function seedGroup(?int $categoryId, array $faqs): void
    {
        foreach ($faqs as $position => $faq) {
            Faq::updateOrCreate(
                ['service_category_id' => $categoryId, 'sort' => $position + 1],
                $faq,
            );
        }
    }
}
