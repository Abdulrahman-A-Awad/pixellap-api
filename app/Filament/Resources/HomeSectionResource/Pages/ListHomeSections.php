<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSectionResource\Pages;

use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Resources\HomeSectionResource;
use Filament\Resources\Pages\ListRecords;

class ListHomeSections extends ListRecords
{
    use RevalidatesOnReorder;

    protected static string $resource = HomeSectionResource::class;

    public function getTitle(): string
    {
        return __('Home Page');
    }
}
