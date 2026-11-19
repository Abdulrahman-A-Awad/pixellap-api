<?php

declare(strict_types=1);

namespace App\Filament\Widgets\HomeItems;

use App\Filament\Support\Fields;
use App\Models\Client;
use Filament\Forms\Components\TextInput;
use Filament\Tables;

/** "Clients" section items. Names are proper nouns: not translatable, no language switcher. */
class ClientsTable extends HomeItemsTable
{
    protected function model(): string
    {
        return Client::class;
    }

    protected function heading(): string
    {
        return 'Client logos';
    }

    protected function itemLabel(): string
    {
        return 'Client';
    }

    protected function itemPluralLabel(): string
    {
        return 'Clients';
    }

    protected function columns(): array
    {
        return [
            Tables\Columns\SpatieMediaLibraryImageColumn::make('logo')
                ->label('Logo')
                ->collection('logo')
                ->height(40)
                ->extraImgAttributes(['style' => 'object-fit: contain; max-width: 120px;']),
            Tables\Columns\TextColumn::make('name')->label('Name')->weight('bold'),
            Tables\Columns\TextColumn::make('url')->label('Website')->limit(40)->color('gray')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('name')->label('Name')->required()->maxLength(255)
                ->helperText(__('Used as the logo’s alternative text.')),
            TextInput::make('url')->label('Website')->url()->maxLength(255)
                ->helperText(__('Optional. The logo links here.')),
            Fields::vectorOrImage('logo')
                ->label('Logo')
                ->helperText(__('SVG, PNG or WebP, up to 10 MB. Transparent background recommended.')),
            Fields::visible(),
        ];
    }
}
