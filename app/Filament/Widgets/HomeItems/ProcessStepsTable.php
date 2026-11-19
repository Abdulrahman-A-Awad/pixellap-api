<?php

declare(strict_types=1);

namespace App\Filament\Widgets\HomeItems;

use App\Filament\Support\Fields;
use App\Models\ProcessStep;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Tables;

/** "How we work" section items. */
class ProcessStepsTable extends HomeItemsTable
{
    use Translatable;

    protected function model(): string
    {
        return ProcessStep::class;
    }

    protected function heading(): string
    {
        return 'Steps in this section';
    }

    protected function itemLabel(): string
    {
        return 'Step';
    }

    protected function itemPluralLabel(): string
    {
        return 'Steps';
    }

    protected function extraHeaderActions(): array
    {
        return [Tables\Actions\LocaleSwitcher::make()];
    }

    protected function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')->label('Title')->weight('bold'),
            Tables\Columns\TextColumn::make('description')->label('Description')->limit(70)->wrap(),
        ];
    }

    protected function formSchema(): array
    {
        return [
            Fields::text('title')->label('Title')->required()
                ->helperText(__('Short step name, e.g. “Discover & Brief”.')),
            Fields::textarea('description')->label('Description')->required()
                ->helperText(__('One or two sentences shown under the step.')),
            Fields::visible(),
        ];
    }
}
