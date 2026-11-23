<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\RevalidationService;
use App\Support\ServiceAnchors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** GET /service-categories?include=services + the shared service anchors. */
class ServicesIncludeTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-site-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['pixellap.api_site_key' => self::KEY]);
        $this->seed();
    }

    /** @return list<array<string, mixed>> */
    private function categories(string $query = '', string $locale = 'en'): array
    {
        return $this->getJson("/api/v1/service-categories?locale={$locale}{$query}", ['X-Site-Key' => self::KEY])
            ->assertOk()
            ->json('data');
    }

    public function test_plain_list_has_no_services(): void
    {
        $category = $this->categories()[0];

        $this->assertArrayNotHasKey('services', $category);
        $this->assertArrayNotHasKey('description', $category);
    }

    public function test_include_services_returns_light_rows_for_every_visible_service(): void
    {
        $categories = $this->categories('&include=services');

        $this->assertCount(6, $categories);
        $this->assertSame(43, array_sum(array_map(fn (array $c): int => count($c['services']), $categories)));
        $first = $categories[0];
        $this->assertArrayHasKey('description', $first);
        $this->assertArrayHasKey('hero_video', $first);
        $this->assertSame(['id', 'title', 'short', 'anchor'], array_keys($first['services'][0]));
        $this->assertSame('brand-strategy-positioning', $first['services'][0]['anchor']);
        foreach ($categories as $category) {
            $this->assertSame($category['services_count'], count($category['services']));
            foreach ($category['services'] as $service) {
                $this->assertLessThanOrEqual(ServiceAnchors::SHORT_MAX, mb_strlen($service['short']));
            }
        }

        // Hidden services are left out.
        Service::query()->where('id', $first['services'][0]['id'])->update(['is_visible' => false]);
        app(RevalidationService::class)->touch(['categories']);
        app(RevalidationService::class)->flush();
        $this->assertCount(count($first['services']) - 1, $this->categories('&include=services')[0]['services']);
    }

    public function test_anchors_are_english_in_both_locales_and_match_the_category_page(): void
    {
        $en = $this->categories('&include=services')[0];
        $ar = $this->categories('&include=services', 'ar')[0];
        $this->assertSame(array_column($en['services'], 'anchor'), array_column($ar['services'], 'anchor'));

        $detail = $this->getJson("/api/v1/service-categories/{$en['slug']}?locale=ar", ['X-Site-Key' => self::KEY])
            ->assertOk()
            ->json('data.services');
        $this->assertSame(array_column($en['services'], 'anchor'), array_column($detail, 'anchor'));
    }

    public function test_anchors_are_unique_within_a_category(): void
    {
        $category = ServiceCategory::query()->firstOrFail();
        $services = $category->services()->orderBy('sort')->get();
        $services[1]->setTranslation('title', 'en', (string) $services[0]->getTranslation('title', 'en'));
        $services[2]->setTranslation('title', 'en', '');

        $anchors = array_values(ServiceAnchors::for($services));
        $this->assertSame($anchors[0].'-2', $anchors[1]);
        $this->assertSame('service-'.$services[2]->id, $anchors[2]);
        $this->assertSame(count($anchors), count(array_unique($anchors)));
    }

    public function test_short_is_the_first_sentence_capped_at_a_word_boundary(): void
    {
        $this->assertSame('One sentence.', ServiceAnchors::short("One sentence.  Second one here.\n"));
        $this->assertSame('سؤال؟', ServiceAnchors::short('سؤال؟ جواب.'));
        $long = str_repeat('word ', 60).'end.';
        $short = ServiceAnchors::short($long);
        $this->assertLessThanOrEqual(ServiceAnchors::SHORT_MAX, mb_strlen($short));
        $this->assertStringEndsWith('word...', $short);
        $this->assertSame('', ServiceAnchors::short(null));
    }
}
