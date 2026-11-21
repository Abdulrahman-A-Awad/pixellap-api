<?php

declare(strict_types=1);

namespace App\Filament\Widgets\HomeItems;

use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use App\Models\Stat;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Tables;

/** "Numbers" section items. */
class StatsTable extends HomeItemsTable
{
    protected function model(): string
    {
        return Stat::class;
    }

    protected function heading(): string
    {
        return 'Numbers in this section';
    }

    protected function itemLabel(): string
    {
        return 'Number';
    }

    protected function itemPluralLabel(): string
    {
        return 'Numbers';
    }

    protected function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('display')
                ->label('Number')
                ->state(fn (Stat $record): string => $record->prefix.number_format($record->value).$record->suffix)
                ->extraAttributes(['dir' => 'ltr'])
                ->weight('bold'),
            ...TableKit::bilingualColumns('label', 'Label'),
        ];
    }

    protected function formSchema(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('prefix')->label('Before the number')->maxLength(10)->placeholder('AED')
                    ->helperText(__('Optional, e.g. a currency.')),
                TextInput::make('value')->label('Number')->numeric()->minValue(0)->required()
                    ->helperText(__('Whole number; it counts up on the website.')),
                TextInput::make('suffix')->label('After the number')->maxLength(10)->placeholder('+')
                    ->helperText(__('Optional, e.g. + or %.')),
            ]),
            Fields::bilingual('label', 'Label', help: __('Short text under the number, e.g. “Projects delivered”.'), required: true, maxLength: 255),
            Fields::visible(),
        ];
    }
}
