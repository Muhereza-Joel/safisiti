<?php

namespace App\Policies;

use App\Models\RecycleRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecycleRecordPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_recycle::record');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecycleRecord $recycleRecord): bool
    {
        return $user->can('view_recycle::record');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_recycle::record');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecycleRecord $recycleRecord): bool
    {
        return $user->can('update_recycle::record');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecycleRecord $recycleRecord): bool
    {
        return $user->can('delete_recycle::record');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_recycle::record');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, RecycleRecord $recycleRecord): bool
    {
        return $user->can('force_delete_recycle::record');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_recycle::record');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RecycleRecord $recycleRecord): bool
    {
        return $user->can('restore_recycle::record');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_recycle::record');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, RecycleRecord $recycleRecord): bool
    {
        return $user->can('replicate_recycle::record');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_recycle::record');
    }
}
