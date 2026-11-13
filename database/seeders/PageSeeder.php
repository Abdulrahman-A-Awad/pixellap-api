<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PageKey;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Pages are matched by key; their blocks by (page, position).
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array<string, mixed>> $pages */
        $pages = require __DIR__.'/data/pages.php';

        foreach (PageKey::cases() as $key) {
            $data = $pages[$key->value];

            $page = Page::updateOrCreate(
                ['key' => $key->value],
                Arr::except($data, ['blocks']),
            );

            foreach ($data['blocks'] as $position => $block) {
                PageBlock::updateOrCreate(
                    ['page_id' => $page->id, 'sort' => $position + 1],
                    $block,
                );
            }
        }
    }
}
