<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PageKey;
use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\Page;
use App\Support\ArabicSearch;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The 3 fixed pages (about, services, contact). No create/delete.
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 3;

    /** Global search (topbar). */
    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'subtitle', 'key', 'blocks.title'];
    }

    /** EN + AR (see App\Support\ArabicSearch): page title / short text / key and block headings. */
    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        ArabicSearch::apply($query, $search, translatable: ['title', 'subtitle'], plain: ['key'], relations: [
            'blocks' => ['translatable' => ['title']],
        ]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) $record->title;
    }

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::CONTENT);
    }

    public static function getModelLabel(): string
    {
        return __('Page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pages');
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

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Group::make()->columnSpan(2)->schema([
                    Section::make(__('Page header'))
                        ->description(__('English and Arabic side by side. Empty Arabic shows a warning; the website then uses the English text.'))
                        ->schema([
                            Fields::bilingual('title', 'Title', help: __('The big heading at the top of the page.'), required: true, maxLength: 255),
                            Fields::bilingual('subtitle', 'Short text', 'textarea', __('One sentence under the heading. Optional.'), rows: 2),
                        ]),
                    Section::make(__('Google & sharing'))
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Fields::bilingual('seo_title', 'Page title for Google', help: __('Up to 60–70 characters. Leave empty to use the title.'), maxLength: 70),
                            Fields::bilingual('seo_description', 'Page description for Google', 'textarea', __('Up to 160 characters. Leave empty to use the site default.'), rows: 2,
                                tap: fn (Field $field) => $field->maxLength(170)),
                        ]),
                ]),
                Section::make(__('Image'))->columnSpan(1)->schema([
                    // The Contact page shows no header image; its image is only used for social previews.
                    Fields::image('hero')
                        ->label(fn (?Page $record): string => $record?->key === PageKey::Contact ? __('Sharing image') : __('Header image'))
                        ->helperText(fn (?Page $record): string => $record?->key === PageKey::Contact
                            ? __('Not shown on the page — used only when the page is shared on social media. 1200 × 630 px.')
                            : __('Wide photo under the page heading, at least 1920 px wide. Also used when the page is shared.')),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Page')
                    ->formatStateUsing(fn (PageKey $state): string => $state->getLabel())
                    ->weight('bold'),
                ...TableKit::bilingualColumns('title', 'Title'),
                Tables\Columns\TextColumn::make('blocks_count')->label('Blocks')->counts('blocks')->badge(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->since(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BlocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
