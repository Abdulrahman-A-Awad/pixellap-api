<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\CheckProduction;
use App\Enums\PageBlockType;
use App\Enums\PageKey;
use App\Filament\Resources\ContactMessageResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\RelationManagers\BlocksRelationManager;
use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Resources\ServiceCategoryResource\Pages\CreateServiceCategory;
use App\Filament\Resources\ServiceCategoryResource\Pages\EditServiceCategory;
use App\Filament\Resources\ServiceCategoryResource\RelationManagers\FaqsRelationManager;
use App\Filament\Resources\ServiceCategoryResource\RelationManagers\ServicesRelationManager;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\ArabicSearch;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Services/Pages: EN | AR side by side (no language switcher, no data loss) + Arabic-aware global search. */
class AdminTranslationsAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::where('email', CheckProduction::DEFAULT_ADMIN_EMAIL)->firstOrFail());
    }

    private function category(string $slug = 'brand-identity-design'): ServiceCategory
    {
        return ServiceCategory::where('slug', $slug)->firstOrFail();
    }

    /* ─────────────────────────── No language switcher ─────────────────────────── */

    public function test_services_and_pages_have_no_language_switcher(): void
    {
        $category = $this->category();
        $about = Page::where('key', PageKey::About)->firstOrFail();

        Livewire::test(EditServiceCategory::class, ['record' => $category->getKey()])->assertActionDoesNotExist('localeSwitcher');
        Livewire::test(CreateServiceCategory::class)->assertActionDoesNotExist('localeSwitcher');
        Livewire::test(EditPage::class, ['record' => $about->getKey()])->assertActionDoesNotExist('localeSwitcher');

        Livewire::test(ServicesRelationManager::class, ['ownerRecord' => $category, 'pageClass' => EditServiceCategory::class])
            ->assertTableActionDoesNotExist('localeSwitcher');
        Livewire::test(FaqsRelationManager::class, ['ownerRecord' => $category, 'pageClass' => EditServiceCategory::class])
            ->assertTableActionDoesNotExist('localeSwitcher');
        Livewire::test(BlocksRelationManager::class, ['ownerRecord' => $about, 'pageClass' => EditPage::class])
            ->assertTableActionDoesNotExist('localeSwitcher');

        $this->get(ServiceCategoryResource::getUrl('edit', ['record' => $category]))->assertOk()
            ->assertSee('Service name — English')->assertSee('Service name — العربية');
    }

    /* ─────────────────────────── Saving keeps both languages ─────────────────────────── */

    public function test_saving_a_category_keeps_both_languages(): void
    {
        $category = $this->category();
        $arExcerpt = $category->getTranslation('excerpt', 'ar');

        Livewire::test(EditServiceCategory::class, ['record' => $category->getKey()])
            ->fillForm(['title.en' => 'Brand Identity', 'seo_title.ar' => 'الهوية | بكسل لاب'])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('Brand Identity', $category->getTranslation('title', 'en'));
        $this->assertSame('بناء الهوية البصرية والتصميم الإبداعي', $category->getTranslation('title', 'ar'), 'untouched Arabic kept');
        $this->assertSame($arExcerpt, $category->getTranslation('excerpt', 'ar'));
        $this->assertSame('الهوية | بكسل لاب', $category->getTranslation('seo_title', 'ar'));
    }

    public function test_empty_arabic_is_a_warning_not_an_error(): void
    {
        Livewire::test(CreateServiceCategory::class)
            ->fillForm([
                'title.en' => 'Event Management',
                'excerpt.en' => 'Events from idea to applause.',
                'description.en' => '<p>Full event production.</p>',
            ])
            ->assertSet('data.slug', 'event-management') // generated from the English title
            ->call('create')
            ->assertHasNoFormErrors();

        $created = ServiceCategory::where('slug', 'event-management')->firstOrFail();
        $this->assertSame('Event Management', $created->getTranslation('title', 'en'));
        $this->assertSame('', $created->getTranslation('title', 'ar', false));
    }

    public function test_saving_a_service_keeps_both_languages_and_feature_lists(): void
    {
        $category = $this->category();
        $service = $category->services()->firstOrFail();
        $arTitle = $service->getTranslation('title', 'ar');

        Livewire::test(ServicesRelationManager::class, ['ownerRecord' => $category, 'pageClass' => EditServiceCategory::class])
            ->mountTableAction('edit', $service)
            ->assertTableActionDataSet(['title.ar' => $arTitle])
            ->setTableActionData(['title.en' => 'Brand Strategy'])
            // Lists are replaced as a whole, like the tags input does (setTableActionData merges arrays).
            ->set('mountedTableActionsData.0.features.en', ['Workshops', 'Positioning'])
            ->set('mountedTableActionsData.0.features.ar', ['ورش عمل', 'تحديد الموقع'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $service->refresh();
        $this->assertSame('Brand Strategy', $service->getTranslation('title', 'en'));
        $this->assertSame($arTitle, $service->getTranslation('title', 'ar'));
        $this->assertSame(['Workshops', 'Positioning'], $service->getTranslation('features', 'en'));
        $this->assertSame(['ورش عمل', 'تحديد الموقع'], $service->getTranslation('features', 'ar'));
    }

    public function test_saving_a_category_faq_keeps_both_languages(): void
    {
        $category = $this->category();
        $faq = Faq::query()->where('service_category_id', $category->id)->firstOrFail();
        $arAnswer = $faq->getTranslation('answer', 'ar');

        Livewire::test(FaqsRelationManager::class, ['ownerRecord' => $category, 'pageClass' => EditServiceCategory::class])
            ->mountTableAction('edit', $faq)
            ->setTableActionData(['question.ar' => 'كم يستغرق المشروع؟'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $faq->refresh();
        $this->assertSame('كم يستغرق المشروع؟', $faq->getTranslation('question', 'ar'));
        $this->assertSame($arAnswer, $faq->getTranslation('answer', 'ar'));
        $this->assertNotSame('', $faq->getTranslation('question', 'en'));
    }

    public function test_saving_a_page_and_a_block_keeps_both_languages_and_cards(): void
    {
        $about = Page::where('key', PageKey::About)->firstOrFail();
        $arTitle = $about->getTranslation('title', 'ar');

        Livewire::test(EditPage::class, ['record' => $about->getKey()])
            ->fillForm(['subtitle.en' => 'A creative agency from the UAE.'])
            ->call('save')
            ->assertHasNoFormErrors();
        $about->refresh();
        $this->assertSame('A creative agency from the UAE.', $about->getTranslation('subtitle', 'en'));
        $this->assertSame($arTitle, $about->getTranslation('title', 'ar'));

        $block = PageBlock::query()->where('page_id', $about->id)->where('type', PageBlockType::Values)->firstOrFail();
        $arBlockTitle = $block->getTranslation('title', 'ar');

        Livewire::test(BlocksRelationManager::class, ['ownerRecord' => $about, 'pageClass' => EditPage::class])
            ->mountTableAction('edit', $block)
            ->setTableActionData(['title.en' => 'What we value'])
            // Repeaters are replaced as a whole (setTableActionData merges arrays).
            ->set('mountedTableActionsData.0.items.en', ['a' => ['title' => 'Quality', 'text' => 'Every pixel counts.']])
            ->set('mountedTableActionsData.0.items.ar', ['b' => ['title' => 'الجودة', 'text' => 'كل بكسل مهم.']])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $block->refresh();
        $this->assertSame('What we value', $block->getTranslation('title', 'en'));
        $this->assertSame($arBlockTitle, $block->getTranslation('title', 'ar'));
        $this->assertSame([['title' => 'Quality', 'text' => 'Every pixel counts.']], $block->getTranslation('items', 'en'));
        $this->assertSame([['title' => 'الجودة', 'text' => 'كل بكسل مهم.']], $block->getTranslation('items', 'ar'));
    }

    /* ─────────────────────────── Arabic global search ─────────────────────────── */

    /** @return list<string> result titles */
    private static function titles(string $resource, string $search): array
    {
        return $resource::getGlobalSearchResults($search)
            ->map(fn (GlobalSearchResult $r): string => (string) $r->title)
            ->values()
            ->all();
    }

    public function test_normalize_strips_diacritics_and_unifies_alef(): void
    {
        $this->assertSame('الهوية', ArabicSearch::normalize('الهُوِيَّة'));
        $this->assertSame('انتاج', ArabicSearch::normalize('إنتاج'));
        $this->assertSame('اسلوب ابداعي', ArabicSearch::normalize('  أسلوب   آبداعي '));
        $this->assertSame('brand', ArabicSearch::normalize('BRAND'));
    }

    public function test_arabic_queries_find_service_categories(): void
    {
        // "الهوية" is in the Brand Identity category title (stored as \u-escaped JSON).
        $this->assertContains('Brand Identity & Design', self::titles(ServiceCategoryResource::class, 'الهوية'));
        // With diacritics, too.
        $this->assertContains('Brand Identity & Design', self::titles(ServiceCategoryResource::class, 'الهُوِيَّة'));
        // "بودكاست" is a service of the Media category ("إنتاج البودكاست").
        $this->assertContains('Media & Content Production', self::titles(ServiceCategoryResource::class, 'بودكاست'));
        // Alef without hamza matches "إنتاج".
        $this->assertContains('Media & Content Production', self::titles(ServiceCategoryResource::class, 'انتاج'));
        // English stays case-insensitive, and nonsense finds nothing.
        $this->assertContains('Brand Identity & Design', self::titles(ServiceCategoryResource::class, 'BRAND'));
        $this->assertSame([], self::titles(ServiceCategoryResource::class, 'زرافة'));
    }

    public function test_arabic_queries_find_pages_and_messages(): void
    {
        $this->assertContains('About Pixellap', self::titles(PageResource::class, 'بكسل'));

        ContactMessage::create(['name' => 'أحمد سعيد', 'email' => 'ahmed@example.com', 'message' => 'نريد عرض سعر لبودكاست أسبوعي', 'locale' => 'ar']);
        ContactMessage::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'message' => 'Website please', 'locale' => 'en']);

        $this->assertSame(['أحمد سعيد'], self::titles(ContactMessageResource::class, 'احمد'));
        $this->assertSame(['أحمد سعيد'], self::titles(ContactMessageResource::class, 'بودكاست'));
        $this->assertSame(['Jane Doe'], self::titles(ContactMessageResource::class, 'jane@'));
    }

    public function test_like_wildcards_in_the_query_are_literal(): void
    {
        $this->assertSame([], self::titles(ServiceCategoryResource::class, '%%'));
        $this->assertSame([], self::titles(ServiceCategoryResource::class, '_'));
    }
}
