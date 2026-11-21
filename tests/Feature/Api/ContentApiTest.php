<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\HomeSectionKey;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\PageBlock;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-site-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['pixellap.api_site_key' => self::KEY]);
        $this->seed();
    }

    private function api(string $uri, string $locale = 'en'): TestResponse
    {
        $sep = str_contains($uri, '?') ? '&' : '?';

        return $this->getJson("/api/v1/{$uri}{$sep}locale={$locale}", ['X-Site-Key' => self::KEY]);
    }

    private function firstCategory(): ServiceCategory
    {
        return ServiceCategory::query()->ordered()->firstOrFail();
    }

    public function test_site_key_is_required_outside_local(): void
    {
        $this->getJson('/api/v1/site')->assertUnauthorized();
        $this->getJson('/api/v1/site', ['X-Site-Key' => 'wrong'])->assertUnauthorized();
        $this->api('site')->assertOk();
    }

    public function test_site_returns_settings_strings_nav_and_locales(): void
    {
        $en = $this->api('site')->assertOk()
            ->assertJsonPath('meta.locale', 'en')
            ->assertJsonPath('data.settings.site_name', 'Pixellap') // localized: "بكسل لاب" in ar (AdminHomeAndSettingsTest)
            ->assertJsonPath('data.locales', ['en', 'ar'])
            ->assertJsonPath('data.nav.categories.0.slug', $this->firstCategory()->slug);
        $this->assertNotNull($en->json('meta.generated_at'));
        $this->assertSame('Services', $en->json('data.ui_strings')['nav.services']); // flat keys contain dots

        $ar = $this->api('site', 'ar')->assertOk()
            ->assertJsonPath('meta.locale', 'ar')
            ->assertJsonPath('data.settings.tagline', 'بيكسلات صغيرة. أثر كبير.');
        $this->assertSame('خدماتنا', $ar->json('data.ui_strings')['nav.services']);
    }

    public function test_site_nav_excludes_hidden_categories(): void
    {
        $hidden = $this->firstCategory();
        $hidden->updateQuietly(['is_visible' => false]);

        $slugs = collect($this->api('site')->json('data.nav.categories'))->pluck('slug');
        $this->assertNotContains($hidden->slug, $slugs);
    }

    public function test_home_returns_visible_sections_in_order_with_data(): void
    {
        HomeSection::query()->where('key', HomeSectionKey::About)->update(['is_visible' => false]);
        Stat::query()->ordered()->firstOrFail()->updateQuietly(['is_visible' => false]);

        $res = $this->api('home')->assertOk();
        $sections = collect($res->json('data'));

        $expected = HomeSection::query()->visible()->ordered()->pluck('key')->map->value->all();
        $this->assertSame($expected, $sections->pluck('key')->all());
        $this->assertNotContains('about', $sections->pluck('key'));

        $stats = $sections->firstWhere('key', 'stats');
        $this->assertCount(Stat::query()->visible()->count(), $stats['items']);

        $services = $sections->firstWhere('key', 'services');
        $this->assertSame($this->firstCategory()->slug, $services['categories'][0]['slug']);
        $this->assertNotEmpty($services['categories'][0]['services']);
        $this->assertIsString($services['categories'][0]['services'][0]);

        $faq = $sections->firstWhere('key', 'faq');
        $this->assertCount(Faq::query()->forHome()->visible()->count(), $faq['faqs']);

        $this->assertArrayHasKey('response_time', $sections->firstWhere('key', 'contact_cta'));
        $this->assertArrayHasKey('steps', $sections->firstWhere('key', 'process'));
    }

    public function test_home_in_arabic(): void
    {
        $en = $this->api('home')->json('data');
        $ar = $this->api('home', 'ar')->assertOk()->assertJsonPath('meta.locale', 'ar')->json('data');

        $hero = HomeSection::query()->where('key', HomeSectionKey::Hero)->firstOrFail();
        $this->assertSame($hero->getTranslation('subtitle', 'ar'), collect($ar)->firstWhere('key', 'hero')['subtitle']);
        $this->assertNotSame(collect($en)->firstWhere('key', 'hero')['subtitle'], collect($ar)->firstWhere('key', 'hero')['subtitle']);
    }

    public function test_missing_arabic_falls_back_to_english(): void
    {
        $category = $this->firstCategory();
        $category->setTranslation('title', 'ar', '')->saveQuietly();

        $this->api('service-categories', 'ar')
            ->assertJsonPath('data.0.title', $category->getTranslation('title', 'en'));
    }

    public function test_page_returns_visible_blocks_only(): void
    {
        $block = PageBlock::query()->whereHas('page', fn ($q) => $q->where('key', 'about'))->ordered()->firstOrFail();
        $block->updateQuietly(['is_visible' => false]);

        $res = $this->api('pages/about')->assertOk()->assertJsonPath('data.key', 'about');
        $this->assertNotContains($block->id, collect($res->json('data.blocks'))->pluck('id'));
        $this->assertNotEmpty($res->json('data.blocks'));

        $this->api('pages/about', 'ar')->assertOk()->assertJsonPath('meta.locale', 'ar');
    }

    public function test_unknown_page_is_404(): void
    {
        $this->api('pages/nope')->assertNotFound()->assertExactJson(['message' => 'Not found.']);
    }

    public function test_categories_list_with_visible_services_count(): void
    {
        $category = $this->firstCategory();
        $visible = $category->services()->where('is_visible', true)->count();
        $category->services()->ordered()->firstOrFail()->updateQuietly(['is_visible' => false]);

        $this->api('service-categories')->assertOk()
            ->assertJsonPath('data.0.slug', $category->slug)
            ->assertJsonPath('data.0.services_count', $visible - 1);

        $this->api('service-categories', 'ar')->assertOk()
            ->assertJsonPath('data.0.title', $category->getTranslation('title', 'ar'));
    }

    public function test_category_detail_excludes_hidden_services_and_faqs(): void
    {
        $category = $this->firstCategory();
        $service = Service::query()->where('service_category_id', $category->id)->ordered()->firstOrFail();
        $faq = Faq::query()->where('service_category_id', $category->id)->ordered()->firstOrFail();
        $service->updateQuietly(['is_visible' => false]);
        $faq->updateQuietly(['is_visible' => false]);

        $res = $this->api("service-categories/{$category->slug}")->assertOk()
            ->assertJsonPath('data.slug', $category->slug);

        $this->assertNotContains($service->id, collect($res->json('data.services'))->pluck('id'));
        $this->assertNotContains($faq->id, collect($res->json('data.faqs'))->pluck('id'));
        $this->assertIsArray($res->json('data.services.0.features'));

        $this->api("service-categories/{$category->slug}", 'ar')->assertOk()
            ->assertJsonPath('data.title', $category->getTranslation('title', 'ar'));
    }

    public function test_hidden_or_missing_category_is_404(): void
    {
        $category = $this->firstCategory();
        $category->updateQuietly(['is_visible' => false]);

        $this->api("service-categories/{$category->slug}")->assertNotFound();
        $this->api('service-categories/does-not-exist')->assertNotFound();
        $this->assertNotContains($category->slug, collect($this->api('service-categories')->json('data'))->pluck('slug'));
    }

    public function test_sitemap_lists_both_locales(): void
    {
        $res = $this->api('sitemap')->assertOk();
        $paths = collect($res->json('data'))->pluck('path');

        $slug = $this->firstCategory()->slug;
        foreach (['/en', '/ar', '/en/about', '/ar/contact', '/en/services', "/ar/services/{$slug}"] as $path) {
            $this->assertContains($path, $paths);
        }
        $this->assertSame(2 * (4 + ServiceCategory::query()->visible()->count()), $paths->count());
    }

    public function test_responses_are_cached_until_content_changes(): void
    {
        $category = $this->firstCategory();
        $first = $this->api('service-categories')->json('meta.generated_at');

        // A quiet change (no events) is not visible: the cached response is served.
        $category->updateQuietly(['title' => ['en' => 'Quiet change', 'ar' => 'تغيير']]);
        $this->travel(2)->seconds();
        $this->api('service-categories')->assertJsonPath('meta.generated_at', $first)->assertJsonMissing(['title' => 'Quiet change']);

        // A real save flushes the "categories" tag at the end of the request.
        $category->update(['title' => ['en' => 'Loud change', 'ar' => 'تغيير']]);
        $this->app->terminate();
        $this->api('service-categories')->assertJsonPath('data.0.title', 'Loud change');
    }
}
