<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceCategoryResource\Pages;
use App\Filament\Resources\ServiceCategoryResource\RelationManagers;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\MimeTypes;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;

class ServiceCategoryResource extends Resource
{
    use Translatable;

    public const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    protected static ?string $model = ServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::SERVICES);
    }

    public static function getModelLabel(): string
    {
        return __('Service category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Service categories');
    }

    /** Slugs are URLs: once created, only a super admin may change them. */
    private static function slugLocked(string $operation): bool
    {
        $user = auth()->user();

        return $operation !== 'create' && ! ($user instanceof User && $user->isSuperAdmin());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Group::make()->columnSpan(2)->schema([
                    Section::make(__('Content'))->schema([
                        Fields::text('title')
                            ->label('Title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation, Livewire $livewire): void {
                                // Generate the slug from the ENGLISH title on create only.
                                $locale = property_exists($livewire, 'activeLocale') ? $livewire->activeLocale : 'en';

                                if ($operation === 'create' && $locale === 'en' && blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(100)
                            ->regex(self::SLUG_REGEX)
                            ->unique(ignoreRecord: true)
                            ->prefix('/services/')
                            ->disabled(fn (string $operation): bool => self::slugLocked($operation))
                            ->helperText(fn (string $operation): string => self::slugLocked($operation)
                                ? __('Locked after creation because it is part of the page URL.')
                                : __('Lowercase English letters, numbers and dashes. Generated from the English title.')),
                        Fields::textarea('excerpt', 2)->label('Excerpt')->required()->maxLength(255),
                        Fields::rich('description')->label('Description')->required(),
                    ]),
                    Section::make(__('SEO'))
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Fields::text('seo_title')->label('SEO title')->maxLength(70)
                                ->helperText(__('Up to 60–70 characters. Leave empty to use the title.')),
                            Fields::textarea('seo_description', 2)->label('SEO description')->maxLength(170)
                                ->helperText(__('Up to 160 characters.')),
                        ]),
                ]),
                Group::make()->columnSpan(1)->schema([
                    Section::make(__('Media'))->schema([
                        Fields::vectorOrImage('icon', MimeTypes::SVG)->label('Icon (SVG)'),
                        Fields::image('cover')->label('Cover image'),
                        Fields::video('hero_video')->label('Hero video'),
                    ]),
                    Section::make()->schema([Fields::visible()]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Title')->weight('bold')->wrap()
                    ->description(fn (ServiceCategory $record): string => '/services/'.$record->slug),
                Tables\Columns\TextColumn::make('services_count')->label('Services')->counts('services')->badge(),
                TableKit::visibleToggle(),
            ])
            ->filters([
                TableKit::visibleFilter(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\FaqsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceCategories::route('/'),
            'create' => Pages\CreateServiceCategory::route('/create'),
            'edit' => Pages\EditServiceCategory::route('/{record}/edit'),
        ];
    }
}
