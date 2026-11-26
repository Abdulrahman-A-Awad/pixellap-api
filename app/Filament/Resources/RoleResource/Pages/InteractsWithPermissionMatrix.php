<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Support\PermissionMatrix;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Shared by CreateRole / EditRole: the matrix lives in $this->data['matrix'] (not a role column)
 * and is written with PermissionMatrix::sync() after the role is saved.
 * The "select all" checkboxes in the matrix view call the two toggle methods.
 */
trait InteractsWithPermissionMatrix
{
    /** @return array<string, array<string, bool>> */
    protected function matrixState(): array
    {
        $state = $this->data['matrix'] ?? [];

        return collect(PermissionMatrix::emptyState())
            ->map(fn (array $cells, string $area): array => collect($cells)
                ->map(fn (bool $off, string $action): bool => (bool) ($state[$area][$action] ?? false))
                ->all())
            ->all();
    }

    /** Row "select all": on unless every cell of the row is already on. */
    public function toggleMatrixRow(string $area): void
    {
        $state = $this->matrixState();
        if (! isset($state[$area])) {
            return;
        }

        $on = in_array(false, $state[$area], true);
        $state[$area] = array_map(fn (): bool => $on, $state[$area]);
        $this->data['matrix'] = $state;
    }

    /** Global "select all": on unless every cell is already on. */
    public function toggleMatrixAll(): void
    {
        $state = $this->matrixState();
        $on = collect($state)->flatten()->contains(false);

        $this->data['matrix'] = array_map(fn (array $cells): array => array_map(fn (): bool => $on, $cells), $state);
    }

    protected function syncMatrix(Role $role): void
    {
        PermissionMatrix::sync($role, $this->matrixState());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
