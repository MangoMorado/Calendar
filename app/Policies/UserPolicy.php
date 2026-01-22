<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin(); // Solo Mango y Admin pueden ver la lista
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin(); // Solo Mango y Admin pueden ver usuarios
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin(); // Solo Mango y Admin pueden crear usuarios
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Mango puede editar a cualquier usuario, incluyéndose a sí mismo
        if ($user->isMango()) {
            return true;
        }

        // Admin no puede editarse a sí mismo (debe usar settings/profile)
        if ($user->id === $model->id) {
            return false;
        }

        return $user->isAdmin(); // Solo Mango y Admin pueden actualizar
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Mango puede eliminar a cualquier usuario excepto a sí mismo
        if ($user->isMango() && $user->id !== $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isMango();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isMango();
    }
}
