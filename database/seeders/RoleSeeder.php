<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario Mango (super administrador)
        User::firstOrCreate(
            ['email' => 'mango@example.com'],
            [
                'name' => 'Usuario Mango',
                'password' => Hash::make('password'),
                'role' => Role::Mango,
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario Admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => Role::Admin,
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario normal
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Usuario Normal',
                'password' => Hash::make('password'),
                'role' => Role::User,
                'email_verified_at' => now(),
            ]
        );
    }
}
