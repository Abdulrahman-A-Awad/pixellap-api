<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Filament\Support\PermissionMatrix;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    use InteractsWithPermissionMatrix;

    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();
        $data['matrix'] = PermissionMatrix::fromPermissions($role->permissions()->pluck('name'));

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        $attributes = ['description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null];
        if (! PermissionMatrix::isBuiltIn($record) && isset($data['name'])) {
            $attributes['name'] = trim((string) $data['name']);
        }

        $record->update($attributes);
        $this->syncMatrix($record);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalDescription(__('People with this role lose its permissions. Their accounts stay.')),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
