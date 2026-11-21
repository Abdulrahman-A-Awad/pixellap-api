<?php

declare(strict_types=1);

namespace App\Filament\Widgets\HomeItems;

use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use App\Models\ProcessStep;

/** "How we work" section items. */
class ProcessStepsTable extends HomeItemsTable
{
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

    protected function columns(): array
    {
        return [
            ...TableKit::bilingualColumns('title', 'Title'),
        ];
    }

    protected function formSchema(): array
    {
        return [
            Fields::bilingual('title', 'Title', help: __('Short step name, e.g. “Discover & Brief”.'), required: true, maxLength: 255),
            Fields::bilingual('description', 'Description', 'textarea', __('One or two sentences shown under the step.'), required: true),
            Fields::visible(),
        ];
    }
}
