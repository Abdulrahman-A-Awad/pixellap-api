<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcessStepResource\Pages;

use App\Filament\Resources\ProcessStepResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProcessStep extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = ProcessStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
