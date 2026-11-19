<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\HomeSectionKey;
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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The 8 fixed home page sections. Editors can change content, order and visibility,
 * but cannot create or delete sections.
 */
class HomeSectionResource extends Resource
{
    use Translatable;

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
            Section::make(__('Text'))
                ->schema([
                    Fields::text('eyebrow')->label('Small label above the title')->visible(self::for('eyebrow'))
                        ->helperText(__('A few words in the brand colour, e.g. “What we do”. Optional.')),
                    Fields::text('title')->label('Title')->visible(self::for('title'))
                        ->helperText(fn (?HomeSection $record): string => $record?->key === HomeSectionKey::Hero
                            ? __('The big headline on the first screen. Empty = the site tagline.')
                            : __('The section heading.')),
                    Fields::textarea('subtitle')->label('Short text')->visible(self::for('subtitle'))
                        ->helperText(__('One or two sentences under the title. Optional.')),
                ]),

            Section::make(__('Buttons'))
                ->columns(2)
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('cta'))
                ->schema([
                    Fieldset::make(fn (?HomeSection $record): string => $record?->key?->supports('cta2') ? __('Main button') : __('Button'))
                        ->schema([
                            Fields::text('cta_label')->label('Button text')
                                ->helperText(__('Leave empty to hide the button.')),
                            TextInput::make('cta_url')->label('Button link')->maxLength(255)
                                ->helperText(__('A page like /contact, an anchor like #reel, or a full web address.')),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                    Fieldset::make(__('Second button'))
                        ->visible(self::for('cta2'))
                        ->schema([
                            Fields::text('cta2_label')->label('Button text')
                                ->helperText(__('Shown only when a showreel link is set in Site settings.')),
                            TextInput::make('cta2_url')->label('Button link')->maxLength(255)
                                ->helperText(__('Keep #reel to open the showreel.')),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ]),

            Section::make(__('Photo'))
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('image'))
                ->schema([
                    Fields::image('image')->label('Photo')
                        ->helperText(__('Shown next to the text. Landscape, at least 1600 px wide, JPG/PNG/WebP up to 10 MB.')),
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
                Fields::video($collections['webm'])
                    ->label('WebM')
                    ->acceptedFileTypes(MimeTypes::WEBM)
                    ->maxSize(self::HERO_VIDEO_MAX_KB)
                    ->helperText(__('WebM (VP9/AV1), up to 6 MB.')),
                Fields::video($collections['mp4'])
                    ->label('MP4')
                    ->acceptedFileTypes(MimeTypes::MP4)
                    ->maxSize(self::HERO_VIDEO_MAX_KB)
                    ->helperText(__('MP4 (H.264), up to 6 MB.')),
                Fields::image($collections['poster'])
                    ->label('Poster')
                    ->maxSize(self::HERO_POSTER_MAX_KB)
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
                    ->state(fn (HomeSection $record): ?string => ($n = self::itemCount($record->key)) === null
                        ? null
                        : trans_choice(':count item|:count items', $n, ['count' => $n]))
                    ->badge()
                    ->color('gray')
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

    /** How many items a section shows (null for sections without a list). */
    public static function itemCount(HomeSectionKey $key): ?int
    {
        return match ($key) {
            HomeSectionKey::Stats => Stat::query()->count(),
            HomeSectionKey::Process => ProcessStep::query()->count(),
            HomeSectionKey::Clients => Client::query()->count(),
            HomeSectionKey::Faq => Faq::query()->whereNull('service_category_id')->count(),
            HomeSectionKey::Services => ServiceCategory::query()->count(),
            default => null,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSections::route('/'),
            'edit' => Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }
}
