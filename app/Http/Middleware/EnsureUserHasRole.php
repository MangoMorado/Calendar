<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            abort(401, 'No autenticado');
        }

        $userRole = $request->user()->role;

        if (! $userRole) {
            abort(403, 'Usuario sin rol asignado');
        }

        // Convertir los roles de string a enum
        $allowedRoles = array_map(function ($role) {
            return Role::from($role);
        }, $roles);

        // Verificar si el usuario tiene alguno de los roles permitidos
        $hasRole = false;
        foreach ($allowedRoles as $role) {
            if ($userRole === $role) {
                $hasRole = true;
                break;
            }
        }

        // El rol Mango tiene acceso a todo
        if ($userRole === Role::Mango) {
            $hasRole = true;
        }

        if (! $hasRole) {
            abort(403, 'No tienes permisos para acceder a este recurso');
        }

        return $next($request);
    }
}
