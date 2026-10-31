<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@proyectoapi.local'],
            [
                'name' => 'Administrador Hama',
                'password' => env('DEMO_ADMIN_PASSWORD', 'AdminHama2026!'),
                'role' => User::ROLE_ADMIN,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'usuario@proyectoapi.local'],
            [
                'name' => 'Usuario Hama',
                'password' => env('DEMO_USER_PASSWORD', 'UsuarioHama2026!'),
                'role' => User::ROLE_USER,
            ],
        );
    }
}
