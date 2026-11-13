<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ContactMessageResource\Pages\ViewContactMessage;
use App\Filament\Resources\ServiceCategoryResource\Pages\EditServiceCategory;
use App\Filament\Resources\StatResource\Pages\ListStats;
use App\Filament\Resources\UiStringResource\Pages\ManageUiStrings;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\UiString;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    /** Admin index paths (relative to /admin). */
    private const INDEXES = [
        'home-sections', 'stats', 'process-steps', 'clients', 'faqs',
        'service-categories', 'pages', 'contact-messages', 'settings',
        'ui-strings', 'users', 'shield/roles', 'activity-log',
    ];

    /** Which indexes each role may open (everything else must be 403). */
    private const ALLOWED = [
        'super_admin' => self::INDEXES,
        'admin' => [
            'home-sections', 'stats', 'process-steps', 'clients', 'faqs', 'service-categories', 'pages',
            'contact-messages', 'settings', 'ui-strings', 'users', 'activity-log',
        ],
        'content_editor' => [
            'home-sections', 'stats', 'process-steps', 'clients', 'faqs', 'service-categories', 'pages', 'ui-strings',
        ],
        'sales' => ['contact-messages'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(); // roles, permissions and all content
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function superAdmin(): User
    {
        return User::where('email', 'admin@pixellap.test')->firstOrFail();
    }

    private function message(): ContactMessage
    {
        return ContactMessage::create([
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'phone' => '+971 50 123 4567',
            'message' => 'We need a new website.',
            'locale' => 'en',
            'service_category_id' => ServiceCategory::first()->id,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function roles(): array
    {
        return array_combine(array_keys(self::ALLOWED), array_map(fn ($r) => [$r], array_keys(self::ALLOWED)));
    }

    #[DataProvider('roles')]
    public function test_role_can_only_reach_its_resources(string $role): void
    {
        $user = $role === 'super_admin' ? $this->superAdmin() : $this->userWithRole($role);

        foreach (self::INDEXES as $path) {
            $expected = in_array($path, self::ALLOWED[$role], true) ? 200 : 403;

            $this->actingAs($user)
                ->get("/admin/{$path}")
                ->assertStatus($expected);
        }
    }

    public function test_content_editor_cannot_delete_anything(): void
    {
        $editor = $this->userWithRole('content_editor');

        foreach ([Stat::first(), ServiceCategory::first(), Service::first(), Faq::first(), PageBlock::first(), UiString::first()] as $record) {
            $this->assertTrue(Gate::forUser($editor)->denies('delete', $record), class_basename($record).' delete');
            $this->assertTrue(Gate::forUser($editor)->allows('update', $record), class_basename($record).' update');
        }

        $this->actingAs($editor);
        Livewire::test(ListStats::class)
            ->assertTableActionHidden('delete', Stat::first())
            ->assertTableActionVisible('edit', Stat::first());
    }

    public function test_content_editor_can_edit_but_not_create_interface_texts(): void
    {
        $this->actingAs($this->userWithRole('content_editor'));

        Livewire::test(ManageUiStrings::class)
            ->assertActionHidden('create')
            ->assertTableActionVisible('edit', UiString::first())
            ->assertTableActionHidden('delete', UiString::first());
    }

    public function test_sales_sees_only_the_inbox(): void
    {
        $this->actingAs($this->userWithRole('sales'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Messages')
            ->assertDontSee('Home sections')
            ->assertDontSee('Service categories')
            ->assertDontSee('Site settings')
            ->assertDontSee('/admin/users') // nav link ("Users" alone matches Windows paths in Livewire snapshots)
            // Widgets lazy-load: check which widget components are on the dashboard.
            ->assertSee('LatestMessages')
            ->assertDontSee('StatsOverview');
    }

    public function test_sales_can_triage_a_message(): void
    {
        $message = $this->message();
        $sales = $this->userWithRole('sales');

        $this->actingAs($sales)->get("/admin/contact-messages/{$message->id}")->assertOk()->assertSee('wa.me/971501234567');

        Livewire::test(ViewContactMessage::class, ['record' => $message->getKey()])
            ->set('triage.status', ContactStatus::Replied->value)
            ->set('triage.assigned_to', $sales->id)
            ->set('triage.internal_notes', 'Called back.')
            ->call('saveTriage')
            ->assertHasNoErrors();

        $message->refresh();
        $this->assertSame(ContactStatus::Replied, $message->status);
        $this->assertSame($sales->id, $message->assigned_to);
        $this->assertTrue(Gate::forUser($sales)->denies('delete', $message));
    }

    public function test_admin_cannot_see_or_edit_a_super_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $super = $this->superAdmin();

        $this->assertTrue(Gate::forUser($admin)->denies('update', $super));
        $this->assertTrue(Gate::forUser($admin)->denies('delete', $super));

        // Super admins are filtered out of the admin's user list entirely → 404.
        $this->actingAs($admin)->get("/admin/users/{$super->id}/edit")->assertNotFound();
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertDontSee('admin@pixellap.test');
    }

    public function test_admin_cannot_grant_super_admin_role(): void
    {
        $admin = $this->userWithRole('admin');
        $editor = $this->userWithRole('content_editor');
        $superRoleId = Role::findByName('super_admin')->id;

        $this->actingAs($admin);
        // A crafted request with the (hidden) super_admin role id is rejected.
        Livewire::test(EditUser::class, ['record' => $editor->getKey()])
            ->fillForm(['roles' => [$superRoleId]])
            ->call('save')
            ->assertHasFormErrors(['roles']);

        $this->assertFalse($editor->fresh()->isSuperAdmin());
        $this->assertTrue($editor->fresh()->hasRole('content_editor'));
    }

    public function test_nobody_can_delete_or_deactivate_themselves(): void
    {
        $admin = $this->userWithRole('admin');
        $super = $this->superAdmin();

        $this->assertTrue(Gate::forUser($admin)->denies('delete', $admin));
        $this->assertTrue(Gate::forUser($super)->denies('delete', $super));
        $this->assertTrue(Gate::forUser($super)->allows('delete', $admin));

        $this->actingAs($admin);
        Livewire::test(EditUser::class, ['record' => $admin->getKey()])
            ->assertFormFieldIsDisabled('is_active')
            ->fillForm(['is_active' => false])
            ->call('save');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_switching_content_locale_shows_arabic_copy(): void
    {
        $this->actingAs($this->superAdmin());
        $category = ServiceCategory::where('slug', 'brand-identity-design')->firstOrFail();

        Livewire::test(EditServiceCategory::class, ['record' => $category->getKey()])
            ->assertFormSet(['title' => 'Brand Identity & Design'])
            ->set('activeLocale', 'ar')
            ->assertFormSet(['title' => 'بناء الهوية البصرية والتصميم الإبداعي', 'slug' => 'brand-identity-design']);
    }

    public function test_locale_toggle_flips_admin_locale(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)->post('/admin/locale/toggle')->assertRedirect();
        $this->assertSame('ar', $user->fresh()->admin_locale);

        $this->actingAs($user->fresh())->post('/admin/locale/toggle')->assertRedirect();
        $this->assertSame('en', $user->fresh()->admin_locale);
    }

    /**
     * Filament registers navigation once per application boot, so this must be the
     * first panel request of the test (as in production, where each request boots fresh).
     */
    public function test_admin_ui_in_arabic_is_rtl_and_translated(): void
    {
        $user = $this->superAdmin();
        $user->forceFill(['admin_locale' => 'ar'])->save();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('الصفحة الرئيسية') // "Home Page" navigation group
            ->assertSee('صندوق الوارد')    // "Inbox"
            ->assertSee('الصلاحيات')       // "Access" (incl. Shield roles)
            ->assertDontSee('Filament Shield');
    }

    public function test_admin_ui_in_english_is_ltr(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Home Page')
            ->assertDontSee('Filament Shield');
    }

    public function test_settings_save_updates_values_and_cache(): void
    {
        $this->actingAs($this->superAdmin());
        Setting::get('tagline'); // warm cache

        Livewire::test(ManageSettings::class)
            ->set('data.tagline.en', 'Tiny pixels. Huge impact.')
            ->set('data.socials.instagram', 'https://instagram.com/pixellap')
            ->call('save')
            ->assertHasNoErrors();

        app()->setLocale('en');
        $this->assertSame('Tiny pixels. Huge impact.', Setting::get('tagline'));
        $this->assertSame('https://instagram.com/pixellap', Setting::get('socials')['instagram']);
    }

    public function test_every_edit_and_view_page_renders(): void
    {
        $this->actingAs($this->superAdmin());
        $message = $this->message();
        $editor = $this->userWithRole('content_editor');
        $activity = Activity::query()->latest('id')->firstOrFail(); // from message()/role changes

        $urls = [
            '/admin',
            '/admin/home-sections/1/edit',
            '/admin/stats/create',
            '/admin/stats/'.Stat::first()->id.'/edit',
            '/admin/process-steps/1/edit',
            '/admin/clients/create',
            '/admin/faqs/'.Faq::forHome()->first()->id.'/edit',
            '/admin/service-categories/create',
            '/admin/service-categories/'.ServiceCategory::first()->id.'/edit',
            '/admin/pages/'.Page::first()->id.'/edit',
            "/admin/contact-messages/{$message->id}",
            '/admin/users/create',
            "/admin/users/{$editor->id}/edit",
            "/admin/activity-log/{$activity->id}",
            '/admin/shield/roles/create',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }
}
