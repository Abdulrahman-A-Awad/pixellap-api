<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_user_without_a_role_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_inactive_user_with_a_role_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('admin');

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_user_with_a_role_can_access_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('content_editor');

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_seeded_super_admin_can_access_panel_and_bypasses_gates(): void
    {
        $admin = User::where('email', 'admin@pixellap.test')->firstOrFail();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->assertTrue(Gate::forUser($admin)->allows('any-undefined-ability'));
    }

    public function test_non_super_admin_does_not_bypass_gates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertFalse(Gate::forUser($user)->allows('any-undefined-ability'));
    }

    public function test_login_records_last_login_at(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        event(new Login('web', $user, false));

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
