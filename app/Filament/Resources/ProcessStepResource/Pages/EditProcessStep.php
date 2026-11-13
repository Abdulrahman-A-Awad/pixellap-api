<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcessStepResource\Pages;

use App\Filament\Resources\ProcessStepResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcessStep extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = ProcessStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
