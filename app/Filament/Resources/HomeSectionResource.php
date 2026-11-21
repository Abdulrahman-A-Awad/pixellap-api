<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\HomeSectionKey;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\HomeSectionResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\ProcessStep;
use App\Models\ServiceCategory;
use App\Models\Stat;
use App\Support\MimeTypes;
use Closure;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * The 8 fixed home page sections. Editors can change content, order and visibility,
 * but cannot create or delete sections.
 *
 * Every translatable field is an EN | AR pair (Fields::bilingual) — no language switcher.
 * Each form starts with "Where this appears"; only the fields the website renders are shown
 * (HomeSectionKey::fields()).
 */
class HomeSectionResource extends Resource
{
    /** The hero loop videos must stay light: they replace the 3D scene on weak devices. */
    public const HERO_VIDEO_MAX_KB = 6 * 1024;

    public const HERO_POSTER_MAX_KB = 1024;

    protected static ?string $model = HomeSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::CONTENT);
    }

    public static function getNavigationLabel(): string
    {
        return __('Home Page');
    }

    public static function getModelLabel(): string
    {
        return __('Home section');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Home sections');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** Show a field only for section keys that use it. */
    private static function for(string $field): Closure
    {
        return fn (?HomeSection $record): bool => $record?->key?->supports($field) ?? true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('Where this appears'))
                ->icon('heroicon-o-map-pin')
                ->compact()
                ->schema([
                    Placeholder::make('where')
                        ->hiddenLabel()
                        ->content(fn (?HomeSection $record): string => $record ? __($record->key->where()) : ''),
                    // Hero: the headline lives in one place (the tagline setting).
                    Placeholder::make('headline')
                        ->label(__('Headline'))
                        ->visible(fn (?HomeSection $record): bool => $record?->key === HomeSectionKey::Hero)
                        ->content(fn (): HtmlString => new HtmlString(
                            e(__('The headline is the site tagline — edit it in Site settings → General.'))
                            .' <a href="'.e(ManageSettings::getUrl()).'" class="font-semibold text-primary-600 underline dark:text-primary-400">'
                            .e(__('Open Site settings')).'</a>'
                        )),
                    // Sections that need items: why the visible toggle may not show anything yet.
                    Placeholder::make('empty')
                        ->hiddenLabel()
                        ->visible(fn (?HomeSection $record): bool => $record !== null && self::isHiddenForNoItems($record))
                        ->content(fn (?HomeSection $record): HtmlString => new HtmlString(
                            '<span class="font-medium text-warning-600 dark:text-warning-400">'
                            .e(__((string) $record?->key->emptyNotice())).'</span>'
                        )),
                ]),

            Section::make(__('Text'))
                ->visible(fn (?HomeSection $record): bool => array_intersect(['eyebrow', 'title', 'subtitle'], $record?->key?->fields() ?? []) !== [])
                ->schema([
                    Fields::bilingual('eyebrow', 'Small label above the title', help: __('A few words in the brand colour, e.g. “What we do”. Optional.'))
                        ->visible(self::for('eyebrow')),
                    Fields::bilingual('title', 'Title', help: __('The section heading.'))
                        ->visible(self::for('title')),
                    Fields::bilingual('subtitle', 'Short text', 'textarea', __('One or two sentences. Optional.'))
                        ->visible(self::for('subtitle')),
                ]),

            Section::make(fn (?HomeSection $record): string => $record?->key === HomeSectionKey::Services
                ? __('Link next to the heading')
                : __('Buttons'))
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('cta'))
                ->schema([
                    Fieldset::make(fn (?HomeSection $record): string => $record?->key?->supports('cta2') ? __('Button 1 (main)') : __('Button'))
                        ->columns(1)
                        ->schema([
                            Fields::bilingual('cta_label', 'Button text', help: __('Empty = the default text (hero: “Start a project”, services: “View all services”); on the contact banner the button is hidden.')),
                            TextInput::make('cta_url')->label('Button link')->maxLength(255)
                                ->helperText(__('A page like /contact, or a full web address.')),
                        ]),
                    Fieldset::make(__('Button 2'))
                        ->visible(self::for('cta2'))
                        ->columns(1)
                        ->schema([
                            Fields::bilingual('cta2_label', 'Button text', help: __('Default: “Explore our services”. Empty = that default.')),
                            TextInput::make('cta2_url')->label('Button link')->maxLength(255)
                                ->helperText(__('Default: /services.')),
                        ]),
                ]),

            Section::make(__('Photo'))
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('image'))
                ->schema([
                    Fields::image('image')->label('Photo')
                        ->helperText(__('Recommended: landscape, at least 1600 px wide (2400 px is ideal). JPG/PNG/WebP up to 10 MB.')),
                ]),

            // Hero only: loop videos shown instead of the 3D scene on phones / low-power devices.
            Section::make(__('Loop videos'))
                ->description(__('Silent, 6–10 s, seamless loop. Prefer a version WITHOUT logo/text — the page already shows the headline.'))
                ->columns(2)
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('media'))
                ->schema([
                    self::loopVariant('wide', 'Wide (desktop)'),
                    self::loopVariant('vertical', 'Vertical (mobile)'),
                ]),

            Section::make()
                ->schema([Fields::visible()]),
        ]);
    }

    /** WebM + MP4 + poster for one orientation of the hero loop video. */
    private static function loopVariant(string $variant, string $label): Fieldset
    {
        $collections = HomeSection::HERO_MEDIA[$variant];

        return Fieldset::make(__($label))
            ->columns(1)
            ->columnSpan(1)
            ->schema([
                // No preview: it would download the whole video on every visit to this page.
                // Filename + size + "Open"; replace / remove still work.
                Fields::video($collections['webm'])
                    ->label('WebM')
                    ->previewable(false)
                    ->openable()
                    ->acceptedFileTypes(MimeTypes::WEBM)
                    ->maxSize(self::HERO_VIDEO_MAX_KB)
                    ->helperText(__('WebM (VP9/AV1), up to 6 MB.')),
                Fields::video($collections['mp4'])
                    ->label('MP4')
                    ->previewable(false)
                    ->openable()
                    ->acceptedFileTypes(MimeTypes::MP4)
                    ->maxSize(self::HERO_VIDEO_MAX_KB)
                    ->helperText(__('MP4 (H.264), up to 6 MB.')),
                Fields::image($collections['poster'])
                    ->label('Poster')
                    ->maxSize(self::HERO_POSTER_MAX_KB)
                    ->imagePreviewHeight('120')
                    ->helperText(__('First frame of the video, up to 1 MB.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->paginated(false)
            ->description(__('Drag the rows to change the order of the sections on the home page.'))
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Section')
                    ->formatStateUsing(fn (HomeSectionKey $state): string => $state->getLabel())
                    ->description(fn (HomeSection $record): string => __($record->key->description()))
                    ->weight('bold')
                    ->wrap(),
                Tables\Columns\TextColumn::make('items')
                    ->label('Items')
                    ->state(fn (HomeSection $record): ?string => match (true) {
                        self::isHiddenForNoItems($record) => __('Hidden: no items'),
                        ($n = self::itemCount($record->key)) === null => null,
                        default => trans_choice(':count item|:count items', $n, ['count' => $n]),
                    })
                    ->badge()
                    ->color(fn (HomeSection $record): string => self::isHiddenForNoItems($record) ? 'warning' : 'gray')
                    ->icon(fn (HomeSection $record): ?string => self::isHiddenForNoItems($record) ? 'heroicon-m-eye-slash' : null)
                    ->tooltip(fn (HomeSection $record): ?string => self::isHiddenForNoItems($record) ? __((string) $record->key->emptyNotice()) : null)
                    ->placeholder('—'),
                TableKit::visibleToggle(),
                Tables\Columns\TextColumn::make('title')->label('Title')->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    /** How many items a section has (null for sections without a list). */
    public static function itemCount(HomeSectionKey $key, bool $visibleOnly = false): ?int
    {
        $query = match ($key) {
            HomeSectionKey::Stats => Stat::query(),
            HomeSectionKey::Process => ProcessStep::query(),
            HomeSectionKey::Clients => Client::query(),
            HomeSectionKey::Faq => Faq::query()->whereNull('service_category_id'),
            HomeSectionKey::Services => ServiceCategory::query(),
            default => null,
        };

        if ($query === null) {
            return null;
        }

        return ($visibleOnly ? $query->where('is_visible', true) : $query)->count();
    }

    /**
     * The section needs items and has no visible one: the website does not render it,
     * whatever its own "visible" toggle says.
     */
    public static function isHiddenForNoItems(HomeSection $record): bool
    {
        return $record->key->emptyNotice() !== null && self::itemCount($record->key, visibleOnly: true) === 0;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSections::route('/'),
            'edit' => Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }
}
