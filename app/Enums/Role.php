<?php

namespace App\Enums;

enum Role: string
{
    case Mango = 'mango';
    case Admin = 'admin';
    case User = 'user';

    /**
     * Obtener todos los roles disponibles
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verificar si un rol tiene permisos de super administrador
     */
    public function isMango(): bool
    {
        return $this === self::Mango;
    }

    /**
     * Verificar si un rol tiene permisos de administrador
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin || $this === self::Mango;
    }

    /**
     * Verificar si un rol tiene permisos de usuario
     */
    public function isUser(): bool
    {
        return $this === self::User || $this->isAdmin();
    }

    /**
     * Obtener el nombre legible del rol
     */
    public function label(): string
    {
        return match ($this) {
            self::Mango => 'Mango',
            self::Admin => 'Administrador',
            self::User => 'Usuario',
        };
    }
}
