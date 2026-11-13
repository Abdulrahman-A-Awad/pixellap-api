<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceCategoryResource\RelationManagers;

use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use App\Models\Service;
use App\Support\MimeTypes;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServicesRelationManager extends RelationManager
{
    use Translatable;

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
            Fields::text('title')->label('Title')->required(),
            Fields::textarea('description')->label('Description')->required(),
            TagsInput::make('features')
                ->label('Features')
                ->reorderable()
                ->placeholder(__('Type a feature and press Enter'))
                ->helperText(__('Each language has its own list — switch the language to edit the other.')),
            Grid::make(2)->schema([
                Fields::vectorOrImage('icon', MimeTypes::ICONS)->label('Icon'),
                Fields::image('image')->label('Image'),
            ]),
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
                Tables\Columns\TextColumn::make('title')->label('Title')->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('features_count')
                    ->label('Features')
                    ->state(fn (Service $record): int => count((array) $record->getTranslation('features', $this->activeLocale ?? 'en', false)))
                    ->badge()
                    ->color('gray'),
                TableKit::visibleToggle(),
            ])
            ->filters([
                TableKit::visibleFilter(),
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
