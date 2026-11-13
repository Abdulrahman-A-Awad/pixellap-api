<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HomeSectionKey;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\ProcessStep;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\UiString;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSeederTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<class-string, int> */
    private function counts(): array
    {
        return [
            HomeSection::class => HomeSection::count(),
            Stat::class => Stat::count(),
            ProcessStep::class => ProcessStep::count(),
            ServiceCategory::class => ServiceCategory::count(),
            Service::class => Service::count(),
            Faq::class => Faq::count(),
            Page::class => Page::count(),
            PageBlock::class => PageBlock::count(),
            UiString::class => UiString::count(),
            Setting::class => Setting::count(),
        ];
    }

    public function test_seeders_populate_expected_content(): void
    {
        $this->seed();

        $this->assertSame(count(HomeSectionKey::cases()), HomeSection::count());
        $this->assertSame(4, Stat::count());
        $this->assertSame(4, ProcessStep::count());
        $this->assertSame(6, ServiceCategory::count());
        $this->assertSame(43, Service::count());
        $this->assertSame(6, Faq::query()->forHome()->count());
        $this->assertSame(6 + 12, Faq::count());
        $this->assertSame(3, Page::count());
        $this->assertSame(4, PageBlock::count());
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed();
        $before = $this->counts();

        $this->seed();

        $this->assertSame($before, $this->counts());
    }

    public function test_every_translatable_field_has_both_locales(): void
    {
        $this->seed();

        $models = [HomeSection::class, Stat::class, ProcessStep::class, ServiceCategory::class,
            Service::class, Faq::class, Page::class, PageBlock::class, UiString::class];

        foreach ($models as $class) {
            foreach ($class::all() as $model) {
                foreach ($model->getTranslatableAttributes() as $attribute) {
                    if ($model->getRawOriginal($attribute) === null) {
                        continue; // optional field left empty
                    }

                    foreach (['en', 'ar'] as $locale) {
                        $this->assertNotEmpty(
                            $model->getTranslation($attribute, $locale, false),
                            "{$class}#{$model->id} {$attribute} missing [{$locale}]",
                        );
                    }
                }
            }
        }
    }

    public function test_services_are_ordered_within_their_category(): void
    {
        $this->seed();

        $category = ServiceCategory::where('slug', 'media-content-production')->firstOrFail();

        $this->assertSame(range(1, 9), $category->services()->ordered()->pluck('sort')->all());
        $this->assertSame('Commercial Video Production', $category->services()->ordered()->first()->getTranslation('title', 'en'));
    }

    public function test_translatable_setting_resolves_current_locale(): void
    {
        $this->seed();

        app()->setLocale('ar');
        $this->assertSame('بيكسلات صغيرة. أثر كبير.', Setting::get('tagline'));

        app()->setLocale('en');
        $this->assertSame('Small Pixels. BIG IMPACT.', Setting::get('tagline'));
    }
}
