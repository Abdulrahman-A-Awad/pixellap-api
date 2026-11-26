<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\CheckProduction;
use App\Enums\HomeSectionKey;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\HomeSectionResource;
use App\Filament\Resources\HomeSectionResource\Pages\EditHomeSection;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\HomeItems\ClientsTable;
use App\Filament\Widgets\HomeItems\HomeFaqsTable;
use App\Filament\Widgets\HomeItems\ProcessStepsTable;
use App\Filament\Widgets\HomeItems\StatsTable;
use App\Filament\Widgets\LatestMessages;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WebsiteReadiness as ReadinessWidget;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\ProcessStep;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\User;
use App\Support\WebsiteReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Api\HeroMediaFiles;
use Tests\TestCase;

class AdminSimplifiedPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::where('email', CheckProduction::DEFAULT_ADMIN_EMAIL)->firstOrFail();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function section(HomeSectionKey $key): HomeSection
    {
        return HomeSection::where('key', $key)->firstOrFail();
    }

    /* ─────────────────────────────── Navigation ─────────────────────────────── */

    public function test_navigation_has_the_simplified_structure(): void
    {
        $html = $this->actingAs($this->superAdmin())->get('/admin')->assertOk()->getContent();

        foreach (['Content', 'Settings', 'Access', 'Home Page', 'Services', 'Pages', 'Site settings', 'Interface texts', 'Messages'] as $label) {
            $this->assertStringContainsString($label, $html, $label);
        }
        foreach (['/admin/users', '/admin/roles', '/admin/activity-log'] as $link) {
            $this->assertStringContainsString($link, $html, $link);
        }

        // The item lists moved inside their home section: no separate pages any more.
        foreach (['stats', 'process-steps', 'clients', 'faqs'] as $old) {
            $this->get("/admin/{$old}")->assertNotFound();
        }
    }

    /** Access menu per role: [Users, Activity log, Roles] in the menu. */
    public static function accessMenu(): array
    {
        return [
            'super_admin' => ['super_admin', true, true, true],
            'admin' => ['admin', true, true, false],
            'content_editor' => ['content_editor', false, false, false],
            'sales' => ['sales', false, false, false],
        ];
    }

    /** Navigation is built once per boot, so each role gets its own test (first panel request). */
    #[DataProvider('accessMenu')]
    public function test_access_menu_follows_the_role_matrix(string $role, bool $users, bool $activity, bool $roles): void
    {
        $this->actingAs($role === 'super_admin' ? $this->superAdmin() : $this->userWithRole($role));

        // Filament lists a resource when the user may view it (policy viewAny).
        $this->assertSame($users, UserResource::canViewAny());
        $this->assertSame($activity, ActivityLogResource::canViewAny());
        $this->assertSame($roles, RoleResource::canViewAny());

        $this->get('/admin')->assertOk(); // builds the navigation for this user
        $nav = collect(filament()->getNavigation())
            ->mapWithKeys(fn ($group): array => [(string) $group->getLabel() => collect($group->getItems())->map(fn ($item): string => $item->getUrl())->all()]);

        $expected = array_keys(array_filter([
            'users' => $users,
            'roles' => $roles,
            'activity-log' => $activity,
        ]));

        if ($expected === []) {
            $this->assertArrayNotHasKey('Access', $nav->all(), 'the Access group is hidden when it has no items');
        } else {
            $this->assertSame(
                array_map(fn (string $path): string => url("/admin/{$path}"), $expected),
                array_values($nav['Access']),
            );
        }
    }

    public function test_admin_activity_log_is_read_only_except_bulk_delete(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        activity()->log('test');
        $activity = Activity::query()->latest('id')->firstOrFail();

        $this->get('/admin/activity-log')->assertOk();
        $this->get("/admin/activity-log/{$activity->id}")->assertOk();
        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertFalse(ActivityLogResource::canEdit($activity));
        $this->assertFalse(ActivityLogResource::canDelete($activity));
        $this->assertTrue(ActivityLogResource::canDeleteAny(), 'admins may bulk delete selected rows');
        $this->assertFalse(ActivityLogResource::canClear(), 'only a super admin clears the whole log');
    }

    /* ─────────────────────────── Home sections list ─────────────────────────── */

    public function test_home_sections_list_shows_friendly_names_descriptions_and_counts(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin/home-sections')->assertOk()
            ->assertSee(['Hero', 'Numbers', 'Our services', 'How we work', 'Clients', 'About teaser', 'FAQ', 'Contact banner'])
            ->assertSee('Shown as a strip under “About”. Turn off to hide the numbers.', false)
            ->assertSee('Hidden: no items') // no client logos are seeded
            ->assertSee(Stat::count().' items');

        $this->assertSame(Faq::whereNull('service_category_id')->count(), HomeSectionResource::itemCount(HomeSectionKey::Faq));
        $this->assertNull(HomeSectionResource::itemCount(HomeSectionKey::Hero));
    }

    /* ─────────────────────────── Embedded item tables ───────────────────────── */

    public function test_each_list_section_embeds_its_items_table(): void
    {
        $this->actingAs($this->superAdmin());

        $expected = [
            'stats' => StatsTable::class,
            'process' => ProcessStepsTable::class,
            'clients' => ClientsTable::class,
            'faq' => HomeFaqsTable::class,
        ];

        foreach (HomeSectionKey::cases() as $key) {
            $page = Livewire::test(EditHomeSection::class, ['record' => $this->section($key)->getRouteKey()]);
            $widgets = (fn () => $this->getFooterWidgets())->call($page->instance());
            $this->assertSame(isset($expected[$key->value]) ? [$expected[$key->value]] : [], $widgets, $key->value);
        }

        $this->get(HomeSectionResource::getUrl('edit', ['record' => $this->section(HomeSectionKey::Stats)]))
            ->assertOk()
            ->assertSee('Numbers in this section');
    }

    public function test_stats_can_be_created_edited_and_deleted_inside_the_section(): void
    {
        $this->actingAs($this->superAdmin());
        $section = $this->section(HomeSectionKey::Stats);
        $table = Livewire::test(StatsTable::class, ['record' => $section]);

        $table->assertCanSeeTableRecords(Stat::all())
            ->callTableAction('create', data: ['value' => 12, 'suffix' => '+', 'label' => ['en' => 'Awards won', 'ar' => 'جوائز'], 'is_visible' => true])
            ->assertHasNoTableActionErrors();

        $stat = Stat::query()->latest('id')->firstOrFail();
        $this->assertSame('Awards won', $stat->getTranslation('label', 'en'));
        $this->assertSame(12, $stat->value);

        $table->callTableAction('edit', $stat, data: ['value' => 15, 'label' => ['en' => 'Awards', 'ar' => 'جوائز']])
            ->assertHasNoTableActionErrors();
        $this->assertSame(15, $stat->refresh()->value);

        // Validation is the same as before: the number is required.
        $table->callTableAction('create', data: ['label' => ['en' => 'No number']])
            ->assertHasTableActionErrors(['value']);

        Livewire::test(StatsTable::class, ['record' => $section])->callTableAction('delete', $stat);
        $this->assertModelMissing($stat);
    }

    public function test_both_languages_are_edited_side_by_side(): void
    {
        $this->actingAs($this->superAdmin());
        $step = ProcessStep::query()->ordered()->firstOrFail();

        Livewire::test(ProcessStepsTable::class, ['record' => $this->section(HomeSectionKey::Process)])
            ->mountTableAction('edit', $step)
            // The modal is filled with BOTH languages (no switcher).
            ->assertTableActionDataSet([
                'title.en' => $step->getTranslation('title', 'en'),
                'title.ar' => $step->getTranslation('title', 'ar'),
            ])
            ->setTableActionData(['title' => ['en' => 'Edited step', 'ar' => 'خطوة معدلة'], 'description' => ['en' => 'Text', 'ar' => 'وصف']])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $step->refresh();
        $this->assertSame('خطوة معدلة', $step->getTranslation('title', 'ar'));
        $this->assertSame('Edited step', $step->getTranslation('title', 'en'));
    }

    public function test_home_faq_table_lists_home_faqs_only(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(HomeFaqsTable::class, ['record' => $this->section(HomeSectionKey::Faq)])
            ->assertCanSeeTableRecords(Faq::whereNull('service_category_id')->get())
            ->assertCanNotSeeTableRecords(Faq::whereNotNull('service_category_id')->get());
    }

    public function test_content_editor_can_add_and_edit_items_but_not_delete(): void
    {
        $this->actingAs($this->userWithRole('content_editor'));
        $client = Client::create(['name' => 'Acme', 'is_visible' => true]);

        Livewire::test(ClientsTable::class, ['record' => $this->section(HomeSectionKey::Clients)])
            ->assertTableActionVisible('create')
            ->assertTableActionVisible('edit', $client)
            ->assertTableActionHidden('delete', $client);

        // Sales may not even open the home sections.
        $this->actingAs($this->userWithRole('sales'))
            ->get(HomeSectionResource::getUrl('edit', ['record' => $this->section(HomeSectionKey::Clients)]))
            ->assertForbidden();
    }

    /* ─────────────────────────────── Dashboard ──────────────────────────────── */

    public function test_dashboard_has_no_account_or_info_widget_and_the_new_stats(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin')->assertOk()
            ->assertDontSee('fi-account-widget', false)
            ->assertDontSee('filament-info-widget')
            ->assertSee(['New messages', 'Messages this month', 'Total messages', 'Services visible', 'Quick actions', 'Website readiness', 'Latest messages']);

        ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'message' => 'Hi', 'locale' => 'en']);
        $counts = StatsOverview::dailyCounts(30);
        $this->assertCount(30, $counts);
        $this->assertSame(1, end($counts));
    }

    public function test_latest_messages_shows_the_last_five_and_an_empty_state(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(LatestMessages::class)->assertSee('No messages yet');

        $messages = collect(range(1, 7))->map(fn (int $i): ContactMessage => ContactMessage::create([
            'name' => "Lead {$i}", 'email' => "lead{$i}@example.com", 'message' => 'Hello', 'locale' => 'en',
            'service_category_id' => ServiceCategory::first()->id,
        ]));

        Livewire::test(LatestMessages::class)
            ->assertCanSeeTableRecords($messages->slice(2))
            ->assertCanNotSeeTableRecords($messages->take(2))
            ->assertSee(ServiceCategory::first()->title);
    }

    public function test_readiness_widget_is_for_admins_and_content_editors(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $this->assertTrue(ReadinessWidget::canView());

        $this->actingAs($this->userWithRole('content_editor'));
        $this->assertTrue(ReadinessWidget::canView());

        $this->actingAs($this->userWithRole('sales'));
        $this->assertFalse(ReadinessWidget::canView());
        $this->get('/admin')->assertOk()->assertDontSee('Website readiness');
    }

    public function test_readiness_lists_what_is_missing_and_refreshes_after_a_save(): void
    {
        $this->actingAs($this->superAdmin());
        $checks = collect(WebsiteReadiness::checks())->keyBy('key');

        // Fresh seed: placeholders everywhere.
        foreach (['contact', 'hero_videos', 'showreel', 'clients', 'categories', 'stats', 'og_image', 'default_admin'] as $key) {
            $this->assertFalse($checks[$key]['ok'], $key);
            $this->assertNotNull($checks[$key]['url'], $key);
        }
        $this->assertContains('VAT number', $checks['contact']['detail']);

        Livewire::test(ReadinessWidget::class)->assertSee('Contact details filled in')->assertDontSee('All set ✓');

        // Saving a setting clears the 60 s cache.
        Setting::where('key', 'showreel_url')->firstOrFail()->update(['value' => 'https://vimeo.com/1']);
        $this->assertTrue(collect(WebsiteReadiness::checks())->firstWhere('key', 'showreel')['ok']);
    }

    public function test_readiness_shows_all_set_when_everything_is_in_place(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
        ]);

        foreach (['email' => 'hello@ppixelmedia.com', 'phone' => '+971 4 000 0000', 'whatsapp' => '971500000000',
            'vat_number' => '100000000000003', 'showreel_url' => 'https://vimeo.com/1', 'og_image' => 'settings/share.jpg'] as $key => $value) {
            Setting::where('key', $key)->firstOrFail()->update(['value' => $value]);
        }
        Setting::where('key', 'address')->firstOrFail()->update(['value' => ['en' => 'Dubai, UAE', 'ar' => 'دبي، الإمارات']]);

        $this->artisan('pixellap:attach-hero-media', ['dir' => HeroMediaFiles::folder()])->assertSuccessful();

        $svg = UploadedFile::fake()->createWithContent('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect width="1" height="1"/></svg>');
        foreach (ServiceCategory::all() as $category) {
            $category->addMedia($svg)->preservingOriginal()->toMediaCollection('icon');
            $category->addMedia(UploadedFile::fake()->image('cover.jpg', 200, 100))->toMediaCollection('cover');
        }

        Client::create(['name' => 'Acme', 'is_visible' => true])
            ->addMedia(UploadedFile::fake()->image('logo.png', 100, 40))->toMediaCollection('logo');

        foreach (Stat::all() as $stat) {
            $stat->setTranslations('label', ['en' => 'Projects', 'ar' => 'مشروع'])->save();
        }

        $owner = $this->userWithRole('super_admin');
        $this->superAdmin()->delete();
        $this->actingAs($owner);

        $failing = collect(WebsiteReadiness::checks())->reject(fn (array $c): bool => $c['ok'])->pluck('key')->all();
        $this->assertSame([], $failing);

        Livewire::test(ReadinessWidget::class)->assertSee('All set ✓');
    }
}
