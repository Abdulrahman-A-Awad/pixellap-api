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
use App\Support\ArabicSearch;
use App\Support\MimeTypes;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceCategoryResource extends Resource
{
    public const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    protected static ?string $model = ServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 2;

    /** Global search (topbar): category title / summary / slug and the titles of its services, EN + AR. */
    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'excerpt', 'slug', 'services.title'];
    }

    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        ArabicSearch::apply($query, $search, translatable: ['title', 'excerpt'], plain: ['slug'], relations: [
            'services' => ['translatable' => ['title']],
        ]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        // Left-to-right isolate: the URL path stays readable inside Arabic text.
        return [__('Page address') => "\u{2066}/services/{$record->slug}\u{2069}"];
    }

    /** EN title (+ page address) | AR title or "AR missing". */
    private static function titleColumns(): array
    {
        [$en, $ar] = TableKit::bilingualColumns('title', 'Service');

        return [
            $en->description(fn (ServiceCategory $record): string => '/services/'.$record->slug),
            $ar,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::CONTENT);
    }

    public static function getNavigationLabel(): string
    {
        return __('Services');
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
                    Section::make(__('Content'))
                        ->description(__('English and Arabic side by side. Empty Arabic shows a warning; the website then uses the English text.'))
                        ->schema([
                            Fields::bilingual('title', 'Service name', help: __('Shown on the service card, the menu and the service page.'), required: true, maxLength: 255,
                                tap: fn (Field $field, string $locale) => $locale === 'en' ? $field
                                    ->live(onBlur: true)
                                    // Generate the slug from the ENGLISH title on create only.
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                                        if ($operation === 'create' && blank($get('slug'))) {
                                            $set('slug', Str::slug((string) $state));
                                        }
                                    }) : null),
                            TextInput::make('slug')
                                ->label('Page address')
                                ->required()
                                ->maxLength(100)
                                ->regex(self::SLUG_REGEX)
                                ->unique(ignoreRecord: true)
                                ->prefix('/services/')
                                ->disabled(fn (string $operation): bool => self::slugLocked($operation))
                                ->helperText(fn (string $operation): string => self::slugLocked($operation)
                                    ? __('Locked after creation because it is part of the page URL.')
                                    : __('Lowercase English letters, numbers and dashes. Generated from the English title.')),
                            Fields::bilingual('excerpt', 'Short summary', 'textarea', __('One sentence on the service card and at the top of the service page.'), required: true, rows: 2,
                                tap: fn (Field $field) => $field->maxLength(255)),
                            Fields::bilingual('description', 'Overview', 'rich', __('The “Overview” text on the service page.'), required: true),
                        ]),
                    Section::make(__('Google & sharing'))
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Fields::bilingual('seo_title', 'Page title for Google', help: __('Up to 60–70 characters. Leave empty to use the title.'), maxLength: 70),
                            Fields::bilingual('seo_description', 'Page description for Google', 'textarea', __('Up to 160 characters. Leave empty to use the short summary.'), rows: 2,
                                tap: fn (Field $field) => $field->maxLength(170)),
                        ]),
                ]),
                Group::make()->columnSpan(1)->schema([
                    Section::make(__('Images & video'))->schema([
                        Fields::vectorOrImage('icon', MimeTypes::SVG)->label('Icon')
                            ->helperText(__('SVG only, single colour, square. Shown on cards and the service page.')),
                        Fields::image('cover')->label('Cover photo')
                            ->helperText(__('Landscape, at least 1600 px wide. Shown on the service card and page header.')),
                        Fields::video('hero_video')->label('Header video (optional)')
                            ->helperText(__('Silent MP4/WebM loop shown in the page header on desktop, up to 40 MB.')),
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
                ...self::titleColumns(),
                Tables\Columns\TextColumn::make('services_count')->label('Sub-services')->counts('services')->badge(),
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
