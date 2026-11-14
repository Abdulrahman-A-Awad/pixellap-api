<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\HomeSectionKey;
use App\Filament\Pages\ManageSettings;
use App\Models\HomeSection;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HeroMediaAndOgImageTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-site-key';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['pixellap.api_site_key' => self::KEY]);
        $this->seed();
    }

    /** @return array<string, mixed> */
    private function site(): array
    {
        return $this->getJson('/api/v1/site?locale=en', ['X-Site-Key' => self::KEY])->assertOk()->json('data');
    }

    /** Smallest byte sequence that `finfo` recognises as video/mp4 (an `ftyp` box). */
    private function fakeMp4(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'mp4');
        file_put_contents($path, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 64));

        return new UploadedFile($path, 'loop.mp4', 'video/mp4', null, true);
    }

    public function test_og_image_is_null_until_uploaded(): void
    {
        $this->assertArrayHasKey('og_image', $this->site()['settings']);
        $this->assertNull($this->site()['settings']['og_image']);
    }

    public function test_og_image_is_returned_as_an_absolute_url(): void
    {
        Setting::where('key', 'og_image')->firstOrFail()->update(['value' => 'settings/share.jpg']);
        $this->app->terminate(); // flush the "site" cache tag

        $url = $this->site()['settings']['og_image'];

        $this->assertStringStartsWith('http', $url);
        $this->assertStringEndsWith('/storage/settings/share.jpg', $url);
    }

    public function test_og_image_is_uploaded_from_the_settings_page_and_replaces_the_old_file(): void
    {
        $this->actingAs(User::where('email', 'admin@pixellap.test')->firstOrFail());

        Livewire::test(ManageSettings::class)
            ->set('data.og_image', [UploadedFile::fake()->image('share.jpg', 1200, 630)])
            ->call('save')
            ->assertHasNoErrors();

        $first = Setting::where('key', 'og_image')->value('value');
        $this->assertIsString($first);
        $this->assertStringStartsWith(Setting::IMAGE_DIRECTORY.'/', $first);
        Storage::disk('public')->assertExists($first);

        Livewire::test(ManageSettings::class)
            ->set('data.og_image', [UploadedFile::fake()->image('share-2.png', 1200, 630)])
            ->call('save')
            ->assertHasNoErrors();

        $second = Setting::where('key', 'og_image')->value('value');
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);
    }

    public function test_home_hero_returns_poster_and_loop_video_with_mime_and_absolute_urls(): void
    {
        $hero = HomeSection::where('key', HomeSectionKey::Hero)->firstOrFail();
        $hero->addMedia(UploadedFile::fake()->image('poster.jpg', 1600, 900))->toMediaCollection('image');
        $hero->addMedia($this->fakeMp4())->toMediaCollection('video');
        $this->app->terminate();

        foreach (['en', 'ar'] as $locale) {
            $sections = $this->getJson("/api/v1/home?locale={$locale}", ['X-Site-Key' => self::KEY])->assertOk()->json('data');
            $heroJson = collect($sections)->firstWhere('key', 'hero');

            $this->assertSame('image/jpeg', $heroJson['image']['mime']);
            $this->assertStringStartsWith('http', $heroJson['image']['url']);
            $this->assertSame(1600, $heroJson['image']['width']);
            $this->assertNotNull($heroJson['image']['conversions']);

            $this->assertSame('video/mp4', $heroJson['video']['mime']);
            $this->assertStringStartsWith('http', $heroJson['video']['url']);
            $this->assertNull($heroJson['video']['conversions']);
        }
    }
}
