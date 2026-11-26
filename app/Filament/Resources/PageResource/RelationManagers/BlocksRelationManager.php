<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageResource\RelationManagers;

use App\Enums\PageBlockType;
use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
 *
 * English | Arabic side by side (no language switcher). `items` is stored per language
 * ({en: [{title, text}], ar: [...]}): one cards list per language; only English enforces the count.
 */
class BlocksRelationManager extends RelationManager
{
    use RevalidatesOnReorder;

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

    private static function maxCards(Get $get): int
    {
        return self::type($get) === PageBlockType::MissionVision ? 2 : 6;
    }

    /** One language's cards list. */
    private static function cards(string $locale): Repeater
    {
        $dir = $locale === 'ar' ? 'rtl' : 'ltr';
        $isEnglish = $locale === 'en';

        return Repeater::make("items.{$locale}")
            ->label(__('Cards').($isEnglish ? ' — English' : ' — العربية'))
            ->schema([
                TextInput::make('title')->label('Title')->required()->maxLength(255)
                    ->extraInputAttributes(['dir' => $dir, 'lang' => $locale]),
                Textarea::make('text')->label('Text')->required()->rows(2)->autosize()
                    ->extraInputAttributes(['dir' => $dir, 'lang' => $locale]),
            ])
            ->minItems(fn (Get $get): int => $isEnglish ? (self::type($get) === PageBlockType::MissionVision ? 2 : 1) : 0)
            ->maxItems(fn (Get $get): int => self::maxCards($get))
            ->defaultItems(fn (Get $get): int => $isEnglish ? (self::type($get) === PageBlockType::MissionVision ? 2 : 1) : 0)
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
            ->live()
            ->when(! $isEnglish, fn (Repeater $repeater) => $repeater
                // Not blocking: missing Arabic cards fall back to English on the website.
                ->hint(fn (?array $state, Get $get): ?string => count($state ?? []) < count((array) $get('items.en')) ? __('Arabic missing') : null)
                ->hintIcon(fn (?array $state, Get $get): ?string => count($state ?? []) < count((array) $get('items.en')) ? 'heroicon-m-exclamation-triangle' : null)
                ->hintColor('warning'))
            ->when($isEnglish, fn (Repeater $repeater) => $repeater
                ->helperText(__('Mission & vision: exactly 2 cards. Values: 1 to 6 cards.')));
    }

    public function form(Form $form): Form
    {
        return $form->columns(1)->schema([
            Select::make('type')
                ->label('Block type')
                ->helperText(__('Decides the layout: text, text with photo, mission & vision, values, or a closing call-to-action.'))
                ->options(PageBlockType::class)
                ->required()
                ->live()
                ->native(false),

            Fields::bilingual('title', 'Heading', help: __('Optional heading shown above the block.'), maxLength: 255),

            Fields::bilingual('body', 'Text', 'textarea', __('Leave an empty line between paragraphs.'), rows: 6)
                ->visible(self::forTypes([PageBlockType::Text, PageBlockType::ImageText, PageBlockType::Cta])),

            Fields::image('image')
                ->label('Photo')
                ->helperText(__('Shown beside the text. Landscape or square, at least 1200 px wide.'))
                ->visible(self::forTypes([PageBlockType::ImageText])),

            Grid::make(['default' => 1, 'lg' => 2])
                ->schema([self::cards('en'), self::cards('ar')])
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
                ...TableKit::bilingualColumns('title', 'Title'),
                TableKit::visibleToggle(),
            ])
            ->headerActions([
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
