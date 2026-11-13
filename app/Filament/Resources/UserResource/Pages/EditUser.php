<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(), // hidden for yourself by UserPolicy
        ];
    }

    /** Server-side guard: you can never deactivate your own account. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->is(auth()->user())) {
            unset($data['is_active']);
        }

        return $data;
    }
}
