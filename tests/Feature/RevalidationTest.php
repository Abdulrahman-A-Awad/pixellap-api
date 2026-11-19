<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HomeSectionKey;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ServiceCategoryResource\Pages\EditServiceCategory;
use App\Filament\Resources\ServiceCategoryResource\RelationManagers\ServicesRelationManager;
use App\Filament\Widgets\HomeItems\StatsTable;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Stat;
use App\Models\User;
use App\Services\RevalidationService;
use App\Support\CacheTags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class RevalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /** End the "request": the collected tags are flushed once. */
    private function terminate(): void
    {
        $this->app->terminate();
    }

    /** @return list<list<string>> tags of every webhook call so far */
    private function postedTags(): array
    {
        return Http::recorded()
            ->map(fn (array $pair): Request => $pair[0])
            ->filter(fn (Request $r): bool => $r->url() === self::REVALIDATE_URL)
            ->map(fn (Request $r): array => $r['tags'])
            ->values()
            ->all();
    }

    public function test_seeding_posts_nothing(): void
    {
        $this->seed(); // re-run: updateOrCreate touches every row, events are disabled
        $this->terminate();

        Http::assertNothingSent();
    }

    public function test_saving_a_service_posts_once_with_expected_tags(): void
    {
        $category = ServiceCategory::query()->ordered()->firstOrFail();
        $service = $category->services()->ordered()->firstOrFail();

        $service->update(['title' => ['en' => 'New title', 'ar' => 'عنوان جديد']]);
        $service->update(['is_visible' => false]); // second change in the same request
        $this->terminate();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r): bool => $r->url() === self::REVALIDATE_URL
            && $r->method() === 'POST'
            && $r->hasHeader('X-Revalidate-Secret', 'test-secret'));

        $tags = $this->postedTags()[0];
        foreach ([CacheTags::HOME, CacheTags::CATEGORIES, CacheTags::category($category->slug)] as $tag) {
            $this->assertContains($tag, $tags);
        }
        $this->assertNotContains(CacheTags::SITE, $tags);
    }

    public function test_moving_a_service_touches_old_and_new_category(): void
    {
        [$from, $to] = ServiceCategory::query()->ordered()->take(2)->get()->all();
        $service = $from->services()->firstOrFail();

        $service->update(['service_category_id' => $to->id]);
        $this->terminate();

        $tags = $this->postedTags()[0];
        $this->assertContains(CacheTags::category($from->slug), $tags);
        $this->assertContains(CacheTags::category($to->slug), $tags);
    }

    public function test_renaming_a_category_slug_touches_old_and_new_slug(): void
    {
        $category = ServiceCategory::query()->ordered()->firstOrFail();
        $old = $category->slug;

        $category->update(['slug' => 'renamed-category']);
        $this->terminate();

        $tags = $this->postedTags()[0];
        foreach ([CacheTags::SITE, CacheTags::HOME, CacheTags::CATEGORIES, CacheTags::SITEMAP, "category:{$old}", 'category:renamed-category'] as $tag) {
            $this->assertContains($tag, $tags);
        }
    }

    public function test_faq_tags_depend_on_category(): void
    {
        $category = ServiceCategory::query()->ordered()->firstOrFail();
        $this->assertSame([CacheTags::HOME], CacheTags::for(Faq::query()->forHome()->firstOrFail()));
        $this->assertSame(
            [CacheTags::category($category->slug)],
            CacheTags::for(Faq::query()->where('service_category_id', $category->id)->firstOrFail()),
        );
    }

    public function test_reordering_services_posts_once_and_logs_activity(): void
    {
        $category = ServiceCategory::query()->ordered()->firstOrFail();
        $ids = $category->services()->ordered()->pluck('id')->reverse()->values()->map(fn ($id) => (string) $id)->all();

        $this->actingAs(User::where('email', 'admin@pixellap.test')->firstOrFail());

        Livewire::test(ServicesRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditServiceCategory::class,
        ])->call('reorderTable', $ids);

        $this->terminate();

        $this->assertSame(array_map('intval', $ids), $category->services()->ordered()->pluck('id')->all());

        Http::assertSentCount(1);
        $tags = $this->postedTags()[0];
        $this->assertContains(CacheTags::category($category->slug), $tags);
        $this->assertContains(CacheTags::HOME, $tags);

        $log = Activity::query()->where('event', 'reordered')->sole();
        $this->assertSame('Service', $log->properties['attributes']['model']);
        $this->assertSame(array_map('intval', $ids), $log->properties['attributes']['order']);
        $this->assertTrue($log->subject->is($category));
    }

    public function test_reordering_home_section_items_revalidates_and_logs_on_the_section(): void
    {
        $ids = Stat::query()->ordered()->pluck('id')->reverse()->values()->map(fn ($id) => (string) $id)->all();
        $section = HomeSection::where('key', HomeSectionKey::Stats)->firstOrFail();

        $this->actingAs(User::where('email', 'admin@pixellap.test')->firstOrFail());
        Livewire::test(StatsTable::class, ['record' => $section])->call('reorderTable', $ids);
        $this->terminate();

        Http::assertSentCount(1);
        $this->assertSame([CacheTags::HOME], $this->postedTags()[0]);
        $this->assertSame(array_map('intval', $ids), Stat::query()->ordered()->pluck('id')->all());

        $log = Activity::query()->where('event', 'reordered')->sole();
        $this->assertSame('Stat', $log->properties['attributes']['model']);
        $this->assertTrue($log->subject->is($section));
    }

    public function test_refresh_action_touches_all_tags_for_admins_only(): void
    {
        $this->actingAs(User::where('email', 'admin@pixellap.test')->firstOrFail());
        Livewire::test(Dashboard::class)->callAction('refreshWebsiteCache');
        $this->terminate();

        $tags = $this->postedTags()[0];
        $this->assertContains('page:about', $tags);
        $this->assertContains(CacheTags::category(ServiceCategory::query()->firstOrFail()->slug), $tags);

        $editor = User::factory()->create();
        $editor->assignRole('content_editor');
        $this->actingAs($editor);
        Livewire::test(Dashboard::class)->assertActionHidden('refreshWebsiteCache');
    }

    public function test_frontend_failure_never_throws(): void
    {
        config(['pixellap.frontend_revalidate_url' => 'http://down.test/api/revalidate']);
        Http::fake(['http://down.test/*' => Http::response('down', 503)]);

        Service::query()->firstOrFail()->update(['is_visible' => false]);
        $this->terminate(); // sync queue: the job fails, the save must not

        $this->assertSame([], app(RevalidationService::class)->pending());
    }
}
