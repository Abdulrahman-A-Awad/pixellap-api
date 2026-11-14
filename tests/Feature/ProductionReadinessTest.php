<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\SecurityHeaders;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\CacheTags;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function asProduction(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
    }

    private function makeProductionReady(): void
    {
        config([
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'pixellap.api_site_key' => str_repeat('k', 64),
            'pixellap.revalidate_secret' => str_repeat('s', 64),
            'queue.default' => 'database',
        ]);
        User::where('email', 'admin@pixellap.test')->delete();
        Setting::query()->get()->each(function (Setting $setting): void {
            $json = (string) json_encode($setting->value, JSON_UNESCAPED_UNICODE);
            if (str_contains($json, 'TODO:')) {
                $setting->update(['value' => json_decode(str_replace('TODO: ', '', $json), true)]);
            }
        });
    }

    public function test_check_production_lists_every_problem_on_seeded_local_data(): void
    {
        $this->seed();
        config(['app.debug' => true, 'pixellap.api_site_key' => 'short', 'pixellap.revalidate_secret' => '']);

        $this->artisan('pixellap:check-production')
            ->expectsOutputToContain('APP_DEBUG is true')
            ->expectsOutputToContain('API_SITE_KEY is shorter than 32 characters')
            ->expectsOutputToContain('REVALIDATE_SECRET is empty')
            ->expectsOutputToContain('The default admin admin@pixellap.test still exists')
            ->expectsOutputToContain('still has the password "password"')
            ->expectsOutputToContain('Settings still contain "TODO:" placeholders: email, phone, whatsapp, address, vat_number')
            ->assertFailed();
    }

    public function test_check_production_passes_once_everything_is_fixed(): void
    {
        $this->seed();
        $this->makeProductionReady();
        $this->artisan('pixellap:check-production')
            ->expectsOutputToContain('Production checks passed.')
            ->assertSuccessful();
    }

    public function test_check_production_flags_a_missing_queue_table(): void
    {
        $this->seed();
        $this->makeProductionReady();
        config(['queue.connections.database.table' => 'missing_jobs']);

        $this->artisan('pixellap:check-production')
            ->expectsOutputToContain('Queue table "missing_jobs" is missing')
            ->assertFailed();
    }

    public function test_role_seeder_creates_no_default_admin_in_production(): void
    {
        $this->asProduction();

        // Called directly: db:seed itself asks for confirmation in production.
        $this->app->call([new RoleSeeder, 'run']);

        $this->assertDatabaseCount('roles', count(RoleSeeder::ROLES));
        $this->assertDatabaseMissing('users', ['email' => 'admin@pixellap.test']);
    }

    public function test_role_seeder_keeps_the_local_admin_outside_production(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertTrue(User::where('email', 'admin@pixellap.test')->firstOrFail()->isSuperAdmin());
    }

    public function test_create_super_admin_creates_an_active_super_admin(): void
    {
        $this->artisan('pixellap:create-super-admin')
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Email', 'Owner@Example.com')
            ->expectsQuestion('Password', 'a-long-secret-phrase')
            ->expectsQuestion('Confirm password', 'a-long-secret-phrase')
            ->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole(AppServiceProvider::SUPER_ADMIN_ROLE));
        $this->assertTrue($user->is_active);
    }

    public function test_revalidate_all_flushes_and_posts_every_tag(): void
    {
        $this->seed();
        config(['pixellap.frontend_revalidate_url' => 'https://pixellap.test/api/revalidate', 'pixellap.revalidate_secret' => 'secret']);
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->artisan('pixellap:revalidate-all')->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://pixellap.test/api/revalidate'
            && $request->hasHeader('X-Revalidate-Secret', 'secret')
            && $request['tags'] === CacheTags::all());
    }

    public function test_revalidate_all_fails_when_the_website_is_down(): void
    {
        config(['pixellap.frontend_revalidate_url' => 'https://pixellap.test/api/revalidate']);
        Http::fake(['*' => Http::response('down', 502)]);

        $this->artisan('pixellap:revalidate-all')->assertFailed();
    }

    public function test_web_and_admin_responses_carry_security_headers(): void
    {
        foreach (['/', '/admin/login'] as $uri) {
            $response = $this->get($uri);
            foreach (SecurityHeaders::HEADERS as $name => $value) {
                $response->assertHeader($name, $value);
            }
        }
    }

    public function test_admin_requires_https_in_production(): void
    {
        $this->asProduction();

        $this->get('http://localhost/admin/login')->assertRedirect('https://localhost/admin/login');
        $this->get('https://localhost/admin/login')->assertOk();
    }

    public function test_login_is_limited_per_email_and_ip(): void
    {
        $this->seed(RoleSeeder::class);
        $key = Login::throttleKey('admin@pixellap.test', '127.0.0.1');
        for ($i = 0; $i < Login::MAX_ATTEMPTS; $i++) {
            RateLimiter::hit($key, Login::DECAY_SECONDS);
        }

        // Correct credentials, but this email+IP pair is locked for a minute.
        Livewire::test(Login::class)
            ->set('data.email', 'admin@pixellap.test')
            ->set('data.password', 'password')
            ->call('authenticate');
        $this->assertGuest();

        // Once the window has passed, the same credentials work.
        RateLimiter::clear($key);
        Livewire::test(Login::class)
            ->set('data.email', 'admin@pixellap.test')
            ->set('data.password', 'password')
            ->call('authenticate');
        $this->assertAuthenticated();
    }
}
