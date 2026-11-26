<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    use InteractsWithPermissionMatrix;

    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $role = Role::create([
            'name' => trim((string) $data['name']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'guard_name' => 'web',
        ]);

        $this->syncMatrix($role);

        return $role;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
