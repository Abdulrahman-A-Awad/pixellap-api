<?php

declare(strict_types=1);

namespace App\Filament\Resources\UiStringResource\Pages;

use App\Filament\Resources\UiStringResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUiStrings extends ManageRecords
{
    protected static string $resource = UiStringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalWidth('3xl'),
        ];
    }
}
