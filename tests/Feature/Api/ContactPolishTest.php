<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Filament\Pages\ManageSettings;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\User;
use App\Services\RevalidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Structured opening hours on /site + the FAQ readability data migration. */
class ContactPolishTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-site-key';

    /** An original seeded answer (home FAQ 4, English) before the readability rewrite. */
    private const OLD_EN = 'Yes. Our team creates content, copy and campaigns natively in Arabic and English, so your brand speaks naturally to every audience in the region.';

    protected function setUp(): void
    {
        parent::setUp();

        config(['pixellap.api_site_key' => self::KEY]);
        $this->seed();
    }

    /** @return array<string, mixed>|null */
    private function hours(): ?array
    {
        return $this->getJson('/api/v1/site?locale=en', ['X-Site-Key' => self::KEY])
            ->assertOk()
            ->json('data.settings.business_hours');
    }

    public function test_site_returns_default_business_hours(): void
    {
        $this->assertSame(
            ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'open' => '09:00', 'close' => '18:00', 'timezone' => 'Asia/Dubai'],
            $this->hours(),
        );
    }

    public function test_business_hours_are_normalised_and_invalid_values_become_null(): void
    {
        $setting = Setting::query()->where('key', 'business_hours')->firstOrFail();

        $setting->update(['value' => ['days' => ['sun', 'mon', 'xyz'], 'open' => '08:30:00', 'close' => '17:00', 'timezone' => 'Nowhere/City']]);
        $this->assertSame(['days' => ['mon', 'sun'], 'open' => '08:30', 'close' => '17:00', 'timezone' => 'Asia/Dubai'], $this->hours());

        $setting->update(['value' => ['days' => ['mon'], 'open' => '18:00', 'close' => '09:00']]);
        app(RevalidationService::class)->flush(); // outside a request: flush the API cache ourselves
        $this->assertNull($this->hours());
    }

    public function test_admin_saves_business_hours_from_the_settings_page(): void
    {
        $admin = User::query()->where('email', 'admin@pixellap.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(ManageSettings::class)
            ->set('data.business_hours.days', ['sat', 'sun'])
            ->set('data.business_hours.open', '10:00')
            ->set('data.business_hours.close', '16:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['days' => ['sat', 'sun'], 'open' => '10:00', 'close' => '16:00', 'timezone' => 'Asia/Dubai'], $this->hours());

        Livewire::test(ManageSettings::class)
            ->set('data.business_hours.open', '16:00')
            ->set('data.business_hours.close', '10:00')
            ->call('save')
            ->assertHasErrors(['data.business_hours.close']);
    }

    public function test_faq_migration_only_replaces_untouched_seed_answers(): void
    {
        $faq = Faq::query()->whereNull('service_category_id')->where('sort', 4)->firstOrFail();
        $faq->setTranslation('answer', 'en', self::OLD_EN);
        $faq->setTranslation('answer', 'ar', '<p>An editor wrote this.</p>');
        $faq->saveQuietly();

        $migration = require database_path('migrations/2026_11_24_100000_faq_answers_readable.php');
        ob_start();
        $migration->up();
        ob_end_clean();

        $faq->refresh();
        $this->assertSame(
            '<p>'.self::OLD_EN.'</p>',
            $faq->getTranslation('answer', 'en'),
        );
        $this->assertSame('<p>An editor wrote this.</p>', $faq->getTranslation('answer', 'ar'));

        // Re-running changes nothing (idempotent); down() restores the original seed text.
        ob_start();
        $migration->up();
        $migration->down();
        ob_end_clean();
        $this->assertSame(self::OLD_EN, $faq->refresh()->getTranslation('answer', 'en'));
    }
}
