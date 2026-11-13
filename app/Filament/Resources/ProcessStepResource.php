<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProcessStepResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\ProcessStep;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProcessStepResource extends Resource
{
    use Translatable;

    protected static ?string $model = ProcessStep::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::HOME);
    }

    public static function getModelLabel(): string
    {
        return __('Process step');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Process steps');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Fields::text('title')->label('Title')->required(),
                Fields::textarea('description')->label('Description')->required(),
                TextInput::make('icon')
                    ->label('Icon')
                    ->maxLength(50)
                    ->helperText(__('Icon key used by the website, e.g. search, lightbulb, clapperboard, rocket.')),
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
                Tables\Columns\TextColumn::make('sort')->label('#')->width('1%'),
                Tables\Columns\TextColumn::make('title')->label('Title')->weight('bold'),
                Tables\Columns\TextColumn::make('description')->label('Description')->limit(70)->wrap(),
                Tables\Columns\TextColumn::make('icon')->label('Icon')->badge()->color('gray'),
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
            'index' => Pages\ListProcessSteps::route('/'),
            'create' => Pages\CreateProcessStep::route('/create'),
            'edit' => Pages\EditProcessStep::route('/{record}/edit'),
        ];
    }
}
