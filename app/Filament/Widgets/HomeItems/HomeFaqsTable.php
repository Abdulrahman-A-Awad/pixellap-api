<?php

declare(strict_types=1);

namespace App\Filament\Widgets\HomeItems;

use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;

/** Home page FAQs (service FAQs live inside each service category). */
class HomeFaqsTable extends HomeItemsTable
{
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

    protected function columns(): array
    {
        return [
            ...TableKit::bilingualColumns('question', 'Question'),
        ];
    }

    protected function formSchema(): array
    {
        return [
            Fields::bilingual('question', 'Question', required: true),
            Fields::bilingual('answer', 'Answer', 'rich', __('Shown when the visitor opens the question.'), required: true),
            Fields::visible(),
        ];
    }
}
