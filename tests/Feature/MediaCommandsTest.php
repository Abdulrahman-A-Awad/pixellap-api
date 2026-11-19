<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ImportMedia;
use App\Enums\HomeSectionKey;
use App\Jobs\RevalidateFrontend;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Services\RevalidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCommandsTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();
        $this->seed();
        $this->dir = sys_get_temp_dir().'/import-media-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    private function png(string $name, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 97, 47, 226));
        imagepng($image, "{$this->dir}/{$name}");

        return $name;
    }

    /** @param list<array<string, string>> $entries */
    private function manifest(array $entries): string
    {
        $path = "{$this->dir}/manifest.json";
        file_put_contents($path, json_encode($entries));

        return $path;
    }

    public function test_import_media_resizes_to_webp_and_attaches_to_each_model(): void
    {
        $manifest = $this->manifest([
            ['model' => 'service_category', 'slug' => 'digital-marketing', 'collection' => 'cover', 'file' => $this->png('wide.png', 3000, 1500)],
            ['model' => 'page', 'key' => 'about', 'collection' => 'hero', 'file' => $this->png('tall.png', 800, 3200)],
            ['model' => 'page_block', 'key' => 'about', 'type' => 'image_text', 'collection' => 'image', 'file' => 'wide.png'],
            ['model' => 'home_section', 'key' => 'about', 'collection' => 'image', 'file' => $this->png('small.png', 1200, 800)],
            ['model' => 'service', 'slug' => 'media-content-production', 'title' => 'Podcast Production', 'collection' => 'image', 'file' => 'small.png'],
        ]);

        $this->artisan('pixellap:import-media', ['manifest' => $manifest])->assertSuccessful();

        $cover = ServiceCategory::where('slug', 'digital-marketing')->firstOrFail()->getFirstMedia('cover');
        $this->assertSame('image/webp', $cover?->mime_type);
        $this->assertSame([ImportMedia::MAX_EDGE, 1280], [$cover->getCustomProperty('width'), $cover->getCustomProperty('height')]);

        $hero = Page::where('key', 'about')->firstOrFail()->getFirstMedia('hero');
        $this->assertSame([640, ImportMedia::MAX_EDGE], [$hero?->getCustomProperty('width'), $hero?->getCustomProperty('height')]);

        // Never upscaled.
        $about = HomeSection::where('key', HomeSectionKey::About)->firstOrFail()->getFirstMedia('image');
        $this->assertSame([1200, 800], [$about?->getCustomProperty('width'), $about?->getCustomProperty('height')]);

        $this->assertNotNull(PageBlock::where('type', 'image_text')->firstOrFail()->getFirstMedia('image'));
        $this->assertNotNull(Service::where('title->en', 'Podcast Production')->firstOrFail()->getFirstMedia('image'));

        // Source files are left untouched.
        $this->assertFileExists("{$this->dir}/wide.png");
    }

    public function test_import_media_is_idempotent_and_replaces_on_change(): void
    {
        $manifest = $this->manifest([
            ['model' => 'service_category', 'slug' => 'digital-marketing', 'collection' => 'cover', 'file' => $this->png('cover.png', 1600, 900)],
        ]);
        $category = ServiceCategory::where('slug', 'digital-marketing')->firstOrFail();

        $this->artisan('pixellap:import-media', ['manifest' => $manifest])->assertSuccessful();
        $first = $category->refresh()->getFirstMedia('cover');

        $this->artisan('pixellap:import-media', ['manifest' => $manifest])
            ->expectsOutputToContain('unchanged')
            ->assertSuccessful();
        $this->assertSame($first?->id, $category->refresh()->getFirstMedia('cover')?->id);

        $this->png('cover.png', 1700, 900);
        $this->artisan('pixellap:import-media', ['manifest' => $manifest])->assertSuccessful();
        $media = $category->refresh()->getMedia('cover');
        $this->assertCount(1, $media);
        $this->assertNotSame($first?->id, $media->first()?->id);
    }

    public function test_import_media_reports_bad_entries_and_continues(): void
    {
        $manifest = $this->manifest([
            ['model' => 'service_category', 'slug' => 'nope', 'collection' => 'cover', 'file' => $this->png('a.png', 100, 100)],
            ['model' => 'service_category', 'slug' => 'digital-marketing', 'collection' => 'nope', 'file' => 'a.png'],
            ['model' => 'service_category', 'slug' => 'digital-marketing', 'collection' => 'cover', 'file' => 'missing.png'],
            ['model' => 'service_category', 'slug' => 'digital-marketing', 'collection' => 'cover', 'file' => 'a.png'],
        ]);

        $this->artisan('pixellap:import-media', ['manifest' => $manifest])->assertFailed();

        $this->assertNotNull(ServiceCategory::where('slug', 'digital-marketing')->firstOrFail()->getFirstMedia('cover'));
    }

    public function test_import_media_revalidates_once_at_the_end(): void
    {
        $manifest = $this->manifest([
            ['model' => 'service_category', 'slug' => 'digital-marketing', 'collection' => 'cover', 'file' => $this->png('a.png', 100, 100)],
            ['model' => 'page', 'key' => 'about', 'collection' => 'hero', 'file' => 'a.png'],
        ]);

        $this->artisan('pixellap:import-media', ['manifest' => $manifest])->assertSuccessful();

        $this->assertSame([], app(RevalidationService::class)->pending());
        Queue::assertPushed(RevalidateFrontend::class, 1);
    }

    public function test_replace_middle_dots_fixes_content_and_settings_and_is_idempotent(): void
    {
        $hero = HomeSection::where('key', HomeSectionKey::Hero)->firstOrFail();
        $hero->setTranslations('eyebrow', ['en' => 'A · B·C', 'ar' => 'أ · ب'])->save();
        Setting::where('key', 'tagline')->update(['value' => json_encode(['en' => 'X · Y', 'ar' => 'س · ص'])]);

        $this->artisan('pixellap:replace-middle-dots')->assertSuccessful();

        $hero->refresh();
        $this->assertSame('A | B | C', $hero->getTranslation('eyebrow', 'en'));
        $this->assertSame('أ | ب', $hero->getTranslation('eyebrow', 'ar'));
        $this->assertSame(['en' => 'X | Y', 'ar' => 'س | ص'], Setting::where('key', 'tagline')->value('value'));

        $this->artisan('pixellap:replace-middle-dots')->expectsOutputToContain('No "·" found.')->assertSuccessful();
    }
}
