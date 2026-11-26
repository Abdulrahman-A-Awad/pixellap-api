<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceCategoryResource\RelationManagers;

use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use App\Models\Service;
use App\Support\MimeTypes;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Services inside a category. English | Arabic side by side (no language switcher). */
class ServicesRelationManager extends RelationManager
{
    use RevalidatesOnReorder;

    protected static string $relationship = 'services';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Services');
    }

    public static function getModelLabel(): string
    {
        return __('Service');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Services');
    }

    public function form(Form $form): Form
    {
        return $form->columns(1)->schema([
            Fields::bilingual('title', 'Title', help: __('Shown as the card heading on the service page.'), required: true, maxLength: 255),
            Fields::bilingual('description', 'Description', 'textarea', __('Two or three sentences under the heading.'), required: true),
            Fields::bilingual('features', 'Features', 'tags', __('Shown as a checklist. Type a feature and press Enter.')),
            Grid::make(['default' => 1, 'md' => 2])->schema([
                Fields::vectorOrImage('icon', MimeTypes::ICONS)->label('Icon')
                    ->helperText(__('Optional. SVG/PNG, square. Empty = a brand pixel icon.')),
                Fields::image('image')->label('Photo')
                    ->helperText(__('Optional. Landscape photo shown at the bottom of the card.')),
            ]),
            Fields::visible(),
        ]);
    }

    /** @return array{en: int, ar: int} */
    private static function featureCounts(Service $record): array
    {
        $count = fn (string $locale): int => count(array_filter((array) $record->getTranslation('features', $locale, false)));

        return ['en' => $count('en'), 'ar' => $count('ar')];
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('sort')->label('#')->width('1%'),
                ...TableKit::bilingualColumns('title', 'Title'),
                Tables\Columns\TextColumn::make('features_count')
                    ->label('Features')
                    // Left-to-right isolate so the counts stay in order in Arabic mode.
                    ->state(fn (Service $record): string => "\u{2066}EN ".self::featureCounts($record)['en'].' · AR '.self::featureCounts($record)['ar']."\u{2069}")
                    ->badge()
                    ->color(fn (Service $record): string => self::featureCounts($record)['ar'] < self::featureCounts($record)['en'] ? 'warning' : 'gray'),
                TableKit::visibleToggle(),
            ])
            ->filters([
                TableKit::visibleFilter(),
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
