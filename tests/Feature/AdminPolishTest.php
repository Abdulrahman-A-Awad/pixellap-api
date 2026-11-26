<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\CheckProduction;
use App\Enums\HomeSectionKey;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Support\PermissionMatrix;
use App\Filament\Widgets\LatestMessages;
use App\Filament\Widgets\MessagesChart;
use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WebsiteReadiness;
use App\Models\HomeSection;
use App\Models\UiString;
use App\Models\User;
use App\Support\ActivityDescriber;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Admin polish: roles matrix, single language switch, user form, activity log, dashboard. */
class AdminPolishTest extends TestCase
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

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    /* ─────────────────────────────── Roles matrix ─────────────────────────────── */

    public function test_matrix_maps_cells_to_shield_permissions_without_technical_ones(): void
    {
        $this->assertSame(['view_any_contact::message', 'view_contact::message'], PermissionMatrix::permissionsFor('messages', 'view'));
        $this->assertSame(['delete_contact::message', 'delete_any_contact::message'], PermissionMatrix::permissionsFor('messages', 'delete'));
        $this->assertSame([], PermissionMatrix::permissionsFor('messages', 'create'), 'no "Add" for messages');
        $this->assertSame(['page_ManageSettings'], PermissionMatrix::permissionsFor('settings', 'update'));

        $all = PermissionMatrix::toPermissions(array_map(
            fn (array $cells): array => array_map(fn (): bool => true, $cells),
            PermissionMatrix::emptyState(),
        ));

        foreach (['replicate_', 'restore_', 'force_delete_', 'widget_'] as $technical) {
            $this->assertEmpty(array_filter($all, fn (string $p): bool => str_starts_with($p, $technical)), $technical);
        }
    }

    public function test_creating_a_role_saves_exactly_the_ticked_permissions(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(CreateRole::class)
            ->assertFormFieldDoesNotExist('guard_name')
            ->fillForm(['name' => 'Designers', 'description' => 'Home page only'])
            ->set('data.matrix.home.view', true)
            ->set('data.matrix.home.update', true)
            ->set('data.matrix.messages.delete', true)
            ->set('data.matrix.settings.update', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::findByName('Designers', 'web');
        $this->assertSame('Home page only', $role->description);

        $expected = collect([
            ...PermissionMatrix::permissionsFor('home', 'view'),
            ...PermissionMatrix::permissionsFor('home', 'update'),
            'delete_contact::message', 'delete_any_contact::message',
            'page_ManageSettings',
        ])->sort()->values()->all();

        $this->assertSame($expected, $role->permissions->pluck('name')->sort()->values()->all());
        $this->assertNotContains('force_delete_contact::message', $role->permissions->pluck('name'));
        $this->assertNotContains('restore_contact::message', $role->permissions->pluck('name'));
    }

    public function test_row_and_global_select_all(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(CreateRole::class)->call('toggleMatrixRow', 'users');
        $this->assertSame(['view' => true, 'create' => true, 'update' => true, 'delete' => true], $page->get('data.matrix.users'));

        $page->call('toggleMatrixRow', 'users');
        $this->assertSame(['view' => false, 'create' => false, 'update' => false, 'delete' => false], $page->get('data.matrix.users'));

        $page->call('toggleMatrixAll');
        $this->assertTrue(collect($page->get('data.matrix'))->flatten()->every(fn ($v) => $v === true));

        $page->call('toggleMatrixAll');
        $this->assertTrue(collect($page->get('data.matrix'))->flatten()->every(fn ($v) => $v === false));
    }

    public function test_editing_a_role_loads_and_rewrites_its_matrix(): void
    {
        $this->actingAs($this->superAdmin());
        $sales = Role::findByName('sales', 'web');

        $page = Livewire::test(EditRole::class, ['record' => $sales->getKey()])
            ->assertFormSet(['name' => 'Sales']);
        $this->assertSame(['view' => true, 'update' => true, 'delete' => false], $page->get('data.matrix.messages'));
        $this->assertFalse($page->get('data.matrix.home.view'));

        $page->set('data.matrix.messages.update', false)
            ->set('data.matrix.ui.view', true)
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $sales->refresh();
        $this->assertSame('sales', $sales->name, 'built-in roles keep their name');
        $this->assertSame(
            ['view_any_contact::message', 'view_any_ui::string', 'view_contact::message', 'view_ui::string'],
            $sales->permissions->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_super_admin_role_cannot_be_edited_or_deleted(): void
    {
        $this->actingAs($this->superAdmin());
        $super = Role::findByName('super_admin', 'web');

        $this->assertFalse(RoleResource::canEdit($super));
        $this->assertFalse(RoleResource::canDelete($super));
        $this->assertTrue(Gate::denies('update', $super), 'not even a super admin');
        $this->assertTrue(Gate::denies('delete', $super));

        $this->get("/admin/roles/{$super->id}/edit")->assertForbidden();
        $this->get('/admin/roles')->assertOk()->assertSee('Full access')->assertSee('Super Admin');
    }

    public function test_built_in_roles_cannot_be_deleted_but_custom_ones_can(): void
    {
        $this->actingAs($this->superAdmin());

        foreach (PermissionMatrix::BUILT_IN as $name) {
            $this->assertFalse(RoleResource::canDelete(Role::findByName($name, 'web')), $name);
        }

        $this->assertTrue(RoleResource::canDelete(Role::create(['name' => 'Interns', 'guard_name' => 'web'])));
    }

    public function test_roles_list_shows_users_count_and_description(): void
    {
        $this->actingAs($this->superAdmin());
        $this->userWithRole('sales');
        $this->userWithRole('sales');

        $this->get('/admin/roles')->assertOk()
            ->assertSee('Reads and answers contact messages.')
            ->assertDontSee('guard_name')
            ->assertDontSee('App\\Models', false);

        $this->assertSame(2, RoleResource::getEloquentQuery()->where('name', 'sales')->first()->users_count);
    }

    /* ─────────────────────────────── Language ─────────────────────────────── */

    public function test_new_users_default_to_arabic_and_the_switch_drives_the_ui(): void
    {
        $plain = User::create(['name' => 'New person', 'email' => 'new@example.com', 'password' => 'password-123']);
        $this->assertSame('ar', $plain->fresh()->admin_locale, 'default for new accounts');

        $user = $this->superAdmin();
        $user->forceFill(['admin_locale' => 'en'])->save();

        $this->actingAs($user)->post('/admin/locale/ar')->assertRedirect();
        $this->assertSame('ar', $user->fresh()->admin_locale);

        // The stored value and the rendered UI always agree.
        $this->actingAs($user->fresh())->get('/admin')->assertOk()->assertSee('dir="rtl"', false)
            ->assertSee('English'); // the menu offers the other language
    }

    public function test_invalid_stored_locale_falls_back_to_arabic(): void
    {
        $user = $this->superAdmin();
        $user->forceFill(['admin_locale' => 'xx'])->save();

        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('dir="rtl"', false);
    }

    public function test_login_page_follows_the_last_language_chosen_in_this_browser(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('dir="rtl"', false);

        $user = $this->superAdmin();
        $this->actingAs($user)->post('/admin/locale/en')->assertCookie('admin_locale', 'en');

        auth()->logout();
        $this->withCookie('admin_locale', 'en')->get('/admin/login')->assertOk()->assertSee('dir="ltr"', false);
    }

    public function test_user_form_has_no_language_field(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(CreateUser::class)->assertFormFieldDoesNotExist('admin_locale');
        $this->get('/admin/users/create')->assertOk()->assertDontSee('Admin language');
    }

    /* ─────────────────────────────── Users form ─────────────────────────────── */

    public function test_user_create_form_opens_empty_without_autofill(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->assertFormSet(['name' => null, 'email' => null, 'password' => null, 'is_active' => true]);

        $html = $this->get('/admin/users/create')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="data\.name"[^>]*autocomplete="off"|autocomplete="off"[^>]*id="data\.name"/', $html);
        $this->assertMatchesRegularExpression('/id="data\.email"[^>]*autocomplete="off"|autocomplete="off"[^>]*id="data\.email"/', $html);
        $this->assertMatchesRegularExpression('/id="data\.password"[^>]*autocomplete="new-password"|autocomplete="new-password"[^>]*id="data\.password"/', $html);
        $this->assertStringNotContainsString('value="'.$admin->email.'"', $html);
    }

    public function test_generate_password_fills_a_strong_password(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(CreateUser::class)
            ->callFormComponentAction('password', 'generatePassword');

        $password = (string) $page->get('data.password');
        $this->assertSame(16, strlen($password));
    }

    /* ─────────────────────────────── Activity log ─────────────────────────────── */

    public function test_activity_rows_read_as_sentences(): void
    {
        $editor = $this->userWithRole('content_editor', ['name' => 'Abdelrahman Awad']);
        $this->actingAs($editor);

        HomeSection::where('key', HomeSectionKey::Stats)->firstOrFail()->update(['is_visible' => false]);
        $edited = Activity::query()->where('subject_type', HomeSection::class)->latest('id')->firstOrFail();
        $this->assertSame('Abdelrahman Awad edited the Home page section: Numbers', ActivityDescriber::sentence($edited));
        $this->assertSame('Edited', ActivityDescriber::eventLabel('updated'));

        auth()->logout();
        UiString::create(['key' => 'nav.test', 'group' => 'nav', 'value' => ['en' => 'Test', 'ar' => 'تجربة']]);
        $added = Activity::query()->where('subject_type', UiString::class)->latest('id')->firstOrFail();
        $this->assertSame('System added an interface text: nav.test', ActivityDescriber::sentence($added));

        app()->setLocale('ar');
        $this->assertSame('النظام أضاف نصًا للواجهة: nav.test', ActivityDescriber::sentence($added));
    }

    public function test_super_admin_clears_the_log_with_typed_confirmation(): void
    {
        $this->actingAs($this->superAdmin());
        activity()->log('one');
        activity()->log('two');

        Livewire::test(ListActivityLogs::class)
            ->callAction('clearLog', data: ['confirmation' => 'confirm'])
            ->assertHasActionErrors(['confirmation']);
        $this->assertGreaterThan(1, Activity::query()->count());

        Livewire::test(ListActivityLogs::class)
            ->callAction('clearLog', data: ['confirmation' => 'CONFIRM'])
            ->assertHasNoActionErrors();

        $this->assertSame(1, Activity::query()->count());
        $entry = Activity::query()->firstOrFail();
        $this->assertSame(ActivityDescriber::CLEARED, $entry->event);
        $this->assertSame('Super Admin cleared the activity log', ActivityDescriber::sentence($entry));
    }

    public function test_only_super_admin_sees_clear_log_and_admins_can_bulk_delete(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $rows = collect([activity()->log('a'), activity()->log('b')]);
        $keep = activity()->log('c');

        Livewire::test(ListActivityLogs::class)
            ->assertActionHidden('clearLog')
            ->callTableBulkAction('delete', $rows)
            ->assertHasNoTableBulkActionErrors();

        $this->assertDatabaseMissing('activity_log', ['id' => $rows[0]->id]);
        $this->assertDatabaseHas('activity_log', ['id' => $keep->id]);
        $this->assertFalse(ActivityLogResource::canClear());

        $this->actingAs($this->userWithRole('content_editor'));
        $this->assertFalse(ActivityLogResource::canDeleteAny());
    }

    public function test_retention_is_30_days_and_cleaned_daily(): void
    {
        $this->assertSame(30, (int) config('activitylog.delete_records_older_than_days'));

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e): bool => str_contains($e->command ?? '', 'activitylog:clean'));
        $this->assertNotNull($event, 'activitylog:clean is scheduled');
        $this->assertStringContainsString('--days=30', $event->command);
        $this->assertSame('15 3 * * *', $event->expression);

        $old = activity()->log('old');
        $old->forceFill(['created_at' => now()->subDays(31)])->save();
        $fresh = activity()->log('fresh');

        $this->artisan('activitylog:clean', ['--days' => 30, '--force' => true])->assertSuccessful();
        $this->assertDatabaseMissing('activity_log', ['id' => $old->id]);
        $this->assertDatabaseHas('activity_log', ['id' => $fresh->id]);
    }

    /* ─────────────────────────────── Dashboard ─────────────────────────────── */

    /** @return array<string, array{string, array<class-string, bool>}> */
    public static function dashboardRoles(): array
    {
        return [
            'super_admin' => ['super_admin', [StatsOverview::class => true, QuickActions::class => true, WebsiteReadiness::class => true, LatestMessages::class => true, MessagesChart::class => true]],
            'admin' => ['admin', [StatsOverview::class => true, QuickActions::class => true, WebsiteReadiness::class => true, LatestMessages::class => true, MessagesChart::class => true]],
            'content_editor' => ['content_editor', [StatsOverview::class => false, QuickActions::class => true, WebsiteReadiness::class => true, LatestMessages::class => false, MessagesChart::class => false]],
            'sales' => ['sales', [StatsOverview::class => true, QuickActions::class => false, WebsiteReadiness::class => false, LatestMessages::class => true, MessagesChart::class => false]],
        ];
    }

    #[DataProvider('dashboardRoles')]
    public function test_dashboard_widgets_follow_the_role(string $role, array $expected): void
    {
        $this->actingAs($role === 'super_admin' ? $this->superAdmin() : $this->userWithRole($role));

        foreach ($expected as $widget => $visible) {
            $this->assertSame($visible, $widget::canView(), "{$role}: ".class_basename($widget));
        }

        $this->get('/admin')->assertOk();
    }

    public function test_quick_actions_respect_permissions(): void
    {
        $this->actingAs($this->userWithRole('content_editor'));
        $keys = collect(QuickActions::tiles())->pluck('key')->all();

        $this->assertContains('home', $keys);
        $this->assertContains('ui', $keys);
        $this->assertNotContains('user', $keys, 'content editors cannot add users');
        $this->assertNotContains('settings', $keys, 'content editors have no site settings permission');

        $this->actingAs($this->superAdmin());
        $this->assertSame(['home', 'client', 'services', 'settings', 'ui', 'user'], collect(QuickActions::tiles())->pluck('key')->all());
    }

    public function test_greeting_and_date_follow_time_and_language(): void
    {
        $this->assertSame('Good morning', Dashboard::greeting(Carbon::parse('2026-11-26 08:00')));
        $this->assertSame('Good afternoon', Dashboard::greeting(Carbon::parse('2026-11-26 14:00')));
        $this->assertSame('Good evening', Dashboard::greeting(Carbon::parse('2026-11-26 20:00')));
        $this->assertSame('Thursday, 26 November 2026', Dashboard::today(Carbon::parse('2026-11-26 08:00')));

        app()->setLocale('ar');
        $this->assertSame('صباح الخير', Dashboard::greeting(Carbon::parse('2026-11-26 08:00')));
        $this->assertStringContainsString('الخميس', Dashboard::today(Carbon::parse('2026-11-26 08:00')));
    }

    public function test_month_over_month_change(): void
    {
        $this->assertSame(50, StatsOverview::percentChange(3, 2));
        $this->assertSame(-50, StatsOverview::percentChange(1, 2));
        $this->assertSame(0, StatsOverview::percentChange(0, 0));
        $this->assertNull(StatsOverview::percentChange(4, 0));
    }
}
