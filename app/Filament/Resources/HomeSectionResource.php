<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\HomeSectionKey;
use App\Filament\Resources\HomeSectionResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\HomeSection;
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

    /** The hero loop video must stay light: it replaces the 3D scene on weak devices. */
    public const HERO_VIDEO_MAX_KB = 3 * 1024;

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
                ->columns(2)
                ->visible(fn (?HomeSection $record): bool => (bool) $record?->key?->supports('image'))
                ->schema([
                    // Hero: the 3D scene's poster + the loop video used on mobile / low-power devices.
                    Fields::image('image')
                        ->label(fn (?HomeSection $record): string => __($record?->key === HomeSectionKey::Hero
                            ? 'Poster image (first frame)'
                            : 'Image')),
                    Fields::video('video')
                        ->label('Fallback loop video (mobile / low-power devices, silent, 6–10s, ≤ 3 MB, webm or mp4)')
                        ->maxSize(self::HERO_VIDEO_MAX_KB)
                        ->helperText(__('Shown instead of the 3D scene on phones and low-power devices. Keep it silent and short.'))
                        ->visible(self::for('video')),
                ]),

            Section::make()
                ->schema([Fields::visible()]),
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
