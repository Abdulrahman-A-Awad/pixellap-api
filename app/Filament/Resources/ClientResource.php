<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Client logos. Not translatable (names are proper nouns), so no locale switcher.
 */
class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::HOME);
    }

    public static function getModelLabel(): string
    {
        return __('Client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Clients');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Name')->required()->maxLength(255),
                TextInput::make('url')->label('Website')->url()->maxLength(255),
                Fields::vectorOrImage('logo')
                    ->label('Logo')
                    ->helperText(__('SVG, PNG or WebP, up to 5 MB. Transparent background recommended.'))
                    ->columnSpanFull(),
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
                Tables\Columns\SpatieMediaLibraryImageColumn::make('logo')
                    ->label('Logo')
                    ->collection('logo')
                    ->height(40)
                    ->extraImgAttributes(['style' => 'object-fit: contain; max-width: 120px;']),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('url')
                    ->label('Website')
                    ->url(fn (Client $record): ?string => $record->url, shouldOpenInNewTab: true)
                    ->limit(40)
                    ->color('gray'),
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
