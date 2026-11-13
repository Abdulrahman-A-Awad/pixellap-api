<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PageKey;
use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Models\Page;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The 3 fixed pages (about, services, contact). No create/delete.
 */
class PageResource extends Resource
{
    use Translatable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::PAGES);
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
                    Section::make(__('Header'))->schema([
                        Fields::text('title')->label('Title')->required(),
                        Fields::textarea('subtitle', 2)->label('Subtitle'),
                    ]),
                    Section::make(__('SEO'))
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Fields::text('seo_title')->label('SEO title')->maxLength(70),
                            Fields::textarea('seo_description', 2)->label('SEO description')->maxLength(170),
                        ]),
                ]),
                Section::make(__('Media'))->columnSpan(1)->schema([
                    Fields::image('hero')->label('Hero image'),
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
                Tables\Columns\TextColumn::make('title')->label('Title'),
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
