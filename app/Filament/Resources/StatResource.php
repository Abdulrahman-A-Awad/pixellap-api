<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StatResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\Stat;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StatResource extends Resource
{
    use Translatable;

    protected static ?string $model = Stat::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::HOME);
    }

    public static function getModelLabel(): string
    {
        return __('Stat');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stats');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(3)->schema([
                    TextInput::make('prefix')->label('Prefix')->maxLength(10)->placeholder('AED'),
                    TextInput::make('value')->label('Value')->numeric()->minValue(0)->required(),
                    TextInput::make('suffix')->label('Suffix')->maxLength(10)->placeholder('+'),
                ]),
                Fields::text('label')->label('Label')->required(),
                Fields::visible(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('display')
                    ->label('Value')
                    ->state(fn (Stat $record): string => $record->prefix.number_format($record->value).$record->suffix)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('label')->label('Label'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStats::route('/'),
            'create' => Pages\CreateStat::route('/create'),
            'edit' => Pages\EditStat::route('/{record}/edit'),
        ];
    }
}
