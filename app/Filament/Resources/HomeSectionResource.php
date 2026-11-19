<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\HomeSectionKey;
use App\Filament\Resources\HomeSectionResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\HomeSection;
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
        return __(NavGroup::HOME);
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
            Section::make(__('Content'))
                ->schema([
                    Fields::text('eyebrow')->label('Eyebrow')->visible(self::for('eyebrow')),
                    Fields::text('title')->label('Title')->visible(self::for('title')),
                    Fields::textarea('subtitle')->label('Subtitle')->visible(self::for('subtitle')),
                ]),

            Section::make(__('Buttons'))
                ->columns(2)
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('cta'))
                ->schema([
                    Fieldset::make(__('Button 1'))
                        ->schema([
                            Fields::text('cta_label')->label('Label'),
                            TextInput::make('cta_url')->label('Link')->maxLength(255)
                                ->helperText(__('A path like /contact, an anchor like #reel, or a full URL.')),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                    Fieldset::make(__('Button 2'))
                        ->visible(self::for('cta2'))
                        ->schema([
                            Fields::text('cta2_label')->label('Label'),
                            TextInput::make('cta2_url')->label('Link')->maxLength(255),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ]),

            Section::make(__('Media'))
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('image'))
                ->schema([
                    Fields::image('image')->label('Image'),
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
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Section')
                    ->formatStateUsing(fn (HomeSectionKey $state): string => $state->getLabel())
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('title')->label('Title')->limit(60),
                TableKit::visibleToggle(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TableKit::visibleFilter(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSections::route('/'),
            'edit' => Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }
}
