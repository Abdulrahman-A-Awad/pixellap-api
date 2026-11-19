<?php

declare(strict_types=1);

namespace App\Filament\Widgets\HomeItems;

use App\Filament\Support\Fields;
use App\Models\Faq;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/** Home page FAQs (service FAQs live inside each service category). */
class HomeFaqsTable extends HomeItemsTable
{
    use Translatable;

    protected function model(): string
    {
        return Faq::class;
    }

    protected function query(): Builder
    {
        return Faq::query()->whereNull('service_category_id');
    }

    protected function heading(): string
    {
        return 'Questions in this section';
    }

    protected function itemLabel(): string
    {
        return 'Question';
    }

    protected function itemPluralLabel(): string
    {
        return 'Questions';
    }

    protected function extraHeaderActions(): array
    {
        return [Tables\Actions\LocaleSwitcher::make()];
    }

    protected function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('question')->label('Question')->wrap()->weight('bold'),
        ];
    }

    protected function formSchema(): array
    {
        return [
            Fields::text('question')->label('Question')->required()->maxLength(500),
            Fields::rich('answer')->label('Answer')->required()
                ->helperText(__('Shown when the visitor opens the question.')),
            Fields::visible(),
        ];
    }
}
