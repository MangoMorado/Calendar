<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $users = User::latest()
            ->paginate(15);

        return Inertia::render('users/index', [
            'users' => $users,
            'canCreate' => $request->user()?->can('create', User::class) ?? false,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        $currentUser = auth()->user();
        $availableRoles = [Role::User];

        // Solo Mango puede crear usuarios Admin
        if ($currentUser->isMango()) {
            $availableRoles[] = Role::Admin;
        }

        return Inertia::render('users/create', [
            'availableRoles' => array_map(fn ($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ], $availableRoles),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $currentUser = auth()->user();
        $availableRoles = [Role::User];

        // Solo Mango puede asignar rol Admin y Mango
        if ($currentUser->isMango()) {
            $availableRoles[] = Role::Admin;
            $availableRoles[] = Role::Mango;
        }

        return Inertia::render('users/edit', [
            'user' => $user,
            'availableRoles' => array_map(fn ($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ], $availableRoles),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        // Solo actualizar la contraseña si se proporciona
        if (isset($data['password']) && ! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
