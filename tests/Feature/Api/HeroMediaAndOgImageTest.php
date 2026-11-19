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

    /** @return array<string, mixed> */
    private function hero(string $locale = 'en'): array
    {
        $sections = $this->getJson("/api/v1/home?locale={$locale}", ['X-Site-Key' => self::KEY])->assertOk()->json('data');

        return collect($sections)->firstWhere('key', 'hero');
    }

    public function test_hero_media_has_both_orientations_with_null_urls_until_uploaded(): void
    {
        $hero = $this->hero();

        $this->assertSame(
            ['wide' => ['webm' => null, 'mp4' => null, 'poster' => null], 'vertical' => ['webm' => null, 'mp4' => null, 'poster' => null]],
            $hero['media'],
        );
        $this->assertNull($hero['image']);
        $this->assertArrayNotHasKey('video', $hero);
        $this->assertArrayNotHasKey('media', collect($this->getJson('/api/v1/home?locale=en', ['X-Site-Key' => self::KEY])->json('data'))->firstWhere('key', 'about'));
    }

    public function test_attach_hero_media_command_fills_every_variant_with_absolute_urls(): void
    {
        $dir = HeroMediaFiles::folder();

        $this->artisan('pixellap:attach-hero-media', ['dir' => $dir])->assertSuccessful();

        foreach (['en', 'ar'] as $locale) {
            $media = $this->hero($locale)['media'];
            foreach (['wide', 'vertical'] as $variant) {
                $this->assertStringStartsWith('http', $media[$variant]['webm']);
                $this->assertStringEndsWith("hero-{$variant}.webm", $media[$variant]['webm']);
                $this->assertStringEndsWith("hero-{$variant}.mp4", $media[$variant]['mp4']);
                $this->assertStringEndsWith("hero-{$variant}-poster.webp", $media[$variant]['poster']);
            }
        }

        // Source files are copied, never moved; running again replaces (singleFile).
        $this->assertFileExists($dir.'/hero-wide.webm');
        $this->artisan('pixellap:attach-hero-media', ['dir' => $dir])->assertSuccessful();
        $hero = HomeSection::where('key', HomeSectionKey::Hero)->firstOrFail();
        $this->assertCount(6, $hero->getMedia('*'));
        foreach (HomeSection::heroMediaCollections() as $collection) {
            $this->assertCount(1, $hero->getMedia($collection), $collection);
        }
    }

    public function test_attach_hero_media_skips_files_over_the_admin_limit(): void
    {
        $dir = HeroMediaFiles::folder();
        file_put_contents($dir.'/hero-wide-poster.webp', str_repeat('x', 1100 * 1024));

        $this->artisan('pixellap:attach-hero-media', ['dir' => $dir])->assertSuccessful();

        $this->assertNull($this->hero()['media']['wide']['poster']);
        $this->assertNotNull($this->hero()['media']['vertical']['poster']);
    }
}

/** Minimal files that finfo recognises as webm / mp4 / webp. */
final class HeroMediaFiles
{
    public static function folder(): string
    {
        $dir = sys_get_temp_dir().'/hero-media-'.bin2hex(random_bytes(4));
        mkdir($dir);
        // EBML header with DocType "webm".
        $webm = "\x1A\x45\xDF\xA3\x9F\x42\x86\x81\x01\x42\xF7\x81\x01\x42\xF2\x81\x04\x42\xF3\x81\x08\x42\x82\x84webm\x42\x87\x81\x04\x42\x85\x81\x02".str_repeat("\x00", 64);
        $mp4 = "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 64);
        $poster = imagecreatetruecolor(16, 9);
        ob_start();
        imagewebp($poster);
        $webp = (string) ob_get_clean();

        foreach (['wide', 'vertical'] as $variant) {
            file_put_contents("{$dir}/hero-{$variant}.webm", $webm);
            file_put_contents("{$dir}/hero-{$variant}.mp4", $mp4);
            file_put_contents("{$dir}/hero-{$variant}-poster.webp", $webp);
        }

        return $dir;
    }
}
