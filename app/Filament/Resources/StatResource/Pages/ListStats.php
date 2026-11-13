<?php

declare(strict_types=1);

namespace App\Filament\Resources\StatResource\Pages;

use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Resources\StatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStats extends ListRecords
{
    use RevalidatesOnReorder;
    use ListRecords\Concerns\Translatable;

    protected static string $resource = StatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
