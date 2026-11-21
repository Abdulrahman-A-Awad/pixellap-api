<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\CheckProduction;
use App\Enums\HomeSectionKey;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\HomeSectionResource;
use App\Filament\Resources\HomeSectionResource\Pages\EditHomeSection;
use App\Filament\Resources\HomeSectionResource\Pages\ListHomeSections;
use App\Filament\Support\Fields;
use App\Filament\Widgets\HomeItems\HomeFaqsTable;
use App\Filament\Widgets\HomeItems\StatsTable;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\User;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin "Site settings" + "Home Page" (prompt: bilingual fields, single source of truth for the
 * brand, every field does something, video uploads without preview, link previews pre-launch).
 */
class AdminHomeAndSettingsTest extends TestCase
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

    private function superAdmin(): User
    {
        return User::where('email', CheckProduction::DEFAULT_ADMIN_EMAIL)->firstOrFail();
    }

    private function section(HomeSectionKey $key): HomeSection
    {
        return HomeSection::where('key', $key)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function apiSection(string $key, string $locale = 'en'): array
    {
        $sections = $this->getJson("/api/v1/home?locale={$locale}", ['X-Site-Key' => self::KEY])->assertOk()->json('data');

        return collect($sections)->firstWhere('key', $key);
    }

    /* ───────────────────── 1. Bilingual fields, no language switcher ───────────────────── */

    public function test_home_section_form_shows_english_and_arabic_side_by_side_without_a_switcher(): void
    {
        $this->actingAs($this->superAdmin());
        $services = $this->section(HomeSectionKey::Services);

        $page = Livewire::test(EditHomeSection::class, ['record' => $services->getRouteKey()])
            ->assertFormSet([
                'title.en' => $services->getTranslation('title', 'en'),
                'title.ar' => $services->getTranslation('title', 'ar'),
            ])
            ->assertFormFieldExists('title.en')
            ->assertFormFieldExists('title.ar')
            ->assertFormFieldExists('cta_label.ar');

        $this->assertSame([], (fn () => $this->getHeaderActions())->call($page->instance()), 'no LocaleSwitcher');

        $page->fillForm(['title' => ['en' => 'Six disciplines.', 'ar' => 'ستة تخصصات.']])
            ->call('save')
            ->assertHasNoFormErrors();

        $services->refresh();
        $this->assertSame('Six disciplines.', $services->getTranslation('title', 'en'));
        $this->assertSame('ستة تخصصات.', $services->getTranslation('title', 'ar'));
    }

    public function test_empty_arabic_is_a_warning_not_an_error_and_english_is_required_where_required(): void
    {
        $this->actingAs($this->superAdmin());
        $section = $this->section(HomeSectionKey::Faq);

        // Required field: English missing → error; Arabic missing → saved (warning hint only).
        Livewire::test(HomeFaqsTable::class, ['record' => $section])
            ->callTableAction('create', data: ['question' => ['en' => '', 'ar' => 'سؤال'], 'answer' => ['en' => '<p>A</p>']])
            ->assertHasTableActionErrors(['question.en']);

        Livewire::test(HomeFaqsTable::class, ['record' => $section])
            ->callTableAction('create', data: ['question' => ['en' => 'Only English?', 'ar' => ''], 'answer' => ['en' => '<p>Yes.</p>', 'ar' => '']])
            ->assertHasNoTableActionErrors();
        $this->assertTrue(Faq::query()->get()->contains(fn (Faq $f): bool => $f->getTranslation('question', 'en') === 'Only English?'));

        $this->assertTrue(Fields::arabicMissing('', 'Hello'));
        $this->assertTrue(Fields::arabicMissing('<p></p>', '<p>Hello</p>'));
        $this->assertFalse(Fields::arabicMissing('', ''), 'nothing to translate yet');
        $this->assertFalse(Fields::arabicMissing('مرحبا', 'Hello'));
    }

    public function test_item_tables_show_both_languages_and_flag_missing_arabic(): void
    {
        $this->actingAs($this->superAdmin());
        $stat = Stat::query()->ordered()->firstOrFail();
        $stat->setTranslation('label', 'ar', '')->saveQuietly();
        $other = Stat::query()->ordered()->skip(1)->firstOrFail();

        Livewire::test(StatsTable::class, ['record' => $this->section(HomeSectionKey::Stats)])
            ->assertTableColumnStateSet('label', $stat->getTranslation('label', 'en'), $stat)
            ->assertTableColumnStateSet('label_ar', 'AR missing', $stat)
            ->assertTableColumnStateSet('label_ar', $other->getTranslation('label', 'ar'), $other)
            ->assertTableActionDoesNotExist('localeSwitcher');
    }

    /* ─────────────────────────── 2. Settings: one source of truth ─────────────────────────── */

    public function test_site_name_is_translatable_and_localized_in_the_api(): void
    {
        $this->assertSame(Setting::TYPE_TRANSLATABLE, Setting::where('key', 'site_name')->value('type'));

        $this->getJson('/api/v1/site?locale=ar', ['X-Site-Key' => self::KEY])
            ->assertJsonPath('data.settings.site_name', 'بكسل لاب')
            ->assertJsonPath('data.settings.seo_default_title', 'بكسل لاب – وكالة إبداعية للإعلام والتسويق الرقمي في الإمارات')
            ->assertJsonPath('data.settings.socials.facebook', '')
            ->assertJsonPath('data.settings.socials.snapchat', '');
        $this->getJson('/api/v1/site?locale=en', ['X-Site-Key' => self::KEY])
            ->assertJsonPath('data.settings.site_name', 'Pixellap');
    }

    public function test_settings_page_edits_both_site_names_and_every_social_network(): void
    {
        $this->actingAs($this->superAdmin());

        $page = Livewire::test(ManageSettings::class)
            ->assertFormFieldExists('site_name.en')
            ->assertFormFieldExists('site_name.ar');
        foreach (array_keys(ManageSettings::NETWORKS) as $network) {
            $page->assertFormFieldExists("socials.{$network}");
        }

        // English name is required.
        Livewire::test(ManageSettings::class)->set('data.site_name.en', '')->call('save')
            ->assertHasErrors(['data.site_name.en']);

        Livewire::test(ManageSettings::class)
            ->set('data.site_name.ar', 'بكسل لاب ميديا')
            ->set('data.socials.facebook', 'https://facebook.com/pixellap')
            ->set('data.socials.snapchat', 'https://snapchat.com/add/pixellap')
            ->call('save')
            ->assertHasNoErrors();

        app()->setLocale('ar');
        $this->assertSame('بكسل لاب ميديا', Setting::get('site_name'));
        $this->assertSame('https://facebook.com/pixellap', Setting::get('socials')['facebook']);
        $this->assertSame('https://snapchat.com/add/pixellap', Setting::get('socials')['snapchat']);
    }

    public function test_hero_has_no_title_field_and_the_api_title_is_always_null(): void
    {
        $this->assertNotContains('title', HomeSectionKey::Hero->fields());
        $this->assertNotContains('eyebrow', HomeSectionKey::Hero->fields());

        $this->actingAs($this->superAdmin());
        Livewire::test(EditHomeSection::class, ['record' => $this->section(HomeSectionKey::Hero)->getRouteKey()])
            ->assertFormFieldIsHidden('title.en')
            ->assertSee('The headline is the site tagline')
            ->assertSee(ManageSettings::getUrl());

        $this->assertNull($this->apiSection('hero')['title']);
        $this->assertNull($this->apiSection('hero', 'ar')['title']);
    }

    /* ─────────────────────────── 3. Every field does something ─────────────────────────── */

    public function test_hero_second_button_is_a_normal_link_with_the_explore_default(): void
    {
        $hero = $this->apiSection('hero');
        $this->assertSame(['label' => 'Explore our services', 'url' => '/services'], $hero['cta2']);
        $this->assertSame(['label' => 'استكشف خدماتنا', 'url' => '/services'], $this->apiSection('hero', 'ar')['cta2']);
    }

    public function test_services_link_label_comes_from_the_cms(): void
    {
        $this->section(HomeSectionKey::Services)
            ->update(['cta_label' => ['en' => 'See everything we do', 'ar' => 'شاهد كل خدماتنا'], 'cta_url' => '/services']);

        $this->assertSame('See everything we do', $this->apiSection('services')['cta']['label']);
        $this->assertSame('شاهد كل خدماتنا', $this->apiSection('services', 'ar')['cta']['label']);
    }

    public function test_sections_without_visible_items_are_flagged_in_the_form_and_the_list(): void
    {
        $this->actingAs($this->superAdmin());
        Client::query()->delete();
        $clients = $this->section(HomeSectionKey::Clients);

        $this->assertTrue(HomeSectionResource::isHiddenForNoItems($clients));
        $this->assertFalse(HomeSectionResource::isHiddenForNoItems($this->section(HomeSectionKey::Process)));
        $this->assertFalse(HomeSectionResource::isHiddenForNoItems($this->section(HomeSectionKey::About)), 'About needs no items');

        // An invisible item does not count.
        Client::create(['name' => 'Hidden Co', 'is_visible' => false]);
        $this->assertTrue(HomeSectionResource::isHiddenForNoItems($clients));

        Livewire::test(ListHomeSections::class)
            ->assertTableColumnStateSet('items', 'Hidden: no items', $clients);

        Livewire::test(EditHomeSection::class, ['record' => $clients->getRouteKey()])
            ->assertSee('This section stays hidden on the website until you add at least one client logo.');
    }

    public function test_every_section_form_explains_where_it_appears(): void
    {
        $this->actingAs($this->superAdmin());

        foreach (HomeSectionKey::cases() as $key) {
            $this->assertNotSame('', $key->where());
            Livewire::test(EditHomeSection::class, ['record' => $this->section($key)->getRouteKey()])
                ->assertSee('Where this appears')
                ->assertSee(e($key->where()), false);
        }

        $this->assertSame('Shown as a strip under “About”. Turn off to hide the numbers.', HomeSectionKey::Stats->description());
    }

    /* ─────────────────────────── 4. Hero videos: no preview ─────────────────────────── */

    public function test_hero_video_uploads_do_not_download_a_preview(): void
    {
        $this->actingAs($this->superAdmin());
        $page = Livewire::test(EditHomeSection::class, ['record' => $this->section(HomeSectionKey::Hero)->getRouteKey()]);

        $uploads = collect($page->instance()->form->getFlatFields(withHidden: true))
            ->filter(fn ($field): bool => $field instanceof SpatieMediaLibraryFileUpload);

        $videos = $uploads->filter(fn (SpatieMediaLibraryFileUpload $f): bool => str_starts_with((string) $f->getCollection(), 'video_'));
        $posters = $uploads->filter(fn (SpatieMediaLibraryFileUpload $f): bool => str_starts_with((string) $f->getCollection(), 'poster_'));

        $this->assertCount(4, $videos);
        foreach ($videos as $video) {
            $this->assertFalse($video->isPreviewable(), $video->getCollection().' must not preview');
            $this->assertTrue($video->isOpenable(), $video->getCollection().' offers "Open"');
        }
        $this->assertCount(2, $posters);
        foreach ($posters as $poster) {
            $this->assertTrue($poster->isPreviewable(), 'posters keep a small preview');
        }
    }

    /* ─────────────────────────── 2b. One-off brand migration ─────────────────────────── */

    public function test_brand_migration_replaces_the_arabic_word_only_and_fixes_the_reel_button(): void
    {
        // Simulate an install from before the change.
        DB::table('settings')->where('key', 'site_name')->update(['type' => 'text', 'value' => json_encode('Pixellap')]);
        DB::table('settings')->where('key', 'socials')->update(['value' => json_encode(['instagram' => 'https://instagram.com/x'])]);
        $hero = $this->section(HomeSectionKey::Hero);
        DB::table('home_sections')->where('id', $hero->id)->update([
            'cta2_url' => '#reel',
            'cta2_label' => json_encode(['en' => 'Watch the reel', 'ar' => 'شاهد العرض التعريفي']),
        ]);
        $faq = Faq::query()->firstOrFail();
        DB::table('faqs')->where('id', $faq->id)->update(['question' => json_encode([
            'en' => 'Why Pixellap?',
            'ar' => 'لماذا Pixellap؟ راسلنا على hello@pixellap.ae أو Pixellap.',
        ])]);

        $migration = require database_path('migrations/2026_11_22_100000_localize_brand_name_and_home_defaults.php');
        $migration->up();
        Cache::flush();

        $faq->refresh();
        $this->assertSame('Why Pixellap?', $faq->getTranslation('question', 'en'), 'English untouched');
        $this->assertSame('لماذا بكسل لاب؟ راسلنا على hello@pixellap.ae أو بكسل لاب.', $faq->getTranslation('question', 'ar'));

        $this->assertSame(['en' => 'Pixellap', 'ar' => 'بكسل لاب'], Setting::where('key', 'site_name')->value('value'));
        $this->assertSame(Setting::TYPE_TRANSLATABLE, Setting::where('key', 'site_name')->value('type'));
        $this->assertSame(
            ['facebook' => '', 'instagram' => 'https://instagram.com/x', 'snapchat' => ''],
            Setting::where('key', 'socials')->value('value'),
        );

        $hero->refresh();
        $this->assertSame('/services', $hero->cta2_url);
        $this->assertSame('Explore our services', $hero->getTranslation('cta2_label', 'en'));
        $this->assertSame('استكشف خدماتنا', $hero->getTranslation('cta2_label', 'ar'));

        // Idempotent: a second run changes nothing.
        $before = DB::table('faqs')->where('id', $faq->id)->value('question');
        $migration->up();
        $this->assertSame($before, DB::table('faqs')->where('id', $faq->id)->value('question'));
    }

    /* ─────────────────────────── 5. Link previews before launch ─────────────────────────── */

    public function test_prelaunch_auth_lets_only_link_preview_bots_through_and_the_runbook_says_so(): void
    {
        $conf = (string) file_get_contents(base_path('deploy/nginx/prelaunch/web-auth.conf'));
        $bots = ['facebookexternalhit', 'Facebot', 'WhatsApp', 'Twitterbot', 'LinkedInBot', 'Slackbot', 'TelegramBot', 'Discordbot'];

        preg_match('/if \(\$http_user_agent ~\* "\(([^)]+)\)"\)/', $conf, $match);
        $this->assertSame($bots, explode('|', $match[1] ?? ''), 'exactly the allowlisted bots');
        $this->assertStringContainsString('set $pixellap_preview_auth off;', $conf);
        $this->assertStringContainsString('auth_basic $pixellap_preview_auth;', $conf);
        $this->assertStringContainsString('auth_basic_user_file /etc/nginx/pixellap-prelaunch/htpasswd;', $conf);

        // noindex stays on everything.
        $this->assertStringContainsString('X-Robots-Tag "noindex, nofollow" always', (string) file_get_contents(base_path('deploy/nginx/prelaunch/noindex.conf')));

        $runbook = (string) file_get_contents(base_path('deploy/README.md'));
        $this->assertStringContainsString('Link-preview bots skip the login', $runbook);
        $this->assertStringContainsString('the link-preview bot allowlist', $runbook, 'removed at go-live');
    }
}
