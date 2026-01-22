<?php

namespace App\Policies;

use App\Models\Calendar;
use App\Models\User;

class CalendarPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Todos los usuarios pueden ver la lista
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Calendar $calendar): bool
    {
        return true; // Todos los usuarios pueden ver un calendario
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin(); // Solo Mango y Admin pueden crear
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Calendar $calendar): bool
    {
        return $user->isAdmin(); // Solo Mango y Admin pueden actualizar
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Calendar $calendar): bool
    {
        return $user->isAdmin(); // Solo Mango y Admin pueden eliminar
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Calendar $calendar): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Calendar $calendar): bool
    {
        return $user->isAdmin();
    }
}
