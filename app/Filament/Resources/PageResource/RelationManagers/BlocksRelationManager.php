<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageResource\RelationManagers;

use App\Enums\PageBlockType;
use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Page content blocks. Fields change with the block type:
 *  text           → title + body
 *  image_text     → title + body + image
 *  mission_vision → title + items (exactly 2: title + text)
 *  values         → title + items (up to 6: title + text)
 *  cta            → title + body
 */
class BlocksRelationManager extends RelationManager
{
    use Translatable;

    protected static string $relationship = 'blocks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Blocks');
    }

    public static function getModelLabel(): string
    {
        return __('Block');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Blocks');
    }

    private static function type(Get $get): ?PageBlockType
    {
        $type = $get('type');

        return $type instanceof PageBlockType ? $type : PageBlockType::tryFrom((string) $type);
    }

    /** @param  list<PageBlockType>  $types */
    private static function forTypes(array $types): Closure
    {
        return fn (Get $get): bool => in_array(self::type($get), $types, true);
    }

    public function form(Form $form): Form
    {
        return $form->columns(1)->schema([
            Select::make('type')
                ->label('Type')
                ->options(PageBlockType::class)
                ->required()
                ->live()
                ->native(false),

            Fields::text('title')->label('Title'),

            Fields::textarea('body', 6)
                ->label('Body')
                ->helperText(__('Leave an empty line between paragraphs.'))
                ->visible(self::forTypes([PageBlockType::Text, PageBlockType::ImageText, PageBlockType::Cta])),

            Fields::image('image')
                ->label('Image')
                ->visible(self::forTypes([PageBlockType::ImageText])),

            Repeater::make('items')
                ->label('Items')
                ->schema([
                    Fields::text('title')->label('Title')->required(),
                    Fields::textarea('text', 2)->label('Text')->required(),
                ])
                ->minItems(fn (Get $get): int => self::type($get) === PageBlockType::MissionVision ? 2 : 1)
                ->maxItems(fn (Get $get): int => self::type($get) === PageBlockType::MissionVision ? 2 : 6)
                ->defaultItems(fn (Get $get): int => self::type($get) === PageBlockType::MissionVision ? 2 : 1)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->visible(self::forTypes([PageBlockType::MissionVision, PageBlockType::Values])),

            Fields::visible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('sort')->label('#')->width('1%'),
                Tables\Columns\TextColumn::make('type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('title')->label('Title')->wrap(),
                TableKit::visibleToggle(),
            ])
            ->headerActions([
                Tables\Actions\LocaleSwitcher::make(),
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
