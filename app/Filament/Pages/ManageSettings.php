<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Models\Setting;
use App\Support\MimeTypes;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Site settings editor. Builds its form from the `settings` table, grouped into tabs.
 * Protected by the Shield permission "page_ManageSettings".
 *
 * @property Form $form
 */
class ManageSettings extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'settings';

    protected static string $view = 'filament.pages.manage-settings';

    /** Tab order and labels. */
    private const GROUPS = [
        'general' => 'General',
        'contact' => 'Contact',
        'social' => 'Social',
        'seo' => 'SEO',
    ];

    /** Field-level hints for known keys. */
    private const URL_KEYS = ['showreel_url', 'map_embed_url'];

    private const MULTILINE_KEYS = ['address', 'seo_default_description'];

    /** Plain-language field labels (English keys, translated via lang/ar.json). */
    public const LABELS = [
        'site_name' => 'Website name',
        'tagline' => 'Tagline',
        'showreel_url' => 'Showreel link',
        'email' => 'Contact email',
        'phone' => 'Phone number',
        'whatsapp' => 'WhatsApp number',
        'address' => 'Address',
        'map_embed_url' => 'Map link (embed)',
        'working_hours' => 'Working hours',
        'vat_number' => 'VAT number',
        'response_time' => 'Reply time promise',
        'socials' => 'Social media links',
        'seo_default_title' => 'Default page title (Google)',
        'seo_default_description' => 'Default page description (Google)',
        'og_image' => 'Social sharing image',
    ];

    private const HELP = [
        'site_name' => 'Shown in the browser tab, the footer and search results.',
        'tagline' => 'A short slogan shown in the footer (and as the home headline if it is empty).',
        'showreel_url' => 'YouTube, Vimeo or a direct .mp4 link. Empty = the “Watch the reel” button is hidden.',
        'email' => 'Shown on the Contact page and footer. New contact-form messages are sent here.',
        'phone' => 'Shown on the Contact page and footer, e.g. +971 50 000 0000.',
        'whatsapp' => 'International format, digits only, e.g. 971501234567. Powers the WhatsApp button.',
        'address' => 'Shown on the Contact page and footer.',
        'map_embed_url' => 'Google Maps → Share → Embed a map → copy the src URL. Empty = no map.',
        'working_hours' => 'Shown on the Contact page and footer, e.g. Mon–Fri 9:00–18:00.',
        'vat_number' => 'Shown in the footer (TRN).',
        'response_time' => 'Shown near the contact buttons, e.g. “We reply within 24 hours.”',
        'socials' => 'Full links to your profiles. Empty ones are not shown in the footer.',
        'seo_default_title' => 'Used when a page has no title of its own. Up to 60–70 characters.',
        'seo_default_description' => 'Used when a page has no description of its own. Up to 160 characters.',
        'og_image' => 'Shown when a page is shared on social media. 1200 × 630 px, JPG or PNG.',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::SETTINGS);
    }

    public static function getNavigationLabel(): string
    {
        return __('Site settings');
    }

    public function getTitle(): string
    {
        return __('Site settings');
    }

    /** @return Collection<int, Setting> */
    private function settings(): Collection
    {
        return Setting::query()->orderBy('id')->get();
    }

    public function mount(): void
    {
        $this->form->fill(
            $this->settings()->mapWithKeys(fn (Setting $s): array => [$s->key => $s->value])->all(),
        );
    }

    public function form(Form $form): Form
    {
        $settings = $this->settings()->groupBy('group');

        $tabs = collect(self::GROUPS)
            ->filter(fn (string $label, string $group): bool => $settings->has($group))
            ->map(fn (string $label, string $group): Tabs\Tab => Tabs\Tab::make(__($label))
                ->schema($settings[$group]->map(fn (Setting $s): Component => $this->fieldFor($s))->all()));

        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('settings')->tabs($tabs->values()->all())->persistTabInQueryString(),
            ]);
    }

    private function fieldFor(Setting $setting): Component
    {
        $key = $setting->key;
        $label = self::LABELS[$key] ?? Str::headline($key);
        $help = isset(self::HELP[$key]) ? __(self::HELP[$key]) : null;

        return match ($setting->type) {
            Setting::TYPE_TRANSLATABLE => Fields::bilingual($key, $label, in_array($key, self::MULTILINE_KEYS, true), $help),

            Setting::TYPE_BOOLEAN => Toggle::make($key)->label($label)->helperText($help),

            Setting::TYPE_IMAGE => FileUpload::make($key)
                ->label($label)
                ->helperText($help)
                ->image()
                ->disk(Setting::IMAGE_DISK)
                ->directory(Setting::IMAGE_DIRECTORY)
                ->visibility('public')
                ->acceptedFileTypes(MimeTypes::IMAGES)
                ->maxSize(Fields::IMAGE_MAX_KB)
                ->imagePreviewHeight('160')
                ->downloadable(),

            // Socials: one URL input per network.
            Setting::TYPE_JSON => $key === 'socials'
                ? Fieldset::make(__($label))->columns(2)->schema(
                    collect((array) $setting->value)->keys()
                        ->map(fn (string $network): TextInput => TextInput::make("{$key}.{$network}")
                            ->label(Str::headline($network === 'x' ? 'X (Twitter)' : $network))
                            ->url()
                            ->prefixIcon('heroicon-o-link'))
                        ->all(),
                )
                : KeyValue::make($key)->label($label)->helperText($help),

            default => TextInput::make($key)
                ->label($label)
                ->helperText($help)
                ->maxLength(1000)
                ->when(in_array($key, self::URL_KEYS, true), fn (TextInput $input) => $input->url()),
        };
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->settings() as $setting) {
            if (array_key_exists($setting->key, $state)) {
                $setting->value = $state[$setting->key];
                $setting->save(); // only dirty rows are written and logged
            }
        }

        Cache::forget(Setting::CACHE_KEY);

        Notification::make()->success()->title(__('Settings saved'))->send();
    }

    /** @return list<Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label(__('Save'))->submit('save'),
        ];
    }
}
