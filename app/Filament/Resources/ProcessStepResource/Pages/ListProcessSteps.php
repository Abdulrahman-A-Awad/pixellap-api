<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcessStepResource\Pages;

use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Resources\ProcessStepResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcessSteps extends ListRecords
{
    use RevalidatesOnReorder;
    use ListRecords\Concerns\Translatable;

    protected static string $resource = ProcessStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
