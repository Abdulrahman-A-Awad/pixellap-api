<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use App\Support\MimeTypes;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
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

    private const HELP = [
        'showreel_url' => 'YouTube, Vimeo or direct .mp4 link.',
        'map_embed_url' => 'Google Maps → Share → Embed a map → copy the src URL.',
        'whatsapp' => 'International format, digits only, e.g. 971501234567.',
        'seo_default_title' => 'Used when a page has no SEO title. Up to 60–70 characters.',
        'seo_default_description' => 'Used when a page has no SEO description. Up to 160 characters.',
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
        $label = Str::headline($key);
        $help = isset(self::HELP[$key]) ? __(self::HELP[$key]) : null;

        return match ($setting->type) {
            Setting::TYPE_TRANSLATABLE => Fields::bilingual($key, $label, in_array($key, self::MULTILINE_KEYS, true)),

            Setting::TYPE_BOOLEAN => Toggle::make($key)->label($label)->helperText($help),

            Setting::TYPE_IMAGE => FileUpload::make($key)
                ->label($key === 'og_image' ? 'Social sharing image' : $label)
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
                ? Grid::make(2)->schema(
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
