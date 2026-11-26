<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceCategoryResource\Pages;

use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Resources\ServiceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceCategories extends ListRecords
{
    use RevalidatesOnReorder;

    protected static string $resource = ServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
