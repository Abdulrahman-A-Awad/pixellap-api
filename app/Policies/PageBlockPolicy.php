<?php

namespace App\Policies;

use App\Models\PageBlock;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Hand-made in Shield's format: this model is managed through a relation manager,
 * so Shield does not generate its policy. Permissions come from ShieldPermissionSeeder.
 */
class PageBlockPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_page::block');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PageBlock $pageBlock): bool
    {
        return $user->can('view_page::block');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_page::block');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PageBlock $pageBlock): bool
    {
        return $user->can('update_page::block');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PageBlock $pageBlock): bool
    {
        return $user->can('delete_page::block');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_page::block');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PageBlock $pageBlock): bool
    {
        return $user->can('force_delete_page::block');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_page::block');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PageBlock $pageBlock): bool
    {
        return $user->can('restore_page::block');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_page::block');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PageBlock $pageBlock): bool
    {
        return $user->can('replicate_page::block');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_page::block');
    }
}
